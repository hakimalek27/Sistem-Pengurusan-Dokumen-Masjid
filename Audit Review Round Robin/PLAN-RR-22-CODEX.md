# Pusingan 22 (Pelan) — Codex: Audit v1.9

Tarikh: 2026-08-02  
Asas kod yang disahkan: `8342d95`  
Skop: perancangan dan bacaan sahaja; tiada kod aplikasi, git mutation, SSH atau produksi.

## Integriti input (hash 2x + status kerja dua-fakta)

`PELAN-PEMBAIKAN.md` disemak dua kali sebelum audit:

| Semakan | SHA-256 | Saiz | LF | mtime |
|---|---|---:|---:|---|
| #1 | `487EDBE64DE7EE9A3D2DB64039B0D73F317B897AAE5602A89467A976E4B05E96` | 311,998 B | 4,072 | `2026-08-02 06:15:57.4880648 +08:00` |
| #2 | `487EDBE64DE7EE9A3D2DB64039B0D73F317B897AAE5602A89467A976E4B05E96` | 311,998 B | 4,072 | `2026-08-02 06:15:57.4880648 +08:00` |

Semakan ketiga tepat sebelum tulisan pertama juga identik. Kiraan LF dibuat daripada bait `0x0A`, bukan `Measure-Object -Line`.

Dua fakta status kerja, dilapor berasingan seperti §0.7 #7:

1. `git status --short -- app resources tests e2e config .github docker composer.json composer.lock package.json package-lock.json` → **0 baris**.
2. `git status --short` → **25 baris**: `M HANDOVER.md` + **24** fail perancangan `??`. `git log -1 --format='%h %ci'` → `8342d95 2026-08-01 14:51:41 +0800`.

Pra-tulis: `PLAN-RR-22-CODEX.md` tidak wujud. `PLAN-RR-STATUS.md` = `C717335614465BB672EBFB46DE902E7C2B1C0BF31632827F18B8D9FFE5ACC4CB`, 28,830 B, mtime `2026-08-02 07:29:44.8903199 +08:00`.

## Titik 1 — §1 F0(iv)(d-1) YAML literal dan `ServeCommand`

**Verdict: YAML operasional tepat, tetapi justifikasi `$_ENV` substantifnya salah/terlalu mutlak; P23 mesti betulkan teks tanpa semestinya membuang `--no-reload`.**

- Nilai workflow sumber disahkan: `.github/workflows/ci.yml:18-21` job `integration`, `:22-51` PostgreSQL/Redis/Meili v1.12, `:52-80` env (`APP_URL=:8080`, `SESSION_DRIVER=array`, `SCOUT_DRIVER=collection`), `:82-121` setup/build, `:128-132` Pest, `:134-148` smoke. Blok pelan `PELAN-PEMBAIKAN.md:1174-1259` masuk di lokasi dan urutan yang betul. Env server dan klien dipisahkan dengan betul; cleanup step berasingan juga betul.
- `composer.lock:3107-3112` memin `laravel/framework v12.63.0`. Vendor sebenar mengisytihar `--no-reload` pada `ServeCommand.php:428-435`; cabang `:181-189` memang memetakan ahli `$_ENV` bukan-passthrough kepada `false` apabila `.env` wujud, dan `--no-reload` memang memintas cabang itu.
- Namun setup-php menggunakan `php.ini-production` secara lalai, dan PHP production/development menetapkan `variables_order=GPCS`. Probe pada PHP CLI mesin ini menghasilkan `GPCS`, `array_key_exists('PATH', $_ENV) = false`, tetapi `getenv('PATH') !== false = true`. Symfony Process kemudian menggabungkan env lalai daripada `getenv()` (`vendor/symfony/process/Process.php:1688-1695`) selepas env constructor (`:327-332`); hanya kunci yang benar-benar hadir sebagai `false` dibuang (`:355-363`). Jadi pada runner lalai yang `$_ENV` kosong, `APP_URL`/`SESSION_DRIVER` diwarisi melalui `getenv()` dan **tidak dibuang**. Dakwaan aktif pelan `:1263-1272`, `:1524`, `:1637` bahawa CI terselamat *hanya* kerana `.env` tiada tidak terbukti dan dijangka salah pada konfigurasi runner ini.
- `--no-reload` masih pilihan defensif yang munasabah: ia menjadikan tingkah laku bebas daripada `variables_order` dan mengelak restart `.env`. Pindaan perlu memakukan `ini_get('variables_order')`/kehadiran `$_ENV` sebagai bukti CI pertama atau membetulkan naratif kepada syarat “jika `E` diaktifkan”.

Keutamaan: **P1 fakta kontrak**, kerana teks kini menganggap satu mekanisme keselamatan yang berbeza daripada runtime runner.

## Titik 2 — Gate Meilisearch

**Verdict: bentuk semasa deterministik untuk kontrak yang dipin; tiada pindaan substantif diperlukan.**

- `composer.lock:4848-4853` memin `meilisearch/meilisearch-php v1.16.1`; server CI dipin `getmeili/meilisearch:v1.12` (`ci.yml:45-49`).
- `SyncHelpIndex.php:71-78` menghantar empat task dan menunggu semua UID. Vendor `Tasks.php:43-58,64-72` hanya kembali apabila status bukan `enqueued`/`processing`; command menolak `failed` pada `SyncHelpIndex.php:79-82`, lalu membaca `stats()` dan mengassert count tepat pada `:83-86` sebelum mencetak ayat kejayaan `:87`.
- Oleh itu `grep -qF` pada `PELAN-PEMBAIKAN.md:1188-1194` bukan pengganti `stats()`; ia pengikat tambahan bahawa denominator literal 83 dan UID indeks yang dijangka benar-benar dicapai. `bash -eo pipefail` memastikan `tee` tidak menutup exit command. Tiada sebab menambah polling kedua tanpa bukti flake sebenar.

Nota kecil: risiko lag `:1292-1297` boleh kekal sebagai pemerhatian, tetapi jangan anggap flake sebelum larian CI membuktikannya.

## Titik 3 — Assertion `stats.skipped === 0`

**Verdict: sah dan tidak menolak larian sah yang ditapis oleh `--grep`/project.**

- Versi dipasang ialah `@playwright/test`/`playwright 1.61.1` (`package-lock.json:763-766,2449-2452`). JSON reporter membina statistik hanya daripada `this.suite.allTests()` dan menaikkan kaunter berdasarkan `test.outcome()` (`node_modules/playwright/lib/runner/index.js:3916-3929`). Ujian yang dibuang oleh discovery/project/`--grep` tidak berada dalam suite larian dan tidak menambah `skipped`.
- `test.describe.configure()` sendiri tidak mencipta skip. Dalam mod serial, ujian baki boleh menjadi skip selepas kegagalan, tetapi larian itu sudah tidak sah (`unexpected`/error); assertion `skipped=0` memberi diagnosis tambahan yang betul.
- Skip eksplisit/fixme/setup-failure memang menghasilkan outcome `skipped` (`runner/index.js:5410-5451,5543-5555`) dan patut menggagalkan gate. Canary `--grep @session-canary` tidak memerlukan pengecualian.

## Titik 4 — Jadual A/B dan nama check matriks

**Verdict: nama dirancang dan kaedah pengesahan betul.**

- Job sumber ialah `docker` dengan `name: Docker ${{ matrix.target }} image` dan matriks `[app, web]` (`ci.yml:159-167`), maka dua nama UI/check yang dijangka ialah `Docker app image` dan `Docker web image`, seperti `PELAN-PEMBAIKAN.md:1368-1377`.
- Endpoint `GET /repos/{owner}/{repo}/commits/{ref}/check-runs` memang endpoint GitHub Checks untuk commit/ref, dan medan `.check_runs[].name` ialah data yang relevan. Command `gh api .../commits/$(git rev-parse HEAD)/check-runs --jq ...` (`PELAN-PEMBAIKAN.md:1408-1417`) sesuai dijalankan **selepas** larian PR F0 seperti dinyatakan.
- Jumlah check yang dirancang jauh di bawah halaman lalai 30, jadi pagination bukan isu kini. Jika workflow kelak melebihi 30 check, tambah `--paginate` sebelum menggunakannya sebagai inventori lengkap.

Tiada akses repo/`gh api` dijalankan dalam P22 kerana sekatan perancangan; keputusan ini berdasarkan workflow tempatan dan kontrak REST GitHub.

## Titik 5 — `storage/app/plan-*` dan upload apabila gagal awal

**Verdict: nama direktori `plan-*` tidak bertembung, tetapi dua dakwaan pelan perlu diperbetul; satu daripadanya substantif kepada kebolehdiagnosan CI.**

- `.gitignore` direktori memang mengabaikan subdirektori baharu (`storage/app/.gitignore:1-4`). Tiada padanan sedia ada untuk literal `storage/app/plan-ci` atau `storage/app/plan-f6` dalam kod produk/ujian/command.
- Namun dakwaan `PELAN-PEMBAIKAN.md:1462-1466` bahawa kod produk tidak menyentuh `storage/app/` di luar `private/`/`public/` adalah salah. Bukti: `config/filesystems.php:54` menggunakan `storage/app/manual-capture`; `config/backup.php:185` menggunakan `storage/app/backup-temp`; `ProcessOcrJob.php:68,118` dan `ExportService.php:21` menggunakan `storage/app/tmp/*`. Ini tidak bertembung dengan awalan `plan-*`, jadi pilihan lokasi masih boleh dikekalkan selepas naratif dikecilkan kepada “tiada pengguna `plan-ci`/`plan-f6`”.
- `if: always()` + `if-no-files-found: error` pada upload lapis 1 (`PELAN-PEMBAIKAN.md:1251-1258`) akan menambah kegagalan upload apabila canary/Playwright gagal sebelum reporter sempat menulis JSON. Kegagalan asal masih wujud dalam step terdahulu, tetapi step terakhir yang merah boleh mengalih perhatian kepada “No files were found” dan menutup diagnosis utama dalam ringkasan. Untuk job yang **sudah gagal**, ketiadaan fail ialah akibat, bukan gate baharu.
- P23 patut membekukan syarat: `error` hanya apabila step Playwright selesai success tetapi fail bukti hilang; pada kegagalan terdahulu, upload gunakan `warn`/condition kewujudan sambil mengekalkan log asal. Assertion script selepas larian kekal gate utama bagi “command success tetapi JSON hilang”. Corak sama perlu dinilai pada upload shard dan agregator.

Keutamaan: **P1 kebolehdiagnosan CI** untuk semantik upload; pembetulan skop storage ialah **P2 fakta**.

## Titik 6 — Kiraan D11 = 16 fail + 1 artifak

**Verdict: kontrak tidak menyeludup fail baharu melalui v1.9, tetapi label/kiraan “16 fail” tidak benar selepas D11 diluluskan semua; P23 mesti menormalkan inventori kepada fail sebenar.**

- Pindaan v1.9 memang hanya menyentuh entri sedia ada: `playwright.config.js`, `.github/workflows/ci.yml`, dan `scripts/audit/assert-playwright-json.mjs`. `actions/setup-node@v5` ialah satu step dalam `ci.yml`, bukan fail repo. `tee storage/logs/*.log` ialah output transient yang sudah berada di bawah `storage/`, bukan fail repo.
- Masalahnya sudah ada dalam jadual `PELAN-PEMBAIKAN.md:1749-1767`: baris #13 mengandungi **dua fail** (`targets.json` + `HELP-TARGETS.md`), dan #16 ialah wildcard sekurang-kurangnya **“2 imej + istilah”**, bukan satu fail. Maka #1–#15 bersamaan **16 fail fizikal**; apabila pemilik meluluskan “semua” D11 (`KEPUTUSAN-PEMILIK.md:25-36`), #16 menambah sekurang-kurangnya tiga fail lagi → **sekurang-kurangnya 19 fail repo**, bukan 16. Baris #17 pula menyebut `manifest.json + tools/`, jadi “1 artifak audit” juga sebenarnya satu **bundle/entri**, bukan semestinya satu fail.
- Ini bukan penyeludupan oleh `setup-node` atau `tee`; ia ralat unit kiraan (entri skop dipanggil fail). Kerana keputusan pemilik direkod atas frasa “16 fail + 1 artifak”, P23 mesti: (a) perluaskan wildcard kepada nama fail exact, (b) beri kiraan fail fizikal exact, (c) bezakan item bersyarat jika ada—tetapi D11 kini “lulus semua”, dan (d) catat bahawa skop kandungan yang diluluskan tidak berubah, hanya kiraan/label dibetulkan.

Keutamaan: **P1 integriti skop pemilik**.

## Imbasan corak lapuk + konsistensi selepas D1-D11 dijawab

Hasil `rg -n -F`:

- `tepat tiga`: padanan aktif = 0; baki `PELAN-PEMBAIKAN.md:1362-1364,1380` ialah sejarah/pembatalan.
- `bukti/plan-ci`: padanan aktif = 0; baki `:1426,1452` menerangkan lokasi lama yang ditolak.
- `trap '`: 0 dalam pelan; `ci.yml:141` kekal sah untuk Horizon dalam satu step.
- `--no-reload`: padanan aktif banyak dan konsisten, tetapi justifikasi `$_ENV` perlu pindaan Titik 1.
- `storage/app/plan-`: padanan aktif konsisten; pembetulan fakta/diagnostik ada pada Titik 5.
- Keputusan pemilik kini lengkap (`KEPUTUSAN-PEMILIK.md:25-36`), tetapi pelan masih lapuk pada `PELAN-PEMBAIKAN.md:20` (“jawapan pemilik D1-D11” sebagai prasyarat belum dipenuhi), `:578` (“D11 mesti dijawab”), tajuk §11 `:3917` (“Keputusan pemilik diperlukan”), nota `:3934-3941`, dan §12 `:3952` (“selepas D11 dijawab”). Ini isu konsistensi wajib P23. `PLAN-RR-STATUS.md:126-127` dan `PLAN-RR-21-CLAUDE.md:302-304` juga lapuk, tetapi status akan dibetulkan dalam serahan P22; laporan P21 ialah rekod sejarah dan tidak perlu ditulis semula.
- `Lampiran A1` (`PELAN-PEMBAIKAN.md:3991`) masih benar-benar menunggu tindakan pemilik dan bukan keputusan D1-D11; jangan padam secara pukal.

## KEPUTUSAN: (b) PERLU v1.10

Isu substantif, mengikut keutamaan:

1. **P1 — D11 salah unit/kiraan:** “16 fail + 1 artifak” sebenarnya sekurang-kurangnya 19 fail repo selepas semua item diluluskan; wildcard dan bundle mesti dieksplisitkan.
2. **P1 — Semantik upload mengaburkan kegagalan asal:** `if-no-files-found: error` tanpa syarat pada upload `if: always()` perlu dibezakan antara “ujian berjaya tetapi bukti hilang” dan “ujian gagal sebelum bukti wujud”.
3. **P1 — Fakta `ServeCommand`/runner:** `--no-reload` boleh dikekalkan, tetapi dakwaan bahawa runner membuang semua override apabila `.env` wujud mesti mengambil kira `variables_order=GPCS`, `$_ENV` kosong, dan fallback `getenv()` Symfony Process.
4. **P2 — Fakta storage:** kod memang menggunakan `storage/app/manual-capture`, `backup-temp` dan `tmp`; keselamatan pilihan bergantung pada awalan `plan-*` yang unik, bukan eksklusiviti `private/public`.
5. **P2 — Konsistensi keputusan pemilik:** semua ayat aktif yang masih berkata D1-D11/D11 menunggu jawapan perlu ditukar kepada “telah diluluskan”, tanpa mengubah Lampiran A1 yang masih menunggu tindakan berasingan.

Titik Meilisearch, `stats.skipped`, nama check matriks dan kaedah `gh api` tidak memerlukan pindaan substantif.

## Integriti output (hash fail yang ditulis)

Hash selepas tulisan pertama direkod selepas seksyen ini diwujudkan; hash akhir selepas catatan integriti ditambah direkod secara autoritatif dalam `PLAN-RR-STATUS.md` kerana hash penuh tidak boleh dimasukkan ke dalam fail yang dihash tanpa mengubah hash itu sendiri.

- Snapshot selepas tulisan pertama: SHA-256 `E8176248D032C56B33F7CE48DEB666441E20A620FD929FADBE1CD2F17CF83643`, 12,950 B, 111 LF, mtime `2026-08-02 07:33:41.0474175 +08:00`.
- Fail yang ditulis dalam P22 hanya `PLAN-RR-22-CODEX.md` dan `PLAN-RR-STATUS.md`. `PELAN-PEMBAIKAN.md` serta kod aplikasi tidak disentuh.

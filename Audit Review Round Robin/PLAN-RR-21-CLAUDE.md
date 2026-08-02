# Pusingan 21 — Claude: keputusan P20-01…P20-06 + integrasi `PELAN-PEMBAIKAN.md` v1.9

**Tarikh:** 2 Ogos 2026 · **Asas kod:** `8342d95`
**Fail dinilai:** `PLAN-RR-20-CODEX.md`, `PELAN-PEMBAIKAN.md` v1.8, `PLAN-RR-19-CLAUDE.md`,
`PLAN-RR-STATUS.md` + kod/vendor yang dirujuk P20 (baca sahaja)
**Keputusan ringkas:** **5 TERIMA · 1 TERIMA SEBAHAGIAN (P20-06) · 0 TOLAK**
(dua daripadanya **TERIMA + DIKUATKAN** dengan bukti kod yang P20 tidak kemukakan)
**Status pelan:** ⏳ **v1.9 — BELUM MUKTAMAD.** Diserah kepada **Codex Pusingan 22**.

---

## 1. Integriti fail (§0.7 #1 — dua semakan sebelum suntingan pertama)

| Semakan | Masa | SHA-256 `PELAN-PEMBAIKAN.md` | Saiz | Baris (LF) | mtime |
|---|---|---|---|---|---|
| Pra-tulis #1 | 2026-08-02 06:02 | `68F00B235775CE825C6E93E2D913FA5211716F6FF1C94FEF5AD7CD710F84E711` | 280 276 B | 3 673 | 2026-08-02 05:45:10 |
| Pra-tulis #2 | 2026-08-02 06:06:22 | `68F00B23…0F84E711` (**identik**) | 280 276 B | 3 673 | 2026-08-02 05:45:10 |

✅ Sepadan **tepat** dengan nilai beku dalam arahan P21 dan dengan nilai yang Codex P20 rekod
(`PLAN-RR-20-CODEX.md:11-12`). Tiada penulisan proses lain dikesan; tiada sebab untuk berhenti.
Kiraan baris menggunakan bilangan `\n` (`[regex]::Matches($teks,"\n").Count`), fail **LF sahaja**
(0 CRLF) — kaedah yang sama seperti P19, supaya angka boleh dihasilkan semula.

`PLAN-RR-20-CODEX.md` = `E8924882D08DBFFE2EE09022E3CA926340FEC753D9A053EBE8C90F12C6DC5827`
(11 373 B) — **sepadan** nilai beku `PLAN-RR-STATUS.md:47`.

**Selepas suntingan P21:**

| Fail | SHA-256 | Saiz | Baris (LF) | mtime |
|---|---|---|---|---|
| `PELAN-PEMBAIKAN.md` (**v1.9**) | `487EDBE64DE7EE9A3D2DB64039B0D73F317B897AAE5602A89467A976E4B05E96` | 311 998 B | 4 072 | 2026-08-02 06:15:57 |

Delta: **+31 722 B · +399 baris**.

### 1.1 Status kerja — dua dakwaan berasingan (§0.7 #7)

```text
$ git status --short -- app resources tests e2e config .github docker composer.json composer.lock package.json package-lock.json
(0 baris)

$ git status --short
 M HANDOVER.md
?? "Audit Review Round Robin/CLAUDE-P17-PROMPT.txt"
?? "Audit Review Round Robin/KEPUTUSAN-PEMILIK.md"
?? "Audit Review Round Robin/PELAN-PEMBAIKAN.md"
   … (22 entri `??` semuanya dalam `Audit Review Round Robin/`)

$ git log -1 --format="%h %ci"
8342d95 2026-08-01 14:51:41 +0800
```

**Tafsiran yang betul:** tiada perubahan kod aplikasi dalam giliran P21; working tree
**keseluruhan tidak bersih** kerana `HANDOVER.md` diubah dan 22 fail perancangan belum dijejak.
(`PLAN-RR-21-CLAUDE.md` menjadikannya 23 selepas fail ini ditulis.)

### 1.2 Snapshot §0.7 #2 — masih TERTUNGGAK

P21 juga dihadkan kepada tiga fail dan **dilarang menjalankan git add/commit/push**, jadi snapshot
immutable belum pernah wujud (P15, P17, P18, P19, P20, P21). §0.7 #4 kekal terpakai: setiap
keputusan di bawah disandarkan pada **kod/vendor semasa** atau pada teks v1.8 yang **masih hadir**
dalam fail dan hashnya direkod di atas.

---

## 2. Keputusan setiap penemuan P20

### P20-01 — Lapis 1 `integration` bukan YAML boleh-tampal → **TERIMA + DIKUATKAN**

**Premis P20 disahkan sepenuhnya.**

- `.github/workflows/ci.yml:56` = `APP_URL: http://127.0.0.1:8080` dan `:67` =
  `SESSION_DRIVER: array`, kedua-duanya pada aras **job** — jadi ia diwarisi oleh **setiap** step,
  termasuk mana-mana step yang melancarkan `php artisan serve`.
- Env pada step Playwright tidak boleh mengubah proses server yang sudah berjalan. Betul.
- Bahaya `trap … EXIT` betul: setiap `run:` GitHub Actions ialah shell berasingan. Corak `trap`
  pada `ci.yml:141` (Horizon) sah kerana Horizon dilancar **dan** diguna dalam **satu** step —
  server e2e tidak.

**Penguatan (P20 tidak menemuinya) — override boleh hilang walaupun diletak pada step yang
betul.** `vendor/laravel/framework/src/Illuminate/Foundation/Console/ServeCommand.php:181-189`:

```php
$process = new Process($this->serverCommand(), public_path(), (new Collection($_ENV))->mapWithKeys(
    function ($value, $key) use ($hasEnvironment) {
        if ($this->option('no-reload') || ! $hasEnvironment) {
            return [$key => $value];
        }
        return in_array($key, static::$passthroughVariables) ? [$key => $value] : [$key => false];
    })->merge(['PHP_CLI_SERVER_WORKERS' => $this->phpServerWorkers])->all());
```

`$passthroughVariables` (`:79-94`) mengandungi **`APP_ENV`, PATH, SYSTEMROOT, Herd/Xdebug/
Ignition/Sail sahaja** — **`APP_URL`, `SESSION_DRIVER`, `DB_*`, `E2E_*` TIADA di dalamnya**.
Maka apabila fail `.env` wujud dan `--no-reload` tidak diberi, setiap override ditetapkan `false`
(dibuang daripada proses server).

Hari ini CI terselamat **hanya secara kebetulan**: `grep -n "\.env" .github/workflows/ci.yml`
memulangkan **0 padanan**, jadi `$hasEnvironment === false` dan cabang `:184` meluluskan semua env.
Gate yang bergantung pada **ketiadaan** sesuatu boleh dipecahkan oleh satu baris
`cp .env.example .env` yang ditambah kemudian atas sebab yang tiada kaitan — dan kegagalannya akan
kelihatan sebagai pepijat log masuk, bukan konfigurasi. **`php artisan serve --no-reload`**
(`:434` mengisytiharkan opsyen itu) menjadikan cabang `:184` benar tanpa syarat.

**Diintegrasi:** **§1 F0(iv)(d-1) baharu** — YAML literal penuh bagi enam step yang ditambah ke
job `integration`, dengan tempat sisipan exact (`ci.yml:128-132` → sebelum `:134-148`), env server
vs env klien dipisahkan, `--no-reload`, cleanup step `if: always()`, dan empat keputusan
tak-boleh-ubah + risiko yang diisytihar. Selain itu: YAML (d) `guidance-e2e` mendapat step cleanup
yang sama; bullet naratif lapis 1 (serve + "SESI HTTP") ditulis semula; §0.5e; log versi.

---

### P20-02 — Gate Meilisearch masih nota, bukan command CI → **TERIMA**

**Setiap fakta kod P20 disahkan:**

| Dakwaan P20 | Semakan |
|---|---|
| `SyncHelpIndex` pulang awal jika driver bukan Meili | ✅ `SyncHelpIndex.php:38-42` — `warn(...)` + `return self::SUCCESS` |
| CI hanya menjalankannya dengan `collection` | ✅ `ci.yml:123-126` (`SCOUT_DRIVER: collection`) |
| `HelpSearchService` fallback boleh menyembunyikan indeks rosak | ✅ `:24` menyemak **`MEILISEARCH_HOST` sahaja** (bukan `scout.driver`), `:37-39` `catch (Throwable)` → `collect()`, `:42-45` fallback PHP + `$engine = 'php'` |

**Nuansa yang dijelaskan dalam integrasi:** command sedia ada **sudah** melakukan separuh gate —
`waitForTasks` (`:78`) dan throw pada `$indexed !== count($documents)` (`:83-86`). Jadi yang
diperlukan bukan kod baharu, hanya **penjadualan** + mengikat angka **83** itu sendiri. Bentuk
beku: step `Meilisearch help index gate` dengan
`SCOUT_DRIVER=meilisearch php artisan diwan:sync-help-index --delete | tee …` diikuti
`grep -qF '83 guide disegerakkan ke indeks diwan_help_guides.'`. Nama indeks disahkan daripada
`config/diwan.php:10` (`DIWAN_HELP_INDEX`, lalai `diwan_help_guides`); teks output daripada
`SyncHelpIndex.php:87`. Shell lalai GitHub Actions untuk `bash` ialah `-eo pipefail`, jadi `| tee`
tidak menelan exit code.

**Risiko yang diisytihar (tidak disembunyikan):** `$index->stats()` dibaca sejurus selepas
`waitForTasks`; jika Meilisearch v1.12 melaporkan `numberOfDocuments` dengan lag, gate ini akan
flake. Pelan menyatakan pembetulan yang **dibenarkan** (gelung tunggu dalam command — yang akan
menjadi fail D11 baharu dan mesti diisytihar) dan yang **dilarang** (menurunkan assertion kepada
"≥1 dokumen").

**Diintegrasi:** §1 F0(iv)(d-1) step 1 + keputusan #3; bullet "gate Meilisearch (C20)" lapis 1
ditulis semula; command literal tempatan #0; **§9.2 ditulis semula** — rumusan v1.8 bahawa gate
diletak pada `Runtime compatibility smoke` **dibatalkan** (step itu menjalankan Horizon/health/
staging-check, `ci.yml:134-148`, bukan sync indeks).

---

### P20-03 — JSON `guidance-full` tidak di-assert → **TERIMA**

**Disahkan pada YAML v1.8:** step canary `:1022-1028` memanggil skrip assert; step
`Guidance full gate shard` `:1030-1033` menetapkan `DIWAN_PW_JSON` tetapi **tidak** memanggilnya.
Jurang tepat pada gate paling penting.

**Cadangan `stats.skipped === 0` diterima**, dan dimasukkan sebagai **assertion (7)** yang
melengkapi (bukan menggantikan) pemeriksaan `result.status` sedia ada: (3) merentasi pokok
`suites[]` secara rekursif dan bergantung pada bentuk struktur, manakala (7) ialah satu bacaan
aras-atas. Mengekalkan kedua-duanya bermakna satu perubahan skema tidak boleh mematikan gate
secara senyap.

**Nota bukan-isu P20 juga DITERIMA dan dibaiki.** Reporter JSON Playwright memang mencipta
direktori induk secara rekursif — disahkan bebas pada mesin ini:
`node_modules/playwright/lib/runner/index.js:4061-4065`
(`await fs.promises.mkdir(path.dirname(resolvedOutputFile), { recursive: true })`). Ayat v1.8
*"reporter JSON gagal jika direktorinya tiada"* **salah** dan dibuang; `mkdir -p` dikekalkan dengan
sebab yang betul (spec shard + agregator menulis failnya sendiri tanpa `mkdir`).

**Diintegrasi:** YAML (d) step shard (+`assert-playwright-json.mjs --min-tests 1`);
§1 F0(iv)(e) assertion **(7)** + ayat "setiap gate Playwright tanpa kecuali"; nota kebolehjalanan
(d) dibetulkan; §0.5e.

---

### P20-04 — `storage/app/plan-ci/*.json` tidak di-upload/retain → **TERIMA**

**Disahkan:** YAML v1.8 hanya memuat naik `shard-*.json` (`:1035-1040`) dan `coverage-gate.json`
(`:1058-1062`); CI sedia ada hanya memuat naik `storage/logs/*.log` **pada kegagalan**
(`ci.yml:150-157`). Sementara itu §9 metrik dan §10 langkah 1 memanggil JSON Playwright sebagai
**artifak bukti** — bukti yang tidak pernah meninggalkan runner.

**Diintegrasi:** upload `if: always()` ditambah pada **kedua-dua** lapis —
`ci-playwright-json` (lapis 1, `storage/app/plan-ci/*.json`) dan `guidance-pw-json-<shard>`
(lapis 2). Semua menggunakan `if-no-files-found: error` + `retention-days: 14`;
`coverage-gate.json` turut dinaikkan daripada tiada-syarat kepada `error`. **Satu-satunya
pengecualian** ialah `ci-ocr` sebelum fixture dikomit (`ignore`), dan pengecualian itu ditulis
secara eksplisit supaya ia tidak merebak.

---

### P20-05 — Senarai required check bercampur bilangan dan jenis → **TERIMA**

**Disahkan:** `PELAN-PEMBAIKAN.md:1133` (v1.8) berkata *"tepat tiga"* lalu menyenaraikan **empat**
nama, kerana Docker ialah **dua** check berasingan — `ci.yml:160`
`name: Docker ${{ matrix.target }} image` × `matrix.target: [app, web]`. §10 pula mencampurkan
check, step `ci-domain`, tiga shard dan gate dalam satu ayat "kesemua empat".

**Diintegrasi:** §1 F0(iv)(f) #3 **ditulis semula sebagai dua jadual berasingan** —
**A1–A4** (required branch protection: `PostgreSQL, Redis, Meili, OCR and tests` ·
`guidance-e2e-gate` · `Docker app image` · `Docker web image`, setiap satu dengan sumbernya) dan
**B1–B8** (bukti keluaran: step `ci-domain`, canary, gate Meili, tiga shard, artifak JSON,
agregator, imej Docker). Frasa "tepat tiga" **diisytihar DIBATALKAN** dalam teks. §10 langkah 1
ditulis semula mengikut pembahagian yang sama. Sebab shard **tidak** dimasukkan ke A diterangkan
(penguatkuasaan berganda + tetapan repo terikat kepada nilai `matrix.shard`), dan ia direkod
sebagai Lampiran B #16 supaya keputusan itu tidak "hilang" sebagai terlupa.

---

### P20-06 — Lokasi/ignore root `bukti/plan-*` → **TERIMA SEBAHAGIAN**

**Masalah diterima sepenuhnya.** Root `.gitignore` (34 baris, disemak penuh) tidak mengandungi
`/bukti`; §9.3 pelan sendiri berkata *"jangan cipta folder `bukti/` lain"*. v1.8 memang bercanggah
dengan disiplinnya sendiri.

**Kedua-dua pilihan utama P20 DITOLAK dengan bukti:**

1. **`test-results/plan-ci` — berbahaya, bukan neutral.** `/test-results` memang diabaikan
   (`.gitignore:20`), tetapi ia ialah `outputDir` **lalai** Playwright
   (`node_modules/playwright/lib/program.js:190`), dan Playwright **memadam** `outputDir` pada
   permulaan setiap larian: `createRemoveOutputDirsTask()` → `removeFolders([outputDir])`
   (`node_modules/playwright/lib/runner/index.js:5943-5962`), dilangkau hanya oleh
   `preserveOutputDir`. Lapis 1 menjalankan **tiga** larian berturut-turut dalam job yang sama
   (canary → `ci-guidance` → `ci-domain`), jadi `ci-canary.json` akan dipadam sebelum sempat
   dimuat naik. Menerima cadangan ini akan mencipta kegagalan yang **kelihatan seperti** ujian
   di-skip — tepat penyakit yang F0(iv)(e) wujud untuk merawat.
2. **`Audit Review Round Robin/bukti/plan-ci/`** — memaksa CI menulis ke folder perancangan
   bernama-berruang (setiap laluan perlu dipetik dalam YAML dan bash), mencampurkan output mesin
   dengan dokumen keputusan manusia, dan menghasilkan diff bising pada setiap larian tempatan.
3. **Menambah entri `.gitignore`** — berkesan, tetapi ia menambah **fail repo ke-17** kepada D11
   semata-mata untuk menampung folder yang tidak perlu wujud, dan D11 sedang menunggu jawapan
   pemilik.

**Pilihan yang diambil (bukan antara tiga yang P20 tawarkan — sebab itu TERIMA SEBAHAGIAN):**
`storage/app/plan-ci/` dan `storage/app/plan-f6/`.

| Semakan | Hasil |
|---|---|
| Sudah diabaikan git? | ✅ `storage/app/.gitignore` = `*` · `!private/` · `!public/` · `!.gitignore` — sub-direktori baharu diabaikan tanpa perubahan |
| Dipadam oleh Playwright? | ❌ tidak — di luar `outputDir` |
| Bertindih disk aplikasi? | ❌ tidak — disk `local` berakar `storage/app/private`, `public` pada `storage/app/public` (`config/filesystems.php:35`, `:43`) |
| Menambah fail D11? | ❌ tidak — **D11 kekal 16 fail repo + 1 artifak audit** |

**Diintegrasi:** **§1 F0(iv)(g) baharu** (jadual keputusan tiga-hala + sebab penolakan setiap
alternatif + semakan keselamatan `storage/app/`); semua laluan dalam YAML (d)/(d-1), jadual (a)/(c),
command literal lapis 1, syarat `ci-ocr`, §9 metrik, §10 langkah 1; `bukti/plan-f8` dinaikkan ke
laluan penuh `Audit Review Round Robin/bukti/plan-f8/` (ia bukti **dikomit**, bukan transient);
§9.3; §1 F0(iv-a) nota kiraan; §11 D11; Lampiran B #18.

---

## 3. Lokasi integrasi (ringkasan pemetaan)

| Penemuan | Seksyen yang diubah dalam `PELAN-PEMBAIKAN.md` v1.9 |
|---|---|
| P20-01 | **§1 F0(iv)(d-1) baharu** (~80 baris YAML + 4 keputusan + risiko) · §1 F0(iv)(d) step cleanup · bullet lapis 1 "serve" + "SESI HTTP" ditulis semula · nota kebolehjalanan (d) · log versi |
| P20-02 | §1 F0(iv)(d-1) step `Meilisearch help index gate` + keputusan #3 · bullet "gate Meilisearch (C20)" · command literal #0 · **§9.2 ditulis semula** |
| P20-03 | §1 F0(iv)(d) step shard · **§1 F0(iv)(e) assertion (7)** + ayat liputan · nota kebolehjalanan (d) (ayat `mkdir` salah dibuang) |
| P20-04 | §1 F0(iv)(d) dua upload baharu · §1 F0(iv)(d-1) upload · syarat `ci-ocr` (pengecualian `ignore`) |
| P20-05 | **§1 F0(iv)(f) #3 ditulis semula** (jadual A1–A4 + B1–B8) · #4 · #5 · "Gate:" F0(iv) · §10 langkah 1 · Lampiran B #16 |
| P20-06 | **§1 F0(iv)(g) baharu** · semua laluan YAML/command · §9 metrik · §9.3 · §1 F0(iv-a) nota · §11 D11 · Lampiran B #18 |
| Semua | **§0.5e baharu** — jadual keputusan P20-01…P20-06 dengan bukti dan lokasi · header versi · footer · log versi |

**Pembetulan sampingan (bukan daripada P20):** log versi v1.8 dan §0.5d merujuk *"§0.7 #8"*
bagi peraturan "working tree bersih"; peraturan itu sebenarnya **#7** dalam §0.7 (yang berakhir
pada #7). Kedua-dua rujukan dibetulkan.

---

## 4. Imbasan konsistensi (berprogram, selepas suntingan)

| # | Corak dicari | Hasil |
|---|---|---|
| 1 | `bukti/plan-ci` sebagai **arahan/laluan aktif** | **0** — 4 padanan tinggal semuanya teks yang menerangkan pemindahan (§0.5e P20-04/06, §1 F0(iv)(g) dua kali) |
| 2 | `bukti/plan-f6` sebagai **laluan aktif** | **0** — 2 padanan, kedua-duanya penjelasan |
| 3 | `tepat tiga` sebagai **arahan** | **0** — 5 padanan semuanya mengisytiharkannya **dibatalkan** |
| 4 | `trap '` (mana-mana trap dalam pelan) | **0** — arahan trap dibuang sepenuhnya |
| 5 | `bukti/plan-f8` tanpa awalan folder perancangan | **0** — dua rujukan asal kini laluan penuh; satu-satunya padanan yang kelihatan "pendek" ialah `…/bukti/plan-f8/` dalam jadual §1 F0(iv)(g), di mana `…` ialah `Audit Review Round Robin` pada lajur yang sama |
| 6 | `assert-playwright-json` | **23** padanan (kiraan `grep -cF`); setiap gate Playwright — canary · `ci-guidance` · `ci-domain` · shard `guidance-full` · `ci-ocr` — mempunyai sekurang-kurangnya satu panggilan |
| 7 | `SCOUT_DRIVER=meilisearch` sebagai command CI | **5** padanan: §1 F0(iv)(d-1) (step + keputusan #3), bullet lapis 1, command literal tempatan, §9.2 |
| 8 | `SESSION_DRIVER: file` | **4** padanan: `env:` job `guidance-e2e` (`:1014`) · step serve lapis 1 (`:1206`) · blok contoh env server (`:1486`) · penjelasan (`:1495`). **Tiada** pada mana-mana step Playwright |
| 9 | `16 fail repo` | konsisten (log versi, §0.5e, F0(iv-a), D11, §12) |
| 10 | `§0.7 #8` | **0** (dibetulkan kepada #7) |

**Semakan fakta kod/vendor yang dijalankan P21 (baca sahaja, tiada mutasi):**
`.github/workflows/ci.yml` (penuh) · `playwright.config.js` (penuh) · `.gitignore` (penuh) ·
`storage/**/.gitignore` (10 fail) · `config/filesystems.php:16-45` · `config/diwan.php:1-15` ·
`bootstrap/app.php` (penuh) · `app/Console/Commands/SyncHelpIndex.php` (penuh) ·
`app/Services/HelpSearchService.php` (penuh) ·
`vendor/laravel/framework/src/Illuminate/Foundation/Console/ServeCommand.php:1-200,425-440` ·
`node_modules/playwright/lib/runner/index.js` (reporter JSON `:4061-4069`, `removeOutputDirs`
`:5943-5963`) · `node_modules/playwright/lib/program.js:190` · `git status`/`git log`.
**Setiap** nombor baris baharu dalam v1.9 diambil daripada bacaan ini, bukan daripada pelan atau
daripada `PLAN-RR-20-CODEX.md`.

---

## 5. Mengapa pelan MASIH belum muktamad

1. **Peraturan penutupan belum dipenuhi.** `PLAN-RR-STATUS.md` #6 menuntut **satu pusingan penuh
   tanpa penambahbaikan substantif**. P20 menemui 6 pindaan dan **kesemuanya diterima** (satu
   sebahagian) — pusingan terakhir menghasilkan +399 baris kontrak baharu, termasuk satu seksyen
   YAML baharu.
2. **Kontrak baharu v1.9 belum disemak pihak kedua.** §1 F0(iv)(d-1) (YAML lapis 1), §1 F0(iv)(g)
   (lokasi artifak), jadual A/B required-vs-evidence, dan assertion (7) semuanya ditulis dalam
   giliran ini. Sejarah round-robin ini menunjukkan apa yang berlaku apabila integrasi dianggap
   betul tanpa audit bebas (P6/P8 palsu → penutupan P9 dibatalkan → P10 menemui 8 bloker).
3. **Keputusan pemilik masih tertunggak.** D8/D9/D11 belum dijawab dan draf Addendum spec v2.6
   (D10) belum wujud. D11 **tidak berubah** dalam giliran ini (kekal 16 + 1) — itu satu-satunya
   berita baik: senarai yang pemilik akan lihat kini stabil merentas dua pusingan.
4. **Snapshot §0.7 #2 masih tidak pernah dibuat** (P15, P17, P18, P19, P20, P21 semuanya dilarang
   git). Selagi folder ini tidak dikomit, setiap dakwaan sejarah tentang teks v1.6–v1.9 kekal
   **tidak boleh disahkan**.

---

## 6. Untuk Codex Pusingan 22 — fokus yang dicadangkan

Semak **enam penutupan** di atas, dan khususnya perkara yang P21 cipta baharu:

1. **§1 F0(iv)(d-1)** — sahkan setiap nilai terhadap `ci.yml` sebenar dan sahkan **dakwaan
   `ServeCommand`**: adakah `--no-reload` benar-benar memintas penapisan pada
   `laravel/framework` versi yang dipin `composer.lock`, dan adakah `$_ENV` pada runner
   `ubuntu-24.04` (setup-php, `variables_order`) mengubah kesimpulan itu ke mana-mana arah?
2. **Gate Meilisearch** — adakah `grep -qF` ke atas output ialah bentuk terbaik, atau adakah
   `$index->stats()` selepas `waitForTasks` cukup deterministik untuk Meili v1.12 sehingga
   assertion teks tidak diperlukan? Jika ia flake, itu pindaan substantif.
3. **Assertion (7)** — adakah `stats.skipped` boleh bukan-sifar pada larian yang **sah** (cth.
   `--grep` yang menapis ujian, atau `test.describe.configure`)? Jika ya, canary `--grep
   @session-canary` mungkin memerlukan pengecualian yang dinamakan.
4. **Jadual A/B** — sahkan nama check `Docker app image` / `Docker web image` seperti yang
   GitHub benar-benar laporkan untuk job matriks ber-`name:` (tanda kurung? spasi?), dan sama ada
   `gh api …/check-runs` ialah cara yang betul untuk mengesahkannya sebelum tetapan repo diubah.
5. **`storage/app/plan-*`** — adakah mana-mana kod produk, ujian, atau command artisan menyentuh
   `storage/app/` di luar `private/` dan `public/`? Adakah `if-no-files-found: error` pada
   `ci-playwright-json` boleh menghasilkan kegagalan mengelirukan apabila step Playwright pertama
   gagal sebelum menulis JSON?
6. **Kiraan D11 = 16 + 1** — sahkan tiada fail repo baharu diseludup masuk oleh kontrak v1.9
   (khususnya: adakah `setup-node` pada job agregator, atau `tee` ke `storage/logs/`, memerlukan
   apa-apa fail repo tambahan?).

---

*Ditulis oleh Claude Pusingan 21. Fail yang disunting dalam giliran ini: **`PELAN-PEMBAIKAN.md`**,
**`PLAN-RR-21-CLAUDE.md`**, **`PLAN-RR-STATUS.md`** — tiada yang lain. Tiada kod aplikasi
disentuh; tiada git/SSH/deploy/ujian mutasi dijalankan.*

# Pusingan 13 — Claude: audit + integrasi keputusan Codex P12 ke pelan v1.5

**Tarikh:** 2026-08-02
**Asas kod:** `8342d95` (working tree: hanya fail perancangan diubah)
**Dokumen diaudit:** `PLAN-RR-12-CODEX.md` terhadap `PELAN-PEMBAIKAN.md` v1.4 + kod sebenar
**Keputusan:** **6 TERIMA · 2 TERIMA SEBAHAGIAN · 0 TOLAK** → `PELAN-PEMBAIKAN.md` **v1.5**
**Status pelan:** ⏳ **BELUM MUKTAMAD** — diserah kepada Codex P14 untuk audit integrasi.

---

## 0. Integriti fail (peraturan P11 #7)

| Peristiwa | SHA-256 `PELAN-PEMBAIKAN.md` | Saiz | Masa-ubah |
|---|---|---|---|
| Sebelum sunting (mula giliran) | `B688A481086AD48AE78B1AB77F939906BB5380A3B83AE3D910E9629A6F612719` | 134 388 B | 2026-08-02 01:22:43 |
| Semakan kedua (sebelum suntingan pertama) | `B688A481…F612719` — **tidak berubah** | 134 388 B | 01:22:43 |
| Selepas sunting (akhir giliran) | `470A4A8102E8EE4FB736F7045D3EDD936E13EF6FF548D81380F86C3322E7ED32` | 166 051 B / 2 282 baris | 2026-08-02 01:43:36 |

**Tiada penulisan oleh proses lain dikesan semasa giliran ini.** Hash sebelum-sunting disemak
dua kali (pada permulaan giliran dan sejurus sebelum suntingan pertama) dan kekal sama.

**Fail yang diubah dalam pusingan ini (tiga sahaja, seperti dibenarkan):**
`PELAN-PEMBAIKAN.md` · `PLAN-RR-13-CLAUDE.md` (baharu) · `PLAN-RR-STATUS.md`.
**Tiada** perubahan pada `app/`, `resources/`, `tests/`, `e2e/`, config, migrasi, `package*.json`,
CI atau Docker. **Tiada** ujian mutasi, `git add/commit/push`, SSH atau deploy dijalankan.

---

## 1. Ringkasan keputusan

| ID | Penemuan P12 | Keputusan P13 | Diintegrasi di (v1.5) |
|---|---|---|---|
| P12-01 | `launchPending` mesti `#[Locked]` | **TERIMA** | §2.2 (kod + nota 6 baharu), §2.3, §2.4 #5/#7, §2.6 |
| P12-02 | Susun F6 mengikut 200 langkah tindakan | **TERIMA** | §7.1, §7.2 (jadual W1–W6 ditulis semula + invarian), §7.3, §7.4, §9, §12 |
| P12-03 | Gate penuh 83/473 tanpa persampelan | **TERIMA** | §7.3 (kontrak gate G1–G5), §7.4, §9 (3 baris metrik), §9.3 |
| P12-04 | CI: services tidak dikongsi antara job | **TERIMA** | §1 F0(iv) ditulis semula, §10 langkah 1, §8.5, Lampiran B #11 |
| P12-05 | Harness produksi + liputan mobile | **TERIMA SEBAHAGIAN** — substansi penuh diterima; satu premis rujukan dibetulkan | §9.1 (jurang 5 → 8), §10 langkah 6, Lampiran B #10 |
| P12-06 | Rantaian bukti imej/aset | **TERIMA** | §1 F0(v), §10 langkah 5A (jadual 7 baris + 2 kesilapan dinamakan), §10 jadual F2/F7, §10 langkah 5 |
| P12-07 | `disabledClick()` + sel bukan pautan | **TERIMA** | §8.1 ditulis semula (kontrak API + gate HTML), §8.5, §0.5 C16 |
| P12-08 | Hormati larangan pakej repo | **TERIMA SEBAHAGIAN** — arahan diterima **sepenuhnya** dan menjadi lalai baharu; satu asas tambahan ditambah | §0.1(2) ditulis semula, §8.5, §11 D5 (dipecah (a)/(b)), Lampiran B #9 |

Kedua-dua "TERIMA SEBAHAGIAN" **tidak** mengurangkan mana-mana tuntutan Codex — dalam kedua-dua
kes tuntutan diterima 100% dan hanya *justifikasinya* yang dibetulkan atau dikuatkan.

---

## 2. Audit satu per satu

### P12-01 — Kunci `launchPending` di sempadan Livewire → **TERIMA**

**Dakwaan Codex:** v1.4 §2.2 mengisytiharkan `public bool $launchPending` tanpa `#[Locked]` dan
§2.4 #7 sengaja membenarkan klien menetapkannya; dakwaan "klien hanya boleh mematikan" tidak
dikuatkuasakan oleh kod.

**Bukti yang saya sahkan sendiri:**

1. **Dakwaan tentang v1.4 adalah tepat.** `PELAN-PEMBAIKAN.md` v1.4 baris 314 (`// BAHARU (C04) —
   one-shot, BUKAN Locked`), baris 411 (`tambah sifat **bukan-Locked** launchPending`), baris
   425 (jadual fail diubah), baris 456-457 (`launchPending **sengaja bukan Locked** (klien hanya
   boleh mematikannya…)`). Tiada satu pun mekanisme yang menghadkan arah mutasi klien.
2. **Semantik `#[Locked]` membenarkan mutasi server.** `vendor/livewire/livewire/src/Attributes/Locked.php:8`
   → `class Locked extends BaseLocked`; `vendor/livewire/livewire/src/Features/SupportLockedProperties/BaseLocked.php:10-13`:

   ```php
   public function update()
   {
       throw new CannotUpdateLockedPropertyException($this->getName());
   }
   ```

   Penjagaan berlaku pada hook `update()` sahaja — iaitu laluan kemas kini **klien**. Penetapan
   dalam `mount()` dan dalam `guidanceProgress()` ialah penugasan sifat PHP biasa dan **tidak**
   melalui hook itu. Maka reka bentuk one-shot kekal utuh: `mount()` boleh menetapkan `true`,
   `guidanceProgress()` boleh menetapkan `false`, klien tidak boleh menetapkan apa-apa.
3. **Tiada kos.** Keempat-empat sifat baharu F1 menjadi Locked secara seragam — hilangnya
   satu pengecualian yang perlu dijelaskan kepada penyelenggara kelak.

**Penambahan P13 (tidak diminta P12, tetapi perlu untuk pelaksanaan literal):**
`HelpLauncher::guidanceProgress()` **sedia ada** mempunyai guard pulang-awal pada
`app/Livewire/HelpLauncher.php:41-43` apabila `findVisible()` mengembalikan `null`. Jika padam
one-shot diletakkan **selepas** guard itu, mana-mana keadaan yang menjadikan guide tidak lagi
kelihatan (kebenaran ditarik, guide dibuang katalog) akan meninggalkan `launchPending = true`
kekal. v1.5 menetapkan padam one-shot berlaku **sebelum** guard, dan ini selamat kerana syaratnya
membandingkan dengan `requestedGuideId` yang `#[Locked]` dan ditetapkan server.

**Gate ujian yang diintegrasikan:** §2.4 #7 kini menuntut `CannotUpdateLockedPropertyException`
bagi **enam** sifat, dengan `launchPending` diuji pada **kedua-dua** arah (`true` DAN `false`) —
bukan satu arah; §2.4 #5 menambah **kes keempat** (event untuk guide yang tidak lagi kelihatan →
one-shot tetap dipadam, membuktikan susunan guard); §2.6 menambah baris risiko
"Klien memaksa auto-start".

---

### P12-02 — Susun F6 mengikut risiko pengguna sebenar → **TERIMA**

**Dakwaan Codex:** §7.1 menyatakan 200/229 langkah tindakan generik berada dalam
`screen` + `workflow` dan tenant/admin mempunyai sifar, tetapi §7.2 masih menetapkan
W1–W2 = `tenant`, W3 = `admin`, W4 = `screen`, W5 = `workflow`.

**Bukti:**

1. **Percanggahan dalam v1.4 disahkan.** §7.1 baris 1298-1303 menulis dengan jelas: *"Kesemua 200
   langkah tindakan bersasar generik berada dalam `screen` + `workflow`, iaitu dua family yang
   tiada langsung dalam W1–W3 v1.3 … W4/W5 bukan 'kerja tambahan di hujung' tetapi teras"* —
   dan §7.2 baris 1339-1347 tetap meletakkan `screen`/`workflow` di W4/W5. Dokumen bercanggah
   dengan dirinya sendiri dalam jarak 40 baris.
2. **Angka dikira semula secara bebas** terus daripada `resources/help/guides.json` (bukan
   disalin daripada mana-mana laporan). Hasil sepadan **baris demi baris** dengan §7.1:

   | Family | Guide | Langkah | Generik | Placeholder | `wait_for_user` | Tindakan bersasar generik |
   |---|---:|---:|---:|---:|---:|---:|
   | admin | 12 | 32 | 32 | 0 | 0 | 0 |
   | public | 3 | 8 | 4 | 0 | 3 | 0 |
   | screen | 29 | 151 | 140 | 140 | 151 | **140** |
   | tenant | 25 | 124 | 124 | 118 | 0 | 0 |
   | workflow | 14 | 158 | 143 | 0 | 75 | **60** |
   | **Jumlah** | **83** | **473** | **443** | **258** | **229** | **200** |

   `catalog_version` = `2026.07.22.2`. Sasaran spesifik = 13 nama merangkumi 30 langkah
   (`classification-*` 20, `inbox-*` 6, `registration-*` 4) — sepadan §7.1.
3. **Prasyarat teknikal disemak.** Guide `screen.*`/`workflow.*` dicapai melalui deep-link
   `?panduan=<id>` (§7.1 — 17 route dikongsi), iaitu laluan yang F1 ubah. Kerana F6 datang
   selepas F1, **tiada halangan urutan**; tetapi ujian W1/W2 mesti mengassert `data-guide-id`
   sebelum menilai langkah. Ini dieksplisitkan dalam §7.2.

**Kesan sampingan yang saya kenal pasti dan tangani secara terbuka (tidak disebut P12):**

- **Gate W1 v1.4 tidak lagi boleh digunakan.** §7.3 v1.4 mendefinisikan gate W1 sebagai penurunan
  `resolved_to_generic` **kohort** (25/124 = `tenant` sepenuhnya). Dengan W1 = `screen`, metrik
  itu **tidak bergerak langsung**. v1.5 menurunkan taraf kohort kepada *perbandingan
  apple-to-apple sahaja* dan menjadikan gate langkah-tindakan sebagai gate utama setiap gelombang.
- **6 langkah popover mobile beralih ke W5.** `STATUS.md:72` merekod 6/124 — iaitu kohort
  `tenant`. Di bawah urutan baharu, `tenant` = W5, jadi item ini **ditangguh**. v1.5 merekodnya
  **secara eksplisit sebagai penangguhan bertarikh** (§7.2 + §9 + §7.4), bukan dibiarkan senyap —
  ini pematuhan pengajaran `spdm-deploy-lessons` (tiada had senyap).
- **258 placeholder terbelah 140 (`screen`, W1/W3) + 118 (`tenant`, W5).** Kedua-dua angka
  dilaporkan berasingan dalam §9.
- **Anggaran §12 disusun semula** mengikut gelombang baharu; jumlah keseluruhan hampir tidak
  berubah, tetapi manfaat pengguna datang ±2 sesi lebih awal.

**Invarian yang diintegrasikan (kata-kata Codex dikekalkan):** *semua langkah `wait_for_user`
generik diselesaikan atau di-risk-accept secara spesifik (ID guide + indeks langkah + sebab +
tarikh) sebelum kerja penerangan tenant/admin dianggap kemajuan utama F6.*

---

### P12-03 — Gate penuh tidak boleh bergantung pada persampelan → **TERIMA**

**Bukti percanggahan v1.4:** §7.3 baris 1435-1438 (*"W2–W6 menggunakan **persampelan
berstruktur** — setiap family sekurang-kurangnya 3 guide"*) dan §7.4 baris 1442-1446 (empat
kotak semak sahaja, semuanya pada aras guide atau skop W1). Codex betul: status aras-guide tidak
membuktikan bahawa 473 langkah individu berfungsi, dan §7.2 v1.4 sendiri membenarkan satu guide
ditandakan `not-applicable` secara keseluruhan.

**Yang diintegrasikan — kontrak gate lima lapis (§7.3):**

| # | Lapis | Liputan wajib |
|---|---|---|
| G1 | Statik, per-langkah | **473/473** berstatus `specific`/`generic-justified`/`not-applicable`/`blocked` + route/permission/viewport/state + `reason`+`since` bertarikh |
| G2 | DOM hidup | **semua** sasaran `specific`: unik, kelihatan, sepadan `data-help-target`, kekal selepas morph, wujud pada setiap viewport diisytihar |
| G3 | Tour black-box | **229/229** langkah tindakan: `.driver-active-element` betul, maju tepat sekali, tiada dead-end |
| G4 | Kitaran guide | **83/83**: mula → maju → tutup → ulang → resume (**boleh di-shard**; shard ≠ persampelan) |
| G5 | Kebolehjejakan | setiap pengecualian = **ID guide + indeks langkah**, bukan aras guide |

**Persampelan diturunkan taraf** kepada *smoke silang-family selepas setiap gelombang* dan
dilabel sedemikian dalam laporan F8 (§9.3). Tiga baris metrik §9 ditulis semula kepada
denominator penuh (`0/229`, `0/258`, `473/473`), dan frasa lapuk "0 dalam family yang telah
digelombangkan" kini disenaraikan sebagai **dilarang** dalam sel metrik itu sendiri.

---

### P12-04 — CI Playwright mesti hidup dalam job yang mempunyai services → **TERIMA**

**Bukti:** `.github/workflows/ci.yml` mempunyai **dua** job sahaja — `integration` (baris 18,
blok `services:` baris 22-51: postgres/redis/meilisearch) dan `docker` (baris 159, **tiada**
`services:`). Service containers GitHub Actions diberikan kepada job yang mengisytiharkannya
sahaja; tiada perkongsian container atau rangkaian antara job. Maka F0(iv) v1.4 (*"guna semula
perkhidmatan job `integration`"*) memang mustahil.

**Keputusan yang diintegrasikan:** cadangan utama Codex diterima — **tambah langkah Playwright ke
dalam job `integration`** selepas baris 128-132, kerana PHP 8.4, Node 22, `npm run build`
(baris 120-121), migrasi dan ketiga-tiga service sudah ada di situ. Alternatif job berasingan
**yang menduplikasi** blok `services:`/`env:`/setup dikekalkan sebagai laluan sah jika masa
melebihi `timeout-minutes: 30`.

**Butiran tambahan yang saya sahkan dan tambah (tidak disebut P12):**

1. **`--seed` wajib, bukan pilihan.** Baris 131 CI hanya `migrate --force`. `guidance.spec.js:13-25`
   memerlukan lapan akaun `*@demo.test` + tenant `mam`/`man`. `DatabaseSeeder` memanggil
   `DemoSeeder` hanya pada `local`/`testing`; CI menetapkan `APP_ENV: testing` (baris 53), jadi
   `migrate:fresh --seed --force` **akan** menjananya (kata laluan `password` —
   `DemoSeeder.php:19,32`).
2. **`E2E_ROLE_LOGIN_DELAY_MS: "0"` diperlukan.** `guidance.spec.js:10` berlalai **15 000 ms**
   antara log masuk — pada 20 konteks itu ±5 minit menunggu semata-mata. Jarak itu wujud untuk
   had 5/min produksi, sedangkan CI sudah menetapkan `DIWAN_LOGIN_RATE_LIMIT: "100"` (baris 80).
3. **Port dan channel dipakukan pada nilai sebenar config:** `playwright.config.js:11` →
   `http://127.0.0.1:8092`; `:13` → `channel: 'chrome'`.
4. **`trap` cleanup mengikut corak yang sudah wujud dalam repo** (langkah Horizon, baris 139-141)
   — bukan corak baharu yang direka.
5. **Gate Meili C20 mendahului spec carian** dengan menunggu task dan mengassert **tepat 83
   dokumen** — menjalankan command sync sahaja bukan bukti indeks siap (Meili tak segerak).

---

### P12-05 — Betulkan harness dan liputan matriks production → **TERIMA SEBAHAGIAN**

**Substansi diterima sepenuhnya.** Kedua-dua fakta kod yang Codex kemukakan disahkan tepat:

- `e2e/guidance.spec.js:124` ialah ujian 10 identiti × 2 viewport dengan
  `expect(contextKeys.size).toBe(20)` pada **baris 214**.
- `e2e/production-readonly.spec.js:66-138` membuka **satu context per akaun**, satu viewport,
  tanpa public/superadmin, dan mengassert `toBe(accounts.length)` — **bukan** 20.
- `guidance.spec.js:156-157` dan `:183-191` mengehadkan lawatan route kepada
  `viewport.name === 'desktop'`; mobile merekod `navigation.length || 1` (`:170`, `:206`) —
  iaitu **satu** halaman. Jadi liputan **page-by-page mobile tidak wujud**.

**Premis yang saya betulkan (sebab "SEBAHAGIAN"):** P12 menyatakan *"dakwaan §9.1 bahawa
`production-readonly.spec.js` ialah harness 20 konteks … adalah salah"*. Dakwaan itu **tidak
wujud** dalam v1.4. `grep -n "production-readonly" PELAN-PEMBAIKAN.md` → **0 padanan**; §9.1
memetik `e2e/guidance.spec.js:124` dengan **betul** (baris 142 dan 1643 v1.4). Pembetulan ini
direkod supaya P14 tidak "membaiki" ralat yang tidak pernah ada — bukan untuk menolak apa-apa.

**Penemuan tambahan saya yang menguatkan P12-05:** `production-readonly.spec.js:71` berlalai
`E2E_PROD_ROLE_LOGIN_DELAY_MS ?? **0**` — jarak log masuk **sifar**. Terhadap produksi dengan
`DIWAN_LOGIN_RATE_LIMIT` lalai 5/min, spec itu akan kena throttle 429 dan menghasilkan kegagalan
palsu. `guidance.spec.js:10,37-40` sebaliknya sudah melaksanakan `waitForLoginSlot` dengan lalai
15 000 ms. Spec produksi baharu mesti mewarisi corak `guidance.spec.js`, bukan
`production-readonly.spec.js`.

**Yang diintegrasikan (§9.1):** jurang naik daripada **5 kepada 8**; jurang 6 = mobile bukan
page-by-page (dengan rujukan baris), 7 = set role tidak diassert (`:23-25` menggantikan
`localTenantRoles` sepenuhnya tanpa semakan → assert tepat lapan role dan setnya tepat), 8 =
kiraan konteks dipakukan pada **tepat 20**. Jurang 4 dikeraskan: spec produksi **mesti read-only**
— `ensureInboxFixture` (`:95-111`, memuat naik dokumen sebenar, tiada pembersihan) dan setiap
laluan mutasi dikeluarkan. §10 langkah 6 menambah amaran eksplisit **jangan** jalankan seluruh
`guidance.spec.js` terhadap produksi. Penemuan route beralih daripada sidebar desktop
(`visibleNavigation` `:62-70` — struktur tidak boleh melengkapkan mobile) kepada **manifest route
beku F0(ii)**.

---

### P12-06 — Betulkan rantaian bukti imej dan aset → **TERIMA**

**Bukti:**

1. **Dua keluarga imej.** `docker-compose.yml:6` → `image: diwan-app:${DIWAN_IMAGE_TAG:-local}`
   digunakan oleh anchor `x-app` yang dikongsi `app` (`:23`), `worker` (`:26`) dan `scheduler`
   (`:31`); `docker-compose.yml:40` → `image: diwan-web:${DIWAN_IMAGE_TAG:-local}` untuk `nginx`.
   Maka §10 #3 v1.4 (*"Image ID setiap container … **mesti sama** dengan #2"*) memang mustahil.
2. **Wildcard tidak wujud pada HTTP.** `curl https://bakwim.my/build/assets/help-*.js` meminta
   laluan literal mengandungi `*` → dijangka 404. Nama aset mesti diekstrak daripada manifest.
3. **nginx memang memiliki aset itu**, jadi perbandingan silang-container bermakna dan wajib:
   `docker/Dockerfile:71-72` — `FROM nginx:1.27-alpine AS web` diikuti
   `COPY --from=app /var/www/html/public /var/www/html/public`.
4. **Kunci manifest disahkan.** `vite.config.js` input mengandungi `resources/js/help.js`;
   manifest tempatan memberi `{"file":"assets/help-pJkQNpPs.js","css":["assets/help-PP-ALO9e.css"]}`.
   **Nota P13 yang tidak disebut P12:** entri `help.js` menghasilkan **dua** artifak (JS **dan**
   CSS) — rantaian mesti meliputi kedua-duanya, jika tidak perubahan CSS bantuan boleh dihidang
   basi tanpa dikesan.

**Yang diintegrasikan (§10 langkah 5A):** jadual bukti ditulis semula kepada **tujuh baris**
(1, 2a, 2b, 3a, 3b, 4a, 4b, 5a, 5b, 6, 7) dengan assert per-keluarga (`3a` padan `2a`; `3b` padan
`2b` dan **dijangka berbeza** daripada `2a`), ekstraksi nama aset berstruktur melalui `php -r` +
`json_decode` (bukan parsing teks), perbandingan sha256 `manifest.json` antara `app` dan `nginx`,
`curl -fsS … | sha256sum` (bendera `-f` supaya 404/5xx **gagal** dan bukan menghasilkan hash badan
ralat), dan header `curl -sI` diturunkan taraf secara eksplisit kepada **bukti tambahan sahaja**.
§10 langkah 5 tidak lagi melabel `curl -sI … = 200` sebagai "hash". Jadual deploy F2 dan F7
dikemas serentak.

---

### P12-07 — Gunakan API Filament semasa dan sel bukan pautan → **TERIMA**

**Bukti vendor (dibaca penuh, 51 baris):**
`vendor/filament/tables/src/Columns/Concerns/CanBeDisabled.php`

```php
public function disabledClick(bool | Closure $condition = true): static   // :20  ← API semasa
/** @deprecated Use `disabledClick()` instead. */                          // :27-29
public function disableClick(bool | Closure $condition = true): static     // :30  ← alias lapuk
public function isClickDisabled(): bool                                    // :47
```

Codex tepat pada kedua-dua baris. Turut disahkan: rujukan v1.4 `CanBeDisabled.php:30,47` memetik
**alias deprecated** sebagai sumber `$isColumnClickDisabled`; sumber sebenar ialah `:47`
(`isClickDisabled()`) yang ditetapkan oleh `:20`.

**Dakwaan kedua juga tepat:** v1.4 §8.1 tidak mengandungi satu pun panggilan API konkrit — ia
hanya menerangkan keputusan dalam prosa, dan mengekalkan kontrak pengesahan
*"baris masih boleh dibuka melalui sel lain"* tanpa membuang idea sel-sebagai-pautan.

**Yang diintegrasikan (§8.1):** blok kod pelaksanaan sebenar dengan `->disabledClick()`, label
dan dua state BM bermakna (`Duplikat dikesan` / `Tiada duplikat`) + tooltip SHA-256; kontrak
"klik sel membawa ke halaman rekod" **dibuang**; gate HTML ditulis semula kepada lima assert
termasuk **sel bukan `<a>`** secara eksplisit, teks boleh akses pada kedua-dua state,
**susun/tapis jadual tidak rosak**, dan `link-name` = 0 pada fixture dengan **dan** tanpa baris
duplikat. Penolakan kiraan duplikat (N+1) dikekalkan tanpa perubahan.

---

### P12-08 — Hormati larangan pakej repo → **TERIMA SEBAHAGIAN** (arahan diterima 100%)

**Bukti:** `CLAUDE.md:10` berbunyi *"DILARANG: mereka-reka keperluan; menukar versi pakej
§3.2/§3.3; **menambah pakej luar senarai**; menambah ciri luar skop fasa; …"*. Frasa itu ditulis
umum dan **tidak** dihadkan kepada Composer. Codex betul.

**Menilai hujah balas v1.4:** v1.4 §0.1(2) berhujah bahawa kerana spec tidak menyenaraikan npm,
`axe-core` "BUKAN percanggahan §3.2/§3.3". Hujah itu **tidak selamat dan saya tarik balik**: jika
tiada senarai npm, maka **setiap** pakej npm berada "luar senarai" — kehadiran
`driver.js`/`pdfjs-dist`/`@playwright/test` membuktikan preseden **sejarah**, bukan kebenaran
untuk menambah yang baharu.

**Asas tambahan yang saya kemukakan (sebab "SEBAHAGIAN" — ia menguatkan, bukan melembutkan):**
asas sebenar bukan sekadar `CLAUDE.md:10` tetapi juga **`CLAUDE.md:3`** — *"Jika spec kabur,
bercanggah … BERHENTI SERTA-MERTA dan tanya soalan yang spesifik. JANGAN teka, jangan 'assume',
jangan pilih sendiri."* Dengan kata lain, tindakan v1.4 memilih tafsiran sendiri terhadap polisi
yang kabur ialah pelanggaran peraturan **3**, bukan sekadar kesimpulan yang boleh dipertikaikan
di bawah peraturan 2. Ini menjadikan keputusan P12-08 lebih kukuh, bukan kurang.

**Yang diintegrasikan:** §0.1(2) ditulis semula (lalai = **jangan tambah**; dua kelulusan
berurutan diperlukan; dokumen kawalan dikemas dahulu, barulah lockfile); §8.5 menjadikan larian
axe luaran/manual sebagai **laluan lalai** yang menghasilkan bukti setara (JSON + skrinsyot) dan
menegaskan F7 **tidak tersekat**; **D5 dipecah kepada dua soalan eksplisit** — (a) luluskan
pengecualian polisi bertulis? (b) jika ya, benarkan `package.json`+lockfile berubah? Cadangan
Claude untuk (a) diubah daripada "Ya" kepada **tidak mengesyorkan memintas polisi; jika ragu,
Tolak**. Nota kebergantungan §11 dan Lampiran B #9 diselaraskan.

---

## 3. Imbasan konsistensi P12 §4 — hasil sebenar

Dijalankan dengan `grep -c` ke atas `PELAN-PEMBAIKAN.md` selepas suntingan.
Baseline v1.4 direkod dahulu supaya pergerakan boleh disemak.

| # | Frasa/kontrak lapuk | v1.4 | v1.5 | Nota |
|---|---|---:|---:|---|
| 1 | `launchPending` + "bukan-Locked" sebagai kontrak | 2 | **0** | 1 padanan kekal pada §2.2 sebagai **rujukan sejarah bertanda "ditarik balik"** |
| 2 | `W1 \| tenant` / `W2 \| tenant` / `W3 \| admin` dalam jadual F6 | 1 + 1 | **0** | jadual §7.2 ditulis semula |
| 3 | "persampelan" sebagai gate F6/F8 | 1 (sebagai gate) | **0** | 5 padanan kekal, kesemuanya melabel persampelan sebagai **larangan** atau **smoke** |
| 4 | `0 (skop W1)` / `0 dalam skop W1` | 1 | **0** | digantikan denominator penuh |
| 5 | "0 dalam family yang telah digelombangkan" sebagai sasaran | 1 | **0** | 2 padanan kekal: satu melabelnya **dilarang**, satu menerangkan larangan |
| 6 | `production-readonly.spec.js` sebagai harness 20 context | 0 | **0** | tidak pernah wujud; kini disebut secara eksplisit sebagai **BUKAN** harness |
| 7 | `help-*.js` pada URL HTTP | 1 | **0** | 5 padanan kekal: 3 melabelnya dilarang, 2 ialah **glob fail sistem** (sah — dianotasi §3.7) |
| 8 | Keempat-empat container mesti mempunyai Image ID sama | 1 | **0** | digantikan assert per-keluarga |
| 9 | `disableClick()` sebagai API pilihan | 6 | **0** | 3 padanan kekal, kesemuanya melabelnya *deprecated, jangan guna* |
| 10 | Dakwaan "npm tidak dikawal polisi / tidak bercanggah" | 1 | **0** | ditarik balik §0.1(2) + §0.5 C24 |
| 11 | "guna semula perkhidmatan job integration" | 1 | **0** | reka bentuk CI ditulis semula |

**Semakan silang tambahan (tidak diminta, dijalankan sendiri):** rujukan gelombang lapuk dikesan
dan dibetulkan pada dua tempat yang tidak disenaraikan P12 — §7.1 (*"W4/W5 … teras"* → kini
menyatakan `screen`/`workflow` = **W1/W2**) dan tajuk lajur jadual kaedah ujian (*"Kaedah ujian
W4–W6"* → *"Kaedah ujian (setiap gelombang)"*).

**Percanggahan sedia ada yang ditemui dan dijelaskan (bukan daripada P12):** F0(ii) menyatakan
*"Sasaran unik dalam katalog = 15"* manakala §7.1 menyatakan *"13 nama sasaran unik"*. Kedua-duanya
**betul** tetapi mengukur benda berbeza — pengiraan saya memberi 15 nilai `target` unik, iaitu
**13 spesifik + 2 generik** (`page-primary`, `page-content`). v1.5 menyatakannya secara eksplisit
supaya P14 tidak melaporkannya sebagai konflik.

**Pengesahan baseline bebas** (kaedah: `node` ke atas `resources/help/guides.json`, bukan salinan
laporan): 83 guide · 473 langkah · 443 generik · 258 placeholder · 229 `wait_for_user` · **200**
langkah tindakan bersasar generik · 13 nama sasaran spesifik / 30 langkah · `sidebar` = **0**
penggunaan · `catalog_version` `2026.07.22.2`. **Kesemuanya sepadan angka beku F0(ii) tanpa
sebarang perbezaan.**

---

## 4. Lokasi integrasi (rujukan pantas untuk audit P14)

| Seksyen v1.5 | Perubahan | Punca |
|---|---|---|
| Kepala + log versi | v1.4 → **v1.5**, ringkasan 8 pindaan | semua |
| §0.1(2) | polisi pakej ditulis semula | P12-08 |
| §0.5 | baris C02/C04/C06/C07/C08/C16/C24 dikemas | semua |
| **§0.5a (baharu)** | peta keputusan P12-01…P12-08 + bukti | semua |
| §1 F0(ii) | penjelasan 15 = 13+2, pengesahan bebas P13 | P12-02, P13 |
| §1 F0(iv) | reka bentuk CI ditulis semula (7 langkah konkrit) | P12-04 |
| §1 F0(v) | baseline dua keluarga imej + aset exact | P12-06 |
| §2.2 | kod `#[Locked]`, nota "Mengapa `#[Locked]`", susunan guard | P12-01 |
| §2.3 | jadual fail diubah: 4 sifat **kesemuanya Locked** | P12-01 |
| §2.4 #5/#7 | kes keempat + tamper dua arah, enam sifat | P12-01 |
| §2.6 | baris risiko "Klien memaksa auto-start" | P12-01 |
| §7.1 | kesimpulan W1/W2, tajuk lajur kaedah ujian | P12-02 |
| §7.2 | jadual W1–W6 ditulis semula + invarian + kesan penangguhan | P12-02 |
| §7.3 | kontrak gate G1–G5; kohort diturunkan taraf; skop e2e | P12-02, P12-03 |
| §7.4 | kriteria per-gelombang + keseluruhan pada denominator penuh | P12-03 |
| §8.1 | `disabledClick()` + kod + gate HTML 5 assert | P12-07 |
| §8.5 | jadual fail diubah F7 (termasuk `vite.config.js`) + laluan axe | P12-04, P12-07, P12-08 |
| §9 | 4 baris metrik pada denominator penuh | P12-02, P12-03 |
| §9.1 | jurang 5 → 8 + pembetulan premis + peraturan read-only | P12-05 |
| §9.3 | "persampelan bukan bukti penutup" | P12-03 |
| §10 jadual F2/F7 | aset exact + entri Vite baharu | P12-06 |
| §10 langkah 5 | `curl -sI` bukan "hash" | P12-06 |
| §10 langkah 5A | rantaian 7 baris + 2 kesilapan dinamakan | P12-06 |
| §10 langkah 6 | spec produksi read-only + amaran mutasi | P12-05 |
| §11 D5 | dipecah (a) polisi / (b) dependensi | P12-08 |
| §11 nota kebergantungan | D5(a) mendahului D5(b) | P12-08 |
| §12 | anggaran disusun semula + nota kalibrasi kedua | P12-02, P12-03 |
| Lampiran B | butir 9, 10, 11 baharu | P12-04, P12-05, P12-08 |
| Footer | v1.5, giliran → Codex P14 | — |

---

## 5. Mengapa pelan kekal BELUM MUKTAMAD

1. **Peraturan penutupan belum dipenuhi** (`PLAN-RR-STATUS.md` #6): penutupan memerlukan **satu
   pusingan penuh kedua-dua ejen tanpa penambahbaikan substantif**. P12 menemui lapan pindaan;
   P13 mengintegrasikannya. Codex P14 belum menyemak integrasi itu.
2. **Pengajaran P9 masih terpakai.** Penutupan P9 dibatalkan kerana ia bersandar pada pusingan
   yang bukan ulasan Codex sebenar, dan P10 kemudiannya menemui 8 bloker yang bertahan sejak v1.0.
   Mengisytihar muktamad atas integrasi sendiri akan mengulang ralat yang sama.
3. **P12 §5 menetapkan syarat penerimaan** yang hanya Codex boleh sahkan — termasuk ketiadaan
   kontradiksi antara §1/§2/§7/§8/§9/§10/§11/§12 dan kebolehjalanan literal setiap command bukti.
4. **Keputusan pemilik D1–D10 (kini termasuk D5(a)/(b) yang dipecah) belum dijawab** — pelan tidak
   boleh dilaksana, apatah lagi dimuktamadkan, sebelum itu.
5. **P12 §5.6 secara eksplisit** melarang sebarang perubahan aplikasi, commit atau deploy sebelum
   pelan dimuktamadkan.

---

## 6. Untuk perhatian Codex P14

Perkara yang paling wajar diserang dalam audit seterusnya:

1. **§7.2 pecahan "~10 guide `screen` kritikal" dan "~6 guide `workflow` kritikal" belum
   diinventori.** Angka itu anggaran perancangan; senarai guide sebenar mesti ditetapkan sebelum
   W1 bermula. Semak sama ada invarian §7.2 mencukupi untuk mengelak "kritikal" ditakrifkan
   secara mudah-mudah semasa pelaksanaan.
2. **Penangguhan 6 langkah popover mobile ke W5** ialah keputusan sedar yang saya buat kesan
   daripada P12-02 — semak sama ada ia patut dipromosikan ke gelombang lebih awal atau menjadi
   item berasingan di luar urutan gelombang.
3. **Kos gate G1–G5** (473 status + 229 tour black-box + 83 kitaran penuh) mungkin melebihi
   `timeout-minutes: 30` job `integration`. Strategi shard §7.3 G4 belum bernombor.
4. **Pembetulan premis P12-05** (§2 di atas) — sahkan bahawa `grep -n "production-readonly"`
   terhadap v1.4 memang 0 padanan sebelum menerima atau menolak pembetulan itu.
5. **Rantaian §10 langkah 5A** memerlukan `sha256sum` dalam imej `nginx:1.27-alpine` (busybox
   menyediakannya) — sahkan pada server sebenar sebelum ia dijadikan gate deploy.

---

*Pusingan 13 tamat. Giliran diserah kepada **Codex Pusingan 14** untuk audit integrasi v1.5.
Pelan **BELUM MUKTAMAD**.*

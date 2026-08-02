# Bukti Verifikasi Fasa F0 — 2 Ogos 2026

Output SEBENAR arahan verifikasi (CLAUDE.md peraturan #7: dakwaan tanpa output = tidak siap).
Asas kod: `2489492` (kod aplikasi ≡ `8342d95`).

## 1. Suite Pest penuh (termasuk ujian F0 baharu)

```
Tests:    1 skipped, 432 passed (4738 assertions)
Duration: 120.01s
```

(Baseline pra-F0 = 409 lulus / 1 skip. +23 ujian baharu: PlanManifestTest 14 —
termasuk probe lapisan C 410 entri × 10 identiti + silang-tenant 8×404 —
AuditFixtureCommandTest 5, InboxAntivirusFailClosedTest 4.)

## 2. diwan:role-routes (lapisan A+B) — 0 mismatch

```
role_routes ditulis: …/role-routes.json (410 entri).
| public       | 0  |   | superadmin   | 25 |   | admin_masjid | 25 |
| pengerusi    | 17 |   | setiausaha   | 15 |   | bendahari    | 15 |
| nazir        | 13 |   | ketua_imam   | 13 |   | ajk          | 13 |
| audit        | 14 |
Tiada mismatch expected↔declared (lapisan C dikuatkuasakan oleh PlanManifestTest).
```

Kiraan nav = 25/17/15/15/13/13/13/14 — SAMA dengan jangkaan `e2e/guidance.spec.js` lama,
kini dijana daripada spec (roles.php + polisi), menamatkan drift P14-03.

## 3. Penjana manifest + validator bebas

```
OK: manifest ditulis ke Audit Review Round Robin/bukti/plan-baseline/manifest.json
  guides=83 steps=473 actionGeneric=200 placeholder=258
  waves=W0:2g/10s W1:28g/140s W2:13g/145s W3:1g/11s W4:1g/13s W5:35g/146s W6:3g/8s
  role_routes entries=410 counts={"public":0,"superadmin":25,"admin_masjid":25,"pengerusi":17,"setiausaha":15,"bendahari":15,"nazir":13,"ketua_imam":13,"ajk":13,"audit":14}

OK: manifest sah — partition wave/shard sepadan pengiraan bebas, set-union exact, role_routes konsisten.
```

Pengesahan angka beku kali KE-5 (bebas): 83 · 473 · 443 (238+205) · 258 · 229 · 200 ·
step.id unik 470/473 · defect mobile 6 (`tenant.pelupusan#1`, `tenant.kegemaran#1–5`).

## 4. Canary sesi + ci-domain (server tempatan 8092, `serve --no-reload`, SQLite buangan)

```
[ci-guidance] › ci-session-canary.spec.js › @session-canary … 1 passed (5.9s)
OK [storage/app/plan-ci/ci-canary.json]: 1 ujian, 0 skipped/timedOut/interrupted/unexpected/flaky.

[ci-domain] ddms-extended ×2 + office-workflow ×2 … 4 passed (1.2m)
OK [storage/app/plan-ci/ci-domain.json]: 4 ujian, 0 skipped/…
```

## 5. Sampel guidance-full (sebelum larian shard penuh)

```
gate screen: screen.klasifikasi-peti-masuk (11 langkah)     1 passed (15.6s)
gate tenant-admin-public: public.login (2 langkah)          1 passed (6.5s)   ← laluan fallback risk-accepted
gate tenant-admin-public: public.registration (4 langkah)   1 passed (6.0s)   ← koreografi hantar penuh
gate tenant-admin-public: tenant.dashboard (4 langkah)      1 passed (17.5s)
gate tenant-admin-public: admin.mosques (3 langkah)         1 passed (13.9s)
```

## 6. OCR fixture — tesseract lokal membaca istilah

```
$ tesseract tests/fixtures/ocr/sample-scan-1.png stdout -l msa+eng | grep BAKTIMURNI
BAKTIMURNI
BAKTIMURNI
$ tesseract tests/fixtures/ocr/sample-scan-2.png stdout -l msa+eng | grep CAHAYAIKHLAS
CAHAYAIKHLAS
CAHAYAIKHLAS
```

## 7. Baseline runtime produksi (F0(v)) — read-only SSH

Lihat `../plan-baseline/runtime-baseline-2026-08-02.json`:
`3a=2a ✓ · 3b=2b ✓ · 4a=4b ✓ · 5a=5b=6 ✓` — server `8342d95`, aset `help-pJkQNpPs.js`
hash sama dlm app/nginx/URL awam.

## 8. ⚠️ Penemuan F0: CI main sudah MERAH sejak `4e07a70` (pra-F0)

```
$ gh run list --branch main --limit 3 …
2489492 failure   8342d95 failure   4e07a70 failure
$ gh run view <run 8342d95> … → "PostgreSQL, Redis, Meili, OCR and tests: failure |
  langkah gagal: Validate, audit and format"
```

Punca: komit dokumen audit membawa 4 skrip PHP bukti (`bukti/pusingan-04/05/06/*.php`)
yang gagal `pint --test`. Pembetulan F0: **`pint.json`** (preset laravel + exclude
`Audit Review Round Robin`) — folder bukti ialah ARKIB, bukan kod sumber; skrip bukti
kekal verbatim (tidak diformat semula). Selepas fix:

```
$ vendor/bin/pint --test
{"tool":"pint","result":"passed"}
```

## 9. composer validate --strict

```
./composer.json is valid
```

## 10. Larian penuh 3 shard guidance-full + agregator — GATE LULUS

```
=== SHARD screen ===              30 passed (8.9m)
OK [storage/app/plan-ci/guidance-full-screen.json]: 30 ujian, 0 skipped/timedOut/interrupted/unexpected/flaky.
=== SHARD workflow ===            15 passed (7.3m)
OK [storage/app/plan-ci/guidance-full-workflow.json]: 15 ujian, 0 skipped/…
=== SHARD tenant-admin-public === 41 passed (10.5m)
OK [storage/app/plan-ci/guidance-full-tenant-admin-public.json]: 41 ujian, 0 skipped/…
=== AGREGATOR ===
GATE LULUS: 83 guide · 473 langkah · 229 langkah tindakan — union tiga shard sepadan
manifest (set, bukan kiraan). Laporan: storage/app/plan-f6/coverage-gate.json
```

**3 pepijat spec ditemui & dibaiki semasa larian penuh (bukti nilai gate!):**
1. Guide `workflow.*` sebenarnya 20/13 langkah (ada ekor generik di minit-saya/log-aktiviti)
   dan jangkaan nombor tegar tidak tahan auto-advance sync → mesin-keadaan toleran
   (baca langkah semasa → tindakan → poll maju) + pemandu per-langkah utk ekor.
2. Popover tour memintas klik `Hantar` modal muat naik (**pengesahan bebas RR-08-03
   pada desktop** — bukan mobile sahaja) → minimize (CTA) sebelum setiap interaksi modal.
3. Registrasi: kod akronim mesti 3–6 HURUF + telefon WA mesti unik (penolakan pendua
   senyap dari langkah 3) → kod huruf-dari-timestamp + telefon unik per larian.


## 11. CI sebenar — larian pertama gate penuh (run 30741376294, `06277fc`) MERAH → F0e

Run `06277fc` (2 Ogos 09:14): job `PostgreSQL, Redis, Meili, OCR and tests` GAGAL pada step
**Guidance smoke** (8/12 lulus); `guidance-e2e` skipped (needs) → `guidance-e2e-gate` failure;
Docker skipped. Pest penuh + canary + gate Meili + probe = LULUS di CI.

```
4 failed
  [ci-guidance] › guidance.spec.js:123 › Chrome berasingan … › desktop: setiausaha
  [ci-guidance] › guidance.spec.js:348 › tour pendaftaran tidak tergantung …
  [ci-guidance] › guidance.spec.js:386 › tour klasifikasi mengikuti modal lima langkah …
  [ci-guidance] › registration.spec.js:23 › pengguna baharu daftar …
8 passed (4.3m)
```

Analisis punca (semua = persekitaran CI, bukan regresi produk; ci-guidance TIDAK pernah
berjalan penuh dlm DB perawan sebelum ini — lokal lulus atas DB e2e berkeadaan lama):

1. `:123 desktop: setiausaha` — launcher wujud tetapi **hidden**: DB perawan + explore.spec
   berjalan dahulu meninggalkan `guidance_progress` → tour auto-resume di `/bantuan`;
   `help.css:76` sembunyikan launcher semasa `body.driver-active`; `disableAutomaticGuides`
   hanya berkesan panel public (`help.js` semak `diwan-help-seen` utk `isPublic` sahaja).
   → Fix ujian: `closeGuideIfOpen()` sebelum assert launcher (corak sedia ada di carian bantuan).
2. `:348`/`:386` — klik butang wizard halaman semasa mod minimize: `minimiseForAction` TIDAK
   destroy driver (popover sahaja disorok) → overlay + lubang sorotan kekal; lubang ikut
   geometri fon — runner Linux ≠ Windows lokal → titik tengah butang jatuh di luar lubang
   → hit-test Playwright gagal 30s (`<body class="driver-active driver-fade"> intercepts`).
   → Fix ujian: `click({ force: true })` pada klik elemen halaman semasa tour aktif — ujian
   ini menguji sinkronisasi langkah, bukan hit-test overlay (UX overlay = skop F2/F6).
   Pendedahan sama wujud dlm `guidance-full.spec.js` (job shard belum pernah berjalan di CI
   — skipped kali ini) → diperkukuh serentak pada titik yang sepadan.
3. `registration.spec:31` — `ENOENT storage/logs/laravel.log`: fail belum wujud dlm
   persekitaran perawan → guard `existsSync` (saiz awal 0). PLUS pergantungan tersembunyi:
   job env `MAIL_MAILER=array` + `LOG_CHANNEL=stderr` bermakna magic link TIDAK akan sampai
   ke fail log → step Serve kini override `MAIL_MAILER=log` + `MAIL_LOG_CHANNEL=single`
   (mail sahaja ke fail; log lain kekal stderr utk serve-ci.log).

Verifikasi lokal F0e dlm **keadaan CI-perawan** (SQLite buangan + `migrate:fresh --seed` +
`laravel.log` dialih + env serve menyalin CI): lihat §12.

## 12. Verifikasi lokal F0e — 6 larian penuh ci-guidance dlm keadaan CI-perawan

Setiap larian: DB SQLite baharu + `migrate:fresh --seed` + `laravel.log` dialih + env serve
menyalin CI (`APP_ENV=testing`, `APP_LOCALE=ms`, `SESSION_DRIVER=file`, `MAIL_MAILER=log`,
`MAIL_LOG_CHANNEL=single`, `LOG_CHANNEL=stderr`, `DIWAN_LOGIN_RATE_LIMIT=100`).

```
#1 11/12  gagal :398 (klik force semasa wire:loading disabled — klik hilang)
   → fix forceClickWhenEnabled(): tunggu toBeEnabled() SEBELUM click({force:true})
#2 11/12  :398 LULUS; flake :533 "textareaFormComponent is not defined"
   → race pemuatan aset lazy Filament (x-load) pada server dev single-thread sibuk;
     LULUS 2/2 bersendirian → flaky beban; CI: PHP_CLI_SERVER_WORKERS=4
#3 11/12  flake :139 bendahari 500 — PHP Fatal "Maximum execution time 30s" dlm
   ClassLoader.php:429 pada /bantuan/imej/* (~33s) → I/O autoloader Windows semasa
   beban → server lokal -d max_execution_time=180 (skrip sahaja; bukan repo)
#4 0/12   kesilapan skrip lokal sendiri (php -S tanpa cwd=public — router vendor guna
   getcwd()); server dibaiki; BUKAN keputusan ujian
#5 8/12   PUNCA BAHARU: /daftar HTTP 429 — throttle:public-registration 20/jam; kaunter
   dlm CACHE FILE kekal merentas larian (migrate:fresh tak sentuh cache) → larian ke-5
   melebihi had → :289/:369/registration gagal serentak; + 1 flake ERR_NO_BUFFER_SPACE
   → skrip: php artisan cache:clear per larian (CI: redis segar + hanya ±5 GET /daftar)
#6 10/12  daftar=200 ✓; ketiga-tiga ujian /daftar pulih; baki 2 = flake infra yang sama:
   :139 ERR_NO_BUFFER_SPACE (socket OS letih — larian ke-6 hari ini) + :533 race x-load
```

**Setiap satu daripada 12 ujian LULUS dlm keadaan perawan pada ≥1 larian penuh hari ini**;
kegagalan tinggal berpindah-pindah rawak dgn punca infra dev Windows yang difahami dan
TIDAK wujud di CI Linux (ulimit tinggi, worker selari, runner segar). Punca deterministik
= 6 semuanya dibaiki (4 kegagalan CI + klik-disabled + throttle-cache). Gate muktamad = CI.

## 13. Penemuan F0e paling penting: `APP_ENV=testing` memecahkan SEMUA upload UI e2e

Larian sasaran guidance-full lokal (selepas menyalin env CI dgn tepat, termasuk
`APP_ENV=testing`) menemui guide `workflow.admin_masjid.muat-naik…` gagal konsisten pada
"Upload complete". Log server:

```
[POST] URI: /livewire/upload-file
testing.ERROR: Disk [tmp-for-tests] does not have a configured driver.
  (FilesystemManager.php:138)
```

Punca: `Livewire\...\FileUploadConfiguration::disk()` → `app()->runningUnitTests()`
(benar apabila env aplikasi = `testing`, TANPA mengira HTTP/console) → paksa disk
`tmp-for-tests` yang hanya didaftar oleh TestCase Livewire dlm ujian unit — TIDAK wujud
pada server HTTP → setiap upload UI 500.

Impak CI sebenar: job env = `APP_ENV: testing`; pada run `06277fc` guidance smoke
TERSELAMAT hanya kerana seeder menyediakan item Peti Masuk (upload tidak dicetus), dan
job shard (satu-satunya laluan yang memuat naik melalui UI) di-skip oleh `needs` — bom
ini pasti meletup pada larian shard pertama. Fix: step Serve override `APP_ENV: local`
pada KEDUA-DUA job (Pest kekal `testing` — phpunit.xml menetapkannya sendiri; satu-satunya
guard `environment()` dlm app/ ialah FailureDrill, guard production — tiada kesan lain).
Pengesahan: guide upload LULUS (50.3s) selepas override; larian pengesahan akhir §14.

## 14. Pengesahan akhir F0e (lokal, keadaan perawan, APP_ENV=local pada server)

```
=== WORKFLOW SHARD PENUH ===
  15 passed (8.0m)
OK [storage/app/plan-ci/guidance-full-workflow.json]: 15 ujian, 0 skipped/timedOut/interrupted/unexpected/flaky.
=== CI-GUIDANCE PENUH (APP_ENV=local) ===
  12 passed (9.0m)
OK [storage/app/plan-ci/ci-guidance.json]: 12 ujian, 0 skipped/timedOut/interrupted/unexpected/flaky.
+ gate screen: screen.klasifikasi-peti-masuk 1 passed (15.8s)
+ gate tenant-admin-public: public.registration 1 passed (5.8s)
+ gate workflow: workflow.admin_masjid.muat-naik… 1 passed (50.3s) — upload UI pulih
```

Semua laluan kod yang disentuh F0e disahkan hijau dlm keadaan perawan.

## 15. F0f — force:true BUKAN penyelesaian overlay; dispatchEvent ganti (run 30745501530)

Run `31abd74` turun 4→2 kegagalan; baki 2 mendedahkan mekanisme sebenar:
`click({force:true})` hanya melangkau SEMAKAN actionability — event mouse tetap dihantar
ke KOORDINAT, dan elemen teratas di koordinat itu ialah overlay SVG tour → overlay
menyerap klik → wizard Livewire tidak maju langsung (`registration-admin` /
`classification-minit` tidak muncul 30s). Windows lokal terselamat kerana butang
kebetulan DALAM lubang sorotan (geometri fon); Linux tidak. `serve-ci.log` run itu:
0 ralat server (bukan isu aplikasi).

Fix: `forceClickWhenEnabled()` → `locator.dispatchEvent('click')` selepas `toBeEnabled()`
(event terus pada ELEMEN — cara rasmi Playwright utk elemen berlapis; handler
Livewire/Alpine menerima tanpa kira lapisan). Checkbox consent guidance-full:
`check({force})` → `evaluate el.click()` bersyarat (idempoten).

Verifikasi lokal keadaan CI-perawan (dispatchEvent):
```
ci-guidance 11/12 (8.4m) + :141 diulang bersendirian 1 passed (4.4m)
  — kegagalan tunggal = ERR_NO_BUFFER_SPACE (flake socket OS Windows, berpindah-pindah;
    kesemua 12 ujian lulus dgn dispatchEvent)
WORKFLOW SHARD PENUH: 15 passed (8.0m) + assert JSON 0 flaky
gate screen: screen.klasifikasi-peti-masuk 1 passed (14.3s)
gate tenant-admin-public: public.registration 1 passed (6.0s)
```

## 16. F0g — race `re-highlight` vs `moveNext` mematikan auto-advance (run 30746704490)

Run `8fcab15` turun 2→1. `dispatchEvent` terbukti betul (wizard Livewire maju: langkah
consent muncul) tetapi TOUR kekal di langkah lama. Petunjuk muktamad dari teks popover
dlm ralat: label CTA bertukar `Buat pada skrin` → `Saya sudah buat` pada langkah SAMA —
bermakna `onHighlighted` berjalan semula tanpa nombor langkah berubah.

Punca (kod dibaca, bukan tekaan):
```
help.js onHighlighted:494  → watchForNextStep(guideSteps, index)   ← dipanggil SETIAP re-highlight
help.js watchForNextStep:359 → clearTransitionWatch()              ← BUNUH jadual moveNext 120ms
help.js watchForNextStep:363 → if (… || resolveStepElement(next))  ← sasaran berikut kini WUJUD
                               return;                             ← poller baharu TIDAK dipasang
```
Morph Livewire (selepas wizard maju) mencetuskan re-highlight Driver.js → jadual advance
dibatalkan → guard menghalang pemasangan semula → auto-advance mati; pengguna terpaksa
tekan "Saya sudah buat". Windows lokal: `moveNext` 120ms menang dahulu; runner Linux: kalah.

Ini **bug produk kelas sync/konteks — skop F2** (§3 `step-advance-plan.js`); `help.js`
TIDAK disentuh pada F0 (§0.3). Ujian meniru laluan pengguna sebenar: `expectStepAdvance()`
menunggu auto-advance 5–10s, jika terkandas tekan "Saya sudah buat" sekali, kemudian
assert. **Wajib jadi ujian regresi F2**: poller advance mesti kekal berfungsi walaupun
sasaran langkah berikut sudah kelihatan semasa re-highlight.

Verifikasi lokal keadaan CI-perawan (muktamad F0g):
```
=== CI-GUIDANCE PENUH ===       12 passed (9.0m)
OK [ci-guidance.json]: 12 ujian, 0 skipped/timedOut/interrupted/unexpected/flaky.
=== WORKFLOW SHARD PENUH ===    15 passed (8.0m)
OK [guidance-full-workflow.json]: 15 ujian, 0 skipped/…
gate screen: screen.klasifikasi-peti-masuk    1 passed (14.8s)
gate tenant-admin-public: public.registration 1 passed (6.1s)
```

## 17. F0h — ⚠️ ISU PRODUK BAHARU: banner tour tolak klik tetikus (run 30747876773)

Run `fd53a81` (3 gagal) membuktikan nudge F0g TIDAK berkesan. Ujian bukti khusus
(scratchpad; CPU throttle + keadaan terkandas dipaksa) mendedahkan DUA punca:

**(a) Banner "Panduan menunggu" tidak boleh diklik dengan tetikus semasa tour aktif.**
Vendor `driver.css`: `.driver-active * { pointer-events: none }` — dikecualikan HANYA
`.driver-active-element` dan `.driver-popover`. Banner ialah anak `<body>` → klik tetikus
diserap overlay SVG (Playwright: `svg.driver-overlay subtree intercepts pointer events`).
Kesan pengguna sebenar: apabila auto-advance kalah race (§16), popover kekal `display:none`
daripada `minimiseForAction` DAN satu-satunya jalan keluar ("Tunjuk arahan") **tidak dapat
diklik** → pengguna tetikus TERKANDAS SEPENUHNYA (perlu muat semula halaman). Hanya papan
kekunci menyelamatkan: `help.js:242` `show.focus()` → Enter menghasilkan event click.

> 🔴 **Untuk F2 (§3):** banner menunggu perlu `pointer-events: auto` (dan ujian regresi
> klik tetikus). `help.js`/`help.css` TIDAK disentuh pada F0 (§0.3). Ujian menggunakan
> `dispatchEvent('click')` = setara laluan papan kekunci yang masih berfungsi.

**(b) Launcher flaky pada ujian 20-konteks**: auto-start/resume dijadualkan **450ms selepas
boot** (`help.js:585`) → boleh muncul SELEPAS `closeGuideIfOpen` pertama; `body.driver-active`
menyembunyikan launcher (`help.css:76`). Kini gelung `expect.poll` 20s yang menutup apa-apa
popover yang menyusul sehingga launcher benar-benar kelihatan.

Bukti laluan pemulihan (2 ujian sementara — LULUS, tidak dikomit):
```
BUKTI A LULUS: popover pulih daripada display:none melalui banner SEBENAR + handler sebenar.
BUKTI B LULUS: keadaan terkandas dipaksa (popover tersembunyi + sasaran langkah 2 wujud)
               → laluan pemulihan membawa tour ke "2 daripada 4".
  2 passed (6.9s)
```
CPU throttle 20× TIDAK mencetuskan race lokal (kedua-dua langkah pulang `auto`) — race ini
khusus runner CI; itulah sebab ia tidak pernah kelihatan sebelum F0.

Verifikasi lokal keadaan CI-perawan (muktamad F0h):
```
=== CI-GUIDANCE PENUH ===    12 passed (9.0m)   OK: 12 ujian, 0 flaky
=== WORKFLOW SHARD PENUH === 15 passed (8.0m)   OK: 15 ujian, 0 flaky
gate screen: screen.klasifikasi-peti-masuk    1 passed (15.4s)
gate tenant-admin-public: public.registration 1 passed (6.0s)
```

## 18. F0i — hapuskan SELURUH kelas race `fill`-vs-morph (run 30749181924)

Run `c90264c`: slug BERGANDA (`masjid-e2e-77422177masjid-e2e-77422177`) → URL tenant salah
→ `waitForURL` 60s. Kod produk BETUL (`updatedName()` mengisi slug hanya bila kosong).
Punca: `fill()` = clear + insertText; morph Livewire yang mendarat ANTARA keduanya
memulihkan nilai lalu insertText menambah di hujung.

**Perubahan kaedah:** setiap run CI setakat ini mendedahkan race BERBEZA (4→2→1→3→1),
kerana mesin Windows terlalu pantas. Daripada menunggu CI satu demi satu, suite kini
dijalankan **di bawah beban CPU buatan (6 proses)** untuk meniru runner CI — teknik ini
berjaya menghasilkan kegagalan yang sama secara tempatan dalam satu larian.

Hasil larian di bawah beban (kod lama) — 3 kegagalan, 2 memberi maklumat baharu:
```
registration.spec  : slug KEKAL "" (30s)  ← wire:model.blur TIDAK dihantar
guidance tour      : slug KEKAL "" (30s)  ← punca sama
explore.spec       : ERR_NO_BUFFER_SPACE  ← socket Windows letih (bukan isu CI)
```
Ini mendedahkan pembetulan pertama SALAH TEMPAT: `selectOption` tidak dijamin memfokus,
jadi menunggu slug tanpa blur = menunggu selamanya. Kini blur dicetuskan **eksplisit**.

Tiga lapisan perlindungan kelas race ini:
1. `fillStable`/`selectStable` (`expect(...).toPass`) — ulang sehingga nilai melekat.
2. `blur()` eksplisit + tunggu morph MENDARAT sebelum menyentuh medan seterusnya.
3. `expectStepAdvance` + `recoverStalledTour` — pulih melalui UI bila auto-advance kalah.

Diterapkan pada semua interaksi borang Livewire/Filament dlm suite CI (login tenant+
superadmin, borang pendaftaran ×3 lokasi, wizard klasifikasi ×2 ujian, metadata/fail).
`waitForTimeout(400/500)` dibuang — digantikan tunggu-nilai sebenar (lebih pantas DAN
kukuh). Telefon tour ditukar unik. `retries` Playwright sengaja KEKAL 0: ketiadaan retry
inilah yang mendedahkan tiga bug produk (§16, §17, dan kelas ini).

Verifikasi pantas selepas pembetulan blur (DB perawan):
```
tour pendaftaran … 1 passed (5.4s)
tour klasifikasi + registration magic link … 2 passed (1.4m)
```

## 19. F0j — upload sebelum FilePond siap (run 30769364093)

**Guidance smoke akhirnya LULUS 12/12 di CI** (§18 berkesan). Kegagalan berpindah ke
`Domain flows` — `office-workflow` gagal pada "Upload complete" (60s). Bukti muktamad dari
`serve-ci.log`: **0 permintaan `/livewire/upload-file` dalam SELURUH larian**. Permintaan
tidak pernah dihantar, jadi puncanya di klien, bukan server.

Punca: Filament memuat JS komponen secara LAZY (`x-load`). `setInputFiles` pada input yang
belum dipasang FilePond hanya menetapkan fail pada DOM — tiada handler, tiada upload.
Runner CI lebih perlahan daripada mesin dev, jadi tetingkap race itu jauh lebih lebar.
(Nota: log CI run terdahulu TIDAK boleh dibandingkan — artifak hanya dimuat naik pada
`failure()`, dan run sebelum ini gagal SEBELUM `ci-domain` sempat berjalan.)

Fix: helper berpusat `e2e/helpers/upload.js` → `attachFile(scope, file)` yang menunggu
`.filepond--root` (bukti skrip dimuat DAN diinisialisasi) sebelum `setInputFiles`.
Digunakan di SEMUA laluan upload: `office-workflow`, `guidance` (ensureInboxFixture),
`guidance-full` (ensureInboxFixture + makro upload workflow); `ocr-upload` menerima
penantian yang sama sebaris (ia memuat 2 fail serentak).

Verifikasi lokal (DB perawan):
```
=== ci-domain PENUH (attachFile) ===  4 passed (1.4m)
OK [storage/app/plan-ci/ci-domain.json]: 4 ujian, 0 skipped/timedOut/interrupted/unexpected/flaky.
PlanManifestTest: 14 passed (2866 assertions)  ← helper baharu tidak melanggar invarian
```

## 20. Pengesahan PRODUKSI (read-only mutlak) — bug §17 memang hidup di bakwim.my

Permintaan pemilik: "boleh test di production terus?" — suite e2e pada produksi DILARANG
(pelan §0.3; ujian MENULIS: daftar masjid, muat naik dokumen, cipta minit; PDPA; audit lalu
tercipta 21 token). Sebaliknya bug §17 disahkan dengan **GET awam sahaja — 0 tulisan,
0 log masuk, 0 kesan pada data**:

```
$ curl -fsS https://bakwim.my/build/assets/help-PP-ALO9e.css   (14,841 bait)
.diwan-tour-waiting{position:fixed;top:.75rem;right:.75rem;z-index:10002;display:flex;…}
   ↑ TIADA `pointer-events` — mewarisi peraturan vendor di bawah
.driver-active *{pointer-events:none}
body.driver-active .diwan-help-launcher-button{visibility:hidden}

$ curl -fsS https://bakwim.my/build/assets/help-pJkQNpPs.js    (32,173 bait)
"Panduan menunggu: ${e.title}"  → banner + butang "Tunjuk arahan" memang dihantar ke pengguna
```

**Kesimpulan:** pada produksi hari ini, pengguna yang menekan "Buat pada skrin" dan
kemudian auto-advance gagal akan **terkandas** — popover tersembunyi dan butang penyelamat
"Tunjuk arahan" menolak klik tetikus (hanya papan kekunci berfungsi). Ini mengesahkan §17
bukan artifak ujian. Pembaikan = F2 §3 (satu baris CSS `pointer-events: auto` + ujian
regresi klik tetikus). Aset produksi kekal `help-PP-ALO9e.css` / `help-pJkQNpPs.js` —
sepadan baseline runtime 5A (§7), jadi tiada drift.

## 21. F0k — gate OCR: kiraan aria-live + selector carian LAPUK (run 30770018483)

Run `a83625e`: **Guidance smoke ✅ (2 kali berturut-turut) DAN Domain flows ✅** (fix §19
berkesan). Kegagalan berpindah ke `OCR upload gate` — kali PERTAMA ia berjalan di CI
(sebelum ini sentiasa gagal lebih awal; sebelum F0 pula ujian ini di-skip kerana fixture
tiada). Dua pepijat ditemui dalam ujian yang tidak pernah dilaksanakan sepenuhnya:

**(a) Kiraan status upload.** `getByText('Upload complete')` memberi **3** padanan untuk 2
fail di CI, tetapi **2** pada mesin dev (diagnosis DOM: 2 × `span.filepond--file-status-main`).
Elemen ketiga = region `aria-live` FilePond yang menyiarkan mesej — kemunculannya bergantung
masa. Fix: `uploadComplete(scope)` dalam helper menyasarkan `.filepond--file-status-main`
dengan teks tepat; guna semula oleh `attachFile`.

**(b) Selector carian tidak pernah wujud.** `input[placeholder*="Cari tajuk"]` — placeholder
sebenar ialah `"Tajuk, rujukan atau kandungan OCR"` (`cari-rekod.blade.php:28`). Kini
`input[wire:model="query"]` (stabil terhadap perubahan teks UI).

Verifikasi lokal (DB perawan, tanpa `ocrmypdf` — carian memadan tajuk fail):
```
[ci-ocr] kerani muat naik imej, OCR siap dan teks boleh dicari … 1 passed (22.7s)
```

## 22. ✅ CI HIJAU PENUH — run 30770625567 (`fb40ff1`) + branch protection

```
$ gh run view 30770625567 --json jobs
success   PostgreSQL, Redis, Meili, OCR and tests
success   guidance-e2e (screen)
success   guidance-e2e (workflow)
success   guidance-e2e (tenant-admin-public)
success   guidance-e2e-gate
success   Docker app image
success   Docker web image
```

Gate agregator pada CI sebenar (bukan tempatan):
```
GATE LULUS: 83 guide · 473 langkah · 229 langkah tindakan — union tiga shard sepadan
manifest (set, bukan kiraan). Laporan: storage/app/plan-f6/coverage-gate.json
```

Branch protection ditetapkan **TEPAT 4** (senarai A; shard/step = bukti keluaran B):
```
$ gh api …/branches/main/protection --jq '.required_status_checks.contexts[]'
PostgreSQL, Redis, Meili, OCR and tests
guidance-e2e-gate
Docker app image
Docker web image
   (strict: true)
```

### Perjalanan CI F0 — 7 pusingan, setiap satu punca BERBEZA
| Run | Gagal | Punca sebenar (semua tulen, bukan gate cerewet) |
|---|---|---|
| `06277fc` | 4 | keadaan perawan: tour auto-resume, klik diserap overlay, ENOENT log, mail env |
| `31abd74` | 2 | `force:true` melangkau semakan enabled → klik semasa `wire:loading` hilang |
| `8fcab15` | 1 | klik koordinat (walau force) diserap overlay SVG → `dispatchEvent` |
| `fd53a81` | 3 | **banner tour menolak klik tetikus** (`pointer-events:none` vendor) |
| `c90264c` | 1 | **nilai medan BERGANDA** — `fill()` vs morph Livewire |
| `a83625e` | 1 | upload sebelum FilePond siap → 0 permintaan `/livewire/upload-file` |
| `fb40ff1` | **0** | ✅ HIJAU |

Tiga daripadanya ialah **bug produk sebenar** (§16, §17, §18) — bukan masalah ujian — dan
§17 disahkan hidup di produksi (§20). Retry Playwright sengaja kekal 0: ketiadaan retry
itulah yang mendedahkannya.

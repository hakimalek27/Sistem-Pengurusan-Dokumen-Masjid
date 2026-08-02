# PELAN PEMBAIKAN — Diwan (SPDM) · Susulan Audit Round-Robin 14 Pusingan

**Versi pelan:** 1.11 — ✅ **MUKTAMAD** (ditutup Claude P27, 2 Ogos 2026, menurut syarat #6:
**Codex P26 = pusingan Codex PERTAMA tanpa pindaan substantif** — keputusan rasmi (a) "TIADA
PENAMBAHBAIKAN SUBSTANTIF — pelan sedia muktamad", `PLAN-RR-26-CODEX.md`, selepas 26 pusingan
berselang-seli dan 11 versi pelan. Pelaksanaan F0–F10 kini menunggu SATU perkara sahaja:
**arahan mula pemilik** — jawapan D1–D11 sudah lengkap dalam `KEPUTUSAN-PEMILIK.md`.
Nota sejarah: status muktamad v1.3 terdahulu **DIBATALKAN** oleh
`PLAN-RR-10-CODEX-AUDIT-LENGKAP.md`: fail `PLAN-RR-06`/`PLAN-RR-08` ditulis proses automatik
ketika giliran Codex, jadi penutupan P9 tidak berasaskan ulasan Codex autoritatif.
v1.4 mengintegrasikan keputusan C01–C25 dalam `PLAN-RR-11-CLAUDE-AUDIT-LENGKAP.md`;
v1.5 mengintegrasikan keputusan P12-01…P12-08 dalam `PLAN-RR-13-CLAUDE.md`;
**v1.6 ialah SERAHAN SEPARA** — Claude Pusingan 15 mengintegrasikan majoriti P14-01…P14-08 ke
dalam badan pelan tetapi **terputus sebelum menghasilkan `PLAN-RR-15-CLAUDE.md`, footer dan
kemas kini status**; percubaan menyambungnya gagal dengan had perbelanjaan bulanan
(*"You've hit your monthly spend limit"*). **`PLAN-RR-15-CLAUDE.md` TIDAK WUJUD dan tidak akan
dicipta secara retroaktif** — rekod keputusan P14 kini dibawa oleh `PLAN-RR-17-CLAUDE.md` §3.
Codex Pusingan 16 mengaudit keadaan separa itu; v1.7 mengintegrasikan keputusan
P16-01…P16-08 dalam **`PLAN-RR-17-CLAUDE.md`**; v1.8 mengintegrasikan keputusan
P18-01…P18-07 dalam `PLAN-RR-19-CLAUDE.md`; v1.9 mengintegrasikan keputusan P20-01…P20-06
dalam `PLAN-RR-21-CLAUDE.md`; v1.10 mengintegrasikan keputusan P22 Titik 1–6 + imbasan
(3 P1 + 2 P2 diterima; 4 titik disahkan tanpa pindaan) dalam `PLAN-RR-23-CLAUDE.md`;
**v1.11 mengintegrasikan satu-satunya isu P24 (P24-T4: label "bersyarat" OCR #16a-c terbatal
oleh "luluskan semua" + typo "D10-16"→"D11") dalam `PLAN-RR-25-CLAUDE.md` — P24 mengesahkan
LULUS semua titik lain (ServeCommand+probe, upload dua-laluan, kiraan 19+1, §11, imbasan
corak) — dan menunggu audit penutup Codex Pusingan 26**.)
**Tarikh:** 2 Ogos 2026 · **Asas kod:** `8342d95` (local = origin = server)
**Rujukan penemuan:** `FINAL-RUMUSAN.md` + `STATUS.md` + `PUSINGAN-01…14`
**Status pelaksanaan:** ⛔ **BELUM DILAKSANAKAN — dokumen perancangan sahaja.** Tiada kod diubah.
**Prasyarat pelaksanaan:** ✅ **keputusan pemilik D1–D11 LENGKAP diterima 2 Ogos 2026**
(`KEPUTUSAN-PEMILIK.md` — D1 Ya · D2 Ya · D3 Kekal · D4 Ya · D5 Ya(a+b) · D6 Terima ·
D7 GABUNG · D8/D9/D11 ikut cadangan · **D10 LULUS**); baki prasyarat = **arahan mula pemilik**
+ pelan ditanda muktamad selepas pusingan Codex tanpa penambahbaikan substantif.

> **Log versi:** v1.0 draf Claude → v1.1 selepas Codex P2: kontrak `skipRender` dibetulkan,
> `$wire.set()` pada `#[Locked]` digugurkan, semantik `aria-modal`/fokus diperhalusi,
> auto-minimize jadi overlap-aware, mekanisme pengesahan Filament ditetapkan
> (`get*FormAction()` + parent), denominator F6 dibekukan (kohort 25/124), `recordActionsColumnLabel()`
> terus digunakan, link-name guna teks eksplisit, urutan deploy migrate-dahulu + force-recreate
> nginx, peta ID dilengkapkan (RR-02-04/RR-04-02 ditutup-lulus), jurang ujian per fasa ditambah.
> → **v1.2** selepas Codex P4: trap fokus DIBUANG dari popover utama bukan-modal (pengurusan
> fokus tanpa trap; trap+`aria-modal` hanya fallback modal), kontrak test-hook browser dipakukan
> (`__DIWAN_E2E__` → `globalThis.__diwanHelpTest`, tiada dalam produksi biasa), verifikasi
> deploy `nginx -t` **dan** `nginx -T` via `compose exec`, laluan manifest penuh
> `Audit Review Round Robin/bukti/plan-baseline/`.
> → **v1.3** selepas Codex P6 (pengesahan): 2 kontradiksi silang sisa dibetulkan — jadual fail
> §3.5 kini menskop trap+`aria-modal` kepada fallback sahaja + `clearFocusManagement()`
> (selaras §3.4), dan rujukan manifest F8 §9 diselaraskan ke laluan penuh (selaras F0).
> → **v1.4** selepas audit lengkap Codex P10 (25 pindaan, 8 bloker; keputusan Claude satu per
> satu dalam `PLAN-RR-11-CLAUDE-AUDIT-LENGKAP.md` — **20 TERIMA, 5 TERIMA SEBAHAGIAN, 0 TOLAK**):
> **fakta dibetulkan** — placeholder `Langkah N` = **258** (bukan 444), generik = 443, katalog
> penuh = **83 guide / 473 langkah** (bukan 68/433 sebagai denominator keluaran), **lima** label
> `Edit` (bukan tiga), **18** kelas notifikasi ber-`toMail()`;
> **kontrak dibetulkan** — Driver.js vendor SUDAH memerangkap Tab (trap custom digugurkan
> sepenuhnya), auto-start jadi **one-shot**, hook ujian global `__DIWAN_E2E__` diganti modul
> tulen + assertion black-box, `.wrap` guest TIDAK menjadi `<main>`, sasaran `sidebar` perlu
> peta responsif, sasaran muat naik dipecah, nama pautan Duplikat mesti padan destinasi;
> **skop diperluas** — F6 meliputi 83/473 (v1.4: 6 gelombang; **dipecah semula kepada 7 wave
> W0–W6 dalam v1.6** — lihat §1 F0(ii-a)), F9 regenerasi Manual Pengguna,
> F10 housekeeping keluar dari F4, gate CI Playwright, matriks produksi 20 BrowserContext,
> rantaian bukti runtime imej, gate carian Meili/PHP, matriks keselamatan setiap fasa;
> **D2 dinaikkan taraf** kepada perubahan produk yang memerlukan **Addendum spec v2.6**.
> → **v1.5** selepas audit integrasi Codex P12 (8 pindaan; keputusan Claude satu per satu dalam
> `PLAN-RR-13-CLAUDE.md` — **6 TERIMA, 2 TERIMA SEBAHAGIAN, 0 TOLAK**):
> **kontrak dibetulkan** — `launchPending` kini **`#[Locked]`** (klien tidak boleh menetapkannya
> ke mana-mana arah; one-shot dipadam hanya oleh server) · kolum Duplikat guna
> **`disabledClick()`** (API semasa; `disableClick()` ialah alias *deprecated*
> `CanBeDisabled.php:30`) dan kontrak "klik sel membawa ke rekod" dibuang;
> **urutan kerja diterbalikkan** — F6 disusun mengikut risiko pengguna sebenar: **W1 `screen`
> kritikal, W2 `workflow` kritikal, W3 baki `screen`, W4 baki `workflow`, W5 `tenant`+`admin`,
> W6 `public`** (kerana kesemua **200/229** langkah tindakan bersasar generik berada dalam
> `screen`+`workflow`, dan kohort `tenant`/`admin` mempunyai **0** langkah tindakan);
> **gate dikeraskan** — persampelan **bukan lagi** gate F6/F8: status **per-langkah** untuk
> kesemua 473 langkah, DOM hidup untuk semua sasaran `specific`, tour black-box untuk kesemua
> **229** langkah tindakan, dan kitaran mula/maju/tutup/ulang/resume untuk kesemua **83** guide;
> **CI dibetulkan** — GitHub Actions **tidak** berkongsi service containers antara job, jadi
> langkah Playwright masuk ke dalam job `integration` sedia ada (bukan job `e2e` baharu yang
> "guna semula" servicenya);
> **harness produksi dibetulkan** — matriks 20 konteks ialah `e2e/guidance.spec.js:124`, tetapi
> ia **tidak** page-by-page pada mobile (`:157`, `:183` mengehadkan lawatan kepada desktop;
> `:170`/`:206` merekod `navigation.length || 1`) dan `e2e/production-readonly.spec.js:66`
> **bukan** matriks 20 konteks (satu viewport, `toBe(accounts.length)`, tanpa public/superadmin,
> jarak log masuk lalai **0 ms**) → F8 mengekstrak spec produksi read-only khusus;
> **rantaian bukti imej dibetulkan** — `app`/`worker`/`scheduler` → **`diwan-app`**, `nginx` →
> **`diwan-web`** (dua keluarga imej, bukan satu ID), dan nama aset **exact** diambil daripada
> `manifest.json` (wildcard `help-*.js` pada URL HTTP dilarang);
> **polisi pakej diketatkan** — `axe-core` **tidak** ditambah secara lalai; ia memerlukan
> **pengecualian polisi bertulis** kepada `CLAUDE.md:10` sebelum `package.json`/lockfile berubah
> (D5 dipecah kepada dua soalan eksplisit).
> → **v1.6 (SERAHAN SEPARA — P15 terputus)** selepas audit kebolehjalanan literal Codex P14
> (8 pindaan; keputusan Claude satu per satu **kini direkod dalam `PLAN-RR-17-CLAUDE.md` §3**,
> kerana `PLAN-RR-15-CLAUDE.md` tidak pernah wujud — **8 TERIMA (3 daripadanya TERIMA +
> DIKUATKAN), 0 TOLAK**):
> **CI dibetulkan pada tahap boleh-jalan** — sesi HTTP `SESSION_DRIVER=file` (bukan `array`,
> `.github/workflows/ci.yml:67`), `APP_URL`/`E2E_BASE_URL` dipakukan pada **8092**
> (CI kini `:56` = 8080), **canary log masuk + redirect** sebelum suite, dan skop dibekukan
> sebagai project Playwright **`ci-guidance`** dengan `testMatch` exact + **satu command literal**;
> **CI dipisah dua lapis** — job `integration` membawa **smoke** Playwright sahaja, gate penuh
> berpindah ke job matriks **`guidance-e2e`** (3 shard yang **mengisytiharkan `services:` sendiri**)
> + job agregator **`guidance-e2e-gate`** sebagai required check dengan denominator
> **473/229/83** diassert daripada gabungan artifak shard;
> **manifest ketiga `role_routes` ditambah** kepada F0 — public + superadmin + **lapan** role
> tenant, positive **dan** negative matrix, kiraan halaman **dikira daripada array**, dan
> `AKSES-PAGE-MENGIKUT-ROLE-PRODUCTION-2026-07-21.md` **dijana** daripada sumber sama
> (dokumen itu kini drift: `:12` merekod Admin 21 halaman, `e2e/guidance.spec.js:14` menjangka 25);
> **runner produksi dinamakan dan dibuat idempotent** — `e2e/production-guidance-readonly.spec.js`
> + wrapper `scripts/audit/run-production-guidance-readonly.ps1`, command exact, `run_uuid`,
> slug unik `smoke-<run_uuid>` (**slug statik `smoke` DILARANG dipadam** — ia milik
> `diwan:smoke` `SmokeE2E.php:33,50` yang menjadi gate deploy §10), inventori before/created/after
> dan cleanup `try/finally` **hanya ID larian**;
> **gelombang F6 dibekukan exact** — `~10`/`~6` diganti partition deterministik berasaskan
> katalog: **W0 6 defect mobile · W1 28 · W2 13 · W3 1 · W4 1 · W5 35 · W6 3** guide
> (10+140+145+11+13+146+8 = **473** langkah), dan enam langkah popover mobile
> (`tenant.pelupusan#1`, `tenant.kegemaran#1–5`) **dinaikkan ke W0 selepas F2**, bukan W5;
> **semantik status dibetulkan** — `blocked` = **release blocker** (`blocked=0` wajib untuk
> menutup F6/F8), `risk-accepted` menjadi kategori berasingan dengan fallback artikel diuji +
> tiket + pemilik + tarikh luput;
> **command audit dibetulkan** — `grep … (?!nya)` (BRE tiada lookahead) dan gate bundle
> `grep -c` berasaskan output diganti `rg` berasaskan **exit code** + guard senarai fail kosong;
> **protokol snapshot ditambah (§0.7)** — hash/saiz/mtime direkod sebelum audit dan dakwaan
> sejarah yang tidak boleh dibuktikan daripada snapshot immutable **tidak** dijadikan keputusan
> produk.
> → **v1.7** selepas audit keadaan separa Codex P16 (8 pindaan; keputusan Claude satu per satu
> dalam `PLAN-RR-17-CLAUDE.md` — **7 TERIMA (3 daripadanya TERIMA + DIKUATKAN) · 1 TERIMA
> SEBAHAGIAN · 0 TOLAK**):
> **canary CI menjadi spec sebenar** — pseudokod `curl` GET/POST v1.6 **dibuang**: borang log
> masuk ialah komponen **Livewire** (`vendor/filament/filament/src/Auth/Pages/Login.php:459`
> `->livewireSubmitHandler('authenticate')`) dengan medan `data.login`, bukan `email`
> (`app/Filament/Auth/Login.php:21`), jadi POST borang HTML biasa **mustahil** berjaya; digantikan
> `e2e/ci-session-canary.spec.js` bertag `@session-canary` + command literal;
> **shard/agregator dibekukan pada tahap boleh-jalan** — nama spec/project/command ketiga-tiga
> shard, skema JSON artifak, `scripts/audit/aggregate-guidance-coverage.mjs`, YAML
> `services`/`needs`/`matrix`/upload/download dan required gate ditulis penuh; **agregator
> membanding SET ID dengan manifest, bukan count** — dan kunci set ialah **`<guide_id>#<index>`**
> kerana `step.id` katalog **tidak unik global** (`dashboard.1/2/3` berulang antara
> `tenant.dashboard` dan `admin.dashboard`; 473 langkah → hanya **470** `step.id` unik);
> **D11 dikembangkan daripada 4 artifak kepada jadual fail F0 penuh** (canary, spec penuh,
> agregator, validator manifest, spec produksi read-only, wrapper, command setup/cleanup, ujian
> idempotensi, project CI domain) — *(kiraan muktamad v1.8: **16 fail repo + 1 artifak audit**;
> angka perantaraan "12" dan "14" v1.7 **DIBATALKAN**, lihat §11 D11)*;
> **setup/cleanup produksi dinamakan** — `diwan:audit-fixture prepare|cleanup|inventory`, argumen,
> sempadan authorization, fail rahsia sementara ber-ACL + `try/finally`, `-CleanupOnly`, dan
> **kontrak `RunUuid` disatukan** (caller-provided untuk reproducibility; wrapper menjana hanya
> jika tidak diberi; nilai sebenar **sentiasa** direkod);
> **senarai ID wave dijana semula dan dibekukan** — `wave`/`shard` menjadi medan **snapshot
> manifest**, senarai 83 ID exact disenaraikan dalam `PLAN-RR-17-CLAUDE.md` §5 (bukan dalam fail
> P15 yang tidak wujud), dengan **validator set-union exact**;
> **gate `rg` dibetulkan sepenuhnya** — `! rg` **dibuang** daripada kedua-dua gate: ia menukar
> **exit 2** (laluan tiada / regex rosak — kedua-duanya disahkan pada mesin ini) menjadi "lulus";
> corak baharu mengassert **input files > 0** dan membezakan rc 1 daripada rc ≥2;
> **`role_routes` menjadi kontrak tiga lapis** — `expected_access` dijana daripada
> **matriks kebenaran `config/roles.php:55-124` + policy/spec**, `declared_access` daripada
> penilaian authorizer kod, `actual_status` daripada probe; **mismatch mana-mana pasangan = gagal**
> (nilai probe **tidak pernah** menulis semula expected), dan **route universe dibina tanpa
> tapisan identiti** supaya negative matrix tidak hilang;
> **suite domain masuk gate** — `office-workflow` + `ddms-extended` menjadi project CI
> `ci-domain` yang **wajib hijau sebelum deploy F8** *(v1.8: sebagai **step** dalam job
> `integration`, bukan sebagai required status check — §1 F0(iv)(f))*; `ocr-upload` masuk
> `ci-ocr` **hanya selepas
> fixture dikomit** dan gate mengassert ia **tidak di-skip** (kini `ocr-upload.spec.js:6`
> `test.skip` senyap tanpa 4 env var). *(Pembetulan fakta terhadap P16-08: **fixture antivirus
> tidak diperlukan** — `config/diwan.php:32` `CLAMAV_ENABLED` lalai **false** dan
> `AntivirusScanner.php:12` pulang awal, jadi tiada service ClamAV perlu ditambah.
> ⚠️ **Diperjelas v1.8 (P18-04): "tiada service ClamAV" ≠ "tiada gate antivirus".** Yang tidak
> diperlukan ialah *container* ClamAV dalam CI; yang **masih hilang** ialah ujian yang
> membuktikan `InboxIngestService` fail-closed apabila ClamAV **diaktifkan** — lihat §0.6 S7 +
> D11 fail #14.)*
> → **v1.8** selepas audit integrasi Codex P18 (7 pindaan; keputusan Claude satu per satu dalam
> `PLAN-RR-19-CLAUDE.md` — **7 TERIMA (2 daripadanya TERIMA + DIKUATKAN) · 0 TERIMA SEBAHAGIAN ·
> 0 TOLAK**):
> **kontrak branch protection dibetulkan** — required status check ialah **nama check GitHub
> Actions**, bukan nama project Playwright. `ci-domain` ialah project Playwright yang dijalankan
> sebagai **step** dalam job `integration`, jadi ia **tidak boleh** menjadi required check; nama
> check job sedia ada pula ialah **`PostgreSQL, Redis, Meili, OCR and tests`** (`ci.yml:19`),
> bukan `integration`. Senarai required dibekukan kepada tiga nama exact + command
> `gh api …/check-runs` untuk mengesahkannya sebelum tetapan repo diubah (§1 F0(iv)(f));
> **YAML CI menjadi literal sepenuhnya** — placeholder `{ image: …, ... }` dan komen
> *"setup … sama lapis 1"* dibuang; blok `services:`/`env:`/`steps:` job `guidance-e2e` ditulis
> penuh (disalin daripada `ci.yml:22-51`, `:52-80`, `:82-121` dengan lima override yang
> dinamakan), supaya implementer menyalin, bukan mereka bentuk;
> **kontrak kredensial superadmin produksi dibekukan pada satu pilihan** — `diwan:audit-fixture`
> **tidak pernah** mencipta atau menulis kredensial superadmin (mustahil mendapat plaintext
> daripada hash; mencipta superadmin sementara pada produksi = identiti akses-penuh baharu pada
> sistem live). `E2E_PROD_SUPERADMIN_EMAIL`/`_PASSWORD` dibekalkan **dari luar**, hanya
> **disahkan hadir**, dan lalai diam `guidance.spec.js:27-28`
> (`superadmin@diwan.test` / `password`) **dilarang** pada laluan produksi;
> **gate antivirus fail-closed ditambah** — `tests/Feature/InboxAntivirusFailClosedTest.php`
> (D11 #14) memock `AntivirusScanner` untuk `infected`/`unavailable`/`error` dan mengassert
> **tiada** `Record`, media atau log aktiviti tercipta (`InboxIngestService.php:72-78` ialah satu
> `throw` sebelum `DB::transaction` `:91` — jadi assertion "0 rekod" ialah ujian regresi sebenar,
> bukan hiasan). Tiada service ClamAV diperlukan;
> **bukti "tidak di-skip" mendapat reporter** — `playwright.config.js:9` hanya `[['line']]`, jadi
> `results.json` **tidak pernah wujud**. Reporter JSON bersyarat-env ditambah (§1 F0(iv)(e)),
> setiap command CI menetapkan `DIWAN_PW_JSON` sendiri, dan
> `scripts/audit/assert-playwright-json.mjs` (D11 #15) menggagalkan larian yang mempunyai
> `skipped`/`timedOut`/`interrupted` **atau sifar ujian**;
> **kiraan D11 menjadi satu angka** — **16 fail repo + 1 artifak audit** (nombor 12 dan 14
> dibuang daripada seluruh dokumen);
> **pernyataan integriti dibezakan** — "working tree bersih" digantikan dua dakwaan berasingan
> (kod aplikasi vs fail perancangan), setiap satu dengan command buktinya (§0.7 #7).
> → **v1.9** selepas audit integrasi Codex P20 (6 pindaan; keputusan Claude satu per satu dalam
> `PLAN-RR-21-CLAUDE.md` — **5 TERIMA (2 daripadanya TERIMA + DIKUATKAN) · 1 TERIMA SEBAHAGIAN
> (P20-06) · 0 TOLAK**):
> **lapis 1 menjadi YAML literal** — §1 **F0(iv)(d-1) baharu** menulis penuh enam step yang
> ditambah ke job `integration` selepas `Migrate PostgreSQL and run full suite`, dengan env
> **server** (bukan env step Playwright) sebagai override eksplisit; **penguatan yang P20 tidak
> kemukakan:** `php artisan serve` **menapis** env yang dihantar kepada proses server —
> `ServeCommand.php:184-189` menetapkan setiap kunci di luar `$passthroughVariables` (`:79-94`;
> `APP_ENV` + PATH/Herd/Xdebug sahaja — **`APP_URL`, `SESSION_DRIVER`, `DB_*` tiada di dalamnya**)
> kepada `false` apabila fail `.env` wujud, jadi override boleh hilang **secara senyap**; command
> dibekukan sebagai **`php artisan serve --no-reload`** kerana `:184` memintas penapisan itu
> sepenuhnya tanpa mengira kewujudan `.env`;
> **`trap … EXIT` dibuang daripada langkah serve** — setiap `run:` GitHub Actions ialah shell
> berasingan, jadi trap membunuh server pada penghujung step itu, sebelum Playwright bermula;
> digantikan **step cleanup `if: always()`** yang membunuh `serve_pid` daripada `$GITHUB_ENV`
> (kedua-dua lapis 1 dan `guidance-e2e`);
> **gate Meilisearch C20 menjadi step CI yang mengikat** — bukan lagi nota naratif:
> `SCOUT_DRIVER=meilisearch php artisan diwan:sync-help-index --delete` + assert output
> mengandungi **`83 guide disegerakkan ke indeks diwan_help_guides.`**, kerana
> `SyncHelpIndex.php:38` pulang **awal** (SUCCESS) apabila driver bukan Meili — jadi langkah
> `Validate help catalog` sedia ada (`ci.yml:123-126`, `SCOUT_DRIVER: collection`) **tidak pernah**
> menyentuh Meilisearch — dan `HelpSearchService.php:24-45` mencuba Meili lalu **fallback senyap**
> ke PHP pada `Throwable`, jadi spec carian boleh hijau di atas indeks kosong;
> **`guidance-full` kini di-assert** — `node scripts/audit/assert-playwright-json.mjs` dipanggil
> selepas setiap shard (bukan canary sahaja), dan assertion **(7) `stats.skipped === 0`**
> ditambah sebagai penjaga langsung di atas pemeriksaan `result.status` sedia ada;
> **artifak JSON dimuat naik** — `if: always()` + `if-no-files-found: error` untuk setiap gate
> wajib (canary · `ci-guidance` · `ci-domain` · shard `guidance-full`), `retention-days: 14`;
> `ci-ocr` sahaja `if-no-files-found: ignore` sehingga fixture dikomit;
> **required check dipisahkan daripada bukti keluaran** — "tepat tiga" **dibatalkan**: senarai
> branch protection ialah **tepat empat nama check** (`PostgreSQL, Redis, Meili, OCR and tests` ·
> `guidance-e2e-gate` · `Docker app image` · `Docker web image`), manakala step/project/shard
> dipindahkan ke senarai **bukti keluaran** yang berasingan (§1 F0(iv)(f));
> **lokasi artifak transient diputuskan** — root `bukti/` **tidak** dicipta; output CI berpindah ke
> **`storage/app/plan-ci/`** dan **`storage/app/plan-f6/`** yang **sudah** diabaikan git
> (`storage/app/.gitignore` = `*` + `!private/` + `!public/` + `!.gitignore`), jadi **tiada**
> perubahan `.gitignore` dan **D11 kekal 16 fail repo + 1 artifak audit**. Cadangan P20 supaya
> menggunakan `test-results/plan-*` **ditolak dengan bukti**: Playwright memadam `outputDir`
> setiap larian (`node_modules/playwright/lib/runner/index.js:5943-5962`,
> lalai `test-results` — `lib/program.js:190`), jadi larian kedua dalam job yang sama akan
> memusnahkan JSON larian pertama.
> → **v1.10** selepas Codex P22 (audit v1.9; 3 P1 + 2 P2, semuanya diterima dengan verifikasi
> bebas Claude): **naratif `ServeCommand` dibetulkan** — penapisan env hanya menyentuh ahli
> `$_ENV`, dan dengan `variables_order=GPCS` (lalai setup-php) `$_ENV` kosong → Symfony Process
> fallback `getenv()` mewarisi `APP_URL`/`SESSION_DRIVER` walau `.env` wujud; `--no-reload`
> KEKAL wajib sebagai satu-satunya perlindungan tanpa syarat + step probe `variables_order`
> sebagai bukti larian pertama; **semantik upload dipisah dua-step** (`if: success()` +
> `if-no-files-found: error` untuk "lulus tapi bukti hilang" vs `if: failure()` + `ignore`
> supaya kegagalan asal kekal diagnosis utama) pada lapis 1, shard, dan agregator; **dakwaan
> storage dikecilkan** kepada keunikan awalan `plan-*` (kod memang guna `manual-capture`/
> `backup-temp`/`tmp` di luar private/public); **kiraan D11 dinormalisasi** — 16 ENTRI =
> **19 fail repo fizikal + 1 bundle audit** (#13a/b dipecah, #16a-c dinamakan exact dan tidak
> lagi bersyarat kerana pemilik "lulus semua"); **semua ayat "menunggu jawapan pemilik"
> dikemas** — D1–D11 LENGKAP dijawab (`KEPUTUSAN-PEMILIK.md`), Lampiran A1 kekal menunggu
> (ia tindakan, bukan keputusan D). Titik yang DISAHKAN TIDAK perlu pindaan: gate Meilisearch
> (`grep -qF` = pengikat tambahan atas `stats()` yang sudah deterministik), `stats.skipped===0`
> (ujian ditapis `--grep`/project tidak dikira skipped oleh reporter), nama check matriks
> Docker + kaedah `gh api …/check-runs`.

---

## 0. Prinsip, kekangan & skop

### 0.1 Kekangan projek yang MESTI dihormati (dari `CLAUDE.md` repo)

1. `DIWAN-SPEC.md` v2.1 (+ addendum v2.2–v2.5 dalam `DIWAN-SPEC-ADDENDUM-2026-07.md`) ialah
   sumber kebenaran. Pembaikan di sini adalah pembetulan kualiti, bukan ciri baharu.
   **Satu pengecualian yang diisytihar (P11/C01):** menukar lalai `auto_disposal_enabled`
   (§5.3/L2) **bercanggah** `DIWAN-SPEC.md:470` (`boolean default true`), §16.1, Aliran L
   dan teks pengakuan §16.2 → ia **perubahan produk**, bukan pembetulan kualiti, dan
   **mesti melalui Addendum spec v2.6 bernombor yang diluluskan pemilik** sebelum sebarang
   migrasi ditulis (lihat §5.3 + D2 + D10).
2. **DILARANG** menukar versi pakej §3.2/§3.3 atau menambah pakej luar senarai.
   **Polisi pakej — dibetulkan P12-08 (premis v1.4 ditarik balik).** `CLAUDE.md:10` berbunyi
   *"DILARANG: … menukar versi pakej §3.2/§3.3; menambah pakej luar senarai"* — larangan
   "menambah pakej luar senarai" ditulis **secara umum** dan **tidak** dihadkan kepada Composer.
   v1.4 berhujah bahawa kerana spec tidak menyenaraikan npm langsung, `axe-core` "bukan
   percanggahan". Hujah itu **tidak selamat**: jika tiada senarai npm, maka **setiap** pakej npm
   berada "luar senarai" — kehadiran `driver.js`/`pdfjs-dist`/`@playwright/test` membuktikan
   preseden **sejarah**, bukan kebenaran untuk menambah yang baharu. Peraturan `CLAUDE.md:3`
   pula memerintahkan: apabila spec **kabur atau bercanggah**, BERHENTI dan tanya pemilik —
   bukan pilih tafsiran sendiri. Maka:
   - **Lalai semasa: JANGAN tambah `axe-core`** (atau mana-mana pakej npm baharu). Laluan
     kebolehcapaian lalai ialah alat luaran/manual yang **tidak menyentuh** `package.json`
     mahupun `package-lock.json` (§8.5).
   - Jika pemilik mahukan `axe-core` dalam `devDependencies`, **dua** kelulusan diperlukan
     mengikut urutan: **(a)** pengecualian bertulis kepada polisi repo/spec yang menamakan pakej
     dan mengehadkan skopnya kepada *dev-only*, direkod dalam dokumen kawalan
     (`CLAUDE.md` atau addendum spec); **(b)** barulah `package.json`/lockfile boleh berubah.
   - D5 (§11) bertanya **kedua-dua** perkara ini secara berasingan dan eksplisit. Tanpa (a),
     laluan manual §8.5 dipakai dan **F7 tidak tersekat**.
3. Semua teks UI **Bahasa Melayu**; kod (kelas/jadual/pemboleh ubah) Bahasa Inggeris.
4. Pengasingan tenant = keperluan #1. Tiada perubahan dalam pelan ini menyentuh laluan
   kebenaran/tenancy — dan ujian isolasi penuh tetap dijalankan setiap fasa sebagai penjaga regresi.
5. Ralat ujian: baiki punca, bukan ujian; ubah ujian hanya jika ujian melanggar spec (dicatat).
6. Satu fasa satu masa; commit per fasa `git commit -m "fix-audit-FN: <ringkasan>"`;
   **CI mesti hijau sebenar** (`gh run list --json conclusion`, JANGAN pipe `gh run watch --exit-status`).

### 0.2 Skop pelan

| Dalam skop | Luar skop |
|---|---|
| Kesemua 7 keutamaan `FINAL-RUMUSAN.md` §4 | Ciri baharu di luar penemuan audit |
| Kesemua ID RR-01-01 … RR-11-05 yang disahkan dua-hala | Penemuan yang DITOLAK audit (RR-02-02 listener leak — terbukti tiada) |
| Item integriti proses audit (RR-11-01/06) — sebagai amalan, bukan kod | Ujian beban/DDoS |
| Housekeeping berkait (Lampiran A) | Naik taraf infrastruktur (RAM 4GB — nota sahaja) |

### 0.3 Perkara yang TERBUKTI SIHAT — TIDAK akan disentuh

Isolasi tenant (baca+tulis) · kebenaran role · enjin retensi/pelupusan (logik) · mekanisme sync
tour (MutationObserver+poll 120ms — **berfungsi 1045ms**, jangan diubah semasa refactor F2!) ·
header keselamatan · magic link interstisial · intake WA kata-kunci-dahulu. Setiap fasa mesti
membuktikan suite penuh Pest hijau supaya kawasan sihat ini tidak regres.

### 0.4 Peta penemuan → fasa (semua penemuan terbuka + status penemuan ditutup)

Peta ini meliputi **semua penemuan yang memerlukan tindakan** dan merekodkan status penemuan
yang ditutup semasa audit (supaya tiada ID hilang tanpa jejak).

| ID penemuan | Ringkasan | Fasa |
|---|---|---|
| RR-01-02 / RR-02-01 / RR-10-01 / RR-11-02 | Konteks HelpLauncher hilang selepas Livewire | **F1** |
| RR-01-11 | helpUrl root `//` | **F1** |
| RR-01-07 / RR-03-03 / RR-10-06 | Label butang tour ≠ kelakuan (2 predikat) | **F2** |
| RR-01-04 | Label EN pada popover fallback (`← Previous`, `1 of 1`) | **F2** |
| RR-08-03 | Overlay tour menghalang modal (mobile) | **F2** |
| RR-03-02 / RR-02-03 | Fokus keluar overlay (status audit: *sebahagian/pengerasan* — bukan pepijat disahkan penuh; ARIA asas lengkap, trap fokus tiada) | **F2** |
| RR-11-04 (subbutir CTA) | CTA tidak konsisten (20× "Buat pada skrin" langkah generik) | **F2** |
| RR-01-03 / RR-03-01 / RR-02-05 | Tiada `lang/ms/` — validasi + kerangka e-mel EN (audit uji 9; **liputan sebenar 18 kelas `toMail()`** — C19) | **F3** |
| RR-05-02 | Validasi rojak `The failkan Ke field is required.` | **F3** |
| RR-05-01 / RR-08-04 | Vendor wizard `Seterus`/`Sebelum` + 3 arahan katalog salin ejaan | **F3** (vendor+katalog) |
| RR-01-05 | Label `Edit` hard-coded — **5 lokasi** (audit asal jumpa 3; +`TetapanPlatform.php:43`, +`TetapanMasjid.php:58` — C10) | **F3** |
| RR-08-01 / RR-09-01 | Auto-padam = lalai (3 lapisan) | **F4** |
| RR-01-01 / RR-08-02 | Tour `/log-masuk` ralat palsu | **F5** |
| RR-01-08 | Tour muat naik minta tindakan pada UI yang tidak dibuka | **F5** |
| RR-01-09 | Arahan bercanggah dengan sorotan (dashboard) | **F5** |
| RR-01-10 / RR-10-03 / RR-11-04 (subbutir tajuk) | 77/124 tajuk = penerangan (duplikasi) | **F5** |
| RR-10-04 / RR-11-04 (subbutir terpotong) | 20/124 tajuk terpotong `...` | **F5** |
| RR-01-06 / RR-03-04 / RR-10-02 / RR-11-03 | 119/124 langkah sorot generik | **F6** |
| RR-10-05 / RR-11-05 | Popover mobile menutup ruang tengah (6 langkah) | **F6** (akibat sasaran generik) |
| RR-04-01 (link-name) | Pautan kosong kolum Duplikat | **F7** |
| RR-04-01 (landmark-unique) | `.fi-topbar` nav tanpa nama unik | **F7** |
| RR-04-01 (empty-table-header) | Header tindakan kosong jadual Rekod | **F7** |
| RR-08-05 | Butang viewer PDF tidak disabled | **F7** |
| RR-11-01 | Proses audit: token/telemetri produksi | **Lampiran A** (housekeeping + protokol) |
| RR-11-06 | Integriti laporan P10 | Tiada tindakan kod — telah dibetulkan dalam laporan |
| RR-02-02 | Kebocoran listener | **DITOLAK** (2 kaedah bebas) — tiada tindakan |
| RR-02-04 | Matriks mutasi silang-tenant | **DITUTUP-LULUS** (P3 §C1 + P5) — penjaga: suite isolasi setiap fasa + F8 |
| RR-04-02 | CSV eksport BM + tenant-scoped | **TERBUKTI SIHAT** (P5) — penjaga: regresi CSV dalam F8 |

> **Semakan kesempurnaan (dikemas P2/P3):** inventori regex `RR-\d+-\d+` ke atas semua fail
> `PUSINGAN-*` + `STATUS.md` telah dijalankan oleh kedua-dua ejen — semua ID kini muncul dalam
> jadual ini (fasa tindakan ATAU status tutup). RR-11-04 kekal SATU ID dengan tiga subbutir
> (CTA→F2, tajuk duplikasi→F5, terpotong→F5) — bukan ID baharu.

### 0.5 Peta keputusan audit pelan Codex P10 (C01–C25) → seksyen pelan v1.4

Keputusan penuh + bukti setiap item: `PLAN-RR-11-CLAUDE-AUDIT-LENGKAP.md`
(**20 TERIMA · 5 TERIMA SEBAHAGIAN · 0 TOLAK**).
(T = TERIMA · TS = TERIMA SEBAHAGIAN — teras diterima, satu premis dibetulkan dengan bukti.)

| ID | Perkara | Keputusan | Diintegrasi di |
|---|---|---|---|
| C01 | Lalai retensi L2 bercanggah spec | T | §0.1(1), §5.3, §5.5, §11 D2/D10 |
| C02 | Liputan katalog 83/473, bukan 37 guide | T | §7.1, §7.2 (W1–W6), §7.4, §9 — **urutan gelombang ditulis semula P12-02** |
| C03 | Trap fokus milik vendor Driver.js | T | §3.4, §3.5, §3.6 |
| C04 | Auto-start mesti one-shot | T | §2.2 nota 6, §2.4 #5/#10 — **`launchPending` jadi `#[Locked]` P12-01** |
| C05 | Fallback SPA mesti dikunci | **TS** — syarat diterima; SPA terbukti **MATI**, jadi fallback **tidak dibina** (YAGNI + permukaan input klien) | §2.2 nota 4 (spesifikasi beku + ujian penjaga), §2.4 #11/#12 |
| C06 | Git HEAD ≠ bukti runtime | T | §10 langkah 5A, §1 F0(v), §9.3 — **rantaian dua keluarga imej + aset exact P12-06** |
| C07 | Playwright belum gate CI | T | §1 F0(iv), §10 langkah 1 — **masuk job `integration`, bukan job baharu P12-04** |
| C08 | Matriks produksi 20 BrowserContext | **TS** — keperluan diterima; matriks **sudah wujud & lulus** (`e2e/guidance.spec.js:124`), kerja = tutup jurangnya | §9.1 — **jurang bertambah 5→8 dan spec produksi khusus diekstrak, P12-05** |
| C09 | Placeholder 258 (bukan 444) | T | §6.4, §7.1, §9, §12 |
| C10 | Lima label `Edit` | T | §4.1, §4.6, §4.7 #7 |
| C11 | Elak hook ujian global produksi | T | §3.6, §7.3 |
| C12 | Muat naik perlu sasaran berasingan | T | §6.2, §6.5 |
| C13 | `sidebar` tiada fallback generik | T | §6.3, §7.2 langkah 2 |
| C14 | Semantik layout tetamu | T | §6.1, §6.5 #6 |
| C15 | Registry sasaran perlu validasi DOM | T | §7.2 langkah 4, §7.3 |
| C16 | Nama pautan Duplikat bermakna | **TS** — sel bukan-pautan dipilih (lebih bersih); kiraan "2 duplikat" **ditolak** (N+1 per baris) | §8.1 — **API muktamad `disabledClick()`, P12-07** |
| C17 | Viewer: input + state async | **TS** — clamp + render-cancel **sudah wujud**; `max`/disabled/find/guard memang tiada | §8.4, §8.5 |
| C18 | Landmark a11y bebas guidance | T | §8.2 |
| C19 | Baseline 18 kelas `toMail()` | T | §4.1, §4.3, §4.7 #2 |
| C20 | Gate Meili vs fallback PHP berlainan | T | §9.2 |
| C21 | Manual pengguna = artifak keluaran | T | **§9A (F9 baharu)** |
| C22 | F8 tidak boleh tutup isu skop W1 | T | §9, §9.3 |
| C23 | Matriks keselamatan setiap fasa | T | §0.6 (baharu), setiap "Kriteria siap" |
| C24 | `axe-core` = keputusan dependensi | **TS→ disemak semula P12-08:** keputusan bertulis diterima; penolakan premis "melanggar polisi" **DITARIK BALIK** — `CLAUDE.md:10` melarang pakej luar senarai secara umum (bukan Composer sahaja), jadi lalai = **jangan tambah** | §0.1(2), §8.5, §11 D5 |
| C25 | Housekeeping keluar dari F4 | T | **§9B (F10 baharu)**, §11 D8, Lampiran A |

### 0.5a Peta keputusan audit integrasi Codex P12 (P12-01…P12-08) → seksyen pelan v1.5

Keputusan penuh + bukti setiap item: `PLAN-RR-13-CLAUDE.md`
(**6 TERIMA · 2 TERIMA SEBAHAGIAN · 0 TOLAK**).

| ID | Perkara | Keputusan | Diintegrasi di |
|---|---|---|---|
| P12-01 | `launchPending` mesti `#[Locked]` | **T** — disahkan: `BaseLocked::update()` (`vendor/livewire/livewire/src/Features/SupportLockedProperties/BaseLocked.php:10-13`) hanya menjaga laluan **kemas kini klien**; penetapan dalam `mount()`/kaedah server tidak terjejas | §2.2 kod + nota 6, §2.3, §2.4 #5/#7, §2.6 |
| P12-02 | Susun F6 mengikut 200 langkah tindakan | **T** — dikira semula bebas daripada `resources/help/guides.json`: 200/229 langkah tindakan bersasar generik, kesemuanya dalam `screen`(140)+`workflow`(60); `tenant`/`admin` = 0 langkah tindakan | §7.2 (jadual W1–W6 ditulis semula), §7.3, §7.4, §9, §12 |
| P12-03 | Gate penuh tanpa persampelan | **T** | §7.3 (kontrak gate 5 lapis), §7.4, §9, §9.3 |
| P12-04 | CI: services tidak dikongsi antara job | **T** — disahkan `.github/workflows/ci.yml` hanya ada job `integration` (services) + `docker` (tiada services) | §1 F0(iv) ditulis semula, §10 langkah 1 |
| P12-05 | Harness produksi + liputan mobile | **TS** — substansi diterima sepenuhnya; **satu premis dibetulkan**: v1.4 §9.1 tidak pernah mendakwa `production-readonly.spec.js` ialah harness 20 konteks (ia memetik `guidance.spec.js:124` dengan betul). Yang benar-benar salah ialah dakwaan liputan **page-by-page mobile** | §9.1 (jurang 5 → 8), §10 langkah 6 |
| P12-06 | Rantaian bukti imej/aset | **T** — disahkan `docker-compose.yml:6,40` (dua imej) + `docker/Dockerfile:71-72` (stage `web` menyalin `public/` dari stage `app`) | §1 F0(v), §10 langkah 5A ditulis semula, §10 jadual F2 |
| P12-07 | `disabledClick()` + sel bukan pautan | **T** — disahkan `CanBeDisabled.php:20` (API semasa) vs `:30` (`@deprecated`) | §8.1 ditulis semula, §8.5 |
| P12-08 | Hormati larangan pakej repo | **TS** — arahan diterima sepenuhnya dan menjadi lalai baharu; **nota tambahan**: asas sebenar bukan sekadar `CLAUDE.md:10` tetapi `CLAUDE.md:3` (spec kabur → BERHENTI dan tanya), yang menjadikan "pilih tafsiran sendiri" itu sendiri satu pelanggaran | §0.1(2), §8.5, §11 D5 |

### 0.5b Peta keputusan audit kebolehjalanan Codex P14 (P14-01…P14-08) → seksyen pelan v1.6

⚠️ **Nota integriti (P17):** giliran P15 **terputus** selepas menyunting badan pelan tetapi
**sebelum** menghasilkan fail keputusannya; `PLAN-RR-15-CLAUDE.md` **tidak wujud** dan tidak
dicipta secara retroaktif. Keputusan penuh + bukti setiap item P14 kini direkod dalam
**`PLAN-RR-17-CLAUDE.md` §3** (**8 TERIMA · 0 TOLAK**; tiga daripadanya **TERIMA + DIKUATKAN**
dengan bukti kod tambahan yang P14 tidak kemukakan). Integrasi teks di bawah **dikekalkan**
kerana Codex P16 mengauditnya dan mengesahkan arahnya betul (`PLAN-RR-16-CODEX.md` §2).

| ID | Perkara | Keputusan | Diintegrasi di |
|---|---|---|---|
| P14-01 | CI tidak boleh log masuk dengan `SESSION_DRIVER=array` + port salah | **T + DIKUATKAN** — disahkan `ci.yml:56` (`APP_URL` 8080) dan `:67` (`SESSION_DRIVER: array`) vs `playwright.config.js:11` (8092). `ArraySessionHandler.php:17` menyimpan sesi dalam `protected $storage = []` **milik instance**; PHP share-nothing per permintaan bermakna `artisan serve` memulakan handler kosong setiap kali → redirect selepas log masuk kehilangan sesi. **Tambahan P15:** inilah sebab suite Pest semasa kekal hijau dengan `array` (permintaan ujian berkongsi instance dalam proses yang sama) — jadi kehijauan CI hari ini **bukan** bukti sesi HTTP berfungsi | §1 F0(iv) ditulis semula (langkah 1–12), §10 langkah 1 |
| P14-02 | Gate 473/229/83 perlu shard + agregator, bukan satu job 30 minit | **T** — disahkan `ci.yml:21` `timeout-minutes: 30` untuk job `integration` yang sudah membawa OCR apt-get + composer + npm + build + migrasi + Pest penuh | §1 F0(iv) lapis 1/2/3, §7.3 (peta ID→shard), §7.4, §10 |
| P14-03 | Manifest bantuan ≠ manifest akses halaman mengikut role | **T + DIKUATKAN** — disahkan drift **pada kelapan-lapan role**, bukan Admin sahaja: `AKSES-PAGE-…-2026-07-21.md:12-19` merekod 21/15/13/13/12/12/12/13 manakala `e2e/guidance.spec.js:14-21` menjangka 25/17/15/15/13/13/13/14 | **§1 F0(ii) set ketiga `role_routes`** (skema + penjanaan + gate), §9.1 jurang 6/7, §9A.3, §11 D11 |
| P14-04 | Runner produksi + kitaran hayat fixture belum boleh dijalankan literal | **T + DIKUATKAN** — disahkan §9.1 #5 hanya berjanji "command tepat direkod". **Bahaya yang P14 tidak namakan:** slug `smoke` **bukan** fixture terpakai-buang — `SmokeE2E.php:33` (`--slug=smoke`) + `:50` (`Mosque::updateOrCreate(['slug'=>'smoke'])`) menjadikannya tenant milik gate deploy `diwan:smoke` 9/9 (§10 langkah 5), dan `production-readonly.spec.js:28` berlalai kepadanya. Arahan v1.5 "bersihkan akaun/tenant fixture `smoke`" akan **memusnahkan gate deploy sendiri** | **§9.1a (baharu)** kontrak runner, §9.1 #4/#5, §10 langkah 6, Lampiran B #12 |
| P14-05 | W1/W2 masih anggaran; 6 defect mobile ditangguh terlalu lama | **T + DIKUATKAN** — `~10`/`~6` diganti partition deterministik yang dikira terus daripada `resources/help/guides.json`: **W1 sebenarnya 28 guide (bukan ~10)** dan W3 hanya **1** guide. Enam langkah dikenal pasti exact daripada `bukti/pusingan-11-codex/production-mobile-all-tour-steps.json` (`centerCovered=true`): `tenant.pelupusan#1` + `tenant.kegemaran#1–5` | §1 F0(ii) jadual wave beku, §7.2 ditulis semula (W0–W6), §7.4, §9, §12 (dikalibrasi semula) |
| P14-06 | Status `blocked` bercanggah gate kitaran penuh | **T** — disahkan G1 (§7.3) menerima `blocked` sebagai status sah manakala G4 menuntut 83/83 guide melalui kitaran penuh, tanpa peraturan bagi langkah blocked; §7.4 pula membenarkan penutupan "dengan baki bersebab" | §7.3 (jadual status + G4 fallback), §7.4, §9 (baris metrik baharu), §9.3 |
| P14-07 | Arahan grep gate manual tidak sah seperti ditulis | **T + separa DIKUATKAN** — `grep` BRE memang tiada lookahead negatif, jadi gate §9A.3 **vakum**. **Pembetulan P15 terhadap cadangan P14:** `! rg …` sahaja **tidak selamat** — `rg` memulangkan **exit 2** apabila fail/glob tiada (disahkan pada mesin ini), jadi `!` menukar ralat "fail tak wujud" menjadi "lulus". Gate mesti menyemak senarai fail tidak kosong dahulu. *(Nota: regex PHP §4.7 #6 `/\bSeterus\b(?!nya)/` **sah** — PCRE menyokong lookahead; hanya laluan shell yang rosak.)* **⚠️ Pembetulan P15 itu sendiri TIDAK MENCUKUPI — lihat P16-06 (§0.5c):** guard kewujudan menutup satu punca exit 2 sahaja, dan `! rg` masih menelan yang lain. Kedua-dua gate ditulis semula sekali lagi dalam v1.7 | §9A.3, §3.7 (gate bundle C11) — **kedua-duanya digantikan v1.7** |
| P14-08 | Bekukan snapshot round-robin + kemas status giliran | **T** — dakwaan sejarah tentang teks v1.4 tidak boleh disahkan semula kerana fail pelan **untracked** (`git status` = `??`), jadi tiada versi immutable untuk dirujuk | **§0.7 (baharu)**, `PLAN-RR-STATUS.md` (konteks ditukar kepada ejen semasa) |

### 0.5c Peta keputusan audit keadaan separa Codex P16 (P16-01…P16-08) → seksyen pelan v1.7

Keputusan penuh + bukti setiap item: `PLAN-RR-17-CLAUDE.md`
(**7 TERIMA · 1 TERIMA SEBAHAGIAN · 0 TOLAK**; tiga daripadanya **TERIMA + DIKUATKAN**).

| ID | Perkara | Keputusan | Diintegrasi di |
|---|---|---|---|
| P16-01 | Canary login masih pseudokod `curl`, bukan command boleh-jalan | **T + DIKUATKAN** — bukti melebihi hujah CSRF P16: log masuk ialah **komponen Livewire**, bukan borang POST. `vendor/filament/filament/src/Auth/Pages/Login.php:459` `->livewireSubmitHandler('authenticate')` + `:387-389` `Action::make('authenticate')->submit('authenticate')`; medan bernama **`data.login`**, bukan `email` (`app/Filament/Auth/Login.php:21` `TextInput::make('login')`; disahkan oleh pemilih e2e sedia ada `guidance.spec.js:45` `input[id="form.login"]`). Maka POST kredensial `email`/`password` ke `/app/login` **tidak boleh** berjaya walaupun token CSRF diekstrak dengan betul | **§1 F0(iv) canary ditulis semula** (spec + tag + command), **§1 F0(iv-a)** (jadual fail baharu), §11 D11 |
| P16-02 | Shard/agregator tiada pelaksanaan literal | **T + DIKUATKAN** — **penemuan baharu P17:** `step.id` katalog **tidak unik global**. 473 langkah menghasilkan hanya **470** `step.id` unik; `dashboard.1`, `dashboard.2`, `dashboard.3` wujud dalam **`tenant.dashboard` DAN `admin.dashboard`**. Agregator yang membina set daripada `step.id` sahaja akan melaporkan 470/473 (gagal palsu) atau — lebih bahaya — menganggap tiga langkah telah diliputi oleh guide lain. Kunci set dibekukan sebagai **`<guide_id>#<index1>`** | **§1 F0(iv) lapis 2/3 ditulis semula** (project/spec/command/skema/YAML/agregator), §7.3 G1/G5, §7.4 |
| P16-03 | Skop D11 mengira artifak terlalu sedikit | **T** — disahkan dengan mengira artifak yang v1.6 sendiri **sudah** mensyaratkan tetapi D11 tidak senaraikan (spec produksi §9.1a, wrapper, agregator, validator, canary, command fixture) | **§11 D11 ditulis semula** (4 artifak → jadual fail penuh; kiraan muktamad **16 fail repo + 1 artifak audit** ditetapkan v1.8/P18-06), **§1 F0(iv-a) jadual fail** |
| P16-04 | Setup/cleanup produksi tidak dinamakan + dua kontrak `run_uuid` bercanggah | **T** — disahkan percanggahan dalaman v1.6: jadual §9.1a mewajibkan `-RunUuid <uuid>` **diberi pemanggil**, manakala peraturan wrapper #2 berkata *"`run_uuid` dijana sekali di awal"*. Kedua-duanya tidak boleh benar serentak tanpa peraturan keutamaan | **§9.1a ditulis semula** (command, argumen, auth, inventory, rahsia, `try/finally`, `-CleanupOnly`, kontrak `RunUuid` disatukan), §11 D11 |
| P16-05 | Senarai ID wave exact belum wujud | **T + DIKUATKAN** — disahkan `PLAN-RR-15-CLAUDE.md` **tidak wujud**, jadi §F0(ii-a) merujuk fail hantu. **P17 menjana semula partition terus daripada `resources/help/guides.json`**: formula deterministik menghasilkan **W0 2 · W1 28 · W2 13 · W3 1 · W4 1 · W5 35 · W6 3 = 83** dan **10+140+145+11+13+146+8 = 473** — sepadan jadual beku v1.6 **tanpa perbezaan** | **§1 F0(ii-a)** (manifest jadi sumber + validator), senarai 83 ID dalam `PLAN-RR-17-CLAUDE.md` §5 |
| P16-06 | `! rg` menukar ralat kepada lulus | **T** — disahkan **dengan menjalankan `rg` 15.2.0 pada mesin ini**: tiada padanan → **rc 1**; laluan tiada → **rc 2**; regex rosak (`rg '('`) → **rc 2**; `! rg … folder/tiada` → cawangan "lulus" diambil. Guard `test -d`/`[ -n "$files" ]` v1.6 menutup **satu** punca exit 2 (laluan hilang) tetapi **bukan** regex rosak | **§3.7** dan **§9A.3** kedua-duanya ditulis semula |
| P16-07 | `role_routes` perlu expected daripada polisi, bukan belajar hasil rosak | **T + DIKUATKAN** — v1.6 memang menulis *"nilai direkod daripada tingkah laku sebenar, kemudian dikunci"* (gate #3), iaitu baseline-as-contract. **Penguatan P17:** cadangan P16 sahaja masih boleh **tautologi** jika `expected` dijana dengan memanggil `canAccess()` yang sama yang diprobe. Kontrak dipecah **tiga lapis** — `expected_access` daripada `config/roles.php:55-124` + policy/spec; `declared_access` daripada penilaian authorizer kod; `actual_status` daripada probe HTTP | **§1 F0(ii-b) gate ditulis semula**, §9.1 jurang 6/7 |
| P16-08 | Suite domain penting di luar gate CI | **TS** — `office-workflow` + `ddms-extended` **diterima penuh** (mutasi dalam CI selamat; ia menguji upload/klasifikasi/minit/carian yang F6/F8 sentuh). `ocr-upload` diterima **bersyarat**: `e2e/ocr-upload.spec.js:4-6` `test.skip` melainkan `SPDM_OCR_FIXTURE_1/2` + `SPDM_OCR_TERM_1/2` diberi — memasukkannya tanpa fixture komited menghasilkan **skip senyap**, iaitu gate palsu. **Premis "antivirus fixture" DITOLAK dengan bukti:** `config/diwan.php:32` `CLAMAV_ENABLED` lalai **false** dan `AntivirusScanner.php:12` pulang awal apabila mati — tiada service ClamAV diperlukan. Queue pula sudah `sync` (`ci.yml:66`) dan tesseract sudah dipasang (`ci.yml:107`) | **§1 F0(iv) project `ci-domain`/`ci-ocr`**, §9 jadual metrik, §10 langkah 1 |

### 0.5d Peta keputusan audit integrasi Codex P18 (P18-01…P18-07) → seksyen pelan v1.8

Keputusan penuh + bukti setiap item: `PLAN-RR-19-CLAUDE.md`
(**7 TERIMA · 0 TERIMA SEBAHAGIAN · 0 TOLAK**; dua daripadanya **TERIMA + DIKUATKAN**).

| ID | Perkara | Keputusan | Diintegrasi di |
|---|---|---|---|
| P18-01 | Required check `ci-domain` mustahil — ia project Playwright, bukan status check | **T + DIKUATKAN** — premis P18 disahkan (`playwright.config.js` yang dicadangkan mendefinisikan `ci-domain` sebagai project, dijalankan sebagai step job `integration`). **Penguatan:** kecacatan yang sama menjangkiti **dua** nama lain yang P18 hanya sebut sepintas — nama check job sedia ada ialah `PostgreSQL, Redis, Meili, OCR and tests` (`.github/workflows/ci.yml:19`), bukan `integration`; dan pelan menyebut required check **`guidance-e2e-gate`** (id job) di 6 lokasi sedangkan v1.7 memberi job itu `name: Guidance coverage gate` (`:843`) → nama check sebenar berbeza daripada yang ditulis. **Pilihan 2 P18 diambil** (kekalkan `ci-domain` sebagai step) + `name:` digugurkan daripada kedua-dua job baharu supaya check name = job id | **§1 F0(iv)(f) (baharu)**, §1 F0(iv)(d) YAML, §10 langkah 1, kriteria siap F2/F3/F5/F7 |
| P18-02 | YAML "bentuk beku" masih placeholder `...` / "sama lapis 1" | **T** — disahkan pada baris yang P18 namakan; blok literal yang boleh disalin memang wujud dalam `ci.yml:22-51` (services), `:52-80` (env), `:82-121` (setup/build) | **§1 F0(iv)(d) ditulis semula penuh** |
| P18-03 | Kontrak kredensial superadmin fixture produksi bercanggah | **T + DIKUATKAN** — percanggahan disahkan (§9.1a `prepare` "superadmin sedia ada dirujuk, tidak dicipta" vs peraturan 3 "tulis `E2E_PROD_SUPERADMIN_*`"). **Penguatan:** bahaya ketiga yang P18 tidak namakan — `e2e/guidance.spec.js:27-28` mempunyai **lalai diam** `superadmin@diwan.test` / `password`, jadi wrapper yang gagal menyemak env akan mencuba kredensial demo terhadap **produksi** (throttle/log kegagalan log masuk, bukan ralat konfigurasi yang jelas). **Pilihan A diambil** + larangan lalai eksplisit | **§9.1a jadual `prepare` + peraturan wrapper 1/3** |
| P18-04 | Antivirus intake fail-closed tiada gate wajib | **T** — disahkan: `InboxIngestService.php:72-78` ialah laluan kritikal, tetapi liputan ujian hanya `DdmsExtendedCapabilitiesTest.php:149-155` (status `disabled`) dan `GuidanceSupportTest.php:91-107` (lampiran **support request**, bukan intake). `grep -rn "AntivirusScanner" tests/` = **satu** fail sahaja | **§0.6 S7 (baharu)**, §1 F0(iv-a) fail #14, §9 jadual metrik, §11 D11 |
| P18-05 | `results.json` tiada reporter/output Playwright | **T** — disahkan `playwright.config.js:9` = `reporter: [['line']]`; tiada JSON dihasilkan, jadi assertion "status !== 'skipped'" v1.7 membaca fail yang tidak wujud (skrip naif = lulus senyap, iaitu tepat mod kegagalan yang P16-08 cuba tutup) | **§1 F0(iv)(e) (baharu)**, §1 F0(iv) syarat `ci-ocr`, §1 F0(iv-a) fail #15, §9 metrik |
| P18-06 | Kiraan D11 bercampur 12/14/15 | **T** — disahkan `:123` dan `:323` masih "4 → 12" manakala `:1058-1082`/`:3157` sudah 14. Angka muktamad selepas P18-04 + P18-05 ialah **16 fail repo + 1 artifak audit** (bukan 15+1 seperti dicadang P18 — cadangan itu dibuat sebelum P18-05 memerlukan `scripts/audit/assert-playwright-json.mjs` sebagai fail repo sendiri) | log versi, §0.5c, **§1 F0(iv-a)**, **§11 D11**, §12 |
| P18-07 | "Working tree bersih" tidak tepat | **T** — disahkan `git status --short` = `M HANDOVER.md` + 20 fail perancangan `??`, manakala `git status --short -- app resources tests e2e config .github docker composer.json package.json` = **0 baris**. Kedua-dua dakwaan itu benar serentak dan mesti ditulis berasingan | **§0.7 #7 (baharu)**, `PLAN-RR-STATUS.md` |

### 0.5e Peta keputusan audit integrasi Codex P20 (P20-01…P20-06) → seksyen pelan v1.9

Keputusan penuh + bukti setiap item: `PLAN-RR-21-CLAUDE.md`
(**5 TERIMA · 1 TERIMA SEBAHAGIAN · 0 TOLAK**; dua daripadanya **TERIMA + DIKUATKAN**).

| ID | Perkara | Keputusan | Diintegrasi di |
|---|---|---|---|
| P20-01 | Lapis 1 `integration` masih naratif, bukan YAML boleh-tampal; env **server** dan cleanup PID tidak dikunci | **T + DIKUATKAN** — premis disahkan: `ci.yml:56` `APP_URL=…:8080` + `:67` `SESSION_DRIVER=array` diwarisi oleh **setiap** step, termasuk proses `php artisan serve`; env pada step Playwright tidak menyentuh proses server yang sudah berjalan. **Penguatan 1:** `trap … EXIT` v1.6/v1.8 (`:1194-1198`) memang membunuh server terlalu awal — setiap `run:` GitHub Actions ialah shell berasingan, jadi trap menyala pada penghujung step serve. **Penguatan 2 (P20 tidak kemukakan):** walaupun env ditetapkan pada step serve, `ServeCommand.php:184-189` menetapkan setiap kunci **di luar** `$passthroughVariables` (`:79-94`) kepada `false` apabila `.env` wujud — `APP_URL`/`SESSION_DRIVER`/`DB_*` **tiada** dalam senarai itu. Hari ini CI selamat hanya kerana ia **tidak** mencipta `.env` (`grep -n "\.env" .github/workflows/ci.yml` = 0), iaitu pergantungan tersirat. Command dibekukan sebagai **`serve --no-reload`** (`:184` memintas penapisan tanpa syarat) | **§1 F0(iv)(d-1) (baharu)**, §1 F0(iv)(d) (cleanup step), teks lapis 1 |
| P20-02 | Gate Meilisearch C20 masih nota naratif, bukan command CI | **T** — disahkan: `SyncHelpIndex.php:38-42` pulang **SUCCESS awal** apabila `config('scout.driver') !== 'meilisearch'`, jadi `Validate help catalog` (`ci.yml:123-126`, `SCOUT_DRIVER: collection`) hanya mengesahkan katalog; dan `HelpSearchService.php:24-45` mencuba Meili lalu **menelan `Throwable`** dan fallback ke PHP, jadi carian hijau **bukan** bukti indeks. Nota: command sedia ada **sudah** menggagalkan mismatch kiraan (`:83-86`), jadi gate ialah **penjadualan**, bukan kod baharu | **§1 F0(iv)(d-1)** step `Meilisearch help index gate`, teks lapis 1, **§9.2** |
| P20-03 | JSON `guidance-full` dijana tetapi tidak di-assert; assert patut menolak `stats.skipped` | **T** — disahkan pada YAML v1.8: `:1030-1033` menetapkan `DIWAN_PW_JSON` tetapi tiada panggilan skrip selepasnya, sedangkan canary `:1022-1028` ada. Assertion **(7) `stats.skipped === 0`** ditambah. **Nota bukan-isu P20 juga diterima:** reporter JSON memang `mkdir` rekursif (`node_modules/playwright/lib/runner/index.js:4064`), jadi ayat v1.8 "reporter JSON gagal jika direktorinya tiada" **salah** dan dibuang; `mkdir -p` dikekalkan hanya untuk direktori yang **spec/agregator** tulis sendiri | **§1 F0(iv)(d)** step shard, **§1 F0(iv)(e)** assertion 7, nota kebolehjalanan |
| P20-04 | `bukti/plan-ci/*.json` dipanggil artifak bukti tetapi tidak di-upload/retain | **T** — disahkan: YAML v1.8 hanya memuat naik `shard-*.json` dan `coverage-gate.json`; CI sedia ada hanya memuat naik log **pada kegagalan** (`ci.yml:150-157`). Upload `if: always()` + `if-no-files-found: error` ditambah untuk setiap gate wajib; `ci-ocr` kekal `ignore` sehingga fixture dikomit | **§1 F0(iv)(d)**, **§1 F0(iv)(d-1)** |
| P20-05 | Senarai required check bercampur bilangan dan jenis ("tepat tiga" tetapi 4 nama + step + shard) | **T** — disahkan `:1133-1138`: Docker ialah **dua** check (`ci.yml:160` `name: Docker ${{ matrix.target }} image` × matriks `app`/`web`), jadi "tepat tiga" mustahil. Dua senarai dipisahkan sepenuhnya: **required branch protection = tepat empat nama**; step `ci-domain`, tiga shard dan artifak JSON dipindah ke **bukti keluaran** | **§1 F0(iv)(f) ditulis semula**, §10 langkah 1 |
| P20-06 | Lokasi/ignore root `bukti/plan-ci` + `bukti/plan-f6` belum diputuskan | **TS** — masalah disahkan (root `.gitignore` tidak mengabaikan `/bukti`; §9.3 sendiri berkata "jangan cipta folder `bukti/` lain"). **Kedua-dua pilihan utama P20 ditolak dengan sebab:** (a) `test-results/plan-*` **berbahaya** — Playwright memadam `outputDir` pada setiap larian (`runner/index.js:5943-5962`; lalai `test-results`, `lib/program.js:190`), jadi larian ke-2 dalam job lapis 1 memusnahkan JSON larian ke-1; (b) menulis ke `Audit Review Round Robin/bukti/…` memaksa CI menulis ke folder perancangan bernama-berruang. **Pilihan ketiga diambil:** `storage/app/plan-ci/` + `storage/app/plan-f6/` — sudah diabaikan oleh `storage/app/.gitignore` (`*` + `!private/` + `!public/`), tidak bertindih disk `local` (root = `storage/app/private`, `config/filesystems.php:35`), **tiada** perubahan `.gitignore`, **D11 kekal 16** | **§1 F0(iv)(g) (baharu)**, semua laluan YAML/command, §9.3, §11 D11 |

### 0.5f Peta keputusan audit Codex P22 (Titik 1–6 + imbasan) → seksyen pelan v1.10

Keputusan penuh + bukti setiap item: `PLAN-RR-23-CLAUDE.md`
(**5 isu DITERIMA — 3 P1 + 2 P2; 4 titik DISAHKAN tanpa pindaan**; semua diverifikasi bebas).

| ID | Perkara | Keputusan | Diintegrasi di |
|---|---|---|---|
| P22-T1 | Justifikasi `$_ENV`/`ServeCommand` terlalu mutlak — `variables_order=GPCS` + `$_ENV` kosong + fallback `getenv()` Symfony (`Process.php:1688-1692`, `:355-363`) mewarisi env walau `.env` wujud | **T (P1)** — probe Claude sendiri: `variables_order=GPCS`, `PATH` tiada dlm `$_ENV`, `getenv('PATH')` ada — sepadan probe Codex. Naratif ditulis semula; `--no-reload` KEKAL (kini dengan sebab yang betul: bebas daripada KEDUA-DUA syarat luaran); step probe bukti ditambah | **§1 F0(iv)(d-1) #1**, rujukan `:1520`, komen skrip §9.2 |
| P22-T2 | Gate Meilisearch | **DISAHKAN — tiada pindaan**: `SyncHelpIndex` menunggu task (vendor `Tasks.php:43-58`), menolak `failed`, assert kiraan `stats()` sebelum ayat kejayaan → `grep -qF` = pengikat tambahan denominator 83 + UID, bukan pengganti | — |
| P22-T3 | `stats.skipped === 0` | **DISAHKAN — tiada pindaan**: reporter JSON membina statistik dari `suite.allTests()` sahaja (`runner/index.js:3916-3929`) — ujian ditapis `--grep`/project tidak wujud dalam suite; skip eksplisit/fixme memang patut menggagalkan gate | — |
| P22-T4 | Nama check matriks + `gh api …/check-runs` | **DISAHKAN — tiada pindaan**; nota pagination >30 check disimpan sebagai peringatan masa depan | — |
| P22-T5 | `if: always()` + `if-no-files-found: error` menutup diagnosis kegagalan awal | **T (P1)** — dua-step upload dibekukan: `success()`+`error` (lulus tapi bukti hilang = gate P20-04 kekal) vs `failure()`+`ignore` (+log server/index utk diagnosis); diterapkan pada lapis 1, kedua-dua upload shard, dan agregator | **§1 F0(iv)(d-1)** upload, **F0(iv)(d)** lapis 2/3 |
| P22-T5b | Dakwaan "kod tidak sentuh `storage/app` luar private/public" salah (`manual-capture`, `backup-temp`, `tmp/*`) | **T (P2)** — dakwaan dikecilkan kepada keunikan awalan `plan-*` (0 padanan dlm kod); pilihan lokasi KEKAL | **§1 F0(iv)(g)** |
| P22-T6 | "16 fail + 1 artifak" salah unit — #13 = 2 fail, #16 wildcard; selepas "lulus semua" ≥19 fail | **T (P1)** — jadual F0(iv-a) dipecah #13a/b + #16a/b/c (nama exact, `terms.json` fail dikomit bukan env ad-hoc), blok KIRAAN DINORMALISASI: **16 entri = 19 fail repo + 1 bundle**; skop kelulusan pemilik TIDAK berubah, hanya label; §11 D11 + §12 dikemas | **§1 F0(iv-a)**, **§11 D11**, §12 |
| P22-T7 | Ayat "menunggu jawapan pemilik" lapuk selepas `KEPUTUSAN-PEMILIK.md` lengkap | **T (P2)** — header prasyarat, F0(i), tajuk §11 + blok status + nota kebergantungan semuanya dikemas ke "telah dijawab/diluluskan"; **Lampiran A1 SENGAJA dikekalkan** sebagai menunggu (tindakan, bukan keputusan D) | header, §1 F0(i), **§11** |

### 0.5g Peta keputusan audit Codex P24 (v1.10 → v1.11)

Keputusan penuh: `PLAN-RR-25-CLAUDE.md`. P24 = **5 titik LULUS, 1 isu substantif (P1)**:

| ID | Perkara | Keputusan | Diintegrasi di |
|---|---|---|---|
| P24-T1 | Naratif ServeCommand + probe `variables_order` | LULUS (nota kosmetik sahaja) | — |
| P24-T2 | Upload dua-laluan (`success()`/`failure()` eksklusif; nama artifak sama tidak konflik; `download-artifact` pattern menemui kedua-dua) | LULUS | — |
| P24-T3 | Kiraan 19 fail + 1 bundle konsisten §1/§11/§12 | LULUS | — |
| P24-T4 | **Kontradiksi:** senarai ringkas dlm baris D11 §11 masih melabel #16 "bersyarat"/"16 terpulang" sedangkan pemilik "luluskan semua" | **T (P1)** — label lama TERBATAL dengan penanda jelas (strikethrough + nota sejarah); #16a-c dinyatakan WAJIB; typo "D10-16" (v1.10) → "D11" | **§11 D11** senarai ringkas + lajur cadangan, **§1 F0(iv-a)** #16a |
| P24-T5 | Imbasan corak + rujukan silang selepas +112 baris | LULUS | — |

### 0.6 Matriks keselamatan tetap (gate SETIAP fasa — C23)

Dijalankan sebagai sebahagian "Kriteria siap" setiap fasa F1–F10, bukan sekali di F8. Ia
menguji **lapisan yang fasa itu sentuh** (runtime tour, katalog, e-mel, carian, aset):

| # | Probe | Jangkaan |
|---|---|---|
| S1 | Pengguna tenant A → URL sumber tenant B | 404 (bukan 403 yang membocorkan kewujudan) |
| S2 | `admin_masjid` tenant → `/admin` | ditolak |
| S3 | Guide/deep-link `?panduan=` di luar panel/role/permission | tiada payload, tiada auto-start |
| S4 | Imej bantuan + artikel `/bantuan` | tiada kandungan tenant lain |
| S5 | `help_events` / `guidance_progress` / carian bantuan | berskop pengguna+tenant; query mentah tidak disimpan |
| S6 | Awam (belum log masuk) | tiada naik taraf ke panel `app`/`admin` |
| **S7** *(baharu v1.8 — P18-04)* | **Intake fail-closed antivirus**: `InboxIngestService::ingest()` dengan `CLAMAV_ENABLED=true` + `CLAMAV_FAIL_CLOSED=true` dan `AntivirusScanner` dimock memulangkan `infected` / `unavailable` / `error` | `ValidationException` dilempar pada ketiga-tiga status; **0** `Record`, **0** media, **0** log aktiviti intake tercipta; tenant lain tidak berubah. Dijalankan sebagai ujian Pest (D11 fail #14) — **tiada** service ClamAV diperlukan |

Kegagalan mana-mana probe = fasa TIDAK siap (keperluan #1 §0.1(4)).

> **Nota S7 (P18-04).** Probe ini kekal dalam matriks tetap walaupun tiada fasa "menyentuh"
> antivirus, kerana F5/F6 mengubah katalog dan F1/F2 mengubah runtime yang **berkongsi** laluan
> muat naik Peti Masuk; regresi fail-closed akan senyap sepenuhnya tanpa ujian ini (`config/diwan.php:32`
> lalai `CLAMAV_ENABLED=false` bermakna suite hari ini **tidak pernah** melalui cabang
> `InboxIngestService.php:76`).

### 0.7 Protokol snapshot & integriti dokumen pelan (P14-08)

**Masalah yang disahkan.** Fail pelan dan fail giliran adalah **untracked** dalam git
(`git status` memaparkannya sebagai `??`). Maka tiada versi immutable: apabila P12 memetik satu
keadaan v1.4 dan P13 memetik keadaan lain, **kedua-duanya tidak boleh disahkan semula** hari ini.
Ini bukan pertikaian tentang siapa betul — ia kecacatan proses yang membolehkan mana-mana pihak
membuat dakwaan sejarah yang tidak boleh diuji.

**Peraturan berkuat kuasa mulai v1.6:**

1. **Rekod sebelum audit.** Setiap giliran merekod `SHA-256 + saiz + mtime` fail yang akan
   diauditnya, **sebelum** membaca untuk membuat keputusan, dan sekali lagi **sebelum** suntingan
   pertama. Jika berbeza → berhenti, rekod bukti, jangan timpa (peraturan `PLAN-RR-STATUS.md` #7).
2. **Snapshot immutable sebelum giliran bertukar.** Sebelum menyerah giliran, versi yang diserah
   dibekukan dengan **salah satu**: (a) `git add -A && git commit` ke atas folder
   `Audit Review Round Robin/` (disyorkan — ia memberi kandungan **dan** sejarah), atau
   (b) salinan baca-sahaja `Audit Review Round Robin/bukti/plan-snapshots/PELAN-PEMBAIKAN-v<N>.md`
   dengan hashnya direkod dalam fail giliran.
3. **Satu ejen tidak boleh menukar versi yang sedang diaudit ejen lain.**
4. **Dakwaan sejarah tanpa snapshot ≠ keputusan produk.** Ayat berbentuk "versi X tidak pernah
   berkata Y" hanya boleh mempengaruhi keputusan jika ia boleh disemak terhadap snapshot beku.
   Tanpa snapshot, substansi teknikal dinilai atas kod semasa sahaja, dan pertikaian premis
   direkod sebagai **tidak boleh disahkan** — bukan sebagai kemenangan mana-mana pihak.
   *(Terpakai secara retroaktif kepada pertikaian premis P12-05/P13: substansinya sudah diterima
   penuh; premisnya kini dilabel tidak boleh disahkan dan tidak dijadikan asas keputusan.)*
5. **Status giliran dikemas pada setiap serahan** — tajuk konteks dalam `PLAN-RR-STATUS.md` mesti
   menamakan **ejen yang menerima giliran seterusnya**, bukan ejen sebelumnya.

6. **(Baharu — P17) Giliran yang terputus direkod sebagai terputus.** Jika satu giliran menyunting
   pelan tetapi tidak menghasilkan fail keputusannya, giliran berikutnya **tidak** menulis fail
   bagi pihaknya dan **tidak** menganggap serahan itu lengkap. Ia direkod sebagai **serahan
   separa**, dengan sebab sebenar dinyatakan, dan rujukan kepada fail yang tidak wujud
   **dibuang daripada badan pelan** (bukan dibiarkan sebagai rujukan hantu).

7. **(Baharu — P18-07) "Bersih" mesti dinyatakan berskop, tidak pernah sebagai dakwaan tunggal.**
   Ayat "working tree bersih" **dilarang** dalam fail giliran dan dalam `PLAN-RR-STATUS.md`, kerana
   ia mencampurkan dua fakta berlainan yang **kedua-duanya benar serentak**. Gantikan dengan dua
   dakwaan berasingan, masing-masing dengan commandnya:
   - **Kod aplikasi:** `git status --short -- app resources tests e2e config .github docker
     composer.json composer.lock package.json package-lock.json` → **0 baris** bermakna "tiada
     perubahan kod aplikasi dalam giliran ini" (dakwaan yang fasa perancangan ini benar-benar
     perlu buat);
   - **Keseluruhan repo:** `git status --short` → pada 2 Ogos 2026 ia memaparkan `M HANDOVER.md`
     + **20** fail perancangan `??`. Ini **bukan** bersih; menulis sebaliknya merosakkan satu-satunya
     audit trail yang pelan ini bergantung padanya.
   Fail giliran mesti **menampal output sebenar** kedua-dua command, bukan meringkaskannya —
   selaras `CLAUDE.md:7` (dakwaan tanpa output = tidak siap).

⚠️ **Rekod serahan separa P15 (jujur, tidak dinaratifkan semula).** Giliran Claude P15 dihadkan
kepada tiga fail (`PELAN-PEMBAIKAN.md`, `PLAN-RR-15-CLAUDE.md`, `PLAN-RR-STATUS.md`) dan
**dilarang menjalankan git**. Ia sempat menyunting badan pelan kepada v1.6 (hash
`A1667A70…FF31E`, 205 840 B, 2 777 baris) tetapi **keluar sebelum**: mewujudkan
`PLAN-RR-15-CLAUDE.md`, mengemas footer (kekal *"Versi 1.5 … Codex Pusingan 14"*), menaikkan
prasyarat D1–D10 → D1–D11, dan membuang percanggahan Lampiran B #11. Percubaan menyambung gagal
dengan mesej literal **"You've hit your monthly spend limit"**. Codex P16 mengaudit keadaan separa
itu dan **tidak** menulis fail bernama Claude. **Akibat yang diwarisi v1.7:** §F0(ii-a) v1.6
merujuk `PLAN-RR-15-CLAUDE.md` §3 sebagai sumber senarai ID wave — fail itu tidak wujud, jadi
P17 **menjana semula partition daripada katalog** dan memindahkan sumber kebenaran kepada
**manifest + validator** (§1 F0(ii-a)).

⚠️ **Had P17 yang diisytihar:** giliran ini juga dihadkan kepada tiga fail
(`PELAN-PEMBAIKAN.md`, `PLAN-RR-17-CLAUDE.md`, `PLAN-RR-STATUS.md`) dan **dilarang menjalankan
git, SSH, deploy atau ujian mutasi**. Maka P17 **tidak** dapat mencipta snapshot (2a atau 2b)
sendiri; ia merekod hash/saiz/mtime sebelum dan selepas sahaja (dua kali sebelum suntingan
pertama). **Tindakan pemilik yang masih diperlukan:** komit folder `Audit Review Round Robin/`
sebelum giliran Codex P18 bermula, atau benarkan P18 mencipta snapshot sebagai langkah pertama.
Selagi ini tidak dibuat, peraturan 4 di atas terpakai kepada **semua** dakwaan sejarah tentang
v1.6 dan v1.7.

---

## 1. Urutan fasa & rasional

```
F0  Prasyarat: keputusan pemilik + baseline harness metrik
F1  Konteks HelpLauncher (punca akar #1 — 45% halaman)          [kecil, impak terbesar]
F2  Runtime tour JS: predikat bersatu + label BM + modal + fokus [kecil-sederhana]
F3  Bahasa: lang/ms + e-mel + vendor Filament + label Edit       [sederhana, bebas]
F4  Lalai retensi (selepas keputusan pemilik D1–D3)              [kecil]
F5  Kandungan katalog: login, muat naik, dashboard, tajuk        [sederhana]
F6  Sasaran spesifik data-help-target (7 wave W0–W6, 83 guide)   [besar, berterusan]
F7  Kebolehcapaian & baki kecil                                  [kecil]
F8  Audit semula & pengukuran metrik tutup                       [pengesahan]
F9  Regenerasi Manual Pengguna (9 persona) — artifak keluaran    [sederhana]   (C21)
F10 Housekeeping: dead code + polisi token login                 [kecil, bebas] (C25)
```

**Rasional urutan:**
- **F1 dahulu** kerana F5/F6 tidak boleh *diuji* pada halaman admin/tenant selagi konteks hilang
  — guide `admin.*` "wujud tetapi tak pernah dipapar". Membaiki F1 memulihkan 19/25 halaman
  produksi serta-merta dengan satu perubahan kecil.
- **F2 sebelum F5/F6** kerana predikat label yang bersatu menentukan CTA yang betul untuk
  kandungan katalog yang akan ditulis semula di F5.
- **F3 bebas** — boleh selari dengan F1/F2 dari segi kod, tetapi dilaksanakan berurutan
  (peraturan satu-fasa-satu-masa) untuk memudahkan bisect regresi.
- **F4 menunggu keputusan pemilik** — boleh masuk bila-bila selepas D1–D3 dijawab.
- **F6 selepas F5** kerana katalog yang dikemas (tajuk/CTA betul) ialah asas untuk menukar
  sasaran langkah; menukar kedua-duanya serentak menyukarkan verifikasi.
- **F8 selepas F7** — ulang metrik audit yang sama untuk membuktikan angka bergerak
  (19/25 → 25/25, 119/124 generik → sasaran, EN leak → 0).
- **F9 selepas F8** (C21) — Manual Pengguna ialah artifak keluaran bertarikh
  ("Versi UI disahkan: 22 Julai 2026") dengan skrinsyot; F3 (Edit→Sunting, `Seterus`→
  `Seterusnya`, validasi BM), F5 (tour log masuk) dan F6 (sasaran) **membatalkan** teks dan
  gambar sedia ada. Regenerasi mesti selepas UI stabil, bukan sebelum.
- **F10 bebas** (C25) — housekeeping (dead code + polisi token) dikeluarkan daripada F4 supaya
  commit lalai-retensi bersih untuk bisect/rollback.

**Setiap fasa = 1 commit + CI hijau + deploy + verifikasi produksi** sebelum fasa berikut.
(Deploy boleh digabung F1+F2 dan F5+F7 jika pemilik mahu kurangkan bilangan deploy — lihat §6.)

**F0 (prasyarat, sebelum sebarang kod):**

(i) **Jawapan pemilik D1–D11 — ✅ LENGKAP diterima (`KEPUTUSAN-PEMILIK.md`, 2 Ogos 2026).**
D10 LULUS → D2 dibuka (teks `# ADDENDUM v2.6` ditulis ke `DIWAN-SPEC-ADDENDUM-2026-07.md`
sebagai langkah pertama F0/F4); D5(a)+(b) diluluskan → `axe-core` dev-only dibenarkan dengan
pengecualian bertulis direkod dalam dokumen kawalan semasa F7; D11 diluluskan semua →
gate F6 + F8 dibuka.

(ii) **Manifest baseline dibekukan** — laluan penuh (P4, elak folder `bukti/` baharu di root
repo): **`Audit Review Round Robin/bukti/plan-baseline/manifest.json`**. Selepas C02/C09 ia
mengandungi dua set; **P14-03/P14-05 menjadikannya TIGA set + satu medan baharu**:
   - `cohort` — 25 guide tenant / 124 langkah audit P11 + route + role + viewport + commit hash
     + angka baseline (119/124 `resolved_to_generic`, 77/124 tajuk=penerangan, 20/124 terpotong,
     CTA 79/25/20, 6 mobile). **Perbandingan sebelum/selepas sahaja.**
   - `catalogue` — **kesemua 83 guide / 473 langkah** dengan `family`, `route`, `target`,
     `wait_for_user`, **`wave`** (P14-05 — lihat jadual beku di bawah), **`shard`** (P14-02) dan
     status liputan awal. **Inilah gate keluaran F6/F8.**
     ⚠️ **Keputusan penempatan (P15):** medan `wave`/`shard` disimpan dalam **manifest beku ini**,
     **bukan** dalam `resources/help/guides.json`. Sebab: `guides.json` ialah katalog produk yang
     dihidang kepada pengguna dan diindeks Meili (`SyncHelpIndex` memetakan `guides`); menambah
     metadata perancangan ke dalamnya memaksa `catalog_version` bump dan risiko auto-tour semula
     (D6) untuk perubahan yang **tiada** kaitan dengan kandungan. Substansi P14-05 dipenuhi
     sepenuhnya — setiap guide/langkah tetap membawa `wave` yang beku dan boleh diaudit.
   - **`role_routes` (BAHARU — P14-03)** — manifest akses halaman mengikut identiti. Lihat
     spesifikasi penuh di bawah.

   Angka baseline katalog penuh yang dibekukan (dikira semula dan disahkan dua kali, P10+P11):

   | Family | Guide | Langkah | Sasaran generik | Tajuk `Langkah N` |
   |---|---:|---:|---:|---:|
   | admin | 12 | 32 | 32 | 0 |
   | public | 3 | 8 | 4 | 0 |
   | screen | 29 | 151 | 140 | 140 |
   | tenant | 25 | 124 | 124 | 118 |
   | workflow | 14 | 158 | 143 | 0 |
   | **Jumlah** | **83** | **473** | **443** | **258** |

   Pecahan sasaran generik: `page-primary` 238 + `page-content` 205 = 443. Langkah
   `wait_for_user` = 229/473, **daripadanya 200 bersasar generik** (metrik keutamaan §7.1).
   Nilai `target` unik dalam katalog = **15**, iaitu **13 spesifik + 2 generik**
   (`page-primary`, `page-content`) — angka "13 nama sasaran unik" di §7.1 merujuk yang
   spesifik sahaja; kedua-duanya betul dan tidak bercanggah (dijelaskan P13).
   `sidebar` = **0** penggunaan dalam katalog (C13).
   `catalog_version` baseline = `2026.07.22.2`.

   *Pengesahan bebas P13 (dikira semula terus daripada `resources/help/guides.json`, bukan
   disalin dari laporan):* 83 guide · 473 langkah · 443 generik · 258 placeholder ·
   229 `wait_for_user` · 200 langkah tindakan bersasar generik · 13 nama sasaran spesifik
   merangkumi 30 langkah (`classification-*` 20, `inbox-*` 6, `registration-*` 4) ·
   pecahan setiap family sepadan jadual di atas **baris demi baris**. Skrip pengiraan disimpan
   dalam `tools/` F0(iii) supaya F8 mengulang kaedah yang sama.

   *Pengesahan bebas P15 (kaedah sama, dikira semula pada `8342d95`):* kesemua angka di atas
   **sepadan sekali lagi tanpa perbezaan** — 83 · 473 · 443 · 258 · 229 · 200,
   `catalog_version` `2026.07.22.2`. Ini kali **ketiga** angka itu dikira secara bebas
   (P10/P11 → P13 → P15).

   *Pengesahan bebas P17 (kali **keempat**, dikira semula pada `8342d95`):* 83 guide · 473 langkah ·
   258 placeholder · 229 `wait_for_user` · 200 langkah tindakan bersasar generik ·
   `catalog_version` `2026.07.22.2` — **sepadan**. Tambahan yang **hanya P17** kira:
   partition wave (2/28/13/1/1/35/3 guide; 10/140/145/11/13/146/8 langkah) dan partition shard
   (`screen` 29/151/151 · `workflow` 14/158/75 · `tenant-admin-public` 40/164/3) — kedua-duanya
   sepadan jadual beku, **dan** keunikan `step.id` = **470/473** (lihat amaran P16-02 di bawah).

   **(ii-a) Jadual `wave` BEKU — menggantikan anggaran `~10`/`~6` (P14-05).**
   Peruntukan gelombang **bukan** pertimbangan rasa; ia fungsi deterministik ke atas katalog beku:

   > `W0` = dua guide yang mengandungi enam langkah popover mobile yang terbukti rosak ·
   > `W1` = guide `screen` yang mempunyai ≥1 langkah `wait_for_user` bersasar generik ·
   > `W2` = guide `workflow` yang sama syaratnya · `W3` = baki `screen` · `W4` = baki `workflow` ·
   > `W5` = `tenant` + `admin` (tolak dua guide W0) · `W6` = `public`.

   | Wave | Guide | Langkah | Langkah tindakan generik | Placeholder `Langkah N` | Defect mobile |
   |---|---:|---:|---:|---:|---:|
   | **W0** (hotfix, selepas F2) | **2** | **10** | 0 | 10 | **6** |
   | **W1** (`screen` bertindakan) | **28** | **140** | **140** | 140 | 0 |
   | **W2** (`workflow` bertindakan) | **13** | **145** | **60** | 0 | 0 |
   | W3 (baki `screen`) | 1 | 11 | 0 | 0 | 0 |
   | W4 (baki `workflow`) | 1 | 13 | 0 | 0 | 0 |
   | W5 (`tenant`+`admin`) | 35 | 146 | 0 | 108 | 0 |
   | W6 (`public`) | 3 | 8 | 0 | 0 | 0 |
   | **JUMLAH** | **83** | **473** | **200** | **258** | **6** |

   **Invarian partition (diassert oleh ujian F0, bukan disemak mata):** setiap guide dan setiap
   langkah tergolong dalam **tepat satu** wave; jumlah silang wave mesti **tepat**
   `83 / 473 / 200 / 258 / 6` **tanpa duplikat dan tanpa baki**.

   **⚠️ SUMBER SENARAI ID DIBETULKAN (P16-05).** v1.6 menyatakan senarai ID exact "direkod dalam
   `PLAN-RR-15-CLAUDE.md` §3" — **fail itu tidak wujud** (§0.7), jadi partition yang sepatutnya
   beku sebenarnya tiada sumber. Dibetulkan seperti berikut:

   1. **Sumber kebenaran tunggal = manifest.** Medan **`wave`** dan **`shard`** disimpan pada
      **setiap entri guide dan setiap entri langkah** dalam
      `Audit Review Round Robin/bukti/plan-baseline/manifest.json` (set `catalogue`). Tiada
      dokumen pusingan yang menjadi sumber; dokumen hanya **merumus**.
   2. **Partition ialah fungsi deterministik**, bukan senarai yang ditaip semula. Peraturan
      W0–W6 di atas diterjemahkan terus kepada kod dalam
      `Audit Review Round Robin/bukti/plan-baseline/tools/` (F0(iii)); menjalankannya semula pada
      `resources/help/guides.json` mesti menghasilkan partition yang **identik**.
   3. **Validator set-union exact** (`scripts/audit/validate-plan-manifest.mjs`, D11) mengassert
      **kesemua** perkara berikut dan keluar bukan-sifar pada kegagalan pertama:
      - union `W0…W6` (guide) = set 83 `guide_id` katalog — **tanpa duplikat, tanpa yatim,
        tanpa lebihan**; persilangan setiap pasangan wave = **kosong**;
      - union `W0…W6` (langkah) = set 473 kunci langkah — kunci ialah **`<guide_id>#<index1>`**
        (lihat amaran keunikan di bawah);
      - union ketiga-tiga `shard` = set yang **sama** dengan union wave (dua partition bebas ke
        atas semesta yang sama);
      - kiraan setiap wave sepadan **exact** jadual di atas (`83/473/200/258/6`);
      - setiap entri membawa `wave` **dan** `shard` (tiada nilai `null`).
   4. **Rumusan mudah-baca** (senarai 83 ID mengikut wave, dijana daripada peraturan yang sama)
      dilampirkan dalam **`PLAN-RR-17-CLAUDE.md` §5** untuk semakan manusia. Jika lampiran itu
      dan manifest berbeza, **manifest yang betul** dan validator yang memutuskan.

   > ⚠️ **Amaran keunikan ID langkah (P16-02, penemuan P17).** `step.id` dalam
   > `resources/help/guides.json` **tidak unik merentas katalog**: 473 langkah hanya menghasilkan
   > **470** `step.id` unik kerana `dashboard.1`, `dashboard.2` dan `dashboard.3` wujud dalam
   > **`tenant.dashboard` dan `admin.dashboard`**. Maka setiap kunci langkah dalam manifest,
   > artifak shard dan agregator ialah **`<guide_id>#<index1>`** (indeks bermula **1**, selaras
   > dengan notasi `tenant.kegemaran#1–5` yang digunakan pelan dan dengan medan `index` dalam
   > `bukti/pusingan-11-codex/production-mobile-all-tour-steps.json`). `step.id` katalog
   > dikekalkan sebagai medan maklumat sahaja, **tidak pernah** sebagai kunci set.

   Perubahan wave selepas freeze memerlukan **sebab bertulis + diff denominator + kelulusan**,
   direkod dalam bukti fasa.

   ⚠️ **Dua pembetulan fakta yang muncul daripada partition ini** (v1.5 menganggarkan salah):
   - **W1 = 28 guide, bukan "~10"** — 28 daripada 29 guide `screen` mengandungi langkah tindakan
     bersasar generik. "Screen kritikal" pada praktiknya bermakna **hampir keseluruhan `screen`**.
   - **W3 = 1 guide (`screen.klasifikasi-peti-masuk`) dan W4 = 1 guide**
     (`workflow.setiausaha.klasifikasikan-surat-masuk-dan-edarkan-minit`) — "baki" hampir tiada.
     Kedua-duanya mempunyai `wait_for_user` (11 dan 6) tetapi **0** bersasar generik, kerana
     30 langkah bersasar spesifik sedia ada (`classification-*`/`inbox-*`) tertumpu di situ.

   Kesan langsung: **anggaran §12 dikalibrasi semula** (W1 naik, W3/W4 turun).

   **(ii-b) Set `role_routes` — manifest akses halaman mengikut identiti (P14-03).**

   **Mengapa `catalogue` tidak boleh menggantikannya:** satu route boleh dikongsi beberapa guide
   (§7.1 — 17 route dikongsi) dan satu guide **tidak** menerangkan semua role yang boleh membuka
   halaman itu. Manifest bantuan menjawab *"panduan apa untuk halaman ini"*; matriks produksi
   §9.1 memerlukan jawapan kepada soalan berbeza: *"identiti ini boleh buka halaman mana, dan
   apa yang mesti berlaku bila ia cuba buka yang lain"*.

   **Bukti drift yang mewajibkannya:** `AKSES-PAGE-MENGIKUT-ROLE-PRODUCTION-2026-07-21.md`
   (dokumen keluaran, ditulis tangan) berbeza daripada jangkaan kod pada **kelapan-lapan** role:

   | Role | Dokumen `:12-19` | `e2e/guidance.spec.js:14-21` | Beza |
   |---|---:|---:|---:|
   | Admin / Kerani | 21 | 25 | +4 |
   | Pengerusi | 15 | 17 | +2 |
   | Setiausaha | 13 | 15 | +2 |
   | Bendahari | 13 | 15 | +2 |
   | Nazir | 12 | 13 | +1 |
   | Ketua Imam | 12 | 13 | +1 |
   | AJK | 12 | 13 | +1 |
   | Juruaudit | 13 | 14 | +1 |

   Kedua-dua senarai ditulis tangan pada masa berbeza; **tiada satu pun** dijana daripada kod.
   Selagi begitu, ia akan drift lagi selepas setiap fasa yang menambah halaman.

   **Identiti yang dibekukan (10):** `public` (belum log masuk) · `superadmin` · dan **tepat
   lapan** role tenant daripada `config/roles.php:22-23` — `admin_masjid`, `pengerusi`,
   `setiausaha`, `bendahari`, `nazir`, `ketua_imam`, `ajk`, `audit`.

   **Skema setiap entri:**

   | Medan | Guna |
   |---|---|
   | `identity` | `public` \| `superadmin` \| salah satu lapan role tenant |
   | `route_template` | cth. `/app/{tenant}/peti-masuk`, `/admin/mosques` |
   | `panel` | `app` \| `admin` \| `public` |
   | `authorizer` | kelas + kaedah yang memutuskan (`Resource::canAccess()` / `Page::canAccess()` / policy) |
   | `permission` | kunci kebenaran `config/roles.php` yang terlibat (atau `—`) |
   | `expected_access` | **allow** \| **deny** — **dijana daripada matriks kebenaran + policy/spec**, bukan daripada probe (P16-07) |
   | `declared_access` | **allow** \| **deny** — hasil menilai authorizer kod sebagai identiti itu |
   | `expected_status` | **200** (positif) atau **403/404** (negatif) — **diterbitkan daripada `expected_access` + peraturan §0.6 S1**, bukan daripada larian |
   | `actual_status` | status HTTP sebenar yang direkod probe — **medan pemerhatian, bukan kontrak** |
   | `requires_tenant` | ya/tidak (route `/admin/*` tidak memerlukan tenant) |
   | `category` | `read-only` \| `mutation` — **matriks produksi hanya menjalankan `read-only`** |
   | `viewport` | `desktop` \| `mobile` \| `both` |
   | `in_navigation` | adakah ia muncul dalam sidebar (untuk mengesan "boleh akses tetapi tiada dalam menu") |

   **Penjanaan (bukan tulisan tangan):** command pengukuran `diwan:role-routes` menghitung
   `Filament::getPanel($p)->getPages()` (`vendor/filament/filament/src/Panel/Concerns/HasComponents.php:429`)
   dan `->getResources()` (`:453`), kemudian menilai kebenaran **sebagai setiap identiti**
   melalui `Resource::canAccess()`
   (`vendor/filament/filament/src/Resources/Resource/Concerns/HasAuthorization.php:27`),
   `Resources\Pages\Page::canAccess()` (`vendor/filament/filament/src/Resources/Pages/Page.php:248`)
   dan `Pages\Concerns\CanAuthorizeAccess::canAccess()` (`:17`). Ia **read-only** — tiada mutasi.
   Command ini ialah **penambahan skop yang diisytihar** (bukan penyeludupan): lihat **D11 §11**.

   **⚠️ KONTRAK EXPECTED-vs-ACTUAL DIBETULKAN (P16-07).** v1.6 gate #3 berbunyi *"nilai 403 vs 404
   … direkod daripada tingkah laku sebenar, kemudian dikunci sebagai kontrak"*. Itu
   **baseline-as-contract**: jika runtime hari ini salah (route terdedah yang sepatutnya ditolak),
   kesalahan itu menjadi kontrak dan gate akan **melindungi pepijat**, bukan mengesannya.
   Pembetulan **tidak boleh** berhenti pada "jana expected daripada `canAccess()`" sahaja, kerana
   probe HTTP juga melalui `canAccess()` — itu **tautologi** yang lulus walaupun `canAccess()`
   itu sendiri tersalah tulis. Maka kontrak dipecah **tiga lapis dengan tiga sumber berbeza**:

   | Lapis | Sumber | Cara dikira |
   |---|---|---|
   | **A. `expected_access`** | **Spesifikasi**: matriks kebenaran `config/roles.php:55-124` (dibaca melalui `Roles::permissions()` / `Roles::can()`, `app/Support/Roles.php:36-44`) + policy + §6.2 spec + peraturan panel (`/admin/*` = superadmin sahaja; `requires_tenant`) | Deklaratif — ditulis dalam manifest sebagai `allow`/`deny` **bersama kunci kebenaran yang menjustifikasikannya**. Halaman yang **tidak** bergantung permission (cth. `Bantuan::canAccess()` = `config('diwan.guidance.enabled')`) diisytihar eksplisit sebagai `permission: —` + `rule: config` |
   | **B. `declared_access`** | **Kod**: `Resource::canAccess()`, `Resources\Pages\Page::canAccess()`, `Pages\Concerns\CanAuthorizeAccess::canAccess()`, policy | Command `diwan:role-routes` menilai authorizer **sebagai setiap identiti**, tanpa HTTP |
   | **C. `actual_status`** | **Runtime**: probe HTTP sebagai identiti itu | Direkod apa adanya |

   **Peraturan keputusan:** `A ≠ B` → **gagal** (kod menyimpang daripada spec/permission);
   `B ≠ C` → **gagal** (routing/middleware menyimpang daripada authorizer);
   `A ≠ C` → **gagal**. **Tiada laluan** dalam mana-mana skrip yang menulis semula `A` daripada
   `B` atau `C` — pembetulan mismatch ialah kerja manusia (baiki kod, atau pinda spec dengan
   addendum), bukan kemas kini baseline automatik.

   **Route universe dibina TANPA tapisan identiti (P16-07).** Penjanaan bermula daripada
   **semesta penuh**: `Filament::getPanel($p)->getPages()` + `->getResources()` bagi panel `app`
   **dan** `admin`, digabung dengan route bernama Laravel yang berkaitan panel — **sebelum**
   sebarang penilaian kebenaran. Barulah setiap identiti dinilai terhadap semesta itu. Jika
   penjanaan bermula daripada navigasi yang sudah ditapis (`.fi-sidebar a[href]` seperti
   `guidance.spec.js:62-70`), setiap route terlarang **hilang** daripada semesta dan negative
   matrix menjadi kosong secara senyap — iaitu kes yang gate ini wujud untuk menangkap.

   **Gate `role_routes` (kesemuanya wajib):**
   1. `expected_page_count` setiap identiti **dikira daripada panjang array**, tiada nombor
      ditulis tangan di mana-mana (termasuk dalam ujian).
   2. **Positif:** setiap entri `expected_access: allow` benar-benar boleh dibuka (probe HTTP
      200) **dan** `declared_access` juga `allow`.
   3. **Negatif:** setiap route dalam semesta yang `expected_access: deny` hadir sebagai entri
      dan probe memulangkan **403 atau 404 mengikut peraturan yang diisytihar dahulu**, bukan
      mengikut apa yang berlaku: silang-**tenant** mesti **404** (§0.6 S1 — tidak membocorkan
      kewujudan); tolakan kebenaran dalam tenant sendiri **403**. Percanggahan = gagal, dan
      **bukan** alasan untuk menukar `expected_status`.
   4. **Dua hala:** setiap route yang kelihatan dalam navigasi wujud dalam manifest, dan setiap
      entri manifest boleh dibuka/ditolak seperti diisytihar. Yatim = 0 pada kedua-dua arah.
      Tambahan: entri dengan `expected_access: allow` **dan** `in_navigation: false` disenaraikan
      berasingan sebagai "boleh akses tetapi tiada dalam menu" — bukan kegagalan automatik,
      tetapi mesti dilihat manusia (audit A–Z melaporkan sifar kes; regresi mesti kelihatan).
   5. **Satu sumber, dua pengguna:** matriks produksi §9.1 **dan** dokumen
      `AKSES-PAGE-MENGIKUT-ROLE-PRODUCTION-*.md` kedua-duanya **dijana** daripada manifest ini
      (dokumen menjadi output, bukan input). Ini yang menghentikan drift.
   6. `e2e/guidance.spec.js:14-21` (`pages: 25/17/15/…`) berhenti menjadi nombor literal dan
      membaca manifest — jika tidak, dua sumber kebenaran kekal wujud.

(iii) **Skrip ukur** (crawl helpRuntime, matriks tour, analisis katalog) dari `bukti/pusingan-*`
dikumpul ke `Audit Review Round Robin/bukti/plan-baseline/tools/` supaya boleh diulang verbatim.
(Nota git: `bukti/.gitignore` sedia ada hanya mengecualikan imej — JSON manifest + tools
akan dikomit; skrinsyot baseline kekal luar git seperti biasa.)

(iv) **Gate CI Playwright disediakan DAHULU (C07; reka bentuk dibetulkan P12-04).** CI semasa
(`.github/workflows/ci.yml`) mempunyai **dua** job sahaja: `integration` (baris 18 — PostgreSQL 16
+ Redis 7 + Meilisearch v1.12 + OCR + Pest, dengan blok `services:` baris 22-51) dan `docker`
(baris 159 — matriks `app`/`web`, **tiada** `services:`). **Tiada satu pun langkah menjalankan
`e2e/*.spec.js`**, jadi setiap kriteria "e2e lulus" dalam pelan ini kini bergantung pada larian
tempatan manual.

**Ralat reka bentuk v1.4 yang dibetulkan:** v1.4 mencadangkan job `e2e` baharu yang *"guna semula
perkhidmatan job `integration`"*. **Itu mustahil** — GitHub Actions memberikan service containers
kepada **job yang mengisytiharkannya sahaja**; tiada perkongsian container mahupun rangkaian
antara job. Job `e2e` tanpa blok `services:` sendiri akan berjalan **tanpa** PostgreSQL, Redis
atau Meilisearch.

**Keputusan v1.5 (dikekalkan sebagai LAPIS 1) — tambah langkah Playwright ke dalam job
`integration` sedia ada**, selepas langkah `Migrate PostgreSQL and run full suite`
(baris 128-132). Sebab: PHP 8.4, Node 22, `npm run build` (baris 120-121), migrasi dan
ketiga-tiga service **sudah** ada di situ.

⚠️ **DIBETULKAN v1.6 (P14-02) — satu job tidak boleh membawa gate penuh.** Job `integration`
mempunyai `timeout-minutes: 30` (`ci.yml:21`) dan sudah menanggung `apt-get` OCR (7 pakej),
`composer install`, `npm ci`, `npm run build`, migrasi, **suite Pest penuh** dan smoke Horizon.
Menambah 473 status + semua sasaran `specific` + 229 tour black-box + 83 kitaran guide ke dalam
job yang sama bukan reka bentuk gate — ia jangkaan. **Menunggu ia melebihi 30 minit dahulu, baru
memindahkannya, ialah rancangan untuk gagal sekali dahulu.** Maka CI dipecah **tiga lapis**:

| Lapis | Job | Kandungan | Timeout |
|---|---|---|---|
| 1 | `integration` (sedia ada) | Pest penuh + **canary sesi** (`@session-canary`) + **smoke Playwright** (project `ci-guidance`) + **aliran domain** (project `ci-domain`, P16-08) | 30 (kekal) |
| 2 | `guidance-e2e` (**baharu**, matriks 3 shard) | Gate penuh G1–G5 §7.3 | 45 setiap shard |
| 3 | `guidance-e2e-gate` (**baharu**, agregator) | Gabung artifak shard + assert denominator | 10 |

**Lapis 2 mengisytiharkan `services:`, `env:` dan setup PHP/Node/build SENDIRI** — ia **tidak**
"guna semula" apa-apa daripada `integration` (P12-04 kekal betul: GitHub Actions tidak berkongsi
service containers antara job). Duplikasi ±60 baris konfigurasi ialah **harga yang betul** untuk
gate yang deterministik; ia dikurangkan dengan `needs: integration` supaya shard hanya berjalan
selepas Pest hijau.

**Pemetaan shard beku (F0 — SEBELUM kerja F6 bermula):**

| Shard | Family | Guide | Langkah | Langkah tindakan |
|---|---|---:|---:|---:|
| `screen` | `screen` | 29 | 151 | 151 |
| `workflow` | `workflow` | 14 | 158 | 75 |
| `tenant-admin-public` | `tenant`+`admin`+`public` | 40 | 164 | 3 |
| **JUMLAH** | | **83** | **473** | **229** |

*(Angka dikira semula secara bebas P15 daripada `resources/help/guides.json` — 29+14+40 = 83 ·
151+158+164 = 473 · 151+75+3 = 229.)*

**⚠️ PELAKSANAAN LITERAL DIBEKUKAN v1.7 (P16-02).** v1.6 membekukan nama shard dan denominator
tetapi tidak nama spec, command, skema artifak, nama skrip agregator atau YAML — jadi reka bentuk
gate masih perlu direka semasa PR, iaitu tepat yang P12/P14 larang. Yang berikut ialah kontrak
lengkap; tiada bahagiannya ditangguhkan.

**(a) Spec dan parameter shard**

| Perkara | Nilai beku |
|---|---|
| Fail spec | **`e2e/guidance-full.spec.js`** (satu fail; **bukan** `guidance.spec.js`, yang kekal sebagai smoke 20-konteks) |
| Project Playwright | **`guidance-full`** |
| Pemilih shard | pemboleh ubah persekitaran **`GUIDANCE_SHARD`** ∈ `screen` \| `workflow` \| `tenant-admin-public` |
| Sumber senarai kerja | manifest `catalogue` F0(ii) ditapis `shard === process.env.GUIDANCE_SHARD` — **bukan** pembahagian automatik |
| Output | **`storage/app/plan-f6/shard-${GUIDANCE_SHARD}.json`** *(laluan ditukar v1.9 — §1 F0(iv)(g))*, dimuat naik sebagai artifak bernama `guidance-shard-${GUIDANCE_SHARD}` |

**Command exact setiap nilai matrix (ketiga-tiganya identik kecuali pemboleh ubah):**

```bash
GUIDANCE_SHARD=screen              npx playwright test --project=guidance-full
GUIDANCE_SHARD=workflow            npx playwright test --project=guidance-full
GUIDANCE_SHARD=tenant-admin-public npx playwright test --project=guidance-full
```

**(b) Skema JSON artifak shard (dibekukan — agregator menolak fail yang tidak sepadan)**

```jsonc
{
  "schema_version": 1,
  "shard": "screen",                       // mesti sama dengan GUIDANCE_SHARD
  "catalog_version": "2026.07.22.2",       // mesti sama merentas ketiga-tiga shard
  "manifest_sha256": "<hash manifest F0>", // mesti sama merentas ketiga-tiga shard
  "expected": { "guides": 29, "steps": 151, "action_steps": 151 },  // dari manifest, bukan tangan
  "guide_ids":       ["screen.buka-fail-baharu", "…"],   // G4: kitaran penuh dilalui
  "step_ids":        ["screen.buka-fail-baharu#1", "…"], // G1: berstatus; kunci <guide_id>#<index1>
  "action_step_ids": ["screen.buka-fail-baharu#3", "…"], // G3: tour black-box dijalankan
  "status_counts":   { "specific": 0, "generic-justified": 0, "not-applicable": 0,
                       "risk-accepted": 0, "blocked": 0 },
  "blocked":  [{ "step": "screen.x#2", "reason": "…" }],
  "failures": [{ "step": "screen.x#2", "gate": "G2", "message": "…" }],
  "complete": true                         // shard sendiri mengisytihar liputannya lengkap
}
```

Peraturan skema: `guide_ids`/`step_ids`/`action_step_ids` ialah **set** (duplikat = gagal);
kunci langkah **mesti** `<guide_id>#<index1>` — `step.id` katalog **dilarang** sebagai kunci
kerana ia tidak unik (`dashboard.1/2/3` muncul dalam `tenant.dashboard` **dan** `admin.dashboard`;
473 langkah → 470 `step.id` unik sahaja).

**(c) Agregator**

| Perkara | Nilai beku |
|---|---|
| Skrip | **`scripts/audit/aggregate-guidance-coverage.mjs`** (Node 22, tiada dependensi baharu — §0.1(2)) |
| Command | `node scripts/audit/aggregate-guidance-coverage.mjs --manifest "Audit Review Round Robin/bukti/plan-baseline/manifest.json" --shards "storage/app/plan-f6/artifacts/guidance-shard-*/shard-*.json" --out storage/app/plan-f6/coverage-gate.json` |
| Exit | `0` hanya jika **semua** assertion di bawah lulus; selainnya `1` dengan senarai ID yang menyebabkannya |

**Kontrak agregator `guidance-e2e-gate` (required check) — perbandingan SET, bukan count:**
- Muat turun artifak **ketiga-tiga** shard; **gagal jika mana-mana artifak hilang** (shard yang
  tidak berjalan ≠ shard yang lulus) dan gagal jika `schema_version` / `catalog_version` /
  `manifest_sha256` tidak sepadan antara shard;
- **Bandingkan set dengan manifest, bukan bilangan.** Untuk setiap kategori
  (`guide_ids` / `step_ids` / `action_step_ids`): kira `hilang = manifest \ gabungan` dan
  `lebihan = gabungan \ manifest`, serta `bertindih` (ID yang muncul dalam >1 shard). **Gagal jika
  mana-mana daripada ketiga-tiga set itu bukan kosong, dan senaraikan ID sebenarnya** (maksimum
  50 dicetak, jumlah penuh dilaporkan — tiada had senyap). Kesamaan **kardinaliti sahaja**
  (473 = 473) **tidak** diterima sebagai bukti: dua ID silap boleh membatalkan antara satu sama
  lain;
- Assert saiz set gabungan **tepat** `473` langkah berstatus · `229` langkah tindakan ·
  `83` guide — **selepas** perbandingan set di atas lulus (angka menjadi semakan kedua, bukan
  pertama);
- Assert `blocked == 0` merentas semua shard (§7.3 — `blocked` ialah release blocker, P14-06);
- Assert `failures` kosong dan setiap shard melaporkan `complete: true`;
- Tulis `coverage-gate.json` (ringkasan + set hilang/lebihan/bertindih) sebagai artifak, supaya
  kegagalan boleh didiagnosis tanpa menjalankan semula shard.

**(d) YAML CI — LITERAL PENUH (ditulis semula v1.8, P18-02)**

⚠️ **Sebab ditulis semula.** v1.7 melabel blok ini "bentuk beku — bukan cadangan" sedangkan ia
masih mengandungi `{ image: postgres:16-alpine, ... }` dan komen *"setup PHP/Node/composer/npm/
build/migrate:fresh --seed/serve/canary — sama lapis 1"*. Placeholder itu memulangkan reka bentuk
services/env/setup kepada masa PR — tepat yang P12/P14/P16 larang. Semua nilai di bawah disalin
daripada blok yang **sudah wujud dan sudah hijau** dalam `.github/workflows/ci.yml`
(services `:22-51` · env `:52-80` · setup `:82-121`); **lima** perbezaan sengaja ditandai
`# OVERRIDE`.

⚠️ **`name:` sengaja TIDAK ditetapkan pada kedua-dua job baharu (P18-01).** Apabila `name:`
ditinggalkan, nama status check GitHub Actions **ialah job id** — jadi check bernama
`guidance-e2e-gate` dan `guidance-e2e (screen|workflow|tenant-admin-public)`, sepadan **persis**
dengan nama yang dirujuk di seluruh pelan ini. v1.7 memberi job agregator
`name: Guidance coverage gate` sambil menyebut required check `guidance-e2e-gate` di enam lokasi
lain — dua nama untuk satu check, dan branch protection hanya menerima satu.

```yaml
  guidance-e2e:
    needs: integration
    runs-on: ubuntu-24.04
    timeout-minutes: 45
    strategy:
      fail-fast: false
      matrix:
        shard: [screen, workflow, tenant-admin-public]
    services:                          # DIISYTIHAR SENDIRI — job tidak berkongsi services (P12-04)
      postgres:                        # salinan literal ci.yml:23-35
        image: postgres:16-alpine
        env:
          POSTGRES_DB: diwan_test
          POSTGRES_USER: diwan
          POSTGRES_PASSWORD: diwan-ci-only
        ports:
          - 5432:5432
        options: >-
          --health-cmd "pg_isready -U diwan -d diwan_test"
          --health-interval 10s
          --health-timeout 5s
          --health-retries 10
      redis:                           # salinan literal ci.yml:36-44
        image: redis:7-alpine
        ports:
          - 6379:6379
        options: >-
          --health-cmd "redis-cli ping"
          --health-interval 10s
          --health-timeout 5s
          --health-retries 10
      meilisearch:                     # salinan literal ci.yml:45-51
        image: getmeili/meilisearch:v1.12
        env:
          MEILI_MASTER_KEY: ci-master-key-1234567890
          MEILI_NO_ANALYTICS: "true"
        ports:
          - 7700:7700
    env:                               # salinan literal ci.yml:53-80 + 5 OVERRIDE bertanda
      APP_ENV: testing
      APP_DEBUG: "false"
      APP_KEY: base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=
      APP_URL: http://127.0.0.1:8092         # OVERRIDE 1 (ci.yml:56 = 8080; Playwright = 8092)
      E2E_BASE_URL: http://127.0.0.1:8092    # OVERRIDE 2 (baharu; playwright.config.js:11)
      DB_CONNECTION: pgsql
      DB_HOST: 127.0.0.1
      DB_PORT: 5432
      DB_DATABASE: diwan_test
      DB_USERNAME: diwan
      DB_PASSWORD: diwan-ci-only
      CACHE_STORE: redis
      REDIS_CLIENT: phpredis
      REDIS_HOST: 127.0.0.1
      QUEUE_CONNECTION: sync
      SESSION_DRIVER: file                   # OVERRIDE 3 (ci.yml:67 = array → sesi HTTP mustahil)
      MAIL_MAILER: array
      SCOUT_DRIVER: collection
      MEILISEARCH_HOST: http://127.0.0.1:7700
      MEILISEARCH_KEY: ci-master-key-1234567890
      DIWAN_STORAGE_DISK: local
      BACKUP_DISK: local
      IMAP_ENABLED: "false"
      WHATSAPP_DRIVER: log
      LOG_CHANNEL: stderr
      DIWAN_LOGIN_RATE_LIMIT: "100"
      E2E_ROLE_LOGIN_DELAY_MS: "0"           # OVERRIDE 4 (guidance.spec.js:10 lalai 15000 ms)
      GUIDANCE_SHARD: ${{ matrix.shard }}    # OVERRIDE 5 (pemilih shard, F0(iv)(a))
    steps:
      - uses: actions/checkout@v5

      - name: Setup PHP                      # salinan literal ci.yml:84-89
        uses: shivammathur/setup-php@v2
        with:
          php-version: "8.4"
          extensions: mbstring, intl, pdo_pgsql, redis, pcntl, posix, zip, gd, exif
          coverage: none

      - name: Setup Node                     # salinan literal ci.yml:91-95
        uses: actions/setup-node@v5
        with:
          node-version: "22"
          cache: npm

      - name: Cache Composer packages        # salinan literal ci.yml:97-102
        uses: actions/cache@v5
        with:
          path: ~/.cache/composer/files
          key: composer-${{ runner.os }}-${{ hashFiles('composer.lock') }}
          restore-keys: composer-${{ runner.os }}-

      - name: Install dependencies           # salinan literal ci.yml:109-112
        run: |
          composer install --no-interaction --prefer-dist --no-progress
          npm ci --no-audit --no-fund

      - name: Build assets                   # salinan literal ci.yml:120-121
        run: npm run build

      - name: Install Chrome for Playwright
        run: npx playwright install --with-deps chrome

      - name: Migrate and seed
        run: |
          php artisan config:clear
          php artisan migrate:fresh --seed --force --no-interaction
          mkdir -p storage/framework/sessions storage/app/plan-ci storage/app/plan-f6
          chmod -R ug+rw storage/framework

      - name: Serve application on 8092
        run: |
          php artisan serve --no-reload --host=127.0.0.1 --port=8092 \
            > storage/logs/serve-ci.log 2>&1 &
          echo "serve_pid=$!" >> "$GITHUB_ENV"
          for attempt in $(seq 1 30); do
            curl --fail --silent http://127.0.0.1:8092/up && break
            sleep 1
          done
          curl --fail --silent http://127.0.0.1:8092/up

      - name: Session canary
        env:
          DIWAN_PW_JSON: storage/app/plan-ci/canary-${{ matrix.shard }}.json
        run: |
          npx playwright test --project=ci-guidance --grep @session-canary
          node scripts/audit/assert-playwright-json.mjs \
            --file "$DIWAN_PW_JSON" --min-tests 1

      - name: Guidance full gate shard
        env:
          DIWAN_PW_JSON: storage/app/plan-ci/guidance-full-${{ matrix.shard }}.json
        run: |
          npx playwright test --project=guidance-full
          node scripts/audit/assert-playwright-json.mjs \
            --file "$DIWAN_PW_JSON" --min-tests 1

      - name: Stop served application        # BUKAN trap: setiap `run:` ialah shell berasingan
        if: always()
        run: kill "$serve_pid" 2>/dev/null || true

      # Corak dua-step yang sama (P22-T5, v1.10) — `error` hanya pada laluan success:
      - uses: actions/upload-artifact@v7
        if: success()
        with:
          name: guidance-shard-${{ matrix.shard }}
          path: storage/app/plan-f6/shard-${{ matrix.shard }}.json
          if-no-files-found: error          # shard lulus + fail hilang = gate
          retention-days: 14

      - uses: actions/upload-artifact@v7
        if: failure()          # shard gagal: naikkan apa yang ada (agregator akan melapor
        with:                  # "missing shard" dengan sebab — itulah diagnosisnya)
          name: guidance-shard-${{ matrix.shard }}
          path: storage/app/plan-f6/shard-${{ matrix.shard }}.json
          if-no-files-found: ignore
          retention-days: 14

      - uses: actions/upload-artifact@v7
        if: success()          # bukti "berjalan, tidak di-skip" (P20-04)
        with:
          name: guidance-pw-json-${{ matrix.shard }}
          path: storage/app/plan-ci/*-${{ matrix.shard }}.json
          if-no-files-found: error
          retention-days: 14

      - uses: actions/upload-artifact@v7
        if: failure()
        with:
          name: guidance-pw-json-${{ matrix.shard }}-failure
          path: storage/app/plan-ci/*-${{ matrix.shard }}.json
          if-no-files-found: ignore
          retention-days: 14

  guidance-e2e-gate:
    needs: guidance-e2e
    if: always()              # mesti berjalan walaupun satu shard gagal, supaya ia melapor SEBAB
    runs-on: ubuntu-24.04
    timeout-minutes: 10
    steps:
      - uses: actions/checkout@v5
      - uses: actions/setup-node@v5
        with:
          node-version: "22"
      - uses: actions/download-artifact@v7
        with:
          pattern: guidance-shard-*
          path: storage/app/plan-f6/artifacts
      - run: |
          mkdir -p storage/app/plan-f6
          node scripts/audit/aggregate-guidance-coverage.mjs \
            --manifest "Audit Review Round Robin/bukti/plan-baseline/manifest.json" \
            --shards "storage/app/plan-f6/artifacts/guidance-shard-*/shard-*.json" \
            --out storage/app/plan-f6/coverage-gate.json
      - uses: actions/upload-artifact@v7
        if: success()          # P22-T5 (v1.10): error hanya bila agregator lulus tetapi
        with:                  # output hilang; jika agregator gagal, mesejnya sendiri
          name: guidance-coverage-gate    # (cth. "missing shard") ialah diagnosis utama
          path: storage/app/plan-f6/coverage-gate.json
          if-no-files-found: error
          retention-days: 14
      - uses: actions/upload-artifact@v7
        if: failure()
        with:
          name: guidance-coverage-gate-failure
          path: storage/app/plan-f6/coverage-gate.json
          if-no-files-found: ignore
          retention-days: 14
```

⚠️ **Tiga perubahan v1.9 dalam blok di atas (P20-01/03/04/06), semuanya sengaja:**
`serve --no-reload` (lihat (d-1) untuk sebabnya) · **step cleanup `if: always()`** menggantikan
mana-mana `trap` · `assert-playwright-json.mjs` dipanggil **selepas shard**, bukan canary sahaja ·
upload artifak JSON · laluan `bukti/plan-*` → `storage/app/plan-*` (§1 F0(iv)(g)).
`actions/setup-node@v5` **ditambah** pada job agregator kerana job itu tidak mempunyai langkah
`Setup Node` sendiri dan menjalankan `node` — imej `ubuntu-24.04` memang membawa Node, tetapi
versinya tidak dijamin 22 dan agregator ialah Node 22 tulen.

**Nota kebolehjalanan yang mesti dihormati semasa PR (bukan pilihan):**
- **`apt-get` OCR (`ci.yml:104-107`) sengaja TIDAK disalin** ke lapis 2: `guidance-full` ialah
  lawatan tour read-only dan tidak memuat naik dokumen, jadi `ProcessOcrJob` tidak pernah
  dicetuskan di sini. **Syarat pembatalan:** jika mana-mana langkah shard memuat naik dokumen,
  langkah `Install OCR tooling` mesti disalin masuk **dalam PR yang sama** — `QUEUE_CONNECTION:
  sync` bermakna job OCR akan berjalan dalam permintaan itu juga.
- **`Validate, audit and format` (`ci.yml:114-118`) tidak disalin** — Pint/`composer audit` sudah
  dijalankan oleh job `integration` yang menjadi `needs:` job ini; mengulanginya menambah masa
  tanpa menambah maklumat.
- **`serve_pid` ditulis ke `$GITHUB_ENV`** supaya **step cleanup berasingan** (`if: always()`)
  boleh membunuhnya. ⚠️ **DIBETULKAN v1.9 (P20-01): `trap … EXIT` DILARANG untuk server ini.**
  Setiap `run:` dalam GitHub Actions dilaksanakan sebagai shell **berasingan**, jadi trap yang
  dipasang dalam step serve menyala pada penghujung step itu — membunuh server **sebelum**
  Playwright bermula. (Corak `trap` pada `ci.yml:141` sah kerana Horizon dilancar **dan** diguna
  dalam **satu** step yang sama.) Nilai yang ditulis ke `$GITHUB_ENV` hanya kelihatan pada step
  **berikutnya**, yang tepat dengan keperluan di sini.
- **`storage/app/plan-ci/` dan `storage/app/plan-f6/` dicipta oleh langkah migrate.**
  ⚠️ **Sebab dibetulkan v1.9 (P20-03):** v1.8 mendakwa "reporter JSON gagal jika direktorinya
  tiada" — **itu tidak benar**; reporter JSON Playwright melakukan `mkdir` rekursif sendiri
  (`node_modules/playwright/lib/runner/index.js:4061-4065`). `mkdir -p` dikekalkan kerana
  **spec shard dan agregator** menulis failnya sendiri tanpa `mkdir`, bukan kerana reporter.

**(d-1) YAML CI LAPIS 1 — LITERAL PENUH (BAHARU v1.9, P20-01/02/03/04)**

⚠️ **Sebab blok ini wujud.** v1.8 membekukan YAML lapis **2/3** tetapi meninggalkan lapis 1 sebagai
senarai naratif + command bash berasingan. Itu memulangkan tiga keputusan reka bentuk kepada masa
PR — env **proses server**, kedudukan cleanup, dan penjadualan gate Meilisearch — iaitu tepat
kelas ralat yang P14-01/P16-01 sudah bayar harganya sekali.

**Tempat sisipan (tunggal):** dalam job `integration`, **selepas** step
`Migrate PostgreSQL and run full suite` (`.github/workflows/ci.yml:128-132`) dan **sebelum**
`Runtime compatibility smoke` (`:134-148`). **Blok `env:` job (`:52-80`) TIDAK diubah** — menukar
`SESSION_DRIVER` pada aras job akan menukar persekitaran suite Pest yang kini hijau, tanpa
keperluan; semua override diletakkan pada step yang memerlukannya.

```yaml
      - name: Meilisearch help index gate         # P20-02 — C20 jadi step, bukan nota
        env:
          SCOUT_DRIVER: meilisearch
        run: |
          php artisan diwan:sync-help-index --delete | tee storage/logs/help-index-ci.log
          grep -qF '83 guide disegerakkan ke indeks diwan_help_guides.' \
            storage/logs/help-index-ci.log

      - name: Prepare Playwright e2e
        run: |
          php artisan migrate:fresh --seed --force --no-interaction
          mkdir -p storage/framework/sessions storage/app/plan-ci
          chmod -R ug+rw storage/framework
          npx playwright install --with-deps chrome

      - name: Serve application on 8092           # env SERVER, bukan env langkah Playwright
        env:
          APP_URL: http://127.0.0.1:8092          # OVERRIDE (job env :56 = 8080)
          SESSION_DRIVER: file                    # OVERRIDE (job env :67 = array)
        run: |
          php artisan serve --no-reload --host=127.0.0.1 --port=8092 \
            > storage/logs/serve-ci.log 2>&1 &
          echo "serve_pid=$!" >> "$GITHUB_ENV"
          for attempt in $(seq 1 30); do
            curl --fail --silent http://127.0.0.1:8092/up && break
            sleep 1
          done
          curl --fail --silent http://127.0.0.1:8092/up

      - name: Session canary
        env:
          E2E_BASE_URL: http://127.0.0.1:8092
          E2E_ROLE_LOGIN_DELAY_MS: "0"
          DIWAN_PW_JSON: storage/app/plan-ci/ci-canary.json
        run: |
          npx playwright test --project=ci-guidance --grep @session-canary
          node scripts/audit/assert-playwright-json.mjs \
            --file "$DIWAN_PW_JSON" --min-tests 1

      - name: Guidance smoke
        env:
          E2E_BASE_URL: http://127.0.0.1:8092
          E2E_ROLE_LOGIN_DELAY_MS: "0"
          DIWAN_PW_JSON: storage/app/plan-ci/ci-guidance.json
        run: |
          npx playwright test --project=ci-guidance
          node scripts/audit/assert-playwright-json.mjs \
            --file "$DIWAN_PW_JSON" --min-tests 3

      - name: Domain flows
        env:
          E2E_BASE_URL: http://127.0.0.1:8092
          E2E_ROLE_LOGIN_DELAY_MS: "0"
          DIWAN_PW_JSON: storage/app/plan-ci/ci-domain.json
        run: |
          npx playwright test --project=ci-domain
          node scripts/audit/assert-playwright-json.mjs \
            --file "$DIWAN_PW_JSON" --min-tests 2

      - name: Stop served application
        if: always()
        run: kill "$serve_pid" 2>/dev/null || true

      # DUA step upload (P22-T5, v1.10): `error` HANYA bila ujian berjaya tetapi bukti hilang —
      # pada kegagalan terdahulu, ketiadaan JSON ialah AKIBAT, bukan gate baharu; satu step
      # `error` tanpa syarat akan menutup diagnosis asal dengan "No files were found".
      - name: Upload Playwright JSON evidence
        if: success()
        uses: actions/upload-artifact@v7
        with:
          name: ci-playwright-json
          path: storage/app/plan-ci/*.json
          if-no-files-found: error          # ujian lulus + JSON hilang = gate (P20-04 kekal)
          retention-days: 14

      - name: Upload Playwright JSON evidence (on failure)
        if: failure()
        uses: actions/upload-artifact@v7
        with:
          name: ci-playwright-json-failure
          path: |
            storage/app/plan-ci/*.json
            storage/logs/serve-ci.log
            storage/logs/help-index-ci.log
          if-no-files-found: ignore          # kegagalan asal step terdahulu ialah diagnosis utama
          retention-days: 14
```

**Empat keputusan dalam blok ini yang TIDAK boleh diubah semasa PR tanpa membatalkan gate:**

1. **`--no-reload` pada `php artisan serve` (P20-01; naratif DIBETULKAN v1.10, P22-T1).**
   Mekanisme sebenar (disahkan vendor + probe runtime oleh KEDUA-DUA ejen):
   `ServeCommand::startProcess()` (`vendor/laravel/framework/src/Illuminate/Foundation/Console/
   ServeCommand.php:181-189`) memetakan **ahli `$_ENV`** yang bukan `$passthroughVariables`
   (`:79-94` — **`APP_URL`, `SESSION_DRIVER`, `DB_*`, `E2E_*` semuanya TIADA** dalam senarai)
   kepada `false` apabila `.env` wujud dan `--no-reload` tidak diberi. **TETAPI** pemetaan itu
   hanya menyentuh kunci yang benar-benar berada dalam `$_ENV`: dengan `php.ini` produksi
   (lalai setup-php) `variables_order=GPCS` → **`$_ENV` KOSONG** (probe mesin ini: `PATH` tiada
   dalam `$_ENV`, `getenv('PATH')` ada) → penapisan menjadi no-op, dan Symfony Process
   membina env proses daripada **`getenv()`** (`Process.php:1688-1692`; hanya kunci bernilai
   `false` dibuang, `:355-363`) → `APP_URL`/`SESSION_DRIVER` **tetap diwarisi** walau `.env`
   wujud. **Maka dua-dua naratif lama salah arah:** CI hari ini selamat bukan *semata-mata*
   kerana `.env` tiada — ia juga bergantung pada `variables_order` runner, dan **kedua-duanya
   ialah keadaan luaran yang tidak dijamin** (imej runner boleh tukar ini; `E` boleh diaktifkan;
   `cp .env.example .env` boleh muncul kemudian). `--no-reload` KEKAL WAJIB kerana ia
   satu-satunya mekanisme yang menjadikan cabang `:184` benar **tanpa bergantung pada
   mana-mana** keadaan itu. *(Kesan sampingan diingini: tiada pengawas `filemtime` yang
   me-restart server di tengah larian.)* **Bukti larian CI pertama (bukan gate):** step probe
   `php -r "echo ini_get('variables_order'), ' env=', count($_ENV);"` direkod ke log bukti
   supaya andaian runner didokumen dengan nilai sebenar.
2. **Cleanup ialah STEP, bukan `trap` (P20-01).** Lihat nota kebolehjalanan (d).
3. **Gate Meilisearch dijalankan sebelum mana-mana spec carian (P20-02).** `SyncHelpIndex.php:38-42`
   pulang **`SUCCESS` awal** apabila `config('scout.driver') !== 'meilisearch'`, jadi step sedia ada
   `Validate help catalog` (`ci.yml:123-126`, `SCOUT_DRIVER: collection`) **tidak pernah** menyentuh
   Meilisearch — ia hanya validasi katalog. Assertion 83 dokumen **tidak** memerlukan kod baharu:
   command sudah membandingkan `numberOfDocuments` dengan bilangan dokumen katalog dan melempar
   pada mismatch (`:83-86`), selepas `waitForTasks` (`:78`). Yang ditambah di sini ialah
   **`grep -qF`** ke atas outputnya, supaya angka **83** itu sendiri terikat pada CI dan bukan
   hanya "sama dengan apa-apa yang ada dalam katalog". Shell lalai GitHub Actions untuk `bash`
   ialah `bash -eo pipefail`, jadi `| tee` **tidak** menyembunyikan exit code command.
   *Mengapa gate ini perlu walaupun spec hijau:* `HelpSearchService.php:24-30` mencuba Meili
   berdasarkan `MEILISEARCH_HOST` sahaja (**bukan** `scout.driver`), dan `:37-39` menangkap
   `Throwable` lalu `:42-45` **fallback senyap** ke carian PHP. Indeks kosong, indeks rosak, atau
   Meili mati semuanya kelihatan **identik** dengan indeks sihat dari sudut spec carian.
4. **`E2E_BASE_URL` + `E2E_ROLE_LOGIN_DELAY_MS` pada setiap step Playwright.** Ia env **klien**;
   ia sengaja **tidak** diletak pada aras job supaya `php artisan test` tidak mewarisinya.

**Risiko yang diisytihar (dipantau pada larian CI pertama, bukan diandaikan selesai):**
`$index->stats()` dibaca sejurus selepas `waitForTasks` — jika Meilisearch v1.12 melaporkan
`numberOfDocuments` dengan lag selepas task `succeeded`, gate ini akan flake pada mismatch.
Jika itu berlaku, pembetulan yang dibenarkan ialah gelung tunggu **dalam command** (kod aplikasi,
D11 fail #4 sudah meliputi `ci.yml`; perubahan `SyncHelpIndex.php` akan menjadi fail D11 **baharu**
dan mesti diisytihar) — **bukan** menurunkan assertion kepada "≥1 dokumen".

**(e) Reporter Playwright dan bukti "tidak di-skip" (BAHARU v1.8 — P18-05)**

⚠️ **Punca.** `playwright.config.js:9` hari ini ialah `reporter: [['line']]` sahaja. Maka
`results.json` yang v1.7 rujuk **tidak pernah dihasilkan**, dan skrip yang membacanya akan
sama ada meledak (baik) atau — lebih berkemungkinan ditulis dengan `try/catch` — **lulus senyap**
(buruk; ia mod kegagalan yang sama seperti `test.skip` yang P16-08 cuba tutup).

| Perkara | Nilai beku |
|---|---|
| Perubahan config | `playwright.config.js` — `reporter: process.env.DIWAN_PW_JSON ? [['line'], ['json', { outputFile: process.env.DIWAN_PW_JSON }]] : [['line']]` |
| Pemboleh ubah | **`DIWAN_PW_JSON`** — nama milik projek ini, **bukan** env dalaman Playwright; ditetapkan **per langkah CI**, tidak pernah pada aras job (dua project menulis fail berbeza) |
| Laluan output | `storage/app/plan-ci/<project>[-<shard>].json` — cth `storage/app/plan-ci/ci-domain.json`, `storage/app/plan-ci/ci-ocr.json`, `storage/app/plan-ci/guidance-full-screen.json` *(laluan ditukar v1.9 — §1 F0(iv)(g))* |
| Skrip assert | **`scripts/audit/assert-playwright-json.mjs`** (D11 fail #15; Node 22 tulen, tiada pakej baharu — §0.1(2)) |
| Command | `node scripts/audit/assert-playwright-json.mjs --file <path> --min-tests <N>` |
| Lalai dev | Tanpa `DIWAN_PW_JSON`, tingkah laku tempatan **tidak berubah** (`line` sahaja) — tiada fail sampah dalam repo pembangun |

**Assertion skrip (exit 1 pada mana-mana):**
1. **Fail wujud dan boleh di-`JSON.parse`** — fail hilang = **gagal**, bukan "tiada apa-apa untuk
   disemak". Ini assertion terpenting: ia yang membezakan "berjalan dan lulus" daripada "tidak
   pernah berjalan";
2. **Bilangan ujian ≥ `--min-tests`** — sifar ujian ditemui = gagal (menutup `testMatch` salah
   taip yang memadankan 0 fail);
3. **Tiada `result.status` ∈ `skipped` · `timedOut` · `interrupted`** merentas
   `suites[].specs[].tests[].results[]` (rekursif — `suites` boleh bersarang);
4. **Setiap `spec.ok === true`** dan `stats.unexpected === 0` dan `stats.flaky === 0`;
5. **`errors` aras-atas kosong**;
6. **Skema tidak dikenali = gagal keras.** Jika `suites`/`stats` tiada (cth. format berubah pada
   naik taraf `@playwright/test` daripada `^1.61.1` yang dipin `package.json:11`), skrip keluar 1
   dengan mesej "skema JSON reporter tidak dikenali" — ia **tidak** boleh mentafsir ketiadaan
   sebagai kejayaan;
7. **`stats.skipped === 0`** *(BAHARU v1.9 — P20-03)*. Ini **bukan** pertindihan dengan (3): (3)
   merentasi pokok `suites[]` secara rekursif dan boleh terlepas jika struktur bersarang berubah,
   manakala (7) ialah satu bacaan aras-atas yang murah dan langsung. Codex P20 mengesahkan pada
   larian temp `@playwright/test` 1.61.1 bahawa `test.skip()` muncul serentak sebagai
   `stats.skipped = 1`, `test.status = "skipped"` dan `results[0].status = "skipped"` — jadi
   kedua-dua pemeriksaan menangkap kes yang sama hari ini, dan mengekalkan kedua-duanya bermakna
   satu perubahan skema tidak boleh mematikan gate secara senyap.

Skrip ini dipakai oleh **setiap** gate Playwright tanpa kecuali: canary sesi · `ci-guidance` ·
`ci-domain` · ketiga-tiga shard `guidance-full` (v1.9, P20-03) · `ci-ocr` (selepas fixture).
Ia bukan khusus OCR — mana-mana project yang boleh `test.skip` sendiri perlu dibuktikan berjalan.
**Tiada gate Playwright dalam pelan ini yang dibenarkan berjalan tanpa panggilan skrip ini
selepasnya**; menambah project baharu tanpa assertnya = penyeludupan gate.

**(f) Required status check — NAMA GITHUB ACTIONS SEBENAR (ditulis semula v1.8, P18-01)**

⚠️ **Ralat yang dibetulkan.** v1.7 mengarahkan branch protection mewajibkan `Guidance coverage
gate`, `integration` **dan `ci-domain`**. Dua daripada tiga mustahil:

| Ditulis v1.7 | Realiti | Kesan jika ditetapkan begitu |
|---|---|---|
| required `ci-domain` | **Project Playwright** (`playwright.config.js` blok `projects:`), dijalankan sebagai *step* dalam job `integration` — GitHub tidak pernah melaporkan status check bernama ini | Check "expected" yang **tidak pernah tiba** → setiap PR tersekat selama-lamanya |
| required `integration` | Job itu mempunyai `name: PostgreSQL, Redis, Meili, OCR and tests` (`.github/workflows/ci.yml:19`); nama check = nilai `name:`, bukan job id | sama — check tidak pernah tiba |
| required `Guidance coverage gate` | Betul **hanya jika** job mengekalkan `name:` itu; tetapi enam lokasi lain dalam pelan menamakannya `guidance-e2e-gate` | dua nama, satu check → kekeliruan tetapan |

**Kontrak beku:**

1. **Job baharu tidak menetapkan `name:`** (lihat YAML (d)) → check name = job id:
   `guidance-e2e-gate`, `guidance-e2e (screen)`, `guidance-e2e (workflow)`,
   `guidance-e2e (tenant-admin-public)`.
2. **Job `integration` sedia ada TIDAK dinamakan semula.** Menukar `name:` menukar nama check dan
   akan mematahkan sebarang branch protection/PR terbuka yang sudah merujuk nama lama — kos tanpa
   faedah. Nama checknya kekal **`PostgreSQL, Redis, Meili, OCR and tests`**.
3. **DUA SENARAI YANG BERASINGAN (ditulis semula v1.9 — P20-05).** v1.8 menulis *"senarai required
   status check pada `main` (tepat tiga)"* lalu menyenaraikan **empat** nama, dan §10 mencampurkan
   check, step, project Playwright dan shard dalam satu ayat. Frasa **"tepat tiga" DIBATALKAN**.
   Kedua-dua senarai di bawah mempunyai **tujuan berlainan** dan tidak boleh digabungkan: senarai
   A ditaip ke dalam tetapan repo; senarai B disemak oleh manusia sebelum deploy.

   **A. REQUIRED BRANCH PROTECTION CHECKS pada `main` — tepat EMPAT nama, satu baris setiap satu**
   *(hanya nama check GitHub Actions layak; nama job id yang mempunyai `name:`, nama step, nama
   project Playwright dan nama artifak **tidak pernah** layak):*

   | # | Nama check exact | Sumber |
   |---|---|---|
   | A1 | `PostgreSQL, Redis, Meili, OCR and tests` | `ci.yml:19` (`name:` job `integration`) |
   | A2 | `guidance-e2e-gate` | job id agregator, **tiada `name:`** (F0(iv)(d)) |
   | A3 | `Docker app image` | `ci.yml:160` `name: Docker ${{ matrix.target }} image`, matriks `app` |
   | A4 | `Docker web image` | sama, matriks `web` |

   A3 dan A4 ialah **dua** check berasingan kerana `name:` mengandungi ungkapan matriks — inilah
   punca sebenar ralat "tepat tiga". Ia **sudah** wujud hari ini; ia disenaraikan supaya tiada
   sesiapa menyangka pelan ini menggugurkannya.

   **B. BUKTI KELUARAN (release evidence) — disemak sebelum deploy, BUKAN ditaip ke branch
   protection:**

   | # | Bukti | Cara disahkan |
   |---|---|---|
   | B1 | Job `integration` hijau | check A1 |
   | B2 | Step `Domain flows` (`--project=ci-domain`) benar-benar **berjalan** | artifak `ci-playwright-json` → `ci-domain.json` lulus `assert-playwright-json.mjs` |
   | B3 | Canary sesi + `ci-guidance` berjalan | artifak yang sama → `ci-canary.json`, `ci-guidance.json` |
   | B4 | Gate Meilisearch berjalan | log step `Meilisearch help index gate` mengandungi `83 guide disegerakkan…` |
   | B5 | Ketiga-tiga shard `guidance-e2e (screen)` / `(workflow)` / `(tenant-admin-public)` hijau | halaman checks larian (bukan branch protection) |
   | B6 | Setiap shard menghasilkan JSON yang lulus assert | artifak `guidance-pw-json-<shard>` |
   | B7 | Agregator lulus | check A2 + artifak `guidance-coverage-gate` |
   | B8 | Imej Docker dibina | check A3 + A4 |

4. **`ci-domain` TIDAK menjadi required check** — ia dikuatkuasakan kerana ia **step** dalam job
   `integration` **tanpa** `continue-on-error`, jadi kegagalannya menjadikan check **A1** merah.
   Bukti positif bahawa step itu benar-benar **berjalan** (bukan dilangkau) datang daripada
   `assert-playwright-json.mjs` pada `storage/app/plan-ci/ci-domain.json` (§1 F0(iv)(e)) — bukan
   daripada warna job. Perkara yang sama terpakai kepada `ci-guidance`, canary dan gate Meili.
5. **Shard `guidance-e2e (…)` tidak perlu required secara individu** — agregator gagal apabila
   mana-mana artifak shard hilang, yang menutup kes "shard dilangkau = dianggap lulus". Ia kekal
   dalam senarai **B** (B5/B6), bukan A. *(Menambahnya ke A tidak salah dari segi teknikal, tetapi
   ia menggandakan penguatkuasaan yang sama dan memperkenalkan tiga nama lagi yang mesti kekal
   sepadan dengan nilai `matrix.shard` selama-lamanya — kos penyelenggaraan tanpa liputan
   tambahan.)*
6. **Nama disahkan sebelum tetapan repo diubah, bukan diteka.** Selepas larian CI pertama pada
   cawangan PR F0, jalankan:

   ```bash
   gh api "repos/hakimalek27/Sistem-Pengurusan-Dokumen-Masjid/commits/$(git rev-parse HEAD)/check-runs" \
     --jq '.check_runs[].name' | sort -u
   ```

   dan tetapkan branch protection **hanya** daripada senarai yang dicetak. Jika satu nama yang
   dirancang tiada dalam output, itu pepijat YAML — bukan alasan untuk menaip nama secara manual.

Alternatif `npx playwright test --shard=i/n` **dibenarkan** menggantikan pemilihan
`GUIDANCE_SHARD`, **tetapi** pemetaan ID→shard mesti tetap dibekukan dalam manifest F0 supaya
agregator boleh membuktikan liputan; pembahagian automatik Playwright **tidak** memberi jaminan
itu sendiri (ia membahagi mengikut fail/ujian, bukan mengikut ID panduan).

**(g) Lokasi artifak & kebersihan git — KEPUTUSAN (BAHARU v1.9, P20-06)**

⚠️ **Masalah yang disahkan.** v1.8 memperkenalkan output CI pada laluan root `bukti/plan-ci/` dan
`bukti/plan-f6/`, sedangkan (i) root `.gitignore` **tidak** mengabaikan `/bukti`, dan (ii) §9.3
pelan ini sendiri berkata *"jangan cipta folder `bukti/` lain"* — bukti perancangan disimpan di
`Audit Review Round Robin/bukti/`. Menjalankan mana-mana command literal secara tempatan akan
menghasilkan fail untracked dalam repo aplikasi.

**Keputusan tiga-hala (mengikat untuk seluruh pelan):**

| Jenis output | Laluan beku | Status git |
|---|---|---|
| **Transient CI/tempatan** (JSON Playwright, JSON shard, `coverage-gate.json`, artifak yang dimuat turun agregator) | **`storage/app/plan-ci/`** dan **`storage/app/plan-f6/`** | **sudah** diabaikan — `storage/app/.gitignore` = `*` · `!private/` · `!public/` · `!.gitignore` |
| **Bukti perancangan/audit yang memang dikomit** (manifest baseline, hasil larian produksi F8) | `Audit Review Round Robin/bukti/plan-baseline/`, `…/bukti/plan-f8/` | dikomit bersama folder perancangan |
| **Root `bukti/`** | — | **DILARANG dicipta.** Sebarang PR yang menambahnya = pelanggaran (g) |

**Kenapa bukan dua cadangan P20 yang lain:**

- **`test-results/plan-ci` DITOLAK — ia akan memusnahkan bukti secara senyap.** `/test-results`
  memang sudah diabaikan (`.gitignore:20`), tetapi ia ialah `outputDir` lalai Playwright
  (`node_modules/playwright/lib/program.js:190`), dan Playwright **memadam** `outputDir` pada
  permulaan **setiap** larian: `createRemoveOutputDirsTask()` →
  `removeFolders([outputDir])` (`node_modules/playwright/lib/runner/index.js:5943-5962`), dilangkau
  hanya dengan `preserveOutputDir`. Lapis 1 menjalankan **tiga** larian Playwright berturut-turut
  dalam job yang sama (canary → `ci-guidance` → `ci-domain`), jadi `ci-canary.json` akan hilang
  sebelum ia sempat dimuat naik — dan gate yang membaca fail hilang akan gagal atas sebab yang
  **salah**, atau lebih buruk, dilonggarkan sehingga "fail tiada = tiada masalah" (mod kegagalan
  yang F0(iv)(e) assertion 1 wujud untuk menutupnya).
- **Menulis ke `Audit Review Round Robin/bukti/plan-ci/` DITOLAK** — ia memaksa CI menulis ke
  dalam folder perancangan yang bernama-berruang (setiap laluan perlu dipetik dalam YAML/bash),
  mencampurkan output mesin dengan dokumen keputusan manusia, dan akan menghasilkan diff bising
  pada setiap larian tempatan. Folder itu ialah rekod **keputusan**, bukan direktori kerja CI.
- **Menambah entri `.gitignore` DITOLAK sebagai lalai** — ia menambah **satu lagi fail repo**
  kepada D11 (`.gitignore`) semata-mata untuk menampung folder baharu yang tidak perlu wujud.
  `storage/app/` memberi hasil yang sama dengan **sifar** fail tambahan. *(Jika pemilik kemudian
  memilih laluan lain, `.gitignore` menjadi **D11 fail #18** dan mesti diisytihar — bukan
  ditambah senyap.)*

**Semakan yang mengesahkan `storage/app/plan-*` selamat (dakwaan DIKECILKAN v1.10, P22-T5b):**
disk Laravel `local` berakar pada `storage/app/private` (`config/filesystems.php:35`), disk
`public` pada `storage/app/public`. ⚠️ Kod aplikasi MEMANG menggunakan laluan `storage/app/` lain
di luar dua itu — `storage/app/manual-capture` (`config/filesystems.php:54`),
`storage/app/backup-temp` (`config/backup.php:185`), `storage/app/tmp/*`
(`ProcessOcrJob.php:68,118`, `ExportService.php:21`) — jadi dakwaan lama "tiada kod produk
menyentuh luar private/public" adalah **salah** dan dibatalkan. Jaminan sebenar ialah **awalan
`plan-*` unik**: `rg -F "plan-ci"`/`"plan-f6"` = 0 padanan dalam kod produk/ujian/command,
tiada disk/`Storage::fake()`/`storage:link` menyentuhnya, dan `migrate:fresh` tidak menyentuh
`storage/`. Bukti kekal bagi audit ialah **artifak GitHub** yang dimuat naik (retention 14 hari),
bukan fail dalam working tree.

**Langkah lapis 1 (smoke) yang ditambah, mengikut urutan:**
   - `php artisan migrate:fresh --seed --force` — `APP_ENV: testing` (baris 53) menyebabkan
     `DatabaseSeeder` memanggil `DemoSeeder` (gate `local`/`testing`), yang mencipta lapan akaun
     `*@demo.test` dengan kata laluan `password` dan tenant `mam`/`man` yang dijangka
     `e2e/guidance.spec.js:13-25`. Tanpa langkah ini, seeder **tidak** berjalan (baris 131 hanya
     `migrate --force`);
   - `npx playwright install --with-deps chrome` (config memin `channel: 'chrome'`);
   - **⚠️ SESI HTTP — pembetulan bloker P14-01, diperbetul semula v1.9 (P20-01).** v1.6–v1.8
     meletakkan **keempat-empat** pemboleh ubah pada "langkah Playwright". Dua daripadanya tidak
     berkesan di sana: `APP_URL` dan `SESSION_DRIVER` dibaca oleh **proses server**, dan proses itu
     sudah dilancar dengan env step **sebelumnya**. Env mesti dipecah mengikut proses yang
     membacanya:

     ```yaml
     # step "Serve application on 8092" — env PROSES SERVER
     env:
       APP_URL: http://127.0.0.1:8092      # job env baris 56 = 8080
       SESSION_DRIVER: file                # job env baris 67 = array — MUSTAHIL log masuk

     # setiap step Playwright — env PROSES KLIEN
     env:
       E2E_BASE_URL: http://127.0.0.1:8092 # playwright.config.js:11 baseURL
       E2E_ROLE_LOGIN_DELAY_MS: "0"
       DIWAN_PW_JSON: storage/app/plan-ci/<project>.json
     ```

     *(Ujian yang membezakan kedua-duanya: jika `SESSION_DRIVER: file` diletak hanya pada step
     Playwright, server terus menulis sesi ke `ArraySessionHandler` dan canary gagal — dengan
     mesej yang menuding kepada UI, bukan kepada konfigurasi. Itulah sebab canary mesti menamakan
     `SESSION_DRIVER` dan `APP_URL` dalam mesej kegagalannya.)*

     **Sebab teknikal (disahkan):** `ArraySessionHandler` menyimpan sesi dalam
     `protected $storage = []` milik **instance handler**
     (`vendor/laravel/framework/src/Illuminate/Session/ArraySessionHandler.php:17`, `read()`/`write()`
     `:61-75`, `:83-88`). PHP ialah *share-nothing* antara permintaan HTTP: setiap permintaan
     kepada `artisan serve` membina semula aplikasi, jadi handler bermula **kosong** dan cookie
     sesi pelayar tidak pernah menemui datanya. Pelayar log masuk → redirect → dianggap tetamu.
     *(Suite Pest semasa kekal hijau dengan `array` kerana permintaan ujian berkongsi satu
     instance dalam proses yang sama — jadi CI hijau hari ini **bukan** bukti sesi HTTP.)*
     `SESSION_DRIVER=file` menulis ke `storage/framework/sessions`
     (`config/session.php:63`), yang **wujud dalam checkout** (`storage/framework/sessions/.gitignore`
     dijejaki git) — jadi tiada `mkdir` diperlukan; langkah tetap menjalankan
     `mkdir -p storage/framework/sessions && chmod -R ug+rw storage/framework` sebagai jaring
     keselamatan idempotent. *(Alternatif `SESSION_DRIVER=database` ditolak: ia menambah
     kebergantungan jadual sesi tanpa faedah untuk larian sekali-guna.)*
   - hidangkan aplikasi pada **`127.0.0.1:8092`** (lalai `baseURL`, `playwright.config.js:11`):
     **`php artisan serve --no-reload --host=127.0.0.1 --port=8092 &`** → **simpan PID ke
     `$GITHUB_ENV`**, kemudian gelung tunggu `curl --fail --silent http://127.0.0.1:8092/up`
     (`health: '/up'` disahkan `bootstrap/app.php`) sehingga 30 percubaan sebelum menyerah dengan
     ralat jelas.
     ⚠️ **DIBETULKAN v1.9 (P20-01): `trap … EXIT` DIBUANG.** v1.6–v1.8 menyalin corak Horizon
     (`ci.yml:139-141`), tetapi corak itu sah **hanya** kerana Horizon dilancar dan diguna dalam
     **satu** step. Server e2e dilancar dalam satu step dan diguna dalam step-step **berikutnya**;
     setiap `run:` ialah shell berasingan, jadi trap EXIT akan membunuh server sebelum Playwright
     bermula. Cleanup ialah **step berasingan `if: always()`** — lihat §1 F0(iv)(d-1).
     ⚠️ **`--no-reload` wajib** — tanpanya `ServeCommand.php:184-189` boleh **membuang**
     `APP_URL`/`SESSION_DRIVER` daripada proses server apabila `.env` wujud **dan** `$_ENV`
     dihuni (`variables_order` mengandungi `E`) — dua keadaan luaran yang tidak dijamin;
     `--no-reload` melindungi tanpa syarat (sebab penuh + probe: (d-1) #1, dibetulkan v1.10);
   - **CANARY LOG MASUK (P14-01) — gate sebelum suite penuh. ⚠️ REKA BENTUK DITUKAR v1.7
     (P16-01): `curl` DIBUANG, canary menjadi spec Playwright bernama.**

     **Sebab pembuangan (bukti kod, bukan kebimbangan umum):** canary v1.6 mengarahkan GET
     `/app/login` + POST kredensial `email`/`password` dengan cookie. Itu **tidak boleh berjaya**:
     - log masuk Filament ialah **komponen Livewire**, bukan borang HTML yang POST ke route —
       `vendor/filament/filament/src/Auth/Pages/Login.php:459`
       (`Form::make(…)->livewireSubmitHandler('authenticate')`) dan `:387-389`
       (`Action::make('authenticate')->submit('authenticate')`). Penghantaran sebenar ialah
       `POST /livewire/update` yang membawa **snapshot + checksum** komponen — bukan sesuatu yang
       boleh dikarang dengan `curl` tanpa memparse HTML dan menandatangani semula snapshot;
     - nama medan bukan `email`. Panel ini menggunakan override `app/Filament/Auth/Login.php:21`
       (`TextInput::make('login')` — "E-mel atau No. Telefon"), jadi medannya ialah **`data.login`**;
       pemilih e2e sedia ada `e2e/guidance.spec.js:45` (`input[id="form.login"]`) mengesahkan ini;
     - had kadar log masuk dibungkus semula (`app/Filament/Auth/Login.php:68-76`), jadi canary
       yang gagal senyap boleh kelihatan seperti throttle.

     Canary yang **ditulis sebagai pseudokod `curl`** akan gagal walaupun `SESSION_DRIVER=file`
     betul — iaitu **bukti palsu terbalik**: ia menyekat CI atas sebab yang salah, atau (lebih
     buruk) dilonggarkan sehingga tidak mengassert apa-apa.

     **Kontrak beku pengganti:**

     | Perkara | Nilai beku |
     |---|---|
     | Fail spec | **`e2e/ci-session-canary.spec.js`** |
     | Nama ujian | `@session-canary sesi HTTP kekal selepas log masuk dan reload` |
     | Project | **`ci-guidance`** (ditambah ke `testMatch`) |
     | Command literal | `npx playwright test --project=ci-guidance --grep @session-canary` |
     | Akaun | `admin_masjid@demo.test` / `password` (`DemoSeeder.php:19,32`) |
     | Kaedah log masuk | **guna semula helper sedia ada** — corak `guidance.spec.js:42-50` (`input[id="form.login"]` → `input[type="password"]` → butang `/Log masuk/i`), **bukan** POST mentah |

     **Assertion wajib (kesemua empat; gagal salah satu = job gagal):**
     1. **Log masuk berjaya** — `page.waitForURL()` sehingga laluan `/app/mam` (tenant seeder
        `DemoSeeder`), bukan kekal pada `/app/login`;
     2. **Redirect sah** — respons akhir bukan borang log masuk: assert penanda panel
        berautentikasi (`[data-help-target="help-launcher"]` wujud — ia dirender hanya dalam
        panel) **dan** medan `input[id="form.login"]` **tidak** wujud;
     3. **Reload sekali** — `page.reload()`, kemudian assert **masih** pada `/app/mam` dan penanda
        panel masih ada (inilah ujian sebenar bahawa sesi **disimpan merentas permintaan**, yang
        `SESSION_DRIVER=array` **tidak** dapat lakukan);
     4. **Sesi masih sah pada permintaan baharu** — navigasi terus ke satu route panel kedua
        (`/app/mam/peti-masuk`) dan assert 200 + tiada redirect ke log masuk.

     Kegagalan canary mesti **menamakan `SESSION_DRIVER` dan `APP_URL` dalam mesejnya** (gunakan
     `expect(...).toBeTruthy()` dengan mesej tersuai atau `test.info().annotations`), kerana
     itulah dua punca yang paling mungkin. Tanpa canary, kegagalan sesi muncul sebagai 20+
     kegagalan Playwright yang kelihatan seperti pepijat UI sedangkan ia konfigurasi.

     *(Nota: jika pelaksana tetap mahu laluan `curl`, ia dibenarkan **hanya** dengan command penuh
     yang memparse token/snapshot Livewire daripada HTML sebenar dan mengassert exit code —
     **pseudokod dilarang**. Cadangan pelan: jangan; spec Playwright lebih pendek dan menggunakan
     laluan yang sama dengan suite.)*
   - **`E2E_ROLE_LOGIN_DELAY_MS: "0"`** — `guidance.spec.js:10` mengambil lalai **15 000 ms**
     antara log masuk; pada 20 konteks itu bermakna ±5 minit menunggu sahaja. Jarak itu wujud
     untuk had kadar produksi (5/min), sedangkan CI sudah menetapkan
     `DIWAN_LOGIN_RATE_LIMIT: "100"` (baris 80), jadi ia tidak diperlukan di sini;
   - **SKOP DIBEKUKAN DALAM PELAN, BUKAN DALAM PR (P14-01).** v1.5 berkata "senarai dinamakan
     dalam PR F0" — itu menangguhkan definisi gate ke masa pelaksanaan, bertentangan dengan
     tuntutan P12 tentang arahan literal, dan membuka ruang spec produksi/perlahan terpilih
     tanpa sengaja. `playwright.config.js` **tiada `projects`** hari ini (`testDir: './e2e'`
     sahaja), jadi F0 menambahnya:

     **⚠️ ALLOWLIST DIKETATKAN v1.7 (P16-08).** v1.6 mengecualikan `office-workflow.spec.js`,
     `ddms-extended.spec.js` dan `ocr-upload.spec.js` daripada CI dengan alasan "perlahan".
     Ketiga-tiganya menguji **tepat** permukaan yang F6/F8 sentuh (muat naik, klasifikasi, minit,
     carian, viewer), jadi gate G1–G5 (yang menguji **panduan** di atas UI itu) **bukan**
     pengganti kepada assertion domain di dalamnya. Ia menjadi project berasingan, bukan
     pengecualian kekal:

     ```js
     // playwright.config.js — F0
     projects: [
         {
             name: 'ci-guidance',           // lapis 1: smoke + canary sesi
             testMatch: [
                 'e2e/ci-session-canary.spec.js',
                 'e2e/guidance.spec.js',
                 'e2e/registration.spec.js',
                 'e2e/explore.spec.js',
             ],
         },
         {
             name: 'ci-domain',             // lapis 1b: aliran domain end-to-end (P16-08)
             testMatch: [
                 'e2e/office-workflow.spec.js',
                 'e2e/ddms-extended.spec.js',
             ],
         },
         {
             name: 'ci-ocr',                // lapis 1c: OCR — lihat syarat fixture di bawah
             testMatch: ['e2e/ocr-upload.spec.js'],
         },
         {
             name: 'guidance-full',         // lapis 2: gate penuh G1–G5, di-shard
             testMatch: ['e2e/guidance-full.spec.js'],
         },
     ],
     ```

     **Command literal lapis 1 (tiada tafsiran) — dikemas v1.8 (bukti JSON, P18-05) dan v1.9
     (laluan + gate Meili + serve, P20-01/02/06).** ⚠️ **Bentuk YAML rasmi ialah §1 F0(iv)(d-1);
     blok di bawah ialah versi tempatan yang setara** (untuk menjalankannya pada mesin pembangun).
     Jika kedua-duanya berbeza, **(d-1) menang** — ia yang ditampal ke `ci.yml`.

     ```bash
     # 0. gate indeks Meilisearch — mendahului sebarang spec carian (C20 / P20-02)
     SCOUT_DRIVER=meilisearch php artisan diwan:sync-help-index --delete \
       | tee storage/logs/help-index-ci.log
     grep -qF '83 guide disegerakkan ke indeks diwan_help_guides.' storage/logs/help-index-ci.log

     # 0b. server e2e — --no-reload wajib (ServeCommand.php:184-189 + syarat $_ENV; (d-1) #1)
     php artisan serve --no-reload --host=127.0.0.1 --port=8092 > storage/logs/serve-ci.log 2>&1 &
     serve_pid=$!
     until curl --fail --silent http://127.0.0.1:8092/up; do sleep 1; done

     # 1. canary sesi — mesti hijau sebelum apa-apa lagi dijalankan
     DIWAN_PW_JSON=storage/app/plan-ci/ci-canary.json \
       npx playwright test --project=ci-guidance --grep @session-canary
     node scripts/audit/assert-playwright-json.mjs \
       --file storage/app/plan-ci/ci-canary.json --min-tests 1

     # 2. smoke penuh (20 konteks, registrasi, explore)
     DIWAN_PW_JSON=storage/app/plan-ci/ci-guidance.json \
       npx playwright test --project=ci-guidance
     node scripts/audit/assert-playwright-json.mjs \
       --file storage/app/plan-ci/ci-guidance.json --min-tests 3

     # 3. aliran domain (office-workflow + ddms-extended) — bukti "berjalan", bukan hanya "hijau"
     DIWAN_PW_JSON=storage/app/plan-ci/ci-domain.json \
       npx playwright test --project=ci-domain
     node scripts/audit/assert-playwright-json.mjs \
       --file storage/app/plan-ci/ci-domain.json --min-tests 2

     # 4. bersihkan server (tempatan: trap SAH di sini kerana semuanya satu shell;
     #    dalam CI ia mesti menjadi step `if: always()` — lihat (d-1))
     kill "$serve_pid" 2>/dev/null || true
     ```

     *(`--min-tests` ialah **lantai**, bukan jangkaan exact: ia menangkap `testMatch` yang
     memadankan sifar fail. Nilai dinaikkan apabila spec baharu ditambah.)*

     **Syarat `ci-ocr` (TERIMA SEBAHAGIAN P16-08 — jangan aktifkan sebelum ini dipenuhi):**
     `e2e/ocr-upload.spec.js:4-6` melakukan `test.skip` melainkan **empat** pemboleh ubah
     (`SPDM_OCR_FIXTURE_1/2`, `SPDM_OCR_TERM_1/2`) diberi, dan fixture itu ialah fail luaran yang
     **tiada dalam repo**. Menambahnya ke CI hari ini menghasilkan **skip senyap** — gate yang
     sentiasa hijau tanpa menguji apa-apa (corak yang `spdm-deploy-lessons` namakan sebagai
     "command boleh cetak *selesai* walau dilangkau"). Maka:
     1. F0 mengekalkan `ci-ocr` sebagai project **tidak-required** sehingga dua imej fixture kecil
        (teks bercetak jelas, tiada data peribadi — PDPA) dikomit ke `tests/fixtures/ocr/` bersama
        dua istilah carian yang diketahui;
     2. selepas fixture wujud, langkah CI menetapkan keempat-empat pemboleh ubah dan gate
        **mengassert ujian TIDAK di-skip**. ⚠️ **DIBETULKAN v1.8 (P18-05):** v1.7 berkata "semak
        `results.json`" sedangkan `playwright.config.js:9` (`reporter: [['line']]`) **tidak
        menghasilkan JSON langsung** — arahan itu tiada fail untuk dibaca. Command literalnya:

        ```bash
        SPDM_OCR_FIXTURE_1=tests/fixtures/ocr/<fail-1> \
        SPDM_OCR_FIXTURE_2=tests/fixtures/ocr/<fail-2> \
        SPDM_OCR_TERM_1=<istilah-1> SPDM_OCR_TERM_2=<istilah-2> \
        DIWAN_PW_JSON=storage/app/plan-ci/ci-ocr.json \
          npx playwright test --project=ci-ocr
        node scripts/audit/assert-playwright-json.mjs \
          --file storage/app/plan-ci/ci-ocr.json --min-tests 1
        ```

        Upload artifaknya menggunakan **`if-no-files-found: ignore`** (bukan `error`) selagi
        `ci-ocr` belum required — ia satu-satunya pengecualian kepada peraturan P20-04; setiap
        gate lain menggunakan `error`.

        `--forbid-only` **tidak** mencukupi (ia menangkap `test.only`, bukan `test.skip`
        bersyarat `ocr-upload.spec.js:6`). Skrip assert menggagalkan `skipped`/`timedOut`/
        `interrupted`/0-ujian/fail-JSON-hilang — kontrak penuh dalam **§1 F0(iv)(e)**. Barulah
        `ci-ocr` menjadi required;
     3. **Tiada service ClamAV diperlukan untuk `ci-ocr`** — `config/diwan.php:31-36` menetapkan
        `CLAMAV_ENABLED` lalai **false** dan `app/Services/AntivirusScanner.php:12` pulang awal
        apabila ia mati, jadi cadangan "antivirus fixture" (P16-08) **tidak terpakai** kepada
        laluan OCR. `QUEUE_CONNECTION: sync` (`ci.yml:66`) dan tesseract/ocrmypdf
        (`ci.yml:104-107`) sudah memenuhi keperluan OCR dalam job `integration`.
        ⚠️ **Skop pembetulan itu dihadkan v1.8 (P18-04):** ia bermakna "tiada *container* ClamAV
        dalam CI", **bukan** "tiada gate antivirus". Cabang fail-closed
        `InboxIngestService.php:76-78` masih **tidak** pernah dilalui oleh mana-mana ujian hari
        ini; ia ditutup oleh ujian Pest ber-mock (§0.6 S7, D11 fail #14) yang **juga** tidak
        memerlukan service ClamAV.

     `testMatch` **sengaja tidak** menyenaraikan `production-readonly.spec.js` dan
     `production-guidance-readonly.spec.js` (§9.1a) — kedua-duanya ditujukan kepada produksi dan
     dijalankan hanya melalui wrapper. Setiap fasa yang menambah spec **mesti** mengemas
     `testMatch` dalam commit yang sama — ujian F0 mengassert bahawa setiap fail `e2e/*.spec.js`
     sama ada tersenarai dalam satu project atau tersenarai dalam allowlist "sengaja di luar CI"
     **dengan sebab bertulis + tarikh semakan semula**; fail tanpa kedua-duanya = merah.
     Ini menutup kes "spec baharu ditulis, tidak pernah dijalankan CI";
   - `E2E_PRODUCTION` **tidak** diset — `production-readonly.spec.js:24,67` kekal di-skip walaupun
     tersilap dimasukkan ke dalam senarai;
   - **gagalkan CI** jika merah; muat naik trace/skrinsyot **hanya apabila gagal**
     (`screenshot: 'only-on-failure'` + `trace: 'retain-on-failure'` sudah ditetapkan
     `playwright.config.js:17-18`), dengan semakan bahawa artifak tidak mengandungi kredensial
     (akaun ujian dijana seeder, bukan rahsia produksi);
   - **gate Meilisearch (C20) mendahului spec carian — kini STEP CI, bukan nota (v1.9, P20-02):**
     step `Meilisearch help index gate` dalam §1 F0(iv)(d-1) menjalankan
     **`SCOUT_DRIVER=meilisearch php artisan diwan:sync-help-index --delete`** dan mengassert
     output mengandungi **`83 guide disegerakkan ke indeks diwan_help_guides.`**. Command itu
     sudah menunggu task Meili (`SyncHelpIndex.php:78` `waitForTasks`) dan sudah menggagalkan
     mismatch kiraan (`:83-86`); yang v1.9 tambah ialah **menjadualkannya dalam CI** dan mengikat
     angka **83** itu sendiri. Menjalankan `diwan:sync-help-index` tanpa `SCOUT_DRIVER=meilisearch`
     **bukan** gate langsung: `:38-42` pulang `SUCCESS` awal dan hanya mengesahkan katalog.
   **Gate:** tiada deploy fasa berikutnya sebelum **kesemua** ini hijau pada commit fasa itu.
   ⚠️ **Bezakan dua senarai (v1.9, P20-05):** **required check** ialah `A1`–`A4` §1 F0(iv)(f)
   (tepat **empat** nama: `PostgreSQL, Redis, Meili, OCR and tests` · `guidance-e2e-gate` ·
   `Docker app image` · `Docker web image`); **bukti keluaran** ialah `B1`–`B8` (termasuk step
   `ci-domain`, canary, gate Meili, ketiga-tiga shard `guidance-e2e (…)` dan artifak JSON).
   Shard dan step **tidak** ditaip ke dalam branch protection. Lapis 2/3 boleh ditandakan
   `continue-on-error: false` tetapi **tidak** boleh ditandakan pilihan (`spdm-deploy-lessons`:
   sahkan CI hijau sebenar, jangan tafsir output). `ci-ocr` menjadi required hanya selepas fixture
   dikomit (syarat di atas). **Hijau sahaja tidak memadai:** setiap gate Playwright turut
   memerlukan artifak JSONnya lulus `assert-playwright-json.mjs` (§1 F0(iv)(e)) — job hijau dengan
   ujian di-skip **bukan** bukti.

**(iv-a) Jadual fail F0 — SEMUA artifak baharu/diubah (P16-03).** Senarai ini dan **D11 §11**
mesti sentiasa sepadan; menambah fail perkakas semasa pelaksanaan tanpa mengemas kedua-duanya
ialah penyeludupan skop. Tiada satu pun daripadanya mengubah tingkah laku produk dan tiada satu
pun menambah pakej (§0.1(2) dihormati).

| # | Fail | Jenis | Tujuan | Rujukan |
|---|---|---|---|---|
| 1 | `app/Console/Commands/RoleRoutes.php` (`diwan:role-routes`) | baharu | Jana manifest `role_routes` (read-only) | F0(ii-b), P14-03/P16-07 |
| 2 | `app/Console/Commands/AuditFixture.php` (`diwan:audit-fixture`) | baharu | Setup/cleanup/inventory fixture larian produksi | §9.1a, P16-04 |
| 3 | `playwright.config.js` | diubah | Blok `projects:` (`ci-guidance`, `ci-domain`, `ci-ocr`, `guidance-full`) **+ reporter JSON bersyarat `DIWAN_PW_JSON`** | F0(iv), F0(iv)(e) |
| 4 | `.github/workflows/ci.yml` | diubah | **Lapis 1 (literal, F0(iv)(d-1)):** gate Meilisearch + prepare + serve `--no-reload` + canary + `ci-guidance` + `ci-domain` + cleanup `if: always()` + upload JSON, dalam `integration`; **lapis 2/3:** job `guidance-e2e` (matriks) + `guidance-e2e-gate` | F0(iv)(d), (d-1) |
| 5 | `e2e/ci-session-canary.spec.js` | baharu | Canary sesi HTTP (`@session-canary`) | F0(iv), P16-01 |
| 6 | `e2e/guidance-full.spec.js` | baharu | Gate penuh G1–G5, dipandu `GUIDANCE_SHARD` | §7.3, P16-02 |
| 7 | `scripts/audit/aggregate-guidance-coverage.mjs` | baharu | Agregator set-union merentas shard | F0(iv)(c) |
| 8 | `scripts/audit/validate-plan-manifest.mjs` | baharu | Validator manifest: partition `wave`/`shard`, set-union exact, skema `role_routes` | F0(ii-a)/(ii-b), P16-05 |
| 9 | `e2e/production-guidance-readonly.spec.js` | baharu | Matriks produksi 20 konteks, **read-only mutlak** | §9.1a, P14-04 |
| 10 | `scripts/audit/run-production-guidance-readonly.ps1` | baharu | Wrapper tunggal larian produksi (`-RunUuid`, `-CleanupOnly`, `try/finally`) | §9.1a |
| 11 | `tests/Feature/AuditFixtureCommandTest.php` | baharu | Ujian **idempotensi** setup/cleanup + skop `run_uuid` + tenant `smoke` tidak tersentuh | §9.1a, P16-03 |
| 12 | `tests/Feature/PlanManifestTest.php` | baharu | Ujian invarian partition (83/473/200/258/6) + kunci `<guide_id>#<index1>` unik + setiap `e2e/*.spec.js` tersenarai dalam project atau allowlist bersebab | F0(ii-a), F0(iv) |
| 13a | `resources/help/targets.json` | baharu | Registry sasaran F6 (sumber kebenaran) | §7.2 langkah 4 |
| 13b | `docs/HELP-TARGETS.md` | dijana | Dokumentasi registry (dijana, bukan tangan) | §7.2 langkah 4 |
| **14** | **`tests/Feature/InboxAntivirusFailClosedTest.php`** | **baharu (v1.8)** | Gate fail-closed antivirus intake — mock `AntivirusScanner` (`infected`/`unavailable`/`error`), assert 0 `Record` + 0 media + 0 log + tenant lain tidak berubah | **§0.6 S7, P18-04** |
| **15** | **`scripts/audit/assert-playwright-json.mjs`** | **baharu (v1.8)** | Assert artifak JSON Playwright: tiada `skipped`/`timedOut`/`interrupted`, ≥`--min-tests`, fail hilang = gagal | **F0(iv)(e), P18-05** |
| 16a | `tests/fixtures/ocr/sample-scan-1.png` | baharu *(D11 "luluskan semua" → tidak lagi bersyarat; typo "D10-16" v1.10 dibetulkan v1.11)* | Imej imbasan #1 untuk `ci-ocr` tanpa skip senyap | F0(iv), P16-08 |
| 16b | `tests/fixtures/ocr/sample-scan-2.png` | baharu | Imej imbasan #2 | F0(iv), P16-08 |
| 16c | `tests/fixtures/ocr/terms.json` | baharu | Istilah padanan (`SPDM_OCR_TERM_1/2` dibaca dari fail ini dalam CI — deterministik & dikomit, bukan env ad-hoc) | F0(iv), P16-08 |
| 17 | `Audit Review Round Robin/bukti/plan-baseline/manifest.json` + `tools/` | baharu — **1 bundle audit** (bilangan fail `tools/` ditetapkan pada F0; bukan fail tunggal) | Manifest tiga set + skrip pengiraan | F0(ii)/(iii) |

**KIRAAN DINORMALISASI (v1.10, P22-T6 — unit dibetulkan, SKOP TIDAK BERUBAH):** jadual di atas
sebelum ini dilabel "16 fail + 1 artifak" dengan unit bercampur (#13 = 2 fail; #16 = wildcard).
Kiraan fail fizikal sebenar selepas pemilik meluluskan **semua** item (KEPUTUSAN-PEMILIK.md):
**#1–#12 = 12 fail · #13a/b = 2 fail · #14–#15 = 2 fail · #16a/b/c = 3 fail → 19 fail repo**
**+ #17 = 1 bundle audit** (manifest + `tools/`, dikira berasingan kerana ia artifak perancangan,
bukan kod). Kandungan yang diluluskan pemilik TIDAK berubah — hanya label kiraan dibetulkan.

*(**Sejarah unit kiraan:** v1.8 melabel jadual ini "16 fail repo + 1 artifak audit" — angka itu
mengira **16 entri** #1–#16, dengan #13 sebenarnya 2 fail dan #16 wildcard. **v1.10 (P22-T6)
menormalisasi unit kepada fail fizikal: 19 fail repo + 1 bundle audit** (blok KIRAAN
DINORMALISASI di atas) — angka **16 entri** kekal sah sebagai kiraan ENTRI, tetapi semua
rujukan "fail" kini menggunakan 19. **Penomboran v1.8 kekal:** #14/#15 baharu (P18-04/05);
bekas #14 → #16, bekas #15 → #17. Rujukan "D11 fail #2"/"#11" dalam §9.1a tidak terjejas.
Angka lapuk **12** dan **14** kekal dibuang — P18-06.)*

✅ **Disahkan v1.9 (P20-06) dan kekal benar selepas normalisasi v1.10:** kesemua enam pindaan P20
dilaksanakan dalam entri yang **sudah** tersenarai (#3 `playwright.config.js`, #4 `ci.yml`,
#15 skrip assert) — tiada entri baharu diseludup (P22-T6 turut mengesahkan `setup-node` = step
dlm #4 dan `tee storage/logs/` = output transient, bukan fail repo). Laluan output dipindah ke
`storage/app/plan-ci/` + `storage/app/plan-f6/` yang **sudah** diabaikan git, jadi
**`.gitignore` TIDAK disentuh** dan tiada entri #18 diperkenalkan — lihat §1 F0(iv)(g). Jika
pemilik kemudian memilih laluan lain yang memerlukan `.gitignore`, ia menjadi **D11 entri #18**
dan mesti dibawa semula kepada pemilik.

⚠️ **Nota kepada pemilik (mengapa 16, bukan 15 seperti dicadang P18-06):** Codex mencadangkan
`15 fail repo + 1 artifak audit` dengan mengandaikan hanya ujian antivirus ditambah. Menerima
P18-05 **juga** menambah satu fail repo (`assert-playwright-json.mjs`), kerana Playwright tidak
mempunyai flag "gagal jika di-skip" dan pemeriksaan itu perlu kod. Alternatif yang dipertimbang
dan ditolak: (a) `node -e` sebaris dalam YAML — tidak boleh diuji, tidak boleh diguna semula oleh
tiga gate; (b) custom reporter — juga fail baharu, tetapi lebih terikat kepada API dalaman
Playwright. Satu skrip generik yang dipakai canary + `ci-domain` + `ci-ocr` ialah kos terkecil.

(v) **Baseline bukti runtime (C06; rantaian dibetulkan P12-06)** — rekod rantaian bukti
pra-pembaikan bagi produksi semasa (§10 langkah 5A) supaya perbandingan selepas setiap deploy
bermakna: Git SHA server · Image ID + Created bagi **`diwan-app` DAN `diwan-web` secara
berasingan** (dua keluarga imej berbeza — `docker-compose.yml:6` vs `:40`) · Image ID setiap
container dipadan dengan keluarga imej yang **betul** (`app`/`worker`/`scheduler` → `diwan-app`;
`nginx` → `diwan-web`) · `public/build/manifest.json` daripada **kedua-dua** container ·
**nama aset exact** yang diekstrak daripada manifest (bukan wildcard) · sha256 fail aset itu
dalam kedua-dua container · sha256 **badan respons** URL awam aset yang sama.

(vi) **Kontrak runner produksi dibekukan (P14-04)** — nama spec, nama wrapper, command exact,
`run_uuid`, slug unik dan kontrak cleanup ditetapkan **sekarang** dalam **§9.1a**, bukan
"direkod dalam bukti fasa" kelak. F0 tidak menjalankannya; F0 membekukan kontraknya supaya F8
tidak mereka-reka prosedur pada saat ia paling berisiko (larian berautentikasi terhadap
produksi).

---

## 2. FASA F1 — Konteks HelpLauncher kekal merentas kitaran Livewire

**Menutup:** RR-01-02 (dan gandingannya) + RR-01-11 · **Keutamaan FINAL-RUMUSAN:** #1

### 2.1 Punca akar (disahkan dalam kod semasa)

`app/Livewire/HelpLauncher.php`:
- Baris **61–66**: `render()` membaca `request()->query('panduan')`, `request()->path()`,
  `request()->query('langkah')` — semasa AJAX Livewire, `request()` ialah `POST /livewire/update`,
  jadi `currentGuide('/livewire/update', …)` → `null`, `data-guide-id` hilang, `data-auto-start`→`0`.
- Baris **88**: `$origin = '/'.request()->path()` — konteks salah pada AJAX **dan** menghasilkan
  `//` pada halaman root (`path()` = `/` → `'/'.'/'` = `//`) = RR-01-11.
- **Pemburuk** (`resources/js/help.js:150-153`): `emit()` menghantar event Livewire
  `guidanceProgress` pada **setiap langkah tour** → setiap langkah mencetuskan render semula
  komponen → **tour memusnahkan konteksnya sendiri**.

### 2.2 Reka bentuk penyelesaian

**Pilihan dinilai:**

| Pilihan | Nilai | Keputusan |
|---|---|---|
| (a) Simpan konteks sebagai sifat komponen ditetapkan pada `mount()` | Livewire mengekalkan sifat merentas update; `mount()` berjalan pada muat halaman penuh sahaja — konteks sentiasa halaman sebenar | ✅ **PILIH** |
| (b) `#[Url]` attribute pada sifat | Menulis query string ke URL — bunyi bising, tidak perlu | ❌ |
| (c) Hantar path dari Blade layout sebagai parameter | Sama hasil dengan (a) tetapi setiap panggilan `<livewire:help-launcher>` perlu diubah (3 lokasi layout) | ❌ (lebih invasif) |
| (d) `skipRender()` pada `guidanceProgress()` supaya listener tidak render semula | Mengurangkan kekerapan masalah tetapi **tidak menutup punca** — interaksi Livewire lain pada halaman (borang, jadual) masih memusnahkan konteks | ➕ **TAMBAH sebagai pelengkap** kepada (a) — kurangkan kerja render sia-sia |

**Reka bentuk terpilih (a)+(d):**

```php
// KONSEP — bukan pelaksanaan akhir
class HelpLauncher extends Component
{
    #[Locked] public string $panel = 'public';
    #[Locked] public ?int $mosqueId = null;
    #[Locked] public string $originPath = '/';       // BAHARU — laluan halaman sebenar
    #[Locked] public ?string $requestedGuideId = null; // BAHARU — ?panduan= dari muat penuh
    #[Locked] public int $requestedStep = 0;           // BAHARU — ?langkah=
    #[Locked] public bool $launchPending = false;      // BAHARU (C04) — one-shot; Locked (P12-01)

    public function mount(string $panel = 'public', bool $showButton = true): void
    {
        // ... sedia ada ...
        $this->originPath = '/'.trim(request()->path(), '/');   // root: trim('/')='' → '/'.''='/'  (fix RR-01-11)
        $requested = request()->query('panduan');
        $this->requestedGuideId = is_string($requested) ? $requested : null;
        $this->requestedStep = max(0, (int) request()->query('langkah', 0));
        $this->launchPending = filled($this->requestedGuideId);
    }

    #[On('guidanceProgress')]
    public function guidanceProgress(string $guideId, string $event, ...): void
    {
        // P12-01: padam one-shot DAHULU — sebelum guard findVisible() yang sedia ada
        // (HelpLauncher.php:41-43) memulangkan awal. Jika tidak, guide yang tidak lagi
        // kelihatan meninggalkan launchPending = true selama-lamanya.
        if ($guideId === $this->requestedGuideId
            && in_array($event, ['started', 'dismissed', 'completed'], true)) {
            $this->launchPending = false;              // C04 — one-shot padam (mutasi SERVER)
        }
        // ... logik sedia ada TIDAK berubah ...
        $this->skipRender();   // telemetri tidak perlu render semula HTML launcher
    }

    public function render()
    {
        // GANTI semua request()->query('panduan')  → $this->requestedGuideId
        // GANTI '/'.request()->path()              → $this->originPath
        // GANTI request()->query('langkah')        → $this->requestedStep
        // $autoStart = $this->launchPending && ...  (bukan lagi filled($requestedId))
    }
}
```

**Nota reka bentuk penting:**
1. `'/'.trim(request()->path(), '/')` menyelesaikan RR-01-11 (`//`) pada punca yang sama —
   `path()` root = `/` → `trim` = `''` → hasil `/`; `helpUrl` menjadi `/bantuan?asal=%2F`.
   (Guard tambahan tidak diperlukan — dibuktikan ujian root #4.)
2. `#[Locked]` pada sifat baharu — klien tidak boleh tamper (konsisten dengan `panel`/`mosqueId`
   sedia ada). **Implikasi (P2):** sifat Locked juga TIDAK boleh dikemas kini dari klien —
   sebarang idea `$wire.set('originPath', …)` adalah mustahil dan DIGUGURKAN dari pelan.
   `requestedGuideId` dinormalisasi/di-authorize semula setiap render melalui `findVisible()`
   (laluan sedia ada — kekal).
3. **Kontrak `skipRender()` (dibetulkan P2):** `skipRender()` menyebabkan respons kitaran update
   itu **tiada HTML/morph langsung** (`vendor/livewire/livewire/src/Component.php:66`). Ini
   selamat kerana `guidanceProgress()` hanya menulis telemetri, TETAPI ia bermakna:
   (a) ujian TIDAK boleh assert "HTML render selepas panggilan itu" — assert payload update
   **tiada** komponen HTML + telemetri DB berubah; kemudian cetuskan kitaran update **lain**
   (atau render eksplisit) dan assert konteks masih betul;
   (b) badge `$taskCount` tidak akan segar pada kitaran telemetri — diterima (badge segar pada
   interaksi lain); jika produk mahu badge sentiasa segar, gugurkan `skipRender` — ia
   **pelengkap pilihan**, teras pembaikan ialah sifat konteks (a).
4. **Navigasi:** mount berlaku pada setiap muat halaman penuh → `originPath` segar.

   **HIPOTESIS SPA v1.3 KINI DITUTUP DENGAN BUKTI KOD (C05 — semakan P11):** SPA **TIDAK
   aktif** pada mana-mana panel. Tiga pemeriksaan bebas:
   - `vendor/filament/filament/src/Panel/Concerns/HasSpaMode.php:9` →
     `protected bool|Closure $hasSpaMode = false;` (opt-in, lalai mati);
   - `grep -n "spa()" app/Providers/Filament/*.php` → **0 padanan** (`AppPanelProvider`,
     `AdminPanelProvider`);
   - `grep -rn "wire:navigate" resources/ app/` → **0 padanan**.

   Maka setiap navigasi antara halaman panel ialah muat penuh → `mount()` berjalan →
   `originPath` sentiasa segar. **Keputusan: laluan fallback `setOrigin()` TIDAK dibina.**
   Membinanya sekarang bermakna menambah permukaan input klien yang perlu dikeraskan untuk
   masalah yang tidak wujud — itu menambah risiko tenancy (keperluan #1), bukan menguranginya.

   **Penjaga (dibina, murah):** ujian §2.4 #11 mengunci ketiga-tiga fakta di atas. Jika sesiapa
   mengaktifkan `->spa()` kelak, suite bertukar merah dan memaksa pelaksana melaksanakan
   spesifikasi beku di bawah **sebelum** SPA boleh dihidupkan. e2e navigasi sidebar sebenar
   (v1.3) **kekal** sebagai lapisan kedua.

   **Spesifikasi beku fallback (JANGAN laksana sekarang — hanya jika penjaga bertukar merah):**
   nilai dahulu penyelesaian tanpa input klien — paksa remount melalui `key`/`wire:key` unik
   per-route pada `<livewire:help-launcher>`, atau `@persist` dimatikan bagi komponen ini.
   HANYA jika itu tidak mencukupi, laksanakan kaedah komponen `setOrigin(string $path)` dengan
   **kesemua** syarat berikut (setiap satu ada ujian; gagal mana-mana → `abort`/abai, JANGAN
   fallback senyap ke laluan yang diminta):
   - **(a) Bentuk:** hanya laluan relatif. Tolak apa-apa yang mengandungi skema (`http:`,
     `//`), hos, `?`, `#`, `\`, atau segmen `..`; normalisasi kepada `'/'.trim($path,'/')`
     dan hadkan panjang.
   - **(b) Kewujudan + keterlihatan:** laluan mesti padan route sebenar yang **pengguna semasa
     boleh lihat** — panel betul, role/permission lulus, dan tenant sepadan `$this->mosqueId`
     (`#[Locked]`). Slug dalam laluan `/app/{slug}/...` mesti sama dengan slug tenant Locked.
   - **(c) Pemilihan guide di server:** klien menghantar laluan sahaja, **tidak pernah** id
     guide; `currentGuide()` + `findVisible()` di server yang memilih.
   - **Ujian wajib:** `/admin` sebagai pengguna tenant; slug tenant kedua; URL mutlak
     (`https://…`); `../` traversal; route tidak wujud; route wujud tetapi tiada kebenaran;
     dan kes normal (sidebar tenant) — 6 negatif + 1 positif.
5. `resumeStep` logik `GuidanceService::resumeStep()` tidak diubah.
6. **Auto-start mesti ONE-SHOT (C04) — perubahan kontrak berbanding v1.3.**
   `render()` semasa mengira `$autoStart = filled(request()->query('panduan'))`, iaitu isyarat
   yang **hilang sendiri** pada kitaran update seterusnya. Sebaik `requestedGuideId` menjadi
   sifat `#[Locked]` yang kekal, isyarat itu menjadi **melekat**: `data-auto-start` kekal `1`
   selama hayat komponen. Kesan: setiap laluan yang memanggil semula `bootRuntime()`
   (`help.js:593-594` — `DOMContentLoaded` + **`livewire:navigated`**) boleh memulakan semula
   tour yang pengguna baru sahaja tutup; penjaga `data-help-booted` yang JS tetapkan
   (`help.js:572-573`) pula boleh dibuang oleh morph Livewire kerana ia tiada dalam HTML server.
   **Reka bentuk (DIBETULKAN P12-01 — `launchPending` mesti `#[Locked]`):** tambah sifat
   **`#[Locked]`** `launchPending` (lalai = `filled($requested)` pada `mount()`), dan matikannya
   di dalam `guidanceProgress()` apabila event `started`, `dismissed` atau `completed` diterima
   untuk guide yang sama (ketiga-tiga event sudah dipancarkan — `help.js:495`, `:545`, `:247`).
   `render()` guna `$autoStart = $this->launchPending && …`. Nota: pada kitaran mematikan itu
   `skipRender()` masih dipanggil — nilai baharu dibaca pada kitaran render berikutnya
   (kontrak §nota 3).

   **Mengapa `#[Locked]` (pembetulan terhadap v1.4).** v1.4 mengisytiharkan sifat ini
   **bukan-Locked** dengan alasan "klien hanya boleh mematikannya". Alasan itu **tidak
   dikuatkuasakan oleh apa-apa kod** — payload Livewire yang menetapkan `launchPending = true`
   sama sahnya dengan yang menetapkan `false`. `#[Locked]` menutup kedua-dua arah **tanpa**
   menghalang reka bentuk one-shot, kerana ia hanya menjaga laluan kemas kini **klien**:
   `BaseLocked::update()` (`vendor/livewire/livewire/src/Features/SupportLockedProperties/BaseLocked.php:10-13`)
   melemparkan `CannotUpdateLockedPropertyException` apabila sifat itu muncul dalam `updates`
   payload; penetapan dalam `mount()` dan dalam `guidanceProgress()` ialah mutasi **server** dan
   tidak melalui laluan itu langsung. Dengan kata lain: tiada apa-apa yang hilang, satu permukaan
   input klien ditutup. Ini juga menjadikan **keempat-empat** sifat baharu F1 konsisten Locked —
   tiada pengecualian yang perlu dijelaskan kepada penyelenggara kelak.

   **Susunan dalam kaedah (penting):** padam one-shot mesti berlaku **sebelum** guard
   `findVisible()` yang sedia ada (`HelpLauncher.php:41-43`) memulangkan awal. Jika tidak,
   sebarang keadaan yang menjadikan guide tidak lagi kelihatan (kebenaran ditarik, guide
   dibuang katalog) meninggalkan `launchPending = true` kekal. Padam-dahulu selamat kerana
   syaratnya membandingkan dengan `requestedGuideId` yang **`#[Locked]`** dan ditetapkan server.

   **Kontrak yang mesti benar:** (a) muat penuh dengan `?panduan=` → auto-start 1 **sekali**;
   (b) selepas `started`/`dismissed`/`completed` + sebarang kitaran update lain → auto-start **0**;
   (c) muat penuh BAHARU dengan URL sama → auto-start 1 semula;
   (d) `$wire.set('launchPending', true)` **dan** `false` → kedua-duanya
   `CannotUpdateLockedPropertyException`.

### 2.3 Fail diubah

| Fail | Perubahan |
|---|---|
| `app/Livewire/HelpLauncher.php` | **4** sifat baharu — `originPath`, `requestedGuideId`, `requestedStep`, `launchPending` — **kesemuanya `#[Locked]`** (C04 + P12-01) + `mount()` + `render()` guna sifat + `guidanceProgress()` padam one-shot **sebelum** guard `findVisible()` + `skipRender()` |
| *(tiada perubahan lain)* | `help.js`, blade, `HelpCatalog` TIDAK disentuh dalam F1 |

*(Jika fallback SPA §2.2 nota 4 terbukti perlu: tambah kaedah `setOrigin()` pada fail sama +
`wire:key` per-route pada 3 lokasi layout. Ini kekal **bersyarat** — jangan bina awal.)*

### 2.4 Ujian

**Baharu — `tests/Feature/Help/HelpLauncherContextTest.php`** (Pest):
1. `render pada halaman tenant menghasilkan guide betul` — GET halaman Peti Masuk sebagai
   admin_masjid → assert HTML mengandungi `data-guide-id="tenant.peti-masuk"` + `asal=%2Fapp%2F...`.
2. `konteks kekal selepas kitaran update Livewire` (kontrak dibetulkan P2) —
   `Livewire::test(HelpLauncher::class, ['panel'=>'app'])` dimuat dari halaman sebenar;
   (i) panggil `guidanceProgress(...)` → assert **respons update tiada HTML** (kontrak
   `skipRender`) DAN telemetri DB bertambah; (ii) cetuskan kitaran update **lain** yang memang
   me-render (atau panggil render eksplisit) → assert `data-guide-id` kekal dan `helpUrl`
   TIDAK mengandungi `livewire`.
3. `halaman admin mengekalkan guide` — superadmin di `/admin/mosques` → `data-guide-id="admin.mosques"`
   kekal selepas update. (Ini ujian yang membuktikan pemulihan 11 halaman superadmin.)
4. `root helpUrl tiada //` — GET `/` → assert `asal=%2F` bukan `%2F%2F`.
5. **`?panduan=` auto-start ialah ONE-SHOT (DITULIS SEMULA — C04; versi v1.3 ujian ini
   mengasertkan tingkah laku yang SALAH).** GET dengan `?panduan=tenant.dashboard` →
   `data-auto-start="1"`; hantar `guidanceProgress(guideId: 'tenant.dashboard', event:
   'started')` → cetuskan kitaran update lain yang me-render → `data-auto-start="0"`
   **dan** `data-guide-id` masih `tenant.dashboard` (konteks kekal, hanya pencetusan padam).
   Ulang untuk `dismissed` dan `completed`. Kes ketiga: muat penuh baharu dengan URL sama →
   `data-auto-start="1"` semula. **Kes keempat (P12-01):** hantar `guidanceProgress` untuk guide
   yang **tidak lagi kelihatan** kepada pengguna (kebenaran ditarik selepas muat) → `launchPending`
   tetap dipadam (membuktikan padam berlaku **sebelum** guard `findVisible()`), dan tiada telemetri
   ditulis untuk guide yang tidak sah itu.
6. Regresi telemetri: `guidanceProgress` masih menulis `help_events` + `guidance_progress`.
7. *(P2; dikemas P12-01)* `sifat Locked menolak tamper` — cubaan set
   `originPath`/`requestedGuideId`/`requestedStep`/`launchPending`/`panel`/`mosqueId` dari klien
   → **`CannotUpdateLockedPropertyException` bagi kesemua enam**. Khusus `launchPending`:
   **kedua-dua** `->set('launchPending', true)` **dan** `->set('launchPending', false)` mesti
   melemparkan — bukan satu arah sahaja. Ujian pelengkap (mempertahankan lapisan kedua):
   walaupun sifat itu **entah bagaimana** bernilai `true`, `?panduan=admin.mosques` sebagai
   pengguna tenant biasa tetap **tidak** memulakan tour, kerana `findVisible()` di server yang
   memutuskan (bertindih dengan ujian #8 dengan sengaja — pertahanan berlapis).
8. *(P2)* `panduan tidak sah / tanpa kebenaran` — `?panduan=admin.mosques` sebagai pengguna
   tenant biasa → `findVisible` null → tiada payload guide, tiada auto-start.
9. *(P2)* `badge taskCount` — keadaan tugasan berubah → kitaran update biasa (bukan telemetri)
   me-refresh badge; dokumen tingkah laku badge-tidak-segar pada kitaran telemetri.
10. *(C04)* `auto-start awam` — pengguna belum log masuk pada `/log-masuk?panduan=public.login`:
    auto-start 1 sekali; selepas `dismissed` → 0; `localStorage diwan-help-seen` sedia ada
    (`help.js:581`) kekal berfungsi sebagai lapisan kedua.
11. *(C05 — penjaga, DIBINA)* `mod SPA kekal mati` — assert `Filament::getPanel('app')
    ->hasSpaMode() === false` **dan** `Filament::getPanel('admin')->hasSpaMode() === false`,
    serta 0 padanan `wire:navigate` dalam `resources/views`. Ujian ini menukar andaian menjadi
    kontrak: ia bertukar merah pada saat sesiapa menghidupkan SPA, dan mesej kegagalannya
    menunjuk ke spesifikasi beku §2.2 nota 4 yang mesti dilaksana dahulu.
12. *(C05 — bersyarat, TIDAK dibina sekarang)* `setOrigin` — 6 ujian negatif + 1 positif seperti
    §2.2 nota 4. Ditulis **hanya jika** ujian #11 bertukar merah. Sebab ia tidak wujud
    (SPA terbukti mati) direkod dalam bukti fasa F1.

**e2e — tambah pada `e2e/guidance.spec.js`:** selepas log masuk, buka Peti Masuk → lakukan satu
interaksi Livewire (contoh: taip dalam carian jadual) → assert
`document.querySelector('[data-diwan-help-runtime]').dataset.guideId` masih nilai asal; klik satu
item sidebar (**navigasi penuh sebenar — mengesahkan tingkah laku mount semula §2.2 nota 4**) →
assert dataset berubah ke guide halaman baharu; ulang pada panel `/admin`.

### 2.5 Verifikasi & kriteria siap

```
php vendor/bin/pint --dirty
php artisan test                        # suite penuh hijau (409+6 baharu)
npx playwright test e2e/guidance.spec.js
```
- [ ] 10 ujian baharu lulus (+1 bersyarat #11); suite penuh hijau
- [ ] e2e: dataset guide kekal selepas interaksi + betul selepas navigasi
- [ ] **One-shot (C04):** selepas `started`, `data-auto-start` = 0 pada render berikutnya —
      dibuktikan ujian #5 DAN e2e (tutup tour → navigasi SPA balik ke halaman sama →
      tour TIDAK bermula semula)
- [ ] Manual produksi (selepas deploy): buka `/admin/mosques` → klik mana-mana penapis jadual →
      butang Pembantu Diwan masih menawarkan panduan halaman (bukan hilang)
- [ ] `bukti/`: ulang skrip `helpRuntime` crawl P1 pada 5 halaman sampel → 5/5 kekal konteks
- [ ] **Matriks keselamatan §0.6 (S1–S6) hijau** — khususnya S3 (deep-link `?panduan=` merentas
      panel/role) kerana F1 menukar cara `?panduan=` dibaca

### 2.6 Risiko & rollback

| Risiko | Mitigasi |
|---|---|
| `skipRender()` menyembunyikan kemas kini badge `taskCount` yang sepatutnya | Ujian #9; jika badge perlu segar, buang `skipRender()` — teras (a) tetap menyelesaikan isu |
| Kes tepi `wire:navigate` (SPA) meninggalkan `originPath` basi | Ujian e2e navigasi WAJIB (hipotesis §2.2 nota 4). Jika terbukti berlaku: cuba `key`/remount dahulu; barulah kaedah server-validated (`setOrigin()`) dengan 3 syarat (a)-(c), **BUKAN** `$wire.set()` — sifat `#[Locked]` menolak set klien |
| **Auto-start melekat** — sifat Locked menukar isyarat sekali-guna menjadi kekal, tour bermula semula selepas ditutup (C04) | Sifat `launchPending` one-shot (**juga `#[Locked]`** — P12-01) + ujian #5 (**4** kontrak) + e2e. Isyarat amaran awal: `data-auto-start` masih `1` selepas event `started` |
| **Klien memaksa auto-start** — payload Livewire menetapkan `launchPending = true` untuk memulakan semula tour yang telah ditutup (P12-01) | `#[Locked]` menolak set klien ke **kedua-dua** arah (ujian #7); `findVisible()` server kekal sebagai lapisan kedua |
| Komponen di-cache oleh Filament page cache? | Tidak — panel tidak menggunakan SSR cache; render setiap permintaan |

**Rollback:** revert 1 commit; tiada migrasi, tiada perubahan data.

---

## 3. FASA F2 — Runtime tour JS: satu predikat, label BM, modal & fokus

**Menutup:** RR-01-07/RR-03-03/RR-10-06 + RR-01-04 + RR-08-03 + RR-03-02 · **Keutamaan:** #6, #7

> ⚠️ **Sempadan F2:** JANGAN sentuh mekanisme sync (`watchForNextStep`, `watchForActionCompletion`,
> poll 120ms) — ia terbukti berfungsi (1045ms). Perubahan F2 terhad kepada (a) keputusan
> label/kelakuan, (b) label fallback, (c) tingkah laku bila modal terbuka, (d) fokus.

### 3.1 (F2a) Predikat bersatu label ↔ kelakuan — RR-01-07

**Punca:** `nextButtonLabel()` (`help.js:323-333`) memutuskan label dengan
`!resolveStepElement(next, false)` (TANPA fallback generik) tetapi `onNextClick` (`help.js:525`)
memutuskan kelakuan dengan `resolveStepElement(next, GENERIC_TARGETS.has(next.target))`
(DENGAN fallback). Untuk 94% langkah generik: label kata **"Buat pada skrin"**, klik hanya
`moveNext()` — inilah "dah tekan ke belum?" pemilik.

**Reka bentuk:** satu sumber keputusan digunakan oleh KEDUA-DUA tempat:

```js
// KONSEP
function stepAdvancePlan(guideSteps, index) {
    const step = guideSteps[index];
    const next = guideSteps[index + 1];
    // Peraturan (ditulis semula supaya eksplisit):
    // 1. Langkah AKHIR:
    //    - finalAction (wait_for_user + sasaran spesifik) => {label:'Buat pada skrin', kind:'final-action'}
    //    - selainnya                                      => {label:'Selesai',        kind:'complete'}
    // 2. next.route berbeza halaman                        => {label:'Seterusnya',    kind:'navigate'}
    //    (jika current.wait_for_user + sasaran spesifik   => {label:'Buat pada skrin', kind:'action-then-navigate'})
    // 3. next kelihatan SEKARANG (dgn fallback generik utk sasaran generik)
    //                                                     => {label:'Seterusnya',    kind:'advance'}
    // 4. next TIDAK kelihatan & step ialah langkah tindakan (wait_for_user / sasaran berbeza)
    //                                                     => {label:'Buat pada skrin', kind:'wait-for-action'}
    // 5. next TIDAK kelihatan & bukan langkah tindakan     => {label:'Seterusnya',    kind:'advance-blocked'}
    //                                                        (klik → mesej ralat target_missing sedia ada)
    return {...};
}
```

- `nextButtonLabel(...)` → `stepAdvancePlan(...).label`
- `onNextClick` → `switch (plan.kind)` — setiap cabang mengekalkan tingkah laku sedia ada yang
  betul (`minimiseForAction`, `watchForActionCompletion`, `completeGuide`, `moveNext`,
  `setTourStatus`) tetapi **cabang dipilih oleh plan yang sama dengan label**.
- Label **"Saya sudah buat"** disingkirkan ATAU dikekalkan untuk `kind:'action-then-navigate'`
  sahaja — keputusan kecil semasa pelaksanaan; kriteria: setiap label mesti 1:1 dengan satu
  `kind`, tiada label yang berkongsi dua kelakuan. Dokumen jadual label↔kind dalam komen kod.
- `onHighlighted` (baris 484-495) juga memanggil `nextButtonLabel` — tukar ke plan yang sama.

**Kesan pada 20× "Buat pada skrin" generik (RR-10-06):** dengan peraturan #3, langkah generik
yang berikutnya juga generik → sentiasa "Seterusnya". "Buat pada skrin" HANYA muncul apabila
benar-benar menunggu tindakan (kind `wait-for-action`/`final-action`/`action-then-navigate`).

### 3.2 (F2b) Label BM pada popover fallback — RR-01-04

`showUnavailableGuide()` (`help.js:392-422`) — tambah pada konfigurasi `driver({...})`:

```js
prevBtnText: 'Kembali',
nextBtnText: 'Seterusnya',
doneBtnText: 'Tutup',            // sedia ada
progressText: '{{current}} daripada {{total}}',
showProgress: false,             // 1 langkah — "1 daripada 1" tiada makna; sembunyikan terus
```

### 3.3 (F2c) Auto-minimize bila modal sasaran terbuka — RR-08-03

**Masalah:** pada mobile, selepas guide membuka modal, overlay+popover Driver.js berada di atas
modal; butang modal tidak boleh ditekan sehingga pengguna menekan "Buat pada skrin".

**Reka bentuk (diperhalusi P2 — overlap-aware, bukan timer buta):** dalam `onHighlighted`,
untuk langkah tindakan (`plan.kind` `wait-for-action`/`action-*`/`final-action`) yang sasarannya
berada dalam `.fi-modal-window` kelihatan:
1. Selepas satu `requestAnimationFrame` (biar layout settle), ukur
   `popover.getBoundingClientRect()` lawan `getBoundingClientRect()` sasaran tindakan/modal —
   **hanya jika bertindih** (atau popover menutup >N% modal pada viewport semasa) →
   auto-minimize.
2. Jika mahu beri masa baca sebelum minimize: guna **1.5–2 s yang boleh dibatalkan** (bukan
   600ms — P2 betul ia terlalu singkat untuk membaca dan cukup panjang untuk race) + butang
   manual "Buat pada skrin" kekal berfungsi serta-merta.
3. **Semua timer/observer auto-minimize DIBATALKAN** pada `onHighlighted` berikutnya,
   `minimiseForAction` manual, `onDestroy*` — ikut corak `clear*()` sedia ada (tambah
   `clearAutoMinimise()`).
4. Ujian e2e ukur dengan keadaan (popover tersembunyi & pil muncul), **bukan**
   `waitForTimeout(600)` — guna `expect.poll`/wait-for-selector.

- **Jangan** auto-minimize untuk langkah penerangan (kind `advance`) — popover perlu kekal untuk
  dibaca; Driver.js membenarkan interaksi elemen disorot (`disableActiveInteraction: false` sudah
  ditetapkan), isu hanya bila popover *bertindih* kawalan pada skrin kecil.
- Alternatif dinilai & ditolak: menjadikan popover `pointer-events: none` (memusnahkan butang
  popover); mengalih popover ke bawah modal dengan CSS (`z-index` Driver.js overlay ialah satu
  sistem — mengubahnya berisiko regresi desktop); timer tetap 600ms tanpa ukuran pertindihan
  (P2 — race + tak cukup masa baca).
- Guard sedia ada `guardAutomaticGuideFromDialogs` (tutup tour auto bila modal TIDAK berkaitan
  muncul) TIDAK diubah — ujian e2e #5 mesti membuktikan ia tidak menutup guide yang langkahnya
  memang menyasar modal (ia hanya menembak bila `current.target` generik; langkah modal
  menyasar spesifik).

### 3.4 (F2d) Pengurusan fokus + semantik dialog yang betul — RR-03-02

**FAKTA VENDOR YANG MENGATASI REKA BENTUK v1.1–v1.3 (C03 — disahkan bebas P11):**
Driver.js **sudah** memerangkap Tab sendiri. `node_modules/driver.js/dist/driver.js.mjs:202-215`
mentakrifkan pengendali `Pe(e)` yang, apabila `isInitialized`, memanggil `e.preventDefault()`
dan mengitar fokus dalam senarai boleh-fokus gabungan **popover wrapper + `__activeElement`
(elemen disorot)**; ia didaftarkan tanpa syarat pada baris **236**
(`window.addEventListener("keydown", Pe, !1)`). Ia **tidak** dipengaruhi
`disableActiveInteraction: false`.

Akibatnya:
1. Dakwaan v1.2/v1.3 bahawa "**Tab bebas keluar**" pada popover utama adalah **SALAH** — Tab
   tidak pernah keluar selagi tour aktif. Ayat itu digugurkan.
2. Cadangan menulis "perangkap fokus penuh (~20 baris)" untuk popover fallback juga
   **digugurkan** — ia akan menjadi trap kedua di atas trap vendor (dua pengendali `keydown`
   yang sama-sama `preventDefault` = fokus melompat tidak menentu). **Tiada trap custom
   di mana-mana.**

**Reka bentuk v1.4 (yang tinggal ialah perkara yang vendor TIDAK buat):**
- **Popover utama:** `role="dialog"` + labelledby/describedby sedia ada (audit sahkan lengkap).
  (i) **fokus awal** — pindahkan fokus ke popover apabila ia muncul (vendor tidak melakukannya);
  (ii) **kitaran Tab = milik vendor** — didokumenkan, tidak diubah, tidak ditambah;
  (iii) **fokus kembali kepada pencetus** pada `onDestroyed` (simpan rujukan semasa `startGuide`);
  (iv) **TIADA `aria-modal`** pada popover utama — halaman masih boleh diinteraksi melalui
  `focusActionTarget`/minimize, jadi `aria-modal="true"` akan menipu pembaca skrin.
- **Popover fallback (`showUnavailableGuide`):** ia benar-benar satu langkah tanpa interaksi
  halaman → **`aria-modal="true"` sahaja** (+ fokus awal + fokus kembali). Perangkapan Tab
  datang percuma daripada vendor; ESC sedia ada (`allowKeyboardControl`).
- Pembersihan: listener fokus-awal/fokus-kembali dibersihkan pada destroy — ikut corak `clear*`
  sedia ada (`clearFocusManagement()`).
- **Had yang diisytihar (C03):** membenarkan pengguna papan kekunci Tab keluar ke seluruh
  halaman semasa tour aktif memerlukan perubahan pada Driver.js (patch/fork/PR upstream) —
  **di luar skop pelan ini**, direkod sebagai had diketahui dalam `SUSULAN-PEMBAIKAN.md` F8.
- Penutupan RR-03-02: isu asal ("fokus terlepas keluar") ditutup dengan (i)+(iii) — fokus tidak
  lagi *tersesat* pada permulaan/penamatan tour; kitaran semasa tour aktif memang dikawal vendor.

### 3.5 Fail diubah (F2 keseluruhan)

| Fail | Perubahan |
|---|---|
| `resources/js/help/step-advance-plan.js` | **BAHARU (C11)** — modul tulen tanpa import CSS/DOM: `stepAdvancePlan()` + jadual label↔`kind`. Boleh diimport terus oleh Playwright (Node ESM; `package.json` sudah `"type":"module"`) tanpa bundler |
| `resources/js/help.js` | import `stepAdvancePlan` dari modul baharu; `nextButtonLabel`/`onNextClick`/`onHighlighted` guna plan yang sama; label BM fallback; auto-minimize overlap-aware; pengurusan fokus §3.4 (fokus awal + fokus-kembali; **`aria-modal` pada fallback `showUnavailableGuide` SAHAJA; TIADA trap custom di mana-mana** — kitaran Tab milik vendor); `clearFocusManagement()`. **TIADA hook ujian global** (C11) |
| `resources/css/help.css` | (jika perlu) gaya pil menunggu di atas modal `z-index` — semak semasa pelaksanaan |

### 3.6 Ujian

- **Jadual keputusan `stepAdvancePlan` diuji sebagai fungsi tulen — kontrak DITULIS SEMULA
  (C11; menggantikan kontrak `__DIWAN_E2E__` v1.2/v1.3):**
  Punca masalah v1.2 kekal betul — `help.js` ialah ES module TANPA export dan mengimport
  `driver.js/dist/driver.css` + `../css/help.css` (baris 1-3), jadi Node ESM tidak boleh
  mengimportnya terus. Tetapi penyelesaian hook global **meninggalkan kod ujian dalam bundle
  produksi** dan mendedahkan dalaman kepada sesiapa yang menetapkan flag sebelum halaman
  dimuat. **Kontrak muktamad v1.4:**
  1. `stepAdvancePlan()` (dan jadual `kind`↔label) diekstrak ke
     **`resources/js/help/step-advance-plan.js`** — fungsi tulen, **tiada import CSS, tiada
     sentuhan DOM**, satu `export`. `help.js` mengimportnya.
  2. Spec Playwright mengimport modul itu **terus sebagai Node ESM**
     (`import { stepAdvancePlan } from '../resources/js/help/step-advance-plan.js'`) — sah
     kerana `package.json` sudah `"type": "module"` dan modul itu tiada aset. **Tiada bundler
     baharu, tiada dependensi baharu.**
  3. **Tiada apa-apa ditambah pada `globalThis`.** Ujian regresi: `grep` bundle terbina
     (`public/build/assets/help-*.js`) untuk `__diwanHelpTest`/`__DIWAN_E2E__` → **0 padanan**.
  4. Ujian jadual: satu kes per `kind` (≥7: complete, final-action, navigate,
     action-then-navigate, advance, wait-for-action, advance-blocked) sebagai ujian unit Node
     terus ke atas fungsi tulen (pantas, tiada pelayar); e2e aliran penuh kekal 2-3 laluan
     utama sahaja.
  5. **`resolveStepElement` TIDAK boleh menjadi modul tulen** (ia menyentuh DOM +
     `decorateTargets()`). Untuk F6 §7.3, gantikan panggilan langsung dengan **assertion
     black-box**: jalankan tour, kemudian baca `document.querySelector('.driver-active-element')`
     dan `dataset.helpTarget`/`tagName` — ini menguji hasil sebenar yang pengguna lihat, dan
     lebih kuat daripada memanggil resolver secara terasing.
- **e2e `e2e/guidance.spec.js` tambahan:**
  1. *Label=kelakuan:* jalankan tour `tenant.dashboard` (semua langkah generik) → assert TIADA
     butang "Buat pada skrin" muncul; semua "Seterusnya"/"Selesai"; klik setiap satu maju.
  2. *Langkah tindakan sebenar:* tour `screen.klasifikasi-peti-masuk` → langkah "Buat pada skrin"
     masih berfungsi hujung-ke-hujung (regresi sync 1045ms — WAJIB kekal lulus).
  3. *Fallback BM + semantik dialog:* paksa guide dengan sasaran tak wujud → popover fallback
     TIADA teks `Previous`/`of` (regex EN-leak) DAN mempunyai `aria-modal="true"`;
     popover utama TIDAK mempunyai `aria-modal`.
  4. *Fokus (DITULIS SEMULA — C03):* buka tour → assert **fokus awal** berpindah ke dalam
     `.driver-popover`; Tab **dan** Shift+Tab berulang (≥6 tekan setiap arah) → fokus sentiasa
     berada dalam `.driver-popover` **ATAU** `.driver-active-element` (kitaran vendor —
     **ini yang betul, bukan kegagalan**); "Buat pada skrin" → `focusActionTarget` memfokus
     kawalan halaman; ESC → tour tutup (regresi ESC) dan **fokus kembali ke pencetus**
     (launcher); minimize → restore → fokus masih terurus; **dua tour berturutan** tanpa muat
     semula → tiada listener/fokus bocor.
  5. *Mobile modal:* viewport iPhone 13 → tour klasifikasi buka modal → assert selepas
     auto-minimize (tunggu keadaan, bukan masa), `elementFromPoint` pada butang "Seterus(nya)"
     modal ialah butang itu sendiri (tiada overlay menghalang), pil "Panduan menunggu"
     kelihatan, DAN `guardAutomaticGuideFromDialogs` tidak menutup tour (tour masih aktif).
  6. *(P2) Timer bersih:* mulakan langkah tindakan modal → tekan ESC serta-merta semasa tempoh
     baca → tiada minimize/ralat selepas itu (timer dibatalkan); dua guide berturutan tanpa
     muat semula → tiada state bocor.
- **Pest:** tiada logik PHP berubah — suite penuh sebagai penjaga sahaja.

### 3.7 Kriteria siap F2

- [ ] 6 ujian e2e baharu + ≥7 ujian unit `stepAdvancePlan` lulus; `npm run build` bersih;
      suite Pest hijau; **gate CI hijau** — check **`PostgreSQL, Redis, Meili, OCR and tests`**
      (canary `@session-canary` + `--project=ci-guidance` + `--project=ci-domain` sebagai step),
      **dan** required check **`guidance-e2e-gate`** (F0(iv), nama exact §1 F0(iv)(f))
- [ ] Semakan manual matriks label: untuk 6 guide sampel (2 generik penuh, 2 campuran, 2 bersasar),
      rakam label setiap langkah + kelakuan klik → 100% padan
- [ ] EN-leak scan runtime tour = 0
- [ ] **Bundle bersih (C11) — arahan DIBETULKAN SEPENUHNYA v1.7 (P14-07 → P16-06):**

      ```bash
      # SALAH (v1.5): grep -c "__diwanHelpTest\|__DIWAN_E2E__" public/build/assets/help-*.js = 0
      #   `grep -c` ke atas BEBERAPA fail mencetak "namafail:0" setiap satu — gate menjadi
      #   perbandingan teks output, bukan status.
      # MASIH SALAH (v1.6): `! rg …` dengan guard fail sahaja.
      #   `!` menukar SEMUA rc bukan-sifar kepada "lulus", termasuk rc 2 (regex rosak,
      #   permission, I/O) — bukan hanya laluan hilang yang guard itu tutup.
      # BETUL (v1.7): senarai fail diassert > 0, DAN rc 1 dibezakan daripada rc >= 2.
      set -u
      mapfile -t files < <(ls public/build/assets/help-*.js 2>/dev/null)
      [ "${#files[@]}" -gt 0 ] || { echo "FAIL: tiada bundle help-*.js — jalankan npm run build"; exit 1; }

      if rg -n '__diwanHelpTest|__DIWAN_E2E__' "${files[@]}"; then
        echo "FAIL: hook ujian masih dalam bundle produksi"; exit 1
      else
        rc=$?
        [ "$rc" -eq 1 ] || { echo "FAIL: rg ralat (rc=$rc), bukan 'tiada padanan'"; exit "$rc"; }
      fi
      echo "OK: ${#files[@]} bundle disemak, 0 padanan"
      ```

      **Bukti tingkah laku rc (dijalankan P17, `ripgrep 15.2.0`):** tiada padanan → **rc 1** ·
      laluan tiada → **rc 2** · regex rosak (`rg '('`) → **rc 2** · `! rg … laluan/tiada` →
      cawangan "lulus" diambil. Maka `!` **dilarang** dalam mana-mana gate pelan ini, dan bilangan
      fail input **mesti** diassert sebelum carian (gate yang berjalan ke atas sifar fail bukan
      gate).
      *(Nota P12-06 kekal: `help-*.js` di sini ialah glob **fail sistem** yang shell kembangkan —
      sah. Larangan wildcard terpakai kepada **URL HTTP** sahaja, §10 langkah 5A.)*
- [ ] Matriks keselamatan §0.6 (S1–S6) hijau

**Risiko:** refactor `onNextClick` tersilap cabang → tour tersekat. Mitigasi: jadual
keputusan ditulis dahulu sebagai ujian e2e (test-first), setiap `kind` ada liputan; rollback = revert commit.

---

## 4. FASA F3 — Bahasa: `lang/ms/` + e-mel + vendor Filament + label

**Menutup:** RR-01-03/RR-03-01/RR-02-05 + RR-05-02 + RR-05-01/RR-08-04 + RR-01-05 · **Keutamaan:** #2

### 4.1 Inventori tepat permukaan Inggeris (dari audit + semakan kod)

| Permukaan | Bukti | Mekanisme pembaikan |
|---|---|---|
| Mesej validasi (`The nama field is required.`) | Tiada `lang/ms/validation.php` | 4.2 |
| `auth.failed`, throttle | Tiada `lang/ms/auth.php` | 4.2 |
| Pagination `Next »` | Tiada `lang/ms/pagination.php` | 4.2 |
| Reset kata laluan | Tiada `lang/ms/passwords.php` | 4.2 |
| Kerangka e-mel: `Hello!`, `Whoops!`, `Regards,`, subcopy, `All rights reserved.` — audit menguji 9 e-mel; **baseline sebenar = 18 kelas ber-`toMail()`** (C19) | Templat vendor guna `@lang()`/`__()` — disahkan `email.blade.php:7,9,42,49` + `message.blade.php:24` | 4.3 `lang/ms.json` |
| Wizard Filament `Seterus`/`Sebelum` | `vendor/filament/schemas/resources/lang/ms/components.php` | 4.4 override |
| 3 arahan katalog menyalin "Seterus" | `guides.json:2867,3774,5796` | 4.5 |
| **5** label `Edit` (bukan 3 — C10) | `UsersTable.php:81`, `MosquesTable.php:50`, `ViewMosque.php:16`, **`TetapanPlatform.php:43`**, **`TetapanMasjid.php:58`** | 4.6 |

### 4.2 `php artisan lang:publish` → terjemah 4 fail teras

- Terbit `lang/en/{validation,auth,passwords,pagination}.php` → cipta salinan `lang/ms/` dan
  terjemah **setiap kunci** (validation ±160 kunci — terjemah penuh, bukan pilihan;
  guna istilah konsisten: "wajib diisi", "mestilah", "tidak sah").
- `lang/ms/validation.php` seksyen `attributes`: petakan nama medan teknikal yang muncul dalam
  borang utama supaya mesej kemas — minimum medan wizard klasifikasi
  (`registry_file_id` → "Failkan Ke", `record_type` → "Jenis Rekod", `direction` → "Arah",
  `title` → "Tajuk", `access_level` → "Tahap Akses", `login` → "e-mel atau telefon", dll.).
  **Kaedah inventori:** kumpul daripada label borang Filament sedia ada (grep `->label(`) supaya
  padanan 1:1 dengan UI — senarai penuh dibina semasa pelaksanaan dan disemak dalam PR.
- **`APP_FALLBACK_LOCALE` KEKAL `en`** — ⚠️ *pindaan sedar terhadap cadangan asal FINAL-RUMUSAN
  (§4 K2 mencadangkan `ms`)*: fallback hanya digunakan bila kunci TIADA dalam locale semasa;
  menetapkannya ke `ms` menjadikan kunci yang tercicir dipaparkan sebagai **kunci mentah**
  (`validation.custom.x`) dan tidak memberi apa-apa untuk kunci JSON (literal EN ialah kunci itu
  sendiri). Perlindungan sebenar = **ujian kesempurnaan** (4.7 #1) yang membandingkan senarai
  kunci `lang/en/*` dengan `lang/ms/*`. → Codex diminta sahkan pendirian ini.

### 4.3 `lang/ms.json` — kerangka e-mel & rentetan `__()` framework

Kunci mesti disalin **verbatim** daripada templat vendor (termasuk baris baharu dalam subcopy):

```json
{
  "Hello!": "Salam sejahtera,",
  "Whoops!": "Harap maaf!",
  "Regards,": "Sekian,",
  "All rights reserved.": "Hak cipta terpelihara.",
  "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\ninto your web browser:": "Jika anda menghadapi masalah menekan butang \":actionText\", salin dan tampal URL di bawah ke pelayar web anda:"
}
```

- **Verifikasi kunci verbatim (2 lapisan, P2):** (i) ujian lookup terus —
  `app()->setLocale('ms'); __('Hello!') === 'Salam sejahtera,'` untuk kesemua 5 kunci (menangkap
  kunci tersilap salin serta-merta dengan mesej jelas); (ii) ujian render `MailMessage` sebenar
  (4.7 #2) sebagai penjaga hujung-ke-hujung. Kunci-kunci di atas telah disahkan verbatim oleh
  kedua-dua ejen terhadap templat vendor (`email.blade.php:7,9,42,49`, `message.blade.php:24`).
- Audit tambahan semasa pelaksanaan: `grep -rn "__(\'" app/ resources/views/` untuk sebarang
  rentetan EN lain yang melalui `__()` dan perlu masuk `ms.json`.
- **Notifikasi — baseline = 18 kelas (C19; strategi fixture P2 kekal):**
  kandungan `line()` semua notifikasi sudah BM; kerangka datang dari templat — kelas TIDAK
  perlu diubah. **Denominator dibekukan:** `grep -rln "public function toMail" app/Notifications/`
  = **18** kelas (`AddonExpiring`, `ApprovalDecided`, `ApprovalRequested`, `AutoDisposalDone`,
  `ConnectionAlert`, `DriveBackupAlert`, `ExportReady`, `GatewayDown`, `GuidanceDigest`,
  `InboxNewItem`, `MailIntakeRejected`, `MinitCompleted`, `MinitReminder`, `MinitRouted`,
  `NewStorageOrder`, `QuotaThreshold`, `RetentionNotice`, `Test`) — tiada `toMail()` di luar
  folder itu (disahkan). Audit asal hanya menghantar 9; **liputan ujian mesti 18/18**.
  Ujian 4.7 #2 menggunakan **data-provider eksplisit**: satu entri per kelas dengan
  factory/fixture minimum yang betul untuk constructor-nya (refleksi automatik ke atas
  constructor pelbagai bentuk adalah rapuh). **Penjaga kesempurnaan berasingan:** ujian yang
  membandingkan senarai kelas `app/Notifications/*.php` ber-`toMail()` dengan senarai
  data-provider — kelas baharu tanpa entri ujian → merah dengan mesej "daftar fixture untuk
  kelas X". (Keseimbangan: kestabilan fixture + jaminan liputan.)

### 4.4 Override vendor Filament — wizard `Seterus`/`Sebelum`

- Namespace terjemahan disahkan: `SchemasServiceProvider` → `->name('filament-schemas')` +
  `hasTranslations()` → laluan override = **`lang/vendor/filament-schemas/ms/components.php`**.
- Kandungan: salin struktur vendor, tukar `next_step.label` → `'Seterusnya'`,
  `previous_step.label` → `'Sebelumnya'`.
- Semakan konsistensi tambahan (dari RR-05-01): `grep -rn "Seterus\b" vendor/filament/*/resources/lang/ms/`
  untuk mengesan fail vendor ms lain yang guna ejaan pendek — override setiap satu yang
  benar-benar dipakai UI projek (jangan override membuta; senarai akhir dicatat dalam PR).

### 4.5 Katalog: 3 arahan "Seterus" → "Seterusnya"

- `resources/help/guides.json` baris 2867, 3774, 5796: "Tekan Seterus" → "Tekan Seterusnya" —
  selaras dengan label yang F4.4 betulkan. ⚠️ Perubahan mesti SELEPAS/SERENTAK 4.4 dalam commit
  sama (jika tidak, arahan menyebut butang yang tidak wujud).
- Bump `catalog_version` → `2026.MM.DD.1`. **JANGAN bump `version` per-guide** untuk perubahan
  ejaan — mengelakkan auto-tour semula massal (lihat §5.6 D6 tentang polisi bump).
- Selepas deploy: `php artisan diwan:sync-help-index --delete` (Meilisearch).

### 4.6 **Lima** label `Edit` → BM (dikemas C10)

Inventori disahkan semula P11 dengan
`grep -rn "label('Edit\|label(\"Edit\|>Edit<\|'Edit'" app/ resources/views/` → tepat 5 padanan:

| Fail | Sekarang | Jadi |
|---|---|---|
| `app/Filament/Admin/Resources/Users/Tables/UsersTable.php:81` | `->label('Edit')` | `->label('Sunting')` |
| `app/Filament/Admin/Resources/Mosques/Tables/MosquesTable.php:50` | `->label('Edit')` | `->label('Sunting')` |
| `app/Filament/Admin/Resources/Mosques/Pages/ViewMosque.php:16` | `->label('Edit Tenant')` | `->label('Sunting Tenant')` |
| **`app/Filament/Admin/Pages/TetapanPlatform.php:43`** | `->label('Edit Tetapan')` | `->label('Sunting Tetapan')` |
| **`app/Filament/App/Pages/TetapanMasjid.php:58`** | `->label('Edit Tetapan')` | `->label('Sunting Tetapan')` |

Dua yang terakhir tertinggal dalam v1.0–v1.3 kerana ia halaman **Tetapan** (bukan Resource
table) — satu di panel `admin`, satu di panel `app` tenant. Ini bermakna crawl EN-leak F3/F8
mesti merangkumi `/admin/tetapan-platform` dan `/app/{slug}/tetapan-masjid`, bukan hanya
halaman senarai.

(Istilah "Sunting" dipilih kerana konsisten dengan vendor Filament ms `edit.label`; sahkan semasa
pelaksanaan — jika vendor guna "Kemas kini", ikut vendor.)

### 4.7 Ujian

**Baharu — `tests/Feature/LocalisationTest.php`:**
(Setiap ujian menetapkan locale `ms` secara **eksplisit** dan memulihkannya selepas — P2;
jangan bergantung pada susunan ujian.)
1. *Kesempurnaan (skop terkawal, P2):* untuk **4 fail yang kita terbitkan sahaja**
   (`validation`, `auth`, `passwords`, `pagination`), setiap kunci (rekursif) dalam `lang/en/X.php`
   wujud dalam `lang/ms/X.php` — gagal dengan senarai kunci hilang. TIDAK membandingkan
   keseluruhan vendor en (mengelakkan kegagalan CI palsu pada setiap naik taraf framework).
2. *E-mel BM penuh — 18/18 (C19):* data-provider eksplisit **kesemua 18** kelas notifikasi
   ber-`toMail()` (§4.3) → render dalam locale `ms` → untuk setiap satu assert **lima
   permukaan**, bukan hanya regex EN: (a) **subjek** BM; (b) **greeting** (salam) BM atau
   tiada; (c) **teks butang aksi** BM; (d) **footer/subcopy** BM; (e) TIADA `Hello`, `Whoops`,
   `Regards,`, `All rights reserved`, `If you're having trouble`. + Penjaga kesempurnaan
   data-provider (§4.3). + *(P2)* satu kes notifikasi ber-`greeting()`/level `error` jika wujud.
   + *(C19)* **locale reset**: selepas render dalam `ms`, locale aplikasi kembali ke nilai asal
   (notifikasi beratur tidak boleh mencemar permintaan lain), dan satu kes **fallback** —
   kunci JSON sengaja dipadam → mesej kekal boleh dibaca (EN), bukan kunci mentah.
3. *Lookup terus (P2):* `__('Hello!')` dsb. — 5 kunci JSON padan terjemahan tepat.
4. *Validasi BM:* POST borang pendaftaran kosong → mesej mengandungi "wajib" dan TIDAK mengandungi
   `The ` + ` field is required`; *(P2)* satu kes placeholder (`:min`/`:max`) dan satu kes
   `trans_choice` (pagination) untuk membuktikan parameter berfungsi.
5. *Wizard label:* render halaman dengan wizard (langkah klasifikasi) → HTML mengandungi
   `Seterusnya` dan TIDAK `>Seterus<` — **selepas** kitaran update Livewire juga (P2: label
   datang dari render vendor; sahkan kekal selepas morph).
6. *Katalog:* `HelpCatalogQualityTest` (baharu, dikongsi F5): regex `/\bSeterus\b(?!nya)/` = 0
   padanan dalam `guides.json`.
7. *Label Edit — kelima-lima (C10):* render `/admin/users`, `/admin/mosques`,
   `/admin/mosques/{id}` (ViewMosque), **`/admin/tetapan-platform`** sebagai superadmin dan
   **`/app/{slug}/tetapan-masjid`** sebagai `admin_masjid` → tiada `>Edit<` / `Edit Tetapan` /
   `Edit Tenant` pada mana-mana daripada lima halaman.
8. *(P2) Semakan ujian sedia ada:* `grep -rn "field is required\|Hello\|Regards" tests/` —
   mana-mana assertion yang bergantung pada rentetan EN dikemas dalam commit sama, dicatat
   sebagai perubahan spec bahasa (peraturan #9).

**e2e:** `explore.spec.js` sedia ada + satu assertion EN-leak per halaman (senarai regex:
`\bEdit\b`, `\bPrevious\b`, `\bNext »`, `The .* field`) — guna semula corak `enLeak` audit P1.

### 4.8 Kriteria siap F3

- [ ] 8 ujian baharu + suite penuh hijau; Pint bersih; **gate CI hijau** — check
      **`PostgreSQL, Redis, Meili, OCR and tests`** (canary + `ci-guidance` + `ci-domain`)
      + required check **`guidance-e2e-gate`** (F0(iv)(f))
- [ ] **18/18 kelas notifikasi** dilindungi data-provider (penjaga kesempurnaan merah jika ada
      kelas baharu tanpa fixture)
- [ ] Hantar e-mel ujian sebenar di staging/produksi (`diwan:staging-check --mail-to=` laluan
      sedia ada) → baca kandungan: BM penuh dari salam hingga footer
- [ ] Skrin pendaftaran produksi: validasi BM
- [ ] Wizard klasifikasi produksi: butang "Seterusnya"/"Sebelumnya"
- [ ] **5/5 label Edit** hilang dari lima halaman §4.6 (termasuk kedua-dua halaman Tetapan)
- [ ] Matriks keselamatan §0.6 (S1–S6) hijau

**Risiko:** (1) Terjemahan validasi mengubah mesej yang mungkin di-assert ujian sedia ada —
semak `grep -rn "field is required" tests/` dan kemas ujian itu **dalam commit sama** (peraturan
#9: dicatat sebagai perubahan spec bahasa). (2) Kunci JSON tak padan verbatim → kekal EN —
ditangkap ujian #2. (3) Override vendor tertinggal selepas naik taraf Filament — ujian #4
menjaga. **Rollback:** revert commit; tiada migrasi.

---

## 5. FASA F4 — Lalai retensi selamat (auto-padam → pilihan sedar)

**Menutup:** RR-08-01 + RR-09-01 · **Keutamaan:** #3 · **⚠️ BERGANTUNG keputusan pemilik
D1–D4 + D10 (§11)** · **Housekeeping (A2/A3) DIPINDAH ke F10 (C25)**

### 5.1 Tiga lapisan lalai semasa (disahkan)

| Lapisan | Nilai semasa | Fail |
|---|---|---|
| L1 Borang peraturan baharu | `->default('auto_padam')` — medan yang sama memaparkan helperText AMARAN tentang auto_padam | `RetentionRuleResource.php:57-61` |
| L2 Suis per-masjid | `auto_disposal_enabled` default `true` | `create_mosques_table.php:24` |
| L3 Peraturan platform | 14/19 = `auto_padam` 7 tahun (13 jenis + prefix `200` kewangan) | `RetentionRuleSeeder.php:19-43` + **data produksi** |

**Konteks penting (dari audit):** enjin BERFUNGSI BETUL — t30+t7 dihormati, sijil dijana, legal
hold dihormati. Isu ialah *lalai*, bukan logik. §16.1 spec memang menetapkan jadual pelupusan
(pematuhan tatacara ANM); menukar L3 mengubah tingkah laku pematuhan → **keputusan pemilik**.

> ### ⚠️ 5.1a Status spec bagi L2 (C01 — bloker yang v1.3 terlepas)
>
> Semakan bebas P11 terhadap sumber kebenaran mendapati **L2 bukan pembetulan kualiti,
> tetapi perubahan produk yang bercanggah spec pada EMPAT tempat**:
>
> | Rujukan | Kandungan | Percanggahan |
> |---|---|---|
> | `DIWAN-SPEC.md:470` | `auto_disposal_enabled \| boolean **default true** \| suis §2.2/§16` | Nilai lalai ditetapkan secara eksplisit |
> | `DIWAN-SPEC.md` §16.1 | 14/19 padanan = `auto_padam`, `semak` ialah **override** | Lalai produk = auto-padam |
> | `DIWAN-SPEC.md` Aliran L langkah 3 | Pelupusan automatik memerlukan `auto_disposal_enabled` | Suis dijangka `true` untuk aliran berfungsi |
> | `DIWAN-SPEC.md` §16.2 | Teks pengakuan **WAJIB** semasa `/daftar`: rekod "**akan dipadam secara automatik dan tidak boleh dikembalikan**" | Jika lalai jadi `false`, teks pengakuan yang ditandatangani masjid baharu menjadi **tidak tepat** |
>
> **Kesan pada pelan:** D2 bukan "kelulusan pelaksanaan biasa". Ia mesti diluluskan sebagai
> **Addendum spec v2.6** (fail sedia ada `DIWAN-SPEC-ADDENDUM-2026-07.md` sudah mengandungi
> v2.2–v2.5 dengan corak tajuk `§X′`), yang **serentak** meminda §16.2 supaya teks pengakuan
> menerangkan keadaan sebenar ("pelupusan automatik **dimatikan secara lalai**; masjid
> menghidupkannya dalam Tetapan Masjid apabila bersedia"). Meminda lalai tanpa meminda teks
> pengakuan mencipta percanggahan pematuhan yang lebih buruk daripada isu asal.
>
> **Sehingga addendum diluluskan (D10):** lalai kekal `true`, migrasi §5.3 **TIDAK ditulis**,
> dan F4 melaksanakan **L1 sahaja** (default borang `semak` + pengesahan sedar) — yang sudah
> memberi brek sedar tanpa menyentuh spec.

### 5.2 Tindakan L1 (tidak kontroversi — cadang laksana terus selepas D1)

- `RetentionRuleResource::form()`: `->default('auto_padam')` → **`->default('semak')`**.
- Tambah **pengesahan kedua bermaklumat** apabila nilai `auto_padam` dipilih semasa Cipta/Edit.
  **Mekanisme muktamad (disahkan P2 terhadap vendor 4.11.8 — bukan lagi spike):** override pada
  halaman `CreateRetentionRule` dan `EditRetentionRule`:

  ```php
  // KONSEP — CreateRetentionRule (vendor: CreateRecord.php:252 & :273)
  protected function getCreateFormAction(): Action
  {
      return parent::getCreateFormAction()
          ->requiresConfirmation(fn (): bool => ($this->data['action'] ?? null) === 'auto_padam')
          ->modalHeading('Sahkan peraturan pemadaman automatik')
          ->modalDescription(fn (): string => $this->autoPadamImpactSummary());
  }
  protected function getCreateAnotherFormAction(): Action   // P2: liputi juga create-another
  {
      return parent::getCreateAnotherFormAction()->requiresConfirmation(...same...);
  }
  // EditRetentionRule (vendor: EditRecord.php:329)
  protected function getSaveFormAction(): Action
  {
      return parent::getSaveFormAction()->requiresConfirmation(...same...);
  }
  ```

  **PENTING (P2):** ambil `parent::get*FormAction()` dan **jangan** panggil `->action(...)` /
  `->submit(...)` semula — callback simpan bawaan vendor sudah terpasang; menggantikannya
  memutuskan simpan. `requiresConfirmation()` + `modalDescription()` ialah API sah
  (`CanRequireConfirmation.php:11`, `CanOpenModal.php:310`).
- Kandungan dialog: *"Peraturan ini membenarkan PEMADAMAN KEKAL automatik selepas N tahun untuk
  skop X. Anggaran rekod padanan skop pada masa ini: Y."* — Y dikira `Record::forMosque(tenant)`
  mengikut `record_type`/`classification_prefix` **daripada `$this->data` (state borang belum
  disimpan, P2)**; kiraan tenant-scoped, murah (COUNT), dan **bermaklumat sahaja** — bukan
  enforcement (nilai boleh berubah antara dialog dan commit; diterima).
- Fallback HANYA jika keadaan borang tidak stabil dalam closure semasa pelaksanaan: checkbox
  pengesahan `accepted_if:action,auto_padam` (UX kurang kemas — pilihan kedua).
- **Had skop yang diisytihar (P2):** dialog ialah brek UI pada laluan borang panel sahaja.
  Laluan bukan-UI (seeder platform, console, ujian) SENGAJA tidak melalui dialog — direkod
  sebagai keputusan reka bentuk; enforcement domain kekal pada gate `auto_disposal_enabled` +
  peraturan retensi itu sendiri.
- HelperText sedia ada dikekalkan.

### 5.3 Tindakan L2 (perlu D2 **DAN** Addendum spec v2.6 — D10; lihat §5.1a)

**GATE:** tiada satu pun butir di bawah ditulis sebelum addendum diluluskan dan dikomit.
Jika D10 = tidak → seksyen ini digugurkan sepenuhnya dan F4 = L1 sahaja.

- Migrasi baharu `2026_..._change_auto_disposal_default.php`:
  `$table->boolean('auto_disposal_enabled')->default(false)->change()` — **hanya masjid BAHARU**;
  data sedia ada TIDAK disentuh oleh `->change()` default.
- **Sokongan pemacu = perkara diuji, bukan dijanjikan (P2):** jangan andaikan mekanisme dalaman
  SQLite. Ujian migrasi WAJIB: `migrate:fresh` → `migrate:rollback` migrasi ini → `migrate`
  semula pada SQLite (suite), DAN semak SQL pgsql melalui `migrate --pretend` (atau integrasi
  CI pgsql yang memang berjalan) sebelum deploy — ALTER default sahaja dijangka, tiada rewrite.
- **Nota liputan (P2):** `tests/Pest.php:39` dan `DemoSeeder.php:126` menetapkan
  `auto_disposal_enabled => true` secara **eksplisit** — fixture ujian/demo TIDAK terkesan oleh
  perubahan default (tingkah laku suite kekal); ujian baharu #3 mesti guna factory TANPA
  override untuk menguji default sebenar.
- Padankan UI: Tetapan Masjid → toggle "Pelupusan automatik" kekal; wizard onboarding TIDAK
  menghidupkannya senyap; `MosqueProvisioningService::approve` disemak tidak menetapkan true
  secara eksplisit.
- Masjid sedia ada (`mamad`, `smoke`): kekal nilai semasa; JIKA pemilik mahu tukar → arahan
  tinker didokumen, bukan migrasi (jangan ubah data operasi dalam migrasi skema).
- **Teks pengakuan §16.2 dikemas serentak** (C01): borang `/daftar` + mana-mana salinan teks
  dalam `resources/` dikemas dalam commit yang SAMA dengan migrasi, supaya pengakuan yang
  ditandatangani sepadan tingkah laku sistem. Ujian: render `/daftar` → teks mengandungi
  rumusan baharu; ujian lama yang mengasert teks lama dikemas dan dicatat sebagai perubahan
  spec (peraturan #9).

### 5.4 Tindakan L3 (perlu D3 — JANGAN buat tanpa jawapan)

- Pilihan A (status quo): kekal `auto_padam` platform (patuh §16.1/ANM). Pengesahan kedua L1 +
  L2 false sudah memadai sebagai brek.
- Pilihan B: tukar seeder → `semak` untuk semua/kebanyakan jenis; **DAN** kemas kini data
  produksi dengan skrip berasingan yang diluluskan (BUKAN `db:seed` semula — `updateOrCreate`
  seeder akan menimpa; catat bahaya ini dalam runbook deploy F4: **JANGAN jalankan
  `RetentionRuleSeeder` pada produksi** semasa fasa ini).
- Kesan B: pelupusan automatik sedia ada terhenti → rekod tamat tempoh menunggu semakan manual.
- **Cadangan Claude:** A untuk peraturan platform (ia direka mengikut tatacara), B tidak perlu
  jika L1+L2 dilaksana — kerana masjid baharu perlu *memilih masuk* dua kali (enable suis +
  peraturan) sebelum sebarang pemadaman automatik berlaku.

### 5.5 Ujian

**`tests/Feature/RetentionDefaultsTest.php`** (baharu):
1. Borang cipta peraturan → nilai awal medan `action` = `semak`.
2. Aksi Create/CreateAnother/Save dengan `action=auto_padam` → `requiresConfirmation` aktif
   (assert konfigurasi aksi); dengan `action=semak` → tiada pengesahan; simpan tetap berfungsi
   (regresi callback parent — P2: buktikan simpan TIDAK putus).
3. **Dua kontrak berasingan (C01)** — ujian dipilih oleh status addendum, bukan digantung:
   (a) *kontrak semasa* (addendum belum lulus): masjid baharu **factory TANPA override** →
   `auto_disposal_enabled === true` **dan** teks pengakuan §16.2 versi asal dipapar `/daftar`.
   Ujian ini wujud SEKARANG dan menjadi penjaga bahawa L1 tidak tersasar menukar L2.
   (b) *kontrak selepas addendum v2.6*: ujian (a) digantikan (bukan ditambah) dengan
   `auto_disposal_enabled === false` + teks pengakuan baharu. Commit yang menukar lalai mesti
   menukar ujian ini dalam commit yang sama, dengan rujukan nombor addendum dalam mesej commit.
4. *(P2)* Kiraan impak: fixture rekod 2 tenant → `autoPadamImpactSummary()` mengira tenant
   sendiri sahaja (tenant asing = 0 — penjaga isolasi #1).
5. *(P2)* Migrasi: rollback + re-migrate hijau (SQLite); `--pretend` pgsql dicatat dalam bukti fasa.
6. Regresi enjin: `RetentionEngineTest` sedia ada (7 ujian) kekal hijau — gate
   `auto_disposal_enabled` false → `executeAuto` tidak memadam (ujian sedia ada meliputi;
   sahkan).

### 5.6 Kriteria siap F4

- [ ] Ujian baharu + suite hijau (termasuk RetentionEngineTest tak berubah)
- [ ] Produksi: buka borang cipta peraturan → default "Semak"; pilih "Auto Padam" → dialog
      amaran dengan kiraan muncul
- [ ] `mamad` & platform rules TIDAK berubah dalam DB produksi (query sebelum/selepas deploy —
      bukti dicatat)
- [ ] **Status spec direkod eksplisit (C01):** sama ada (a) "Addendum v2.6 diluluskan, dikomit,
      migrasi + teks §16.2 dikemas", atau (b) "Addendum tidak diluluskan → L2 digugurkan,
      lalai kekal `true`, F4 = L1 sahaja". Tiada keadaan ketiga.
- [ ] Tiada housekeeping dalam commit ini (C25 — A2/A3 milik F10)
- [ ] Matriks keselamatan §0.6 (S1–S6) hijau

**Risiko:** `->change()` migrasi pada pgsql produksi — operasi ALTER ringan (default sahaja,
tiada rewrite jadual). Rollback: migrasi `down()` kembalikan default `true` + revert commit.

---

## 6. FASA F5 — Kandungan katalog & tour halaman awam

**Menutup:** RR-01-01/RR-08-02 + RR-01-08 + RR-01-09 + RR-01-10/RR-10-03 + RR-10-04 + RR-11-04 · **Keutamaan:** #5, #6

### 6.1 (F5a) Tour `/log-masuk` — RR-01-01

**Punca:** layout tetamu (`guest-layout.blade.php:89`) guna `<div class="wrap">` — tiada `<main>`;
`decorateTargets()` gagal; kedua-dua langkah `public.login` (`page-content`, `page-primary`)
tiada sasaran → `showUnavailableGuide()` = ralat palsu setiap kali.

**Pembaikan (dua-dua, bukan salah satu):**
1. **Semantik layout — DIBETULKAN v1.4 (C14).** Cadangan v1.3 (`<div class="wrap">` →
   `<main class="wrap">`) **ditolak** kerana `.wrap` **bukan** hanya kandungan halaman:
   baris 90–101 menunjukkan ia membungkus `.brand` yang mengandungi **`<h1>`** (jenama Diwan)
   dan **`<nav class="brand-actions" aria-label="Navigasi utama">`** (Utama/Log Masuk/Daftar +
   `<livewire:help-launcher>`), barulah `{{ $slot }}`. Menukarnya kepada `<main>` akan
   (a) menyarangkan landmark `navigation` di dalam `main`, (b) menghapuskan landmark `banner`,
   (c) menjadikan `page-content` menyorot seluruh halaman termasuk nav — iaitu **masalah
   sorotan-terlalu-besar yang sama** yang F6 cuba selesaikan.

   **Bentuk yang betul:**
   ```blade
   <div class="wrap">                                  {{-- kekal div, CSS tidak berubah --}}
       <header class="brand"> … h1 + nav … </header>   {{-- banner --}}
       <main data-help-target="page-content">{{ $slot }}</main>
   </div>
   ```
   Kesan: `page-content` kini menyasar **kandungan sebenar** halaman tetamu; landmark
   `banner`/`navigation`/`main` ketiga-tiganya sah dan tidak bersarang. Nota: `.wrap` mempunyai
   `padding`/`width` — sahkan visual selepas menambah `<main>` (elemen blok, tiada gaya lalai
   yang memecahkan susun atur; jika perlu `main{display:block}` eksplisit).
   Semak: `magic-continue/magic-invalid` guna layout sama? (grep semasa pelaksanaan; jika tidak,
   biarkan — bukan halaman tour).
2. **Sasaran spesifik login:** `request-magic-link.blade.php` — tambah
   `data-help-target="login-identity"` pada `<input wire:model="login">` dan
   `data-help-target="login-submit"` pada butang; `guides.json` `public.login`:
   langkah 1 `target: "login-identity"`, langkah 2 `target: "login-submit"`,
   `wait_for_user` dinilai (langkah 2 = tindakan sebenar → `wait_for_user: true`).
   Bump `public.login.version` → 2 (auto-tour semula halaman awam dikawal `localStorage`
   `diwan-help-seen`, bukan version — kesan minimum; sahkan semasa uji).

### 6.2 (F5b) Tour muat naik — RR-01-08

Sasaran sedia ada hanya **dua**: `ListInbox.php:29` (`inbox-upload` — butang pencetus) dan
`:30` (`inbox-upload-modal` — `extraModalWindowAttributes`, iaitu **seluruh tetingkap modal**).

**Pembetulan v1.4 (C12):** jadual v1.3 menggunakan `inbox-upload-modal` untuk **langkah 2 DAN
langkah 3**. Kedua-dua langkah itu akan menyorot objek yang **sama dan sama besar** — pengguna
melihat sorotan tidak berubah sambil arahan bertukar daripada "pilih fail" kepada "tekan
Hantar". Itu ialah kecacatan yang sama seperti sorotan generik, cuma dalam skala modal. Sasaran
mesti dipecah kepada elemen sebenar:

| # | Sasaran | Baharu? | Arahan (intipati) | wait_for_user |
|---|---|---|---|---|
| 1 | `inbox-upload` | sedia ada | Tekan butang **+ Muat Naik Dokumen** | true |
| 2 | `inbox-upload-dropzone` | **BAHARU** | Pilih/seret fail; format sah + had saiz dipapar di sini | true |
| 3 | `inbox-upload-submit` | **BAHARU** | Tekan **Hantar** selepas senarai fail lengkap | true |
| 4 | `page-content` | sedia ada | Semak toast & baris baharu — antivirus, OCR, sumber | false |

**Mekanisme (API sudah terbukti dalam repo — tiada teknik baharu):**
- `inbox-upload-dropzone` → `FileUpload::make('files')->extraAttributes(['data-help-target' => …])`
  (corak sama `InboxTable.php:106/117/133`).
- `inbox-upload-submit` → `->modalSubmitAction(fn (Action $a): Action => $a->extraAttributes([…]))`
  — corak **verbatim** daripada `InboxTable.php:92` (`classification-submit`).
- `inbox-upload-modal` **dikekalkan** dalam registry sebagai sasaran rizab (`reserved`) untuk
  langkah orientasi, tetapi tidak digunakan dua kali berturut-turut.

(4 langkah menggantikan 5; langkah "sahkan toast" digabung. Kandungan penuh ditulis semasa
pelaksanaan mengikut UI sebenar.) Bump versi guide.

### 6.3 (F5c) Dashboard arahan vs sorotan — RR-01-09

`tenant.dashboard`: langkah 1 ("Semak nama masjid…") + langkah 4 ("Gunakan menu kiri…") →
sasaran navigasi (sasaran `sidebar` disediakan `decorateTargets()` → `.fi-sidebar`).

**⛔ Ralat fakta v1.3 yang dibetulkan (C13):** ayat "sasaran kekal `sidebar` **dengan fallback
sedia ada**" adalah **salah**. `GENERIC_TARGETS` dalam `resources/js/help.js:6` ialah tepat
`new Set(['page-content', 'page-primary'])` — `sidebar` **tiada di dalamnya**. Dalam
`resolveStepElement()`, cabang fallback hanya berjalan jika
`allowGenericFallback && GENERIC_TARGETS.has(step.target)`; untuk `sidebar` syarat itu `false`,
jadi apabila `.fi-sidebar` tidak `isVisible` (mobile <lg, tersembunyi di sebalik ☰) fungsi
memulangkan **`null`** → langkah menjadi **`target_missing`** → "Tindakan belum tersedia".
Tambahan: `sidebar` kini digunakan **0 kali** dalam katalog (semakan taburan sasaran F0), jadi
F5 akan menjadi **penggunaan pertama** — pepijat ini akan diperkenalkan oleh pelan sendiri jika
tidak dibetulkan sekarang.

**Reka bentuk v1.4 — sasaran navigasi responsif:**
1. Daftar **dua** sasaran berasingan dan biarkan katalog merujuk sasaran **logik** `nav-primary`:
   - desktop: `.fi-sidebar` → `data-help-target="nav-sidebar"`
   - mobile: butang buka menu Filament (`.fi-topbar` toggle) → `data-help-target="nav-menu-toggle"`
2. `decorateTargets()` diperluas dengan satu peta responsif kecil: `nav-primary` diselesaikan
   kepada `nav-sidebar` jika ia `isVisible`, jika tidak kepada `nav-menu-toggle`. Ini
   **peraturan pemilihan**, bukan fallback generik — ia tetap menyorot elemen navigasi sebenar,
   bukan `main`.
3. Arahan ditulis neutral-peranti ("menu navigasi — pada skrin kecil, tekan **☰** dahulu").
4. TIDAK membuka drawer secara automatik (tour tidak mengklik — prinsip sedia ada); langkah
   mobile menyorot butang ☰ dan `wait_for_user` supaya pengguna sendiri membukanya.
5. **Ujian dua breakpoint wajib** (§6.5 #7): desktop 1280px → elemen dipilih ialah `.fi-sidebar`;
   mobile 390px → elemen dipilih ialah butang ☰ dan **bukan** `MAIN`, dan **tiada**
   `target_missing` dipancarkan.

### 6.4 (F5d) Tajuk duplikasi & terpotong — RR-01-10/RR-10-03/RR-10-04

**Dua punca berbeza:**
1. **Katalog (angka DIBETULKAN — C09): `258`/473 langkah** bertajuk `"Langkah N"`, bukan 444.
   Angka 444 dalam v1.0–v1.3 ialah **kekeliruan dengan metrik lain**: `443`/473 ialah bilangan
   langkah bersasar **generik** (`page-primary` 238 + `page-content` 205). Kiraan placeholder
   sebenar (dikira dua kali, P10+P11): **tenant 118 + screen 140 = 258**; family `admin`,
   `public` dan `workflow` mempunyai **0** placeholder — tajuknya sudah ditulis tangan.
   Kesan pada pelan: (a) skop menulis tajuk hampir **separuh** daripada anggaran lama;
   (b) kerja tertumpu pada family `tenant` + `screen` sahaja; (c) baseline F8 mesti melapor
   258, bukan 444.
   Mekanisme kerosakan kekal sama: placeholder → runtime hidrat daripada klausa pertama
   arahan (`HelpCatalog::meaningfulStepTitle`) → jika arahan tiada `;`/`.` pertengahan, tajuk =
   keseluruhan ayat = penerangan (duplikasi); `Str::limit(72)` memotong pertengahan perkataan.
2. **Runtime:** `meaningfulStepTitle` boleh lebih pintar, tetapi tajuk sebenar lebih baik
   daripada heuristik.

**Pembaikan berperingkat (keutamaan diterbalikkan P2 — kandungan dahulu, heuristik fallback):**
- **(i) Kandungan (penyelesaian utama):** tulis `title` eksplisit untuk **kohort 25 guide
  tenant produksi (124 langkah — manifest P11)** dahulu; baki katalog pada iterasi F6. Gaya
  tajuk: ≤6 patah, kata kerja dahulu ("Buka Peti Masuk", "Pilih dokumen", "Semak metadata").
  Tajuk eksplisit mengelakkan heuristik menghasilkan tajuk ganjil.
- **(ii) Runtime (fallback untuk baki katalog sahaja):** `meaningfulStepTitle()` —
  `Str::limit($firstClause, 72, '…', preserveWords: true)` (API disahkan wujud:
  `vendor/.../Str.php:730-750` parameter ke-4 `$preserveWords` — P2 sahkan; deterministik,
  kekalkan had aksara, elipsis sentiasa hujung perkataan). Heuristik koma/8-perkataan
  DIGUGURKAN (P2 — hasil tidak deterministik).
- **JS `stepDescription`** juga papar `step.instruction` penuh — dengan tajuk pendek (i)/(ii),
  duplikasi visual selesai tanpa ubah JS.

### 6.5 Ujian

**`tests/Feature/Help/HelpCatalogQualityTest.php`** (baharu, penjaga kekal):
1. 0 padanan `/\bSeterus\b(?!nya)/` (dari F3).
2. Untuk setiap langkah: `title` ≠ `instruction` (normalisasi; toleransi: title ialah prefix
   ≤60% panjang instruction).
3. *(dibetulkan P2 — regex 1-2 huruf asal tidak sah untuk BM/Unicode)* Kualiti tajuk kohort:
   **kohort 25 guide/124 langkah mesti mempunyai `title` eksplisit** (bukan `Langkah N`) dan
   **tiada `title` kohort mengandungi elipsis** (`...`/`…`) — tajuk eksplisit tidak dipotong,
   jadi semakan menjadi deterministik tanpa heuristik "potong-tengah". Untuk baki katalog
   (hidratan fallback), assert output `meaningfulStepTitle()` menggunakan `preserveWords`
   (unit ujian terus fungsi itu dengan input berdiakritik).
4. `public.login` sasaran = `login-identity`/`login-submit`; `screen.muat-naik-dokumen`
   langkah 1–3 = `inbox-upload` / `inbox-upload-dropzone` / `inbox-upload-submit`
   — **dan tiada dua langkah berturut-turut berkongsi sasaran yang sama** dalam guide itu (C12).
5. `catalog_version` berubah bila kandungan berubah (bandingkan hash? — praktikal: ujian
   memastikan versi ≥ nilai tertentu; nilai sebenar dijaga review PR).
6. *(P2, diperhalusi C14)* Layout tetamu: render semua halaman guest (`/`, `/log-masuk`,
   `/daftar`, `/bantuan`, halaman magic) → tepat SATU elemen `<main>`, `<main>` mengandungi
   slot **dan bukan** `<h1>` jenama atau `nav.brand-actions`, serta tepat satu `<header>`
   (banner) di luar `<main>`.
7. *(C13)* Sasaran navigasi: unit ujian ke atas peta responsif — `nav-primary` menyelesai
   kepada `nav-sidebar` bila sidebar kelihatan, kepada `nav-menu-toggle` bila tidak; **tiada**
   keadaan yang memulangkan `null`/`MAIN`.
8. *(C12)* Muat naik: sasaran `inbox-upload-dropzone`/`inbox-upload-submit` benar-benar wujud
   dalam HTML modal yang dirender (ujian HTTP render, bukan grep sumber) — menutup jurang
   "registry menyenaraikan sasaran yang tiada dalam DOM".

**e2e:** tour `/log-masuk` pada layout baharu → TIADA "Tindakan belum tersedia"; langkah 1
menyorot input (bukan `main`); viewport mobile juga; *(P2)* kes JS gagal memuat sasaran
(elemen dinyahaktif) → fallback artikel masih berfungsi.

**e2e muat naik — matriks keadaan (C12, skop diisytihar):**

| Keadaan | Gate? | Sebab |
|---|---|---|
| Fail sah (PDF kecil) → 4 langkah tour tamat | **Ya** | laluan utama |
| Format salah (`acceptedFileTypes` menolak) | **Ya** | tour tidak tersangkut; mesej validasi BM (F3) |
| Melebihi saiz (`maxSize` = `diwan.max_upload_mb`) | **Ya** | sama |
| Kuota penuh (`QuotaService::isFull` → notifikasi merah, `ListInbox` cabang sedia ada) | **Ya** | laluan ralat yang sudah wujud dalam kod |
| Antivirus `pending`/`infected` pada baris hasil | Ya (assert paparan) | kolum `virus_scan_status` sedia ada (`InboxTable.php:61-63`); langkah 4 merujuknya |
| Batal modal di tengah aliran | Nota sahaja | kelakuan vendor Filament; direkod, bukan gate |
| Hantar dua kali (double submit) | Nota sahaja | dikawal Filament (`wire:loading` disable); direkod, bukan gate |

Sebab pemisahan: dua baris terakhir menguji **tingkah laku vendor**, bukan pembaikan pelan ini;
menjadikannya gate akan menghasilkan ujian rapuh yang gagal pada naik taraf Filament tanpa
regresi sebenar pada Diwan.

### 6.6 Kriteria siap F5

- [ ] Ujian katalog + e2e login lulus; suite penuh hijau; `npm run build`; **gate CI hijau** —
      check **`PostgreSQL, Redis, Meili, OCR and tests`** (canary + `ci-guidance` + `ci-domain`)
      + required check **`guidance-e2e-gate`** (F0(iv)(f))
- [ ] `diwan:sync-help-index --delete` dalam langkah deploy
- [ ] Produksi: buka `/log-masuk` pengguna baharu (sesi bersih) → tour bermula sorot medan;
      Peti Masuk → tour muat naik membuka aliran butang sebenar (3 sasaran berbeza)
- [ ] Metrik tajuk kohort: duplikasi verbatim 77/124 → **0**; terpotong-tengah 20/124 → **0**
- [ ] Metrik placeholder katalog dilapor dengan angka betul: **258** baseline (bukan 444) —
      selepas F5 kohort tenant, baki dilapor sebagai kerja F6 (C09)
- [ ] Layout tetamu: tepat satu `<main>`, `<h1>`+nav **di luar** `<main>` (C14)
- [ ] Navigasi mobile: langkah dashboard menyorot ☰, `target_missing` = 0 (C13)
- [ ] Matriks keselamatan §0.6 (S1–S6) hijau

**Risiko:** bump versi guide → auto-tour semula untuk pengguna yang pernah selesai (lihat D6).
Kandungan BM perlu proofread — semakan Codex pusingan plan + review PR.

---

## 7. FASA F6 — Sasaran spesifik `data-help-target` (kerja berperingkat)

**Menutup:** RR-01-06/RR-03-04/RR-10-02/RR-11-03 + RR-10-05/RR-11-05 · **Keutamaan:** #4

### 7.1 Fakta asas (disahkan; denominator dibekukan P2)

- **Tiga angka BERBEZA — jangan campur (dikemas C02/C09):**
  (i) **Katalog penuh = 83 guide / 473 langkah** — inilah **gate keluaran** F6/F8;
  (ii) subset panel app = 68 guide / 433 langkah (angka v1.3; ia **bukan** denominator keluaran
  kerana ia meninggalkan family `public` dan sebahagian `screen`);
  (iii) **kohort audit produksi = 25 guide / 124 langkah** (manifest P11) — SEMUA metrik
  sebelum/selepas (119/124 dsb.) merujuk kohort ini, dan ia kekal **untuk perbandingan
  apple-to-apple sahaja**, bukan gate.
- **Pecahan katalog penuh (dibekukan F0(ii); dikira dua kali P10+P11):**

  | Family | Guide | Langkah | Generik | Placeholder `Langkah N` |
  |---|---:|---:|---:|---:|
  | admin | 12 | 32 | 32 | 0 |
  | public | 3 | 8 | 4 | 0 |
  | screen | 29 | 151 | 140 | 140 |
  | tenant | 25 | 124 | 124 | 118 |
  | workflow | 14 | 158 | 143 | 0 |
  | **Jumlah** | **83** | **473** | **443** | **258** |

  Generik = `page-primary` 238 + `page-content` 205. Kohort: **119/124 resolve ke `page-content`**
  pada runtime (95.97%).
- **Jurang liputan yang dibetulkan (C02):** W1–W3 v1.3 hanya merangkumi `tenant` (25) +
  `admin` (12) = **37/83 guide**. Baki **46 guide / 317 langkah** (`screen` 29, `workflow` 14,
  `public` 3) tidak pernah masuk mana-mana gelombang, walaupun `screen` sahaja mempunyai
  **140 langkah generik + 140 placeholder** — bahagian terbesar hutang katalog. v1.4 memperluas
  kepada enam gelombang; **v1.6 memecahnya semula kepada TUJUH wave `W0–W6`** apabila enam defect
  mobile dinaikkan menjadi hotfix `W0` (§7.2 + §1 F0(ii-a)).
- Dua metrik dilapor BERASINGAN (P2): `generic_target_declared` (katalog) dan
  `resolved_to_generic` (runtime) — kerana `page-primary` boleh resolve spesifik via
  `semanticAction()` dan sasaran spesifik boleh gagal resolve.

- **Metrik KETIGA yang menjadi keutamaan sebenar (baharu P11):
  `action_steps_with_generic_target` = 200/229.** Bukan setiap langkah generik sama beratnya.
  Langkah **penerangan** yang menyorot `main` hanya kurang kemas; langkah **tindakan**
  (`wait_for_user: true`) yang menyorot `main` ialah punca terus aduan pemilik "dah tekan ke
  belum?" — sistem meminta tindakan tanpa menunjuk apa yang perlu ditekan. Pecahannya:

  | Family | `wait_for_user` | daripadanya bersasar generik |
  |---|---:|---:|
  | screen | 151 | **140** |
  | workflow | 75 | **60** |
  | public | 3 | 0 |
  | tenant | **0** | 0 |
  | admin | **0** | 0 |
  | **Jumlah** | **229** | **200** |

  **Implikasi yang mengubah susunan kerja:** kohort audit (tenant 25/124) yang v1.3 jadikan
  denominator utama mengandungi **sifar** langkah tindakan dan **sifar** sasaran spesifik —
  ia bahagian katalog yang paling ringan. Kesemua 200 langkah tindakan bersasar generik berada
  dalam `screen` + `workflow`, iaitu dua family yang **tiada langsung** dalam W1–W3 v1.3.
  Oleh itu gate utama F6 ialah **`action_steps_with_generic_target` 200 → 0** (dengan allowlist
  bersebab). **Kesimpulan yang dikuatkuasakan v1.5 (P12-02):** `screen` dan `workflow` bukan
  "kerja tambahan di hujung" tetapi **teras** pembaikan CTA — maka ia menjadi **W1 dan W2**
  dalam urutan gelombang §7.2, bukan W4/W5 seperti v1.4.
  (Nota konsisten: keseluruhan katalog hanya menggunakan **30 langkah** bersasar spesifik
  merangkumi **13 nama sasaran unik** — `classification-*` 20, `inbox-*` 6, `registration-*` 4.)

- **Kaedah ujian berbeza mengikut family — kerana 17 route dikongsi (baharu P11).**
  `HelpCatalog::currentGuide()` (`app/Services/HelpCatalog.php:111-123`) memilih **satu** guide
  per laluan (route terpanjang; seri diputuskan oleh susunan katalog). 17 route dikongsi lebih
  daripada satu guide — cth. `/app/{tenant}/peti-masuk` dikongsi `tenant.peti-masuk`,
  `screen.muat-naik-dokumen` dan `screen.klasifikasi-peti-masuk`; `/app/{tenant}` dikongsi
  `tenant.dashboard` + 2 guide `workflow.*`. Akibatnya guide `screen.*`/`workflow.*` pada route
  dikongsi **tidak pernah** menjadi guide halaman dan **tidak boleh** diuji dengan sekadar
  membuka halaman itu.

  | Family | Cara guide dicapai | Kaedah ujian (setiap gelombang) |
  |---|---|---|
  | tenant, admin | auto/`currentGuide()` pada route | buka route → tour halaman |
  | screen, workflow | **deep-link `?panduan=<id>`**, carian, Pusat Bantuan | navigasi `route?panduan=<id>` → assert `data-guide-id` = id yang diminta sebelum menilai langkah |
  | public | auto + `localStorage diwan-help-seen` (`help.js:581`) | sesi bersih per kes |

  Manifest F0(ii) menyimpan medan `access_method` per guide supaya skrip ukur F8 tidak tersilap
  melaporkan "guide tidak wujud" sedangkan ia hanya tidak terpilih pada route dikongsi.
- `semanticAction()` (padanan token arahan↔label butang, skor ≥16) — pada praktiknya gagal
  memadan (0/124 di P11) kerana arahan katalog terlalu umum.
- Corak BERJAYA sedia ada (5 langkah): `extraAttributes(['data-help-target' => ...])` pada
  aksi/medan Filament (`ListInbox.php`, `InboxTable.php`) + sasaran dirujuk katalog.
  `extraModalWindowAttributes()` disahkan wujud (`HasExtraModalWindowAttributes.php:18-29`);
  nilai atribut mesti literal statik, bukan input pengguna (amaran vendor).
- Popover mobile menutup tengah (6 langkah — RR-11-05) ialah **akibat** sorotan generik: bila
  sasaran = `main` setinggi 2781px, Driver.js letak popover di tengah. Sasaran spesifik = popover
  melekat elemen = isu hilang.

### 7.2 Strategi

**Bukan "tambah sasaran untuk semua 473 langkah serentak"** — itu tidak realistik dan berisiko.
Pendekatan: **tujuh wave `W0`–`W6`** (C02 memperluas tiga → enam; P14-05 menambah hotfix `W0`),
setiap satu deploy + ukur. Senarai ID exact setiap wave ialah medan **manifest** (§1 F0(ii-a)),
dirumus untuk semakan manusia dalam `PLAN-RR-17-CLAUDE.md` §5.

> ⚠️ **URUTAN DITERBALIKKAN v1.5 (P12-02).** v1.4 menyusun W1–W2 = `tenant`, W3 = `admin`,
> W4 = `screen`, W5 = `workflow` — iaitu **bercanggah terus dengan rumusannya sendiri di §7.1**,
> yang membuktikan bahawa kesemua **200/229** langkah tindakan bersasar generik berada dalam
> `screen` (140) + `workflow` (60), manakala `tenant` dan `admin` mempunyai **sifar** langkah
> tindakan. Menyusun `tenant`/`admin` dahulu bermakna tiga gelombang pertama **tidak menggerakkan
> metrik keutamaan langsung** — pelan akan kelihatan "hampir siap selepas W3" sedangkan punca
> aduan pemilik ("dah tekan ke belum?") belum disentuh. Urutan v1.5 mengikut **risiko pengguna
> sebenar**, bukan saiz kohort audit.

> ⚠️ **ANGGARAN DIGANTI DENGAN PARTITION EXACT v1.6 (P14-05).** v1.5 menulis `~10` guide W1 dan
> `~6` guide W2 sambil mengakui inventori belum dibuat. Angka lebih-kurang membenarkan guide
> sukar berpindah senyap ke "baki" dan menjadikan metrik per-gelombang **tidak boleh diaudit**.
> v1.6 menggantikannya dengan **partition deterministik** yang dikira terus daripada katalog beku
> (peraturan + jadual + invarian: **§1 F0(ii-a)**). Dua daripada anggaran itu ternyata **salah**:
> W1 sebenarnya **28** guide (bukan ~10) dan W3 hanya **1** guide (bukan ~19).

| Gelombang | Family | Skop | Guide | Langkah | Langkah tindakan generik ditutup |
|---|---|---|---:|---:|---:|
| **W0** | `tenant` (rentas-family) | **Hotfix defect mobile** — 6 langkah popover yang terbukti menutup ruang tengah | **2** | **10** | 0 (defect mobile **6 → 0**) |
| **W1** | **`screen` bertindakan** | 28/29 guide `screen` yang mempunyai ≥1 langkah tindakan bersasar generik | **28** | **140** | **140** |
| **W2** | **`workflow` bertindakan** | 13/14 guide `workflow` yang sama syaratnya | **13** | **145** | **60** |
| W3 | baki `screen` | `screen.klasifikasi-peti-masuk` (11 langkah, 11 `wait_for_user`, **0** bersasar generik) | **1** | 11 | 0 |
| W4 | baki `workflow` | `workflow.setiausaha.klasifikasikan-surat-masuk-dan-edarkan-minit` | **1** | 13 | 0 |
| W5 | `tenant` + `admin` | 37 guide **tolak 2 yang naik ke W0** (boleh dipecah W5a/W5b) | **35** | 146 | **0** |
| W6 | `public` | 3 guide / 8 langkah — sebahagian sudah ditutup F5 | 3 | 8 | 0 |
| | | **JUMLAH** | **83** | **473** | **200** |

**W0 — enam defect mobile dinaikkan ke hadapan (P14-05).** v1.5 menangguhkannya ke **W5**
semata-mata kerana familynya `tenant`. Itu salah dari segi keutamaan: ia **kerosakan pengguna
sedia ada yang telah diukur pada produksi**, bukan kerja penerangan biasa, dan tidak wajar
menunggu empat gelombang selepas F2. Keenam-enamnya dikenal pasti **exact** daripada
`Audit Review Round Robin/bukti/pusingan-11-codex/production-mobile-all-tour-steps.json`
(medan `centerCovered: true`, 6/124):

| # | Guide | Langkah | Sasaran katalog | Sasaran diselesaikan (runtime) | Tinggi popover |
|---|---|---:|---|---|---:|
| 1 | `tenant.pelupusan` | 1 | `page-content` | `page-content` | 327 px |
| 2 | `tenant.kegemaran` | 1 | `page-content` | `page-content` | 306 px |
| 3 | `tenant.kegemaran` | 2 | **`page-primary`** | `page-content` | 244 px |
| 4 | `tenant.kegemaran` | 3 | `page-content` | `page-content` | 222 px |
| 5 | `tenant.kegemaran` | 4 | `page-content` | `page-content` | 244 px |
| 6 | `tenant.kegemaran` | 5 | `page-content` | `page-content` | 243 px |

*(Nota konsistensi: baris 3 memperlihatkan perbezaan `generic_target_declared` lawan
`resolved_to_generic` yang §7.1 sudah isytiharkan — katalog mengisytihar `page-primary`,
runtime menyelesaikannya ke `page-content`. Bukan percanggahan.)*

**Skop W0 = dua guide penuh** (`tenant.pelupusan` 5 langkah + `tenant.kegemaran` 5 langkah),
bukan enam langkah terpencil. Sebab: G4 mengira **kitaran guide**, jadi memecah satu guide antara
dua gelombang akan merosakkan perakaunan 83/83. Kos tambahan hanya 4 langkah.
**Masa:** selepas **F2** (F2 memiliki tingkah laku popover/auto-minimize), sebelum W1.
**Gate W0:** keenam-enam langkah diuji **desktop DAN mobile** (390×664) sebelum W2 boleh
bermula — bukan sekadar "tiada regresi".

**Invarian yang mengatasi sebarang pecahan gelombang alternatif:** jika pelaksana memilih
pemecahan lain, syarat ini kekal — **semua langkah `wait_for_user` bersasar generik mesti
diselesaikan, ATAU di-risk-accept secara spesifik dengan ID guide + indeks langkah + sebab +
tarikh, SEBELUM kerja penerangan `tenant`/`admin` boleh dikira sebagai kemajuan utama F6.**

**Prasyarat teknikal W1/W2 (dipenuhi):** guide `screen.*` dan `workflow.*` dicapai melalui
deep-link `?panduan=<id>` (§7.1 — 17 route dikongsi), iaitu laluan yang F1 ubah
(`launchPending` one-shot, §2.2 nota 6). Kerana F6 datang selepas F1, tiada halangan urutan;
tetapi ujian W1/W2 **mesti** mengassert `data-guide-id` = id yang diminta **sebelum** menilai
langkah, kerana `currentGuide()` akan memilih guide lain pada route yang sama.

**Kesan urutan ke atas dua item yang dahulunya "skop W1":**
- **Popover mobile menutup ruang tengah — 6 langkah (RR-10-05/RR-11-05).** v1.5 menjatuhkannya
  ke **W5** kerana familynya `tenant`. **DIBETULKAN v1.6 (P14-05): ia kini W0**, dilaksana
  sejurus selepas F2 sebagai hotfix rentas-family. Tiada penangguhan, jadi tiada "penangguhan
  bertarikh" untuk item ini lagi. Sasaran kekal **6 → 0**, diuji desktop **dan** mobile.
- **Placeholder tajuk `Langkah N` (258).** Pecahan mengikut wave beku:
  **W0 10 + W1 140 + W5 108 = 258**. Laporan F8 menyatakan ketiga-tiga angka pada denominator
  penuh, bukan "0 dalam family yang telah digelombangkan".

**Gate keluaran (menggantikan "selesai selepas W3"):** setiap satu daripada **83 guide** mesti
mempunyai status liputan yang direkod dalam manifest — `specific`, `generic-justified`,
`not-applicable`, `risk-accepted` atau `blocked`. **Tiada guide tanpa status.**
Makna setiap status dan kesannya terhadap keluaran ditakrifkan **satu kali sahaja** dalam
jadual status §7.3 (P14-06) — khususnya: **`blocked` menyekat keluaran, `risk-accepted` tidak.**

Nota realiti untuk `workflow` (**W2 + W4** dalam urutan v1.5): guide ini merentasi beberapa
halaman, jadi sebahagian besar langkah **penerangan**nya dijangka sah sebagai
`generic-justified`/`not-applicable`. Itu **keputusan sedar yang direkod**, bukan pengecualian
senyap — perbezaan inilah yang C02 tuntut. **Had tegas (P12-02):** kelonggaran ini terpakai
kepada langkah penerangan sahaja; **60 langkah `wait_for_user` bersasar generik** dalam
`workflow` **tidak** layak dilepaskan secara pukal atas alasan "merentas halaman" — setiap satu
memerlukan sasaran spesifik atau justifikasi per-langkah bertarikh.

Setiap gelombang:
1. **Inventori langkah:** untuk setiap guide halaman itu, tentukan elemen sebenar setiap langkah
   (butang header, input carian, tab, baris jadual pertama, medan borang).
2. **Tambah atribut** melalui mekanisme betul mengikut jenis komponen:
   - Aksi halaman/jadual: `->extraAttributes(['data-help-target' => 'x'])`
   - Modal: `->extraModalWindowAttributes([...])`
   - Medan borang: `->extraAttributes(...)` (corak `InboxTable` sedia ada)
   - Input carian jadual / elemen vendor tanpa cangkuk PHP: JS `decorateTargets()` diperluas —
     jadual pemetaan `[selector, target]` per halaman (kekal kecil; HANYA untuk yang tiada API
     Filament; setiap entri didokumen sebab)
3. **Kemas katalog:** langkah berkenaan `page-primary`/`page-content` → sasaran baharu;
   `wait_for_user` disemak semula setiap langkah (nilai yang salah = punca CTA mengelirukan).
4. **Namakan konsisten:** `{skrin}-{fungsi}` (cth. `records-search`, `minit-tab-selesai`,
   `approvals-approve`, `members-invite`). **Sumber kebenaran = registry (P2):** fail
   `resources/help/targets.json` (baharu). Ujian membaca registry (bukan grep source sebagai
   kebenaran); `docs/HELP-TARGETS.md` **dijana** daripada registry (skrip kecil), bukan
   diselenggara tangan.

   **Skema registry (dikemas C15 — grep sumber TIDAK memadai):** setiap entri mesti membawa
   medan yang membolehkan pengesahan **DOM sebenar**, bukan sekadar kewujudan teks:

   | Medan | Guna |
   |---|---|
   | `id` | nama sasaran (`records-search`) |
   | `family` + `route` | halaman tempat ia dijangka wujud (untuk ujian render) |
   | `owner_source` | fail:baris PHP/blade yang memasangnya (atau `js:decorateTargets`) |
   | `selector_hint` | pemilih pengesahan (cth. `.fi-ta-search-field input`) |
   | `viewport` | `desktop` \| `mobile` \| `both` — menutup kes C13 |
   | `state` | prasyarat sebelum ia wujud (cth. `modal:muatNaik terbuka`, `jadual tidak kosong`) |
   | `permission` | kebenaran yang diperlukan supaya elemen dirender |
   | `status` | `active` \| `reserved` \| `blocked` (+ `reason`, `since`) |

   **Gate registry:** (a) skema sah (ujian struktur); (b) setiap sasaran `active`
   **unik dan kelihatan** dalam render halaman `route`-nya pada `viewport` yang dinyatakan,
   selepas `state` disediakan; (c) tahan morph Livewire (ujian render selepas tapis jadual);
   (d) registry yatim = 0 dua hala; (e) sasaran katalog yang tiada dalam registry = 0;
   (f) `generic-justified` hanya melalui allowlist **bersebab + bertarikh**.

### 7.3 Penjaga automatik (kritikal untuk kerja berperingkat)

**`HelpCatalogQualityTest` tambahan:**
- *Sasaran yatim dua hala (P2):* (i) setiap `target` bukan-generik dalam katalog wujud dalam
  registry; (ii) setiap entri registry dirujuk oleh sekurang-kurang satu guide ATAU ditanda
  `reserved`. Registry↔kod: ujian smoke render halaman berkenaan (HTTP test) → assert atribut
  hadir dalam HTML (membuktikan kod, bukan sekadar senarai).
- *Metrik kohort (diganti P2; skop dijelaskan P12-02):* baseline kohort 25/124 dibekukan sebagai
  **perbandingan apple-to-apple sahaja**. Kerana kohort ialah `tenant` sepenuhnya, ia **tidak
  bergerak langsung** semasa W1–W4 dan mula bergerak pada **W5**. Ia **bukan** gate mana-mana
  gelombang; menjadikannya gate ialah ralat v1.4 yang menyebabkan urutan gelombang salah.
  Sasaran akhir selepas W5: `resolved_to_generic` kohort ≤ 25/124 **dengan syarat** setiap baki
  generik ada justifikasi per-langkah dalam allowlist — allowlist kosong bermakna semua spesifik.
- *Gate langkah TINDAKAN — metrik keutamaan, berkuat kuasa pada SETIAP gelombang:*
  selepas gelombang yang menyentuh sesuatu family, **tiada langkah `wait_for_user: true` dalam
  family itu yang kekal bersasar generik** melainkan ia disenaraikan dalam allowlist dengan
  **ID guide + indeks langkah + sebab + tarikh**. Dalam urutan v1.5 gate ini bergerak
  **serta-merta pada W1** dan menanggung beban terbesarnya pada W1–W4
  (`screen` 140 + `workflow` 60 = **200**), bukan ditangguh ke hujung.

**SEMANTIK STATUS — dibetulkan v1.6 (P14-06).** v1.5 membenarkan `blocked` sebagai status sah
dalam G1/G5 sambil G4 menuntut **83/83** guide melalui kitaran penuh, tanpa menyatakan apa yang
berlaku kepada langkah `blocked`; §7.4 pula membenarkan F6 ditutup "dengan baki bersebab".
Gabungan itu membenarkan guide yang **tidak berfungsi** dilabel `blocked` sambil pelan mendakwa
kitaran penuh lulus. Kekaburan itu ditutup:

| Status | Maksud | Kesan pada keluaran | Bukti wajib |
|---|---|---|---|
| `specific` | Setiap langkah menyasar elemen sebenar | — | G2 + G3 |
| `generic-justified` | Sasaran kekal generik atas sebab reka bentuk (langkah penerangan) | Dibenarkan | Sebab + `since` **per-langkah**; **dilarang** untuk langkah `wait_for_user` melainkan menjadi `risk-accepted` |
| `not-applicable` | Langkah/guide konsep yang memang tiada UI tunggal | Dibenarkan | Sebab per-langkah + G4 mesti membuktikan kitaran guide tetap tamat |
| **`risk-accepted`** | Kerosakan **diketahui** yang pemilik terima buat sementara | **Tidak menyekat keluaran** | ID guide + indeks langkah · impak kepada pengguna · **fallback artikel `/bantuan` yang benar-benar diuji** · nombor tiket · **nama pemilik** · **tarikh luput** |
| **`blocked`** | Langkah/guide tidak berfungsi dan **tiada** penerimaan risiko diluluskan | **RELEASE BLOCKER** — F6 dan F8 **tidak boleh ditutup** | Sebab + isu susulan; sasaran wajib **`blocked = 0`** |

**Peraturan tambahan (P14-06):**
1. `blocked` bukan tempat letak kerja yang tidak sempat — ia keadaan **sementara dalam
   gelombang berjalan** sahaja. Untuk menutup gelombang, setiap `blocked` mesti dinaikkan taraf
   kepada `specific`, `generic-justified`, `not-applicable`, atau **dibawa kepada pemilik**
   untuk menjadi `risk-accepted` dengan kesemua enam medan buktinya.
2. **G4 bagi `risk-accepted` menguji laluan pengguna sebenar**, bukan berpura-pura tour selesai:
   assert bahawa pengguna yang terkena langkah itu **benar-benar sampai** kepada fallback
   (artikel `/bantuan` guide berkenaan terbuka dan mengandungi arahan yang setara), dan bahawa
   tour **tidak** meninggalkan pengguna dalam dead-end tanpa jalan keluar.
3. **Tarikh luput wajib.** `risk-accepted` yang melepasi tarikhnya bertukar semula kepada
   `blocked` secara automatik dalam laporan — jadi penerimaan risiko tidak boleh menjadi kekal
   secara senyap.

**KONTRAK GATE F6 — lima lapis, tiada persampelan (P12-03).** Status pada aras *guide* tidak
membuktikan bahawa setiap satu daripada 473 langkah berfungsi. Penutupan F6/F8 memerlukan
kelima-lima lapis di bawah; **persampelan kekal berguna sebagai smoke selepas setiap gelombang,
tetapi ia BUKAN gate.**

| # | Lapis | Liputan wajib | Bukti |
|---|---|---|---|
| G1 | **Statik, per-langkah** | **473/473** langkah mempunyai status daripada jadual status di atas | Manifest katalog F0(ii) diperluas: setiap langkah dikunci oleh **`<guide_id>#<index1>`** (bukan `step.id` — ia tidak unik, §1 F0(ii-a)) dan membawa `wave`, `shard`, `route`, `permission`, `viewport`, `state`/prasyarat, dan (untuk status bukan-`specific`) `reason` + `since` bertarikh; `risk-accepted` menambah `impact`/`fallback`/`ticket`/`owner`/`expires` |
| G2 | **DOM hidup** | **semua** sasaran berstatus `specific` | Buka `route` dengan role + `state` yang diisytihar; assert sasaran **unik**, **kelihatan**, `data-help-target` sepadan, **kekal selepas morph** Livewire, dan wujud pada **setiap** `viewport` yang diisytihar |
| G3 | **Tour black-box** | **229/229** langkah tindakan | Jalankan langkah sebenar dalam BrowserContext berasingan + fixture deterministik; assert `.driver-active-element` ialah elemen yang betul, `Next`/tindakan maju **tepat sekali**, tiada dead-end |
| G4 | **Kitaran guide** | **83/83** guide | Mula → maju hingga tamat atau titik `not-applicable` → tutup → ulang → resume. Bagi guide ber-`risk-accepted`: **fallback pengguna sebenar** diuji (peraturan 2 di atas). Bagi `blocked`: **gagal** — tiada laluan lulus. Shard mengikut jadual beku F0(iv) — shard ≠ persampelan (setiap guide tetap dijalankan) |
| G5 | **Kebolehjejakan pengecualian** | setiap `blocked` / `risk-accepted` / `not-applicable` / `generic-justified` | Disenaraikan sebagai **ID guide + indeks langkah**, bukan pada aras guide. Menandakan satu guide penuh sebagai dikecualikan **tidak dibenarkan** melainkan setiap langkahnya disenaraikan. Laporan mengasingkan **empat kategori** (§9.3) — tidak boleh digabung menjadi satu "baki" |
- **e2e resolusi runtime — kaedah DITUKAR kepada black-box (C11).** Kontrak v1.2/v1.3 memanggil
  `resolveStepElement` melalui hook global `globalThis.__diwanHelpTest`. Hook itu **digugurkan**
  (§3.6) kerana ia kekal dalam bundle produksi. `resolveStepElement()` menyentuh DOM, jadi ia
  **tidak boleh** dipindahkan ke modul tulen seperti `stepAdvancePlan()` — sebaliknya ia diuji
  melalui **kesannya yang boleh dilihat**:
  1. Jalankan tour sebenar pada route guide berkenaan.
  2. Pada setiap langkah, baca **`document.querySelector('.driver-active-element')`** — inilah
     elemen yang Driver.js sorot, iaitu output `resolveStepElement` selepas melalui seluruh
     laluan sebenar (termasuk `decorateTargets()` dan `semanticAction()`).
  3. Assert: `tagName !== 'MAIN'` untuk langkah yang katalog isytihar spesifik;
     `dataset.helpTarget` (atau atribut `data-help-target`) **sama dengan** `step.target`;
     elemen **unik** (`querySelectorAll(selector).length === 1`) dan **kelihatan**.
  4. **Mobile juga** (menjaga RR-11-05: assert kotak popover tidak bertindih kotak sasaran).
  5. Satu kes **selepas morph Livewire** (tapis jadual → sasaran masih wujud — atribut daripada
     PHP kekal kerana ia sebahagian HTML server; ujian membuktikannya).
  Kelebihan tambahan: ujian ini menguji **apa yang pengguna nampak**, bukan fungsi dalaman,
  jadi ia kekal sah walaupun `resolveStepElement` di-refactor kelak.
- **Skop e2e per gelombang (dibetulkan P12-03):** dalam **gelombang berjalan**, e2e meliputi
  **semua** guide yang gelombang itu sentuh — bukan sampel. Persampelan berstruktur (setiap
  family ≥3 guide) kekal **hanya** sebagai *smoke silang-family* selepas setiap gelombang, untuk
  menangkap regresi pada family yang belum disentuh. Ia **tidak** boleh digunakan sebagai bukti
  penutupan F6 atau F8. Guide yang dilangkau oleh smoke **disenaraikan** dalam bukti fasa
  (tiada had senyap — pengajaran `spdm-deploy-lessons`).

### 7.4 Kriteria siap F6 (per gelombang + keseluruhan)

**Per gelombang (Wn):**
- [ ] **G1** untuk setiap langkah dalam skop Wn: status per-langkah direkod (tiada langkah kosong)
- [ ] **G2** untuk setiap sasaran `specific` baharu Wn: unik + kelihatan + tahan morph + setiap viewport
- [ ] **G3** untuk setiap langkah `wait_for_user` dalam skop Wn: tour black-box lulus
- [ ] **G4** untuk setiap guide dalam skop Wn: mula/maju/tutup/ulang/resume lulus
- [ ] Registry `targets.json` seiring; sasaran yatim = 0 dua hala; `docs/HELP-TARGETS.md` dijana
- [ ] Metrik gelombang dilaporkan pada **denominator penuh** (cth. "langkah tindakan generik:
      200 → 60 daripada 229"), bukan "0 dalam skop Wn"
- [ ] **`blocked` = 0 dalam skop Wn** — setiap `blocked` sudah dinaikkan taraf atau menjadi
      `risk-accepted` dengan enam medan buktinya (§7.3). Gelombang **tidak** boleh ditutup
      dengan baki `blocked` (P14-06)
- [ ] Denominator Wn sepadan **exact** dengan jadual beku F0(ii-a); sebarang perubahan wave
      disertai sebab + diff denominator + kelulusan (P14-05)

**Keseluruhan F6 (semua mesti benar serentak):**
- [ ] **473/473** langkah berstatus (G1) · **229/229** langkah tindakan diuji black-box (G3) ·
      **83/83** guide melalui kitaran penuh (G4) — diassert oleh job agregator
      `guidance-e2e-gate` daripada gabungan tiga shard (F0(iv)), bukan oleh kaunter setempat.
      **Kaedah: perbandingan SET terhadap manifest** (`hilang`/`lebihan`/`bertindih` mesti kosong,
      dengan ID disenaraikan) — kesamaan bilangan sahaja **tidak diterima** (P16-02)
- [ ] **`blocked` = 0** merentas kesemua 473 langkah — **syarat keluaran** (P14-06)
- [ ] `risk-accepted` disenaraikan berasingan, setiap satu dengan fallback **diuji**, tiket,
      pemilik dan tarikh luput yang **belum** lepas
- [ ] `action_steps_with_generic_target`: **200 → 0**, atau setiap baki `risk-accepted` dengan
      ID guide + indeks langkah + sebab + tarikh (G5)
- [ ] Placeholder `Langkah N`: **258 → 0** merentas ketiga-tiga wave (**W0 10 + W1 140 + W5 108**),
      atau baki disenaraikan per-langkah
- [ ] Popover mobile: **6 → 0** (kesemua enam; ditutup pada **W0** selepas F2 — §7.2)
- [ ] Kohort 25/124 diukur semula dengan skrip P11 dan dilaporkan bersama katalog penuh (§9.3)

**Risiko:** atribut pada elemen vendor berubah selepas naik taraf Filament → penjaga sasaran-yatim
+ e2e resolusi menangkap. Gelombang **kemudian** boleh ditangguh tanpa merosakkan yang terdahulu
(berperingkat sengaja), **tetapi F6 tidak boleh diisytihar selesai** sebelum kelima-lima lapis
gate dipenuhi pada denominator penuh (C02 + P12-03) — penangguhan direkod sebagai
`risk-accepted`/`generic-justified` **per-langkah dan bertarikh**, bukan kesenyapan dan bukan
pada aras guide. **`blocked` bukan bentuk penangguhan yang sah** (P14-06): ia menyekat keluaran
sehingga dinaikkan taraf atau diterima secara rasmi oleh pemilik.

---

## 8. FASA F7 — Kebolehcapaian & baki kecil

**Menutup:** RR-04-01 (3 isu axe) + RR-08-05 · **Keutamaan:** #7

### 8.1 `link-name` — pautan kosong kolum Duplikat (serious)

**Punca:** `InboxTable.php:66-69` — `TextColumn::make('duplikat')->state(fn ($r) => ... ? '⚠' : '')`;
Filament membungkus setiap sel dengan `<a href=recordUrl>`; state `''` → `<a>` tanpa nama.

**Pilihan (keputusan diubah P2 — IconColumn DITOLAK sebagai pilihan utama):**
- (b) ~~IconColumn~~ — **DITOLAK**: ikon tanpa teks tidak menghasilkan nama boleh-akses bagi
  `<a>` pembungkus, jadi ia tidak menyelesaikan `link-name`.
  ⚠️ **Pembetulan rujukan (P11):** v1.1–v1.3 menyandarkan penolakan ini pada dakwaan "ikon
  Filament dirender `aria-hidden` — `IconColumn.php:92-97,215-220` disahkan P2". Dakwaan itu
  **tidak dapat disahkan semula** terhadap vendor terpasang (Filament 4.11.8):
  `grep -n "aria-hidden" vendor/filament/tables/src/Columns/IconColumn.php` (fail 359 baris)
  → **0 padanan**, dan tiada view `icon-column.blade.php` yang mengandunginya. Penolakan (b)
  **kekal sah** kerana ia berdiri atas sebab yang lebih asas (ikon = tiada teks = tiada nama
  boleh-akses melainkan `aria-label` ditambah), tetapi **rujukan baris itu tidak boleh dibawa
  ke fasa pelaksanaan sebagai fakta**. Dicatat di sini, bukan dipadam senyap, supaya P12 boleh
  mengesahkan atau menolak pembetulan ini.
- (c) ~~teks bermakna sahaja~~ — **bukan lagi pilihan utama** (lihat di bawah); dikekalkan
  sebagai laluan jatuh.

**Keputusan muktamad — `disabledClick()` (C16 + P12-07; menggantikan (c) v1.3):**

Kolum ini **bukan tindakan** — ia penunjuk status. Punca `link-name` ialah kewujudan `<a>` itu
sendiri, bukan teksnya. Vendor menentukan pembungkus sel di
`vendor/filament/tables/resources/views/index.blade.php:2233-2237`:

```php
$columnWrapperTag = match (true) {
    ($columnUrl || ($recordUrl && $columnAction === null))
        && (! $columnHasStateBasedUrls) && (! $isColumnClickDisabled) => 'a',
    …
    default => 'div',
};
```

`$isColumnClickDisabled` dibaca daripada `Column::isClickDisabled()`
(`vendor/filament/tables/src/Columns/Concerns/CanBeDisabled.php:47`), yang ditetapkan oleh
**`disabledClick()`** pada **baris 20**.

⚠️ **API muktamad (P12-07): `disabledClick()` — BUKAN `disableClick()`.** v1.4 menulis
`disableClick()` (6 tempat). Vendor terpasang menunjukkan bahawa ia ialah **alias `@deprecated`**:

```php
// vendor/filament/tables/src/Columns/Concerns/CanBeDisabled.php
public function disabledClick(bool | Closure $condition = true): static   // :20  ← API semasa
/** @deprecated Use `disabledClick()` instead. */
public function disableClick(bool | Closure $condition = true): static    // :30  ← alias lapuk
```

**Kontrak pelaksanaan (panggilan API sebenar, bukan penerangan):**

```php
TextColumn::make('duplikat')
    ->label('Duplikat')
    ->disabledClick()                       // sel dirender <div>, bukan <a>
    ->state(fn ($record) => app(InboxIngestService::class)->isFlaggedDuplicate($record)
        ? 'Duplikat dikesan'
        : 'Tiada duplikat')
    ->tooltip('Padanan SHA-256 dengan dokumen lain dalam masjid yang sama')
```

Maka sel menjadi `<div>` — **tiada `<a>` langsung** — jadi `link-name` mustahil gagal pada kolum
itu, tanpa bergantung pada teks pengganti. **Kontrak "klik sel membawa ke halaman rekod" DIBUANG
daripada pelan** (P12-07): kolum ini penunjuk status, bukan tindakan; navigasi rekod kekal
melalui sel lain dan `ViewAction`. Kedua-dua state membawa teks BM yang bermakna supaya pembaca
skrin membaca sesuatu dalam **setiap** keadaan, bukan sel kosong.

**Ditolak: kiraan duplikat (`"2 duplikat"`).** `InboxTable.php:66-69` sudah memanggil
`app(InboxIngestService::class)->isFlaggedDuplicate($record)` **setiap baris**; menukarnya kepada
kiraan menambah satu query agregat per baris (**N+1**) pada jadual yang paling kerap dibuka.
Nilai a11y tambahannya kecil berbanding kosnya.

*Nota destinasi (bukti sokongan):* `PUSINGAN-04-CODEX.md:59` merekod `<a>` kosong menuju
**`/app/mam/peti-masuk/4`** — iaitu **rekod baris itu**, bukan duplikatnya. Ini mengesahkan
bahawa teks seperti "Buka padanan duplikat" akan menamakan destinasi yang salah;
`disabledClick()` mengelak masalah itu sepenuhnya.

**Gate HTML + a11y (wajib, P12-07):** (i) sel dirender `<div>`, dan assert **eksplisit** bahawa
ia **bukan `<a>`**; (ii) kedua-dua state mempunyai teks boleh akses yang dibaca pembaca skrin
(accessible name kolum + kandungan); (iii) **susun dan tapis jadual tidak rosak** selepas
`disabledClick()` (kolum ini `->state()` dikira, jadi ujian mesti membuktikan interaksi jadual
lain kekal berfungsi); (iv) larian axe sebenar pada fixture **DENGAN dan TANPA** baris duplikat
(P2/P6: data seeded berubah selepas ujian tulis — sediakan kedua-dua keadaan secara eksplisit),
`link-name` = **0** pada kedua-duanya; (v) baris masih boleh dibuka melalui sel lain /
`ViewAction` (regresi navigasi). Jika `disabledClick()` didapati mengganggu susunan/penapisan
semasa pelaksanaan → jatuh ke (c) teks bermakna sahaja, dan sebabnya dicatat.

### 8.2 `landmark-unique` — `.fi-topbar` (moderate)

`nav.fi-topbar` + `nav` sidebar tanpa nama unik pada semua halaman. Pilihan tanpa publish view
vendor (elak beban selenggara): JS kecil yang menetapkan `aria-label` pada kedua-dua `nav`,
idempotent, pada `DOMContentLoaded` + `livewire:navigated`.

**Lokasi pemuatan — dibetulkan (C18, TERIMA SEBAHAGIAN):**
- *Premis Codex yang tidak tepat:* "jangan bergantung pada `help.js` (mungkin tidak dimuat)".
  Semakan menunjukkan `help.js` **memang sentiasa dimuat** pada kedua-dua panel —
  `AppPanelProvider.php:52-55` dan `AdminPanelProvider.php:50` mendaftarkan
  `PanelsRenderHook::SCRIPTS_AFTER → view('filament.help-assets')` **tanpa syarat**, dan
  `help-assets.blade.php` hanyalah `@vite('resources/js/help.js')`. Bandingkan dengan
  `help-launcher.blade.php` yang **memang** berpagar `@if (config('diwan.guidance.enabled'))` —
  jadi butang boleh hilang sedangkan skrip kekal.
- *Prinsip Codex yang DITERIMA:* walaupun begitu, **menyandarkan pembaikan a11y pada runtime
  panduan tetap salah dari segi reka bentuk** — kerana (a) `DIWAN_GUIDANCE_ENABLED=false`
  ialah suis yang wujud dan boleh diperluas kelak untuk menggugurkan aset itu sendiri;
  (b) sebarang `throw` awal dalam `help.js` (cth. import `driver.js` gagal) akan
  **mendiamkan label landmark** bersama-sama; (c) kebolehcapaian bukan ciri pilihan.
- **Keputusan v1.4:** letakkan kod dalam **entri berasingan `resources/js/a11y-landmarks.js`**
  (± 6 baris, tiada import), didaftarkan melalui render hook `SCRIPTS_AFTER` tersendiri pada
  kedua-dua panel. Idempotent (semak `hasAttribute('aria-label')` dahulu), jalan pada
  `DOMContentLoaded` + `livewire:navigated`.
- **Ujian:** larian dengan **`DIWAN_GUIDANCE_ENABLED=false`** → kedua-dua `nav` masih
  mempunyai `aria-label` unik dan axe `landmark-unique` = 0. Ini ujian yang membuktikan
  pemisahan itu benar-benar berlaku.
- Had diakui (kekal): pembaikan JS bukan server-side — axe dijalankan atas DOM hidup, jadi ia
  lulus; alternatif "betul" (PR upstream Filament) di luar skop.

### 8.3 `empty-table-header` — header tindakan Rekod (minor)

Codex P6: `.fi-ta-actions-header-cell` kosong walaupun `aria-label` wujud → axe `empty-table-header`
menuntut **teks** atau `aria-hidden`. **Spike DIBUANG (P2 — API disahkan wujud):**
`$table->recordActionsColumnLabel('Tindakan')` — vendor `HasRecordActions.php:76-80`
(`actionsColumnLabel()` ialah alias *deprecated* baris 162-168 — jangan guna). Terapkan pada
`RecordsTable` dan, untuk konsistensi, semua jadual utama tenant/admin (senarai penuh semasa
pelaksanaan — semua `Tables\*Table.php`). Sahkan HTML + axe selepas.

### 8.4 Butang viewer PDF — RR-08-05

`document-viewer.js` — dalam `renderPage()` selepas `pageNumber` dikira dan dalam pemuatan awal:
```js
prevBtn.disabled = pageNumber <= 1;
nextBtn.disabled = pageNumber >= pdf.numPages;      // dokumen 1 halaman → kedua-dua disabled
zoomOutBtn.disabled = scale <= 0.5;  zoomInBtn.disabled = scale >= 3;   // dalam updateZoom()
```
Butiran (P2): rujukan elemen diangkat ke pemboleh ubah; **`aria-disabled` sentiasa seiring
sifat `disabled` native** (jangan salah satu sahaja); liputi juga keadaan **loading**
(butang disabled sehingga `pdf` termuat), **error** (kekal disabled), dan **imej bukan-PDF**
(butang halaman disembunyikan/disabled — tentukan paparan semasa pelaksanaan); had zoom guna
perbandingan float selamat (0.5/3 tepat — elak isu titik terapung dengan clamp sedia ada).

**Pembahagian kerja BAHARU vs REGRESI (C17, TERIMA SEBAHAGIAN).** Codex menuntut satu senarai
gabungan; semakan kod menunjukkan **separuh daripadanya sudah dilaksana**, jadi ia mesti diuji
sebagai penjaga regresi, **bukan ditulis semula** (menulis semula logik yang betul = risiko
tanpa faedah):

| Tuntutan C17 | Keadaan sebenar | Tindakan v1.4 |
|---|---|---|
| Set `pageInput.max` | **TIADA** — `document-viewer.blade.php:37` hanya `type="number" min="1"` | **Kerja baharu:** tambah `max` (diselaraskan `pdf.numPages` selepas muat) |
| Clamp kosong/0/negatif/bukan-nombor/>jumlah | **SUDAH ADA** — `document-viewer.js:31`: `Math.min(Math.max(Number(number) \|\| 1, 1), pdf.numPages)` (`Number('')`→0→`\|\|1`; `NaN`→`\|\|1`) | **Ujian regresi** sahaja |
| Batal render pada klik pantas | **SUDAH ADA** — `:33` `if (renderTask) renderTask.cancel()` | **Ujian regresi** sahaja |
| Enter pada medan cari | **SUDAH ADA** — `:79-80` | **Ujian regresi** sahaja |
| Disable semasa loading/error | **TIADA** | **Kerja baharu** |
| Butang halaman/zoom disabled pada had | **TIADA** (RR-08-05) | **Kerja baharu** (blok kod di atas) |
| Cari: kosong / jumpa / tidak jumpa / PDF tanpa lapisan teks | separa (gelung `:66-67` wujud; kes tepi tidak diuji) | **Ujian baharu** |
| Cetak metadata tidak membocorkan kandungan | laluan **wujud** — butang `data-print` (`:47`) + `.print-meta` yang hanya dipaparkan `@media print` (`:21-26`) manakala kanvas disembunyikan | **Ujian baharu** (assert `@media print` memaparkan metadata sahaja) |

### 8.5 Ujian & kriteria siap F7

- e2e: viewer PDF 1 halaman → prev+next `disabled`; 3 halaman → prev disabled di 1, next disabled
  di 3; zoom had; `pageInput.max === numPages`; input `''`/`0`/`-3`/`abc`/`999` → sentiasa
  halaman sah dan nilai input diselaraskan semula; keadaan loading/error → kawalan disabled;
  cari: kosong, jumpa, tidak jumpa, PDF tanpa lapisan teks, Enter.
- Cetak: `.print-meta` mengandungi metadata sahaja; kanvas dokumen tidak dicetak.
**Fail diubah F7 (dieksplisitkan — P12 §2 butir 6):**

| Fail | Perubahan |
|---|---|
| `app/Filament/App/.../InboxTable.php` | `TextColumn::make('duplikat')` → `->disabledClick()` + state BM + tooltip (§8.1) |
| `resources/js/a11y-landmarks.js` | **BAHARU** — ±6 baris, tiada import (§8.2) |
| `vite.config.js` | tambah `resources/js/a11y-landmarks.js` ke dalam array `input` (kini 5 entri: `app.css`, `app.js`, `help.js`, `document-viewer.js`, `filament/theme.css`) — **tanpa ini entri tidak dibina dan tidak muncul dalam `manifest.json`** |
| `app/Providers/Filament/AppPanelProvider.php` + `AdminPanelProvider.php` | render hook `SCRIPTS_AFTER` **tersendiri** untuk entri a11y (berasingan daripada hook `filament.help-assets` sedia ada — `AppPanelProvider.php:52-55`, `AdminPanelProvider.php:50`) |
| `resources/views/filament/*.blade.php` | view kecil `@vite('resources/js/a11y-landmarks.js')` untuk hook di atas |
| `app/Filament/**/Tables/*Table.php` | `recordActionsColumnLabel('Tindakan')` (§8.3) |
| `resources/js/document-viewer.js` + `resources/views/**/document-viewer.blade.php` | butang disabled + `pageInput.max` (§8.4) |

- **axe (C24 → dikemas P12-08 — laluan lalai kini TANPA dependensi baharu):**
  - **Laluan lalai (dipakai melainkan D5(a) diluluskan bertulis):** larian axe melalui alat
    **luaran/manual** yang **tidak mengubah `package.json` mahupun `package-lock.json`** —
    contohnya sambungan axe DevTools dalam pelayar, pada **5 halaman × 2 viewport**, dengan
    **JSON hasil + skrinsyot** disimpan dalam bukti fasa. Ini bukan laluan "kelas kedua": ia
    menghasilkan bukti yang sama bentuknya (JSON pelanggaran per-peraturan).
  - **Laluan automatik hanya selepas DUA kelulusan (§0.1(2)):** (a) pengecualian polisi bertulis
    yang menamakan `axe-core` dan mengehadkannya kepada *dev-only*, direkod dalam dokumen
    kawalan; **kemudian** (b) kelulusan menambah `devDependencies` + lockfile. Barulah
    `axe-core` boleh disuntik daripada `node_modules` dalam langkah e2e.
  - Sasaran **sama** pada kedua-dua laluan: `link-name` 0, `landmark-unique` 0,
    `empty-table-header` 0-atau-didokumen (8.3). Kriteria siap F7 **tidak** boleh disekat oleh
    keputusan dependensi.
- [ ] Suite + e2e hijau; **check `PostgreSQL, Redis, Meili, OCR and tests` (canary +
      `ci-guidance` + `ci-domain`) dan required check `guidance-e2e-gate` hijau** (F0(iv)(f)); Peti Masuk
      dengan baris duplikat & tanpa — kedua-dua render betul, sel **bukan `<a>`**, accessible
      name bermakna pada kedua-dua keadaan, susun/tapis jadual tidak rosak (C16 + P12-07)
- [ ] `landmark-unique` = 0 **juga dengan `DIWAN_GUIDANCE_ENABLED=false`** (C18)
- [ ] `manifest.json` mengandungi entri `resources/js/a11y-landmarks.js` selepas `npm run build`
      (bukti bahawa `vite.config.js` benar-benar dikemas)
- [ ] Matriks keselamatan §0.6 (S1–S6) hijau

---

## 9. FASA F8 — Audit semula & metrik penutup

**Tujuan:** buktikan dengan angka bahawa setiap penemuan ditutup — kaedah sama dengan audit asal
supaya perbandingan sah (apple-to-apple).

| Metrik | Sebelum (audit) | Sasaran selepas | Kaedah ukur |
|---|---|---|---|
| Halaman produksi kekal konteks bantuan selepas interaksi | 6/25 | **25/25** (+11/11 admin) | Skrip crawl `helpRuntime` P1/P11 |
| `helpUrl` `asal=livewire/update` | ada | **0** | sama |
| Langkah tour generik — `resolved_to_generic` **kohort** | 119/124 | perbandingan apple-to-apple sahaja (**bukan gate**); kohort ialah `tenant` → bergerak pada **W5**; sasaran akhir ≤25/124 + allowlist per-langkah | Matriks resolusi P11 (manifest F0) |
| **Sasaran generik diisytihar — KATALOG PENUH** (C02) | **443/473** | **473/473 langkah** berstatus `specific`/`generic-justified`/`not-applicable`/`blocked` (G1 §7.3); **0 langkah tanpa status** — status aras-guide TIDAK diterima (P12-03) | Analisis katalog + registry `targets.json` |
| **Langkah TINDAKAN bersasar generik** `action_steps_with_generic_target` (§7.1 — metrik keutamaan sebenar) | **200/229** (screen 140 + workflow 60) | **0/229** + allowlist per-langkah bersebab bertarikh | Analisis katalog (`wait_for_user` × sasaran) + tour black-box **229/229** (G3 §7.3) |
| **Placeholder tajuk `Langkah N` — katalog penuh** (C09) | **258/473** (tenant 118 + screen 140 → wave: **W0 10 + W1 140 + W5 108**) | **0/258**; sebarang baki disenaraikan sebagai ID guide + indeks langkah + tarikh. **Laporkan pada denominator penuh** — frasa "0 dalam family yang telah digelombangkan" dilarang (P12-03) | Skrip analisis katalog |
| **Langkah berstatus `blocked`** (P14-06) | belum diukur (status baharu) | **0/473 — syarat keluaran.** `risk-accepted` dilapor **berasingan** dengan fallback diuji + tiket + pemilik + tarikh luput | Manifest G1 + agregator `guidance-e2e-gate` |
| **Liputan gate diassert oleh agregator** (P14-02) | tiada (Playwright belum dalam CI) | **473 status · 229 tour · 83 kitaran** — union tiga shard, tanpa pertindihan/ID hilang | Job `guidance-e2e-gate` |
| **Drift dokumen akses role** (P14-03) | 8/8 role berbeza antara `AKSES-PAGE-…-2026-07-21.md:12-19` dan `guidance.spec.js:14-21` | **0** — kedua-dua dijana daripada `role_routes` F0(ii-b) | Diff manifest ↔ dokumen dijana |
| **Mismatch `role_routes`** (P16-07) | belum diukur (manifest belum wujud) | **0** pada ketiga-tiga pasangan: `expected_access` ↔ `declared_access` ↔ `actual_status`. Nilai probe **tidak pernah** menulis semula expected | Validator `scripts/audit/validate-plan-manifest.mjs` + probe `diwan:role-routes` |
| **Suite domain dalam gate CI** (P16-08) | 0/3 (`office-workflow`, `ddms-extended`, `ocr-upload` di luar CI) | **2/3 wajib hijau** — project `ci-domain` sebagai **step** dalam job `integration` (bukan required check sendiri, P18-01) sebelum deploy F8; `ci-ocr` masuk **selepas** fixture dikomit, dengan assert "tidak di-skip" | `gh run list --json conclusion` + **artifak JSON per project** (artifak `ci-playwright-json` → `storage/app/plan-ci/ci-domain.json`, `…/ci-ocr.json`) disemak oleh `scripts/audit/assert-playwright-json.mjs` (§1 F0(iv)(e)) — bukan `results.json` generik, yang **tidak pernah dijana** oleh `reporter: [['line']]` (P18-05) |
| **Gate antivirus intake fail-closed** (P18-04) | **0 ujian** — `grep -rn "AntivirusScanner" tests/` = `GuidanceSupportTest` sahaja (lampiran tiket, bukan intake); `DdmsExtendedCapabilitiesTest.php:149-155` hanya menguji `disabled` | **3/3 status ditolak** (`infected` · `unavailable` · `error`) dengan **0** `Record`, **0** media, **0** log aktiviti dan **0** kesan pada tenant lain | `tests/Feature/InboxAntivirusFailClosedTest.php` (D11 #14) dalam `php artisan test`; §0.6 S7 |
| Tajuk = penerangan (kohort) | 77/124 | **0** | Skrip analisis katalog P12 |
| Tajuk terpotong tengah perkataan (kohort) | 20/124 | **0** | sama |
| CTA "Buat pada skrin" pada langkah tanpa tindakan | 20 | **0** | Matriks P11 + e2e |
| Popover mobile menutup tengah | 6 langkah — exact: `tenant.pelupusan#1`, `tenant.kegemaran#1–5` (`bukti/pusingan-11-codex/production-mobile-all-tour-steps.json`, `centerCovered=true`) | **0/6** (ditutup pada **W0** sejurus selepas F2 — §7.2; **bukan lagi W5**, P14-05) | Matriks mobile P11, desktop **dan** mobile |
| Tour `/log-masuk` | ralat palsu 100% | **lulus** | e2e + manual |
| EN-leak permukaan UI (Edit/Previous/Next »/validation) | ≥5 kelas | **0** | Crawl enLeak P1 — **termasuk `/admin/tetapan-platform` + `/app/{slug}/tetapan-masjid`** (C10) |
| E-mel kerangka EN | 9 diuji audit | **0/18** kelas ber-`toMail()` (C19) | Ujian render data-provider 18 entri + e-mel sebenar |
| Wizard label | `Seterus` | `Seterusnya` | e2e |
| Default borang retensi | `auto_padam` | `semak` + dialog | e2e/manual |
| axe serious | 1 (`link-name`) | **0** | axe 5 halaman × 2 viewport |
| Suite Pest | 409✓/1s | semua✓ (± ujian baharu ±40) | `php artisan test` |
| `diwan:smoke` produksi | 9/9 | 9/9 | SSH |

### 9.1 Matriks produksi penuh — 20 BrowserContext terasing (C08)

Spot-check v1.3 **tidak mencukupi** sebagai bukti penutup. Aritmetik matriks (disahkan
`config/roles.php:22-23` → 8 peranan): **8 peranan tenant** (`admin_masjid`, `pengerusi`,
`setiausaha`, `bendahari`, `nazir`, `ketua_imam`, `ajk`, `audit`) **+ superadmin + awam =
10 identiti × 2 viewport (desktop / mobile) = 20 BrowserContext**.

**Premis yang dibetulkan (C08 → TERIMA SEBAHAGIAN): matriks ini BUKAN kerja baharu — ia sudah
wujud dan sudah lulus.** `e2e/guidance.spec.js:124` ialah ujian *"Chrome berasingan untuk superadmin,
lapan role dan public pada desktop serta mobile"*, dengan **`expect(contextKeys.size).toBe(20)`**
(baris 213), `browser.newContext()` + `context.close()` per identiti, `monitorBrowserErrors`,
`assertNoHorizontalPageOverflow`, kiraan halaman sidebar per role
(`expect(navigation.length).toBe(account.pages)`), probe silang-tenant
`expect(crossTenant?.status()).toBe(404)`, dan `assertFloatingHelpLauncher`.

**Pembetulan kedua (P12-05): "sudah lulus" ≠ "sudah membuktikan apa yang F8 perlu buktikan".**
Dua fakta kod menghadkan nilai matriks sedia ada sebagai bukti penutup:

- **Mobile bukan page-by-page.** `guidance.spec.js:156-157` dan `:183-191` mengehadkan lawatan
  route kepada `viewport.name === 'desktop'` sahaja; pada mobile, inventori merekod
  `navigation.length || 1` (`:170`, `:206`) — iaitu **satu** halaman (`/bantuan`). Jadi
  "20 konteks" benar, tetapi "liputan setiap halaman pada desktop **dan** mobile" **tidak** benar.
- **Penemuan route bergantung sidebar desktop.** `visibleNavigation()` (`:62-70`) membaca
  `.fi-sidebar a[href]` yang kelihatan — pada mobile sidebar tertutup, jadi kaedah ini secara
  struktur tidak boleh melengkapkan liputan mobile.

*(Nota ketepatan terhadap P12: audit P12 menyatakan §9.1 mendakwa `production-readonly.spec.js`
ialah harness 20 konteks. Dakwaan itu **tidak wujud** dalam v1.4 — §9.1 memetik
`guidance.spec.js:124` dengan betul, dan `production-readonly.spec.js` tidak disebut langsung
dalam pelan. Substansi P12-05 tetap **diterima sepenuhnya**; hanya rujukan itu dibetulkan.
Pemerhatian P12 tentang `production-readonly.spec.js` sendiri **disahkan tepat**: `:66-138`
membuka satu context per akaun, satu viewport, tanpa public/superadmin, mengassert
`toBe(accounts.length)` dan **bukan** 20 — dan jarak log masuk lalainya ialah **0 ms**
(`:71`, `?? 0`), berbeza daripada `guidance.spec.js:10` yang lalainya 15 000 ms. Menggunakan
spec itu terhadap produksi akan kena throttle 429 pada had 5/min.)*

Maka kerja F8 ialah **"ekstrak spec produksi read-only khusus daripada matriks sedia ada +
tutup lapan jurangnya"**, bukan "bina matriks dari kosong" dan bukan juga "jalankan
`guidance.spec.js` seadanya terhadap produksi":

| # | Jurang disahkan | Tindakan F8 |
|---|---|---|
| 1 | **Tiada liputan tour** — `disableAutomaticGuides()` (baris 31-35) mematikan tour untuk **semua** konteks | Tambah **satu tour per role × viewport** pada guide halaman utama role itu |
| 2 | Carian bantuan hanya assert satu `.diwan-help-result` kelihatan (baris 194) | 3 pertanyaan (tepat / salah ejaan / istilah DDMS) + assert tapisan role (§9.2) |
| 3 | Inventori hanya `console.log(JSON.stringify(...))` | Tulis artifak berstruktur **`Audit Review Round Robin/bukti/plan-f8/route-manifest.json`** (laluan penuh dibekukan v1.9 — §1 F0(iv)(g); ia bukti audit yang **dikomit**, bukan output CI transient) + hasil per role/viewport |
| 4 | **`ensureInboxFixture` (baris 95-111) memuat naik dokumen sebenar dan TIADA pembersihan** (carian padam/`afterAll` = 0 padanan) — pada produksi ini meninggalkan rekod | Spec produksi **mesti read-only**: `ensureInboxFixture` dan setiap laluan mutasi **tidak** dimasukkan. Jika mana-mana fixture tetap perlu, pembersihan wajib + ID dicatat dalam laporan (pengajaran RR-11-01) |
| 5 | Larian terhadap produksi belum pernah dijalankan sebagai gate | **DIKEMAS P14-04:** kontrak runner penuh (nama spec, wrapper, command exact, `run_uuid`, slug unik, inventori, cleanup idempotent) dibekukan dalam **§9.1a** — bukan "command tepat direkod kelak". ⚠️ Tenant `smoke` **BUKAN** fixture sementara (lihat §9.1a) |
| **6** | **Mobile bukan page-by-page** (`:157`, `:183`, `:170`, `:206`) | Lawati **semua halaman yang dibenarkan pada desktop DAN mobile** menggunakan manifest **`role_routes`** F0(ii-b) — bukan sidebar desktop, dan bukan manifest bantuan (`catalogue` menjawab soalan berbeza — P14-03) |
| **7** | **Set role tidak diassert** — `E2E_PROD_ROLE_ACCOUNTS` (`:23-25`) menggantikan `localTenantRoles` sepenuhnya tanpa semakan | Assert **tepat lapan** role unik dan setnya **tepat** `admin_masjid, pengerusi, setiausaha, bendahari, nazir, ketua_imam, ajk, audit`; assert kredensial superadmin berasingan hadir; assert public **tidak** log masuk |
| **8** | Kiraan konteks tidak dipakukan pada spec produksi | Cipta **tepat 20** context dan assert inventori **tepat 20** sebelum lulus (`guidance.spec.js:214` sudah betul — spec produksi mesti mewarisinya, bukan `toBe(accounts.length)`) |

Setiap route dalam matriks produksi mesti mengassert: **status 200** · `<main>`/landmark betul ·
**tiada** console/page error · **tiada** overflow mendatar · bantuan/carian/tour minimum
berfungsi. Setiap role tenant turut menjalankan probe **silang-tenant 404**.

Peraturan pelaksanaan yang kekal:
- **Konteks berasingan** — cookie dan `localStorage` **tidak** dikongsi (penting:
  `diwan-help-seen` mempengaruhi auto-tour, jadi konteks dikongsi akan menyembunyikan pepijat).
- **Log masuk dijarakkan ≥15 saat, secara global merentas kesemua 20 konteks.** Sebab konkrit:
  `DIWAN_LOGIN_RATE_LIMIT` lalai **5/min** pada produksi (CI menaikkannya ke 100) — 20 log masuk
  berturut-turut akan kena throttle 429 dan menghasilkan kegagalan palsu. `guidance.spec.js:10,37-40`
  sudah melaksanakan corak ini (`waitForLoginSlot`, lalai 15 000 ms); spec produksi mesti
  mewarisinya, **bukan** corak `?? 0` dalam `production-readonly.spec.js:71`.
- **Telemetri diisytihar dahulu**, dikira selepas (pengajaran RR-11-01, lihat §9.3).
- **Selepas larian:** cleanup mengikut **§9.1a** — **hanya ID yang dicipta oleh `run_uuid`
  larian itu**. Arahan v1.5 "bersihkan akaun/tenant fixture `smoke`" **DIBATALKAN** (P14-04):
  ia akan memusnahkan gate deploy sendiri (lihat §9.1a).

### 9.1a Kontrak runner produksi — nama, command, `run_uuid`, cleanup (P14-04)

⚠️ **Bahaya yang mesti dinamakan dahulu: slug `smoke` BUKAN fixture terpakai-buang.**

| Bukti | Maksud |
|---|---|
| `app/Console/Commands/SmokeE2E.php:33` — `protected $signature = 'diwan:smoke {--slug=smoke}'` | Tenant `smoke` ialah **lalai** gate smoke E2E |
| `SmokeE2E.php:50` — `Mosque::query()->updateOrCreate(['slug' => $slug], …)` | Ia **dikekalkan**, bukan dicipta-dan-dibuang |
| `SmokeE2E.php:56,67-68` — `User::updateOrCreate(['email' => "admin-{$slug}@smoke.test"])` + `MembershipService::invite(...)` | Akaun `*-smoke@smoke.test` ialah sebahagian tenant itu |
| §10 langkah 5 — `diwan:smoke` **9/9** ialah gate setiap deploy | Memadamnya = memusnahkan gate deploy |
| `e2e/production-readonly.spec.js:28` — `process.env.E2E_PROD_TENANT ?? 'smoke'` | Spec lain berlalai kepadanya |
| `bukti/pusingan-11-codex/production-mobile-all-tour-steps.json` — `route: "/app/smoke"` | Audit produksi juga berjalan padanya |

**Maka: "padam berdasarkan slug `smoke`" DILARANG.** Cleanup hanya menyentuh ID yang dicipta
oleh larian semasa.

**Kontrak yang dibekukan sekarang (bukan "direkod dalam bukti fasa" kelak):**

| Perkara | Nilai beku |
|---|---|
| Nama spec | **`e2e/production-guidance-readonly.spec.js`** |
| Wrapper | **`scripts/audit/run-production-guidance-readonly.ps1`** (satu-satunya titik masuk) |
| Command exact | `pwsh -File scripts/audit/run-production-guidance-readonly.ps1 -RunUuid <uuid> -BaseUrl https://bakwim.my` — **`-RunUuid` adalah pilihan**; jika ditinggalkan wrapper menjananya dan mencetaknya (peraturan 2 di bawah). Recovery: tambah `-CleanupOnly` (memerlukan `-RunUuid`) |
| Playwright | `workers=1` (dipaksa wrapper, bukan bergantung `playwright.config.js:5`) |
| Tenant larian | **`smoke-<run_uuid>`** — dicipta khusus, tidak pernah `smoke` |
| Jarak log masuk | corak `guidance.spec.js:10,37-40` (lalai **15 000 ms**), **bukan** `production-readonly.spec.js:71` (`?? 0` → 429 pada had 5/min) |
| Konteks | **tepat 20** (`toBe(20)`, bukan `toBe(accounts.length)`) |
| Skop spec pelayar | **read-only mutlak** — `ensureInboxFixture` (`guidance.spec.js:95-111`) dan setiap laluan mutasi **tiada** dalam fail ini |
| Setup/cleanup | command pentadbiran **berasingan** (**`diwan:audit-fixture`**, di bawah), bukan dalam spec pelayar; keduanya diaudit |

**Command pentadbiran — DINAMAKAN v1.7 (P16-04).** v1.6 hanya berkata "command pentadbiran
berasingan"; tanpa nama, argumen dan sempadan kebenaran, ia bukan kontrak yang boleh diaudit.

| Perkara | Nilai beku |
|---|---|
| Fail | **`app/Console/Commands/AuditFixture.php`** (D11 fail #2) |
| Signature | `diwan:audit-fixture {action : prepare\|cleanup\|inventory} {--run= : UUID larian (WAJIB)} {--json= : laluan fail output} {--force}` |
| `prepare` | Cipta tenant `smoke-<run_uuid>` + **8 akaun role** `<role>-<run_uuid>@smoke.test`, tulis inventori `created`. **Superadmin TIDAK dicipta dan TIDAK ditulis** (P18-03, di bawah); `public` tiada akaun. Matriks 20 konteks tetap **10 identiti** = 8 role + superadmin (luaran) + public (tanpa akaun) |
| `cleanup` | Padam **hanya** ID dalam fail inventori larian itu; **idempotent** (larian kedua = 0 perubahan, exit 0) |
| `inventory` | Kira `before`/`after` tanpa mengubah apa-apa (read-only) |
| Authorization | Command konsol sahaja — **tiada** endpoint HTTP. Ia menolak berjalan jika `--run` kosong atau tidak sepadan corak UUIDv4, dan **menolak** sebarang slug yang tidak berawalan `smoke-` (penjaga keras terhadap tenant sebenar dan terhadap slug `smoke` gate deploy) |
| Kata laluan akaun | Dijana rawak setiap larian (bukan `password`), **tidak pernah** dicetak ke stdout |
| Output rahsia | Ditulis **hanya** ke fail `--json` (lihat peraturan 3 di bawah); stdout memaparkan **bilangan + nama akaun tersanitasi + ID**, tidak pernah kata laluan |
| **Superadmin (dibekukan v1.8, P18-03)** | **Di luar skop command sepenuhnya.** `prepare` **tidak** mencipta superadmin, **tidak** menetapkan semula kata laluannya, dan **tidak** menulis `E2E_PROD_SUPERADMIN_*` ke mana-mana fail. Ia **hanya** mengesahkan (dalam `inventory`) bahawa **tepat satu** akaun superadmin yang dijangka wujud, dan melaporkan **e-melnya sahaja** |

**Peraturan wrapper:**
1. **Validasi env tanpa mencetak rahsia** — sahkan setiap pemboleh ubah `E2E_PROD_*` yang
   diperlukan hadir; jika tiada, gagal dengan **nama** pemboleh ubah sahaja, **jangan** cetak
   nilainya (kata laluan produksi tidak boleh masuk log CI/terminal).
   **⚠️ Senarai wajib dibekukan v1.8 (P18-03):** `E2E_PROD_SUPERADMIN_EMAIL` dan
   `E2E_PROD_SUPERADMIN_PASSWORD` **mesti** dibekalkan dari luar dan **mesti** disemak hadir +
   tidak kosong sebelum Playwright dilancarkan. Sebabnya bukan gaya: `e2e/guidance.spec.js:27-28`
   mempunyai **lalai diam** —
   `process.env.E2E_PROD_SUPERADMIN_EMAIL ?? 'superadmin@diwan.test'` dan
   `?? defaultPassword` (yang sendiri berlalai `'password'`, `:6`). Wrapper yang tidak menyemak
   akan menghantar **kredensial demo ke borang log masuk produksi**: cubaan gagal berulang, had
   kadar 5/min (`config/diwan.php:41` — `login_rate_limit`) tercetus, dan larian kelihatan seperti pepijat UI
   sedangkan ia konfigurasi hilang. Wrapper **mesti** gagal dengan mesej yang menamakan kedua-dua
   pemboleh ubah itu.
2. **Kontrak `RunUuid` — DISATUKAN v1.7 (P16-04).** v1.6 bercanggah dengan dirinya: jadual
   command mewajibkan `-RunUuid <uuid>` daripada pemanggil manakala peraturan wrapper berkata
   UUID "dijana sekali di awal". Satu peraturan menggantikan kedua-duanya:
   - **Jika `-RunUuid` diberi** → nilai itu digunakan **apa adanya** (inilah laluan untuk
     reproducibility, recovery dan `-CleanupOnly`). Wrapper mengesahkan formatnya (UUIDv4) dan
     **gagal** jika ia tidak sah — ia tidak "membetulkan" senyap;
   - **Jika tidak diberi** → wrapper menjananya sendiri (`[guid]::NewGuid()`);
   - **Dalam kedua-dua kes, nilai sebenar SENTIASA direkod** sebagai baris pertama log larian,
     dalam nama ketiga-tiga fail inventori, dan dalam laporan bukti fasa. Tiada larian tanpa
     `run_uuid` yang tercatat. Semua nama diterbitkan daripadanya: `smoke-<run_uuid>`,
     `<role>-<run_uuid>@smoke.test`, `Audit Review Round Robin/bukti/plan-f8/<run_uuid>/…`
     (§1 F0(iv)(g)).
3. **Rahsia dalam fail private sementara sahaja — DUA SUMBER, DIPISAHKAN v1.8 (P18-03).**

   ⚠️ **Percanggahan yang dibetulkan.** v1.7 menyatakan pada satu tempat bahawa "superadmin sedia
   ada dirujuk, **tidak** dicipta semula", dan pada tempat lain bahawa `prepare` **menulis**
   `E2E_PROD_SUPERADMIN_*` ke fail rahsia. Kedua-duanya tidak boleh benar: kata laluan disimpan
   sebagai **hash** (`bcrypt`/`argon`), jadi tiada command boleh memulihkan plaintext akaun sedia
   ada. Satu-satunya cara `prepare` boleh "menulis" kredensial itu ialah dengan **menetapkan
   semula kata laluan superadmin produksi** — iaitu mutasi kepada akaun paling berkuasa dalam
   sistem live, dalam larian yang seluruh §9.1a isytiharkan **read-only**.

   **Pilihan A (P18-03) diambil; Pilihan B ditolak.** Pilihan B (`prepare` mencipta
   `superadmin-<run_uuid>@smoke.test` sementara) ditolak kerana ia mencipta **identiti akses-penuh
   silang-tenant baharu pada produksi** yang, jika cleanup gagal (rangkaian putus, Ctrl-C sebelum
   `finally`, exit paksa), kekal hidup dengan kata laluan yang ditulis ke fail — persis kategori
   kesilapan RR-11-01 (audit meninggalkan 21 token produksi). Fixture role tenant tidak membawa
   risiko yang sama kerana ia berskop tenant terpakai-buang.

   | Kredensial | Sumber | Ditulis oleh `prepare`? |
   |---|---|---|
   | 8 akaun role (`E2E_PROD_ROLE_ACCOUNTS`) | **Dicipta** oleh `prepare` dalam tenant `smoke-<run_uuid>`; kata laluan rawak per larian | **Ya** — ke fail rahsia sementara sahaja |
   | `E2E_PROD_SUPERADMIN_EMAIL` / `_PASSWORD` | **Dibekalkan pemilik dari luar** (pengurus kata laluan / `$env:` sesi pentadbir) | **Tidak pernah** — wrapper hanya **mengesahkan hadir** dan menyalurkannya ke proses anak |

   `prepare` menulis kredensial role (bentuk JSON yang `guidance.spec.js:23-25` jangka)
   ke satu fail sementara di luar repo dengan **ACL ketat**
   (`icacls <fail> /inheritance:r /grant:r "$env:USERNAME:(R)"` pada Windows; `chmod 600` pada
   Linux). Wrapper memuatkannya ke pemboleh ubah persekitaran proses anak sahaja, **tidak pernah**
   ke `$env:` global, `Write-Host`, transcript atau artifak. Fail itu dipadam dalam blok
   `finally` yang sama dengan cleanup — **walaupun larian gagal**.
   Kredensial superadmin **tidak masuk fail ini**: wrapper menyalurkan
   `E2E_PROD_SUPERADMIN_EMAIL`/`_PASSWORD` terus daripada persekitaran pemanggil ke persekitaran
   proses anak Playwright, jadi ia tidak pernah disentuh cakera. **Ujian penjaga (D11 fail #11):**
   `AuditFixtureCommandTest` mengassert output `prepare` **tidak** mengandungi substring
   `SUPERADMIN` dan tiada baris `users` bertanda superadmin dalam inventori `created`.
4. **Inventori tiga fasa** ditulis sebagai JSON:
   `before` → `created` → `after`, meliputi **`mosques`, `users`, `login_tokens`, `help_events`,
   `guidance_progress`**. `after` mesti sepadan `before` selepas cleanup (delta = 0) **kecuali**
   telemetri yang sengaja diisytihar (§9.3) — dan deltanya dilapor, bukan disembunyikan.
5. **Cleanup `try/finally`** — struktur wrapper ialah
   `try { prepare; playwright } finally { diwan:audit-fixture cleanup --run=<uuid>; padam fail
   rahsia }`, jadi ia berjalan walaupun Chrome/proses terhenti di tengah jalan atau pengguna
   menekan Ctrl-C. Ia memadam **hanya** rekod yang `created` senaraikan (dipadan `run_uuid`),
   tidak pernah melalui pertanyaan corak slug/e-mel umum.
6. **Idempotent + boleh pulih.** Menjalankan cleanup dua kali = tiada kesan tambahan (exit 0,
   0 baris dipadam). Jika larian terputus,
   `pwsh -File scripts/audit/run-production-guidance-readonly.ps1 -RunUuid <uuid> -CleanupOnly`
   membersihkan larian lama daripada fail inventorinya **tanpa** menjalankan `prepare` atau
   Playwright. `-CleanupOnly` **memerlukan** `-RunUuid` (tiada tekaan; wrapper gagal jika tiada).
   **Ujian idempotensi wajib** (`tests/Feature/AuditFixtureCommandTest.php`, D11 fail #11):
   prepare → cleanup → cleanup = keadaan pangkalan data identik dengan sebelum prepare, **dan**
   tenant `smoke` + akaun `*-smoke@smoke.test` **tidak tersentuh** dalam mana-mana laluan.
7. **Bukti fasa** menyimpan: command yang dijalankan (dengan rahsia ditapis), `run_uuid`,
   ketiga-tiga fail inventori, dan senarai artifak yang **sengaja** ditinggalkan (jika ada).

### 9.2 Gate carian bantuan — Meilisearch DAN fallback PHP (C20)

Dua laluan berbeza mesti digate **berasingan** kerana ia gagal secara berbeza:

| Laluan | Gate |
|---|---|
| **Meilisearch** | Indeks mengandungi **tepat 83 dokumen** (`SyncHelpIndex` memetakan `$catalog->raw()['guides']`); **tiada** data tenant/pengguna dalam dokumen; query biasa, query salah ejaan (typo-tolerance) dan query akronim (`DDMS`) memulangkan hasil |
| **Fallback PHP** | Meili mati/timeout → carian masih berfungsi (`SyncHelpIndex` sendiri memberi amaran "fallback PHP kekal aktif" bila `scout.driver !== meilisearch`); hasil setara untuk query mudah |
| **Kedua-duanya** | Hasil ditapis ikut role/panel/permission; **query mentah tidak disimpan**; awam tidak nampak guide tenant; tenant A tidak nampak konteks tenant B |

**Titik integrasi CI — DIBEKUKAN v1.9 (P20-02).** Rumusan v1.8 (*"gate Meili diletakkan pada
langkah `Runtime compatibility smoke`"*) **dibatalkan**: langkah itu (`ci.yml:134-148`) menjalankan
Horizon + `diwan:health` + `diwan:staging-check` dan **tidak** menyegerakkan indeks bantuan
langsung, jadi ia bukan gate C20. Gate sebenar ialah **step berasingan** `Meilisearch help index
gate` (§1 F0(iv)(d-1)) yang menjalankan
`SCOUT_DRIVER=meilisearch php artisan diwan:sync-help-index --delete` dan mengassert output
`83 guide disegerakkan ke indeks diwan_help_guides.`, **sebelum** server e2e dilancar dan
sebelum sebarang spec carian.

| Fakta kod | Kesan |
|---|---|
| `SyncHelpIndex.php:38-42` — pulang `SUCCESS` awal jika `config('scout.driver') !== 'meilisearch'` | step sedia ada `Validate help catalog` (`ci.yml:123-126`, `SCOUT_DRIVER: collection`) mengesahkan **katalog sahaja**; ia **tidak pernah** menyentuh Meilisearch |
| `SyncHelpIndex.php:78` `waitForTasks` + `:83-86` throw pada mismatch `numberOfDocuments` | "tunggu task + assert kiraan" **sudah** wujud dalam command — CI hanya perlu memanggilnya dengan driver yang betul |
| `HelpSearchService.php:24` menyemak `MEILISEARCH_HOST` sahaja (**bukan** `scout.driver`); `:37-39` catch `Throwable`; `:42-45` fallback PHP | spec carian **tidak boleh** membuktikan Meili sihat — indeks kosong/rosak/mati kelihatan identik dengan indeks sihat |

### 9.3 Disiplin pengukuran F8

- **Manifest dibekukan di F0**: **dua set** (kohort 25/124 + katalog penuh 83/473) disimpan
  **`Audit Review Round Robin/bukti/plan-baseline/manifest.json`** (laluan penuh — sama dengan
  F0; jangan cipta folder `bukti/` lain — **peraturan itu kini formal dalam §1 F0(iv)(g)**, dan
  output CI transient berpindah ke `storage/app/plan-ci/` + `storage/app/plan-f6/`).
  F8 mengukur set YANG SAMA (apple-to-apple).
- **Pelaporan berlapis (C22) — F8 TIDAK boleh menutup isu hanya dengan angka W1.** Setiap metrik
  dilapor pada **tiga paras**: (i) kohort 25/124; (ii) katalog penuh 83/473; (iii) pecahan
  **setiap family × role × viewport**. Sebarang **risk acceptance** mesti menyenaraikan
  **ID guide dan nombor langkah yang tepat** yang diterima sebagai tidak dibaiki — bukan
  peratusan, bukan "baki kecil".
- **Empat kategori dipisahkan, tiada "baki" (P14-06).** Laporan akhir mengasingkan
  **`passed` · `not-applicable` · `risk-accepted` · `blocked`** sebagai lajur berasingan pada
  setiap denominator. Menggabungkan `risk-accepted` dan `blocked` menjadi satu angka "baki"
  **dilarang** — ia menyembunyikan perbezaan antara "pemilik terima secara sedar, ada fallback,
  ada tarikh luput" dan "rosak, tiada sesiapa memutuskan apa-apa". **`blocked = 0` ialah syarat
  keluaran**; `risk-accepted > 0` dibenarkan tetapi setiap satu mesti membawa fallback yang
  **diuji**, tiket, pemilik dan tarikh luput yang belum lepas.
- **Persampelan bukan bukti penutup (P12-03).** Laporan F8 mesti membezakan dengan jelas antara
  (a) **smoke/persampelan** dan (b) **liputan keluaran penuh** G1–G5 §7.3 (473 langkah berstatus,
  semua sasaran `specific` disahkan DOM, 229 langkah tindakan black-box, 83 guide kitaran penuh).
  Metrik yang hanya disokong (a) dilabel **"smoke"** dalam jadual dan **tidak** boleh menutup
  sebarang ID penemuan. Setiap angka dilaporkan pada **denominator penuh** (`n/473`, `n/229`,
  `n/83`, `n/258`, `n/6`) — bukan "0 dalam skop Wn".
- **Telemetri (pengajaran RR-11-01):** crawl berautentikasi produksi MENULIS `help_events` +
  `guidance_progress` + token log masuk. Sebelum F8: **isytihar kesan** dalam laporan; guna
  akaun ujian tenant `smoke` sahaja; selepas F8: kira & rekod kesan (kiraan token pada masa
  aplikasi `Asia/Kuala_Lumpur`, bezakan melalui `intended_url`), luputkan token audit tidak
  terpakai, dan senaraikan artifak untuk pembersihan.
- Probe silang-tenant negatif diulang (regresi RR-02-04); regresi CSV (RR-04-02) disemak;
  matriks keselamatan §0.6 dijalankan penuh.

**Penghantaran F8:** laporan `Audit Review Round Robin/SUSULAN-PEMBAIKAN.md` — jadual di atas
diisi dengan angka sebenar + bukti; jika mana-mana sasaran tidak tercapai, sebab + keputusan
(baiki lagi / terima) direkod. Round-robin audit mini Claude↔Codex 2 pusingan ke atas hasil.

---

## 9A. FASA F9 — Regenerasi Manual Pengguna (artifak keluaran) — C21

**Menutup:** jurang yang tidak pernah ada dalam pelan v1.0–v1.3.

### 9A.1 Mengapa ia wajib

`Manual Penguna/` ialah **artifak keluaran yang dihantar kepada pengguna**, bukan nota dalaman:
9 folder persona (`01-Admin-Kerani` … `09-Orang-Awam-Pendaftaran`), setiap satu dengan
`MANUAL-PENGGUNA.md` + folder `imej/`, ditambah `README.md` dan `manifest-tangkapan.json`.
Ia dijana oleh `scripts/manual/{prepare-manual.php, capture-manual.mjs, generate-manuals.mjs}`.

Setiap manual mengisytiharkan **"Versi UI disahkan: 22 Julai 2026"** dan **"Liputan Chrome:
25/25 halaman"**. Pembaikan F3/F5/F6 **membatalkan** kandungan itu secara langsung:

| Fasa | Yang menjadi salah dalam manual |
|---|---|
| F3 | Skrinsyot + teks menunjukkan butang `Edit` (jadi `Sunting`), wizard `Seterus` (jadi `Seterusnya`), mesej validasi Inggeris (jadi BM) |
| F5 | Aliran log masuk & muat naik berubah (sasaran/langkah baharu); tour `/log-masuk` tidak lagi memaparkan ralat palsu |
| F6 | Sorotan panduan menunjuk elemen berbeza dalam setiap skrinsyot yang menangkap tour |
| F7 | Kolum Duplikat memaparkan teks baharu; kawalan viewer disabled |

Manual yang tidak dijana semula = dokumen yang **mengajar pengguna menekan butang yang sudah
tiada**. Itu regresi produk, bukan hal kosmetik.

### 9A.2 Skop

1. Jalankan semula rantaian `scripts/manual/` selepas F8 hijau (UI sudah stabil).
2. Kemas kini "Versi UI disahkan" kepada tarikh larian sebenar.
3. Semak teks manual yang menyebut label lama secara literal (carian `Edit`, `Seterus`,
   `field is required`) — betulkan dalam sumber penjana, bukan dalam output.
4. Data latihan sahaja (tenant contoh) — **tiada** nama/e-mel/telefon/dokumen sebenar (PDPA).

### 9A.3 Gate F9

- [ ] **9/9 persona** dijana semula; tiada folder tertinggal pada versi lama
- [ ] Setiap imej yang dirujuk **wujud** (tiada pautan imej mati)
- [ ] Langkah bernombor **berurutan** dan sepadan nombor pada gambar
- [ ] Setiap gambar diterangkan **tujuan + tindakan** (bukan sekadar "rajah 3")
- [ ] Kiraan halaman sepadan `manifest-tangkapan.json`
- [ ] Aliran **pendaftaran awam** lengkap hujung-ke-hujung
- [ ] Probe **silang-tenant 404** direkod dalam manual liputan (kekal seperti versi 22 Jul)
- [ ] Tiada label lama tinggal — **arahan DIBETULKAN SEPENUHNYA v1.7 (P14-07 → P16-06)**:

      ```bash
      # SALAH (v1.5): grep -rn "\bEdit\b\|\bSeterus\b(?!nya)" "Manual Penguna/"
      #   grep BRE TIADA lookahead negatif; "(?!nya)" ditafsir sebagai aksara literal,
      #   jadi bahagian "Seterus" tidak pernah padan → gate VAKUM (sentiasa "lulus").
      # MASIH SALAH (v1.6): test -d + `! rg …`
      #   (a) `test -d` lulus untuk folder KOSONG — gate berjalan ke atas 0 fail dan "lulus";
      #   (b) `!` menelan rc 2 (regex rosak/permission), bukan hanya laluan hilang.
      # BETUL (v1.7): assert bilangan fail input > 0, DAN bezakan rc 1 daripada rc >= 2.
      set -u
      mapfile -t manuals < <(find "Manual Penguna" -type f -name '*.md' 2>/dev/null)
      [ "${#manuals[@]}" -gt 0 ] || { echo "FAIL: tiada fail .md dalam 'Manual Penguna/'"; exit 1; }

      if rg -n '\b(Edit|Seterus)\b' "${manuals[@]}"; then
        echo "FAIL: label lama masih wujud dalam manual"; exit 1
      else
        rc=$?
        [ "$rc" -eq 1 ] || { echo "FAIL: rg ralat (rc=$rc), bukan 'tiada padanan'"; exit "$rc"; }
      fi
      echo "OK: ${#manuals[@]} fail manual disemak, 0 padanan"
      ```

      **Bukti rc (dijalankan P17, `ripgrep 15.2.0`):** tiada padanan → **rc 1**; laluan tiada →
      **rc 2**; regex rosak → **rc 2**. Baseline semasa: `Manual Penguna/` mengandungi **10** fail
      `.md` (9 persona + `README.md`) — jadi gate yang melaporkan `${#manuals[@]}` kurang daripada
      itu selepas F9 menandakan folder persona hilang, bukan "bersih".
      `Seterusnya` **tidak** tertangkap kerana `\b` selepas `Seterus` gagal di hadapan `n`.
      Jika teks sejarah memang perlu mengandungi perkataan itu, hadkan senarai `find` kepada
      output persona (`-path '*/MANUAL-PENGGUNA.md'`) atau senaraikan allowlist **fail:baris**
      yang nyata — jangan longgarkan regex.
- [ ] Matriks keselamatan §0.6 tidak terjejas (manual tidak mendedahkan data tenant sebenar)

**Risiko:** penjanaan memerlukan pelayan berjalan + data latihan; jika `capture-manual.mjs`
gagal separa, manual bercampur versi. Mitigasi: jana **semua** persona dalam satu larian; jika
gagal, ulang penuh (jangan tampal separa).

---

## 9B. FASA F10 — Housekeeping (dikeluarkan daripada F4) — C25

**Sebab pemisahan:** dead code dan pruning token **bukan** pembaikan lalai retensi. Menyimpannya
dalam F4 mencampurkan dua jenis perubahan dalam satu commit — menyukarkan bisect dan menjadikan
rollback F4 (yang mempunyai migrasi) turut membatalkan housekeeping yang tidak berkaitan.

### 9B.1 Skop

| Item | Tindakan | Prasyarat |
|---|---|---|
| **A2** `app/Filament/App/Resources/RetentionRules/Schemas/RetentionRuleForm.php` (dead code, dikenal pasti 19 Jul) | `git rm` — borang hidup ialah `RetentionRuleResource::form()` | **Semakan rujukan dahulu:** grep nama kelas di seluruh `app/`, `tests/`, `resources/`; sifar rujukan sebelum padam |
| **A3(i)** Pruning `login_tokens` | Tambah pruning token `used_at`/`expires_at` lama ke `diwan:prune-logs` (kini command itu **tiada** logik `login_tokens` — disahkan) | **D8** (polisi) dijawab |
| **A3(ii)** Polisi peringatan minit | Hadkan penjanaan token peringatan harian (cth. berhenti selepas 7 hari / eskalasi) — kini 13 minit tertunggak menjana 13 token + notifikasi **setiap pagi selama-lamanya** | **D8** |

### 9B.2 Ujian

- Padam dead code: suite penuh hijau + `grep` rujukan = 0 (bukti ditampal).
- Pruning: fixture tiga keadaan token — **aktif** (belum luput, belum guna) → **TIDAK** dipadam;
  **used** (>30 hari) → dipadam; **expired** (>30 hari) → dipadam; **used/expired baharu**
  (<30 hari) → dikekalkan (tetingkap forensik).
- Polisi peringatan: minit tertunggak N hari → bilangan token yang dijana mengikut polisi D8,
  bukan tanpa had.
- Regresi log masuk: magic link sedia ada masih berfungsi selepas pruning (token aktif tidak
  tersentuh) — ini gate keselamatan, bukan kosmetik.

### 9B.3 Gate F10

- [ ] Suite penuh hijau; CI hijau
- [ ] `RetentionRuleForm.php` dipadam **dengan** bukti sifar rujukan
- [ ] Pruning diuji 4 keadaan; magic link produksi masih berfungsi selepas deploy
- [ ] Kiraan `login_tokens` sebelum/selepas direkod dalam bukti fasa

---

## 10. Strategi deploy (setiap fasa)

Ikut `spdm-deploy-lessons` + runbook sedia ada. Ringkasan per fasa:

| Fasa | Aset frontend berubah? | Imej rebuild | Langkah khas |
|---|---|---|---|
| F1 | Tidak (PHP sahaja) | `app` (nginx force-recreate ikut langkah 4 §bawah) | — |
| F2 | **Ya** (help.js + modul `step-advance-plan.js`) | **`app` + `nginx`** | Ekstrak nama aset **exact** entri `resources/js/help.js` daripada `manifest.json` (langkah 5A #4a), sahkan ia **berubah** berbanding baseline F0(v), dan sahkan hash badan respons awam = hash dalam `app` **dan** `nginx` (5A #5a/#5b/#6). **Wildcard `help-*.js` pada URL HTTP dilarang** (P12-06); **grep hook ujian = 0** (C11) |
| F3 | Ya (jika blade/js tersentuh; lang PHP sahaja → tidak) | Ikut sebenar; lang+vendor override = `app` sahaja; `guides.json` disalin ke imej? — **semak Dockerfile COPY konteks: guides.json dalam `resources/` → imej `app`; nginx tidak hidang JSON → `app` sahaja** | `config:cache` + `diwan:sync-help-index --delete` |
| F4 | Tidak | `app` | `php artisan migrate --force` (1 migrasi `change()`); **JANGAN `db:seed`** |
| F5 | Ya (blade + guides.json) | `app` + `nginx` (blade guest = HTML dari PHP; CSS inline — nginx perlu jika aset Vite berubah sahaja; muktamad ikut `npm run build` diff) | `diwan:sync-help-index --delete` |
| F6 | Ya (JS + PHP + katalog) | `app` + `nginx` | sama F5, **per gelombang W0–W6** (W0 = hotfix mobile, sejurus selepas deploy F2) |
| F7 | Ya (JS — termasuk **entri Vite baharu** `a11y-landmarks.js`, §8.5) | `app` + `nginx` | Sahkan `manifest.json` mengandungi entri baharu itu **dalam kedua-dua imej** (langkah 5A #4b) sebelum mengesahkan axe |
| F8 | Tidak (pengukuran) | — | Matriks 20 konteks §9.1; isytihar telemetri dahulu |
| F9 | Tidak (dokumen + imej) | — | Jana selepas F8 hijau; tiada rebuild imej aplikasi |
| F10 | Tidak (PHP sahaja) | `app` | Tiada migrasi kecuali D8 menuntut; rekod kiraan token sebelum/selepas |

**Setiap deploy (urutan dibetulkan P2 — migrate SEBELUM kod baharu terima trafik +
force-recreate nginx SETIAP penggantian app):**
1. Lokal: pint → test → build → commit → push → **CI hijau disahkan** (`gh run list --json conclusion`).
   ⚠️ **Ditulis semula v1.9 (P20-05) — jangan campur dua senarai.** v1.8 menulis "kesemua empat"
   sambil menyenaraikan satu check, satu **step**, tiga **shard** dan satu check lagi. Bentuk yang
   betul (definisi penuh: §1 F0(iv)(f)):

   **(A) Required branch protection checks — tepat EMPAT nama, ini sahaja yang ditaip ke tetapan
   repo:** `PostgreSQL, Redis, Meili, OCR and tests` · `guidance-e2e-gate` · `Docker app image` ·
   `Docker web image`.

   **(B) Bukti keluaran yang disemak manusia sebelum deploy (BUKAN required check):** step
   `Domain flows` (`--project=ci-domain`, P16-08) di dalam check A1 · canary `@session-canary` ·
   step `Meilisearch help index gate` · ketiga-tiga larian `guidance-e2e (screen|workflow|
   tenant-admin-public)` · artifak JSON setiap gate.

   Shard yang **hilang** dikira **gagal**, bukan dilangkau — dan ujian yang **di-skip**
   (cth. `ci-ocr` tanpa fixture) tidak dikira lulus: buktinya ialah artifak
   `storage/app/plan-ci/*.json` (dimuat naik sebagai `ci-playwright-json` /
   `guidance-pw-json-<shard>`) yang lulus `assert-playwright-json.mjs` (§1 F0(iv)(e)), bukan warna
   job. Sebelum menetapkan (A) buat kali pertama, jalankan `gh api …/check-runs` (§1 F0(iv)(f) #6)
   dan taip **hanya** nama yang benar-benar dicetak.
2. Server: `chown` jika perlu → `git fetch && git reset --hard origin/main` → `docker compose build …`.
3. **Migrasi dahulu, dari imej BAHARU, sebelum trafik:**
   `docker compose run --rm -T app php artisan migrate --force` (corak runbook 22 Jul —
   elak `docker compose run` tanpa `-T` yang menelan stdin). Semua migrasi pelan ini
   **backward-compatible** (tambah default/kolum sahaja) → kod lama yang masih hidup seketika
   selamat (expand-contract).
4. `docker compose up -d --force-recreate app worker scheduler nginx` — **nginx sentiasa
   force-recreate bila app diganti** (HANDOVER: nginx cache IP upstream → 502; `restart` sahaja
   tidak memadai selepas recreate) + `nginx -t` sahkan config.
5. `config:cache` → verifikasi: `/up` 200 · `diwan:health` OK · `diwan:smoke` 9/9 ·
   **rantaian aset langkah 5A #4a–#6** (nama aset exact dari manifest + hash badan respons;
   `curl -sI` = header sahaja, bukan bukti kandungan — P12-06) ·
   **`docker compose exec -T nginx nginx -t` DAN `docker compose exec -T nginx nginx -T`**
   (P4 — `-t` sahkan sintaks, `-T` buktikan konfigurasi EFEKTIF selepas bind-mount/recreate;
   output tersanitasi disimpan sebagai bukti fasa) ·
   `schedule:list` tiada "Has Mutex" · `failed_jobs=0` · `docker compose ps` semua healthy.
**5A. Rantaian bukti runtime — WAJIB setiap deploy (C06).**
"Git HEAD server sama dengan origin" **bukan** bukti bahawa kod baharu sedang berjalan:
checkout boleh betul sementara container masih menggunakan imej lama, dan `HTTP 200` hanya
membuktikan sesuatu dihidang, bukan **apa** yang dihidang. Rekod **kesemua enam** dan simpan
sebagai bukti fasa:

⚠️ **Rantaian v1.4 mengandungi dua kesilapan yang menjadikannya tidak boleh membuktikan apa yang
didakwanya — dibetulkan P12-06:**
1. **DUA keluarga imej, bukan satu.** `docker-compose.yml:6` menandai `app`/`worker`/`scheduler`
   sebagai `diwan-app:${DIWAN_IMAGE_TAG:-local}`, manakala `:40` menandai `nginx` sebagai
   `diwan-web:${DIWAN_IMAGE_TAG:-local}`. Maka "keempat-empat container mesti mempunyai Image ID
   yang sama dengan #2" adalah **mustahil dan salah**; assert yang betul ialah **pemadanan
   per-keluarga**.
2. **Wildcard tidak wujud pada HTTP.** `curl https://bakwim.my/build/assets/help-*.js` tidak
   dikembangkan oleh pelayan — ia meminta laluan literal yang mengandungi `*` dan akan
   **404**. Nama aset mesti **diekstrak daripada `manifest.json`** dahulu.
   *(Nota: entri `resources/js/help.js` menghasilkan **dua** artifak — `.file` JS **dan** satu
   `.css` — jadi rantaian meliputi kedua-duanya.)*

`nginx` **memang** mempunyai aset itu: `docker/Dockerfile:71-72` — stage `web`
(`FROM nginx:1.27-alpine`) menyalin `/var/www/html/public` daripada stage `app`. Maka
perbandingan silang-container adalah bermakna dan wajib.

| # | Bukti | Arahan (boleh dijalankan literal; `<tag>` = `${DIWAN_IMAGE_TAG:-local}`) |
|---|---|---|
| 1 | Git SHA server | `git -C <repo> rev-parse HEAD` |
| 2a | Image ID + Created **`diwan-app`** | `docker image inspect diwan-app:<tag> --format '{{.Id}} {{.Created}}'` |
| 2b | Image ID + Created **`diwan-web`** | `docker image inspect diwan-web:<tag> --format '{{.Id}} {{.Created}}'` |
| 3a | Container keluarga `app` | `docker inspect --format '{{.Name}} {{.Image}}' <app> <worker> <scheduler>` — ketiga-tiganya **mesti sama dengan #2a** |
| 3b | Container `nginx` | `docker inspect --format '{{.Name}} {{.Image}}' <nginx>` — **mesti sama dengan #2b**, dan **dijangka BERBEZA** daripada #2a |
| 4a | **Nama aset exact** dari manifest (app) | `docker compose exec -T app php -r '$m=json_decode(file_get_contents("public/build/manifest.json"),true)["resources/js/help.js"]; echo $m["file"],PHP_EOL; foreach(($m["css"]??[]) as $c) echo $c,PHP_EOL;'` |
| 4b | Manifest identik pada nginx | `docker compose exec -T app sha256sum public/build/manifest.json` **dan** `docker compose exec -T nginx sha256sum /var/www/html/public/build/manifest.json` — **mesti sama** (membuktikan kedua-dua imej dibina daripada `npm run build` yang sama) |
| 5a | Hash aset **dalam `app`** | `docker compose exec -T app sha256sum "public/build/<asset>"` bagi setiap `<asset>` dari #4a |
| 5b | Hash aset **dalam `nginx`** | `docker compose exec -T nginx sha256sum "/var/www/html/public/build/<asset>"` — **mesti sama** dengan #5a |
| 6 | Hash **badan respons** URL awam | `curl -fsS "https://bakwim.my/build/<asset>" \| sha256sum` — **mesti sama** dengan #5a/#5b. `-f` memastikan 404/5xx **gagal**, bukan menghasilkan hash badan ralat |
| 7 | Header status/cache (**bukti tambahan sahaja**) | `curl -sI "https://bakwim.my/build/<asset>"` — direkod untuk diagnosis cache; ia **BUKAN** pengganti hash badan |

Rantaian yang benar-benar membuktikan "kod dalam repo = kod dalam imej = kod yang pengguna
terima" ialah: **#3a padan #2a · #3b padan #2b · #4b sama · #5a = #5b = #6**.
⚠️ v1.4 langkah 5 melabel `curl -sI … = 200` sebagai "hash" — itu **dibuang**; status 200 hanya
membuktikan *sesuatu* dihidang, bukan **apa**.

*Nota ketepatan:* Codex mencadangkan turut merekod **"label SHA"** pada imej. Itu **belum
mungkin** hari ini — `docker/Dockerfile` mengandungi **0 `LABEL`** dan `docker-compose.yml`
menandai imej sebagai `diwan-app:${DIWAN_IMAGE_TAG:-local}` (tag tidak mengekod SHA). Dua
pilihan: **(a)** terima rantaian #1–#6 di atas sebagai bukti (mencukupi — ia mengikat kandungan,
bukan metadata); **(b)** tambah `ARG GIT_SHA` + `LABEL org.opencontainers.image.revision`
dalam Dockerfile — perubahan kod kecil, jadi ia **D9** (keputusan pemilik), bukan andaian.

6. e2e Chrome spot-check produksi → kemas `HANDOVER.md` + rekod bukti fasa.
   **Pada F8: bukan spot-check — `e2e/production-guidance-readonly.spec.js` melalui wrapper
   `scripts/audit/run-production-guidance-readonly.ps1`, tepat 20 BrowserContext, page-by-page
   desktop DAN mobile daripada manifest `role_routes` F0(ii-b), dengan `run_uuid` + tenant
   `smoke-<run_uuid>` + cleanup `try/finally` (§9.1a).**
   ⚠️ **Jangan** jalankan `e2e/guidance.spec.js` seluruhnya terhadap produksi — fail itu
   mengandungi laluan mutasi (`ensureInboxFixture:95-111` memuat naik dokumen sebenar).
   ⚠️ **Jangan** padam tenant `smoke` — ia milik gate `diwan:smoke` langkah 5 (§9.1a).
7. Rollback fasa: `git reset` ke SHA sebelum + rebuild + (jika ada migrasi) `migrate:rollback`
   yang telah diuji di F4 §5.5 #5 — **latihan rollback dicatat sebagai langkah bukti F4**.

**Pengurangan bilangan deploy (pilihan pemilik):** gabung F1+F2 (satu deploy), F5+F7 — trade-off:
bisect regresi lebih kasar. Cadangan: kekal berasingan untuk F1 (paling kritikal diukur bersih).

---

## 11. Keputusan pemilik — ✅ SEMUA D1–D11 TELAH DIJAWAB (2 Ogos 2026)

> **Status (v1.10, P22-T7):** kesemua soalan di bawah telah dijawab pemilik — jawapan rasmi +
> pemetaan dalam **`KEPUTUSAN-PEMILIK.md`** (D1 Ya · D2 Ya · D3 Kekal · D4 Ya · D5 Ya(a+b) ·
> D6 Terima · D7 GABUNG · D8/D9 ikut cadangan · **D10 LULUS** · **D11 luluskan semua**).
> Jadual di bawah DIKEKALKAN sebagai rekod soalan/cadangan/kesan — lajur "Cadangan Claude"
> ialah sejarah cadangan, bukan status menunggu. Baki tindakan pemilik yang BUKAN keputusan D:
> Lampiran A1 (padam tiket ujian produksi) — masih menunggu, tidak terjejas oleh jadual ini.

| # | Soalan | Pilihan | Cadangan Claude | Blok |
|---|---|---|---|---|
| D1 | Default borang peraturan retensi → `semak`? | Ya / Tidak | **Ya** | F4 |
| D2 | `auto_disposal_enabled` default `false` untuk masjid **baharu**? ⚠️ **DINAIKKAN TARAF (C01): ini perubahan PRODUK yang bercanggah `DIWAN-SPEC.md:470` + §16.1 + Aliran L + teks pengakuan §16.2 — bukan pembetulan kualiti.** Ia memerlukan **D10 (addendum) diluluskan dahulu** | Ya (dengan addendum) / Tidak | **Ya, tetapi hanya selepas D10** | F4 (L2 sahaja) |
| D3 | 14 peraturan platform produksi: kekal `auto_padam` (patuh §16.1) atau tukar `semak`? | Kekal / Tukar | **Kekal** (L1+L2 sudah jadi brek berganda) | F4 |
| D4 | Terima pengesahan-kedua dgn kiraan rekod (sedikit kerja UI tambahan)? | Ya / ringkas sahaja | **Ya** | F4 |
| D5 | **DUA soalan berasingan (ditulis semula P12-08 — rumusan v1.4 mendakwa "tidak bercanggah polisi"; dakwaan itu ditarik balik).** **(a) Polisi:** luluskan **pengecualian bertulis** kepada `CLAUDE.md:10` (*"DILARANG … menambah pakej luar senarai"*, ditulis umum, tidak terhad kepada Composer) yang menamakan `axe-core` dan mengehadkan skopnya kepada **dev-only**, direkod dalam dokumen kawalan? **(b) Dependensi:** jika (a) diluluskan, benarkan `package.json` + `package-lock.json` diubah untuk menambahnya? | (a) Luluskan / Tolak · (b) Ya / Tidak | **(a) Terpulang pemilik — Claude TIDAK mengesyorkan memintas polisi**; jika ragu, **Tolak** dan gunakan laluan manual. **(b) Hanya bermakna jika (a) diluluskan.** Tanpa (a): laluan lalai manual §8.5, **F7 tidak tersekat** | F7/F8 (tidak menyekat) |
| D6 | Polisi bump `version` guide: bump per-guide yang diubah → pengguna sedia ada dapat auto-tour semula guide itu SEKALI. Terima? | Terima / mitigasi (migrate progress) | **Terima** (pengguna produksi masih sedikit; tour semula selepas pembaikan justru bermanfaat) | F5/F6 |
| D7 | Susunan/gabungan deploy §10 | Berasingan / gabung | Berasingan F1; gabungan lain terpulang | Semua |
| **D8** | **Polisi token log masuk (C25/A3):** (i) padam token `used`/`expired` >30 hari melalui `diwan:prune-logs`? (ii) hadkan peringatan minit harian (13 minit tertunggak → 13 token + notifikasi **setiap pagi tanpa henti**) — berhenti selepas 7 hari? eskalasi? kekal? | (i) Ya/Tidak · (ii) 7 hari / eskalasi / kekal | (i) **Ya** (30 hari = tetingkap forensik memadai); (ii) **berhenti selepas 7 hari + satu eskalasi kepada admin** | **F10** |
| **D9** | **Label imej untuk bukti runtime (C06):** tambah `ARG GIT_SHA` + `LABEL org.opencontainers.image.revision` dalam `docker/Dockerfile` (kini **0 LABEL**)? | Ya / Tidak | **Ya** — label revisi OCI menjadikan jejak imej→commit remeh; **jika Tidak, rantaian bukti #1–#6 §10 langkah 5A tetap WAJIB** sebagai satu-satunya pengikat kandungan | **Bukti deploy** (F0 + setiap deploy fasa) |
| **D10** | **Addendum spec v2.6 (C01):** luluskan addendum bernombor yang menukar lalai `auto_disposal_enabled` kepada `false` untuk masjid baharu, **dan** menyelaraskan teks pengakuan §16.2 supaya ia tidak lagi memberitahu masjid baharu bahawa rekod "akan dipadam secara automatik" sedangkan suisnya mati? Format: seksyen `# ADDENDUM v2.6` baharu dalam `DIWAN-SPEC-ADDENDUM-2026-07.md` (mengikut corak v2.2–v2.5 sedia ada) | Luluskan / Tolak | **Luluskan hanya jika pemilik benar-benar mahu D2** — addendum ini wujud semata-mata untuk membolehkan D2; **jika Tolak, lalai `auto_disposal_enabled` kekal `true`** (patuh `DIWAN-SPEC.md:470`), D2 gugur, dan F4 terhad kepada L1 sahaja | **F4 (L2)** |

| **D11** | **✅ DILULUSKAN SEMUA oleh pemilik (2 Ogos — `KEPUTUSAN-PEMILIK.md`); kiraan dinormalisasi v1.10 (P22-T6): 16 ENTRI = 19 fail repo fizikal + 1 bundle audit (§1 F0(iv-a)); #16a-c tidak lagi bersyarat kerana "lulus semua".** Perkakas pengukuran F0 yang menyentuh repo (P14-02/03/04 + P16-01/02/03/04/08 + P18-04/05) — diisytihar, bukan diseludup. ⚠️ **SKOP DIBETULKAN v1.7 (P16-03) DAN DIMUKTAMADKAN v1.8 (P18-04/05/06): empat artifak → 16 entri.** Angka perantaraan **12** (v1.7 log versi) dan **14** (v1.7 jadual) **DIBATALKAN**. ✅ **v1.9 (P20-06) mengesahkan tiada entri baharu:** enam pindaan P20 semuanya masuk ke dalam fail yang sudah tersenarai (#3, #4, #15), dan laluan output CI dipindah ke `storage/app/plan-ci/` + `storage/app/plan-f6/` yang **sudah** diabaikan git — jadi **`.gitignore` tidak disentuh** (§1 F0(iv)(g)). Senarai penuh + tujuan setiap fail: **§1 F0(iv-a)** — ringkasnya: (1) `app/Console/Commands/RoleRoutes.php`; (2) `app/Console/Commands/AuditFixture.php`; (3) `playwright.config.js` (`projects:` **+ reporter JSON bersyarat-env**); (4) `.github/workflows/ci.yml` (gate Meilisearch + serve `--no-reload` + canary + `ci-guidance` + `ci-domain` + cleanup `if: always()` + upload artifak JSON + job `guidance-e2e` + `guidance-e2e-gate`); (5) `e2e/ci-session-canary.spec.js`; (6) `e2e/guidance-full.spec.js`; (7) `scripts/audit/aggregate-guidance-coverage.mjs`; (8) `scripts/audit/validate-plan-manifest.mjs`; (9) `e2e/production-guidance-readonly.spec.js`; (10) `scripts/audit/run-production-guidance-readonly.ps1`; (11) `tests/Feature/AuditFixtureCommandTest.php`; (12) `tests/Feature/PlanManifestTest.php`; (13) `resources/help/targets.json` + `docs/HELP-TARGETS.md`; **(14) `tests/Feature/InboxAntivirusFailClosedTest.php` (BAHARU v1.8 — gate keselamatan, bukan pengukuran); (15) `scripts/audit/assert-playwright-json.mjs` (BAHARU v1.8);** (16a-c) `tests/fixtures/ocr/{sample-scan-1.png, sample-scan-2.png, terms.json}` (~~bersyarat~~ — **label lama TERBATAL v1.11 (P24-T4): keputusan pemilik "luluskan semua" menjadikannya WAJIB**; `ci-ocr` kini boleh menjadi gate sebenar). Luluskan kesemuanya sebagai **perkakas pengukuran + gate** di bawah F0? | Luluskan semua / luluskan sebahagian (namakan nombor) / Tolak | **[CADANGAN SEJARAH — keputusan sebenar pemilik: LULUSKAN SEMUA]** ~~Luluskan 1–15; 16 terpulang.~~ Tanpa (1) manifest role tidak boleh dijana daripada kod dan drift 8/8 role kekal · tanpa (2)+(10)+(11) larian produksi kekal ad-hoc dan cleanup tidak boleh dibuktikan idempotent · tanpa (3)+(4) skop CI kekal tidak literal dan gate 473/229/83 tiada tempat untuk berjalan · tanpa (5) kegagalan sesi menyamar sebagai pepijat UI · tanpa (6)+(7)+(8) liputan penuh tidak boleh dibuktikan sebagai **set**, hanya sebagai kaunter · tanpa (12) spec baharu boleh ditulis tanpa pernah dijalankan CI · **tanpa (14) cabang fail-closed `InboxIngestService.php:76-78` kekal 0% diuji — dokumen berjangkit boleh masuk arkib jika ClamAV mati dan seseorang menyilap `CLAMAV_FAIL_CLOSED`** · **tanpa (15) mana-mana gate Playwright boleh hijau sambil di-skip sepenuhnya** (`ocr-upload.spec.js:6`). Kesemuanya **tidak** mengubah tingkah laku produk dan **tidak** menambah pakej npm/Composer (§0.1(2) dihormati; agregator/validator/assert ialah Node 22 tulen; ujian antivirus guna Mockery yang sudah ada dalam `GuidanceSupportTest.php:93`). *(Nota sejarah v1.11: ayat lama "(16) hanya jika pemilik mahu gate OCR sebenar" TERBATAL — "luluskan semua" bermakna #16a-c wajib dan `ci-ocr` layak menjadi gate liputan sebenar.)* **(14) disyorkan diluluskan walaupun jika pemilik menolak yang lain**, kerana ia satu-satunya item D11 yang menutup risiko keselamatan dan bukan risiko pengukuran | **F0** (menyekat F6 gate + F8) |

*(Nota kebergantungan — kini DISELESAIKAN oleh jawapan pemilik, direkod untuk sejarah:
D1/D3/D4 → F4 ✅; **D2 → F4 (L2), D10 LULUS → dibuka** — teks `# ADDENDUM v2.6` ditulis sebagai
langkah pertama F0/F4; **D5(a)+(b) diluluskan** → `axe-core` dev-only dibenarkan, pengecualian
bertulis direkod semasa F7; D6 → F5/F6 ✅; D7 = **GABUNG** → urutan deploy §10 guna pilihan
gabungan; **D8 → F10 ✅** (prune 30 hari; peringatan berhenti 7 hari + satu eskalasi);
**D9 ✅** → label OCI ditambah; **D11 ✅ luluskan semua** → F0 (19 fail §1 F0(iv-a)) dibuka,
gate F6 + F8 tidak lagi tersekat.)*

**Prasyarat F0 juga meliputi item bukan-soalan (P14-08):** komit atau snapshot folder
`Audit Review Round Robin/` supaya versi pelan yang diaudit menjadi immutable (§0.7).

---

## 12. Anggaran usaha & jujukan masa

| Fasa | Anggaran (sesi kerja fokus) | Boleh selari? |
|---|---|---|
| **F0 (perkakas pengukuran + gate — 19 fail repo §1 F0(iv-a) / D11 ✅ diluluskan)** | **2–3** — manifest 3 set + validator + canary + spec gate penuh + agregator + 2 job CI + command fixture + wrapper + 3 ujian (termasuk **ujian antivirus fail-closed**, P18-04) + skrip assert JSON Playwright (P18-05) + 3 fixture OCR (#16a-c, D11 lulus semua). **Menyekat gate F6 dan seluruh F8**, jadi ia bukan kos pilihan | D11 ✅ dijawab; boleh selari dengan F1–F3 |
| F1 | 0.5–1 | — |
| F2 | 1–1.5 | selepas F1 |
| F3 | 1.5–2 (terjemahan ±160 kunci + ujian) | bebas F1/F2 |
| F4 | 0.5 (+ menunggu D1–D4) | bebas |
| F5 | 1–1.5 (kandungan 124 tajuk kohort = bahagian terbesar) | selepas F2 |
| **F6 W0 (hotfix mobile, 2 guide / 10 langkah)** | **0.5** — 6 defect mobile + 10 tajuk; diuji desktop **dan** mobile | **selepas F2** (tidak menunggu F5) |
| **F6 W1 (`screen` bertindakan, 28 guide / 140 langkah)** | **4–5** — hutang tindakan terbesar: **140** langkah tindakan + 140 placeholder dalam satu wave | selepas F5 |
| **F6 W2 (`workflow` bertindakan, 13 guide / 145 langkah)** | **2–2.5** — 60 langkah tindakan; kerja merentas halaman | selepas W1 |
| **F6 W3 (baki `screen`, 1 guide / 11 langkah)** | **0.25** — `screen.klasifikasi-peti-masuk`; 0 langkah tindakan generik | selepas W2 |
| **F6 W4 (baki `workflow`, 1 guide / 13 langkah)** | **0.25** — kerja utama ialah **mengklasifikasi + merekod sebab per-langkah** | selepas W3 |
| **F6 W5 (`tenant`+`admin`, 35 guide / 146 langkah)** | **2–2.5** — 108 placeholder; **0** langkah tindakan; 0 defect mobile (dipindah ke W0) | selepas W4 |
| **F6 W6 (public, 3 guide / 8 langkah)** | **0.25** — sebahagian sudah ditutup F5 | bila-bila selepas F5 |
| F7 | 0.5–1 | bila-bila selepas F2 |
| F8 | **1–1.5** (naik: matriks 20 BrowserContext §9.1 + pelaporan 3 paras) | selepas F7 |
| **F9 (manual)** | **1–1.5** (jana 9 persona + semak teks/imej) | selepas F8 |
| **F10 (housekeeping)** | **0.5** (+ menunggu D8) | bebas |

Jumlah teras (**F0** + F1–F5, F7, F8 + W1 + W2): **±11.5–16 sesi**. W3–W6 + F9 + F10: ±6.5–9 sesi
tambahan. *(Kenaikan ±2–3 sesi berbanding v1.6 datang sepenuhnya daripada **F0 dijadikan eksplisit**
— kerjanya sudah tersirat dalam v1.6 tetapi tidak pernah dianggarkan; P16-03 mendedahkannya.)*

**Nota kalibrasi (C09):** anggaran menulis tajuk **turun**, bukan naik — placeholder sebenar
ialah **258**, bukan 444 seperti v1.3, dan 0 daripadanya berada dalam family `admin`/`public`/
`workflow`. Kenaikan jumlah keseluruhan datang daripada **liputan** (kesemua 83 guide, C02) dan
**pengesahan** (matriks produksi C08, manual C21), bukan daripada kerja tajuk.

**Nota kalibrasi kedua (P12-02/P12-03):** jumlah keseluruhan hampir tidak berubah daripada v1.4 —
yang berubah ialah **taburannya**. Kerja bernilai tertinggi (200 langkah tindakan) beralih ke
**W1+W2**, jadi manfaat kepada pengguna datang selepas ±3.5 sesi F6, bukan selepas ±5.5.
Sedikit kenaikan bersih datang daripada gate G1–G5 §7.3 yang menggantikan persampelan dengan
liputan penuh 473/229/83 — kos pengesahan, bukan kos pembinaan.

---

## Lampiran A — Housekeeping berkait (bukan penemuan produk, dari audit & pemerhatian)

| # | Item | Cadangan | Status |
|---|---|---|---|
| A1 | Tiket ujian `SUP-260801-HXQ0DIOL` produksi | Pemilik padam di `/admin/tiket-sokongan` | ⏳ menunggu pemilik |
| A2 | `RetentionRuleForm.php` dead code (kelas Schema tidak digunakan; dikenal pasti 19 Jul; kewujudan fail disahkan semula P11) | Padam (satu `git rm`) **selepas** bukti sifar rujukan — borang hidup ialah `RetentionRuleResource::form()` | **→ F10 §9B** (dikeluarkan dari F4 — C25) |
| A3 | `login_tokens` terkumpul (39 aktif = 13 minit × 3 hari peringatan; token used/expired kekal selamanya). Disahkan P11: `diwan:prune-logs` **tiada** logik `login_tokens` langsung | (i) Tambah pruning token `used_at`/`expires_at` lama (>30 hari); (ii) hadkan peringatan minit harian — kini 13 minit tertunggak menjana 13 token+notifikasi SETIAP pagi selama-lamanya | **→ F10 §9B**, kedua-duanya menunggu **D8** (bukan lagi "masuk F4") |
| A4 | Protokol audit masa depan (pengajaran RR-11-01) | Templat pengisytiharan kesan sebelum audit produksi + kiraan token pada masa aplikasi (`Asia/Kuala_Lumpur`) — sudah direkod dalam memori & HANDOVER; tiada kod | selesai (dokumentasi) |
| A5 | OCR 300 DPI (penambahbaikan tertunda dari penanda aras 21 Jul — CER 16.2%→3.8% cetakan halus) | Luar skop pelan ini; eksperimen berasingan dgn ujian RAM 2GB | tidak dirancang di sini |
| A6 | RAM 2GB sempit (nota audit 21 Jul: syor 4GB sebelum volum tinggi) | Nota kepada pemilik; bukan kod | nota |

## Lampiran B — Perkara yang pelan ini SENGAJA tidak lakukan (dan sebab)

1. **Tidak menulis semula sistem tour** kepada "senarai semak dalam panel" (cadangan produk P1
   §D) — perubahan produk besar; sasaran spesifik F6 menyelesaikan keluhan teras dengan risiko
   jauh lebih kecil. Boleh dinilai semula selepas F8 jika metrik masih tidak memuaskan.
2. **Tidak menukar `semanticAction()`** — selepas F6, langkah `page-primary` berkurangan; fungsi
   kekal sebagai fallback. Menala heuristik = usaha tinggi, pulangan rendah berbanding sasaran eksplisit.
3. **Tidak publish templat e-mel vendor** — `lang/ms.json` mencukupi; publish menambah beban
   selenggara naik taraf framework.
4. **Tidak mengubah data retensi produksi secara automatik** — sebarang perubahan L3 mesti skrip
   diluluskan berasingan (D3).
5. **Tidak menyentuh** kawasan sihat §0.3.
6. **Tidak mem-patch/fork Driver.js** untuk membenarkan Tab keluar ke seluruh halaman semasa
   tour aktif (C03) — perangkap Tab ialah tingkah laku vendor (`driver.js.mjs:202-215,236`).
   Direkod sebagai had diketahui; penyelesaian sebenar ialah PR upstream, di luar skop.
7. **Tidak menambah bundler** untuk ujian JS (C11) — `stepAdvancePlan()` diekstrak ke modul
   tulen yang boleh diimport terus oleh Playwright, dan `resolveStepElement()` diuji black-box
   melalui `.driver-active-element`. Tiada hook ujian global dalam bundle produksi.
8. **Tidak melaksanakan D2 sebelum Addendum spec v2.6** (C01) — pelan ini tidak mengubah spec
   secara senyap.
9. **Tidak menambah `axe-core` (atau mana-mana pakej npm) secara lalai** (P12-08) — `CLAUDE.md:10`
   melarang pakej luar senarai secara umum dan `CLAUDE.md:3` memerintahkan berhenti-dan-tanya
   apabila polisi kabur. Laluan lalai ialah alat luaran/manual yang tidak menyentuh
   `package.json`/lockfile; penambahan hanya selepas **pengecualian polisi bertulis** (D5(a)).
10. **Tidak menjalankan `guidance.spec.js` seluruhnya terhadap produksi** (P12-05) — fail itu
    mengandungi laluan mutasi (`ensureInboxFixture`); F8 menggunakan spec produksi read-only
    khusus yang diekstrak daripada matriks 20 konteks yang sama.
11. **Tidak mencipta job CI yang "guna semula" services job lain** (P12-04) — ⚠️ **DIPERJELAS v1.7
    (P16 §4.4): larangan ini adalah tentang PERKONGSIAN, bukan tentang job berasingan.**
    GitHub Actions tidak berkongsi service containers antara job, jadi **smoke** Playwright
    (canary + `ci-guidance` + `ci-domain`) masuk ke dalam job `integration` yang sudah mempunyai
    PostgreSQL/Redis/Meilisearch, PHP, Node dan `npm run build`. Sebaliknya job matriks
    **`guidance-e2e` DIWAJIBKAN wujud** (F0(iv) lapis 2) dan ia **mesti mengisytiharkan
    `services:`, `env:` dan setup sendiri** — duplikasi ±60 baris ialah harga yang betul untuk
    gate deterministik. Rumusan v1.6 yang berbunyi seolah-olah "tiada job e2e berasingan"
    bercanggah dengan F0(iv); rumusan ini menggantikannya.
12. **Tidak menjadikan `ci-ocr` required sebelum fixturenya dikomit** (P16-08) — `test.skip`
    tanpa `SPDM_OCR_FIXTURE_*` menghasilkan gate yang sentiasa hijau tanpa menguji apa-apa.
    Lebih baik tidak-required dan jujur daripada required dan vakum.
13. **Tidak menamakan semula job `integration`** (P18-01) — walaupun nama checknya
    (`PostgreSQL, Redis, Meili, OCR and tests`, `.github/workflows/ci.yml:19`) tidak
    menggambarkan kandungan barunya, menukar `name:` menukar **nama status check** dan mematahkan
    setiap tetapan branch protection serta PR terbuka yang merujuk nama lama. Pelan menyesuaikan
    diri kepada nama sedia ada; penamaan semula (jika dimahukan) ialah kerja berasingan yang
    mesti dibuat serentak dengan mengemas tetapan repo.
14. **Tidak mencipta superadmin sementara pada produksi** (P18-03) — `diwan:audit-fixture` hanya
    mencipta identiti **berskop tenant terpakai-buang**. Superadmin ialah akses penuh
    silang-tenant; kegagalan cleanup akan meninggalkan akaun sedemikian hidup pada sistem live.
    Kredensialnya dibekalkan dari luar dan hanya disahkan hadir.
15. **Tidak menjadikan `ci-domain` required status check** (P18-01) — ia project Playwright, bukan
    job; GitHub tidak boleh melaporkan check dengan nama itu. Ia dikuatkuasakan sebagai step dalam
    job `integration` + artifak JSON yang membuktikan ia benar-benar berjalan.
16. **Tidak menjadikan ketiga-tiga shard `guidance-e2e (…)` required check** (P20-05) — agregator
    `guidance-e2e-gate` sudah gagal apabila mana-mana artifak shard hilang, jadi menambah tiga
    nama lagi ke branch protection menggandakan penguatkuasaan yang sama sambil mengikat tetapan
    repo kepada nilai `matrix.shard` selama-lamanya. Ia kekal sebagai **bukti keluaran** (B5/B6).
17. **Tidak menukar `SESSION_DRIVER` pada aras job `integration`** (P20-01) — hanya step serve
    yang memerlukan `file`. Menukarnya pada `env:` job akan menukar persekitaran suite Pest yang
    kini hijau, tanpa faedah, dan menyembunyikan sebab sebenar canary wujud.
18. **Tidak memindahkan output CI ke `test-results/`** (P20-06) — walaupun ia sudah diabaikan git,
    Playwright memadam `outputDir` pada permulaan setiap larian
    (`node_modules/playwright/lib/runner/index.js:5943-5962`), jadi larian kedua dalam job yang
    sama akan memusnahkan bukti larian pertama. Output berada di `storage/app/plan-*`
    (§1 F0(iv)(g)).

---

*Versi 1.9 — **BUKAN muktamad**. Giliran seterusnya: **Codex Pusingan 22** mengaudit integrasi
versi ini; lihat `PLAN-RR-STATUS.md`, keputusan penuh C01–C25 dalam
`PLAN-RR-11-CLAUDE-AUDIT-LENGKAP.md`, keputusan P12-01…P12-08 dalam `PLAN-RR-13-CLAUDE.md`,
keputusan **P14-01…P14-08 + P16-01…P16-08** dalam `PLAN-RR-17-CLAUDE.md`, keputusan
**P18-01…P18-07** dalam `PLAN-RR-19-CLAUDE.md`, dan keputusan **P20-01…P20-06** dalam
`PLAN-RR-21-CLAUDE.md`.
`PLAN-RR-15-CLAUDE.md` **tidak wujud** — giliran P15 terputus oleh had perbelanjaan bulanan
selepas menyunting badan pelan (§0.7).*

# Pusingan 18 — Codex: audit integrasi v1.7

Tarikh: 2026-08-02  
Fail dinilai: `PELAN-PEMBAIKAN.md` v1.7, `PLAN-RR-17-CLAUDE.md`, kod semasa pada `8342d95`  
Keputusan: **BELUM MUKTAMAD — v1.8 diperlukan**

Saya gunakan mod semakan kod: cari fakta salah, kontrak yang belum boleh dijalankan, jurang ujian dan percanggahan dalaman. Kod aplikasi tidak disentuh.

## Integriti sebelum tulis

- Semakan #1 pra-tulis: `PELAN-PEMBAIKAN.md` = `DD58889AEB2D58CA4A2C3E2A9EA040D9C555F7B64FDBF246EBE8699705BBDDBE`, 248,846 B, 3,270 baris, mtime `2026-08-02 05:12:54`.
- Semakan #2 pra-tulis pada `2026-08-02 05:27:25 +08:00`: hash, saiz, bilangan baris dan mtime kekal sama.
- `PLAN-RR-17-CLAUDE.md` = `37940CB1A64494D8342B26C19C203452772AF19BD0DFC380A429B727590D3318`.
- Nota integriti: `PLAN-RR-STATUS.md:73` menyatakan `working tree bersih`, tetapi `git status --short` sebenar menunjukkan `M HANDOVER.md` dan banyak fail perancangan belum dijejak. Maksud yang betul mungkin "kod aplikasi bersih relatif kepada `8342d95`", bukan seluruh working tree bersih.

## Penemuan

### P18-01 — Required check `ci-domain` tidak boleh wujud seperti ditulis

`PELAN-PEMBAIKAN.md:861-863` meminta branch protection mewajibkan `Guidance coverage gate`, `integration` dan `ci-domain`. Namun `ci-domain` dalam pelan ialah **Playwright project** di `playwright.config.js` (`PELAN-PEMBAIKAN.md:991-995`) dan command dalam job integration (`PELAN-PEMBAIKAN.md:1011-1013`), bukan GitHub Actions job/status check berasingan.

Kod CI semasa juga menamakan job `integration` sebagai `PostgreSQL, Redis, Meili, OCR and tests` (`.github/workflows/ci.yml:18-19`). Jika repo branch protection menggunakan nama check yang dipaparkan, arahan "required `integration`" juga berisiko tidak sepadan.

**Pembaikan v1.8:** pilih satu kontrak:

1. Jadikan `ci-domain` job GitHub Actions berasingan dengan `name: ci-domain`, services/env/setup literal dan required status check sendiri; atau
2. Kekalkan `ci-domain` sebagai step dalam `integration`, tetapi branch protection hanya mewajibkan check GitHub Actions sebenar, contohnya `PostgreSQL, Redis, Meili, OCR and tests` dan `Guidance coverage gate`. Nyatakan bahawa `ci-domain` enforced kerana step itu menggagalkan job integration.

### P18-02 — YAML "bentuk beku" masih bukan literal

`PELAN-PEMBAIKAN.md:809` menyebut YAML CI "bentuk beku — bukan cadangan", tetapi snippet masih mengandungi placeholder:

- `postgres: { image: postgres:16-alpine, ... }` (`:822`)
- `redis: { image: redis:7-alpine, ... }` (`:823`)
- `meilisearch: { image: getmeili/meilisearch:v1.12, ... }` (`:824`)
- `# setup PHP/Node/composer/npm/build/migrate:fresh --seed/serve/canary — sama lapis 1` (`:833`)

Ini belum menutup P16-02 kerana implementer masih perlu mereka bentuk services/env/setup sendiri. CI sedia ada mempunyai blok literal yang boleh disalin: `.github/workflows/ci.yml:23-51` untuk services, `:52-80` untuk env, dan `:82-148` untuk setup/build/test/runtime smoke.

**Pembaikan v1.8:** ganti placeholder dengan YAML penuh atau ekstrak reusable step/action yang dinamakan tepat. Jika ada perbezaan port `8092`/`8080`, `SESSION_DRIVER=file`/`array`, atau `SCOUT_DRIVER`, nyatakan baris env sebenar secara eksplisit.

### P18-03 — Kontrak kredensial superadmin fixture produksi bercanggah

`PELAN-PEMBAIKAN.md:2822` menyatakan `prepare` mencipta tenant `smoke-<run_uuid>` dan 10 identiti, tetapi "superadmin sedia ada dirujuk, tidak dicipta semula". Kemudian `:2844-2849` menyatakan `prepare` menulis kredensial termasuk `E2E_PROD_SUPERADMIN_*` ke fail private sementara.

Jika superadmin memang sedia ada, command tidak boleh mendapatkan kata laluan plaintext daripada hash DB. Jadi kontrak ini tidak boleh dilaksanakan dengan selamat seperti ditulis.

**Pembaikan v1.8:** beku satu pilihan sahaja:

- Pilihan A: wrapper memerlukan `E2E_PROD_SUPERADMIN_EMAIL/PASSWORD` dibekalkan dari luar, hanya disahkan hadir, tidak pernah ditulis oleh `diwan:audit-fixture`; atau
- Pilihan B: `prepare` mencipta superadmin sementara `superadmin-<run_uuid>@smoke.test` dengan password rawak dan cleanup wajib memadamnya.

Saya cadangkan Pilihan A jika production tidak boleh mencipta superadmin sementara. Jika Pilihan A dipilih, keluarkan `E2E_PROD_SUPERADMIN_*` daripada output `prepare`.

### P18-04 — Antivirus intake belum ada gate wajib

P17 menolak "antivirus fixture" kerana CI default `CLAMAV_ENABLED=false` dan `AntivirusScanner` pulang `disabled`. Fakta itu benar untuk lalai CI (`config/diwan.php:32`, `app/Services/AntivirusScanner.php:12`), tetapi ia tidak cukup untuk syarat keselamatan pengguna: intake awam/email/WhatsApp/UI mesti fail-closed apabila ClamAV diaktifkan.

Kod semasa memang mempunyai logik kritikal:

- `InboxIngestService.php:72-78` mengimbas kandungan, menolak `infected`, dan fail-closed jika scan bukan `clean`.
- `InboxIngestService.php:104-106` menyimpan status/signature/masa scan.
- `docker-compose.yml:98` mempunyai healthcheck ClamAV.

Ujian semasa hanya meliputi:

- intake menyimpan status `disabled` (`tests/Feature/DdmsExtendedCapabilitiesTest.php:149-155`);
- lampiran support request ditolak jika antivirus mengesan ancaman (`tests/Feature/GuidanceSupportTest.php:80-108`).

Tiada gate khusus memastikan `InboxIngestService` menolak `infected`, `unavailable`, atau `error` bila `CLAMAV_ENABLED=true` dan `fail_closed=true`, serta memastikan tiada rekod/media/log tenant tercipta.

**Pembaikan v1.8:** tambah D11 artifact wajib, contohnya `tests/Feature/InboxAntivirusFailClosedTest.php`, menggunakan mock `AntivirusScanner` tanpa perlu service ClamAV sebenar. Assert:

- `infected` ditolak dengan mesej ancaman;
- `unavailable`/`error` ditolak bila fail-closed aktif;
- tiada `Record`, media, atau activity log intake tercipta selepas rejection;
- tenant lain tidak berubah.

Fixture EICAR/container boleh jadi bukti tambahan kemudian, tetapi test fail-closed inbox mesti required.

### P18-05 — Bukti `results.json` untuk skip OCR belum ada kontrak reporter

`PELAN-PEMBAIKAN.md:1025-1027` dan metrik `:2703` mahu CI menyemak `results.json` untuk memastikan `ci-ocr` tidak di-skip. Tetapi `playwright.config.js:9` semasa hanya `reporter: [['line']]`; Playwright tidak akan menghasilkan `results.json` secara automatik.

**Pembaikan v1.8:** tetapkan kontrak output machine-readable:

- sama ada command `npx playwright test --project=ci-ocr --reporter=json > bukti/plan-ci/ci-ocr-results.json`; atau
- konfigurasi reporter JSON/JUnit khusus CI dengan path output stabil.

Kemudian skrip assert perlu membaca path itu dan fail jika mana-mana test `skipped`, `timedOut`, `interrupted`, atau tiada test ditemui. Perkara sama berguna untuk `ci-domain` jika metrik hendak dibuktikan daripada artifact, bukan hanya log manusia.

### P18-06 — Kiraan D11 masih bercampur 12, 14 dan 15 artifact

v1.7 sudah membetulkan D11 kepada 14 fail repo + 1 artifact audit pada seksyen utama (`PELAN-PEMBAIKAN.md:1058-1082`) dan jadual D11 (`:3157`). Namun log awal masih menyatakan:

- `D11 dikembangkan 4 → 12 artifak` (`:123`);
- `§11 D11 ditulis semula (4 → 12)` (`:323`).

Jika P18-04 diterima, kiraan baharu menjadi **15 fail repo + 1 artifact audit**. Jika tidak, ia tetap **14 fail repo + 1 artifact audit**. Versi akhir tidak boleh meninggalkan nombor 12 kerana ia akan mengelirukan kelulusan pemilik D11.

**Pembaikan v1.8:** kemas semua log dan peta integrasi kepada angka tunggal. Saya cadangkan `15 fail repo + 1 artifact audit` selepas tambah test antivirus inbox.

### P18-07 — Status kerja perlu bezakan kod aplikasi vs fail perancangan

`PLAN-RR-STATUS.md:73` menyebut "working tree bersih", tetapi realiti semasa:

```text
 M HANDOVER.md
?? Audit Review Round Robin/...
```

Ini bukan isu kod, tetapi isu bukti. Untuk plan yang sangat bergantung pada audit trail, ayat itu boleh disalah baca sebagai tiada fail pending langsung.

**Pembaikan v1.8/status:** tukar kepada ayat tepat:

> Asas kod aplikasi: commit `8342d95`; tiada perubahan kod aplikasi dikesan dalam giliran P18. Working tree keseluruhan tidak bersih kerana `HANDOVER.md` diubah dan fail perancangan round-robin belum dijejak.

## Perkara yang saya sahkan tidak perlu dibuka semula

- P16-01 tentang canary Livewire adalah betul: login Filament ialah Livewire submit handler (`vendor/.../Login.php:459`, `:387-389`) dan medan login aplikasi ialah `TextInput::make('login')` (`app/Filament/Auth/Login.php:21`).
- Kunci set `<guide_id>#<index1>` wajar kerana `step.id` tidak unik global; kiraan semula saya sepadan dengan P17: 83 guide, 473 langkah, 229 `wait_for_user`, 443 sasaran generik, 200 action generic, 258 placeholder, 30 specific, 470/473 `step.id` unik.
- Partition wave dan shard P17 sepadan dengan kiraan semula daripada `resources/help/guides.json`.

## Cadangan untuk Claude P19

v1.8 perlu menutup tujuh perkara di atas sebelum plan boleh dipanggil muktamad:

1. Betulkan kontrak required status check GitHub Actions vs Playwright project.
2. Jadikan YAML CI benar-benar literal, tanpa `...` atau "sama lapis 1".
3. Pilih satu kontrak kredensial superadmin fixture produksi.
4. Tambah gate wajib antivirus fail-closed untuk `InboxIngestService`.
5. Tetapkan reporter/output JSON Playwright untuk assert "tidak di-skip".
6. Seragamkan angka D11.
7. Betulkan status kerja supaya audit trail tidak mendakwa working tree bersih.

Selepas v1.8, Codex P20 patut semak semula hanya tujuh penutupan ini dan imbas frasa lapuk berkaitan `ci-domain`, `results.json`, `4 → 12`, dan `working tree bersih`.

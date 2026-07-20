# DIWAN-SPEC — ADDENDUM v2.2 (Pasca-Lancar, 19 Julai 2026)

> Pindaan kepada `DIWAN-SPEC.md` v2.1 (sumber kebenaran asal kekal). Diluluskan
> pemilik produk. Semua dilaksanakan Fasa A–E dengan ujian hijau + commit per fasa.

## §15.1′ — Log masuk telefon-ATAU-e-mel + gate kata laluan pertama (Fasa B)
- **E-mel kini PILIHAN.** `users.email` nullable (unik apabila diisi). Ahli boleh
  guna **nombor telefon sahaja** — admin masjid selalunya tahu nombor, bukan e-mel.
- Satu medan log masuk "E-mel atau No. Telefon" pada **kedua-dua panel**
  (`App\Filament\Auth\Login`). Input digit → dinormalkan (0→60, sama seperti
  routing WhatsApp) → padan `users.phone_wa`; selainnya → e-mel. Magic link kekal
  laluan utama (§15.1) dan kini boleh dihantar melalui **e-mel + WhatsApp**.
- **Gate kata laluan pertama**: middleware `EnsurePasswordIsSet` (authMiddleware
  kedua-dua panel) — akaun `password=null` dipaksa ke `/tetapkan-kata-laluan`
  sebelum meneruskan (aliran: klik pautan → tetapkan kata laluan → baca surat).
- Had kadar log masuk boleh dikonfigurasi: `DIWAN_LOGIN_RATE_LIMIT` (produksi
  kekal 5/min; e2e naikkan untuk elak flake login banyak-peranan).
- `login_tokens.user_id` (nullable) — token boleh terikat pengguna terus.

## §6.4′ — Kredensial ahli oleh admin (Fasa B)
- `MembershipService::invite` — e-mel pilihan, telefon ATAU e-mel diperlukan;
  identiti dipadan ikut e-mel → telefon (satu akaun global merentas masjid).
- `+resetPassword` (set kata laluan sementara) & `+resendLoginLink` — guard ringkas
  `users.manage` (bukan sekatan admin-terakhir §6.4 penuh). UI di "Ahli & Peranan".

## §10 Aliran I′ — Wizard onboarding (Fasa C)
- Halaman `/app/{slug}/persediaan` (`OnboardingWizard`, `canAccess` mosque.settings):
  wizard 4 langkah — jawatan admin, nombor WhatsApp masjid, daftar ahli (repeater;
  telefon wajib, e-mel pilihan), rumusan. Tandakan `settings.onboarding_done`.
- **1 peranan per ahli dikekalkan** — Pentadbir sudah merangkumi kuasa
  Kerani/Setiausaha (§6.2). `jawatan` = label paparan sahaja.

## §11.2′ — Telegram (Fasa D)
- Command `diwan:telegram-set-webhook` (dahulunya hanya dalam spec).
- Sambung akaun guna **token cache pendek** `Str::random(48)` (deep-link
  `t.me/{bot}?start=` terhad 64 aksara; output `Crypt` terlalu panjang) —
  cabang `Crypt` lama dikekalkan (BC). `TELEGRAM_BOT_USERNAME` baharu.
- Aksi Sambung/Putus Telegram di Profil (app) + halaman "Profil Saya" (/admin).

## §11.1′ / §14′ — WhatsApp platform + pemantauan sesi (Fasa D)
- **Integrasi WhatsApp peringkat platform** (`whatsapp_integrations.mosque_id`
  nullable; `external_id "{instance}:platform"`). Digunakan untuk alert superadmin.
  Halaman `/admin` "WhatsApp Platform" (provision/pair/sync/toggle). `WhatsAppChannel`
  & `WhatsAppGateway::send` guna sesi platform bila `mosque_id` null. Mesej masuk ke
  sesi platform **diabaikan** (fail-closed, tiada intake).
- **Pemantauan**: `diwan:check-wa-sessions` (scheduler /10 min) — sync semua sesi +
  `ConnectionAlertNotification` (e-mel + Telegram + **WA platform**) pada transisi
  connected→terputus (cooldown 60 min) kepada superadmin + admin masjid (e-mel +
  Telegram sahaja — sesi masjid yang mati tidak boleh alert dirinya). + kesihatan
  IMAP (streak). Halaman `/admin` "Status Sambungan".

## Pengasingan tenant (§15.2) — kekal
- Integrasi platform (`mosque_id` null) tidak muncul dalam skop `forMosque` tenant;
  `scopePlatform()` eksplisit. Diuji: `PlatformWhatsAppTest`, `WaSessionMonitorTest`.

## Env baharu
`DIWAN_LOGIN_RATE_LIMIT` (5), `TELEGRAM_BOT_USERNAME`.

## Bukti
Pest **234 passed / 1 skip**; Pint passed; Playwright chromium semua spec LULUS
(registration+explore 9-peranan+office-workflow), ocr-upload skip. Produksi:
`staging-check` 9/9, `diwan:smoke` 9/9, `/up` 200, 7 container running.

---

# ADDENDUM v2.3 (Pusingan 2 — 19 Julai 2026, pasca-ujian pemilik)

> Pindaan susulan selepas ujian pemilik produk mendedahkan 6 kumpulan isu.
> Semua dilaksana F0–F5 (commit per fasa), CI hijau, satu deploy, E2E disahkan.

## §15.7′ — Format dokumen berpusat (F1)
- **Satu sumber kebenaran** `App\Support\AllowedFormats` (config `diwan.allowed_formats`
  peta extension→MIME kanonik). Format sah: **PDF, DOC/DOCX, XLS/XLSX, PPT/PPTX,
  TXT, JPG/JPEG, PNG**. **webp DIBUANG**; doc/xls/ppt (Office lama) + txt DITAMBAH.
- Digunakan SEMUA saluran: `InboxIngestService` (validasi pelayan), `MailIngestService`
  (extension + MIME kanonik), `WhatsAppInboundService` (semak + tangkap
  `ValidationException` → tutup lubang webhook 500), `ListInbox`/`ListRecords`
  (acceptedFileTypes + helperText). MIME diterbit daripada extension (elak
  `mime_content_type` salah label docx=zip).
- **Penolakan bermaklum** setiap saluran: WA balas "format tidak disokong"; e-mel
  kumpul `rejected_format` + notifikasi admin; UI mesej validasi.
- OCR: `txt` diindeks terus; doc/xls/ppt/docx/xlsx/pptx langkau OCR (status Siap).

## §11.3′ — Intake e-mel boleh-lihat + kata kunci PILIHAN (F2)
- **Kata kunci intake e-mel kini PILIHAN** (`Mosque::mailIntakeKeyword` + config
  lalai kosong). Kosong = terima semua daripada pengirim allowlist (allowlist =
  pagar utama). Punca asal "emel tak masuk": subjek tiada kata kunci wajib lama.
- **Hapus kegagalan senyap**: `MailIngestService::recordOutcome` — log SEMUA hasil
  bukan-jaya + simpan diagnostik `settings.mail_intake_last` (dipapar di Tetapan
  Masjid) + `MailIntakeRejectedNotification` (mail+WA+Telegram) kepada admin masjid,
  throttle 1 jam/masjid+sebab. Sebab dimaklum: sender_not_allowed, keyword_missing,
  quota, rejected_format.
- `MailIngestService::isIntakeAddress` + validasi `TetapanMasjid`: tolak alamat
  intake sistem dimasukkan tersilap sebagai "pengirim" (kekeliruan MAMAD).

## §11.2″ — Telegram via UI superadmin (F3)
- Konfigurasi penuh melalui `/admin` Tetapan Platform (TANPA sentuh `.env`):
  aksi "Tetapan Telegram" (token password-revealable disimpan **tersulit**
  `PlatformSetting::putEncrypted`, username, rahsia auto-jana) + "Set Webhook Telegram".
- `TelegramService` (setWebhook/getWebhookInfo + `hydrateRuntimeConfig` statik).
  `AppServiceProvider::boot` suntik config Telegram dari DB (DB-dahulu, fallback env,
  try/catch, cache 5 min). Butang "Sambung Telegram" di Profil muncul selepas
  username diset via UI.

## §10 Aliran I″ — Wizard onboarding muncul sendiri (F4)
- `MagicLoginController::landingUrl` — admin masjid belum selesai persediaan diarah
  ke `/app/{slug}/persediaan?mula=1` selepas login. Banner "Siapkan persediaan
  masjid" di Dashboard (renderHook PAGE_START, scoped Dashboard) sehingga selesai.
  Aksi "Langkau Buat Sementara". (Checklist onboarding TIDAK pernah hilang — hanya
  tidak bergaya sebelum tema F5.)

## §9.0 — Tema Filament v4 + UI/UX (F5)
- **Akar punca UI runtuh**: view custom guna utiliti Tailwind yang tiada dalam CSS
  panel lalai. Fix: tema `resources/css/filament/theme.css` (`@import` vendor +
  `@source` app/Filament + views custom), `->viteTheme()` kedua-dua panel.
- Jenama: `->brandLogo` (SVG kubah "Diwan", **Htmlable** bukan string) + favicon.
- **Dockerfile**: stage `assets` disusun semula (SELEPAS `vendor`, COPY vendor+app)
  — wajib untuk tema build. Nav admin dikumpulkan (Operasi/Platform/Akaun);
  Profil app masuk nav. Widget `ChannelStatusOverview`. AhliPeranan/StatusSambungan/
  Profil guna jadual responsif + badge Filament.

## CI (F0)
- `pint` bersih (5 fail gaya dibaiki). Actions `checkout/setup-node/cache/upload-artifact`
  @v4→@v5 (node24, atasi amaran Node 20). Env CI `DIWAN_LOGIN_RATE_LIMIT=100`
  (cache Redis CI kekal merentas ujian serial); `PhoneLoginTest` clear rate-limiter.

## Env baharu/berubah
`MAIL_INTAKE_ADDRESS` (baharu), `MAIL_INTAKE_KEYWORD` lalai kini KOSONG (pilihan).
Telegram token/username/secret kini boleh via UI DB (tersulit) — env kekal fallback.

## Bukti Pusingan 2
Pest **257 passed / 1 skip**; Pint passed; `npm run build` OK; **CI GitHub HIJAU**
(integration + docker app/web). Playwright chromium: registration + office-workflow
+ explore (9-peranan; flake login DIBAIKI oleh rate-limit config) LULUS, ocr skip.
Produksi (commit `4fcb000`): imej rebuild, `staging-check` **9/9** (imap+smtp),
`diwan:smoke` **9/9**, `/up` 200, tema+logo termuat. **Emel MAMAD dipulihkan**:
4 emel ujian diproses semula → 2 rekod E-mel dalam Peti Masuk (baki dedup).

---

# NOTA VERIFIKASI — Review Codex + E2E Produksi (19 Julai 2026, commit `86264e9`)

> Bukan pindaan spec; rekod semakan bebas ke atas dua commit audit Codex
> (`ff5f844` harden workflows+UI, `ae95d6e` nginx limits) yang sudah LIVE.

## Skop
Review A-Z (butang-ke-butang, isolasi tenant, keselamatan, workflow dokumen, UI)
terhadap kod sebenar + E2E langsung di produksi (Chrome MCP) + SSH server.

## Keputusan
- **12/12 kategori dakwaan Codex disahkan SAH** dalam kod: modal klasifikasi + edaran
  minit (§9.C.5/§14), ganti versi semak format/kuota (§9.C.4), eksport ZIP recheck role
  + `visibleTo` (§16.4), download policy tiada auto-requester, privasi notifikasi
  approval/minit (§14), batal pesanan storan superadmin+kata laluan+audit (§10.J),
  filter carian tenant-safe (§13), webhook WA fail-closed rahsia kosong (§11.1),
  header keselamatan global + Horizon gate superadmin (§0.3), nginx rate-limit,
  homepage/dashboard, label BM retensi.
- **Penemuan tunggal (F-1):** `RetentionRuleForm.php` (Schemas) ialah **dead code**
  (sifar rujukan) — Codex mengeraskannya tetapi borang HIDUP ialah
  `RetentionRuleResource::form()` inline yang tidak mendedah `mosque_id`, dilindungi
  `getEloquentQuery()` skop tenant + `CreateRetentionRule::mutateFormDataBeforeCreate`.
  Retensi sudah tenant-safe. **Tindakan:** tambah guard eksplisit
  `EditRetentionRule::mutateFormDataBeforeSave` (paksa `mosque_id` = tenant semasa) +
  `RetentionTenantScopeTest` (cross-tenant edit → 404; simpan tamper-safe). §15.2.
- **F-3 (nota, bukan pepijat):** had nginx `~ ^/(app|admin)/login` hanya lindungi GET
  halaman login; percubaan log masuk sebenar (Livewire `/livewire/update`) dilindungi
  limiter aplikasi `DIWAN_LOGIN_RATE_LIMIT` + had global nginx 10r/s (burst 40, conn 40).
- **Nota kosmetik:** `x-frame-options`/`x-content-type-options`/`referrer-policy` dihantar
  DUA kali (nginx `add_header` + middleware `AddSecurityHeaders`) — benign (nilai sama).

## Bukti
Pint bersih · Pest **271 passed / 1 skip** (269 + 2 ujian tenant retensi) · **CI GitHub
HIJAU** (`86264e9`: integration + docker app + docker web) · Playwright **5/5**
(office-workflow termasuk ujian klasifikasi-minit Codex, registration, explore +
cross-tenant 404). 🐛 Kegagalan Playwright "klasifikasi-minit" pada mulanya = **server
`php artisan serve` hantu** (dua proses bind :8092 dari sesi lalu → Windows edar request
rambang → jadual peti masuk kosong); membunuh server basi → LULUS. (Punca sama untuk
"flake explore 9-peranan" yang direkod dahulu.)

## E2E Chrome produksi (tenant `smoke`, login magic-link server — tiada kata laluan ditaip)
- **Superadmin**: dashboard (widget Kesihatan Saluran + Ringkasan Platform ikon/warna),
  Tenant/Organisasi, Akaun Pengguna, Pesanan Storan + **modal Batal (Sebab + Sahkan Kata
  Laluan)**, Tetapan Platform + **Telegram (@spdmediwanbot, webhook Berjaya)**, Status
  Sambungan (F5), Profil (Telegram Bersambung, 3 saluran ON) — semua render + berfungsi.
- **Workflow tenant LIVE**: kerani klasifikasi rekod (modal memuat SEMUA medan — Jenis,
  Arah, Ruj Kami/Tuan, u.p., Failkan Ke, Tahap Akses + helper max-waris, dan **Edaran
  Minit**: Untuk Tindakan, Untuk Makluman s.k., Catatan/Arahan, Keutamaan) + minit Segera
  → **pengerusi terima di Minit Saya → Tanda Selesai → Selesai**. Kelulusan terbukti
  `diwan:smoke` 9/9.
- **Isolasi tenant (§15.2)**: pengerusi-smoke → `/app/mamad/*` = **404**.

## Deploy
`local = origin = server = 86264e9`; `/up` 200; `diwan:smoke` **9/9**;
`diwan:staging-check` 8/9 (smtp "GAGAL WAJIB" = perlu `--mail-to`, config tidak berubah).

---

# INSIDEN + REKA BENTUK: Intake WhatsApp Kata-Kunci-Dahulu (19 Julai 2026, commit `48c8b2f`)

> Insiden operasi sebenar dilaporkan pemilik + pembetulan reka bentuk (diluluskan pemilik).
> Bukan pindaan spec asas; menggantikan tingkah laku balasan intake WA §11.1.

## Insiden
Nombor sesi MAMAD (60176811605) menghantar mesej berulang ke nombor **tidak dikenali**
(0174632511, 0173070193, 0123198704, 0139718582, 01123744631). Disahkan 100% via jadual
`NotificationLog`: **37 balasan `wa_reject`** ("Maaf, tidak berdaftar…") ke 6 nombor
(5 bukan-ahli), letusan pantas (19 Jul 16:56–17:07 ~25 dlm 4 min). Nombor itu **tidak
pernah menghantar mesej tulen** — 37 event webhook **TIADA `message_id`** (0 kunci dedup
`wa_msg` dalam Redis; mesej WhatsApp tulen sentiasa berID) = **echo/sintetik dari gateway**.

## Punca akar
`WhatsAppInboundService::handle()` (kod lama) membalas `wa_reject` pada **SETIAP** mesej
bukan-ahli **tanpa had kadar**; event tanpa `message_id` tidak di-dedup. Gateway echo mesej
keluar sebagai event masuk `from_me:false` → Diwan balas → **gelung ping-pong**. (`WhatsAppGateway::send`
merekod setiap penghantaran ke `NotificationLog`; driver `gateway` TIDAK `Log::info` → Docker
log kosong. Guna `NotificationLog` sebagai audit hantar WA.)

## Reka bentuk baharu — kata-kunci-dahulu (§11.1′)
Diwan **SENYAP sepenuhnya** melainkan penghantar:
1. menghantar kata kunci intake **TUNGGAL tepat** (cth `spdm`), ATAU
2. sedang dalam **tetingkap intake aktif** (`intake_window_minutes`, lalai 10), ATAU
3. menghantar **dokumen dengan kata kunci dalam kapsyen**.

Perbualan biasa / echo / mesej panjang tanpa kata kunci → **TIADA balasan**. Balasan Diwan
sendiri tidak mengandungi kata kunci → echo takkan mencetus → **gelung mustahil**.

- `spdm` → buka tetingkap 10 min → hantar dokumen (boleh berbilang) → `spdm` semula = buka semula.
- **Submission awam** (`allow_public_intake`, lalai `true`): orang luar tiada akaun boleh
  hantar dokumen selepas kata kunci; kerani semak di Peti Masuk. `WHATSAPP_ALLOW_PUBLIC_INTAKE=false`
  = ahli sahaja. **Pengguna berdaftar masjid LAIN diblok** (isolasi §18.37 kekal — semak
  `mosque_user.phone_wa` global). Had `submission_cap` (10) / `submission_window_minutes` (60) per nombor.
- Backstop: `replySuppressed()` menghadkan HANYA balasan penolakan (wa_reject/wa_quota) —
  cooldown per nombor + pemutus litar; balasan kejayaan (ack/intake_ready) sentiasa dihantar.
- Log ringkas event masuk (tanpa media) untuk audit.

## Env baharu
`WHATSAPP_INTAKE_WINDOW_MINUTES` (10), `WHATSAPP_ALLOW_PUBLIC_INTAKE` (true),
`WHATSAPP_SUBMISSION_CAP` (10), `WHATSAPP_SUBMISSION_WINDOW_MINUTES` (60),
`WHATSAPP_REJECT_COOLDOWN_MINUTES` (60), `WHATSAPP_REPLY_CAP` (5), `WHATSAPP_REPLY_CAP_WINDOW_MINUTES` (10).

## Bukti
`WhatsAppWebhookTest` **17 lulus** (senyap tanpa kata kunci; submission awam creator-null;
mod ahli-sahaja; §18.37 senyap; dua-langkah; kapsyen-kata-kunci). Pest **273 lulus/1 skip**;
Pint bersih; **CI HIJAU**. **Ujian LIVE server:** `handle()` dengan mesej bukan-kata-kunci →
`NotificationLog` tidak bertambah = **SENYAP disahkan** di produksi.

---

# GATE GO-LIVE + FIX BORANG KLASIFIKASI (19 Julai 2026, fix `268f860`)

> Rekod menjalankan 3 gate go-live baki (arahan pemilik) pada server SEBENAR + satu
> pepijat produksi ditemui melalui gate pantau-log, dibaiki, di-deploy & disahkan live.

## Gate 1 — Isolasi silang-tenant (data produksi, tenant mamad id1 vs smoke id2)
Skrip tinker terhadap DB produksi:
- **Skop data `forMosque()`** 6 model (Record 8/4, RegistryFile 1/3, ClassificationNode 40/40,
  Minit 2/4, Approval 0/3, StorageOrder 1/1) — **sifar baris bocor**.
- **Global scope Filament** (login Admin MAMAD, tenant MAMAD aktif): `Record::count()=8`
  (MAMAD sahaja), `RegistryFile::count()=1`; rekod/fail smoke `whereKey()->exists()`=false,
  `find()`=NULL. Membuktikan laluan HTTP sebenar terlindung.
- **Polisi `RecordPolicy::view`**: ahli B tak boleh view rekod A & sebaliknya (`canIn($record->mosque)`).
- **SearchService fail-closed**: `for(ahli B, tenant A)`=kosong (bukan ahli), sebaliknya sama.
- **Sesi WA + alias e-mel** terasing (`sess_…` MAMAD vs kosong smoke; scan+mamad@ vs scan+smoke@).
- **RetentionRule**: sengaja TIADA `forMosque` (perlu lihat peraturan platform `mosque_id=NULL`,
  §5.11) — 18 peraturan platform dikongsi; isolasi di lapisan resource/`getEloquentQuery` +
  guard `EditRetentionRule` (RetentionTenantScopeTest). **Keputusan: SEMUA LULUS.**

## Gate 2 — Restore drill backup
`backup:run --only-db` → zip 37KB → disk `cos_backup` (COS ap-jakarta). Tarik balik zip dari
COS (round-trip) + unzip → `db-dumps/postgresql-diwan.sql` **216,547 bytes, 32 CREATE TABLE +
32 COPY**, semua jadual utama (mosques/records/users/minits/approvals/registry_files/
classification_nodes) hadir = **dump sah & boleh dipulihkan**. Backup harian `02:30` (scheduler).

## Gate 3 — Pantau log → BUG DITEMUI + DIBAIKI
Log live 3 jam: **0 ralat app, 0 nginx 5xx, 0 failed_jobs**. `laravel.log` mendedah pepijat:
`Filament\Forms\Components\Select::modifyQueryUsing does not exist` (BadMethodCallException,
userId 1, 12:20) → borang **Cipta/Edit Nod Klasifikasi CRASH** pada render.
- **Punca:** `ClassificationNodeForm` guna `->relationship('parent','title')->modifyQueryUsing(...)`
  sebagai method BERANTAI pada Select — tidak wujud dalam Filament v4 (tandatangan sah:
  `relationship($name, $titleAttribute, ?Closure $modifyQueryUsing, ...)`, Select.php:781).
- **Fix (`268f860`):** hantar closure skop tenant sebagai **argumen ke-3** `relationship()`.
  Global scope `BelongsToMosque` juga aktif; skop eksplisit dikekalkan (peraturan #10 / §15.2).
- **Ujian regresi** `ClassificationNodeFormTest` (4): borang Cipta/Edit render tanpa ralat +
  cipta nod induk sendiri berjaya + nod induk masjid lain ditolak (skop tenant kekal).
- **Disahkan LIVE (Chrome, tenant smoke):** borang render penuh; dropdown "Nod Induk" muat
  nod skop-tenant (Aktiviti & Program, Audit & Pemeriksaan, Bajet & Penyata…).

## Nota ops
- Config `monitor_backups.disks` diperbetul `['local']` → `[env('BACKUP_DISK','cos_backup')]`
  supaya `backup:list`/`backup:monitor` menyemak disk destinasi SEBENAR (dulu lapor "tiada
  backup" palsu). `backup:monitor` belum dijadualkan — hanya `backup:run` (02:30).
- **OCR 6/13 rekod produksi `ocr_status=gagal`** (ralat "Couldn't find trailer dictionary/xref
  table" = PDF input rosak/terpotong pada fail ujian pilot). OCR gagal-anggun pada PDF rosak =
  betul; **perlu semakan pemilik dengan dokumen sebenar** untuk sahkan bukan isu format meluas.

## Bukti
Pest **277 passed / 1 skip** (931 assertions); Pint bersih; deploy rebuild imej app;
`/up` 200; 7 container healthy; isolasi + restore + render borang disahkan pada produksi.

---

# ADDENDUM v2.4 — Ekstrak Teks Office (Fasa 2) + Penjajaran Tatacara ANM + Naik Taraf OCR (20 Julai 2026)

> Diluluskan pemilik (soalan-jawapan 20 Jul). Berdasarkan kajian PENUH 2 dokumen rasmi:
> *Tatacara Pengurusan Rekod Elektronik dalam DDMS di Pejabat Awam* (Arkib Negara, 2020) +
> *Panduan Pengguna DDMS 2.0* (MAMPU). Commit `081ff2e` (office+ocr), `0d72f57` (tatacara).

## Pindaan §3.3 — pakej baharu (kelulusan pemilik)
Ditambah `phpoffice/phpword ^1.4` + `phpoffice/phpspreadsheet ^5.9` untuk ekstrak teks
Office. ext-zip/dom/xmlreader/simplexml sudah dalam imej Docker (`docker/Dockerfile:19`).
Larangan §0.3 lain kekal.

## §20′ / §12 — Ekstrak teks Office boleh dicari (Fasa 2 → dilaksana)
- `App\Support\OfficeTextExtractor::extract($path, $ext)` — **PhpOffice utama** (docx=PhpWord,
  xlsx/xls=PhpSpreadsheet), **sandaran native ZipArchive+XMLReader** (bila PhpOffice gagal
  atau xlsx > 8MB → streaming jimat RAM worker 768M). **pptx = native terus** (PhpPresentation
  tidak dipasang). doc/xls lama = cubaan terbaik; ppt lama = metadata sahaja.
- `ProcessOcrJob` laluan Office (dahulu no-op `ocr_text=null`) kini muat turun fail → ekstrak →
  `ocr_text` (had `diwan.ocr_text_limit`) + `searchable()`. Gagal/tak dapat → `ocr_text` null,
  status Siap (rekod kekal sah). `searchableAttributes` Meili sudah termasuk `ocr_text` — tiada
  perubahan indeks. **Fail asal TIDAK diubah.**

## §12′ — Naik taraf kualiti OCR
- `ocrmypdf` +`--clean` (pra-proses unpaper, imej Docker) +`--optimize 1` (lossless) — ketepatan
  & kekemasan lebih baik. KEKAL `--output-type pdf` (regresi Ghostscript 10.0–10.02). jbig2enc /
  naik taraf Ghostscript / PDF-A = **nota tangguh** (belum dibuat).
- `OfficeTextExtractor::normalizeText()` — sambung suku kata terpotong hujung baris
  (`maklu-\nmat`→`maklumat`), buang aksara kawalan, kemas whitespace. Diguna pada **sidecar OCR**
  + teks Office. `searchable.pdf` tidak disentuh.

## §13′ — Snippet + highlight carian
- `SearchService::snippetFor(Record, query)` — petikan ±90 aksara sekitar padanan pertama
  (ocr_text → tajuk → rujukan → pengirim). `SearchService::highlight()` — teks di-escape DAHULU,
  padanan dibalut `<mark>` gaya inline → selamat XSS. `CariRekod` + blade papar snippet.

## §6.5.9 / §8 — "u.p." (Untuk Perhatian) hibrid + auto-cadang
DDMS metadata "Surat Menyurat" TIADA medan u.p. — dalam tatacara, u.p. ialah keputusan
**PENGEDARAN** (§6.5.9: hantar terus kepada pegawai + s.k. Ketua/Timbalan). Diwan menjadikannya
**hibrid**: `metadata.untuk_perhatian` kekal teks bebas (nama pada surat mungkin bukan pengguna)
+ **datalist autocomplete** nama ahli; bila padan ahli aktif (tepat) → `attentionSuggestion()`
cadang ahli itu sebagai penerima "Untuk Tindakan" (hanya jika kerani belum pilih) + Pengerusi ke
s.k. Kerani bebas ubah. Guard kewujudan medan minit → wizard "Rekod Baharu" tidak terjejas.

## Penjajaran tatacara lain
- **§10 Aliran D — Ruj. Kami auto**: `InboxIngestService::fileRecord` isi `our_ref` =
  `file_no(enclosure)` bila kosong (DDMS: No. Rujukan Kami WAJIB). Manual tidak ditindih.
- **Carta 8.1 — Tarikh Terima**: prefill modal = tarikh masuk Peti Masuk.
- **§6.9.1 — fail 100 kandungan**: helperText amaran pada "Failkan Ke" bila ≥90/≥100 (tidak menyekat).
- **§6.4.2 — s.k. boleh balas**: `MinitPolicy::reply` kini benarkan penerima makluman + pengirim
  (bukan tindakan sahaja). Tanda Selesai (`complete`/`respond`) kekal terhad penerima tindakan.

## Rumusan perbandingan Diwan vs Tatacara ANM/DDMS (untuk rujukan)
- **SELARAS**: tawan-dulu-sebelum-edar (§6.3.1=Peti Masuk); catatan atas surat dilarang, minit
  digital (§6.4.1); s.k.=makluman tanpa tindakan (§6.4.2); arahan via fungsi Minit + penerima
  dipilih + keutamaan (§6.5.7; DDMS 4.4.5); catat tindakan + maklum pemberi (§6.5.8/10); kod
  klasifikasi Fungsi-Aktiviti/Sub/Transaksi `AKRONIM.###-#/#/#` (§5.13.1=`MAM.900-1/1`); semak
  klasifikasi sebelum buka fail (§6.2). Diwan minit **lebih kaya** (threading, SLA 7/3/1, resit baca).
- **JURANG DITUTUP** oleh addendum ini: u.p. berstruktur, Ruj. Kami auto, tarikh terima, amaran 100, s.k. balas.
- **BEZA REKA BENTUK (sengaja)**: KF Diwan operasi, BUKAN diluluskan Arkib Negara (spec §7); eskalasi
  SLA automatik + auto-klasifikasi AI + ekstrak teks .doc/.ppt lama = luar skop (nota Fasa 2 kekal).

## Bukti v2.4
Pest **296 passed / 1 skip** (971 assertions); Pint bersih; `npm run build` OK; Playwright
chromium **office-workflow + explore + registration LULUS** (server e2e tunggal, DB buangan);
ujian baharu: OfficeTextExtractionTest (7) + SearchSnippetTest (6) + TatacaraAlignmentTest (7).

---

# ADDENDUM v2.5 — Mirror Google Drive per-tenant + Magic link auto-login notifikasi + Fix salib landing (20 Julai 2026)

> Diluluskan pemilik (soalan-jawapan 20 Jul). Tiga ciri besar + satu ops.
> Commit: `1bc5cc0` (salib), `9789bfd` (magic teras), `c15e8d6` (notif deep-link),
> `b5bff77`/`166f421`/`a0c8844` (Google Drive).

## Pindaan §3.3 — pakej baharu (kelulusan pemilik)
Ditambah `google/apiclient ^2.16` (Drive-only cleanup: `extra.google/apiclient-services=["Drive"]`
+ `scripts.pre-autoload-dump=Google\Task\Composer::cleanup`). Tiada ext PHP baharu (guna curl/guzzle
sedia ada). Larangan §0.3 lain kekal.

## Pindaan §4.6 — Mirror Google Drive boleh-browse (keputusan PDPA pemilik)
Spec asal §4.6 hadkan salinan luar ke Google kepada bentuk TERSULIT sahaja (rclone crypt). **Pemilik
LULUS (20 Jul) mirror plaintext boleh-browse SEMUA rekod** ke akaun Google sendiri sebagai jaring
keselamatan tambahan (backup COS + pg_dump tersulit KEKAL). Rekod keputusan: risiko PDPA merentas
sempadan diterima pemilik; akaun Google milik pemilik; skop `drive.file` (least privilege).
- **Pokok**: `SPDM/Backup/{slug}/{fungsi kod-tajuk}/{aktiviti}/[{sub}]/{file_no - tajuk fail}/{enclosure - tajuk}.ext`;
  DB dump → `SPDM/Backup/_Pangkalan-Data/`.
- **Isolasi (§15.2, keperluan #1)**: resolusi folder SENTIASA dari id induk tersimpan
  (`mosques/registry_files/classification_nodes.gdrive_folder_id`) + assert `mosque_id` setiap hop;
  job `SyncRecordToDriveJob` refetch berskop `mosque_id` (rekod tenant lain tidak dijumpai walau id
  dipalsukan). Diuji: `GoogleDriveMirrorTest` tamper silang-tenant = SIFAR upload.
- **Aliran**: kelulusan masjid → `CreateMosqueDriveFolderJob`; klasifikasi/pindah/ganti-versi →
  `SyncRecordToDriveJob` (afterCommit); **pelupusan → padam salinan Drive** (keputusan pemilik: selaras
  sijil pelupusan ANM) + null kolum. Reconcile setiap jam (`diwan:drive-reconcile`, minit 20) tangkap
  yang tercicir/berubah + muat naik DB dump + prune. Verify: `diwan:drive-verify`.
- **Superadmin UI**: `/admin` Tetapan Platform → Tetapan/Sambung/Uji/Putus Google Drive + kad status
  (akaun/kuota/kesihatan). OAuth akaun pemilik (client id/secret di Google Cloud Console — sekali sahaja).
- **Ketahanan**: ralat maut (`invalid_grant`/kuota penuh) → pemutus litar 6 jam + alert superadmin
  (e-mel/Telegram, throttle 24 jam). Supervisor Horizon `backup` (1 proses, memori 256M).
- Env/PlatformSetting: `gdrive_client_id`, `gdrive_client_secret`+`gdrive_refresh_token` (tersulit),
  `gdrive_enabled`, `gdrive_account`, `gdrive_root_folder_id`, `gdrive_status`, `gdrive_keep_dumps` (7),
  `DIWAN_MAGIC_LINK_TTL_HOURS` (72).

## §15.1″ — Magic link interstisial + deep-link auto-login (notifikasi)
- **Interstisial**: `GET /masuk/{token}` TIDAK lagi guna token — papar halaman "Teruskan" (auto-submit JS).
  Bot pratonton pautan (WhatsApp/Telegram) tidak jalankan JS → **token tidak terbakar oleh pratonton**.
  `POST /masuk/{token}` guna token (atomik, sekali guna) → log masuk → deep-link.
- **Deep-link**: notifikasi yang meng-mention penerima (minit tindakan/makluman, kelulusan, peti masuk)
  kini bawa pautan magic PER PENERIMA (`MagicLinkService::deepLinkFor`, TTL 72 jam, `purpose=notification`).
  Klik → auto-login + terus ke rekod/minit tanpa login manual. Akaun tanpa kata laluan → gate set-kata-laluan
  dahulu → sambung ke sasaran (guna semula `EnsurePasswordIsSet` + `url.intended`).
- **Fix bounce**: selaras `password_hash_web` sesi dengan pengguna baharu (elak Filament `AuthenticateSession`
  paksa-logout bila sesi pengguna lain masih aktif). **Keselamatan**: `safeIntended()` whitelist relatif
  `/app|/admin|/r` (sekat open redirect). `login_tokens` + `intended_url` + `purpose`.

## §14″ — Deep-link dalam 6 notifikasi
Trait `HasMagicDeepLink` (memo per penerima → 1 token walau merentas mail+WA+Telegram). Diguna:
MinitRouted/MinitReminder/MinitCompleted + ApprovalRequested/ApprovalDecided (→`/r/{ulid}`) + InboxNewItem
(→`/app/{slug}/peti-masuk`). Penerima bukan User → pautan biasa (perlu login).

## §9.0′ — Fix imej landing (salib → bulan sabit)
Hero SVG landing (`guest-layout`) melukis salib Latin hijau di puncak kubah → diganti tiang + bulan sabit
(imej Islamik). Tiada aset salib lain di UI awam (audit penuh).

## Ops — Re-OCR rekod Office lama
Rekod Office (doc/docx/xls/xlsx/ppt/pptx) sebelum Fasa 2 (`ocr_text=null`) → re-dispatch `ProcessOcrJob` +
`scout:import` supaya kandungan boleh dicari.

## Bukti v2.5
Pest **326 passed / 1 skip** (1091 assertions); Pint bersih; `npm run build` OK; **Playwright registration
(interstisial magic-link) + office-workflow (klasifikasi/minit/kelulusan) LULUS** (DB benih segar; explore
9-peranan = flake had-kadar terdokumentasi, diliputi Pest RoleAuthorizationMatrixTest). Ujian baharu:
MagicLinkDeepLinkTest (10) + NotificationDeepLinkTest (4) + GoogleDriveConnectTest (5) + GoogleDriveMirrorTest (8)
+ GoogleDriveReconcileTest (4) + MagicLinkTest dikemas.

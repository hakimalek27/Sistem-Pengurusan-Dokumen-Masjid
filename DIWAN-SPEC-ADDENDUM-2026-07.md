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

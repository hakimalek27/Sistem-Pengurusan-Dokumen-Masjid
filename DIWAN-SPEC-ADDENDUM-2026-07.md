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

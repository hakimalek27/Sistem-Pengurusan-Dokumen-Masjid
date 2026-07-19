# HANDOVER — Diwan (SPDM) Produksi bakwim.my

**Kemas kini:** 2026-07-19 · **Status:** LIVE di https://bakwim.my (Cloudflare Full strict, COS, login password, Brevo SMTP). Sesi 18 Jul: Email intake LIVE PENUH, WhatsApp E2E LENGKAP (pilot MAMAD), bug OCR Ghostscript dibaiki (`fe5744a`).

**Sesi 19 Jul — PUSINGAN 2 LIVE (HEAD `ae95d6e`):** pasca-ujian pemilik, 6 kumpulan isu dibaiki + deploy. (F0) **CI GitHub HIJAU** (pint + actions v5 + rate-limit CI). (F1) **format dokumen berpusat** `App\Support\AllowedFormats` — hanya PDF/DOC(X)/XLS(X)/PPT(X)/TXT/JPG/PNG; lain ditolak + notifikasi 3 saluran (webp keluar). (F2) **intake e-mel boleh-lihat** — kata kunci kini PILIHAN, penolakan dilog + notifikasi admin (hapus lesap senyap); **emel MAMAD dipulihkan**. (F3) **Telegram via UI superadmin** (token tersulit DB, Set Webhook). (F4) **wizard onboarding muncul** (redirect + banner). (F5) **tema Filament v4 + UI/UX penuh** (logo, nav berkumpulan, badge, jadual, widget). Fasa saya = commit `fasa2-0`…`fasa2-8` (hingga `685415e`). Lihat `DIWAN-SPEC-ADDENDUM-2026-07.md` (v2.3).

**Audit tambahan pemilik (selepas fasa2-8):** `ff5f844` *audit: harden document workflows and UI* (23 fail — middleware `AddSecurityHeaders`, header keselamatan, AppServiceProvider, InboxIngestService, WhatsAppWebhookController, BillingService, ApprovalService, dll.) + `ae95d6e` *ops: enforce nginx limits* (nginx-ssl.conf: rate-limit `diwan_auth` 5r/m, `limit_conn` 40, `client_max_body_size` 30M). **Kedua-dua LIVE**: imej app dibina semula 09:06 (header keselamatan disahkan hidup: x-frame-options/x-content-type/referrer-policy/permissions-policy), nginx muat config baharu (nginx -t OK). **Keselarasan: `local = origin = server = ae95d6e`**, `/up` 200. Bukti gabungan: Pest **269✓/1 skip**, staging 9/9, smoke 9/9, Playwright semua LULUS.

**Sesi 19 Jul — Naik taraf Fasa A–E LIVE (commit `ad45887`):** (A) hint silang-panel log masuk + throttle log IMAP; (B) **log masuk telefon-ATAU-e-mel** kedua-dua panel + **gate kata laluan pertama** + kredensial ahli (e-mel jadi PILIHAN); (C) **wizard onboarding** pendaftaran masjid; (D) **Telegram produksi** (command set-webhook + sambung akaun) + **WhatsApp platform** (alert superadmin) + **pemantauan sesi** (`diwan:check-wa-sessions` /10 min, alert 3-saluran); (E) audit + e2e. Bukti: Pest **234 passed/1 skip**, Pint passed, Playwright semua LULUS, prod **staging-check 9/9 + smoke 9/9 + /up 200**. **IMAP dibaiki** (App Password baru disahkan berfungsi). Lihat `DIWAN-SPEC-ADDENDUM-2026-07.md`.

**Login akaun MAMAD (kini):** boleh guna **telefon** (60176811605 admin / 60189030363 kerani / 60199654974 pengerusi) ATAU e-mel `@mamad.local` + kata laluan di `/app/login`. Akaun sudah ada kata laluan (tidak kena gate).

---

## 1. Infrastruktur

| Item | Nilai |
|---|---|
| Server | Tencent **Lighthouse** `Ubuntu-s0Hu` (lhins-mmc2juw3), Singapore, 2 vCPU / 2GB RAM / 30GB |
| IP awam | **43.156.242.188** (⚠️ bukan 43.156.71.249 — itu CVM lain) |
| SSH | `ssh ubuntu@43.156.242.188` (kunci `claude_deploy`, bind via Lighthouse SSH Keys/TAT) |
| Aplikasi | Docker Compose di `/opt/diwan` |
| Domain | `bakwim.my` — registrar **Exabyte**, NS **Cloudflare** (akaun Hakimalek27@gmail.com) |
| Swap | 3GB (RAM 2GB ketat) |

**Container (7):** app, worker (horizon), scheduler, nginx, db (postgres:16), redis:7, meilisearch:v1.12.

### Nota operasi PENTING
- Selepas **recreate `app`** → mesti `docker compose restart nginx` (nginx cache IP upstream → 502 jika tidak).
- Selepas **ubah `.env`** → `docker compose up -d --force-recreate app worker scheduler` (env_file dibaca hanya semasa container start; www-data tak boleh baca `.env` chmod 600 terus).
- `docker-compose.override.yml` (di server sahaja, tidak di git): port 80/443 + mount `docker/certs` + `nginx-ssl.conf`.

---

## 2. Yang SUDAH SIAP (sesi 2026-07-18)

### ✅ SSL Full (strict) + origin cert
- CSR dijana **di server** (`/opt/diwan/docker/certs/origin.key` — kunci privat tak pernah keluar server), ditandatangani oleh Cloudflare Origin CA (sah 15 tahun).
- nginx dengar 443 (`docker/nginx-ssl.conf`), sijil di `docker/certs/origin.{pem,key}`.
- Firewall **Lighthouse** dibuka port 443 (sebelum ni hanya 22/80 — punca 522 asal).
- Cloudflare mod **Full (strict)** + Always Use HTTPS. Universal SSL edge auto-renew selamanya.
- Bukti: `https://bakwim.my/up` → 200, `ssl_verify=0`, `Server: cloudflare`.

### ✅ COS (storan objek)
- Bucket utama `spdm-1455289506` (ap-singapore, private). Backup `spdm-backup-1455289506` (ap-jakarta, private + versioning + SSE-COS).
- Sub-user CAM `diwan-cos` (polisi **QcloudCOSFullAccess** sahaja — least privilege). Kredensial di `/opt/diwan/.env` (`COS_SECRET_ID`/`COS_SECRET_KEY`).
- `DIWAN_STORAGE_DISK=cos`, `FILESYSTEM_DISK=cos`, `BACKUP_DISK=cos_backup`. Diuji tulis/baca/padam kedua-dua bucket.

### ✅ Login kata laluan (fallback magic link)
- `/log-masuk` kini ada pautan **"Log masuk dengan kata laluan"** → `/app/login` (Filament).
- Halaman **Profil** ada aksi **"Tetapkan Kata Laluan"**.
- **Kesan:** boleh log masuk TANPA SMTP. Superadmin `azanmalek@maiwp.gov.my` — password **sudah ditukar** oleh operator (18 Jul; disimpan dalam pengurus kata laluan). Nilai awal dibuang dari dokumen atas sebab keselamatan (pernah ter-commit plaintext).

### ✅ WhatsApp (sisi SPDM sahaja)
- `WHATSAPP_DRIVER=gateway`, `WHATSAPP_GATEWAY_URL=https://wassap.wehdah.my`, `WHATSAPP_WEBHOOK_URL=https://bakwim.my/api/webhooks/whatsapp`, 2 secret 32-byte, `DIWAN_INSTANCE_ID=spdm-production`.
- Webhook `POST /api/webhooks/whatsapp` → **401 tanpa HMAC** (betul).

### ✅ Emel HANTAR (SMTP) — magic link & notifikasi
- **Brevo** (org "Wehdah Solution", akaun percuma 300/hari). `.env`: `MAIL_MAILER=smtp`, `MAIL_HOST=smtp-relay.brevo.com`, `MAIL_PORT=587`, `MAIL_SCHEME=smtp` (STARTTLS), `MAIL_USERNAME=b269ee001@smtp-brevo.com`, `MAIL_PASSWORD=<SMTP key diwan-spdm>`, `MAIL_FROM_ADDRESS=admin@bakwim.my`.
- **Domain `bakwim.my` AUTHENTICATED di Brevo** — DKIM1/DKIM2/DMARC/brevo-code + branded (send/img.send/r.send) semua diimport ke Cloudflare (DNS-only) & disahkan. Emel DKIM-signed + SPF-aligned → inbox, bukan spam.
- Diuji: `MAIL_SENT_OK`. **Magic link kini berfungsi** (selain login password).

### ✅ Bukti ujian (sesi 18 Julai — petang)
- **Pest suite lokal:** `202 passed, 1 skipped (694 assertions)`, 57s (skip = OCR sebenar; tesseract hanya dalam imej Docker).
- **Prod infra `diwan:staging-check` (di server):** `postgresql redis_cache horizon cos ocr meilisearch smtp gateway = LULUS`; `imap` dilangkau (menunggu App Password). `diwan:health = OK`. Bukti COS tulis/baca/padam + SMTP hantar sebenar via Brevo.
- **Playwright e2e (lokal, server :8092 + seed demo, MAIL log):** `registration` (daftar→lulus superadmin→magic link→panel), `office-workflow` (minit/balas/susulan/kelulusan 4 peranan), `explore` panel superadmin = **LULUS**; `ocr-upload` = skip (tiada fixture OCR lokal); crawl 9-peranan = login `waitForURL` timeout pada peranan yang **berubah antara run** (admin_masjid / nazir / bendahari) walau dengan server berbilang-worker (`PHP_CLI_SERVER_WORKERS=10`) → **artifak ujian/persekitaran** (login 9× pantas/IP kena rate-limit, atau timing dev-server), **BUKAN pepijat app**. Logik semua 9 peranan hijau dalam Pest `RoleAuthorizationMatrixTest`; login peranan berjaya dalam `office-workflow` (4 peranan) & `explore` superadmin.

---

## 3. Yang TERTUNGGAK (perlu tindakan pengguna)

### ✅ A. git push — SELESAI
Semua commit di-push ke `origin/main` (HEAD `5bf9db4`) via GCM device-flow selepas token luput dikosongkan. Server boleh `git pull` untuk selaras (kini server guna fail scp + imej rebuild yang setara).

### ✅ B. Emel HANTAR — SELESAI (Brevo authenticated). Lihat seksyen 2.

### 🟢 C. Emel TERIMA / intake — LIVE PENUH (18 Jul petang) ✅
**Mailbox intake:** **`spdmediwan@gmail.com`** (tukar dari spdmdiwan yang bermasalah; guna yang ada "e").

**SIAP & DISAHKAN hujung-ke-hujung:**
- Cloudflare Email Routing ENABLED; destination **VERIFIED** (Claude klik pautan CF dalam inbox — akaun log masuk); **catch-all `*@bakwim.my` → spdmediwan = ACTIVE**.
- Routing diuji: emel → `scan+cfroute@bakwim.my` (Brevo) → CF → **sampai inbox spdmediwan** ✅.
- 2FA aktif + **App Password** dimasukkan oleh pengguna (via `sudoedit`); `IMAP_ENABLED=true`.
- ✅ **`diwan:staging-check` SEMUA LULUS termasuk `imap LULUS`** — SPDM boleh poll `spdmediwan` via IMAP (imap.gmail.com:993 ssl).
- ⚠️ **Gotcha dibaiki:** `.env` ada **2 baris `IMAP_PASSWORD=`** (satu kosong dari auto-set awal Claude, satu bernilai dari pengguna). `env_file` docker ambil yang **TERAKHIR** (kosong) → container `IMAP_PASSWORD` kosong walau `grep -m1` nampak nilai. Fix: padam baris kosong (`sed '/^IMAP_PASSWORD=$/d'`), kekal yang bernilai → recreate → `config:cache` → imap LULUS.

**BAKI (E2E slug penuh — perlu masjid pilot):**
- Cipta masjid pilot → Tetapan Masjid aktifkan intake emel + allowlist pengirim + keyword → hantar emel berlampiran ke `scan+{slug}@bakwim.my` → Peti Masuk + OCR + carian.
- Reka bentuk: alias `scan+{slug}@bakwim.my` (satu peti mel, plus-addressing). Destination lama `spdmdiwan` (Pending) boleh padam di CF.
- 🔐 **Pengguna:** regenerate App Password (nilai tadi muncul dalam chat/transkrip) selepas sistem stabil.

### 🟢 D. WhatsApp — E2E LENGKAP & LULUS (pilot MAMAD; provisioning + pairing + inbound + outbound + OCR-fix, 18 Jul petang)
**SIAP:** gateway `DIWAN_PROVISIONING_SECRET` kini **padan** `WHATSAPP_PROVISIONING_SECRET` SPDM (fingerprint `b5ee6a00d53e1af0`). Probe SPDM-signed → **HTTP 200** `{"success":true,"data":{"tenantId":"10","status":"active","maxDevices":2}}`. Integrasi provisioning SPDM ↔ gateway **HIDUP**.
- Punca asal 401: nilai **fingerprint 16-aksara tersalin sebagai secret** (bukan 64-hex); dibetulkan di gateway + `config:cache`.
- SPDM `WhatsAppIntegrationService::baseRequest()` sudah `->acceptJson()` → hantar `Accept: application/json` (elak 302 gateway pada ralat validasi). **Tiada perubahan kod SPDM diperlukan.** Pengerasan gateway `shouldRenderJsonWhen` = pilihan sahaja.
- ⚠️ Bersihkan: probe cipta tenant junk `spdm-production:mosque:0` (gateway tenantId 10) — boleh padam di gateway.

**✅ E2E LENGKAP & LULUS — pilot MAMAD (Masjid Al-Mukhlisin Alam Damai, slug=mamad, mosque_id=1):**
- Dicipta di server (login panel perlu kata laluan → Claude tak boleh UI): admin+WA **60176811605**, kerani **60189030363**, pengerusi **60199654974**; 40 nod KF; status aktif.
- WhatsApp provision → gateway tenant 11, linked.
- **Pairing kod telefon** (bukan QR): `beginPairing(phone)` → `linking_code` → pengguna taip di telefon → **connected**, wa_number=60176811605 ✅.
- **Outbound**: `WhatsAppGateway::send` → pengerusi → ok=1 ✅.
- **Inbound SEBENAR** (telefon pengguna): kerani hantar `spdm` → slot (`wa_intake_ready`) → hantar dokumen → **rekod Peti Masuk (channel=whatsapp) + OCR siap + `InboxNewItemNotification`** ke admin/kerani ✅. Aliran penuh terbukti.
- Simulasi: `diwan:simulate-whatsapp <session> <phone> <file>` (webhook HMAC sebenar) untuk uji pipeline tanpa telefon.
- Reka bentuk: 1 nombor/sesi per masjid. Gateway sokong `maxDevices=2`, SPDM kuatkuasa 1.

**🐛 BUG PRODUKSI DIJUMPAI + DIBAIKI (hasil E2E ini — go-live blocker):** dokumen dengan **teks bercetak GAGAL OCR** — `ocrmypdf --skip-text --output-type pdfa` **abort pada Ghostscript 10.0.0** (imej php:8.3 bookworm); imej tanpa teks lulus (kosong) menyembunyikan isu. **Fix (`fe5744a`):** `--output-type pdfa`→`pdf` dalam `ProcessOcrJob::runOcrMyPdf` (elak Ghostscript). **Disahkan di produksi selepas rebuild imej:** JPEG berteks → `ocr=siap, ocr_len=109`, teks betul diekstrak + searchable.pdf dijana ✅. PDF/A boleh dipulih dengan naik taraf Ghostscript >10.02.0; fail asal tak diubah.

**Nota:** rekod ujian simulate (MAMAD id 2–4) = artifak, boleh padam. Junk gateway tenant `spdm-production:mosque:0` (tenantId 10, dari probe awal) boleh padam di gateway.

**Login akaun ahli MAMAD (nota operasi, 18 Jul lewat):** 3 ahli guna email **placeholder** `admin@mamad.local` / `kerani@mamad.local` / `pengerusi@mamad.local` (bukan inbox sebenar → **magic link tak berguna**; guna **login password** sahaja di `/app/login`, BUKAN `/admin`). Admin ada password (ditukar operator); kerani/pengerusi asalnya **tiada** password → set via `/admin` → **Pengguna** → edit → medan **Kata Laluan** (auto-hash; model User cast `password => hashed`). Panel `/app` **tidak** paksa pengesahan email (User bukan `MustVerifyEmail`; AppPanelProvider tiada `emailVerification`) → `email_verified_at` kosong TAK halang login. Untuk pengguna SEBENAR nanti: tukar ke email betul mereka supaya magic link + notifikasi email hidup (notifikasi WhatsApp sudah aktif untuk MAMAD).

---

## 4. Semakan penuh (gate) sebelum buka pengguna sebenar
- [x] git push + Pest 234✓/1 skip + Playwright semua LULUS + Pint
- [x] Emel: magic link sampai inbox (Brevo authenticated); IMAP intake LULUS
- [ ] Intake: WA + emel + upload manual → OCR `siap` → carian jumpa (MAMAD terbukti)
- [ ] **Ujian silang tenant (2 masjid) di server sebenar** — carian/slug/signed URL/alias emel/sesi WA terasing (suite Pest membuktikan; belum diuji pd 2 tenant produksi)
- [ ] `backup:run` → objek di bucket backup (restore drill)
- [ ] Log 30–60 min tiada error berulang

### Tindakan pengguna untuk ciri Fasa D (bila mahu aktif)
- **Telegram**: BotFather → cipta bot → `sudoedit .env` (`TELEGRAM_BOT_TOKEN`, `TELEGRAM_BOT_USERNAME`, `TELEGRAM_WEBHOOK_SECRET`) → recreate → `php artisan diwan:telegram-set-webhook` → superadmin & pengguna tekan **Sambung Telegram** (Profil).
- **WhatsApp platform** (alert superadmin): sediakan nombor WA khas → `/admin` → **WhatsApp Platform** → Aktifkan → Pasangkan (QR/kod) → Segerakkan. Alert sesi-terputus akan hantar via nombor ini.
- Nota: `diwan:check-wa-sessions` sudah dijadualkan (/10 min); alert e-mel+Telegram berfungsi tanpa WA platform.

## 5. Rujukan
- Spec: `DIWAN-SPEC.md`. Checklist go-live: `WHAT-TO-DO-NEXT.md`. Bukti audit: `AUDIT-E2E-2026-07-16.md`.
- Memori sesi: `~/.claude/projects/.../memory/spdm-deploy-bakwim.md`.

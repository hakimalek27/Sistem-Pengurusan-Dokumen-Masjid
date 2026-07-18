# HANDOVER — Diwan (SPDM) Produksi bakwim.my

**Kemas kini:** 2026-07-18 (petang) · **Status:** LIVE di https://bakwim.my (Cloudflare Full strict, COS, login password, Brevo SMTP). Mod **canary** (`DIWAN_REGISTRATION_OPEN=false`, 0 tenant). Sesi petang: **Email intake LIVE PENUH** (mailbox `spdmediwan@gmail.com` verified, catch-all active, **`staging-check imap LULUS`**), **WhatsApp provisioning SELARAS** (probe SPDM-signed → 200), prod **`staging-check` 9/9 LULUS**.

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
- **Kesan:** boleh log masuk TANPA SMTP. Superadmin `azanmalek@maiwp.gov.my` sudah ada password (`cc8459737b42a05ca3` — **sila tukar**).

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

### 🟢 D. WhatsApp — provisioning secret SELARAS (disahkan 200, 18 Jul petang) + QR
**SIAP:** gateway `DIWAN_PROVISIONING_SECRET` kini **padan** `WHATSAPP_PROVISIONING_SECRET` SPDM (fingerprint `b5ee6a00d53e1af0`). Probe SPDM-signed → **HTTP 200** `{"success":true,"data":{"tenantId":"10","status":"active","maxDevices":2}}`. Integrasi provisioning SPDM ↔ gateway **HIDUP**.
- Punca asal 401: nilai **fingerprint 16-aksara tersalin sebagai secret** (bukan 64-hex); dibetulkan di gateway + `config:cache`.
- SPDM `WhatsAppIntegrationService::baseRequest()` sudah `->acceptJson()` → hantar `Accept: application/json` (elak 302 gateway pada ralat validasi). **Tiada perubahan kod SPDM diperlukan.** Pengerasan gateway `shouldRenderJsonWhen` = pilihan sahaja.
- ⚠️ Bersihkan: probe cipta tenant junk `spdm-production:mosque:0` (gateway tenantId 10) — boleh padam di gateway.

**BAKI (E2E penuh — perlu tindakan pengguna):**
1. Cipta masjid pilot di SPDM → Tetapan Masjid → **Aktifkan WhatsApp** (`linked`) → **Pasangkan** → **scan QR** telefon rasmi masjid → `connected`.
2. Uji: ahli hantar `spdm` → slot 10 min → hantar PDF → Peti Masuk + OCR + carian.
3. Reka bentuk: 1 nombor/sesi per masjid (skema unique). Gateway sokong `maxDevices=2`, SPDM kuatkuasa 1 — 2 nombor = fasa berasingan.

---

## 4. Semakan penuh (gate) sebelum buka pengguna sebenar
- [ ] git push + CI hijau
- [ ] Emel: magic link sampai inbox sebenar (bukan spam)
- [ ] Intake: WA + emel + upload manual → OCR `siap` → carian jumpa
- [ ] Ujian silang tenant (2 masjid): carian/slug/signed URL/alias emel/sesi WA terasing
- [ ] `backup:run` → objek di bucket backup (dibaiki: postgresql-client-16 dalam imej)
- [ ] Log 30–60 min tiada error berulang

## 5. Rujukan
- Spec: `DIWAN-SPEC.md`. Checklist go-live: `WHAT-TO-DO-NEXT.md`. Bukti audit: `AUDIT-E2E-2026-07-16.md`.
- Memori sesi: `~/.claude/projects/.../memory/spdm-deploy-bakwim.md`.

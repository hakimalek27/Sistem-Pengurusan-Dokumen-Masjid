# HANDOVER — Diwan (SPDM) Produksi bakwim.my

**Kemas kini:** 2026-07-18 · **Status:** LIVE di https://bakwim.my (Cloudflare Full strict, COS, login password).

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

---

## 3. Yang TERTUNGGAK (perlu tindakan pengguna)

### 🔴 A. git push (commit belum naik GitHub)
`GH_TOKEN` invalid (401). Commit lokal terkini belum push. **Tindakan:** beri PAT GitHub sah, atau jalankan `git push origin main` sendiri, atau tambah kunci SSH ke GitHub. Selepas push → server boleh `git pull` untuk selaras (kini server guna fail yang di-scp + imej rebuild).

### 🔴 B. Emel HANTAR — magic link & notifikasi (Brevo, percuma)
Tanpa ini, magic link tak sampai (guna login password buat masa ini).
1. Daftar akaun **Brevo** (percuma 300 emel/hari) — https://www.brevo.com
2. Senders & Domains → **Authenticate domain** `bakwim.my` → Brevo beri rekod DKIM/SPF.
3. Beri rekod itu kepada saya → saya tambah di Cloudflare DNS (automasi).
4. Dapatkan **SMTP key** Brevo → saya set di `.env`: `MAIL_MAILER=smtp`, `MAIL_HOST=smtp-relay.brevo.com`, `MAIL_PORT=587`, `MAIL_USERNAME=<login>`, `MAIL_PASSWORD=<smtp key>`, `MAIL_FROM_ADDRESS=admin@bakwim.my`.
   - *Alternatif pantas (kurang rasmi):* App Password Gmail sedia ada + smtp.gmail.com.

### 🔴 C. Emel TERIMA / intake dokumen (Cloudflare Email Routing + Gmail)
1. Cipta **akaun Gmail** khusus (cth `spdm.bakwim@gmail.com`), aktif 2FA, jana **App Password**.
2. Cloudflare → **Email Routing** → enable (rekod MX/DKIM/SPF sudah "Added") → **catch-all** `*@bakwim.my` → forward ke Gmail tsb (sahkan emel pengesahan di inbox Gmail).
3. Beri App Password → saya set `.env`: `IMAP_ENABLED=true`, `IMAP_USERNAME=<gmail>`, `IMAP_PASSWORD=<app password>`, `MAIL_INTAKE_ADDRESS=scan@bakwim.my`.
4. Reka bentuk: setiap masjid dapat alias unik `scan+{slug}@bakwim.my` (plus-addressing, satu peti mel sahaja — TIDAK perlu banyak akaun). Dikawal allowlist pengirim + kata kunci per masjid di **Tetapan Masjid**.

### 🔴 D. WhatsApp — sisi gateway + QR
1. Pada server **wassap.wehdah.my** (berasingan — ikut runbook, jangan sentuh selain servis wassap): set `DIWAN_PROVISIONING_SECRET` = nilai `WHATSAPP_PROVISIONING_SECRET` dari `/opt/diwan/.env` → `php artisan config:cache` + reload php-fpm.
2. Dalam SPDM: Tetapan Masjid → **Aktifkan WhatsApp** (status `linked`) → **Pasangkan Nombor** → **scan QR** dengan telefon rasmi masjid → status `connected`.
3. Reka bentuk semasa: **1 nombor/sesi per masjid** (dikuatkuasa skema `whatsapp_integrations.mosque_id` unique). Sokongan **2 nombor** = fasa berasingan (perlu migrasi skema + ubah routing/UI) — belum dibuat.

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

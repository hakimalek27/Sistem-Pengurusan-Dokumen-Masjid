# Diwan — Sistem Pengurusan Dokumen Masjid (SPDM)

Platform SaaS **multi-tenant** untuk pengurusan dokumen masjid: registri digital, klasifikasi
fail, carian kandungan (OCR), minit & routing, e-Kelulusan, kuota storan, dan enjin retensi
arkib automatik. Setiap masjid = satu *tenant* (Filament tenancy); satu panel superadmin.

Sumber kebenaran reka bentuk: **`DIWAN-SPEC.md`** (v2.1). Pelan pembinaan berfasa:
**`CLAUDE-CODE-PROMPTS.md`**.

## Timbunan Teknologi
- **Laravel 12** + **Filament 4** (dua panel: `/admin` superadmin, `/app/{slug}` tenant masjid)
- **PostgreSQL 16** · **Redis 7** · **Meilisearch v1** · **Tencent COS** (S3) · **Horizon**
- OCR: **tesseract 5 + ocrmypdf** (dalam imej Docker) · PDF: **dompdf** · QR: **simple-qrcode**

## Persekitaran Pembangunan (mesin ini)
Dev/ujian pada mesin ini menggunakan **SQLite** + PHP 8.4 tempatan (tanpa Docker). Ujian penuh
berjalan **tanpa** perkhidmatan luaran (`Storage::fake`, `WHATSAPP_DRIVER=log`,
`MAIL_MAILER=log`, `IMAP_ENABLED=false`, Scout `collection`). OCR sebenar hanya berjalan dalam
imej Docker (tesseract/ocrmypdf) — OcrPipelineTest melangkau bahagian itu di luar Docker.

```bash
php composer.phar install
php artisan key:generate
php artisan migrate:fresh --seed     # data demo (mam/man) dalam local/testing
php artisan test                     # suite Pest
```

## Naik Produksi (Docker — Tencent Lighthouse)
Prasyarat ✋ (§21): DNS + Caddy + swap; COS 2 bucket + CAM; Gmail App Password; BotFather;
gateway `wassap.wehdah.my`; rclone crypt; harga & bank di Tetapan Platform; semakan Terma/DPA.

```bash
git clone <repo> /opt/diwan && cd /opt/diwan
cp .env.example .env    # isi APP_KEY, DB, COS, MEILI, WA, IMAP, TELEGRAM
docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan diwan:sync-meili
docker compose exec app php artisan diwan:make-superadmin admin@wehdah.my --password=…
STAGING_CHECK_EMAIL=ops@example.com APP_URL=https://staging.example.com ./scripts/staging-smoke.sh
```

- Scheduler: servis `scheduler` menjalankan `schedule:work` (8 tugasan operasi + pangkas log
  bulanan — lihat `routes/console.php`).
- Worker: servis `worker` menjalankan `php artisan horizon` (queue: default, ocr[maxProcesses 1],
  exports).
- Sandaran 3 lapis (§4.6): COS versioning + `spatie/laravel-backup` (02:30 → `cos_backup`) +
  rclone crypt mingguan (cron host).

### Gate staging dan failure drill

`diwan:staging-check` menguji PostgreSQL, Redis, Horizon, COS baca/tulis/padam, OCR, Meilisearch,
SMTP sebenar, socket IMAP dan gateway. Ia gagal jika `--mail-to` tidak diberi supaya SMTP tidak
boleh ditanda lulus berdasarkan konfigurasi sahaja.

```bash
docker compose exec app php artisan diwan:staging-check --mail-to=ops@example.com
docker compose exec app php artisan diwan:failure-drill queue --confirm-production
docker compose exec app php artisan diwan:failure-drill cos --confirm-production
docker compose exec app php artisan diwan:failure-drill smtp --confirm-production
```

Jalankan drill di staging. Probe queue mesti muncul pada Horizon/`failed_jobs`; probe COS dan SMTP
mesti dikesan sebagai kegagalan terkawal. Jangan padam bukti log/alert.

### Latihan pemulihan sandaran

`backup:run` sahaja bukan bukti boleh pulih. Salin satu ZIP sandaran sebenar ke host staging dan
jalankan skrip berikut. Ia memulihkan dump ke container PostgreSQL 16 terpencil, mengesahkan jadual
teras dan merekod kiraan masjid/rekod/pengguna tanpa menyentuh DB staging.

```bash
./scripts/restore-drill.sh /secure/path/backup-diwan.zip
```

Bukti bertarikh ditulis ke `storage/logs/restore-drill-*.log`. Gate live memerlukan satu log
`LULUS restore drill` daripada backup sebenar yang terkini.

## Arahan Diwan
| Command | Fungsi |
|---|---|
| `diwan:make-superadmin {email}` | Cipta/naik taraf superadmin |
| `diwan:sync-meili` | Segerak tetapan indeks Meilisearch |
| `diwan:health` / `diwan:staging-check` | Health container dan gate dependency staging |
| `diwan:failure-drill {cos\|queue\|smtp}` | Failure injection terkawal di staging |
| `diwan:simulate-whatsapp {session} {phone} {path}` | Uji webhook WhatsApp masuk |
| `diwan:fetch-mail` | Tarik e-mel pengimbas (IMAP) |
| `diwan:run-retention-notices` / `diwan:run-retention-execute` | Enjin retensi (§16) |
| `diwan:reconcile-storage` · `diwan:expire-addons` · `diwan:ping-gateway` · `diwan:send-minit-reminders` · `diwan:prune-logs` | Tugasan operasi |

## Tindakan Manusia Sebelum Live (§21)
Lihat `DIWAN-SPEC.md §21` — DNS/COS/Gmail/BotFather/gateway/rclone/harga/Terma; luluskan MAM
sebagai tenant pertama; buang data demo sebelum produksi.

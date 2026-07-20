# Diwan — Sistem Pengurusan Dokumen Masjid (SPDM)

Platform SaaS **multi-tenant** untuk pengurusan dokumen masjid: registri digital, klasifikasi
fail, carian kandungan (OCR), minit & routing, e-Kelulusan, kuota storan, dan enjin retensi
arkib automatik. Setiap masjid = satu *tenant* (Filament tenancy); satu panel superadmin.

> **Status 21 Julai 2026:** SPDM live di `https://bakwim.my`. Bukti semasa, isu yang
> dibaiki dan jurang terhadap rujukan DDMS direkod dalam
> [`AUDIT-E2E-2026-07-21.md`](AUDIT-E2E-2026-07-21.md). Gunakan [`HANDOVER.md`](HANDOVER.md)
> untuk operasi/deploy; dokumen 16 Julai ialah rekod sejarah sebelum production.

Sumber kebenaran reka bentuk: **`DIWAN-SPEC.md`** (v2.1). Pelan pembinaan berfasa:
**`CLAUDE-CODE-PROMPTS.md`**.

## Timbunan Teknologi
- **PHP 8.4** · **Laravel 12** + **Filament 4** (dua panel: `/admin` superadmin, `/app/{slug}` tenant masjid)
- **PostgreSQL 16** · **Redis 7** · **Meilisearch v1** · **Tencent COS** (S3) · **Horizon**
- OCR: **tesseract 5 + ocrmypdf** (dalam imej Docker) · PDF: **dompdf** · QR: **simple-qrcode**

## Persekitaran Pembangunan (mesin ini)
Dev/ujian pada mesin ini menggunakan **SQLite** + PHP 8.4 tempatan (tanpa Docker). Ujian penuh
berjalan **tanpa** perkhidmatan luaran (`Storage::fake`, `WHATSAPP_DRIVER=log`,
`MAIL_MAILER=log`, `IMAP_ENABLED=false`, Scout `collection`). OCR sebenar disokong secara rasmi
dalam imej Docker; host Windows turut disokong melalui fallback Tesseract/Poppler dengan resolver
path mutlak. `OcrPipelineTest` akan melangkau bahagian sebenar jika tooling tiada.

```bash
php composer.phar install
php artisan key:generate
php artisan migrate:fresh --seed     # data demo (mam/man) dalam local/testing
php artisan test                     # suite Pest
```

Regression Chrome berada dalam `e2e/`. Sediakan DB E2E/seeder dan server local seperti diterangkan
dalam [`AUDIT-E2E-2026-07-16.md`](AUDIT-E2E-2026-07-16.md), kemudian jalankan `npm run test:e2e`.
Untuk bukti OCR fail sebenar, tetapkan `SPDM_OCR_FIXTURE_1/2` dan `SPDM_OCR_TERM_1/2` sebelum
menjalankan Pest atau `e2e/ocr-upload.spec.js`; fail pengguna tidak disalin ke Git.

## Naik Produksi (Docker — Tencent Lighthouse)

Pemilik memilih deployment terus ke production tanpa staging berasingan. Gunakan maintenance/
canary mode dan ikut runbook [`WHAT-TO-DO-NEXT.md`](WHAT-TO-DO-NEXT.md); arahan di bawah ialah
rujukan teknikal asas, bukan bukti bahawa gate live sudah lulus.
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
SMTP sebenar, autentikasi IMAP serta bacaan folder dan gateway. Ia gagal jika `--mail-to` tidak diberi supaya SMTP tidak
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

### CI dan deploy staging terkawal

GitHub Actions menjalankan suite penuh pada PostgreSQL 16 + Redis 7 + Meilisearch, OCR sebenar,
Horizon, build aset serta build/publish imej Docker `app` dan `web`. Workflow deploy staging ialah
manual, menggunakan environment `staging`, SSH host-key verification dan rollback automatik jika
smoke, failure injection atau restore drill gagal.

Rahsia wajib pada GitHub environment `staging`: `STAGING_HOST`, `STAGING_USER`,
`STAGING_SSH_KEY`, `STAGING_KNOWN_HOSTS`, `STAGING_PATH`, `STAGING_BACKUP_ZIP`,
`STAGING_CHECK_EMAIL`, `STAGING_APP_URL`; `STAGING_PORT` pilihan (lalai `22`). Lindungi environment
ini dengan required reviewer sebelum menjalankan workflow **Deploy staging and run live gates**.

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

## Intake dokumen tenant

- **WhatsApp:** admin aktifkan integrasi di Tetapan Masjid dan pair nombor rasmi. Ahli hantar `spdm`, tunggu balasan slot 10 minit, kemudian hantar satu PDF/imej. Sesi, nombor ahli, API key dan dokumen semuanya disemak mengikut tenant.
- **E-mel:** platform menyediakan satu akaun IMAP yang menyokong plus-addressing. Setiap tenant mendapat alias unik daripada halaman Tetapan Masjid, contohnya `scan.diwan+mam@domain`. Admin tenant mesti aktifkan fungsi, tetapkan kata kunci dan allowlist pengirim. Letak `spdm` pada subjek/isi serta lampirkan dokumen; scheduler mengambilnya setiap minit.
- **OCR dan carian:** semua saluran masuk ke Peti Masuk dan queue `ocr`. Item Peti Masuk mempunyai tindakan `Lihat / OCR`; teks yang siap terus boleh dicari dan deep-link kekal pada tenant yang betul.

## Tindakan Manusia Sebelum Live (§21)
Lihat `DIWAN-SPEC.md §21` — DNS/COS/Gmail/BotFather/gateway/rclone/harga/Terma; luluskan MAM
sebagai tenant pertama; buang data demo sebelum produksi.

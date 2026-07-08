# LAPORAN KESEDIAAN — Diwan (SPDM)

**Tarikh:** 9 Julai 2026 | **Fasa:** 9/9 (Verifikasi Penuh) | **Status:** Kod SEDIA (menunggu §21 + staging)

Dibina mengikut `DIWAN-SPEC.md` v2.1 melalui 9 fasa berpagar ujian (`CLAUDE-CODE-PROMPTS.md`).

---

## (a) Suite Ujian — Output Sebenar

```
php artisan test
Tests:  128 passed, 1 skipped (320 assertions)
```

**10 fail ujian penerimaan §18 (semua hijau):**
RecordNumberingTest · SensitivityPolicyTest · TenantIsolationTest · WhatsAppWebhookTest ·
DedupTest · InboxClassifyTest · OcrPipelineTest · QuotaTest · RetentionEngineTest · RegistrationTest

**Ujian tambahan:** Membership, Suspend, Billing, Approval, Export, EmailRouting, Minit, MagicLink,
SearchIsolation, RecordVersion, PublicPages, FilamentResources, AdminPanel, MigrationSmoke.

**Smoke E2E berskrip (`php artisan diwan:smoke`):** 9/9 lulus —
daftar → lulus (KF 40 nod) → jemput ahli → klasifikasi (SMOK.100-4/1(1)) → minit → kelulusan (IP) →
carian → eksport ZIP → auto-padam (sijil + batu nisan).

---

## (b) Jadual Verifikasi 41 Item §18

| # | Item | Status | Bukti |
|---|---|---|---|
| 1 | RecordNumbering | ✔ | RecordNumberingTest (6) |
| 2 | SensitivityPolicy | ✔ | SensitivityPolicyTest (6) |
| 3 | TenantIsolation | ✔ | TenantIsolationTest (6) + FilamentResourcesTest (URL 404) |
| 4 | WhatsAppWebhook | ✔ | WhatsAppWebhookTest (8: HMAC/idempotensi/sesi/ahli/kuota/intake) |
| 5 | Dedup | ✔ | DedupTest (3) |
| 6 | InboxClassify | ✔ | InboxClassifyTest (3) |
| 7 | OcrPipeline | ✔/⚠ | Office-skip diuji; OCR sebenar **di-skip** (perlu tesseract/ocrmypdf dalam imej Docker §4.4) |
| 8 | Quota | ✔ | QuotaTest (6) |
| 9 | RetentionEngine | ✔ | RetentionEngineTest (7: positif + 5 negatif + gantung) |
| 10 | Registration | ✔ | RegistrationTest (3) |
| 11 | Halaman BM (compose) | ✔/⚠ | PublicPagesTest; Docker→justifikasi SQLite dev |
| 12 | Magic link penuh | ✔ | MagicLinkTest (7) |
| 13 | Nyahaktif/keluar tenant | ✔ | SuspendTest + TenantIsolationTest |
| 14 | Muat naik 3 fail + sha256 | ✔ | QuotaTest (kaunter) + DedupTest (sha256/⚠) + UI Peti Masuk |
| 15 | simulate-whatsapp | ✔ | WhatsAppWebhookTest + command `diwan:simulate-whatsapp` |
| 16 | Klasifikasi buka fail | ✔ | InboxClassifyTest + wizard (FilamentResourcesTest render) |
| 17 | OCR ≤2 min | ⚠ | Perlu container Docker (tesseract) — dijalankan di staging |
| 18 | Carian highlight + isolasi | ✔ | SearchIsolationTest (4) |
| 19 | Log akses sulit + IP | ✔ | FilamentResourcesTest (view sulit → SensitiveAccessLog) |
| 20 | Edarkan minit + berantai | ✔ | MinitTest (create/reply/markDone) |
| 21 | Reminder LEWAT | ✔ | MinitTest (§18.21) |
| 22 | Kelulusan + IP + lencana | ✔ | ApprovalTest (3) |
| 23 | QR + /r/{ulid} | ✔ | RecordVersionTest (QR) + TenantIsolationTest (/r) |
| 24 | Jilid (Jld.2) | ✔ | RecordNumberingTest (openNextVolume) |
| 25 | Pindah fail + audit | ✔ | InboxClassifyTest (moveToFile audit) |
| 26 | Laporan | ⚠ | Versi asas (dashboard Filament); carta penuh §9.C.9 = penambahbaikan |
| 27 | Pelupusan manual hujung-ke-hujung | ✔ | DisposalService prepare/approve/execute + RetentionEngineTest |
| 28 | backup:run | ⚠ | config/backup.php (cos_backup); dijalankan di staging dengan disk sebenar |
| 29 | Latihan pemulihan | ✋ | Manual di staging (README §4.6) |
| 30 | Daftar→lulus→checklist | ✔ | RegistrationTest + smoke E2E |
| 31 | Ubah kuota + sekat | ✔ | QuotaTest + MosqueResource (Ubah Kuota) |
| 32 | Add-on 10GB | ✔ | BillingTest (§18.32) |
| 33 | Luput addon | ✔ | BillingTest (§18.33) |
| 34 | Retensi kitaran + 4 varian TIDAK | ✔ | RetentionEngineTest (7) |
| 35 | Eksport ZIP (csv+pdf+media) | ✔ | ExportTest |
| 36 | E-mel +man → peti MAN | ✔ | EmailRoutingTest (§18.36) |
| 37 | Sesi man bukan ahli → tolak | ✔ | WhatsAppWebhookTest (§18.37) |
| 38 | Gantung MAN | ✔ | SuspendTest + RetentionEngineTest (auto-padam dijeda) |
| 39 | Superadmin log sulit (is_superadmin) | ✔ | ViewRecord::mount (is_superadmin ditulis) |
| 40 | Ping gateway → banner | ✔ | Command `diwan:ping-gateway` + GatewayDownNotification |
| 41 | test hijau + horizon + schedule | ✔ | 128 passed; `schedule:list` 9 tugasan (8 operasi §17.24 + prune-log) |

Legenda: ✔ diuji automatik/smoke · ⚠ perlu perkhidmatan/imej sebenar (staging) · ✋ tindakan manusia.

---

## (c) Versi Terkunci (composer.lock)
- PHP **8.4** (dev tempatan) / **8.3** (imej Docker produksi §3.2) · Laravel **12.63** · Filament **4.11.8** · Pest **3.8**
- medialibrary 11 · activitylog 5 · backup 10 · scout 11 · meilisearch-php 1 · horizon 5 · dompdf 3 · simple-qrcode 4 · webklex/imap 6 · telegram 7

## (d) Semakan Kesediaan Produksi
- ✔ `.env.example`: `APP_ENV=production`, `APP_DEBUG=false`
- ✔ Webhook WA: HMAC-SHA256 wajib + `throttle:60,1` (route/api.php)
- ✔ `composer.lock` dikomit · ✔ Tiada `.env`/rahsia dalam repo (`.gitignore`)
- ✔ Larangan §0.3 dipatuhi (tiada NRIC, tiada pustaka WA tidak rasmi, bucket cos private, tiada padam-atas-kuota)

## (e) Had Diketahui (selaras §19)
- **OCR** tidak dijalankan di luar imej Docker (tesseract/ocrmypdf) — ⚠ item 7/17 disahkan di staging.
- **Dev/ujian guna SQLite** (bukan pgsql) — justifikasi persekitaran (Kriteria Fasa 1); produksi kekal pgsql/Docker.
- **Laporan/widget dashboard** (§9.C.9/§9.C.2) = versi asas — penambahbaikan visual pasca-MVP.
- Semua had lain selaras §19 (whatsmeow tidak rasmi, SPOF, had OCR Jawi/tulisan tangan, bil manual).

## Senarai §21 — MENUNGGU Tindakan Manusia (sebelum live)
DNS+Caddy+swap · COS 2 bucket+CAM+lifecycle · Gmail App Password · BotFather+set-webhook ·
**Gateway wassap.wehdah.my** (/send bersesi + logik kata kunci + QR UI + HMAC) · rclone crypt ·
Harga+bank di Tetapan Platform · semakan Terma/DPA peguam · luluskan MAM + buang data demo ·
masjid ANM: sahkan pendekatan retensi.

---

**Pengisytiharan akhir:** Kod SEDIA. Go-live menunggu tindakan manusia §21 + satu larian staging
(sahkan item ⚠: OCR sebenar, backup:run, pemulihan) dengan perkhidmatan sebenar (COS/gateway/SMTP/Meili).

# DIWAN — Platform SaaS Pengurusan Dokumen Masjid (SPDM)
## Spesifikasi Teknikal Lengkap v2.1 (MULTI-TENANT) untuk Pembinaan MVP Satu Pusingan

**Versi:** 2.1 | **Tarikh:** 7 Julai 2026 | **Pemilik:** Azan (Wehdah)
**Status verifikasi:** Versi perisian, tenancy Filament 4, fakta undang-undang (PDPA Pindaan 2024) dan pipeline OCR telah DISAHKAN melalui carian web (6 Julai 2026) dan ujian sandbox sebenar. Tanda ⚠️ = had/andaian dinyatakan jujur. Tanda ✋ = tindakan manusia, bukan kod. Dokumen ini BERDIRI SENDIRI — tiada rujukan luaran diperlukan.

**LOG PERUBAHAN v2.0:**
- Seni bina **MULTI-TENANT** dari hari pertama: setiap masjid = tenant (Filament tenancy), panel superadmin berasingan.
- Peranan per-masjid melalui pivot `mosque_user.role` + peta kebenaran config (spatie/laravel-permission digugurkan — §6.0).
- **Kuota storan** 20GB/masjid (superadmin boleh ubah) + **add-on storan berbayar** (kadar/GB ditetapkan superadmin) + halaman pemantauan penggunaan penuh.
- **Enjin retensi automatik 7 tahun**: notis T-90/T-30/T-7 kepada admin masjid + superadmin, alat Eksport ZIP, kemudian auto-padam — dengan injap keselamatan `kekal` & `legal hold` (§16; sebab undang-undang §2.2).
- Penghalaan multi-tenant untuk WhatsApp & e-mel pengimbas (§11); ujian pengasingan tenant diwajibkan (§15.2, §18).

**LOG PERUBAHAN v2.1 (7 Julai 2026):** WhatsApp kini **nombor sendiri setiap masjid** — satu sesi whatsmeow per masjid (✋ imbas QR di gateway); kemasukan dokumen dicetuskan **kata kunci** (lalai `spdm`) sebelum/selepas dokumen atau dalam kapsyen; penghalaan tenant ikut SESI; nombor sistem berkongsi & konsep "masjid lalai" DIBUANG (§5.1, §9.C.14, §10.B, §11.1, §14).

---

## §0. ARAHAN KEPADA CLAUDE CODE (BACA DULU)

1. **Baca keseluruhan dokumen sebelum menulis kod.** Semua keputusan reka bentuk sudah dibuat & disahkan — jangan buat kajian keperluan tambahan. Bina ikut urutan §17.
2. **Jangan ubah versi pakej §3.** Semua teks UI **Bahasa Melayu**; kod (kelas, jadual, pemboleh ubah) dalam Bahasa Inggeris.
3. **Larangan mutlak:**
   - JANGAN simpan blob dokumen pada cakera server (kecuali `storage/app/tmp` sementara OCR/eksport, dipadam selepas siap).
   - JANGAN guna pustaka WhatsApp tidak rasmi dalam kod (Baileys, whatsapp-web.js) — hanya HTTP ke gateway pengguna (§11.1).
   - JANGAN bina modul OSA/Rahsia Rasmi (§2.5).
   - JANGAN cipta medan No. Kad Pengenalan (NRIC) dalam mana-mana metadata (§15.3).
   - JANGAN jadikan bucket COS public — signed URL / stream terkawal sahaja.
   - JANGAN padam data kerana kuota melebihi had — sekat muat naik sahaja (§5.14).
   - **JANGAN benarkan kebocoran silang-tenant** — kegagalan paling teruk yang mungkin (§15.2).
4. **3 jurang skop Filament yang didokumenkan RASMI & wajib ditutup manual (§15.2):** (a) pilihan `Select/CheckboxList/Repeater ->relationship()` TIDAK berskop tenant automatik — sentiasa `modifyQueryUsing` skop masjid; (b) validasi unik dalam tenant guna `scopedUnique()/scopedExists()`; (c) query di luar Resource (widget/service/job/command/carian) tidak diskop automatik — guna trait `BelongsToMosque` + middleware `ApplyTenantScopes`; job queue bawa `mosque_id` dalam payload.
5. **Definisi "siap":** semua item §18 lulus + `php artisan test` hijau.
6. Jika API sebenar gateway WhatsApp pengguna berbeza daripada kontrak §11.1, HANYA `app/Services/WhatsAppGateway.php` diubah — reka bentuk adapter memastikan ini.

---

## §1. IDENTITI & SKOP

| Perkara | Nilai |
|---|---|
| Nama jenama | **Diwan** (الديوان — pejabat pendaftaran & arkib pentadbiran Islam klasik) |
| Nama rasmi | **SPDM** — Sistem Pengurusan Dokumen Masjid |
| Model | **SaaS multi-tenant** — banyak masjid/surau, satu pemasangan |
| Domain platform | `diwan.wehdah.my` ✋(atau pilihan lain; letak dalam `APP_URL`) |
| URL tenant | Laluan path: `/app/{slug}` (cth `/app/mam`) |
| Panel superadmin | `/admin` |
| Tenant perintis | Masjid Al-Muttaqin Wangsa Melawati (kod **MAM**) |
| Pengguna per masjid | ±15–40 (AJK, pegawai masjid, kerani) |
| Bahasa UI / Zon masa / Tarikh | Bahasa Melayu / `Asia/Kuala_Lumpur` / paparan `d/m/Y` |

### 1.1 Skop MVP
1. **Pendaftaran & onboarding masjid** dengan kelulusan superadmin (§10.I).
2. **Registri digital per masjid**: Peti Masuk 3 saluran (muat naik, e-mel pengimbas, WhatsApp) → klasifikasi → fail → carian kandungan penuh (OCR) — terasing sepenuhnya antara masjid.
3. **Minit/routing + SLA + e-Kelulusan + jejak audit + sensitiviti 3 peringkat** (umum/dalaman/sulit).
4. **Kuota storan & add-on berbayar** (aliran invois-manual MVP; gerbang bayaran automatik = Fasa 2) + pemantauan penggunaan penuh.
5. **Enjin retensi 7 tahun automatik** dengan tangga notifikasi, Eksport ZIP, pengecualian, sijil.
6. **Panel superadmin**: pantau segala-galanya — semua masjid, penggunaan, pesanan, log, kesihatan integrasi; boleh masuk panel mana-mana masjid.
7. Notifikasi WhatsApp (gateway whatsmeow pengguna) + Telegram + E-mel ikut pilihan pengguna.
8. Pengurusan kitaran hayat: buka fail, jilid, tutup fail, pelupusan manual (kekal ada) & automatik.

### 1.2 BUKAN skop MVP (→ §20)
Gerbang bayaran automatik (Bayarcash/FPX/ToyyibPay), OnlyOffice, auto-klasifikasi AI, domain per masjid, aplikasi natif, tandatangan digital bersijil, ClamAV, jenis rekod tersuai per masjid.

---

## §2. UNDANG-UNDANG & PEMATUHAN (DISAHKAN 6 JULAI 2026)

### 2.1 PDPA 2010 (Akta 709) + Pindaan 2024 — kini TERPAKAI DENGAN JELAS

Pindaan berkuat kuasa penuh berperingkat, fasa akhir **1 Jun 2025** (DPO + notifikasi pelanggaran); Garis Panduan DPO & Breach Notification dikeluarkan 25 Feb 2025. **Perubahan penting v2.0:** platform **menjual storan** (add-on berbayar) → "transaksi komersial" → PDPA terpakai kepada operasi platform tanpa kekaburan.

**Struktur tanggungjawab (masuk dalam terma perkhidmatan ✋):**
- **Setiap masjid = Pengawal Data** bagi data dalam akaunnya (data jemaah/asnaf/AJK dalam dokumen mereka).
- **Platform Diwan (Wehdah/Azan) = Pemproses Data** — Pindaan 2024 mengenakan obligasi keselamatan LANGSUNG ke atas pemproses.
- Perjanjian Pemprosesan Data (DPA) ringkas dipersetujui semasa pendaftaran (checkbox; teks ✋ semakan peguam — MVP: placeholder standard).

| Kewajipan | Fakta disahkan | Implikasi Diwan |
|---|---|---|
| DPO | Wajib jika >20,000 subjek data / >10,000 subjek data sensitif / pemantauan sistematik berkala. Platform kecil → belum wajib; **pantau ambang bila masjid bertambah** | `platform_settings.data_protection_officer`; setiap masjid isi "Wakil Perlindungan Data" sendiri |
| Notifikasi pelanggaran | Pesuruhjaya secepat praktik ≤72 jam dari kejadian (significant harm); subjek data ≤7 hari selepasnya. Denda kegagalan ≤RM250k/2 thn | Runbook 2 peringkat §15.6: platform maklum masjid terjejas SERTA-MERTA; pengawal & pemproses masing-masing menilai kewajipan lapor |
| Penalti prinsip | ≤RM1 juta / 3 tahun | Kawalan §15 + pengasingan tenant §15.2 |
| Data sensitif | + biometrik (baharu 2024). Asnaf/khairat/kewangan peribadi dilayan sensitif | Fail 200/300/800 lalai `sulit` (§7) |
| Rentas sempadan (s.129 pindaan) | Perlindungan setara diperlukan | COS **ap-singapore** (Singapura ada PDPA setara — munasabah). Offsite Google Drive HANYA melalui `rclone crypt` — sifer sahaja keluar (§4.6) |

### 2.2 Akta Arkib Negara 2003 (Akta 629) × auto-padam 7 tahun — KEPUTUSAN REKA BENTUK PENTING

Kehendak produk: dokumen berusia 7 tahun di-auto-padam selepas notifikasi. Realiti yang WAJIB diseimbangkan jujur: (1) masjid di bawah Majlis Agama (MAIWP/MAIN) → rekodnya berpotensi **rekod awam** Akta 629; pemusnahan tanpa prosedur boleh menyalahi akta — tanggungjawab itu pada **masjid (pengawal)**, bukan platform; (2) sesetengah dokumen **masih berkuat kuasa selepas 7 tahun** (perjanjian/sijil) atau bernilai kekal (minit mesyuarat agung) — pemadaman buta merugikan masjid sendiri.

**Penyelesaian (§16):** enjin auto-padam 7 tahun DIBINA seperti dikehendaki, dengan tiga injap keselamatan lalai-AKTIF tetapi boleh dilaraskan:
- Jenis rekod `kekal` lalai (minit mesyuarat, perjanjian, sijil, laporan) dikecualikan — admin masjid boleh override ke 7-tahun-rata jika mahu (amaran dipapar).
- **`legal_hold`** per rekod (admin masjid/kerani) — rekod dipegang tidak dipadam.
- **Suis `auto_disposal_enabled` per masjid** (superadmin) — masjid MAIWP yang tertakluk prosedur ANM boleh guna pelupusan manual sahaja (§10.G).
Onboarding memerlukan pengakuan dasar retensi (checkbox §16.2). Metadata rekod dipadam KEKAL selamanya; akaun pengguna dinyahaktif, tidak dipadam (prinsip ANM 6.10.3 & 6.11.2).

### 2.3 Akta 505 (Pentadbiran Undang-Undang Islam WP) & enakmen negeri setara
Struktur peranan §6 mencerminkan jawatan sebenar (Nazir, Pengerusi, Imam, AJK). Platform terbuka kepada masjid mana-mana negeri; label generik mencukupi. Masjid bukan pertubuhan ROS — tiada apa-apa berkaitan Akta Pertubuhan.

### 2.4 Akta Perdagangan Elektronik 2006 (Akta 658)
Mengiktiraf kesahan rekod & tandatangan elektronik → e-Kelulusan (sah kata laluan semula + IP + timestamp + audit) sah untuk urusan dalaman; BUKAN pengganti tandatangan basah bila pihak luar memintanya — aliran surat keluar mengekalkan langkah cetak-tandatangan (§10.D).

### 2.5 Akta Rahsia Rasmi 1972 — TIDAK TERPAKAI. Jangan bina klasifikasi OSA/DRM/prosedur CGSO. Keperluan kerahsiaan dipenuhi oleh 3 peringkat sensitiviti PDPA (§6.3).

### 2.6 Kewangan
Norma audit (rujukan s.82 Akta Cukai Pendapatan: 7 tahun) → retensi kewangan 7 tahun (§16.1). Add-on berbayar = hasil perniagaan Wehdah ✋ pastikan invois bersiri & rekod akaun (sistem jana PDF invois §10.J).

---
## §3. SENI BINA & VERSI (DIPIN & DISAHKAN)

### 3.1 Gambaran

```
 Awam: / dan /daftar
 Superadmin: /admin ───────────────┐        ┌────────────────────────────────┐
 Masjid A: /app/mam                ├─Caddy─►│ TENCENT LIGHTHOUSE (1 VM)      │
 Masjid B: /app/annur              │ (host) │ nginx → php-fpm (app)          │
                                   │        │ worker (Horizon) · scheduler   │
 wassap.wehdah.my ──webhook────────┘        │ PostgreSQL16 · Redis7 · Meili  │
 IMAP scan.diwan+{slug}@… ◄──poll           └───────────┬────────────────────┘
 Telegram ──webhook──►                                  │ S3 API (HTTPS)
                                          ┌─────────────▼──────────────┐
                                          │ COS ap-singapore (utama)   │
                                          │  tenants/{id}/records/...  │
                                          │ COS ap-jakarta (sandaran)  │
                                          └────────────────────────────┘
                     rclone crypt mingguan → Google Drive (sifer sahaja)
```

**Keputusan tenancy (disahkan dari dokumentasi rasmi Filament 4):** SATU pangkalan data + kolum `mosque_id` + **tenancy terbina dalam Filament 4** (`->tenant(Mosque::class)`: skop resource automatik, 404 untuk rekod tenant lain, pengguna boleh milik banyak tenant dengan menu tukar). BUKAN stancl/multi-DB — kerumitan tidak wajar pada skala ini. Dokumentasi Filament sendiri menyatakan skop automatik TIDAK meliputi pilihan borang berhubung & query luar-resource — penutupan jurang diwajibkan (§0.4, §15.2).

### 3.2 Jadual versi (JANGAN UBAH — setiap pilihan ada sebab)

| Komponen | Versi dipin | Status & sebab |
|---|---|---|
| PHP | **8.3** (imej `php:8.3-fpm`) | Serasi L12 dan laluan L13 (min 8.3, disahkan nota rasmi) |
| Laravel | **^12.0** | ⚠️ L13 (17 Mac 2026) wujud; L12 dipilih untuk kematangan one-shot — ekosistem & korpus paling luas; disokong (bug→Ogos 2026, keselamatan→Feb 2027); naik taraf L12→L13 rasmi sifar-breaking, buat pasca-MVP |
| Filament | **~4.0** | Stabil 12 Ogos 2025; tenancy kelas pertama DISAHKAN; v5 (Jan 2026, Livewire 4) TIDAK dipilih — terlalu baharu |
| PostgreSQL | **16** (`postgres:16-alpine`) | JSONB metadata dinamik |
| Redis | **7** (`redis:7-alpine`) | Queue + cache + Horizon |
| Meilisearch | **v1 terkini** (pin tag dalam `.env` `MEILI_IMAGE_TAG`; JANGAN `latest`) | Pemacu rasmi Scout; typo-tolerant; highlight |
| Tesseract | **5.3.4** + `tesseract-ocr-msa` | ✅ **DIUJI SANDBOX 6/7/2026** — pek `msa` wujud dalam apt, OCR surat BM 100% tepat (§12) |
| OCRmyPDF + img2pdf | terkini (pip, venv dalam imej) | ✅ **DIUJI** — output PDF/A-2b (format arkib) |
| Caddy | 2 (pakej OS host) | Auto-HTTPS |

### 3.3 Pakej Composer (jalankan tepat)

```bash
composer create-project laravel/laravel:^12.0 diwan && cd diwan
composer require filament/filament:"~4.0"
composer require spatie/laravel-medialibrary spatie/laravel-activitylog spatie/laravel-backup
composer require laravel/scout meilisearch/meilisearch-php http-interop/http-factory-guzzle
composer require laravel/horizon league/flysystem-aws-s3-v3:"^3.0"
composer require webklex/laravel-imap laravel-notification-channels/telegram
composer require simplesoftwareio/simple-qrcode barryvdh/laravel-dompdf
composer require --dev pestphp/pest pestphp/pest-plugin-laravel
```
**Nota v2.0:** `spatie/laravel-permission` DIGUGURKAN dengan sengaja — peranan kini per-masjid; gabungan ciri "teams" spatie + tenancy Filament dilaporkan komuniti mempunyai kes tepi halus. Pendekatan pivot+config (§6.0) lebih ringkas, deterministik, mudah diuji. Pakej tanpa pin: biarkan Composer selesaikan versi serasi L12; jika konflik, laporkan — jangan tukar Laravel/Filament senyap.

### 3.4 Struktur repositori sasaran (fail utama yang mesti wujud)

```
diwan/
├── app/
│   ├── Enums/{Sensitivity,RecordStatus,RecordDirection,MinitPriority,MinitStatus,ApprovalStatus,
│   │          OcrStatus,SourceChannel,MosqueStatus,OrderStatus,RetentionAction}.php
│   ├── Models/{User,Mosque,MosqueUser,ClassificationNode,RegistryFile,Record,Minit,MinitRecipient,
│   │          Approval,RetentionRule,DisposalBatch,DisposalItem,SensitiveAccessLog,NotificationLog,
│   │          LoginToken,PlatformSetting,FileAccessGrant,StorageOrder,StorageAddon}.php
│   ├── Concerns/{BelongsToMosque,ChecksSensitivity}.php
│   ├── Support/{Roles.php}                          # baca config/roles.php
│   ├── Policies/{RecordPolicy,RegistryFilePolicy,MinitPolicy,ApprovalPolicy,MosquePolicy,
│   │          UserPolicy,ClassificationNodePolicy,RetentionRulePolicy,DisposalBatchPolicy}.php
│   ├── Services/{WhatsAppGateway,RecordNumberingService,OcrService,SearchService,InboxIngestService,
│   │          DisposalService,QuotaService,RetentionEngine,ExportService,BillingService,
│   │          MosqueProvisioningService}.php
│   ├── Jobs/{ProcessOcrJob,SendWhatsAppJob,FetchMailJob,ExecuteAutoDisposalJob,BuildExportZipJob,
│   │          ReconcileStorageJob,ExpireAddonsJob}.php
│   ├── Observers/MediaObserver.php
│   ├── Notifications/{MinitRoutedNotification,InboxNewItemNotification,ApprovalRequestedNotification,
│   │          ApprovalDecidedNotification,MinitReminderNotification,GatewayDownNotification,
│   │          QuotaThresholdNotification,AddonExpiringNotification,RetentionNoticeNotification,
│   │          AutoDisposalDoneNotification,ExportReadyNotification,RegistrationApprovedNotification,
│   │          NewRegistrationNotification,NewStorageOrderNotification}.php
│   ├── Notifications/Channels/WhatsAppChannel.php
│   ├── Http/Controllers/Webhooks/{WhatsAppWebhookController,TelegramWebhookController}.php
│   ├── Http/Controllers/{MagicLoginController,SecureFileController,RecordDeepLinkController}.php
│   ├── Http/Middleware/{ApplyTenantScopes,EnsureUserIsActive,EnsureMosqueActive}.php
│   ├── Livewire/RegisterMosque.php                  # /daftar
│   ├── Filament/Admin/...                           # panel superadmin §9.B
│   ├── Filament/App/...                             # panel masjid §9.C (resources, pages, widgets)
│   └── Console/Commands/{FetchMail,SimulateWhatsApp,SyncMeiliSettings,SendMinitReminders,PingGateway,
│              RunRetentionNotices,RunRetentionExecute,ReconcileStorage,ExpireAddons,
│              TelegramSetWebhook,MakeSuperadmin}.php
├── config/{diwan.php,roles.php,record_types.php,filesystems.php,scout.php,backup.php,...}
├── database/
│   ├── migrations/                                  # §5 turutan tepat
│   └── seeders/{PlatformSettingSeeder,RetentionRuleSeeder,DemoSeeder}.php
│   └── seeders/data/kf_template.php                 # §7
├── docker/{Dockerfile,nginx.conf,php.ini}
├── docker-compose.yml · Caddyfile.example · scripts/rclone-offsite.sh
├── tests/Feature/{RecordNumberingTest,SensitivityPolicyTest,TenantIsolationTest,WhatsAppWebhookTest,
│              DedupTest,InboxClassifyTest,OcrPipelineTest,QuotaTest,RetentionEngineTest,RegistrationTest}.php
└── DIWAN-SPEC.md                                    # salin dokumen ini ke akar repo
```

---

## §4. INFRASTRUKTUR & DEPLOYMENT

### 4.1 Saiz server (realistik)

| Konfigurasi | Kesesuaian |
|---|---|
| 4 vCPU / 8 GB / 80 GB SSD | **Disyorkan mula** (multi-tenant; Meili membesar dengan jumlah dokumen SEMUA masjid) + swap 2GB |
| 2 vCPU / 4 GB | Dev/staging sahaja |
| Cakera | Blob semua di COS — cakera hanya OS, kod, DB metadata, indeks Meili, log |

Swap (sekali pada host):
```bash
fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab
```

### 4.2 Tencent COS ✋ (langkah manual)
1. Bucket **`diwan-docs-<APPID>`** region **`ap-singapore`** — Private, Versioning ON, SSE-COS ON.
2. Bucket **`diwan-backup-<APPID>`** region **`ap-jakarta`** — Private, SSE ON, lifecycle: padam objek >90 hari.
3. Lifecycle bucket utama: padam prefix `tenants/*/exports/` selepas **14 hari** (fail eksport sementara §16.4).
4. Sub-akaun CAM `diwan-app`, polisi HANYA 2 bucket ini (Get/Put/Delete/List/Head + versi) → SecretId/Key ke `.env`. JANGAN kunci akaun utama.
5. Endpoint S3: `https://cos.ap-singapore.myqcloud.com` (& ap-jakarta).

Struktur laluan objek:
```
tenants/{mosque_id}/records/{tahun}/{record_ulid}/original/{namafail}
tenants/{mosque_id}/records/{tahun}/{record_ulid}/derived/searchable.pdf
tenants/{mosque_id}/records/{tahun}/{record_ulid}/attachments/{n}-{namafail}
tenants/{mosque_id}/exports/{export_ulid}.zip
tenants/{mosque_id}/disposal-certs/{batch_id}.pdf
platform/invoices/{invoice_no}.pdf
```

`config/filesystems.php` — dua disk:
```php
'cos' => [
  'driver'=>'s3','key'=>env('COS_SECRET_ID'),'secret'=>env('COS_SECRET_KEY'),
  'region'=>env('COS_REGION','ap-singapore'),'bucket'=>env('COS_BUCKET'),
  'endpoint'=>env('COS_ENDPOINT'),'use_path_style_endpoint'=>false,'throw'=>true,'visibility'=>'private',
],
'cos_backup' => [
  'driver'=>'s3','key'=>env('COS_SECRET_ID'),'secret'=>env('COS_SECRET_KEY'),
  'region'=>env('COS_BACKUP_REGION','ap-jakarta'),'bucket'=>env('COS_BACKUP_BUCKET'),
  'endpoint'=>env('COS_BACKUP_ENDPOINT'),'use_path_style_endpoint'=>false,'throw'=>true,
],
```
⚠️ Jika `temporaryUrl()` gagal terhadap COS semasa ujian penerimaan: fallback WAJIB sudah dibina — route `GET /secure-file/{media}` (auth + policy + pengesahan tenant + log) yang stream fail melalui aplikasi. Signed URL diutamakan; stream = pelan B.

### 4.3 `docker-compose.yml` (fail penuh)

```yaml
services:
  app:
    build: { context: ., dockerfile: docker/Dockerfile }
    restart: unless-stopped
    env_file: .env
    volumes: [ "./:/var/www/html", "diwan_tmp:/var/www/html/storage/app/tmp" ]
    depends_on: [ db, redis, meilisearch ]
  worker:
    build: { context: ., dockerfile: docker/Dockerfile }
    restart: unless-stopped
    command: php artisan horizon
    env_file: .env
    volumes: [ "./:/var/www/html", "diwan_tmp:/var/www/html/storage/app/tmp" ]
    depends_on: [ db, redis ]
  scheduler:
    build: { context: ., dockerfile: docker/Dockerfile }
    restart: unless-stopped
    command: php artisan schedule:work
    env_file: .env
    volumes: [ "./:/var/www/html" ]
    depends_on: [ db, redis ]
  nginx:
    image: nginx:1.27-alpine
    restart: unless-stopped
    ports: [ "127.0.0.1:8080:80" ]
    volumes:
      - ./:/var/www/html
      - ./docker/nginx.conf:/etc/nginx/conf.d/default.conf
    depends_on: [ app ]
  db:
    image: postgres:16-alpine
    restart: unless-stopped
    environment:
      POSTGRES_DB: ${DB_DATABASE}
      POSTGRES_USER: ${DB_USERNAME}
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes: [ "diwan_pgdata:/var/lib/postgresql/data" ]
  redis:
    image: redis:7-alpine
    restart: unless-stopped
    volumes: [ "diwan_redis:/data" ]
  meilisearch:
    image: getmeili/meilisearch:${MEILI_IMAGE_TAG}
    restart: unless-stopped
    environment:
      MEILI_MASTER_KEY: ${MEILISEARCH_KEY}
      MEILI_ENV: production
      MEILI_NO_ANALYTICS: "true"
    volumes: [ "diwan_meili:/meili_data" ]
volumes: { diwan_pgdata: {}, diwan_redis: {}, diwan_meili: {}, diwan_tmp: {} }
```

### 4.4 `docker/Dockerfile` (fail penuh — OCR terbina dalam imej, ✅ arahan diuji)

```dockerfile
FROM php:8.3-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libpq-dev libzip-dev libicu-dev libpng-dev libjpeg-dev libfreetype6-dev libexif-dev \
    tesseract-ocr tesseract-ocr-msa ghostscript qpdf unpaper pngquant \
    python3 python3-pip python3-venv \
 && python3 -m venv /opt/ocr && /opt/ocr/bin/pip install --no-cache-dir ocrmypdf img2pdf \
 && ln -s /opt/ocr/bin/ocrmypdf /usr/local/bin/ocrmypdf \
 && ln -s /opt/ocr/bin/img2pdf /usr/local/bin/img2pdf \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo_pgsql pgsql intl zip gd bcmath pcntl exif \
 && pecl install redis && docker-php-ext-enable redis \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY docker/php.ini /usr/local/etc/php/conf.d/diwan.ini
```

`docker/php.ini`:
```ini
upload_max_filesize = 25M
post_max_size = 30M
memory_limit = 512M
max_execution_time = 120
```

`docker/nginx.conf`:
```nginx
server {
    listen 80;
    root /var/www/html/public;
    index index.php;
    client_max_body_size 30M;
    add_header X-Frame-Options SAMEORIGIN;
    add_header X-Content-Type-Options nosniff;
    add_header Referrer-Policy strict-origin-when-cross-origin;
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 120;
    }
}
```

### 4.5 TLS (host)
Pasang Caddy → `Caddyfile`:
```
diwan.wehdah.my {
    reverse_proxy 127.0.0.1:8080
}
```
DNS A `diwan.wehdah.my` → IP Lighthouse. Caddy urus sijil automatik.

### 4.6 Sandaran 3 lapis (melindungi SEMUA tenant sekali gus)
1. **COS versioning** bucket utama (padam/tulis-ganti tidak sengaja).
2. **spatie/laravel-backup** → disk `cos_backup`: dump PostgreSQL + `.env` + `storage/app` (kecil). Jadual harian **02:30**; simpanan diurus lifecycle 90 hari; notifikasi kegagalan → e-mel superadmin.
3. **Offsite disulitkan mingguan** (cron host) `scripts/rclone-offsite.sh`:
```bash
#!/usr/bin/env bash
# Prasyarat sekali: rclone config — remote "cosb" (s3 endpoint ap-jakarta) dan
# remote "gdrive-crypt" (jenis crypt membalut Google Drive; kata laluan crypt disimpan LUAR server)
set -euo pipefail
rclone sync cosb:${COS_BACKUP_BUCKET}/ gdrive-crypt:diwan-offsite/ --transfers 4 --log-file /var/log/diwan-offsite.log
```
Cron host: `0 4 * * 0 /opt/diwan/scripts/rclone-offsite.sh`
⚠️ PDPA rentas sempadan: apa yang tiba di Google ialah sifer. JANGAN sync bucket dokumen mentah.

**Runbook pemulihan (uji SEKALI — §18):** VM baharu → Docker+Caddy → clone repo → `.env` dari sandaran → `docker compose up -d --build` → muat turun zip sandaran terkini dari `cos_backup` → restore pg dump → `php artisan scout:import "App\Models\Record"` + `diwan:sync-meili` → pulih. Blob tidak perlu restore (kekal di COS utama).

### 4.7 `.env.example` (LENGKAP — semua kunci yang kod baca)

```env
APP_NAME=Diwan
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://diwan.wehdah.my
APP_TIMEZONE=Asia/Kuala_Lumpur
APP_LOCALE=ms

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=diwan
DB_USERNAME=diwan
DB_PASSWORD=

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true
REDIS_HOST=redis

DIWAN_STORAGE_DISK=cos            # 'local' utk dev; ujian guna Storage::fake
DIWAN_DEFAULT_QUOTA_GB=20
DIWAN_DEFAULT_RETENTION_YEARS=7
DIWAN_REGISTRATION_OPEN=true

FILESYSTEM_DISK=cos
COS_SECRET_ID=
COS_SECRET_KEY=
COS_REGION=ap-singapore
COS_BUCKET=diwan-docs-125xxxxxxx
COS_ENDPOINT=https://cos.ap-singapore.myqcloud.com
COS_BACKUP_REGION=ap-jakarta
COS_BACKUP_BUCKET=diwan-backup-125xxxxxxx
COS_BACKUP_ENDPOINT=https://cos.ap-jakarta.myqcloud.com

SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=
MEILI_IMAGE_TAG=v1.12             # semak tag v1 terkini semasa deploy; JANGAN 'latest'

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=diwan@wehdah.my
MAIL_FROM_NAME="Diwan SPDM"

WHATSAPP_DRIVER=gateway           # gateway | log (mock utk dev/ujian)
WHATSAPP_GATEWAY_URL=https://wassap.wehdah.my
WHATSAPP_GATEWAY_TOKEN=
WHATSAPP_WEBHOOK_SECRET=          # jana: openssl rand -hex 32

TELEGRAM_BOT_TOKEN=
TELEGRAM_WEBHOOK_SECRET=

IMAP_ENABLED=true
IMAP_HOST=imap.gmail.com
IMAP_PORT=993
IMAP_ENCRYPTION=ssl
IMAP_USERNAME=scan.diwan@gmail.com   # plus-addressing per masjid: scan.diwan+{slug}@gmail.com
IMAP_PASSWORD=                        # Gmail App Password (2FA wajib)

OCR_LANGS=msa+eng
```

---
## §5. MODEL DATA (SKEMA PENUH v2.0)

Konvensyen: semua jadual berdata-tenant ada `mosque_id` FK **NOT NULL + indeks komposit** dan guna trait `BelongsToMosque` (§15.2). Semua enum = PHP Backed Enum + kolum string (bukan enum Postgres). `id` bigint auto + `timestamps` kecuali dinyatakan. Turutan migrasi = turutan di bawah.

### 5.1 `mosques` (TENANT)
| Kolum | Jenis | Nota |
|---|---|---|
| name | string | "Masjid Al-Muttaqin Wangsa Melawati" |
| slug | string unique | `mam` — URL tenant; a-z0-9 |
| code | string(6) unique | Akronim penomboran: `MAM` — kunci selepas ada fail |
| state / district / address / phone | string nullable | negeri dropdown 16 |
| status | string default 'menunggu' | menunggu / aktif / digantung / ditutup |
| storage_quota_bytes | bigint | lalai 20GB (base — superadmin boleh ubah bila-bila, dilog) |
| storage_used_bytes | bigint default 0 | kaunter cache (§5.14) |
| auto_disposal_enabled | boolean default true | suis §2.2/§16 |
| retention_ack_at / retention_ack_by | timestamp / FK nullable | pengakuan §16.2 |
| wa_session_id | string nullable unique | ID sesi masjid pada gateway whatsmeow (§11.1); NULL = saluran WA masjid tidak aktif |
| wa_number | string(20) nullable | Nombor WA rasmi masjid (paparan sahaja) |
| settings | jsonb default '{}' | `data_protection_rep{name,phone,email}`, `wa_intake_enabled:true`, `wa_intake_keyword:"spdm"`, `mail_intake_enabled`, `mail_intake_keyword`, `mail_intake_senders[]` |
| approved_at / approved_by | timestamp / FK nullable | |
| softDeletes | | tutup akaun §10.M |

### 5.2 `mosque_user` (pivot keahlian + PERANAN)
mosque_id FK, user_id FK, **role** string (§6.1), joined_at. Unique(mosque_id, user_id).

### 5.3 `users` (GLOBAL — tiada mosque_id)
| Kolum | Jenis | Nota |
|---|---|---|
| name / email unique / password nullable | | kata laluan = fallback; magic link laluan utama |
| **is_superadmin** | boolean default false | `Gate::before` lulus semua + akses semua tenant (§6.0) |
| phone_wa | string(20) nullable unique | E.164 tanpa `+` cth `60123456789` — WAJIB format ini (padanan webhook global) |
| telegram_chat_id | string nullable | aliran sambung §11.2 |
| jawatan | string nullable | teks bebas |
| notify_whatsapp / notify_telegram / notify_email | boolean | lalai true / false / true |
| is_active | boolean default true | nyahaktif ≠ padam; TIADA butang padam |
| last_login_at | timestamp nullable | |
Filament: `getTenants()` = masjid pivot (superadmin → SEMUA masjid aktif); `canAccessTenant()` = pivot wujud ATAU is_superadmin.

### 5.4 `login_tokens`
email (index), token char(64) [SHA-256 token mentah], expires_at, used_at nullable, ip string(45) nullable. Tiada updated_at.

### 5.5 `classification_nodes` (+mosque_id)
parent_id FK-self nullable, level string (`fungsi`|`aktiviti`|`sub_aktiviti`), code string (fungsi `500`; aktiviti `500-1`; sub `500-1/2`), title, default_sensitivity string default 'dalaman', is_active bool, sort int. Unique(mosque_id, parent_id, code).

### 5.6 `registry_files` (+mosque_id)
| Kolum | Jenis | Nota |
|---|---|---|
| classification_node_id | FK | nod `aktiviti` atau `sub_aktiviti` |
| transaction_no / volume | int / int default 1 | |
| file_no | string | dijana §5.15 — **unique(mosque_id, file_no)** |
| title / sensitivity | string | |
| status | string default 'terbuka' | terbuka / tutup |
| enclosure_count | int default 0 | kaunter kandungan |
| opened_at / closed_at / closed_reason / created_by | | |
Unique(mosque_id, classification_node_id, transaction_no, volume).

### 5.7 `records` (TERAS; +mosque_id index)
| Kolum | Jenis | Nota |
|---|---|---|
| ulid | ulid unique | URL deep-link |
| registry_file_id | FK nullable index | NULL = masih Peti Masuk |
| record_type | string(50) index | kunci `config/record_types.php` |
| title | string nullable | NULL sah di peti masuk |
| our_ref / their_ref | string nullable | Ruj. Kami / Ruj. Tuan |
| record_date / received_date | date nullable | |
| direction | string nullable | masuk / keluar / dalaman |
| sender_name / sender_org / recipient_name | string nullable | |
| sensitivity | string default 'dalaman' index | umum / dalaman / sulit |
| status | string default 'peti_masuk' index | peti_masuk / difailkan / diganti / dilupus |
| enclosure_no | int nullable | no. kandungan dalam fail |
| metadata | jsonb default '{}' | medan khusus jenis §8 |
| ocr_status / ocr_text | string default 'belum' / text nullable | belum/dalam_proses/siap/gagal; teks had 1,000,000 aksara |
| sha256 | char(64) nullable index | dedup (BERSKOP masjid) |
| source_channel / source_meta | string / jsonb | muat_naik/emel/whatsapp/imbasan; `{"from":..,"subject":..}` |
| created_by / filed_by / filed_at | FK / FK nullable / ts nullable | |
| superseded_by_record_id | FK-self nullable | ganti versi |
| **legal_hold** | boolean default false | §16 — dipegang = tak dipadam |
| **retention_due_at** | date nullable | dikira enjin §16 (NULL = kekal/hold) |
| **retention_notified** | jsonb default '{}' | `{"t90":ts,"t30":ts,"t7":ts}` |
| softDeletes | | |
Indeks: (mosque_id,status), (mosque_id,retention_due_at), (registry_file_id,enclosure_no), (record_type,record_date), (mosque_id,sha256).
Lampiran → **spatie/laravel-medialibrary**: collections `original` (1), `derived` (searchable.pdf), `attachments` (banyak); disk `config('diwan.storage_disk')`; `custom_properties.sha256` setiap media; path generator custom → prefix `tenants/{mosque_id}/...` (§4.2).

### 5.8 `minits` (+mosque_id)
record_id FK, from_user_id FK, body text, priority string default 'biasa' (biasa/segera/kritikal), due_at nullable, status string default 'terbuka' (terbuka/selesai), parent_id FK-self nullable (bebenang), completed_at/by nullable.

### 5.9 `minit_recipients`
minit_id FK, user_id FK, jenis string ('tindakan'|'makluman'), read_at nullable, status string default 'belum' (belum/dibaca/selesai). Unique(minit_id,user_id).

### 5.10 `approvals` (+mosque_id)
record_id FK, requested_by FK, approver_id FK, status default 'menunggu' (menunggu/lulus/tolak), request_note / decision_note text nullable, decided_at nullable, decision_ip string(45) nullable.

### 5.11 `retention_rules` (**mosque_id NULLABLE**)
mosque_id NULL = lalai platform (superadmin); berisi = override masjid (admin_masjid). record_type string nullable, classification_prefix string nullable (cth `200`), retain_years int nullable (NULL=kekal), **action** string (`kekal`|`semak`|`auto_padam`), note. Resolusi: masjid-spesifik > lalai platform; dalam skop sama: record_type > prefix panjang > prefix pendek.

### 5.12 `disposal_batches` (+mosque_id, **kind** 'manual'|'auto') & `disposal_items`
batches: created_by, approved_by nullable, status (draf/menunggu_kelulusan/lulus/selesai/dibatalkan), executed_at, certificate_path nullable. items: batch_id, record_id, **metadata_snapshot jsonb** — salinan PENUH metadata + senarai lampiran + file_no saat pelupusan — KEKAL selamanya.

### 5.13 Bil & storan
**`storage_orders`**: mosque_id, ordered_by, gb int, unit_price_cents int (salinan kadar semasa), amount_cents int, period_months int (lalai 12; 0=kekal), status ('menunggu_bayaran'|'dibayar'|'dibatalkan'), invoice_no string unique (`INV-{YYYY}-{0001}`), invoice_path nullable, paid_at / confirmed_by nullable.
**`storage_addons`**: mosque_id, storage_order_id, gb, starts_at, expires_at nullable (NULL=kekal), status ('aktif'|'luput'). **Kuota efektif = storage_quota_bytes + Σ(addon aktif)**.
**`platform_settings`** (key→jsonb): `pricing {per_gb_year_rm:null✋, block_gb:10}`, `bank_details{...}`✋, `data_protection_officer{...}`, `default_retention_years:7`, `registration_open:true`, `terms_version`, `gateway_status{ok,checked_at}`.

### 5.14 Perakaunan & penguatkuasaan storan (`QuotaService` + `MediaObserver`)
- Observer media created/deleted: `increment/decrement mosques.storage_used_bytes` ikut `size` (atomik). SEMUA media dikira; eksport ZIP tidak (sementara, lifecycle 14 hari).
- Penguatkuasaan SEBELUM simpan di 3 pintu: UI muat naik (mesej BM + pautan Tambah Storan), webhook WA (tolak + balasan "⚠️ Kuota storan masjid penuh."), ingest e-mel (skip + notifikasi admin_masjid). **Lebih kuota = sekat TULIS sahaja; baca/muat turun sentiasa OK. JANGAN padam data kerana kuota.**
- Ambang 80% / 90% / 100% → `QuotaThresholdNotification` admin_masjid + superadmin (maks sekali per ambang per bulan).
- `ReconcileStorageJob` malam 03:00: Σ media per masjid vs kaunter; drift >1MB → betulkan + log.
- `ExpireAddonsJob` harian 06:00: notis T-30/T-7 addon; luput → status luput → kira semula kuota; jika guna>kuota → mod sekat-tulis + notifikasi.

### 5.15 Penomboran (`RecordNumberingService` — dilindungi ujian)
- **file_no**: `"{mosques.code}.{kodNod}/{transaction_no}"` + (` Jld.{n}` jika volume>1). Contoh nod `500-1/2`, transaksi ke-3 → **`MAM.500-1/2/3`**. transaction_no = max sedia ada bawah nod (semua jilid = 1 transaksi) + 1 — berskop masjid.
- **enclosure_no** semasa failkan: transaksi DB + `lockForUpdate()` baris fail → enclosure_count+1. Rujukan penuh: `{file_no}({enclosure_no})` cth **`MAM.500-1/2/3(15)`** — format DISAHKAN diekstrak sempurna OCR (§12).
- **our_ref** surat keluar auto-cadang = rujukan penuh; boleh diedit.

### 5.16 Jadual sokongan
- `sensitive_access_logs` (+mosque_id, +**is_superadmin** boolean): user_id, record_id, action ('view'|'download'), ip, user_agent, created_at sahaja.
- `notification_logs` (+mosque_id nullable): user_id nullable, channel, to, notification_type, status ('sent'|'failed'), error nullable, created_at.
- `file_access_grants`: registry_file_id, user_id, granted_by; unique(registry_file_id,user_id) — akses khas fail sulit kepada individu luar peranan lalai.
- Jadual pakej: media (medialibrary), activity_log (activitylog), notifications, jobs/failed_jobs. JANGAN pasang Telescope produksi.

---

## §6. PERANAN & KEBENARAN v2.0

### 6.0 Keputusan seni bina peranan
Peranan = **string pada pivot `mosque_user.role`**; kebenaran = **peta statik `config/roles.php`** (matriks §6.2 sebagai array). `is_superadmin` = bendera global dengan `Gate::before` (lulus semua) — dan SETIAP akses superadmin ke rekod sulit turut dilog dengan penanda (§15.4; ketelusan pemproses, didedahkan dalam terma). API dalaman: `$user->roleIn(Mosque $m): ?string`, `$user->canIn(Mosque $m, string $perm): bool`; policies memanggil dengan `Filament::getTenant()`. Kelebihan vs spatie-teams: sifar jadual tambahan, sifar kes tepi konteks, ujian mudah — pilihan sedar demi one-shot.

### 6.1 Peranan per masjid
`admin_masjid` (pentadbir akaun masjid — BAHARU) · `kerani` · `pengerusi` · `setiausaha` · `bendahari` · `nazir` · `ketua_imam` · `ajk` · `audit` (baca sahaja + log). Global: `is_superadmin`.

### 6.2 Matriks kebenaran (config/roles.php — laksanakan TEPAT)

| Kebenaran | admin_masjid | kerani | pengerusi | setiausaha | bendahari | nazir | ketua_imam | ajk | audit |
|---|---|---|---|---|---|---|---|---|---|
| inbox.view / inbox.classify | ✓ | ✓ | – | ✓ | – | – | – | – | – |
| records.view (ikut sensitiviti §6.3) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| records.create / records.update | ✓ | ✓ | – | ✓ | ✓* | – | – | – | – |
| records.move (pindah fail) | ✓ | ✓ | – | – | – | – | – | – | – |
| records.supersede (ganti versi) | ✓ | ✓ | – | ✓ | – | – | – | – | – |
| files.view | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| files.open / files.close | ✓ | ✓ | – | – | – | – | – | – | – |
| files.grant_access | ✓ | ✓ | ✓ | – | – | – | – | – | – |
| minit.create / minit.respond | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | – |
| approvals.request | ✓ | ✓ | – | ✓ | ✓ | – | – | – | – |
| approvals.decide | – | – | ✓ | – | – | ✓ | – | – | – |
| classification.manage | ✓ | ✓ | – | – | – | – | – | – | – |
| retention.manage (peraturan masjid + legal_hold) | ✓ | hold sahaja | – | – | – | – | – | – | – |
| export.create (Eksport ZIP) | ✓ | ✓ | – | – | – | – | – | – | – |
| disposal.prepare | ✓ | ✓ | – | – | – | – | – | – | – |
| disposal.approve | – | – | ✓ | – | – | – | – | – | – |
| disposal.execute (manual) | ✓ | – | – | – | – | – | – | – | – |
| users.manage (ahli masjid sendiri; §6.4) | ✓ | – | – | – | – | – | – | – | – |
| mosque.settings | ✓ | – | – | – | – | – | – | – | – |
| usage.view | ✓ | ✓ | ✓ | – | ✓ | – | – | – | – |
| storage.order | ✓ | – | – | – | ✓ | – | – | – | – |
| audit.view | ✓ | – | ✓ | – | – | – | – | – | ✓ |

\* bendahari: create/update terhad kepada rekod dalam fail klasifikasi `200` (Kewangan) — kuatkuasa dalam `RecordPolicy`.
Pengasingan sengaja: `disposal.approve` (pengerusi) ≠ `disposal.execute` (admin_masjid/superadmin) — dua orang. `admin_masjid` mewarisi semua kebenaran kerani+setiausaha secara definisi peta (senaraikan eksplisit dalam config, jangan waris dinamik).

### 6.3 Dasar sensitiviti (lapisan policy ATAS kebenaran; berskop dalam masjid)
| Tahap | Siapa boleh lihat/muat turun |
|---|---|
| `umum` | Semua ahli masjid log masuk |
| `dalaman` | Semua ahli masjid log masuk |
| `sulit` | admin_masjid, kerani, pengerusi, setiausaha, nazir + bendahari (hanya fail klasifikasi `200`/`300`) + individu dalam `file_access_grants` fail berkenaan |
Waris: rekod mewarisi max(sensitiviti rekod, sensitiviti fail). Laksana dalam trait `ChecksSensitivity` (SATU sumber kebenaran — dipanggil policies & `SearchService`). Setiap view/download `sulit` → tulis `sensitive_access_logs` (dalam page mount / controller, BUKAN policy).

### 6.4 Sekatan users.manage (admin_masjid)
Tidak boleh: sentuh akaun is_superadmin; buang/turunkan admin_masjid terakhir masjid; ubah peranan diri sendiri ke bawah; padam akaun (hanya keluarkan-dari-masjid = detach pivot; akaun global kekal).

---
## §7. KLASIFIKASI FAIL — TEMPLAT PLATFORM (SEEDER PENUH)

Templat KF disimpan dalam `database/seeders/data/kf_template.php`. Semasa masjid diluluskan (§10.I), `MosqueProvisioningService` menyalin templat → `classification_nodes` dengan `mosque_id` masjid itu. Selepas itu setiap masjid bebas mengubah suai KF sendiri (`classification.manage`). Perubahan templat kemudian TIDAK menyentuh masjid sedia ada. Akronim dalam `file_no` datang dari `mosques.code`, bukan templat.

```php
<?php
// database/seeders/data/kf_template.php — SALIN PENUH.
// Format: 'kod' => [tajuk, sensitiviti_lalai, [aktiviti...]]
return [
  '100' => ['Pentadbiran & Tadbir Urus', 'dalaman', [
      '100-1' => 'Mesyuarat AJK & Agung',
      '100-2' => 'Pelantikan & Watikah AJK / Pegawai Masjid',
      '100-3' => 'Polisi, Garis Panduan & Pekeliling Dalaman',
      '100-4' => 'Surat-Menyurat Am',
      '100-5' => 'Laporan Tahunan & Perancangan',
  ]],
  '200' => ['Kewangan', 'sulit', [
      '200-1' => 'Tabung & Kutipan',
      '200-2' => 'Resit, Baucar & Pembayaran',
      '200-3' => 'Bajet & Penyata',
      '200-4' => 'Audit & Pemeriksaan',
      '200-5' => 'Sebut Harga & Perolehan',
  ]],
  '300' => ['Kebajikan, Zakat & Khairat', 'sulit', [
      '300-1' => 'Bantuan Asnaf & Kebajikan',
      '300-2' => 'Khairat Kematian',
      '300-3' => 'Sumbangan & Penajaan Diterima',
  ]],
  '400' => ['Aktiviti & Program', 'dalaman', [
      '400-1' => 'Program Bulanan & Mingguan (Kuliah, Riadah)',
      '400-2' => 'Program Ramadan & Hari Kebesaran',
      '400-3' => 'Ibadah Korban',
      '400-4' => 'Program Komuniti & Gotong-royong',
  ]],
  '500' => ['Fasiliti & Aset', 'dalaman', [
      '500-1' => 'Tempahan Dewan & Ruang',
      '500-2' => 'Penyelenggaraan & Baik Pulih',
      '500-3' => 'Inventori & Aset (termasuk CCTV/ICT)',
      '500-4' => 'Utiliti & Perkhidmatan',
  ]],
  '600' => ['Pendidikan & Pengimarahan', 'dalaman', [
      '600-1' => 'Kelas Pengajian & KAFA',
      '600-2' => 'Jadual Imam, Bilal & Penceramah',
  ]],
  '700' => ['Komunikasi & Media', 'umum', [
      '700-1' => 'Poster & Hebahan',
      '700-2' => 'Laman Web & Media Sosial',
      '700-3' => 'Foto & Dokumentasi Program',
  ]],
  '800' => ['Sumber Manusia & Petugas', 'sulit', [
      '800-1' => 'Rekod Petugas & Sukarelawan',
      '800-2' => 'Elaun & Saguhati',
  ]],
  '900' => ['Perhubungan Luar', 'dalaman', [
      '900-1' => 'Majlis Agama / JAI Negeri / Pejabat Mufti',
      '900-2' => 'Pihak Berkuasa Tempatan & Agensi',
      '900-3' => 'Penaja & Rakan Strategik',
  ]],
];
```

⚠️ Ini KF operasi yang direka mengikut corak ANM. Ia BUKAN KF yang diluluskan Arkib Negara (kelulusan ANM hanya wajib untuk pelaksanaan DDMS rasmi sektor awam). Jika Majlis Agama mengeluarkan KF seragam masjid kelak, struktur `classification_nodes` menampung penstrukturan semula tanpa migrasi skema.

---

## §8. JENIS REKOD (17) & `config/record_types.php` PENUH

Prinsip: medan teras (title, our_ref, their_ref, record_date, received_date, direction, sender_*, recipient_name) = kolum sebenar §5.7; medan khusus jenis = `metadata` JSONB. Borang Filament dijana **dinamik** daripada config ini — satu komponen borang, 17 definisi. `fields` = medan metadata TAMBAHAN sahaja; medan teras yang relevan disenaraikan dalam `core` (tanda `*` = required). Jenis rekod adalah GLOBAL platform (sama untuk semua masjid) pada MVP; jenis tersuai per masjid = Fasa 2.

```php
<?php
// config/record_types.php — SALIN PENUH. Jenis medan: text|textarea|date|select|number|toggle
return [
  'surat_menyurat' => ['label' => 'Surat Menyurat', 'icon' => 'heroicon-o-envelope',
    'core' => ['direction*','our_ref','their_ref','record_date*','received_date','sender_name','sender_org','recipient_name'],
    'fields' => [
      ['name'=>'jumlah_lampiran','label'=>'Jumlah Lampiran','type'=>'number'],
      ['name'=>'untuk_perhatian','label'=>'Untuk Perhatian (u.p.)','type'=>'text'],
    ]],
  'memo' => ['label' => 'Memo', 'icon' => 'heroicon-o-document-text',
    'core' => ['our_ref','record_date*','sender_name*','recipient_name*'],
    'fields' => [['name'=>'jawatan_pengirim','label'=>'Jawatan Pengirim','type'=>'text']]],
  'emel' => ['label' => 'E-mel', 'icon' => 'heroicon-o-at-symbol',
    'core' => ['record_date*','sender_name*','recipient_name*'],
    'fields' => [
      ['name'=>'alamat_pengirim','label'=>'Alamat E-mel Pengirim','type'=>'text'],
      ['name'=>'cc','label'=>'CC','type'=>'text'],
      ['name'=>'subjek_asal','label'=>'Subjek Asal','type'=>'text'],
    ]],
  'emel_muatnaik' => ['label' => 'E-mel Muat Naik', 'icon' => 'heroicon-o-arrow-up-tray',
    'core' => ['record_date*','sender_name'],
    'fields' => [['name'=>'catatan_sumber','label'=>'Catatan Sumber','type'=>'text']]],
  'minit_mesyuarat' => ['label' => 'Minit Mesyuarat', 'icon' => 'heroicon-o-users',
    'core' => ['record_date*'],
    'fields' => [
      ['name'=>'nama_mesyuarat','label'=>'Nama Mesyuarat','type'=>'text','required'=>true],
      ['name'=>'bilangan','label'=>'Bilangan (cth 3/2026)','type'=>'text'],
      ['name'=>'tempat','label'=>'Tempat','type'=>'text'],
      ['name'=>'pengerusi_mesyuarat','label'=>'Pengerusi Mesyuarat','type'=>'text'],
    ]],
  'laporan' => ['label' => 'Laporan', 'icon' => 'heroicon-o-chart-bar',
    'core' => ['record_date*','sender_name'],
    'fields' => [['name'=>'penyedia','label'=>'Penyedia','type'=>'text'],
                 ['name'=>'tempoh_liputan','label'=>'Tempoh Liputan','type'=>'text']]],
  'kertas_kerja' => ['label' => 'Kertas Kerja / Konsep', 'icon' => 'heroicon-o-light-bulb',
    'core' => ['record_date*'],
    'fields' => [['name'=>'penyedia','label'=>'Penyedia','type'=>'text'],
                 ['name'=>'anggaran_kos','label'=>'Anggaran Kos (RM)','type'=>'number']]],
  'borang' => ['label' => 'Borang', 'icon' => 'heroicon-o-clipboard-document-list',
    'core' => ['record_date','received_date','sender_name'],
    'fields' => [['name'=>'jenis_borang','label'=>'Jenis Borang','type'=>'text'],
                 ['name'=>'no_borang','label'=>'No. Borang','type'=>'text']]],
  'tender_sebutharga' => ['label' => 'Dokumen Tender / Sebut Harga', 'icon' => 'heroicon-o-banknotes',
    'core' => ['record_date*','received_date','sender_org*'],
    'fields' => [
      ['name'=>'no_sebutharga','label'=>'No. Tender/Sebut Harga','type'=>'text','required'=>true],
      ['name'=>'nilai','label'=>'Nilai Tawaran (RM)','type'=>'number'],
      ['name'=>'tarikh_tutup','label'=>'Tarikh Tutup','type'=>'date'],
    ]],
  'perjanjian' => ['label' => 'Perjanjian / Kontrak / MoU', 'icon' => 'heroicon-o-scale',
    'core' => ['record_date*','our_ref'],
    'fields' => [
      ['name'=>'pihak_terlibat','label'=>'Pihak-Pihak Terlibat','type'=>'textarea','required'=>true],
      ['name'=>'tarikh_mula','label'=>'Tarikh Kuat Kuasa','type'=>'date'],
      ['name'=>'tarikh_tamat','label'=>'Tarikh Tamat','type'=>'date'],
      ['name'=>'nilai_kontrak','label'=>'Nilai (RM)','type'=>'number'],
      ['name'=>'lokasi_asal','label'=>'Lokasi Simpanan Salinan Asal Fizikal','type'=>'text','required'=>true],
    ]],
  'pekeliling' => ['label' => 'Pekeliling', 'icon' => 'heroicon-o-megaphone',
    'core' => ['record_date*','received_date','sender_org','their_ref'],
    'fields' => [['name'=>'bilangan_tahun','label'=>'Bilangan/Tahun','type'=>'text'],
                 ['name'=>'tarikh_kuatkuasa','label'=>'Tarikh Kuat Kuasa','type'=>'date']]],
  'garis_panduan' => ['label' => 'Garis Panduan', 'icon' => 'heroicon-o-book-open',
    'core' => ['record_date','sender_org'],
    'fields' => [['name'=>'versi','label'=>'Versi/Edisi','type'=>'text'],
                 ['name'=>'penerbit','label'=>'Penerbit','type'=>'text']]],
  'jadual' => ['label' => 'Jadual', 'icon' => 'heroicon-o-calendar-days',
    'core' => ['record_date*'],
    'fields' => [['name'=>'tempoh','label'=>'Tempoh Jadual (cth Julai–Sep 2026)','type'=>'text']]],
  'poster' => ['label' => 'Poster / Hebahan', 'icon' => 'heroicon-o-photo',
    'core' => ['record_date*'],
    'fields' => [['name'=>'nama_program','label'=>'Nama Program','type'=>'text'],
                 ['name'=>'tarikh_program','label'=>'Tarikh Program','type'=>'date'],
                 ['name'=>'pereka','label'=>'Pereka','type'=>'text']]],
  'foto' => ['label' => 'Foto / Dokumentasi', 'icon' => 'heroicon-o-camera',
    'core' => ['record_date*'],
    'fields' => [['name'=>'nama_program','label'=>'Program/Peristiwa','type'=>'text','required'=>true],
                 ['name'=>'jurugambar','label'=>'Jurugambar','type'=>'text']]],
  'sijil' => ['label' => 'Sijil', 'icon' => 'heroicon-o-academic-cap',
    'core' => ['record_date*','sender_org'],
    'fields' => [['name'=>'penerima_sijil','label'=>'Penerima','type'=>'text'],
                 ['name'=>'sah_hingga','label'=>'Sah Hingga','type'=>'date'],
                 ['name'=>'lokasi_asal','label'=>'Lokasi Simpanan Salinan Asal','type'=>'text']]],
  'rekod_kewangan' => ['label' => 'Rekod Kewangan (Resit/Baucar)', 'icon' => 'heroicon-o-receipt-percent',
    'core' => ['record_date*','our_ref'],
    'fields' => [
      ['name'=>'jenis_dokumen','label'=>'Jenis','type'=>'select','options'=>['resit'=>'Resit','baucar'=>'Baucar Bayaran','invois'=>'Invois','penyata'=>'Penyata'],'required'=>true],
      ['name'=>'amaun','label'=>'Amaun (RM)','type'=>'number','required'=>true],
      ['name'=>'pihak','label'=>'Dibayar Kepada / Diterima Daripada','type'=>'text','required'=>true],
      ['name'=>'no_dokumen','label'=>'No. Resit/Baucar','type'=>'text'],
    ]],
];
// LARANGAN: jangan tambah medan no. kad pengenalan (§15.3).
```

---
## §9. SKRIN DEMI SKRIN v2.0 — HALAMAN AWAM + DUA PANEL

### 9.A Halaman awam
- **`/`**: halaman ringkas jenama Diwan (satu seksyen hero) + butang "Daftar Masjid" & "Log Masuk".
- **`/daftar`** (Livewire `RegisterMosque`, aktif jika `registration_open`; throttle 3/jam/IP): borang — Nama Masjid*, Negeri* (dropdown 16), Daerah, Cadangan Kod Akronim* (3–6 huruf, semak unik nyata), Slug auto-cadang dari nama (boleh ubah, semak unik), Nama Pentadbir*, E-mel*, No. WhatsApp*, ☑ Setuju Terma Perkhidmatan & DPA (modal teks), ☑ Pengakuan Dasar Retensi (teks §16.2). Hantar → transaksi: `mosques(status=menunggu)` + `users`(is_active=false, atau attach jika e-mel wujud) + pivot role=admin_masjid → e-mel "Permohonan diterima, menunggu kelulusan" → `NewRegistrationNotification` kepada semua superadmin.
- **`/masuk/{token}`** — magic link (§15.1). Selepas sah: 1 masjid → terus `/app/{slug}`; >1 masjid → pemilih tenant Filament; superadmin → `/admin`.
- **`/r/{ulid}`** — deep-link rekod: auth → sahkan pengguna ahli tenant rekod (atau superadmin) → redirect halaman rekod dalam `/app/{slug}`; bukan ahli → 404.

### 9.B PANEL SUPERADMIN `/admin` (Filament panel kedua, TANPA tenancy; gate `is_superadmin`)
Navigasi: Papan Pemuka · Masjid · Pendaftaran Menunggu [badge] · Pesanan Storan [badge] · Peraturan Retensi Lalai · Semua Pengguna · Log Notifikasi · Log Audit Global · Tetapan Platform.
1. **Papan Pemuka**: kad — masjid aktif, jumlah pengguna, storan agregat (GB), pendaftaran menunggu, pesanan menunggu, rekod diproses hari ini, kesihatan gateway WA/IMAP/COS (hijau-merah), kedalaman queue OCR; carta pertumbuhan storan platform 12 bulan; jadual 10 masjid teratas ikut storan.
2. **Masjid** (`MosqueResource`): senarai (nama, slug, status badge, bar guna/kuota, bil. ahli, bil. rekod). Halaman lihat — tab: *Maklumat* (edit; **Kuota Asas GB boleh ubah terus** — medan sebab wajib, dilog), *Penggunaan* (seperti 9.C.10), *Add-on*, *Ahli*, *Retensi* (peraturan efektif + suis `auto_disposal_enabled`), *Log*. Tindakan header: **Masuk Panel Masjid** (pautan `/app/{slug}`), **Gantung/Aktifkan**, **Tutup Akaun** (§10.M; dua pengesahan + taip slug).
3. **Pendaftaran Menunggu**: semak → **Lulus** (jalankan `MosqueProvisioningService`) / **Tolak** (sebab wajib; e-mel dihantar).
4. **Pesanan Storan**: semua tenant; lihat invois PDF; **Tandakan Dibayar** (sah kata laluan) → addon aktif + notifikasi; **Batal**.
5. **Peraturan Retensi Lalai**: CRUD `retention_rules(mosque_id=NULL)` (§16.1).
6. **Semua Pengguna**: carian global; lihat keahlian; nyahaktif global; jadikan/lucut superadmin (dilog).
7. **Tetapan Platform**: harga RM/GB/tahun + saiz blok GB; butiran bank; DPO platform; `registration_open`; retensi lalai; butang uji Gateway WA / Telegram / COS (tulis+baca+padam objek ujian); paparan Runbook Insiden versi platform.

### 9.C PANEL MASJID `/app/{slug}` (tenancy `Mosque`; menu tukar-masjid automatik untuk pengguna berbilang masjid)
Jenama panel: "Diwan · {nama masjid}". Navigasi (kumpulan & susunan tepat; item Pentadbiran ikut kebenaran):

```
(tanpa kumpulan)  Papan Pemuka
REGISTRI          Peti Masuk [badge: bil. peti_masuk] · Rekod · Fail
TUGASAN           Minit Saya [badge] · Kelulusan [badge] · Carian · Laporan
AKAUN             Penggunaan & Storan · Retensi & Pegangan
PENTADBIRAN       Ahli & Peranan · Klasifikasi Fail · Pelupusan ·
                  Log Audit · Log Akses Sulit · Tetapan Masjid
(menu pengguna)   Profil · Tukar Masjid · Log Keluar
```

**9.C.1 Log Masuk** (`/app/login` halaman custom): medan e-mel + butang **"Hantar Pautan Log Masuk"** → `login_tokens` (token 64 aksara, simpan hash SHA-256, luput 15 minit) → e-mel pautan `/masuk/{token}`. Pautan kecil "Log masuk dengan kata laluan" (fallback). Rate limit 5/min/IP kedua-dua laluan.

**9.C.2 Papan Pemuka**: (a) `StatsOverview` 4 kad: *Peti Masuk*, *Minit Saya Belum Selesai*, *Lewat SLA*, *Rekod Bulan Ini*; (b) **tolok storan** guna/kuota efektif (warna ikut ambang 80/90/100) + pautan Penggunaan; (c) kad "Rekod menunggu tindakan retensi" (bil. due ≤90 hari) + pautan; (d) jadual "Minit Perlu Tindakan Saya" (5 teratas); (e) jadual "Rekod Terkini" (10, ikut kebenaran); (f) banner merah jika `gateway_status.ok=false`: "⚠️ Gateway WhatsApp tidak dapat dihubungi — notifikasi jatuh ke Telegram/E-mel."; (g) untuk masjid baharu: **checklist onboarding** (Tetapan Masjid → Jemput AJK → Set e-mel pengimbas → Uji WhatsApp) — hilang bila semua selesai.

**9.C.3 Peti Masuk** (`Record` status=peti_masuk):
- Jadual: ikon jenis fail, sumber (badge 📤 Muat Naik / 📧 E-mel / 💬 WhatsApp), maklumat sumber (`source_meta`), tarikh terima, status OCR, penanda duplikat (ikon ⚠ jika sha256 wujud pada rekod lain MASJID INI — tooltip pautan).
- Tindakan baris: **Klasifikasikan** (utama) · Lihat pratonton · Muat turun · Padam (soft; sebab wajib — spam).
- Header: **"+ Muat Naik Dokumen"** (dropzone berbilang; setiap fail → 1 item; semak kuota SEBELUM terima §5.14; kira sha256; hantar `ProcessOcrJob`).
- **Wizard "Klasifikasikan" (modal 3 langkah)** — jantung sistem:
  1. **Jenis & Metadata**: grid ikon 17 jenis (§8) → borang dinamik core+fields. Prefill: `title` ← subjek e-mel/nama fail; `record_date` ← hari ini.
  2. **Pilih Fail**: carian/senarai `registry_files` terbuka masjid ini (file_no + tajuk + sensitiviti; Select DISKOP tenant §15.2). Butang "＋ Buka Fail Baharu" (jika `files.open`): pilih nod KF → tajuk → sistem jana file_no (§5.15) → kembali ke wizard dengan fail terpilih.
  3. **Sensitiviti & Sahkan**: lalai = max(waris fail, semasa); ringkasan penuh; **Sahkan & Failkan** → transaksi: enclosure_no diperuntuk, status=`difailkan`, filed_by/at, kemas kini indeks Scout; notifikasi kejayaan memaparkan rujukan penuh.
- Selepas failkan: dialog "Mahu edarkan minit sekarang?" [Ya → borang minit 9.C.5 | Tidak].

**9.C.4 Rekod** (`RecordResource`):
- Senarai: penapis (jenis, fail, julat tarikh, sensitiviti, status, sumber); lajur: rujukan penuh, tajuk, jenis (badge), fail, tarikh, sensitiviti (badge hijau/kuning/merah), status.
- **Halaman lihat — tab**: **Pratonton** (iframe PDF signed URL 5 min / `SecureFileController`; imej terus; sulit → log akses ditulis pada mount) · **Maklumat** (infolist core + JSONB berlabel dari config) · **Teks OCR** (monospace, boleh salin; butang "Cuba Semula OCR" jika gagal) · **Minit** (bebenang + borang baharu) · **Kelulusan** (sejarah + status) · **Lampiran & Versi** (media; rantai supersede dua hala) · **Audit** (aktiviti rekod ini).
- Tindakan header: **Edarkan Minit** · **Mohon Kelulusan** · Muat Turun (asal / boleh-cari; log jika sulit) · **Ganti Versi** (muat naik → rekod baharu, lama status=`diganti`, pautan dua hala) · **Jana Kod QR** · **Pindah Fail** (kerani/admin_masjid; sebab wajib; dilog) · **Legal Hold** toggle (ikut kebenaran §6.2; sebab wajib).

**9.C.5 Minit**:
- Borang: Penerima Tindakan (multi-pilih ahli masjid aktif), Penerima Makluman/s.k. (multi), Catatan/Arahan* (textarea), Keutamaan (Biasa/Segera/Kritikal), Tarikh Akhir (lalai +7/+3/+1 hari ikut keutamaan — `config/diwan.php`).
- Hantar → simpan + recipients → notifikasi §14 setiap penerima.
- Bebenang: kad setiap minit (pengirim, masa, keutamaan, catatan, penerima + status baca ✓✓). Penerima tindakan: **Balas & Edarkan** (minit anak; boleh route ke orang lain) · **Tanda Selesai** (semua penerima tindakan selesai → minit selesai → notifikasi pengirim).
- **Minit Saya**: tab *Perlu Tindakan / Makluman / Saya Hantar / Selesai*; klik → terus tab Minit rekod.

**9.C.6 Kod QR**: tindakan pada rekod & fail → PDF label (dompdf, grid 2×5 A4; setiap label: QR ke `{APP_URL}/r/{ulid}` + rujukan penuh + tajuk pendek). Tampal pada dokumen/fail fizikal = jambatan hibrid.

**9.C.7 Kelulusan**: senarai menunggu (pemohon, rekod, nota, tarikh). **Lulus / Tolak** → modal: nota (wajib jika tolak) + **pengesahan kata laluan semula** → decided_at + IP → notifikasi pemohon → audit. Halaman rekod papar lencana "✔ Diluluskan oleh {nama} pada {tarikh}".

**9.C.8 Carian** (halaman Livewire): kotak besar → `SearchService` (Meilisearch) dengan **mosque_id + sensitiviti dipaksa server-side** (§13); penapis: jenis, fail, julat tarikh; hasil: tajuk + rujukan + **snippet highlight** `_formatted`; klik → rekod. Placeholder: "Cari tajuk, no. rujukan, atau kandungan surat…".

**9.C.9 Laporan**: (a) carta bar rekod ikut jenis (12 bulan); (b) carta garis rekod difailkan/bulan; (c) jadual prestasi SLA minit per ahli (% tepat masa, purata hari); (d) jadual "Akan Cukup Tempoh Retensi 12 Bulan"; (e) Eksport CSV senarai rekod (ikut penapis + sensitiviti pengeksport).

**9.C.10 Penggunaan & Storan** (`usage.view`): tolok guna/kuota efektif; pecahan ikut jenis rekod & top-10 fail; carta pertumbuhan 12 bulan; senarai add-on aktif + luput; sejarah pesanan + invois PDF; butang **"Tambah Storan"** (`storage.order`) → wizard: bilangan blok (X × {block_gb}GB @ RM{kadar}/tahun) → ringkasan → **Jana Pesanan** → Invois PDF (no. siri, butiran bank, arahan bayar) → status "Menunggu pengesahan bayaran".

**9.C.11 Ahli & Peranan** (`users.manage`): senarai ahli + peranan (dropdown §6.1) + lajur masjid lalai; **Jemput Ahli** (e-mel — wujud global: attach pivot; baharu: cipta + hantar magic link + WA ringkas jika ada nombor); **Keluarkan** (detach pivot); sekatan §6.4 dikuatkuasakan.

**9.C.12 Retensi & Pegangan** (`retention.manage`): jadual peraturan EFEKTIF (lalai platform ⊕ override masjid, sumber dilabel) + tambah/ubah override (amaran jika menukar jenis `kekal` kepada `auto_padam`); senarai "Akan Luput ≤12 Bulan" dengan toggle **Legal Hold** per rekod (sebab wajib) + butang **Eksport ZIP** skop senarai; banner status `auto_disposal_enabled`.

**9.C.13 Pentadbiran lain**: **Klasifikasi Fail** (urus pokok KF masjid: tambah/edit/nyahaktif; kod dikunci selepas ada fail) · **Pelupusan** (wizard manual §10.G; amaran §16.2 kekal terpapar) · **Log Audit** (penapis pengguna/model/tarikh) · **Log Akses Sulit** (audit, pengerusi, admin_masjid; lajur is_superadmin).

**9.C.14 Tetapan Masjid** (`mosque.settings`): profil masjid (kod baca-sahaja selepas ada fail); **Wakil Perlindungan Data** (nama/tel/e-mel); **WhatsApp Masjid** — integrasi tenant/API key encrypted + pairing QR terus di SPDM, kata kunci intake (lalai `spdm`) dan toggle; **E-mel Pengimbas** — alamat alias unik dijana daripada akaun IMAP platform, toggle, kata kunci dan senarai e-mel pengirim dibenarkan per tenant; Runbook Insiden (teks §15.6 versi pengawal).

**9.C.15 Profil** (menu pengguna): toggle 3 saluran notifikasi; nombor WhatsApp (baca sahaja — admin ubah); butang **"Sambung Telegram"** (deep-link §11.2); **"Hantar Notifikasi Ujian"** (semua saluran aktif; keputusan dipapar).

---

## §10. ALIRAN KERJA END-TO-END (langkah demi langkah)

### Aliran A — Surat masuk fizikal (paling kerap)
1. Surat tiba di pejabat masjid; kerani cop tarikh terima (amalan fizikal kekal).
2. Kerani imbas → mesin e-mel PDF ke **`scan.diwan+{slug}@gmail.com`** (scan-to-email).
3. ≤60 saat: `FetchMailJob` (§11.3) tarik → route ikut slug → `records` peti_masuk (source `emel`), lampiran ke COS prefix tenant, sha256, `ProcessOcrJob`.
4. `InboxNewItemNotification` → pemegang `inbox.view` masjid itu.
5. Kerani buka Peti Masuk (teks OCR mungkin sudah siap — membantu memahami kandungan).
6. **Klasifikasikan** → wizard: jenis `surat_menyurat`, direction `masuk`, their_ref dari surat, pilih fail (cth `MAM.900-1/1`) → **Sahkan & Failkan** → rujukan `MAM.900-1/1(23)`.
7. Dialog susulan → **Ya, edarkan minit** → penerima tindakan: Pengerusi; catatan "Untuk perhatian dan arahan tuan"; Biasa (+7 hari).
8. Pengerusi terima WhatsApp → klik pautan → log masuk (magic link jika perlu) → terus tab Minit.
9. Pengerusi baca (tab Pratonton) → **Balas & Edarkan** → SU: "Sila sediakan jawapan sebelum Jumaat."
10. SU dinotifikasi → buat kerja → **Tanda Selesai** dengan catatan.
11. Semua penerima tindakan selesai → minit selesai → kerani (pengirim asal) dinotifikasi.
12. Kerani cetak label QR → tampal pada surat fizikal → simpan dalam fail fizikal bernombor sama. Hibrid selaras.

### Aliran B — Dokumen masuk melalui WhatsApp (nombor masjid sendiri + kata kunci)
1. ✋(Sekali, semasa onboarding) admin_masjid daftar nombor WA masjid di gateway wassap.wehdah.my (imbas QR) dan isi `wa_session_id` dalam Tetapan Masjid.
2. AJK hantar ke NOMBOR MASJID: taip kata kunci (lalai `spdm`) kemudian hantar dokumen — ATAU dokumen dahulu kemudian kata kunci (≤10 minit) — ATAU dokumen berkapsyen `spdm`.
3. Gateway memadankan kata kunci (logik sisi-gateway §11.1) dan POST HANYA dokumen layak ke webhook Diwan bersama `session` + HMAC.
4. Diwan: HMAC → masjid ikut sesi → aktif + intake + kuota → penghantar mesti AHLI masjid itu (bukan ahli → balasan tolak sopan, TIADA rekod) → media ke COS, `records` peti_masuk, OCR → ack: "✅ Diterima untuk *{nama masjid}*. Rujukan #…".
5. Mesej WhatsApp masjid yang TIDAK melibatkan kata kunci kekal privasi masjid — langsung tidak dihantar ke Diwan.
6. Sambung seperti Aliran A langkah 5.

### Aliran C — Muat naik manual
Kerani/SU → Peti Masuk → "＋ Muat Naik Dokumen" → setiap fail jadi item → klasifikasi biasa. Pintasan: pemegang `records.create` boleh "＋ Rekod Baharu" dari halaman Rekod — wizard sama bermula langkah 1 dengan fail dimuat naik.

### Aliran D — Surat keluar + e-Kelulusan + penomboran automatik
1. SU sedia draf (Word/PDF di komputer sendiri — MVP tiada editor dalam talian).
2. Rekod Baharu → `surat_menyurat` keluar → muat naik draf → failkan → sistem cadang `our_ref` = rujukan penuh (cth `MAM.100-4/2(31)`) — SU salin ke dokumen.
3. **Mohon Kelulusan** → approver Pengerusi + nota.
4. Pengerusi dinotifikasi → semak → **Lulus** (sah kata laluan) / **Tolak** (nota; SU baiki → **Ganti Versi** → mohon semula).
5. Lulus → cetak → tandatangan basah → pos/serah. Salinan bertandatangan diimbas → **Ganti Versi** (versi akhir = salinan ditandatangani).
6. Jejak lengkap: draf → kelulusan (siapa/bila/IP) → versi akhir.

### Aliran E — SLA minit & peringatan
`SendMinitReminders` harian 08:00: (a) due esok & belum selesai → peringatan; (b) lewat → peringatan harian + tanda 🔴 Lewat pada senarai & laporan. Tiada eskalasi automatik (keputusan sedar; Fasa 2).

### Aliran F — Tutup fail / jilid baharu
`enclosure_count` capai **100** → banner "amalan registri: tutup jilid & buka jilid baharu" + butang **Buka Jld. Baharu** → fail baharu volume+1 (` Jld.{n}`), lama status `tutup` (sebab "Jilid penuh"). Failkan seterusnya hanya ke jilid terbuka. Tutup manual boleh (sebab wajib). Fail tutup = baca sahaja.

### Aliran G — Pelupusan MANUAL (dua kelulusan; untuk pelupusan awal tempoh atau masjid auto_disposal=false)
1. Kerani/admin_masjid → Pelupusan → **Sedia Senarai Semakan** → sistem tapis rekod melepasi peraturan (action `semak`/`auto_padam` yang due) → pilih satu-satu → batch `menunggu_kelulusan`. Amaran §16.2 terpapar kekal.
2. Pengerusi (`disposal.approve`) semak → **Lulus** (kata laluan) → status `lulus`.
3. admin_masjid/superadmin (`disposal.execute`) → **Laksana**: setiap item — snapshot penuh → padam SEMUA objek COS rekod **termasuk semua versi** → media rows dipadam → status `dilupus`, ocr_text=NULL, keluar indeks → **Sijil Pelupusan PDF** → notifikasi.
4. Halaman rekod jadi "batu nisan": metadata + "Dilupuskan pada … Batch #… — Sijil".

### Aliran H — Onboarding & akses pengguna
admin_masjid jemput ahli (§9.C.11) → e-mel magic link + WA ringkas "Anda didaftarkan dalam Diwan {masjid}. Semak e-mel untuk pautan log masuk." Keluar AJK: detach pivot / nyahaktif global → sesi tamat, notifikasi henti, sejarah kekal.

### Aliran I — Pendaftaran & onboarding MASJID
1. Wakil masjid isi `/daftar` (2 pengakuan wajib).
2. Superadmin dinotifikasi → semak (✋kod akronim sesuai & tak berulang) → **Lulus**.
3. `MosqueProvisioningService` (transaksi): status→aktif; salin KF templat §7; rekod `retention_ack_*`; aktifkan admin_masjid; hantar magic link + WA alu-aluan.
4. admin_masjid log masuk → checklist onboarding papan pemuka: Tetapan Masjid → jemput AJK & tetapkan peranan (Pengerusi, K.Imam, Bendahari…) → set e-mel pengimbas → ✋daftar nombor WA masjid di gateway (imbas QR) + isi `wa_session_id` → uji hantar dokumen dengan kata kunci `spdm`.
5. Masjid beroperasi.

### Aliran J — Pembelian add-on storan (MVP invois manual; gerbang automatik Fasa 2)
1. admin_masjid/bendahari → Penggunaan & Storan → **Tambah Storan** → pilih blok → **Jana Pesanan** → Invois PDF (`INV-2026-0007`) + arahan bayar; status `menunggu_bayaran`; superadmin dinotifikasi.
2. ✋Masjid bayar (pindahan/FPX manual); hantar bukti kepada platform (luar sistem).
3. Superadmin → Pesanan Storan → **Tandakan Dibayar** (sah kata laluan) → addon aktif (mula hari ini, luput +period_months) → kuota efektif naik serta-merta → notifikasi "✅ Storan tambahan {X}GB aktif sehingga {tarikh}".
4. `ExpireAddonsJob`: T-30/T-7 "akan luput — perbaharui"; luput → kuota turun; guna>kuota → sekat-tulis + notifikasi (data TIDAK disentuh).

### Aliran K — Kuota harian
Melebihi kuota efektif → disekat di pintu (§5.14) dengan mesej jelas + pautan Tambah Storan. Ambang 80/90/100% → notifikasi admin_masjid + superadmin.

### Aliran L — Retensi automatik 7 tahun
1. `RunRetentionNotices` 07:00: segarkan `retention_due_at` (peraturan efektif §5.11); rekod due dalam 90/30/7 hari belum dinotis peringkat itu → `RetentionNoticeNotification` **admin_masjid + superadmin** + tanda `retention_notified`. Notis 30 & 7 sertakan pautan Eksport ZIP.
2. Masjid bertindak: Eksport ZIP / Legal Hold / biarkan.
3. `RunRetentionExecute` 07:30: due ≤ hari ini DAN tiada hold DAN peraturan=`auto_padam` DAN masjid `auto_disposal_enabled` DAN status aktif DAN **notis t30 & t7 sudah dihantar** → batch `kind=auto` per masjid → snapshot → padam blob semua versi → `dilupus` → sijil "SIJIL PELUPUSAN AUTOMATIK" → `AutoDisposalDoneNotification` ("{n} rekod dilupuskan automatik — sijil dalam sistem").
4. Metadata kekal. Rekod kekal/hold/toggle-off TIDAK disentuh.

### Aliran M — Gantung & tutup akaun masjid
- **Gantung** (superadmin): `EnsureMosqueActive` sekat panel ("Akaun digantung — hubungi platform"); notis retensi TERUS dihantar; **auto-padam DIJEDA** (elak padam semasa masjid tak boleh eksport); superadmin masih boleh masuk panel.
- **Tutup**: superadmin tanda → sistem jana Eksport ZIP PENUH akaun + e-mel pautan (14 hari) → selepas 30 hari: softdelete mosque, padam blob prefix tenant, metadata & log kekal. Dua pengesahan + taip slug.

---
## §11. INTEGRASI (kontrak tepat)

### 11.1 Gateway WhatsApp whatsmeow (`wassap.wehdah.my`) — SESI PER MASJID + KATA KUNCI

**Model:** setiap masjid menggunakan **nombor WhatsApp SENDIRI** sebagai satu sesi whatsmeow pada gateway; provisioning tenant, API key dan pairing QR berlaku terus di SPDM tanpa berkongsi kata laluan. **Penghalaan tenant = ikut SESI** dan nombor pivot keahlian tenant. Kemasukan dokumen menggunakan aliran dua langkah: ahli hantar kata kunci (lalai `spdm`) untuk mengaktifkan slot sesi+nombor selama 10 minit, kemudian hantar satu dokumen. Caption yang mengandungi kata kunci masih disokong sebagai aliran satu langkah.

⚠️ **Kenyataan jujur (kekal):** gateway ialah kod pengguna sendiri; kontrak di bawah ialah apa yang **Diwan laksanakan**, diasingkan sepenuhnya dalam `App\Services\WhatsAppGateway` (`WHATSAPP_DRIVER=gateway|log`; semua ujian automatik guna `log`). Logik sisi-gateway ditanda ✋ — spesifikasi diberi supaya kedua-dua pihak sepadan; jika API sebenar berbeza, HANYA fail adapter itu diubah.

**Keluar (Diwan → gateway) — notifikasi dihantar DARI nombor masjid berkenaan:**
```
POST {WHATSAPP_GATEWAY_URL}/send
Authorization: Bearer {WHATSAPP_GATEWAY_TOKEN}
Content-Type: application/json
{"session": "{wa_session_id}", "to": "60123456789", "message": "teks penuh"}
Jangkaan: HTTP 200 = berjaya; selain itu = gagal (retry §14). Timeout klien 8 saat.
```
Masjid tanpa `wa_session_id` → saluran WA dilangkau (§14).

**✋ Logik sisi-gateway (spesifikasi untuk kod Go pengguna) — padanan kata kunci per sesi:**
- Mesej teks == kata kunci masjid (tak sensitif huruf, trim) → "arm" penghantar itu **10 minit** + balas "📥 Diwan sedia menerima dokumen (10 minit)."
- Media berkapsyen mengandungi kata kunci → layak serta-merta.
- Media tanpa kata kunci → pegang rujukan **10 minit**; kata kunci tiba daripada penghantar sama dalam tempoh → SEMUA media tertahan itu layak.
- HANYA item layak di-POST ke Diwan (satu POST per media). Trafik WhatsApp masjid yang lain TIDAK dihantar — privasi perbualan masjid terpelihara.

**Masuk (gateway → Diwan) — hanya dokumen layak:**
```
POST {APP_URL}/api/webhooks/whatsapp
X-Diwan-Signature: hex( HMAC-SHA256( rawBody, WHATSAPP_WEBHOOK_SECRET ) )
{
  "session": "mam",
  "from": "60198765432",
  "type": "image" | "document",
  "media_base64": "…",              // ≤25MB selepas dekod
  "media_mime": "image/jpeg",
  "filename": "surat.jpg",
  "caption": "spdm",                // pilihan
  "message_id": "ABCD1234",
  "timestamp": 1720252800
}
```
**Pemprosesan `WhatsAppWebhookController`:** (1) sahkan HMAC `X-Signature` atas raw body. (2) Idempotensi berskop sesi 24 jam. (3) `session_id` mesti memetakan integrasi tenant aktif/connected. (4) Semak masjid aktif, toggle dan kuota. (5) Penghantar mesti sepadan dengan `mosque_user.phone_wa` tenant itu. (6) Mesej teks tepat kata kunci mengaktifkan cache `sesi+nombor` 10 minit; media tanpa slot/caption kata kunci ditolak dengan arahan. (7) Media sah dan bukan pendua → COS prefix tenant, `records` peti_masuk, queue OCR dan ack; slot dipadam selepas berjaya. (8) Group dan echo sendiri diabaikan; kerja berat kekal pada queue.

**Simulator (WAJIB dibina):** `php artisan diwan:simulate-whatsapp {session} {phone} {path-fail}` — bina payload sebenar + HMAC sah → POST ke aplikasi sendiri. Digunakan §18.

**Pemantauan:** `PingGateway` setiap 5 minit (cuba `GET {URL}/health`, fallback `GET {URL}/`) → `platform_settings.gateway_status` + banner + `GatewayDownNotification` (e-mel+Telegram superadmin). Diwan turut merekod kejayaan hantar terakhir per masjid (`notification_logs`) — dipaparkan di Tetapan Masjid sebagai penunjuk sesi tidak langsung. ⚠️ Status sesi tepat per masjid memerlukan endpoint gateway (cth `GET /sessions`) — pilihan ✋; jika wujud, paparkan di Tetapan Masjid & panel superadmin.

### 11.2 Telegram
1. ✋Cipta bot melalui @BotFather → token ke `.env`.
2. `diwan:telegram-set-webhook` daftar `{APP_URL}/api/webhooks/telegram/{TELEGRAM_WEBHOOK_SECRET}`.
3. Sambung akaun: butang Profil → `https://t.me/{bot}?start={token}` di mana token = `Crypt::encrypt(['user_id'=>…,'exp'=>now()+30min])`. Webhook terima `/start {token}` → dekrip & sah → simpan `telegram_chat_id` → balas "✅ Telegram anda kini bersambung dengan Diwan."
4. Mesej lain ke bot → "Bot ini untuk notifikasi Diwan sahaja."

### 11.3 Ingest e-mel pengimbas — IMAP + plus-addressing per masjid
- Pakej `webklex/laravel-imap`; satu peti Gmail; setiap masjid guna `scan.diwan+{slug}@gmail.com` (Gmail hantar semua +tag ke peti sama — ciri standard).
- `FetchMailJob` dijadualkan **setiap minit** (`withoutOverlapping`; guard `IMAP_ENABLED`): sambung IMAP → INBOX UNSEEN → sahkan alamat `To`/`Delivered-To` benar-benar alias akaun IMAP platform + `{slug}` → masjid aktif dan intake e-mel dihidupkan → pengirim berada dalam allowlist tenant → subjek/isi mengandungi kata kunci tenant → kuota OK → lampiran MIME dibenarkan. SHA-256 didedup **dalam masjid itu sahaja**; baharu masuk COS prefix tenant + Peti Masuk + queue OCR + notifikasi. Semua kes ditolak ditanda SEEN tanpa mencipta rekod.
- ✋Gmail khusus + 2FA + **App Password** → `.env`. ⚠️ Bergantung polisi Google semasa; mana-mana IMAP dengan plus-addressing boleh ganti melalui `.env`. Cloudflare Email Worker = Fasa 2.
- Ralat sambungan IMAP 3 kali berturut → e-mel superadmin sekali sehari (elak spam).

### 11.4 Penghantaran fail kepada pengguna
Semua pratonton/muat turun: `Storage::disk(config('diwan.storage_disk'))->temporaryUrl($path, now()->addMinutes(5))`. Fallback terbina: `GET /secure-file/{media}` — auth + policy + **pengesahan tenant** (media→record→mosque vs keahlian pengguna; superadmin dikecualikan TETAPI dilog) + log sulit.

---

## §12. PIPELINE OCR (✅ DISAHKAN MELALUI UJIAN SANDBOX 6 JULAI 2026)

**Bukti ujian sebenar:** imej surat BM dijana (kepala surat, "Rujukan Kami: MAM.500-1/2/3 (15)", tajuk "PERMOHONAN MENGGUNAKAN DEWAN SERBAGUNA", 2 ayat kandungan) → `img2pdf` → `ocrmypdf -l msa+eng --sidecar` dengan **tesseract 5.3.4 + pek `tesseract-ocr-msa` (repo apt rasmi)** → teks diekstrak **100% tepat termasuk format nombor rujukan**, output **PDF/A-2b** (format pengarkiban ISO). Arahan & versi dalam Dockerfile §4.4 = tepat seperti diuji.

**`ProcessOcrJob`** (queue `ocr`, `$timeout=300`, `$tries=2`, backoff 60s; payload membawa `record_id` — mosque diperoleh dari rekod, BUKAN konteks global):
1. Set `ocr_status=dalam_proses`.
2. Muat turun media `original` → `storage/app/tmp/{ulid}/`.
3. MIME imej → `img2pdf {in} -o {tmp}.pdf`; PDF → guna terus; docx/xlsx/pptx → **langkau** (`ocr_status=siap`, ocr_text kosong — indeks metadata sahaja; ⚠️ ekstrak teks Office = Fasa 2).
4. `ocrmypdf --skip-text -l {OCR_LANGS} --rotate-pages --deskew --sidecar {txt} --output-type pdfa {in.pdf} {out.pdf}` melalui `Symfony\Component\Process` (timeout 240).
5. Muat naik `out.pdf` → collection `derived` (COS prefix tenant). **Fail asal TIDAK diubah** — integriti arkib.
6. `ocr_text` = sidecar (potong 1,000,000 aksara) → `ocr_status=siap` → `$record->searchable()`.
7. Padam direktori tmp (blok `finally`). Gagal selepas retry → `ocr_status=gagal` (butang cuba semula §9.C.4).
Horizon: queue `ocr` maxProcesses **1** (OCR berat CPU).

⚠️ **Had jujur (papar dalam dokumentasi pengguna):** (a) tulisan tangan — tidak boleh diharap; (b) **Jawi tidak disokong** pek `msa` (Rumi sahaja); (c) imbasan gelap/condong menurunkan ketepatan walaupun `--deskew` membantu; (d) OCR = alat carian, BUKAN pengesahan kandungan — kerani tetap membaca dokumen.

---

## §13. CARIAN (Scout + Meilisearch)

**`Record::toSearchableArray()`** (`shouldBeSearchable()` = status difailkan/diganti sahaja):
```php
return [
  'id'=>$this->id, 'ulid'=>$this->ulid,
  'mosque_id'=>$this->mosque_id,
  'title'=>$this->title, 'our_ref'=>$this->our_ref, 'their_ref'=>$this->their_ref,
  'sender_name'=>$this->sender_name, 'sender_org'=>$this->sender_org, 'recipient_name'=>$this->recipient_name,
  'record_type'=>$this->record_type, 'file_no'=>$this->registryFile?->file_no,
  'registry_file_id'=>$this->registry_file_id,
  'sensitivity'=>$this->sensitivity->value, 'status'=>$this->status->value,
  'record_date'=>$this->record_date?->timestamp,
  'ocr_text'=>mb_substr($this->ocr_text ?? '', 0, 100_000),
];
```
**Tetapan indeks** (`diwan:sync-meili` — jalankan dalam deploy): `filterableAttributes=[mosque_id, sensitivity, record_type, status, registry_file_id, record_date]` (**mosque_id PERTAMA**), `sortableAttributes=[record_date]`, `searchableAttributes=[title,our_ref,their_ref,sender_name,sender_org,recipient_name,file_no,ocr_text]` (susunan=keutamaan), highlight pra/pasca-tag untuk snippet.

**Penguatkuasaan (KRITIKAL):** SEMUA carian melalui `SearchService::for(User $u, Mosque $tenant)` yang MEMAKSA `filter: mosque_id = {tenant->id} AND sensitivity IN [tahap dibenarkan §6.3]` pada peringkat Meilisearch — TIADA laluan query Meili lain di seluruh aplikasi (satu titik masuk; ujian §18). bendahari: `sulit` hanya bersama `registry_file_id IN [id fail klasifikasi 200/300 masjid itu]` (pra-kira, cache 5 min). Superadmin dalam panel tenant: filter mosque_id tenant semasa KEKAL. ⚠️ `file_access_grants` individu TIDAK dicerminkan dalam carian MVP (dokumen granted ditemui melalui halaman fail; Fasa 2 multi-index).

---

## §14. NOTIFIKASI (saluran, templat penuh BM, fallback)

**Prinsip PDPA:** mesej WA/Telegram TIDAK PERNAH mengandungi kandungan dokumen, nama pengirim luar sensitif, atau amaun — hanya jenis peristiwa + tajuk pendek selamat + pautan yang perlu log masuk.

**Saluran per pengguna:** `via()` = `['mail' jika notify_email] + ['whatsapp' jika notify_whatsapp && phone_wa] + ['telegram' jika notify_telegram && telegram_chat_id]`. **E-mel lalai ON untuk semua** — jaminan penyampaian.
Saluran WhatsApp menghantar MELALUI SESI MASJID berkaitan notifikasi (§11.1) — penerima menerima daripada nombor masjid sendiri; masjid tanpa `wa_session_id`/sesi terputus → WA dilangkau senyap (e-mel tetap sampai, dilog skip). Notifikasi peringkat PLATFORM (GatewayDown, NewRegistration, NewStorageOrder) → e-mel + Telegram sahaja.
**Kegagalan WhatsApp:** `SendWhatsAppJob` tries=3 backoff [30,120]; gagal muktamad → `notification_logs` failed (e-mel sudah dihantar selari — tiada mesej hilang).

**Templat (guna TEPAT; ganti {…}; e-mel = kandungan sama + butang, subjek = baris pertama tanpa emoji):**
```
[MinitRouted → penerima tindakan]
📄 *Diwan · {KOD}*
Minit baharu untuk tindakan anda.
Daripada: {nama_pengirim}
Perkara: {tajuk_60_aksara}
Keutamaan: {Biasa|Segera|Kritikal} | Tindakan sebelum: {d/m/Y}
Log masuk: {APP_URL}/r/{ulid}

[MinitRouted → makluman]  (baris 2: "Minit baharu untuk makluman (s.k.) anda.")

[InboxNewItem → kerani/pemegang inbox.view]
📥 *Diwan · {KOD}*
{n} dokumen baharu dalam Peti Masuk ({sumber}).
Sila klasifikasikan: {APP_URL}/app/{slug}

[MinitReminder]
⏰ *Diwan · {KOD}*
Peringatan: tindakan minit "{tajuk_60}" perlu diselesaikan {esok|LEWAT {n} hari}.
{APP_URL}/r/{ulid}

[ApprovalRequested]
✍️ *Diwan · {KOD}*
Permohonan kelulusan daripada {nama}: "{tajuk_60}".
{APP_URL}/r/{ulid}

[ApprovalDecided]
{✅ Diluluskan | ❌ Ditolak} — "{tajuk_60}" oleh {nama_approver}.
{APP_URL}/r/{ulid}

[GatewayDown → superadmin; e-mel+Telegram SAHAJA]
⚠️ Gateway WhatsApp gagal dihubungi sejak {masa}. Notifikasi WA dijeda; e-mel/Telegram beroperasi.

[QuotaThreshold → admin_masjid + superadmin]
📦 *Diwan · {KOD}* — Storan mencapai {80|90|100}% ({guna} / {kuota} GB).
{jika 100%: "Muat naik baharu DISEKAT sehingga storan ditambah."}
Tambah storan: {APP_URL}/app/{slug}

[AddonExpiring T-30/T-7 / AddonExpired]
📦 *Diwan · {KOD}* — Add-on storan {X}GB {akan luput pada {d/m/Y}|telah luput}. Perbaharui: {pautan}

[RetentionNotice T-90/T-30/T-7 → admin_masjid + superadmin]
🗄️ *Diwan · {KOD}* — {n} rekod akan mencapai tempoh simpanan {tahun} tahun dalam {90|30|7} hari
dan AKAN DIPADAM AUTOMATIK. Sila eksport untuk sandaran luar atau tetapkan pegangan:
{APP_URL}/app/{slug}

[AutoDisposalDone → admin_masjid + superadmin]
🗄️ *Diwan · {KOD}* — {n} rekod telah dilupuskan automatik (cukup tempoh {tahun} tahun).
Sijil pelupusan tersedia dalam sistem. Metadata rekod kekal tersimpan.

[ExportReady] 📦 *Diwan · {KOD}* — Eksport ZIP anda sedia (pautan luput 14 hari): {pautan-log-masuk}

[RegistrationApproved → admin_masjid baharu]
🕌 Akaun *{nama masjid}* diluluskan! Log masuk: {pautan magic}

[NewRegistration / NewStorageOrder → superadmin]  (satu baris + pautan /admin)
```

---
## §15. KESELAMATAN & KAWALAN PDPA

### 15.1 Autentikasi & sesi
- Magic link (§9.C.1): token 64 aksara rawak (`Str::random(64)`), simpan **hash SHA-256 sahaja**, luput 15 minit, sekali guna, IP direkod. Kata laluan fallback: bcrypt bawaan.
- Sesi: Redis, hayat **720 minit (12 jam)**, `SESSION_SECURE_COOKIE=true`, `same_site=lax`.
- Middleware `EnsureUserIsActive` (kedua-dua panel): `is_active=false` → log keluar paksa + "Akaun dinyahaktifkan. Hubungi pentadbir." Middleware `EnsureMosqueActive` (panel tenant): status≠aktif → halaman gantung.
- Rate limit: `throttle:5,1` POST log masuk & magic-link; `throttle:60,1` webhook; `throttle:3,60` /daftar.
- MFA/2FA: Fasa 2 (keputusan sedar — magic link e-mel sudah faktor milikan).

### 15.2 PENGASINGAN TENANT (kritikal — punca kegagalan #1 sistem multi-tenant)
- Trait `BelongsToMosque` pada SEMUA model berdata-tenant: global scope `where mosque_id = Filament::getTenant()->id` bila konteks tenant wujud + auto-isi `mosque_id` semasa cipta. Middleware `ApplyTenantScopes` (corak rasmi Filament) mendaftarkannya untuk semua request panel tenant termasuk AJAX Livewire (`->persistent()`).
- Tutup 3 jurang rasmi Filament (§0.4): SEMUA `->relationship()` dalam Select/CheckboxList/Repeater diberi `modifyQueryUsing(fn($q)=>$q->whereBelongsTo(Filament::getTenant()))`; validasi unik guna `scopedUnique()/scopedExists()`; SEMUA query dalam Services/Jobs/Widgets/Commands menerima `$mosque` eksplisit — **job queue membawa `mosque_id`/`record_id` dalam payload, JANGAN bergantung konteks global dalam queue**.
- Laluan bukan-panel (`/r/{ulid}`, `/secure-file/{media}`, webhook, eksport, invois): pengesahan keahlian tenant manual + 404/403.
- Meilisearch: SATU titik masuk `SearchService` dengan filter mosque_id dipaksa (§13).
- **`TenantIsolationTest` WAJIB** (§18) — tanpa ujian ini lulus, sistem dianggap GAGAL keseluruhan.

### 15.3 Peminimuman data (prinsip PDPA)
- **LARANGAN KERAS: tiada medan No. Kad Pengenalan** dalam borang/metadata MVP (§0.3, §8). No. KP dalam dokumen imbasan berada dalam blob COS terkawal — diterima; sistem tidak MENSTRUKTURKAN data itu. ⚠️ Jika Fasa 2 perlu (cth khairat): Eloquent **encrypted cast** + JANGAN indeks ke Meilisearch.
- Notifikasi tanpa kandungan sensitif (§14). `laravel.log` TIDAK log kandungan `ocr_text`/isi dokumen — hanya id/ulid.

### 15.4 Jejak audit
- `spatie/laravel-activitylog` pada: Record, RegistryFile, Minit, Approval, User, RetentionRule, DisposalBatch, ClassificationNode, FileAccessGrant, **Mosque, MosqueUser (tukar peranan!), StorageOrder, StorageAddon, PlatformSetting** — created/updated/deleted + properties + causer. IP melalui `->withProperties(['ip'=>request()->ip()])` pada tindakan kritikal (kelulusan, pelupusan, pindah fail, grant, kuota, legal_hold, tandakan-dibayar).
- `sensitive_access_logs` (+mosque_id, +is_superadmin): DITULIS setiap paparan/muat turun rekod sulit — dalam `ViewRecord::mount()` & `SecureFileController`/penjana signed URL (BUKAN dalam policy — policy dipanggil berulang).

### 15.5 Retensi log
Scheduler bulanan: padam `activity_log`, `sensitive_access_logs`, `notification_logs` **> 24 bulan**. (Log ≠ rekod; snapshot pelupusan TIDAK dipangkas.)

### 15.6 Runbook insiden pelanggaran data (PDPA 2024 — 72 JAM; dua peringkat pemproses/pengawal)
Teks statik dipaparkan di Tetapan kedua-dua panel (versi masing-masing):
1. **Kesan & bendung** (jam 0–4): rotate token gateway / kata laluan DB / kunci CAM di konsol. JANGAN rotate `APP_KEY` melulu (memusnahkan data encrypted).
2. **Siasat skop** (jam 4–24): Log Audit + Log Akses Sulit (tapis mosque_id) → senarai masjid, rekod & subjek terjejas; eksport CSV bukti.
3. **Platform maklum SERTA-MERTA setiap admin_masjid terjejas** — masjid ialah pengawal; kewajipan lapor Pesuruhjaya ≤72 jam dari kejadian berada di pihak mereka (jika significant harm — data sulit asnaf/kewangan hampir pasti ya). Platform sendiri menilai kewajipan lapor sebagai pemproses.
4. **Notis subjek data ≤7 hari** selepas notifikasi Pesuruhjaya (oleh pengawal).
5. Post-mortem difailkan sebagai rekod `laporan` dalam fail 100-3 masjid platform/terjejas.
Kontak: Wakil Perlindungan Data masjid (Tetapan Masjid) & DPO platform (Tetapan Platform); portal rasmi Jabatan Perlindungan Data Peribadi (pdp.gov.my).

### 15.7 Validasi muat naik
MIME dibenarkan (semak KANDUNGAN sebenar — `mimes:` + `mimetypes:`): `pdf, jpg, jpeg, png, webp, docx, xlsx, pptx`. Maks **25 MB**/fail (selaras php.ini). Webhook base64: semak saiz selepas dekod. Nama fail disanitasi (`Str::slug` nama, kekal sambungan); laluan COS guna ulid — tiada input pengguna dalam path. + Semakan kuota (§5.14). Antivirus: Fasa 2 (dinyatakan §19).

### 15.8 Rangkaian & rahsia
HTTPS sahaja (Caddy); `APP_DEBUG=false` produksi; header keselamatan nginx §4.4; webhook WA = HMAC-SHA256 wajib, Telegram = rahsia dalam path; semua webhook throttle. Rahsia hanya `.env` (bukan repo); `.env` termasuk dalam sandaran spatie (bucket peribadi SSE) — diterima pada skala ini.

### 15.9 Akaun
TIADA operasi padam pengguna di mana-mana UI — hanya `is_active` toggle (global) & detach pivot (keluar masjid). Sejarah tindakan kekal (§2.2).

---

## §16. ENJIN RETENSI & PELUPUSAN

### 16.1 Peraturan lalai PLATFORM (`RetentionRuleSeeder`, mosque_id=NULL; superadmin boleh ubah; masjid boleh override)

| Padanan | Tahun | action | Nota |
|---|---|---|---|
| minit_mesyuarat · perjanjian · sijil · laporan | NULL | **kekal** | Injap §2.2 — masjid boleh override ke `auto_padam` (amaran dipapar semasa menyimpan override) |
| classification_prefix `200` | 7 | **auto_padam** | Kewangan |
| rekod_kewangan · tender_sebutharga | 7 | **auto_padam** | |
| surat_menyurat · memo · emel · emel_muatnaik · borang · kertas_kerja · pekeliling · garis_panduan | 7 | **auto_padam** | Dasar produk: 7 tahun |
| poster · foto · jadual | 7 | **auto_padam** | |

Nilai tahun lalai global = `platform_settings.default_retention_years` (7). Action `semak` kekal wujud sebagai pilihan override — rekod padanan masuk senarai calon pelupusan MANUAL sahaja, tidak auto-dipadam.

### 16.2 Teks pengakuan onboarding (checkbox `/daftar` — WAJIB; simpan `retention_ack_*`)
> "Saya memahami dokumen dalam Diwan disimpan mengikut jadual retensi (lalai 7 tahun bagi kebanyakan jenis; minit mesyuarat/perjanjian/sijil/laporan kekal melainkan diubah). Selepas notifikasi 90/30/7 hari, rekod yang cukup tempoh **akan dipadam secara automatik dan tidak boleh dikembalikan**; metadata rekod kekal. Masjid bertanggungjawab membuat sandaran luar (alat Eksport disediakan) dan mematuhi mana-mana kewajipan Akta Arkib Negara 2003 / arahan Majlis Agama masing-masing sebelum pemadaman."

### 16.3 Enjin (`RetentionEngine` + 2 command §10.L)
`retention_due_at` = (`record_date` ?? `filed_at`) + tahun peraturan efektif; **NULL jika kekal atau legal_hold**. Disegarkan bila: rekod difailkan, peraturan masjid/platform berubah (job kira semula masjid berkenaan), hold ditoggle. Tangga notis t90/t30/t7 direkod per rekod dalam `retention_notified`; **execute HANYA jika t30 DAN t7 sudah dihantar** — jaminan mustahil padam tanpa amaran, walaupun rekod lama di-backfill (rekod backfill akan menerima notis dahulu, padam kemudian). Masjid digantung = execute dijeda. Batch `kind=auto` → snapshot → padam blob semua versi → batu nisan → sijil "SIJIL PELUPUSAN AUTOMATIK" → notifikasi ringkasan.

### 16.4 Eksport ZIP (`BuildExportZipJob`, queue `exports`, timeout 1800)
Skop: (a) "rekod akan luput ≤90 hari" (halaman Retensi); (b) satu fail penuh (halaman Fail); (c) seluruh akaun (tutup akaun §10.M). Kandungan: PDF asal + derived + lampiran (folder ikut file_no) + `metadata.csv` (semua medan) + `senarai.pdf` (dompdf). Jana dalam tmp (`ZipArchive`) → muat naik `tenants/{id}/exports/{ulid}.zip` → pautan (perlu log masuk) luput 14 hari (lifecycle §4.2) → `ExportReady`. Had praktikal ≈ kuota masjid.

### 16.5 Pelupusan MANUAL — Aliran G §10 (kekal untuk pelupusan awal tempoh & masjid `auto_disposal_enabled=false`). Amaran §16.2 kekal dipaparkan pada UI Pelupusan & Retensi.

---

## §17. PELAN PEMBINAAN BERURUTAN (Claude Code — ikut turutan)

**Prinsip dev/ujian tanpa akaun luar:** semua akses storan melalui `Storage::disk(config('diwan.storage_disk'))`; ujian `Storage::fake(config('diwan.storage_disk'))`; dev boleh `DIWAN_STORAGE_DISK=local`, `WHATSAPP_DRIVER=log`, `MAIL_MAILER=log`, `IMAP_ENABLED=false`. Binaan & ujian penuh berjalan TANPA COS/gateway/Gmail sebenar. ✋Langkah 0 (manusia, bukan blocker): DNS, COS, kunci CAM, Gmail App Password, BotFather.

1. Init projek + pakej TEPAT §3.3 → commit. Salin fail §4: docker-compose, Dockerfile, php.ini, nginx.conf, `.env.example`, `Caddyfile.example`, `scripts/rclone-offsite.sh`.
2. Config: `diwan.php` (storage_disk, ocr langs, whatsapp driver/url/token/secret/kata kunci lalai `spdm`, sla [biasa=7,segera=3,kritikal=1], kuota & retensi lalai, registration_open), `record_types.php` (§8 verbatim), `roles.php` (matriks §6.2 sebagai array), semua Enum §3.4.
3. Migrasi §5 ikut turutan + model + relationships + casts (metadata/source_meta/retention_notified array, enum, date) + `HasUlids` + `Searchable` + `InteractsWithMedia` + path generator tenant (§5.7) + helper peranan User (§6.0) + trait `BelongsToMosque`.
4. **Dua panel Filament**: `AdminPanelProvider` (/admin, gate `is_superadmin`, TANPA tenancy) & `AppPanelProvider` (/app, `->tenant(Mosque::class, slugAttribute: 'slug')`, middleware `ApplyTenantScopes`+`EnsureMosqueActive`+`EnsureUserIsActive`; pendaftaran tenant Filament DIMATIKAN — guna /daftar awam).
5. Seeders: `PlatformSettingSeeder`, `RetentionRuleSeeder` (§16.1), data `kf_template.php` (§7); `DemoSeeder` (local/testing SAHAJA): 2 masjid **MAM** & **MAN "Masjid An-Nur Demo"** (kedua-dua aktif, KF tersalin, `wa_session_id`='mam'/'man') + 1 superadmin + set pengguna semua peranan di MAM + 1 pengguna dwi-masjid + 3 fail MAM + 1 fail MAN + beberapa rekod termasuk rekod di-backdate ~7 tahun (ujian retensi). Kata laluan demo `password`.
6. `MosqueProvisioningService` + halaman awam `/`, `/daftar` (§9.A) + kelulusan/tolak superadmin (§10.I) + `RegistrationTest`.
7. Auth: magic link (`login_tokens`, `MagicLoginController`), fallback kata laluan, jemputan, rate limit; pendaratan ikut §9.A.
8. `RecordNumberingService` per-tenant (§5.15) + **`RecordNumberingTest`** (format; transaksi berturutan; enclosure berurutan bawah 2 transaksi serentak; skop masjid — nombor sama boleh wujud di 2 masjid).
9. Policies (guna `canIn(tenant, perm)`) + trait `ChecksSensitivity` + **`SensitivityPolicyTest`** + **`TenantIsolationTest`** (tulis AWAL — merah dahulu, hijau bila skop siap).
10. Panel masjid — resources teras: Rekod (senarai + ViewRecord semua tab §9.C.4), Fail (+jilid §10.F, QR, relation manager grants), Klasifikasi Fail; SEMUA Select berhubung diskop (§15.2).
11. Peti Masuk + wizard 3 langkah + muat naik berbilang + dedup skop-masjid + dialog susulan + **`InboxClassifyTest`** + **`DedupTest`**.
12. Minit penuh + Minit Saya + badge (§9.C.5).
13. Notifikasi: `WhatsAppGateway` (driver gateway|log) + `WhatsAppChannel` + `SendWhatsAppJob` retry + Telegram + mail + SEMUA templat §14 + `notification_logs` + `PingGateway` + banner + `GatewayDownNotification`.
14. Webhook WA sesi-per-masjid (§11.1 + Aliran B) + `diwan:simulate-whatsapp {session}` + **`WhatsAppWebhookTest`** (HMAC sah/gagal; sesi tak dikenali → 200+log; penghantar bukan ahli → balasan tolak tanpa rekod; imej ahli sah → peti masuk + ack sebut masjid; idempotensi; kuota penuh; intake off).
15. Ingest e-mel plus-addressing (§11.3) + ujian unit penghalaan slug & dedup.
16. `QuotaService` + `MediaObserver` + 3 pintu penguatkuasaan + ambang + `ReconcileStorageJob` + **`QuotaTest`** (kaunter ±; kuota efektif base+addon; sekat 100%; luput addon sekat tulis bukan baca).
17. OCR `ProcessOcrJob` (§12) + Horizon queue ocr maxProcesses 1 + **`OcrPipelineTest`** (jana imej teks BM dengan GD dalam ujian, jalankan job segerak, assert ocr_text mengandungi frasa — ocrmypdf+tesseract ADA dalam imej §4.4, ujian sebenar bukan mock).
18. Carian: `SearchService` (mosque_id+sensitiviti dipaksa) + halaman Livewire + `diwan:sync-meili` + ujian isolasi carian (boleh guna pemacu `collection` Scout dalam suite jika Meili tiada dalam CI — nyatakan mana digunakan).
19. Kelulusan (§9.C.7) + QR/dompdf + `/r/{ulid}` tenant-aware + Ganti Versi + Pindah Fail.
20. **Storan & bil**: halaman Penggunaan & Storan + wizard pesanan + invois PDF (`BillingService`) + panel superadmin Pesanan (Tandakan Dibayar → addon) + `ExpireAddonsJob`.
21. **Enjin retensi**: `RetentionEngine` + `RunRetentionNotices`/`RunRetentionExecute` + halaman Retensi & Pegangan + legal_hold + Eksport ZIP (`BuildExportZipJob`) + sijil auto + **`RetentionEngineTest`** (padam yang due+dinotis; TIDAK padam: kekal / hold / toggle-off / masjid digantung / tiada-t30t7).
22. Panel superadmin penuh §9.B (dashboard, Masjid+kuota+gantung+masuk-panel+tutup, pendaftaran, pesanan, retensi lalai, pengguna global, log, tetapan platform).
23. Pelupusan manual (Aliran G) + Laporan + widget papan pemuka + `SendMinitReminders` + Ahli & Peranan + Tetapan Masjid + Profil.
24. Ops: spatie-backup 02:30 + pangkas log bulanan + scheduler penuh **8 tugasan** (mail-poll 1min · reminders 08:00 · ping 5min · retensi 07:00 & 07:30 · reconcile 03:00 · expire-addons 06:00 · backup 02:30 · prune-log bulanan) + `diwan:make-superadmin {email}` + README BM (naik produksi, cipta superadmin, deploy: `migrate --force` + `diwan:sync-meili`) → jalankan §18 sehingga hijau.

---

## §18. SENARAI SEMAK UJIAN PENERIMAAN (semua mesti lulus)

**Automatik — 10 fail Pest (§3.4):**
1. `RecordNumberingTest` — format `MAM.500-1/2/3` & ` Jld.2`; enclosure berurutan tanpa langkau bawah keserentakan; nombor sama dibenarkan di masjid berbeza.
2. `SensitivityPolicyTest` — matriks §6.3: ajk tak nampak sulit; bendahari nampak sulit 200/300 sahaja; grant individu buka satu fail; audit baca sahaja.
3. **`TenantIsolationTest`** — pengguna MAM: rekod MAN → 404 (URL resource, `/r/{ulid}`, `/secure-file`); carian MAM tidak memulangkan dokumen MAN; Select fail dalam wizard tidak menyenaraikan fail MAN; pengguna dwi-masjid nampak setiap set HANYA dalam tenant masing-masing.
4. `WhatsAppWebhookTest` — 401 HMAC salah; sesi tak dikenali → 200 + log, tiada rekod; penghantar BUKAN ahli masjid sesi itu → balasan tolak, tiada rekod; imej ahli sah → peti masuk + media + ack sebut masjid; message_id ulang → tiada pendua; kuota penuh → tolak; intake off → tolak.
5. `DedupTest` — sha256 sama DISEKAT dalam masjid sama (muat naik ditanda / e-mel diskip), DIBENARKAN merentas masjid.
6. `InboxClassifyTest` — wizard: enclosure diperuntuk, status difailkan, waris sensitiviti max(), mosque_id auto-isi betul.
7. `OcrPipelineTest` — imej BM → `ocr_text` mengandungi frasa; media `derived` wujud; tmp dibersihkan.
8. `QuotaTest` — kaunter ±, kuota efektif base+addon, sekat tulis pada 100% (baca OK), luput addon.
9. `RetentionEngineTest` — 5 kes: padam (due+t30+t7); kekal TIDAK; hold TIDAK; toggle-off TIDAK; tiada-notis TIDAK; + snapshot & sijil wujud selepas padam.
10. `RegistrationTest` — daftar→menunggu→lulus→KF tersalin (bilangan nod = templat)→admin aktif→magic link dihantar.

**Manual (persekitaran compose dev; `WHATSAPP_DRIVER=log`; tenant MAM kecuali dinyatakan):**
11. `docker compose up -d --build` bersih; `/` & `/daftar` & `/app` papar BM.
12. Magic link penuh (mail log → salin URL → masuk); token luput 15 min ditolak; guna-semula ditolak; percubaan ke-6/minit → 429.
13. Akaun dinyahaktif tak boleh log masuk & tiada notifikasi; keluarkan-dari-masjid menghilangkan akses tenant itu sahaja.
14. Muat naik 3 fail sekali → 3 item peti masuk ≤10s, sha256 terisi; item duplikat bertanda ⚠.
15. `php artisan diwan:simulate-whatsapp mam 60110000001 tests/fixtures/surat.jpg` → item peti masuk MAM + payload ack (sebut nama masjid) dalam log.
16. Klasifikasi item → buka fail baharu dalam wizard → rujukan penuh betul dipaparkan.
17. OCR siap ≤2 minit pada imbasan sebenar; tab Teks OCR berisi; muat turun boleh-cari berfungsi.
18. Carian "DEWAN SERBAGUNA" → hasil + highlight; log masuk `ajk@demo` → rekod sulit TIADA dalam carian & senarai.
19. Buka rekod sulit sebagai kerani → baris Log Akses Sulit + IP.
20. Edarkan minit → e-mel (mail log) + payload WA; badge penerima naik; Balas & Edarkan berantai (Pengerusi→SU); semua selesai → pengirim asal dinotifikasi.
21. Minit due semalam → `diwan:send-minit-reminders` → peringatan LEWAT.
22. Kelulusan: Lulus dengan kata laluan → IP+masa direkod → lencana ✔; Tolak → Ganti Versi → mohon semula → Lulus; rantai versi kelihatan.
23. Jana QR → PDF label; `/r/{ulid}` tanpa sesi → paksa log masuk → mendarat di rekod; bukan-ahli → 404.
24. Set `enclosure_count=99` → failkan 1 → banner 100 + Buka Jld. Baharu; fail lama tolak failkan baharu.
25. Pindah Fail perlu sebab & muncul dalam Log Audit.
26. Laporan terisi; jadual "Akan Cukup Tempoh" papar rekod backdate; eksport CSV patuh sensitiviti pengeksport.
27. Pelupusan MANUAL hujung-ke-hujung: sedia (kerani) → laksana sebagai kerani DITOLAK → lulus (pengerusi) → laksana (admin_masjid) → objek storan fake dipadam, snapshot ada, sijil ada, batu nisan betul.
28. `php artisan backup:run` → arkib pada disk sandaran (fake/sebenar); kegagalan disengajakan → notifikasi superadmin.
29. **Latihan pemulihan** (§4.6) SEKALI ke atas compose segar — didokumen dalam README (tarikh + hasil).
30. `/daftar` → lulus superadmin → magic link admin masjid baharu → checklist onboarding muncul; KF penuh wujud.
31. Superadmin ubah Kuota Asas MAN 20→5GB (sebab) → tolok berubah; muat naik melebihi → disekat + pautan Tambah Storan.
32. Pesanan add-on 10GB → invois PDF bersiri → Tandakan Dibayar → kuota efektif +10GB serta-merta → notifikasi diterima.
33. Set `expires_at` addon semalam → `diwan:expire-addons` → kuota turun; guna>kuota: muat naik disekat, muat turun OK.
34. Rekod backdate 7 tahun → `diwan:run-retention-notices` ×3 (t90/t30/t7 — manipulasi `retention_notified` untuk simulasi) → `diwan:run-retention-execute` → dilupus + batu nisan + sijil auto + notifikasi; ulang dengan legal_hold=ON → TIDAK; jenis minit_mesyuarat → TIDAK; `auto_disposal_enabled=false` → TIDAK; masjid digantung → TIDAK.
35. Eksport ZIP "akan luput" → ZIP mengandungi PDF + metadata.csv + senarai.pdf; pautan tamat 14 hari.
36. E-mel ke `scan.diwan+man@...` (objek mel simulasi dalam ujian) → rekod masuk peti MAN, bukan MAM.
37. Simulasi sesi `man` oleh ahli MAN → masuk peti MAN; penghantar yang BUKAN ahli MAN (walaupun ahli MAM) ke sesi `man` → balasan tolak, tiada rekod.
38. Gantung MAN → ahli MAN dapat halaman gantung; auto-padam MAN dijeda; superadmin masih boleh Masuk Panel MAN.
39. Superadmin Masuk Panel MAM → buka rekod sulit → baris log akses sulit `is_superadmin=true`.
40. Ping gateway ke URL tidak sah → banner muncul + `GatewayDownNotification` (log).
41. `php artisan test` hijau; `horizon:status` aktif; `schedule:list` menyenaraikan 8 tugasan.

---

## §19. RISIKO & KELEMAHAN (penilaian jujur — bukan jualan)

| # | Risiko | Kesan | Mitigasi (dalam reka bentuk) |
|---|---|---|---|
| 1 | **whatsmeow tidak rasmi** — nombor boleh disekat Meta; sesi putus senyap | Notifikasi WA terhenti | E-mel SENTIASA selari (§14); ping 5-min + banner (§11.1); nombor khusus; adapter satu-fail memudahkan migrasi ke Cloud API rasmi jika perlu |
| 2 | **Satu server (SPOF)** | Downtime; TIADA kehilangan data (COS + sandaran 3 lapis) | Runbook pemulihan DIUJI (§18.29). Downtime diterima pada skala ini |
| 3 | **Had OCR** — tulisan tangan & Jawi tidak disokong; imbasan buruk | Sebahagian dokumen tak boleh dicari kandungan | Metadata + tajuk tetap dicari; had dinyatakan dalam UI (§12) |
| 4 | Ingest bergantung **Gmail App Password** (polisi Google boleh berubah); latensi poll 60s | Saluran pengimbas terganggu | Mana-mana IMAP ganti via `.env`; muat naik & WA kekal; CF Worker Fasa 2 |
| 5 | **Magic link bergantung deliverability e-mel** | Pengguna tak dapat masuk | SMTP transaksional bereputasi ✋; fallback kata laluan |
| 6 | 8GB RAM dikongsi Meili+PG+Redis+PHP+OCR semua tenant | Kelembapan semasa puncak | Swap wajib; OCR maxProcesses=1; pantau & naik taraf bila tenant bertambah |
| 7 | Filament 4 bukan terkini (v5 wujud) | Hutang naik taraf kecil | Pilihan sedar one-shot (§3.2); naik taraf terancang pasca-MVP |
| 8 | Tiada edit dalam talian MVP | Aliran muat turun→edit→Ganti Versi | Versioning elak kekeliruan; OnlyOffice Fasa 2 |
| 9 | Tiada antivirus muat naik MVP | Fail berniat jahat tersimpan | Whitelist MIME ketat; tiada pelaksanaan fail di server; pratonton PDF/imej sahaja; ClamAV Fasa 2 |
| 10 | Bottleneck manusia: kerani memproses peti masuk | Timbunan kerja | setiausaha & admin_masjid turut ada `inbox.classify`; laporan papar tunggakan |
| 11 | Penerimaan pengguna (AJK pelbagai umur) | Sistem tak dipakai | Magic link tanpa kata laluan; WA sebagai pintu harian; latihan ✋ |
| 12 | PDPA: skop pemakaian kepada masjid individu berbeza ikut status masing-masing | Ketidakpastian pengawal | Dibina ke piawaian penuh; setiap masjid isi Wakil PDPA sendiri; terma jelaskan peranan pengawal/pemproses |
| 13 | **Bug pengasingan tenant** — kegagalan paling teruk (PDPA + amanah) | Kebocoran silang masjid | Trait+middleware corak rasmi; 3 jurang Filament ditutup eksplisit; satu titik carian; **TenantIsolationTest wajib-lulus**; akses superadmin dilog |
| 14 | **Auto-padam tak boleh undo** — masjid abai notis, hilang dokumen | Kehilangan kekal | Tangga t90/t30/t7 DIPAKSA sebelum execute; eksport satu-klik; kekal/hold lalai untuk dokumen kritikal; jeda semasa gantung; metadata+sijil kekal; risiko baki dipindahkan melalui pengakuan §16.2 |
| 15 | Bil manual (tandakan-dibayar) — geseran & pertikaian | Kelewatan aktif; salah sahkan | Invois bersiri + sah kata laluan + log siapa sahkan; gerbang automatik Fasa 2 |
| 16 | Sesi WA per masjid boleh terputus (perlu imbas QR semula); risiko sekatan Meta kini per nombor masjid | Saluran WA masjid itu terhenti senyap | E-mel SENTIASA selari; status hantar terakhir dipapar di Tetapan Masjid; daftar semula QR = tindakan masjid ✋; operasi multi-sesi gateway = tanggungjawab pengguna |
| 17 | Kos COS membesar dengan tenant; harga jual mesti melebihi kos ✋ | Kerugian senyap | Superadmin tetapkan kadar; dashboard agregat pantau; kuota sekat pertumbuhan liar |
| 18 | Terma/DPA platform belum formal | Pendedahan undang-undang pemproses | Checkbox placeholder MVP; ✋ semakan peguam/MAIWP sebelum onboard masjid luar |

---

## §20. FASA 2 (JANGAN BINA SEKARANG — direkod supaya MVP tidak tersasar)

Gerbang bayaran automatik (Bayarcash/ToyyibPay/FPX) + resit auto · OnlyOffice/Collabora (≥8GB tambahan) · auto-klasifikasi & ringkasan AI atas ocr_text · ekstrak teks docx/xlsx/pptx · MFA/passkey · watermark dinamik pratonton sulit · ClamAV · file_access_grants dalam carian (multi-index Meili) · eskalasi SLA automatik · portal aduan awam per masjid · domain/subdomain per masjid · jenis rekod & templat KF tersuai per masjid · dashboard Majlis Agama baca-sahaja rentas masjid (dengan persetujuan masjid) · PWA mudah alih · pemindahan pemilikan akaun · Cloudflare Email Worker ingest.

---

## §21. SENARAI TINDAKAN MANUSIA (Azan — bukan kod; binaan & ujian TIDAK menunggu ini)

1. DNS `diwan.wehdah.my` → IP Lighthouse; pasang Docker + Caddy pada host; salin `Caddyfile`; sediakan swap §4.1.
2. COS: 2 bucket (§4.2) + versioning + SSE + lifecycle (backup 90 hari; `tenants/*/exports/` 14 hari) + sub-akaun CAM → kunci ke `.env`.
3. Gmail khusus pengimbas + 2FA + App Password → `.env`.
4. BotFather → token → `.env` → `php artisan diwan:telegram-set-webhook`.
5. **Gateway wassap.wehdah.my** (kod Go anda): (a) sokong `/send` dengan parameter `session` ikut §11.1; (b) implemen logik kata kunci per sesi (§11.1 — arm 10 minit / kapsyen / pegang-media) dan POST hanya dokumen layak ke webhook Diwan dengan HMAC; (c) sediakan UI pendaftaran sesi (imbas QR) untuk setiap masjid; (pilihan) endpoint `GET /sessions` untuk status sesi. Jika API sebenar berbeza → laraskan `WhatsAppGateway.php` sahaja.
6. `rclone config` pada host: remote COS-backup + Google Drive + wrapper `crypt` (kata laluan crypt disimpan SELAMAT LUAR server — tanpa ini sandaran offsite tidak boleh dipulihkan).
7. Tetapan Platform: harga RM/GB/tahun + saiz blok, butiran bank, DPO platform.
8. Teks Terma+DPA & Dasar Retensi: ✋ semakan undang-undang (MVP guna placeholder §16.2) — WAJIB sebelum onboard masjid luar MAM.
9. Luluskan MAM sebagai tenant pertama; nyahaktif/keluarkan data demo sebelum produksi.
10. Untuk masjid Majlis Agama yang tertakluk prosedur ANM: sahkan pendekatan retensi dengan Majlis; matikan `auto_disposal_enabled` masjid berkenaan jika perlu (§2.2).
11. Uji SEKALI kitaran bayaran manual dengan satu pesanan sebenar; jalankan latihan pemulihan sandaran (§18.29).
12. Pasca-stabil ±1 bulan: rancang naik taraf Laravel 13 / Filament 5 (§3.2) + pilih item Fasa 2 (cadangan pertama: gerbang bayaran).

— TAMAT SPESIFIKASI v2.0 —

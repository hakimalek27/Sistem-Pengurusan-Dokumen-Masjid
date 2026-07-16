# Handover Lengkap A-Z — Diwan SPDM

**Snapshot asal:** 13 Julai 2026, 17:26 MYT

**Addendum semasa:** 16 Julai 2026

**Tujuan:** sumber sambungan kerja yang lengkap, selamat dan tidak mengelirukan untuk pemilik, pembangun atau sesi AI seterusnya.

**Status ringkas:** kod SPDM telah dibina dan diuji, tetapi **SPDM belum dideploy ke production**. Gateway `wassap.wehdah.my` sudah live dan sihat, tetapi integrasi auto-provision belum boleh digunakan sehingga rahsia silang sistem dipasang pada kedua-dua aplikasi.

> Jangan tafsir “ujian hijau” sebagai “SPDM sudah live”. Status production hanya boleh ditukar selepas semua langkah dalam [`WHAT-TO-DO-NEXT.md`](WHAT-TO-DO-NEXT.md) mempunyai bukti lulus.

### Addendum audit 16 Julai 2026

Audit terkini dan paling berautoriti berada di [`AUDIT-E2E-2026-07-16.md`](AUDIT-E2E-2026-07-16.md). Suite penuh semasa lulus **202 ujian / 700 assertions**, termasuk dua fail OCR sebenar. Chrome sebenar turut meluluskan pendaftaran tenant baharu, upload UI→OCR→carian, workflow minit/kelulusan, semua pautan kelihatan bagi sembilan peranan, dan penolakan silang tenant. Hardening tambahan meliputi pengarkiban tenant tanpa hard delete, sekatan tenant digantung, kebocoran tajuk fail/sensitiviti, ACL carian, route pesanan storan, pendaftaran fail-closed, superadmin terakhir, notifikasi minit selesai dan fallback OCR Windows.

Bahagian bertarikh 13 Julai di bawah dikekalkan sebagai sejarah. Jika nombor ujian, keputusan browser atau status kod bercanggah, gunakan laporan 16 Julai. Status production masih **belum live** kerana host/domain/path/SSH SPDM dan secret/dependency production belum dibuktikan.

## 1. Mulakan di sini

Gunakan turutan sumber kebenaran berikut:

1. Dokumen ini — keadaan semasa, seni bina, workflow, peta halaman, bukti dan blocker.
2. [`WHAT-TO-DO-NEXT.md`](WHAT-TO-DO-NEXT.md) — runbook tindakan seterusnya langkah demi langkah.
3. [`LAPORAN-KESEDIAAN.md`](LAPORAN-KESEDIAAN.md) — ringkasan gate dan keputusan ujian terkini.
4. [`README.md`](README.md) — arahan pembangunan, Docker dan command operasi.
5. [`DIWAN-SPEC.md`](DIWAN-SPEC.md) — baseline produk dan domain v2.1. Jika fakta statusnya bercanggah dengan dokumen ini, gunakan dokumen ini kerana ia lebih baharu.
6. [`CLAUDE-CODE-PROMPTS.md`](CLAUDE-CODE-PROMPTS.md) — pelan pembinaan asal; bukan status deployment semasa.
7. Gateway WhatsApp: [branch `fasa-4-devices`](https://github.com/hakimalek27/wassap-multi-tenant/tree/fasa-4-devices), [handover integrasi](https://github.com/hakimalek27/wassap-multi-tenant/blob/fasa-4-devices/HANDOVER.md), [API](https://github.com/hakimalek27/wassap-multi-tenant/blob/fasa-4-devices/docs/API.md), dan [OpenAPI](https://github.com/hakimalek27/wassap-multi-tenant/blob/fasa-4-devices/docs/openapi-diwan-integration.json).

## 2. Matlamat sesi seterusnya

Lengkapkan production SPDM tanpa staging berasingan, tetapi gunakan deployment terkawal dalam maintenance/canary mode sebelum dibuka kepada pengguna. Hasil wajib:

- tentukan host, domain, path dan kaedah SSH production SPDM;
- pasang rahsia provisioning yang sama pada SPDM dan gateway secara serentak;
- lengkapkan PostgreSQL, Redis/Horizon, Meilisearch, COS, SMTP, IMAP dan OCR production;
- deploy `main`, migrate, cache, hidupkan worker dan scheduler;
- pair satu nombor WhatsApp tenant perintis dari Tetapan Masjid;
- lulus smoke muat naik, OCR, carian, WhatsApp masuk, e-mel masuk dan notifikasi keluar;
- lulus ujian negatif silang tenant;
- lulus backup sebenar dan restore ke pangkalan data terpencil;
- simpan bukti bertarikh sebelum membuka pendaftaran/penggunaan umum.

Runbook penuh berada di [`WHAT-TO-DO-NEXT.md`](WHAT-TO-DO-NEXT.md).

## 3. Snapshot repositori

### 3.1 SPDM

| Perkara | Nilai snapshot |
|---|---|
| Path tempatan | `C:\Projek Coding\Sistem Pengurusan Dokumen Masjid` |
| Remote | `https://github.com/hakimalek27/Sistem-Pengurusan-Dokumen-Masjid.git` |
| Branch | `main` |
| Baseline fungsi terakhir | `d9fee0c feat(intake): isolate tenant channels and searchable OCR` |
| Hardening sebelumnya | `57965a9 fix(tenancy): fail closed across search and webhook cache` |
| Retry OCR | `c41cc43 fix(ocr): preserve derived file across job retries` |
| Keadaan sebelum dokumen ini | bersih, `main...origin/main` |
| Production SPDM | **belum dikenal pasti / belum dideploy** |

Pautan commit: [`d9fee0c`](https://github.com/hakimalek27/Sistem-Pengurusan-Dokumen-Masjid/commit/d9fee0c), [`57965a9`](https://github.com/hakimalek27/Sistem-Pengurusan-Dokumen-Masjid/commit/57965a9), [`c41cc43`](https://github.com/hakimalek27/Sistem-Pengurusan-Dokumen-Masjid/commit/c41cc43).

### 3.2 Gateway WhatsApp

| Perkara | Nilai snapshot |
|---|---|
| Path tempatan | `C:\Projek Coding\Whatsapp Multi Tenant` |
| Remote | `https://github.com/hakimalek27/wassap-multi-tenant.git` |
| Branch | `fasa-4-devices` |
| HEAD tempatan dan server | `d4c9b62` |
| Commit integrasi | `4dfd798` provisioning; `dfc4f94` QR tenant; `d4c9b62` webhook media bertandatangan |
| Host live | `43.133.34.55` |
| Domain live | `https://wassap.wehdah.my` |
| Repo server | `/home/ubuntu/wassap-multi-tenant` |
| Live path | `/var/www/wassap.wehdah.my` |
| Backup deployment | `/home/ubuntu/wassap-backups/wassap-wassap_multitenant-20260713-081859.sql.gz` |
| Release lama | `/var/www/wassap-old-20260713-081907` |
| Binary lama | `engine/bin/engine.pre-diwan-20260713` |

## 4. Keadaan sebenar sekarang

### 4.1 Sudah siap dalam kod SPDM

- Filament strict authorization, polisi resource, authorization custom action dan matriks 9 peranan.
- Pengasingan tenant pada model/query, senarai, carian, service, job, webhook, media path, URL dan artefak muat turun.
- Log akses sulit append-only pada model dan read-only pada policy/resource.
- Tapis sensitiviti pada query sebelum hasil senarai/carian dipulangkan.
- Secure file/artifact controller dengan authorization dan signed URL 5 minit untuk fail, invois dan sijil; eksport menggunakan tarikh luputnya.
- Deep-link `/r/{ulid}` menuju rekod atau Peti Masuk tenant yang betul dan menolak tenant lain.
- Pelupusan tahan kegagalan, state terkawal, pemisahan sedia/lulus/laksana dan batu nisan/sijil.
- Klasifikasi yang sudah digunakan tidak dipadam secara merosakkan; retensi dikira semula melalui service/observer.
- Billing dan invois mempunyai kawalan transaksi/idempotensi yang diuji.
- Wizard klasifikasi Peti Masuk, rekod/fail, minit berantai, kelulusan, audit, dashboard, laporan, retensi, pelupusan, storan dan onboarding.
- Integrasi WhatsApp per tenant: auto-provision, API key terenkripsi, QR/pairing, status sync dan pilihan aktif/nonaktif dari SPDM.
- Penghalaan notifikasi WhatsApp berdasarkan nombor dan opt-in pada pivot ahli tenant; tiada fallback nombor global.
- WhatsApp masuk dua langkah: mesej tepat `spdm` membuka slot 10 minit bagi `session + phone`, kemudian satu media diterima.
- E-mel masuk per tenant melalui plus-addressing tepat, keyword, allowlist penghantar, dedup, MIME dan kuota.
- OCR imej/PDF menghasilkan teks dan PDF derived; Peti Masuk yang telah OCR boleh dicari sebelum klasifikasi.
- Multi-stage Docker, PostgreSQL/Redis/Meili/OCR CI, Horizon, scheduler, backup config, failure drill dan restore drill script tersedia.

### 4.2 Sudah live pada gateway

- Deployment gateway berada pada commit `d4c9b62`.
- Migration provisioning/API key/external identity/managed webhook telah dijalankan.
- Laravel backend, queue, scheduler dan Go engine telah direstart.
- Engine memuat turun media masuk yang disokong sehingga 25 MB, menghantar base64 dalam webhook bertandatangan, dan tidak menyimpan base64 dalam log delivery.
- Endpoint provisioning menolak request tanpa HMAC.
- Dua sesi WhatsApp sedia ada kembali bersambung selepas restart.

### 4.3 Belum siap secara production

- Tiada host/domain/path/SSH production SPDM yang dikenal pasti dalam repo atau konfigurasi semasa.
- Workflow GitHub yang tersedia ialah deployment `staging`; belum ada workflow `production`.
- `DIWAN_PROVISIONING_SECRET` pada gateway production masih kosong.
- `WHATSAPP_PROVISIONING_SECRET` pada SPDM local belum ditetapkan.
- `WHATSAPP_WEBHOOK_URL` SPDM belum ditetapkan kepada URL public HTTPS.
- `IMAP_PASSWORD` local kosong dan `IMAP_ENABLED=false`; saluran e-mel sebenar belum boleh live.
- COS production, PostgreSQL production, Meilisearch production, SMTP production, backup offsite dan restore sebenar belum ada bukti pada host SPDM kerana host itu belum dikenal pasti.
- Ujian WhatsApp dokumen sebenar dari telefon ke SPDM live dan e-mel sebenar ke SPDM live belum boleh dibuat sebelum SPDM mempunyai URL public.

## 5. Seni bina hujung-ke-hujung

```text
Browser -> host SPDM -> Laravel 12 + Filament 4
        -> PostgreSQL (metadata tenant)
        -> Redis/Horizon (OCR, eksport, notifikasi)
        -> Meilisearch (tenant + sensitiviti)
        -> COS private (tenants/{mosque_id}/records/...)

WhatsApp -> wassap.wehdah.my (sesi/API key tenant)
         -> webhook HMAC -> resolve session + ahli
         -> Peti Masuk -> OCR/derived -> carian tenant

E-mel -> scan.diwan+{slug}@domain tepat -> FetchMailJob
      -> tenant aktif + enable + allowlist + keyword
      -> Peti Masuk -> OCR/derived -> carian tenant
```

Satu pangkalan data dikongsi, tetapi setiap entiti domain membawa `mosque_id`. Pemisahan keselamatan berlaku pada beberapa lapisan; ia tidak bergantung pada satu global scope sahaja.

## 6. Jaminan pengasingan multi-tenant

### 6.1 Identiti dan URL

- Setiap masjid/organisasi ialah model `Mosque` dan tenant Filament pada `/app/{slug}`.
- Ahli boleh mempunyai peranan berbeza pada setiap tenant melalui pivot `mosque_user`.
- Route/resource tenant lain dipulangkan sebagai 404 atau hasil kosong, bukan redirect yang membocorkan kewujudan data.
- Superadmin menggunakan panel `/admin`; akses rekod sulit sebagai superadmin dilog dengan `is_superadmin=true`.

### 6.2 Query dan policy

- Model tenant menggunakan concern `BelongsToMosque`; middleware `ApplyTenantScopes` menetapkan konteks request.
- Semua query luar resource yang kritikal menambah `mosque_id` secara eksplisit.
- `Record::visibleTo()` menapis tenant, kebenaran dan sensitiviti sebelum senarai/carian terbentuk.
- `SearchService` memaksa tenant semasa dan sensitiviti yang dibenarkan; Peti Masuk tenant lain tidak masuk hasil.
- Inbox dan `SensitiveAccessLog` mempunyai policy khusus.
- Semua custom action memanggil authorization/service yang relevan.

### 6.3 Queue dan fail

- `ProcessOcrJob` membawa `recordId + mosqueId` dan query kedua-duanya.
- `BuildExportZipJob` membawa `mosqueId` serta menapis semula semua `recordIds`.
- Job/notifikasi lain resolve tenant daripada model/payload, bukan sesi Filament sementara.
- Media disimpan pada `tenants/{mosque_id}/records/{year}/{ulid}/{collection}/` melalui `TenantPathGenerator`.
- Fail private hanya dibuka melalui route `auth + signed`; controller authorize model pemilik dan melog akses sulit.

### 6.4 WhatsApp

- Satu row `whatsapp_integrations` per masjid.
- External ID unik berbentuk `{DIWAN_INSTANCE_ID}:mosque:{id}`.
- Plain API key hanya terenkripsi di SPDM; gateway menyimpan hash SHA-256.
- Setiap request sesi/status/hantar menggunakan API key tenant tepat.
- Gateway menolak session milik tenant lain.
- Webhook inbound hanya menerima integration yang aktif/connected dan `session_id` sepadan.
- Penghantar mesti wujud pada pivot ahli tenant semasa; menjadi ahli masjid A tidak memberi akses kepada sesi masjid B.
- Slot keyword dicache menggunakan hash `session + phone`; slot tidak boleh dipakai oleh nombor atau tenant lain.
- `message_id` didedup dalam skop sesi supaya ID sama pada tenant lain tidak bercampur.

### 6.5 E-mel

- Alias dibina daripada username IMAP yang dikonfigurasi, contoh `scan.diwan+mam@gmail.com`.
- Local-part dan domain mesti sepadan tepat; domain menyerupai atau alias palsu ditolak.
- Slug menentukan tenant, kemudian sistem semak tenant aktif, toggle tenant, allowlist penghantar dan keyword tenant.
- Dedup hash, MIME dan kuota berlaku dalam tenant yang telah disahkan.

### 6.6 Regression gate

- `TenantIsolationTest`, `SearchIsolationTest`, `P0SecurityRegressionTest`, `SecureDownloadTest`, `SensitivityPolicyTest`, `WhatsApp*Test`, `EmailRoutingTest` dan `RoleAuthorizationMatrixTest` tidak boleh dibuang atau dilangkau ketika deploy.
- Ujian matriks meliputi 9 peranan: `admin_masjid`, `kerani`, `pengerusi`, `setiausaha`, `bendahari`, `nazir`, `ketua_imam`, `ajk`, `audit`.

## 7. Workflow fungsi ke fungsi

### 7.1 Daftar dan onboarding tenant

1. Pengguna buka `/daftar`.
2. Isi organisasi, pentadbir pertama, nombor telefon dan pengakuan berkaitan.
3. Sistem mencipta tenant pending dan membership `admin_masjid`.
4. Superadmin buka `/admin/mosques`, pilih `Lulus` atau `Tolak`.
5. `MosqueProvisioningService::approve()` mengaktifkan tenant dan menyalin templat klasifikasi.
6. Pentadbir masuk melalui password/magic link dan melihat checklist onboarding.
7. Tetapan tenant, ahli, retensi, intake e-mel dan WhatsApp dilengkapkan.

Kegagalan selamat: tenant pending/suspended tidak boleh menggunakan panel normal; tenant lain tidak boleh dilihat.

### 7.2 Muat naik manual -> Peti Masuk -> OCR

1. Ahli berizin buka `/app/{tenant}/peti-masuk`.
2. Muat naik PDF/imej/jenis fail yang dibenarkan; nama fail asal disimpan.
3. `InboxIngestService` semak tenant, MIME, kuota dan SHA-256 dedup.
4. Rekod dicipta dengan `status=peti_masuk`, `source_channel=muat_naik` dan media `original` private.
5. `ProcessOcrJob(recordId, mosqueId)` masuk queue OCR.
6. Untuk imej, `img2pdf` menghasilkan input PDF; `ocrmypdf` + Tesseract `msa+eng` menghasilkan `sidecar.txt` dan `searchable.pdf`.
7. Rekod dikemas kini kepada `ocr_status=siap`, `ocr_text` disimpan dan indeks carian dikemas kini.
8. Tindakan `Lihat / OCR` memaparkan teks dan pautan fail yang diberi kuasa.

### 7.3 WhatsApp masuk -> Peti Masuk

Setup sekali oleh admin tenant:

1. `/app/{tenant}/tetapan-masjid` -> `Aktifkan WhatsApp`.
2. SPDM HMAC-provision tenant di gateway.
3. Pilih `Pasangkan WhatsApp`, scan QR atau gunakan kod pautan telefon.
4. SPDM poll/sync sehingga status `connected`.
5. `/app/{tenant}/ahli-peranan` -> set nombor WhatsApp ahli dan opt-in notifikasi.

Penggunaan harian:

1. Ahli berdaftar menghantar mesej tepat `spdm` kepada nombor WhatsApp masjidnya.
2. Gateway menghantar webhook `message.received`; SPDM membuka slot 10 minit untuk pasangan `session + phone`.
3. Ahli menghantar satu PDF/imej dalam tempoh slot.
4. Gateway memuat turun media, menghantar base64 dengan `X-Signature: sha256=...`.
5. SPDM verify raw-body HMAC, session, tenant, ahli, kuota, MIME dan idempotency.
6. Dokumen masuk Peti Masuk tenant dan OCR berjalan.

Jalan singkat yang turut disokong: media dengan kapsyen yang mengandungi keyword tenant. Group dan mesej `from_me` diabaikan.

### 7.4 E-mel masuk -> Peti Masuk

Setup sekali oleh admin tenant:

1. Platform menyediakan satu akaun IMAP dengan plus-addressing.
2. Admin buka Tetapan Masjid, aktifkan intake e-mel, tetapkan keyword dan allowlist penghantar.
3. UI memaparkan alamat unik tenant daripada username IMAP sebenar.

Penggunaan harian:

1. Hantar e-mel daripada alamat allowlist ke alias tenant.
2. Letakkan keyword, lalai `spdm`, dalam subjek atau body.
3. Lampirkan dokumen yang dibenarkan.
4. `diwan:fetch-mail` / `FetchMailJob` membaca folder setiap minit.
5. `MailIngestService` menjalankan semua semakan fail-closed dan menghantar attachment ke `InboxIngestService`.
6. Dokumen masuk Peti Masuk tenant, OCR dan carian.

Status penolakan yang dijangka: `no_slug`, `unknown_or_inactive`, `disabled`, `sender_not_allowed`, `keyword_missing`, `quota`, MIME tidak dibenarkan atau duplicate.

### 7.5 Peti Masuk -> klasifikasi -> rekod rasmi

1. Kerani/admin/setiausaha buka item Peti Masuk.
2. Semak preview asal/derived dan teks OCR.
3. Pilih jenis rekod, tarikh, pengirim/penerima, sensitiviti dan fail destinasi.
4. Jika perlu, buka fail registry baharu berdasarkan nod klasifikasi aktif.
5. `InboxIngestService::fileRecord()` authorize tenant, fail dan sensitiviti lalu memberi nombor lampiran.
6. Status menjadi `difailkan`; audit, retention due date dan indeks dikemas kini.
7. Rekod boleh dipindah fail dengan sebab, diganti versi atau diberi legal hold mengikut role.

### 7.6 Carian -> hasil -> deep-link

1. Buka `/app/{tenant}/carian`.
2. Cari tajuk, rujukan, metadata atau kandungan OCR.
3. Service mengehadkan `mosque_id` dan sensitiviti sebelum memulangkan hasil.
4. Klik hasil untuk `/r/{ulid}` atau halaman rekod/Peti Masuk tenant tepat.
5. Akses tenant lain, rekod sulit tanpa hak atau deep-link palsu ditolak.

### 7.7 Rekod, fail dan muat turun selamat

1. `/records` menyenaraikan rekod yang sudah difailkan/diganti dan dibenarkan.
2. `/registry-files` mengurus fail, volume, tutup fail dan access grant fail sulit.
3. QR label menggunakan deep-link `/r/{ulid}`.
4. Preview/download menghasilkan signed URL; media/invois/sijil sah 5 minit, eksport hingga `expires_at`.
5. Controller query model tenant, authorize pengguna, stream private object dan log `view/download` bagi bahan sulit.

### 7.8 Minit/routing dan kelulusan

- `MinitService::create()` mencipta arahan kepada action recipients dan CC dalam tenant.
- Penerima boleh baca, balas dan route sebagai thread; sejarah parent/child kekal.
- `markDone()` menutup tugasan; scheduler hantar reminder lewat.
- Kelulusan dicipta oleh role berizin, diputus oleh role `approvals.decide`, dengan nota/IP/audit.
- Notifikasi mengikuti tetapan e-mel/Telegram/WhatsApp pengguna dan nombor tenant.

### 7.9 Storan, pesanan dan invois

1. `/penggunaan` memaparkan kuota, penggunaan dan carta.
2. `Tambah Storan` mencipta pesanan/invois manual.
3. Superadmin buka `/admin/storage-orders` dan `Tandakan Dibayar` atau `Batal`.
4. Transaksi mengaktifkan add-on secara idempotent; scheduler mengingatkan/melupuskan add-on tamat.
5. Muat naik disekat apabila penuh; dokumen sedia ada tidak dipadam.

### 7.10 Retensi dan pelupusan

1. Retention rule efektif dipilih berdasarkan tenant/jenis rekod.
2. Due date dikira semula selepas perubahan berkaitan dan sebelum notis.
3. Legal hold, rekod kekal, tenant suspended atau `auto_disposal_enabled=false` menghentikan auto-disposal.
4. Notis T-90/T-30/T-7 dihantar.
5. Pelupusan manual: penyedia sediakan batch -> pengerusi lulus -> admin masjid laksana.
6. `DisposalService` memproses item secara stateful; kegagalan blob tidak boleh menghasilkan status palsu berjaya.
7. Metadata batu nisan dan sijil dikekalkan; media asal/derived dipadam mengikut keputusan sah.

## 8. Peta halaman dan pautan

### 8.1 Awam dan autentikasi

| URL | Fungsi | Kawalan |
|---|---|---|
| `/` | halaman penerangan | awam |
| `/daftar` | daftar organisasi/tenant | throttle |
| `/log-masuk` | minta magic link | throttle |
| `/masuk/{token}` | guna token sekali | token sah + throttle |
| `/app/login` | login panel tenant | auth Filament |
| `/admin/login` | login superadmin | superadmin sahaja selepas login |
| `/r/{ulid}` | deep-link QR rekod/Peti Masuk | auth + membership/policy |

### 8.2 Panel tenant `/app/{tenant}`

| Halaman | URL | Fungsi/tindakan utama |
|---|---|---|
| Dashboard | `/app/{tenant}` | statistik tenant, trend rekod, penggunaan, onboarding |
| Peti Masuk | `/peti-masuk` | upload, senarai, lihat/OCR, klasifikasi, padam spam |
| Lihat Peti Masuk | `/peti-masuk/{record}` | metadata, OCR, fail asal/derived dan tindakan terkawal |
| Rekod | `/records` | senarai rekod difailkan, filter, lihat, eksport |
| Lihat Rekod | `/records/{record}` | butiran, preview/download, minit, kelulusan, versi, audit |
| Fail | `/registry-files` | buka, lihat, tutup fail dan volume |
| Lihat Fail | `/registry-files/{record}` | rekod dalam fail, QR, access grant fail sulit |
| Minit Saya | `/minit-saya` | inbox tugasan, baca, balas, route dan selesai |
| Kelulusan | `/kelulusan` | lihat permintaan, lulus/tolak dengan authorization |
| Carian | `/carian` | carian metadata + OCR tenant/sensitiviti |
| Laporan | `/laporan` | metrik pejabat dan eksport CSV |
| Ahli & Peranan | `/ahli-peranan` | jemput, tukar role, keluarkan, nombor WA dan opt-in |
| Klasifikasi Fail | `/classification-nodes` | create/edit/deactivate struktur klasifikasi |
| Pelupusan | `/pelupusan` | sedia batch, lulus dan laksana mengikut separation of duties |
| Peraturan Retensi | `/retensi-peraturan` | create/edit/deactivate override retensi |
| Tetapan Masjid | `/tetapan-masjid` | profil, intake e-mel, provisioning/pairing/sync/toggle WhatsApp |
| Penggunaan & Storan | `/penggunaan` | kuota, carta, add-on dan invois |
| Retensi & Pegangan | `/retensi` | legal hold, senarai akan luput dan eksport |
| Log Akses Sulit | `/sensitive-access-logs` | audit read-only bagi role `audit.view` |
| Profil | `/profil` | preferensi notifikasi dan hantar notifikasi ujian |

Semua URL dalam jadual tenant mesti diawali `/app/{tenant}`.

### 8.3 Panel superadmin `/admin`

| Halaman | Fungsi/tindakan utama |
|---|---|
| Dashboard | statistik platform dan pertumbuhan tenant |
| `/admin/mosques` | create/view/edit, lulus, tolak, gantung, ubah kuota, masuk panel |
| `/admin/users` | create/edit pengguna global/superadmin secara terkawal |
| `/admin/storage-orders` | semak pesanan, tandakan dibayar, batal |
| `/admin/tetapan-platform` | harga storan, bank, DPO dan tetapan platform |

### 8.4 Endpoint dilindungi

| Endpoint | Tujuan | Kawalan |
|---|---|---|
| `POST /api/webhooks/whatsapp` | intake WhatsApp | throttle + raw-body HMAC + session/tenant/member |
| `POST /api/webhooks/telegram/{secret}` | callback Telegram | secret + throttle |
| `GET /secure-file/{media}` | preview/download private | auth + signed + policy |
| `GET /secure-artifact/invoice/{order}` | invois PDF | auth + signed + policy |
| `GET /secure-artifact/certificate/{batch}` | sijil pelupusan | auth + signed + policy |
| `GET /secure-artifact/export/{export}` | ZIP eksport | auth + signed + owner/tenant + expiry |
| `/horizon` | queue monitoring | authorization Horizon; jangan dedah awam tanpa auth |

## 9. Matriks peranan ringkas

Sumber penuh ialah [`config/roles.php`](config/roles.php); policy boleh menambah sekatan domain.

| Peranan | Akses utama |
|---|---|
| Pentadbir Masjid | hampir semua operasi tenant kecuali keputusan kelulusan dan lulus pelupusan |
| Kerani | intake, rekod, fail, minit, minta kelulusan, klasifikasi, hold, eksport, sedia pelupusan |
| Pengerusi | lihat, grant fail sulit, minit, putus kelulusan, lulus pelupusan, audit |
| Setiausaha | intake, rekod, minit dan minta kelulusan |
| Bendahari | rekod kewangan terkawal, minit, minta kelulusan, penggunaan/pesanan storan |
| Nazir | lihat, minit dan putus kelulusan |
| Ketua Imam | lihat dan minit |
| AJK | lihat dan minit |
| Juruaudit | lihat rekod/fail dan log audit |

Sekatan penting: bendahari create/update hanya dalam klasifikasi kewangan yang dibenarkan; kelulusan pelupusan dan pelaksanaan dipisahkan; role tanpa akses sulit hanya melihat rekod bukan sulit atau access grant eksplisit.

## 10. Model data utama

| Domain | Jadual/model utama |
|---|---|
| Tenant dan pengguna | `mosques`, `users`, `mosque_user` |
| Intake/registri | `records`, `registry_files`, `classification_nodes`, media library |
| Kerja pejabat | `minits`, `minit_recipients`, `approvals` |
| Keselamatan | `sensitive_access_logs`, `file_access_grants`, activity log |
| Retensi | `retention_rules`, `disposal_batches`, `disposal_items` |
| Storan/billing | `storage_orders`, `storage_addons`, `stored_exports` |
| Notifikasi | `notification_logs`, Laravel notifications |
| WhatsApp | `whatsapp_integrations` dan medan routing pada `mosque_user` |

Jangan cipta query baru terhadap jadual domain tanpa menambah syarat tenant atau melalui relationship yang telah diskop.

## 11. Job dan scheduler

| Jadual | Command/job | Fungsi |
|---|---|---|
| setiap minit | `diwan:fetch-mail` | tarik dan proses e-mel intake |
| 02:30 harian | `backup:run` | backup aplikasi/DB ke disk backup |
| 03:00 harian | `diwan:reconcile-storage` | kira semula penggunaan tenant |
| 06:00 harian | `diwan:expire-addons` | notis/luput add-on |
| 07:00 harian | `diwan:run-retention-notices` | notis T-90/T-30/T-7 |
| 07:30 harian | `diwan:run-retention-execute` | pelupusan automatik layak |
| 08:00 harian | `diwan:send-minit-reminders` | reminder minit lewat |
| setiap 5 minit | `diwan:ping-gateway` | kesihatan WhatsApp |
| 04:00, 1 haribulan | `diwan:prune-logs` | pangkas log ikut dasar |

Queue utama: `default`, `ocr`, dan `exports`. Production memerlukan Horizon aktif serta OCR `maxProcesses=1` untuk mengawal RAM.

## 12. Konfigurasi dan rahsia — tanpa nilai

Jangan simpan nilai production dalam Git, dokumen, chat, screenshot atau output CI. Gunakan secret store/password manager dan audit akses.

### 12.1 Rahsia silang sistem WhatsApp

| SPDM | Gateway | Hubungan |
|---|---|---|
| `WHATSAPP_PROVISIONING_SECRET` | `DIWAN_PROVISIONING_SECRET` | **mesti nilai sama**, dijana sekali dan dideploy pada kedua-dua consumer sebelum restart |
| `WHATSAPP_WEBHOOK_SECRET` | disimpan terenkripsi oleh provisioning | SPDM jana >=32 aksara; dihantar hanya dalam request HMAC provisioning |
| `WHATSAPP_WEBHOOK_URL` | managed webhook target | `https://<domain-spdm>/api/webhooks/whatsapp` |
| `WHATSAPP_GATEWAY_URL` | domain gateway | `https://wassap.wehdah.my` |
| `DIWAN_INSTANCE_ID` | `external_id` prefix | unik, stabil, contoh `spdm-production` |

Jangan rotate satu sisi sahaja. Aliran rotation: jana -> deploy kepada semua consumer -> verify -> revoke nilai lama.

### 12.2 Kumpulan konfigurasi production SPDM

- Aplikasi: `APP_ENV`, `APP_KEY`, `APP_DEBUG=false`, `APP_URL`, timezone/locale, secure cookie.
- DB: PostgreSQL host/port/database/user/password.
- Cache/queue: Redis, `QUEUE_CONNECTION=redis`, Horizon.
- Storage: `DIWAN_STORAGE_DISK=cos`, COS utama private, COS backup, endpoint dan CAM least privilege.
- Search: `SCOUT_DRIVER=meilisearch`, URL dan master key.
- Mail keluar: SMTP host/port/user/password/from.
- Mail masuk: `IMAP_ENABLED=true`, host/port/protocol/encryption/cert/username/App Password.
- OCR: `OCR_LANGS=msa+eng`, Tesseract, OCRmyPDF, img2pdf, Ghostscript/qpdf tersedia dalam image.
- Telegram jika digunakan: bot token dan webhook secret.

### 12.3 Snapshot local semasa

- `WHATSAPP_PROVISIONING_SECRET`: tidak ditetapkan.
- `WHATSAPP_WEBHOOK_URL`: tidak ditetapkan.
- `IMAP_PASSWORD`: kosong.
- `IMAP_ENABLED`: ditetapkan tetapi nilainya `false` dalam pemeriksaan sebelum handover.
- `WHATSAPP_WEBHOOK_SECRET`: ada secara local, tetapi tidak boleh dianggap nilai production.
- SMTP password local ada, tetapi tidak membuktikan SMTP production.
- DB/COS production tidak lengkap pada local dan tidak sepatutnya disalin terus ke production.

### 12.4 Snapshot gateway production

- `DIWAN_PROVISIONING_SECRET`: **MISSING** pada 13 Julai 2026.
- Jangan jana/set di gateway sahaja; tunggu lokasi production SPDM diketahui supaya nilai sama boleh dipasang dalam satu change window.

## 13. Bukti ujian dan verifikasi

### 13.1 SPDM automatik

- Suite penuh penutup handover pada 13 Julai 2026: **190 passed, 647 assertions** dalam 123.09 saat, dengan OCR sebenar tersedia.
- Targeted OCR retry pada 13 Julai 2026: **2 passed, 9 assertions**.
- CI GitHub run [`29214854569`](https://github.com/hakimalek27/Sistem-Pengurusan-Dokumen-Masjid/actions/runs/29214854569) direkodkan hijau untuk PostgreSQL, Redis, Meili, OCR, suite penuh, runtime smoke dan dua image Docker.
- `gh` CLI tempatan kini memberi `401 Bad credentials`, jadi status run tersebut tidak dapat di-refresh melalui CLI pada snapshot ini; link run kekal sebagai artefak bukti.
- Pint, `npm run build`, route cache dan config cache telah lulus pada baseline fungsi.

Nota verifikasi 13 Julai: satu larian pertama dihentikan oleh had masa alat 120 saat. Larian kedua yang bertindih sementara memberi 189 lulus/1 gagal pada retry OCR kerana fake storage ujian dikongsi; selepas tiada proses bertindih, targeted retry lulus dan larian penuh tunggal lulus 190/190. Ini ialah isu orkestrasi runner, bukan defect aplikasi yang boleh diulang.

### 13.2 OCR imej sebenar melalui Chrome

Fail ujian asal:

| Fail | Saiz | SHA-256 |
|---|---:|---|
| `C:\Users\hakim\Downloads\WhatsApp Image 2026-07-10 at 12.39.18.jpeg` | 76,411 byte | `664084C620EA486474766E79721944C3B7AB457D8E12B442534802E389833792` |
| `C:\Users\hakim\Downloads\WhatsApp Image 2026-07-10 at 12.39.18 (1).jpeg` | 104,150 byte | `048F842E00737450172B157B2E5301E66AB0A274FC6EADB374857FA08042A837` |

Bukti local DB selepas E2E:

| Record | Tenant | Status | Aksara OCR | Derived | Frasa carian disahkan |
|---:|---:|---|---:|---|---|
| 14 | 1 (MAM local) | `siap` | 916 | `searchable.pdf` | `MESYUARAT JAWATANKUASA` |
| 15 | 1 (MAM local) | `siap` | 1,165 | `searchable.pdf` | `PENCERAHAN HUKUM` |

- Chrome upload -> queue OCR -> halaman `Lihat / OCR` -> carian MAM lulus dalam 32.9s.
- Carian tenant MAN bagi `PENCERAHAN HUKUM` tidak memulangkan rekod MAM; ujian silang tenant lulus dalam 20.3s.
- In-app Chrome MCP tiada browser/tab terpasang ketika itu, jadi standalone Google Chrome melalui Playwright digunakan. Test sementara telah dibuang dan tidak menjadi dependency repo.

### 13.3 Gateway tempatan

- Laravel: **168 passed, 488 assertions** pada 13 Julai 2026.
- `go vet ./...`: lulus.
- `go test ./...`: lulus; package manager/server/webhook hijau.
- API linter terdahulu: 100/100 tanpa warning; scorecard B, 80.53.

### 13.4 Gateway production — semakan baru

Pada 13 Julai 2026:

- `https://wassap.wehdah.my/up` -> HTTP 200.
- `POST /internal/v1/tenants/provision` tanpa HMAC -> HTTP 401.
- Remote repo -> `d4c9b62`.
- `wassap-engine`, `wassap-queue`, `wassap-scheduler` -> `active`.
- Engine `/health` -> `db_ok=true`, `sessions_connected=2`, `sessions_total=2`, `status=ok`.
- `DIWAN_PROVISIONING_SECRET` -> `MISSING`.

## 14. Batas dan risiko yang masih wujud

1. SPDM tidak mempunyai sasaran production diketahui; tiada bukti live untuk DB/COS/IMAP/SMTP/Meili/backup.
2. Pengguna memilih tiada staging. Risiko dikurangkan dengan maintenance/canary pada production, backup dahulu dan buka hanya tenant perintis selepas smoke.
3. WhatsApp menggunakan whatsmeow, bukan Cloud API rasmi; nombor berisiko disconnect/sekatan. E-mel dan upload manual mesti kekal sebagai fallback.
4. OCR tulisan tangan/Jawi/imbasan buruk tidak dijamin; metadata masih boleh dicari.
5. Plus-addressing bergantung pada provider e-mel; sahkan Gmail/provider benar-benar menerima alias.
6. Satu VM ialah single point of failure walaupun fail berada dalam COS/backup.
7. Restore drill belum terbukti pada data production SPDM sebenar.
8. Terma, DPA, dasar retensi dan kesesuaian pelupusan bagi masjid di bawah pihak berkuasa agama memerlukan pengesahan manusia/undang-undang.
9. GitHub Actions memberi anotasi deprecation Node.js 20 untuk action tertentu; tidak blocking sekarang tetapi perlu kemas kini action apabila upstream tersedia.
10. Jangan buka pendaftaran awam sebelum pilot tenant, notifikasi, backup dan negative tenant smoke lulus.

## 15. Rollback dan pemulihan

### Gateway

- Kod lama: `/var/www/wassap-old-20260713-081907`.
- DB backup: `/home/ubuntu/wassap-backups/wassap-wassap_multitenant-20260713-081859.sql.gz`.
- Go binary lama: `engine/bin/engine.pre-diwan-20260713`.
- Rollback hanya jika smoke production gagal; backup DB sebelum sebarang migration baru. Jangan restore DB tanpa menilai migration/data selepas backup.

### SPDM akan datang

- Sebelum deploy: backup DB, simpan image/tag release sebelumnya dan pastikan rollback compose/release tersedia.
- Deploy migration secara `--force` hanya selepas backup.
- Jika smoke gagal: kekalkan maintenance mode, rollback image/kod, pulihkan DB hanya jika migration tidak backward-compatible dan bukti menunjukkan perlu.
- Fail COS jangan dipadam untuk rollback aplikasi; gunakan versioning/lifecycle.
- Restore drill mesti menggunakan DB container/database terpencil, bukan menimpa production.

## 16. Open decisions yang memerlukan pemilik

- Apakah domain production SPDM sebenar? Baseline spesifikasi ialah `diwan.wehdah.my`, tetapi belum disahkan.
- Apakah host/IP, user SSH dan path deployment SPDM?
- Secret store mana akan menjadi source of truth production?
- Akaun IMAP mana akan digunakan dan adakah plus-addressing disokong?
- Siapa penerima e-mel smoke/alert operasi?
- Tenant pilot pertama MAM atau organisasi lain?
- Adakah pendaftaran awam dibuka terus atau kekal tutup sehingga UAT pilot selesai?
- Adakah Telegram diaktifkan semasa go-live atau ditangguh?
- Siapa meluluskan Terma/DPA dan dasar retensi sebelum tenant luar?

## 17. Kemahiran disyorkan untuk sesi seterusnya

- `env-secrets-manager` — memasang dan memutar rahsia silang sistem tanpa kebocoran/drift.
- `docker-development` — membina dan menjalankan stack production SPDM.
- `browser-automation` / `playwright-pro` — smoke browser sebenar dan ujian silang tenant.
- `api-test-suite-builder` — negative test provisioning, HMAC, API key dan session tenant.
- `code-reviewer` — semakan akhir jika kod berubah semasa deployment.
- `incident-response` — hanya jika live smoke mengesan kebocoran tenant, kehilangan data atau secret exposure.

## 18. Artefak penting

### SPDM

- Produk/domain: [`DIWAN-SPEC.md`](DIWAN-SPEC.md)
- Operasi: [`README.md`](README.md)
- Status gate: [`LAPORAN-KESEDIAAN.md`](LAPORAN-KESEDIAAN.md)
- Tindakan seterusnya: [`WHAT-TO-DO-NEXT.md`](WHAT-TO-DO-NEXT.md)
- Role matrix: [`config/roles.php`](config/roles.php)
- Route awam/secure: [`routes/web.php`](routes/web.php)
- Webhook: [`routes/api.php`](routes/api.php)
- Scheduler: [`routes/console.php`](routes/console.php)
- WhatsApp provisioning: [`app/Services/WhatsAppIntegrationService.php`](app/Services/WhatsAppIntegrationService.php)
- WhatsApp inbound: [`app/Services/WhatsAppInboundService.php`](app/Services/WhatsAppInboundService.php)
- E-mel inbound: [`app/Services/MailIngestService.php`](app/Services/MailIngestService.php)
- OCR: [`app/Jobs/ProcessOcrJob.php`](app/Jobs/ProcessOcrJob.php)
- Carian: [`app/Services/SearchService.php`](app/Services/SearchService.php)
- Secure files: [`app/Http/Controllers/SecureFileController.php`](app/Http/Controllers/SecureFileController.php)
- CI: [`.github/workflows/ci.yml`](.github/workflows/ci.yml)
- Deployment sedia ada: [`.github/workflows/deploy-staging.yml`](.github/workflows/deploy-staging.yml)
- Docker: [`docker/Dockerfile`](docker/Dockerfile), [`docker-compose.yml`](docker-compose.yml)
- Restore drill: [`scripts/restore-drill.sh`](scripts/restore-drill.sh)

### Gateway

- Handover: `C:\Projek Coding\Whatsapp Multi Tenant\HANDOVER.md`
- API: `C:\Projek Coding\Whatsapp Multi Tenant\docs\API.md`
- OpenAPI: `C:\Projek Coding\Whatsapp Multi Tenant\docs\openapi-diwan-integration.json`
- Deploy script: `C:\Projek Coding\Whatsapp Multi Tenant\deploy\deploy.sh`
- Laravel backend: `C:\Projek Coding\Whatsapp Multi Tenant\backend`
- Go engine: `C:\Projek Coding\Whatsapp Multi Tenant\engine`

## 19. Definisi “siap live”

SPDM hanya boleh dilabel **LIVE / semua OK** apabila:

- semua rahsia dan dependency production sah;
- migration, Horizon, scheduler, OCR, Meili, COS, SMTP dan IMAP hijau;
- upload manual, WhatsApp dan e-mel sebenar masuk ke tenant tepat;
- OCR boleh dilihat dan dicari;
- tenant kedua tidak nampak hasil/fail tenant pertama;
- notifikasi keluar dari nombor tenant yang betul kepada penerima opt-in yang betul;
- signed URL/authorization negatif ditolak;
- backup dan restore drill lulus;
- rollback tersedia;
- bukti Pass/Fail/Evidence disimpan dan semua P0 berstatus Pass.

Sehingga itu, pengisytiharan yang tepat ialah: **gateway WhatsApp live; kod SPDM siap dan diuji; deployment production SPDM masih menunggu maklumat host serta konfigurasi rahsia/dependency.**

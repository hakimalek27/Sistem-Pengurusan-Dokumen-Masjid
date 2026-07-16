# What To Do Next — Go-Live SPDM Tanpa Staging Berasingan

**Status mula:** gateway WhatsApp live; SPDM belum berada pada host production.

**Gate local 16 Julai 2026:** 202 ujian/700 assertions, build Vite, pendaftaran tenant, dua imej UI→OCR→carian, workflow minit/kelulusan dan crawl 9 peranan lulus. Bukti penuh: [`AUDIT-E2E-2026-07-16.md`](AUDIT-E2E-2026-07-16.md). Langkah seterusnya ialah menyediakan akses production yang masih tiada; jangan mengulang audit local sebagai ganti bukti production.

**Prinsip:** production-first mengikut keputusan pemilik, tetapi aplikasi kekal dalam maintenance/canary sehingga gate keselamatan, data dan intake lulus.

**Sumber konteks:** [`HANDOVER-LENGKAP-A-Z.md`](HANDOVER-LENGKAP-A-Z.md).

## Cara guna checklist ini

- Buat satu langkah pada satu masa.
- Tandakan `Pass/Fail` dan simpan `Evidence`; jangan bergantung pada ingatan.
- Jangan tampal secret sebenar ke chat, Git, dokumen, screenshot atau log.
- Jika langkah P0 gagal, hentikan pembukaan pengguna. Kekalkan maintenance mode dan rollback jika perlu.
- Jangan ubah gateway global/server bersama di luar servis/path `wassap`.

## P0. Maklumat yang mesti diperoleh dahulu

Pemilik perlu memberikan atau mengesahkan perkara berikut:

- [ ] `SPDM_PROD_DOMAIN` — domain public HTTPS sebenar.
- [ ] `SPDM_PROD_HOST` — IP/hostname SSH.
- [ ] `SPDM_PROD_USER` dan port SSH.
- [ ] `SPDM_PROD_PATH` — cadangan `/opt/diwan`.
- [ ] kaedah deployment: Docker Compose dari source atau image GHCR.
- [ ] lokasi secret store/password manager production.
- [ ] akaun IMAP dan Gmail App Password/provider equivalent.
- [ ] alamat penerima e-mel untuk smoke SMTP.
- [ ] tenant pilot dan akaun admin pilot.
- [ ] tempoh change window serta orang yang boleh memutuskan rollback.

**Stop condition:** jangan jana/set shared provisioning secret sebelum lokasi SPDM production dikenal pasti. Menetapkan satu sisi sahaja menghasilkan drift dan integrasi gagal.

## P1. Persediaan keselamatan production

### 1. Kunci versi release

Pada mesin kerja:

```powershell
cd 'C:\Projek Coding\Sistem Pengurusan Dokumen Masjid'
git status --short --branch
git fetch origin
git log -1 --oneline origin/main
```

Gate:

- [ ] working tree bersih;
- [ ] commit dokumentasi ini telah push;
- [ ] CI untuk commit release hijau;
- [ ] SHA release direkod dalam bukti deployment.

### 2. Sediakan rollback sebelum deploy

Jika host SPDM baharu, rekod bahawa belum ada DB/data untuk dipulihkan. Jika host pernah digunakan:

- [ ] ambil backup DB sebelum migration;
- [ ] salin `.env`/secret melalui kaedah selamat, bukan ke Git;
- [ ] rekod image/tag/release lama;
- [ ] pastikan ruang disk dan rollback path mencukupi;
- [ ] hidupkan versioning pada bucket COS;
- [ ] sahkan restore target terpencil tersedia.

### 3. Kekalkan sistem tertutup semasa canary

- Set `DIWAN_REGISTRATION_OPEN=false`.
- Jangan umumkan domain kepada tenant luar.
- Gunakan maintenance mode atau had akses reverse proxy kepada operator/pilot semasa smoke.
- Jika menggunakan `php artisan down`, simpan bypass secret dalam secret store dan jangan masukkan dalam laporan.

## P2. Sediakan dependency production SPDM

### 4. PostgreSQL

- [ ] PostgreSQL 16 aktif.
- [ ] database/user khusus SPDM, bukan user superuser.
- [ ] network hanya kepada container/app yang memerlukan.
- [ ] backup credential berada dalam secret store.
- [ ] migration boleh connect.

### 5. Redis dan Horizon

- [ ] Redis 7 aktif dan tidak didedah ke internet.
- [ ] `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, `SESSION_DRIVER=redis`.
- [ ] Horizon boleh bermula dan `php artisan horizon:status` melapor aktif.
- [ ] queue OCR menggunakan concurrency 1.

### 6. Meilisearch

- [ ] versi dipin, bukan `latest`.
- [ ] master key production kuat dan rahsia.
- [ ] service tidak public tanpa auth/firewall.
- [ ] `SCOUT_DRIVER=meilisearch`.
- [ ] `php artisan diwan:sync-meili` lulus.

### 7. COS private dan backup

- [ ] bucket dokumen utama di rantau dipilih, private, SSE/versioning aktif.
- [ ] bucket backup berasingan dan lifecycle dikonfigurasi.
- [ ] CAM credential least privilege.
- [ ] `DIWAN_STORAGE_DISK=cos`, `FILESYSTEM_DISK=cos`, `BACKUP_DISK=cos_backup`.
- [ ] signed URL/stream sahaja; jangan ubah bucket menjadi public.
- [ ] write/read/delete probe lulus.

### 8. OCR

- [ ] image app mengandungi Tesseract, bahasa `msa`, OCRmyPDF, img2pdf, Ghostscript, qpdf dan unpaper.
- [ ] `OCR_LANGS=msa+eng`.
- [ ] worker mempunyai RAM/tmp space yang cukup.
- [ ] satu imej BM sebenar menghasilkan `ocr_status=siap`, teks dan `searchable.pdf`.

### 9. SMTP

- [ ] credential SMTP production ditetapkan.
- [ ] from address/domain sah.
- [ ] TLS/certificate disahkan.
- [ ] satu e-mel smoke sebenar diterima.

### 10. IMAP intake

- [ ] provider menyokong plus-addressing.
- [ ] 2FA diaktifkan jika Gmail.
- [ ] App Password dijana dan disimpan terus dalam secret store.
- [ ] `IMAP_ENABLED=true`.
- [ ] `IMAP_VALIDATE_CERT=true`, `IMAP_DEBUG=false`.
- [ ] username tepat, contoh `scan.diwan@gmail.com`.
- [ ] folder boleh dibaca tanpa memadam mesej yang belum selesai secara salah.

## P3. Pasang rahsia silang sistem secara atomik

### 11. Jana secret

Dalam secret store, jana nilai rawak sekurang-kurangnya 32 byte untuk:

- `SPDM_WHATSAPP_PROVISIONING_SHARED_SECRET`;
- `SPDM_WHATSAPP_WEBHOOK_SECRET`.

Jangan gunakan secret yang sama untuk kedua-dua tujuan. Jangan print nilai dalam terminal yang dirakam atau CI log.

### 12. Peta nilai ke consumer

| Nilai secret store | SPDM | Gateway |
|---|---|---|
| provisioning shared secret | `WHATSAPP_PROVISIONING_SECRET` | `DIWAN_PROVISIONING_SECRET` |
| webhook secret | `WHATSAPP_WEBHOOK_SECRET` | diterima/disimpan terenkripsi melalui provisioning |

SPDM turut memerlukan:

```dotenv
WHATSAPP_DRIVER=gateway
WHATSAPP_GATEWAY_URL=https://wassap.wehdah.my
WHATSAPP_WEBHOOK_URL=https://<SPDM_PROD_DOMAIN>/api/webhooks/whatsapp
DIWAN_INSTANCE_ID=spdm-production
```

`DIWAN_INSTANCE_ID` mesti unik dan kekal; menukarnya kemudian boleh menghasilkan external tenant baharu.

### 13. Tetapkan gateway dalam change window yang sama

Pada gateway production, edit fail live secara interaktif/secret automation; jangan echo nilai:

```bash
sudoedit /var/www/wassap.wehdah.my/.env
cd /var/www/wassap.wehdah.my
php artisan optimize:clear
php artisan config:cache
sudo systemctl reload php8.4-fpm
```

Semak kewujudan tanpa mencetak nilai:

```bash
grep -Eq '^DIWAN_PROVISIONING_SECRET=.+$' /var/www/wassap.wehdah.my/.env \
  && echo SET || echo MISSING
```

Gate:

- [ ] kedua-dua consumer menerima nilai yang sama;
- [ ] tiada nilai muncul dalam shell history/log;
- [ ] gateway `/up` kekal 200;
- [ ] provisioning tanpa HMAC kekal 401.

## P4. Deploy SPDM production

### 14. Checkout release

Contoh jika menggunakan `/opt/diwan`:

```bash
sudo install -d -o "$USER" -g "$USER" /opt/diwan
git clone https://github.com/hakimalek27/Sistem-Pengurusan-Dokumen-Masjid.git /opt/diwan
cd /opt/diwan
git checkout main
git pull --ff-only origin main
git rev-parse HEAD
```

Jika repo private, gunakan deploy key read-only atau image GHCR; jangan simpan PAT dalam remote URL.

### 15. Tetapkan environment

Gunakan [`.env.example`](.env.example) sebagai senarai nama, bukan sebagai sumber nilai. Minimum:

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL`;
- [ ] `APP_KEY` production unik;
- [ ] PostgreSQL, Redis/Horizon;
- [ ] COS utama dan backup;
- [ ] Meilisearch;
- [ ] SMTP dan IMAP;
- [ ] WhatsApp shared/webhook settings;
- [ ] secure session cookie;
- [ ] pendaftaran masih ditutup.

Semak `.env` dimiliki user servis dan tidak world-readable. Jangan bake `.env` ke image.

### 16. Build/start stack

```bash
cd /opt/diwan
docker compose config
docker compose build --pull
docker compose up -d
docker compose ps
```

Gate: app, web, db, redis, meilisearch, worker dan scheduler semuanya healthy/running.

### 17. Migration dan cache

```bash
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan migrate --force --no-interaction
docker compose exec app php artisan diwan:sync-meili
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
docker compose exec app php artisan horizon:status
docker compose exec app php artisan schedule:list
```

Gate:

- [ ] migration exit 0;
- [ ] tiada pending migration;
- [ ] Horizon aktif;
- [ ] 9 jadual operasi muncul;
- [ ] route/config/view cache lulus.

### 18. Cipta superadmin

Gunakan command repo dengan password dimasukkan secara selamat:

```bash
docker compose exec app php artisan diwan:make-superadmin <EMAIL_OPERATOR>
```

Jangan masukkan password dalam command yang akan disimpan di shell history jika command menyediakan prompt interaktif.

## P5. Health gate sebelum pengguna

### 19. Dependency smoke

Walaupun command bernama `staging-check`, ia boleh digunakan sebagai health gate pada production dalam maintenance window:

```bash
docker compose exec app php artisan diwan:health
docker compose exec app php artisan diwan:staging-check --mail-to=<EMAIL_SMOKE>
```

Ia mesti membuktikan PostgreSQL, Redis/Horizon, COS, OCR, Meili, SMTP, IMAP dan gateway. Jangan guna `--skip-imap` untuk final live gate.

### 20. HTTP/security smoke

- [ ] `/up` SPDM -> 200.
- [ ] `/` -> 200.
- [ ] `/app`/tenant tanpa login -> login, bukan data.
- [ ] `/admin` bukan superadmin -> ditolak.
- [ ] `APP_DEBUG=false`; error tidak memaparkan stack/secret.
- [ ] cookie Secure/HttpOnly/SameSite sesuai.
- [ ] `/horizon` tidak boleh diakses awam tanpa authorization.
- [ ] bucket/object URL langsung tidak public.

## P6. Tenant pilot dan intake sebenar

### 21. Cipta/lulus tenant pilot

1. Daftar dari `/daftar` atau cipta secara terkawal.
2. Superadmin `/admin/mosques` -> `Lulus`.
3. Login admin tenant.
4. Lengkapkan checklist, klasifikasi, ahli dan tetapan retensi.
5. Kekalkan hanya tenant pilot semasa canary.

Evidence wajib: tenant ID/slug, timestamp, operator dan screenshot tanpa secret/PII berlebihan.

### 22. Pair WhatsApp dari SPDM

1. `/app/{tenant}/tetapan-masjid`.
2. Klik `Aktifkan WhatsApp`.
3. Pastikan provisioning berjaya dan tiada `last_error`.
4. Klik `Pasangkan WhatsApp`.
5. Scan QR dari telefon rasmi tenant.
6. Refresh/sync sehingga `connected` dan nombor tepat dipapar.
7. `/ahli-peranan` -> simpan nombor ahli dan `notify_whatsapp`.

Negative gate:

- [ ] API key penuh tidak pernah dipapar dalam HTML.
- [ ] tenant lain tidak boleh melihat/refresh session pilot.
- [ ] session ID salah/wrong API key ditolak.

### 23. WhatsApp dokumen masuk sebenar

1. Dari nombor ahli pilot, hantar teks tepat `spdm` ke nombor rasmi tenant.
2. Pastikan balasan/slot diterima.
3. Dalam 10 minit, hantar satu PDF atau imej.
4. Semak Peti Masuk tenant: satu rekod sahaja, channel WhatsApp, nama fail betul.
5. Tunggu OCR `siap`.
6. Buka `Lihat / OCR` dan cari frasa unik dalam `/carian`.

Negative gate:

- [ ] nombor bukan ahli ditolak/tiada rekod;
- [ ] group/from_me diabaikan;
- [ ] media selepas slot tamat tidak diterima;
- [ ] slot nombor A tidak boleh digunakan nombor B;
- [ ] resend message ID sama tidak mencipta duplicate.

### 24. E-mel dokumen masuk sebenar

1. Tetapan Masjid -> aktifkan intake e-mel.
2. Simpan keyword `spdm` dan allowlist satu sender.
3. Salin alias yang dipaparkan SPDM.
4. Hantar dari sender allowlist, subjek/body mengandungi `spdm`, lampirkan PDF/imej.
5. Tunggu scheduler maksimum beberapa minit.
6. Semak Peti Masuk, OCR dan carian.

Negative gate:

- [ ] sender bukan allowlist ditolak;
- [ ] keyword tiada ditolak;
- [ ] alias/domain lookalike ditolak;
- [ ] tenant disabled/suspended ditolak;
- [ ] attachment duplicate tidak mencipta rekod kedua.

### 25. Muat naik manual, OCR dan carian

Gunakan dua fail yang direkod dalam handover atau dokumen pilot tanpa PII sensitif:

- [ ] upload dari Peti Masuk;
- [ ] status OCR `siap`;
- [ ] teks OCR dipaparkan;
- [ ] `searchable.pdf` boleh preview/download dengan signed URL;
- [ ] frasa unik boleh dicari;
- [ ] hasil klik menuju halaman tenant yang betul.

### 26. Ujian silang tenant production

Cipta tenant kedua khusus smoke dengan user berlainan.

- [ ] cari frasa unik tenant A dari tenant B -> kosong;
- [ ] ubah URL slug/record ID tenant A ketika login tenant B -> 404;
- [ ] signed URL tenant A oleh user tenant B -> 403/404;
- [ ] session/QR WhatsApp tenant A dari tenant B -> ditolak;
- [ ] alias e-mel tenant A tidak masuk tenant B;
- [ ] export/job tenant A tidak mengambil record tenant B;
- [ ] log akses sulit hanya tenant yang betul.

Jika mana-mana item gagal: ini insiden P0. Tutup akses, preserve log, jangan “fix data” manual sebelum punca diketahui.

## P7. Workflow pejabat dan notifikasi

### 27. Klasifikasi dan rekod

- [ ] klasifikasi item Peti Masuk ke fail sedia ada;
- [ ] buka fail baharu dan nombor rujukan unik;
- [ ] pindah fail dengan sebab/audit;
- [ ] ganti versi tanpa memadam sejarah;
- [ ] QR `/r/{ulid}` menuju rekod/Peti Masuk tepat.

### 28. Minit dan kelulusan

- [ ] kerani/setiausaha route minit kepada pengerusi;
- [ ] penerima baca/balas/route/selesai;
- [ ] approval request dibuat;
- [ ] role dibenarkan lulus/tolak;
- [ ] history, nota, IP dan audit wujud.

### 29. Notifikasi WhatsApp keluar

- [ ] penerima opt-in menerima notifikasi pada nombor pivot tenant;
- [ ] penerima opt-out tidak menerima;
- [ ] nombor tenant lain tidak digunakan;
- [ ] tenant tanpa integration gagal secara terkawal/fallback e-mel;
- [ ] status/log penghantaran boleh diaudit tanpa API key.

### 30. Billing, retensi dan pelupusan

- [ ] pesanan storan -> invois -> tandakan dibayar sekali sahaja;
- [ ] invois signed URL authorize tenant;
- [ ] legal hold menghalang pelupusan;
- [ ] sedia batch -> pengerusi lulus -> admin laksana;
- [ ] kegagalan blob tidak menghasilkan status berjaya palsu;
- [ ] sijil dan batu nisan tersedia.

## P8. Backup, restore dan kegagalan terkawal

### 31. Backup sebenar

```bash
docker compose exec app php artisan backup:run
```

- [ ] archive wujud pada disk backup sebenar;
- [ ] checksum/saiz direkod;
- [ ] retention/lifecycle benar;
- [ ] backup tidak boleh dicapai awam.

### 32. Restore drill terpencil

Salin satu backup sebenar ke host dan jalankan:

```bash
./scripts/restore-drill.sh /secure/path/backup-diwan.zip
```

- [ ] restore menggunakan PostgreSQL/container terpencil;
- [ ] jadual teras dan kiraan tenant/rekod/user disahkan;
- [ ] production DB tidak disentuh;
- [ ] `storage/logs/restore-drill-*.log` mengandungi `LULUS restore drill`.

### 33. Failure drill

Kerana tiada staging, jalankan hanya dalam maintenance window dengan operator memantau dan rollback tersedia:

```bash
docker compose exec app php artisan diwan:failure-drill queue --confirm-production
docker compose exec app php artisan diwan:failure-drill cos --confirm-production
docker compose exec app php artisan diwan:failure-drill smtp --confirm-production
```

- [ ] kegagalan dikesan dan dilog;
- [ ] alert/notifikasi operasi sampai;
- [ ] worker/dependency pulih;
- [ ] tiada data pilot hilang.

Jika risiko change window tidak diterima, jangan palsukan Pass; tandakan `Deferred by owner` dengan risiko yang diterima secara bertulis.

## P9. Buka production

### 34. Final review

- [ ] semua P0 Pass;
- [ ] CI release hijau;
- [ ] browser E2E dan role/tenant matrix hijau;
- [ ] backup + restore lulus;
- [ ] gateway dan SPDM health hijau;
- [ ] log 30–60 minit tiada error berulang;
- [ ] operator tahu rollback path;
- [ ] Terma/DPA/retensi diluluskan untuk skop tenant yang dibuka.

### 35. Buka akses berperingkat

1. Keluar maintenance mode tetapi kekalkan `DIWAN_REGISTRATION_OPEN=false`.
2. Benarkan admin/kerani tenant pilot sahaja.
3. Pantau satu kitaran kerja sebenar.
4. Aktifkan role lain selepas pilot stabil.
5. Buka pendaftaran hanya selepas keputusan pemilik.

### 36. Pantauan 24 jam pertama

- Horizon queue depth/failed jobs.
- Scheduler dan `diwan:fetch-mail`.
- OCR duration/failures.
- COS errors dan usage reconciliation.
- Meili indexing/search.
- SMTP/IMAP auth/delivery.
- Gateway session connected, webhook delivery dan 401/403/429 spike.
- Sensitive access logs dan percubaan silang tenant.
- Disk/RAM/swap/CPU.

## Template bukti akhir

Simpan satu salinan bertarikh dalam lokasi operasi yang dipilih:

| Gate | Pass/Fail | Masa MYT | Operator | Evidence/path | Catatan |
|---|---|---|---|---|---|
| Release SHA + CI |  |  |  |  |  |
| PostgreSQL/Redis/Horizon |  |  |  |  |  |
| COS private |  |  |  |  |  |
| OCR real |  |  |  |  |  |
| Meili search |  |  |  |  |  |
| SMTP |  |  |  |  |  |
| IMAP tenant intake |  |  |  |  |  |
| WhatsApp provision/pair |  |  |  |  |  |
| WhatsApp document intake |  |  |  |  |  |
| Cross-tenant negative matrix |  |  |  |  |  |
| Secure download negative |  |  |  |  |  |
| Backup |  |  |  |  |  |
| Restore drill |  |  |  |  |  |
| Rollback ready |  |  |  |  |  |

## Arahan pertama untuk sesi seterusnya

Berikan empat maklumat ini tanpa menghantar password/secret:

1. domain production SPDM;
2. host/IP + user/path SSH;
3. secret store yang akan digunakan;
4. pengesahan bahawa IMAP App Password sudah dimasukkan terus ke secret store/server.

Selepas itu, sesi seterusnya boleh terus bermula pada P1 tanpa mengulangi audit yang telah siap.

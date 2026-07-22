# Handover Log Aktiviti Masjid dan Manual Pengguna

Tarikh: 22 Julai 2026

## Skop perubahan

1. Manual sembilan persona disusun semula dengan aliran tugas bergambar yang jelas: Gambar 1, Gambar 2 dan seterusnya sehingga hasil akhir.
2. Log Aktiviti Masjid append-only ditambah untuk Admin/Kerani, Pengerusi, Setiausaha dan Bendahari.
3. Timeline merekod snapshot supaya sejarah kekal boleh dibaca walaupun tajuk, fail, role atau status asal berubah.
4. Tangkapan manual dan smoke Log Aktiviti menggunakan Chrome sebenar, SQLite latihan dan storan media latihan yang berasingan.

## Kawalan keselamatan

- Semua query halaman Log Aktiviti mewajibkan `mosque_id` tenant semasa.
- Logger menolak `record`, `registry_file` atau `subject` yang mempunyai `mosque_id` tenant lain.
- Model log tidak membenarkan `update` atau `delete`; resource tidak mempunyai route create/edit/delete.
- Hanya role `admin_masjid`, `pengerusi`, `setiausaha` dan `bendahari` mempunyai `activity.view`.
- Bendahari tidak menerima baris log rekod/fail yang gagal skop `Record::visibleTo()` atau `RegistryFile::visibleTo()`.
- Snapshot dipaparkan tanpa memuat relationship polymorphic silang tenant.
- Ujian silang tenant bagi semua lapan konteks role kekal HTTP 404.

## Data log

Jadual `mosque_activity_logs` menyimpan:

- tenant, pelaku, nama/role snapshot, masa, action dan keterangan;
- subject polymorphic untuk korelasi dalaman;
- ID/tajuk/rujukan rekod serta ID/nombor/tajuk fail sebagai snapshot;
- saluran Dashboard, e-mel, WhatsApp atau imbasan;
- identiti pengirim/uploader, alamat IP jika benar-benar tersedia dan metadata JSON peristiwa.

IP e-mel tidak direka daripada alamat pengirim. Ia hanya disimpan apabila pipeline penerimaan membekalkan IP yang boleh dipercayai.

## Katalog peristiwa utama

- Intake: `record_uploaded`, `inbox_spam_deleted`.
- Rekod: `record_classified`, `record_moved`, `record_superseded`.
- Minit: `minit_created`, `minit_replied`, `minit_read`, `minit_recipient_completed`, `minit_completed`.
- Kelulusan/pembetulan: `approval_requested`, `approval_decided`, `record_correction_requested`, `record_correction_reviewed`.
- Fail: `file_opened`, `file_closed`, `file_volume_opened`.
- Fizikal/hibrid: `physical_file_checked_out`, `physical_file_returned`, `physical_file_relocated`.
- Pelupusan: `disposal_requested`, `disposal_approved`, `disposal_executed`.
- Ahli: jemputan, reset kata laluan, pautan log masuk, tetapan WhatsApp, perubahan role dan pengeluaran ahli.
- Storan: permohonan, pengesahan bayaran dan pembatalan pesanan.
- Retensi: Legal Hold dikenakan atau dilepaskan.

## UI dan carian

Halaman `/app/{tenant}/log-aktiviti` menyediakan:

- susunan terkini dahulu dan refresh 30 saat;
- carian teks;
- penapis jenis aktiviti, pelaku, saluran dan julat tarikh;
- lajur masa, pelaku/role, keterangan, rekod, fail, sumber/pengirim dan IP;
- modal Butiran baca sahaja dengan snapshot dan metadata peristiwa.

## Manual pengguna

Folder `Manual Penguna` kekal mempunyai sembilan persona. Bahagian baharu `Cara melaksanakan tugas - gambar demi gambar` menerangkan kesinambungan skrin bagi workflow sebenar. Contoh Admin/Kerani:

1. Papan pemuka.
2. Peti Masuk dan pilih dokumen.
3. Muat naik jika perlu.
4. Isi modal klasifikasi.
5. Sahkan minit di Minit Saya.
6. Sahkan perjalanan dalam Log Aktiviti.

Generator dan capture boleh diulang melalui:

```powershell
php scripts/manual/prepare-manual.php
node scripts/manual/capture-manual.mjs
node scripts/manual/generate-manuals.mjs
node scripts/manual/verify-activity-log.mjs
```

Gunakan `MANUAL_DEMO_PASSWORD`, `MANUAL_BASE_URL`, SQLite latihan dan `DIWAN_STORAGE_DISK=manual`. Jangan jalankan fixture terhadap production.

## Bukti verifikasi tempatan

- PHPUnit: 383 lulus, 1281 assertion, 1 skip tooling.
- Ujian khusus Log Aktiviti: 6 lulus, 39 assertion.
- Pint: lulus.
- Vite production build: lulus.
- Chrome manual: 8 role, 115/115 halaman HTTP 200, 8/8 silang tenant HTTP 404, tiada browser error.
- Chrome Log Aktiviti: empat role dibenarkan HTTP 200 dan modal berjaya; AJK HTTP 403; carian nombor WhatsApp dan e-mel berjaya.
- Manual: 231 PNG, 309 rujukan imej, 0 rujukan hilang.

## Deployment

Migration `2026_07_22_000001_create_mosque_activity_logs_table.php` mesti dijalankan sebelum app baharu menerima trafik. Selepas container app ditukar, force-recreate nginx kerana override production menggunakan upstream container yang boleh bertukar IP. Sahkan `/up`, migration, queue/worker dan halaman role production selepas deploy.

# Pusingan 8 — Codex

**Tarikh:** 2026-08-01 09:55–11:45 MYT  
**Commit diaudit:** 4e07a70  
**Skop:** Chrome channel chrome pada production dan fixture tempatan, desktop/mobile, sembilan role, bantuan/tour, workflow dokumen, viewer, tenant isolation, retensi/pelupusan dan ujian aplikasi.

## Status ejen sebelumnya

Claude menjalankan Pusingan 7 dengan Chrome MCP, tetapi sesi berhenti kerana had sesi pada 10:37 MYT sebelum laporan PUSINGAN-07-CLAUDE.md ditulis. Artifak separa kekal di bukti/pusingan-07 dan tidak dianggap pengesahan lengkap. Pusingan ini menguji semula penemuan yang boleh disahkan.

## Penemuan

### RR-08-01 — Tinggi: tindakan retensi baharu default kepada auto_padam

Borang Cipta Peraturan Retensi (Override) pada /app/mam/retensi-peraturan/create memaparkan amaran pemadaman automatik, tetapi nilai select Tindakan yang sebenar ketika borang baru dibuka ialah auto_padam. Pengguna yang mengisi skop dan menekan Cipta tanpa menyentuh medan tindakan boleh mencipta peraturan pemadaman automatik. Dengan skop kosong, risiko salah faham skop lebih besar.

Bukti: bukti/pusingan-08/local/retention-rule-create.png dan retention-rule-create.json. Kod sumber yang menetapkan default ialah app/Filament/App/Resources/RetentionRules/RetentionRuleResource.php.

Cadangan: default kepada semak, paksa pemilihan eksplisit untuk auto_padam, wajibkan tempoh atau skop jelas, dan minta pengesahan kedua yang menerangkan skop serta tarikh pelupusan.

### RR-08-02 — Sederhana: tour Log Masuk tidak boleh bermula pada kawalan identiti

Pada production, desktop dan mobile, menekan Mulakan Panduan untuk Log Masuk menghasilkan Tindakan belum tersedia dan mesej bahawa sasaran Masukkan identiti tidak kelihatan. Tour tidak boleh maju dari langkah itu.

Bukti: bukti/pusingan-08/public/login-tour-desktop.png dan login-tour-mobile.png.

### RR-08-03 — Sederhana: tour klasifikasi dan modal menghasilkan dua lapisan interaksi

Pada mobile, selepas guide membuka modal klasifikasi, overlay guide berada di atas modal. Butang Seterusnya modal tidak boleh ditekan sehingga pengguna menekan Buat pada skrin pada popover guide. Aliran akhirnya berfungsi dan guide maju, tetapi pengguna boleh menganggap butang modal rosak kerana pointer interception.

Bukti: bukti/pusingan-08/local/mobile-classification-tour-late.png, mobile-classification-modal-sync.png dan workflow-upload-modal-sync.png.

Cadangan: apabila modal sasaran dibuka, auto-minimize guide kepada bar kecil yang tidak melindungi modal, atau beri callout jelas di luar modal.

### RR-08-04 — Rendah: istilah UI dan validation belum konsisten

Wizard klasifikasi menggunakan Seterus berulang kali, bukan Seterusnya. Validation langkah metadata menghasilkan The arah field is required. walaupun keseluruhan UI Bahasa Melayu. Matriks tempatan menjumpai 33 halaman yang mengandungi Seterus.

### RR-08-05 — Rendah/UX: butang navigasi viewer satu halaman tidak disabled

Viewer PDF berfungsi, tetapi pada PDF satu halaman butang Halaman sebelumnya dan Halaman seterusnya kekal aktif. Klik tidak menyebabkan ralat dan halaman kekal 1; disabled state akan mengurangkan kekeliruan.

Bukti: viewer-controls.json, viewer-boundary.json, viewer-pdf-initial.png dan viewer-pdf-controls.png.

## Pengesahan lulus

### Chrome production

- 12/12 route awam HTTP 200 pada desktop dan mobile.
- Tiada page error, console application error atau horizontal overflow pada smoke awam.
- Carian /bantuan untuk daftar masjid mengembalikan hasil desktop dan mobile.
- Registration stepper awam memaparkan tiga langkah tanpa overflow.
- Tour Log Masuk gagal seperti RR-08-02 dan direkodkan sebagai penemuan.

### Chrome fixture tempatan

- Sembilan login role berjaya dengan BrowserContext berasingan: Superadmin, Admin/Kerani, Pengerusi, Setiausaha, Bendahari, Nazir, Ketua Imam, AJK dan Juruaudit.
- 290 page visits role × desktop/mobile direkodkan. 16 status 404 ialah probe silang tenant yang dijangka, bukan page failure. Tiada horizontal overflow.
- Route inventory: Superadmin 12, Admin/Kerani 26, Pengerusi 18, Setiausaha 16, Bendahari 16, Nazir 14, Ketua Imam 14, AJK 14 dan Juruaudit 15 route unik.
- filamentActionModals is not defined yang muncul pada crawler terkumpul diuji semula dalam page/context baharu dan tidak boleh diulang; tidak dilaporkan sebagai bug aplikasi.

### Bantuan dan tour

- Carian role Admin/Kerani untuk nak klasifikasi surat mengembalikan 12 hasil selepas debounce.
- Guide klasifikasi 5 langkah maju sehingga selesai dan sasaran DOM wujud, tetapi beberapa sasaran terlalu generik pada MAIN.
- Guide upload/klasifikasi 20 langkah membuka route serta modal yang betul; Buat pada skrin tidak mengupload atau submit secara automatik.
- Help catalog mempunyai 83 guide; 71 mempunyai rujukan gambar yang wujud dan 12 memang tiada gambar. Gambar yang dipaparkan diuji naturalWidth > 0.
- Borang laporan masalah awam memaparkan kategori, jangkaan, kejadian sebenar, ID request, attachment maksimum 5 MB dan format fail; tiada submission production dibuat.

### Dokumen, fail dan viewer

- Peti Masuk memaparkan tajuk, saluran, sumber/uploader, e-mel/nombor telefon jika tersedia, tarikh-masa upload, antivirus dan OCR.
- Rekod view memaparkan metadata, OCR, lampiran/versi, minit, kelulusan dan audit.
- Viewer PDF sebenar diuji dalam Chrome: render halaman, status halaman, zoom 125% → 150% → 100%, page input, text-find menghasilkan Padanan ditemui pada halaman 1., cetak metadata memanggil print, dan muat turun HTTP 200 application/pdf.
- URL viewer yang sama apabila diminta user tenant man menerima HTTP 404.
- Fixture PDF viewer media id 2 dipadam selepas ujian; custom property audit_fixture=rr8 berbaki 0.

### Workflow dan role sensitivity

- Fail fizikal/hibrid diuji dengan fixture sementara: Medium, rujukan fizikal, lokasi, penjagaan, Keluarkan Fail, Pindah Lokasi, pemegang, lokasi tujuan, tarikh pulang dan catatan. Fixture dikembalikan kepada elektronik dan lokasi kosong.
- Delegasi memaparkan Principal, Delegate, tugas, mula, tamat, sebab/catatan; tiada delegation fixture disimpan.
- Retensi memaparkan status pelupusan, effective schedule, legal hold/export context dan candidate list.
- Pelupusan memaparkan amaran kekal, candidate selector dan pengesahan; tiada batch production disentuh.
- Minit yang ditujukan kepada Pengerusi dan Setiausaha muncul pada penerima yang betul. Nazir yang tidak ditujukan tidak menerima rekod.
- Bendahari melihat rekod kewangan yang dibenarkan; AJK dan Juruaudit tidak melihat rekod kewangan sensitif. Pengerusi melihat skop yang dibenarkan.
- Favourite dan saved search diuji create/delete dan dibersihkan.

### Tenant isolation dan carian

- Probe ID tampering MAM → MAN untuk record, registry file, classification node dan route tanpa tenant semuanya 404: bukti/pusingan-08/local/tenant-id-probes.json.
- Carian local UI yang kosong ialah isu indeks fixture, bukan production failure: database local mempunyai records tetapi index local belum disegerakkan.
- Production Meilisearch dan SearchService diuji baca sahaja; carian Surat mengembalikan hasil untuk tenant/user sah.

### Ujian automated dan smoke

- Feature suite: 46 passed, 545 assertions, meliputi secure download/viewer, authorization, sensitivity, DDMS extended capabilities, guidance/support, activity log dan help catalog.
- Full suite: 409 passed, 1 skipped, 1,804 assertions dalam 83.15 saat.
- php artisan diwan:smoke --slug=audit-rr8-smoke-1125: 9 lulus, 0 gagal untuk daftar, provision 40 nod, invite, ingest, klasifikasi, minit, kelulusan, carian, eksport dan auto-padam/sijil.
- Smoke tenant, tiga user fixture, record, batch dan tiga artefak baharu telah dibuang. Fail tenant 4 yang wujud sebelum smoke dikekalkan; bukti ringkas di smoke-cleanup.json.

### Production SSH health baca sahaja

- SSH ubuntu@43.156.242.188, /opt/diwan, commit 4e07a70.
- App, ClamAV, DB, Meilisearch, Nginx, Redis, scheduler dan worker running; app/ClamAV/DB/Meilisearch/Redis/scheduler/worker healthy.
- /up HTTP 200.
- nginx -t lulus.
- Tiada migration, restart, deploy, data mutation atau config change production dibuat.

## Had pengesahan

- Intake sebenar WhatsApp/e-mel dan notification delivery luar sistem tidak dijalankan kerana memerlukan gateway/akaun production dan akan mengubah data atau menghantar mesej sebenar. Routing dalaman dan logik tenant diuji pada fixture.
- ClamAV production disahkan healthy, tetapi binary upload production tidak dibuat.
- DDoS/volumetric load test tidak dijalankan dari sesi audit ini. Rate-limit dan anti-brute-force perlu disahkan melalui konfigurasi edge/metrics tanpa flood.
- Tiket production SUP-260801-HXQ0DIOL yang dibuat pusingan terdahulu masih memerlukan pemilik menutup atau memadam melalui panel superadmin; protokol melarang mutasi production.

## Cadangan susunan kerja

1. Betulkan RR-08-01 sebelum membenarkan admin membuat override retensi.
2. Betulkan target login guide dan reka semula keadaan minimize apabila modal/action sebenar dibuka.
3. Selaraskan Seterusnya dan translation validation.
4. Tambah automated assertions untuk guide target, default retensi bukan merosakkan, dan overlay tidak menghalang modal.
5. Selepas pembetulan, ulang Pusingan Chrome production serta role matrix tempatan dan minta Claude mengesahkan laporan ini.

## Artifak

Semua screenshot dan JSON Pusingan 8 berada di Audit Review Round Robin/bukti/pusingan-08/production, local dan public. Tiada fail aplikasi tracked diubah.

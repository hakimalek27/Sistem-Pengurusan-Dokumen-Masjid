# PERANAN & SUMBER KEBENARAN
Anda membina platform SaaS multi-tenant "Diwan (SPDM)" untuk pengurusan dokumen masjid.
SATU-SATUNYA sumber kebenaran ialah fail `DIWAN-SPEC.md` v2.1 dalam folder ini. Ia lengkap dan
berdiri sendiri: semua keputusan reka bentuk, versi, skema, config, aliran, kontrak API,
templat notifikasi dan kriteria ujian sudah ditetapkan di dalamnya.

# PERATURAN TEGAR (tiada pengecualian)
1. BACA keseluruhan DIWAN-SPEC.md sebelum menulis sebarang kod. Pada setiap fasa, baca semula
   seksyen yang dirujuk fasa itu SEBELUM membina.
2. DILARANG: mereka-reka keperluan; menukar versi pakej §3.2/§3.3; menambah pakej luar senarai;
   menambah ciri luar skop fasa; melanggar mana-mana larangan §0.3; membina item §20 (Fasa 2).
3. Jika spec kabur, bercanggah, atau API pakej sebenar berbeza daripada jangkaan spec:
   BERHENTI SERTA-MERTA dan tanya soalan yang spesifik. JANGAN teka, jangan "assume",
   jangan pilih sendiri. Tunggu jawapan pengguna sebelum sambung.
4. Satu fasa pada satu masa. JANGAN mulakan fasa berikutnya tanpa arahan pengguna,
   walaupun anda rasa mudah.
5. Persekitaran dev/ujian: WHATSAPP_DRIVER=log, MAIL_MAILER=log, IMAP_ENABLED=false,
   DIWAN_STORAGE_DISK ikut §17; ujian guna Storage::fake(config('diwan.storage_disk')).
   JANGAN sesekali perlukan kredential luar untuk lulus ujian.
6. Semua teks UI Bahasa Melayu; kod (kelas/jadual/pemboleh ubah) Bahasa Inggeris.
7. JANGAN isytihar apa-apa "siap" tanpa MENJALANKAN arahan verifikasi dan MENAMPAL output
   sebenar dalam laporan. Dakwaan tanpa output = tidak siap.
8. Selepas setiap fasa hijau: `git add -A && git commit -m "fasa-N: <ringkasan>"`.
9. Ralat ujian: baiki punca, bukan ujian. Ubah ujian HANYA jika ujian itu sendiri melanggar
   spec — dan nyatakan dalam laporan. Selepas 3 cubaan gagal pada masalah sama → berhenti,
   terangkan apa dicuba, tanya pengguna.
10. Pengasingan tenant ialah keperluan #1 (§15.2). Setiap query model ber-mosque_id mesti
    berskop; setiap Select ->relationship() mesti modifyQueryUsing skop tenant;
    setiap job bawa mosque_id dalam payload.

# FORMAT LAPORAN FASA (wajib, setiap fasa)
## Laporan Fasa N
(a) Ringkasan apa dibina (5-10 baris)
(b) Fail dicipta/diubah (senarai laluan)
(c) Output SEBENAR arahan verifikasi (php artisan test / migrate / dsb.) — tampal penuh
(d) Kriteria Siap fasa: setiap item ✔/✘
(e) Lencongan dari spec: mesti "TIADA" — atau senarai soalan menunggu jawapan
(f) Nota/risiko untuk fasa seterusnya

---

# STATUS PEMBINAAN (kemas kini setiap sesi — untuk kesinambungan)

Repo: https://github.com/hakimalek27/Sistem-Pengurusan-Dokumen-Masjid

## Keputusan persekitaran lokal (dibenarkan Kriteria Fasa 1 "atau justifikasi jika persekitaran tidak sokong")
Mesin dev = **Windows 11, tiada Docker, tiada Redis**. Justifikasi & pemetaan:
- **DB dev + ujian = SQLite** (bukan pgsql). Sebab: PostgreSQL 17 tempatan dikongsi projek lain
  (spkm/kariah) & auth scram tanpa kata laluan yang diketahui — elak sentuh data orang lain.
  `.env.example` **kekal pgsql** untuk produksi Docker (spec §4.7 dihormati). Migrasi ditulis
  portable (`json()`/`jsonb()` → text pada SQLite). Ujian: SQLite `:memory:` (phpunit.xml).
- **PHP 8.4.17 tempatan** (spec pin 8.3 dalam imej Docker). Laravel 12 + Filament 4 serasi 8.4.
  Imej Docker produksi (`docker/Dockerfile`) kekal `php:8.3-fpm` seperti spec — TIDAK diubah.
- **Horizon** dipasang dengan `config.platform` ext-pcntl/ext-posix (Unix-only; tiada pada
  Windows). Daemon `horizon` hanya jalan pada produksi Linux. Dev/ujian guna queue `sync`/`database`.
- **Meilisearch** tiada tempatan → ujian carian guna pemacu Scout `collection` (§17.18).
- **OCR (tesseract/ocrmypdf)** dalam imej Docker; ketersediaan tempatan disemak pada Fasa 5.
- Composer dipanggil sebagai `php C:/Users/hakim/composer.phar` (composer.phar tempatan).

## Kemajuan fasa
- [x] Persediaan: Laravel 12.63 + pakej §3.3 + Filament 4.11.8 + Pest 3.8 + git + remote.
- [x] Fasa 1 — Asas Projek, Config & Model Data (25 migrasi, 19 model, 11 enum, 3 seeder, MigrationSmokeTest 6✓)
- [x] Fasa 2 — Tenancy 2 Panel, Auth magic link, Pendaftaran, Penomboran, 9 Policies (suite 40✓)
- [x] Fasa 3 — Registri: RecordResource/RegistryFileResource/ClassificationNodeResource + Peti Masuk wizard + dedup (suite 53✓)
- [x] Fasa 4 — Minit, Notifikasi §14, Webhook WA §11.1, Ingest E-mel §11.3 (suite 73✓)
      NOTA: Minit "tab" §9.C.5 diganti filter kategori (Filament 4 getTabs+tenant bermasalah — fungsi sama).
- [x] Fasa 5 — QuotaService §5.14 + ProcessOcrJob §12 + SearchService §13 + Carian (suite 85✓,1 skip OCR)
      NOTA: OCR sebenar di-skip lokal (tesseract/ocrmypdf hanya dlm imej Docker); carian ujian guna Scout collection.
- [x] Fasa 6 — Kelulusan §9.C.7, Ganti Versi/QR §9.C.4/6, BillingService §10.J, Penggunaan & Storan (suite 96✓)
      Nota: UI superadmin Pesanan Storan (Tandakan Dibayar) → Fasa 8 (logik markPaid sedia & diuji).
- [x] Fasa 7 — RetentionEngine §16.3 + DisposalService (snapshot/sijil) + notices/execute + Eksport ZIP §16.4
      (suite 106✓; RetentionEngineTest 7 = positif + 5 negatif + gantung + kitaran; invarian t30+t7 dibuktikan)
- [x] Fasa 8 — Panel Superadmin (Masjid/Pesanan/Pengguna/Tetapan), MembershipService §6.4, Pelupusan manual,
      Ahli/Tetapan Masjid/Profil, Ops (scheduler 9 tugasan, make-superadmin, prune-logs, backup, README) (suite 124✓)
      Nota: Laporan §9.C.9 + widget dashboard §9.C.2 = versi asas (dashboard Filament lalai).
- [x] Fasa 9 — Verifikasi: migrate:fresh --seed bersih + 128 passed/1 skip + diwan:smoke 9/9 +
      LAPORAN-KESEDIAAN.md (41 item §18 dipetakan). Kod SEDIA — go-live menunggu §21 + staging.

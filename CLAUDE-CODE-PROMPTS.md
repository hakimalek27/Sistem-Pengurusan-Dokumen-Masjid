# PAKEJ PROMPT CLAUDE CODE — Projek DIWAN (SPDM) v2.1
Padanan: `DIWAN-SPEC.md` v2.1 (7 Julai 2026). Pakej ini memecahkan pembinaan kepada **9 fasa berpagar ujian** supaya Claude Code tidak lari dari plan.

---

## CARA GUNA (untuk Azan)

1. Cipta folder projek kosong. Letak `DIWAN-SPEC.md` di dalamnya.
2. Salin keseluruhan **PROMPT INDUK** di bawah ke dalam fail bernama **`CLAUDE.md`** dalam folder yang sama (Claude Code membacanya secara automatik setiap sesi).
3. Buka Claude Code dalam folder itu. Tampal **FASA 1**. Tunggu laporan.
4. **Peraturan emas anda:** JANGAN beri fasa seterusnya selagi laporan fasa semasa belum menunjukkan semua Kriteria Siap ✔ dan output ujian HIJAU sebenar (bukan dakwaan). Kalau ada ✘ → arahkan "baiki dan lapor semula".
5. Jika Claude Code bertanya → jawab ringkas & tepat. Jika dia cadang lencongan dari spec → tolak melainkan anda sengaja mahu ubah (dan jika ubah, minta dia kemas kini DIWAN-SPEC.md sekali supaya spec kekal sumber kebenaran).
6. Selepas Fasa 9 hijau → ikut §21 spec (tindakan manusia: COS, gateway QR, SMTP, dll.) → staging → live.

⚠️ **Nota jujur:** tiada proses boleh jamin sifar-bug mutlak. Pakej ini menguatkuasakan pagar ujian automatik + 41 item verifikasi manual supaya apa yang lepas ialah perkara yang TERUJI, dan apa-apa masalah ditangkap sebelum live, bukan selepas.

---

## PROMPT INDUK (salin ke `CLAUDE.md`)

```
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
```

---

## FASA 1 — Asas Projek, Config & Model Data

```
FASA 1 daripada 9. Baca dahulu: DIWAN-SPEC.md §0, §1, §3, §4, §5, §7, §8 (dan imbas keseluruhan
fail sekali jika belum).

BINA (= §17 langkah 1, 2, 3, 5):
1. Init projek + pakej TEPAT §3.3 (jangan ubah versi). Salin fail infrastruktur §4:
   docker-compose.yml, docker/Dockerfile, docker/php.ini, docker/nginx.conf, .env.example,
   Caddyfile.example, scripts/rclone-offsite.sh. Sediakan .env dev (pgsql compose,
   WHATSAPP_DRIVER=log, MAIL_MAILER=log, IMAP_ENABLED=false).
2. Config: config/diwan.php, config/roles.php (matriks §6.2 penuh sebagai array),
   config/record_types.php (§8 verbatim), semua Enum §3.4, database/seeders/data/kf_template.php (§7 verbatim).
3. SEMUA migrasi §5 ikut turutan + semua Model + relationships + casts + trait BelongsToMosque
   (§15.2) + HasUlids/Searchable/InteractsWithMedia pada Record + path generator tenant (§5.7)
   + helper peranan User: roleIn()/canIn() (§6.0) + MediaObserver (daftar, logik kuota Fasa 5).
4. Seeders: PlatformSettingSeeder, RetentionRuleSeeder (§16.1), DemoSeeder (§17 langkah 5 —
   local/testing sahaja; 2 masjid mam/man dengan wa_session_id, pengguna semua peranan,
   pengguna dwi-masjid, fail & rekod contoh termasuk rekod backdate ±7 tahun).
5. Ujian smoke: tests/Feature/MigrationSmokeTest.php — semua jadual §5 wujud;
   Mosque::count()==2; config record_types == 17 kunci; config roles == 9 peranan;
   peraturan retensi lalai wujud.

JANGAN BINA LAGI: panel Filament, auth, webhook, UI — itu fasa berikutnya.

KRITERIA SIAP (jalankan & tampal output):
[ ] docker compose up -d --build berjaya (atau justifikasi jika persekitaran tidak sokong,
    dengan alternatif php lokal yang dilaporkan)
[ ] php artisan migrate:fresh --seed bersih tanpa ralat
[ ] php artisan test --filter=MigrationSmokeTest HIJAU
[ ] Larangan §0.3 dipatuhi (sahkan sendiri satu-satu dalam laporan)
Hantar Laporan Fasa 1. BERHENTI.
```

---

## FASA 2 — Tenancy Dua Panel, Pendaftaran, Auth, Penomboran, Policies

```
FASA 2 daripada 9. Baca dahulu: §0.4, §6, §9.A, §9.B (rangka sahaja), §10.I, §5.15, §15.1, §15.2.

BINA (= §17 langkah 4, 6, 7, 8, 9):
1. Dua panel Filament: AdminPanelProvider (/admin, gate is_superadmin, TANPA tenancy) &
   AppPanelProvider (/app, ->tenant(Mosque::class, slugAttribute:'slug'), middleware
   ApplyTenantScopes + EnsureMosqueActive + EnsureUserIsActive; pendaftaran tenant Filament
   DIMATIKAN). Panel boleh kosong isi — struktur & tenancy yang diuji di sini.
2. MosqueProvisioningService + halaman awam / dan /daftar (§9.A, throttle 3/jam/IP,
   2 checkbox pengakuan) + skrin kelulusan/tolak superadmin minimum (§10.I).
3. Auth: magic link penuh (login_tokens, MagicLoginController, luput 15 min, sekali guna,
   hash SHA-256), fallback kata laluan, jemputan, rate limit 5/min, pendaratan ikut §9.A.
4. RecordNumberingService per-tenant (§5.15, lockForUpdate).
5. SEMUA Policies + trait ChecksSensitivity (§6.3) + Gate::before superadmin +
   log akses sulit pada titik §15.4.
6. UJIAN — tulis TenantIsolationTest DULU (merah), kemudian hijaukan:
   RegistrationTest, RecordNumberingTest, SensitivityPolicyTest, TenantIsolationTest
   (kes-kes tepat §18.1–3 & §18.10).

KRITERIA SIAP:
[ ] php artisan test HIJAU penuh (termasuk 4 ujian baharu + smoke)
[ ] Manual: /daftar → lulus di /admin → magic link (mail log) → masuk /app/{slug};
    KF tersalin (kira nod = templat) — tampal bukti ringkas
[ ] Pengguna MAM buka URL rekod MAN → 404 (tunjuk dalam ujian)
[ ] Lencongan: TIADA / soalan
Hantar Laporan Fasa 2. BERHENTI.
```

---

## FASA 3 — Registri Teras: Rekod, Fail, KF, Peti Masuk + Wizard

```
FASA 3 daripada 9. Baca dahulu: §8, §9.C.3, §9.C.4, §9.C.13 (Klasifikasi Fail sahaja),
§10.C, §10.F, §5.14 (baca sahaja — penguatkuasaan penuh Fasa 5).

BINA (= §17 langkah 10, 11):
1. Resources panel masjid: RecordResource (senarai + ViewRecord SEMUA tab §9.C.4 — tab
   Minit/Kelulusan boleh placeholder), RegistryFileResource (+ jilid §10.F + relation manager
   file_access_grants), ClassificationNodeResource. SEMUA Select ->relationship()
   WAJIB modifyQueryUsing skop tenant (§15.2) — semak satu-satu.
2. Peti Masuk: muat naik berbilang (sha256, penanda duplikat skop-masjid), wizard
   Klasifikasikan 3 langkah TEPAT §9.C.3 (borang dinamik dari config §8, buka fail baharu
   dalam wizard, waris sensitiviti max, transaksi enclosure), dialog susulan minit
   (butang sahaja — borang minit Fasa 4), Padam-spam dengan sebab.
3. Ujian: InboxClassifyTest, DedupTest (§18.5–6).

KRITERIA SIAP:
[ ] php artisan test HIJAU penuh
[ ] Manual §18.14, §18.16, §18.24 (jilid), §18.25 (pindah fail + audit) — bukti ringkas
[ ] Borang dinamik memaparkan medan betul untuk sekurang-kurangnya 3 jenis berbeza
    (surat_menyurat / minit_mesyuarat / rekod_kewangan) — screenshot/keterangan
[ ] Lencongan: TIADA / soalan
Hantar Laporan Fasa 3. BERHENTI.
```

---

## FASA 4 — Minit, Notifikasi, Webhook WhatsApp (sesi+kata kunci), Ingest E-mel

```
FASA 4 daripada 9. Baca dahulu: §9.C.5, §11 KESELURUHAN (nota: WhatsApp = sesi per masjid,
kata kunci di sisi gateway — Diwan hanya terima dokumen layak), §14 (templat TEPAT), §10.A/B/E/H.

BINA (= §17 langkah 12, 13, 14, 15):
1. Minit penuh: borang, bebenang, Balas & Edarkan, Tanda Selesai, halaman Minit Saya, badge.
2. Notifikasi: WhatsAppGateway service (driver gateway|log; keluar dengan parameter session
   §11.1) + WhatsAppChannel (resolusi sesi ikut MASJID notifikasi; tiada sesi → skip + log)
   + SendWhatsAppJob retry [30,120] + Telegram channel + mail + SEMUA templat §14 VERBATIM
   + notification_logs + PingGateway + banner + GatewayDownNotification.
3. WhatsAppWebhookController TEPAT §11.1 (HMAC → idempotensi → sesi → aktif/intake/kuota →
   ahli → simpan → ack) + diwan:simulate-whatsapp {session} {phone} {path}.
4. FetchMailJob plus-addressing §11.3 (route slug, dedup skop-masjid, guard IMAP_ENABLED).
5. Ujian: WhatsAppWebhookTest (§18.4 semua kes) + unit penghalaan slug e-mel + SendMinitReminders
   (command + logik; jadual penuh Fasa 8).

KRITERIA SIAP:
[ ] php artisan test HIJAU penuh
[ ] Manual: §18.15 (simulate sesi mam), §18.20, §18.21, §18.37 — tampal log payload
    (ack menyebut nama masjid; templat sepadan §14 aksara demi aksara)
[ ] Bukan-ahli hantar ke sesi man → balasan tolak, TIADA rekod (dalam ujian)
[ ] Lencongan: TIADA / soalan
Hantar Laporan Fasa 4. BERHENTI.
```

---

## FASA 5 — Kuota & Storan (penguatkuasaan), OCR, Carian

```
FASA 5 daripada 9. Baca dahulu: §5.14, §12 (nota: pipeline SUDAH diuji di sandbox — ikut
arahan TEPAT), §13, §9.C.8.

BINA (= §17 langkah 16, 17, 18):
1. QuotaService + MediaObserver (± storage_used_bytes atomik) + penguatkuasaan 3 pintu
   (UI muat naik / webhook WA / ingest e-mel) + notifikasi ambang 80/90/100 +
   ReconcileStorageJob. INGAT: lebih kuota = sekat TULIS sahaja, JANGAN padam data.
2. ProcessOcrJob TEPAT §12 (7 langkah; queue ocr maxProcesses 1; asal TIDAK diubah;
   derived ke prefix tenant; tmp dibersihkan finally).
3. Carian: SearchService::for(user, tenant) — filter mosque_id + sensitiviti DIPAKSA,
   SATU-SATUNYA titik masuk Meili; halaman Carian Livewire §9.C.8; diwan:sync-meili.
4. Ujian: QuotaTest (§18.8), OcrPipelineTest (§18.7 — jalankan dalam container Docker:
   `docker compose exec app php artisan test --filter=OcrPipelineTest` kerana tesseract
   berada dalam imej), ujian isolasi carian (ajk tak nampak sulit; MAM tak nampak MAN —
   nyatakan pemacu Scout yang digunakan dalam suite: meilisearch atau collection).

KRITERIA SIAP:
[ ] php artisan test HIJAU penuh (OcrPipelineTest dalam container — tampal output)
[ ] Manual §18.17 (OCR imbasan sebenar ≤2 min), §18.18 (carian highlight + isolasi),
    §18.19 (log akses sulit), §18.31 (sekat kuota — guna kuota kecil ujian)
[ ] Lencongan: TIADA / soalan
Hantar Laporan Fasa 5. BERHENTI.
```

---

## FASA 6 — Kelulusan, QR & Versi + Bil, Add-on & Halaman Penggunaan

```
FASA 6 daripada 9. Baca dahulu: §9.C.6, §9.C.7, §9.C.10, §10.D, §10.J, §10.K, §5.13.

BINA (= §17 langkah 19, 20):
1. Kelulusan penuh (password confirm + IP + lencana ✔) + QR/dompdf label + /r/{ulid}
   tenant-aware + Ganti Versi (rantai dua hala) + Pindah Fail.
2. BillingService: halaman Penggunaan & Storan §9.C.10 (tolok, pecahan, carta, sejarah) +
   wizard Tambah Storan + Invois PDF bersiri INV-{YYYY}-{seq} + (panel superadmin: skrin
   Pesanan Storan dengan Tandakan Dibayar → storage_addons aktif — boleh minimum, dipenuhkan
   Fasa 8) + ExpireAddonsJob (notis T-30/T-7, luput → kira semula → sekat-tulis jika perlu).
3. Ujian: tambah kes kiraan kuota efektif base+addon+luput ke QuotaTest jika belum.

KRITERIA SIAP:
[ ] php artisan test HIJAU penuh
[ ] Manual §18.22, §18.23, §18.32, §18.33 — bukti (invois PDF terjana & bersiri;
    kuota efektif naik serta-merta selepas Tandakan Dibayar)
[ ] Lencongan: TIADA / soalan
Hantar Laporan Fasa 6. BERHENTI.
```

---

## FASA 7 — Enjin Retensi Automatik & Eksport ZIP

```
FASA 7 daripada 9. Baca dahulu: §16 KESELURUHAN, §10.L, §10.M (kesan gantung pada enjin),
§9.C.12. Ini modul yang MEMADAM DATA — ketepatan mutlak diperlukan.

BINA (= §17 langkah 21):
1. RetentionEngine: kiraan retention_due_at (peraturan efektif §5.11: masjid > platform;
   record_type > prefix panjang > pendek), penyegaran pada failkan/ubah-peraturan/toggle-hold.
2. RunRetentionNotices (07:00; t90/t30/t7 sekali sahaja setiap peringkat, direkod dalam
   retention_notified) + RunRetentionExecute (07:30; SYARAT PENUH §16.3: due + tiada hold +
   action=auto_padam + masjid enabled + status aktif + t30 DAN t7 sudah dihantar) →
   batch kind=auto → snapshot → padam blob SEMUA versi → batu nisan → sijil PDF → notifikasi.
3. Halaman Retensi & Pegangan §9.C.12 (peraturan efektif berlabel sumber, override dengan
   amaran kekal→auto_padam, senarai akan-luput + toggle Legal Hold bersebab).
4. Eksport ZIP: BuildExportZipJob §16.4 (3 skop; metadata.csv + senarai.pdf; pautan 14 hari).
5. Ujian: RetentionEngineTest §18.9 — WAJIB meliputi SEMUA 5 kes negatif + kes positif
   + snapshot & sijil wujud selepas padam + masjid digantung TIDAK dipadam.

KRITERIA SIAP:
[ ] php artisan test HIJAU penuh
[ ] Manual §18.34 (kitaran penuh backdate → 3 notis → execute → batu nisan; kemudian
    4 varian TIDAK-padam) & §18.35 (kandungan ZIP) — tampal bukti
[ ] Sahkan eksplisit dalam laporan: TIADA laluan kod yang memadam tanpa t30+t7 direkod
[ ] Lencongan: TIADA / soalan
Hantar Laporan Fasa 7. BERHENTI.
```

---

## FASA 8 — Panel Superadmin Penuh, Pelupusan Manual, Laporan, Ops & README

```
FASA 8 daripada 9. Baca dahulu: §9.B KESELURUHAN, §10.G, §10.M, §9.C.9, §9.C.11, §9.C.14,
§9.C.15, §4.6, §15.5.

BINA (= §17 langkah 22, 23, 24):
1. Panel /admin penuh §9.B: papan pemuka (semua kad/carta/top-10), MosqueResource
   (kuota-bersebab, Masuk Panel Masjid, Gantung/Aktifkan, Tutup Akaun §10.M),
   Pendaftaran Menunggu, Pesanan Storan (lengkapkan), Peraturan Retensi Lalai,
   Semua Pengguna, Log Notifikasi/Audit Global, Tetapan Platform (+butang uji COS/gateway/TG).
2. Pelupusan MANUAL Aliran G (pengasingan tugas: prepare/approve/execute) + sijil.
3. Laporan §9.C.9 + widget papan pemuka masjid §9.C.2 penuh + Ahli & Peranan §9.C.11
   (sekatan §6.4) + Tetapan Masjid §9.C.14 (termasuk seksyen WhatsApp sesi/kata kunci)
   + Profil §9.C.15.
4. Ops: spatie-backup (destinasi cos_backup, jadual 02:30, notifikasi gagal), pangkas log
   §15.5, scheduler PENUH 8 tugasan §17.24, diwan:make-superadmin, README.md BM
   (naik produksi, deploy, cipta superadmin, nota §21).
5. Ujian: tambah ujian sekatan §6.4 (admin_masjid terakhir tak boleh dibuang) dan
   ujian gantung (EnsureMosqueActive + jeda auto-padam) jika belum diliputi.

KRITERIA SIAP:
[ ] php artisan test HIJAU penuh
[ ] php artisan schedule:list menyenaraikan TEPAT 8 tugasan §17.24 — tampal output
[ ] Manual §18.26, §18.27, §18.28, §18.36, §18.38, §18.39, §18.40 — bukti ringkas
[ ] Lencongan: TIADA / soalan
Hantar Laporan Fasa 8. BERHENTI.
```

---

## FASA 9 — VERIFIKASI PENUH & KESEDIAAN LIVE

```
FASA 9 daripada 9 (fasa verifikasi — TIADA ciri baharu; hanya semak, baiki, buktikan).

LAKUKAN mengikut turutan:
1. `docker compose down -v && docker compose up -d --build` → persekitaran segar →
   `php artisan migrate:fresh --seed`.
2. `docker compose exec app php artisan test` — 10 fail ujian §18 + smoke. Tampal output PENUH.
3. Jalankan SETIAP item manual §18.11 hingga §18.41 SATU-SATU dalam persekitaran ini.
   Hasilkan jadual: No. item | Status ✔/✘ | Bukti satu-baris (output/log/URL).
   Mana-mana ✘ → baiki → ulang item itu (dan ujian automatik berkaitan) sehingga ✔.
   JANGAN langkau, JANGAN tanda ✔ tanpa benar-benar menjalankannya.
4. Smoke E2E berskrip (tulis sebagai arahan artisan diwan:smoke ATAU jujukan manual
   berdokumen): daftar masjid ke-3 → lulus → jemput ahli → simulate-whatsapp sesi baharu →
   klasifikasi → minit → kelulusan → carian jumpa → eksport ZIP → backdate → notis×3 →
   auto-padam → batu nisan + sijil. Tampal jejak.
5. Semakan kesediaan produksi: APP_DEBUG=false dalam .env.example produksi; tiada rahsia
   dalam repo (`git grep` kunci); route:list — webhook ada throttle+HMAC; composer.lock
   dikomit; `php artisan horizon:status`; larangan §0.3 disemak sekali lagi satu-satu.
6. Hasilkan fail `LAPORAN-KESEDIAAN.md`: (a) jadual 41 item + bukti; (b) output suite ujian;
   (c) versi terkunci (php/laravel/filament dari composer.lock); (d) senarai §21 yang
   MENUNGGU manusia sebelum live; (e) sebarang had diketahui (mesti selaras §19 sahaja).
7. Commit "fasa-9: verifikasi penuh — sedia untuk go-live selepas §21".

KRITERIA SIAP:
[ ] 41/41 item §18 ✔ dengan bukti
[ ] Suite ujian HIJAU pada persekitaran segar (output ditampal)
[ ] LAPORAN-KESEDIAAN.md wujud & lengkap
[ ] Pengisytiharan akhir: "Kod SEDIA. Go-live menunggu tindakan manusia §21 + satu larian
    staging." — TIADA pengisytiharan lebih daripada itu.
```

---

## SELEPAS FASA 9 (checklist go-live Azan — luar Claude Code)

1. Selesaikan §21 spec: DNS+Caddy, COS 2 bucket+CAM+lifecycle, Gmail App Password, BotFather,
   **gateway**: /send bersesi + logik kata kunci + webhook HMAC + UI daftar QR per masjid,
   rclone crypt, harga & bank di Tetapan Platform, semakan terma/DPA.
2. Staging: deploy sebenar → ulang item §18.15/17/18/32/34 dengan servis SEBENAR
   (COS, gateway, SMTP) → latihan pemulihan §18.29 SEKALI.
3. Luluskan MAM tenant pertama → padam data demo → live.

# Handover Pembantu Pengguna Diwan

Tarikh: 22 Julai 2026
Katalog panduan: `2026.07.22.1`

## Objektif release

Release ini menambah bantuan dalam aplikasi tanpa menghantar dokumen, metadata tenant atau
pertanyaan pengguna kepada AI luar. Ia merangkumi superadmin, lapan role masjid dan orang awam,
serta mengekalkan semua keputusan kritikal pada pengguna.

## Komponen yang disiapkan

1. Pusat Bantuan role-aware di `/admin/bantuan`, `/app/{tenant}/bantuan` dan `/bantuan`.
2. Katalog JSON berversi di `resources/help/guides.json` sebagai sumber tunggal aplikasi,
   Meilisearch, ujian liputan dan generator manual.
3. Carian bahasa biasa, singkatan, salah ejaan dan istilah DDMS melalui indeks bantuan berasingan,
   dengan fallback PHP apabila Meilisearch tidak tersedia.
4. Tour Driver.js yang menyokong langkah async, modal Livewire, lintas halaman, tutup, sambung,
   selesai dan ulang. Sasaran hilang direkod lalu pengguna dikembalikan ke artikel bantuan.
5. Widget `Apa Perlu Dibuat Sekarang`, deep-link bertapis dan badge tugasan peribadi sahaja.
6. Wizard klasifikasi lima langkah: sumber, jenis/metadata, fail/sensitiviti, minit/edaran dan
   semakan akhir. Penyimpanan kekal satu transaksi atomik.
7. Diagnosis baca sahaja untuk login, upload, antivirus, OCR, kuota, intake, butang, klasifikasi,
   minit, kelulusan dan notifikasi.
8. Tiket sokongan dengan `X-Request-ID`, konteks browser disanitasi dan satu lampiran pilihan
   maksimum 5 MB pada storan private serta imbasan ClamAV.
9. Mod `Lengkap`, `Ringkas` dan `Dimatikan`, snooze satu/tujuh hari, quiet hours dan digest opt-in
   maksimum sekali sehari tanpa mengulang peringatan minit.
10. Analitik agregat bantuan, pengumuman pelengkap dan retensi data automatik.
11. Pendaftaran awam tiga langkah dengan validasi setiap langkah dan sasaran bantuan stabil.
12. Sembilan manual dijana semula dengan kesinambungan gambar; wizard klasifikasi mempunyai lima
    tangkapan berurutan untuk Admin/Kerani dan Setiausaha.

## Privasi dan sempadan tenant

- Indeks `diwan_help_guides` hanya menerima katalog statik version-controlled. Ia tidak menyimpan
  dokumen, metadata rekod, nama pengguna atau data tenant.
- Teks carian mentah tidak disimpan. `help_events` hanya menyimpan hash HMAC, jumlah hasil,
  guide padanan dan enjin carian.
- Pertanyaan tanpa hasil hanya dimasukkan ke tiket apabila checkbox persetujuan dipilih.
- Progress pengguna menggunakan gabungan `user_id` dan `context_key`; konteks tenant turut
  menyimpan `mosque_id`. Progress awam kekal dalam sesi/browser.
- Semua diagnosis tenant memerlukan keahlian tenant semasa. Kiraan rekod sensitif menggunakan
  skop `Record::visibleTo()` apabila hasil boleh mendedahkan akses rekod.
- Tiket Admin/Kerani ditapis dengan `mosque_id` tenant Filament. Superadmin sahaja mempunyai
  paparan global. Pengguna biasa hanya melihat tiket yang dihantarnya sendiri.
- Lampiran tiket disimpan pada disk `local`, bukan direktori awam. Muat turun memerlukan auth,
  policy `view`, kewujudan fail dan respons `private, no-store`.
- Route, query URL, nilai borang, kata laluan, token dan kandungan dokumen tidak ditangkap secara
  automatik dalam laporan.
- Tour tidak mengklik, mengisi atau menghantar tindakan kritikal. Ujian wizard menutup modal tanpa
  submit.
- `support.manage` dan `help.analytics` hanya ditambah kepada role kanonik `admin_masjid`
  (Admin/Kerani). Gate superadmin sedia ada kekal global.

## Skema data

Migration `2026_07_22_000002_create_guidance_and_support_tables.php` menambah:

| Jadual | Tujuan | Retensi |
|---|---|---|
| `guidance_preferences` | mod, auto-start, nudges, digest, quiet hours, snooze | hayat akaun |
| `guidance_progress` | versi guide, langkah dan status tour per konteks | hayat akaun |
| `help_events` | analitik minimum tanpa teks carian | 90 hari |
| `help_announcements` | FAQ/makluman pelengkap superadmin | sehingga dipadam |
| `support_requests` | aduan, konteks sanitasi dan status | 24 bulan |
| `support_attachments` | metadata dan laluan lampiran private | bersama tiket |

`diwan:prune-logs` memadam analitik/tiket luput dan blob lampiran tiket secara bersama.

## Feature flags

```dotenv
DIWAN_GUIDANCE_ENABLED=true
DIWAN_GUIDANCE_NUDGES_ENABLED=true
DIWAN_SUPPORT_ENABLED=true
```

Urutan pelancaran disyorkan:

1. Jalankan migration dan bina aset.
2. Segerakkan indeks bantuan.
3. Aktifkan `DIWAN_GUIDANCE_ENABLED=true`, tetapi nudges boleh dibiarkan `false` untuk canary.
4. Sahkan pusat bantuan, carian, tour dan tiket.
5. Aktifkan nudges/digest selepas health, queue dan log sasaran tour stabil.

## Arahan operasi

```bash
php artisan migrate --force --no-interaction
php artisan diwan:sync-help-index
php artisan diwan:send-guidance-digests
php artisan diwan:prune-logs
```

`diwan:sync-help-index` mengesahkan katalog dahulu. Jika Scout bukan Meilisearch, command berjaya
dengan fallback PHP dan tidak mendakwa indeks luar telah disegerakkan.

## Regenerasi manual

Gunakan pangkalan data SQLite dan storan latihan yang berasingan. Jangan gunakan data production.

```powershell
php scripts/manual/prepare-manual.php
node scripts/manual/capture-manual.mjs
node scripts/manual/sync-help-images.mjs
node scripts/manual/generate-manuals.mjs
```

Pemboleh ubah penting: `MANUAL_BASE_URL`, `MANUAL_DEMO_PASSWORD`,
`MANUAL_ONLY_CLASSIFICATION=1`, `MANUAL_ONLY_PUBLIC=1` dan `MANUAL_RESUME=1`.

## Bukti tempatan

- Pest penuh: 405 lulus, 1 dilangkau, 1,635 assertion, 82.35 saat.
- Ujian akhir katalog/sokongan selepas pembetulan indeks: 12 lulus, 360 assertion.
- Pint: lulus.
- Vite production build: lulus; 63 modul ditransform.
- `npm audit --audit-level=high`: 0 vulnerability.
- `composer validate --strict`: lulus.
- `composer audit --no-dev`: tiada advisory.
- Validasi indeks dengan `SCOUT_DRIVER=collection`: katalog sah dan fallback PHP aktif.
- Chrome matrix: 20 BrowserContext berasingan untuk public, superadmin dan lapan role pada desktop
  serta mobile.
- Chrome desktop: superadmin 12 halaman; Admin/Kerani 25; Pengerusi 17; Setiausaha 15;
  Bendahari 15; Nazir 13; Ketua Imam 13; AJK 13; Juruaudit 14.
- Chrome silang tenant: semua lapan role menerima HTTP 404.
- Tour lifecycle dan public first-use: 2 ujian lulus dalam 1.2 minit selepas patch akhir.
- Wizard klasifikasi: Admin/Kerani desktop dan Setiausaha mobile melalui kelima-lima langkah,
  fokus papan kekunci dan modal fit, tanpa submit.
- Workflow pejabat: klasifikasi ke penerimaan minit Pengerusi, respons, susulan dan kelulusan lulus.
- Manual: 8 role + public, 252 PNG dalam hasil capture, 338 rujukan Markdown dan 0 imej hilang.

Satu ujian `ActualOcrDocumentTest` dilangkau kerana `SPDM_OCR_FIXTURE_1/2` dan istilah padanannya
tidak dibekalkan. Ujian OCR imej sebenar, PDF bertulis, Office extraction dan carian tenant tetap
lulus; skip ini bukan kegagalan pipeline release.

## Pemeriksaan production

- Runtime aplikasi: `2fa18ba` (`feat: tune Malay help search stop words`). Ref rollback sebelum
  release ialah `b8c362a`. GitHub Actions `29891811653` lulus penuh untuk runtime ini selepas dua
  CI pembetulan terdahulu turut lulus.
- Backup `cos_backup` disahkan reachable dan healthy sebelum deploy; terdapat 7 backup dan backup
  terbaharu berumur kurang satu jam ketika semakan akhir.
- Migration `2026_07_22_000002_create_guidance_and_support_tables` telah berjalan dalam batch 7.
- `diwan:sync-help-index --delete` berjaya menyegerakkan tepat 83 guide. Pemeriksaan API sebenar
  menunjukkan `numberOfDocuments=83`, `isIndexing=false`, primary key `document_id`, lapan
  stop-word Melayu aktif dan tiada medan `mosque_id`, `tenant_id` atau `user_id` dalam sampel hit.
- Carian production `nak klasifikasi surat` dijawab terus oleh Meilisearch dengan 11 hasil;
  tiga hasil teratas ialah Peti Masuk, workflow klasifikasi Setiausaha dan Klasifikasi Fail.
- Feature flag efektif dalam container: guidance, nudges dan support semuanya `true`. Digest
  dijadualkan 08:15 tetapi tidak dihantar secara manual; saluran luar kekal opt-in pengguna.
- `app`, `worker`, `scheduler`, PostgreSQL, Redis, ClamAV dan Meilisearch semuanya `healthy`;
  nginx berjalan selepas force-recreate dan `nginx -t` lulus. `diwan:health` memulangkan `OK` dan
  `failed_jobs=0`.
- `/up`, `/`, `/bantuan`, `/app/login` dan `/admin/login` semuanya HTTP 200 dari luar server serta
  mengandungi `X-Request-ID` unik.
- `nginx -T` mengesahkan kadar umum 10 request/saat, auth 5 request/minit, zon connection limit
  dan upstream `fastcgi_pass app:9000`.
- Chrome production menggunakan tepat 20 BrowserContext berasingan pada 1440x1000 dan 390x844.
  Desktop: public 3 halaman, superadmin 12, Admin/Kerani 25, Pengerusi 17, Setiausaha 15,
  Bendahari 15, Nazir 13, Ketua Imam 13, AJK 13 dan Juruaudit 14.
- Semua lapan role menerima 404 apabila mengakses tenant lain pada desktop dan mobile, iaitu 16
  probe silang tenant. Tiada `pageerror`, console error atau overflow mendatar dikesan.
- Empat ujian Chrome production lulus dalam 5.6 minit: matriks role/peranti, tour mula-tutup-
  sambung-selesai-ulang, public first-use dan ikon `?`, serta wizard klasifikasi lima langkah bagi
  Admin/Kerani desktop dan Setiausaha mobile tanpa submit.
- Fixture terpencil menggunakan dua tenant, lapan role, satu superadmin, satu fail dan satu rekod.
  Selepas ujian, baki tepat sifar bagi 2 tenant, 9 pengguna, rekod, progress, preference, event,
  tiket sokongan dan sesi fixture. Tenant `smoke` lama yang bukan milik run ini tidak diubah.
- Semakan akhir log container selama 20 minit tidak menemui error/exception/fatal; event
  `target_missing` sejam terakhir dan failed jobs kedua-duanya sifar.

## Rollback

1. Ref aplikasi sebelum release ini ialah `b8c362a`; runtime baharu yang disahkan ialah `2fa18ba`.
2. Jika rollback diperlukan, checkout `b8c362a` dan bina semula semua servis aplikasi.
3. Force-recreate nginx kerana production bind-mount `docker/nginx-ssl.conf` dan IP upstream app
   boleh berubah selepas container diganti.
4. Tiga feature flag boleh dimatikan segera tanpa rollback migration. Jadual baharu bersifat
   additive dan selamat dibiarkan semasa rollback aplikasi.
5. Jangan jalankan migration `down` pada production kecuali backup telah disahkan dan tiada tiket,
   lampiran atau progress baharu yang perlu dikekalkan.

## Perjalanan isu dan penyelesaian

- Modal klasifikasi asal terlalu panjang. Ia dipecah kepada lima langkah tetapi submit kekal satu
  transaksi supaya rekod separuh siap tidak boleh terhasil.
- Livewire mengitar semula DOM pendaftaran antara langkah. `wire:key` berasingan ditambah supaya
  input dan bantuan tidak bercampur.
- Selector wrapper modal ARIA mempunyai saiz sifar. Capture dan Chrome menggunakan
  `.fi-modal-window:visible`; runtime bantuan menggunakan `data-help-target` sah untuk
  `querySelector` biasa.
- LocalStorage awam pernah berpotensi menahan tour versi baharu pengguna berlog masuk. Penanda itu
  kini hanya digunakan bagi panel public; pengguna sah menggunakan progress DB per konteks.
- Guide klasifikasi mempunyai banyak gambar bagi dua role. Controller kini memilih gambar pertama
  dalam folder role yang betul, bukan menganggap indeks gambar sama dengan indeks role.
- Login berulang tanpa sela mencetuskan had 5/minit yang sah. Semua audit role menggunakan sela
  15 saat; had production tidak dinaikkan.
- CI pertama untuk release ini berhenti di `npm ci` kerana lockfile yang dijana pada Windows tidak
  menyenaraikan dependency bundle WASI `@emnapi/core` dan `@emnapi/runtime`. Lockfile dijana semula
  dalam Node 24 Linux sambil mengekalkan versi dependency, kemudian `npm ci` Linux sebenar, build
  dan audit disahkan sebelum CI diulang.
- Sync canary production mendapati Meilisearch tidak menerima `tenant.dashboard` sebagai primary
  key kerana titik bukan aksara ID yang sah. Indeks ditukar kepada `document_id` SHA-256 yang sah
  dan `guide_id` asal dikekalkan untuk pemetaan akses; pemadaman indeks kini menunggu task selesai.
  Sepanjang pembetulan, carian kekal tersedia melalui fallback PHP dan nudges belum diaktifkan.
- Canary kedua menemui SDK `meilisearch-php` terkunci menyediakan `stats()`, bukan `getStats()`.
  Pemeriksaan jumlah dokumen ditukar kepada API versi sebenar, diuji dan dideploy sebelum indeks
  dibina semula.
- Indeks pada awalnya memulangkan sifar bagi frasa penuh `nak klasifikasi surat` walaupun kata
  utama mempunyai hasil. Kata pengisi Melayu konservatif ditambah sebagai stop-word; frasa yang
  sama kemudian memulangkan 11 hasil terus dari Meilisearch tanpa bergantung pada fallback PHP.

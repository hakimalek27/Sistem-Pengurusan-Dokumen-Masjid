# Handover Regression Pembantu Diwan

Tarikh selesai: 22 Julai 2026, 22:56 MYT

## Skop

Audit ini menyambung kerja Pembantu Diwan yang terhenti dan menyemak semula isu yang dilaporkan:

1. butang `Next` kadang-kadang tidak bergerak;
2. arahan tour tidak cukup jelas;
3. ikon `?` di topbar tidak sesuai;
4. halaman bantuan kelihatan seperti carian tidak berfungsi;
5. tour pendaftaran tersangkut selepas Livewire menukar langkah;
6. risiko sasaran tersembunyi, fokus papan kekunci hilang dan imej bantuan terkena rate limit;
7. sempadan role, tenant dan data production selepas perubahan.

Semakan dibuat pada source, Pest, Vite, Playwright Chrome lokal, CI, server production dan Chrome
production. Tour hanya memandu pengguna; ujian tidak menghantar klasifikasi atau tindakan kritikal.

## Punca dan pembaikan

### Pembaikan utama `6fc1df3`

- `wait_for_user` dalam katalog sebelum ini tidak dihormati oleh JavaScript. Tour kini membezakan
  langkah penerangan, langkah tindakan pengguna dan langkah akhir yang memerlukan tindakan sebenar.
- Driver.js pernah menunggu sasaran dinamik sehingga 10 saat tanpa penjelasan. Ia kini memaparkan
  status menunggu, arahan tindakan dan fallback ke artikel apabila sasaran tidak tersedia.
- Selector generik `page-primary` boleh memilih kawalan yang tidak berkaitan. Sasaran stabil
  `data-help-target` ditambah pada pendaftaran, upload dan wizard klasifikasi.
- Tajuk mentah seperti `Langkah 1` tidak menerangkan tindakan. Runtime kini menghidrat tajuk
  bermakna daripada klausa arahan katalog.
- Duplikasi DOM Livewire boleh menyebabkan elemen tersembunyi dipilih. Resolver kini mengutamakan
  sasaran yang benar-benar kelihatan.
- Fokus boleh kekal pada butang Driver.js yang telah disembunyikan. Fokus kini dipulangkan kepada
  kawalan tindakan sebenar dan status disampaikan secara boleh akses.
- Auto-tour tidak lagi mengganggu modal yang tidak berkaitan. Guard first-use dan lifecycle modal
  diperketatkan.
- Ikon `?` diganti dengan ikon lifebuoy kompak berlabel/tooltip `Bantuan`, diletakkan sebagai
  launcher tetap dan bukan bersebelahan `Log keluar`.
- Pusat bantuan kini memaparkan status carian sebenar, cadangan, butang kosongkan dan sempadan
  public/internal yang jelas. Carian public tidak membocorkan guide dalaman.
- Route imej bantuan dipisahkan kepada limiter `public-help-images` 180/minit. Ujian regresi
  mengesahkan 61 permintaan berturut-turut tidak menerima 429.
- Wizard klasifikasi dikekalkan lima langkah, responsif dan atomik, dengan sasaran stabil serta
  ringkasan kesan sebelum submit.

### Patch Livewire `00775ec`

Chrome production menemui satu regression yang tidak muncul semula secara konsisten di lokal:
selepas pengguna menekan `Seterusnya` pada pendaftaran, Livewire telah memaparkan langkah ketiga
tetapi tour kekal pada `2 daripada 4` dan banner menunggu.

`resources/js/help.js` kini menggunakan dua mekanisme serentak ketika menunggu sasaran langkah
berikutnya:

- `MutationObserver` untuk perubahan DOM biasa;
- poll 120 ms yang dihentikan sebaik sahaja sasaran kelihatan, sebagai fallback apabila Livewire
  selesai mengganti DOM di antara delivery cycle observer semasa popover Driver.js tersembunyi.

Poll, observer dan timer semuanya dibersihkan apabila tour bergerak, ditutup atau dimusnahkan.
Pemeriksaan indeks aktif dibuat secara defensif supaya callback lama tidak menggerakkan tour yang
telah berubah.

## Fail utama

- `resources/js/help.js`
- `resources/css/help.css`
- `resources/views/livewire/help-launcher.blade.php`
- `resources/views/livewire/help-center.blade.php`
- `app/Livewire/HelpCenter.php`
- `app/Services/HelpCatalog.php`
- `resources/help/guides.json` (`2026.07.22.2`)
- view pendaftaran, upload dan wizard klasifikasi yang mempunyai `data-help-target`
- `app/Providers/AppServiceProvider.php` dan `routes/web.php`
- `tests/Feature/PublicPagesTest.php` dan suite bantuan/tenant berkaitan
- `e2e/guidance.spec.js` serta fixture workflow E2E

## Bukti lokal dan CI

- Pest penuh selepas patch: 409 lulus, 1 dilangkau, 1,804 assertion, 73.05 saat.
- Skip tunggal ialah `ActualOcrDocumentTest` kerana `SPDM_OCR_FIXTURE_1/2` luar tidak dibekalkan.
  Ujian OCR imej, PDF bertulis, Office extraction dan carian tetap lulus.
- Playwright Chrome bantuan bukan matrix: 7/7 lulus dalam 1.8 minit.
- Regression tour pendaftaran yang sama: 1/1 lulus di lokal selepas patch.
- Vite production build: 63 modul dan bundle akhir `help-pJkQNpPs.js`.
- Pint dan `git diff --check`: lulus.
- `composer validate --strict`: lulus; `composer audit --no-dev`: tiada advisory.
- `npm audit`: 0 vulnerability.
- Katalog: 83 guide, 473 langkah, 15 jenis sasaran dan 0 fail imej hilang.
- Generator manual menghasilkan semula sembilan manual tanpa perubahan tidak dijangka.
- Commit pembaikan utama: `6fc1df360207bbb0016f4206679aa1b8a9839b5a`.
- Commit patch Livewire: `00775ec2a3c43b1944f79213638324e365a15db4`.
- GitHub Actions `29928198795`: lulus. Job PostgreSQL/Redis/Meili/OCR/tests, Docker web
  dan Docker app semuanya berjaya.

## Deployment production

- Server: `/opt/diwan` pada `ubuntu@43.156.242.188`.
- Backup COS disahkan healthy sebelum deploy; lapan backup tersedia ketika semakan pra-deploy.
- Migration guidance/support telah berada pada batch 7 dan deploy ini memulangkan
  `Nothing to migrate`.
- Deploy awal melalui skrip berpaip terhenti selepas `docker compose run` kerana command itu
  mengambil stdin skrip. Ia dibetulkan menggunakan `docker compose run --rm -T app`; tiada
  migration separuh jalan berlaku.
- Selepas checkout `00775ec`, source server telah betul tetapi HTML luar masih merujuk bundle lama
  `help-B9Yf2GlM.js`. Pemeriksaan container membuktikan image app/nginx masih membawa asset lama.
- App dan web dibina semula dengan `--no-cache`, menghasilkan `help-pJkQNpPs.js`. App, worker,
  scheduler dan nginx kemudian di-force-recreate. Pemeriksaan HTTPS cache-busting mengesahkan
  bundle 32,173 bytes dan signature `window.setInterval(...,120)` dilayan dari luar.
- Production bind-mount `docker/nginx-ssl.conf` dikekalkan. `nginx -t` lulus dan `nginx -T`
  mengesahkan general limit 10 request/saat, auth 5/minit, connection limit dan
  `fastcgi_pass app:9000`.
- Route imej menggunakan middleware `public-help-images`; index disegerakkan semula kepada tepat
  83 guide.
- `/up`, `/`, `/bantuan`, `/app/login` dan `/admin/login` memulangkan HTTP 200 dari luar.
- `diwan:health` ialah `OK`, Horizon berjalan, failed jobs kosong dan semua app/worker/scheduler,
  PostgreSQL, Redis, Meilisearch serta ClamAV healthy.

## Chrome production akhir

Playwright menggunakan Chromium dengan `channel: chrome`, headless, domain sebenar
`https://bakwim.my` dan credential fixture sementara. Ini bukan anggaran HTTP sahaja.

- Matrix: 1/1 lulus dalam 5.0 minit dan mencipta tepat 20 BrowserContext berasingan.
- Desktop: public 3 halaman, superadmin 12, Admin/Kerani 25, Pengerusi 17, Setiausaha 15,
  Bendahari 15, Nazir 13, Ketua Imam 13, AJK 13 dan Juruaudit 14.
- Mobile: public 3 halaman; superadmin dan setiap lapan role memeriksa halaman responsif dalam
  context berasingan.
- Semua lapan role menerima 404 bagi tenant lain pada desktop dan mobile: 16 probe silang tenant.
- Tour mula/tutup/sambung/selesai/ulang, carian role-aware dan fallback imej: 3/3 lulus,
  39.8 saat.
- First-use public, regression Livewire pendaftaran, tour klasifikasi dan wizard lima langkah
  desktop/mobile tanpa submit: 4/4 lulus, 49.5 saat.
- DDMS read-only smoke bagi dashboard, carian, kegemaran, delegasi, pembetulan, peti masuk,
  rekod dan registry: 1/1 lulus, 8.2 saat, termasuk cross-tenant 404.
- Tiada page error, console error atau overflow yang tidak dijangka dalam matrix.

## Cleanup dan keadaan akhir

Fixture unik `gdr-e2e645` menggunakan dua tenant, lapan role, satu superadmin, satu fail registry
dan satu rekod peti masuk. Semua notifikasi luaran dimatikan.

Cleanup menghasilkan:

- 2 tenant dipadam;
- 9 pengguna dipadam;
- baki tenant, pengguna, rekod, fail, help row dan session semuanya 0;
- pemeriksaan DB tambahan: `fixture_mosques=0`, `fixture_users=0`;
- tenant operasi lama `smoke` kekal 1 dan tidak disentuh;
- helper container, helper lokal dan fail credential sementara dipadam.

Post-cleanup: health `OK`, Horizon berjalan, failed jobs kosong, index 83 guide dan tiada padanan
`ERROR`, `CRITICAL`, upstream failure atau HTTP 5xx dalam log container 25 minit terakhir.
Empat fail operasi server yang telah sedia ada (`.env.bak.*` dan `docker-compose.override.yml`)
tidak diubah atau dipadam.

## Rollback

1. Parent sebelum patch Livewire ialah `6fc1df3`; gunakan ref ini hanya jika patch transition perlu
   diundur.
2. Bina semula app dan web, kemudian force-recreate app, worker, scheduler dan nginx. Pemeriksaan
   bundle luar wajib dilakukan kerana source Git yang betul tidak membuktikan asset image aktif.
3. Jalankan `diwan:sync-help-index --delete`, `diwan:health`, `horizon:status`, `queue:failed` dan
   `nginx -t` selepas rollback.
4. Tiga feature flag guidance/nudges/support boleh dimatikan untuk mitigasi segera tanpa rollback
   migration; jadual migration bersifat additive.
5. Ref sebelum keseluruhan release guidance dan arahan rollback skema kekal direkod dalam
   `HANDOVER-2026-07-22-PEMBANTU-DIWAN.md`.

## Baki risiko diketahui

- Tiada kegagalan belum selesai dalam skop ujian bantuan, role, tenant dan workflow production di
  atas.
- Dua dokumen OCR luaran khusus belum dapat disahkan kerana fixture dan istilah padanannya tidak
  dibekalkan. Ini direkod sebagai skip terkawal, bukan dianggap lulus.
- Tiada MCP Chrome berasingan tersedia dalam toolchain sesi ini. Pengesahan browser sebenar dibuat
  dengan Playwright `channel: chrome`, BrowserContext terasing dan domain production.

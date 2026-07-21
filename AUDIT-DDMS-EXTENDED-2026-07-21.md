# Audit dan Release DDMS Lanjutan - 21 Julai 2026

## Keputusan

Fungsi DDMS lanjutan telah dilaksanakan, diuji pada DB buangan melalui Chrome sebenar,
dipush dan dideploy ke `https://bakwim.my`. Imej aplikasi production dibina daripada
commit `9579897`; commit induk ciri ialah `f2fcc75`.

Semua kawalan baharu menggunakan `mosque_id`, policy dan query visibility sedia ada.
Tiada route baharu yang membenarkan pengguna memilih tenant atau objek melalui ID tanpa
semakan authorization semula pada server.

## Fungsi yang dilaksanakan

### 1. Carian tersimpan, kegemaran dan carian lanjutan

- Carian boleh disimpan per pengguna dan per masjid, ditetapkan sebagai lalai, dimuat
  semula dan dipadam. Pengguna lain dalam tenant sama tidak boleh melihat carian itu.
- Kegemaran menyokong rekod dan fail. Objek diselesaikan semula melalui `visibleTo()`;
  favourite tidak boleh digunakan sebagai jalan pintas kepada rekod sulit atau tenant lain.
- Filter baharu: jenis rekod, fail, arah, sensitiviti, status, saluran, pengirim/organisasi,
  rujukan, penerima, julat tarikh rekod dan julat tarikh terima.
- Carian tanpa kata kunci kini sah untuk kegunaan filter metadata sahaja. Hasil kekal
  terhad kepada 500 rekod yang pengguna dibenarkan lihat.

### 2. Viewer dokumen khusus

- Signed URL 30 minit dan policy rekod diperiksa semula pada setiap akses viewer.
- PDF: halaman sebelumnya/seterusnya, nombor halaman, zoom, pencarian teks dan cetakan
  metadata. Imej: zoom dan cetakan metadata.
- Media asal, derived dan lampiran sahaja dibenarkan; MIME selain PDF/imej mendapat 404.
- Muat turun masih menggunakan signed URL 5 minit. Akses rekod sulit direkodkan.
- PDF.js dan worker dibundle secara lokal; tiada dokumen dihantar ke CDN/pihak ketiga.

### 3. Pembetulan rekod salah tawan

- Pengguna yang boleh melihat rekod boleh memohon pembetulan dengan sebab dan perubahan
  sebenar. Rekod asal tidak berubah sehingga reviewer berautoriti meluluskan.
- Medan yang boleh dibetulkan di-whitelist dan divalidasi. Reviewer lain sahaja yang
  menerima tugasan jika mempunyai kebenaran update rekod berkenaan.
- Keputusan menggunakan row lock, status sekali-putus, audit sebelum/cadangan/keputusan,
  kira semula retensi dan reindex carian.

### 4. Principal/delegate

- Delegasi terhad kepada `minit` dan `approvals`, mempunyai waktu mula/tamat, status aktif
  dan sebab. Ia bukan impersonation akaun dan tidak memberi akses umum kepada data principal.
- Principal biasa hanya boleh mewakilkan dirinya; pentadbir ahli boleh mengurus principal
  dalam tenant. Delegate tidak boleh membatalkan mandat principal.
- Keputusan approval menyimpan `decided_by` sebenar dan `on_behalf_of`. Tindakan minit
  menyimpan `acted_by_user_id` dan `acted_on_behalf_of_user_id`.
- Delegate tetap mesti boleh melihat rekod. Delegasi tenant lain atau rekod yang tidak
  kelihatan tidak membuka authorization.

### 5. Katalog, fail fizikal dan hibrid

- Katalog meningkat daripada 17 kepada 33 jenis rekod, termasuk agenda/keputusan mesyuarat,
  polisi, prosedur, bajet, bank, aset, perolehan, permit, aduan dan audio/video.
- Fail mempunyai medium elektronik/hibrid/fizikal, rujukan fizikal, lokasi, status kustodi,
  pemegang, tarikh perlu pulang dan sejarah pergerakan append-only.
- Checkout/pulangan/pindah lokasi menggunakan row lock. Pemegang ahli wajib aktif dalam
  tenant yang sama; nama luar boleh digunakan sebagai alternatif. Pulangan tanpa checkout
  dan double checkout ditolak pada service, bukan UI sahaja.

### 6. Antivirus dan provenance inbox

- Semua intake melalui `InboxIngestService` (UI, e-mel dan WhatsApp) diimbas ClamAV sebelum
  DB/media ditulis. Production menggunakan `CLAMAV_ENABLED=true` dan fail-closed.
- TCP INSTREAM menangani partial write, timeout, daemon terputus dan respons kosong. Fail
  berjangkit ditolak dengan signature; kegagalan scanner juga menolak intake.
- Container `clamav/clamav:1.4` mempunyai volume signature persisten, health scan sebenar
  dan port 3310 hanya rangkaian Docker (`3310/tcp: null` pada host).
- Peti Masuk dan halaman rekod memaparkan saluran, alamat e-mel atau nombor WhatsApp,
  nama/e-mel/no. WhatsApp uploader UI, tarikh serta masa upload, status antivirus dan signature.

## Bukti ujian

| Gate | Keputusan |
|---|---|
| Pest penuh selepas semua fix | 376 lulus, 1 skip, 1247 assertions |
| Ujian capability/viewer targeted | 16 lulus, 57 assertions |
| Ujian WhatsApp selepas buang warning PSR-4 | 6 lulus, 15 assertions |
| DB `migrate:fresh --seed` | Lulus termasuk migration baharu |
| Chrome workflow pejabat DB bersih | 2/2 lulus: klasifikasi+minit dan minit+approval |
| Chrome capability baharu | 2/2 lulus: carian/saved/favourite dan provenance/pembetulan |
| Chrome sembilan peranan | Semua sidebar terlihat 200; silang tenant MAN 404 |
| Chrome production read-only | 1/1 lulus untuk 8 halaman baharu/teras; silang tenant 404 |
| Viewer authorization | Signed viewer 200; tenant luar 404; metadata cetakan render |
| Vite/Node 22 production build | Lulus; PDF.js worker dibundle |
| Pint dan `git diff --check` | Lulus |
| Composer audit | 0 advisory selepas Guzzle 7.15.1 |
| npm audit | 0 vulnerability |

Skip tunggal ialah OCR dokumen sebenar kerana `SPDM_OCR_FIXTURE_1/2` tidak dibekalkan.
OCR unit/integration sedia ada tetap lulus.

## Bukti production

- Migration `2026_07_21_000001_expand_ddms_capabilities` berstatus `Ran`.
- Lapan container hidup; app, worker, scheduler, DB, Redis, Meilisearch dan ClamAV healthy.
- `diwan:health` = `OK`; `diwan:smoke --slug=smoke` = 9 lulus, 0 gagal.
- Failed queue kosong; jadual fetch-mail dan semua tugasan lain hadir.
- Scanner sebenar: teks bersih = `clean`; EICAR = `infected`, signature
  `Eicar-Test-Signature`. Tiada kandungan ujian disimpan sebagai rekod.
- `/up`, `/app/login` dan `/admin/login` = 200. HSTS setahun, nosniff, frame policy dan
  permissions policy hadir. `nginx -t` lulus.
- Hash manifest app dan nginx sama:
  `32482ef478aa5ebbb3e09a2c522098f68f9ea5c01580d736989ad039c59d1acd`.
- Log 15 minit selepas deploy: tiada fatal, panic, OOM, SQLSTATE, 502 atau 503.
- Docker build cache tidak digunakan sebanyak 11.03 GB dibersihkan. Disk turun daripada
  82% (5.1 GB lapang) kepada 46% (16 GB lapang); image/volume aktif tidak disentuh.

## Masalah semasa journey dan penyelesaian

1. Healthcheck awal ClamAV salah mentafsir `--wait 5` sebagai fail bernama `5`.
   Penyelesaian akhir ialah scan kecil `/etc/hostname`; container kini benar-benar healthy.
2. `pdfjs-dist 6` memerlukan Node sekurang-kurangnya 22.13, sedangkan Docker menggunakan
   Node 20. Docker dinaikkan ke Node 22 dan lockfile dijana semula dengan npm 10 supaya
   `npm ci` local serta Docker deterministik.
3. Composer audit menemui empat advisory Guzzle medium yang diterbitkan 20 Julai.
   Guzzle dinaikkan 7.13.2 ke 7.15.1 bersama promises/PSR-7; audit selepas itu bersih.
4. E2E inbox menggunakan satu fixture mutable. Rerun tanpa reset gagal kerana test pertama
   telah memfailkan item itu. DB direset dan suite penuh kemudian lulus 2/2.
5. Satu larian Chrome mengeluarkan `ERR_NO_BUFFER_SPACE` pada mesin Windows yang mempunyai
   banyak proses Chrome. Rerun bersih yang sama lulus semua sembilan peranan tanpa JS error.
6. Warning PSR-4 helper ujian WhatsApp dibuang dengan anonymous notification factory.

## Batas dan cadangan seterusnya

- ClamAV hanya mengimbas intake baharu. Jalankan job backfill berjadual jika semua media
  sejarah juga perlu discan dan dikuarantin.
- VM mempunyai RAM 1.9 GB + swap 3 GB. Selepas warm: ClamAV kira-kira 222 MB, worker
  428 MB, RAM available kira-kira 297 MB dan swap digunakan kira-kira 1 GB. Naik taraf
  kepada sekurang-kurangnya 4 GB RAM sebelum volum intake besar atau OCR serentak.
- Tambah notifikasi fail fizikal overdue dan laporan chain-of-custody CSV/PDF.
- Tambah perkongsian saved search secara eksplisit dengan ACL jika organisasi memerlukannya;
  keadaan kini sengaja private per pengguna.
- Pencarian teks viewer bergantung text layer PDF. Untuk scan imej, gunakan PDF derived OCR.
- Tiada serangan volumetric DDoS dilakukan pada production. Nginx/Cloudflare/rate limiter
  disahkan, tetapi ujian beban perlu dibuat dalam staging dan origin perlu kekal firewall-only.

## Operasi dan rollback

- Deploy: build app+nginx, migrate, recreate app/worker/scheduler, kemudian force-recreate
  nginx untuk mengelakkan IP FastCGI lama.
- Backup konfigurasi sebelum ClamAV: `/opt/diwan/.env.bak.pre-clamav-f2fcc75`.
- Untracked operasi yang dikekalkan: `.env.bak.1784338951`, backup env baharu dan
  `docker-compose.override.yml`.
- Rollback aplikasi: checkout release sebelumnya `82bc74b`, rebuild app+nginx dan recreate
  semua runtime. Migration mempunyai `down()`, tetapi jangan rollback schema selepas data
  capability baharu digunakan tanpa backup DB.

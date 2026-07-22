# Manual Pengguna Diwan - Admin / Kerani

**Versi UI disahkan:** 22 Julai 2026

**Tenant contoh:** MAM (data latihan, bukan production)

**Liputan Chrome:** 22/22 halaman, silang tenant HTTP 404, 25 skrin tindakan tambahan.

Manual ini khusus untuk role **Admin / Kerani**. Gambar menggunakan data latihan. Nama, e-mel, nombor telefon dan dokumen sebenar organisasi tidak patut dimasukkan ke manual.

## 1. Skop dan had role

**Tanggungjawab:** Mengurus operasi registri dan pentadbiran masjid. Peranan ini ialah gabungan rasmi Admin dan Kerani.

**Dibenarkan:** Peti masuk, klasifikasi, rekod, fail, minit, permohonan kelulusan, klasifikasi fail, retensi, pelupusan persediaan/pelaksanaan, ahli, tetapan masjid, storan, Log Aktiviti Masjid dan log akses sulit.

**Had penting:** Tidak membuat keputusan kelulusan dokumen dan tidak meluluskan batch pelupusan sendiri. Kelulusan itu kekal tugas Pengerusi/Nazir atau Pengerusi bagi pelupusan.

Jika butang tidak kelihatan, itu lazimnya sekatan role, status, sensitiviti atau tenant. Jangan cuba memintas melalui URL.

## 2. Log masuk

![Log masuk Admin / Kerani](<imej/00-log-masuk.png>)

**Nombor pada gambar**
1. Masukkan e-mel atau nombor telefon yang didaftarkan.
2. Masukkan kata laluan sendiri. Jangan kongsi dengan orang lain.
3. Tekan Log masuk selepas kedua-dua medan lengkap.
4. Gunakan pautan selamat jika terlupa atau belum menetapkan kata laluan.

### Log masuk dengan kata laluan

1. Buka `https://bakwim.my/app/login`.
2. Masukkan e-mel **atau** nombor telefon yang didaftarkan untuk akaun sendiri.
3. Masukkan kata laluan; pastikan Caps Lock dan susun atur papan kekunci betul.
4. Tekan **Log masuk** sekali dan tunggu dashboard tenant.
5. Sahkan nama masjid. Jika masjid salah atau anda tidak mengenalinya, log keluar dan lapor kepada Admin/Kerani.

### Log masuk melalui pautan selamat

1. Buka `https://bakwim.my/log-masuk`.
2. Masukkan e-mel atau nombor telefon berdaftar dan tekan **Hantar Pautan Log Masuk**.
3. Semak e-mel/WhatsApp. Respons sistem sengaja tidak mendedahkan sama ada akaun wujud.
4. Buka pautan dalam masa 15 minit. Pautan hanya sekali guna.
5. Jika tamat tempoh, minta pautan baharu; jangan kongsi atau forward pautan.

### Jika gagal

- Jangan cuba berulang kali kerana perlindungan brute-force/rate-limit boleh mengunci percubaan sementara.
- Gunakan pautan selamat atau minta Admin/Kerani hantar semula pautan.
- HTTP 403 bermaksud tindakan tidak dibenarkan; HTTP 404 juga digunakan untuk menyembunyikan tenant/rekod yang bukan milik anda.
- Jangan hantar screenshot kata laluan, token atau pautan sekali guna kepada sesiapa.

## 3. Cara melaksanakan tugas - gambar demi gambar

Bahagian ini menerangkan kesinambungan gambar untuk satu tugas lengkap. **Gambar 1** ialah titik mula workflow, diikuti **Gambar 2**, **Gambar 3** dan seterusnya sehingga hasil akhir disahkan.

### 3.1 Log masuk dan sahkan masjid

**Hasil akhir:** Pengguna masuk ke tenant yang betul sebelum membuka atau mengubah sebarang rekod.

Ikuti gambar mengikut nombor. Jangan lompat ke gambar seterusnya sehingga langkah gambar semasa selesai.

#### Gambar 1: Halaman log masuk

![Log masuk dan sahkan masjid - Gambar 1](<imej/00-log-masuk.png>)

**Apa perlu dibuat pada Gambar 1**
1. Masukkan e-mel atau nombor telefon akaun sendiri.
2. Masukkan kata laluan sendiri.
3. Tekan Log masuk sekali dan tunggu sehingga URL tenant dipaparkan.

**Kemudian:** teruskan ke **Gambar 2: Papan pemuka tenant**.


#### Gambar 2: Papan pemuka tenant

![Log masuk dan sahkan masjid - Gambar 2](<imej/01-dashboard.png>)

**Apa perlu dibuat pada Gambar 2**
1. Semak nama dan kod masjid pada panel.
2. Semak role serta statistik yang dipaparkan.
3. Jika masjid salah, jangan teruskan; log keluar dan laporkan kepada Admin/Kerani.

**Selesai:** semak hasil akhir workflow ini sebelum menutup halaman.


### 3.2 Muat naik, semak dan klasifikasikan dokumen serta hantar minit

**Hasil akhir:** Dokumen keluar daripada Peti Masuk, mendapat nombor fail/kandungan yang betul dan penerima tindakan menerima minit.

Ikuti gambar mengikut nombor. Jangan lompat ke gambar seterusnya sehingga langkah gambar semasa selesai.

#### Gambar 1: Mulakan dari Papan pemuka

![Muat naik, semak dan klasifikasikan dokumen serta hantar minit - Gambar 1](<imej/01-dashboard.png>)

**Apa perlu dibuat pada Gambar 1**
1. Sahkan tenant MAM/data masjid sendiri.
2. Pada menu kiri, tekan Peti Masuk.

**Kemudian:** teruskan ke **Gambar 2: Senarai Peti Masuk**.


#### Gambar 2: Senarai Peti Masuk

![Muat naik, semak dan klasifikasikan dokumen serta hantar minit - Gambar 2](<imej/13-peti-masuk.png>)

**Apa perlu dibuat pada Gambar 2**
1. Semak sumber, pengirim, tarikh dan masa diterima.
2. Semak Antivirus, OCR dan amaran Duplikat.
3. Untuk upload baharu tekan + Muat Naik Dokumen; untuk dokumen sedia ada pilih baris yang hendak diproses.

**Kemudian:** teruskan ke **Gambar 3: Muat naik dokumen**.


#### Gambar 3: Muat naik dokumen

![Muat naik, semak dan klasifikasikan dokumen serta hantar minit - Gambar 3](<imej/inbox-muat-naik.png>)

**Apa perlu dibuat pada Gambar 3**
1. Pilih atau seret fail yang dibenarkan.
2. Tunggu upload selesai dan toast berjaya.
3. Kembali ke Peti Masuk; jangan klasifikasikan sebelum antivirus/OCR dan sumber disemak.

**Kemudian:** teruskan ke **Gambar 4: Klasifikasi peti masuk**.


#### Gambar 4: Klasifikasi peti masuk

![Muat naik, semak dan klasifikasikan dokumen serta hantar minit - Gambar 4](<imej/inbox-klasifikasi.png>)

**Apa perlu dibuat pada Gambar 4**
1. Tekan Klasifikasikan pada dokumen yang tepat.
2. Isi Jenis Rekod, Tajuk, Arah, Ruj. Kami/Ruj. Tuan, tarikh, pengirim, penerima dan u.p.
3. Pilih Failkan Ke; jika perlu buka fail baharu pada nod yang betul.
4. Pilih penerima tindakan, s.k., arahan dan keutamaan.
5. Tekan Klasifikasikan dan catat nombor fail(kandungan) pada toast.

**Kemudian:** teruskan ke **Gambar 5: Sahkan minit diedarkan**.


#### Gambar 5: Sahkan minit diedarkan

![Muat naik, semak dan klasifikasikan dokumen serta hantar minit - Gambar 5](<imej/16-minit-saya.png>)

**Apa perlu dibuat pada Gambar 5**
1. Buka Minit Saya.
2. Pilih kategori Saya Hantar.
3. Sahkan rekod, penerima, arahan, keutamaan dan tarikh akhir sepadan.

**Kemudian:** teruskan ke **Gambar 6: Sahkan perjalanan dalam Log Aktiviti**.


#### Gambar 6: Sahkan perjalanan dalam Log Aktiviti

![Muat naik, semak dan klasifikasikan dokumen serta hantar minit - Gambar 6](<imej/22-log-aktiviti.png>)

**Apa perlu dibuat pada Gambar 6**
1. Buka Log Aktiviti Masjid.
2. Cari tajuk rekod.
3. Sahkan urutan record_uploaded, record_classified dan minit_created dengan pelaku serta masa yang betul.

**Selesai:** semak hasil akhir workflow ini sebelum menutup halaman.


### 3.3 Betulkan rekod salah tawan tanpa memadam sejarah

**Hasil akhir:** Cadangan pembetulan dihantar, disemak dan keputusan kekal dalam timeline.

Ikuti gambar mengikut nombor. Jangan lompat ke gambar seterusnya sehingga langkah gambar semasa selesai.

#### Gambar 1: Cari rekod

![Betulkan rekod salah tawan tanpa memadam sejarah - Gambar 1](<imej/14-records.png>)

**Apa perlu dibuat pada Gambar 1**
1. Cari tajuk atau nombor rujukan.
2. Buka Lihat pada rekod yang tepat.

**Kemudian:** teruskan ke **Gambar 2: Butiran rekod dan tindakan mengikut kebenaran**.


#### Gambar 2: Butiran rekod dan tindakan mengikut kebenaran

![Betulkan rekod salah tawan tanpa memadam sejarah - Gambar 2](<imej/rekod-butiran.png>)

**Apa perlu dibuat pada Gambar 2**
1. Semak dokumen asal, metadata, OCR dan tab Audit.
2. Tekan Mohon Pembetulan hanya jika salah tawan disahkan.

**Kemudian:** teruskan ke **Gambar 3: Mohon pembetulan rekod**.


#### Gambar 3: Mohon pembetulan rekod

![Betulkan rekod salah tawan tanpa memadam sejarah - Gambar 3](<imej/rekod-mohon-pembetulan.png>)

**Apa perlu dibuat pada Gambar 3**
1. Nyatakan sebab khusus.
2. Ubah hanya medan yang salah.
3. Hantar dan jangan ubah rekod melalui jalan lain.

**Kemudian:** teruskan ke **Gambar 4: Pantau atau semak permohonan**.


#### Gambar 4: Pantau atau semak permohonan

![Betulkan rekod salah tawan tanpa memadam sejarah - Gambar 4](<imej/21-pembetulan-rekod.png>)

**Apa perlu dibuat pada Gambar 4**
1. Bandingkan nilai asal dengan cadangan.
2. Reviewer berkuasa memilih Luluskan atau Tolak.
3. Sahkan status dan catatan semakan.

**Kemudian:** teruskan ke **Gambar 5: Semak jejak pembetulan**.


#### Gambar 5: Semak jejak pembetulan

![Betulkan rekod salah tawan tanpa memadam sejarah - Gambar 5](<imej/22-log-aktiviti.png>)

**Apa perlu dibuat pada Gambar 5**
1. Cari tajuk rekod.
2. Sahkan pemohon, reviewer, keputusan dan masa.
3. Pastikan tiada perubahan senyap tanpa log.

**Selesai:** semak hasil akhir workflow ini sebelum menutup halaman.


### 3.4 Urus fail fizikal atau hibrid dan jejak penjagaan

**Hasil akhir:** Lokasi, pemegang dan setiap pergerakan fail fizikal boleh dijejak.

Ikuti gambar mengikut nombor. Jangan lompat ke gambar seterusnya sehingga langkah gambar semasa selesai.

#### Gambar 1: Pilih fail

![Urus fail fizikal atau hibrid dan jejak penjagaan - Gambar 1](<imej/15-registry-files.png>)

**Apa perlu dibuat pada Gambar 1**
1. Cari nombor fail.
2. Semak Medium dan Status.
3. Buka Lihat.

**Kemudian:** teruskan ke **Gambar 2: Butiran fail elektronik, fizikal atau hibrid**.


#### Gambar 2: Butiran fail elektronik, fizikal atau hibrid

![Urus fail fizikal atau hibrid dan jejak penjagaan - Gambar 2](<imej/fail-butiran.png>)

**Apa perlu dibuat pada Gambar 2**
1. Sahkan nombor, tajuk, lokasi dan status penjagaan.
2. Tekan Keluarkan Fail apabila serahan fizikal berlaku.

**Kemudian:** teruskan ke **Gambar 3: Keluarkan fail fizikal**.


#### Gambar 3: Keluarkan fail fizikal

![Urus fail fizikal atau hibrid dan jejak penjagaan - Gambar 3](<imej/fail-keluarkan-fizikal.png>)

**Apa perlu dibuat pada Gambar 3**
1. Pilih pemegang ahli atau isi nama luar.
2. Isi lokasi tujuan, tarikh pulang dan catatan.
3. Simpan sebelum fail diserahkan.

**Kemudian:** teruskan ke **Gambar 4: Pindah lokasi fizikal**.


#### Gambar 4: Pindah lokasi fizikal

![Urus fail fizikal atau hibrid dan jejak penjagaan - Gambar 4](<imej/fail-pindah-lokasi.png>)

**Apa perlu dibuat pada Gambar 4**
1. Masukkan lokasi rak/kotak baharu.
2. Tambah catatan dan simpan.
3. Kemas kini label fizikal yang sebenar.

**Kemudian:** teruskan ke **Gambar 5: Semak log pergerakan**.


#### Gambar 5: Semak log pergerakan

![Urus fail fizikal atau hibrid dan jejak penjagaan - Gambar 5](<imej/22-log-aktiviti.png>)

**Apa perlu dibuat pada Gambar 5**
1. Tapis jenis aktiviti fail fizikal.
2. Sahkan pemegang, lokasi asal/tujuan, pelaku dan masa.

**Selesai:** semak hasil akhir workflow ini sebelum menutup halaman.


### 3.5 Sediakan dan laksanakan pelupusan terkawal

**Hasil akhir:** Rekod cukup tempoh dilupuskan hanya selepas kelulusan berasingan dan sijil tersedia.

Ikuti gambar mengikut nombor. Jangan lompat ke gambar seterusnya sehingga langkah gambar semasa selesai.

#### Gambar 1: Semak kelayakan retensi

![Sediakan dan laksanakan pelupusan terkawal - Gambar 1](<imej/10-retensi.png>)

**Apa perlu dibuat pada Gambar 1**
1. Semak tarikh cukup tempoh dan peraturan.
2. Pastikan Legal Hold tidak aktif.
3. Sediakan eksport luar jika diperlukan.

**Kemudian:** teruskan ke **Gambar 2: Buka Pelupusan**.


#### Gambar 2: Buka Pelupusan

![Sediakan dan laksanakan pelupusan terkawal - Gambar 2](<imej/06-pelupusan.png>)

**Apa perlu dibuat pada Gambar 2**
1. Semak calon dan batch sedia ada.
2. Jangan cipta batch pendua.

**Kemudian:** teruskan ke **Gambar 3: Sedia senarai pelupusan**.


#### Gambar 3: Sedia senarai pelupusan

![Sediakan dan laksanakan pelupusan terkawal - Gambar 3](<imej/pelupusan-sedia.png>)

**Apa perlu dibuat pada Gambar 3**
1. Pilih rekod satu per satu.
2. Baca amaran pemadaman kekal.
3. Hantar untuk kelulusan Pengerusi.

**Kemudian:** teruskan ke **Gambar 4: Laksana selepas diluluskan**.


#### Gambar 4: Laksana selepas diluluskan

![Sediakan dan laksanakan pelupusan terkawal - Gambar 4](<imej/06-pelupusan.png>)

**Apa perlu dibuat pada Gambar 4**
1. Tunggu status Lulus.
2. Tekan Laksana sekali.
3. Muat turun sijil apabila status Selesai.

**Kemudian:** teruskan ke **Gambar 5: Sahkan pemisahan tugas**.


#### Gambar 5: Sahkan pemisahan tugas

![Sediakan dan laksanakan pelupusan terkawal - Gambar 5](<imej/22-log-aktiviti.png>)

**Apa perlu dibuat pada Gambar 5**
1. Sahkan penyedia, pelulus dan pelaksana ialah peristiwa berasingan.
2. Semak tajuk rekod, batch dan masa setiap tindakan.

**Selesai:** semak hasil akhir workflow ini sebelum menutup halaman.


## 4. Senarai halaman role

| # | Halaman | Laluan | Status Chrome |
|---:|---|---|---:|
| 1 | Papan pemuka | `/app/{tenant}` | 200 |
| 2 | Log Akses Sulit | `/app/{tenant}/sensitive-access-logs` | 200 |
| 3 | Persediaan Berpandu | `/app/{tenant}/persediaan` | 200 |
| 4 | Ahli & Peranan | `/app/{tenant}/ahli-peranan` | 200 |
| 5 | Klasifikasi Fail | `/app/{tenant}/classification-nodes` | 200 |
| 6 | Pelupusan | `/app/{tenant}/pelupusan` | 200 |
| 7 | Peraturan Retensi | `/app/{tenant}/retensi-peraturan` | 200 |
| 8 | Tetapan Masjid | `/app/{tenant}/tetapan-masjid` | 200 |
| 9 | Penggunaan & Storan | `/app/{tenant}/penggunaan` | 200 |
| 10 | Retensi & Pegangan | `/app/{tenant}/retensi` | 200 |
| 11 | Delegasi | `/app/{tenant}/delegasi` | 200 |
| 12 | Profil Saya | `/app/{tenant}/profil` | 200 |
| 13 | Peti Masuk 1 | `/app/{tenant}/peti-masuk` | 200 |
| 14 | Rekod | `/app/{tenant}/records` | 200 |
| 15 | Fail | `/app/{tenant}/registry-files` | 200 |
| 16 | Minit Saya | `/app/{tenant}/minit-saya` | 200 |
| 17 | Kelulusan | `/app/{tenant}/kelulusan` | 200 |
| 18 | Carian | `/app/{tenant}/carian` | 200 |
| 19 | Kegemaran | `/app/{tenant}/kegemaran` | 200 |
| 20 | Laporan | `/app/{tenant}/laporan` | 200 |
| 21 | Pembetulan Rekod | `/app/{tenant}/pembetulan-rekod` | 200 |
| 22 | Log Aktiviti Masjid | `/app/{tenant}/log-aktiviti` | 200 |

## 5. Panduan setiap halaman

### 1. Papan pemuka

**URL:** `/app/{tenant}`

**Tujuan:** Ringkasan kerja dan keadaan semasa tenant: jumlah rekod, peti masuk, minit, storan dan carta trend yang dibenarkan.

![Papan pemuka - paparan 200](<imej/01-dashboard.png>)

**Nombor pada gambar**
1. Tajuk halaman Papan pemuka.

**Cara menggunakan**
1. Semak nama masjid pada panel untuk memastikan tenant yang betul.
2. Baca kad statistik; nombor hanya merangkumi data yang role ini dibenarkan lihat.
3. Semak senarai semak persediaan jika masih dipaparkan.
4. Gunakan menu kiri untuk membuka tugasan; jangan gunakan URL tenant lain secara manual.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`.

**Hasil dijangka:** Dashboard terbuka tanpa ralat dan tiada metadata masjid lain dipaparkan.


### 2. Log Akses Sulit

**URL:** `/app/{tenant}/sensitive-access-logs`

**Tujuan:** Jejak tidak boleh ubah bagi akses lihat atau muat turun rekod sensitif.

![Log Akses Sulit - paparan 200](<imej/02-sensitive-access-logs.png>)

**Nombor pada gambar**
1. Tajuk halaman Log Akses Sulit.
2. Carian atau tapisan halaman.

**Cara menggunakan**
1. Gunakan carian jadual untuk nama pengguna, rekod atau tindakan.
2. Semak pengguna, tindakan, alamat IP, user-agent dan masa akses.
3. Siasat akses luar biasa melalui rekod asal; jangan padam atau ubah log.
4. Laporkan akses tidak dikenali kepada Admin/Kerani dan wakil perlindungan data.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`, `Masa`.

**Hasil dijangka:** Log hanya memaparkan tenant semasa dan kekal baca sahaja.


### 3. Persediaan Berpandu

**URL:** `/app/{tenant}/persediaan`

**Tujuan:** Wizard persediaan pertama bagi profil admin, telefon masjid, saluran WhatsApp dan ahli awal.

![Persediaan Berpandu - paparan 200](<imej/03-persediaan.png>)

**Nombor pada gambar**
1. Tajuk halaman Persediaan Berpandu.

**Cara menggunakan**
1. Tekan Mula Persediaan Berpandu.
2. Isi jawatan anda dan nombor telefon rasmi masjid.
3. Pilih sama ada nombor sendiri digunakan sementara atau nombor khas masjid.
4. Tambah ahli awal dengan nama, peranan, telefon dan e-mel jika ada.
5. Semak setiap peranan sebelum simpan; elakkan memberi Admin/Kerani tanpa keperluan.
6. Jika dilangkau, kembali melalui menu Persediaan Berpandu untuk melengkapkan kemudian.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`, `Mula Persediaan Berpandu`, `Langkau Buat Sementara`.

**Hasil dijangka:** Tetapan dan ahli disimpan, kemudian penanda persediaan selesai dikemas kini.


### 4. Ahli & Peranan

**URL:** `/app/{tenant}/ahli-peranan`

**Tujuan:** Mengurus ahli tenant, peranan, nombor WhatsApp, pilihan notifikasi dan pautan log masuk.

![Ahli & Peranan - paparan 200](<imej/04-ahli-peranan.png>)

**Nombor pada gambar**
1. Tajuk halaman Ahli & Peranan.
2. Tindakan utama yang dibenarkan untuk peranan ini.

**Cara menggunakan**
1. Tekan Jemput Ahli dan isi nama serta nombor WhatsApp format negara.
2. Masukkan e-mel jika tersedia dan pilih hanya satu peranan yang tepat.
3. Selepas jemputan, pastikan ahli muncul dalam senarai tenant ini.
4. Gunakan Hantar Semula Pautan jika ahli belum menerima pautan; jangan cipta akaun pendua.
5. Tetapkan semula kata laluan sementara hanya apabila identiti ahli disahkan.
6. Untuk ubah peranan atau keluarkan ahli, semak kesan terhadap tugasan/minit dahulu.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`, `Jemput Ahli`, `Simpan`, `Tindakan`.

**Hasil dijangka:** Ahli menerima pautan melalui saluran tersedia dan hanya menjadi ahli masjid semasa.


### 5. Klasifikasi Fail

**URL:** `/app/{tenant}/classification-nodes`

**Tujuan:** Katalog klasifikasi berhierarki Fungsi, Aktiviti dan Sub-Aktiviti untuk nombor serta tajuk fail.

![Klasifikasi Fail - paparan 200](<imej/05-classification-nodes.png>)

**Nombor pada gambar**
1. Tajuk halaman Klasifikasi Fail.
2. Carian atau tapisan halaman.
3. Tindakan utama yang dibenarkan untuk peranan ini.

**Cara menggunakan**
1. Cari kod/tajuk sedia ada sebelum mencipta nod baharu.
2. Pilih nod induk yang betul dan peringkat yang sepadan.
3. Gunakan pola kod seperti 500, 500-1 atau 500-1/2.
4. Tetapkan tajuk rasmi, sensitiviti lalai, status Aktif dan susunan.
5. Nod yang sudah digunakan tidak boleh diubah sesuka hati; nyahaktifkan nod lama jika perlu.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`, `Kod`.

**Hasil dijangka:** Nod baharu muncul hanya dalam tenant semasa dan tersedia pada pembukaan fail.


### 6. Pelupusan

**URL:** `/app/{tenant}/pelupusan`

**Tujuan:** Mengurus pelupusan terkawal dengan pengasingan tugas antara penyedia, pelulus dan pelaksana.

![Pelupusan - paparan 200](<imej/06-pelupusan.png>)

**Nombor pada gambar**
1. Tajuk halaman Pelupusan.

**Cara menggunakan**
1. Admin/Kerani memilih rekod cukup tempoh satu per satu dan menyediakan batch.
2. Pengerusi menyemak senarai, pegangan undang-undang dan bukti sandaran sebelum meluluskan.
3. Admin/Kerani hanya melaksanakan batch selepas status diluluskan.
4. Jika gagal, jangan cipta batch pendua; baiki storan dan gunakan Cuba Semula pada batch sama.
5. Simpan sijil pelupusan; metadata audit kekal walaupun blob rekod dipadam.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`, `Sedia Senarai Semakan`.

**Hasil dijangka:** Tiada individu menyediakan, meluluskan dan melaksanakan keseluruhan pelupusan seorang diri.


### 7. Peraturan Retensi

**URL:** `/app/{tenant}/retensi-peraturan`

**Tujuan:** Menetapkan tempoh simpan dan tindakan akhir mengikut jenis rekod atau prefix klasifikasi.

![Peraturan Retensi - paparan 200](<imej/07-retensi-peraturan.png>)

**Nombor pada gambar**
1. Tajuk halaman Peraturan Retensi.
2. Tindakan utama yang dibenarkan untuk peranan ini.

**Cara menggunakan**
1. Semak peraturan lalai platform sebelum membuat override masjid.
2. Pilih jenis rekod atau isi prefix klasifikasi yang khusus.
3. Isi tahun simpanan; kosongkan hanya apabila tindakan kekal memang dikehendaki.
4. Pilih tindakan akhir yang sah dan tulis catatan dasar/kelulusan.
5. Uji kesan pada halaman Retensi sebelum mengaktifkan pelupusan.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`.

**Hasil dijangka:** Tarikh retensi rekod dikira daripada peraturan paling khusus yang sah.


### 8. Tetapan Masjid

**URL:** `/app/{tenant}/tetapan-masjid`

**Tujuan:** Maklumat masjid, wakil perlindungan data dan konfigurasi intake e-mel/WhatsApp.

![Tetapan Masjid - paparan 200](<imej/08-tetapan-masjid.png>)

**Nombor pada gambar**
1. Tajuk halaman Tetapan Masjid.
2. Tindakan utama yang dibenarkan untuk peranan ini.

**Cara menggunakan**
1. Semak telefon rasmi dan wakil perlindungan data.
2. Aktifkan intake WhatsApp/e-mel hanya selepas saluran dikawal oleh masjid.
3. Tetapkan kata kunci intake yang mudah tetapi khusus.
4. Bagi e-mel dipercayai, masukkan alamat PENGHANTAR seperti pengimbas; jangan masukkan alamat intake sistem.
5. Pasangkan WhatsApp melalui QR/kod telefon dan semak status tersambung.
6. Matikan notifikasi jika peranti hilang atau sesi tidak lagi dikawal.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`, `Edit Tetapan`, `Aktifkan WhatsApp`.

**Hasil dijangka:** Dokumen masuk ke peti masuk tenant ini dan sumber asal direkodkan.


### 9. Penggunaan & Storan

**URL:** `/app/{tenant}/penggunaan`

**Tujuan:** Memantau kuota, pesanan storan dan add-on aktif.

![Penggunaan & Storan - paparan 200](<imej/09-penggunaan.png>)

**Nombor pada gambar**
1. Tajuk halaman Penggunaan & Storan.
2. Tindakan utama yang dibenarkan untuk peranan ini.

**Cara menggunakan**
1. Bandingkan penggunaan GB, kuota efektif dan peratus penggunaan.
2. Semak pesanan sedia ada sebelum memohon sekali lagi.
3. Jika dibenarkan, tekan Tambah Storan dan pilih bilangan blok 10 GB.
4. Catat nombor invois dan tunggu pengesahan pembayaran platform.
5. Jangan anggap pesanan menunggu sebagai kuota aktif sehingga status disahkan.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`, `Tambah Storan`.

**Hasil dijangka:** Pesanan idempotent muncul sekali dan kuota bertambah hanya selepas disahkan.


### 10. Retensi & Pegangan

**URL:** `/app/{tenant}/retensi`

**Tujuan:** Senarai rekod akan luput, sumber peraturan, legal hold dan eksport sebelum luput.

![Retensi & Pegangan - paparan 200](<imej/10-retensi.png>)

**Nombor pada gambar**
1. Tajuk halaman Retensi & Pegangan.
2. Tindakan utama yang dibenarkan untuk peranan ini.

**Cara menggunakan**
1. Semak rekod yang akan luput dalam 365/90 hari.
2. Bandingkan sumber peraturan lalai dengan override masjid.
3. Aktifkan Legal Hold jika ada audit, siasatan, litigasi atau arahan simpan.
4. Tarik hold hanya dengan kebenaran dan bukti urusan selesai.
5. Eksport ZIP sebelum pelupusan jika perlu; pautan eksport mempunyai tempoh tamat.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`, `Eksport ZIP (Akan Luput ≤90 hari)`.

**Hasil dijangka:** Rekod ber-hold tidak memasuki pelupusan walaupun tarikh retensi tiba.


### 11. Delegasi

**URL:** `/app/{tenant}/delegasi`

**Tujuan:** Paparan principal/delegate untuk mewakilkan minit atau keputusan kelulusan dalam tempoh tertentu.

![Delegasi - paparan 200](<imej/11-delegasi.png>)

**Nombor pada gambar**
1. Tajuk halaman Delegasi.
2. Tindakan utama yang dibenarkan untuk peranan ini.

**Cara menggunakan**
1. Pilih Principal, iaitu pemilik tugas asal.
2. Pilih Delegate, iaitu orang yang akan bertindak bagi pihak principal.
3. Hadkan capability kepada Minit, Kelulusan atau kedua-duanya.
4. Tetapkan mula/tamat dan sebab yang boleh diaudit.
5. Semak nama “bagi pihak” pada tindakan yang dibuat delegate.
6. Batal delegasi sebaik keperluan tamat.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`, `Batal`.

**Hasil dijangka:** Delegate hanya boleh bertindak dalam tenant, capability dan julat masa yang diluluskan.


### 12. Profil Saya

**URL:** `/app/{tenant}/profil`

**Tujuan:** Maklumat akaun, saluran notifikasi, Telegram dan kata laluan sendiri.

![Profil Saya - paparan 200](<imej/12-profil.png>)

**Nombor pada gambar**
1. Tajuk halaman Profil Saya.

**Cara menggunakan**
1. Semak nama, e-mel dan nombor WhatsApp.
2. Buka Tetapan Notifikasi dan hidupkan hanya saluran yang boleh dicapai.
3. Sambung Telegram melalui pautan rasmi dan tekan Start sebelum tamat tempoh.
4. Gunakan Hantar Notifikasi Ujian selepas perubahan.
5. Tetapkan kata laluan panjang, unik dan tidak digunakan di sistem lain.
6. Putuskan Telegram atau tukar kata laluan segera jika peranti hilang.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`, `Tetapan Notifikasi`, `Hantar Notifikasi Ujian`, `Tetapkan Kata Laluan`.

**Hasil dijangka:** Notifikasi ujian sampai melalui saluran aktif; kata laluan lama tidak lagi boleh digunakan selepas ditukar.


### 13. Peti Masuk 1

**URL:** `/app/{tenant}/peti-masuk`

**Tujuan:** Pintu masuk dokumen UI, e-mel, WhatsApp atau imbasan sebelum menjadi rekod rasmi.

![Peti Masuk 1 - paparan 200](<imej/13-peti-masuk.png>)

**Nombor pada gambar**
1. Tajuk halaman Peti Masuk 1.
2. Carian atau tapisan halaman.
3. Tindakan utama yang dibenarkan untuk peranan ini.

**Cara menggunakan**
1. Semak Sumber, Tajuk/Fail, Tarikh Terima, Penghantar/Sumber, Diterima, Antivirus, OCR dan Duplikat.
2. Buka Lihat Dokumen/OCR dan sahkan fail boleh dibaca serta benar untuk masjid ini.
3. Muat naik hanya format dibenarkan; fail melalui semakan antivirus dan kuota.
4. Tekan Klasifikasikan dan lengkapkan metadata serta fail destinasi.
5. Jika spam/tidak berkaitan, gunakan Padam (Spam) dan nyatakan sebab; jangan klasifikasikan sebagai rekod.
6. Pastikan sumber menunjukkan e-mel pengirim, nombor WhatsApp atau pengguna UI serta masa upload.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`, `+ Muat Naik Dokumen`, `Diterima`, `Klasifikasikan`, `Padam (Spam)`.

**Hasil dijangka:** Item hanya keluar daripada peti masuk selepas berjaya difailkan atau dipadam dengan audit.


### 14. Rekod

**URL:** `/app/{tenant}/records`

**Tujuan:** Senarai rekod rasmi yang telah difailkan, dengan metadata, media, OCR, minit, kelulusan dan audit.

![Rekod - paparan 200](<imej/14-records.png>)

**Nombor pada gambar**
1. Tajuk halaman Rekod.
2. Carian atau tapisan halaman.

**Cara menggunakan**
1. Cari melalui rujukan/tajuk dan tapis Jenis, Sensitiviti atau Status.
2. Buka Lihat untuk memeriksa metadata dan asal dokumen.
3. Semak tab Teks OCR, Lampiran & Versi, Minit, Kelulusan dan Audit.
4. Gunakan Kegemaran untuk rujukan kerap.
5. Gunakan hanya tindakan yang dipaparkan oleh role; jangan cuba mengubah URL untuk mendapatkan tindakan lain.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`, `Rekod Baharu`, `Tarikh`.

**Hasil dijangka:** Senarai tidak merangkumi peti masuk dan tidak mendedahkan rekod tenant/sensitiviti yang tidak dibenarkan.


### 15. Fail

**URL:** `/app/{tenant}/registry-files`

**Tujuan:** Senarai fail mengikut nombor klasifikasi, jilid, sensitiviti, medium dan penjagaan fizikal.

![Fail - paparan 200](<imej/15-registry-files.png>)

**Nombor pada gambar**
1. Tajuk halaman Fail.
2. Carian atau tapisan halaman.
3. Tindakan utama yang dibenarkan untuk peranan ini.

**Cara menggunakan**
1. Cari nombor/tajuk fail sedia ada sebelum membuka fail baharu.
2. Semak status terbuka/tutup dan bilangan kandungan.
3. Bagi hibrid/fizikal, semak rujukan, lokasi, pemegang dan tarikh pulang.
4. Gunakan Keluarkan Fail, Terima Pulangan atau Pindah Lokasi untuk setiap pergerakan.
5. Tutup fail dengan sebab; buka jilid baharu apabila had kandungan dicapai.
6. Akses khas fail sulit hendaklah minimum, individu dan ditarik selepas selesai.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`, `No. Fail`, `Tutup Fail`, `Kegemaran`.

**Hasil dijangka:** Nombor fail elektronik dan fizikal sepadan; sejarah pergerakan tidak terputus.


### 16. Minit Saya

**URL:** `/app/{tenant}/minit-saya`

**Tujuan:** Arahan, makluman, balasan dan status tindakan minit yang berkaitan dengan pengguna.

![Minit Saya - paparan 200](<imej/16-minit-saya.png>)

**Nombor pada gambar**
1. Tajuk halaman Minit Saya.

**Cara menggunakan**
1. Tapis Kategori: Perlu Tindakan, Makluman, Saya Hantar atau Selesai.
2. Baca rekod, pengirim, arahan, penerima, keutamaan dan tarikh akhir.
3. Jika tindakan, buat kerja sebenar dahulu sebelum Tanda Selesai.
4. Gunakan Balas & Edarkan untuk catatan susulan dan penerima seterusnya.
5. Penerima s.k. ialah makluman; penerima tindakan mempunyai tanggungjawab dan SLA.
6. Tindakan delegate direkod sebagai “oleh X bagi pihak Y”.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`.

**Hasil dijangka:** Apabila semua penerima tindakan selesai, minit ditutup dan pengirim dimaklumkan.


### 17. Kelulusan

**URL:** `/app/{tenant}/kelulusan`

**Tujuan:** Permohonan dan keputusan kelulusan dengan pengesahan semula kata laluan, masa, IP dan pihak yang bertindak.

![Kelulusan - paparan 200](<imej/17-kelulusan.png>)

**Nombor pada gambar**
1. Tajuk halaman Kelulusan.

**Cara menggunakan**
1. Semak tajuk rekod, pemohon, nota dan status.
2. Buka rekod asal dan media sebelum membuat keputusan.
3. Pelulus yang ditetapkan memilih Lulus atau Tolak.
4. Masukkan kata laluan sendiri; nota wajib bagi penolakan dan digalakkan bagi kelulusan.
5. Jangan berkongsi kata laluan untuk membolehkan orang lain meluluskan.
6. Semak status akhir dan rekod “bagi pihak” jika delegasi digunakan.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`.

**Hasil dijangka:** Keputusan direkod sekali, tidak boleh ditindih, dan pemohon menerima notifikasi.


### 18. Carian

**URL:** `/app/{tenant}/carian`

**Tujuan:** Carian penuh dan metadata dengan saved search, julat tarikh serta hasil yang ditapis mengikut akses.

![Carian - paparan 200](<imej/18-carian.png>)

**Nombor pada gambar**
1. Tajuk halaman Carian.
2. Carian atau tapisan halaman.
3. Tindakan utama yang dibenarkan untuk peranan ini.

**Cara menggunakan**
1. Masukkan teks tajuk, rujukan atau kandungan OCR jika perlu.
2. Gabungkan Jenis, Fail, Arah, Sensitiviti, Status dan Saluran.
3. Isi pengirim, rujukan, penerima serta julat tarikh rekod/terima.
4. Tekan Cari dan semak jumlah hasil.
5. Isi Nama carian, tandakan Lalai jika sesuai, kemudian Simpan.
6. Pilih carian tersimpan untuk guna semula atau Padam carian apabila tidak diperlukan.
7. Tekan bintang untuk menambah hasil ke Kegemaran.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`, `Simpan`, `Cari`.

**Hasil dijangka:** Hasil hanya mengandungi rekod yang policy role dan tenant benarkan.


### 19. Kegemaran

**URL:** `/app/{tenant}/kegemaran`

**Tujuan:** Pintasan peribadi kepada rekod dan fail yang kerap dirujuk.

![Kegemaran - paparan 200](<imej/19-kegemaran.png>)

**Nombor pada gambar**
1. Tajuk halaman Kegemaran.

**Cara menggunakan**
1. Tambah bintang dari senarai fail, butiran rekod atau hasil carian.
2. Buka Kegemaran untuk melihat rekod/fail tersimpan.
3. Klik item untuk membuka sumber asal.
4. Tekan bintang penuh untuk membuang kegemaran.
5. Kegemaran tidak mengatasi permission; item hilang jika akses ditarik.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`, `★`.

**Hasil dijangka:** Senarai kegemaran adalah per pengguna dan per tenant.


### 20. Laporan

**URL:** `/app/{tenant}/laporan`

**Tujuan:** Ringkasan jumlah rekod, retensi, minit lewat, sumber dan akses sensitif mengikut kebenaran.

![Laporan - paparan 200](<imej/20-laporan.png>)

**Nombor pada gambar**
1. Tajuk halaman Laporan.
2. Tindakan utama yang dibenarkan untuk peranan ini.

**Cara menggunakan**
1. Semak Jumlah Rekod, Akan Luput, Minit Lewat dan Akses Sulit.
2. Bandingkan pecahan Jenis, Status dan Sumber.
3. Jika butang Eksport CSV tersedia, muat turun untuk analisis terkawal.
4. Buka fail CSV sebagai data; berhati-hati dengan perkongsian luar tenant.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`, `Eksport CSV`.

**Hasil dijangka:** Angka dan eksport menggunakan skop rekod yang pengguna dibenarkan lihat.


### 21. Pembetulan Rekod

**URL:** `/app/{tenant}/pembetulan-rekod`

**Tujuan:** Workflow pembetulan salah tawan tanpa memadam jejak nilai asal.

![Pembetulan Rekod - paparan 200](<imej/21-pembetulan-rekod.png>)

**Nombor pada gambar**
1. Tajuk halaman Pembetulan Rekod.

**Cara menggunakan**
1. Dari butiran rekod, tekan Mohon Pembetulan.
2. Nyatakan sebab sekurang-kurangnya 10 aksara dan ubah hanya medan yang salah.
3. Hantar; rekod asal kekal sehingga reviewer yang berkuasa memutuskan.
4. Reviewer membandingkan nilai asal/cadangan, kemudian Luluskan atau Tolak dengan catatan.
5. Pemohon menerima notifikasi keputusan dan audit menyimpan sebelum/selepas.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`, `Luluskan`, `Tolak`.

**Hasil dijangka:** Tiada perubahan senyap; setiap pembetulan mempunyai pemohon, reviewer, masa dan keputusan.


### 22. Log Aktiviti Masjid

**URL:** `/app/{tenant}/log-aktiviti`

**Tujuan:** Timeline append-only bagi perjalanan dokumen, fail, minit, kelulusan, pelupusan, ahli dan storan dalam masjid semasa.

![Log Aktiviti Masjid - paparan 200](<imej/22-log-aktiviti.png>)

**Nombor pada gambar**
1. Tajuk halaman Log Aktiviti Masjid.
2. Carian atau tapisan halaman.

**Cara menggunakan**
1. Tapis mengikut jenis aktiviti, pelaku, saluran atau julat tarikh.
2. Cari tajuk, keterangan, nombor rujukan atau alamat IP yang berkaitan.
3. Tekan Butiran untuk melihat snapshot rekod/fail, pengirim dan metadata peristiwa.
4. Bandingkan masa peristiwa secara kronologi; log tidak boleh diedit atau dipadam.
5. Bendahari hanya menerima log rekod/fail yang dasar aksesnya benarkan.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`, `Tarikh & Masa`, `Butiran`.

**Hasil dijangka:** Timeline hanya memaparkan tenant semasa dan tidak mendedahkan rekod yang role tidak dibenarkan lihat.


## 6. Panduan tindakan dan modal

Bahagian ini hanya menyenaraikan tindakan yang benar-benar kelihatan bagi role ini semasa verifikasi. Medan bertanda `*` wajib.

### 1. Persediaan berpandu

![Persediaan berpandu](<imej/persediaan-modal.png>)

**Nombor pada gambar**
1. Semak semua medan dalam dialog sebelum menghantar.
2. Tekan hanya selepas maklumat disemak.

**Medan/kawalan yang disahkan:** `Jawatan Anda`, `Nombor Telefon Masjid`, `Nombor WhatsApp untuk notifikasi`, `Guna nombor saya sendiri buat sementara`, `Guna nombor khas masjid (saya akan sediakan telefon)`, `Ahli untuk didaftarkan`.

**Langkah terperinci**
1. Isi jawatan pengguna.
2. Isi telefon rasmi masjid.
3. Pilih sumber nombor WhatsApp notifikasi.
4. Tambah ahli awal dan semak peranannya.
5. Simpan hanya selepas maklumat lengkap.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


### 2. Jemput ahli

![Jemput ahli](<imej/ahli-jemput.png>)

**Nombor pada gambar**
1. Semak semua medan dalam dialog sebelum menghantar.
2. Tekan hanya selepas maklumat disemak.

**Medan/kawalan yang disahkan:** `Nama*`, `No. Telefon (WhatsApp)*`, `E-mel (pilihan)`, `Peranan*`.

**Langkah terperinci**
1. Isi nama penuh.
2. Isi nombor WhatsApp format 60...
3. Masukkan e-mel jika tersedia.
4. Pilih peranan minimum yang diperlukan.
5. Hantar dan sahkan ahli menerima pautan log masuk.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


### 3. Sedia senarai pelupusan

![Sedia senarai pelupusan](<imej/pelupusan-sedia.png>)

**Nombor pada gambar**
1. Semak semua medan dalam dialog sebelum menghantar.
2. Tekan hanya selepas maklumat disemak.

**Medan/kawalan yang disahkan:** `Pilih Rekod Satu per Satu*`.

**Langkah terperinci**
1. Semak setiap rekod sudah cukup tempoh.
2. Pastikan rekod tidak mempunyai Legal Hold.
3. Pilih satu per satu.
4. Sahkan amaran pemadaman kekal.
5. Hantar batch untuk kelulusan Pengerusi.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


### 4. Edit tetapan masjid

![Edit tetapan masjid](<imej/tetapan-edit.png>)

**Nombor pada gambar**
1. Semak semua medan dalam dialog sebelum menghantar.
2. Tekan hanya selepas maklumat disemak.

**Medan/kawalan yang disahkan:** `Telefon Masjid`, `Wakil Perlindungan Data — Nama`, `Wakil Perlindungan Data — E-mel`, `Kata Kunci Intake`, `Terima dokumen WhatsApp`, `Terima dokumen melalui e-mel`, `Kata Kunci E-mel (pilihan)`, `E-mel Pengirim Dipercayai (pilihan)`.

**Langkah terperinci**
1. Semak telefon dan wakil perlindungan data.
2. Tetapkan kata kunci intake.
3. Aktif/matikan WhatsApp atau e-mel.
4. Masukkan e-mel pengirim dipercayai, bukan alamat intake.
5. Simpan dan uji satu dokumen masuk.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


### 5. Permohonan storan tambahan

![Permohonan storan tambahan](<imej/storan-tambah.png>)

**Nombor pada gambar**
1. Semak semua medan dalam dialog sebelum menghantar.
2. Tekan hanya selepas maklumat disemak.

**Medan/kawalan yang disahkan:** `Bilangan Blok (10 GB setiap satu)*`.

**Langkah terperinci**
1. Semak baki kuota dan pesanan sedia ada.
2. Masukkan bilangan blok 10 GB.
3. Sahkan jumlah/invois.
4. Tunggu pengesahan bayaran sebelum menganggap kuota aktif.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


### 6. Muat naik dokumen

![Muat naik dokumen](<imej/inbox-muat-naik.png>)

**Nombor pada gambar**
1. Semak semua medan dalam dialog sebelum menghantar.
2. Tekan hanya selepas maklumat disemak.

**Langkah terperinci**
1. Pilih atau seret satu/lebih fail yang dibenarkan.
2. Pastikan saiz setiap fail dalam had.
3. Tunggu upload selesai.
4. Sahkan toast bilangan dokumen.
5. Buka Peti Masuk dan semak antivirus, OCR serta sumber UI.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


### 7. Tetapan notifikasi

![Tetapan notifikasi](<imej/profil-notifikasi.png>)

**Nombor pada gambar**
1. Semak semua medan dalam dialog sebelum menghantar.
2. Tekan hanya selepas maklumat disemak.

**Medan/kawalan yang disahkan:** `E-mel`, `WhatsApp`, `Telegram`.

**Langkah terperinci**
1. Hidup/matikan E-mel, WhatsApp dan Telegram mengikut saluran sebenar.
2. Simpan perubahan.
3. Gunakan Hantar Notifikasi Ujian.
4. Betulkan nombor/e-mel jika ujian tidak sampai.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


### 8. Tetapkan kata laluan

![Tetapkan kata laluan](<imej/profil-kata-laluan.png>)

**Nombor pada gambar**
1. Semak semua medan dalam dialog sebelum menghantar.
2. Tekan hanya selepas maklumat disemak.

**Medan/kawalan yang disahkan:** `Kata Laluan Baharu*`, `Sahkan Kata Laluan*`.

**Langkah terperinci**
1. Cipta kata laluan unik yang panjang.
2. Masukkan semula nilai sama.
3. Simpan.
4. Uji pada sesi baharu; jangan kongsi kata laluan.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


### 9. Butiran log aktiviti

![Butiran log aktiviti](<imej/log-aktiviti-butiran.png>)

**Nombor pada gambar**
1. Semak semua medan dalam dialog sebelum menghantar.

**Langkah terperinci**
1. Semak tarikh dan masa tepat.
2. Sahkan pelaku dan role ketika aktiviti berlaku.
3. Bandingkan tajuk/rujukan rekod serta nombor fail.
4. Semak saluran, identiti pengirim dan IP jika tersedia.
5. Baca metadata peristiwa tanpa mengubah log.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


### 10. Klasifikasi peti masuk

![Klasifikasi peti masuk](<imej/inbox-klasifikasi.png>)

**Nombor pada gambar**
1. Lengkapkan metadata, pilih atau buka fail, tetapkan sensitiviti dan penerima minit.
2. Klasifikasikan hanya selepas dokumen, rujukan dan penerima disahkan.

**Medan/kawalan yang disahkan:** `Jenis Rekod*`, `Tajuk`, `Arah*`, `Ruj. Kami`, `Ruj. Tuan`, `Tarikh Rekod*`, `Tarikh Terima`, `Nama Pengirim`, `Organisasi Pengirim`, `Nama Penerima`, `Jumlah Lampiran`, `Untuk Perhatian (u.p.)`, `Failkan Ke*`, `Tahap Akses Rekod*`, `Untuk Tindakan (Minit)`, `Untuk Makluman (s.k.)`, `Catatan / Arahan Minit`, `Keutamaan Minit`.

**Langkah terperinci**
1. Sahkan dokumen, sumber dan status antivirus/OCR dahulu.
2. Pilih Jenis Rekod; medan metadata khusus akan berubah mengikut jenis.
3. Isi Tajuk, Arah, Ruj. Kami/Ruj. Tuan dan kedua-dua tarikh.
4. Isi nama/organisasi pengirim, penerima, jumlah lampiran dan Untuk Perhatian (u.p.).
5. Pilih Failkan Ke. Jika tiada fail sesuai, buka fail baharu pada nod klasifikasi yang betul.
6. Tetapkan Tahap Akses. Nilai efektif tidak boleh lebih rendah daripada sensitiviti fail.
7. Pilih Untuk Tindakan bagi orang yang wajib bertindak; pilih s.k. bagi makluman sahaja.
8. Jika ada penerima tindakan, isi arahan yang jelas dan keutamaan.
9. Tekan Klasifikasikan dan sahkan nombor fail/kandungan pada notifikasi kejayaan.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


### 11. Cipta nod klasifikasi

![Cipta nod klasifikasi](<imej/klasifikasi-cipta.png>)

**Nombor pada gambar**
1. Isi semua medan wajib bertanda asterisk.
2. Semak semula sebelum menyimpan.

**Langkah terperinci**
1. Pilih Nod Induk jika Aktiviti/Sub-Aktiviti.
2. Pilih Peringkat.
3. Masukkan Kod mengikut hierarki.
4. Isi Tajuk rasmi.
5. Tetapkan Sensitiviti Lalai, Aktif dan Susunan.
6. Cipta dan semak nod pada senarai.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


### 12. Buka fail baharu

![Buka fail baharu](<imej/fail-cipta.png>)

**Nombor pada gambar**
1. Isi semua medan wajib bertanda asterisk.
2. Semak semula sebelum menyimpan.

**Langkah terperinci**
1. Pilih Nod Klasifikasi Aktiviti/Sub-Aktiviti.
2. Isi Tajuk Fail khusus.
3. Pilih Medium Elektronik/Hibrid/Fizikal.
4. Bagi hibrid/fizikal, isi Rujukan Salinan Fizikal dan Lokasi.
5. Cipta dan semak nombor fail automatik.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


### 13. Cipta peraturan retensi

![Cipta peraturan retensi](<imej/retensi-peraturan-cipta.png>)

**Nombor pada gambar**
1. Isi semua medan wajib bertanda asterisk.
2. Semak semula sebelum menyimpan.

**Langkah terperinci**
1. Pilih Jenis Rekod atau Prefix Klasifikasi.
2. Isi Tahun Simpanan.
3. Pilih Tindakan.
4. Tambah Catatan dasar.
5. Cipta dan semak kesan pada halaman Retensi.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


### 14. Cipta delegasi

![Cipta delegasi](<imej/delegasi-cipta.png>)

**Nombor pada gambar**
1. Isi semua medan wajib bertanda asterisk.
2. Semak semula sebelum menyimpan.

**Langkah terperinci**
1. Pilih Principal.
2. Pilih Delegate.
3. Pilih tugas Minit/Kelulusan.
4. Tetapkan mula dan tamat.
5. Nyatakan sebab.
6. Cipta dan semak status Aktif.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


### 15. Butiran rekod dan tindakan mengikut kebenaran

![Butiran rekod dan tindakan mengikut kebenaran](<imej/rekod-butiran.png>)

**Nombor pada gambar**
1. Metadata utama, sumber, tarikh upload dan status antivirus.
2. Buka viewer atau muat turun fail yang dibenarkan.
3. Jejak arahan, penerima, status dan bebenang minit.
4. Jejak permohonan dan keputusan kelulusan.
5. Mohon pembetulan metadata tanpa mengubah rekod secara senyap.

**Langkah terperinci**
1. Semak tab Maklumat dan asal dokumen.
2. Semak Teks OCR.
3. Buka Lampiran & Versi.
4. Semak Minit dan Kelulusan.
5. Semak Audit.
6. Gunakan hanya butang yang role anda paparkan.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


### 16. Mohon pembetulan rekod

![Mohon pembetulan rekod](<imej/rekod-mohon-pembetulan.png>)

**Nombor pada gambar**
1. Nyatakan sebab salah tawan dan hanya ubah medan yang benar-benar salah.
2. Hantar untuk semakan; rekod asal kekal sehingga diluluskan.

**Medan/kawalan yang disahkan:** `Sebab Rekod Salah Tawan*`, `Tajuk`, `Jenis Rekod*`, `Ruj. Kami`, `Ruj. Tuan`, `Tarikh Rekod`, `Tarikh Terima`, `Arah`, `Nama Pengirim`, `Organisasi Pengirim`, `Penerima`, `Sensitiviti*`.

**Langkah terperinci**
1. Nyatakan sebab salah tawan.
2. Semak semua nilai sedia ada.
3. Ubah sekurang-kurangnya satu medan sebenar.
4. Hantar untuk semakan.
5. Pantau keputusan di Pembetulan Rekod.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


### 17. Edarkan minit

![Edarkan minit](<imej/rekod-edarkan-minit.png>)

**Nombor pada gambar**
1. Tetapkan penerima tindakan, penerima makluman, arahan dan keutamaan.
2. Hantar minit selepas penerima disemak.

**Medan/kawalan yang disahkan:** `Penerima Tindakan*`, `Makluman (s.k.)`, `Catatan / Arahan*`, `Keutamaan*`.

**Langkah terperinci**
1. Pilih sekurang-kurangnya seorang Penerima Tindakan.
2. Tambah s.k. jika hanya perlu makluman.
3. Tulis arahan yang boleh dilaksanakan.
4. Pilih Biasa/Segera/Kritikal.
5. Hantar dan semak penerima muncul pada tab Minit.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


### 18. Mohon kelulusan

![Mohon kelulusan](<imej/rekod-mohon-kelulusan.png>)

**Nombor pada gambar**
1. Pilih pelulus yang dibenarkan dan beri nota yang jelas.
2. Hantar permohonan untuk direkod dan dinotifikasikan.

**Medan/kawalan yang disahkan:** `Kepada*`, `Nota`.

**Langkah terperinci**
1. Pilih pelulus yang dipaparkan.
2. Tulis nota konteks.
3. Hantar.
4. Pantau status pada tab Kelulusan.
5. Jangan hantar permohonan pendua.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


### 19. Ganti versi rekod

![Ganti versi rekod](<imej/rekod-ganti-versi.png>)

**Nombor pada gambar**
1. Pilih fail versi baharu; versi lama kekal dalam jejak audit.
2. Sahkan hanya jika fail benar-benar versi pengganti.

**Langkah terperinci**
1. Pilih fail versi baharu yang sah.
2. Semak nama dan format.
3. Simpan.
4. Sahkan rekod baharu menjadi versi aktif dan versi lama kekal dalam jejak.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


### 20. Pindah rekod ke fail lain

![Pindah rekod ke fail lain](<imej/rekod-pindah-fail.png>)

**Nombor pada gambar**
1. Pilih fail baharu dalam tenant yang sama dan nyatakan sebab.
2. Sahkan perpindahan selepas nombor fail diperiksa.

**Medan/kawalan yang disahkan:** `Fail Baharu*`, `Sebab*`.

**Langkah terperinci**
1. Pilih Fail Baharu dalam tenant sama.
2. Semak sensitiviti fail sasaran.
3. Nyatakan sebab.
4. Sahkan pindah.
5. Semak nombor kandungan dan audit.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


### 21. Viewer dokumen

![Viewer dokumen](<imej/rekod-viewer.png>)

**Nombor pada gambar**
1. Ke halaman sebelumnya.
2. Ke halaman seterusnya.
3. Cari teks dalam PDF yang mempunyai lapisan teks.
4. Cetak dokumen bersama metadata yang dibenarkan.

**Langkah terperinci**
1. Gunakan anak panah untuk halaman sebelum/seterusnya.
2. Ubah nombor halaman secara terus.
3. Zum keluar/masuk tanpa mengubah fail.
4. Cari teks jika PDF mempunyai lapisan teks.
5. Cetak Metadata untuk bukti konteks.
6. Muat Turun hanya jika dibenarkan dan simpan secara terkawal.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


### 22. Butiran fail elektronik, fizikal atau hibrid

![Butiran fail elektronik, fizikal atau hibrid](<imej/fail-butiran.png>)

**Nombor pada gambar**
1. Pastikan nombor fail sepadan dengan klasifikasi.
2. Semak sama ada elektronik, fizikal atau hibrid.
3. Lokasi sebenar salinan fizikal.
4. Jejak keluar, pulang dan pindah lokasi fail.

**Langkah terperinci**
1. Semak nombor, tajuk dan klasifikasi.
2. Semak medium serta rujukan fizikal.
3. Semak lokasi, status penjagaan dan pemegang.
4. Semak sejarah pergerakan.
5. Semak Akses Khas bagi fail sulit jika role dibenarkan.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


### 23. Keluarkan fail fizikal

![Keluarkan fail fizikal](<imej/fail-keluarkan-fizikal.png>)

**Nombor pada gambar**
1. Rekod pemegang, lokasi tujuan, tarikh pulang dan catatan.
2. Simpan supaya penjagaan fail boleh dijejak.

**Medan/kawalan yang disahkan:** `Pemegang Ahli`, `Nama Pemegang Luar / Tambahan`, `Lokasi Tujuan`, `Perlu Dipulangkan`, `Catatan*`.

**Langkah terperinci**
1. Pilih Pemegang Ahli atau isi pemegang luar.
2. Isi lokasi tujuan.
3. Tetapkan tarikh perlu pulang.
4. Tulis catatan tujuan.
5. Simpan dan serahkan fail hanya selepas rekod pergerakan berjaya.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


### 24. Pindah lokasi fizikal

![Pindah lokasi fizikal](<imej/fail-pindah-lokasi.png>)

**Nombor pada gambar**
1. Masukkan lokasi baharu dan catatan.
2. Simpan selepas label rak atau kotak disahkan.

**Medan/kawalan yang disahkan:** `Lokasi Baharu*`, `Catatan`.

**Langkah terperinci**
1. Masukkan lokasi baharu yang tepat hingga rak/kotak.
2. Tulis sebab/catatan.
3. Simpan.
4. Pastikan label fizikal turut dikemas kini.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


### 25. Hasil carian lanjutan

![Hasil carian lanjutan](<imej/carian-hasil.png>)

**Nombor pada gambar**
1. Jumlah hasil yang pengguna ini dibenarkan lihat.

**Langkah terperinci**
1. Semak jumlah hasil.
2. Pastikan metadata hasil sepadan dengan kriteria.
3. Buka rekod untuk pengesahan.
4. Tambah bintang jika kerap dirujuk.
5. Ubah kriteria jika hasil terlalu luas.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


## 7. Ringkasan workflow hujung ke hujung untuk role ini

1. Intake UI/e-mel/WhatsApp masuk ke Peti Masuk tenant.
2. Admin/Kerani semak fail, antivirus, OCR, duplikat dan provenance.
3. Lengkapkan metadata dan pilih fail sedia ada atau buka fail baharu.
4. Tetapkan penerima tindakan, s.k., arahan dan keutamaan.
5. Klasifikasikan; sistem memberi nombor fail(kandungan) dan menghantar notifikasi.
6. Pantau balasan minit, permohonan kelulusan, pembetulan dan retensi.
7. Log masuk dan sahkan nama tenant.
8. Cari atau buka rekod/fail yang dibenarkan.
9. Semak metadata, sumber, tarikh upload, antivirus, OCR dan lampiran.
10. Laksanakan tindakan yang dipaparkan mengikut role.
11. Semak toast, status, tab Audit/Minit/Kelulusan dan notifikasi penerima.
12. Log keluar atau kunci peranti selepas selesai.

## 8. Peraturan klasifikasi, minit dan notifikasi

- **Untuk Tindakan (Minit):** penerima wajib mengambil tindakan, boleh membalas/mengedarkan dan perlu menanda selesai.
- **Untuk Makluman (s.k.):** penerima dimaklumkan tetapi bukan pemilik tindakan asal.
- **Untuk Perhatian (u.p.):** nama/unit khusus yang patut membaca surat; ia metadata surat dan tidak menggantikan penerima minit.
- **Ruj. Kami:** rujukan yang dikeluarkan masjid/organisasi sendiri. **Ruj. Tuan:** rujukan pihak penghantar.
- **Arah Masuk:** diterima daripada luar. **Keluar:** dihantar keluar. **Dalaman:** diwujud/diedar dalam organisasi.
- Notifikasi dihantar hanya melalui saluran yang aktif dan tersedia: pangkalan data, e-mel, WhatsApp atau Telegram. Semak Profil dan tetapan tenant jika notifikasi tidak tiba.
- Penerima dipilih daripada ahli aktif tenant yang dibenarkan melihat sensitiviti rekod. Nama tenant lain tidak patut muncul.

## 9. Keselamatan dan pengasingan data

1. Gunakan akaun sendiri; jangan guna akaun kongsi.
2. Semak tenant sebelum upload, klasifikasi, minit, kelulusan atau eksport.
3. Jangan ubah slug/ID pada URL. Ujian silang tenant manual ini mengembalikan HTTP 404.
4. Simpan muat turun sensitif hanya pada peranti/storan organisasi yang dibenarkan.
5. Jika data masjid lain kelihatan, berhenti serta-merta, jangan muat turun/sebar, catat masa/URL dan lapor insiden.
6. Semak sumber dokumen (UI/e-mel/WhatsApp), masa upload, antivirus dan OCR sebelum pemfailan.
7. Jangan luluskan permintaan, pembetulan atau pelupusan tanpa membuka bukti asal.
8. Log keluar pada peranti awam dan jangan simpan kata laluan dalam browser yang dikongsi.

## 10. Senarai semak sebelum menutup tugasan

- [ ] Tenant betul.
- [ ] Dokumen dan sumber telah disahkan.
- [ ] Metadata/rujukan/tarikh tepat.
- [ ] Fail dan sensitiviti tepat.
- [ ] Penerima tindakan dan s.k. tepat.
- [ ] Toast/status kejayaan dilihat.
- [ ] Notifikasi atau audit disahkan jika berkaitan.
- [ ] Tiada fail sensitif tertinggal pada peranti awam.

## 11. Bantuan dan pelaporan masalah

1. Catat masa kejadian, role, nama tenant, URL halaman, tindakan terakhir dan mesej ralat.
2. Jika berkaitan rekod/fail, sertakan nombor rujukan atau ID sahaja; lindungi kandungan dan data peribadi.
3. Hantar kepada Admin/Kerani. Admin/Kerani mengeskalasi kepada operator platform jika isu melibatkan tenant, keselamatan, intake atau servis luar.
4. Jangan hantar kata laluan, token, pautan sekali guna, kunci API atau keseluruhan dokumen sensitif.
5. Jika data tenant lain kelihatan, berhenti menggunakan halaman itu dan laporkan sebagai insiden keselamatan segera.

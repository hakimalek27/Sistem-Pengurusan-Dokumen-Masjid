# Manual Pengguna Diwan - AJK

**Versi UI disahkan:** 22 Julai 2026

**Tenant contoh:** MAM (data latihan, bukan production)

**Liputan Chrome:** 12/12 halaman, silang tenant HTTP 404, 8 skrin tindakan tambahan.

Manual ini khusus untuk role **AJK**. Gambar menggunakan data latihan. Nama, e-mel, nombor telefon dan dokumen sebenar organisasi tidak patut dimasukkan ke manual.

## 1. Skop dan had role

**Tanggungjawab:** Membaca rekod/fail yang dibenarkan serta melaksanakan arahan minit.

**Dibenarkan:** Rekod, fail dan minit.

**Had penting:** Fail/rekod sulit tidak kelihatan kecuali akses khas diberi. Tiada kuasa ubah, kelulusan atau pentadbiran.

Jika butang tidak kelihatan, itu lazimnya sekatan role, status, sensitiviti atau tenant. Jangan cuba memintas melalui URL.

## 2. Log masuk

![Log masuk AJK](<imej/00-log-masuk.png>)

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

## 3. Senarai halaman role

| # | Halaman | Laluan | Status Chrome |
|---:|---|---|---:|
| 1 | Papan pemuka | `/app/{tenant}` | 200 |
| 2 | Minit Saya | `/app/{tenant}/minit-saya` | 200 |
| 3 | Kelulusan | `/app/{tenant}/kelulusan` | 200 |
| 4 | Carian | `/app/{tenant}/carian` | 200 |
| 5 | Kegemaran | `/app/{tenant}/kegemaran` | 200 |
| 6 | Laporan | `/app/{tenant}/laporan` | 200 |
| 7 | Pembetulan Rekod | `/app/{tenant}/pembetulan-rekod` | 200 |
| 8 | Klasifikasi Fail | `/app/{tenant}/classification-nodes` | 200 |
| 9 | Rekod | `/app/{tenant}/records` | 200 |
| 10 | Fail | `/app/{tenant}/registry-files` | 200 |
| 11 | Delegasi | `/app/{tenant}/delegasi` | 200 |
| 12 | Profil Saya | `/app/{tenant}/profil` | 200 |

## 4. Panduan setiap halaman

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


### 2. Minit Saya

**URL:** `/app/{tenant}/minit-saya`

**Tujuan:** Arahan, makluman, balasan dan status tindakan minit yang berkaitan dengan pengguna.

![Minit Saya - paparan 200](<imej/02-minit-saya.png>)

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


### 3. Kelulusan

**URL:** `/app/{tenant}/kelulusan`

**Tujuan:** Permohonan dan keputusan kelulusan dengan pengesahan semula kata laluan, masa, IP dan pihak yang bertindak.

![Kelulusan - paparan 200](<imej/03-kelulusan.png>)

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


### 4. Carian

**URL:** `/app/{tenant}/carian`

**Tujuan:** Carian penuh dan metadata dengan saved search, julat tarikh serta hasil yang ditapis mengikut akses.

![Carian - paparan 200](<imej/04-carian.png>)

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


### 5. Kegemaran

**URL:** `/app/{tenant}/kegemaran`

**Tujuan:** Pintasan peribadi kepada rekod dan fail yang kerap dirujuk.

![Kegemaran - paparan 200](<imej/05-kegemaran.png>)

**Nombor pada gambar**
1. Tajuk halaman Kegemaran.

**Cara menggunakan**
1. Tambah bintang dari senarai fail, butiran rekod atau hasil carian.
2. Buka Kegemaran untuk melihat rekod/fail tersimpan.
3. Klik item untuk membuka sumber asal.
4. Tekan bintang penuh untuk membuang kegemaran.
5. Kegemaran tidak mengatasi permission; item hilang jika akses ditarik.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`.

**Hasil dijangka:** Senarai kegemaran adalah per pengguna dan per tenant.


### 6. Laporan

**URL:** `/app/{tenant}/laporan`

**Tujuan:** Ringkasan jumlah rekod, retensi, minit lewat, sumber dan akses sensitif mengikut kebenaran.

![Laporan - paparan 200](<imej/06-laporan.png>)

**Nombor pada gambar**
1. Tajuk halaman Laporan.

**Cara menggunakan**
1. Semak Jumlah Rekod, Akan Luput, Minit Lewat dan Akses Sulit.
2. Bandingkan pecahan Jenis, Status dan Sumber.
3. Jika butang Eksport CSV tersedia, muat turun untuk analisis terkawal.
4. Buka fail CSV sebagai data; berhati-hati dengan perkongsian luar tenant.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`.

**Hasil dijangka:** Angka dan eksport menggunakan skop rekod yang pengguna dibenarkan lihat.


### 7. Pembetulan Rekod

**URL:** `/app/{tenant}/pembetulan-rekod`

**Tujuan:** Workflow pembetulan salah tawan tanpa memadam jejak nilai asal.

![Pembetulan Rekod - paparan 200](<imej/07-pembetulan-rekod.png>)

**Nombor pada gambar**
1. Tajuk halaman Pembetulan Rekod.

**Cara menggunakan**
1. Dari butiran rekod, tekan Mohon Pembetulan.
2. Nyatakan sebab sekurang-kurangnya 10 aksara dan ubah hanya medan yang salah.
3. Hantar; rekod asal kekal sehingga reviewer yang berkuasa memutuskan.
4. Reviewer membandingkan nilai asal/cadangan, kemudian Luluskan atau Tolak dengan catatan.
5. Pemohon menerima notifikasi keputusan dan audit menyimpan sebelum/selepas.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`.

**Hasil dijangka:** Tiada perubahan senyap; setiap pembetulan mempunyai pemohon, reviewer, masa dan keputusan.


### 8. Klasifikasi Fail

**URL:** `/app/{tenant}/classification-nodes`

**Tujuan:** Katalog klasifikasi berhierarki Fungsi, Aktiviti dan Sub-Aktiviti untuk nombor serta tajuk fail.

![Klasifikasi Fail - paparan 200](<imej/08-classification-nodes.png>)

**Nombor pada gambar**
1. Tajuk halaman Klasifikasi Fail.
2. Carian atau tapisan halaman.

**Cara menggunakan**
1. Cari kod/tajuk sedia ada sebelum mencipta nod baharu.
2. Pilih nod induk yang betul dan peringkat yang sepadan.
3. Gunakan pola kod seperti 500, 500-1 atau 500-1/2.
4. Tetapkan tajuk rasmi, sensitiviti lalai, status Aktif dan susunan.
5. Nod yang sudah digunakan tidak boleh diubah sesuka hati; nyahaktifkan nod lama jika perlu.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`, `Kod`.

**Hasil dijangka:** Nod baharu muncul hanya dalam tenant semasa dan tersedia pada pembukaan fail.


### 9. Rekod

**URL:** `/app/{tenant}/records`

**Tujuan:** Senarai rekod rasmi yang telah difailkan, dengan metadata, media, OCR, minit, kelulusan dan audit.

![Rekod - paparan 200](<imej/09-records.png>)

**Nombor pada gambar**
1. Tajuk halaman Rekod.
2. Carian atau tapisan halaman.

**Cara menggunakan**
1. Cari melalui rujukan/tajuk dan tapis Jenis, Sensitiviti atau Status.
2. Buka Lihat untuk memeriksa metadata dan asal dokumen.
3. Semak tab Teks OCR, Lampiran & Versi, Minit, Kelulusan dan Audit.
4. Gunakan Kegemaran untuk rujukan kerap.
5. Gunakan hanya tindakan yang dipaparkan oleh role; jangan cuba mengubah URL untuk mendapatkan tindakan lain.

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`, `Tarikh`.

**Hasil dijangka:** Senarai tidak merangkumi peti masuk dan tidak mendedahkan rekod tenant/sensitiviti yang tidak dibenarkan.


### 10. Fail

**URL:** `/app/{tenant}/registry-files`

**Tujuan:** Senarai fail mengikut nombor klasifikasi, jilid, sensitiviti, medium dan penjagaan fizikal.

![Fail - paparan 200](<imej/10-registry-files.png>)

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

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`, `No. Fail`, `Kegemaran`.

**Hasil dijangka:** Nombor fail elektronik dan fizikal sepadan; sejarah pergerakan tidak terputus.


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

**Kawalan/tindakan yang terlihat semasa verifikasi:** `Masjid Al-Muttaqin Wangsa Melawati`.

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


## 5. Panduan tindakan dan modal

Bahagian ini hanya menyenaraikan tindakan yang benar-benar kelihatan bagi role ini semasa verifikasi. Medan bertanda `*` wajib.

### 1. Tetapan notifikasi

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


### 2. Tetapkan kata laluan

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


### 3. Butiran rekod dan tindakan mengikut kebenaran

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


### 4. Mohon pembetulan rekod

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


### 5. Edarkan minit

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


### 6. Viewer dokumen

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


### 7. Butiran fail elektronik, fizikal atau hibrid

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


### 8. Hasil carian lanjutan

![Hasil carian lanjutan](<imej/carian-hasil.png>)

**Nombor pada gambar**
1. Jumlah hasil yang pengguna ini dibenarkan lihat.
2. Buka rekod atau tekan bintang untuk kegemaran.

**Langkah terperinci**
1. Semak jumlah hasil.
2. Pastikan metadata hasil sepadan dengan kriteria.
3. Buka rekod untuk pengesahan.
4. Tambah bintang jika kerap dirujuk.
5. Ubah kriteria jika hasil terlalu luas.

**Semakan akhir:** jangan tutup halaman sehingga toast kejayaan atau perubahan status yang dijangka kelihatan. Jika validasi gagal, betulkan medan yang ditanda; jangan ulang hantar secara rawak.


## 6. Workflow hujung ke hujung untuk role ini

1. Terima arahan minit.
2. Semak rekod yang dibenarkan.
3. Balas/edarkan kepada ahli berkaitan.
4. Tanda selesai selepas tugasan lengkap.
5. Log masuk dan sahkan nama tenant.
6. Cari atau buka rekod/fail yang dibenarkan.
7. Semak metadata, sumber, tarikh upload, antivirus, OCR dan lampiran.
8. Laksanakan tindakan yang dipaparkan mengikut role.
9. Semak toast, status, tab Audit/Minit/Kelulusan dan notifikasi penerima.
10. Log keluar atau kunci peranti selepas selesai.

## 7. Peraturan klasifikasi, minit dan notifikasi

- **Untuk Tindakan (Minit):** penerima wajib mengambil tindakan, boleh membalas/mengedarkan dan perlu menanda selesai.
- **Untuk Makluman (s.k.):** penerima dimaklumkan tetapi bukan pemilik tindakan asal.
- **Untuk Perhatian (u.p.):** nama/unit khusus yang patut membaca surat; ia metadata surat dan tidak menggantikan penerima minit.
- **Ruj. Kami:** rujukan yang dikeluarkan masjid/organisasi sendiri. **Ruj. Tuan:** rujukan pihak penghantar.
- **Arah Masuk:** diterima daripada luar. **Keluar:** dihantar keluar. **Dalaman:** diwujud/diedar dalam organisasi.
- Notifikasi dihantar hanya melalui saluran yang aktif dan tersedia: pangkalan data, e-mel, WhatsApp atau Telegram. Semak Profil dan tetapan tenant jika notifikasi tidak tiba.
- Penerima dipilih daripada ahli aktif tenant yang dibenarkan melihat sensitiviti rekod. Nama tenant lain tidak patut muncul.

## 8. Keselamatan dan pengasingan data

1. Gunakan akaun sendiri; jangan guna akaun kongsi.
2. Semak tenant sebelum upload, klasifikasi, minit, kelulusan atau eksport.
3. Jangan ubah slug/ID pada URL. Ujian silang tenant manual ini mengembalikan HTTP 404.
4. Simpan muat turun sensitif hanya pada peranti/storan organisasi yang dibenarkan.
5. Jika data masjid lain kelihatan, berhenti serta-merta, jangan muat turun/sebar, catat masa/URL dan lapor insiden.
6. Semak sumber dokumen (UI/e-mel/WhatsApp), masa upload, antivirus dan OCR sebelum pemfailan.
7. Jangan luluskan permintaan, pembetulan atau pelupusan tanpa membuka bukti asal.
8. Log keluar pada peranti awam dan jangan simpan kata laluan dalam browser yang dikongsi.

## 9. Senarai semak sebelum menutup tugasan

- [ ] Tenant betul.
- [ ] Dokumen dan sumber telah disahkan.
- [ ] Metadata/rujukan/tarikh tepat.
- [ ] Fail dan sensitiviti tepat.
- [ ] Penerima tindakan dan s.k. tepat.
- [ ] Toast/status kejayaan dilihat.
- [ ] Notifikasi atau audit disahkan jika berkaitan.
- [ ] Tiada fail sensitif tertinggal pada peranti awam.

## 10. Bantuan dan pelaporan masalah

1. Catat masa kejadian, role, nama tenant, URL halaman, tindakan terakhir dan mesej ralat.
2. Jika berkaitan rekod/fail, sertakan nombor rujukan atau ID sahaja; lindungi kandungan dan data peribadi.
3. Hantar kepada Admin/Kerani. Admin/Kerani mengeskalasi kepada operator platform jika isu melibatkan tenant, keselamatan, intake atau servis luar.
4. Jangan hantar kata laluan, token, pautan sekali guna, kunci API atau keseluruhan dokumen sensitif.
5. Jika data tenant lain kelihatan, berhenti menggunakan halaman itu dan laporkan sebagai insiden keselamatan segera.

# Manual Pengguna Diwan - Orang Awam / Pendaftaran Masjid

**Versi UI disahkan:** 22 Julai 2026

**Skop:** pusat bantuan, pendaftaran berperingkat, kelulusan, log masuk dan persediaan pertama.

## 1. Sebelum mendaftar

Sediakan nama rasmi masjid, negeri/daerah, kod akronim 3-6 huruf, cadangan slug URL, nama pentadbir pertama, e-mel aktif dan nombor WhatsApp format negara seperti `60123456789`. Pentadbir pertama akan menjadi **Admin / Kerani** tenant selepas diluluskan.

Jangan gunakan e-mel atau telefon yang anda tidak kawal. Baca Terma/DPA dan dasar retensi; rekod cukup tempoh boleh dipadam selepas notifikasi dan proses pelupusan yang berkenaan.

## 2. Aliran pendaftaran - Gambar 1 hingga Gambar 7

Ikuti gambar mengikut urutan. Setiap gambar ialah kesinambungan skrin sebelumnya; jangan lompat sebelum langkah semasa berjaya.

### Gambar 1: Buka laman utama

![Gambar 1 - Laman utama Diwan](<imej/01-laman-utama.png>)

1. Buka `https://bakwim.my`.
2. Pilih **Daftar Masjid** untuk permohonan baharu.
3. Jika sudah mempunyai akaun, pilih **Log Masuk** dan jangan daftar tenant pendua.

**Kemudian:** teruskan ke **Gambar 2: Maklumat masjid**.

### Gambar 2: Langkah 1 - Maklumat masjid

![Gambar 2 - Maklumat masjid](<imej/02-borang-daftar.png>)

1. **Nama Masjid:** nama rasmi penuh.
2. **Negeri/Daerah:** lokasi pentadbiran sebenar.
3. **Kod Akronim:** 3-6 huruf sahaja dan mesti unik, contoh `MAM`. Kod digunakan pada nombor fail.
4. **Slug URL:** huruf kecil/nombor, ringkas dan unik. Sistem boleh mengkanonkan slug berdasarkan nama.
5. Tekan **Seterusnya**. Jika validasi gagal, betulkan medan pada skrin ini sebelum meneruskan.

**Kemudian:** teruskan ke **Gambar 3: Maklumat pentadbir**.

### Gambar 3: Langkah 2 - Maklumat Admin / Kerani pertama

![Gambar 3 - Maklumat pentadbir](<imej/02b-pentadbir.png>)

1. Masukkan nama individu yang bertanggungjawab, bukan nama jawatan umum.
2. Masukkan e-mel aktif yang akan menerima pautan selepas kelulusan.
3. Masukkan nombor WhatsApp format `60...` tanpa ruang atau simbol.
4. Tekan **Kembali** jika identiti masjid perlu dibetulkan; data langkah ini tidak dihantar lagi.
5. Tekan **Seterusnya** selepas ketiga-tiga butiran tepat.

**Kemudian:** teruskan ke **Gambar 4: Semakan dan persetujuan**.

### Gambar 4: Langkah 3 - Semakan dan persetujuan

![Gambar 4 - Semakan dan persetujuan](<imej/02c-persetujuan.png>)

1. Bandingkan ringkasan nama, kod, lokasi dan pentadbir dengan maklumat sebenar.
2. Baca lalu tandakan Terma Perkhidmatan dan DPA.
3. Baca lalu tandakan pengakuan dasar retensi.
4. Gunakan **Kembali** jika satu nilai tidak tepat.
5. Tekan **Hantar Permohonan** sekali sahaja.

**Kemudian:** tunggu **Gambar 5: Permohonan diterima**.

### Gambar 5: Permohonan diterima

![Gambar 5 - Permohonan diterima](<imej/03-permohonan-diterima.png>)

1. Pastikan mesej **Permohonan diterima!** kelihatan.
2. Permohonan berstatus menunggu kelulusan platform.
3. Jangan daftar semula. Tunggu e-mel/WhatsApp rasmi.
4. Jika terlalu lama, hubungi pentadbir platform dengan nama masjid, kod dan masa permohonan; jangan kirim kata laluan.

**Selepas permohonan diluluskan:** teruskan ke **Gambar 6: Tetapkan kata laluan**.

### Gambar 6: Buka pautan kelulusan dan tetapkan kata laluan

![Gambar 6 - Tetapkan kata laluan pertama](<imej/04-tetapkan-kata-laluan.png>)

1. Selepas diluluskan, buka pautan log masuk yang diterima. Pautan sah 15 minit dan sekali guna.
2. Pastikan domain ialah `bakwim.my`; jangan masukkan kata laluan pada domain lain.
3. Cipta kata laluan panjang dan unik.
4. Taip semula kata laluan yang sama.
5. Tekan **Simpan & Teruskan**.
6. Jika pautan tamat, gunakan halaman Log Masuk untuk meminta pautan baharu.

**Kemudian:** teruskan ke **Gambar 7: Persediaan kali pertama**.

### Gambar 7: Persediaan kali pertama

![Gambar 7 - Persediaan tenant kali pertama](<imej/05-persediaan-pertama.png>)

1. Tekan **Mula Persediaan Berpandu**.
2. Isi jawatan, telefon rasmi dan pilihan nombor WhatsApp.
3. Jemput ahli awal dengan role yang tepat.
4. Lengkapkan tetapan masjid dan notifikasi.
5. Jika perlu Langkau, kembali kemudian melalui menu Persediaan; jangan biarkan tetapan intake/ahli tidak disahkan.
6. Selepas ini, teruskan panduan dalam folder `01-Admin-Kerani`.

## 3. Log masuk tanpa kata laluan - Gambar 8

![Gambar 8 - Minta pautan log masuk](<imej/06-log-masuk-pautan.png>)

1. Buka `https://bakwim.my/log-masuk`.
2. Masukkan e-mel atau nombor telefon berdaftar.
3. Tekan **Hantar Pautan Log Masuk** sekali.
4. Semak e-mel/WhatsApp dan buka pautan dalam 15 minit.
5. Respons halaman tidak mengesahkan kewujudan akaun demi keselamatan.
6. Jika sudah menetapkan kata laluan, gunakan pautan **Log masuk dengan kata laluan**.

## 4. Pusat bantuan dan laporan masalah - Gambar 9

![Gambar 9 - Pusat bantuan orang awam](<imej/07-pusat-bantuan.png>)

1. Buka `https://bakwim.my/bantuan` atau tekan **Bantuan** pada navigasi.
2. Cari dengan ayat biasa, contohnya “cara daftar masjid” atau “tak boleh login”.
3. Pilih **Baca langkah** untuk jawapan rasmi atau **Mulakan panduan** untuk penunjuk pada skrin.
4. Jalankan diagnosis baca sahaja sebelum membuat laporan.
5. Jika isu belum selesai, isi hasil dijangka dan kejadian sebenar. Lampiran pilihan maksimum 5 MB akan diperiksa antivirus.
6. Jangan masukkan kata laluan, token, query URL atau kandungan dokumen. Simpan nombor rujukan tiket selepas dihantar.

## 5. Keselamatan pendaftaran

- Jangan daftar bagi pihak masjid tanpa kuasa.
- Jangan kongsi pautan sekali guna, kata laluan atau kod Telegram/WhatsApp.
- Jangan cipta permohonan pendua untuk mengatasi kelewatan.
- Jika menerima pautan tanpa memohon, abaikan dan laporkan.
- Selepas masuk, sahkan nama/slug tenant. Jika salah, log keluar dan hubungi platform.

## 6. Maklumat yang selamat untuk laporan

1. Catat nama masjid, kod akronim, masa permohonan dan mesej ralat.
2. Gunakan borang Pusat Bantuan; X-Request-ID membantu operator memadankan log tanpa membaca kandungan borang anda.
3. Jangan sertakan kata laluan, token, pautan sekali guna atau dokumen pengenalan dalam laporan awal.
4. Jika menerima pautan atau mesej mencurigakan, jangan klik; laporkan domain/nombor penghantar dan masa penerimaan.

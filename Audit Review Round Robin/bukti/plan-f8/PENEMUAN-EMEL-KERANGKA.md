# F8 nota F — e-mel DIHANTAR dari produksi, dan gate itu sendiri tidak boleh membuktikan apa yang ia dakwa

**Tarikh:** 11 Ogos 2026 · **Kebenaran:** pemilik memberi kebenaran eksplisit dalam sembang
**Penerima:** alamat akaun pemilik (dipilih oleh pemilik daripada dua pilihan)
**Pelayan:** produksi `43.156.242.188`, `/opt/diwan`, 8 kontena hidup

## 1. `diwan:staging-check` dijalankan pada produksi — 9/9 LULUS

```
postgresql  LULUS      cos    LULUS      meilisearch LULUS      imap    LULUS
redis_cache LULUS      ocr    LULUS      smtp        LULUS      gateway LULUS
horizon     LULUS
```

Itu menutup jurang "penghantaran sebenar belum diuji": SMTP Brevo **memang** menghantar.

## 2. 🔴 Tetapi gate itu TIDAK boleh membuktikan tujuannya sendiri

Nota F menyatakan jurang yang tinggal ialah *"bukti SMTP Brevo kekalkan BM (pengekodan)"*.
Dibaca pada kod (`StagingCheck.php:61`), gate itu menghantar:

```php
Mail::raw('Ujian staging Diwan pada '.now()->toIso8601String(), …);
```

⭐ **`Mail::raw` ialah teks kosong.** Tiada salam, tiada penutup, tiada nota hak cipta — tiada
kerangka sama sekali. Jadi pemilik yang membuka e-mel itu **tidak mempunyai apa-apa untuk
disahkan**, dan gate itu tidak boleh menutup jurang yang ia ditulis untuk tutup. Ia membuktikan
*penghantaran*, bukan *kerangka*.

Ini kelas kecacatan yang sama seperti gate carian yang hijau tanpa menguji apa-apa: assertion
yang namanya menjanjikan lebih daripada apa yang ia periksa.

## 3. Kerangka BM dihantar melalui Brevo, dan disahkan PADA produksi

Notifikasi berkerangka (templat markdown yang SAMA seperti 18 kelas `toMail()` produk) dirender
di dalam kontena produksi, diperiksa, kemudian dihantar:

```
Salam sejahtera         : ADA
Sekian                  : ADA
Hak cipta terpelihara   : ADA
Hello                   : -
Regards                 : -
HANTAR: OK
```

Urutan itu sengaja: **periksa dahulu, hantar kemudian.** Menghantar sesuatu yang belum diperiksa
bermakna satu-satunya bukti berada dalam peti masuk orang lain.

⚠️ **Langkah terakhir milik pemilik:** membuka e-mel dan mengesahkan salam/penutup itu masih BM
selepas pengekodan transport. Itu tidak boleh saya lakukan — saya tidak membuka peti masuk
pemilik. Yang saya boleh buktikan ialah kerangka betul **pada titik penghantaran**, dan itu sudah
dibuktikan pada produksi.

## 4. ✅ Gate DIBAIKI supaya ia benar-benar menguji kerangka

`diwan:staging-check --mail-to=` kini:
1. membina `StagingSkeletonNotification` (kerangka markdown yang sama seperti notifikasi produk);
2. **mengassert** kerangka itu BM dan bebas kebocoran Inggeris — SEBELUM menghantar;
3. baru kemudian menghantar.

Kegagalan terjemahan kini gagal **di gate**, bukan senyap dalam peti masuk. Dibuktikan:

```
biasa           smtp LULUS ok
APP_LOCALE=en   smtp GAGAL  kerangka e-mel kehilangan "Salam sejahtera" — locale ms tidak dimuatkan
```

Dan dikunci oleh ujian CI (`LocalisationTest`), dengan anti-vakum pada panjang render supaya
rentetan kosong tidak boleh lulus kedua-dua gelung secara vakum.

⚠️ **BELUM di-deploy** — pembaikan gate ini berkuat kuasa pada deploy berikutnya. E-mel yang
pemilik terima hari ini dihantar melalui notifikasi ad-hoc yang menggunakan kerangka yang SAMA,
jadi apa yang dilihat pemilik ialah apa yang gate baharu akan hantar.

# F8 — keputusan pemilik #2: denominator **229 lawan 172**, dijawab dengan data

**Tarikh:** 9 Ogos 2026 · **Artifak:** `denominator-229-vs-172.json` (senarai penuh setiap kunci)
**Cara hasilkan semula:** ekstrak manifest F0 (`git show 7129369:…/manifest.json`) dan bandingkan
medan `wait_for_user` per kunci langkah dengan manifest semasa.

Keputusan ini sebelum ini dibingkai sebagai pertimbangan ("172 = invarian manifest; 229 = teks
pelan"). Ia tidak perlu menjadi pertimbangan — ia boleh diukur, dan sudah diukur.

## 1. Angka tepat

```
langkah  F0=473   kini=473   kunci sama=473        (tiada langkah dicipta/dibuang)
wait_for_user   F0=229   kini=172   beza=57
  HILANG (dulu tindakan, kini tidak) : 58
  TAMBAH (kini tindakan, dulu tidak) :  1   -> public.login#2 (page-primary → login-submit)
```

Bersih 57. Yang bertambah itu penting: `public.login#2` ialah **tindakan sebenar** (hantar
pautan log masuk), jadi pembetulan berlaku pada KEDUA-DUA arah, bukan hanya ke bawah.

## 2. Hipotesis pertama saya SALAH — dan itu disemak sebelum ditulis

Saya menjangka `wait_for_user` **diterbitkan** daripada sasaran generik (langkah yang menunjuk
kepada seluruh halaman tidak boleh maju sendiri). Jika benar, denominator akan mengecil secara
automatik sebagai kesan sampingan pembaikan.

**Ditolak dengan membaca kod dan katalog:** `build-manifest.mjs:207` membaca
`st.wait_for_user === true` daripada katalog, dan katalog memang mempunyai medan itu per langkah.
Ia **ditulis**, bukan diterbitkan. Jadi 57 itu ialah **suntingan yang disengajakan**, dan
soalannya menjadi: adakah setiap satu wajar?

## 3. Sebab bertulis, direkod SEZAMAN (bukan dibina semula sekarang)

`build-manifest.mjs:51–86` merekod ketiga-tiga pembetulan pada masa ia dibuat:

| Bila | Perubahan | Sebab yang direkod |
|---|---|---|
| F5 | 229 → 228 (−2 +1) | 2 langkah PEMERHATIAN pada `muat-naik-dokumen`; +1 `public.login#2` ialah tindakan tulen |
| F6-W1 | 228 → 190 (−38) | langkah PEMERHATIAN tersalah label ("Semak baki kuota", "Pantau status") |
| F6-W2 | 190 → 172 (−18) | arahan BACA atau kerja **di luar sistem** ("Kemas kini label fizikal yang sebenar") |

⭐ Sebab yang direkod menamakan kesannya: nilai `true` yang salah itulah yang menyebabkan CTA
**"Buat pada skrin"** muncul pada langkah yang pengguna hanya perlu BACA — dan itu **aduan asal
pemilik**.

## 4. Saya tidak percaya nota saya sendiri — 58 langkah diklasifikasi semula daripada teks arahan

Setiap satu daripada 58 diklasifikasi mengikut **kata kerja arahan sebenar** dalam katalog:

```
PEMERHATIAN   35     (Semak / Sahkan / Pantau / Baca / Rujuk …)
lain          20     (contoh: "Pastikan metadata hasil sepadan", "Tunggu pengesahan bayaran",
                      "Gunakan hanya butang yang dipaparkan untuk peranan anda")
TINDAKAN(?)    3     ← dibendera untuk pemeriksaan tangan
```

## 5. Ketiga-tiga yang dibendera diperiksa — dan kriterianya mekanikal

Soalan yang betul bukan "adakah ayat itu berbunyi seperti arahan", tetapi **adakah sasaran
langkah BERIKUTNYA wujud tanpa pengguna bertindak?** Jika ya, tour boleh maju dan
`wait_for_user: false` betul.

| Langkah | Arahan | Keputusan |
|---|---|---|
| `buat-keputusan-kelulusan#1` | "Buka dan semak rekod asal…" | sasaran berikutnya `approval-lulus` ialah **adik-beradik pada halaman yang sama** — wujud tanpa tindakan ✔ |
| `butiran-rekod…#3` | "Buka Lampiran & Versi." | sasaran ini dan yang berikutnya kedua-duanya **TAB**, dan kedua-dua tab hadir dalam DOM tanpa mengira yang mana dibuka ✔ |
| `viewer-dokumen#6` | "Muat turun hanya jika dibenarkan…" | **langkah TERAKHIR**. `true` di sini akan menggantung tour selamanya — `final-action` menunggu sasaran HILANG (perangkap yang sudah direkod sebagai lencongan F5 #2) ✔ |

**Kesemua 58 wajar.** Tiada satu pun terbukti terlebih-betul.

## 6. Maka keputusan itu bukan lagi seri

- **229** ialah kiraan baseline F0, dan ia **mengandungi 57 label yang salah**.
- Mengejar "0 daripada 229" bermakna mengembalikan `wait_for_user: true` kepada arahan baca —
  iaitu **memulihkan semula kecacatan yang pemilik adukan**.
- **172** ialah bilangan langkah yang benar-benar menunggu tindakan pengguna.

**Cadangan:** guna **172** sebagai denominator hidup, dan kekalkan **229** direkod sebagai
baseline F0 supaya sejarah tidak hilang. Pembilang yang penting —
`action_steps_with_generic_target` — ialah **0** pada kedua-dua denominator, jadi pilihan ini
**tidak mengubah** sama ada sasaran F6 tercapai.

⚠️ Itu cadangan, bukan keputusan. Yang berubah ialah pemilik kini memilih dengan senarai penuh
di tangan (`denominator-229-vs-172.json`) dan bukan antara dua nombor tanpa konteks.

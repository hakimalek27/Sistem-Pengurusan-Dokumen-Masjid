# F8 — `centerCovered` mobile: metrik pelan bergerak BERTENTANGAN dengan matlamatnya

**Tarikh:** 8 Ogos 2026 · **Kod:** `7793cb0` (produksi `2325bec`)
**Status:** memerlukan **keputusan pemilik** — bukan pepijat untuk dibaiki secara senyap.

## Ringkasan

Baris §9 pelan menetapkan: *"Popover mobile menutup tengah — 6 langkah → **0/6**, ditutup pada
W0"*. Ukuran F8 mendapati ia **tidak tercapai**, dan lebih jauh daripada itu — angkanya
**naik**:

```
audit  (produksi, tenant smoke, commit 4e07a70) :  6/124
F8     (tempatan, tenant mam, commit 7793cb0)   : 45/124
enam asal: masih tertutup 6/6 · pulih 0 · BAHARU tertutup 39
```

Sebelum melaporkan ini sebagai regresi, dua perkara disemak: adakah metriknya bermakna, dan
adakah kenaikan itu milik kami atau milik persekitaran.

## 1. Metrik DITENTUKUR dahulu — ia memang membezakan

Kebimbangan pertama: mungkin mana-mana popover pada skrin 390×664 melitupi titik pusat, jadi
metrik itu tidak bermakna. **Ditolak oleh data audit sendiri:** audit mengukur **124** langkah,
kesemuanya mempunyai popover, dan hanya **6** yang `centerCovered`. Kawalan tempatan pada
langkah yang tidak pernah cacat memberi 5/6 **tidak** tertutup.

Metrik itu membezakan. Angka 45 bukan artifak alat.

## 2. A/B pada mesin yang SAMA — kenaikan itu milik KAMI

Pembauran yang perlu dihapuskan: audit berjalan pada produksi tenant `smoke`; F8 tempatan pada
`mam` dengan benih demo. Jadi katalog LAMA diukur pada mesin, tenant, benih, viewport dan skrip
yang **identik** — satu pemboleh ubah sahaja.

| Katalog | `centerCovered` | Kedudukan popover tipikal |
|---|---|---|
| **LAMA** `2026.07.22.2` (sasaran generik) | **1/24** | `top` 390–411 |
| **SEMASA** `2026.08.08.2` (sasaran spesifik) | **8/24** | `top` 180–280 |

**Mekanisme:** dengan sasaran generik, Driver.js meletakkan popover relatif kepada `<main>` —
elemen gergasi — jadi ia diparkir jauh ke bawah, di luar pusat viewport. Dengan sasaran
spesifik, popover diletakkan **di sebelah elemen yang ia terangkan**, dan pada skrin 664px
tinggi elemen itu selalunya berada di jalur tengah.

⭐ **Kawalan dalaman yang menutup hujah:** `tenant.profil#2` sudah bersasar spesifik
(`profil-notifikasi`) dalam katalog LAMA, `top=195` — dan ia `centerCovered` dalam **kedua-dua**
larian. Sasaran sama → kedudukan sama → keputusan sama. Jadi puncanya ialah **jenis sasaran**,
bukan perubahan kod penempatan.

## 3. Maka: metrik ini menghukum tepat pembaikan yang F6 hantar

Sasaran generik ialah kecacatan yang seluruh F6 wujud untuk hapuskan — popover yang tidak
menunjuk kepada apa-apa. Metrik `centerCovered` memberi ganjaran kepada keadaan itu, kerana
popover yang tidak berlabuh pada apa-apa akan duduk jauh dari tengah.

Mengejar `0/124` bermakna menolak popover **menjauhi** elemen yang dirujuknya. Itu bertentangan
dengan tujuan.

**Ukuran yang bermakna sudah wujud dan sudah hijau:** penjaga W0 dalam `guidance.spec.js`
mengassert *popover tidak menutup **sasarannya sendiri*** — iaitu perkara yang pengguna
sebenarnya perlu lihat. Ia berjalan desktop DAN mobile untuk kedua-dua guide W0, dan hijau
dalam CI `31213031582`.

## 3A. ⚠️ Had eksperimen — dinyatakan selepas audit pusingan 1 (Codex #22, #23)

Codex betul bahawa kesimpulan versi pertama melampaui eksperimennya. Had yang sebenar:

- **A/B ialah 24 langkah, sampel BERTUJUAN.** Kriteria: 8 guide tenant × 3 langkah pertama,
  dipilih kerana kesemuanya mempunyai ≥3 langkah dalam KEDUA-DUA katalog, merentas papan pemuka
  / senarai / borang / laporan. Ia direka untuk mengasingkan **satu** pemboleh ubah, BUKAN untuk
  mendakwa kadar populasi. Data per-langkah kini dikomit (`ab-lama.json`, `ab-semasa.json`).
- **Perbandingan penuh 6/124 → 45/124 mencampurkan persekitaran** (produksi/`smoke` lawan
  tempatan/`mam`). A/B wujud tepat untuk menampung kelemahan itu, tetapi ia tidak
  menghapuskannya bagi angka 45 itu sendiri.
- **Satu kawalan dalaman (`tenant.profil#2`) tidak mengasingkan kesan seluruh katalog.** Ia
  menunjukkan mekanisme itu masuk akal; ia bukan bukti bahawa tiada faktor lain menyumbang.
- **Penjaga "tidak menutup sasaran sendiri" hanya berjalan pada DUA guide W0**, bukan kohort
  penuh. Mencadangkannya sebagai pengganti bermakna meluaskannya dahulu.
- **Saya TIDAK membuktikan bahawa menutup bahagian tengah tidak merosakkan UX.** 39 langkah
  baharu itu tidak diperiksa dengan mata. Jadi cadangan (1) di §5 ialah cadangan berdasarkan
  mekanisme, bukan kesimpulan berdasarkan pengalaman pengguna yang diukur.

Kesimpulan yang KEKAL disokong: **metrik itu bergerak apabila jenis sasaran berubah, dengan
arah dan mekanisme yang konsisten merentas 24 langkah berpasangan pada persekitaran yang
identik.** Kesimpulan yang DITARIK: "sasaran 0 patut dibersarakan" — itu keputusan pemilik,
bukan hasil ukuran ini.

## 3B. 📸 BUKTI VISUAL — dan ia menyelesaikan persoalan

📸 `gambar/centercovered-mobile-390x664.png` — ⚠️ **tidak dikomit**: `bukti/.gitignore:5` menolak
`*.png` mengikut polisi pelan ("PNG kekal luar git"). Ia boleh dijana semula bila-bila dengan
`skrip/gambar-centercovered.mjs`, dan ukurannya dicetak sebagai teks di bawah supaya angka itu
kekal boleh diaudit walaupun gambar tiada.
Produksi, mobile **390×664 tepat**, dengan titik pusat viewport dan sempadan sasaran DILUKIS
pada gambar supaya hubungan itu boleh dilihat, bukan hanya dibaca.

Diukur pada gambar yang sama:

```
viewport 390x664 · pusat (195, 332)
popover  x10  y100  366x243   -> merentangi y 100..343, jadi ia menyentuh y=332
sasaran  help-search-form  x16 y363 358x116   -> y 363..479
centerCovered            : true
popoverMenutupSasaran    : FALSE
sasaranKelihatanPenuh    : TRUE
```

**Apa yang gambar tunjukkan:** popover di ATAS, titik pusat jatuh pada **tepi bawahnya**, dan
borang carian yang ia terangkan berada **terus di bawah — kelihatan penuh, tidak terlindung**.
Arahan di atas, benda yang dirujuk di bawah, kedua-duanya jelas.

⭐ **Itu susun atur yang BETUL.** `centerCovered = true` di sini menandakan reka bentuk yang
baik, bukan kecacatan. Pada viewport 664px, mana-mana popover yang diletakkan **di atas**
sasarannya akan menyentuh titik tengah — geometri, bukan kualiti.

Ini menjawab kebimbangan yang §3A biarkan terbuka ("saya TIDAK membuktikan bahawa menutup
bahagian tengah tidak merosakkan UX"). Bagi kes ini, ia **dibuktikan tidak merosakkan**: sasaran
kelihatan penuh dan tidak terlindung. Ia satu kes, bukan 45 — tetapi ia kes yang metrik itu
tandakan merah.

ℹ️ **Nota kaedah — MCP Chrome DIUKUR tidak boleh melayani tujuan ini (tiga probe bebas):**

```
1. document.visibilityState  -> "hidden"   pada SETIAP tab MCP (yang dicipta DAN sedia ada)
2. outerWidth x outerHeight  -> 0x0        tetingkap tiada dimensi; resize_window diakui
                                            tetapi viewport kekal 1920x889
3. Page.captureScreenshot    -> CDP timeout 30s, "renderer may be frozen"
```

Ketiga-tiganya menunjuk punca yang SAMA, dan ia punca yang sama seperti penemuan **#73**:
tetingkap kumpulan MCP pada mesin ini tidak pernah dipaparkan, jadi Chrome membekukan
pemasa dan rendering di dalamnya. Ia bukan langkah yang dilangkau — ia had persekitaran yang
diukur, dan kaedah terkawal (Playwright pada 390×664 tepat) digunakan sebagai gantinya.

🔑 Ini menjadikan #73 lebih bernilai daripada satu penggera palsu: had yang sama akan
menjatuhkan **mana-mana** pengesahan visual melalui MCP pada mesin ini, jadi ia perlu diketahui
sekali dan bukan ditemui semula setiap kali.


## 3C. ⭐ METRIK PENGGANTI DIUKUR MERENTAS KOHORT PENUH — jurang §3A ditutup

§3A menyatakan had yang jujur: *"Penjaga 'tidak menutup sasaran sendiri' hanya berjalan pada
DUA guide W0, bukan kohort penuh. Mencadangkannya sebagai pengganti bermakna meluaskannya
dahulu."* Ia kini diluaskan dan diukur pada **kesemua 124 langkah**, dalam larian yang SAMA
seperti `centerCovered` supaya perbandingan bukan merentas larian.

Provenance dalam artifak: commit `ebd837b` · katalog `2026.08.08.2` · viewport 390×664.

```
langkah dengan rect sasaran                        : 124/124
popover menutup SEBAHAGIAN sasarannya              :  47
popover menutup >=50% sasarannya                   :   0     <-- sifar
sasaran berada di LUAR viewport                    :   0     <-- sifar

centerCovered = TRUE tetapi sasaran TIDAK terlindung : 45/45
```

⭐ **Kesemua 45 penanda `centerCovered` ialah penggera palsu.** Dalam setiap satu daripada 45,
benda yang pengguna perlu lihat — sasaran langkah itu — **tidak dilindungi oleh popover**.
Kadar positif-palsu metrik itu pada kohort ini ialah **100%**.

Dan metrik pengganti sudah **hijau merentas kohort penuh**, bukan hanya pada dua guide W0:
tiada satu langkah pun yang popovernya menutup ≥50% sasarannya, dan tiada satu sasaran pun
terkeluar daripada viewport. 47 langkah mempunyai pertindihan tepi, tetapi tiada yang
mengaburkan.

Ini mengubah pilihan (1) di §5 daripada cadangan berdasarkan mekanisme + satu tangkapan skrin
kepada **cadangan berangka**: penggantinya bukan sahaja lebih bermakna, ia sudah dipenuhi.

⚠️ Yang masih TIDAK didakwa: ini diukur pada tenant `mam` tempatan dengan benih demo, bukan
pada produksi. Larian §9.1a akan mengesahkannya pada data sebenar.

## 4. Apa yang saya TIDAK dakwa

- Saya **tidak** mengukur 124 langkah pada produksi — itu memerlukan sesi tenant produksi
  (kredensial tidak pernah dicipta). Angka 45/124 ialah **tempatan**.
- Saya **tidak** membuktikan tiada satu pun daripada 45 itu mengganggu pengguna sebenar. Yang
  dibuktikan ialah popover tidak menutup sasarannya bagi guide W0; 39 yang baharu itu belum
  disemak satu per satu dengan mata.
- Saya **tidak** mengubah metrik, penjaga, atau pelan. Ini keputusan pemilik.

## 5. Pilihan yang dicadangkan (pemilik memutuskan)

⭐ Selepas bukti visual §3B, cadangan (1) tidak lagi bersandar pada mekanisme sahaja: pada kes
yang metrik ini tandakan merah, **sasaran kelihatan penuh dan tidak terlindung**, dan popover
berada betul-betul di atasnya. Gambar itu menunjukkan susun atur yang baik ditandakan sebagai
kecacatan.

1. **Bersara `centerCovered` sebagai gate; kekalkan sebagai pemerhatian.** Gantikan dengan
   penjaga "popover tidak menutup sasarannya sendiri" (sudah wujud, sudah dalam CI), diperluas
   daripada 2 guide W0 kepada kohort mobile penuh. — *cadangan saya*
2. Kekalkan `centerCovered` sebagai sasaran dan laraskan penempatan popover pada mobile
   (cth. paksa `side: 'bottom'` pada viewport sempit). Risikonya: popover berpindah menjauhi
   sasaran, iaitu kemunduran UX untuk memuaskan satu nombor.
3. Terima 45/124 sebagai `risk-accepted` dengan tarikh luput dan tiket.

Sehingga pemilik memutuskan, baris ini dilaporkan **TIDAK TERCAPAI** dalam jadual §9 — bukan
ditanda hijau, dan bukan didiamkan.

## Cara menghasilkan semula

```
skrip/ukur-mobile-kohort-f8.mjs   124 langkah kohort, definisi audit tepat
skrip/kawalan-mobile.mjs          tentukur metrik terhadap langkah bukan-cacat
skrip/ab-mobile.sh + ab-ukur.mjs  A/B katalog lama vs semasa (katalog dipulihkan dalam trap)
```
Data: `mobile-kohort-f8.json` · `ab-lama.json` · `ab-semasa.json` · `mobile-centercovered-f8.json`

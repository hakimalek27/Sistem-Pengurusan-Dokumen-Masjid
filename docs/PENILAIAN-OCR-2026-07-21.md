# Penilaian OCR — Tesseract/ocrmypdf (semasa) lawan Baidu Unlimited-OCR

**Tarikh:** 21 Julai 2026 · **Soalan pemilik:** *"Kita sekarang pakai OCR apa? Mana lebih OK — yang kita pakai sekarang atau `github.com/baidu/Unlimited-OCR`?"*

**Jawapan ringkas:** Bergantung pada ukuran — dan itulah dapatan sebenarnya.

- **Ketepatan aksara:** Unlimited-OCR menang telak — CER **4.2%** lawan **15.4%** (≈3.5× lebih tepat).
- **Maklumat sebenar yang sampai ke sistem:** hampir **seri** — 78.8% lawan 80.8% medan kunci. VLM sempurna pada 6 daripada 11 dokumen, tetapi **musnah** pada dua (pelan tapak 0%, jadual kuliah 53%).
- **Boleh pasang pada Diwan?** **Tidak.** Perlu GPU ~9 GB VRAM; pelayan ada **2 GB RAM tanpa GPU**.
- **Risiko baharu:** VLM gagal **secara senyap** — ia menukar `kecekapan` → `kecepatan` (disahkan visual), menggugurkan 4 daripada 7 hari dalam jadual, dan pada satu dokumen **terperangkap gelung selama 19 minit menghasilkan 70% sampah**. Tesseract gagal dengan bunyi bising yang jelas; VLM gagal dengan ayat yang fasih dan meyakinkan.

**Syor: kekalkan Tesseract.** Bukan kerana ia lebih baik — pada banyak dokumen ia memang lebih teruk — tetapi kerana ia **boleh dijalankan**, **boleh diramal**, dan untuk sistem pengurusan rekod rasmi, **gagal-dengan-jelas lebih selamat daripada gagal-dengan-fasih**.

---

## 1. Apa yang Diwan guna sekarang

Rujukan kod: `app/Jobs/ProcessOcrJob.php`, `docker/Dockerfile`, `config/diwan.php`.

| Jenis fail | Laluan | Enjin |
|---|---|---|
| PDF berteks asli | `pdftotext -layout` | **Tiada OCR** — teks diambil terus (tepat 100%) |
| Imej (JPG/PNG) | `img2pdf --rotation=ifvalid` → `ocrmypdf` | **Tesseract 5** |
| PDF imbasan | `ocrmypdf --skip-text` | **Tesseract 5** |
| Sandaran (tiada ocrmypdf) | `tesseract` terus / `pdftoppm`+`tesseract`+`pdfunite` | **Tesseract 5** |
| Office (DOCX/XLSX/PPTX) | `OfficeTextExtractor` | Bukan OCR — ekstrak XML |

Arahan sebenar produksi:

```
ocrmypdf --skip-text -l msa+eng --rotate-pages --deskew --clean \
         --optimize 1 --sidecar out.txt --output-type pdf in.pdf out.pdf
```

Bahasa `msa+eng` (`OCR_LANGS`). Jadi **enjin OCR sebenar Diwan ialah Tesseract 5**; `ocrmypdf` hanyalah pembungkus yang menambah pra-proses (luruskan, putar, bersih) dan menghasilkan PDF boleh-cari.

## 2. Apa itu Unlimited-OCR

Model **vision-language 3 bilion parameter** dari Baidu (lesen MIT), seni bina DeepSeek-V2 + `deepencoder` — pewaris DeepSeek-OCR. Ia bukan pustaka OCR biasa: ia model AI yang "membaca" halaman dan mengeluarkan Markdown berstruktur (jadual jadi HTML, rajah jadi rujukan imej).

- Saiz berat: **6.67 GB** (BF16) · perlu **GPU CUDA**
- Berjalan melalui Hugging Face Transformers (`trust_remote_code`), vLLM, atau SGLang
- Juga ditawarkan sebagai API Baidu Cloud

## 3. Kaedah ujian

Semua ujian dijalankan **100% pada mesin ini** (RTX 3070 Ti 8GB, 32GB RAM). **Tiada satu dokumen pun dihantar ke perkhidmatan luar** — penting kerana korpus mengandungi data kewangan masjid.

Tiga set, **22 halaman/imej**, semuanya **dokumen sebenar** dari PC ini (bukan fixture buatan):

| Set | Kandungan | Rujukan kebenaran |
|---|---|---|
| **A** — 11 halaman | PDF digital (surat saguhati, sebut harga, perlembagaan PERKIB, cadangan CCTV, Tatacara ANM) di-raster 200 DPI | `pdftotext` pada PDF asal = kebenaran mutlak → kira **CER/WER** |
| **B** — 9 imej | Foto & poster sebenar: sebut harga foto telefon, poster BKPM MAIWP, **jadual kuliah masjid**, carta organisasi (Jawi), infografik, **pelan tapak masjid**, hebahan, borang elaun, logo Jawi | **Medan kunci** yang saya baca sendiri dari imej (184 medan) |
| **C** — 2 halaman | **Penyata bank masjid** — imbasan tulen, sifar lapisan teks, tiga bahasa | Medan **struktur borang** sahaja (lihat nota PDPA) |

Tiga enjin dibandingkan pada **imej yang sama**: `ocrmypdf` (paip produksi), `tesseract` tulen (laluan sandaran), dan Unlimited-OCR.

> **Nota PDPA.** Set C ialah penyata bank sebenar dengan nombor akaun, baki dan **nama penderma individu**. Ia digunakan kerana ia kes ujian paling realistik untuk Diwan, tetapi: pemprosesan 100% luar talian, pemarkahan guna **label borang sahaja** (`Wang Masuk`, `Baki`, `TARIKH PENYATA`…), **tiada kandungannya dipetik** dalam laporan ini, dan semua fail terbitan dipadam selepas audit.

### Kesahihan alat (disemak dahulu, bukan diandaikan)

- **Penskor medan kunci diuji** terhadap 10 kes positif/negatif buatan → **10/10 betul** (tidak terlalu longgar, tidak terlalu ketat).
- Penormalan **sama** untuk kedua-dua enjin sebelum banding: huruf kecil, buang tag HTML/Markdown, nyahkod entiti, mampat whitespace. Ini supaya VLM tidak diberi kelebihan/penalti kerana *format*, hanya *ketepatan aksara*.

## 4. Keputusan Set A — ketepatan aksara (CER)

CER = kadar silap aksara. **Rendah lebih baik.**

| Halaman | Jenis | ocrmypdf | tesseract | **Unlimited-OCR** |
|---|---|---|---|---|
| `saguhati-p1` | Surat rasmi BM | 1.0% | 3.7% | 19.6% ⚠️ |
| `q2671-p1` | Sebut harga (ada logo) | 23.0% | 6.2% | 18.6% ⚠️ |
| `q2672-p1` | Sebut harga (ada logo) | 20.1% | 26.1% | **6.1%** |
| `perlembagaan-p1` | Perlembagaan | 6.2% | 6.2% | **0.0%** |
| `perlembagaan-p2` | Perlembagaan | 13.8% | 15.2% | **0.0%** |
| `perlembagaan-p3` | Perlembagaan padat | 0.8% | 1.5% | **0.5%** |
| `cctv-p3` | Senarai kandungan | 29.2% | 29.0% | **0.4%** |
| `cctv-p4` | Perenggan biasa | **0.0%** | **0.0%** | 0.7% |
| `cctv-p5` | Halaman rajah penuh | 72.0% | 72.0% | **0.0%** |
| `ddms-p12` | Tatacara ANM | 1.0% | 1.0% | **0.0%** |
| `ddms-p13` | Tatacara ANM | 2.5% | **0.0%** | 0.1% |

**Purata:**

| Enjin | CER semua | WER semua | CER teks-tulen (8 hlm) |
|---|---|---|---|
| ocrmypdf (produksi) | 15.4% | 21.5% | 6.8% |
| tesseract sahaja | 14.6% | 20.8% | 7.1% |
| **Unlimited-OCR** | **4.2%** | **5.7%** | **2.7%** |

**Unlimited-OCR ~3.5× lebih tepat secara keseluruhan, ~2.5× pada teks tulen.**

### Dua kaveat penting supaya angka ini tidak disalah tafsir

1. **Dua "kekalahan" VLM (`saguhati-p1` 19.6%, `q2671` 18.6%) sebenarnya bukan kesilapan bacaan.** VLM menyusun semula kandungan menjadi **jadual HTML** yang menggabungkan sel yang `pdftotext` pecahkan merentas baris. Kandungannya betul — cuma susunannya berbeza daripada rujukan. Ketepatan sebenar VLM **lebih baik** daripada 4.2% yang dilaporkan.
2. **`cctv-p5` (72% bagi kedua-dua enjin klasik)** ialah artifak metodologi terbalik: halaman itu sebahagian besarnya **rajah**. Tesseract membaca label di dalam rajah (berguna untuk carian!) tetapi dihukum kerana `pdftotext` tidak mengekstraknya. VLM melabel rajah sebagai imej dan melangkaunya — jadi ia "menang" CER dengan membaca **kurang**.

## 5. Keputusan Set B + C — medan kunci ditemui

Metrik ini lebih dekat dengan soalan sebenar: *"adakah maklumat yang saya cari sampai ke dalam sistem?"*

| Dokumen | ocrmypdf | tesseract | **Unlimited-OCR** |
|---|---|---|---|
| `B1` sebut harga (foto telefon) | 18/19 (95%) | 18/19 (95%) | **19/19 (100%)** |
| `B2` poster BKPM MAIWP | 26/30 (87%) | **28/30 (93%)** | 24/30 (80%) |
| `B3` **jadual kuliah masjid** | 20/34 (59%) | **23/34 (68%)** | 18/34 (53%) ⚠️ |
| `B4` carta organisasi (Jawi) | 16/20 (80%) | 16/20 (80%) | **20/20 (100%)** |
| `B5` infografik saguhati | 26/29 (90%) | 26/29 (90%) | **29/29 (100%)** |
| `B6` **pelan tapak masjid** | **2/24 (8%)** | 1/24 (4%) | 0/24 (0%) ⚠️ |
| `B7` hebahan PERKIB | 22/22 (100%) | 22/22 (100%) | 22/22 (100%) |
| `B8` borang elaun | 5/5 (100%) | 5/5 (100%) | 5/5 (100%) |
| `B9` logo Jawi | 1/1 (100%) | 1/1 (100%) | 1/1 (100%) |
| `C1` **penyata bank (imbasan)** | 26/28 (93%) | **28/28 (100%)** | 26/28 (93%) |
| `C3` penyata bank (imbasan) | 26/28 (93%) | **26/28 (93%)** | 25/28 (89%) |
| **KESELURUHAN** | 188/240 (78.3%) | **194/240 (80.8%)** | 189/240 (78.8%) |

**Keseluruhannya hampir seri** — tetapi taburannya sangat berbeza, dan itulah yang penting:

- **Unlimited-OCR sempurna (100%) pada 6 daripada 11 dokumen**, termasuk foto telefon berkualiti rendah dan carta organisasi Jawi yang mengalahkan Tesseract dengan jelas.
- **Tetapi ia jatuh ke 0% pada pelan tapak** dan **53% pada jadual kuliah** — dua kegagalan besar yang menarik puratanya turun.
- **Tesseract tidak pernah cemerlang, tetapi jarang musnah sepenuhnya.** Ia paling konsisten, dan ia **paling baik pada penyata bank imbasan** (100%) — kes guna teras Diwan.

## 6. Dapatan kritikal — dua corak kegagalan yang BERBEZA

Ini penemuan paling penting dalam keseluruhan ujian, dan ia mengubah syor.

### Tesseract gagal dengan KUAT (jelas rosak)

```
dokumen  →  ounrwbnro
persekutuan  →  persekutuan□
LED Spotlight 100W  →  LED Spotight 1COW
Kontraktor: Musreen Enterprise  →  Kontrado': Musreen Enterpris2
```

Sampah yang jelas. Manusia nampak ia salah. Carian gagal, tetapi **tiada siapa tertipu**.

### Unlimited-OCR gagal dengan SENYAP (fasih tetapi salah)

Analisis substitusi merentas 8 halaman teks-tulen menemui **5 gantian senyap** (perkataan sah ditukar kepada perkataan sah lain):

| Dokumen asal | Output VLM | Kesan |
|---|---|---|
| `kecekapan` | `kecepatan` | **Makna berubah** (efisiensi → kelajuan) |
| `ertinya` | `artinya` | Ejaan Melayu → Indonesia |
| `permuafakatan` | `permufakatan` | Ejaan Melayu → Indonesia |
| `sekotor` | `sektor` | Model "membetulkan" teks asal |
| `lekukan` | `lakukan` | Model "membetulkan" teks asal |

**Bukti visual disahkan** untuk kes pertama — potongan imej sebenar `Tatacara ANM hlm 13` jelas tertulis:

> `...secara berjadual bagi meningkatkan kecekapan dan kemahiran...`

Tesseract membacanya **betul**. Unlimited-OCR menghasilkan `kecepatan`. Ayat itu kekal fasih sempurna — **tiada apa-apa dalam sistem yang akan menandakannya sebagai salah**.

Ini corak yang dijangka daripada model bahasa: ia mempunyai *prior* tentang perkataan yang "sepatutnya" ada, dan prior itu condong ke Bahasa Indonesia (data latihan). Untuk sistem **pengurusan rekod rasmi**, ini serius — Diwan sepatutnya menyimpan rekod **sebagaimana adanya**, termasuk kesilapan penulisnya. Enjin yang "membetulkan" dokumen sedang memalsukan rekod.

### Kelemahan VLM yang lain

| Isu | Bukti |
|---|---|
| **🔴 Kemerosotan gelung (paling serius)** | Lihat di bawah — 70% output pada jadual kuliah ialah sampah berulang |
| **Pelan/lukisan teknikal langsung diabaikan** | Pelan tapak masjid → VLM keluarkan `![](images/0.jpg)` sahaja, **0 teks**. Tesseract yang lemah pun (2/24) masih mengalahkannya |
| **Bahagian dokumen digugurkan senyap** | Jadual kuliah: 4 daripada 7 tajuk hari (`JUMAAT`, `SABTU`, `AHAD`, `RABU`) hilang terus; poster BKPM kehilangan 4 medan yang Tesseract jumpa |
| **VRAM melebihi kad** | Puncak **8.89 GB** pada kad 8.59 GB → limpah ke RAM sistem (perlahan mendadak) |
| **Ralat encoding Windows** | 3 imej gagal simpan (`UnicodeEncodeError`) sehingga UTF-8 dipaksa — kerana ia mengeluarkan skrip Arab/Jawi |

### 🔴 Kemerosotan gelung — kegagalan yang tidak boleh berlaku pada Tesseract

Pada jadual kuliah masjid (imej padat 2560×1810), model **terperangkap dalam gelung penjanaan**:

```
jumlah output      : 11,809 aksara / 1,535 baris
baris ≤2 aksara    : 1,070  (70% daripada output)
baris berulang     : "B" × 802,  "J" × 260,  frasa rosak × 200
kandungan berguna  : ~1% pertama sahaja, kemudian merosot
tamat              : terpotong di tengah token  <|det|>image_caption [603,
masa               : 1,140 saat (19 minit)
```

Ini berlaku **walaupun** parameter anti-ulangan README (`no_repeat_ngram_size=35`, `ngram_window=128`) digunakan. Model membakar had 32,768 token menjana sampah.

⚠️ **Pembetulan kepada tanggapan awal saya:** semasa ujian, saya mula-mula membaca "11,809 aksara berbanding 2,627 Tesseract" sebagai bukti VLM mengekstrak 4.5× lebih banyak kandungan. **Pemeriksaan menunjukkan itu salah** — majoriti adalah ulangan. Angka mentah menipu; ia perlu diperiksa.

Tesseract **tidak boleh** gagal begini. Ia bersifat determinisik dan terbatas: ia mungkin menghasilkan sampah untuk satu perkataan, tetapi tidak akan terperangkap 19 minit atau memusnahkan 70% dokumen.

### Kelebihan VLM yang nyata

| Kelebihan | Bukti |
|---|---|
| **Struktur jadual dikekalkan** | Sebut harga foto telefon → jadual HTML 6 baris lengkap dengan kuantiti/harga tepat; Tesseract hasilkan teks rata bercelaru |
| **Maklumat yang klasik tercicir** | **Nombor akaun bank `1050-4000-1582`** dalam jadual kuliah — Tesseract tercicir sepenuhnya, VLM jumpa |
| **Foto berkualiti rendah** | `Kontraktor: Musreen Enterprises` + `SSM 202003225658 (003155015-H)` tepat (19/19 medan), sedangkan Tesseract rosakkan kedua-duanya |
| **Skrip Arab/Jawi** | Mengeluarkan Jawi & Arab berdiakritik (`Unwānul Falah`); Tesseract `msa+eng` langsung tak boleh. Carta organisasi Jawi: **20/20** lawan 16/20 |
| **Susunan bacaan** | Senarai kandungan berbintik: Tesseract → `PONAARWNE`; VLM → senarai bernombor betul |
| **Rajah diasingkan** | Halaman berajah → rajah ditanda `![](images/N.jpg)`, teks kekal bersih; Tesseract membuang serpihan rajah ke dalam teks |

### Prestasi diukur (RTX 3070 Ti, 22 imej)

| Metrik | Nilai |
|---|---|
| Muat model | 5.7 s (VRAM 6.77 GB sebaik dimuat) |
| Masa/imej | min **8 s** · median **42 s** · purata **91 s** · maks **1,140 s** |
| VRAM puncak | **8.89 GB** (melebihi kad 8.59 GB) |
| Jumlah | 33.5 minit untuk 22 imej |

Bandingkan Tesseract: **0.7–16 saat** setiap imej, pada CPU, tanpa GPU.

## 7. Bolehkah ia dipasang pada Diwan?

**Tidak pada pelayan sekarang.**

| Keperluan | Unlimited-OCR | Pelayan Diwan (Tencent Lighthouse) |
|---|---|---|
| GPU | NVIDIA CUDA, ~8 GB VRAM | **Tiada GPU** |
| RAM | >8 GB | **2 GB** |
| Saiz model | 6.67 GB | Cakera 30 GB (muat, tapi tak berguna tanpa GPU) |

Diukur pada mesin ini: **puncak VRAM 8.09 GB** — hampir menepu kad 8GB. Purata 38 saat/halaman, maksimum **19 minit** untuk satu jadual padat.

### Pilihan jika mahu kualiti VLM

| Pilihan | Kos/kesan | Penilaian |
|---|---|---|
| **A. Kekal Tesseract** | RM0 | Selamat, laju, berjalan sekarang |
| **B. Sewa pelayan GPU** | T4 awan ~USD 0.06–4.35/jam ([rujukan](https://getdeploying.com/gpus/nvidia-t4)); sentiasa hidup ≈ USD 44–300+/bulan | Berkali ganda kos pelayan sekarang untuk SaaS masjid |
| **C. API Baidu Cloud** | Bayar per panggilan | ⛔ **Dokumen dihantar ke pelayan luar** — penyata bank & nama penderma. Halangan PDPA serius |
| **D. GPU atas permintaan / kelompok** | Beban Diwan kecil (~puluhan dokumen/bulan ≈ beberapa minit GPU) | Paling munasabah dari segi kos, tapi perlu kerja kejuruteraan |
| **E. Guna PC ini untuk kes sukar** | RM0 (GPU sudah ada) | Boleh: eksport dokumen bermasalah → OCR lokal → import teks. Manual, tapi percuma |

## 8. Penambahbaikan PERCUMA yang ditemui semasa ujian ini

Diuji, bukan diteka:

1. **300 DPI (bukan 200) untuk dokumen bercetak halus.** Pada halaman berlogo/cop kecil, CER jatuh **16.2% → 3.8%**. Pada teks tulen tiada beza (7.08% → 7.03%). Kos: imej 2.25× lebih besar (perlu diuji terhadap RAM 2GB pelayan).
2. **`--deskew`/`--rotate-pages` mungkin merugikan raster digital yang sudah lurus.** Tesseract tulen (80.8% medan kunci) mengatasi paip ocrmypdf penuh (78.3%). Beza kecil, tetapi wajar diuji berasingan.

⚠️ Kedua-duanya **cadangan untuk diuji**, bukan perubahan yang saya sudah buat. Tiada kod Diwan disentuh dalam sesi ini.

## 9. Syor

**Kekalkan Tesseract/ocrmypdf buat masa ini.** Empat sebab, mengikut berat:

1. **Halangan keras:** pelayan tiada GPU dan hanya 2 GB RAM. Model perlu ~9 GB VRAM. Ini bukan soal tala — ia tidak akan berjalan langsung.
2. **Kebolehpercayaan:** kemerosotan gelung 19 minit dengan 70% sampah bukan kes tepi yang boleh diabaikan — ia berlaku pada **jadual kuliah masjid**, iaitu tepat jenis dokumen yang Diwan wujud untuk uruskan.
3. **Integriti rekod:** substitusi senyap (`kecekapan`→`kecepatan`) dan pengguguran bahagian dokumen bermakna sistem menyimpan rekod yang **berbeza daripada dokumen asal**, dalam bahasa yang fasih sehingga tiada siapa akan menyedarinya. Untuk arkib rasmi masjid, ini tidak boleh diterima.
4. **Tesseract sudah memadai untuk kes teras:** surat rasmi, borang, hebahan, dan **imbasan penyata bank** semuanya **93–100%** medan kunci. Kelemahannya tertumpu pada pelan/lukisan teknikal — di mana VLM lebih teruk lagi (0%).

**Apa yang VLM ini betul-betul lebih baik** (untuk rekod, jika ditinjau semula kemudian): foto telefon berkualiti rendah, dokumen berjadual, dan **skrip Jawi/Arab**. Jika suatu hari intake WhatsApp dibanjiri foto telefon, dapatan ini patut ditimbang semula.

**Yang patut dibuat seterusnya (ikut keutamaan):**

1. Uji **300 DPI** untuk imbasan — pembaikan percuma yang terbukti pada dokumen bercetak halus.
2. Terima bahawa **pelan/lukisan teknikal tidak akan boleh dicari** melalui OCR; sandarkan pada tajuk/metadata untuk dokumen jenis itu.
3. **Semak semula dalam 6–12 bulan.** Bidang ini bergerak laju; model VLM yang lebih kecil (atau kuantisasi 4-bit model ini) mungkin muat pada CPU/GPU murah.
4. Jika pemilik mahu kualiti VLM sekarang: **Pilihan E** (guna PC ini untuk dokumen sukar) ialah satu-satunya yang berkos sifar dan tidak melanggar PDPA.

---

## Lampiran A — persekitaran ujian

| Item | Nilai |
|---|---|
| GPU | NVIDIA RTX 3070 Ti, 8 GB · pemacu 595.95 |
| RAM / cakera | 32 GB / 318 GB bebas |
| Python | 3.12.10 · torch **2.11.0+cu128** · transformers **4.57.1** (dipin) |
| Tesseract | **5.5.0** + `msa`/`eng`/`osd` traineddata |
| ocrmypdf / img2pdf | **17.8.1** / **0.6.3** |
| Model | `baidu/Unlimited-OCR`, 6.67 GB BF16, konfigurasi *gundam* (base 1024, imej 640, crop) |

## Lampiran B — kaveat & masalah yang ditemui

- **`transformers` 5.x tidak serasi** dengan kod model (`is_torch_fx_available` dibuang) → dipin ke 4.57.1 seperti README.
- **`hf_transfer` menggantung** muat turun pada 1.2 GB tanpa ralat → dimatikan; muat turun biasa berjaya.
- **`--clean` digugurkan** pada ujian tempatan kerana `unpaper` tiada pada Windows. Enjin teras (Tesseract) sama; imej Docker produksi mempunyai `unpaper` jadi produksi tidak terjejas.
- **Tesseract scoop tiada folder `configs/`** (`hocr`, `pdf`, `txt`, `tsv`) — dibina secara berasingan untuk ujian. Imej Docker produksi (pakej Debian) sudah lengkap; **ini jurang persekitaran Windows, bukan defek Diwan.**
- **Ujian OCR repo dijalankan:** `php artisan test --filter=Ocr` → **12 lulus, 1 dilangkau**. Paip semasa sihat.

## Lampiran C — kebersihan data

Semua fail terbitan OCR (termasuk semua output penyata bank dan borang elaun) dipadam selepas laporan ini disiapkan. Tiada kandungan dokumen peribadi disimpan dalam repo. Model dan persekitaran ujian kekal di direktori scratchpad sesi, di luar repo.

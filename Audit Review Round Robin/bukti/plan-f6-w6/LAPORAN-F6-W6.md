# Laporan Fasa F6-W6 — gelombang TERAKHIR F6 (`public`)

## (a) Ringkasan

W6 menutup **dua langkah generik terakhir dalam seluruh katalog**: `public.help#1` dan
`public.help#2`, kedua-duanya pada `/bantuan` (halaman tetamu). Dengan itu **`action generic`
dan `placeholder` kekal 0, `generic_declared` jatuh 61 → 59, dan setiap langkah generik yang
tinggal membawa justifikasi eksplisit bertarikh** — W6 menyertai `justified_waves`, jadi
KESEMUA tujuh gelombang kini tertutup.

Dua sasaran baharu dicipta pada komponen `help-center` yang DIKONGSI tiga panel:
`help-search-form` (borang carian, 70px) dan `help-scope` (baris "Skop panduan: …", 20px).
Kedua-duanya dipilih **selepas mengukur DOM produksi live**, bukan daripada membaca Blade.

## (b) Fail dicipta / diubah

```
resources/views/livewire/help-center.blade.php        (+2 data-help-target, +komen sebab)
resources/help/guides.json                            (public.help 2 langkah; catalog_version)
resources/help/targets.json                           (+2 entri aktif; 223 -> 225)
docs/HELP-TARGETS.md                                  (DIJANA semula)
Audit Review Round Robin/bukti/plan-baseline/manifest.json   (DIJANA semula)
Audit Review Round Robin/bukti/plan-baseline/tools/build-manifest.mjs   (justified_waves +W6)
scripts/audit/validate-plan-manifest.mjs                                (JUSTIFIED_WAVES +W6)
tests/Feature/PlanManifestTest.php                                      (senarai +W6)
tests/Feature/Help/W6TargetRenderTest.php             (BAHARU — 5 ujian)
Audit Review Round Robin/bukti/plan-f6-w6/INVENTORI-W6.md   (DIJANA)
Audit Review Round Robin/bukti/plan-f6-w6/skrip/{inventori-w6.mjs,sunting-w6.mjs,gate-w6.sh}
```

## (c) Output verifikasi sebenar

Inventori **DIJANA** (peraturan yang W5 bayar mahal — jadual W5 versi pertama ditaip tangan
dan tersilap kira 16 langkah):

```
**Guide W6: 3 · langkah: 8 · bersasar generik: 2**
generic_keys: ["public.help#1", "public.help#2"]
routes: ["/daftar", "/log-masuk", "/bantuan"]
```

Sunting katalog + registri:

```json
{ "registri_ditambah": 2, "langkah_diubah": 2, "catalog_version": "2026.08.08.1",
  "guide": 83, "langkah": 473, "generik_baki": 59,
  "registri_jumlah": 225, "registri_aktif": 201 }
```

Manifest dijana semula (prosedur `tools/README.md` langkah 1–4 penuh, termasuk seed sqlite
sementara + `diwan:role-routes`):

```
generic_declared 443 → 59 (−384)
placeholder_titles 258 → 0 (−258)
action_steps_with_generic_target 200 → 0 (−200)
Justifikasi eksplisit: 59 langkah; wave tertutup W0, W1, W2, W3, W4, W5, W6 liputan PENUH.
OK: manifest ditulis — guides=83 steps=473 actionGeneric=0 placeholder=0
    waves=W0:2g/10s W1:0g/0s W2:0g/0s W3:29g/151s W4:14g/158s W5:35g/146s W6:3g/8s
```

Validator BEBAS + dok registri:

```
OK: manifest sah — partition wave/shard sepadan pengiraan bebas, set-union exact,
    role_routes konsisten.                                     (exit 0)
OK: docs/HELP-TARGETS.md dijana (201 aktif + 24 rizab).        (exit 0)
```

Suite penuh: **`Tests: 1 skipped, 606 passed (5768 assertions)`** (601 → 606).
`vendor/bin/pint --dirty` → fixed lalu passed.
`npm run build` → **aset TIDAK berubah** (`help-D_qumira.js` + `help-Cfwb6f_j.css`) kerana W6
menyentuh Blade + JSON sahaja. Itu ramalan untuk Deploy 12: bukti mesti **kandungan dalam
imej** + ImageID berubah, bukan nama aset (corak yang sudah terbukti pada D2, D7, D8).

### Penjaga dibuktikan DUA arah

`tests/Feature/Help/W6TargetRenderTest.php` — 5 ujian, sengaja **tanpa autentikasi** untuk
langkah awam (jika sasaran hanya wujud selepas log masuk, W6 tidak menyelesaikan apa-apa).

Regresi sengaja: `data-help-target="help-scope"` dibuang daripada baris skop dan dipindahkan
ke seksyen pembungkus — iaitu tepat "pembaikan mudah" yang akan memusnahkan makna sorotan.

```
regresi dipasang  -> 3 failed, 2 passed
pulih             -> 5 passed (15 assertions)
```

Ujian katalog (#4) sengaja LULUS dalam kedua-dua keadaan: ia menjaga katalog, bukan DOM.
Perbezaan itu disengajakan — dua penjaga berlainan lapisan tidak sepatutnya gagal bersama.

## (d) Kriteria siap

| Item | Status |
|---|---|
| Inventori DIJANA daripada skrip (bukan ditaip) | ✔ |
| Dua langkah generik terakhir ditutup | ✔ `public.help` 2 → 0 |
| Sasaran dipilih daripada UKURAN DOM live, bukan bacaan Blade | ✔ |
| Sasaran KETAT (bukan pembungkus) | ✔ 70px + 20px lawan 1199px |
| Registri dikemas + `HELP-TARGETS.md` dijana | ✔ 225 (201 aktif · 24 rizab) |
| Yatim dua hala = 0 | ✔ validator exit 0 |
| `W6` ditambah ke `justified_waves` dalam TIGA penjaga | ✔ |
| Manifest dijana semula + validator bebas hijau | ✔ |
| Penjaga baharu dibuktikan dua arah | ✔ merah → hijau |
| Pest / pint / build | ✔ 606✓/1 skip · passed · exit 0 |
| Gate 3 shard + agregator | (di bawah) |

## (e) Lencongan dari spec

**TIADA.** §7.2 memperuntukkan W6 = `public`, 3 guide / 8 langkah; itu tepat yang dibuat.

## (f) Nota & risiko untuk fasa seterusnya

### 1. ⭐ `help-search-form` kini WUJUD — F7 boleh menutup hutang §17 W5 dengan dua baris

LAPORAN-F6-W5.md §17 merekod bahawa `tenant.bantuan#1` dan `admin.bantuan#1` menyasar
`help-search`, iaitu seksyen **3211px** pada panel tenant — sah tetapi hampir tidak dapat
dibezakan daripada menyorot seluruh halaman. Sasaran ketat itu kini ada.

**Suntingan tepat untuk F7** (sengaja TIDAK dibuat dalam W6 kerana kedua-dua guide itu milik
gelombang W5 dan skop W6 ialah `public`):

```
resources/help/guides.json
  tenant.bantuan  langkah 1: "help-search" -> "help-search-form"
  admin.bantuan   langkah 1: "help-search" -> "help-search-form"
```

Kedua-duanya sudah `specific`, jadi tiada denominator atau wave yang bergerak — tetapi katalog
berubah, jadi ia memerlukan pusingan gate penuh + `sync-help-index --delete`.

### 2. ⚠️ Penemuan KANDUNGAN — `public.help#2` bercakap bahasa dalaman kepada orang awam

Arahan langkah itu berbunyi *"Pastikan panel, tenant dan role semasa adalah betul."*
Pada `/bantuan`, pelawat **tiada panel, tiada tenant dan tiada role** — halaman itu sendiri
menulis "Skop panduan: **Orang Awam**". Sasaran `help-scope` menjawab niat langkah dengan
setia, tetapi AYATNYA menggunakan istilah yang tidak bermakna kepada pembacanya.

Ini keluarga yang sama seperti penemuan `tenant.kelulusan` (W5 §2): **bukan jurang data,
bukan jurang sasaran — jurang kandungan.** Diserahkan kepada **F9** (regenerasi Manual
Pengguna memiliki teks), bukan dibaiki di sini, kerana W6 ialah gelombang sasaran.
Cadangan ayat: *"Semak baris skop: panduan yang dipaparkan hanya untuk Orang Awam."*

### 3. Semua tujuh gelombang kini TERTUTUP

`justified_waves` = `W0…W6`. Selepas ini, mana-mana langkah generik BAHARU dalam mana-mana
wave akan menggagalkan penjanaan manifest melainkan ia membawa justifikasi bertarikh. Itu
menjadikan katalog gagal-tertutup terhadap kemerosotan — sifat yang F6 wujud untuk membina.

---

## PENGESAHAN VISUAL — diukur DAN dipandang (pelayan tempatan, viewport 1280×900)

Diukur **7 saat selepas muat**, iaitu selepas tetingkap morph Livewire yang memusnahkan
sorotan sebelum W5d:

```
langkah 1: help-search-form  FORM  y=231 w=1008 h= 70   3% tinggi <main>
           teks dalam sorotan: "Apa yang anda mahu lakukan? Cari"
           popover "Buka fungsi" · 1 daripada 2 · ralat palsu: tiada

langkah 2: help-scope        DIV   y=198 w=1008 h= 20   1% tinggi <main>
           teks dalam sorotan: "Skop panduan: Orang Awam Versi 2026.08.08.1 …"
           popover "Sahkan skop" · 2 daripada 2 · ralat palsu: tiada
```

**Sebelum W6 kedua-dua langkah menyorot `page-content`, iaitu 100% tinggi `<main>`.**
Jadi metrik yang bermakna bukan "sasaran spesifik" (ia sudah boleh dicapai dengan sasaran
pembungkus) tetapi **100% → 3%** dan **100% → 1%**.

Skrinsyot mengesahkan pengalaman sebenar: borang carian menyala terang manakala selebihnya
halaman malap; pengguna nampak SERTA-MERTA ke mana hendak melihat. Itu tidak pernah benar
apabila sorotan meliputi seluruh halaman.

### ⭐ Skrinsyot langkah 2 MEMBUKTIKAN penemuan kandungan (f)(2) secara visual

Dalam satu paparan, popover berbunyi *"Pastikan panel, tenant dan role semasa adalah betul"*
sementara baris yang disorot berbunyi *"Skop panduan: **Orang Awam**"*. Ketidakpadanan itu
tidak dapat dilihat daripada katalog atau daripada mana-mana ujian automatik — ia hanya
kelihatan apabila sasaran menjadi cukup ketat sehingga mata boleh membandingkan **ayat
langkah** dengan **perkara yang disorot** serentak.

🔑 **Sasaran yang lebih ketat bukan sahaja pengalaman yang lebih baik — ia alat AUDIT.**
Sorotan seluruh halaman menyembunyikan ketidakpadanan makna; sorotan 20px mendedahkannya.
Ini sebab tambahan untuk menutup hutang `admin.*` (W5 §10) dalam F7.

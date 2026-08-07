# Laporan — Hutang F7: sasaran bantuan + semantik `admin.*`

**Tarikh:** 8 Ogos 2026 · **Asas:** `c94f1ba` (F7 LIVE sebagai Deploy 13 `774f9ab`)
**Skop:** enam langkah katalog menukar sasaran. Tiada langkah ditambah atau dibuang; bilangan
guide/langkah/generik kekal 83/473/59.

## (a) Ringkasan

Dua hutang direkod semasa F7 ditutup, kedua-duanya kelas yang sama — **sasaran yang SAH tetapi
tidak BERMAKNA**:

1. `tenant.bantuan#1` / `admin.bantuan#1` menyorot `help-search`, satu `<section>` yang meliputi
   hampir seluruh lajur kandungan. Diukur: **tenant 1056×3211 = 70% tinggi `<main>`**,
   **admin 1056×1421 = 53%**. Sorotan sebegitu hampir tidak dapat dibezakan daripada menyorot
   seluruh halaman.
2. Tiga langkah `admin.*` (W5 §10) menyorot **kotak carian 211×36** sedangkan ayatnya
   menerangkan **tindakan baris** — cth `admin.mosques#2`: "Semak, lulus, gantung atau pulihkan
   tenant".

Kedua-duanya ditutup dalam satu batch kerana perubahan katalog memerlukan satu pusingan gate
penuh; memisahkannya bermakna membayar dua kali.

## (b) Fail diubah

```
resources/help/guides.json                     enam sasaran + catalog_version -> 2026.08.08.2
resources/help/targets.json                    +2 aktif · 3 active -> reserved (yatim)
resources/js/help/page-target-plan.js          +2 pemetaan (mosques, users)
tests/Feature/Help/PageTargetSelectorTest.php  penjaga drift dibaiki + 2 sauh admin
e2e/page-target-plan.spec.js                   jangkaan pemetaan /admin/mosques dikemas
docs/HELP-TARGETS.md                           DIJANA (200 aktif + 27 rizab)
Audit Review Round Robin/bukti/plan-baseline/manifest.json   DIJANA
```

## (c) Keputusan yang diukur, bukan dipilih

| Calon | Tenant | Admin | Keputusan |
|---|---|---|---|
| `help-search` `<section>` | 1056×3211 (70%) | 1056×1421 (53%) | ✘ terlalu luas |
| **`help-search-form`** `<form>` | 1056×66 (1%) | 1056×66 (2%) | ✔ dipilih |
| **`help-scope`** `<div>` | 1056×20 (0%) | 1056×20 (1%) | ✔ dipilih |
| `.fi-ta-actions` | mosques 593×20 · users 186×20 | | ✘ jalur nipis |
| **sel `<td>` tindakan** | mosques 629×105 · users 222×57 | | ✔ dipilih |

⚠️ `.fi-ta-actions` DITOLAK atas ukuran, bukan gaya: 20px tinggi tidak melitupi butang yang
membalut ke baris kedua. Itu tepat kecacatan `disposal-actions` yang W4 bayar — sorotan sah
yang tidak bermakna.

`admin.storage-orders#2` **SENGAJA KEKAL** pada sasaran carian: benih mempunyai **0 baris**
(diukur), jadi sasaran baris tidak akan wujud dan gate hijau bermakna "tiada yang diuji"
— pelajaran W4 (butang Laksana yang tidak pernah dirender).

Langkah **2** kedua-dua panduan bantuan turut diselaraskan dengan `public.help` yang W6 sudah
reka (`nav-primary` → `help-scope`). Ayatnya berbunyi "Pastikan panel, tenant dan role semasa
adalah betul" dan `help-scope` ialah baris yang MENYATAKAN skop itu; `nav-primary` hanya
menyiratkannya. Ini melebihi hutang yang direkod — dinyatakan di sini, bukan diselitkan.

## (d) Pengesahan visual — 6/6 SEBELUM gate

Dijalankan sebelum melabur 40 minit pada gate, supaya sasaran yang salah tidak membakar satu
pusingan penuh.

```
tenant.bantuan#1  help-search-form <form> 1056x66  = 1%   "Apa yang anda mahu lakukan? Cari"
tenant.bantuan#2  help-scope       <div>  1056x20  = 0%   "Skop panduan: Admin / Kerani …"
admin.bantuan#1   help-search-form <form> 1056x66  = 2%   "Apa yang anda mahu lakukan? Cari"
admin.bantuan#2   help-scope       <div>  1056x20  = 1%   "Skop panduan: Pentadbir Platform …"
admin.mosques#2   platform-mosques-actions <td> 629x105 = 13%
                  "Paparan Sunting Arkibkan Gantung Ubah Kuota Masuk Panel Masjid"
admin.users#2     platform-users-actions   <td> 222x57  = 4%   "Sunting Nyahaktifkan"
```

`Versi 2026.08.08.2` dalam teks yang disorot membuktikan katalog BAHARU yang berkuat kuasa,
bukan cache.

## (e) 🔴 Penjaga yang TIDAK BOLEH GAGAL — ditemui dan dibaiki

`PageTargetSelectorTest` mempunyai penjaga drift yang memastikan setiap selektor dalam
`page-target-plan.js` mempunyai sauh yang diuji. Regexnya:

```php
preg_match_all("/\['(\.[a-z0-9-]+)',\s*'[a-z-]+'\]/i", $modul, $padanan);
```

Ia hanya memadan selektor **kelas** (`\.`). Sebaik hutang ini memperkenalkan selektor
**struktur** (`tbody tr:first-child td:last-child`), penjaga itu akan lulus tanpa meliputinya
langsung — penjaga yang tidak boleh gagal.

⚠️ Ini **keluarga yang sama** seperti kecacatan §8.1 dalam F7 sendiri (tetingkap 400 bait yang
tidak pernah mengandungi elemen yang dicari). Corak berulang: penjaga yang ditulis untuk bentuk
data SEMASA, yang senyap apabila bentuk itu berkembang.

**Dibuktikan dua arah:** buang entri daripada `$diuji` → merah 1.41s dengan mesej yang betul;
pulihkan → hijau.

## (f) Verifikasi

```
Pest              622 lulus / 1 skip (5821 assertion)   EXIT=0
unit JS           33/33                                  EXIT=0
pint --dirty      passed
npm run build     OK — help-EPOANIj9.js -> help-Ckg4e8Xm.js · css KEKAL
manifest          83 guide · 473 langkah · generik 59 · action-generic 0 · placeholder 0
validator bebas   EXIT=0 — partition, set-union, role_routes konsisten
HELP-TARGETS.md   dijana (200 aktif + 27 rizab)
pengesahan visual 6/6
gate screen       30/30  (10.6m)   EXIT=0
gate workflow     15/15  (10.8m)   EXIT=0
gate t-a-p        41/41  (12.7m)   EXIT=0
agregator         GATE LULUS: 83 guide · 473 langkah · 172 langkah tindakan (union SET)
```

⚠️ **Keadaan mesin semasa gate direkod SEBELUM ia berjalan** (pelajaran F7): RAM bebas
**4.8 GB / 31.7 GB**, 53 proses chrome (sesi pengguna), 30 node, CPU 24%. Tempoh ujian
**13–30s** berbanding **~12s** baseline W6 — mesin kira-kira 2× lebih perlahan.

Ketiga-tiga shard tetap **hijau pada percubaan pertama**. Itu menjadikan bacaan ini kukuh ke
arah yang selamat: gate lulus WALAUPUN di bawah beban yang memerahkan shard `t-a-p` (38/41)
dalam sesi F7. Keadaan mesin direkod dahulu supaya ia tidak boleh dijadikan alasan selepas
melihat keputusan — dalam kes ini ia tidak diperlukan langsung.

## (g) CI pusingan 1 MERAH — dan puncanya ialah jurang dalam verifikasi TEMPATAN saya

CI **31211426672**: satu ujian merah, 35 lulus.

```
[ci-guidance] › e2e/guidance-f5.spec.js:323 › F6-W5d sorotan tour bertahan selepas morph Livewire
```

Job `guidance-e2e-gate` turut merah, tetapi itu **akibat, bukan punca**: shard matriks
`skipped` apabila lapis 1 gagal, dan agregator melaporkan "missing shard (tidak berjalan ≠
lulus)" — gagal-tertutup berkelakuan betul.

**Punca:** penjaga W5d mengekod sasaran `tenant.bantuan#1` pada TIGA tempat (pemerhati mutasi,
prasyarat keterlihatan, assertion akhir). Sasaran itu ialah tepat yang batch ini tukar. Ia
merah atas sebab yang BETUL — penjaga mengikut katalog, bukan sebaliknya. Kedua-dua nod berada
dalam komponen `help-center` yang sama, jadi senario morph yang dikunci kekal identik; hanya
nod yang diperhatikan berubah. Pemerhati sengaja TIDAK dilonggarkan kepada mana-mana
`[data-help-target]`: ketepatan nod ialah sebahagian daripada apa yang penjaga itu buktikan.

🔴 **Jurang proses sebenar:** saya menjalankan gate 3 shard (`guidance-full`), `unit`, dan Pest
penuh — tetapi **bukan projek `ci-guidance`**, dan `guidance-f5.spec.js` hanya hidup di sana.
Gate penuh 40 minit boleh hijau sepenuhnya sambil check WAJIB merah.
**Peraturan baharu: apabila katalog berubah, jalankan `ci-guidance` DAN gate 3 shard.**
Skrip: `bukti/plan-f7-hutang/skrip/` (lihat `jalan-ci-guidance.sh` dalam laporan ini).

✅ **Bukti dua arah datang percuma:** CI 31211426672 ialah arah MERAH penjaga (sasaran tidak
sepadan → gagal); larian tempatan selepas pembetulan ialah arah HIJAU (14.7s).

## (h) Lencongan dari spec

TIADA. Perubahan sasaran ialah tepat kerja yang §7.2 peruntukkan kepada F7 ("penghalusan
semantik"), dan pengecualian polisi baharu tidak diperlukan.

## (i) Nota untuk F8/F9

- `admin.storage-orders#2` kekal ketidakpadanan makna yang DIREKOD, bukan tersembunyi. Ia hanya
  boleh ditutup jika benih demo memperoleh sekurang-kurangnya satu pesanan storan — perubahan
  benih memecahkan `MinitService` pada W3, jadi ia perlu suite penuh, bukan tampalan cepat.
- `public.help#2` masih berbunyi "Pastikan panel, tenant dan role semasa adalah betul" pada
  halaman AWAM yang tiada panel/tenant/role. Itu jurang KANDUNGAN yang F9 miliki.
- Ukuran **SAIZ** sorotan kini sebahagian senarai semak, bukan sekadar identitinya — jadual (c)
  ialah bentuk yang patut diulang.

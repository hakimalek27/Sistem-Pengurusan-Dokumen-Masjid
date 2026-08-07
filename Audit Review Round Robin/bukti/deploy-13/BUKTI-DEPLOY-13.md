# BUKTI DEPLOY 13 — F7 (Kebolehcapaian) ke bakwim.my (8 Ogos 2026)

**LIVE: `774f9ab`** (sebelum: `0e0fd84`, Deploy 12). CI **31184654233 = 7/7 HIJAU**, termasuk
langkah WAJIB `Accessibility (axe)` dan `Domain flows` (yang kini membawa 4 ujian viewer).

## Ramalan yang ditulis SEBELUM deploy — 5/5 tepat

| # | Ramalan | Hasil |
|---|---|---|
| 1 | `help-*.js` BERUBAH (help.js disunting) | ✅ `help-D_qumira.js` → `help-EPOANIj9.js` |
| 2 | `help-*.css` KEKAL (help.css tidak disentuh) | ✅ `help-Cfwb6f_j.css` |
| 3 | Aset BAHARU `a11y-landmarks-*.js` wujud dalam imej | ✅ `a11y-landmarks-mQ2zo0LK.js` |
| 4 | `catalog_version` KEKAL (F7 tidak sentuh katalog) | ✅ `2026.08.08.1 \| 83 \| 473 \| generik 59` |
| 5 | `Nothing to migrate` | ✅ |

ImageID: app `30cc176e7f97` → **`c101393d0e42`** · web `ade064feeae7` → **`b28eb89aad0d`**.
Label revisi: **`774f9ab6b156a9b5153cd66651bc349484ef38b6`**.

⭐ Ramalan #3 ialah semakan yang paling bernilai kali ini: entri Vite BAHARU. Jika
`vite.config.js` tidak dikemas, aset itu tidak dibina, `@vite(...)` melempar, dan KEDUA-DUA
panel gagal dirender. Langkah bukti `#3c` ditambah khusus untuknya.

## Rantaian bukti runtime 5A — LULUS PENUH

```
#2a katalog DALAM imej app : catalog_version 2026.08.08.1 | guide 83 | langkah 473 | generik 59
#3a manifest DALAM imej app: a11y-landmarks.js  -> assets/a11y-landmarks-mQ2zo0LK.js
                             document-viewer.js -> assets/document-viewer-sF29a_1w.js
                             help.js            -> assets/help-EPOANIj9.js
                                                   css: assets/help-Cfwb6f_j.css
#3c aset a11y BAHARU       : a11y-landmarks-mQ2zo0LK.js  (hadir)
#4b hash dalam imej nginx  : 646d725431df38af5b94e57d6f261f80  help-EPOANIj9.js
                             f45d35e1438855cf3cd18ac0a19e0cea  help-Cfwb6f_j.css
#5a/#5b/#6 badan awam      : 646d725431df38af5b94e57d6f261f80  help-EPOANIj9.js
                             f45d35e1438855cf3cd18ac0a19e0cea  help-Cfwb6f_j.css
```
app = nginx = badan yang dihidang kepada awam. Algoritma: `sha256sum | cut -c1-32`.

## Kesihatan selepas deploy

```
8/8 kontena running · diwan:health OK · SMOKE E2E: 9 lulus, 0 gagal
GET /up -> 200 · failed_jobs=0 · Nothing to migrate · 83 guide disegerakkan
cakera 67% · view:clear dijalankan
```
⛔ Tiada seeder dijalankan pada produksi.

## Pengesahan visual LIVE — pembaikan a11y BERKUAT KUASA

Diukur pada `https://bakwim.my/bantuan?panduan=public.help&langkah=0`:

```
header class="brand"                  role=(tiada)     <- banner halaman, tunggal
header class="driver-popover-title"   role=none        <- landmark PALSU DINEUTRALKAN
footer class="driver-popover-footer"  role=none        <- landmark PALSU DINEUTRALKAN
nav aria-label = "Navigasi utama"
versi katalog  = 2026.08.08.1
```

Kecacatan `landmark-unique` yang larian axe pertama temui **tidak lagi wujud di produksi**.

ℹ️ `a11y-landmarks.js` TIDAK dimuat pada `/bantuan`, dan itu BETUL: ia didaftarkan melalui
render hook PANEL Filament, manakala `/bantuan` menggunakan layout tetamu. Pembaikan pada
halaman awam datang daripada `role="none"` dalam `help.js`. Dinyatakan supaya ketiadaan skrip
itu tidak disalah tafsir sebagai deploy yang tidak lengkap.

## 🔴 PENEMUAN TERBUKA — sorotan tour masih HILANG pada produksi

Diukur pada halaman yang SAMA, dua bacaan berturut:

```
muatan segar          -> aktif = "help-search-form"   (sorotan BETUL)
beberapa saat kemudian -> aktif = null                 (sorotan HILANG)
                          sasaran MASIH dalam DOM, popover MASIH "Buka fungsi", "1 daripada 2"
```

Ini tandatangan kecacatan **W5d** (morph Livewire memadam kelas `.driver-active-element`).
Pemulihan `watchHighlightLoss` — yang tetingkapnya dilebarkan 6s → 20s dalam F7 — **tidak
menyelamatkan kes ini**.

**Apa yang BELUM diketahui, dan sengaja tidak dispekulasi:**
- masa tepat kehilangan (pemerhati saya dipasang selepas ia sudah hilang);
- sama ada Deploy 12 berkelakuan sama — pada W6 saya hanya mengukur SEBAIK selepas muatan,
  jadi saya **tidak boleh** mendakwa F7 memperkenalkannya;
- sama ada puncanya morph KEDUA selepas cap 2 pembaikan habis, atau morph selepas 20s.

**Kesan pengguna:** popover tour kekal terbuka dengan tiada apa-apa disorot — pengguna diberi
arahan tanpa tahu ke mana hendak melihat. Sama kelasnya seperti kecacatan yang W5d tutup.

**Kenapa tidak dibaiki sekarang:** mengubah dalaman runtime tour pada penghujung sesi tanpa
kitaran ukur-baiki-sahkan yang penuh ialah tepat cara regresi dihantar. Ia menjadi **item
PERTAMA sesi berikutnya**, dengan langkah yang sudah diketahui: pasang perekam dalam halaman
SEBELUM tour bermula (`addInitScript` dalam Playwright terhadap produksi tidak boleh; guna
pelayan tempatan dengan benih yang sama), rakam setiap peralihan kelas + setiap commit
Livewire, dan tentukan sama ada cap 2 pembaikan atau tetingkap 20s yang menjadi had.

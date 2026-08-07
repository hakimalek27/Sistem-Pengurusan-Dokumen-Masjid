# BUKTI DEPLOY 11 — F6-W5 ke bakwim.my (8 Ogos 2026)

**LIVE: `f0fc273`** (F6-W5 + akibat W5b/W5c/W5d). Sebelum: `cea55da` (Deploy 10).
CI **31140198079 = 7/7 HIJAU**, termasuk ketiga-tiga shard `guidance-e2e` — larian PERTAMA
yang benar-benar menjalankan matriks shard sejak W5 bermula (sebelum ini `skipped`).

| Semakan CI | Keputusan |
|---|---|
| PostgreSQL, Redis, Meili, OCR and tests | ✅ |
| guidance-e2e (screen) · (workflow) · (tenant-admin-public) | ✅ ✅ ✅ |
| guidance-e2e-gate | ✅ |
| Docker app image · Docker web image | ✅ ✅ |

## Ramalan yang ditulis SEBELUM deploy — 5/5 tepat

| # | Ramalan | Hasil |
|---|---|---|
| 1 | Nama KEDUA-DUA aset berubah (W5 sentuh JS *dan* CSS) | ✅ `help-D_qumira.js` + `help-Cfwb6f_j.css` |
| 2 | ImageID app berubah | ✅ `ba034a48e81a` → `5a92fd87659f` |
| 3 | ImageID web berubah | ✅ `016f6b18c559` → `d8ca2e76c04a` |
| 4 | `Nothing to migrate` (W5 tidak sentuh skema) | ✅ |
| 5 | `catalog_version 2026.08.07.1` · 83 guide · 473 langkah · 61 generik | ✅ tepat |

Label revisi: `cea55da…` → **`f0fc273314dacb09108fb639ad4bdd88f5a8c0ba`** (GIT_SHA dihantar).

## Rantaian bukti runtime 5A — LULUS PENUH

```
#2a katalog DALAM imej app : catalog_version 2026.08.07.1 | guide 83 | langkah 473 | generik 61
#3a manifest DALAM imej app: resources/js/help.js -> assets/help-D_qumira.js
                                                     css: assets/help-Cfwb6f_j.css
#3b hash DALAM imej app    : c6d25ee1b8db6d26a0de24b9f64f9d25  help-D_qumira.js
                             f45d35e1438855cf3cd18ac0a19e0cea  help-Cfwb6f_j.css
#4b hash DALAM imej nginx  : c6d25ee1b8db6d26a0de24b9f64f9d25  (IDENTIK)
                             f45d35e1438855cf3cd18ac0a19e0cea  (IDENTIK)
#5a/#5b/#6 badan awam      : c6d25ee1b8db6d26a0de24b9f64f9d25  help-D_qumira.js
  (curl -fsS, bukan -sI)     f45d35e1438855cf3cd18ac0a19e0cea  help-Cfwb6f_j.css
```
Algoritma: `sha256sum | cut -c1-32` (dilabel sengaja — dua deploy terdahulu terbuang masa
menyiasat "percanggahan" yang sebenarnya md5-lawan-sha256).

## Kesihatan selepas deploy

```
8/8 kontena running (app clamav db meilisearch nginx redis scheduler worker)
diwan:health OK
SMOKE E2E: 9 lulus, 0 gagal
GET /up -> 200
failed_jobs=0
Nothing to migrate — 0 baris data disentuh
83 guide disegerakkan ke indeks diwan_help_guides
cakera 61% (tiada prune diperlukan)
```

⛔ Tiada seeder dijalankan pada produksi (benih demo berubah dalam W5).

## Pengesahan visual LIVE (Chrome, https://bakwim.my/bantuan?panduan=public.help&langkah=0)

```json
{ "aset": ["help-Cfwb6f_j.css", "help-D_qumira.js"],
  "disorot": "page-content", "rect": { "y": 0, "w": 1008, "h": 2132 },
  "popover": "Buka fungsi", "progres": "1 daripada 2", "ralatPalsu": false }
```
Diukur **9 saat selepas muat**, iaitu selepas tetingkap morph yang memusnahkan sorotan
sebelum W5d. Halaman memaparkan `Versi 2026.08.07.1` = katalog baharu benar-benar berkuat kuasa.

Bundel yang DIHIDANG mengandungi pembaikan (diperiksa terus daripada aset produksi):
`moveTo(` ✔ · `diwan-tour-auto` ✔ · tinjauan 250ms ✔ · 8 rujukan `driver-active-element`.

### ⚠️ Had pengesahan ini — dinyatakan, bukan dilangkau

`public.help` bersasar `page-content` (generik; itu skop **W6**), dan `<main>` berada DI LUAR
komponen Livewire yang memorph. Jadi larian live ini membuktikan **tour produksi sihat dan
kod pembaikan dihidang**, tetapi ia **TIDAK** melaksanakan senario kecacatan itu sendiri.
Senario sebenar ialah `/app/{tenant}/bantuan` (sasaran `help-search` DI DALAM komponen yang
memorph) dan ia memerlukan sesi tenant berautentikasi — **kredensial produksi tidak pernah
saya cipta atau taip**. Senario itu dibuktikan pada pelayan tempatan dengan penjaga dua arah
(regresi ✘ 23.7s → pulih ✓ 11.6s) dan oleh shard CI `tenant-admin-public` 41/41.
**Pemilik boleh menutup jurang ini** dengan membuka `/app/<masjid>/bantuan` dalam sesinya
sendiri dan mengesahkan bahagian "Cari panduan" kekal disorot selepas halaman siap dimuat.

### Pembetulan kepada bacaan visual saya sendiri

Skrinsyot menunjukkan kad panduan dengan imej yang kelihatan KOSONG dan saya hampir
melaporkannya sebagai regresi. Ukuran menolaknya: ketiga-tiga `[data-help-image]` memulangkan
**HTTP 200, `complete: true`, `naturalWidth: 1440`**, `is-missing` false. Ia hanya kelihatan
pucat pada skrinsyot berskala kecil.
🔑 Pengesahan visual mesti diikuti ukuran — mata menjana hipotesis, bukan kesimpulan.

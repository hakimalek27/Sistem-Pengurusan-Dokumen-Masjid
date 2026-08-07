# BUKTI DEPLOY 12 — F6-W6 ke bakwim.my (8 Ogos 2026) · **F6 TAMAT**

**LIVE: `0e0fd84`** (sebelum: `f0fc273`, Deploy 11). CI **31145153663 = 7/7 HIJAU**,
ketiga-tiga shard `guidance-e2e` berjalan penuh (tiada `skipped`).

## Ramalan yang ditulis SEBELUM deploy — 5/5 tepat

| # | Ramalan | Hasil |
|---|---|---|
| 1 | **Nama aset KEKAL** (W6 sentuh Blade+JSON sahaja, tiada entri Vite) | ✅ `help-D_qumira.js` + `help-Cfwb6f_j.css` — identik Deploy 11 |
| 2 | ImageID app berubah | ✅ `5a92fd87659f` → `30cc176e7f97` |
| 3 | ImageID web berubah walaupun `public/` identik | ✅ `d8ca2e76c04a` → `ade064feeae7` |
| 4 | `Nothing to migrate` | ✅ |
| 5 | #2a: `catalog_version 2026.08.08.1 \| 83 \| 473 \| generik 59` | ✅ **tepat** |

Ramalan #1 ialah yang paling mudah disalahtafsir: pada deploy jenis ini, nama aset yang
**tidak** berubah adalah BETUL. Bukti utama mesti **kandungan dalam imej hidup**, bukan nama
fail — corak yang kini terbukti empat kali (Deploy 2, 7, 8, 12).

Label revisi: `f0fc273…` → **`0e0fd849e5df18ec28b4c0b0f42634ebfdbcc903`**.

## Rantaian bukti runtime 5A — LULUS PENUH

```
#2a katalog DALAM imej app : catalog_version 2026.08.08.1 | guide 83 | langkah 473 | generik 59
#3a manifest DALAM imej app: resources/js/help.js -> assets/help-D_qumira.js
                                                     css: assets/help-Cfwb6f_j.css
#3b/#4b/#5a/#5b/#6        : c6d25ee1b8db6d26a0de24b9f64f9d25  help-D_qumira.js
                             f45d35e1438855cf3cd18ac0a19e0cea  help-Cfwb6f_j.css
                             (app = nginx = badan yang dihidang kepada awam)
```
Algoritma dilabel: `sha256sum | cut -c1-32`.

## Kesihatan selepas deploy

```
8/8 kontena running · diwan:health OK · SMOKE E2E: 9 lulus, 0 gagal
GET /up -> 200 · failed_jobs=0 · Nothing to migrate · 83 guide disegerakkan
cakera 64% · view:clear dijalankan (Blade berubah — wajib)
```
⛔ Tiada seeder dijalankan pada produksi.

## Pengesahan visual LIVE (Chrome, https://bakwim.my/bantuan?panduan=public.help&langkah=0)

```json
{ "disorot": "help-search-form", "tag": "FORM",
  "rect": { "y": 231, "w": 1008, "h": 70 }, "peratusTinggiMain": 3,
  "teksDalamSorotan": "Apa yang anda mahu lakukan? Cari",
  "popover": "Buka fungsi", "progres": "1 daripada 2",
  "versiKatalog": "2026.08.08.1", "ralatPalsu": false }
```

Diukur **8 saat selepas muat** (selepas tetingkap morph yang memusnahkan sorotan sebelum W5d).
Nilai IDENTIK dengan pengukuran tempatan — dan `versiKatalog` membuktikan katalog baharu
benar-benar berkuat kuasa, bukan cache lama.

**Metrik yang bermakna: 100% → 3%.** Sebelum W6 langkah ini menyorot `page-content`, iaitu
seluruh `<main>`. Skrinsyot produksi mengesahkan borang carian menyala manakala selebihnya
halaman malap — pengguna nampak serta-merta ke mana hendak melihat.

Berbeza daripada Deploy 11, pengesahan live kali ini **memang melaksanakan perkara yang
diubah**: `public.help` ialah guide AWAM, jadi tiada jurang kredensial. (Deploy 11 hanya boleh
disahkan pada halaman awam sedangkan kecacatannya pada halaman tenant.)

## Status F6 selepas Deploy 12

```
83 guide · 473 langkah
specific 414 · generic-justified 36 · not-applicable 23 · risk-accepted 0 · blocked 0
generic_declared 59 — KESEMUANYA berjustifikasi eksplisit bertarikh
action_steps_with_generic_target 0 · placeholder 0 · defect mobile 0
justified_waves = W0 W1 W2 W3 W4 W5 W6  (ketujuh-tujuh TERTUTUP)
```

Mana-mana langkah generik BAHARU dalam mana-mana wave kini menggagalkan penjanaan manifest
melainkan ia membawa justifikasi bertarikh. Itu sifat gagal-tertutup yang F6 wujud untuk
membina, dan ia berkuat kuasa mulai hari ini.

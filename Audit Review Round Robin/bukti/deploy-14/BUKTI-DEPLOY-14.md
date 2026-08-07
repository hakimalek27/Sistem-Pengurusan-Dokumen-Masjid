# BUKTI DEPLOY 14 — hutang F7 (sasaran bantuan + semantik `admin.*`) ke bakwim.my (8 Ogos 2026)

**LIVE: `2325bec`** (sebelum: `774f9ab`, Deploy 13). CI **31213031582 = 7/7 HIJAU** termasuk
ketiga-tiga shard `guidance-e2e` dan kedua-dua imej Docker.

## Ramalan yang ditulis SEBELUM deploy — 5/5 tepat

| # | Ramalan | Hasil |
|---|---|---|
| 1 | `help-*.js` BERUBAH — `page-target-plan.js` DIIMPORT oleh `help.js` | ✅ `help-EPOANIj9.js` → `help-Ckg4e8Xm.js` |
| 2 | `help-*.css` KEKAL (tiada CSS disentuh) | ✅ `help-Cfwb6f_j.css` |
| 3 | `a11y-landmarks-mQ2zo0LK.js` KEKAL — jika BERUBAH, sesuatu tidak dijangka masuk binaan | ✅ nama DAN hash `8791780a…` sama |
| 4 | `catalog_version` 2026.08.08.1 → **2026.08.08.2**, 83/473/59 TIDAK berubah | ✅ |
| 5 | `Nothing to migrate` | ✅ |

ImageID: app `c101393d0e42` → **`838f60ba3018`** · web `b28eb89aad0d` → **`80ce84298fd8`**.
Label revisi: **`2325becb4828724f0f46f22712eefefcf43a276d`**.

⭐ Ramalan #4 ialah yang paling bernilai kali ini, dan sebabnya songsang daripada biasa: yang
dibuktikan ialah angka yang **TIDAK** bergerak. Batch ini menukar SASARAN enam langkah tanpa
menambah atau membuang satu pun. Jika `generik` bergeser daripada 59, sesuatu tersalah tukar.

## Semakan #2b — khusus untuk deploy ini

Nama aset dan versi katalog membuktikan imej baharu; ia tidak membuktikan **kandungan yang
betul**. Jadi enam langkah disemak terus DALAM imej:

```
admin.mosques    #2  platform-mosques-actions   OK
admin.users      #2  platform-users-actions     OK
tenant.bantuan   #1  help-search-form           OK
tenant.bantuan   #2  help-scope                 OK
admin.bantuan    #1  help-search-form           OK
admin.bantuan    #2  help-scope                 OK
```

## Rantaian bukti runtime 5A — LULUS PENUH

```
#2a katalog DALAM imej app : catalog_version 2026.08.08.2 | guide 83 | langkah 473 | generik 59
#3a manifest DALAM imej app: a11y-landmarks.js -> assets/a11y-landmarks-mQ2zo0LK.js
                             help.js           -> assets/help-Ckg4e8Xm.js
                                                  css: assets/help-Cfwb6f_j.css
#3b hash dalam imej app    : 3fa7dcdb16c1418ccbebc78df47ae795  help-Ckg4e8Xm.js
                             f45d35e1438855cf3cd18ac0a19e0cea  help-Cfwb6f_j.css
                             8791780ac3208c21030116f4508277dd  a11y-landmarks-mQ2zo0LK.js
#4b hash dalam imej nginx  : 3fa7dcdb16c1418ccbebc78df47ae795  help-Ckg4e8Xm.js
                             f45d35e1438855cf3cd18ac0a19e0cea  help-Cfwb6f_j.css
#5a/#5b/#6 badan awam      : 3fa7dcdb16c1418ccbebc78df47ae795  help-Ckg4e8Xm.js
                             f45d35e1438855cf3cd18ac0a19e0cea  help-Cfwb6f_j.css
                             8791780ac3208c21030116f4508277dd  a11y-landmarks-mQ2zo0LK.js
```
app = nginx = badan yang dihidang kepada awam, ketiga-tiga aset.

## Kesihatan selepas deploy

```
8/8 kontena running · diwan:health OK · SMOKE E2E: 9 lulus, 0 gagal
GET /up -> 200 · failed_jobs=0 · Nothing to migrate · 83 guide disegerakkan
cakera 71% (bawah ambang prune 80%) · view:clear + config:cache + route:cache
```
⛔ Tiada seeder dijalankan pada produksi.

## Pengesahan LIVE

```
bundel yang dihidang mengandungi : platform-mosques-actions
                                   platform-users-actions
                                   tbody tr:first-child td:last-child
halaman awam /bantuan            : "Versi 2026.08.08.2"
                                   help-search · help-search-form · help-scope (1 setiap satu)
```

Bundel awam membawa pemetaan baharu dan halaman awam melaporkan versi katalog baharu — jadi
katalog yang berkuat kuasa ialah yang di-deploy, bukan cache.

## ⚠️ Jurang yang JUJUR — apa yang pengesahan live ini TIDAK liputi

Keenam-enam langkah yang berubah berada pada halaman **tenant** dan **admin**. Mengesahkan
sorotannya secara live memerlukan sesi log masuk produksi, dan **kredensial produksi tidak
pernah dicipta atau ditaip**. Apa yang menggantikannya:

- pengesahan visual **6/6** pada pelayan tempatan dengan bundel yang hash-nya sama dengan yang
  dibina di sini (`bukti/plan-f7-hutang/LAPORAN-HUTANG-F7.md` §d);
- semakan `#2b` di atas — kandungan katalog DALAM imej produksi;
- gate 3 shard + `ci-guidance` 7/7 di CI pada commit yang di-deploy.

**Pemilik boleh menutup jurang ini dalam satu minit:** buka `/app/<masjid>/bantuan` dan
`/admin/bantuan` dalam sesi sendiri — langkah 1 mesti menyorot **borang carian sahaja**
(bukan seluruh seksyen), dan langkah 2 baris "Skop panduan: …". Kemudian `/admin/mosques`
dengan `?panduan=admin.mosques&langkah=1` — sorotan mesti jatuh pada **sel butang tindakan**
baris pertama, bukan kotak carian.

## Nota

Ini deploy pertama sejak Deploy 13; tiada migrasi tertunggak, dan `catalog_version` berubah
jadi `sync-help-index --delete` WAJIB dan dijalankan (83 guide disegerakkan).

# BUKTI DEPLOY 7 — hotfix BUG-A..D LIVE di bakwim.my

**Tarikh:** 5 Ogos 2026 · **Komit:** `8a9f6cb` · **CI:** run 30957946490
**Sifat deploy:** PHP + Blade sahaja — **tiada** migrasi, **tiada** perubahan JS/CSS,
**tiada** perubahan katalog panduan.

---

## 1. Gate sebelum deploy

| Check WAJIB | Status |
|---|---|
| `PostgreSQL, Redis, Meili, OCR and tests` | (diisi) |
| `guidance-e2e-gate` | (diisi) |
| `Docker app image` | (diisi) |
| `Docker web image` | (diisi) |

Tempatan pada komit sama: Pest **545 lulus / 1 skip** · pint passed · `npm run build` OK ·
e2e `ci-guidance` **34 lulus / 1 gagal** (kegagalan itu **dibuktikan sedia ada** — lihat
`LAPORAN-BUG-A.md` §"Kegagalan explore.spec.js:83").

## 2. Semakan pra-deploy pada pelayan (dan satu pembetulan operasi)

```
cakera SEBELUM : 4.8G baki (83% guna)      ← risiko: build boleh gagal kehabisan ruang
build cache    : 11.82GB (10.16GB boleh dituntut)
docker builder prune -f --filter until=48h  →  2.707GB dituntut
cakera SELEPAS : 7.4G baki (74% guna)      ← imej, container, volume TIDAK disentuh
memori         : 1.9Gi total / 497Mi available
```

⚠️ **Dua kelemahan prosedur deploy ditemui dan dibetulkan dalam deploy ini:**

1. **Label revisi imej berbunyi `unknown`.** `docker/Dockerfile` memang menerima `ARG GIT_SHA`
   dan menulis `LABEL org.opencontainers.image.revision` (keperluan D9 F0), tetapi tiada deploy
   sebelum ini (termasuk Deploy 1–6 saya sendiri) pernah **menghantar** `GIT_SHA` — jadi label
   itu tidak pernah berguna. Deploy 7 menghantarnya, jadi imej kini boleh dikaitkan kepada
   komit tanpa bergantung pada catatan luaran.
2. **`storage` ialah volume BERKEKALAN** (`diwan_storage:/var/www/html/storage`), dan ia
   mengandungi **223 fail view Blade terkompil** daripada deploy sebelumnya. Deploy ini banyak
   menyentuh Blade, jadi `view:clear` ditambah pada urutan — menghapuskan kelas pepijat
   "markup lama dihidangkan" (keluarga insiden manifest Vite 22 Julai).

## 3. Rantaian bukti runtime 5A (§10)

| # | Bukti | Sebelum (Deploy 6) | Selepas (Deploy 7) |
|---|---|---|---|
| 1 | git SHA server | `aaf381a` | (diisi) |
| 2a | ImageID `diwan-app` | `2d00c92e3cac` | (diisi) |
| 2b | ImageID `diwan-web` | `4824bd182d3a` | (diisi) |
| 3a | container app/worker/scheduler | ketiga = `2d00c92e3cac` | (diisi) |
| 3b | container nginx | `4824bd182d3a` | (diisi) |
| 4a | nama aset help | `help-D0185fq1.js` + `help-CrH0eDM1.css` | (diisi) |
| 4b | sha256 `manifest.json` app vs nginx | `1aa1b3f4b87ae4d204e86d003706f3de` | (diisi) |

⭐ **RAMALAN BOLEH-GAGAL, DIBUAT SEBELUM DEPLOY** — diterbitkan daripada `docker/Dockerfile`,
bukan daripada gerak hati:

| Ramalan | Sebab dalam Dockerfile |
|---|---|
| Nama aset help + sha256 `manifest.json` **IDENTIK** dengan Deploy 6 | tiada fail JS/CSS disentuh; Vite deterministik untuk input yang sama |
| ImageID `diwan-app` **BERUBAH** | `COPY --chown … . .` (baris 56) membawa kod PHP/Blade yang berubah |
| ImageID `diwan-web` **BERUBAH** walaupun `public/` identik | target `web` menyalin `public/` **dari stage app** (baris 78), tetapi `LABEL …revision=$GIT_SHA` (baris 77) datang **SEBELUM** COPY itu — dan `GIT_SHA` bertukar daripada `unknown` → `8a9f6cb`, jadi cache terputus di situ |
| Label `org.opencontainers.image.revision` = `8a9f6cb` pada KEDUA-DUA imej | `ARG GIT_SHA` kini benar-benar dihantar (pertama kali) |

Ini kes **terbalik** daripada Deploy 6 (JS berubah, CSS kekal), jadi ia menguji rantaian bukti
dari arah bertentangan: kalau ImageID tidak berubah, deploy tidak berkuat kuasa; kalau hash aset
berubah, andaian saya tentang skop perubahan adalah salah.

## 4. Kesihatan selepas deploy

(diisi)

## 5. Pengesahan LIVE dalam Chrome

(diisi)

## 6. Baseline untuk deploy seterusnya

(diisi)

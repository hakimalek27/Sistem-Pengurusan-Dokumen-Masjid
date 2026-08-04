# BUKTI DEPLOY 7 — hotfix BUG-A..D LIVE di bakwim.my

**Tarikh:** 5 Ogos 2026 · **Komit:** `4fd64cf` · **CI:** run 30958629599 **7/7 HIJAU**
**Sifat deploy:** PHP + Blade sahaja — **tiada** migrasi, **tiada** perubahan JS/CSS,
**tiada** perubahan katalog panduan.

---

## 1. Gate sebelum deploy (tiada deploy atas gate merah)

| Check | Status |
|---|---|
| `PostgreSQL, Redis, Meili, OCR and tests` **(wajib)** | ✅ success |
| `guidance-e2e-gate` **(wajib)** | ✅ success |
| `Docker app image` **(wajib)** | ✅ success |
| `Docker web image` **(wajib)** | ✅ success |
| `guidance-e2e (screen)` · `(workflow)` · `(tenant-admin-public)` | ✅ ✅ ✅ |

Tempatan pada komit sama: Pest **546 lulus / 1 skip** · pint passed · `npm run build` OK ·
e2e `ci-guidance` **34 lulus / 1 gagal** (kegagalan itu **dibuktikan sedia ada** — lihat
`LAPORAN-BUG-A.md`).

⚠️ Larian CI terdahulu `8a9f6cb` = **`cancelled`**, BUKAN gagal: workflow menetapkan
`concurrency: cancel-in-progress: true`, jadi push `4fd64cf` menggantikannya. Job
`guidance-e2e-gate` melaporkan `failure` di situ semata-mata kerana ia `if: always()` dan
dependensinya dibatalkan. (Perbezaan `cancelled` vs `failure` ini pernah saya salah baca —
kali ini dibaca betul.)

## 2. Semakan pra-deploy + dua pembetulan prosedur

```
cakera SEBELUM : 4.8G baki (83% guna)      ← risiko: build boleh gagal kehabisan ruang
build cache    : 11.82GB (10.16GB boleh dituntut)
docker builder prune -f --filter until=48h  →  2.707GB dituntut
cakera SELEPAS : 7.4G baki (74% guna)      ← imej, container, volume TIDAK disentuh
memori         : 1.9Gi total / 497Mi available
```

1. **Label revisi imej berbunyi `unknown`.** `docker/Dockerfile` menerima `ARG GIT_SHA` dan
   menulis `LABEL org.opencontainers.image.revision` (keperluan D9 F0), tetapi tiada deploy
   sebelum ini (Deploy 1–6 saya sendiri) pernah **menghantar** `GIT_SHA`. Deploy 7 menghantarnya.
2. **`storage` ialah volume BERKEKALAN** (`diwan_storage:/var/www/html/storage`) yang menyimpan
   **223 fail view Blade terkompil**. Deploy ini banyak menyentuh Blade → `view:clear` ditambah.

## 3. 🔴 INSIDEN DEPLOY: skrip berhenti SENYAP dengan exit 0

Skrip dijalankan sebagai `ssh ubuntu@… 'bash -s' < deploy7.sh`. Output tamat pada
`INFO Nothing to migrate.` — namun `ssh` pulang **exit 0**.

**Punca:** skrip disalurkan melalui **stdin**. Arahan `docker compose exec -T app php artisan
migrate --force` juga membaca **stdin**, jadi ia **memakan baki skrip** sebagai inputnya sendiri.
Bash kehabisan input → tamat normal → exit 0. `config:cache`, `view:clear`,
`sync-help-index`, semua semakan kesihatan dan bukti 5A **tidak pernah dijalankan**.

**Dikesan** dengan membaca output, **bukan** dengan mempercayai kod keluar — ini keluarga
pelajaran yang sudah direkod: *"command boleh cetak 'selesai' walau dilangkau"*.

**Pembetulan:** setiap `docker compose exec` diberi `< /dev/null`, dan langkah yang tertinggal
dijalankan semula. Untuk deploy akan datang: `scp` skrip ke pelayan dan jalankan sebagai FAIL,
jangan salurkan melalui stdin.

```
=== 5b. CACHE + VIEW + INDEKS (stdin ditutup setiap exec) ===
   INFO  Configuration cached successfully.
   INFO  Compiled views cleared successfully.
83 guide disegerakkan ke indeks diwan_help_guides.
```

## 4. Rantaian bukti runtime 5A (§10) — LULUS PENUH

| # | Bukti | Sebelum (Deploy 6) | Selepas (Deploy 7) |
|---|---|---|---|
| 1 | git SHA server | `aaf381a` | **`4fd64cf`** |
| 2a | ImageID `diwan-app` | `2d00c92e3cac` | **`35774700bd58`** |
| 2b | ImageID `diwan-web` | `4824bd182d3a` | **`96b969b4b925`** |
| 3a | container app/worker/scheduler | — | ketiga-tiganya `35774700bd58` = #2a ✅ |
| 3b | container nginx | — | `96b969b4b925` = #2b, ≠ #2a ✅ |
| 4a | nama aset EXACT | `help-D0185fq1.js` + `help-CrH0eDM1.css` | **identik** ✅ |
| 4b | sha256 `manifest.json` app vs nginx | `1aa1b3f4b87ae4d204e86d003706f3de` | **`1aa1b3f4b87ae4d204e86d003706f3de`** (app = nginx ✅) |
| — | label `image.revision` | `unknown` / `unknown` | **`4fd64cf` / `4fd64cf`** ✅ |

**#5a = #5b = #6 untuk KEDUA-DUA aset** (app · nginx · URL awam):

```
assets/help-D0185fq1.js     md5 f812113abc0ef90a230018039122b89f   (3/3 sama)
assets/help-CrH0eDM1.css    md5 0447d0f566a11f4d3a21c56c73db77fb   (3/3 sama)
```

### ⭐ Setiap ramalan pra-deploy disahkan

| Ramalan (dibuat SEBELUM deploy, diterbitkan daripada Dockerfile) | Keputusan |
|---|---|
| Nama aset + sha256 manifest **IDENTIK** dgn Deploy 6 (tiada JS/CSS disentuh) | ✅ tepat |
| ImageID `diwan-app` **BERUBAH** (`COPY … . .` baris 56) | ✅ `2d00c92e` → `35774700` |
| ImageID `diwan-web` **BERUBAH** walau `public/` identik — `LABEL …=$GIT_SHA` (77) sebelum `COPY --from=app` (78), dan `GIT_SHA` `unknown` → `4fd64cf` | ✅ `4824bd18` → `96b969b4` |
| Label revisi bermakna pada kedua-dua imej | ✅ `4fd64cf` |

Ini kes **terbalik** daripada Deploy 6 (JS berubah, CSS kekal): di sini **aset kekal, imej
berubah**. Rantaian 5A dengan itu diuji dari kedua-dua arah.

### 🔑 Percanggahan hash yang diselesaikan dengan UJIAN, bukan andaian

Deploy 6 merekod badan JS sebagai `753f7e26…`; md5 hari ini memberi `f812113a…` untuk **nama
fail yang sama** — nampak seperti fail berubah. Hipotesis (daripada pelajaran Deploy 2:
*labelkan algoritma hash*) diuji:

```
assets/help-D0185fq1.js    md5 f812113a…   sha256sum|cut -c1-32 753f7e263047d5d0df10f3501fe92a0d ✅
assets/help-CrH0eDM1.css   md5 0447d0f5…   sha256sum|cut -c1-32 f2406b313fca404825c3aabc40aec121 ✅
```

Nilai `sha256|cut` **sama sebiji** dengan rekod Deploy 6 → aset **byte-untuk-byte identik**;
yang berbeza hanyalah algoritma. Kedua-dua algoritma direkod di sini supaya deploy akan datang
tidak perlu mengulang penyiasatan ini.

## 5. Kesihatan selepas deploy

```
migrate --force            → INFO  Nothing to migrate.      (SIFAR baris data disentuh)
config:cache               → Configuration cached successfully.
view:clear                 → Compiled views cleared successfully.   (223 view basi dibuang)
diwan:sync-help-index      → 83 guide disegerakkan ke indeks diwan_help_guides.
diwan:health               → OK
/up  (127.0.0.1:8080)      → 200
/up  (127.0.0.1:80)        → 200
/up  (https://bakwim.my)   → 200      ← hujung-ke-hujung melalui Cloudflare
diwan:smoke                → SMOKE E2E: 9 lulus, 0 gagal.
queue:failed               → INFO  No failed jobs found.
container                  → 8/8 running (app clamav db meilisearch nginx redis scheduler worker)
```

## 6. Pengesahan LIVE dalam Chrome (sesi pemilik, tiada kredensial ditaip)

### SEBELUM → SELEPAS, diukur pada produksi hidup

| Ujian | SEBELUM | SELEPAS |
|---|---|---|
| `/` nav | `Utama · Log Masuk · Daftar` | **`Utama · Ke Panel · Daftar`** |
| `/` CTA | "Log Masuk" | **"Teruskan ke Panel" → `https://bakwim.my/admin`** |
| `/` notis sesi | tiada (`adaSesi: false`) | **"Anda sudah log masuk sebagai Superadmin"** |
| `/log-masuk` | borang minta pautan seperti tetamu | **notis + "Ke Panel" → `/admin`**, borang KEKAL (tukar akaun) |
| `/app/mamad` href logo | `https://bakwim.my/app/smoke` ❌ | **`https://bakwim.my/app/mamad`** ✅ |
| `/app/mamad/peti-masuk` href logo | `/app/smoke` ❌ | **`/app/mamad`** ✅ |
| `/app/mamad` "Panel Pentadbir" | tiada (0/38 pautan ke `/admin`) | **ada, `href="/admin"`** ✅ |
| `/admin` | berfungsi | **kekal berfungsi**, logo → `/admin` |

⚠️ **Gejala 1 BUG-A (pendaratan selepas log masuk) TIDAK boleh disahkan live oleh saya**: ia
memerlukan sesi log masuk BAHARU, dan saya tidak menaip kata laluan ke dalam borang. Ia
dibuktikan oleh **19 ujian Pest + 3 ujian Chromium** melalui borang Filament yang sebenar.
**Pemilik boleh mengesahkan dalam satu langkah:** log keluar → `/log-masuk` → "Log masuk dengan
kata laluan" → mesti mendarat di `/admin`, bukan dalam masjid.

## 7. Baseline untuk deploy seterusnya

```
git  4fd64cf · app 35774700bd58 · web 96b969b4b925
label org.opencontainers.image.revision = 4fd64cf (kedua-dua imej)
aset assets/help-D0185fq1.js + assets/help-CrH0eDM1.css   (TIDAK berubah sejak Deploy 6)
  sha256|cut -c1-32 : 753f7e26… / f2406b31…
  md5               : f812113a… / 0447d0f5…
manifest.json sha256 1aa1b3f4b87ae4d204e86d003706f3de (app = nginx)
catalog_version tidak berubah · 83 guide dalam indeks
cakera 74% guna (7.4G baki) selepas prune cache build
```

⚠️ **Untuk deploy akan datang:** `scp` skrip deploy ke pelayan dan jalankan sebagai fail.
`ssh 'bash -s' < skrip` **tidak selamat** apabila skrip mengandungi `docker compose exec`.

# BUKTI DEPLOY 8 — F6-W2 ke bakwim.my

**Tarikh:** 5 Ogos 2026 · **Komit:** `81b526f` · **CI:** run **30973519119 = 7/7 HIJAU** (pusingan #1 `30972196342` merah — Lampiran B laporan fasa)
**Sifat deploy:** PHP + Blade + `guides.json` + `targets.json` + seeder + harness e2e.
**Tiada** migrasi baharu · **tiada** perubahan JS/CSS produk · `catalog_version` **berubah**
(`2026.07.22.2` → `2026.08.05.1`) jadi `sync-help-index --delete` **WAJIB**.

---

## 1. Gate sebelum deploy

| Check | Status |
|---|---|
| `PostgreSQL, Redis, Meili, OCR and tests` **(wajib)** | OK |
| `guidance-e2e-gate` **(wajib)** | OK |
| `Docker app image` **(wajib)** | OK |
| `Docker web image` **(wajib)** | OK |
| `guidance-e2e (screen)` / `(workflow)` / `(tenant-admin-public)` | OK / OK / OK |

Tempatan pada komit sama: Pest **553 lulus / 1 skip** · pint passed · `npm run build` OK ·
gate 3 shard pada DB SEGAR (15/15 · 30/30 · 41/41) · agregator **GATE LULUS 83/473/172** ·
playwright `unit` 17/17 · `ci-guidance` 34/35 (kegagalan = had masa `explore.spec`, dibuktikan
lulus 4.6m dengan belanjawan lebih besar).

## 2. Baseline SEBELUM deploy (direkod daripada pelayan hidup)

```
git    : 03defc3
app    : sha256:35774700bd58        web : sha256:96b969b4b925
revisi : 4fd64cf / 4fd64cf
cakera : 6.3G baki (78% guna)       memori : 1963MB total, 548MB available
container: app clamav db meilisearch nginx redis scheduler worker — 8/8 running
```

## 3. Ramalan dibuat SEBELUM deploy (diterbitkan daripada Dockerfile + `npm run build` tempatan)

| # | Ramalan | Sebab | Keputusan |
|---|---|---|---|
| 1 | Nama aset **KEKAL** `help-D0185fq1.js` + `help-CrH0eDM1.css` | tiada JS/CSS produk disentuh | TEPAT — `help-D0185fq1.js` + `help-CrH0eDM1.css` |
| 2 | ImageID `diwan-app` **BERUBAH** | `COPY . .` — Blade/PHP/`guides.json` berubah | TEPAT — `35774700bd58` menjadi `1208bc00fa5c` |
| 3 | ImageID `diwan-web` **BERUBAH** walau `public/` identik | `LABEL …=$GIT_SHA` sebelum `COPY --from=app` | TEPAT — `96b969b4b925` menjadi `8d7d46cbd6b1` |
| 4 | `migrate --force` → **Nothing to migrate** | tiada migrasi dalam commit | TEPAT — `INFO  Nothing to migrate.` |
| 5 | `sync-help-index --delete` → **83 guide** | `catalog_version` berubah, kiraan guide tidak | TEPAT — `83 guide disegerakkan` |

## 4. Output deploy sebenar

### INSIDEN: deploy DISEKAT DUA KALI oleh keizinan yang Deploy 7 tinggalkan

Deploy 7 menjalankan sebahagian arahan git sebagai `root`, jadi checkout `/opt/diwan`
mengandungi laluan milik root yang **menyekat deploy seterusnya sepenuhnya**:

```
Cubaan 1 - git fetch:
  error: insufficient permission for adding an object to repository database .git/objects
  punca: 3 direktori objek milik root (.git/objects/3b, /03, /79) + 95 fail objek

Cubaan 2 - git reset --hard:
  error: unable to create file Audit Review Round Robin/bukti/plan-f6-w2/LAPORAN-F6-W2.md:
         Permission denied
  punca: 22 laluan pokok kerja milik root, KESEMUANYA dicipta semasa Deploy 7
```

**`set -euo pipefail` membuktikan nilainya:** kedua-dua kali skrip berhenti pada langkah 2 —
**sebelum** satu pun imej dibina atau container disentuh. Produksi tidak pernah berada dalam
keadaan separuh-deploy.

**Pembaikan (skop KETAT, tiada data disentuh):**

```
sudo chown -R ubuntu:ubuntu /opt/diwan/.git
find . -path ./.git -prune -o ! -user ubuntu -print0 | sudo xargs -0 chown ubuntu:ubuntu
selepas: 0 laluan bukan-ubuntu.  storage/ (volume Docker): 0 terjejas SEBELUM dan SELEPAS.
```

### Output deploy

```
migrate --force        -> INFO  Nothing to migrate.       (SIFAR baris data disentuh)
config:cache           -> Configuration cached successfully.
view:clear             -> Compiled views cleared successfully.  (volume storage BERKEKALAN)
diwan:sync-help-index  -> 83 guide disegerakkan ke indeks diwan_help_guides.
diwan:health           -> OK
/up 127.0.0.1:8080     -> 200     /up 127.0.0.1:80 -> 200     /up https://bakwim.my -> 200
diwan:smoke            -> SMOKE E2E: 9 lulus, 0 gagal.
queue:failed           -> INFO  No failed jobs found.
container              -> 8/8 running
cakera                 -> 6.3G menjadi 8.2G baki (78% menjadi 72%) selepas prune 2.0GB cache
```

## 5. Rantaian bukti runtime 5A (§10)

| # | Bukti | Deploy 7 | Deploy 8 |
|---|---|---|---|
| 1 | git SHA pelayan | `4fd64cf` | **`81b526f`** |
| 2a | ImageID `diwan-app` | `35774700bd58` | **`1208bc00fa5c`** |
| 2b | ImageID `diwan-web` | `96b969b4b925` | **`8d7d46cbd6b1`** |
| 3a | container app/worker/scheduler | — | ketiga-tiganya `1208bc00fa5c` = #2a |
| 3b | container nginx | — | `8d7d46cbd6b1` = #2b, bukan #2a |
| 4a | nama aset EXACT | `help-D0185fq1.js` + `help-CrH0eDM1.css` | **identik** |
| 4b | sha256 `manifest.json` app vs nginx | `1aa1b3f4…` | **`1aa1b3f4b87ae4d204e86d003706f3de`** (app = nginx) |
| — | label `image.revision` | `4fd64cf` | **`81b526f` / `81b526f`** |

**#5a = #5b = #6 untuk KEDUA-DUA aset** (app · nginx · URL awam), dan nilainya **identik dengan
Deploy 6 dan 7** — membuktikan aset benar-benar tidak berubah:

```
assets/help-CrH0eDM1.css  sha256|cut f2406b313fca404825c3aabc40aec121  md5 0447d0f566a11f4d3a21c56c73db77fb
assets/help-D0185fq1.js   sha256|cut 753f7e263047d5d0df10f3501fe92a0d  md5 f812113abc0ef90a230018039122b89f
```

Kerana nama aset **tidak** berubah, bukti utama deploy ini ialah **kandungan dalam imej**
(seksyen 6) + ImageID — pelajaran Deploy 2, dipakai semula di sini.

## 6. Pengesahan LIVE dalam Chrome

### (a) Kandungan katalog DALAM IMEJ PRODUKSI HIDUP — bukti utama

```
catalog_version dalam imej HIDUP: 2026.08.05.1
action_steps_with_generic_target: 0 (workflow: 0)     <- METRIK UTAMA F6 = 0 DI PRODUKSI
wait_for_user global: 172
  betulkan#3  record-tab-audit           wfu=false  Semak butiran dan jejak audit
  betulkan#4  record-correction          wfu=true   Buka Mohon Pembetulan
  betulkan#5  record-correction-reason   wfu=true   Mohon pembetulan rekod
  betulkan#6  record-correction-title    wfu=true   Mohon pembetulan rekod
  betulkan#7  record-correction-submit   wfu=true   Mohon pembetulan rekod
```

Pembaikan kegagalan senyap dan sasaran baharu, disahkan hadir dalam imej hidup:

```
"Tiada perubahan dikesan"           dalam ViewRecord.php        -> 1
"Pergerakan tidak dapat direkodkan" dalam ViewRegistryFile.php  -> 1
minit-record       dalam MinitsTable.php  -> 1
inbox-scan-status  dalam InboxTable.php   -> 1
registri: minit-record, inbox-scan-status, file-checkout-holder, record-minit-action,
          storage-blocks = KESEMUANYA `active`
```

### (b) Runtime tour — semakan regresi awam (bebas peranan)

`https://bakwim.my/log-masuk?panduan=public.login&langkah=0` diukur dalam Chrome hidup:

```
guide      : public.login
disorot    : login-identity        (tagName = INPUT - elemen sebenar, bukan halaman)
CTA        : "Seterusnya"
ralat palsu: false
```

Aset yang dihidangkan kepada pelayar: `help-D0185fq1.js` — sepadan bukti 5A.

### (c) Yang TIDAK dapat saya sahkan secara interaktif — dan sebabnya

Tour `workflow.*` memerlukan sesi dengan **peranan tenant** (`admin_masjid`, `pengerusi`,
`bendahari`, ...). Sesi pelayar yang ada ialah **superadmin**, yang tidak memegang peranan
tenant, jadi `findVisible()` dengan betul tidak menawarkan guide itu — diukur, bukan
diandaikan:

```
/app/mamad/records                        -> runtime dirender, data-guide-id: (tiada)
/app/mamad/carian?panduan=workflow.audit  -> (tiada runtime)
sasaran halaman WUJUD dalam DOM: search-results=1, search-result-open=1, search-result-item=1
```

Saya **tidak** mencipta atau menaip kredensial produksi. Pemilik boleh mengesahkan dalam dua
langkah:

1. Log masuk sebagai admin masjid, buka
   `/app/<slug>/records?panduan=workflow.admin_masjid.betulkan-rekod-salah-tawan-tanpa-memadam-sejarah&langkah=3`
   — langkah 4 mesti menyorot butang **Mohon Pembetulan**, bukan seluruh halaman.
2. Buka Mohon Pembetulan, tekan **Hantar** tanpa mengubah satu pun medan — mesej merah
   **"Tiada perubahan dikesan"** mesti muncul (dahulu: senyap sepenuhnya).

## 7. Baseline untuk deploy seterusnya

```
git  81b526f · app 1208bc00fa5c · web 8d7d46cbd6b1
label org.opencontainers.image.revision = 81b526f (kedua-dua imej)
aset assets/help-D0185fq1.js + assets/help-CrH0eDM1.css  (TIDAK berubah sejak Deploy 6)
  sha256|cut -c1-32 : 753f7e26… / f2406b31…
  md5               : f812113a… / 0447d0f5…
manifest.json sha256 1aa1b3f4b87ae4d204e86d003706f3de (app = nginx)
catalog_version 2026.08.05.1 · 83 guide dalam indeks
cakera 72% guna (8.2G baki) selepas prune 2.0GB
```

**Untuk deploy akan datang:** checkout `/opt/diwan` kini **sepenuhnya milik `ubuntu`**.
Jangan jalankan arahan git di sana dengan `sudo` — itulah yang menyekat Deploy 8 dua kali.

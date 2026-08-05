# BUKTI DEPLOY 9 — F6-W3 ke bakwim.my

**Tarikh:** 5 Ogos 2026 · **Komit:** `81d3901` · **CI:** run `31001021747`
**Sifat deploy:** PHP + `guides.json` + `targets.json` + allowlist baharu + ujian + harness e2e
+ satu langkah CI. **Tiada** migrasi · **tiada** perubahan JS/CSS produk · `catalog_version`
**berubah** (`2026.08.05.1` → `2026.08.05.2`) jadi `sync-help-index --delete` **WAJIB**.

---

## 1. Gate sebelum deploy

CI run **31002906128 = 7/7 HIJAU** pada `2cd7ab8`:

| Check | Status |
|---|---|
| `PostgreSQL, Redis, Meili, OCR and tests` **(wajib)** | success |
| `guidance-e2e-gate` **(wajib)** | success |
| `Docker app image` **(wajib)** | success |
| `Docker web image` **(wajib)** | success |
| `guidance-e2e (screen)` / `(workflow)` / `(tenant-admin-public)` | success / success / success |

⚠️ **Tiga pusingan CI, dua punca berbeza, kedua-duanya kod W2 (bukan W3):**
`31001021747` merah (memo `baris1()` hayat-proses) → `31001766297` merah (benih demo memilih
rekod secara tidak deterministik) → `31002906128` **hijau**. Punca penuh: laporan fasa §c.9/§c.10.

Tempatan pada komit sama: Pest **556 lulus / 1 skip** · pint passed · `npm run build` OK ·
gate 3 shard pada DB SEGAR (30/30 · 15/15 · 41/41) · agregator **GATE LULUS 83/473/172** ·
validator manifest bebas EXIT=0.

## 2. Baseline SEBELUM deploy (direkod daripada pelayan hidup)

```
git    : 56caac7
app    : sha256:1208bc00fa5c        web : sha256:8d7d46cbd6b1
revisi : 81b526f / 81b526f          (label OCI kedua-dua imej)
aset   : help-CrH0eDM1.css + help-D0185fq1.js  (imej app DAN imej nginx)
manifest.json sha256 (app = nginx):
         1aa1b3f4b87ae4d204e86d003706f3de0ea5101272e416b44691970013aa67f3
cakera : 5.3G baki (82% guna)       build cache 11.24GB (9.581GB boleh dituntut)
container: app clamav db meilisearch nginx redis scheduler worker — 8/8 running
health : OK
```

Laluan aset dalam container nginx **diukur** pada container hidup, bukan diandaikan:
`/var/www/html/public/build/assets` (sepadan `docker/Dockerfile:77`
`COPY --from=app /var/www/html/public /var/www/html/public` — BUKAN `/usr/share/nginx/html`).

⚠️ **Cakera 82%.** Prune build cache DAHULU (pelajaran Deploy 7: 11.8GB cache pada cakera 83%
hampir mematikan deploy). Skrip deploy melakukannya sebagai langkah 1.

## 3. Ramalan dibuat SEBELUM deploy

Diterbitkan daripada `docker/Dockerfile` + `git diff --name-only` + `npm run build` tempatan —
bukan tekaan. Ramalan yang ditulis selepas fakta tidak boleh salah, jadi ia tidak membuktikan
apa-apa.

| # | Ramalan | Sebab (boleh disemak sekarang) | Keputusan |
|---|---|---|---|
Asas disemak pada **julat penuh** `56caac7..2cd7ab8` (20 fail berubah), bukan hanya komit
pertama W3 — kerana dua komit pembaikan susulan menyentuh PHP dan benih:

```
migrasi baharu : 0        JS/CSS produk : 0        jumlah fail : 20
```

| # | Ramalan | Sebab (boleh disemak sekarang) | Keputusan |
|---|---|---|---|
| 1 | Nama aset **KEKAL** `help-D0185fq1.js` + `help-CrH0eDM1.css` | 0 fail `resources/js`/`resources/css` dalam julat penuh; binaan tempatan memberi nama identik | **TEPAT** — kedua-duanya identik |
| 2 | ImageID `diwan-app` **BERUBAH** | `COPY . .` — PHP, `guides.json`, `targets.json`, allowlist baharu, seeder semuanya berubah | **TEPAT** — `1208bc00fa5c` → `2c43512f9004` |
| 3 | ImageID `diwan-web` **BERUBAH** walau `public/` identik | `docker/Dockerfile:74-76` — `ARG GIT_SHA` + `LABEL` **sebelum** `COPY --from=app`, jadi build arg baharu membatalkan cache hiliran | **TEPAT** — `8d7d46cbd6b1` → `8e02a4b00223` |
| 4 | `migrate --force` → **Nothing to migrate** | 0 fail `database/migrations` dalam julat penuh | **TEPAT** — `INFO  Nothing to migrate.` |
| 5 | `sync-help-index --delete` → **83 guide** | `catalog_version` berubah, bilangan guide tidak (83/473 struktur beku) | **TEPAT** — `83 guide disegerakkan` |

**5/5 tepat.**

## 4. Output deploy sebenar

Skrip di-`scp` ke pelayan dan dijalankan sebagai **fail** (pelajaran Deploy 7 — `ssh 'bash -s' <`
menyebabkan `docker compose exec` menelan baki skrip). `< /dev/null` pada setiap `exec` sebagai
lapisan kedua. **Tiada** arahan git dijalankan dengan `sudo` (pelajaran Deploy 8).

Semakan keizinan SEBELUM deploy — pembaikan Deploy 8 kekal:

```
laluan bukan-ubuntu (tidak termasuk storage): 0
objek .git bukan-ubuntu                     : 0
```

```
prune build cache      -> Total: 859.7MB dituntut; cakera 82% (5.3G) → 79% (6.1G)
git                    -> 56caac7 → 2cd7ab8
migrate --force        -> INFO  Nothing to migrate.       (SIFAR baris data disentuh)
config:cache           -> Configuration cached successfully.
view:clear             -> Compiled views cleared successfully.  (volume storage BERKEKALAN)
diwan:sync-help-index  -> 83 guide disegerakkan ke indeks diwan_help_guides.
diwan:health           -> OK
/up 127.0.0.1:8080     -> 200      /up 127.0.0.1:80 -> 200      /up https://bakwim.my -> 200
diwan:smoke            -> SMOKE E2E: 9 lulus, 0 gagal.
queue:failed           -> INFO  No failed jobs found.
container              -> 8/8 running
```

`diwan:smoke` penuh (kesembilan-sembilan lulus): Daftar masjid · Lulus + KF disalin (40 nod) ·
Jemput ahli · Klasifikasi → difailkan + rujukan `04D588.100-4/26(1)` · Edarkan minit ·
Kelulusan (lulus + IP) · Carian jumpa rekod · Eksport ZIP dijana · Auto-padam + sijil + batu nisan.

## 5. Rantaian bukti runtime 5A (§10)

| # | Bukti | Deploy 8 | Deploy 9 |
|---|---|---|---|
| 1 | git SHA pelayan | `56caac7` | **`2cd7ab8`** |
| 2a | ImageID `diwan-app` | `1208bc00fa5c` | **`2c43512f9004`** |
| 2b | ImageID `diwan-web` | `8d7d46cbd6b1` | **`8e02a4b00223`** |
| 3a | container app/worker/scheduler | — | ketiga-tiganya naik semula drp imej `2c43512f9004` |
| 3b | container nginx | — | `8e02a4b00223` = #2b, bukan #2a |
| 4a | nama aset EXACT (imej app **dan** imej nginx) | `help-D0185fq1.js` + `help-CrH0eDM1.css` | **identik** |
| 4b | sha256 `manifest.json` app vs nginx | `1aa1b3f4…` | **`1aa1b3f4b87ae4d204e86d003706f3de0ea5101272e416b44691970013aa67f3`** (app = nginx) |
| — | label `image.revision` | `81b526f` | **`2cd7ab8` / `2cd7ab8`** kedua-dua imej |

**#5a = #5b = #6** — hash badan aset yang benar-benar **dihidangkan kepada awam**:

```
https://bakwim.my/build/assets/help-CrH0eDM1.css : f2406b313fca404825c3aabc40aec121
https://bakwim.my/build/assets/help-D0185fq1.js  : 753f7e263047d5d0df10f3501fe92a0d
                                                   (sha256sum | cut -c1-32)
https://bakwim.my/build/manifest.json sha256     : 1aa1b3f4b87ae4d204e86d003706f3de… = app = nginx
```

Nilai-nilai ini **identik dengan Deploy 6, 7 dan 8** — membuktikan aset benar-benar tidak
berubah, seperti diramalkan. Kerana nama aset tidak berubah, **bukti utama deploy ini ialah
kandungan dalam imej** (seksyen 6) + ImageID + label revisi — corak Deploy 2/7/8, kini kali
keempat.

## 6. Pengesahan LIVE

### (a) Kandungan katalog DALAM IMEJ PRODUKSI HIDUP — bukti utama

```
catalog_version : 2026.08.05.2
generic_declared: 236        <- 443 pada asas audit
action_generic  : 0          <- METRIK UTAMA F6 kekal 0
wait_for_user   : 172
  muat-naik#1 inbox-upload           wfu=true   Tekan Muat Naik Dokumen
  muat-naik#2 inbox-upload-dropzone  wfu=true   Pilih atau seret fail
  muat-naik#3 inbox-upload-submit    wfu=true   Tekan Hantar
  muat-naik#4 inbox-record           wfu=false  Sahkan toast dan baris baharu   <- W3
  muat-naik#5 inbox-classify         wfu=false  Semak antivirus sebelum klasifikasi
justifikasi     : 8 entri
inbox-record dalam InboxTable.php: 1
```

### (b) Runtime tour — semakan regresi awam (bebas peranan), diukur dalam Chrome hidup

`https://bakwim.my/log-masuk?panduan=public.login&langkah=0`:

```
guide       : public.login
kedudukan   : 1 daripada 2
tajuk       : "Masukkan identiti"
disorot     : login-identity  (tagName = INPUT — elemen sebenar, bukan halaman)
CTA         : "Seterusnya"
ralat palsu : false
aset dimuat : help-CrH0eDM1.css + help-D0185fq1.js   ← sepadan bukti 5A
```

`https://bakwim.my/bantuan?q=muat+naik+dokumen` → Pusat Bantuan dimuat, mengandungi
"Muat Naik Dokumen", **0 kebocoran Inggeris**.

### (c) Yang TIDAK dapat saya sahkan secara visual — dan sebabnya

Sasaran W3 (`inbox-record`) hidup pada `/app/{tenant}/peti-masuk`, yang memerlukan sesi dengan
**peranan tenant**. Diukur: kedua-dua tab pelayar yang ada kini dialih ke `/app/login` — sesi
pemilik sudah **luput**. Saya **tidak** mencipta atau menaip kredensial produksi.

Bukti tidak-langsung yang ADA, dan ia kuat:

| Bukti | Nilai |
|---|---|
| Katalog dalam imej hidup | `muat-naik#4 → inbox-record` |
| Kod dalam imej hidup | `inbox-record` hadir dalam `InboxTable.php` |
| Gate CI shard `screen` | hijau — memandu tour SEBENAR dan mengassert sorotan (`assertTrailTargets`) |
| Gate tempatan shard `screen` | 30/30, DB segar |
| Ujian render Pest | `inbox-record` unik + menandakan baris TERBAHARU + kekal betul merentas render |

**Pemilik boleh menutup jurang ini dalam satu langkah:** log masuk sebagai admin masjid atau
setiausaha, kemudian buka
`/app/<slug>/peti-masuk?panduan=screen.muat-naik-dokumen&langkah=3` — langkah 4 mesti menyorot
**sel tajuk baris pertama** Peti Masuk (dokumen terbaharu), bukan seluruh halaman.

## 7. Baseline untuk deploy seterusnya

```
git  2cd7ab8 · app 2c43512f9004 · web 8e02a4b00223
label org.opencontainers.image.revision = 2cd7ab8 (kedua-dua imej)
aset assets/help-D0185fq1.js + assets/help-CrH0eDM1.css  (TIDAK berubah sejak Deploy 6)
  sha256|cut -c1-32 : 753f7e26… / f2406b31…
manifest.json sha256 1aa1b3f4b87ae4d204e86d003706f3de0ea5101272e416b44691970013aa67f3 (app = nginx)
catalog_version 2026.08.05.2 · 83 guide dalam indeks · 8 justifikasi eksplisit
cakera 79% guna (6.1G baki) selepas prune 859.7MB
checkout /opt/diwan: 0 laluan bukan-ubuntu, 0 objek .git bukan-ubuntu
```

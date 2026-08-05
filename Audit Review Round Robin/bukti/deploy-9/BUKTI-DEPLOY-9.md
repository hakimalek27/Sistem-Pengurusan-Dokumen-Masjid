# BUKTI DEPLOY 9 — F6-W3 ke bakwim.my

**Tarikh:** 5 Ogos 2026 · **Komit:** `81d3901` · **CI:** run `31001021747`
**Sifat deploy:** PHP + `guides.json` + `targets.json` + allowlist baharu + ujian + harness e2e
+ satu langkah CI. **Tiada** migrasi · **tiada** perubahan JS/CSS produk · `catalog_version`
**berubah** (`2026.08.05.1` → `2026.08.05.2`) jadi `sync-help-index --delete` **WAJIB**.

---

## 1. Gate sebelum deploy

| Check | Status |
|---|---|
| `PostgreSQL, Redis, Meili, OCR and tests` **(wajib)** | *(diisi selepas CI)* |
| `guidance-e2e-gate` **(wajib)** | *(diisi)* |
| `Docker app image` **(wajib)** | *(diisi)* |
| `Docker web image` **(wajib)** | *(diisi)* |
| `guidance-e2e (screen)` / `(workflow)` / `(tenant-admin-public)` | *(diisi)* |

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
| 1 | Nama aset **KEKAL** `help-D0185fq1.js` + `help-CrH0eDM1.css` | `git diff --name-only 56caac7..81d3901 -- resources/js resources/css` = **0 fail**; binaan tempatan memberi nama identik | *(diisi)* |
| 2 | ImageID `diwan-app` **BERUBAH** | `COPY . .` — PHP, `guides.json`, `targets.json`, allowlist baharu semuanya berubah | *(diisi)* |
| 3 | ImageID `diwan-web` **BERUBAH** walau `public/` identik | `docker/Dockerfile:74-76` — `ARG GIT_SHA` + `LABEL` **sebelum** `COPY --from=app`, jadi build arg baharu membatalkan cache hiliran | *(diisi)* |
| 4 | `migrate --force` → **Nothing to migrate** | `git diff --name-only … -- database/migrations` = **0 fail** | *(diisi)* |
| 5 | `sync-help-index --delete` → **83 guide** | `catalog_version` berubah, bilangan guide tidak (83/473 struktur beku) | *(diisi)* |

## 4. Output deploy sebenar

*(diisi selepas deploy)*

## 5. Rantaian bukti runtime 5A (§10)

*(diisi selepas deploy)*

## 6. Pengesahan LIVE dalam Chrome

*(diisi selepas deploy)*

## 7. Baseline untuk deploy seterusnya

*(diisi selepas deploy)*

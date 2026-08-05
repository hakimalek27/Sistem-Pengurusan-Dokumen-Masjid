# BUKTI DEPLOY 8 — F6-W2 ke bakwim.my

**Tarikh:** 5 Ogos 2026 · **Komit:** `0dfd201` · **CI:** run 30972196342
**Sifat deploy:** PHP + Blade + `guides.json` + `targets.json` + seeder + harness e2e.
**Tiada** migrasi baharu · **tiada** perubahan JS/CSS produk · `catalog_version` **berubah**
(`2026.07.22.2` → `2026.08.05.1`) jadi `sync-help-index --delete` **WAJIB**.

---

## 1. Gate sebelum deploy

<!--GATE-->

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
| 1 | Nama aset **KEKAL** `help-D0185fq1.js` + `help-CrH0eDM1.css` | tiada JS/CSS produk disentuh | <!--R1--> |
| 2 | ImageID `diwan-app` **BERUBAH** | `COPY . .` — Blade/PHP/`guides.json` berubah | <!--R2--> |
| 3 | ImageID `diwan-web` **BERUBAH** walau `public/` identik | `LABEL …=$GIT_SHA` sebelum `COPY --from=app` | <!--R3--> |
| 4 | `migrate --force` → **Nothing to migrate** | tiada migrasi dalam commit | <!--R4--> |
| 5 | `sync-help-index --delete` → **83 guide** | `catalog_version` berubah, kiraan guide tidak | <!--R5--> |

## 4. Output deploy sebenar

<!--DEPLOY-->

## 5. Rantaian bukti runtime 5A (§10)

<!--5A-->

## 6. Pengesahan LIVE dalam Chrome

<!--CHROME-->

## 7. Baseline untuk deploy seterusnya

<!--BASELINE-->

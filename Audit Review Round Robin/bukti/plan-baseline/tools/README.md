# tools/ — Bundle Audit F0 (D11 #17)

Skrip pengukuran boleh-ulang untuk manifest baseline (PELAN-PEMBAIKAN.md §1 F0(ii)/(iii)).
**Bilangan fail bundle ditetapkan pada F0: 4** (`build-manifest.mjs`, `generate-help-targets-doc.mjs`,
`gen-ocr-fixtures.mjs`, `README.md` ini).

## Jana semula manifest (selepas katalog / role / halaman berubah)

```bash
# 1. Lapisan A+B role_routes (persekitaran seeded — DILARANG produksi):
DB_CONNECTION=sqlite DB_DATABASE=/tmp/rr.sqlite php artisan migrate:fresh --seed --force
DB_CONNECTION=sqlite DB_DATABASE=/tmp/rr.sqlite php artisan diwan:role-routes --json=/tmp/role-routes.json

# 2. Manifest 3 set (verifikasi kendiri terhadap invarian beku — gagal jika tidak sepadan):
node "Audit Review Round Robin/bukti/plan-baseline/tools/build-manifest.mjs" \
  --catalog resources/help/guides.json \
  --mobile "Audit Review Round Robin/bukti/pusingan-11-codex/production-mobile-all-tour-steps.json" \
  --role-routes /tmp/role-routes.json \
  --out "Audit Review Round Robin/bukti/plan-baseline/manifest.json"

# 3. Validator BEBAS (pelaksanaan berasingan — dua penjaga saling menjaga):
node scripts/audit/validate-plan-manifest.mjs \
  --manifest "Audit Review Round Robin/bukti/plan-baseline/manifest.json"

# 4. Dok registry sasaran (dijana, bukan tangan):
node "Audit Review Round Robin/bukti/plan-baseline/tools/generate-help-targets-doc.mjs"
```

## Nota

- Perubahan wave selepas freeze memerlukan sebab bertulis + diff denominator + kelulusan
  (§1 F0(ii-a)) — kemas jadual `FROZEN` dalam `build-manifest.mjs` DAN `EXPECT` dalam
  `scripts/audit/validate-plan-manifest.mjs` serentak, dan catat dalam bukti fasa.
- Lapisan C (probe HTTP) dikuatkuasakan `tests/Feature/PlanManifestTest.php` pada SETIAP
  larian suite + runner produksi F8 — bukan semasa penjanaan.
- Angka beku disahkan bebas 5× (P10/P11 → P13 → P15 → P17 → F0): 83 guide · 473 langkah ·
  443 generik (238+205) · 258 placeholder · 229 wait_for_user · 200 tindakan-generik ·
  470/473 step.id unik · 6 defect mobile.

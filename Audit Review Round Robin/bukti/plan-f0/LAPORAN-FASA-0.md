# Laporan Fasa 0 — Perkakas, Gate CI, Addendum

**Tarikh siap:** 3 Ogos 2026 · **Komit terakhir:** `fb40ff1` · **CI:** run 30770625567 HIJAU PENUH
**Sumber kebenaran:** `Audit Review Round Robin/PELAN-PEMBAIKAN.md` v1.11 MUKTAMAD §1 (F0)
**Bukti terperinci:** `VERIFIKASI-F0.md` §1–§22 (output sebenar setiap arahan)

---

## (a) Ringkasan apa dibina

Fasa perkakas pengukuran untuk seluruh pelan F1–F10 — **tiada tingkah laku produk diubah**.
19 fail repo D11 + bundle audit dibina: command `diwan:role-routes` (3 lapisan anti-tautologi
expected/declared/actual), command `diwan:audit-fixture` (tenant buangan `smoke-<uuid>`),
manifest baseline 3 set (cohort 25/124 · catalogue 83/473 · role_routes 410 entri),
gate Playwright 4 project + reporter JSON bersyarat, spec canary sesi, spec `guidance-full`
G1–G5 ber-shard, 2 skrip agregator/validator, spec produksi read-only + wrapper PowerShell,
3 fail ujian Pest baharu (PlanManifestTest 14, AuditFixtureCommandTest 5,
InboxAntivirusFailClosedTest 4 = S7), registri `targets.json` + dokumen dijana, fixture OCR
sintetik. Ditambah: `# ADDENDUM v2.6` pada spec (D10), label OCI `revision` pada kedua-dua
imej Docker (D9), dan CI tiga lapis (integration + matriks 3 shard + gate agregator).

## (b) Fail dicipta/diubah

**Perkakas D11 (19 fail + bundle):**
`app/Console/Commands/RoleRoutes.php` · `app/Console/Commands/AuditFixture.php` ·
`playwright.config.js` · `.github/workflows/ci.yml` · `e2e/ci-session-canary.spec.js` ·
`e2e/guidance-full.spec.js` · `e2e/production-guidance-readonly.spec.js` ·
`scripts/audit/aggregate-guidance-coverage.mjs` · `scripts/audit/validate-plan-manifest.mjs` ·
`scripts/audit/assert-playwright-json.mjs` · `scripts/audit/run-production-guidance-readonly.ps1` ·
`tests/Feature/PlanManifestTest.php` · `tests/Feature/AuditFixtureCommandTest.php` ·
`tests/Feature/InboxAntivirusFailClosedTest.php` · `resources/help/targets.json` ·
`docs/HELP-TARGETS.md` · `tests/fixtures/ocr/{sample-scan-1.png,sample-scan-2.png,terms.json}` ·
`Audit Review Round Robin/bukti/plan-baseline/{manifest.json,tools/*,runtime-baseline-2026-08-02.json}`

**Spec + config (D9/D10 + adjunct diisytihar):**
`DIWAN-SPEC-ADDENDUM-2026-07.md` (ADDENDUM v2.6) · `docker/Dockerfile` · `docker-compose.yml` ·
`pint.json` · `composer.lock` (dompdf 3.1.6) · `app/Console/Commands/SyncHelpIndex.php` ·
`e2e/guidance.spec.js` · `e2e/registration.spec.js` · `e2e/office-workflow.spec.js` ·
`e2e/ocr-upload.spec.js` · `e2e/helpers/upload.js` (baharu)

**Bukti:** `Audit Review Round Robin/bukti/plan-f0/{VERIFIKASI-F0.md,LAPORAN-FASA-0.md}`

## (c) Output verifikasi sebenar

Ditampal penuh dalam `VERIFIKASI-F0.md`. Ringkasan angka:

```
Pest                 432 passed / 1 skip (4,738 assertions)
diwan:role-routes    410 entri · 0 mismatch A↔B · nav 25/17/15/15/13/13/13/14 (dijana)
PlanManifestTest     14 passed (2,866 assertions) — termasuk lapisan C: 410 probe HTTP
                     × 10 identiti + silang-tenant 8 × 404
guidance-full        screen 30/30 · workflow 15/15 · tenant-admin-public 41/41
agregator (CI)       GATE LULUS: 83 guide · 473 langkah · 229 langkah tindakan (SET)
ci-guidance          12/12 · ci-domain 4/4 · ci-ocr 1/1 · canary 1/1
baseline runtime     rantaian 5A: 3a=2a · 3b=2b · 4a=4b · 5a=5b=6 (server 8342d95)
CI run 30770625567   7/7 job success
branch protection    TEPAT 4 check, strict=true
```

## (d) Kriteria Siap fasa

| # | Kriteria F0 | Status |
|---|---|---|
| 1 | ADDENDUM v2.6 ditulis (D10) | ✔ |
| 2 | `ARG GIT_SHA` + `LABEL …revision` kedua-dua stage (D9) | ✔ |
| 3 | Manifest baseline 3 set + `tools/` | ✔ |
| 4 | 19 fail repo D11 + bundle | ✔ |
| 5 | Baseline bukti runtime produksi (F0(v)) | ✔ |
| 6 | Branch protection TEPAT 4 check | ✔ |
| 7 | `php artisan test` hijau | ✔ 432/1 skip |
| 8 | `npm run build` hijau | ✔ |
| 9 | Blok lapis 1 tempatan (canary+smoke+domain+assert JSON) | ✔ |
| 10 | Validator manifest exit 0 | ✔ |
| 11 | **CI hijau semua check** | ✔ run 30770625567 |
| 12 | Commit `fix-audit-F0…` | ✔ 8 komit (F0 → F0k) |

## (e) Lencongan dari spec

**TIADA lencongan.** Adjunct berikut diisytihar terbuka — semuanya diperlukan untuk gate
berfungsi, tiada satu pun mengubah tingkah laku produk:

1. `pint.json` exclude arkib audit — CI `main` sudah **merah sejak `4e07a70` (pra-F0)`.
2. `composer.lock` dompdf 3.1.5→3.1.6 — 6 advisori GHSA; transitif, `composer.json` tak diubah.
3. `SyncHelpIndex --delete` toleransi `index_not_found` — Meili perawan CI.
4. `APP_LOCALE: ms` env CI — config lalai `en` + CI tiada `.env`.
5. Step Serve: `APP_ENV=local`, `MAIL_MAILER=log`, `MAIL_LOG_CHANNEL=single`,
   `PHP_CLI_SERVER_WORKERS=4` (Pest kekal `testing` melalui `phpunit.xml`).
6. `e2e/helpers/upload.js` — helper upload berpusat (fail bukan `.spec.js`; invarian
   PlanManifestTest kekal lulus).
7. `guidance.spec.js` membaca kiraan nav dari manifest; `ocr-upload.spec.js` titlePattern
   dari nama fixture — kedua-dua diarahkan pelan (gate F0(ii-b)#6 / syarat `ci-ocr`).

## (f) Nota/risiko untuk fasa seterusnya

**🔴 Tiga bug produk ditemui oleh gate ini — masuk skop F2 (§3), WAJIB ada ujian regresi:**

1. **Auto-advance tour boleh mati** (`VERIFIKASI-F0 §16`). `onHighlighted` → `watchForNextStep`
   dipanggil semula pada setiap re-highlight (dicetus morph Livewire) → `clearTransitionWatch`
   membunuh jadual `moveNext` 120ms → guard `help.js:363` menghalang poller baharu kerana
   sasaran berikut sudah wujud. Pengguna terpaksa tekan butang sendiri.
2. **Banner "Panduan menunggu" menolak klik tetikus** (`§17`, disahkan hidup di produksi `§20`).
   Vendor `driver.css`: `.driver-active * { pointer-events: none }`; banner ialah anak `<body>`
   dan TIADA `pointer-events: auto`. Bila (1) berlaku, pengguna tetikus **terkandas sepenuhnya**
   — hanya papan kekunci menyelamatkan (`help.js:242` `show.focus()`). Pembaikan = satu
   peraturan CSS + ujian regresi klik tetikus.
3. **Nilai medan borang boleh berganda** (`§18`). Morph Livewire yang mendarat semasa
   pengguna menaip boleh memulihkan nilai lama lalu input baharu ditambah di hujung
   (dibuktikan: slug berganda). Menjejaskan pengguna yang menaip laju selepas medan
   ber-`wire:model.blur`.

**Operasi:**
- Server produksi belum di-deploy dan **tidak sepatutnya** — F0 tidak mengubah runtime.
  Deploy pertama = **Deploy 1 (F1+F2)** ikut D7. Server git `3f94a90`; runtime imej `8342d95`.
- `retries` Playwright kekal **0** — sengaja. Ketiadaan retry inilah yang mendedahkan ketiga-tiga
  bug di atas; menambahnya akan menyembunyikan regresi F1–F10.
- Teknik `scratchpad/f0/run-under-load.sh` (beban CPU buatan) terbukti menghasilkan kegagalan
  jenis-CI secara tempatan — guna semula pada fasa seterusnya sebelum push.
- Tiket `SUP-260801-HXQ0DIOL` masih menunggu pemilik padam (Lampiran A1 audit).

**Seterusnya:** F1 — `app/Livewire/HelpLauncher.php` (§2 pelan). Baca semula §2 sebelum membina.

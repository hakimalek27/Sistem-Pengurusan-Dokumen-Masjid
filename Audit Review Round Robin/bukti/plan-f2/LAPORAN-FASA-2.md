# Laporan Fasa 2 — Runtime tour JS: satu predikat, label BM, modal & fokus

**Tarikh:** 3 Ogos 2026 · **Menutup:** RR-01-07/RR-03-03/RR-10-06 + RR-01-04 + RR-08-03 + RR-03-02
**Keutamaan FINAL-RUMUSAN:** #6, #7 · **Rujukan:** `PELAN-PEMBAIKAN.md` §3 (dibaca penuh dahulu)

---

## (a) Ringkasan apa dibina

**F2a — satu predikat untuk label DAN kelakuan.** `nextButtonLabel()` memutuskan label dengan
`resolveStepElement(next, false)` (TANPA fallback generik) sementara `onNextClick` memutuskan
kelakuan DENGAN fallback. Untuk langkah generik — 94% katalog — label berkata "Buat pada skrin"
padahal klik hanya `moveNext()`. Itulah "dah tekan ke belum?" yang dilaporkan pemilik dan 20×
CTA generik (RR-10-06). Keputusan kini datang dari modul tulen `stepAdvancePlan()` yang
digunakan oleh `nextButtonLabel`, `onHighlighted`, `onNextClick` DAN `watchForNextStep`.
`onNextClick` menjadi `switch (plan.kind)` — 7 kind, setiap satu satu kelakuan.
Label **"Saya sudah buat" disingkirkan** (§3.1 membenarkan pilihan ini): setiap label kini 1:1
dengan satu kelakuan.

**F2b — label BM pada popover fallback** (`prevBtnText`/`nextBtnText`/`progressText`;
`showProgress:false` kerana "1 daripada 1" tiada makna).

**F2c — auto-minimize overlap-aware.** Pada skrin kecil popover menutup modal yang baru dibuka
guide (RR-08-03). Pertindihan **diukur** (`getBoundingClientRect`) selepas satu frame; hanya
jika benar-benar bertindih, tempoh baca **1.8 s yang boleh dibatalkan** bermula. Semua timer
dibatalkan pada peralihan langkah, minimize manual, dan destroy (`clearAutoMinimise`).

**F2d — fokus.** Vendor Driver.js sudah memerangkap Tab (disahkan §3.4), jadi **tiada trap
custom**. Yang ditambah ialah apa yang vendor tidak buat: fokus awal masuk popover, dan fokus
**pulang** kepada pencetus (atau butang Pembantu Diwan) selepas tour ditutup. `aria-modal`
hanya pada popover fallback — popover utama sengaja tiada, kerana halaman di sana masih boleh
diguna melalui minimize.

**Sempadan §3 dihormati:** mekanisme sync (`watchForNextStep`, poll 120ms, 1045ms) TIDAK
diubah — hanya predikat pemilihannya kini datang dari plan yang sama.

## (b) Fail dicipta/diubah

| Fail | Perubahan |
|---|---|
| `resources/js/help/step-advance-plan.js` | **BAHARU** — modul tulen (tiada CSS/DOM), `stepAdvancePlan()` + `ACTION_KINDS` + jadual label↔kind dalam komen |
| `resources/js/help.js` | import plan; `planFor()`; `nextButtonLabel`/`onHighlighted`/`onNextClick`/`watchForNextStep` guna plan sama; label BM fallback; `scheduleAutoMinimise`/`clearAutoMinimise`; `focusPopover`/`clearFocusManagement`; `aria-modal` fallback sahaja |
| `e2e/step-advance-plan.spec.js` | **BAHARU** — 10 ujian jadual keputusan (Node ESM terus, tiada pelayar) |
| `playwright.config.js` | project `unit` baharu |
| `e2e/guidance.spec.js` | +5 ujian F2; CTA rujuk kelas (bukan teks label yang kini berubah) |
| `e2e/guidance-full.spec.js` | CTA rujuk kelas — selaras |
| `resources/css/help.css` | banner menunggu `pointer-events: auto` (menutup §17) |

## (c) Output verifikasi sebenar

```
$ vendor/bin/pint --dirty
{"tool":"pint","result":"passed"}

$ npx playwright test --project=unit
  10 passed (435ms)

$ npx playwright test --project=ci-guidance
  19 passed (10.8m)
OK [storage/app/plan-ci/ci-guidance.json]: 19 ujian, 0 skipped/timedOut/interrupted/unexpected/flaky.

$ npx playwright test --project=ci-domain
  4 passed (1.2m)

$ php artisan test
  Tests:  1 skipped, 453 passed (4791 assertions)

# Bukti penjaga CSS banner (peraturan pointer-events dibuang sementara):
  1 failed  ← regresi DITANGKAP
  (dipulihkan) 1 passed (6.0s)

$ npm run build   → ✓ built
# gate bundle bersih (arahan tepat §3.7)
OK: 1 bundle disemak, 0 padanan     ← 0 hook ujian dalam bundle produksi
```

## (d) Kriteria Siap fasa (§3.7)

| Kriteria | Status |
|---|---|
| ≥7 ujian unit `stepAdvancePlan` | ✔ 10 (7 kind + regresi RR-01-07 + kontrak 1:1) |
| 6 ujian e2e baharu | ✔ 5 F2 + regresi tour klasifikasi sedia ada (sync 1045ms kekal lulus) |
| `npm run build` bersih | ✔ |
| Suite Pest hijau | ✔ (tiada logik PHP berubah) |
| Bundle bersih — 0 hook ujian | ✔ arahan tepat, rc dibezakan |
| EN-leak runtime tour = 0 | ✔ ujian fallback assert regex `Previous\|Next\|N of N` |
| Matriks label: guide generik penuh → 0 CTA tindakan | ✔ ujian F2a (4 langkah `tenant.dashboard`) |
| Banner menunggu boleh diklik tetikus (regresi §17) | ✔ + bukti penjaga merah/hijau |

Belum: semakan manual 6 guide sampel pada produksi + gate CI hijau (bergantung push).

## (e) Lencongan dari spec

**TIADA.** Satu keputusan pelaksanaan yang §3.1 serahkan kepada pelaksana: label
**"Saya sudah buat" disingkirkan** (bukan dikekalkan untuk `action-then-navigate`), supaya
kriteria "setiap label 1:1 dengan satu kind" dipenuhi tanpa pengecualian. Kesan: ujian sedia
ada yang mencari teks itu dikemas kini kepada kelas CTA — perubahan ujian yang sah kerana
spec berubah (peraturan #9), dicatat di sini.

## (f) Nota/risiko untuk fasa seterusnya

1. **KETIGA-TIGA bug yang gate F0 temui kini DITUTUP** (VERIFIKASI-F0 §16/§17/§18):
   - auto-advance yang mati kerana re-highlight → predikat plan yang konsisten (F2a);
   - popover menutup modal pada skrin kecil → auto-minimize overlap-aware (F2c);
   - **banner "Panduan menunggu" menolak klik tetikus** → satu peraturan
     `pointer-events: auto` dalam `help.css` (§3.5 membenarkan fail ini). Ini bug yang
     **disahkan hidup di produksi** (§20) dan memerangkap pengguna tetikus sepenuhnya.
     Ujian regresi menggunakan klik tetikus SEBENAR; dibuktikan menangkap regresi (buang
     peraturan → merah; pulihkan → hijau).
2. **Deploy 1 (F1+F2) — aset frontend BERUBAH** (`help.js` + modul baharu), jadi rebuild
   **kedua-dua** imej `app` DAN `nginx` wajib; nginx menyimpan salinan `public/build` sendiri.
   Rantaian bukti runtime 5A wajib selepas deploy.
3. Ujian e2e kini merujuk **kelas CTA** dan bukan teks — kekalkan corak itu pada F6 supaya
   perubahan salinan teks tidak memecahkan gate liputan.

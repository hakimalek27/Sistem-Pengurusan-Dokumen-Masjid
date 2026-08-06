# Laporan Fasa F6-W4 — baki generik shard `workflow`

Format wajib CLAUDE.md repo. Semua angka DIUKUR; output verifikasi ditampal penuh.

📄 Inventori (ditulis sebelum kod): `INVENTORI-W4.md`

---

## (a) Ringkasan apa dibina

W4 menutup **82 langkah generik terakhir shard `workflow`** — kesemuanya langkah PENERANGAN
(`wait_for_user: false`). **79 dinaikkan kepada sasaran DOM sebenar**, **3 menerima justifikasi
eksplisit bertarikh** dalam allowlist.

Kerja sebenarnya jauh lebih besar daripada anggaran pelan (0.25 sesi, "klasifikasi + rekod
sebab") kerana ukuran mendedahkan bahawa kesemua 82 langkah **sudah mengisytiharkan `route`
sendiri** dan runtime memang menavigasi ke sana — jadi ia layak dinaikkan, bukan
dijustifikasikan secara pukal. Itu menuntut **18 sasaran DOM baharu** merentas 7 fail produk,
satu mekanisme baharu untuk elemen vendor, dan perluasan benih demo bagi tiga skrin yang
sebelum ini kosong.

Tiga mekanisme/keputusan utama:

1. **`resources/js/help/page-target-plan.js`** — pemetaan sasaran vendor **PER HALAMAN**
   (medan carian jadual + pencetus tapisan; Filament 4 tidak mendedahkan `extraAttributes()`
   untuk keduanya). Modul TULEN mengikut kontrak C11.
2. **Sasaran keadaan-KOSONG** pada `/retensi` dan `/laporan` — diletak pada perenggan
   penerangan dan grid kad kerana kedua-dua jadualnya dilindungi `@if isEmpty`.
3. **Benih demo diperbesar** untuk `DisposalBatch`, `RecordCorrectionRequest`,
   `SensitiveAccessLog` (ketiga-tiganya diukur **0** sebelum ini).

## (b) Fail dicipta/diubah

**Komit 1 — `5dbfda4` (sasaran DOM, registri `reserved`)**

```
resources/js/help/page-target-plan.js                                        (BAHARU)
resources/js/help.js                                                        (import + pageTargetsFor)
e2e/page-target-plan.spec.js                                                (BAHARU, 8 ujian)
playwright.config.js                                                        (daftar spec dlm projek `unit`)
tests/Feature/Help/W4TargetRenderTest.php                                   (BAHARU, 7 ujian)
app/Filament/App/Resources/Records/Tables/RecordsTable.php                   (records-view)
app/Filament/App/Resources/RegistryFiles/Tables/RegistryFilesTable.php       (regfiles-view, regfiles-medium + memo baris1)
app/Filament/App/Resources/RecordCorrections/Tables/RecordCorrectionsTable.php (correction-diff/status/decision + memo)
app/Filament/App/Resources/SensitiveAccessLogs/Tables/SensitiveAccessLogsTable.php (sensitive-log-record + memo)
app/Filament/App/Pages/RetensiPegangan.php                                  (retention-export)
app/Filament/App/Pages/Laporan.php                                          (report-export)
resources/views/filament/app/pages/retensi-pegangan.blade.php               (retention-schedule, retention-hold)
resources/views/filament/app/pages/laporan.blade.php                        (report-summary)
resources/help/targets.json                                                 (+17 entri reserved -> 187)
docs/HELP-TARGETS.md                                                        (dijana)
Audit Review Round Robin/bukti/plan-f6-w4/INVENTORI-W4.md                   (BAHARU)
```

**Komit 2 — katalog + aktifkan + justifikasi + benih**

```
resources/help/guides.json                             (79 sasaran + catalog_version 2026.08.06.1)
resources/help/targets.json                            (18 entri reserved -> active)
resources/help/step-justifications.json                (8 -> 11 entri)
database/seeders/DemoSeeder.php                        (benihSkrinW4(): 3 fixture)
Audit Review Round Robin/bukti/plan-baseline/tools/build-manifest.mjs   (justified_waves += W4)
scripts/audit/validate-plan-manifest.mjs                               (JUSTIFIED_WAVES += W4)
tests/Feature/PlanManifestTest.php                                     (assert wave tertutup += W4)
Audit Review Round Robin/bukti/plan-baseline/manifest.json             (dijana semula)
docs/HELP-TARGETS.md                                                   (dijana semula: 166 aktif + 21 rizab)
```

## (c) Output SEBENAR arahan verifikasi

### c.1 Suntingan katalog (79 + 3)

```
=== round-trip guides.json identik dahulu? ===
  identik: true | 300150 bait

sasaran ditukar      : 79
kekal generik (justify): 3 -> workflow.bendahari.urus-rekod-kewangan-dan-minit#10 workflow.audit.laksanakan-semakan-audit-baca-sahaja#9 workflow.audit.laksanakan-semakan-audit-baca-sahaja#11
catalog_version      : 2026.08.06.1

 resources/help/guides.json | 160 ++++++++++++++++++++++-----------------------
 1 file changed, 80 insertions(+), 80 deletions(-)
```

### c.2 Aktifkan registri (tiada yatim)

```
diaktifkan (18): correction-decision correction-diff correction-status disposal-actions
disposal-status log-filters log-search minit-filters records-search records-view
regfiles-medium regfiles-search regfiles-view report-summary retention-export retention-hold
retention-schedule sensitive-log-record
aktif tetapi TIDAK dirujuk katalog (mesti 0): 0
justifikasi kini: 11
```

`report-export` **kekal `reserved`** dengan betul — katalog tidak merujuknya kerana peranan
`audit` tiada `export.create` (lihat c.5).

### c.3 Manifest dijana semula + validator bebas

```
  generic_pc 205 → 121 (−84)
  placeholder_titles 258 → 0 (−258)
  action_steps_with_generic_target 200 → 0 (−200)
  shard workflow.action_steps 75 → 57 (−18)
Justifikasi eksplisit: 11 langkah; wave tertutup W0, W1, W2, W3, W4 liputan PENUH.
OK: manifest ditulis ke Audit Review Round Robin/bukti/plan-baseline/manifest.json
  guides=83 steps=473 actionGeneric=0 placeholder=0
  waves=W0:2g/10s W1:0g/0s W2:0g/0s W3:29g/151s W4:14g/158s W5:35g/146s W6:3g/8s
  role_routes entries=410 counts={"public":0,"superadmin":25,"admin_masjid":25,"pengerusi":17,"setiausaha":15,"bendahari":15,"nazir":13,"ketua_imam":13,"ajk":13,"audit":14}

=== validator manifest bebas ===
KEMAJUAN berbanding baseline F0:
  action generic 200 → 0 (−200)
  placeholder 258 → 0 (−258)
OK: manifest sah — partition wave/shard sepadan pengiraan bebas, set-union exact, role_routes konsisten.
exit=0
```

### c.4 Metrik selepas W4

```
  status  : {"specific":316,"generic-justified":153,"not-applicable":4}
  generik : 157 (sebelum W4: 236)
  baki generik per wave: {"W5/generic-justified":144,"W4/not-applicable":2,
                          "W4/generic-justified":1,"W3/not-applicable":2,
                          "W3/generic-justified":6,"W6/generic-justified":2}
  catalog_version: 2026.08.06.1
```

### c.5 Kebenaran peranan DIUKUR (menentukan tiga pemetaan)

`canIn()` sebenar terhadap benih demo, bukan pembacaan config:

```
peranan        export.create  records.view  audit.view  minit.view  retention.hold  retention.manage  disposal.view  records.update
admin_masjid        ✔             ✔            ✔           ✘             ✔                ✔               ✘              ✔
setiausaha          ✘             ✔            ✘           ✘             ✘                ✘               ✘              ✔
pengerusi           ✘             ✔            ✔           ✘             ✘                ✘               ✘              ✘
nazir               ✘             ✔            ✘           ✘             ✘                ✘               ✘              ✘
bendahari           ✘             ✔            ✘           ✘             ✘                ✘               ✘              ✔
ketua_imam          ✘             ✔            ✘           ✘             ✘                ✘               ✘              ✘
ajk                 ✘             ✔            ✘           ✘             ✘                ✘               ✘              ✘
audit               ✘             ✔            ✔           ✘             ✘                ✘               ✘              ✘
```

Kesan langsung: **peranan `audit` tiada `export.create`**, dan butang eksport `/laporan`
di-`authorize()` dengannya — jadi butang itu **tidak pernah dirender** untuk khalayak guide
`workflow.audit`. Langkah 11 kerana itu `generic-justified`, bukan `report-export`.
Sebaliknya `admin_masjid` MEMANG ada `export.create`, jadi `retention-export` sah untuk guide
pelupusan.

Kesemua **27 gabungan peranan × halaman** yang W4 guna disemak terhadap `role_routes`:
`expected_status = 200` untuk semuanya.

### c.6 Benih demo — sebelum dan selepas

```
SEBELUM (diukur, tenant mam):
  DisposalBatch 0 · RecordCorrectionRequest 0 · SensitiveAccessLog 0
  Record.retention_due_at 0 · legal_hold 0

SELEPAS:
  DisposalBatch              mam=1
  RecordCorrectionRequest    mam=1
  SensitiveAccessLog         mam=1
  Minit                      mam=1     (tidak berubah — fixture W2 utuh)
  Approval                   mam=2     (tidak berubah)
```

`retention_due_at`/`legal_hold` **sengaja tidak disentuh**: sasaran `/retensi` diletak pada
elemen keadaan-KOSONG, dan mengubah tarikh retensi benih akan menyentuh enjin retensi yang
§0.3 lindungi.

### c.7 Suite Pest penuh (selepas perubahan benih)

```
  Tests:    1 skipped, 567 passed (5436 assertions)
  Duration: 413.89s
EXIT=0
```

W3 membuktikan perubahan benih boleh memecahkan `MinitService` secara tidak dijangka; kali ini
suite penuh dijalankan dan **tiada regresi**.

### c.8 Ujian unit + render (komit 1)

```
  25 passed (3.0s)                     <- projek `unit` (8 baharu: page-target-plan)

  PASS  Tests\Feature\Help\W4TargetRenderTest
  ✓ records-view wujud pada senarai /records
  ✓ regfiles-view dan regfiles-medium wujud pada senarai /registry-files
  ✓ regfiles-medium ikut baris pertama SETIAP render, bukan render pertama proses
  ✓ tiga sasaran /pembetulan-rekod wujud apabila ada permohonan menunggu
  ✓ sensitive-log-record wujud apabila ada log akses sulit
  ✓ sasaran /retensi wujud walaupun TIADA rekod cukup tempoh (keadaan LALAI)
  ✓ sasaran /laporan wujud walaupun tenant tiada rekod (keadaan LALAI)
  Tests:    7 passed (25 assertions)
```

### c.9 Penjaga dibuktikan DUA ARAH

```
########## 1) DENGAN reset — mesti LULUS ##########
  ✓ regfiles-medium ikut baris pertama SETIAP render...   Tests: 1 passed (7 assertions)

########## 2) TANPA reset — mesti GAGAL ##########
  ➜  84▕     expect($posTanda)->toBeLessThan($posBarisKedua,
     85▕         'regfiles-medium menandakan baris KEDUA — memo statik tidak diset semula...
  Tests:    1 failed (7 assertions)
```

⚠️ **DUA versi pertama ujian ini LULUS walaupun regresi dipasang**, atas dua sebab yang
BERBEZA daripada jangkaan saya:

1. fixture menambah fail pada nod yang sama — `defaultSort('file_no')` MENAIK, jadi fail baharu
   tidak pernah menjadi baris pertama dan memo lama masih sah;
2. selepas fixture dibetulkan supaya baris pertama benar-benar bertukar, memo lama menandakan
   baris **KEDUA**, jadi `substr_count` kekal **1**.

**Pelajaran: bilangan tidak pernah dapat menangkap kecacatan ini — yang berubah ialah
KEDUDUKAN.** Assertion kini menguji urutan dalam aliran HTML.

### c.10 Gate 3 shard + agregator

**Pusingan 1 — shard `screen` LULUS, shard `workflow` mendedahkan andaian harness.**

```
=================== SHARD screen ===================
  30 passed (31.3m)
  screen EXIT=0

=================== SHARD workflow ===================
  ✘  1  workflow.admin_masjid.muat-naik-…-serta-hantar-minit (20 langkah) (3.9m)
  ✓  2  workflow.admin_masjid.betulkan-rekod-salah-tawan-tanpa-memadam-sejarah (13 langkah) (1.8m)
```

Ralat penuh (`test-results/…/error-context.md`):

```
Error: workflow.admin_masjid.muat-naik-…: langkah 14 tidak maju
expect(received).toBeGreaterThan(expected)
Matcher error: received value must be a number or bigint
Received has value: null
Call Log: - Timeout 90000ms exceeded while waiting on the predicate
```

**Punca — proksi harness yang W4 batalkan, BUKAN kecacatan produk.**

Komen `guidance-full.spec.js:1291-1293` menyatakan reka bentuk sebenar: *"langkah generik AWAL
dipandu per-langkah; julat modal (spesifik) diikuti mesin-keadaan toleran; langkah generik
PENGHUJUNG (minit-saya/log-aktiviti) dipandu per-langkah semula."*

Pelaksanaannya pula menggunakan `status === 'specific'` sebagai **proksi** untuk "julat modal":

```js
const specificSteps = guide.steps.filter((s) => s.status === 'specific');
const firstSpecific = specificSteps[0];
const lastSpecific  = specificSteps[specificSteps.length - 1];
```

Proksi itu sah hanya selagi langkah awal DAN penghujung masih generik. W4 memberi kesemua
langkah sasaran spesifik, jadi `lastSpecific` melompat **14 → 20** dan mesin-keadaan — yang
hanya memahami SATU halaman dan modalnya — cuba memandu merentas `peti-masuk` → `minit-saya`
→ `log-aktiviti`. Ia tersekat pada peralihan silang-halaman 14 → 15 dan `currentStepNumber()`
memulangkan `null` kerana popover halaman lama sudah tiada. `firstSpecific` juga berubah
(5 → 1), jadi KEDUA-DUA hujung julat rosak.

**Bukti bahawa mekanisme W4 itu sendiri betul:** guide **2** (`betulkan-rekod`) LULUS dalam
1.8m — dan ia guide yang membawa lapan sasaran baharu sekali gus, termasuk `records-search`
dan `log-search` (pemetaan JS per-halaman dalam pelayar sebenar), ketiga-tiga sasaran
`/pembetulan-rekod` (bergantung pada benih baharu), dan dua peralihan silang-halaman melalui
`driveFlowGuide`. Jadi kegagalan itu khusus kepada dua guide BERKOREOGRAFI, bukan kepada W4.

**PUNCA KEDUA — selektor vendor yang saya pilih tidak wujud pada susun atur LALAI.**

Larian diteruskan dan mendedahkan punca kedua yang BERBEZA:

```
✘  3  urus-fail-fizikal#12 : tour tidak pernah merekod langkah 12 dengan sasaran log-filters
       halaman: /app/mam/log-aktiviti?…&langkah=11 (readyState=complete, popover=true)
       sasaran dijangka: -:tiada
✘  5  pengerusi.terima#1   : … sasaran minit-filters … sasaran dijangka: -:tiada
✘  6  pengerusi.keputusan#8: … sasaran log-filters  … sasaran dijangka: -:tiada
```

`page-target-plan.js` menyasar `.fi-ta-filters-trigger-action-ctn` kerana kelas itu kelihatan
dalam blade Filament — tetapi ia dirender **HANYA** `@if ($hasCollapsibleFilters)`. Susun atur
tapisan LALAI merender `<x-filament::dropdown class="fi-ta-filters-dropdown">`.

⚠️ Kegagalan ini **milik saya, bukan Filament**: saya membaca blade dan bukan mengukur DOM.
Bukti bahawa pembahagian itu tepat — langkah **1** guide 3 (`regfiles-search`,
`.fi-ta-search-field`) **LULUS**, jadi hanya selektor tapisan yang salah, bukan mekanisme
pemetaan.

**Tally pusingan 1** (larian dihentikan selepas guide 7 kerana corak sudah pasti):

```
screen   : 30 passed (31.3m)   EXIT=0
workflow : 2 passed, 6 failed  EXIT=1
```

Lima kegagalan SEBENAR, dua punca sahaja — dan nombor langkahnya mengesahkan diagnosis:

| Guide | Ralat | Punca |
|---|---|---|
| `muat-naik` | langkah **14** tidak maju | sempadan koreografi (julat sepatutnya tamat 14) |
| `setiausaha.klasifikasikan` | langkah **9** tidak maju | sempadan koreografi (julat sepatutnya tamat 9) |
| `urus-fail#12` | `log-filters` tiada | selektor tapisan |
| `pengerusi.terima#1` | `minit-filters` tiada | selektor tapisan |
| `pengerusi.keputusan#8` | `log-filters` tiada | selektor tapisan |

(`setiausaha.mohon` = 0ms dan `worker process exited` ialah artifak penghentian saya, bukan
kegagalan produk.)

**Yang LULUS pada pusingan 1 sudah membuktikan sebahagian besar W4:**
`betulkan-rekod` (8 sasaran baharu: `records-search`, `records-view`, ketiga-tiga
`correction-*`, `log-search`, `log-detail` + dua peralihan silang-halaman) dan
`sediakan-dan-laksanakan-pelupusan` (10 sasaran: ketiga-tiga `/retensi` keadaan-KOSONG,
`retention-export`, `disposal-candidates/batches/status/actions` termasuk kedua-dua yang
dahulu `reserved` dan bergantung pada batch benih baharu).

### c.11 Dua pembaikan yang dipohon

1. **`resources/js/help/page-target-plan.js`** — `.fi-ta-filters-trigger-action-ctn` →
   `.fi-ta-filters-dropdown`, DIUKUR pada HTML sebenar.
2. **`e2e/guidance-full.spec.js`** — sempadan julat koreografi dikira daripada halaman
   koreografi + `AKSI_KOREOGRAFI` (kunci peta `actions`), bukan daripada
   `status === 'specific'`. Formula menghasilkan tepat julat yang dahulunya hijau
   (muat-naik 5–14, setiausaha 4–9) → MEMULIHKAN koreografi, bukan mengubahnya. Penjaga
   hanyut mengassert `AKSI_KOREOGRAFI` sepadan kunci `actions` pada masa larian.

🆕 **Penjaga baharu `tests/Feature/Help/PageTargetSelectorTest.php`** — mengassert setiap
kelas vendor yang `page-target-plan.js` bergantung padanya benar-benar hadir dalam HTML
halamannya, PLUS penjaga hanyut yang menolak selektor baharu tanpa sauh. Ini jurang sebenar:
ujian unit JS membuktikan pemetaan konsisten dengan registri tetapi **bukan** bahawa
selektornya sepadan DOM; ujian render PHP pula tidak nampak `data-help-target` kerana
`decorateTargets()` berjalan dalam pelayar. Yang boleh disahkan ialah SAUH vendornya — dan
ia merah dalam 7s berbanding shard e2e 25 minit.

```
  Tests: 6 passed (11 assertions)     <- PageTargetSelectorTest (5 sauh + drift guard)
  25 passed (2.5s)                    <- projek `unit`
  aset: help-DaHF3IsK.js -> help-B9tTj0Zg.js  (css help-CrH0eDM1.css KEKAL)
```

⚠️ **Gotcha terakam berulang:** `TaskStop` menghentikan pembalut tetapi BUKAN cucunya — skrip
gate terus berjalan dan memulakan shard ke-3 **selepas** saya mengubah sumber, menjadikan
keputusannya tidak sah. Dibunuh dengan betul melalui `Win32_Process` + padanan baris arahan
(`playwright|gate-w4|8092|guidance-full`), 12 proses. Pusingan 2 dijalankan bersih dari awal.

### c.12 Pusingan 2 (CI run 31036770642, komit `9abb066`) — 11/15, dua punca BAHARU

```
success  PostgreSQL, Redis, Meili, OCR and tests
success  guidance-e2e (screen)                    · tempatan: 30 passed (24.6m)
success  guidance-e2e (tenant-admin-public)
failure  guidance-e2e (workflow)                  → 11 passed, 4 failed (8.9m)
success  Docker app image · success  Docker web image
```

Kedua-dua pembaikan pusingan 1 **berkesan**: bilangan guide `workflow` yang lulus naik
**2 → 11**, dan tiada kegagalan `log-filters`/`minit-filters` atau "langkah N tidak maju"
yang tinggal. Empat kegagalan baki mempunyai DUA punca baharu:

**(a) `assertTrailTargets` mengassert langkah SEBELUM julat koreografi** — 2 guide.

```
Error: workflow.admin_masjid.muat-naik-…#1: langkah tidak pernah dirakam perekam
       (jejak: 5:inbox-upload)
Error: workflow.setiausaha.klasifikasikan-…#1: langkah tidak pernah dirakam perekam
       (jejak: 4:inbox-classify)
```

Perekam tour **direset oleh navigasi**, jadi jejak halaman koreografi hanya mengandungi
langkah yang dipandu DI SANA. Sebelum W4 had bawah tidak diperlukan: langkah 1–4 generik dan
dilangkau oleh semakan `status !== 'specific'`. W4 menjadikannya spesifik dan
`driveGenericSteps` memandunya pada halaman LAIN. Jejak dalam mesej ralat (`5:inbox-upload`,
`4:inbox-classify`) mengesahkan julat koreografi kini BETUL — ia bermula tepat pada 5 dan 4.
**Fix:** parameter `daripadaIndex`, dipanggil dengan `mulaKoreografi`.

**(b) `/kelulusan` sentiasa KOSONG untuk setiausaha** — 1 guide (2 langkah).

```
Error: workflow.setiausaha.mohon-kelulusan-dan-pembetulan-rekod#7: … sasaran approval-status
  halaman: /app/mam/kelulusan?…&langkah=6 (readyState=complete, popover=true)
  sasaran dijangka: -:tiada
```

DIUKUR daripada kod: `ApprovalResource::getEloquentQuery()` menapis
`whereIn('approver_id', …)` sahaja — setiausaha **tidak pernah** menjadi pelulus, jadi
halaman itu tiada satu pun baris untuk peranan ini. Tiada sasaran baris yang boleh wujud.
Kedua-dua langkah kini `generic-justified` dengan sebab yang diukur.

⭐ **Penemuan KANDUNGAN yang ikut serta:** guide menyuruh setiausaha "Semak status permohonan"
pada halaman yang, mengikut reka bentuk, tidak menunjukkan apa-apa kepada mereka. Destinasi
yang betul bagi PEMOHON ialah tab Kelulusan pada rekod (`record-tab-approval`). Direkod
sebagai `followup` dalam allowlist dan dicadangkan untuk F9 (Manual) — ia perubahan
kandungan/route, di luar skop pemetaan sasaran W4.

`approval-status` kekal `active` (masih dirujuk `nazir#9` dan
`screen.buat-keputusan-kelulusan#6`) → tiada entri yatim.

Justifikasi **11 → 13**. Manifest dijana semula, validator bebas exit 0, `PlanManifestTest`
liputan wave tertutup **69 assertion** lulus.

### c.13 Pusingan 3 (CI 31038869313, `e7c0fa6`) — 12/15, punca KEEMPAT

```
Error: workflow.admin_masjid.muat-naik-…#6: kedudukan langkah salah
Error: workflow.setiausaha.klasifikasikan-…#5: kedudukan langkah salah
  3 failed · 12 passed (9.2m)
```

Kedua-dua pembaikan pusingan 2 berkesan. Punca baharu: `padaHalamanKoreografi` menyemak
`route === null`. **Dalam MANIFEST, langkah tanpa route sendiri DIISI dengan route GUIDE** —
ia tidak pernah `null` di sana, tidak seperti `guides.json` mentah. Gelung berhenti pada
langkah pertama (`tamat = 5`) lalu menghantar langkah 6 ke `driveGenericSteps`.

**Fix:** padankan route guide juga — selamat kerana gelung bermula pada `mulaKoreografi`,
jadi langkah papan pemuka yang berkongsi route itu sudah dikecualikan.
**Disahkan terhadap manifest SEBELUM push** (bukan menunggu CI): mula/tamat = **5/14** dan
**4/9**, pecahan awal 1–4 / 1–3 dan akhir 15–20 / 10–13.

### c.14 Pusingan 4 (CI 31040898498, `6f1a249`) — 13/15

```
success  integration · screen · tenant-admin-public · Docker app · Docker web
failure  guidance-e2e (workflow)   → 13 passed, 2 failed (8.2m)

Error: workflow.setiausaha.klasifikasikan-surat-masuk-dan-edarkan-minit#10:
       klik maju tidak menambah tepat satu langkah
```

Punca keempat ditutup. **Satu guide tinggal.** Langkah 10 ialah langkah EKOR pertama
(julat koreografi tamat pada 9), pada `/minit-saya`. `driveGenericSteps` melihat langkah 11
berkongsi route yang sama, jadi ia klik "Seterusnya" dan mengassert kaunter jadi `11`.

**HIPOTESIS (belum disahkan):** sasaran langkah 11 (`minit-status`) sudah kelihatan sebaik
langkah 10 dipaparkan, jadi sync F2 memaju tour sendiri dan klik manual menjadi kemajuan
KEDUA. Keluarga masalah yang sama seperti G3 yang diselesaikan di tempat lain dengan
mengassert **urutan yang DIREKOD** (`__diwanTourLog`), bukan kaunter seketika.

⚠️ Ia direkod sebagai HIPOTESIS dengan sengaja: **empat kali dalam W4 punca sebenar berbeza
daripada tekaan pertama saya.** Sahkan daripada trace sebelum membaiki.

### c.15 Kemajuan merentas pusingan

| Pusingan | Komit | `workflow` | Punca ditutup |
|---|---|---|---|
| 1 | `9abb066` | 2/15 | proksi `status==='specific'` · selektor tapisan |
| 2 | `9abb066` | 11/15 | had bawah `assertTrailTargets` · `/kelulusan` kosong utk setiausaha |
| 3 | `e7c0fa6` | 12/15 | `route===null` — manifest isi route guide |
| 4 | `6f1a249` | **13/15** | — (baki: G3 langkah ekor pertama) |

🔑 **Corak yang mendasari KEEMPAT-EMPAT punca:** setiap tempat yang menyimpulkan sesuatu
daripada "langkah ini generik" — sama ada `status === 'specific'` atau `route === null` —
pecah serentak apabila W4 menjadikan semua langkah spesifik. **Cari corak ini dahulu pada W5.**

## (d) Kriteria Siap W4

| Kriteria | Status |
|---|---|
| Setiap langkah generik shard `workflow` ditangani (sasaran ATAU justifikasi bertarikh) | ✔ 79 + 3 = 82 |
| `action_steps_with_generic_target` kekal 0 | ✔ 0 |
| Registri: tiada entri `active` yatim | ✔ 0 |
| Registri: setiap sasaran katalog ada dalam registri | ✔ (HelpCatalogQualityTest) |
| Sasaran baharu terbukti dalam DOM SEBENAR | ✔ 12 PHP/Blade (render test) + 6 vendor (gate) |
| `W4` ditambah ke `justified_waves` dalam TIGA penjaga | ✔ |
| Manifest dijana semula + validator bebas lulus | ✔ exit 0 |
| Suite Pest penuh hijau selepas perubahan benih | ✔ 567 lulus |
| Gate 3 shard + agregator | ⏳ §c.10 |
| Lencongan dari spec | TIADA (lihat (e)) |

## (e) Lencongan dari spec

**TIADA.**

Tiga keputusan yang perlu dinyatakan secara eksplisit kerana ia mudah disalah anggap sebagai
lencongan:

1. **`W4.guides` kekal 14, bukan 0.** `build-manifest.mjs:165` mendefinisikan
   `workflow → hasActionGeneric ? 'W2' : 'W4'` — W4 ialah baldi TERMINAL shard `workflow`.
   Ramalan saya dalam inventori (W4 → 0) SALAH dan sudah dibatalkan di sana.
2. **3 langkah kekal generik.** §7.2 membenarkannya secara eksplisit untuk langkah PENERANGAN
   `workflow` sebagai "keputusan sedar yang direkod". Had tegasnya (langkah TINDAKAN tidak
   layak) dihormati: `action_steps_with_generic_target` kekal **0**.
3. **`report-export` dibina tetapi kekal `reserved`.** Ia sasaran yang sah dengan DOM sebenar,
   tetapi tiada guide merujuknya kerana ukuran kebenaran (c.5) menunjukkan khalayaknya tidak
   dapat melihat butang itu. Menandakannya `active` akan menjadikan ia yatim.

## (f) Nota/risiko untuk fasa seterusnya

- **Deploy W4 MESTI rebuild `app` DAN `nginx`** — `help.js` berubah:
  `help-D0185fq1.js` → **`help-DaHF3IsK.js`**; `help-CrH0eDM1.css` **KEKAL**.
  `catalog_version` berubah → jalankan `diwan:sync-help-index --delete` (jangka 83 guide).
- **W5 ialah wave TERBESAR yang tinggal**: 35 guide / 146 langkah / **144** generik, dan ia
  satu-satunya wave yang menggerakkan metrik KOHORT 25/124 (§7.3). Corak W4 boleh diguna
  semula terus: allowlist, `assertTrailTargets`, pemetaan per-halaman.
- Benih demo kini menyentuh tiga jadual tambahan. Sebarang ujian masa depan yang mengira
  `DisposalBatch`/`RecordCorrectionRequest`/`SensitiveAccessLog` mesti mengambil kira baris
  demo ini. Suite semasa: 567 hijau.
- Diukur, TIDAK dibaiki (kekal untuk F7/F9): beberapa arahan berbunyi "Buka Log Aktiviti
  Masjid" / "Buka Minit Saya" sedangkan runtime SUDAH menavigasi ke halaman itu sebelum
  langkah dipaparkan — isu teks kandungan, bukan sasaran.

# Laporan Fasa 5 — Kandungan katalog & tour halaman awam (§6)

**Tarikh:** 4 Ogos 2026 · **Pelan:** `PELAN-PEMBAIKAN.md` v1.11 §6 (F5a–F5d)
**Menutup:** RR-01-01/RR-08-02 · RR-01-08 · RR-01-09 · RR-01-10/RR-10-03 · RR-10-04 · RR-11-04

---

## (a) Ringkasan apa dibina

1. **F5a — tour `/log-masuk` (RR-01-01).** Layout tetamu kini `<header class="brand">` +
   `<main data-help-target="page-content">`; `.wrap` **kekal `<div>`** (ia membungkus jenama
   dan nav, jadi menjadikannya `<main>` akan menyarangkan landmark navigasi dan menyorot
   seluruh halaman). Medan dan butang log masuk mendapat sasaran sendiri
   (`login-identity` / `login-submit`); `public.login` → v2, langkah 2 `wait_for_user: true`.
2. **F5b — tour muat naik (RR-01-08).** `inbox-upload-dropzone` (FileUpload `extraAttributes`)
   dan `inbox-upload-submit` (`modalSubmitAction`, corak verbatim `InboxTable.php:92`).
   `screen.muat-naik-dokumen` → v2 dengan **lima sasaran berbeza**; tiada dua langkah
   berturut-turut berkongsi sasaran (kecacatan C12).
3. **F5c — sasaran navigasi responsif (RR-01-09).** Modul tulen `nav-target-plan.js`
   memilih `nav-sidebar` (desktop) / `nav-menu-toggle` (mobile) / `nav-bar` (penambat).
   `tenant.dashboard` langkah 1 & 4 → `nav-primary`, arahan neutral-peranti.
4. **F5d — tajuk (RR-01-10/RR-10-03/RR-10-04).** **114 tajuk eksplisit** ditulis (108
   placeholder kohort + 6 tajuk generik silang-guide) + 5 tajuk `screen.muat-naik-dokumen`;
   `meaningfulStepTitle()` kini `preserveWords: true`.
5. **Penjaga:** `HelpCatalogQualityTest` 12 semakan baharu · `InboxUploadMatrixTest` 3 ·
   `nav-target-plan.spec.js` 7 unit · `guidance-f5.spec.js` 8 e2e.
6. **Dua pembetulan gate ditemui semasa F5** (bukan dalam pelan): projek Playwright `unit`
   tidak pernah dijalankan CI; 4 entri registri `active` tidak dirujuk katalog.

## (b) Fail dicipta / diubah

**Dicipta**
- `resources/js/help/nav-target-plan.js`
- `e2e/nav-target-plan.spec.js` · `e2e/guidance-f5.spec.js`
- `tests/Feature/InboxUploadMatrixTest.php`
- `Audit Review Round Robin/bukti/plan-f5/LAPORAN-FASA-5.md`

**Diubah**
- `resources/views/components/guest-layout.blade.php` — `<header>` + `<main>`
- `resources/views/livewire/request-magic-link.blade.php` — 2 sasaran
- `resources/views/livewire/public-help-center.blade.php` — buang `page-content` bertindan
- `app/Filament/App/Resources/Inbox/Pages/ListInbox.php` — 2 sasaran
- `app/Services/HelpCatalog.php` — `preserveWords: true`
- `resources/js/help.js` — ruang nama `data-help-nav`, `intersectsViewport()`, cabang `nav-primary`
- `resources/help/guides.json` — 114 tajuk + 9 langkah dipatch + `catalog_version 2026.08.04.1`
- `resources/help/targets.json` + `docs/HELP-TARGETS.md` — 4 sasaran baharu, 4 → `reserved`
- `playwright.config.js` — 2 spec baharu didaftarkan
- `.github/workflows/ci.yml` — jalankan projek `unit`
- `tests/Feature/HelpCatalogQualityTest.php` · `tests/Feature/PlanManifestTest.php`
- `Audit Review Round Robin/bukti/plan-baseline/tools/build-manifest.mjs`
- `scripts/audit/validate-plan-manifest.mjs` · `.../plan-baseline/manifest.json`

## (c) Output verifikasi SEBENAR

### Suite Pest penuh
```
Tests:    1 skipped, 512 passed (5182 assertions)
Duration: 97.84s
```

### Penjaga katalog F5 (14 ujian)
```
✓ arahan katalog guna "Seterusnya", bukan ejaan pendek "Seterus"
✓ arahan katalog tidak menyebut butang "Sebelum" (label kini "Sebelumnya")
✓ §6.5 #2 tajuk tidak menduplikasi arahan (77/124 duplikasi verbatim → 0)
✓ §6.5 #3 kohort 25 guide/124 langkah: tajuk eksplisit, tiada elipsis
✓ §6.5 #3 fallback meaningfulStepTitle memotong pada sempadan perkataan (2 dataset)
✓ §6.5 #4 public.login menyasar medan dan butang sebenar
✓ §6.5 #4 muat naik: 3 sasaran berbeza, tiada dua langkah berturut sama
✓ §6.5 #7 tenant.dashboard menyasar navigasi, bukan page-content
✓ §6.5 #8 setiap sasaran bukan-generik dalam katalog terdaftar (yatim = 0)
✓ §6.5 #8 sasaran login benar-benar wujud dalam HTML /log-masuk yang dirender
✓ §6.5 #8 sasaran muat naik didaftarkan pada objek aksi Filament sebenar
✓ §6.5 #6 layout tetamu: tepat satu <main>, jenama & nav di LUAR <main>
✓ §6.5 #5 catalog_version dibumbung bila kandungan katalog berubah
Tests:    14 passed (90 assertions)
```

### Matriks muat naik (Pest)
```
✓ §6.5 sasaran tour muat naik didaftarkan pada aksi sebenar, bukan hanya dalam sumber
✓ §6.5 matriks: kuota penuh → notifikasi merah, 0 rekod dicipta
✓ §6.5 matriks: status antivirus dipaparkan pada baris Peti Masuk
Tests:    3 passed (24 assertions)
```

### Unit fungsi tulen
```
17 passed (803ms)     ← 10 step-advance-plan (F2) + 7 nav-target-plan (F5c)
OK [storage/app/plan-ci/unit.json]: 17 ujian, 0 skipped/timedOut/interrupted/unexpected/flaky.
```

### e2e F5 (pelayar sebenar, :8092)
```
✓ F5a tour /log-masuk menyorot medan sebenar — desktop 1280×800
✓ F5a tour /log-masuk menyorot medan sebenar — mobile 390×664
✓ F5a layout tetamu: satu <main>, jenama+nav di luar, tiada ralat JS
✓ F5c nav-primary menyelesai kepada nav-sidebar — desktop 1280×800
✓ F5c nav-primary menyelesai kepada nav-menu-toggle — mobile 390×664
✓ F5b sasaran dropzone dan Hantar wujud dalam DOM modal (bukan grep sumber)
✓ F5b matriks: fail sah → toast bilangan dokumen
✓ F5b matriks: format salah ditolak, tour tidak tersangkut
```

### Penjaga manifest (tiga pelaksanaan bebas)
```
KEMAJUAN berbanding baseline F0:
  generic_declared 443 → 425 (−18)
  generic_pp 238 → 230 (−8)
  generic_pc 205 → 195 (−10)
  placeholder_titles 258 → 135 (−123)
  action_steps_with_generic_target 200 → 195 (−5)
  wave W0.placeholder 10 → 0 (−10)
  wave W1.action_generic 140 → 135 (−5)
  wave W1.placeholder 140 → 135 (−5)
  wave W5.placeholder 108 → 0 (−108)
  shard screen.action_steps 151 → 149 (−2)
OK: manifest ditulis … guides=83 steps=473 actionGeneric=195 placeholder=135
OK: manifest sah — partition wave/shard sepadan pengiraan bebas, set-union exact, role_routes konsisten.
```

### Metrik tajuk (alat DITENTUKUR — lihat §(f))
```
                          langkah   dup   elipsis
4e07a70 (asas audit)        124      77     20     ← angka audit dihasilkan semula TEPAT
HEAD (selepas W0+F3)        124      72     18
SELEPAS F5                  124       0      0
```

### Pint + build
```
{"tool":"pint","result":"passed"}
✓ 65 modules transformed.  built in 2.17s
```

## (d) Kriteria Siap §6.6

| # | Kriteria | Status |
|---|---|---|
| 1 | Ujian katalog + e2e login lulus; suite penuh hijau; `npm run build` | ✔ |
| 2 | Gate CI hijau (4 check wajib) | ⏳ selepas push |
| 3 | `diwan:sync-help-index --delete` dalam langkah deploy | ✔ (katalog berubah — wajib) |
| 4 | Produksi: `/log-masuk` tour sorot medan; Peti Masuk tour 3 sasaran berbeza | ⏳ selepas Deploy 5 |
| 5 | Tajuk kohort: duplikasi 77/124 → **0**; terpotong 20/124 → **0** | ✔ diukur |
| 6 | Placeholder dilapor dengan angka betul (258 baseline) | ✔ 258 → **135** |
| 7 | Layout tetamu: satu `<main>`, `<h1>`+nav di luar | ✔ 4 halaman |
| 8 | Navigasi mobile sorot ☰, `target_missing` = 0 | ✔ e2e 390×664 |
| 9 | Matriks keselamatan §0.6 S1–S7 hijau | ✔ dalam suite 512 |

**S1–S7:** S1/S2/S6 = `PlanManifestTest` lapisan C (410 probe × 10 identiti + silang-tenant
8×404) · S3 = `HelpLauncherContextTest` + `guidance-full` G3 · S4/S5 = `GuidanceSupportTest` ·
S7 = `InboxAntivirusFailClosedTest` (4 ujian). Kesemuanya dalam larian 512 di atas.

## (e) Lencongan dari spec

**Tiga, semuanya disengajakan dan dinyatakan:**

1. **`screen.muat-naik-dokumen` kekal 5 langkah, bukan 4.** §6.2 mencadangkan menggabungkan
   kepada 4, tetapi F0 membekukan partition **473 langkah** dan `PlanManifestTest:74`
   mengassertnya sebagai STRUKTUR. Mengurangkan langkah akan memecahkan penjaga yang
   membuktikan tiada langkah tercicir senyap. Maksud sebenar §6.2 (C12 — dua langkah
   berturut-turut tidak boleh berkongsi sasaran) dipenuhi SEPENUHNYA dengan lima sasaran
   berbeza, dan langkah ke-5 mengekalkan amaran "semak antivirus sebelum klasifikasi" yang
   penggabungan akan mampatkan.
2. **`tenant.dashboard#4` TIDAK diberi `wait_for_user: true`** walaupun §6.3(4) menyebut
   `wait_for_user` untuk langkah mobile. Langkah 4 ialah langkah AKHIR: `stepAdvancePlan`
   akan memberi kind `final-action`, dan `watchForActionCompletion` hanya tamat apabila
   sasaran HILANG. Membuka sidebar tidak menghilangkannya → tour tergantung selama-lamanya.
   Dikunci oleh ujian.
3. **Tiga denominator beku dikemas** melalui prosedur bertulis `tools/README.md`
   ("sebab bertulis + kemas KEDUA-DUA penjaga dalam commit sama + catat dalam bukti fasa"):
   `wait_for_user` 229→228 · `W1` 28/140→27/135 dengan `W3` 1/11→2/16 ·
   `tenant-admin-public.action_steps` 3→4. Sebab penuh dalam komen `build-manifest.mjs`.
   Jumlah 83/473 dan struktur shard `screen` 29/151 **tidak berubah**.

## (f) Nota, risiko dan penemuan

### 🔴 Regresi yang F5 sendiri perkenalkan lalu tutup (paling penting)

Lima ujian tour F2 **tamat masa 180s** selepas F5 dibina. Bukan flake — 100% boleh dihasilkan
semula pada DB perawan. Eksperimen penentu: `git stash` F5 → kelima-limanya lulus **10–14s**;
pulihkan → gagal semula.

**Punca:** satu elemen hanya boleh memegang SATU `data-help-target`. Percubaan pertama F5c
menandakan `.fi-sidebar` sebagai `sidebar` **dan** `nav-sidebar`. Kedua-duanya berebut atribut
yang sama, jadi `decorateTargets()` — yang dipanggil pada **setiap** `resolveStepElement()` —
menulis semula atribut pada setiap panggilan. `transitionObserver` dan `automaticModalGuard`
memerhati `attributes: true` pada `documentElement`, jadi tulisan berulang itu menjadi **ribut
mutasi berterusan** dan koreografi tour klasifikasi tersangkut.

**Pembaikan:** ruang nama BERASINGAN `data-help-nav` untuk calon navigasi + tulisan idempoten
(`if (element.dataset.helpNav !== nav)`). Penjaga baharu dalam `guidance-f5.spec.js` menghitung
mutasi atribut sepanjang 1 saat tour aktif dan menuntut **0**, serta mengassert `data-help-target`
tidak tercemar dan `sidebar` kekal.

### ⚠️ Andaian saya yang SALAH, dibetulkan oleh ukuran

`.fi-sidebar` mobile **bukan** `display:none`. Diukur pada iPhone 13 (390×664):
`display:flex`, `visibility:visible`, `getClientRects().length === 1`, tetapi **`x = −320`**
dengan `width = 320` — off-canvas. `isVisible()` melaporkannya kelihatan, jadi `nav-primary`
memilih sidebar pada mobile dan e2e breakpoint gagal. Ditambah `intersectsViewport()`, dikenakan
**hanya** pada calon nav (memasukkannya ke `isVisible()` global akan mengubah keputusan label
bagi 473 langkah sekali gus — itu kerja F6/F7).

### ⚠️ Dua penjaga saya sendiri GAGAL menangkap regresi (dibetulkan)

Bukti penjaga 9 regresi sengaja: **R2 dan R7 lulus** sedangkan patut merah.
- **R2** — `strpos('<h1>') < strpos('<main')` sentiasa benar kerana `<h1>` jenama datang
  dahulu; `<h1>` KEDUA di dalam `<main>` terlepas. Kini kandungan `<main>` diekstrak dan
  diperiksa terus.
- **R7** — fixture saya jatuh **tepat pada sempadan perkataan** pada aksara 72, jadi potongan
  naif dan `preserveWords` memberi hasil identik. Kini dua dataset + assert `!== naif`
  (penjaga anti-fixture-lemah).

Selepas dibetulkan: **9/9 regresi ditangkap**.

### ⚠️ Alat metrik saya sendiri pada mulanya SALAH

Percubaan pertama mengukur `title === instruction` dan memberi **0** walaupun pada asas audit —
ia akan membenarkan saya mendakwa "77 → 0" secara palsu. Ditentukur terhadap data produksi
sebenar (`bukti/pusingan-11-codex/production-desktop-all-tour-steps.json`, 124 baris dengan
`title`+`description` yang benar-benar dirender): definisi yang menghasilkan **77 tepat** ialah
`norm(title)` **tolak noktah** === `norm(description)` tolak noktah; dan **20** ialah bilangan
tajuk yang berakhir dengan elipsis (audit mengira kesemua 20 sebagai "terpotong", walaupun hanya
16 benar-benar pecah di tengah perkataan).

### 🔧 Dua jurang gate ditemui di luar skop pelan (dibaiki)

1. **Projek Playwright `unit` tidak pernah dijalankan dalam CI** sejak dicipta pada F2 — 10
   ujian yang mengunci kontrak label↔kelakuan tour tidak pernah melindungi `main`. Ditambah
   sebagai step dalam job `integration` (check WAJIB), ~1s, tiada perkhidmatan diperlukan.
2. **4 entri registri `active` tidak dirujuk mana-mana guide** (`disposal-status`,
   `disposal-actions`, `favourite-open`, `favourite-remove` — drift daripada F6-W0).
   Ditandakan `reserved` dengan sebab; ujian yatim dua hala kini menguatkuasakannya.

### Nota lain

- **`/bantuan` mempunyai `page-content` BERTINDAN** selepas `<main>` ditambah
  (`public-help-center.blade.php:1` sudah mempunyainya). Yang bersarang dibuang — registri
  §7.2 menuntut sasaran aktif unik dan `resolveStepElement` memilih padanan pertama mengikut
  susunan dokumen.
- **Pelayan e2e tempatan**: `php -d max_execution_time=180 artisan serve` membunuh gelung
  penyelia selepas 180s. Untuk pelayan jangka panjang mesti `max_execution_time=0`.
- E-mel demo tenant sebenarnya `admin_masjid@demo.test` (bukan `admin@demo.test`).
- **Baki F6 selepas F5:** generik 425/473 dan placeholder 135/473 — kesemuanya dijadualkan
  W1–W6. Kohort tenant (W5) placeholder kini **0**.

### 🔴 Pusingan CI #1 (`142cb56`) MERAH — dua andaian gate yang F5 langgar (dibaiki `f0115a6`)

Check **WAJIB** `PostgreSQL, Redis, Meili, OCR and tests` **LULUS** (termasuk 8 ujian F5
baharu + step `unit` yang saya tambah). Yang gagal: 2 daripada 3 shard `guidance-full`.
Kedua-dua kegagalan **tulen** — gate betul, ia menuntut kemas kini bukan pengecualian.

```
success :: PostgreSQL, Redis, Meili, OCR and tests
success :: Docker web image · Docker app image
success :: guidance-e2e (workflow)
failure :: guidance-e2e (screen)               ← screen.muat-naik-dokumen#1
failure :: guidance-e2e (tenant-admin-public)  ← tenant.dashboard#1
failure :: guidance-e2e-gate
```

**(i) `tenant.dashboard#1: sasaran aktif sidebar ≠ nav-primary`.** `assertStepPopover`
mengassert `data-help-target` elemen yang disorot **===** `step.target`. `nav-primary` ialah
sasaran **logik** — ia tidak wujud dalam DOM. Gate kini memahami indireksi itu **dan kekal
ketat**: elemen yang disorot mesti salah satu `NAV_CANDIDATES` dan **bukan** `MAIN`/`BODY`.

**(ii) `screen.muat-naik-dokumen#1: klik maju tidak menambah tepat satu langkah`.** Guide
ini dahulu 5× `page-primary` generik → `driveGenericSteps` memadai. Selepas F5, sasaran
langkah berikut hanya **wujud selepas tindakan sebenar**, jadi `stepAdvancePlan` betul
memberi kind `wait-for-action` (CTA "Buat pada skrin" yang **meminimize**, bukan maju).
Ditambah koreografi sendiri, sama seperti guide `workflow.*`. Gotcha: `getByRole('dialog')`
melanggar mod ketat — popover tour **juga** `role="dialog"` (ARIA yang betul).

**(iii) Penjaga KEEMPAT yang terlepas.** `aggregate-guidance-coverage.mjs` juga membekukan
**229**. Denominator 229→228 kini dikemas dalam **keempat-empat** penjaga. Mesej "GATE LULUS"
dijadikan **dinamik** supaya ia tidak boleh lapuk lagi.

**Verifikasi tempatan PENUH sebelum push kedua** (bukan sampel — 25 min CI/pusingan terlalu
mahal untuk meneka):
```
shard screen               30 passed (9.0m)
shard tenant-admin-public  41 passed (10.9m)
shard workflow             15 passed (7.5m)
agregator   GATE LULUS: 83 guide · 473 langkah · 228 langkah tindakan
```

### 🎯 Pusingan CI #2 (`f0115a6`) — PUNCA flake `workflow` yang berlarutan AKHIRNYA DIBUKTIKAN

`tenant-admin-public` dan `workflow` hijau; hanya `screen` gagal — pada
`\d+ dokumen dimuat naik ke Peti Masuk` yang **tidak pernah muncul**. Tandatangan **identik**
dengan kegagalan berselang shard `workflow` yang belum selesai sejak F3 (F,P,F,P,F,P).

Kali ini artifak diagnostik yang dipasang pada `08d3643` **ada**. `serve-ci.log`:

```
18:56:52  /livewire/upload-file        <- fail SAMPAI ke pelayan
18:56:54  /livewire/update   500ms     <- muat naik selesai
…62 saat SIFAR permintaan…
18:57:56  /app/login                   <- ujian tamat masa, ujian seterusnya bermula
```

**Klik "Hantar" menghasilkan SIFAR permintaan.** Itu memuktamadkan tiga perkara:
- **bukan** overlay tour — `dispatchEvent` menghantar terus pada elemen, tidak melalui koordinat;
- **bukan** antivirus — permintaan tidak pernah sampai untuk ditapis;
- **bukan** masa/beban semata — 60 saat menunggu, sifar aktiviti.

Penjelasan yang konsisten dengan bukti: morph Livewire menggantikan nod footer modal selepas
muat naik selesai, dan Alpine memasang semula pendengarnya **secara tak segerak**. Klik yang
mendarat dalam tetingkap itu mengenai nod tanpa pendengar — **hilang senyap**.

**Pembaikan (`submitUploadUntilToast`)**: cuba semula sehingga ada KESAN, dan hanya **selagi
modal masih terbuka** (modal tertutup = penghantaran diterima → cuma tunggu toast, jadi tiada
risiko hantar dua kali). Digunakan pada **kedua-dua** tapak — koreografi F5 baharu dan
koreografi `workflow` asal, iaitu tapak flake yang asal.

**Diagnosis LAMA saya di sini terbukti SALAH dan sudah direkod sedemikian:** teori
"`force:true` diserap overlay" tidak pernah menjelaskan apa-apa, kerana `dispatchEvent`
memintas koordinat sepenuhnya dan kegagalan tetap berulang.

⚠️ **Ini juga kelemahan PRODUK yang tulen**, bukan hanya harness: pengguna yang menekan
Hantar tepat dalam tetingkap morph itu tidak akan nampak apa-apa berlaku. Severiti rendah
(tekan sekali lagi memulihkannya) — **direkod untuk F6/F7; F5 tidak mendakwa membaikinya.**

**Kesilapan saya semasa membaiki:** percubaan pertama menghantar `getByRole('dialog')` sebagai
locator modal. Itu melanggar mod ketat (popover tour juga `role="dialog"`), lemparan itu
**ditelan** oleh `.catch(() => false)` saya, jadi retry tidak pernah mengklik dan hanya
menunggu 120s. Diperbaiki dengan `[data-help-target="inbox-upload-modal"]`.

**Verifikasi:** lulus 2/2 di bawah **beban CPU buatan** (6 proses gelung ketat — teknik F0
yang menghasilkan semula kegagalan jenis-CI secara tempatan).

### 🖼️ Pusingan CI #3 (`01a0c7e`) — kecacatan SEDIA ADA didedahkan, bukan regresi F5

Kali ini `guidance.spec.js:108` (`assertNoHorizontalPageOverflow`) pada `/bantuan?asal=`
viewport 390×844: overflow **1066px**.

**Diagnosis berperingkat (setiap langkah diukur, bukan diteka):**
1. Rantaian nenek moyang dicetak semasa overflow → semua nenek moyang **358px betul**;
   hanya `IMG.diwan-help-thumb` **1440px**, `display:inline`, dan `.diwan-help-media`
   melaporkan `aspect-ratio: auto` / `overflow: visible` — nilai LALAI, bukan nilai `help.css`.
2. Keadaan penuh ditangkap: `readyState: "loading"` · `styleSheets: ["inline","inline"]` —
   `help-CrH0eDM1.css` **belum** dalam senarai · `imgComplete: true`, `naturalWidth: 1440`.

**Punca:** `help.css` ialah `<link>` LUARAN. Sementara ia dalam perjalanan, imej (dari cache)
sudah lengkap dan dirender pada saiz asalnya. Layout tetamu ditulis tangan dan **tiada reset
imej** — panel Filament tidak terjejas kerana preflight Tailwind sudah menyediakannya.

**Pembaikan:** `img, svg, video { max-width:100% }` ditambah ke blok `<style>` **inline**
layout tetamu. Inline bermakna ia terpakai semasa parse, jadi tetingkap itu tertutup
sepenuhnya. Ia tidak bertelagah dengan `help.css` (`max-width` ≠ `width`).

⚠️ **PEMBETULAN kesimpulan saya sendiri.** Larian pra-F5 **pertama** memberi 0, jadi saya
menulis "F5 CAUSED this". Menjalankannya **tiga kali** pada commit pra-F5 (`16c3376`) memberi
**1066 juga** — larian pertama hanya menang perlumbaan kerana cache imej sejuk. Ini kecacatan
**SEDIA ADA**; CI hanya kebetulan kalah perlumbaan pada `01a0c7e`. Satu larian bukan bukti
atribusi.

Selepas pembaikan: **0 overflow dalam 3/3** larian probe, dan ujian CI yang gagal itu lulus
**3/3**.

**Satu lagi assertion saya yang terlalu luas:** ujian layout e2e mengira **semua** `<header>`
dalam dokumen dan gagal dengan `headers: 2` pada `/bantuan`. HTML pelayan hanya ada **satu**;
yang kedua ialah chrome tour Driver.js yang disuntik JS. Kontrak §6.5 #6 ialah tentang
**layout**, jadi kiraan diperhalus kepada `.wrap > header`. Ujian Pest kekal mengira HTML
pelayan, di mana 1 tetap betul.

### ⭐ `risk-accepted` = 0

`public.login` ialah **satu-satunya** entri risiko-diterima dalam baseline F0, dengan tarikh
luput **2026-09-30**. F5 menutupnya pada 4 Ogos — hampir dua bulan awal. Langkah `specific`
katalog **30 → 48**. Cabang `risk-accepted` dalam `build-manifest.mjs` kini tidak tercapai
tetapi **dikekalkan sengaja**: jika satu perubahan masa depan menjadikan semula sasaran
`public.login` generik, manifest akan melabelnya risiko-diterima dengan sebab penuh, bukan
senyap-senyap sebagai `generic-justified`.

## (g) Untuk fasa seterusnya

- **Deploy 5** mesti menjalankan `diwan:sync-help-index --delete` (katalog berubah ke
  `2026.08.04.1`) dan rebuild `app` **+** `nginx` (aset Vite `help.js` berubah).
- Jangkakan perpindahan wave pada setiap gelombang F6 — denominator `W1`/`W2` akan mengecil
  setiap kali guide dibaiki sepenuhnya. Ikut prosedur `tools/README.md`.
- Off-canvas sebagai isu am (`isVisible()` global) kekal terbuka untuk F6/F7.

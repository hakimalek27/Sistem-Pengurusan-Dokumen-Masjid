# Laporan Fasa F7 — Kebolehcapaian & baki kecil (§8)

## (a) Ringkasan

F7 menutup RR-04-01 (tiga peraturan axe) dan RR-08-05 (kawalan viewer PDF), dan menambah
**larian axe automatik** sebagai penjaga kekal. Empat commit, sengaja dalam urutan ini:

| Commit | Kandungan |
|---|---|
| `f111ef6` | **ADDENDUM v2.7** — pengecualian polisi D5a untuk `axe-core`, DIKOMIT SEBELUM pakej ditambah |
| `d3b56a1` | §8.1 `link-name` · §8.2 `landmark-unique` · §8.3 `empty-table-header` |
| `c660119` | §8.4 kawalan viewer PDF + modul tulen + fixture PDF |
| `e2ffe59` | axe automatik + kecacatan landmark popover yang larian pertama temui |

Urutan commit #1 sebelum #4 bukan kosmetik: D5 menetapkan pengecualian bertulis mesti direkod
**semasa** F7 dan sebelum pakej ditambah. Memisahkannya menjadikan pematuhan itu boleh
dibuktikan daripada sejarah git, bukan didakwa dalam prosa.

## (b) Fail dicipta / diubah

```
DIWAN-SPEC-ADDENDUM-2026-07.md                        (ADDENDUM v2.7)
app/Filament/App/Resources/Inbox/Tables/InboxTable.php        (§8.1 disabledClick + teks BM)
app/Filament/**/Tables/*Table.php  (14 fail)                  (§8.3 recordActionsColumnLabel)
app/Providers/Filament/{App,Admin}PanelProvider.php           (§8.2 hook SCRIPTS_AFTER sendiri)
resources/js/a11y-landmarks.js                        BAHARU  (§8.2, tiada import)
resources/views/filament/a11y-assets.blade.php        BAHARU  (§8.2)
resources/js/viewer-control-plan.js                   BAHARU  (§8.4 modul tulen)
resources/js/document-viewer.js                               (§8.4 guna modul tulen)
resources/js/help.js                                          (neutralkanLandmarkPopover)
vite.config.js                                                (entri keenam)
package.json + package-lock.json                              (axe-core devDependencies)
playwright.config.js                                          (projek ci-a11y + spec unit baharu)
e2e/a11y-axe.spec.js                                  BAHARU  (11 ujian)
e2e/viewer-control-plan.spec.js                       BAHARU  (7 ujian unit)
tests/Feature/A11y/{InboxDuplicateColumn,A11yLandmarks,RecordActionsHeader}Test.php  BAHARU
tests/fixtures/viewer/{satu-halaman,tiga-halaman,tanpa-teks}.pdf                     BAHARU
Audit Review Round Robin/bukti/plan-f7/skrip/{gen-pdf-fixtures.mjs,gate-f7.sh}
```

## (c) Output verifikasi sebenar

```
ci-a11y                 11 passed (57.3s)   — 5 halaman x 2 viewport + penjaga anti-hijau-palsu
unit (JS)               33 passed (719ms)   — 26 -> 33
Pest                    620 passed / 1 skipped (5816 assertions)   — 606 -> 620
pint --dirty            passed
npm run build           exit 0; manifest mengandungi resources/js/a11y-landmarks.js
PlanManifestTest        15 passed (spec e2e baharu terdaftar dalam projek)
```

Fixture PDF disahkan boleh dibaca pdf.js SEBELUM digunakan:

```
satu-halaman     halaman=1  teks="Dokumen ujian satu halaman"
tiga-halaman     halaman=3  teks="Halaman pertama ujian"
tanpa-teks       halaman=1  teks=""
```

## ⭐ Penemuan utama — landmark PALSU daripada popover tour

Larian axe automatik yang PERTAMA menemui kecacatan yang tiada sesiapa cari, dan yang tiada
laporan manual akan temui melainkan penguji kebetulan membuka tour semasa mengimbas.

Driver.js merender, DI DALAM `div[role="dialog"]`, elemen `<header class="driver-popover-title">`
dan `<footer class="driver-popover-footer">`. Elemen `div` bukan kandungan seksyen mengikut
HTML, jadi pelayar memetakan kedua-duanya kepada landmark **`banner`** dan **`contentinfo`
peringkat HALAMAN**. Pada `/bantuan` itu berlanggar dengan `<header class="brand">` yang
sebenar — dua banner tanpa nama, tidak boleh dibezakan oleh pembaca skrin.

Diukur semasa popover terbuka:

```
HEADER  class="brand"                 label=""
HEADER  class="driver-popover-title"  label=""      <- banner PALSU
FOOTER  class="driver-popover-footer" label=""      <- contentinfo PALSU
```

Pada panel Filament ia **lolos** hanya kerana layout di sana tidak menggunakan `<header>`,
jadi popover menjadi satu-satunya banner dan kekangan keunikan tidak dilanggar. Kecacatan itu
hadir pada setiap halaman yang tournya terbuka; hanya kebetulan markup yang menyembunyikannya.

**Fix:** `neutralkanLandmarkPopover()` menetapkan `role="none"` pada kedua-dua elemen dalam
`onPopoverRender` (kedua-dua pemandu). Ia membuang peranan landmark tanpa menyembunyikan teks:
tajuk kekal dibaca dan kekal menjadi sumber nama popover; butang footer kekal boleh dicapai.

## ⚠️ Kesilapan saya dalam fasa ini — semuanya ditangkap oleh ukuran

1. **Penjaga §8.1 pertama saya tidak boleh gagal.** Ia memeriksa 400 aksara sebelum teks
   state; atribut tooltip yang panjang menolak tag `<a>` pembungkus KELUAR daripada tetingkap,
   jadi ia LULUS walaupun selepas `disabledClick()` dibuang. Ditulis semula dengan sauh
   STRUKTUR (`DOMXPath` pada `<td wire:key="...column.duplikat">`). Ini pelajaran F6-W3 #4
   ("kalibrasi BAIT akan hanyut — guna sempadan STRUKTUR") yang saya langgar semula.
2. **`toContain()` Pest bersifat VARIADIC** — argumen kedua ialah *needle* tambahan, bukan
   mesej kegagalan. Ditemui hanya kerana penjaga anti-fixture-lemah saya gagal.
3. **Fixture §8.3 hijau palsu.** 1 rekod dalam DB tetapi **0 baris kelihatan**:
   `makeRecord($m, null)` memberi status `PetiMasuk`, yang `RecordResource::getEloquentQuery()`
   kecualikan. Header tindakan hanya dirender apabila `count($records) > 0`, jadi ujian itu
   tidak menguji apa-apa. Prasyarat kini diassert secara eksplisit SEBELUM assertion sebenar.
4. **Probe axe saya bercanggah dengan spec** dan saya hampir menyalahkan saluran pelayar.
   Ujian kawalan: chromium terbina dan chrome berkelakuan **IDENTIK**. Puncanya probe
   menjalankan axe SERTA-MERTA manakala spec menunggu 2s — tour auto-mula selepas 450ms, jadi
   probe mengukur halaman TANPA popover. Satu `waitForTimeout` menukar keputusan.
5. **Kelas `fi-ta-actions-header-cell` yang pelan petik** hanya wujud pada cabang TANPA label
   (`index.blade.php:1590-1593`). Selepas pembaikan berjaya, vendor merender
   `<th class="fi-ta-header-cell">` biasa — jadi assertion terhadap kelas lama akan sentiasa
   gagal. Rujukan dalam dokumen mesti disahkan terhadap vendor terpasang, bukan dipercayai.

## (d) Kriteria siap

| Item | Status |
|---|---|
| §8.1 sel Duplikat `div`, bukan `a`; teks BM kedua-dua keadaan | ✔ penjaga struktur, dibuktikan dua arah |
| §8.1 susun/tapis tidak rosak; baris masih boleh dibuka | ✔ |
| §8.2 entri a11y berasingan + hook sendiri kedua-dua panel | ✔ |
| §8.2 `landmark-unique` = 0 **juga dengan `DIWAN_GUIDANCE_ENABLED=false`** | ✔ ujian khusus |
| §8.2 manifest mengandungi entri `a11y-landmarks.js` selepas build | ✔ |
| §8.3 `recordActionsColumnLabel('Tindakan')` semua jadual + API lapuk tidak digunakan | ✔ 14/14 |
| §8.4 butang disabled pada had / memuat / ralat; `aria-disabled` seiring | ✔ 7 ujian unit |
| §8.4 `pageInput.max` = `numPages` | ✔ |
| axe `link-name` 0 · `landmark-unique` 0 · `empty-table-header` 0 | ✔ 5 halaman x 2 viewport |
| Suite + lint + build | ✔ |
| Gate 3 shard | dijalankan kerana `help.js` berubah |

## (e) Lencongan dari spec

**SATU, dinyatakan:** §8.5 menetapkan laluan **lalai** ialah alat axe luaran/manual. Laluan
**automatik** dipilih, yang dibenarkan §8.5 selepas dua kelulusan — kedua-duanya ada (D5 a+b),
dan (a) direkod dalam ADDENDUM v2.7 sebelum pakej ditambah. Sebab pilihan itu direkod dalam
addendum: hanya larian dalam-suite boleh MENGGAGALKAN build apabila pelanggaran baharu
diperkenalkan; laporan manual mengukur satu ketika, penjaga mengukur selama-lamanya. Sasaran
tidak dilonggarkan.

## (f) Nota & risiko untuk fasa seterusnya

1. **BAKI F7 — e2e viewer dalam pelayar sebenar.** Logik kawalan kini diuji sepenuhnya sebagai
   fungsi tulen (7 ujian), dan fixture PDF 1/3/tanpa-teks sudah dikomit dan disahkan. Yang
   belum ialah memandu viewer SEBENAR: muat naik PDF melalui UI Peti Masuk, buka
   `/viewer/{media}`, dan sahkan `disabled` pada DOM + kes cari (kosong / jumpa / tidak jumpa /
   tanpa lapisan teks / Enter) + `.print-meta` mengandungi metadata sahaja. Koreografi muat
   naik yang diperlukan sudah wujud (`e2e/helpers/upload.js`).
2. **Hutang W5/W6 yang F7 warisi masih terbuka:** tukar `tenant.bantuan#1` dan
   `admin.bantuan#1` daripada `help-search` (seksyen 3211px) kepada `help-search-form`
   (70px) — dua baris `guides.json`, tetapi perlu pusingan gate penuh + `sync-help-index`.
   Juga: penghalusan semantik `admin.*` (LAPORAN-F6-W5.md §10) dan **ukuran SAIZ sorotan**
   dalam senarai semak, bukan sekadar identitinya.
3. **`ci-a11y` DISAMBUNGKAN ke CI dalam fasa ini.** Langkah `Accessibility (axe)` ditambah ke
   job lapis 1 `.github/workflows/ci.yml` (bentuk sama seperti `ci-domain`), termasuk
   `assert-playwright-json --min-tests 11` supaya larian yang tidak menjalankan apa-apa
   dikira GAGAL. Penjaga yang hanya berjalan pada mesin saya tidak melindungi `main`, jadi
   meninggalkannya tempatan bermakna F7 tidak benar-benar menutup apa-apa.
   ⚠️ Ia bukan *required check* sehingga pemilik menambahnya dalam branch protection; setakat
   ini ia sebahagian check WAJIB sedia ada `PostgreSQL, Redis, Meili, OCR and tests` kerana ia
   berjalan dalam job yang SAMA — jadi kegagalan axe memang memerahkan check itu.
4. `role="none"` pada elemen popover ialah pembetulan sisi-pelanggan ke atas markup vendor.
   Pembaikan "betul" (PR upstream Driver.js) di luar skop, sama seperti had yang §8.2 sudah
   akui untuk label landmark Filament.

---

## (g) Gate 3 shard — bacaan tempatan TIDAK BOLEH DIPERCAYAI sesi ini

Larian gate F7 memberi `screen` **30/30** ✔, `workflow` **15/15** ✔, dan
`tenant-admin-public` **38/41** ✘ (`public.registration` tamat masa klik 30s;
`tenant.bantuan#1: tiada elemen aktif`). Sebelum menyalahkan perubahan F7, keadaan mesin
diukur:

```
chrome  : 64 proses (62 = sesi pelayar PENGGUNA, bukan Playwright)
node    : 37 proses
RAM bebas : 1.9 GB daripada 31.7 GB
setiap ujian shard: ~1 minit  (gate W6 pada set ujian SAMA: ~12 saat)
```

Mesin **5× lebih perlahan** daripada semasa gate W6 yang memberi 41/41 pada set yang sama.
Kedua-dua ujian yang gagal **LULUS berasingan** pada HEAD yang sama. Hanya 4 daripada
proses tertinggal itu milik saya dan boleh dibunuh; bakinya sesi pelayar pengguna, jadi
RAM tidak pulih.

**Kesimpulan yang jujur: larian itu bukan bukti menyokong mahupun menentang perubahan F7.**
CI (runner Linux bersih) ialah pengesah, dan itu memang yang pelan tuntut sebelum deploy.
⚠️ Larian gate yang "dihentikan" TERUS BERJALAN dan melaporkan kegagalan BAHARU selepas
penghentian — pelajaran "TaskStop tidak membunuh cucu" berulang; 10 proses dibunuh mengikut
baris arahan sebelum pengukuran boleh dipercayai semula.

### Satu pembaikan SEBENAR yang siasatan itu hasilkan

Gejala `tenant.bantuan#1: tiada elemen aktif` ialah tandatangan TEPAT kecacatan W5d (morph
Livewire memadam sorotan). Itu membawa kepada pemeriksaan semula penjaga W5d, dan tetingkap
pemulihannya memang **terlalu ketat**:

> Commit Livewire yang memadam sorotan mengambil **~3s** pada mesin sihat (diukur W5d).
> Tetingkap pemulihan ialah **6s**. Pada mesin 2–3× lebih perlahan — runner CI yang sibuk,
> atau mesin dev yang kehabisan RAM — morph mendarat SELEPAS tetingkap ditutup dan pemulihan
> tidak pernah menembak.

Tetingkap dilebarkan **6s → 20s**. Ia selamat kerana bukan tetingkap masa yang menghalang
gelung: had sebenar ialah `highlightLossRepairs >= 2`, dan tinjauan berhenti sendiri sebaik
tour tidak aktif atau indeks langkah berubah. Tetingkap hanya menghalang tinjauan berjalan
selama-lamanya pada tab yang ditinggalkan terbuka.

**Bukti selepas pelebaran, di bawah beban yang SAMA yang tadi menggagalkannya:**

```
F6-W5d sorotan tour bertahan selepas morph Livewire   ✓ (1.4m)
gate tenant.bantuan                                    ✓ (36.4s)
gate public.registration                               ✓ (38.7s)
unit JS 33/33 · Pest 620 lulus/1 skip · pint passed · build OK
```

🔑 **Kegagalan di bawah beban ialah maklumat, bukan gangguan.** Ia tidak membuktikan
perubahan fasa ini rosak, tetapi ia mendedahkan pemalar masa yang dikalibrasi pada mesin
yang sihat sahaja — persis kelas pepijat yang muncul di CI dan hilang secara tempatan.

---

## (h) CI — dua pusingan, dan pengesahan bahawa gate tempatan memang tidak boleh dipercayai

### Pusingan 1 (`8a6ef28`) — MERAH pada langkah PERTAMA

```
failure  Install dependencies
npm error `npm ci` can only install packages when your package.json and
          package-lock.json ... are in sync
npm error Missing: @emnapi/core@1.11.3 from lock file
npm error Missing: @emnapi/runtime@1.11.3 from lock file
npm error Missing: @emnapi/wasi-threads@1.2.3 from lock file
```

**Punca saya sendiri, diukur:** `npm install --save-dev axe-core` yang dijalankan pada
**Windows** MEMANGKAS tiga entri optional khusus-platform yang bersarang di bawah
`node_modules/@tailwindcss/oxide-wasm32-wasi/node_modules/@emnapi/*`. Kiraan `@emnapi` dalam
lock: **12** (baseline `f111ef6`, terbukti berfungsi di CI) → **9**. Runner Linux memerlukan
entri itu; Windows tidak, jadi npm membuangnya secara senyap.

⚠️ `npm install --package-lock-only` **TIDAK** memulihkannya — npm menyelesaikan dep optional
mengikut platform SEMASA. Lock dibina semula daripada baseline yang terbukti berfungsi dengan
HANYA dua penambahan axe-core disisipkan: diff **+11/−0**, `npm ci --dry-run` exit 0.

⚠️ Percubaan pertama saya menulis lock itu dengan indentasi **2** dan menghasilkan diff
**2,886 baris**; `package-lock.json` guna **4**. Itu perangkap yang SAMA seperti
`targets.json` dalam W6 — direkod dua commit sebelumnya, lalu dilanggar semula dalam sesi
yang sama.

### Pusingan 2 (`89a7c91`) — HIJAU PENUH 7/7

```
success  PostgreSQL, Redis, Meili, OCR and tests
success  guidance-e2e (screen) · (workflow) · (tenant-admin-public)
success  guidance-e2e-gate
success  Docker app image · Docker web image
```

Langkah baharu, dalam job WAJIB:

```
success  Accessibility (axe)
         11 passed (46.7s)
         OK [storage/app/plan-ci/ci-a11y.json]: 11 ujian, 0 skipped/timedOut
```

⭐ **`guidance-e2e (tenant-admin-public)` HIJAU di CI** — shard yang sama yang memberi 38/41
secara tempatan. Itu mengesahkan bacaan §(g): kegagalan tempatan ialah keadaan mesin
(RAM bebas 1.9 GB, ~1 min/ujian), bukan perubahan F7. Menahan diri daripada "membaiki" kod
yang tidak rosak ialah keputusan yang betul di sini — tetapi hanya kerana keadaan mesin
DIUKUR dahulu, bukan diandaikan.

---

## (i) e2e viewer dalam pelayar sebenar — BAKI F7 ditutup

`e2e/document-viewer.spec.js` (projek `ci-domain`), 4 ujian, **4/4 lulus**. Ia memandu aliran
domain PENUH: log masuk → muat naik fixture ke Peti Masuk melalui UI → buka tab
"Lampiran & Versi" → ikut pautan "Buka Viewer" (URL bertandatangan, diambil daripada UI
kerana membinanya sendiri bermakna menguji URL yang pengguna tidak pernah dapat) → viewer.

Liputan §8.5: dokumen 3 halaman (prev disabled di 1, next disabled di 3) · dokumen 1 halaman
(kedua-duanya disabled, zum kekal hidup) · `pageInput.max === numPages` · clamp
`''` / `0` / `-3` / `abc` / `999` · had zum 50%/300% dengan `aria-disabled` seiring `disabled`
· cari jumpa / tidak jumpa / kosong / Enter / PDF tanpa lapisan teks · cetak
(`.print-meta` dipaparkan, `.viewer-stage` dan `.viewer-toolbar` TIDAK).

### EMPAT kegagalan sebelum hijau — keempat-empatnya dalam UJIAN saya, bukan produk

| Gejala | Punca sebenar |
|---|---|
| `Buka Viewer` "element(s) not found" | `RecordInfolist` BERTAB; pautan media dalam tab "Lampiran & Versi" (:56), bukan tab lalai "Maklumat" (:24) |
| `fill('abc')` ditolak Playwright | `input[type=number]` — pelayar sebenar pun tidak membenarkan pengguna menaipnya; nilai bukan-nombor hanya sampai melalui laluan PROGRAMATIK |
| klik zum ke-8 tamat masa | butang memang jadi `disabled` pada had — gelung saya menghukum produk kerana berkelakuan BETUL |
| cari `UNIKKEYWORD` tidak ditemui | **fixture** memotong teks |

Yang terakhir paling bernilai: aliran PDF **betul** (`/Length` padan bait-untuk-bait, teks
penuh ada dalam fail), tetapi `getTextContent()` pdf.js memulangkan
`"Halaman kedua mengandungi kata"` — MediaBox 300×200 terlalu sempit untuk baris 48 aksara
pada 18pt. Fixture yang memotong kandungan secara SENYAP menjadikan ujian carian tidak
bermakna. Dibetulkan kepada 612×792 dan **pengekstrakan SETIAP halaman disahkan**, bukan
halaman pertama sahaja seperti semakan asal saya.

### ⚠️ PENEMUAN PRODUK — muatan yang DIBATALKAN tidak pernah melaporkan ralat

Diukur, bukan diandaikan. Apabila permintaan media **dibatalkan** (bukan ditolak dengan status
ralat), `pdfjsLib.getDocument().promise` **tidak menolak**, jadi cabang `.catch()` dalam
`document-viewer.js` tidak pernah berjalan:

```
<div role="status" data-status data-error="false">Memuatkan dokumen...</div>   ← kekal selamanya
```

**Sifat keselamatan §8.4 KEKAL dipenuhi:** kawalan tetap dikunci, jadi pengguna tidak boleh
berinteraksi dengan viewer kosong — itu yang ujian assert. Yang hilang ialah **maklum balas**:
pengguna tidak pernah diberitahu mengapa tiada apa-apa berlaku.

**TIDAK dibaiki dalam F7, dan sebabnya dinyatakan:** menambah tempoh-tamat muatan ialah
keputusan reka bentuk dengan pertukaran nyata (berapa lama? apa kesannya pada sambungan mudah
alih perlahan atau PDF besar?) dan §8.4 tidak memintanya — ia meminta kawalan kekal disabled,
yang memang berlaku. Diserahkan kepada pemilik/F8 dengan ukuran penuh di atas.

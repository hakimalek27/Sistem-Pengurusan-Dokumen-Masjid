# Laporan Fasa F6-W5 — shard `tenant-admin-public`

Asas: `cea55da` (Deploy 10, F6-W4 LIVE). 📄 Inventori & pemetaan: `INVENTORI-W5.md`.

---

## (a) Ringkasan

W5 ialah gelombang TERBESAR F6: **144 langkah generik / 35 guide**, dan satu-satunya yang
menggerakkan metrik **KOHORT** (23 daripada 25 guide kohort berada di dalamnya).

**98 langkah dinaikkan** kepada sasaran DOM sebenar dan **46 dijustifikasikan** dengan sebab
yang diukur. `generic_declared` **159 → 61**. Panel Pentadbir Platform menerima sasaran tour
buat kali PERTAMA — W5 ialah gelombang pertama yang menyentuhnya langsung.

Kerja utama: 21 sasaran DOM baharu (Blade, jadual Filament, dan pemetaan vendor per-halaman),
6 sasaran yang sudah dirender produk tetapi **tidak pernah didaftarkan**, perluasan benih demo
untuk dua skrin yang kosong khusus untuk peranan gate, dan pembaikan satu memo statik yang
tidak pernah ditetapkan semula sejak W1.

## (b) Fail dicipta / diubah

**Sasaran DOM baharu**
- `resources/js/help/page-target-plan.js` — +11 laluan (papan pemuka 2 panel, carian jadual
  ×5, tiket ×2 panel)
- `resources/views/filament/app/pages/cari-rekod.blade.php` — 5 sasaran borang carian
- `resources/views/filament/app/pages/ahli-peranan.blade.php` — 3 sasaran + id ahli pertama
  bukan-superadmin dikira sekali
- `resources/views/filament/app/pages/tetapan-masjid.blade.php` — 2 seksyen
- `resources/views/filament/app/pages/laporan.blade.php` — `report-breakdown`
- `resources/views/filament/app/widgets/onboarding-checklist.blade.php` — sasaran + tajuk BM
- `resources/views/filament/pages/help-analytics.blade.php` — `analytics-metrics` (2 panel)
- `resources/views/filament/admin/pages/{status-sambungan,whatsapp-platform,tetapan-platform}.blade.php`
- `app/Filament/App/Resources/Delegations/Tables/DelegationsTable.php` — `delegation-revoke` + corak `baris1()`
- `app/Filament/App/Resources/Inbox/Tables/InboxTable.php` — `inbox-view` `inbox-source` `inbox-spam`
- `app/Filament/App/Resources/MosqueActivityLogs/Tables/MosqueActivityLogsTable.php` — `log-time` + **reset memo**
- `app/Filament/App/Resources/SensitiveAccessLogs/Tables/SensitiveAccessLogsTable.php` — `sensitive-log-target`
- `app/Filament/App/Resources/RegistryFiles/Tables/RegistryFilesTable.php` — `regfiles-status`
- `app/Filament/App/Resources/RegistryFiles/RelationManagers/AccessGrantsRelationManager.php` — **reset memo**

**Data & katalog**
- `database/seeders/DemoSeeder.php` — `benihSkrinW5()` (delegasi aktif + minit tindakan)
- `resources/help/guides.json` — 98 sasaran, 24 route langkah, `catalog_version 2026.08.07.1`
- `resources/help/targets.json` — 187 → 223 entri (199 aktif · 24 rizab)
- `resources/help/step-justifications.json` — 13 → 59 entri
- `docs/HELP-TARGETS.md` — dijana semula

**Penjaga**
- `tests/Feature/Help/W5TargetRenderTest.php` (BAHARU)
- `tests/Feature/Help/PageTargetSelectorTest.php` — +6 sauh panel admin + penjaga LALUAN
- `tests/Feature/PlanManifestTest.php` · `scripts/audit/validate-plan-manifest.mjs` ·
  `Audit Review Round Robin/bukti/plan-baseline/tools/build-manifest.mjs` — `justified_waves` +W5
- `e2e/page-target-plan.spec.js` — invarian perkongsian sasaran DIKETATKAN
- `tests/Feature/OfficeWorkflowTest.php` — tajuk widget BM

**Skrip kerja** — `bukti/plan-f6-w5/skrip/{peta-w5,tambah-registri-w5,sunting-katalog-w5,justifikasi-w5}.mjs` + `gate-w5.sh`

## (c) Output verifikasi sebenar

### c.1 Gate tempatan 3 shard + agregator — PUSINGAN 2, semua HIJAU

```
=================== SHARD screen ===================
  screen EXIT=0                 30 passed (8.0m)
=================== SHARD workflow ===================
  workflow EXIT=0               15 passed (8.8m)
=================== SHARD tenant-admin-public ===================
  tenant-admin-public EXIT=0    41 passed (10.8m)
=================== AGREGATOR ===================
GATE LULUS: 83 guide - 473 langkah - 172 langkah tindakan
  union tiga shard sepadan manifest (set, bukan kiraan)
```

Payload shard (`complete` dikira daripada `doneGuides.size`, gagal-tertutup):

```
shard-screen.json               complete=true  29 guide  151 langkah  failures=0
                                specific 143 - generic-justified 6 - not-applicable 2 - blocked 0
shard-workflow.json             complete=true  14 guide  158 langkah  failures=0
                                specific 153 - generic-justified 3 - not-applicable 2 - blocked 0
shard-tenant-admin-public.json  complete=true  40 guide  164 langkah  failures=0
```

Agregator: `guide_ids` union 83 = jangkaan 83, missing 0, extra 0, overlap 0.

### c.2 Suite PHP + unit JS + penjana

```
php artisan test              601 passed, 1 skipped (5753 assertions)   [OCR di-skip: tesseract Docker sahaja]
npx playwright --project=unit  26 passed (700ms)
vendor/bin/pint --dirty        passed
npm run build                  EXIT=0
  assets/help-DHUxqBmp.js  37.07 kB   (sebelum: help-B9tTj0Zg.js)
  assets/help-CrH0eDM1.css 14.90 kB   KEKAL
build-manifest.mjs             OK  guides=83 steps=473 actionGeneric=0 placeholder=0
  waves=W0:2g/10s W1:0g/0s W2:0g/0s W3:29g/151s W4:14g/158s W5:35g/146s W6:3g/8s
  Justifikasi eksplisit: 59 langkah; wave tertutup W0..W5 liputan PENUH
validate-plan-manifest.mjs     OK: manifest sah - partition wave/shard sepadan pengiraan bebas,
                               set-union exact, role_routes konsisten   (exit 0)
generate-help-targets-doc.mjs  OK: docs/HELP-TARGETS.md dijana (199 aktif + 24 rizab)
```

### c.3 Kemajuan berbanding baseline F0 (dilaporkan oleh penjana manifest)

```
generic_declared                  443 -> 61   (-382)     [159 -> 61 dalam W5 sahaja]
generic_pp                        238 -> 6    (-232)
generic_pc                        205 -> 55   (-150)
placeholder_titles                258 -> 0    (-258)
action_steps_with_generic_target  200 -> 0    (-200)
wave W5.placeholder               108 -> 0    (-108)
```

Status per-langkah seluruh katalog: **specific 412 - not-applicable 23 - generic-justified 38
- risk-accepted 0 - blocked 0** (473 jumlah). `blocked = 0` ialah syarat keluaran §7.3.

## (d) Kriteria siap

| Kriteria | Status |
|---|---|
| Setiap langkah W5 mempunyai status yang sah (bukan baseline automatik) | ✔ 98 specific - 46 justifikasi bertarikh |
| `blocked = 0` | ✔ |
| `action_steps_with_generic_target` kekal 0 | ✔ |
| Sasaran baharu wujud dalam DOM SEBENAR, bukan hanya registri | ✔ `W5TargetRenderTest` 17/17 |
| Kelas vendor yang pemetaan JS bergantung padanya disahkan | ✔ `PageTargetSelectorTest` 17/17, termasuk 6 sauh panel admin |
| Registri: 0 yatim dua hala | ✔ `HelpCatalogQualityTest` |
| Denominator beku dikemas dalam SEMUA penjaga | ✔ tiga tempat `justified_waves` |
| Gate 3 shard + agregator hijau tempatan | ✔ 30/30 - 15/15 - 41/41 - GATE LULUS |
| Suite penuh + lint + build | ✔ 601 lulus - pint - build |
| Penjaga baharu dibuktikan menangkap regresi | ✔ memo statik: merah dgn regresi, hijau tanpa |

## (e) Lencongan dari spec

**TIADA lencongan spec.** Dua perubahan produk di luar pemetaan semata-mata, kedua-duanya
dinyatakan:

1. **Tajuk widget "Checklist Onboarding" → "Senarai Semak Persediaan".** Peraturan repo #6
   menuntut teks UI Bahasa Melayu, dan tour `tenant.dashboard#3` merujuknya sebagai "senarai
   semak persediaan" — tajuk berlainan bahasa pada langkah yang sama mengelirukan pengguna.
   `OfficeWorkflowTest` dikemas kerana PRODUK berubah dengan sengaja (peraturan #9).
2. **Reset memo statik pada dua kelas.** `MosqueActivityLogsTable` dan
   `AccessGrantsRelationManager` mengisytiharkan `$barisPertamaId` sejak W1 tetapi tidak
   pernah menetapkannya semula — jurang yang sama yang memerahkan CI tiga pusingan pada W3.

## (f) Nota & risiko untuk fasa seterusnya

1. **F6-W6 kecil** — `public`, 3 guide / 2 langkah generik sahaja.
2. **Sasaran `reserved` yang kini kelihatan:** `help-center` `help-diagnosis`
   `help-preferences` `help-support` — calon W6/F7 tanpa perlu ditemui semula.
3. **Cadangan penjaga F8 (arah KETIGA):** setiap `data-help-target` dalam sumber mesti ada
   dalam registri. Tanpanya, sasaran boleh hidup dalam DOM selama berbulan tanpa dikesan —
   enam ditemui dalam W5.
4. **Penemuan kandungan untuk F9:** `tenant.kelulusan` menawarkan lapan peranan sedangkan
   hanya dua boleh menggunakan skrin itu. Audit menyeluruh dicadangkan: *peranan yang guide
   tawarkan mesti peranan yang benar-benar boleh menggunakan skrinnya.*
5. **F10 housekeeping:** `.gitignore` tiada `.env.bak*` (lihat §9).
6. **Deploy 11** — aset BERUBAH, jadi rebuild `app` DAN `nginx`; `catalog_version` berubah
   jadi `sync-help-index --delete`; benih demo berubah jadi ⛔ JANGAN jalankan seeder pada
   produksi. Cakera pelayan 61%, prune tidak diperlukan.

---

## PENEMUAN — direkod supaya tidak hilang

### 1. ⚠️ Dua perangkap "skrin kosong per-peranan", ditemui SEBELUM gate

Inventori §5.1 W4 menetapkan satu semakan wajib: *adakah skrin ini ada baris untuk peranan
yang guide ini tujukan?* Dijalankan pada benih demo sebenar melalui pelayar. Ia menangkap dua
perkara yang setiap satunya bernilai satu pusingan gate 25 minit:

| Skrin | Diukur | Punca |
|---|---|---|
| `/kelulusan` | **0 baris** | `admin_masjid` TIADA `approvals.decide` |
| `/minit-saya` | 1 baris, **0 aksi baris** | admin masjid PENGHANTAR minit demo, bukan penerima tindakan |

Kedua-duanya akan gagal dengan mesej gate yang berlainan sepenuhnya — tepat seperti W4, di
mana empat mesej berbeza menyembunyikan satu punca yang sama.

### 2. ⭐ `/kelulusan` ialah penemuan KANDUNGAN, bukan jurang data

`tenant.kelulusan` menyenaraikan **kesemua lapan peranan**, tetapi hanya `pengerusi` dan
`nazir` memegang `approvals.decide` (`config/roles.php:57-80`). `ApprovalService::request()`
akan **menolak** mana-mana pelulus lain, jadi tiada benih yang boleh membetulkannya —
skrin itu memang sentiasa kosong untuk enam daripada lapan peranan yang guide ini tawarkan.

Keseluruhan 6 langkah dijustifikasikan dan penemuan direkod sebagai `followup` F9. Ini
keluarga yang SAMA seperti penemuan W4 (setiausaha "Semak status permohonan" pada skrin yang
sentiasa kosong untuknya) — corak yang patut diaudit menyeluruh: **peranan yang guide tawarkan
mesti peranan yang benar-benar boleh menggunakan skrin itu.**

### 3. ⭐ Enam sasaran hidup dalam DOM tanpa satu pun entri registri

`help-center` `help-search` `help-diagnosis` `help-preferences` `help-support` `help-launcher`
dirender sejak binaan asal. Ujian yatim **dua hala** hanya membandingkan katalog ↔ registri,
jadi ia buta kepada `data-help-target` dalam DOM yang tidak dirujuk sesiapa.

Lima didaftarkan (satu `active`, empat `reserved` dengan sebab). `help-launcher` sengaja
tidak: ia infrastruktur pelancar, bukan sasaran tour.

➡️ **Cadangan F8:** penjaga arah KETIGA — setiap `data-help-target` dalam sumber mesti ada
dalam registri.

### 4. 🔴 Memo statik yang tidak pernah diset semula — dan penjaga saya yang gagal DUA KALI

`MosqueActivityLogsTable` mengisytiharkan `$barisPertamaId` sejak W1 tanpa reset. Ia belum
menggigit kerana hanya satu sasaran menggunakannya; W5 menambah yang kedua.

**Penjaga pertama saya LULUS dengan regresi dipasang.** Sebabnya: memo basi menandakan baris
**KEDUA**, jadi `substr_count` kekal 1. Itu pengulangan TEPAT pelajaran W4 — *bilangan tidak
pernah dapat menangkap kecacatan kedudukan*.

**Penjaga kedua saya JUGA lulus.** Saya membandingkan kedudukan sasaran dengan teks baris
LAMA; tetapi dalam baris yang sama lajur masa mendahului lajur Aktiviti, jadi perbandingan itu
benar walaupun sasaran berada pada baris kedua. Sauh yang betul ialah teks baris **PERTAMA**.

Selepas dibetulkan: regresi dipasang → **MERAH**; dipulihkan → **HIJAU**. Dua arah, dibuktikan.

🔑 **Pelajaran:** bukan cukup menukar assertion daripada "bilangan" kepada "kedudukan" —
**sauh kedudukan itu sendiri mesti diuji terhadap regresi**, kerana sauh yang salah memberi
ujian yang kelihatan lebih kuat tetapi tidak.

### 5. Gate pusingan 1 — TIGA punca, semuanya kelas yang sudah dikenali

| Shard | Hasil | Punca |
|---|---|---|
| `screen` | 27/30 | benih: minit ketiga jadi baris pertama PENGERUSI |
| `workflow` | 13/15 | **punca yang SAMA** (`workflow.pengerusi.*`) |
| `tenant-admin-public` | **39/41** | `driveFlowGuide` menuntut sasaran tepat bagi langkah GENERIK |
| agregator | merah palsu | glob skrip gate TEMPATAN menunjuk direktori kosong |

**Punca A — benih menggeser baris pertama seseorang.** Saya menambah minit
(pengerusi → admin masjid) supaya `/minit-saya` admin masjid mempunyai aksi baris. Ia menjadi
baris PERTAMA untuk pengerusi, dan pengerusi ialah PENGHANTARnya — jadi baris itu tiada
butang, `baris1()` menguncinya, dan `minit-reply` tidak pernah wujud. **Kelas defect yang
SAMA yang blok benih itu cuba elak, cuma untuk peranan LAIN.**

Pembaikan tidak menambah baris langsung: admin masjid dijadikan penerima TINDAKAN pada minit
setiausaha yang SEDIA ADA, dan minit lain ditarik ke belakang supaya susunan deterministik
pada SQLite dan PostgreSQL. Kedua-dua admin masjid dan pengerusi penerima tindakan padanya.
Disahkan dengan RENDER sebenar sebagai pengerusi sebelum gate dijalankan semula:
`[minit-record minit-status minit-complete minit-reply]`.

🔑 **Peraturan yang keluar daripadanya:** sebelum menambah baris demo, tanya *baris siapa yang
akan tergeser?* — bukan sekadar *siapa yang mendapat baris baharu?*

**Punca B — `driveFlowGuide` menuntut sasaran tepat bagi langkah generik.**
`assertStepPopover` sudah lama menghadkan G2 kepada langkah `specific`; `driveFlowGuide`
tidak, kerana sehingga W5 tiada guide berlangkah-generik pernah mengambil laluan ALIRAN —
pemandu dipilih daripada `state` registri, dan hanya guide bersasar penuh yang layak.
W5 memberi `tenant.pembetulan-rekod` dua sasaran BARIS, jadi `needsFlow()` menjadi benar
sedangkan langkah 1–3 kekal generik dengan justifikasi bertulis. Jejak gate
(`1:page-content → 2:page-content` sedangkan langkah 2 mengisytihar `page-primary`) menunjukkan
produk betul dan pengamat terlalu ketat. Kedua-dua pemandu kini menguatkuasakan peraturan
yang sama.

**Punca C — glob agregator skrip gate tempatan salah.** Payload ditulis ke
`storage/app/plan-f6/shard-<shard>.json`, tetapi skrip menunjuk direktori artifact reporter
yang KOSONG. Ia melaporkan "missing shard" untuk ketiga-tiganya tanpa mengira keputusan
sebenar — merah palsu yang menyembunyikan keputusan yang betul. Payload lama kini dibuang
sebelum setiap larian supaya shard yang crash tidak meninggalkan fail lapuk yang kelihatan
segar (gagal-terbuka).

⚠️ **CI TIDAK terjejas dan tidak diubah.** `ci.yml:516-517` MEMUAT TURUN artifact shard ke
`storage/app/plan-f6/artifacts/guidance-shard-*/` sebelum memanggil agregator, jadi glob
CI memang betul. Skrip tempatan menyalin glob itu tanpa langkah muat turun — kesilapan
perkakas tempatan sahaja. Mengapa larian tempatan W4 tidak mendedahkannya TIDAK diukur
dan sengaja tidak dispekulasi di sini; yang pasti ialah gate CI — check wajib yang
membenarkan deploy — sentiasa membaca payload yang betul.

🔑 **Corak yang berulang untuk kali KETIGA merentas W3–W5:** setiap tempat dalam harness yang
menyimpulkan sesuatu daripada "langkah ini generik" pecah apabila gelombang menjadikan
sebahagian langkah spesifik. W4 menutup `status === 'specific'` dan `route === null`;
W5 menemui yang ketiga — `needsFlow()` menukar PEMANDU, dan pemandu baharu itu membawa
andaian yang berbeza tentang langkah generik.

### 6. Kekangan reka bentuk yang menentukan seluruh gelombang

Kesemua 144 langkah `wait_for_user: false` → CTA "Seterusnya" → `driveFlowGuide` tidak pernah
mencapai cabang yang melakukan tindakan. Maka **sasaran hanya boleh dinaikkan jika ia kelihatan
dalam keadaan LALAI halaman**. Itu bukan kelonggaran; ia sebab 46 langkah dijustifikasikan
dan bukan dinaikkan secara palsu kepada sasaran yang tidak akan pernah dirender.

### 7. Kesilapan saya dalam inventori sendiri (dibetulkan sebelum kod)

Versi pertama `INVENTORI-W5.md` **tersilap kira** 16 langkah: ia menyenaraikan 7 route
`/admin/*` sedangkan ukuran memberi 8 route 3-langkah + 8 guide 2-langkah yang tidak pernah
disebut. Puncanya jadual ringkasan ditaip tangan, bukan dijana daripada output skrip.
➡️ Peraturan W6: **jadual inventori mesti dijana**.

### 8. `@if` dalam kedudukan atribut tag komponen Blade tidak dikompil

`<x-filament::button @if (...) data-help-target="x" @endif>` memberi
`syntax error, unexpected token "endif"` dan **500 pada halaman** — ditangkap oleh ukuran
render, bukan oleh mana-mana ujian sedia ada. Guna pembalut HTML biasa.

### 9. Penemuan operasi semasa menyemak prasyarat Deploy 11 (BUKAN skop W5)

Checkout produksi `/opt/diwan` mempunyai empat laluan tidak bersih — semuanya **untracked**
dan milik `ubuntu` (pembaikan keizinan Deploy 8 bertahan), jadi ia TIDAK menyekat deploy:

```
?? .env.bak.1784338951            ?? .env.bak.pre-clamav-f2fcc75
?? .env.bak.pre-guidance-142365c  ?? docker-compose.override.yml
```

⚠️ **`.gitignore` menyenaraikan `.env`, `.env.backup`, `.env.production` — tetapi BUKAN
`.env.bak*`.** Jadi ketiga-tiga sandaran env itu untracked namun **tidak diabaikan**: satu
`git add -A` di pelayan akan meng-commit rahsia produksi. Kandungannya TIDAK dibaca.

Risiko semasa RENDAH (skrip deploy guna `git fetch` + `merge --ff-only`, tidak pernah
`git add`), jadi ia direkod sebagai item **F10 housekeeping** dan bukan diperluas ke dalam W5:
satu baris `.gitignore` + pemilik memindahkan/memadam sandaran itu.

Cakera pelayan **61%** (12G lapang) — prune TIDAK diperlukan untuk Deploy 11
(build cache 4.39GB, 2.73GB boleh dituntut jika perlu kemudian).

### 10. PENGESAHAN VISUAL — dua sahih, satu ketidakpadanan MAKNA direkod

Dijalankan pada pelayan tempatan dengan benih demo segar, deep-link `?panduan=&langkah=`
(kaedah W4). Gate hanya membuktikan sasaran DISOROT; hanya mata boleh menilai sama ada
sorotan itu BERMAKNA.

| Semakan | Keputusan |
|---|---|
| `tenant.dashboard#2` → `dashboard-stats` | ✔ menyorot kad "Ringkasan Pejabat" — tepat "kad statistik" |
| `tenant.dashboard#3` → `dashboard-checklist` | ✔ menyorot "Senarai Semak Persediaan (3/6)"; tajuk BM baharu kini SEPADAN teks tour |
| `admin.mosques#2` → `platform-mosques` | ⚠️ berfungsi, tetapi sorotan jatuh pada kotak **Carian** sedangkan ayatnya berbunyi "Semak, lulus, gantung atau pulihkan tenant" — iaitu tindakan BARIS |

⚠️ **Ketidakpadanan makna, bukan kegagalan.** Elemen yang disorot NYATA dan kelihatan
(berbeza daripada defect `<th>` kosong W4), dan tindakan baris berada dalam pandangan yang
sama — jadi pengguna tidak tersesat. Tetapi sasaran yang paling setia kepada ayat itu ialah
sel tindakan baris pertama, bukan medan carian. Corak yang sama terpakai pada
`admin.users#2` ("Urus akaun global…") dan `admin.storage-orders#2` ("Sahkan pesanan…").

**Tidak dibaiki dalam W5 — sebab dinyatakan, bukan diam-diam dilangkau:** menukarnya bermakna
sasaran baris baharu + satu pusingan gate 40 minit + satu pusingan CI, untuk menaikkan
sorotan yang sudah sah kepada yang lebih tepat. `/admin/storage-orders` pula mempunyai **0
baris** dalam benih, jadi ia tetap memerlukan sauh peringkat-halaman. Direkod sebagai
penghalusan **F7/W6**, dalam keluarga yang sama seperti penemuan kandungan §2:
**adakah sasaran menjawab AYAT langkah itu, bukan sekadar berada pada halaman yang betul?**

### 11. CI pusingan 1 — KECACATAN PRODUK sebenar yang gate 3 shard tidak boleh lihat

CI merah pada SATU ujian: `guidance.spec.js:612` wizard klasifikasi. Gate `guidance-e2e-gate`
merah hanya kerana shard bergantung pada job itu dan DILANGKAU — bukan kegagalan gate.

**Punca (diukur pada pelayar, bukan disimpulkan):** `guardAutomaticGuideFromDialogs`
(`help.js:400`) memusnahkan tour AUTOMATIK apabila modal dibuka — tetapi hanya jika langkah
semasa bersasar GENERIK:

```js
if (!modal || !current || !GENERIC_TARGETS.has(current.target)) return;
```

Pengawal itu hanya dipasang untuk guide automatik (`if (!explicit)`, help.js:854), jadi syarat
`GENERIC_TARGETS` tidak pernah menjawab soalan "adakah ini guide automatik" — ia PROKSI yang
kebetulan benar selagi guide halaman bersasar `page-content`. W5 menjadikan
`tenant.peti-masuk#1` spesifik (`inbox-record`) dan pengawal berhenti berfungsi.

Diukur dalam Chrome pada `/peti-masuk`: tour auto-mula, modal dibuka, `diminimize:false`,
popover masih ada, `document.activeElement = driver-popover-close-btn`. CI melihat gejala
yang lain — popover memintas klik pada butang "Seterusnya" modal.

⚠️ **Ini KECACATAN PRODUK, bukan isu ujian.** `disableAutomaticGuides()` dalam harness hanya
berkesan untuk panel AWAM (`help.js:889` menyemak `diwan-help-seen:` hanya bila
`isPublic`), jadi guide tenant SENTIASA auto-mula. Tanpa pembaikan ini, setiap pengguna yang
membuka Peti Masuk akan mendapat popover tour di atas modal klasifikasi — butang tidak boleh
ditekan dan fokus terperangkap.

**Pembaikan:** predikat digantikan dengan yang menjawab soalan sebenar — *adakah sorotan tour
berada DI LUAR modal?* Niat asal dikekalkan (langkah yang sasarannya memang di dalam modal
tidak memusnahkan tour). `.driver-active-element` dibaca dan bukan `resolveStepElement()`,
kerana memanggil `decorateTargets()` dari dalam MutationObserver ialah punca ribut mutasi F5c.

🔑 **KALI KEEMPAT corak "proksi langkah generik" muncul** (W3 → W4 ×2 → gate W5 → di sini).
Senarai proksi yang sudah ditutup: `status === 'specific'` · `route === null` ·
`needsFlow()` menukar pemandu · `GENERIC_TARGETS.has(step.target)` menentukan pengawal modal.
**Cadangan F8: cari setiap rujukan `GENERIC_TARGETS` dan tanya "adakah ini benar-benar
soalan yang hendak dijawab?"**

### 12. Dua kesilapan saya semasa mendiagnosisnya — direkod

**(a) Eksperimen "penentu" pertama saya menguji perkara yang salah.** Saya memulihkan katalog
lama dan menjalankan ujian: ia GAGAL juga, jadi saya hampir menyimpulkan W5 tidak bersalah.
Tetapi ia gagal pada assertion yang BERBEZA (fokus, baris 569) daripada CI (klik dipintas,
baris 582) — reproduksi tempatan saya tidak setia. **Bandingkan TITIK kegagalan, bukan hanya
lulus/gagal, sebelum mempercayai eksperimen pemulihan.**

**(b) Penjaga sempit yang saya tulis kemudian adalah cacat dan DIBUANG.** Ia mengklik
"Klasifikasikan" melalui koordinat semasa tour aktif — yang overlay Driver.js memang serap
(`overlayClickBehavior: 'close'`, pelajaran F0/W1 yang sudah direkod dan saya langgar semula).
Ujian `wizard klasifikasi` sedia ada sudah menjadi penjaga DUA ARAH yang terbukti: merah
sebelum pembaikan, hijau selepasnya. Penjaga yang tidak setia kepada laluan pengguna lebih
buruk daripada tiada penjaga.

**Ralat Alpine `textareaFormComponent is not defined` DIKECUALIKAN daripada punca W5** melalui
ukuran: katalog LAMA + pembaikan menghasilkan ralat yang sama, tetapi pada viewport yang
BERBEZA (1440px, bukan 390px). Viewport berbeza setiap larian ⇒ perlumbaan pemuatan aset pada
pelayan dev satu-benang Windows, bukan kecacatan produk. Tiga baki kegagalan `ci-guidance`
tempatan semuanya `net::ERR_ABORTED` / tamat masa — had persekitaran yang sama dan sudah
didokumen (`PHP_CLI_SERVER_WORKERS` tidak disokong pada Windows).

### 13. CI attempt 2 — dua pembaikan DISAHKAN, satu akibat ketiga ditutup

```
9.  Validate, audit and format ... success   (fix league/commonmark berkesan)
13. Migrate PostgreSQL + suite .. success
18. Unit fungsi tulen ........... success
20. Guidance smoke .............. success   (fix pengawal modal DISAHKAN CI)
21. Domain flows ................ FAILURE   → ditangani di bawah
```

**Akibat KETIGA W5: tour AUTO melumpuhkan halaman.** Vendor `driver.css` menetapkan
`.driver-active * { pointer-events: none }` dan hanya mengecualikan elemen DISOROT beserta
anaknya. Selagi guide halaman bersasar `page-content` (`<main>`), pengecualian itu meliputi
seluruh kandungan; W5 menjadikan sasaran KECIL, jadi seluruh halaman mati.

Diukur: `getComputedStyle(butangCari).pointerEvents === 'none'` dan
`document.elementFromPoint()` pada butang memulangkan `BODY`. `overlayClickBehavior: 'close'`
tidak menyelamatkan kerana overlay sendiri `pointer-events: none`.

**Pembaikan (dua lelaran, kedua-duanya diukur):**
1. ❌ `.diwan-tour-auto.driver-active * { pointer-events: auto; }` — TERLALU LUAS. Ia memaksa
   bekas notifikasi Filament (`div.fi-no`, fixed, z-50) menjadi `auto`, dan bekas itu
   kemudiannya menutupi halaman. Menukar satu penyekat dengan penyekat lain.
2. ✅ Skop kepada `main` sahaja — memulihkan TEPAT keadaan pra-W5 (dahulu `<main>` yang
   disorot, jadi vendor memberi seluruh kandungan `auto`). Segala di luar `main` kekal
   mengikut vendor. Kelas `diwan-tour-auto` dipasang/dibuang BERPASANGAN dengan
   `guardAutomaticGuideFromDialogs`, jadi tour EKSPLISIT (termasuk setiap guide gate) tidak
   pernah terjejas.

**Harness:** `ddms-extended.spec.js` sudah cuba mematikan tour melalui `localStorage`, tetapi
`help.js:889` hanya menghormati `diwan-help-seen:` untuk panel AWAM — helper itu kod mati yang
kelihatan berfungsi. Diganti dengan penutupan tour sebenar (laluan pengguna), dan helper mesti
MENUNGGU kerana tour auto-mula selepas `setTimeout(450)` — versi pertama helper menjadi no-op
atas sebab itu.

Selepas pembaikan: `ci-domain` **4/4 lulus** apabila dijalankan berasingan; `ddms-extended`
tidak lagi gagal.

### 14. ⚠️ TIGA kesilapan metodologi saya dalam siasatan ini

**(a) Instrumen ukur tidak disahkan.** Saya melaporkan "dua klik SEBENAR pada butang Cari
tidak berbuat apa-apa" sebagai bukti pengguna tersekat. Eksperimen kawalan kemudian
menunjukkan klik koordinat MCP **tidak sampai ke halaman langsung** — klik pada butang
popover pun tidak memajukan tour. Kesimpulan (pengguna tersekat) kekal SAH kerana
`pointer-events: none` + `elementFromPoint → BODY`, tetapi bukti yang saya petik sebahagiannya
tidak sah. **Sahkan instrumen sebelum mempercayai keputusannya** — pelajaran yang sudah ada
dalam memori dan saya langgar.

**(b) Eksperimen pemulihan katalog hampir menyesatkan.** Katalog lama juga gagal, jadi saya
hampir menyimpulkan W5 tidak bersalah — tetapi ia gagal pada assertion BERBEZA daripada CI.
**Bandingkan TITIK kegagalan, bukan hanya lulus/gagal.**

**(c) Penjaga sempit yang cacat, dibuang.** Ia mengklik koordinat semasa tour aktif — yang
overlay memang serap (pelajaran F0/W1 yang saya langgar semula).

### 15. Keadaan ujian tempatan ketika sesi ditutup

`ci-domain` 4/4 (berasingan) · `ci-guidance` 30/35 · unit 26/26 · pint lulus · Pest 601✓/1 skip.

Kelima-lima kegagalan `ci-guidance` ialah tandatangan persekitaran yang sudah didokumen:
3× `net::ERR_ABORTED` / `waitForURL` tamat masa (pelayan dev satu-benang Windows;
`PHP_CLI_SERVER_WORKERS` tidak disokong) dan 2× `textareaFormComponent is not defined`
(perlumbaan pemuatan aset — dibuktikan bebas daripada W5 kerana katalog LAMA menghasilkannya
pada viewport BERBEZA). ⚠️ Ini **konsisten dengan** had persekitaran, bukan dibuktikan
satu-persatu; CI Linux ialah pengesah muktamad.

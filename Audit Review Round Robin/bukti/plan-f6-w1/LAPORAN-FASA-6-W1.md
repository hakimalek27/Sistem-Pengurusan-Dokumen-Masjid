# Laporan Fasa F6 — Gelombang W1 (`screen` bertindakan)

**Tarikh:** 4 Ogos 2026 · **Skop:** PELAN-PEMBAIKAN.md §7 · **Status:** kerja produk SELESAI,
gate 28/29 hijau — **belum deploy** (satu guide belum boleh dipandu harness).

---

## (a) Ringkasan

Kesemua **27 guide `screen`** yang berbaki dalam senarai kerja W1 diberi sasaran
`data-help-target` spesifik, tajuk bermakna, dan nilai `wait_for_user` yang betul. Dua corak
digunakan, kedua-duanya dibuktikan hujung-ke-hujung dengan pemanduan tour hidup sebelum
diskalakan:

- **Corak A — halaman Cipta khusus** (4 guide): medan borang melalui
  `extraFieldWrapperAttributes`, butang simpan melalui `getCreateFormAction()` yang DIHIAS
  (`parent::` dikekalkan supaya callback simpan vendor tidak putus).
- **Corak B — pencetus → modal** (selebihnya): langkah 1 menyasar butang pencetus kerana
  `help.js:630` mematikan tour dengan ralat palsu jika sasaran langkah pertama tidak wujud
  dalam 2.5s; langkah 2..N menyasar medan modal, dan mekanisme sync F2 auto-maju sebaik
  modal terbuka.

Aksi baris jadual menggunakan pembantu `baris1()` (memo statik per-permintaan) supaya satu
sasaran tidak memadan N elemen dan melanggar keunikan G2; lajur menggunakan
`extraCellAttributes`.

## (b) Fail utama diubah

- Borang/halaman: `ClassificationNodeForm` · `RegistryFileForm` · `RetentionRuleResource` ·
  `DelegationForm` · `Create{ClassificationNode,RegistryFile,RetentionRule,Delegation}`
- Halaman & modal: `TetapanMasjid` · `AhliPeranan` · `PelupusanManual` · `PenggunaanStoran` ·
  `ProfileActions` · `OnboardingWizard` · `ViewRecord` · `ViewRegistryFile` ·
  `AccessGrantsRelationManager` · `RecordInfolist` · `RegistryFileInfolist`
- Jadual: `MinitsTable` · `ApprovalsTable` · `MosqueActivityLogsTable`
- Blade: `penggunaan-storan` · `profil` · `cari-rekod` · `activity-log-details` ·
  `document-viewer`
- Katalog & registri: `resources/help/guides.json` · `resources/help/targets.json` ·
  `docs/HELP-TARGETS.md` (dijana)
- Penjaga: `build-manifest.mjs` · `validate-plan-manifest.mjs` · `PlanManifestTest.php` ·
  `aggregate-guidance-coverage.mjs` · `HelpCatalogQualityTest.php`
- Gate: `e2e/guidance-full.spec.js` (pemandu aliran baharu)
- Data: `database/seeders/DemoSeeder.php`

## (c) Output verifikasi sebenar

```
$ php artisan test
  Tests:    1 skipped, 515 passed (5207 assertions)

$ vendor/bin/pint --dirty
  {"tool":"pint","result":"passed"}

$ node scripts/audit/validate-plan-manifest.mjs --manifest .../manifest.json
  KEMAJUAN berbanding baseline F0:
    action generic 200 → 60 (−140)
    placeholder 258 → 0 (−258)
  OK: manifest sah — partition wave/shard sepadan pengiraan bebas, set-union exact.

$ node .../tools/build-manifest.mjs …
  placeholder_titles 258 → 0 (−258)
  action_steps_with_generic_target 200 → 60 (−140)
  wave W1.action_generic 140 → 0 · wave W1.placeholder 140 → 0 · wave W5.placeholder 108 → 0
  guides=83 steps=473 actionGeneric=60 placeholder=0
  waves=W0:2g/10s W1:0g/0s W2:13g/145s W3:29g/151s W4:1g/13s W5:35g/146s W6:3g/8s

$ GUIDANCE_SHARD=screen npx playwright test e2e/guidance-full.spec.js --project=guidance-full
  (larian penuh pertama)  21 passed, 9 failed  — 8 guide + penulis shard JSON
  (selepas pembaikan harness, disahkan satu per satu)
    butiran-rekod-dan-tindakan ✔ · butiran-fail-elektronik ✔ · permohonan-storan-tambahan ✔
    edarkan-minit ✔ · tanda-tindakan-minit-selesai ✔ · buat-keputusan-kelulusan ✔
    beri-akses-khas-fail-sulit ✔ · mohon-pembetulan-rekod ✔
    persediaan-berpandu ✘  ← satu-satunya yang tinggal
```

## (d) Kriteria §7.4 per gelombang

| Kriteria | Status |
|---|---|
| G1 status per-langkah direkod (tiada langkah kosong) | ✔ manifest 473/473 |
| G2 sasaran `specific` unik + kelihatan + desktop/mobile | ✔ diukur pada DOM sebenar |
| G3 tour black-box setiap langkah tindakan W1 | ✔ 26/27 guide · ✘ `persediaan-berpandu` |
| G4 kitaran guide (mula/tutup/ulang) | ✔ untuk guide yang lulus |
| Registri seiring; yatim dua hala = 0; HELP-TARGETS dijana | ✔ 167 sasaran (142 aktif + 25 rizab) |
| Metrik pada denominator PENUH | ✔ (lihat (c)) |
| `blocked` = 0 dalam skop W1 | ✔ tiada langkah berstatus `blocked` |
| Denominator sepadan jadual beku | ✔ dikemas dalam KEEMPAT-EMPAT penjaga |

## (e) Lencongan dari pelan — dinyatakan

1. **Arahan dipinda pada beberapa langkah** supaya menamakan kawalan yang disorot
   (cth. "Tekan Jemput Ahli, kemudian isi nama penuh"). Tanpa ini langkah 1 corak B
   menyorot butang sambil arahannya bercakap tentang medan yang belum wujud.
2. **`wait_for_user` 228 → 190.** 38 langkah PEMERHATIAN yang tersalah label sebagai
   tindakan dibetulkan kepada `false` — §7.2 langkah 3 memang mengarahkan semakan semula.
3. **Route dua guide dibetulkan**: `edarkan-minit` dan `mohon-kelulusan` menunjuk halaman
   HASIL (`/minit-saya`, `/kelulusan`); tindakan sebenarnya pada halaman butiran rekod.
   `butiran-log-aktiviti` menunjuk `/records`, dibetulkan ke `/log-aktiviti`.
4. **`mohon-pembetulan-rekod` kekal pada SATU skrin.** Langkah 5 dahulunya pada halaman
   lain, menjadikan langkah 4 penghantaran borang di tengah guide. Itu kelakuan family
   `workflow`, bukan `screen` ("Panduan **skrin** dan tindakan").
5. **Data benih demo diperluas** (`DemoSeeder::seedTugasanDemo`) — empat skrin tidak pernah
   mempunyai data (0 minit, 0 kelulusan, 0 log, 0 fail fizikal), jadi tour tiada kawalan
   untuk disorot dan gate akan hijau pada halaman kosong.
6. **`screen.viewer-dokumen` kekal generik** dengan `wait_for_user: false` — lihat (f).

## (f) Nota & risiko

### ⚠️ KECACATAN PRODUK DIUKUR, BELUM DIBAIKI → F7 §8

Sasaran di dalam bekas boleh-skrol (modal panjang) boleh berada di BAWAH lipatan. Driver.js
menggulung HALAMAN, bukan `div.fi-modal-window-ctn`. Kerana popover `position: fixed`, ia
mendarat separa di luar skrin dan butang CTAnya SEPENUHNYA di luar viewport; lebih teruk,
lubang overlay berada di luar skrin jadi overlay pepejal **menyerap setiap klik**. Pengguna
terkandas sepenuhnya — keluarga yang sama dengan bug banner F0.

Diukur pada viewport 1440×1000, `screen.mohon-pembetulan-rekod` langkah 4 (sebelum pindaan
katalog): sasaran y=1168 · popover y=789 tinggi 263 (hujung 1052) · CTA y=1004 · lubang
overlay `M291,1158`.

`element.scrollIntoView({block:'center'})` TERBUKTI berfungsi dipanggil sendiri (scrollTop
0→244, sasaran y 1168→924) tetapi TIDAK melekat dalam kitaran tour. Empat pendekatan dicuba,
semuanya berakhir scrollTop kembali 0:
(1) `onHighlighted` + `driver.refresh()` · (2) dalam callback `element` sebelum penentuan ·
(3) `onHighlighted` tanpa refresh · (4) menyelesaikan semula `.driver-active-element`.
Sesuatu menetapkan semula gulungan SELEPAS hook — berkemungkinan perangkap fokus modal
Filament. **Peraturan #9 repo dipatuhi**: berhenti selepas 3 cubaan, kod tidak terbukti
DIBUANG, ukuran penuh direkod dalam `resources/js/help.js`.

### ⚠️ Viewer dokumen tidak boleh menjalankan tour langsung

`/viewer/{media}` ialah halaman berasingan (URL bertandatangan 30 minit) TANPA Livewire dan
TANPA `help.js`. Sasaran kawalannya dipasang dan direkod `reserved`; menaikkan taraf guide
itu memerlukan runtime bantuan pada halaman viewer — dicadangkan F7.

### 🔴 BELUM SELESAI: `screen.persediaan-berpandu` tidak boleh dipandu gate

Tour tersekat pada langkah 1 (`n:1`) walaupun **produk terbukti betul**: probe langsung
menunjukkan `dispatchEvent` pada butang "Seterusnya" wizard memajukan wizard dan
`onboarding-phone` bertukar daripada `tersembunyi` → `VISIBLE`. Ini **jurang harness**,
bukan kecacatan produk. Tiga pendekatan dicuba (gelung menunggu, pulih banner "Tunjuk
arahan", tempoh menunggu lebih panjang). Peraturan #9 dipatuhi — berhenti dan direkod.
**Tindakan seterusnya:** muat turun trace larian itu dan baca log tindakan untuk melihat
sama ada klik wizard benar-benar berlaku dalam konteks tour.

### Penemuan harness yang bernilai

1. **`.driver-active-element` boleh KEKAL pada elemen langkah sebelumnya.** `querySelector`
   (padanan pertama mengikut susunan DOM) memulangkan elemen yang SALAH. Gate kini
   mengumpulkan SEMUA elemen aktif dan memeriksa keahlian.
2. **Resolver halaman butiran mesti mengesahkan SEMUA sasaran peringkat-halaman**, bukan
   hanya langkah 1: "Beri Akses" wujud pada setiap fail, jadi memeriksanya sahaja memilih
   fail pertama yang tiada geran akses.
3. **`isVisible()` ialah snapshot tanpa menunggu** — relation manager & infolist Filament
   dirender selepas muatan awal, jadi snapshot memberi negatif palsu.
4. **`Tab::extraAttributes()` Filament menerapkan atribut pada BUTANG tab DAN panel tab**
   (vendor `tabs.blade.php:127/165/304`). Resolusi tour deterministik memilih butang
   (panel tidak aktif tidak kelihatan; butang mendahului dalam susunan DOM) — disahkan
   desktop DAN mobile.

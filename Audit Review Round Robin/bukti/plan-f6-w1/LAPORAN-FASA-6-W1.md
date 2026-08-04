# Laporan Fasa F6 — Gelombang W1 (`screen` bertindakan)

**Tarikh:** 4 Ogos 2026 · **Skop:** PELAN-PEMBAIKAN.md §7 · **Status:** kerja produk SELESAI,
**gate tiga shard HIJAU PENUH**.

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
  `help.js` mematikan tour dengan ralat palsu jika sasaran langkah pertama tidak wujud
  dalam 2.5s; langkah 2..N menyasar medan modal, dan mekanisme sync F2 auto-maju sebaik
  modal terbuka.

Aksi baris jadual menggunakan pembantu `baris1()` (memo statik per-permintaan) supaya satu
sasaran tidak memadan N elemen dan melanggar keunikan G2; lajur menggunakan
`extraCellAttributes`.

**Penutupan gate (sesi 4 Ogos petang):** larian shard PENUH yang pertama mendedahkan **dua
kecacatan harness** yang tidak boleh dilihat semasa guide disahkan satu-per-satu. Kedua-duanya
kini terbukti, dibaiki, dan ketiga-tiga shard hijau (§(c)).

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
- Gate: `e2e/guidance-full.spec.js` (pemandu aliran + dua pembaikan penutupan gate)
- Data: `database/seeders/DemoSeeder.php`

## (c) Output verifikasi sebenar

### Gate tiga shard (benih segar sebelum setiap shard)

```
$ GUIDANCE_SHARD=screen npx playwright test e2e/guidance-full.spec.js --project=guidance-full
  30 passed (13.2m)          EXIT=0
  shard-screen.json  → 29 guide / 151 langkah / 111 tindakan · blocked 0 · failures 0 · complete true

$ GUIDANCE_SHARD=workflow …
  15 passed (10.9m)          EXIT_WORKFLOW=0
  shard-workflow.json → 14 guide / 158 langkah / 75 tindakan · blocked 0 · failures 0 · complete true

$ GUIDANCE_SHARD=tenant-admin-public …
  41 passed (17.5m)          EXIT_TAP2=0
  shard-tenant-admin-public.json → 40 guide / 164 langkah / 4 tindakan · blocked 0 ·
                                   failures 0 · complete true
  (larian pertama 39/41: `tenant.records` — guide W5, TIDAK disentuh W1 — tumbang pada stall
   pelayan tempatan; punca dikenal pasti & dibaiki, lihat §(f) nota 6. Larian bersih = 41/41,
   dengan SIFAR fatal `Maximum execution time` dalam log pelayan sepanjang 17.5 minit.)
```

Jangkaan manifest per shard: `screen` 29/151/111 · `workflow` 14/158/75 ·
`tenant-admin-public` 40/164/4. Jumlah **83 / 473 / 190**.

### Agregator gate (perbandingan SET, bukan kiraan)

```
$ node scripts/audit/aggregate-guidance-coverage.mjs \
    --manifest "Audit Review Round Robin/bukti/plan-baseline/manifest.json" \
    --shards "storage/app/plan-f6/shard-*.json" \
    --out storage/app/plan-f6/coverage-gate.json

GATE LULUS: 83 guide · 473 langkah · 190 langkah tindakan — union tiga shard sepadan
manifest (set, bukan kiraan). Laporan: storage/app/plan-f6/coverage-gate.json
EXIT_AGREGATOR=0
```

### Suite, lint, build

```
$ vendor/bin/pint --dirty      → {"tool":"pint","result":"passed"}
$ php artisan test             → Tests: 1 skipped, 515 passed (5207 assertions)   EXIT=0
$ npm run build                → ✓ built in 11.30s                                 EXIT=0
     assets/help-BFhO_EWt.js   (produksi kini help-Da8KtLOe.js → BERUBAH)
     assets/help-CrH0eDM1.css  (TIDAK berubah — hanya help.js disentuh)
```

### Metrik katalog penuh

```
$ node scripts/audit/validate-plan-manifest.mjs --manifest .../manifest.json
  KEMAJUAN berbanding baseline F0:
    action generic 200 → 60 (−140)
    placeholder 258 → 0 (−258)
  OK: manifest sah — partition wave/shard sepadan pengiraan bebas, set-union exact.

$ node .../tools/build-manifest.mjs …
  guides=83 steps=473 actionGeneric=60 placeholder=0
  waves=W0:2g/10s W1:0g/0s W2:13g/145s W3:29g/151s W4:1g/13s W5:35g/146s W6:3g/8s
```

## (d) Kriteria §7.4 per gelombang

| Kriteria | Status |
|---|---|
| G1 status per-langkah direkod (tiada langkah kosong) | ✔ manifest 473/473 |
| G2 sasaran `specific` unik + kelihatan + desktop/mobile | ✔ diukur pada DOM sebenar |
| G3 tour black-box setiap langkah tindakan W1 | ✔ 29/29 guide shard `screen` |
| G4 kitaran guide (mula/tutup/ulang) | ✔ termasuk dalam setiap ujian guide |
| Registri seiring; yatim dua hala = 0; HELP-TARGETS dijana | ✔ 167 sasaran (142 aktif + 25 rizab) |
| Metrik pada denominator PENUH | ✔ (lihat (c)) |
| `blocked` = 0 dalam skop W1 | ✔ 0 dalam ketiga-tiga shard JSON |
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

### ✅ DUA KECACATAN HARNESS — punca diukur, bukan diteka

Larian shard **penuh** yang pertama memberi 8 kegagalan walaupun setiap guide berkenaan
LULUS apabila dijalankan satu-per-satu dalam sesi sebelumnya. Pengesahan satu-per-satu itulah
yang menyembunyikan kedua-dua kecacatan.

**1. `filter({ hasText: <RegExp> })` menguji regex terhadap teks MENTAH.**
Whitespace **tidak** dinormalisasi (berbeza daripada `hasText: <string>` dan daripada nama
boleh-akses). Butang Filament dirender Blade dengan baris baharu + indentasi di sekeliling
labelnya, jadi `/^Seterusnya$/` memberi **count=0** sedangkan butang itu jelas di skrin.
Diukur dua kali — pada DOM aplikasi sebenar DAN pada DOM sintetik minimum:

| Bentuk locator | count |
|---|---|
| `hasText: /^Seterusnya$/` | **0** |
| `hasText: /Seterusnya/` | 1 |
| `hasText: /^\s*Seterusnya\s*$/` | 1 |
| `hasText: 'Seterusnya'` | 1 |
| `getByRole(name, { exact: true })` | 1 |

Kegagalannya **senyap**: locator tak-padan hanya memulangkan `isVisible()=false`, jadi gelung
retry berpusing sehingga tamat masa tanpa satu pun ralat — nampak seperti "produk tidak maju"
sedangkan produk tidak pernah disentuh. Trace mengesahkan `dispatchEvent` pada butang wizard
**tidak pernah berlaku** dan setiap lelaran mengambil cabang 1000ms.
**Pembaikan:** `modal.getByRole('button', { name: 'Seterusnya', exact: true })` — bentuk yang
SUDAH digunakan tiga tempat lain dalam fail yang sama (baris 773/778/783).

**2. `dispatchEvent` sebagai sandaran `.catch()` = bom masa 30 saat.**
`await x.click({timeout: 5_000}).catch(() => x.dispatchEvent('click'))` nampak selamat, tetapi
`dispatchEvent` tiada tempoh sendiri → mewarisi `actionTimeout` 30s dan **MELEMPAR** apabila
elemen tidak akan kembali. Diukur: banner "Panduan menunggu" lenyap kerana tour **berjaya**
maju sendiri (`clearWaitingBanner()`), klik gagal pada 5.1s, `dispatchEvent` melempar 30s
kemudian → guide yang laluan penggunanya BETUL dilaporkan GAGAL. Ini perlumbaan
**check-then-act**; `isVisible()` hanyalah snapshot.
**Pembaikan:** tempoh pendek pada kedua-dua percubaan + ditelan, kerana **ketiadaan banner
ialah kejayaan**. Satu pembaikan ini menyelesaikan **kesemua tujuh** kegagalan baki.

⚠️ **Ranjau sama yang DIBIARKAN sengaja:** `guidance-full.spec.js:303`
(`cta.click(...).catch(() => cta.dispatchEvent('click'))`) mempunyai bentuk tanpa-tempoh yang
sama. Ia TIDAK diubah kerana gate kini hijau dan disiplin repo melarang menyentuh kod hijau
tanpa bukti kegagalan. Jika ia pernah menyala, pembaikannya satu baris — sama seperti di atas.
Nota kedua: `e2e/helpers/upload.js:29` masih guna regex bersauh `/^Upload complete$/`; ia lulus
kerana FilePond menetapkan teks itu melalui JS tanpa whitespace sekeliling — landasan ranjau,
bukan pepijat semasa.

### 🔴 PUSINGAN CI #1 (`2f7bbbb`, run 30906909355) MERAH — dua kecacatan harness LAGI

Ketiga-tiga shard hijau TEMPATAN, tetapi CI memerahkan shard `screen` (5 guide + penulis JSON).
`workflow` dan `tenant-admin-public` **lulus di CI**. Ini larian CI **pertama** bagi gate W1
(larian `794fc6d` sebelumnya merah kerana gate 28/29), jadi ia pendedahan pertama, bukan regresi.

**Bentuk A — wizard TERLANJUR maju.** `screen.persediaan-berpandu#4`: `n` **betul 4** tetapi
`sasaranAktif: false`. Gelung tunggu menggunakan `waitForTimeout(1500)` tetap selepas mengklik
"Seterusnya"; pada CI render wizard mengambil lebih lama, jadi lelaran berikut mengklik
"Seterusnya" **KEDUA** → wizard melangkaui langkah yang guide ini sasarkan → `onboarding-members`
hilang selama-lamanya → Driver.js menyorot fallback `page-content`.
**Pembaikan:** tunggu **KESAN** (`sasaranSeterusnya.waitFor({state:'visible'})`), bukan tempoh
tetap.

**Bentuk B — klik tindakan HILANG (4 guide).** `edit-tetapan-masjid#2` ·
`ganti-versi-rekod#2` · `pindah-rekod-ke-fail-lain#2` · `keluarkan-fail-fizikal#2`: semuanya
`n: 1` (tersekat langkah 1). Keempat-empatnya mempunyai langkah 1 = butang Action Filament dan
langkah 2 = medan di dalam modal.
**Bukti keras daripada `serve-ci.log`** (artifak diagnostik yang dipasang pada F4 — akhirnya
berguna):
```
12:12:43  /app/mam/tetapan-masjid    ← halaman dimuat
12:12:44  /livewire/update  ×2
          … 94 SAAT SIFAR PERMINTAAN …
12:14:18  /app/login                 ← ujian seterusnya
```
Klik itu menghasilkan **sifar** permintaan pelayan — tandatangan yang IDENTIK dengan flake muat
naik F5. Modal tidak pernah terbuka.
**Pembaikan:** **pulih-sendiri** — ulang `dispatchEvent` yang SAMA sehingga ada KESAN. Ulangan
dipagar oleh `state` yang DIISYTIHARKAN dalam registri (`modal:`) supaya tindakan bersifat
toggle tidak membatalkan kesannya sendiri.

### ⛔ DUA pendekatan pembaikan DIUJI dan DITOLAK (kedua-duanya memerahkan guide hijau)

Kedua-dua penolakan ini direkod kerana kedua-duanya kelihatan betul di atas kertas.

**Ditolak #1 — tukar `dispatchEvent` → klik sebenar.** Memerahkan **TIGA** guide yang sebelumnya
hijau (`jemput-ahli`, `sedia-senarai-pelupusan`, `tetapkan-kata-laluan`), setiap satu tamat masa
~1.7m. Sebabnya `resources/js/help.js:663-664` menetapkan **`overlayClickBehavior: 'close'`** →
klik berasaskan KOORDINAT yang mendarat pada overlay tour **MENUTUP TOUR**, bukan menekan butang.
Ini pelajaran F0 yang SUDAH direkod ("overlay menyerap klik koordinat → guna `dispatchEvent`") —
dibaca, dikutip dalam komen sendiri, lalu dilanggar. **Bila klik hilang, ulang event yang SAMA;
jangan tukar jenis event.** (Nuans: klik sebenar memang betul untuk penghantaran borang dalam
modal TANPA overlay tour aktif — konteks fix F5. Konteks berbeza, bukan bercanggah.)

**Ditolak #2 — ganti tidur tetap 1500ms dengan `waitFor(sasaran, 8s)`.** Memerahkan
`persediaan-berpandu` dan `permohonan-storan-tambahan` yang sebelumnya hijau. Kesannya diukur;
mekanismenya TIDAK dituntut terbukti (`waitFor` nampaknya boleh selesai atas sebab yang salah
semasa wizard masih beralih, lalu gelung memaju lagi). Kod tidak terbukti DIBUANG.

**Ditolak #3 — ulang tindakan pada SETIAP lelaran gelung.** Ulangan berlaku ~0ms selepas klik
asal, semasa modal masih dalam peralihan; mengklik pencetus Filament dua kali me-`mountAction`
dua kali atau menutup modal yang sedang dibuka. Memerahkan `edit-tetapan-masjid`.
**Dibetulkan, bukan dibuang:** beri modal ~4 saat, kemudian cuba paling banyak dua kali dalam
bajet 12 lelaran.

**Diterima #1 — hadkan kemajuan wizard kepada SATU per peralihan langkah.** Invarian ini datang
daripada struktur guide, bukan daripada masa: satu langkah guide bersamaan paling banyak satu
langkah wizard. Masa 1500/1000ms **tidak diubah** (ia sudah terbukti 30/30 tempatan), dan gelung
masih ada 12 lelaran kesabaran untuk runner perlahan.

**Diterima #2 (yang menutup punca sebenar) — G3 diassert terhadap URUTAN YANG DIREKOD.**
Gate dahulu mengundi keadaan **seketika** dan menuntut `n === i` pada saat ia membaca. Tetapi
mekanisme sync F2 memang direka untuk memajukan tour sebaik sasaran langkah berikut muncul,
jadi tour boleh melintasi satu langkah dalam beberapa milisaat. Bukti muktamad: harness menunggu
`n: 4` sedangkan tour sudah **`n: 5`** — ia menunggu 90s untuk nombor yang tidak akan kembali.
Itu **mengassert pada keadaan sementara**, iaitu kecacatan reka bentuk dalam alat, bukan masalah
masa.

Pembaikan: satu perekam dalam halaman (`addInitScript`) merakam setiap peralihan
`(n, sasaran aktif, ralatPalsu)`, dan assertion bertanya "adakah langkah *i* PERNAH berlaku
dengan sasaran yang betul". Ia kalis-perlumbaan dan **lebih kuat** daripada sebelumnya, bukan
lebih longgar. Harness juga tidak lagi menekan CTA jika tour sudah melintasi langkah itu — CTA
itu kini milik langkah lain dan menekannya memaju tour dua kali.

⚠️ **Perekam itu sendiri gagal SENYAP pada percubaan pertama.** `addInitScript` berjalan SEBELUM
mana-mana skrip halaman, jadi `document.documentElement` boleh masih `null` dan
`observe(null, …)` melempar — memusnahkan pemasangan sementara `window.__diwanTourLog = []` pada
baris pertama sudah berjaya. Akibatnya "log kosong", yang kelihatan **serupa** dengan "langkah
tidak berlaku" dan memerahkan langkah 1 dengan mesej yang mengelirukan. Dua pembetulan: pasang
observer selepas DOM sedia (interval selamat dipasang segera kerana ia hanya menyentuh DOM
apabila dipanggil), dan **laporkan `perekam sedia=` + bilangan entri + jejak dalam mesej
kegagalan** supaya dua keadaan itu tidak boleh disamakan lagi.

🔑 **Pola yang muncul daripada lima percubaan:** empat percubaan pertama melaras **cara** harness
berinteraksi (jenis event, tempoh, kekerapan) dan setiap satu memerahkan guide yang hijau. Yang
berjaya ialah dua yang mengubah **apa yang diperhatikan** atau **apa yang dianggap benar tentang
struktur**. Dalam sistem dengan koreografi yang sudah terbukti, laraskan pengamatan — bukan
interaksi.

Sifar fatal dalam `serve-ci.log` — jadi ini bukan masalah pelayan CI.

### ⚠️ KECACATAN PRODUK BAHARU DIUKUR → F7: **sorotan fallback bersifat MELEKAT**

Untuk menguji pembaikan di atas tanpa menunggu pusingan CI 25 minit, shard `screen` dijalankan
di bawah **beban CPU buatan** (teknik F0: 14 proses gelung ketat pada mesin 20-teras).
Keputusan:

- Keempat-empat guide yang gagal di CI **LULUS** → pembaikan berkesan.
- **Lima guide LAIN gagal**, semuanya dengan bentuk yang IDENTIK: `n` **betul** (tour sampai ke
  langkah yang betul) tetapi `sasaranAktif: false`. Sasarannya:
  `record-correction-submit` · `record-approval-note` · `file-checkout-submit` ·
  `file-access-submit` · `minit-reply-body` — **kesemuanya di dalam modal**.

Mekanisme: `startGuide` membina setiap langkah dengan
`element: () => resolveStepElement(step) || document.querySelector(SELECTOR('page-content'))`.
Driver.js memanggil callback itu **sekali** semasa peralihan langkah. Jika morph Livewire
menjadikan sasaran tiada pada saat itu — tetingkap yang jauh lebih lebar di bawah beban —
tour menyorot **seluruh halaman** dan **tidak pernah menyelesaikan semula**, walaupun kawalan
sebenar muncul sepersekian saat kemudian. Pengguna nampak sorotan yang tidak bermakna tanpa
jalan pulih selain memulakan panduan semula.

**Ini kelemahan PRODUK, bukan harness** — dan gate BETUL untuk menangkapnya, jadi ia sengaja
TIDAK ditutup dengan melonggarkan assertion. Pembaikan yang dicadangkan (F7 §8, sepasukan dengan
kecacatan popover-luar-viewport): jadikan fallback **tidak melekat** — dalam `onHighlighted`,
jika elemen yang disorot ialah fallback sedangkan sasaran sebenar kini wujud, sorot semula.

**Titik operasi:** kelima-lima guide ini **LULUS di CI** (kegagalan CI ialah set yang BERBEZA),
jadi beban 14-pembakar melebihi keadaan CI. Ia berguna sebagai penguji tekanan, bukan sebagai
definisi hijau. Larian bersih + CI kekal sebagai gate berkuasa.

### ⚠️ Dua hipotesis SAYA yang salah — dihapuskan oleh ukuran

1. **Pelayan hantu.** `netstat` mendedahkan **dua** proses `artisan serve` mengikat :8092
   serentak (Windows mengagihkan sambungan antara keduanya) — persis gotcha yang direkod.
   Dibunuh dahulu; kegagalan **berulang serupa** selepas itu, jadi ia BUKAN punca. Menghapuskan
   pemboleh ubah tetap wajib sebelum apa-apa diagnosis boleh dipercayai.
2. **Mutasi data silang-guide.** Kelihatan sangat meyakinkan (data demo hanya ada SATU minit,
   SATU geran akses, SIFAR pesanan storan). Snapshot DB sebelum/selepas membuktikan
   **sifar** perubahan data perniagaan — hanya `help_events` (telemetri tour) bertambah.

### ⚠️ Dua kesilapan proses saya sendiri — direkod supaya tidak diulang

1. **Diagnosis daripada `trace.zip` dengan penghurai sendiri memberi mesej ralat guide dan
   langkah yang SALAH** (rekod lama dalam folder bernama sama), lalu memburu punca yang tidak
   wujud selama beberapa pusingan. `--reporter=list` dengan output PENUH ke fail memberi ralat
   sebenar + nombor baris serta-merta. Guna trace untuk **turutan tindakan**, bukan identiti
   kegagalan.
2. **Menyalurkan larian ujian melalui `tail`** menjadikan `EXIT=0` milik `tail` — ulangan tepat
   pelajaran `gh run watch` yang sudah direkod dalam memori deploy.

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
semuanya berakhir scrollTop kembali 0. **Peraturan #9 repo dipatuhi**: berhenti selepas 3
cubaan, kod tidak terbukti DIBUANG, ukuran penuh direkod dalam `resources/js/help.js`.

### ⚠️ Viewer dokumen tidak boleh menjalankan tour langsung

`/viewer/{media}` ialah halaman berasingan (URL bertandatangan 30 minit) TANPA Livewire dan
TANPA `help.js`. Sasaran kawalannya dipasang dan direkod `reserved`; menaikkan taraf guide
itu memerlukan runtime bantuan pada halaman viewer — dicadangkan F7.

### ⚠️ `failures: []` dalam shard JSON BUKAN bermakna "tiada kegagalan"

Larian `tenant-admin-public` yang gagal menulis `failures: []` **dan** `complete: false` secara
serentak. Sebabnya: Playwright memulakan semula proses worker selepas ujian gagal (untuk
menjamin persekitaran bersih), jadi keadaan aras-modul `results`/`failures` **direset**;
hanya guide yang berjalan SELEPAS mula semula itu dikira. `guide_ids`/`step_ids` pula datang
daripada MANIFEST, bukan daripada keputusan larian, jadi ia sentiasa penuh dan tidak boleh
digunakan sebagai penunjuk liputan.

Gate tetap **gagal-tertutup** dan sah: `complete` dikira daripada `doneGuides.size ===
shardGuides.length`, jadi ia menangkap kekurangan itu walaupun senarai `failures` hilang.
Yang hilang hanyalah **diagnostik**. Jangan sekali-kali membaca `failures: []` sebagai bukti
kejayaan — baca `complete`.

### Penemuan harness lain yang bernilai

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
5. **`PHP_CLI_SERVER_WORKERS` tidak disokong pada Windows** — pelayan dev tempatan melayan
   satu permintaan pada satu masa, jadi `page.goto` boleh tamat masa 60s di bawah beban.
   CI (Linux) tidak berkongsi had ini.
6. **`php -d … artisan serve` TIDAK menghantar `-d` kepada pelayan web anak.** Disahkan pada
   kod vendor: `ServeCommand::serverCommand()` memulangkan tepat
   `[php_binary(), '-S', host:port, server.php]` — sifar bendera `-d`, dan cwd anak ialah
   `public_path()`. Maka `-d max_execution_time=0` hanya melindungi PENYELIA; pelayan web
   sebenar kekal pada had php.ini (30s). Bukti dalam log pelayan:
   `PHP Fatal error: Maximum execution time of 30 seconds exceeded … vendor/composer/ClassLoader.php:429`.
   Permintaan yang mati begitu tidak pernah menyelesaikan `load`, jadi `page.goto` tamat masa
   pada 60s — itulah tandatangan `screen.pindah-lokasi-fizikal` dan `tenant.records`
   (kedua-duanya guide yang laluannya betul; `tenant.records` malah W5, tidak disentuh W1).
   **Pelayan e2e tempatan mesti dilancarkan terus** supaya had dikenakan pada proses yang betul:
   `cd public && php -d max_execution_time=0 -S 127.0.0.1:8092 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php`
   (melancarkan terus juga memberi env penuh, setara `--no-reload`). **Isu tempatan sahaja** —
   tiada perubahan repo diperlukan.

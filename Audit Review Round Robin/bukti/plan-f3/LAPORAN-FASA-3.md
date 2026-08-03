# Laporan Fasa 3 — Bahasa: `lang/ms/` + kerangka e-mel + vendor Filament + label

**Tarikh:** 3 Ogos 2026 · **Rujukan:** `PELAN-PEMBAIKAN.md` §4
**Menutup:** RR-01-03 · RR-03-01 · RR-02-05 · RR-05-02 · RR-05-01 · RR-08-04 · RR-01-05

---

## (a) Ringkasan apa dibina

Audit mendapati Diwan bercakap Inggeris pada setiap permukaan yang datang daripada framework,
walaupun `APP_LOCALE=ms` — kerana **`lang/ms/` langsung tidak wujud**. Kesannya: mesej validasi
(`The nama field is required.`), `auth.failed`, pagination, dan **kerangka kesemua e-mel**
(`Hello!` … `Regards,` … `All rights reserved.`). Mesej rojak paling teruk yang dilaporkan:
`The failkan Ke field is required.`

F3 menutupnya pada sumbernya:

1. **`lang/ms/{validation,auth,passwords,pagination}.php`** — terjemahan **penuh** 146 kunci
   daun (bukan pilihan), istilah konsisten: *wajib diisi* · *mestilah* · *tidak sah*.
2. **`validation.attributes`** — 109 nama medan dipetakan kepada label UI **yang sebenar**,
   dikumpul secara automatik daripada `->label()` komponen borang Filament, jadi mesej ralat
   menyebut nama yang sama seperti yang pengguna nampak pada skrin.
3. **`lang/ms.json`** — 5 kunci kerangka e-mel, disalin **verbatim** daripada templat vendor.
4. **`lang/vendor/filament-schemas/ms/components.php`** — butang wizard `Seterus`/`Sebelum`
   → **`Seterusnya`/`Sebelumnya`**.
5. **Katalog panduan** — 3 arahan yang menyebut butang "Seterus" diselaraskan, `catalog_version`
   dinaikkan.
6. **Label `Edit`** → `Sunting` pada **6** tempat (inventori pelan menjangka 5 — lihat (e)).

**`APP_FALLBACK_LOCALE` KEKAL `en`** seperti yang pelan tetapkan: fallback `ms` akan memaparkan
**kunci mentah** (`validation.custom.x`) bagi kunci yang tercicir. Perlindungan sebenar ialah
ujian kesempurnaan, yang dibina di sini.

## (b) Fail dicipta/diubah

| Fail | Perubahan |
|---|---|
| `lang/en/{validation,auth,passwords,pagination}.php` | diterbitkan (`lang:publish`) — rujukan ujian kesempurnaan |
| `lang/ms/validation.php` | **BAHARU** — 136 kunci + 109 `attributes` |
| `lang/ms/auth.php` · `passwords.php` · `pagination.php` | **BAHARU** — 3 + 5 + 2 kunci |
| `lang/ms.json` | **BAHARU** — 5 kunci kerangka e-mel |
| `lang/vendor/filament-schemas/ms/components.php` | **BAHARU** — override label wizard |
| `resources/help/guides.json` | 3 arahan "Seterus"→"Seterusnya"; `catalog_version` `2026.08.03.3` |
| `app/Filament/Admin/Resources/Users/Tables/UsersTable.php` | `Edit` → `Sunting` |
| `app/Filament/Admin/Resources/Mosques/Tables/MosquesTable.php` | `Edit` → `Sunting` |
| `app/Filament/Admin/Resources/Mosques/Pages/ViewMosque.php` | `Edit Tenant` → `Sunting Tenant` |
| `app/Filament/Admin/Pages/TetapanPlatform.php` | `Edit Tetapan` → `Sunting Tetapan` |
| `app/Filament/App/Pages/TetapanMasjid.php` | `Edit Tetapan` → `Sunting Tetapan` |
| `resources/views/filament/app/pages/tetapan-masjid.blade.php` | **(ke-6)** teks arahan yang menyebut butang itu |
| `tests/Feature/LocalisationTest.php` | **BAHARU** — 34 ujian |
| `tests/Feature/HelpCatalogQualityTest.php` | **BAHARU** — 2 ujian (dikongsi F5) |
| `e2e/explore.spec.js` | penjaga EN-leak per halaman (superadmin + 8 role tenant) |
| `e2e/guidance.spec.js` · `guidance-full.spec.js` · `office-workflow.spec.js` | selektor butang wizard mengikut label baharu (10 tempat) |
| `Audit Review Round Robin/bukti/plan-baseline/manifest.json` | dijana semula (`catalog_version` berubah) |

## (c) Output verifikasi sebenar

```
$ php artisan lang:publish
  INFO  Language files published successfully.
  validation  136 kunci daun · auth 3 · passwords 5 · pagination 2

$ php artisan test tests/Feature/HelpCatalogQualityTest.php tests/Feature/LocalisationTest.php
  Tests:  36 passed (276 assertions)

$ node .../tools/build-manifest.mjs --catalog … --mobile … --role-routes … --out …
  OK: guides=83 steps=473 actionGeneric=200 placeholder=248
      waves=W0:2g/10s W1:28g/140s W2:13g/145s W3:1g/11s W4:1g/13s W5:35g/146s W6:3g/8s
      role_routes entries=410

$ node scripts/audit/validate-plan-manifest.mjs --manifest …
  OK: manifest sah — partition wave/shard sepadan pengiraan bebas, set-union exact,
      role_routes konsisten.        exit=0

$ vendor/bin/pint --dirty
  {"tool":"pint","result":"passed"}

$ php artisan test
  Tests:  1 skipped, 489 passed (5070 assertions)      [453 → 489]

$ npm run build
  ✓ built in 2.20s      (aset help TIDAK berubah — F3 tidak sentuh entri Vite)
```

### e2e tempatan (server :8092, DB perawan `migrate:fresh --seed`)

```
$ npx playwright test --project=ci-guidance e2e/explore.spec.js
  inventori panel superadmin ................................................ PASS
  inventori dan smoke semua peranan tenant .................................. PASS (8.9m)
  → penjaga EN-leak baharu: 0 kebocoran pada 7 halaman superadmin + semua
    halaman navigasi bagi kesemua 8 role tenant

$ npx playwright test --project=ci-guidance e2e/guidance.spec.js
  19 passed, 1 failed (11.8m)
  ✔ tour klasifikasi mengikuti modal lima langkah tanpa menghantar rekod
  ✔ wizard klasifikasi lima langkah berfungsi pada desktop dan mobile
    → kedua-duanya mengklik butang wizard melalui selektor BAHARU 'Seterusnya'
```

**Kegagalan tunggal — disiasat, BUKAN regresi F3:**

```
desktop: setiausaha
  "Failed to load resource: net::ERR_NO_BUFFER_SPACE"
  "filamentTable is not defined"
```

`ERR_NO_BUFFER_SPACE` ialah keletihan soket peringkat OS pada mesin dev Windows (sudah direkod
sebagai flake dev-sahaja semasa F0); `filamentTable is not defined` ialah akibatnya — bundle
jadual gagal dimuat, jadi komponen Alpine tiada. Ia berlaku selepas ±9 minit merangkak
berterusan dalam proses yang sama. Tiada kaitan dengan bahasa: F3 tidak menyentuh pemuatan
aset mahupun JS jadual.

**Hipotesis flake DIUJI, bukan diandaikan** — ujian yang sama dijalankan semula bersendirian:

```
$ npx playwright test --project=ci-guidance e2e/guidance.spec.js --grep "Chrome berasingan"
  1 passed (11.4m)
```

### e2e `ci-domain` — selektor wizard ke-10

```
$ npx playwright test --project=ci-domain e2e/office-workflow.spec.js
  larian #1 (DB selepas 3 larian lain) : 1 passed, 1 failed
     ✔ :33 klasifikasi Peti Masuk terus edarkan minit melalui modal   <- guna selektor BAHARU
     ✘ :86 minit, maklum balas, susulan dan kelulusan pejabat
            ('Minit diedarkan.' tidak muncul)

$ php artisan migrate:fresh --seed        # DB perawan
$ npx playwright test --project=ci-domain e2e/office-workflow.spec.js
  2 passed (2.3m)
```

Kegagalan `:86` ialah **keadaan DB yang telah diguna pakai** oleh tiga larian sebelumnya
(gotcha yang sudah direkod: item Peti Masuk benih habis diklasifikasi antara larian) — ujian
itu **tidak** menggunakan selektor wizard, dan ia lulus pada DB perawan. CI sentiasa
`migrate:fresh --seed`, jadi keadaan ini tidak wujud di sana.

**Kelambatan tempatan disahkan sedia ada, bukan kos penjaga baharu:** `explore.spec.js` **versi
asal** (assertion saya di-`git stash`) juga melebihi had 180s pada mesin ini. Jadi timeout
tempatan bukan disebabkan penjaga EN-leak.

### Bukti penjaga menangkap regresi (ujian yang tidak pernah gagal ialah ujian palsu)

| Regresi disuntik | Keputusan |
|---|---|
| Buang `lang/vendor/filament-schemas/ms/components.php` | **2 merah** (lookup + objek Action) |
| Padam kunci JSON `Regards,` | **1 merah** |
| Padam satu kunci `lang/ms/validation.php` (`boolean`) | **merah**: `lang/ms/validation.php kehilangan kunci: boolean` |
| Pulangkan "Seterus" ke katalog | **merah**, menamakan langkah tepat: `workflow.admin_masjid.muat-naik…#10 (instruction)` |
| Pulangkan `->label('Edit')` | **merah**, menamakan fail |
| **Pulih semula** | **36 passed** |

## (d) Kriteria Siap §4.8

| Kriteria | Status |
|---|---|
| Ujian baharu + suite penuh hijau; Pint bersih | ✔ 36 baharu · 489 lulus · pint passed |
| **18/18** kelas notifikasi dilindungi data-provider + penjaga kesempurnaan | ✔ senarai eksplisit dibandingkan dengan imbasan fail sebenar |
| Skrin pendaftaran: validasi BM | ✔ diuji melalui `RegisterMosque` sebenar |
| Wizard: "Seterusnya"/"Sebelumnya" | ✔ diassert pada objek `Action` yang dirender |
| **5/5** (sebenarnya **6/6**) label Edit hilang | ✔ + penjaga imbasan seluruh `app/`+`resources/` |
| Gate CI hijau | ⏳ menunggu larian CI selepas push |
| Hantar e-mel ujian sebenar di produksi (`--mail-to`) | ⏳ semasa Deploy 3 |
| Matriks keselamatan §0.6 S1–S6 | ✔ suite penuh merangkumi `PlanManifestTest` (410 probe × 10 identiti + silang-tenant) |

## (e) Lencongan dari spec

**TIADA lencongan skop.** Tiga perkara ditemui semasa pelaksanaan yang pelan tidak jangka —
kesemuanya dalam semangat §4 dan direkod di sini:

### 1. Label `Edit` bukan 5, tetapi **6**

Inventori §4.6 dibina dengan grep `label('Edit\|label("Edit\|>Edit<\|'Edit'`. Corak itu
menemui 5 pengisytiharan label — tetapi terlepas **teks arahan yang MENYEBUT butang itu**:

```
resources/views/filament/app/pages/tetapan-masjid.blade.php:53
  “Admin aktifkan fungsi dan tetapkan e-mel pengirim dibenarkan melalui “Edit Tetapan”.”
```

Membiarkannya bermakna arahan pada skrin merujuk butang yang **tidak lagi wujud** — kesilapan
yang sama betul-betul seperti "Seterus" dalam katalog (§4.5), yang pelan sendiri melarang.
Dibetulkan, dan penjaga diperluas daripada "5 halaman" kepada **imbasan seluruh `app/` +
`resources/`** supaya kelas pepijat ini tidak boleh berulang.

*(Nota kecil yang menghiburkan: penjaga baharu itu memerahkan suite atas komen yang saya
tulis sendiri dalam blade, kerana komen itu memetik label lama secara literal. Penjaga
berkelakuan betul; komen ditulis semula.)*

### 2. Selektor e2e mengklik butang wizard mengikut nama lamanya

`guidance.spec.js` (5×), `guidance-full.spec.js` (4×) dan `office-workflow.spec.js` (1×)
mengklik `getByRole('button', { name: 'Seterus', exact: true })`. Dengan label baharu,
`exact: true` **tidak lagi padan** — kesemua sepuluh akan gagal di CI.

Dikemas dalam commit yang **sama** (peraturan #9 + §4.5): perubahan ujian di sini ialah
akibat langsung perubahan produk yang disengajakan, bukan ujian dilonggarkan untuk lulus.

### 3. Wizard Filament 4 tidak boleh disahkan melalui HTML pelayan

Pelan §4.7 #5 meminta "render halaman dengan wizard → HTML mengandungi Seterusnya".
Dalam Filament 4 kandungan modal dirender **pelanggan-sisi**, jadi HTML pelayan bagi
`OnboardingWizard` (satu-satunya halaman berwizard) **tidak pernah** mengandungi butang itu —
`mountAction('mula')` pun tidak menghasilkannya.

Diganti dengan pengesahan yang **lebih kuat, bukan lebih lemah**: assert terus pada objek
yang menjana butang itu —

```php
$wizard = Wizard::make([]);
expect($wizard->getNextAction()->getLabel())->toBe('Seterusnya');       // vendor Wizard.php:164
expect($wizard->getPreviousAction()->getLabel())->toBe('Sebelumnya');   // vendor Wizard.php:194
```

— ditambah ujian yang mengesahkan ketiga-tiga wizard projek masih menggunakan komponen yang
override ini terpakai (jika projek berpindah komponen, override menjadi tidak relevan secara
senyap). Pengesahan hujung-ke-hujung sebenar kekal pada e2e, yang mengklik butang itu.

## (f) Nota/risiko untuk fasa seterusnya

1. **`attributes` ialah dokumen hidup.** 109 medan dipetakan hari ini. Medan borang baharu
   tanpa entri akan memaparkan nama lajur mentah dalam mesej ralat (`registry_file_id` dan
   bukan "Failkan Ke"). Tiada penjaga automatik untuk ini — menambahnya bermakna mengimbas
   setiap `->label()` pada setiap larian, yang rapuh. Dicadangkan sebagai semakan F8.
2. **Petunjuk dalam kurungan sengaja dibuang** daripada `attributes` ("(pilihan)",
   "(cth 200)"): ia membantu semasa mengisi borang tetapi janggal dalam ayat mesej ralat.
3. **Deploy 3 memerlukan `diwan:sync-help-index --delete`** — katalog berubah.
4. Selepas deploy, jalankan `diwan:staging-check --mail-to=` dan **baca** e-mel sebenar:
   salam, butang, subcopy dan footer mesti BM. Itu satu-satunya kriteria §4.8 yang tidak
   boleh dibuktikan tanpa produksi.
5. F5 akan menambah semakan lain ke dalam `HelpCatalogQualityTest` — fail itu sengaja dicipta
   di sini sebagai rumah bersama, bukan hanya untuk satu semakan.

# Laporan Fasa F6-W3 — baki generik shard `screen`

**Tarikh:** 5 Ogos 2026 · **Asas:** `56caac7` · **Inventori:** `INVENTORI-W3.md` (ditulis sebelum kod)

---

## (a) Ringkasan apa dibina

W3 menutup sembilan langkah generik terakhir dalam shard `screen` — dan, dalam proses itu,
membina mekanisme yang menjadikan perkataan "dijustifikasikan" bermakna buat kali pertama.

1. **Satu langkah dinaikkan kepada `specific`.** `screen.muat-naik-dokumen#4` ("Sahkan toast dan
   baris baharu") kini menyasar `inbox-record` — sel tajuk baris **pertama** Peti Masuk, iaitu
   dokumen yang baru dimuat naik (jadual disusun `created_at desc`).
2. **Lapan langkah menerima justifikasi eksplisit bertarikh.** Enam langkah
   `screen.viewer-dokumen` (`generic-justified` — kawalannya berada pada halaman kendiri
   `/viewer/{media}` yang tiada runtime bantuan) dan dua langkah konsep (`not-applicable`).
3. **Allowlist justifikasi per-langkah dibina** — §7.2 gate registri **(f)** menuntutnya sejak
   F0, tetapi mekanismenya tidak pernah wujud: setiap langkah generik menerima ayat automatik
   "penambahbaikan **dijadualkan** Wn", yang bercanggah dengan dirinya sendiri sebaik wave itu
   ditutup, dan menjadikan gate buta terhadap perbezaan "dijustifikasikan" lawan "belum dibuat".
4. **Jurang G2 untuk guide berkoreografi ditutup.** `assertStepPopover()` menguatkuasakan
   "elemen aktif = sasaran langkah", tetapi `driveChoreographedRange()` hanya mengundi nombor
   langkah. Menaikkan langkah 4 kepada `specific` tanpa menutup jurang itu bermakna menambah
   ujian yang **tidak boleh gagal**.
5. **Tiga penjaga dikemas serentak** dengan senarai wave tertutup yang sama
   (`build-manifest.mjs` · `validate-plan-manifest.mjs` · `PlanManifestTest.php`).

**Skop diukur semula, bukan disalin.** Jadual beku §7.2 menulis W3 = 1 guide / 11 langkah. Itu
keadaan pada 2 Ogos; `waveOf()` ialah fungsi dinamik, jadi selepas W1 seluruh shard `screen`
(29 guide / 151 langkah) berada dalam W3, dan guide asalnya
(`screen.klasifikasi-peti-masuk`) sudah 11/11 spesifik — tiada kerja padanya.

## (b) Fail dicipta/diubah

| Fail | Perubahan |
|---|---|
| `app/Filament/App/Resources/Inbox/Tables/InboxTable.php` | lajur `title` → `baris1($record, 'inbox-record')` |
| `resources/help/targets.json` | entri `inbox-record` (`active`) + betulkan `owner_source` dua entri sedia ada |
| `resources/help/guides.json` | `screen.muat-naik-dokumen#4` → `inbox-record`; `catalog_version` `2026.08.05.1` → `2026.08.05.2` |
| `resources/help/step-justifications.json` | **BAHARU** — allowlist 8 justifikasi per-langkah |
| `Audit Review Round Robin/bukti/plan-baseline/tools/build-manifest.mjs` | `--justifications`; `FROZEN.justified_waves`; penjaga yatim/basi + liputan penuh |
| `scripts/audit/validate-plan-manifest.mjs` | semakan allowlist BEBAS (dikira semula drp katalog) + tolak sebab baseline dalam wave tertutup |
| `tests/Feature/PlanManifestTest.php` | ujian baharu: justifikasi eksplisit bertarikh, dua arah, larangan `generic-justified` pada langkah tindakan |
| `tests/Feature/Help/W3TargetRenderTest.php` | **BAHARU** — 2 ujian render (`inbox-record` unik + menandakan baris TERBAHARU) |
| `e2e/guidance-full.spec.js` | `assertTrailTargets()` + dipakai pada KEDUA-DUA laluan berkoreografi |
| `Audit Review Round Robin/bukti/plan-baseline/manifest.json` | dijana semula |
| `docs/HELP-TARGETS.md` | dijana semula (148 aktif + 22 rizab) |
| `Audit Review Round Robin/bukti/plan-baseline/tools/README.md` | prosedur `--justifications` + peraturan "tiga penjaga satu commit" |
| `Audit Review Round Robin/bukti/plan-f6-w3/` | **BAHARU** — inventori + laporan ini |

## (c) Output SEBENAR arahan verifikasi

### c.1 Metrik katalog (dijana semula)

```
KEMAJUAN berbanding baseline F0:
  generic_declared 443 → 236 (−207)
  generic_pp 238 → 36 (−202)
  generic_pc 205 → 200 (−5)
  placeholder_titles 258 → 0 (−258)
  action_steps_with_generic_target 200 → 0 (−200)
  wave W0.placeholder 10 → 0 (−10)
  wave W1.action_generic 140 → 0 (−140)
  wave W1.placeholder 140 → 0 (−140)
  wave W2.action_generic 60 → 0 (−60)
  wave W5.placeholder 108 → 0 (−108)
  shard workflow.action_steps 75 → 57 (−18)
Justifikasi eksplisit: 8 langkah; wave tertutup W0, W1, W2, W3 liputan PENUH.
OK: manifest ditulis ke Audit Review Round Robin/bukti/plan-baseline/manifest.json
  guides=83 steps=473 actionGeneric=0 placeholder=0
  waves=W0:2g/10s W1:0g/0s W2:0g/0s W3:29g/151s W4:14g/158s W5:35g/146s W6:3g/8s
  role_routes entries=410 counts={"public":0,"superadmin":25,"admin_masjid":25,…}
BUILD EXIT=0
```

**Ramalan inventori disemak:** `generic_declared` 237 → 236 ✔ · `action_generic` 0 → 0 ✔ ·
`placeholder` 0 → 0 ✔ · `wait_for_user` 172 → 172 ✔ · struktur 83/473 dan wave tidak berubah ✔.

### c.2 Validator bebas

```
KEMAJUAN berbanding baseline F0:
  action generic 200 → 0 (−200)
  placeholder 258 → 0 (−258)
OK: manifest sah — partition wave/shard sepadan pengiraan bebas, set-union exact, role_routes konsisten.
VALIDATOR EXIT=0
```

### c.3 ⭐ Bukti penjaga — lima regresi sengaja, kelima-limanya DITANGKAP

Ujian yang tidak pernah gagal ialah ujian palsu. Setiap penjaga baharu diuji dengan
memperkenalkan kerosakan sebenar, kemudian dipulihkan.

```
=== R1: buang SATU justifikasi (screen.viewer-dokumen#3) ===
FAIL: wave TERTUTUP (W0, W1, W2, W3) masih ada langkah generik tanpa justifikasi eksplisit: screen.viewer-dokumen#3
  ✔ build-manifest menolak liputan tidak lengkap — exit 1 (dijangka 1)

=== R2: entri allowlist YATIM (kunci tidak wujud dalam katalog) ===
FAIL: justifikasi YATIM/BASI (kunci tiada dalam katalog atau langkah sudah specific): screen.tiada-guide-ini#9
  ✔ build-manifest menolak entri yatim — exit 1 (dijangka 1)

=== R3: entri allowlist BASI (langkah sudah specific) ===
FAIL: justifikasi YATIM/BASI (kunci tiada dalam katalog atau langkah sudah specific): screen.muat-naik-dokumen#4
  ✔ build-manifest menolak entri basi — exit 1 (dijangka 1)

=== R4: sebab terlalu pendek (skema) ===
FAIL: justifikasi screen.tanda-tindakan-minit-selesai#5: sebab terlalu pendek/kosong
  ✔ build-manifest menolak sebab tidak bermakna — exit 1 (dijangka 1)

=== R5: manifest membawa sebab BASELINE untuk langkah wave tertutup (validator bebas) ===
FAIL: manifest masih membawa sebab BASELINE untuk langkah wave tertutup
  ✔ validator menolak sebab baseline dalam wave tertutup — exit 1 (dijangka 1)

=== PULIH: bina semula + sahkan HIJAU ===
Justifikasi eksplisit: 8 langkah; wave tertutup W0, W1, W2, W3 liputan PENUH.
  ✔ build-manifest bersih — exit 0 (dijangka 0)
  ✔ validator bersih — exit 0 (dijangka 0)
```

**Dan penjaga render (buang `extraCellAttributes` daripada `InboxTable`):**

```
grep -c inbox-record InboxTable.php = 0
Tests:    2 failed (4 assertions)          EXIT=1
--- dipulihkan ---
Tests:    2 passed (6 assertions)          EXIT=0
```

### c.4 Suite Pest penuh

```
Tests:    1 skipped, 556 passed (5369 assertions)
Duration: 171.71s
PEST EXIT=0
```

553 → **556** (+3: dua ujian render `inbox-record`, satu ujian allowlist manifest).

### c.5 Pint + binaan aset

```
{"tool":"pint","result":"fixed","files":[{"path":"tests\\Feature\\Help\\W3TargetRenderTest.php",
 "fixers":["no_blank_lines_after_phpdoc"]}]}

public/build/assets/help-CrH0eDM1.css     14.90 kB │ gzip: 3.52 kB
public/build/assets/help-D0185fq1.js      35.20 kB │ gzip: 10.66 kB
✓ built in 5.76s                          BUILD EXIT=0
```

**Nama aset KEKAL** — sama seperti Deploy 6/7/8. Betul dan dijangka: W3 menyentuh PHP, JSON,
ujian dan harness sahaja; tiada entri Vite disentuh. Bukti deploy mesti bersandar pada
**kandungan dalam imej** + ImageID (pelajaran Deploy 2/7/8).

### c.6 Gate tempatan — 3 shard (DB SEGAR setiap satu) + agregator

```
shard screen               EXIT=0    30 passed (13.1m)
shard workflow             EXIT=1    3 failed / 12 passed (13.5m)   <- pusingan #1
shard tenant-admin-public  EXIT=0    41 passed (18.8m)
AGREGATOR EXIT=1
GATE GAGAL (1 masalah):
  - shard workflow melaporkan complete=false
```

**Pusingan #2 selepas pembetulan gate:**

```
shard workflow             EXIT=0    15 passed (13.0m)
AGREGATOR EXIT=0
GATE LULUS: 83 guide · 473 langkah · 172 langkah tindakan — union tiga shard sepadan
manifest (set, bukan kiraan). Laporan: storage/app/plan-f6/coverage-gate.json
```

Artifak shard `screen`: `complete=true` · 29 guide · 151 langkah · 111 tindakan ·
`blocked=0` · `failures=0` — sepadan **tepat** angka beku.

#### 🔎 Kegagalan pusingan #1 — satu punca, tiga gejala, dan ia BUKAN kecacatan produk

Assertion G2 baharu memerahkan dua guide `workflow` berkoreografi. Jejak perekam memberi
diagnosis terus:

```
workflow.setiausaha…#5: sasaran "classification-source" tidak pernah disorot
 (jejak: 4:inbox-classify → 4:inbox-classification-modal → 5:inbox-classification-modal
        → 6:inbox-classification-modal → 7:inbox-classification-modal
        → 8:inbox-classification-modal → 8:classification-submit → 9:classification-submit)
```

Puncanya ialah kelakuan runtime yang **disengajakan** — `resources/js/help.js:231`:

```js
if (step.target.startsWith('classification-') && step.target !== 'classification-submit') {
    return exact.closest('.fi-modal-window') || exact;
}
```

Menyorot SATU medan di dalam modal boleh-skrol menolak popover (dan lubang overlaynya) ke luar
viewport — kecacatan yang sudah diukur dan dibawa ke F7. Jadi `inbox-classification-modal`
ialah jawapan yang **betul**.

**Tiga bukti bebas bahawa ini reka bentuk, bukan kerosakan:** (1) jejak konsisten pada setiap
langkah, bukan rawak; (2) `classification-submit` — satu-satunya pengecualian dalam kod —
**memang** disorot terus (`8:classification-submit`), jadi kelakuan sepadan kod baris demi
baris; (3) modal itu memang membawa `data-help-target="inbox-classification-modal"`
(`InboxTable.php:114`).

Gejala ketiga (`tulis shard JSON` gagal) ialah akibat, dan ia mengesahkan gotcha yang sudah
direkod: **`failures: []` bukan bermakna tiada kegagalan** — bacaan sebenar ialah
`complete: false`.

**Pembetulan mengetatkan, bukan melonggarkan.** Untuk langkah `classification-*`, gate kini
menuntut DUA fakta serentak: modal yang betul disorot **DAN** medan yang diisytihar
benar-benar hadir lagi kelihatan pada saat itu (dibaca daripada peta `sasaran` perekam).
Menerima "modal sahaja" akan membenarkan wizard berada pada langkah yang salah.

**Bukti penjaga bagi pembetulan itu** — sasaran ditukar kepada nama yang tidak wujud, dengan
modal tetap disorot seperti kes lulus:

```
guard EXIT=1 (dijangka 1)
Error: workflow.setiausaha…#5: sasaran "classification-tiada-langsung" tidak pernah disorot
  (keadaan sasaran pada langkah itu: tiada/tiada/tiada/tiada;
   jejak: 4:inbox-classify → 4:inbox-classification-modal → 5:inbox-classification-modal → …)
```

Medan diagnostik baharu (`keadaan sasaran`) itulah yang membezakan **peluasan modal yang sah**
daripada **sasaran yang benar-benar hilang** — dua keadaan yang sebelum ini menghasilkan jejak
yang kelihatan sama.

### c.7 Viewport DIUKUR (bukan disalin) — dan satu pengisytiharan registri didapati SALAH

Pelajaran F5: `isVisible()` tidak mengesan elemen di luar skrin. Jadi `viewport` diukur dengan
`getBoundingClientRect()` sebenar + persilangan viewport, desktop **dan** iPhone 13:

```
desktop    inbox-record       n=1  x=473 w=92  dalamViewport=true   "Dokumen baharu dalam peti masuk"
desktop    inbox-scan-status  n=1  x=965 w=107 dalamViewport=true   "tidak_diimbas"
iPhone13   inbox-record       n=1  x=129 w=92  dalamViewport=TRUE   "Dokumen baharu dalam peti masuk"
iPhone13   inbox-scan-status  n=1  x=621 w=107 dalamViewport=FALSE  "tidak_diimbas"
```

- `inbox-record` (sasaran W3) — `viewport: both` **disahkan betul**.
- `inbox-scan-status` (sasaran W2) — diisytihar `both`, tetapi pada 390px ia berada **di luar
  viewport lalai**. Pengisytiharan itu **salah** dan dibetulkan kepada `desktop` dengan nota
  ukuran. Gate W2 hanya berjalan pada 1440×1000, jadi ia tidak boleh menangkapnya.

Jadual boleh diskrol mendatar, jadi pengguna masih boleh mencapainya; yang tidak berfungsi
ialah **tour menggulung bekas dalaman** — kecacatan yang sama seperti popover-dalam-modal,
dan ia direkod untuk F7.

### c.9 ⭐ CI pusingan #1 MERAH — dan ia mendedahkan kecacatan produk W2

CI run `31001021747` gagal pada `PostgreSQL, Redis, Meili, OCR and tests`. Kegagalannya ialah
**dua ujian W3 saya sendiri**, yang lulus tempatan:

```
⨯ sasaran inbox-record wujud TEPAT SEKALI dalam /peti-masuk yang dirender    0.28s
⨯ inbox-record menandakan baris TERBAHARU, bukan sebarang baris             0.29s

sasaran inbox-record tidak wujud (atau tidak unik) dalam /peti-masuk
Failed asserting that 0 is identical to 1.
```

*(Amaran `[OCR] gagal record N … pdftotext` dalam log yang sama ialah `testing.WARNING` sedia
ada, bukan puncanya. Begitu juga "554 warnings, 1 passed" — pelabelan Pest di CI yang sudah
direkod sejak F4; isyarat sebenar ialah "2 failed".)*

**Puncanya bukan ujian.** `baris1()` memoi baris pertama dalam sifat **statik**, dan komennya
mendakwa memo itu "hidup satu permintaan sahaja". Dakwaan itu salah: sifat statik hidup selama
hayat **proses**. Dalam proses ujian, `??=` tidak pernah menetapkannya semula, jadi memo
memegang ID daripada ujian terdahulu.

**Mengapa lulus tempatan, gagal di CI:** SQLite mengembalikan kaunter AUTOINCREMENT apabila
transaksi `RefreshDatabase` dirollback, jadi ID rekod bermula semula pada 1 setiap ujian dan
kebetulan sepadan memo. Jujukan PostgreSQL **tidak** dirollback, jadi ID terus menaik → tiada
baris padan → `substr_count(...) === 0`. Keluarga sama seperti perangkap `idempotency_key`
uuid (F3): **DB tempatan yang longgar-jenis tidak akan memberitahu anda.**

**Pembaikan:** `self::$barisPertamaId = null;` pada permulaan `configure()` — dipanggil sekali
setiap render jadual — dalam **ketiga-tiga** jadual (`InboxTable`, `MinitsTable`,
`ApprovalsTable`), plus komen yang salah dibetulkan supaya andaian itu tidak diulang.

**Bukti penjaga (bebas enjin DB):** ujian baharu merender **dua kali dalam satu proses** dengan
baris pertama yang berbeza.

```
kod LAMA : EXIT=1 — "render kedua masih menandakan baris LAMA — memo statik tidak diset semula"
kod BAHARU: 5 passed (W2 + W3)
```

#### ⚠️ PEMBETULAN kepada mesej komit `9bfbf74`

Mesej komit itu menulis *"Pada mana-mana pelayan PHP yang kekal hidup, tour boleh menyorot
baris LAMA selepas muat naik"* dan melabelnya **KESAN PENGGUNA**. Saya kemudian **menguji**
dakwaan itu dan ia **SALAH**:

```
php -S, tiga permintaan berturutan pada satu pelayan:
  memo=9556 pid=46264
  memo=5808 pid=46264      <- PID SAMA, nilai BERUBAH
  memo=7115 pid=46264
```

PHP menetapkan semula sifat statik pada setiap kitaran permintaan walaupun proses dikekalkan
(model shared-nothing). Jadi skop kesan sebenar ialah:

| Persekitaran | Terjejas? |
|---|---|
| Produksi php-fpm | **TIDAK** |
| `php artisan serve` / `php -S` (gate e2e) | **TIDAK** |
| Proses ujian (Pest — kernel HTTP dipanggil dalam proses, tiada shutdown permintaan) | **YA** — di sinilah ia menggigit |
| Octane/Swoole/RoadRunner (aplikasi kekal hidup) | YA — projek ini tidak menggunakannya |

Jadi ini kecacatan **ketepatan-ujian + andaian palsu dalam kod**, bukan pepijat hidup di
produksi. Pembaikan tetap betul dan tetap bernilai — ia menjadikan penjaga bermakna dan
membuang andaian yang tidak dijamin — tetapi ia **tidak** patut dilaporkan sebagai kerosakan
yang pengguna alami. Direkod di sini kerana mesej komit sudah dipush dengan dakwaan berlebihan
itu.

### c.8 Suite Pest penuh + pint + validator (selepas SEMUA perubahan)

```
{"tool":"pint","result":"passed"}
Tests:    1 skipped, 557 passed (5375 assertions)
Duration: 169.12s          PEST EXIT=0
VALIDATOR EXIT=0
```

553 → **557** (+4): dua ujian render `inbox-record`, satu ujian allowlist manifest, satu ujian
regresi memo statik.

## (d) Kriteria Siap §7.4 per gelombang

| Kriteria | Status | Bukti |
|---|---|---|
| **G1** status per-langkah direkod untuk setiap langkah W3 | ✔ | 151/151; 8 justifikasi eksplisit + selebihnya `specific`; penjaga liputan penuh |
| **G2** sasaran `specific` baharu: unik + kelihatan + tahan morph + setiap viewport | ✔ | `inbox-record` — 2 ujian render (unik pada 2 baris; menandakan baris TERBAHARU) + jejak tour + ukuran desktop & iPhone 13 |
| **G3** tour black-box untuk setiap langkah `wait_for_user` W3 | ✔ | shard `screen` 30/30, 111 langkah tindakan |
| **G4** kitaran guide untuk setiap guide W3 | ✔ | shard `screen` 29/29 guide, `complete=true` |
| Registry seiring · yatim 0 dua hala · `HELP-TARGETS.md` dijana | ✔ | `HelpCatalogQualityTest` 14/14; dok dijana (148 aktif + 22 rizab) |
| Metrik pada denominator PENUH | ✔ | `generic_declared` **443 → 236**; W3 generik **9 → 8**, kesemuanya dijustifikasikan |
| **`blocked` = 0** dalam skop W3 | ✔ | agregator `blocked=0`; `PlanManifestTest` melarang `blocked` dalam baseline |
| Denominator W3 sepadan exact jadual beku | ✔ | W3 29/151 tidak berubah; jumlah 83/473 tidak berubah |

## (e) Lencongan dari spec

**TIADA lencongan daripada §7.** Tiga keputusan dalam skop yang dinyatakan secara terbuka:

1. **Skop W3 diukur semula.** Jadual beku menulis 1 guide / 11 langkah (keadaan 2 Ogos);
   `waveOf()` dinamik, jadi skop sebenar ialah 9 langkah generik dalam shard `screen`. Angka
   beku wave **tidak** diubah — W3 kekal 29/151.
2. **Enam langkah viewer kekal generik.** Memasang runtime bantuan pada `/viewer/{media}`
   memerlukan Livewire + entri Vite + route baharu dalam manifest `role_routes` beku. Pelan
   **tidak pernah** menugaskannya (§8.4 F7 hanya menugaskan butang viewer *disabled* +
   `pageInput.max`), jadi ia dicatat sebagai cadangan F7 bertarikh dalam allowlist.
3. **Dua item di luar inventori asal dibetulkan kerana ia ditemui oleh ukuran W3:**
   validator manifest bebas ditambah ke CI, dan pengisytiharan `viewport` `inbox-scan-status`
   dibetulkan. Kedua-duanya kecil, keduanya berkaitan terus dengan penjaga W3, dan
   meninggalkannya bermakna merekod sesuatu yang saya tahu tidak benar.

## (f) Nota/risiko untuk fasa seterusnya

### Jurang gate yang W3 tutup (guna semula pada W4–W6)

1. **`generic-justified` kini bermakna sesuatu.** Wave yang ditutup mesti disenaraikan dalam
   `FROZEN.justified_waves` (tiga tempat: `build-manifest.mjs`, `validate-plan-manifest.mjs`,
   `PlanManifestTest.php`) dan setiap langkah generiknya mesti ada dalam allowlist dengan sebab
   ≥40 aksara + `since` bertarikh. Menutup W4 bermakna 82 langkah `workflow` perlu keputusan.
2. **Validator bebas kini berjalan dalam CI.** Sebelum ini ia hanya wujud dalam komen —
   keluarga sama seperti penemuan F5 (projek Playwright `unit` tidak pernah dijalankan CI).
3. **G2 kini berkuat kuasa untuk guide berkoreografi.** `assertTrailTargets()` boleh diguna
   semula; ia sudah memahami sasaran yang diluaskan runtime.

### Inventori awal W4 (diukur, untuk permulaan pantas)

82 langkah generik merentas 14 guide `workflow`, kesemuanya `wait_for_user: false`:

```
10/20 admin_masjid.muat-naik-semak-dan-klasifikasikan…    5/13 admin_masjid.urus-fail-fizikal…
10/13 admin_masjid.sediakan-dan-laksanakan-pelupusan      5/12 pengerusi.terima-baca-balas…
 8/13 admin_masjid.betulkan-rekod-salah-tawan…            5/10 bendahari.urus-rekod-kewangan…
 7/13 setiausaha.klasifikasikan-surat-masuk…              5/9  bendahari.mohon-storan-tambahan
 7/11 audit.laksanakan-semakan-audit-baca-sahaja          4/9  nazir.proses-minit-dan-keputusan…
 6/9  pengerusi.buat-keputusan-kelulusan-atau-pelupusan    2/8  ketua_imam.laksanakan-arahan-minit
 6/10 setiausaha.mohon-kelulusan-dan-pembetulan-rekod      2/8  ajk.baca-rekod-dan-selesaikan…
```

Pecahan kasar: **35** berada pada route yang JUGA mempunyai sasaran spesifik lain (calon
dinaikkan) dan **47** pada route tanpa sasaran spesifik (calon justifikasi). Angka ini kasar —
ia belum mengambil kira prasyarat `detail:`/`modal:` — jadi ia titik permulaan inventori W4,
bukan keputusan.

### Kecacatan DIUKUR, tidak dibaiki (F7)

- **Tour tidak menggulung bekas boleh-skrol dalaman.** Disahkan sekali lagi oleh W3 pada paksi
  MENDATAR: `inbox-scan-status` pada 390px berada di luar viewport lalai. Keluarga sama seperti
  popover-dalam-modal yang menolak CTA ke luar skrin.
- **`/viewer/{media}` tiada runtime bantuan.** Enam sasaran DOM sudah dipasang dan `reserved`.
- **Tour tidak bertahan merentas navigasi yang dimulakan PENGGUNA** (dibawa dari W2).

### Cadangan (bukan kerja W3)

Medan `viewport` registri **tidak dibaca oleh mana-mana ujian** — ia semata-mata pengisytiharan,
dan W3 membuktikan satu daripadanya salah. Penjaga yang mengukur setiap sasaran `active` pada
kedua-dua viewport akan menutup kelas ralat ini; kosnya satu pelayar per sasaran, jadi ia lebih
sesuai sebagai langkah F8 daripada gate setiap wave.


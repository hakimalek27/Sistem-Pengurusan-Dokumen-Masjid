# Laporan Fasa F6-W2 — 13 guide `workflow` bertindakan (60 langkah)

**Tarikh:** 5 Ogos 2026 · **Skop beku:** W2 = 13 guide / 145 langkah / **60 langkah
`wait_for_user` bersasar generik** · **Asas:** `03defc3` (CI run **7/7 hijau**, disahkan
sebelum satu baris pun diubah).

📄 Prasyarat: `INVENTORI-W2.md` (langkah 1, dikomit `22d854e`).

---

## (a) Ringkasan

Kesemua **60** langkah tindakan bersasar generik dalam family `workflow` diberi sasaran
spesifik. Metrik utama F6 — `action_steps_with_generic_target` — kini **200 → 0**, iaitu
KESELURUHAN skop asal audit ditutup (W1 menutup 140, W2 menutup baki 60).

Kerja sebenar ternyata **bukan** sekadar memetakan katalog seperti inventori jangka:

1. **Dua sasaran BAHARU** perlu dipasang (`minit-record`, `inbox-scan-status`) — tanpa
   keduanya, tiga guide pada Minit Saya dan satu langkah amaran muat naik tidak mempunyai
   elemen sebenar untuk disorot.
2. **18 langkah PEMERHATIAN** yang tersalah label `wait_for_user: true` dibetulkan kepada
   `false` (§7.2 langkah 3). Ini menggerakkan invarian STRUKTUR beku `wait_for_user`
   **190 → 172** dalam keempat-empat penjaga, dengan sebab bertulis.
3. **Enam guide menyeberang halaman TEPAT selepas satu penghantaran borang.** Runtime
   memberikan `action-then-navigate`, dan `watchForActionCompletion()` hanya berpindah
   halaman selepas sasaran HILANG — jadi gate mesti benar-benar melengkapkan dan menghantar
   borang itu. Tujuh entri `AKSI_LANGKAH` ditulis untuk itu.
4. **Benih demo diperbesar**: satu minit dengan **empat** penerima tindakan (bukan pengerusi
   sahaja) dan kelulusan tertunggak untuk Pengerusi **dan** Nazir. Tanpanya, tiga skrin
   `minit-saya` dan satu skrin `kelulusan` kosong → gate hijau PALSU (pelajaran W1).

Wave berpindah **W2 13/145 → 0/0** dan **W4 1/13 → 14/158**. Jumlah **83/473 tidak berubah**.

## (b) 18 langkah `wait_for_user: true → false` — satu per satu

Setiap satu ialah arahan **BACA** atau kerja **DI LUAR sistem**; pada kedua-duanya CTA
"Buat pada skrin" menunggu tindakan UI yang tidak pernah wujud — punca tepat aduan pemilik
("dah tekan ke belum?").

| Guide (tanpa awalan `workflow.`) | # | Arahan | Kelas |
|---|---:|---|---|
| `admin_masjid.betulkan-rekod-…` | 3 | Semak dokumen asal, metadata, OCR dan tab Audit. | baca |
| `admin_masjid.urus-fail-fizikal-…` | 4 | Sahkan nombor, tajuk, lokasi dan status penjagaan. | baca |
| `admin_masjid.urus-fail-fizikal-…` | 11 | Kemas kini label fizikal yang sebenar. | **luar sistem** |
| `pengerusi.terima-baca-…` | 4 | Sahkan kandungan, metadata, sumber dan sensitiviti. | baca |
| `pengerusi.terima-baca-…` | 5 | Kembali ke Minit Saya selepas semakan. | navigasi |
| `pengerusi.terima-baca-…` | 10 | Sahkan status penerima berubah. | baca |
| `bendahari.urus-rekod-…` | 3 | Semak media dan metadata kewangan. | baca |
| `bendahari.mohon-storan-tambahan` | 5 | Semak jumlah dan invois. | baca |
| `nazir.proses-minit-…` | 9 | Semak status akhir. | baca |
| `ketua_imam.laksanakan-arahan-minit` | 3 | Semak rekod dan lampiran. | baca |
| `ketua_imam.laksanakan-arahan-minit` | 4 | Jangan muat turun jika tidak diperlukan. | amaran |
| `ketua_imam.laksanakan-arahan-minit` | 7 | Selesaikan kerja sebenar. | **luar sistem** |
| `ajk.baca-rekod-…` | 3 | Semak kandungan yang dibenarkan. | baca |
| `ajk.baca-rekod-…` | 4 | Jika akses ditolak, minta akses melalui Admin/Kerani; jangan ubah URL. | amaran |
| `ajk.baca-rekod-…` | 8 | Sahkan status berubah. | baca |
| `audit.laksanakan-semakan-…` | 4 | Semak jumlah hasil. | baca |
| `audit.laksanakan-semakan-…` | 6 | Semak metadata, OCR, versi, minit, kelulusan dan audit. | baca |
| `audit.laksanakan-semakan-…` | 7 | Jangan gunakan sebarang kaedah untuk mengubah rekod. | amaran |

**Tiada satu pun langkah dilepaskan sebagai `generic-justified`.** Nota: status itu TIDAK
akan membantu walaupun digunakan — metrik `action_steps_with_generic_target` mengira
`wait_for_user && sasaran generik` tanpa mengambil kira status, jadi justifikasi bertulis
tidak mengeluarkan satu langkah pun daripadanya. Setiap satu daripada 60 mesti mendapat
sasaran spesifik ATAU pembetulan label — dan itulah yang berlaku.

## (c) Pemetaan 61 langkah (60 tindakan + 1 penerangan)

| Guide | # | Sasaran lama → baharu | wfu | Tajuk |
|---|---:|---|---|---|
| `admin_masjid.muat-naik-semak-dan-klasifikasikan-dokumen-serta-hantar-minit` | 8 | `page-content` → **`inbox-scan-status`** | true | Semak imbasan sebelum klasifikasi |
| `admin_masjid.betulkan-rekod-salah-tawan-tanpa-memadam-sejarah` | 3 | `page-primary` → **`record-tab-audit`** | **true→false** | Semak butiran dan jejak audit |
| `admin_masjid.betulkan-rekod-salah-tawan-tanpa-memadam-sejarah` | 4 | `page-primary` → **`record-correction`** | true | Buka Mohon Pembetulan |
| `admin_masjid.betulkan-rekod-salah-tawan-tanpa-memadam-sejarah` | 5 | `page-primary` → **`record-correction-reason`** | true | Mohon pembetulan rekod |
| `admin_masjid.betulkan-rekod-salah-tawan-tanpa-memadam-sejarah` | 6 | `page-primary` → **`record-correction-title`** | true | Mohon pembetulan rekod |
| `admin_masjid.betulkan-rekod-salah-tawan-tanpa-memadam-sejarah` | 7 | `page-primary` → **`record-correction-submit`** | true | Mohon pembetulan rekod |
| `admin_masjid.urus-fail-fizikal-atau-hibrid-dan-jejak-penjagaan` | 4 | `page-primary` → **`file-identity`** | **true→false** | Sahkan identiti dan penjagaan fail |
| `admin_masjid.urus-fail-fizikal-atau-hibrid-dan-jejak-penjagaan` | 5 | `page-primary` → **`file-checkout`** | true | Buka Keluarkan Fail |
| `admin_masjid.urus-fail-fizikal-atau-hibrid-dan-jejak-penjagaan` | 6 | `page-primary` → **`file-checkout-holder`** | true | Keluarkan fail fizikal |
| `admin_masjid.urus-fail-fizikal-atau-hibrid-dan-jejak-penjagaan` | 7 | `page-primary` → **`file-checkout-location`** | true | Keluarkan fail fizikal |
| `admin_masjid.urus-fail-fizikal-atau-hibrid-dan-jejak-penjagaan` | 8 | `page-primary` → **`file-checkout-submit`** | true | Keluarkan fail fizikal |
| `admin_masjid.urus-fail-fizikal-atau-hibrid-dan-jejak-penjagaan` | 9 | `page-primary` → **`file-relocate`** | true | Buka Pindah Lokasi |
| `admin_masjid.urus-fail-fizikal-atau-hibrid-dan-jejak-penjagaan` | 10 | `page-primary` → **`file-relocate-submit`** | true | Pindah lokasi fizikal |
| `admin_masjid.urus-fail-fizikal-atau-hibrid-dan-jejak-penjagaan` | 11 | `page-primary` → **`file-movements`** | **true→false** | Selaraskan label fizikal dengan log |
| `admin_masjid.sediakan-dan-laksanakan-pelupusan-terkawal` | 6 | `page-primary` → **`disposal-prepare`** | true | Buka Sedia Senarai Semakan |
| `admin_masjid.sediakan-dan-laksanakan-pelupusan-terkawal` | 7 | `page-primary` → **`disposal-records`** | true | Amaran dan senarai rekod |
| `admin_masjid.sediakan-dan-laksanakan-pelupusan-terkawal` | 8 | `page-primary` → **`disposal-confirm`** | true | Hantar batch untuk kelulusan |
| `pengerusi.terima-baca-balas-dan-selesaikan-minit` | 4 | `page-primary` → **`minit-record`** | **true→false** | Semak rekod berkaitan |
| `pengerusi.terima-baca-balas-dan-selesaikan-minit` | 5 | `page-primary` → **`minit-status`** | **true→false** | Kembali ke senarai minit |
| `pengerusi.terima-baca-balas-dan-selesaikan-minit` | 6 | `page-primary` → **`minit-reply`** | true | Buka Balas & Edarkan |
| `pengerusi.terima-baca-balas-dan-selesaikan-minit` | 7 | `page-primary` → **`minit-reply-body`** | true | Balas dan edarkan minit |
| `pengerusi.terima-baca-balas-dan-selesaikan-minit` | 8 | `page-primary` → **`minit-reply-submit`** | true | Balas dan edarkan minit |
| `pengerusi.terima-baca-balas-dan-selesaikan-minit` | 9 | `page-primary` → **`minit-complete`** | true | Tanda tindakan minit selesai |
| `pengerusi.terima-baca-balas-dan-selesaikan-minit` | 10 | `page-primary` → **`minit-status`** | **true→false** | Status penerima selepas ditanda |
| `pengerusi.buat-keputusan-kelulusan-atau-pelupusan` | 3 | `page-primary` → **`approval-lulus`** | true | Buat keputusan kelulusan |
| `pengerusi.buat-keputusan-kelulusan-atau-pelupusan` | 4 | `page-primary` → **`approval-password`** | true | Buat keputusan kelulusan |
| `pengerusi.buat-keputusan-kelulusan-atau-pelupusan` | 5 | `page-primary` → **`approval-submit`** | true | Buat keputusan kelulusan |
| `setiausaha.mohon-kelulusan-dan-pembetulan-rekod` | 3 | `page-primary` → **`record-approval`** | true | Buka Mohon Kelulusan |
| `setiausaha.mohon-kelulusan-dan-pembetulan-rekod` | 4 | `page-primary` → **`record-approval-submit`** | true | Mohon kelulusan |
| `setiausaha.mohon-kelulusan-dan-pembetulan-rekod` | 5 | `page-primary` → **`record-correction`** | true | Buka Mohon Pembetulan |
| `setiausaha.mohon-kelulusan-dan-pembetulan-rekod` | 6 | `page-primary` → **`record-correction-submit`** | true | Mohon pembetulan rekod |
| `bendahari.urus-rekod-kewangan-dan-minit` | 3 | `page-primary` → **`record-tab-info`** | **true→false** | Tab maklumat rekod |
| `bendahari.urus-rekod-kewangan-dan-minit` | 4 | `page-primary` → **`record-minit`** | true | Buka Edarkan Minit |
| `bendahari.urus-rekod-kewangan-dan-minit` | 5 | `page-primary` → **`record-minit-action`** | true | Edarkan minit |
| `bendahari.urus-rekod-kewangan-dan-minit` | 6 | `page-primary` → **`record-minit-body`** | true | Edarkan minit |
| `bendahari.urus-rekod-kewangan-dan-minit` | 7 | `page-primary` → **`record-minit-submit`** | true | Edarkan minit |
| `bendahari.mohon-storan-tambahan` | 3 | `page-content` → **`storage-add`** | false | Buka Tambah Storan |
| `bendahari.mohon-storan-tambahan` | 4 | `page-primary` → **`storage-blocks`** | true | Permohonan storan tambahan |
| `bendahari.mohon-storan-tambahan` | 5 | `page-primary` → **`storage-orders`** | **true→false** | Jumlah dan pesanan storan |
| `bendahari.mohon-storan-tambahan` | 6 | `page-primary` → **`storage-submit`** | true | Permohonan storan tambahan |
| `nazir.proses-minit-dan-keputusan-kelulusan` | 3 | `page-primary` → **`minit-reply`** | true | Buka Balas & Edarkan |
| `nazir.proses-minit-dan-keputusan-kelulusan` | 4 | `page-primary` → **`minit-reply-submit`** | true | Balas dan edarkan minit |
| `nazir.proses-minit-dan-keputusan-kelulusan` | 7 | `page-primary` → **`approval-lulus`** | true | Buat keputusan kelulusan |
| `nazir.proses-minit-dan-keputusan-kelulusan` | 8 | `page-primary` → **`approval-password`** | true | Buat keputusan kelulusan |
| `nazir.proses-minit-dan-keputusan-kelulusan` | 9 | `page-primary` → **`approval-status`** | **true→false** | Status akhir kelulusan |
| `ketua_imam.laksanakan-arahan-minit` | 3 | `page-primary` → **`minit-record`** | **true→false** | Rekod dan lampiran berkaitan |
| `ketua_imam.laksanakan-arahan-minit` | 4 | `page-primary` → **`minit-record`** | **true→false** | Had muat turun dokumen |
| `ketua_imam.laksanakan-arahan-minit` | 5 | `page-primary` → **`minit-reply`** | true | Buka Balas & Edarkan |
| `ketua_imam.laksanakan-arahan-minit` | 6 | `page-primary` → **`minit-reply-body`** | true | Balas dan edarkan minit |
| `ketua_imam.laksanakan-arahan-minit` | 7 | `page-primary` → **`minit-status`** | **true→false** | Laksanakan kerja sebenar dahulu |
| `ketua_imam.laksanakan-arahan-minit` | 8 | `page-primary` → **`minit-complete`** | true | Tanda tindakan minit selesai |
| `ajk.baca-rekod-dan-selesaikan-tugasan-minit` | 3 | `page-primary` → **`minit-record`** | **true→false** | Kandungan yang dibenarkan |
| `ajk.baca-rekod-dan-selesaikan-tugasan-minit` | 4 | `page-primary` → **`minit-record`** | **true→false** | Jika akses ditolak |
| `ajk.baca-rekod-dan-selesaikan-tugasan-minit` | 5 | `page-primary` → **`minit-reply`** | true | Buka Balas & Edarkan |
| `ajk.baca-rekod-dan-selesaikan-tugasan-minit` | 6 | `page-primary` → **`minit-reply-submit`** | true | Balas dan edarkan minit |
| `ajk.baca-rekod-dan-selesaikan-tugasan-minit` | 7 | `page-primary` → **`minit-complete`** | true | Tanda tindakan minit selesai |
| `ajk.baca-rekod-dan-selesaikan-tugasan-minit` | 8 | `page-primary` → **`minit-status`** | **true→false** | Status tugasan selepas selesai |
| `audit.laksanakan-semakan-audit-baca-sahaja` | 4 | `page-primary` → **`search-results`** | **true→false** | Jumlah hasil carian |
| `audit.laksanakan-semakan-audit-baca-sahaja` | 5 | `page-primary` → **`search-result-open`** | true | Hasil carian lanjutan |
| `audit.laksanakan-semakan-audit-baca-sahaja` | 6 | `page-primary` → **`search-result-item`** | **true→false** | Butiran sampel dalam hasil |
| `audit.laksanakan-semakan-audit-baca-sahaja` | 7 | `page-primary` → **`search-results`** | **true→false** | Semakan kekal baca-sahaja |

## (c-2) ⭐ DUA kecacatan produk ditemui oleh gate — kedua-duanya KEGAGALAN SENYAP

Gate tidak hanya mengesahkan sasaran; ia memandu borang seperti pengguna sebenar, dan itulah
yang mendedahkan dua kecacatan yang tidak pernah dilihat oleh mata mahupun oleh suite.

**Corak yang sama pada kedua-duanya:** lapisan perkhidmatan menolak dengan
`ValidationException::withMessages([<kunci> => ...])` di mana `<kunci>` **bukan nama medan
borang**. Filament merender ralat pengesahan pada medan yang sepadan; apabila tiada medan
sepadan, mesej itu **hilang sepenuhnya**. Pengguna menekan Hantar dan **tiada apa-apa berlaku** —
tiada toast, tiada ralat medan, modal kekal terbuka.

| | Borang | Kunci ralat | Medan sebenar borang | Gejala |
|---|---|---|---|---|
| **S1** | Mohon Pembetulan Rekod | `changes` | `reason`, `title`, `record_type`, … | Hantar tanpa mengubah medan → senyap |
| **S2** | Keluarkan Fail (fizikal) | `holder`, `file` | `holder_user_id`, `holder_name`, … | Hantar tanpa pemegang → senyap |

**Bukti pengukuran (S1):** 5 permintaan `/livewire/update` (jadi klik SAMPAI ke pelayan),
`ralat pengesahan: (tiada mesej ralat dirender)`, dan tangkapan skrin memperlihatkan borang
penuh dengan butang Hantar yang kelihatan tidak berfungsi. **(S2):** 4 permintaan, 0 mesej.

**Pembaikan:** kedua-dua aksi Filament kini menangkap `ValidationException` dan menghantar
notifikasi merah yang boleh dibaca, kemudian `throw new Halt` (modal kekal terbuka supaya
pengguna boleh membetulkannya). Perkhidmatan domain **tidak diubah** — ia betul; hanya
permukaannya yang bisu.

⚠️ **Nota keluarga:** ini kecacatan ketiga dan keempat pada borang Mohon Pembetulan selepas
BUG-B (5 Ogos). Borang itu tidak pernah berfungsi untuk sebarang penghantaran sehingga BUG-B,
dan selepas itu ia masih gagal senyap apabila tiada perubahan. Patut disemak semula pada F8.

## (d) Output verifikasi sebenar

### Penjana manifest (invarian beku)

```
$ node "Audit Review Round Robin/bukti/plan-baseline/tools/build-manifest.mjs" --catalog resources/help/guides.json …
KEMAJUAN berbanding baseline F0:
  generic_declared 443 → 237 (-206)
  generic_pp 238 → 36 (-202)
  generic_pc 205 → 201 (-4)
  placeholder_titles 258 → 0 (-258)
  action_steps_with_generic_target 200 → 0 (-200)
  wave W0.placeholder 10 → 0 (-10)
  wave W1.action_generic 140 → 0 (-140)
  wave W1.placeholder 140 → 0 (-140)
  wave W2.action_generic 60 → 0 (-60)
  wave W5.placeholder 108 → 0 (-108)
  shard workflow.action_steps 75 → 57 (-18)
OK: manifest ditulis ke Audit Review Round Robin/bukti/plan-baseline/manifest.json
  guides=83 steps=473 actionGeneric=0 placeholder=0
  waves=W0:2g/10s W1:0g/0s W2:0g/0s W3:29g/151s W4:14g/158s W5:35g/146s W6:3g/8s
  role_routes entries=410 counts={"public":0,"superadmin":25,"admin_masjid":25,…}
```

### Validator BEBAS (pelaksanaan berasingan)

```
$ node scripts/audit/validate-plan-manifest.mjs --manifest …/manifest.json
KEMAJUAN berbanding baseline F0:
  action generic 200 → 0 (-200)
  placeholder 258 → 0 (-258)
OK: manifest sah — partition wave/shard sepadan pengiraan bebas, set-union exact, role_routes konsisten.
```

### Registri sasaran dijana semula

```
$ node "…/tools/generate-help-targets-doc.mjs"
OK: docs/HELP-TARGETS.md dijana (147 aktif + 22 rizab).
```

### Gate e2e — tiga shard (setiap satu pada DB SEGAR, seperti job CI berasingan)

```
===== SHARD workflow =====             15 passed (13.1m)
===== SHARD tenant-admin-public =====  41 passed (17.6m)
===== SHARD screen =====               30 passed (12.3m)
```

Ringkasan artifak shard (SET, bukan kiraan sahaja):

```
screen               guide=29/29  langkah=151/151  tindakan=111/111  lengkap=true  blocked=0
workflow             guide=14/14  langkah=158/158  tindakan= 57/57   lengkap=true  blocked=0
tenant-admin-public  guide=40/40  langkah=164/164  tindakan=  4/4    lengkap=true  blocked=0
catalog_version 2026.08.05.1 pada ketiga-tiga shard
```

### Agregator (gate keluaran)

```
$ node scripts/audit/aggregate-guidance-coverage.mjs --manifest …/manifest.json --shards "storage/app/plan-f6/shard-*.json"
GATE LULUS: 83 guide · 473 langkah · 172 langkah tindakan — union tiga shard sepadan manifest (set, bukan kiraan).
```

### Suite Pest + projek Playwright lain + binaan

```
$ php artisan test
Tests:    1 skipped, 553 passed (5319 assertions)     ← 549 → 553 (+4 penjaga baharu)

$ npx playwright test --project=unit
17 passed (1.1s)

$ npm run build
public/build/assets/help-D0185fq1.js   35.20 kB       ← nama aset TIDAK berubah (tiada JS produk disentuh)
✓ built in 7.89s

$ vendor/bin/pint --dirty
{"tool":"pint","result":"passed"}
```

### `ci-guidance` — 34/35, dan kegagalan itu DIUKUR sebagai had masa, bukan regresi

```
$ npx playwright test --project=ci-guidance
  1 failed  [ci-guidance] > e2e/explore.spec.js:83 > inventori dan smoke semua peranan tenant
  34 passed (22.2m)

$ sed -n '/```/,/```/p' test-results/*explore*/error-context.md
Test timeout of 180000ms exceeded.          ← tamat masa TULEN; tiada assertion gagal

$ npx playwright test --project=ci-guidance --grep "inventori dan smoke" --timeout=900000
  1 passed (4.6m)                            ← lulus apabila diberi belanjawan
```

Ujian itu merangkak 8 peranan × ~10 halaman pada pelayan dev satu-benang Windows
(`forking is not supported`). Memberi belanjawan lebih besar menjadikannya lulus dalam 4.6m,
jadi ia **kehabisan masa**, bukan kerosakan. CI Linux (4 worker) menjalankannya dalam had lalai.

### Penjaga DIBUKTIKAN menangkap regresi (bukan sekadar hijau)

```
# Sasaran baharu — pasang semula kod lama:
$ git checkout HEAD -- MinitsTable.php InboxTable.php && php artisan test --filter=W2TargetRender
Tests:    2 failed (4 assertions)

# Kegagalan senyap S1 — pasang semula kod lama:
$ git stash push -- ViewRecord.php && php artisan test --filter="hantar tanpa perubahan"
Tests:    1 failed (5 assertions)
```


## (e) Kriteria Siap

| # | Kriteria (§7.2 / §7.3) | Status |
|---|---|---|
| 1 | Kesemua **60** langkah tindakan bersasar generik `workflow` ditutup | ✔ `action_steps_with_generic_target` **0** |
| 2 | Tiada pelepasan pukal `generic-justified` untuk langkah tindakan | ✔ 0 digunakan |
| 3 | Setiap sasaran dalam registri, `status: active`, tiada yatim dua hala | ✔ `HelpCatalogQualityTest §6.5 #7` |
| 4 | Sasaran BAHARU benar-benar dirender (bukan hanya tersenarai) | ✔ `W2TargetRenderTest` 2 ujian + penjaga dibuktikan merah pada kod lama |
| 5 | Denominator beku dikemas dalam KEEMPAT-EMPAT penjaga, sebab bertulis, commit sama | ✔ |
| 6 | Manifest dijana semula + validator bebas lulus | ✔ exit 0 |
| 7 | `HELP-TARGETS.md` dijana semula (bukan tangan) | ✔ 147 aktif + 22 rizab |
| 8 | Gate 3 shard + agregator SET | lihat (d) |
| 9 | Suite Pest hijau | lihat (d) |
| 10 | `catalog_version` dibumbung (sync-help-index bergantung padanya) | ✔ `2026.08.05.1` |

## (f) Nota/risiko untuk fasa seterusnya

### Jurang produk yang DIUKUR tetapi TIDAK dibaiki dalam W2 (bukan skop wave ini)

**Tour tidak bertahan merentas navigasi yang dimulakan PENGGUNA.** Guide `workflow` bermula
pada senarai ("Cari rekod", "Buka Lihat") dan kemudian merujuk halaman butiran. URL butiran
bersifat dinamik (`/records/{id}`), jadi katalog tidak boleh mengisytiharkannya sebagai `route`
langkah dan `gotoNextRoute()` tiada apa-apa untuk dituju. Apabila pengguna membuka rekod,
`autoStart` adalah **false** kerana progres sudah wujud (`GuidanceProgress.status =
dalam_proses`), jadi tour **tidak muncul semula sendiri** — pengguna mesti menekan pelancar
Pembantu, dan barulah `resumeStep()` memulihkan kedudukan.

Gate melangkaui jurang ini dengan deep-link deterministik (didokumen dalam kod, bukan
disembunyikan). Ia mengesahkan **sasaran** setiap langkah butiran, dan **tidak** mendakwa
menguji kesinambungan tour merentas navigasi pengguna. Calon F7: sama ada (a) auto-resume
apabila `?panduan=` tiada tetapi progres `dalam_proses` wujud pada route yang sepadan, atau
(b) beri langkah "Buka Lihat" sasaran pautan baris supaya runtime boleh membawa deep-link.

### Kekangan persekitaran tempatan (bukan kecacatan produk — DIUKUR)

`php artisan serve` pada Windows mencetak `forking is not supported on this platform` = SATU
proses. Navigasi yang dimulakan runtime kadangkala meninggalkan dokumen pada
`readyState=loading` dengan belasan aset tergantung. **Dibuktikan bukan pelayan tersekat:**
sepanjang 140s gantung, `curl /up` dijawab **200 dalam ~0.6s setiap 10s**. Harness memulihkan
dengan satu muat semula pada URL yang SAMA. CI Linux menggunakan `PHP_CLI_SERVER_WORKERS=4`
dan tidak sepatutnya terjejas — sahkan pada larian CI.

### ⚠️ Shard `workflow` kini MENGUBAH fixture fail — isolasi shard menjadi WAJIB

Koreografi W2 melakukan penghantaran borang SEBENAR (itulah maksudnya). Salah satunya —
`urus-fail#8` — benar-benar mengeluarkan fail hibrid, jadi `custody_status` menjadi `dipinjam`
dan aksi **Keluarkan Fail** tidak lagi dirender pada fail itu.

Menjalankan ketiga-tiga shard berturut-turut pada SATU pangkalan data tempatan menyebabkan
tiga guide `screen` gagal selepas itu (`file-identity`, `file-checkout`, `file-access-grant`
tidak ditemui pada mana-mana baris). **Bukan regresi produk** — kesan sampingan susunan larian.

Disahkan bahawa CI tidak terjejas: `.github/workflows/ci.yml:291-295` menjalankan shard sebagai
**matriks job berasingan**, setiap satu dengan `services:` sendiri (komen sedia ada: "job tidak
berkongsi services (P12-04)") dan langkah `Migrate and seed` sendiri. Larian tempatan mesti
menyemai semula ANTARA shard — itu kini prosedur, bukan pilihan.

### Untuk W3–W6

- Guide yang merentas senarai→butiran akan berulang pada W5 (`tenant`/`admin`). Corak
  `hasDetailStep` vs `startsOnDetail` + deep-link peralihan kini sedia digunakan semula.
- `pilihPilihanPertama()` (komponen select Filament sendiri, BUKAN Choices.js) juga sedia
  digunakan semula untuk mana-mana medan berbilang.
- Pemeriksaan borang wajib mesti dibuat SEBELUM menulis koreografi: dua daripada tujuh
  penghantaran W2 ditolak kerana medan wajib yang saya tidak semak dahulu.

---

## Lampiran — pelajaran prosedur yang dibayar harganya dalam fasa ini

1. **`tail -60` pada larian gate membuang diagnostik.** Larian shard pertama menghasilkan 10
   kegagalan; saya menyalurkannya melalui `tail` dan hanya menyimpan senarai nama ujian.
   Diagnostik sebenar terpaksa dipulihkan daripada `test-results/*/error-context.md`. **Jangan
   `tail` output gate** — simpan penuh, tapis kemudian.

2. **JANGAN ubah fail sumber semasa larian gate sedang berjalan.** Saya melakukan
   `git stash` pada `ViewRecord.php` (untuk membuktikan penjaga) sedangkan shard penuh sedang
   berjalan di latar. Pelayan `artisan serve` membaca semula PHP pada setiap permintaan, jadi
   guide yang kebetulan berjalan dalam tetingkap itu diuji terhadap kod LAMA. Bukti penjaga
   mesti dijalankan apabila tiada larian lain aktif.

3. **"Cuba lagi" boleh MENCIPTA kegagalan yang disangka dipulihkan.** Gelung hantar 4×
   saya membanjiri pelayan dev satu-benang dengan POST `/livewire/update` yang belum dijawab
   dan menghabiskan keenam-enam sambungan Chrome; dokumen berikutnya kemudian tersangkut pada
   `readyState=loading` dengan 17 permintaan tergantung. Menunggu **kesan yang boleh
   diperhatikan** (toast) dan mengehadkan kepada 2 percubaan menyelesaikannya.

# INVENTORI F6-W4 — baki generik shard `workflow`

Ditulis **sebelum** sebarang kod W4, seperti W2/W3. Semua angka di bawah DIUKUR daripada
`resources/help/guides.json`, `resources/help/targets.json`,
`Audit Review Round Robin/bukti/plan-baseline/manifest.json` dan `php artisan route:list`,
bukan daripada jadual beku pelan.

Asas: `local = origin = server = e8bfd75`; imej produksi dibina daripada `2cd7ab8` (Deploy 9).

---

## 1. Skop SEBENAR vs jadual beku pelan

| Sumber | Guide | Langkah | Langkah generik |
|---|---|---|---|
| Jadual beku §7.2 / §12 pelan | **1** | **13** | — |
| Diukur (manifest `wave === 'W4'`) | **14** | **158** | **82** |

Sebabnya sama seperti W3 dan sudah dijangka: **`waveOf()` DINAMIK.** Selepas W2 menutup
13 guide `workflow` bertindakan, SELURUH shard `workflow` berpindah ke W4. Jadual beku ialah
snapshot keadaan pra-W1, bukan definisi skop. Ramalan W3 ("jangka perkara sama pada W4:
jadual kata 1 guide, sebenarnya 82 langkah / 14 guide") **tepat**.

Ciri kesemua 82 langkah:

```
wait_for_user = false  : 82/82   (semuanya langkah PENERANGAN, bukan tindakan)
target                 : page-content  82/82
viewport               : desktop       82/82
mobile_defect          : false         82/82
status kini            : generic-justified 82 (sebab BASELINE automatik, bukan justifikasi benar)
```

Metrik keutamaan `action_steps_with_generic_target` sudah **0** sejak W2 dan W4 **tidak**
menyentuhnya. Metrik yang W4 gerakkan ialah `generic_declared` (236 → jangkaan di §5).

---

## 2. ⭐ Penemuan penentu: 82 langkah itu BUKAN "tidak boleh dicapai"

Handover W3 meneka pecahan "35 calon dinaikkan / 47 calon justifikasi" berdasarkan **route
GUIDE**. Ukuran sebenar membatalkan tekaan itu:

**Kesemua 82 langkah generik sudah mengisytiharkan `route` MEREKA SENDIRI**, dan runtime
memang menavigasi ke sana:

- Skema katalog membenarkan `route` per-langkah — divalidasi `app/Services/HelpCatalog.php:168`
  dan `{tenant}` digantikan pada `:193`.
- `resources/js/help/step-advance-plan.js:48` memilih `kind: 'navigate'` /
  `'action-then-navigate'` apabila `next.route && !samePath(next.route)`.
- `resources/js/help.js:775-777` melaksanakan `window.location.assign(next.route + '?panduan=…&langkah=…')`.
- 180/473 langkah katalog membawa `route` sendiri; **147** berbeza daripada route guidenya.

Jadi tour **benar-benar mendaratkan pengguna** di `/log-aktiviti`, `/pembetulan-rekod`,
`/sensitive-access-logs`, `/laporan`, `/retensi`, `/pelupusan` dan seterusnya, lalu menyorot
`page-content` di sana. Itu bermakna majoriti besar langkah ini **layak dinaikkan kepada sasaran
sebenar**, bukan dijustifikasikan. Melepaskannya secara pukal akan menjadi tepat "pengecualian
senyap" yang §7.2 larang.

**14/14 route yang diisytihar disahkan WUJUD** (`php artisan route:list --json`) — termasuk
`sensitive-access-logs`, satu-satunya yang tiada `$slug` eksplisit (Filament menerbitkannya
daripada nama resource). **Tiada langkah menghantar pengguna ke 404.**

---

## 3. Beban kerja per route (tempat tour SEBENARNYA berada)

| Route | Langkah | Sasaran aktif sedia ada | Perlu sasaran BAHARU |
|---|---|---|---|
| `/app/{tenant}/log-aktiviti` | **24** | 5 (`log-detail` jadual; `log-record`/`log-metadata`/`log-actor`/`log-source` **perlu modal**) | 2 |
| `/app/{tenant}/minit-saya` | **14** | 10 | 1 |
| `/app/{tenant}/pelupusan` | 7 | 6 aktif + **2 `reserved` yang DOMnya sudah ada** | 0 |
| `/app/{tenant}/records` | 6 | 28 (tiada carian; `page-actions` = `detail:records`) | 2 |
| `/app/{tenant}/kelulusan` | 6 | 6 | 0 |
| `/app/{tenant}` (papan pemuka) | 4 | 0 — tetapi sasaran LOGIK `nav-primary` terpakai | 0 |
| `/app/{tenant}/peti-masuk` | 3 | 13 | 0 |
| `/app/{tenant}/pembetulan-rekod` | 3 | **0** | 3 |
| `/app/{tenant}/registry-files` | 3 | 18 (semua `detail:`) | 3 |
| `/app/{tenant}/retensi` | 3 | **0** | 3 |
| `/app/{tenant}/carian` | 3 | 5 | 0 |
| `/app/{tenant}/penggunaan` | 2 | 5 | 0 |
| `/app/{tenant}/sensitive-access-logs` | 2 | **0** | 1 |
| `/app/{tenant}/laporan` | 2 | **0** | 2 |
| **JUMLAH** | **82** | — | **≈20** |

Prasyarat disahkan daripada medan `state` registri (bukan andaian):

- `log-record`/`log-metadata`/`log-actor`/`log-source` = `modal:Butiran Log Aktiviti terbuka`
  → **tidak boleh** dipakai untuk langkah senarai. Hanya `log-detail` berada pada jadual.
- `file-medium`/`file-identity` = `detail:registry-files` → **tidak boleh** dipakai untuk
  langkah senarai `registry-files`.
- `page-actions` = `detail:records` → bukan senarai.
- `disposal-actions` + `disposal-status` = `reserved`, state `ada batch`, **atribut DOM sudah
  dipasang** (`pelupusan-manual.blade.php:28` dan `:26`) → W4 hanya perlu **mengaktifkannya**.
- `inbox-scan-status` = `desktop` sahaja (diukur W3) — selaras kerana kesemua 82 langkah
  diisytihar `viewport: desktop`.

---

## 4. Keputusan per kumpulan langkah

Kaedah penamaan `{skrin}-{fungsi}` (§7.2 langkah 4).

### 4.1 Dinaikkan kepada sasaran yang SUDAH ADA — 0 kod produk baharu (31 langkah)

| Route | Langkah | Sasaran |
|---|---|---|
| `/app/{tenant}` | muat-naik #1,#2 · setiausaha.klasifikasikan #1,#2 | `nav-primary` (sasaran LOGIK; `tenant.dashboard#1` sudah menggunakannya untuk ayat yang sama) |
| `/peti-masuk` | muat-naik #3 · setiausaha #3 | `inbox-record` |
| `/peti-masuk` | muat-naik #4 | `inbox-scan-status` |
| `/kelulusan` | pengerusi.keputusan #1,#2 · nazir #5,#6 | `approval-record` |
| `/kelulusan` | setiausaha.mohon #7,#8 | `approval-status` |
| `/pelupusan` | pelupusan #4 | `disposal-candidates` |
| `/pelupusan` | pelupusan #5 · pengerusi.keputusan #6 | `disposal-batches` |
| `/pelupusan` | pelupusan #9 | `disposal-status` *(reserved → active)* |
| `/pelupusan` | pelupusan #10,#11 · pengerusi.keputusan #7 | `disposal-actions` *(reserved → active)* |
| `/carian` | audit #1 | `search-filters` |
| `/carian` | audit #2 | `search-favourite` |
| `/carian` | audit #3 | `search-result-open` |
| `/penggunaan` | bendahari.storan #1 | `storage-usage` |
| `/penggunaan` | bendahari.storan #2 | `storage-orders` |
| `/minit-saya` | 7 langkah "baca/sahkan minit" | `minit-record` / `minit-status` |
| `/log-aktiviti` | 15 langkah "sahkan pelaku/masa/urutan" | `log-detail` |

### 4.2 Perlu sasaran BAHARU (≈20 sasaran, ≈37 langkah)

| Sasaran baharu | Route | Untuk arahan |
|---|---|---|
| `log-search` | `/log-aktiviti` | "Cari tajuk rekod." ×4 |
| `log-filters` | `/log-aktiviti` | "Tapis jenis aktiviti…" ×3 |
| `minit-filters` | `/minit-saya` | "Tapis Perlu Tindakan." ×4, "Tapis/Pilih kategori Saya Hantar" ×2 |
| `records-search` | `/records` | "Cari tajuk atau nombor rujukan." ×3 |
| `records-view` | `/records` | "Buka Lihat…" ×3 |
| `regfiles-search` | `/registry-files` | "Cari nombor fail." |
| `regfiles-medium` | `/registry-files` | "Semak Medium dan Status." |
| `regfiles-view` | `/registry-files` | "Buka Lihat." |
| `correction-diff` | `/pembetulan-rekod` | "Bandingkan nilai asal dengan cadangan." |
| `correction-decision` | `/pembetulan-rekod` | "Reviewer berkuasa memilih Luluskan atau Tolak." |
| `correction-status` | `/pembetulan-rekod` | "Sahkan status dan catatan semakan." |
| `retention-due` | `/retensi` | "Semak tarikh cukup tempoh dan peraturan." |
| `retention-hold` | `/retensi` | "Pastikan Legal Hold tidak aktif." |
| `retention-export` | `/retensi` | "Sediakan eksport luar jika diperlukan." → header action `eksportAkanLuput` (disahkan `RetensiPegangan.php:74`) |
| `sensitive-log-record` | `/sensitive-access-logs` | "Semak pengguna, tindakan, IP dan masa." (+#9) |
| `report-summary` | `/laporan` | "Bandingkan jumlah rekod, retensi dan minit lewat." (kad ringkasan, `laporan.blade.php:3-6`) |
| `report-export` | `/laporan` | "Eksport hanya jika dibenarkan…" → header action `eksportCsv` (disahkan `Laporan.php:44`) |

### 4.3 Calon `not-applicable` / `generic-justified` (dinilai semasa pelaksanaan)

Hanya untuk arahan yang benar-benar tiada rujukan DOM, contohnya:

- `bendahari.urus-rekod-kewangan` #10 — "Rekod pentadbiran sulit di luar akses tidak akan
  dipulangkan." (menyatakan kesan kebenaran, bukan elemen)
- `audit` #9 — "Bandingkan dengan skop audit." (kerja di luar sistem)
- `betulkan-rekod` #13 — "Pastikan tiada perubahan senyap tanpa log."

Setiap satu mesti masuk `resources/help/step-justifications.json` dengan sebab ≥40 aksara +
`since`, dan `W4` mesti ditambah kepada `justified_waves` dalam **TIGA** penjaga
(`build-manifest.mjs` FROZEN, `validate-plan-manifest.mjs` JUSTIFIED_WAVES,
`PlanManifestTest.php`) — mekanisme yang W3 bina.

---

## 5. Ramalan (ditulis SEBELUM kod)

1. Struktur kekal: `guides 83`, `steps 473`, `unique_step_ids 470`.
2. `wave W4.guides` akan jadi **0** dan guide berpindah ke... **tiada wave lain** — W4 ialah
   wave TERAKHIR shard `workflow`, jadi jangkaan: `W4 14 → 0`, `W3 29 → 29` (shard berbeza),
   dan **jumlah 83/473 kekal**. ⚠️ Jika penjaga melaporkan `W4.guides` bukan 0, ertinya
   ada langkah tertinggal — bukan ralat penjaga.
3. `generic_declared` **236 → ≈8–12** (baki = W5 144 + W6 2 belum disentuh… **SALAH**:
   W5/W6 masih generik, jadi ramalan betul ialah **236 → 236 − 82 + (justifikasi W4)**
   iaitu **≈154–160**. Angka tepat direkod dalam laporan fasa.
4. `action_generic` kekal **0** pada setiap wave.
5. ~~Nama aset Vite **KEKAL** (`help-D0185fq1.js` / `help-CrH0eDM1.css`) kerana W4 tidak
   menyentuh `resources/js/help*` — hanya Blade/PHP/katalog.~~
   **❌ RAMALAN INI SALAH — dibatalkan 6 Ogos.** Reka bentuk yang diukur menuntut pemetaan
   sasaran vendor PER HALAMAN, dan itu ialah kod JS (`help/page-target-plan.js` + satu import
   dalam `help.js`). Diukur selepas `npm run build`:
   **`help-D0185fq1.js` → `help-DaHF3IsK.js`**, manakala **`help-CrH0eDM1.css` KEKAL**
   (hanya JS disentuh). Deploy W4 MESTI membina semula `app` DAN `nginx`.
   Punca ramalan salah: saya menganggap skop W4 = katalog sahaja sebelum mengukur bahawa
   Filament tidak mendedahkan cangkuk atribut untuk medan carian jadual.
6. `catalog_version` akan dinaikkan → `2026.08.06.1`; deploy perlu `sync-help-index --delete`.
7. Risiko terbesar: **sasaran mesti wujud dalam keadaan LALAI** (pelajaran W1) — jadual
   `/log-aktiviti`, `/pembetulan-rekod`, `/sensitive-access-logs`, `/pelupusan` mesti ADA
   BARIS dalam benih demo, jika tidak gate akan hijau PALSU atau merah tanpa sebab produk.
   Ini akan diukur, bukan diandaikan, sebelum sasaran diisytihar `active`.

---

## 5A. ⚠️ Data LALAI diukur — beberapa jadual KOSONG (perangkap W1 muncul lagi)

Dikira selepas `migrate:fresh --seed` pada SQLite bersih (tenant `mam`):

| Halaman | Model | Baris (jumlah / mam) | Kesan |
|---|---|---|---|
| `/log-aktiviti` | `MosqueActivityLog` | 4 / **4** | ✔ ada baris |
| `/records`, `/retensi` | `Record` | 5 / **4** | ✔ ada baris |
| `/registry-files` | `RegistryFile` | 5 / **4** | ✔ ada baris |
| `/kelulusan` | `Approval` | 2 / **2** | ✔ ada baris |
| `/minit-saya` | `Minit` | 1 / **1** | ✔ ada baris |
| `/pelupusan` | `DisposalBatch` | 0 / **0** | ✘ **kosong** |
| `/sensitive-access-logs` | `SensitiveAccessLog` | 0 / **0** | ✘ **kosong** |
| `/penggunaan` | `StorageOrder` | 0 / **0** | ✘ kosong (sasaran sedia ada = pembalut, kekal dirender) |
| `/retensi` butiran | `Record.retention_due_at` / `legal_hold` | **0** / **0** | ✘ **kosong** |

Disahkan dengan membaca blade, bukan diteka:

- `pelupusan-manual.blade.php:18-54` — `disposal-status` (`:24`) dan `disposal-actions` (`:26`)
  berada **di dalam cabang `@else`** bagi `$batches->isEmpty()`. Dengan 0 batch kedua-duanya
  **tidak dirender**. Itulah sebab kedua-duanya ditanda `reserved`, dan mengaktifkannya
  **tanpa** baris = gate hijau palsu atau merah tanpa punca produk.
- `retensi-pegangan.blade.php:16-33` + `:40-63` — kedua-dua jadual juga dilindungi `@if …isEmpty()`.

Akibatnya keputusan reka bentuk untuk W4: **utamakan elemen yang dirender dalam keadaan KOSONG**
(input carian jadual, butang tapisan, kad ringkasan, header action, pembalut seksyen), dan
perbesar benih demo HANYA di tempat langkah itu benar-benar merujuk baris yang mesti ada
(preseden W2). Setiap perluasan benih perlu suite penuh dijalankan — W3 membuktikan perubahan
benih boleh mematahkan `MinitService` secara tidak dijangka.

Nota: kelas sebenar bagi `/pembetulan-rekod` ialah **`RecordCorrectionRequest`**
(bukan `RecordCorrection`), jadual `RecordCorrectionsTable.php`.

## 6. Perkara diukur yang BUKAN kerja W4 (direkod, tidak dibaiki)

- Beberapa arahan berbunyi "Buka Log Aktiviti Masjid" / "Buka Minit Saya" sedangkan runtime
  SUDAH menavigasi ke halaman itu sebelum langkah dipaparkan. Itu isu **teks kandungan**
  (skop §6/F9 Manual), bukan sasaran. Direkod di sini supaya tidak hilang.
- `log-record`/`log-metadata`/`log-actor`/`log-source` hanya boleh disorot apabila modal
  butiran terbuka. Langkah W4 tidak membuka modal itu, jadi keempat-empatnya kekal untuk
  guide `screen` — bukan yatim.

# F6-W2 — Inventori langkah (§7.2 langkah 1) sebelum sebarang kod ditulis

**Skop beku:** 13 guide `workflow` · 145 langkah · **60 langkah `wait_for_user` bersasar generik**.
Disahkan daripada KEDUA-DUA sumber: manifest beku `plan-baseline/manifest.json` (wave `W2`) DAN
katalog HIDUP `resources/help/guides.json` — kedua-duanya memberi **60**.

⛔ Had tegas §7.2 (P12-02): kelonggaran "merentas halaman" terpakai kepada langkah **penerangan**
sahaja. Kesemua **60** langkah tindakan ini memerlukan **sasaran spesifik** atau justifikasi
per-langkah bertarikh. Tiada pelepasan pukal.

---

## Taburan kerja mengikut skrin (bukan mengikut guide)

| Skrin / route | Guide | Langkah aksi-generik |
|---|---:|---:|
| `/app/{tenant}/minit-saya` | 4 (pengerusi · nazir · ketua_imam · ajk) | **24** |
| `/app/{tenant}/records` | 3 (admin_masjid · setiausaha · bendahari) | **14** |
| `/app/{tenant}/registry-files` | 1 (admin_masjid) | **8** |
| `/app/{tenant}/carian` | 1 (audit) | 4 |
| `/app/{tenant}/retensi` | 1 (admin_masjid) | 3 |
| `/app/{tenant}/kelulusan` | 1 (pengerusi) | 3 |
| `/app/{tenant}/penggunaan` | 1 (bendahari) | 3 |
| `/app/{tenant}` (dashboard) | 1 (admin_masjid muat naik) | 1 |
| | **13** | **60** |

Empat guide `minit-saya` = **40%** kerja W2 pada SATU skrin. Membaikinya dahulu memberi kadar
kemajuan tertinggi per unit risiko.

## ⭐ Penemuan yang menentukan bentuk W2

Registri sasaran yang dibina semasa **W1** sudah meliputi hampir setiap kawalan yang W2 perlukan,
kerana guide `screen` dan `workflow` **berkongsi skrin yang sama**:

| Skrin | Sasaran sedia ada dalam `targets.json` |
|---|---|
| minit | `minit-reply` `-body` `-cc` `-priority` `-submit` `-action` · `minit-complete` `-confirm` · `minit-status` (9) |
| rekod | `record-correction` `-reason` `-title` `-submit` · `record-approval` `-approver` `-note` `-submit` · `record-minit` `-body` `-cc` `-priority` `-submit` · `record-move` `-file` `-reason` `-submit` · `record-version` `-file` `-submit` · 6 `record-tab-*` (27) |
| kelulusan | `approval-lulus` `-tolak` `-note` `-password` `-record` `-status` `-submit` (7) |
| fail fizikal | `file-checkout` `-holder` `-due` `-location` · `file-custody` · `regfile-physical` · `file-access-*` (27 `file*`) |
| carian | `search-results` `-result-item` `-result-open` `-filters` `-favourite` (5) |
| storan | `storage-add` `-blocks` `-submit` `-orders` `-usage` (5) |
| retensi/pelupusan | `disposal-candidates` `-batches` `-actions` · `retention-*` (6+) |

**Maksudnya: W2 dijangka hampir keseluruhannya kerja MEMETAKAN KATALOG**, bukan menambah atribut
Blade. Implikasi yang mesti disahkan, bukan diandaikan:
1. **Setiap sasaran yang dipetakan mesti WUJUD dalam keadaan LALAI halaman** (pelajaran W0: sasaran
   yang hanya wujud bila berdata menghasilkan "Tindakan belum tersedia" untuk majoriti pengguna).
2. **Setiap sasaran mesti wujud pada route guide itu** — beberapa sasaran `record-*` hidup pada
   halaman **butiran** (`/records/{id}`), bukan senarai. `currentGuide()` memadan awalan route
   (`HelpCatalog.php:117-122`), jadi ini sah — tetapi gate mesti memandunya sebagai aliran.
3. Jika tiada perubahan Blade → **nama aset tidak akan berubah**; bukti deploy mesti bergantung
   pada kandungan imej + ImageID (pelajaran Deploy 2), bukan nama aset.

## 60 langkah — pemetaan calon (untuk disahkan satu per satu semasa membina)

### `/minit-saya` — 24 langkah (4 guide)
`pengerusi` #4 semak rekod → `record-tab-info` · #5 kembali ke senarai → `minit-status` ·
#6 penerima susulan → `minit-reply-cc` · #7 tulis jawapan → `minit-reply-body` ·
#8 hantar → `minit-reply-submit` · #9 tanda selesai → `minit-complete` ·
#10 sahkan status → `minit-status`
`nazir` #3 balasan → `minit-reply-body` · #4 penerima+hantar → `minit-reply-cc`/`-submit` ·
#7 keputusan → `approval-lulus` · #8 kata laluan+nota → `approval-password`/`-note` ·
#9 status akhir → `approval-status`
`ketua_imam` #3 semak rekod+lampiran → `record-tab-attachments` · #4 jangan muat turun →
justifikasi (langkah amaran, bukan tindakan UI) · #5 edarkan susulan → `minit-reply` ·
#6 catatan → `minit-reply-body` · #7 selesaikan kerja → justifikasi (kerja luar sistem) ·
#8 tanda selesai+status → `minit-complete`/`minit-status`
`ajk` #3 kandungan dibenarkan → `record-tab-info` · #4 akses ditolak → justifikasi ·
#5 catat tindakan → `minit-reply-body` · #6 hantar → `minit-reply-submit` ·
#7 tanda selesai → `minit-complete` · #8 sahkan status → `minit-status`

### `/records` — 14 langkah (3 guide)
`admin_masjid.betulkan` #3 → `record-tab-audit` · #4 → `record-correction` ·
#5 → `record-correction-reason` · #6 → `record-correction-title` · #7 → `record-correction-submit`
`setiausaha` #3 → `record-approval-approver` · #4 → `record-approval-submit` ·
#5 → `record-correction-reason` · #6 → `record-correction-submit`
`bendahari` #3 → `record-tab-info` · #4 → `record-minit`/`record-approval` ·
#5 → `record-minit-cc` · #6 → `record-minit-body`/`-priority` · #7 → `record-minit-submit`

### `/registry-files` — 8 langkah
#4 → `regfile-physical` · #5 → `file-checkout` · #6 → `file-checkout-holder` ·
#7 → `file-checkout-location`/`-due` · #8 → `file-checkout-submit`(sahkan wujud) ·
#9 → `file-custody` · #10 → (catatan+simpan; sahkan sasaran) · #11 → justifikasi (label fizikal
di luar sistem)

### Baki 14 langkah
`carian` #4 → `search-results` · #5 → `search-result-open` · #6 → `record-tab-*` ·
#7 → justifikasi (amaran baca-sahaja)
`retensi` #6 → `disposal-candidates` · #7 → `disposal-warning` · #8 → `disposal-batches`
`kelulusan` #3 → `approval-lulus` · #4 → `approval-password`/`-note` · #5 → `approval-submit`
`penggunaan` #4 → `storage-blocks` · #5 → `storage-add`/`storage-orders` · #6 → `storage-submit`
`dashboard` (muat naik #8) → `inbox-*` (sasaran Peti Masuk sedia ada)

⚠️ Langkah yang dicadangkan sebagai **justifikasi** (bukan sasaran) ialah arahan **amaran/kerja
luar sistem** — cth "jangan muat turun jika tidak diperlukan", "selesaikan kerja sebenar",
"kemas kini label fizikal". Setiap satu memerlukan `reason` + `since` bertarikh dalam manifest
(§7.3), dan **bilangannya mesti kecil serta disebut satu per satu dalam laporan fasa** — bukan
jalan keluar pukal. Anggaran awal: **≤6 daripada 60**.

## ✅ Prasyarat setiap sasaran — DISAHKAN daripada registri, bukan diandaikan

Registri `targets.json` merekod medan **`state`** untuk setiap sasaran, jadi soalan "adakah ia
wujud dalam keadaan lalai?" dijawab oleh data, bukan tekaan. Tiga kelas prasyarat muncul:

| Kelas `state` | Maksud untuk tour | Skrin |
|---|---|---|
| `LALAI` (`-`) | sasaran ada sebaik halaman dibuka | **`carian` (5/5)** |
| `jadual tidak kosong (baris pertama)` | perlu ≥1 baris data | `minit-saya`, `kelulusan` |
| `modal:X terbuka` / `detail:Y` | perlu tindakan pengguna dahulu | `records` (28), `registry-files` (18), baki `minit-saya` |

**Kesan reka bentuk:** langkah W2 yang menyasarkan `modal:`/`detail:` MESTI datang selepas langkah
yang membukanya — iaitu tepat bentuk `wait_for_user` yang sudah ada. Jadi urutan katalog semasa
serasi; yang berubah hanyalah **sasaran** setiap langkah.

**Data demo mencukupi** (ditambah semasa W1 `DemoSeeder::seedTugasanDemo`): 1 minit, 1 kelulusan,
1 fail hibrid + pergerakan + geran akses. Itu memenuhi ketiga-tiga prasyarat "jadual tidak kosong"
dan `medium fizikal/hibrid`. ⚠️ Sahkan semula dengan kiraan DB sebelum mempercayai gate hijau
(pelajaran W1: "skrin tanpa data menjadikan gate hijau palsu").

**Dua sasaran `reserved` akan menjadi `active`** apabila W2 merujuknya — `record-minit-action`,
`file-checkout-holder`. Ujian yatim dua-hala akan menguatkuasakan pertukaran itu; ia mesti
dilakukan dalam commit yang SAMA.

## Prasyarat gate yang sudah diketahui

- Guide `workflow` dipandu **koreografi** dalam `guidance-full.spec.js` (bukan `driveFlowGuide`);
  `CHOREOGRAPHED` kini meliputi `workflow.admin_masjid.muat-naik-…`,
  `workflow.setiausaha.klasifikasikan-…` dan `public.registration`. **10 guide W2 yang lain
  memerlukan laluan pemanduan** — sama ada koreografi baharu atau pemandu aliran generik.
- Ujian mesti mengassert `data-guide-id` = id yang diminta SEBELUM menilai langkah (17 route
  dikongsi; §7.1).
- Denominator beku mesti dikemas dalam **KEEMPAT-EMPAT** penjaga bila kiraan bergerak:
  `build-manifest.mjs` · `validate-plan-manifest.mjs` · `PlanManifestTest.php` ·
  `aggregate-guidance-coverage.mjs`.

**Baseline kemajuan sebelum W2:** placeholder katalog **0** · tindakan-generik **60** ·
mobile defect **0** · Pest 549✓/1 skip · gate `83/473/190 SET`.

---

## 🔑 PROSEDUR PELAKSANAAN — dipelajari daripada percubaan pertama (jangan ulang kesilapan)

Percubaan pertama saya membaiki **satu** guide (`workflow.audit…`, 4 langkah) dan menjana semula
manifest. Pembina menolaknya:

```
FAIL: wave W2.guides 12 ≠ 13
```

**Sebab:** wave **dikira** daripada katalog (`W2` = guide `workflow` dengan **≥1** langkah tindakan
bersasar generik; `W4` = baki `workflow`). Sebaik guide `audit` kehilangan keempat-empat langkah
aksi-generiknya, ia **berpindah sendiri** W2 → W4, jadi partition beku `2/28/13/1/1/35/3` pecah.

**Implikasi yang menentukan bentuk kerja:**
1. **W2 mesti dilaksana sebagai SATU batch**, bukan guide-demi-guide. Setiap guide yang siap
   memindahkan dirinya keluar dari W2 dan akan memaksa kemas kini denominator beku dalam
   **KEEMPAT-EMPAT** penjaga setiap kali (`build-manifest.mjs` · `validate-plan-manifest.mjs` ·
   `PlanManifestTest.php` · `aggregate-guidance-coverage.mjs`).
2. **Keadaan akhir W2 yang dijangka:** `W2.guides 13 → 0` dan `W4.guides 1 → 14`
   (`W4.steps 13 → 158`). Jumlah **83/473 tidak berubah** — hanya partition berpindah. Ini corak
   yang SAMA seperti W1 (`W1 27/135 → 0/0`, `W1→W3 29/151`), jadi ia dijangka, bukan kejutan.
3. **Turutan yang betul:** (a) edit kesemua 13 guide → (b) jana semula manifest (akan gagal pada
   partition) → (c) kemas jadual wave beku dalam empat penjaga **dengan sebab bertulis, dalam
   commit yang sama** → (d) jana semula `HELP-TARGETS.md` → (e) gate penuh 3 shard + agregator →
   (f) CI → (g) deploy.

## ⚙️ Fakta perkakas yang disahkan (elak kerosakan fail)

- **Edit `guides.json` MESTI menggunakan `JSON.stringify(d, null, 2) + "\n"` (Node).** Diuji
  round-trip: pengekod itu menghasilkan fail **identik bait-untuk-bait**; `json_encode` PHP dengan
  `JSON_PRETTY_PRINT` (4 ruang) menghasilkan **366 KB berbanding 300 KB** = diff seluruh fail.
  Dengan kaedah betul, edit 4 langkah memberi diff **12 sisipan / 12 pemadaman** sahaja.
- **Definisi berkuasa "generik" ialah DUA sasaran sahaja**: `GEN = {'page-primary','page-content'}`
  (`tools/build-manifest.mjs:25`, sepadan `GENERIC_TARGETS` dalam `resources/js/help.js:8`).
  Jadi `page-actions`, `page-header`, `sidebar` dikira **spesifik** — `page-actions` berguna untuk
  langkah amaran ("jangan ubah rekod") tanpa perlu menyentuh kiraan `wait_for_user` beku.
- **Langkah boleh mengisytiharkan `route` sendiri** (medan `route` per langkah; guide muat-naik
  menggunakannya merentas 4 halaman). Langkah yang mewarisi route kekal pada halaman semasa —
  itulah sebab langkah butiran `betulkan-rekod` (#3–#7) berfungsi tanpa route eksplisit.
- **Penjaga F5 anti-duplikasi tajuk BERFUNGSI** dan ia menangkap saya: tajuk "Semak jumlah hasil"
  adalah salinan verbatim arahannya sendiri → `HelpCatalogQualityTest §6.5 #2` merah. Tajuk mesti
  **meringkaskan**, bukan mengulang, arahan.

# INVENTORI F6-W3 — baki generik shard `screen`

**Tarikh:** 5 Ogos 2026 · **Asas:** `56caac7` (produksi = imej `81b526f`)
**Prosedur:** §7.2 "Setiap gelombang" langkah 1 (inventori sebelum kod).

---

## 1. Skop sebenar W3 — DIUKUR, bukan disalin daripada jadual beku

Jadual beku §7.2 menulis **W3 = 1 guide / 11 langkah** (`screen.klasifikasi-peti-masuk`).
Angka itu ialah keadaan pada **2 Ogos**. `waveOf()` ialah fungsi **dinamik** ke atas katalog
(`build-manifest.mjs:131-138`): guide `screen` jatuh ke **W1** jika ia masih mempunyai langkah
tindakan bersasar generik, dan ke **W3** jika tidak. F6-W1 membaiki kesemua 27 guide berbaki,
jadi **seluruh shard `screen` kini berada dalam W3**.

Pengiraan bebas (skrip yang sama seperti validator, dijalankan atas katalog semasa
`2026.08.05.1`):

| Wave | guide | langkah | generik | generik **dan** `wait_for_user` |
|---|---:|---:|---:|---:|
| W0 | 2 | 10 | **0** | 0 |
| W1 | 0 | 0 | 0 | 0 |
| W2 | 0 | 0 | 0 | 0 |
| **W3** | **29** | **151** | **9** | **0** |
| W4 | 14 | 158 | 82 | 0 |
| W5 | 35 | 146 | 144 | 0 |
| W6 | 3 | 8 | 2 | 0 |
| | **83** | **473** | **237** | **0** |

**Guide asal W3 (`screen.klasifikasi-peti-masuk`) TIADA kerja** — disahkan dengan membaca
katalog: 11/11 langkah bersasar spesifik (`inbox-classify`, `classification-source`,
`classification-metadata` ×3, `classification-file` ×2, `classification-minit` ×2,
`classification-review`, `classification-submit`) dengan 11 tajuk bermakna. Ia memang guide
**model** yang audit sebut sebagai contoh betul (RR penemuan #3).

**Kerja sebenar W3 = 9 langkah generik yang tinggal dalam shard `screen`.**
Kesemuanya `wait_for_user: false` — iaitu metrik keutamaan `action_steps_with_generic_target`
sudah **0** dan kekal 0 sepanjang W3. Yang tinggal ialah metrik `generic_declared`.

---

## 2. Sembilan langkah — pemetaan calon satu per satu

### (a) `screen.muat-naik-dokumen#4` → **boleh dinaikkan `specific`**

```
route  : /app/{tenant}/peti-masuk        roles: admin_masjid, setiausaha
tajuk  : "Sahkan toast dan baris baharu"
arahan : "Sahkan toast bilangan dokumen dan baris baharu muncul dalam Peti Masuk."
sasaran: page-content
```

Objek yang arahan ini rujuk — "**baris baharu** … dalam Peti Masuk" — wujud sebagai elemen
sebenar: baris pertama jadual Peti Masuk. Jadual disusun `defaultSort('created_at','desc')`
(`InboxTable.php:41`), jadi baris pertama **ialah** dokumen yang baru dimuat naik.

**Sasaran baharu:** `inbox-record` pada sel lajur `title` ("Tajuk / Fail") baris pertama,
melalui corak `baris1()` yang sudah terbukti (`InboxTable.php:91`, dipakai `inbox-scan-status`
sejak W2; corak sama `MinitsTable::baris1()` / `ApprovalsTable::baris1()`). Memo statik
menjamin **satu** elemen sahaja memegang atribut = syarat keunikan G2.

Langkah 5 guide ini menyasar `inbox-classify` (butang) dan langkah 4 kini menyasar sel tajuk
baris yang sama — dua elemen berbeza, jadi tiada pertindihan.

### (b) `screen.tetapkan-kata-laluan#4` → **`not-applicable`**

```
route  : /app/{tenant}/profil            roles: 8 (semua)
tajuk  : "Uji pada sesi baharu"
arahan : "Uji pada sesi baharu; jangan kongsi kata laluan."
```

Tindakan yang diminta berlaku **di luar skrin semasa** (log keluar → log masuk semula dalam
sesi baharu). Sasaran `active` yang wujud pada `/profil` disenaraikan penuh daripada registri:
`profil-akaun`, `profil-kata-laluan`, `profil-notifikasi`, `profil-notifikasi-save`,
`profil-password-confirm`, `profil-password-save`, `profil-ujian`. **Tiada satu pun** mewakili
"uji pada sesi baharu" — `profil-ujian` ialah butang **"Hantar Notifikasi Ujian"**
(`ProfileActions.php:47-50`, disahkan dengan membaca fail), bukan ujian sesi.

⛔ **Ditolak secara sedar:** menyasarkan butang log keluar. Ia akan menggalakkan pengguna
menekan log keluar **di tengah tour** — memusnahkan sesi dan tour itu sendiri. Sorotan yang
salah lebih buruk daripada sorotan generik.

Ini tepat takrifan §7.3 `not-applicable`: "langkah/guide konsep yang memang tiada UI tunggal".
Ia langkah **akhir** guide dan `wait_for_user: false`, jadi kitaran guide tetap tamat (G4).

### (c) `screen.tanda-tindakan-minit-selesai#5` → **`not-applicable`**

```
route  : /app/{tenant}/minit-saya        roles: 5
tajuk  : "Makluman pengirim"
arahan : "Pengirim dimaklumkan apabila semua penerima tindakan selesai."
```

Ini **pernyataan tentang kesan sistem kepada pengguna LAIN** (pengirim menerima notifikasi),
bukan arahan kepada pengguna semasa. Notifikasi itu tidak dirender pada `/minit-saya` bagi
penerima. Langkah 4 sudah menyasar `minit-status` untuk perubahan yang penerima **boleh**
lihat; menyasarkan elemen sama sekali lagi hanya akan mengulang sorotan yang sama.
`not-applicable`, langkah akhir, `wait_for_user: false` → kitaran tamat.

### (d) `screen.viewer-dokumen#1–6` → **`generic-justified`** (6 langkah)

```
route guide : /app/{tenant}/records      roles: 8 (semua)
1 Navigasi halaman     4 Carian teks dokumen
2 Lompat ke halaman    5 Cetak metadata
3 Kawalan zum          6 Muat turun terkawal
```

Guide berjalan pada senarai rekod, tetapi kesemua enam langkah menerangkan kawalan yang berada
pada **halaman berasingan** `/viewer/{media}` (`routes/web.php:62` →
`resources/views/document-viewer.blade.php`).

**Disahkan dengan membaca fail, bukan diandaikan:** halaman itu memuatkan
`@vite(['resources/css/app.css','resources/js/document-viewer.js'])` sahaja
(`document-viewer.blade.php:8`) — **tiada Livewire, tiada `help.js`**, jadi runtime bantuan
tidak wujud di sana dan tour tidak boleh berjalan. Komen di baris 33 fail itu sendiri
merekodkannya.

Keenam-enam sasaran DOM **sudah dipasang** semasa W1 dan ditanda `reserved` dalam registri
dengan `state: "halaman viewer TIADA runtime bantuan — rujuk nota F7"`:
`viewer-page-prev` (:39) · `viewer-page-input` (:40) · `viewer-zoom-in` (:45) ·
`viewer-find` (:47) · `viewer-print` (:50) · `viewer-download` (:51).

**Keputusan skop (direkod, bukan senyap):** memasang runtime bantuan pada `/viewer/{media}`
bermakna menambah Livewire + `help.js` + entri Vite pada halaman kendiri, menukar `route`
guide, dan memperluas manifest `role_routes` beku kepada route baharu. Itu **bukan** kerja
"memetakan sasaran" W3, dan **pelan tidak pernah menugaskannya** — §8.4 F7 hanya menugaskan
butang viewer *disabled* + `pageInput.max`. Jadi ia dicatat sebagai **cadangan F7 bertarikh**
dalam allowlist, bukan diselesaikan diam-diam mahupun ditinggalkan tanpa rekod.

---

## 3. Jurang yang inventori ini dedahkan (bukan dalam skop asal W3)

### 3.1 `generic-justified` belum pernah bermakna apa-apa

§7.2 gate registri **(f)** menuntut: *"`generic-justified` hanya melalui allowlist bersebab +
bertarikh"*. Mekanisme itu **tidak wujud**. `build-manifest.mjs:203-207` memberi SETIAP langkah
generik satu sebab yang dijana automatik:

```
Baseline pra-F6: sasaran generik sedia ada; penambahbaikan dijadualkan ${wave} (F6)
```

Kesannya: sebaik sesuatu wave ditutup, langkahnya masih berkata "penambahbaikan **dijadualkan**
wave ini" — ayat yang bercanggah dengan dirinya sendiri, dan gate tidak dapat membezakan
"dijustifikasikan" daripada "belum dibuat". W3 mesti membina mekanisme itu, kalau tidak
penutupan W3 tidak bermakna apa-apa.

### 3.2 G2 tidak berkuat kuasa untuk guide berkoreografi

`assertStepPopover()` (`guidance-full.spec.js:194`) mengassert `data-help-target` elemen aktif
— tetapi ia hanya dipanggil oleh `driveGenericSteps()` dan `driveFlowGuide()`.
`driveChoreographedRange()` (:832) **hanya mengundi nombor langkah**; ia tidak pernah memeriksa
sasaran. `screen.muat-naik-dokumen` menggunakan laluan itu (:1105).

Maksudnya: menukar langkah 4 kepada `specific` tanpa menutup jurang ini bermakna mendakwa
`specific` **tanpa bukti gate** — melanggar disiplin "ujian yang tidak pernah gagal ialah ujian
palsu". Perekam dalam halaman sudah merakam `aktif: [data-help-target…]` setiap peralihan
(:113-115), jadi buktinya sudah dikumpul; yang tiada hanyalah assertion ke atasnya.

---

## 4. Keluaran W3 yang dirancang

| # | Kerja | Fail |
|---|---|---|
| A1 | Sasaran `inbox-record` pada sel tajuk baris pertama | `InboxTable.php` |
| A2 | Entri registri `inbox-record` (`active`) | `resources/help/targets.json` |
| A3 | `screen.muat-naik-dokumen#4` → `inbox-record`; bump `catalog_version` | `resources/help/guides.json` |
| B1 | Allowlist justifikasi per-langkah (8 entri) | `resources/help/step-justifications.json` **(baharu)** |
| B2 | Manifest baca allowlist; gagal pada entri yatim/basi | `tools/build-manifest.mjs` |
| B3 | Penjaga: setiap langkah generik dalam wave TERTUTUP mesti dijustifikasikan | `validate-plan-manifest.mjs`, `PlanManifestTest.php` |
| C1 | Ujian render: `inbox-record` unik | `tests/Feature/Help/W3TargetRenderTest.php` **(baharu)** |
| C2 | Ujian allowlist: skema · yatim dua hala · liputan W3 | `tests/Feature/Help/StepJustificationTest.php` **(baharu)** |
| C3 | G2 untuk guide berkoreografi | `e2e/guidance-full.spec.js` |
| D | `docs/HELP-TARGETS.md` dijana semula | (skrip) |

**Ramalan metrik selepas W3** (ditulis SEBELUM kerja, untuk disemak selepasnya):

```
generic_declared            237 -> 236     (satu langkah dinaikkan)
action_steps_generic_target   0 ->   0     (tiada perubahan — sudah ditutup W2)
placeholder_titles            0 ->   0
wait_for_user               172 -> 172     (tiada label salah dikesan dalam 9 langkah ini)
W3 generik                    9 ->   8 dan KESEMUANYA dalam allowlist
struktur 83/473 dan wave guides/steps  TIDAK berubah
```

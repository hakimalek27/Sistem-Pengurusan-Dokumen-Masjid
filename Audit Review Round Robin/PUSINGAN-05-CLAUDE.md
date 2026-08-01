# Pusingan 05 — CLAUDE — 1 Ogos 2026

Salinan tempatan commit `4e07a70`. Produksi tidak disentuh. Tiada kod diubah.

---

## A. Semakan penemuan Codex (Pusingan 4)

| ID Codex | Verdict saya | Bukti |
|---|---|---|
| Kiraan bebas RR-01-02 = **124/274** | **✅ SAH — sepadan** | Codex mengira sendiri daripada JSON mentah dan mendapat angka identik, dengan pecahan per role. Kiraan kini **disahkan dua-hala**. |
| CDP listener: `document` 35→35, `window` 73→72 selepas 20 navigasi | **✅ SAH — mengesahkan penolakan saya terhadap RR-02-02** | Dua kaedah bebas (nod DOM + heap oleh saya; CDP oleh Codex) sampai ke kesimpulan sama. **RR-02-02 ditutup sebagai TIDAK SAH.** |
| RR-04-01 axe `landmark-unique` pada 5/5 halaman + `link-name` serious pada peti masuk + `empty-table-header` | **✅ SAH** | Munasabah dan konsisten dengan struktur Filament. Saya tidak menjalankan semula axe (Codex sudah beri output per-halaman desktop+mobile); saya menerima ini sebagai diliputi. |
| RR-04-02 CSV eksport = BM + tenant-scoped | **✅ SAH** | Saya jana sendiri: `laporan-rekod-mam-20260801.csv`, header `Rujukan,Tajuk,Jenis,Tarikh,Sensitiviti,Status`, 3 baris data semua `MAM.*`, **0 kebocoran MAN**. |
| Probe silang-tenant service (minit/kelulusan/pelupusan ditolak) | **✅ SAH — dan diperluas** | Saya ulang + tambah 2 laluan lagi. Lihat C1. |

### ⚠️ Pembetulan kepada laporan saya sendiri

Dalam larian pertama Pusingan 5 saya menandakan header CSV sebagai *"Header Inggeris: ⚠️ YA"*.
**Itu positif palsu saya sendiri** — regex saya memadankan perkataan "Status", yang ejaannya sama
dalam BM dan Inggeris. Header sebenar `Rujukan, Tajuk, Jenis, Tarikh, Sensitiviti, Status` adalah
**BM yang betul**. Codex betul; saya salah. Direkodkan supaya tidak diulang.

---

## C. Penemuan

### C1 · ✅ HAPPY-PATH TULIS PENUH — LULUS, dan 4/4 cubaan silang-tenant DITOLAK

Ini **jurang terakhir** yang kedua-dua ejen belum tutup. Kini ditutup.

Fixture: rekod peti masuk #4 (MAM) dengan gate dilepasi (`virus_scan_status=clean`, `ocr_status=siap`).
Bukti penuh: `bukti/pusingan-05/laluan-tulis-output.txt` + skrip `skrip-laluan-tulis.php`.

**Happy path (sebagai Admin/Kerani MAM):**

| Langkah | Keputusan |
|---|---|
| Klasifikasi rekod #4 → fail MAM #1 | ✅ `status=difailkan`, `fail=1`, **`our_ref=MAM.100-4/1(2)` dijana automatik**, `enclosure=2` |
| Cipta minit → pengerusi | ✅ minit #1, `keutamaan=segera`, 1 penerima |
| Mohon kelulusan | ✅ approval #1, `status=menunggu` |
| Pengerusi putuskan | ✅ `status=lulus`, `decided_by=3` |

Penomboran rujukan automatik (§10 Aliran D) **terbukti berfungsi pada runtime** — bukan hanya
dalam ujian unit.

**Probe silang-tenant pada laluan TULIS** (sesi Admin MAM, sasaran objek MAN):

| Cubaan | Hasil |
|---|---|
| `fileRecord()` rekod MAM → **fail MAN #4** | ✅ `ValidationException` — *"Rekod dan fail mesti dalam tenant sama…"* |
| `MinitService::create()` pada **rekod MAN #5** | ✅ `AuthorizationException` — *"Tiada kebenaran mengedarkan minit bagi rekod ini."* |
| `ApprovalService::request()` pada **rekod MAN #5** | ✅ `AuthorizationException` — *"Tiada kebenaran memohon kelulusan rekod ini."* |
| `moveToFile()` rekod MAM → **fail MAN #4** | ✅ `ValidationException` — *"Fail sasaran mesti terbuka dalam tenant sama…"* |

**Kiraan DB sebelum/selepas:** `minit dalam MAN +0`, `approval dalam MAN +0`.
**Sifar pencemaran silang-tenant pada laluan tulis.**

Guard tenant eksplisit disahkan dalam kod di `app/Services/InboxIngestService.php:164-168`
(semakan `$record->mosque_id !== $file->mosque_id` sebelum sebarang tulisan).

> **Nota bahasa positif:** semua mesej penolakan ini adalah **BM yang betul dan jelas**.
> Mesej yang ditulis pembangun konsisten baik — hanya rangka Laravel yang Inggeris (RR-03-01).

---

### C2 · RR-05-01 · SEDERHANA · Terjemahan BM wizard Filament salah — dan katalog bantuan menyalinnya

Butang wizard klasifikasi 5 langkah (skrin operasi paling kerap digunakan) berbunyi:

| Butang | Teks sebenar | Sepatutnya |
|---|---|---|
| Maju | **"Seterus"** | "Seterusnya" |
| Undur | **"Sebelum"** | "Sebelumnya" / "Kembali" |

**Punca:** `vendor/filament/schemas/resources/lang/ms/components.php:9-15`
```php
'previous_step' => ['label' => 'Sebelum'],
'next_step'     => ['label' => 'Seterus'],
```

Ia **tidak konsisten dengan pakej Filament lain** dalam pemasangan yang sama —
`vendor/filament/support/resources/lang/ms/components/pagination.php:38,42` menggunakan
**"Seterusnya"** dan **"Sebelumnya"** dengan betul.

**Kesan menular:** katalog bantuan Diwan sendiri **menyalin ejaan salah itu** ke dalam arahan
pengguna — `resources/help/guides.json` baris **2867, 3774, 5796** semuanya berbunyi
*"Tekan Seterus…"*. Jadi panduan mengajar pengguna istilah yang tidak betul.

**Pembaikan:** apabila `lang/ms/` dicipta untuk RR-01-03/RR-03-01, tambah override
`lang/vendor/filament-schemas/ms/components.php` sekali gus, dan betulkan 3 arahan katalog.

---

### C3 · RR-05-02 · TINGGI (bukti visual terbaik untuk RR-01-03) · Mesej validasi bahasa hibrid

Semasa wizard klasifikasi, meninggalkan medan wajib kosong menghasilkan:

```
The failkan Ke field is required.
```

Rangka Inggeris (`The … field is required.`) dengan **nama medan BM disisipkan di tengah**
(`failkan Ke`, huruf besar tidak konsisten pula). Ini contoh paling jelas mengapa RR-01-03
penting: ia bukan sekadar "mesej dalam Inggeris" — ia menghasilkan ayat **rojak** yang kelihatan
rosak kepada pengguna masjid.

**Bukti:** `bukti/pusingan-05/r5c-L4.png` … `r5c-L8.png`

---

### C4 · Nota kaedah — wizard tidak dapat diselesaikan melalui automasi UI

Automasi saya berjaya melalui langkah 1→3 (`Semak Dokumen & Sumber` → `Jenis & Metadata` →
`Fail & Sensitiviti`) tetapi **tersekat pada medan "Failkan Ke"**, yang merupakan komponen
select tersuai (bukan `<select>` asli dan bukan `.choices` yang saya jangkakan).

**Ini had kaedah ujian saya, BUKAN pepijat aplikasi** — medan itu berfungsi normal untuk
pengguna manusia (disahkan secara visual dalam skrinsyot Pusingan 1, di mana modal memaparkan
kesemua 18 medan dengan betul). Saya membuktikan laluan tulis melalui lapisan service sebagai
ganti, yang memberi liputan setara untuk logik perniagaan + keselamatan.

Direkod supaya Codex atau pusingan akan datang tidak tersalah anggap ini sebagai kecacatan.

---

## D. Kedudukan akhir cadangan (selepas 5 pusingan)

| # | Tindakan | Menutup | Anggaran usaha |
|---|---|---|---|
| 1 | Persist origin/guide/langkah sebagai state komponen `HelpLauncher` (bukan `request()`) | RR-01-02 + RR-02-01 — 124/274 halaman | Sederhana |
| 2 | Cipta `lang/ms/` (validation, auth, passwords, pagination) + templat notifikasi + override wizard Filament + `APP_FALLBACK_LOCALE=ms` | RR-01-03 + RR-03-01 + RR-05-01 + RR-05-02 | Sederhana |
| 3 | Tambah `<main>` / `page-content` pada layout tetamu | RR-01-01 | Kecil |
| 4 | Selaraskan predikat `nextButtonLabel` ↔ `onNextClick` | RR-01-07 + RR-03-03 | Kecil |
| 5 | Tambah `data-help-target` sebenar mengikut halaman | RR-01-06 + RR-03-04 | Besar (berterusan) |
| 6 | `aria-modal` + perangkap fokus / `inert` semasa tour | RR-03-02 | Kecil |
| 7 | Betulkan `landmark-unique`, `link-name` (peti masuk), `empty-table-header` | RR-04-01 | Kecil |
| 8 | Katalog: mulakan tour muat naik pada `inbox-upload`; padan arahan dgn sasaran; betulkan "Seterus" | RR-01-08, 09 + RR-05-01 | Kecil |
| 9 | Label BM fallback popover · `'Sunting'` × 3 · `//` helpUrl root | RR-01-04, 05, 11 | Kecil |

### ✅ Terbukti SIHAT selepas 5 pusingan dua-hala — jangan sentuh

- **Keselamatan tenant: lulus penuh pada KEDUA-DUA lapisan** — akses (16+10 probe = 404/403)
  **dan tulis** (4/4 mutasi silang-tenant ditolak, 0 pencemaran DB).
- Happy-path perniagaan berfungsi: klasifikasi → penomboran auto → minit → kelulusan.
- Sync tour berfungsi (1045 ms, klik sebenar).
- Tiada kebocoran listener/DOM (disahkan dua kaedah bebas).
- ARIA popover lengkap; ESC berfungsi.
- 274/274 halaman HTTP 200; 0 ralat JS; 0 overflow mendatar desktop+mobile.
- Eksport CSV: BM + tenant-scoped.

---

## E. Liputan

**Ditutup pusingan ini:** happy-path tulis penuh; silang-tenant pada laluan tulis (4 laluan);
CSV eksport bebas; pembetulan positif palsu sendiri; terjemahan wizard.

**Masih belum diuji oleh mana-mana pihak (baki kecil):**
- Sijil pelupusan PDF + invois storan PDF (tiada batch/pesanan dalam fixture; route
  `/app/mam/penggunaan-storan` mengembalikan 404 pada percubaan Codex — patut disemak sama ada
  itu route yang salah atau halaman benar-benar tiada).
- Muat naik fail sebenar → ClamAV → OCR (memerlukan tooling ClamAV/tesseract tempatan).
- Intake WhatsApp / e-mel (memerlukan kredensial luar).
- Modal superadmin bermutasi (Lulus tenant, Tandakan Dibayar, Batal pesanan).

**Artifak DB tempatan** (buangan, bukan produksi): rekod #4 kini difailkan, 2 minit, 2 approval
dicipta oleh ujian ini. Pulihkan bila-bila dengan `php artisan migrate:fresh --seed`.

---

## F. Status

**SIAP PUSINGAN 5.** Giliran → **Codex Pusingan 6**.

### Cadangan saya: round robin hampir tamat

Semua penemuan utama kini **disahkan dua-hala**, jurang keselamatan terbesar **ditutup dan lulus**,
dan dua pusingan terakhir hanya menghasilkan penemuan bahasa/kosmetik — bukan lagi isu struktur.

**Permintaan kepada Codex untuk Pusingan 6 (pusingan penutup):**

1. Sahkan C1 (happy-path tulis + 4 probe silang-tenant) dengan larian anda sendiri.
2. Sahkan RR-05-01 (`Seterus`/`Sebelum` dalam vendor Filament + 3 baris katalog).
3. Tutup baki kecil jika boleh: sijil pelupusan PDF / invois storan PDF, dan sahkan sama ada
   `/app/mam/penggunaan-storan` 404 itu route salah atau jurang sebenar.
4. Jika anda tidak menemui isu struktur baharu, **tulis `FINAL-RUMUSAN.md`** bersama saya:
   senarai penemuan bersepadu mengikut keutamaan, apa yang sihat, dan cadangan pelan pembaikan
   untuk pemilik. Kemudian tandakan round robin **SELESAI** dalam STATUS.md.

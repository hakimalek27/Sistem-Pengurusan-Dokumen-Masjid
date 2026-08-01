# Pusingan 01 — CLAUDE — 1 Ogos 2026

**Sistem:** Diwan (SPDM) · commit `4e07a70` (local = origin = server)
**Skop diminta pemilik:** audit A–Z semua workflow/halaman/butang sebagai penguji sebenar,
desktop + mobile, fokus khas pada penyelarasan (sync) Pembantu Bantuan. Tiada perubahan kod.

---

## A. Semakan penemuan pusingan sebelumnya

Tiada — ini pusingan pertama.

---

## B. Skop & kaedah

### Persekitaran yang diaudit

| Persekitaran | Kegunaan | Kaedah |
|---|---|---|
| **Produksi `https://bakwim.my`** | Halaman awam (landing, log masuk, daftar, bantuan, kedua-dua skrin login) | Chrome MCP (interaktif) + Playwright `channel: chrome` (sesi bersih, desktop 1440×900 + iPhone 13) |
| **Salinan tempatan `127.0.0.1:8080`** | Semua panel berautentikasi (9 identiti × semua halaman sidebar) | Playwright klik sebenar + Chrome MCP |

> **Mengapa audit berautentikasi dijalankan pada salinan tempatan, bukan produksi:**
> Penciptaan akaun ujian pada pelayan produksi dan penghantaran borang yang bermutasi
> **disekat oleh pengelas keselamatan** harness. Saya tidak cuba memintasnya.
> Sebagai ganti, salinan tempatan dijalankan pada **commit yang sama (`4e07a70`)** dengan
> `migrate:fresh --seed` + `npm run build`. Bundle bantuan yang dibina tempatan ialah
> **`help-pJkQNpPs.js` — hash yang IDENTIK dengan yang dihidangkan produksi**, jadi tingkah
> laku Pembantu Diwan yang diaudit adalah kod yang sama seperti live.
> Batasan ini disenaraikan jujur dalam Seksyen E.

### Liputan sebenar

- **274 muat halaman** berautentikasi (9 identiti × halaman sidebar × 2 viewport)
- **12 muat halaman awam produksi** (6 halaman × 2 viewport)
- **16 probe silang tenant**
- **311 skrinsyot** disimpan dalam `bukti/pusingan-01/`
- Analisis statik penuh katalog bantuan (83 guide, 473 langkah)

### Bilangan halaman sidebar per identiti (desktop = mobile, konsisten)

| Identiti | Halaman | | Identiti | Halaman |
|---|---:|---|---|---:|
| Superadmin | 12 | | Nazir | 13 |
| Admin / Kerani | 25 | | Ketua Imam | 13 |
| Pengerusi | 17 | | AJK | 13 |
| Setiausaha | 15 | | Juruaudit | 14 |
| Bendahari | 15 | | | |

---

## C. Penemuan

### ✅ Yang TERBUKTI SIHAT (jangan dibaiki)

| Semakan | Keputusan |
|---|---|
| Status HTTP | **274/274 halaman = 200**; 0 halaman gagal |
| Ralat JavaScript / konsol | **0** merentas semua 274 muat halaman |
| Permintaan HTTP ≥400 | **0** |
| Overflow mendatar (desktop **dan** mobile) | **0 halaman** — reka bentuk responsif kukuh |
| Isolasi silang tenant | **16/16 = HTTP 404** (8 role × 2 viewport) |
| Popover tour pada mobile | Muat dalam viewport, tidak keluar skrin |
| Konsistensi role desktop vs mobile | Bilangan halaman identik setiap role |
| **Sync tour selepas tindakan sebenar** | **BERFUNGSI** — lihat nota penting di bawah |

> #### ⚠️ Pembetulan penting kepada kebimbangan pemilik tentang sync
> Pemilik bimbang: *"panduan suruh tekan, tapi bila user dah tekan, dia tak detect — jadi konfius."*
>
> **Diuji secara langsung** (`bukti/pusingan-01/tour/tour-audit.txt`, ujian 1):
> tour `screen.klasifikasi-peti-masuk` → tekan "Buat pada skrin" → panduan mengecil kepada pil
> "Panduan menunggu…" → **klik pengguna sebenar** pada butang *Klasifikasikan* →
> **modal terbuka pada 1045ms dan tour maju ke langkah 2/11 pada 1045ms yang sama.**
>
> Mekanisme pengesanan (MutationObserver + poll 120 ms) **berfungsi dan pantas**.
> Pada percubaan awal saya melalui Chrome MCP ia kelihatan tersangkut — itu **artifak kaedah
> ujian saya** (klik JS sintetik membuka lalu menutup semula modal), bukan pepijat aplikasi.
> Saya sahkan semula dengan klik sebenar sebelum melaporkan. **Ini BUKAN pepijat.**

### 🔴 Penemuan yang perlu tindakan

---

#### RR-01-01 · TINGGI · Tour `public.login` sentiasa gagal dengan mesej ralat palsu

**Lokasi:** `https://bakwim.my/log-masuk` (produksi) · `resources/help/guides.json` → `public.login`

**Apa yang berlaku:** Pengguna kali pertama membuka halaman Log Masuk. Panduan auto-mula, gagal
mencari sasarannya, lalu memaparkan popover **"Tindakan belum tersedia — Kawalan untuk *Masukkan
identiti* tidak kelihatan pada halaman ini. Semak prasyarat atau data yang diperlukan dahulu."**

Halaman itu **berfungsi dengan sempurna**. Mesej itu memberitahu pengguna baharu bahawa sesuatu
rosak pada saat pertama mereka berjumpa sistem.

**Punca akar (disahkan):**
- Kedua-dua langkah `public.login` menyasarkan `page-content` dan `page-primary`.
- `decorateTargets()` (`resources/js/help.js:46-56`) memberikan `page-content` kepada elemen `<main>`.
- **Layout tetamu tidak mempunyai `<main>`** — ia menggunakan `<div class="wrap">`.
  Disahkan langsung pada produksi: satu-satunya `data-help-target` pada `/log-masuk` ialah `help-launcher`.
- `resolveStepElement` → null → `waitForStep` tamat tempoh 2.5s → `showUnavailableGuide()`.

**Bukti:** desktop **dan** mobile, sesi Playwright bersih —
`bukti/pusingan-01/public-audit.json`, `desktop-log-masuk.png`, `mobile-log-masuk.png`.
Bandingkan: `/app/login` dan `/admin/login` **ada** `<main>` + `page-content` (tiada masalah).

**Cadangan:** tambah `<main>` pada layout tetamu, **atau** beri `data-help-target="page-content"`
pada `div.wrap`, **atau** tukar sasaran `public.login` kepada elemen borang sebenar
(seperti yang sudah dibuat dengan betul untuk `public.registration`).

---

#### RR-01-02 · TINGGI · Konteks Pembantu Diwan hilang pada 124/274 halaman (45%) selepas render Livewire

**Lokasi:** `app/Livewire/HelpLauncher.php:60-66, 88-93`

**Apa yang berlaku:** Selepas mana-mana kitaran Livewire (`POST /livewire/update`) — iaitu hampir
setiap interaksi dalam panel Filament — komponen HelpLauncher di-render semula dan **kehilangan
pengetahuan tentang halaman ia berada**:

| Atribut | HTML asal pelayan | Selepas render Livewire |
|---|---|---|
| `data-guide-id` | `public.login` | *(hilang)* |
| `data-auto-start` | `1` | `0` |
| `data-help-url` | `/bantuan?asal=%2Flog-masuk` | `/bantuan?asal=%2Flivewire%2Fupdate` |
| payload panduan JSON | ada | **tiada** |

**Punca akar:** `render()` membaca `request()->path()` dan `request()->query('panduan')`.
Semasa permintaan AJAX Livewire, kedua-duanya merujuk kepada endpoint `livewire/update`,
bukan halaman sebenar. Maka `currentGuide('/livewire/update', …)` → null.

**Impak diukur:** **124 daripada 274 halaman** kehilangan panduan sepenuhnya — termasuk
**kesemua 11 halaman superadmin `/admin/*`** (Masjid, Pengguna, Pesanan Storan, Tetapan Platform,
WhatsApp Platform, Status Sambungan, Tiket Sokongan, Analitik Bantuan, Profil Saya…).
Guide `admin.mosques`, `admin.users`, `admin.storage-orders` dsb. **wujud dalam katalog tetapi
tidak pernah dapat dipaparkan** pada halamannya sendiri.

**Bukti:** `bukti/pusingan-01/crawl/crawl.json` (medan `helpRuntime`); perbandingan HTML mentah
lawan DOM langsung direkodkan pada produksi.

**Cadangan:** jangan bergantung pada `request()` di dalam `render()`. Simpan laluan asal dan
`panduan` sebagai **sifat awam komponen** yang ditetapkan pada `mount()` (Livewire mengekalkannya
merentas kemas kini), atau hantar melalui `#[Url]` / atribut Blade daripada layout.

> Nota: commit `00775ec` ("recover guidance after Livewire transitions") membaiki peralihan
> **dalam** tour. Kehilangan konteks **komponen** ini adalah masalah berasingan yang masih terbuka.

---

#### RR-01-03 · TINGGI · Mesej validasi seluruh sistem dalam Bahasa Inggeris

**Lokasi:** tiada direktori `lang/ms/` dalam repo (hanya `lang/vendor/backup`)

Borang pendaftaran awam produksi memaparkan:
`The name field is required.` · `The state field is required.` · `The code field is required.` ·
`The slug field is required.`

**Punca akar:** `APP_LOCALE=ms` diset pada `.env` produksi (disahkan melalui SSH), tetapi
**tiada fail terjemahan `lang/ms/validation.php`**, jadi Laravel jatuh balik ke rangkaian Inggeris.

Ini melanggar peraturan projek #6 (*"Semua teks UI Bahasa Melayu"*), pada **skrin pertama yang
dilihat pengguna baharu**.

**Bukti:** `bukti/pusingan-01/desktop-daftar.png` (produksi).

**Cadangan:** `php artisan lang:publish` → terjemah `lang/ms/validation.php` (+ `auth.php`,
`passwords.php`, `pagination.php`), dan tetapkan `APP_FALLBACK_LOCALE=ms`.

---

#### RR-01-04 · SEDERHANA · Teks Inggeris dalam popover panduan fallback

**Lokasi:** `resources/js/help.js:395-421` (`showUnavailableGuide`)

Popover "Tindakan belum tersedia" memaparkan butang **`← Previous`** dan teks kemajuan **`1 of 1`**.

**Punca akar:** `showUnavailableGuide()` mencipta instance `driver()` **kedua** yang hanya
menetapkan `doneBtnText: 'Tutup'`. Ia meninggalkan `prevBtnText`, `nextBtnText` dan `progressText`,
jadi Driver.js menggunakan lalai Inggerisnya. Instance utama (baris 469-479) menetapkan
kesemuanya dengan betul dalam BM — jadi ketidakselarasan hanya berlaku dalam laluan fallback.

**Bukti:** desktop + mobile produksi, `public-audit.json` (`EN-LEAK: ["Previous"]`).

**Cadangan:** salin ketiga-tiga label BM ke dalam pemanggilan `driver()` fallback.

---

#### RR-01-05 · SEDERHANA · Butang "Edit" Bahasa Inggeris pada 8 halaman superadmin

**Lokasi:** `/admin/mosques`, `/admin/users`, `/admin/tetapan-platform` (dan lain-lain)

Satu-satunya kebocoran Inggeris yang dikesan dalam UI panel merentas 274 halaman — tetapi ia
konsisten. Sepatutnya "Sunting".

**Bukti:** `crawl.json` medan `enLeak`.

---

#### RR-01-06 · TINGGI (UX) · 95% panduan tidak menunjuk apa-apa yang spesifik

**Lokasi:** `resources/help/guides.json`

Analisis penuh katalog:

| Metrik | Nilai |
|---|---|
| Jumlah guide | 83 |
| Guide yang **setiap** langkahnya menggunakan sasaran generik | **79 (95%)** |
| Jumlah langkah | 473 |
| Langkah menyasarkan `page-primary` | 238 |
| Langkah menyasarkan `page-content` | 205 |
| Langkah dengan sasaran khusus sebenar | **30 (6.3%)** |

`page-content` sentiasa diselesaikan kepada elemen `<main>` — **keseluruhan kawasan kandungan**.

**Kesan yang diperhatikan** (tour `tenant.dashboard`, 4 langkah, `tour-audit.txt` ujian 2):
kesemua empat langkah menyorot kotak yang **identik** — `320,0 1120×1566`. Sorotan tidak
pernah berubah. Pengguna tidak menerima panduan visual langsung; hanya teks popover berubah.

Inilah punca sebenar perasaan pemilik bahawa panduan "tak selari" — bukan kerana ia gagal
mengesan tindakan (ia berjaya), tetapi kerana **ia tidak pernah menunjukkan ke mana hendak melihat**.

Empat guide yang **betul** dan boleh dijadikan model:
`screen.klasifikasi-peti-masuk`, `public.registration`,
`workflow.admin_masjid.muat-naik-semak-dan-klasifikasikan-dokumen-serta-hantar-minit`,
`workflow.setiausaha.klasifikasikan-surat-masuk-dan-edarkan-minit`.

**Cadangan:** utamakan penambahan `data-help-target` sebenar untuk halaman yang paling kerap
digunakan (Peti Masuk, Rekod, Fail, Minit Saya, Kelulusan, Ahli & Peranan, Tetapan Masjid),
mengikut corak yang sudah terbukti pada modal klasifikasi.

---

#### RR-01-07 · SEDERHANA (UX) · Label butang bercanggah dengan kelakuan sebenar

**Lokasi:** `resources/js/help.js:323-333` (`nextButtonLabel`) lawan `:525` (`onNextClick`)

Kedua-dua fungsi memutuskan perkara yang sama menggunakan **kriteria berbeza**:

```js
// label butang — TANPA fallback generik
nextButtonLabel:  !resolveStepElement(next, false)  → "Buat pada skrin"

// kelakuan klik — DENGAN fallback generik
onNextClick:      resolveStepElement(next, GENERIC_TARGETS.has(next.target)) → moveNext()
```

Apabila langkah seterusnya menyasarkan `page-content`/`page-primary` (94% langkah), butang
berlabel **"Buat pada skrin"** — mengisyaratkan pengguna perlu melakukan sesuatu di skrin —
tetapi menekannya **hanya maju ke langkah berikutnya**. Disahkan pada langkah 1
`tenant.dashboard`: label "Buat pada skrin", klik → terus ke langkah 2, tiada tindakan diperlukan.

Ini **tepat** kekeliruan yang pemilik gambarkan: *"dah tekan ke belum? saya kena tekan fungsi
tu dulu, atau tekan next ni?"*

**Cadangan:** jadikan kedua-dua keputusan menggunakan predikat yang sama.

---

#### RR-01-08 · SEDERHANA (UX) · Tour muat naik meminta tindakan pada UI yang tidak dibuka

**Lokasi:** `screen.muat-naik-dokumen` (`tour-audit.txt` ujian 3)

| Langkah | Arahan | Butang | Sorotan |
|---|---|---|---|
| 1/5 | "Pilih atau seret satu/lebih fail yang dibenarkan" | **Saya sudah buat** | `page-content` |
| 2/5 | "Pastikan saiz setiap fail dalam had" | **Saya sudah buat** | `page-content` |
| 3/5 | "Tunggu upload selesai" | **Saya sudah buat** | `page-content` |
| 4/5 | "Sahkan toast bilangan dokumen" | **Saya sudah buat** | `page-content` |
| 5/5 | "Buka Peti Masuk dan semak antivirus, OCR serta sumber UI" | **Buat pada skrin** | **null** |

Tour bermula pada senarai Peti Masuk **tanpa membuka modal muat naik**. Pengguna diminta
"pilih fail" sedangkan tiada pemilih fail di skrin, kemudian mengaku **"Saya sudah buat"**
empat kali berturut-turut. Langkah akhir tiada sasaran langsung (`sorot=null`) tetapi tetap
masuk ke mod menunggu — pil "Panduan menunggu…" yang tidak akan pernah selesai sendiri.

**Cadangan:** mulakan tour ini pada langkah yang menyorot butang **+ Muat Naik Dokumen**
(sasaran `inbox-upload` sudah wujud), gunakan `inbox-upload-modal` untuk langkah dalam modal.

---

#### RR-01-09 · SEDERHANA (UX) · Arahan bercanggah dengan kawasan yang disorot

`tenant.dashboard` langkah 1: *"Semak nama masjid pada panel…"* — nama masjid berada di
**sidebar kiri**, tetapi sorotan meliputi kandungan **kanan** dan sidebar **digelapkan** oleh overlay.

Langkah 4: *"Gunakan menu kiri untuk membuka tugasan"* — sekali lagi menyorot kandungan kanan,
walaupun sasaran `sidebar` **wujud dan tersedia** pada halaman itu.

**Bukti:** `bukti/pusingan-01/tour/tour2-langkah1.png` … `tour2-langkah4.png`.

---

#### RR-01-10 · RENDAH (UX) · Tajuk langkah menduplikasi penerangannya

Tajuk dalam katalog hanyalah placeholder (`"Langkah 1"` × 50, `"Langkah 2"` × 50, …), jadi runtime
menghidrat tajuk daripada klausa pertama arahan. Apabila arahan tiada tanda `;`, tajuk yang
dihidrat menjadi **keseluruhan ayat** — sama dengan penerangan di bawahnya.

**444 daripada 473 langkah (93%)** tiada `;`, jadi hampir setiap popover memaparkan ayat yang
sama dua kali. Disahkan visual pada `tenant.dashboard` langkah 1.

---

#### RR-01-11 · RENDAH · Pautan bantuan landing mengandungi sengkang berganda

`https://bakwim.my/` → pautan Pembantu Diwan = `/bantuan?asal=%2F%2F` (iaitu `//`), kerana
`'/'.request()->path()` menghasilkan `'//'` apabila path ialah root.

---

## D. Cadangan penambahbaikan (tidak dilaksanakan)

Mengikut keutamaan cadangan saya:

1. **Baiki RR-01-02 dahulu.** Ia satu punca akar yang memulihkan panduan pada 45% halaman,
   termasuk keseluruhan panel superadmin. Kesan tertinggi bagi satu pembaikan.
2. **RR-01-03** (`lang/ms/`) — pembaikan kecil, tetapi ia melanggar peraturan bahasa projek
   pada skrin pertama pengguna baharu.
3. **RR-01-01** — tambah `<main>`/`page-content` pada layout tetamu; menghapuskan mesej ralat
   palsu pada halaman log masuk.
4. **RR-01-07** — selaraskan predikat label/kelakuan; menghapuskan kekeliruan "dah tekan ke belum".
5. **RR-01-06** — kerja berterusan: tambah sasaran sebenar mengikut halaman, bermula dengan
   yang paling kerap digunakan. Corak modal klasifikasi sudah membuktikan pendekatannya berkesan.
6. **RR-01-08 / 09 / 10** — pembetulan kandungan katalog, boleh dibuat secara berkelompok.
7. **RR-01-04 / 05 / 11** — pembetulan kecil satu baris.

**Cadangan produk tambahan:** memandangkan sorotan generik tidak memberi nilai visual,
pertimbangkan untuk memaparkan langkah bersasar-generik sebagai **senarai semak dalam panel
bantuan** dan bukan sebagai overlay tour. Overlay hanya sesuai apabila ia benar-benar menunjuk
kepada sesuatu.

---

## E. Liputan — apa yang BELUM diuji (jujur)

| Belum diuji | Sebab |
|---|---|
| Mutasi data pada **produksi** (cipta/edit/padam/hantar) | Disekat pengelas keselamatan harness; saya tidak memintasnya |
| Penghantaran penuh borang pendaftaran awam | Akan mencipta tenant sebenar dalam produksi yang saya tidak boleh bersihkan tanpa akses superadmin |
| Muat naik fail sebenar → ClamAV → OCR → carian | Perlu mutasi |
| Kitaran penuh minit / kelulusan / pelupusan / retensi | Perlu mutasi; halaman disahkan render dengan lajur & aksi betul, tetapi tindakan tidak dilaksanakan |
| Interaksi mendalam modal superadmin (Lulus tenant, Tandakan Dibayar, Batal pesanan) | Perlu mutasi |
| Wizard klasifikasi 5 langkah **melepasi** langkah 1 | Selector skrip saya tidak menemui butang Seterusnya dalam footer modal; modal itu sendiri disahkan terbuka dengan kelima-lima langkah kelihatan |
| Intake WhatsApp / e-mel | Perlu kredensial luar + mutasi |
| Ujian beban / volumetrik | Di luar skop audit fungsi |

**Untuk pusingan seterusnya:** jika pemilik membenarkan penciptaan akaun ujian pada produksi
(skrip sedia: `scratchpad/audit-fixtures.php` mencipta 8 akaun role + pautan magic dalam tenant
buangan `smoke` sahaja), audit mutasi hujung-ke-hujung boleh diselesaikan pada produksi sebenar.

---

## E2. Artifak yang saya cipta pada PRODUKSI (perlu pembersihan pemilik)

Semasa menguji borang "Lapor masalah" di Pusat Bantuan awam produksi, satu tiket sokongan
sebenar telah dihantar dan direkodkan:

| Perkara | Nilai |
|---|---|
| Rujukan tiket | **`SUP-260801-HXQ0DIOL`** |
| Masa | 01/08/2026 08:06 MYT |
| Tajuk | `[AUDIT RR1] Ujian borang lapor masalah awam - sila abaikan` |
| Kategori | Log masuk |
| Lampiran | tiada |
| Status | Baharu |

Ini satu-satunya perubahan data yang saya buat pada produksi. Ia dihantar sebagai orang awam
(tidak berlog masuk) melalui borang yang direka untuk itu. **Tindakan disyorkan:** superadmin
padam/tutup tiket ini melalui `/admin/tiket-sokongan`. Tiada tenant, akaun, rekod atau fail
produksi lain disentuh.

*(Aliran fungsi itu sendiri LULUS: borang menerima input, menjana rujukan, memaparkan
"Laporan diterima. Rujukan: …" dan menyenaraikannya di bawah "Tiket saya".)*

---

## F. Status

**SIAP PUSINGAN 1.** Giliran diserahkan kepada **Codex**.

Bukti: `bukti/pusingan-01/` — 311 skrinsyot, `crawl.json` (308 rekod),
`public-audit.json`, `tour/tour-audit.txt`, `workflow/workflow-audit.txt`.

### Permintaan khusus kepada Codex untuk Pusingan 2

1. **Sahkan atau tolak** setiap penemuan RR-01-01 hingga RR-01-11 dengan bukti anda sendiri.
   Beri perhatian khusus kepada RR-01-02 (kehilangan konteks Livewire) — sahkan pengiraan 124/274
   saya dan periksa sama ada terdapat mekanisme pemulihan yang saya terlepas.
2. **Semak semula kesimpulan "sync BERFUNGSI"** saya. Saya mengujinya pada satu tour
   (`screen.klasifikasi-peti-masuk`). Uji tour bersasar-khusus yang lain
   (`public.registration`, dua guide workflow) untuk mengesahkan ia berlaku secara konsisten.
3. **Liputi yang saya tidak boleh:** semakan kod bagi laluan mutasi — Policy, service, dan
   pengasingan tenant untuk klasifikasi, minit, kelulusan, pelupusan, billing.
4. **Cari yang saya terlepas:** aksesibiliti (fokus papan kekunci dalam tour, ARIA), keadaan
   perlumbaan dalam `help.js`, dan kebocoran bahasa dalam e-mel/notifikasi.

# Pusingan 03 — CLAUDE — 1 Ogos 2026

Semua kerja pada salinan tempatan commit `4e07a70`. Produksi tidak disentuh. Tiada kod diubah
(`git status` bersih kecuali folder audit ini).

---

## A. Semakan penemuan Codex (Pusingan 2) — verdict + bukti saya sendiri

| ID Codex | Verdict saya | Bukti |
|---|---|---|
| RR-02-01 — konteks Livewire tiada pemulihan state | **SAH** (tetapi pendua) | Betul secara teknikal, namun ini punca akar yang **sama** dengan RR-01-02 saya, bukan penemuan berasingan. Digabungkan. |
| RR-02-02 — kebocoran listener/timer pada navigasi berulang | **❌ TIDAK SAH** | Diuji secara empirik: 20 navigasi Livewire. Nod runtime Pembantu Diwan kekal **1**, `data-help-booted` kekal **1**, launcher kekal **1**, **0** popover/overlay/banner tertinggal pada setiap petikan. Heap 14 MB → 19 MB (+5 MB / 20 navigasi = normal SPA). Sentinel `if (runtime.dataset.helpBooted === '1') return;` berfungsi seperti direka. Bukti: `bukti/pusingan-03/r3b-audit.txt` seksyen B. |
| RR-02-03 — aksesibiliti tour belum memadai | **⚠️ SEBAHAGIAN** | Dakwaan ARIA **tidak tepat** — ARIA sebenarnya lengkap: `role="dialog"`, `aria-labelledby="driver-popover-title"`, `aria-describedby="driver-popover-description"`, `aria-live` hadir, butang tutup ada `aria-label="Tutup panduan"` BM. **ESC berfungsi** (popover + overlay ditutup). Yang **memang benar**: tiada perangkap fokus — lihat RR-03-02. |
| RR-02-04 — matriks mutasi: perlindungan kod ada, ujian silang-tenant belum | **SAH pada masa itu — KINI DIUJI DAN LULUS** | Saya jalankan probe runtime sebenar. Lihat seksyen C1. |
| RR-02-05 — kebocoran Inggeris notifikasi (RENDAH, "berpotensi") | **SAH — tetapi TERLALU RENDAH** | Dirender sebenar: **9 daripada 9** notifikasi yang boleh dibina membawa kerangka Inggeris. Dinaikkan kepada TINGGI — lihat RR-03-01. |

**Nota proses:** verdict Codex untuk RR-01-02 berbunyi *"crawl.json Claude merekod 124/274; saya tidak
mengubahnya"* — itu **menerima kiraan saya, bukan mengesahkannya secara bebas**. Kiraan itu masih
belum disahkan oleh pihak kedua. Saya lampirkan data mentah dalam seksyen E supaya Codex boleh
mengiranya sendiri pada Pusingan 4.

---

## B. Skop & kaedah pusingan ini

Empat ujian empirik yang **tidak dijalankan** oleh mana-mana pihak sebelum ini:

1. Probe mutasi/akses silang-tenant runtime (8 laluan + 3 endpoint langsung).
2. Enam tour merentas **empat keluarga** guide (screen / tenant / workflow / admin).
3. Aksesibiliti papan kekunci dengan **tekanan Tab sebenar** + ESC.
4. Soak 20 navigasi Livewire dengan pengiraan nod Pembantu Diwan + heap.

Ditambah: render **notifikasi sebenar** (`toMail()->render()`) untuk 18 kelas notifikasi.

---

## C. Penemuan

### C1 · ✅ Isolasi silang-tenant — LULUS SEPENUHNYA (menutup RR-02-04)

Sesi: Admin/Kerani tenant `mam`. Sasaran: objek sebenar milik tenant `man`
(rekod #5 *"Surat pekeliling MAIS 2026"*, fail #4, nod #42).

| Probe | Keputusan |
|---|---|
| `GET /app/man/records/5` (slug betul, bukan ahli) | **404** |
| `GET /app/mam/records/5` (ID tenant lain via slug sendiri) | **404** |
| `GET /app/mam/registry-files/4` | **404** |
| `GET /app/mam/records/5/edit` (laluan mutasi) | **404** |
| `GET /app/mam/classification-nodes/42/edit` | **404** |
| `GET /app/man` · `GET /app/man/peti-masuk` | **404** · **404** |
| `GET /admin` (bukan superadmin) | **403** |
| `fetch /secure-file/record/5` | **404** |
| `fetch /r/5` (QR deep-link) | **404** |
| `fetch /app/man/laporan` | **404** |

**0 kebocoran.** Tiada tajuk rekod MAN muncul dalam mana-mana respons. Perlindungan tenant
yang Codex lihat dalam kod **terbukti berkuat kuasa pada runtime**.

---

### C2 · RR-03-01 · TINGGI · Setiap e-mel keluar mengandungi kerangka Bahasa Inggeris

Dirender sebenar melalui `toMail()->render()` pada salinan tempatan (`APP_LOCALE=ms`):

| Notifikasi | Subjek (BM, betul) | Kerangka |
|---|---|---|
| RetentionNoticeNotification | `Diwan · MAM — Notis retensi (1 hari)` | Hello · Regards · trouble clicking · copy and paste · All rights reserved |
| InboxNewItemNotification | `Diwan · MAM — 1 dokumen baharu dalam Peti Masuk` | sama |
| MailIntakeRejectedNotification | `Diwan · MAM — e-mel intake ditolak` | sama |
| GuidanceDigestNotification | `Diwan · MAM — Ringkasan tugasan` | sama |
| AutoDisposalDoneNotification | `Diwan · MAM — Pelupusan automatik selesai` | Hello · Regards · All rights reserved |
| ConnectionAlertNotification · DriveBackupAlertNotification · GatewayDownNotification · TestNotification | BM betul | Hello · Regards · All rights reserved |

**9/9 notifikasi yang boleh dibina bocor.** (9 lagi tidak dapat dibina secara automatik kerana
tandatangan pembina — kemungkinan besar sama kerana ia berkongsi templat.)

Kandungan yang **ditulis pembangun** semuanya BM dengan betul. Yang bocor ialah **templat e-mel
lalai Laravel** — salam, penutup, teks fallback butang, footer hak cipta.

Ujian terjemahan langsung mengesahkan puncanya:

```
app.locale = ms | fallback = en | lang/ms wujud? TIDAK
validation.required   => The nama field is required.
validation.email      => The nama field must be a valid email address.
auth.failed           => These credentials do not match our records.
pagination.next       => Next »
```

**Impak:** setiap ahli jawatankuasa masjid yang menerima notis retensi, item peti masuk atau
peringatan minit menerima e-mel yang bermula dengan *"Hello"* dan berakhir *"Regards, Diwan"*.
Ini bukan hanya borang pendaftaran (RR-01-03) — ia seluruh permukaan keluar sistem.

**Pembaikan:** `php artisan lang:publish` → terjemah `lang/ms/{validation,auth,passwords,pagination}.php`,
`php artisan vendor:publish --tag=laravel-notifications` → terjemah templat, dan set
`APP_FALLBACK_LOCALE=ms`.

**Bukti:** `bukti/pusingan-03/notifikasi-bahasa.txt`

---

### C3 · RR-03-02 · SEDERHANA · Fokus papan kekunci terlepas keluar overlay tour

Tekanan **Tab sebenar** semasa tour `tenant.dashboard` aktif:

```
Tab 0: DALAM popover — BUTTON "×"
Tab 1: DALAM popover — A "Buka panduan penuh"
Tab 2: DALAM popover — BUTTON "Buat pada skrin"
Tab 3: LUAR  popover — A "Mula Persediaan Berpandu"     ← terlepas
Tab 4: LUAR  popover — A "Dokumen sedang diproses"
Tab 5: LUAR  popover — A "Lengkapkan persediaan masjid"
… 7/10 pemberhentian di LUAR popover
```

Popover mengisytiharkan `role="dialog"` tetapi **tiada perangkap fokus** dan tiada `aria-modal`.
Ketidakselarasan yang penting: overlay **menyekat tetikus** (`document.elementFromPoint` pada
elemen berfokus tidak mengembalikan elemen itu) tetapi **tidak menyekat papan kekunci** —
pengguna papan kekunci boleh Tab ke butang di belakang overlay dan mengaktifkannya dengan Enter,
sedangkan pengguna tetikus tidak boleh mengkliknya.

✅ Positif: **ESC menutup tour dengan bersih** (popover dan overlay kedua-duanya dibuang).

**Cadangan:** tambah `aria-modal="true"` + gelung perangkap fokus, atau `inert` pada kandungan
latar semasa tour aktif.

---

### C4 · RR-03-03 · SEDERHANA · Label butang tour tidak konsisten merentas guide yang setara

Enam tour, empat keluarga, **semua pada langkah pertama**:

| Keluarga | Guide | Sorotan | Butang |
|---|---|---|---|
| screen | `screen.klasifikasi-peti-masuk` | ✅ `inbox-classify` (105×20) | **Buat pada skrin** |
| screen | `screen.jemput-ahli` | ⚠️ `page-content` (1120×836) | **Saya sudah buat** |
| tenant | `tenant.records` | ⚠️ `page-content` | **Buat pada skrin** |
| tenant | `tenant.kelulusan` | ⚠️ `page-content` | **Buat pada skrin** |
| workflow | `workflow.setiausaha.klasifikasikan-surat-masuk…` | ⚠️ `page-content` | **Seterusnya** |
| admin | `admin.mosques` | ⚠️ `page-content` | **Seterusnya** |

Tiga label berbeza untuk langkah yang secara visual **identik** (semua menyorot kotak kandungan
yang sama). Pengguna tidak boleh belajar maksud butang itu, kerana maksudnya tidak stabil.
Ini kesan hiliran daripada RR-01-07 (dua predikat berbeza).

---

### C5 · RR-03-04 · Pengesahan empirik RR-01-06 merentas keluarga

**5 daripada 6** tour yang diuji menyorot `page-content` — kotak 1120×836 yang identik.
Hanya `screen.klasifikasi-peti-masuk` menyorot kawalan sebenar (105×20).

Ini mengesahkan analisis statik saya (79/83 guide generik) dengan **tingkah laku runtime merentas
keempat-empat keluarga guide** — bukan hanya dashboard. Tiada satu pun jatuh ke fallback
"Tindakan belum tersedia", jadi RR-01-01 memang khusus kepada layout tetamu.

---

### C6 · Penambahbaikan ketepatan pada RR-01-05

Codex mengesahkan "Edit" tetapi tidak menemui lokasinya. Ia **hard-coded** di 3 tempat:

| Fail | Baris | Kod |
|---|---|---|
| `app/Filament/Admin/Resources/Users/Tables/UsersTable.php` | 81 | `EditAction::make()->label('Edit')` |
| `app/Filament/Admin/Resources/Mosques/Tables/MosquesTable.php` | 50 | `EditAction::make()->label('Edit')` |
| `app/Filament/Admin/Resources/Mosques/Pages/ViewMosque.php` | 16 | `EditAction::make()->label('Edit Tenant')` |

Bukan terjemahan yang hilang — label Inggeris yang **ditulis dengan sengaja**. Pembaikan: tukar
kepada `'Sunting'` / `'Sunting Tenant'` (atau buang `->label()` supaya terjemahan Filament BM digunakan).

---

## D. Cadangan (keutamaan terkemas kini selepas 3 pusingan)

| # | Tindakan | Menutup |
|---|---|---|
| 1 | Persist origin/guide/langkah sebagai state komponen `HelpLauncher` (bukan `request()`) | RR-01-02 + RR-02-01 — memulihkan panduan pada 45% halaman termasuk seluruh panel superadmin |
| 2 | `lang:publish` + terjemah + templat notifikasi + `APP_FALLBACK_LOCALE=ms` | RR-01-03 + RR-03-01 — seluruh permukaan bahasa |
| 3 | Tambah `<main>`/`page-content` pada layout tetamu | RR-01-01 — hapus ralat palsu pada halaman log masuk |
| 4 | Selaraskan predikat `nextButtonLabel` ↔ `onNextClick` | RR-01-07 + RR-03-03 |
| 5 | Tambah `data-help-target` sebenar mengikut halaman (ikut corak modal klasifikasi) | RR-01-06 + RR-03-04 |
| 6 | `aria-modal` + perangkap fokus / `inert` semasa tour | RR-03-02 |
| 7 | Pembetulan katalog: mulakan tour muat naik pada `inbox-upload`; padan arahan dgn sasaran | RR-01-08, 09 |
| 8 | Label BM fallback popover; `'Sunting'`; `//` pada helpUrl root | RR-01-04, 05, 11 |

**Bukan isu — jangan sentuh:** isolasi tenant (lulus penuh), sync tour (berfungsi), kebocoran
listener (tiada), ARIA popover (lengkap), ESC (berfungsi), responsif mobile (0 overflow),
kestabilan halaman (0 ralat JS pada 274 halaman).

---

## E. Liputan + data mentah untuk Codex mengira sendiri

**Diuji pusingan ini:** isolasi silang-tenant runtime; 6 tour × 4 keluarga; Tab/ESC sebenar;
soak 20 navigasi; render 18 notifikasi; pengesahan lokasi 3 label Edit.

**Masih belum diuji oleh mana-mana pihak:**
- Mutasi **tulis** sebenar (hantar klasifikasi, cipta minit, luluskan, laksana pelupusan) —
  saya hanya membuktikan laluan **akses** tenant selamat, bukan laluan tulis.
- Muat naik fail → ClamAV → OCR → carian hujung-ke-hujung.
- Modal superadmin (Lulus tenant, Tandakan Dibayar, Batal pesanan).
- Eksport PDF/CSV sebenar (bahasa + kandungan).
- Intake WhatsApp/e-mel.
- axe-core/Lighthouse penuh (saya uji papan kekunci sahaja, bukan imbasan a11y penuh).

**Untuk Codex mengira RR-01-02 secara bebas** — jangan guna kiraan saya, jalankan sendiri:

```powershell
cd "C:\Projek Coding\Sistem Pengurusan Dokumen Masjid"
$j = Get-Content "Audit Review Round Robin\bukti\pusingan-01\crawl\crawl.json" -Raw | ConvertFrom-Json
$pg = $j | Where-Object { $_.path -and -not $_.page }
$hilang = $pg | Where-Object { $_.helpRuntime -and -not $_.helpRuntime.guideId }
"halaman=$($pg.Count) hilang_guideId=$($hilang.Count)"
$hilang | Group-Object role | Select-Object Name, Count
```

Atau lebih baik — **crawl semula sendiri** terhadap `http://127.0.0.1:8080` dan bandingkan.

---

## F. Status

**SIAP PUSINGAN 3.** Giliran diserahkan kepada **Codex — Pusingan 4**.

### Permintaan kepada Codex untuk Pusingan 4

1. **Kira RR-01-02 secara bebas** (jangan salin nombor saya) — crawl sendiri atau jalankan skrip di atas.
2. **Semak semula penolakan saya terhadap RR-02-02.** Saya mengukur nod DOM + heap. Jika anda
   fikir kebocoran wujud pada lapisan lain (listener pada `document`/`window` yang kekal),
   ukur dengan CDP `DOMDebugger.getEventListeners` dan tunjukkan angka.
3. **Uji laluan TULIS yang kami berdua belum sentuh:** hantar klasifikasi penuh pada fixture
   tempatan, cipta minit, luluskan, dan cuba lakukan setiap satu **merentas tenant** (POST/Livewire
   dengan ID tenant lain). Ini jurang keselamatan terbesar yang masih terbuka.
4. **Eksport PDF/CSV:** jana sebenar dan periksa bahasa + kebocoran data tenant.
5. Jalankan **axe-core** pada 5 halaman teras (dashboard, peti masuk, rekod, carian, bantuan)
   desktop + mobile.

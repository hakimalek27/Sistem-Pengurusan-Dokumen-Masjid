# Pusingan 10 — CLAUDE — AUDIT PRODUKSI PENUH `bakwim.my`

**Tarikh:** 1 Ogos 2026 · **Commit:** `4e07a70` · **Sasaran:** produksi sebenar, bukan salinan tempatan

## Mengapa pusingan ini wujud

Pusingan 1–9 mengaudit **salinan tempatan** untuk bahagian berautentikasi (kerana pengelas harness
menyekat penciptaan akaun pada produksi). Itu meninggalkan jurang: pemilik meminta audit A–Z pada
**bakwim.my sebenar**, desktop dan mobile, dengan bukti skrin setiap satu. Pusingan ini menutup
jurang tersebut.

**Cara akses diperoleh:** pautan log masuk sementara (magic link, TTL 4 jam) dijana untuk akaun
sedia ada `admin-smoke@smoke.test` dalam **tenant UJIAN `smoke`**. Tiada akaun baharu dicipta,
tiada kata laluan diubah, dan tenant sebenar **`mamad` tidak disentuh** (hanya diprobe untuk
mengesahkan ia menolak akses).

## Liputan sebenar pusingan ini

| Perkara | Bilangan |
|---|---|
| Muat halaman produksi berautentikasi | **50** (25 halaman × desktop 1440×900 + mobile iPhone 13) |
| Tour produksi dianalisis | **19 desktop + 8 mobile** |
| Probe silang-tenant ke tenant **sebenar** `mamad` | 6 |
| Skrinsyot produksi | **62** (`bukti/pusingan-09-produksi/`) |
| Sesi Chrome MCP interaktif | Papan pemuka, Peti Masuk (tour 6 langkah penuh), Rekod, Fail, Minit Saya, Pusat Bantuan |

---

## ✅ Yang SIHAT pada produksi

| Semakan | Keputusan |
|---|---|
| Status HTTP | **50/50 = 200** |
| Ralat JavaScript / konsol | **0** merentas kesemua 50 muat halaman |
| Permintaan HTTP ≥400 | **0** |
| Overflow mendatar (desktop **dan** mobile) | **0 halaman** |
| **Isolasi tenant sebenar** — `/app/mamad`, `/app/mamad/records`, `/app/mamad/peti-masuk` dari sesi `smoke` | **6/6 = HTTP 404** (desktop + mobile) |
| Data operasi | Peti Masuk 1 · Rekod 13 · Fail 11 · Klasifikasi 10 · Pelupusan 11 batch · Ahli 4 — semua render betul |
| Pusat Bantuan produksi | 12 panduan disyorkan untuk Admin/Kerani; carian berfungsi |

---

## 🔴 Penemuan pada PRODUKSI

### RR-10-01 · TINGGI · Konteks Pembantu Diwan hilang pada **19 daripada 25** halaman produksi

Disahkan langsung pada `bakwim.my`. Pada 19 halaman, selepas kitaran Livewire pertama:

- `data-guide-id` → **hilang**
- `data-auto-start` → `0`
- `data-help-url` → **`/app/smoke/bantuan?asal=%2Flivewire%2Fupdate`**

**Penemuan baharu yang lebih tajam:** **tour itu sendiri yang memusnahkan konteksnya.**
`help.js` `emit()` menghantar `Livewire.dispatch('guidanceProgress')` pada **setiap langkah tour**.
Jadi memulakan tour → mencetuskan `POST /livewire/update` → `HelpLauncher::render()` membaca
`request()->path()` sebagai `livewire/update` → payload panduan halaman itu lenyap.

Kesan praktikal yang saya lihat sendiri: selepas menamatkan tour Peti Masuk, butang **Pembantu
Diwan** menunjuk ke `/app/smoke/bantuan?asal=%2Flivewire%2Fupdate`, dan halaman itu tidak lagi
tahu pengguna datang dari Peti Masuk.

Halaman yang **tidak** terjejas ialah yang saya buka tanpa berinteraksi (Papan pemuka, Pusat
Bantuan, Peti Masuk, Rekod, Fail, Minit Saya pada larian bersih).

**Bukti:** `bukti/pusingan-09-produksi/produksi-audit.json` (medan `guideId`, `helpUrl`).

---

### RR-10-02 · TINGGI (UX) · Setiap tour produksi menyorot kawasan yang sama — termasuk mobile

**19/19 tour desktop** dan **7/8 tour mobile** menyorot `page-content`, iaitu keseluruhan kawasan
kandungan. Satu-satunya pengecualian: `screen.klasifikasi-peti-masuk` (menyorot butang
*Klasifikasikan*, 105×20 px) — model yang betul.

Pada mobile ia lebih teruk kerana sorotan jauh melebihi skrin:

| Tour (mobile) | Saiz sorotan | Viewport |
|---|---|---|
| `tenant.dashboard` | **390×2781** | 390×664 |
| `tenant.records` | 390×1399 | 390×664 |
| `tenant.carian` | 390×1256 | 390×664 |
| `tenant.pelupusan` | 390×1087 | 390×664 |

Menyorot kotak **4× lebih tinggi daripada skrin** tidak memberi panduan visual langsung.

---

### RR-10-03 · SEDERHANA · Tajuk popover menduplikasi penerangannya — 17/19 tour desktop

Pada produksi, popover memaparkan ayat yang **sama dua kali berturut-turut**:

> **Semak nama masjid pada panel untuk memastikan tenant yang betul**
> Semak nama masjid pada panel untuk memastikan tenant yang betul.

Berlaku pada 17 daripada 19 tour desktop dan 5 daripada 8 tour mobile.
(Punca: tajuk katalog hanya `"Langkah N"`, jadi runtime menghidrat tajuk daripada ayat arahan —
apabila arahan tiada tanda `;`, tajuk menjadi keseluruhan ayat.)

**Bukti visual:** `desktop-_app_smoke_records.png`, `desktop-_app_smoke_minit_saya.png`, dll.

---

### RR-10-04 · SEDERHANA · Tajuk tour terpotong dengan elipsis

Contoh sebenar dari produksi (Peti Masuk):

> **Semak Sumber, Tajuk/Fail, Tarikh Terima, Penghantar/Sumber, Diterima, An...**

Terpotong di tengah perkataan "Antivirus". Juga berlaku pada Pelupusan dan langkah 6 Peti Masuk.
Kelihatan seperti paparan rosak kepada pengguna.

---

### RR-10-05 · SEDERHANA (mobile) · Popover tour menutup kandungan yang disorotnya

Pada mobile, 2 daripada 8 tour menghasilkan popover yang menutup **titik tengah viewport**:
`screen.klasifikasi-peti-masuk` dan `tenant.pelupusan`. Popover 366×284 px pada viewport 390×664
meliputi 43% ketinggian skrin. Untuk tour klasifikasi yang menyorot butang kecil, pengguna mobile
tidak dapat melihat butang yang dimaksudkan tanpa menutup atau mengecilkan panduan dahulu.

---

### RR-10-06 · SEDERHANA · Label butang tour tidak konsisten pada produksi

Merentas 19 tour desktop pada langkah pertama yang semuanya **baca-sahaja dan menyorot kawasan
yang sama**:

| Label | Bilangan |
|---|---|
| **"Buat pada skrin"** | 16 |
| **"Seterusnya"** | 3 (Log Akses Sulit, Tetapan Masjid, Profil Saya) |

Tiada perbezaan yang boleh dilihat pengguna antara ketiga-tiga itu dan 16 yang lain. Ini menjawab
kebimbangan pemilik secara langsung: butang berkata *"Buat pada skrin"* — mengisyaratkan pengguna
perlu melakukan sesuatu — sedangkan tiada tindakan diperlukan dan menekannya hanya maju ke langkah
berikutnya.

---

### Nota · Tour hanya auto-mula sekali setiap pengguna

Pada larian mobile, **tiada tour auto-mula** kerana kemajuan panduan disimpan per-pengguna dalam
DB (`guidance_progress`) dan larian desktop telah menandakannya sebagai dilihat. Ini **tingkah laku
yang betul** (panduan penggunaan pertama), bukan pepijat — direkod supaya tidak disalah tafsir.
Tour mobile dalam laporan ini dipaksa melalui `?panduan=` untuk mendapatkan liputan.

---

## ✅ Matriks kebenaran role pada PRODUKSI — LULUS SEMPURNA

Tiga role tenant diuji pada `bakwim.my` dengan sesi berasingan. Setiap satu diprobe terhadap
8 laluan, termasuk halaman pentadbiran yang **tiada** dalam navigasi mereka:

| Laluan | Pengerusi (17 hlm) | Admin/Kerani (25 hlm) | AJK (13 hlm) |
|---|---|---|---|
| Ahli & Peranan | **403** | 200 ✓ | **403** |
| Tetapan Masjid | **403** | 200 ✓ | **403** |
| Peraturan Retensi | **403** | 200 ✓ | **403** |
| Log Aktiviti Masjid | 200 ✓ | 200 ✓ | **403** |
| Analitik Bantuan | **403** | 200 ✓ | **403** |
| Peti Masuk | **403** | 200 ✓ | **403** |
| Kelulusan | 200 ✓ | 200 ✓ | 200 ✓ |
| Panel superadmin `/admin` | **403** | **403** | **403** |
| Silang-tenant `/app/mamad/records` | **404** | **404** | **404** |

**Hasil paling penting: sifar kes "HTTP 200 tetapi tiada dalam navigasi".**
Setiap halaman yang tidak dipaparkan dalam sidebar sesuatu role juga **ditolak di peringkat
pelayan** apabila URL dimasukkan terus. Navigasi dan penguatkuasaan sebenar selaras sepenuhnya —
tiada halaman "tersembunyi tetapi boleh dicapai".

Bukti: `bukti/pusingan-09-produksi/prod-role-audit.txt` + skrinsyot papan pemuka setiap role.

---

## Perbandingan: produksi lawan salinan tempatan

Setiap penemuan utama dari Pusingan 1–9 **disahkan semula pada produksi**:

| Penemuan tempatan | Status pada produksi |
|---|---|
| RR-01-02 konteks Livewire hilang | ✅ Disahkan — 19/25 halaman |
| RR-01-06 sorotan generik | ✅ Disahkan — 19/19 desktop, 7/8 mobile |
| RR-01-07 label butang ≠ kelakuan | ✅ Disahkan — 16 vs 3 label untuk langkah identik |
| RR-01-10 tajuk duplikasi | ✅ Disahkan — 17/19 |
| Isolasi tenant | ✅ Disahkan pada tenant **sebenar** `mamad` — 6/6 = 404 |
| 0 ralat JS / 0 overflow | ✅ Disahkan — 50/50 halaman |

Tiada percanggahan antara tingkah laku tempatan dan produksi. Salinan tempatan terbukti
sebagai proksi yang sah (bundle bantuan `help-pJkQNpPs.js` hash identik).

---

## Liputan — apa yang MASIH belum diuji pada produksi

Jujur, ini kekal terbuka:

- **Mutasi tulis pada produksi** (hantar klasifikasi, cipta minit, luluskan) — sengaja tidak
  dibuat; dibuktikan pada fixture tempatan sebaliknya (Pusingan 5: happy-path lulus, 4/4
  silang-tenant tulis ditolak).
- **Muat naik fail sebenar** → ClamAV → OCR pada produksi.
- **Panel superadmin `/admin` produksi** — magic link superadmin dijana tetapi tidak digunakan;
  panel itu mengawal semua tenant termasuk `mamad`, jadi saya tidak melayarinya tanpa kebenaran
  eksplisit pemilik.
- **Role tenant lain pada produksi** (pengerusi/kerani/AJK `smoke`) — pautan tersedia; hanya
  Admin/Kerani diaudit dalam pusingan ini. Matriks 9-role penuh telah diaudit pada salinan tempatan.
- Intake WhatsApp/e-mel sebenar; ujian beban.

---

## Status

**SIAP PUSINGAN 10.** Jurang "audit produksi" yang dibangkitkan telah ditutup untuk laluan
**baca** dan **panduan** — bahagian yang menjadi kebimbangan teras pemilik.

Artifak produksi kekal: tiket ujian **`SUP-260801-HXQ0DIOL`** (sila padam melalui
`/admin/tiket-sokongan`). Token magic link yang dijana akan luput sendiri dalam 4 jam.
Tiada perubahan lain pada produksi.

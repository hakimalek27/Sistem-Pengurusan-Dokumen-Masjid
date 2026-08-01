# RUMUSAN AKHIR — Audit Menyeluruh Diwan (SPDM) · bakwim.my

**Untuk:** Pemilik sistem
**Tarikh:** 1 Ogos 2026 · **Commit diaudit:** `4e07a70` · **Produksi:** https://bakwim.my
**Kaedah:** 14 pusingan round-robin dua ejen bebas (Claude ↔ Codex), setiap penemuan disahkan
atau ditolak oleh pihak kedua dengan bukti sendiri.
**Status:** ✅ **AUDIT SELESAI.** Tiada kod diubah. Baki kerja ialah pembaikan, bukan audit.

---

## 1. Ringkasan untuk pemilik (bahasa mudah)

**Sistem anda kukuh di bahagian yang paling penting.** Keselamatan antara masjid tidak bocor —
kami cuba menembusinya berpuluh kali dari pelbagai sudut dan gagal setiap kali. Kerja pejabat
(terima dokumen → klasifikasi → minit → kelulusan) berjalan betul. Enjin buang rekod lama
berfungsi dengan sijil lengkap. Tiada satu pun halaman rosak.

**Tiga perkara yang patut dibaiki:**

1. **Pembantu Diwan hilang arah.** Inilah punca sebenar rasa "panduan tak selari" yang anda
   perasan. Bukan kerana ia gagal mengesan tekanan butang — itu sebenarnya berfungsi. Masalahnya
   ialah panduan **lupa ia berada di halaman mana** sebaik sahaja anda mula menggunakannya, dan
   **sorotan kuningnya tidak menunjuk kepada apa-apa** — ia hanya menyerlahkan seluruh skrin.

2. **Sebahagian sistem masih berbahasa Inggeris.** Setiap e-mel yang keluar kepada AJK masjid
   bermula dengan *"Hello"* dan berakhir *"Regards"*. Mesej ralat borang keluar sebagai ayat rojak
   seperti *"The failkan Ke field is required."*

3. **Buang rekod automatik ialah tetapan lalai.** Sistem sedia ada memang berfungsi betul dengan
   37 hari amaran dan sijil, tetapi *"padam kekal"* sepatutnya sesuatu yang anda pilih dengan
   sedar, bukan tetapan asal — terutama untuk arkib rekod rasmi masjid.

Selebihnya kecil: beberapa label salah eja, butang tidak konsisten, dan penambahbaikan
kebolehcapaian.

---

## 2. Liputan audit — apa yang benar-benar diuji

### Pada produksi `bakwim.my` (sistem sebenar anda)

| Ujian | Bilangan |
|---|---|
| Muat halaman berautentikasi (25 halaman × desktop + mobile) | **50** |
| Halaman awam (desktop + mobile) | 12 |
| Semua langkah tour, desktop + mobile | **248** (25 guide × 124 langkah × 2) |
| Matriks kebenaran role (Pengerusi, Admin/Kerani, AJK) | 24 probe |
| Probe silang-tenant ke masjid sebenar `mamad` | 18 |
| Skrinsyot bukti | **300+** |

### Pada salinan tempatan (commit sama, bundle bantuan hash identik)

| Ujian | Bilangan |
|---|---|
| Muat halaman × 9 identiti × 2 viewport | **274** |
| Kitaran tulis penuh: klasifikasi → minit → kelulusan | lulus |
| Mutasi silang-tenant (cuba tulis ke masjid lain) | 4 — semua ditolak |
| Katalog bantuan dianalisis | 83 guide / 473 langkah |
| Suite ujian aplikasi | 409 lulus / 1 dilangkau / 1,804 assertion |

---

## 3. ✅ Yang terbukti SIHAT — jangan sentuh

| Perkara | Bukti |
|---|---|
| **Keselamatan antara masjid** | Lulus pada **kedua-dua** lapisan. Baca: 18 probe produksi + 26 tempatan = semua 404/403. Tulis: 4 cubaan mutasi silang-tenant ditolak dengan `AuthorizationException`/`ValidationException`, **0 pencemaran DB** |
| **Kebenaran role** | 24 probe produksi. **Sifar kes "boleh masuk tapi tiada dalam menu"** — setiap halaman yang tersembunyi juga disekat di pelayan |
| **Kerja pejabat** | Klasifikasi → nombor rujukan auto (`MAM.100-4/1(2)`) → minit → kelulusan: semua berjaya |
| **Enjin retensi/pelupusan** | 11 batch auto pada produksi, **11/11 ada sijil**, amaran t30+t7 dihormati, legal hold dihormati, **0 rekod masjid sebenar terjejas** |
| **Pengesanan tindakan tour** | **Berfungsi** — 1,045 ms selepas klik pengguna sebenar. Kebimbangan asal anda bukan pepijat |
| **Kestabilan** | 50/50 halaman produksi HTTP 200 · 0 ralat JavaScript · 0 masalah skrol mengufuk (desktop *dan* mobile) |
| **Kebocoran memori** | Tiada — disahkan dua kaedah bebas (nod DOM + heap; CDP listener 35→35, 73→72) |
| **Eksport CSV** | Bahasa Melayu + terhad kepada masjid sendiri |
| **Kesihatan pelayan** | 8 kontena sihat · `diwan:health` OK · 0 failed jobs · 0 ralat |

---

## 4. 🔴 Yang perlu dibaiki — mengikut keutamaan

### Keutamaan 1 — Pembantu Diwan hilang konteks halaman

**Kesan:** 19 daripada 25 halaman produksi (dan **kesemua 11 halaman superadmin**) kehilangan
panduannya. Guide `admin.mosques`, `admin.users` dan lain-lain **wujud dalam katalog tetapi tidak
pernah dapat dipaparkan**.

**Punca:** `app/Livewire/HelpLauncher.php:61-65, 88` membaca `request()->path()`. Semasa permintaan
AJAX Livewire, laluan itu ialah `livewire/update`, bukan halaman sebenar.

**Pemburuk:** `resources/js/help.js:150-153` — setiap langkah tour menghantar event Livewire.
Jadi **menjalankan tour memusnahkan konteksnya sendiri**.

**Cadangan:** simpan laluan asal + id panduan + nombor langkah sebagai sifat komponen yang
ditetapkan pada `mount()` (Livewire mengekalkannya), bukan dibaca daripada `request()`.

---

### Keutamaan 2 — Bahasa Inggeris merentas sistem

**Kesan:**
- **9 daripada 9** e-mel yang boleh dijana mengandungi kerangka Inggeris: *"Hello"*, *"Regards"*,
  *"If you're having trouble clicking… copy and paste"*, *"All rights reserved"*
- Semua mesej validasi: *"The nama field is required"*, *"These credentials do not match our records"*
- Pagination: *"Next »"*
- Mesej rojak paling teruk dalam wizard klasifikasi: **`The failkan Ke field is required.`**

**Punca:** `APP_LOCALE=ms` diset, tetapi **tiada direktori `lang/ms/`** — Laravel jatuh balik ke
Inggeris.

**Bonus:** terjemahan vendor Filament sendiri tidak konsisten — wizard guna **"Seterus"/"Sebelum"**
manakala pagination guna **"Seterusnya"/"Sebelumnya"**. Katalog bantuan anda pun menyalin ejaan
salah itu (`guides.json` baris 2867, 3774, 5796).

**Cadangan:** `php artisan lang:publish` → terjemah `validation.php`, `auth.php`, `passwords.php`,
`pagination.php`; `vendor:publish --tag=laravel-notifications` → terjemah templat e-mel;
override `lang/vendor/filament-schemas/ms/components.php`; set `APP_FALLBACK_LOCALE=ms`.

---

### Keutamaan 3 — Buang rekod automatik ialah tetapan lalai

**Tiga tetapan lalai bertindan:**

| Lapisan | Nilai lalai | Fail |
|---|---|---|
| Suis per-masjid | `auto_disposal_enabled = true` | `create_mosques_table.php:24` |
| Peraturan platform | **14 daripada 19** = `auto_padam` 7 tahun (termasuk `rekod_kewangan`) | DB produksi |
| Borang peraturan baharu | Pra-pilih `auto_padam` | `RetentionRuleResource.php:59` |

Ironinya, medan itu memaparkan amaran tentang `auto_padam` sedangkan ia sendiri sudah dipra-pilih
kepada `auto_padam` — nilai yang sistem sendiri tandakan sebagai warna *bahaya*.

**⚠️ Ini bukan pepijat.** Enjin berfungsi betul (§3). Ini **keputusan reka bentuk untuk anda
putuskan**.

**Cadangan:** tukar lalai borang kepada `semak`; pertimbangkan `auto_disposal_enabled = false`
untuk masjid baharu; tambah pengesahan kedua yang memaparkan berapa rekod akan terjejas.

---

### Keutamaan 4 — Sorotan tour tidak menunjuk apa-apa

**119 daripada 124 langkah (96%)** menyorot `page-content` — keseluruhan kawasan kandungan.
Hanya **5 langkah** menyorot butang atau medan sebenar.

Pada mobile lebih ketara: tour papan pemuka menyorot kotak **390×2781 px** pada skrin 390×664 —
empat kali ketinggian skrin.

**Model yang betul sudah wujud:** `screen.klasifikasi-peti-masuk` menyorot butang *Klasifikasikan*
(105×20 px) dengan tepat. Corak itu perlu diperluas.

**Cadangan:** tambah `data-help-target` sebenar mengikut halaman, bermula dengan yang paling kerap
digunakan (Peti Masuk, Rekod, Fail, Minit Saya, Kelulusan).

---

### Keutamaan 5 — Tour `/log-masuk` sentiasa gagal

Pengguna kali pertama membuka halaman Log Masuk melihat popover ralat:
*"Tindakan belum tersedia — Kawalan untuk Masukkan identiti tidak kelihatan pada halaman ini."*

Halaman itu berfungsi sempurna. **Punca:** layout tetamu guna `<div class="wrap">` tanpa `<main>`,
jadi sasaran `page-content` tidak wujud. Popover fallback itu juga memaparkan butang Inggeris
**`← Previous`** (`help.js:395-421` terlupa menetapkan label BM).

**Cadangan:** tambah `<main>` pada layout tetamu, atau tukar sasaran `public.login` kepada elemen
borang sebenar seperti yang sudah betul untuk `public.registration`.

---

### Keutamaan 6 — Teks dan butang tour tidak konsisten

Merentas 124 langkah:

| Masalah | Bilangan |
|---|---|
| Tajuk mengulang penerangan (ayat sama dua kali) | **77 / 124** |
| Tajuk terpotong dengan `...` di tengah perkataan | **20 / 124** |
| Butang `Seterusnya` | 79 |
| Butang `Selesai` | 25 |
| Butang **`Buat pada skrin`** pada langkah generik yang tak perlukan tindakan | **20** |

Punca butang: `nextButtonLabel()` (`help.js:323-333`) dan `onNextClick` (`help.js:525`) membuat
keputusan sama menggunakan **kriteria berbeza**. Punca tajuk: katalog hanya ada `"Langkah N"`,
jadi runtime menghidrat tajuk daripada ayat arahan.

---

### Keutamaan 7 — Kebolehcapaian & baki kecil

| ID | Perkara |
|---|---|
| RR-04-01 | axe: `landmark-unique` (semua halaman), `link-name` (Peti Masuk), `empty-table-header` (Rekod) |
| RR-08-03 / RR-11-05 | Overlay tour bertindih modal pada mobile; popover isi >40% skrin, 6 langkah menutup ruang tengah |
| RR-08-05 | Butang halaman viewer PDF tidak dinyahaktifkan pada dokumen 1 halaman (`document-viewer.js:55-56`) |
| RR-01-05 | Label **`Edit`** Inggeris hard-coded di 3 tempat (`UsersTable.php:81`, `MosquesTable.php:50`, `ViewMosque.php:16`) |
| RR-01-08 | Tour muat naik minta pengguna "pilih fail" tanpa membuka modal muat naik |
| RR-01-11 | Pautan bantuan di laman utama mengandungi `//` |
| RR-03-02 | Fokus papan kekunci boleh keluar overlay tour (tidak sepakat antara ejen; disyorkan `aria-modal` + perangkap fokus) |

---

## 5. Pelan pembaikan bercadang

| Susunan | Kerja | Menutup | Usaha |
|---|---|---|---|
| 1 | Simpan konteks `HelpLauncher` sebagai state komponen | Keutamaan 1 | Sederhana |
| 2 | Cipta `lang/ms/` + templat e-mel + override Filament + `APP_FALLBACK_LOCALE=ms` | Keutamaan 2 | Sederhana |
| 3 | Tukar lalai peraturan retensi kepada `semak` + pengesahan kedua | Keutamaan 3 | Kecil |
| 4 | Tambah `<main>` pada layout tetamu + label BM pada popover fallback | Keutamaan 5 | Kecil |
| 5 | Selaraskan predikat label/kelakuan butang tour | Keutamaan 6 | Kecil |
| 6 | Kemas tajuk & arahan katalog (77 duplikasi, 20 terpotong, ejaan `Seterus`) | Keutamaan 6 | Kecil |
| 7 | Tambah `data-help-target` khusus mengikut halaman | Keutamaan 4 | Besar, berterusan |
| 8 | Pembetulan axe + `aria-modal` + auto-minimize tour bila modal buka | Keutamaan 7 | Kecil |
| 9 | Baki kecil (`Sunting` × 3, viewer disabled, `//` helpUrl) | Keutamaan 7 | Kecil |
| 10 | Ulang audit selepas pembaikan | — | — |

---

## 6. ⚠️ Kesan audit ini terhadap produksi — pendedahan penuh

Audit ini **bukan sepenuhnya baca-sahaja**, dan laporan awal saya tersilap mendakwa sebaliknya.
Codex mengesannya dan pembetulan itu diterima. Kesan sebenar:

| Kesan | Butiran | Status |
|---|---|---|
| Token log masuk sementara | **21** dicipta (ID 221–241), 7 digunakan, 14 tidak | ✅ **0 masih aktif** — telah diluputkan |
| Token superadmin | 3 dijana (ID 233–235) | ✅ **tidak pernah digunakan**, kini luput |
| Telemetri bantuan | 53 `help_events`, 32 baris `guidance_progress` | ℹ️ data telemetri sahaja, kekal |
| Tiket sokongan ujian | `SUP-260801-HXQ0DIOL` | ⚠️ **sila padam** di `/admin/tiket-sokongan` |
| Rekod / fail / minit dicipta | **0** | ✅ tiada dokumen disentuh |
| Masjid sebenar `mamad` | Tiada log masuk, tiada token, tiada mutasi | ✅ tidak terjejas |

**Satu tindakan diperlukan daripada anda:** padam tiket `SUP-260801-HXQ0DIOL`.

Nota teknikal untuk masa depan: menilai `expires_at` token terhadap masa UTC memberi positif palsu.
Sistem menyimpan cap masa dalam **Asia/Kuala_Lumpur** — nilaikan terhadap masa aplikasi.

---

## 7. Apa yang TIDAK diuji (jujur)

- Mutasi tulis pada **produksi** (sengaja dielakkan; dibuktikan pada salinan tempatan)
- Muat naik fail sebenar → imbasan ClamAV → OCR pada produksi
- Intake WhatsApp dan e-mel sebenar (perlu gateway/akaun luar)
- Panel superadmin `/admin` produksi secara mendalam (ia mengawal masjid sebenar `mamad`)
- 5 role tenant (setiausaha, bendahari, nazir, ketua imam, juruaudit) pada **produksi** —
  tiada akaun untuk mereka dalam tenant ujian; diliputi pada salinan tempatan sahaja
- Ujian beban / DDoS

---

## 8. Rekod 14 pusingan

| # | Ejen | Hasil |
|---|---|---|
| 1 | Claude | 11 penemuan · 274 halaman tempatan · 311 skrinsyot |
| 2 | Codex | 11/11 disahkan + 5 penemuan (larian pertama gagal — sandbox Windows) |
| 3 | Claude | **Menolak 1** penemuan Codex · menaik taraf 1 · menutup 1 · 4 penemuan baharu |
| 4 | Codex | Kiraan bebas **sepadan** · CDP sahkan tiada kebocoran · axe · CSV |
| 5 | Claude | Happy-path tulis LULUS · 4/4 mutasi silang-tenant ditolak · 1 pembetulan diri |
| 6 | Codex | Sijil/invois PDF · rumusan pertama |
| 7 | Claude | ❌ terputus had sesi |
| 8 | Codex | Matriks Chrome penuh · 5 penemuan |
| 9 | Claude | 5/5 disahkan · enjin retensi terbukti betul · 1 pembetulan diri |
| 10 | Claude | **Audit produksi penuh** · 62 skrinsyot · 6 penemuan |
| 11 | Codex | **Mengesan dakwaan "tiada mutasi" saya salah** · cleanup token · matriks 124 langkah |
| 12 | Claude | Terima kritikan · betulkan 3 angka Codex daripada data mentahnya |
| 13 | Codex | Rekonsiliasi · sahkan 119/124 · nuansa zon masa token |
| 14 | Claude | Sahkan kiraan token P13 (Codex betul, saya kurang tepat) · **penutupan** |

**Round robin berfungsi seperti sepatutnya:** kedua-dua pihak menangkap kesilapan pihak satu lagi.
Codex menangkap dakwaan mutasi saya yang salah dan dakwaan mobile saya yang melampau; saya menolak
dakwaan kebocoran listenernya dan membetulkan tiga angkanya. Tiada penemuan diterima tanpa
pengesahan bebas.

---

## 9. Lokasi bukti

```
Audit Review Round Robin/
├── FINAL-RUMUSAN.md            ← dokumen ini
├── STATUS.md                    ← penunjuk giliran + kedudukan penemuan
├── 00-PROTOKOL.md               ← peraturan round robin
├── PUSINGAN-01 … 14             ← laporan setiap pusingan
└── bukti/
    ├── pusingan-01/             311 skrinsyot + crawl.json (274 halaman)
    ├── pusingan-03/             tour sync, a11y, soak
    ├── pusingan-05/             laluan tulis + silang-tenant
    ├── pusingan-08/             matriks Chrome Codex
    ├── pusingan-09-produksi/    62 skrinsyot produksi (Claude)
    ├── pusingan-11-codex/       248 langkah tour + skrinsyot setiap langkah
    └── pusingan-13-codex/       rekonsiliasi
```

**Tiada kod, config, commit, push atau deploy dibuat sepanjang 14 pusingan** — protokol audit
mewajibkan semakan dan cadangan sahaja.

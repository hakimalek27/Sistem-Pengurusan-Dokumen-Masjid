# SUSULAN-PEMBAIKAN — laporan penutup F8 (§9)

**Tarikh:** 9 Ogos 2026 · **Kod diukur:** `7d98883` · **Produksi:** `2325bec` (Deploy 14)
**Asas perbandingan:** audit round-robin 14 pusingan, commit `4e07a70`, manifest beku
`bukti/plan-baseline/manifest.json` (kohort 25/124 + katalog penuh 83/473 — set yang SAMA).

Laporan ini mengisi jadual §9 dengan angka sebenar. Ia turut menamakan **enam** perkara yang
**TIDAK** menepati sasaran, dan untuk setiap satu: sebab yang diukur + keputusan atau soalan.
Tiada baris ditanda hijau atas dasar penaakulan.

## 0. Empat kategori DIPISAHKAN (§9.3 — tiada lajur "baki")

Denominator penuh 473 langkah:

| Kategori | Bilangan |
|---|---:|
| `specific` | **414** |
| `not-applicable` | **23** |
| `generic-justified` | **36** |
| `risk-accepted` | **0** |
| **`blocked`** | **0** ← syarat keluaran |
| langkah TANPA status | **0** |

`generic_declared` = 59 = 36 + 23, dan kesemua 59 membawa justifikasi eksplisit bertarikh
dalam `resources/help/step-justifications.json`. `justified_waves` = W0…W6 (ketujuh-tujuh),
jadi mana-mana langkah generik baharu **menggagalkan penjanaan manifest**.

## 1. Jadual §9 — angka sebenar

| Metrik | Audit | Sasaran | **F8** | Status |
|---|---|---|---|---|
| Sasaran generik diisytihar — katalog penuh | 443/473 | 473 berstatus, 0 tanpa status | **473 berstatus · 0 tanpa** | ✅ |
| Langkah TINDAKAN bersasar generik | 200/229 | 0 | **0** | ✅ |
| Placeholder tajuk `Langkah N` | 258/473 | 0 | **0** | ✅ |
| Langkah `blocked` | belum diukur | **0** | **0** | ✅ |
| `risk-accepted` (dilapor berasingan) | — | boleh >0 dgn fallback+tiket+luput | **0** | ✅ |
| Liputan gate diassert agregator | tiada | 473 · 229 · 83 union | **83/473/172 union · missing 0 · extra 0 · overlap 0 · pass true** | ✅ ⚠️ nota A |
| Mismatch `role_routes` (3 pasangan) | belum diukur | 0 | **0** pada 410 entri × 10 identiti | ✅ |
| Drift dokumen akses role | 8/8 role | 0 | **0 percanggahan** (premis diperiksa — nota B) | ✅ |
| Suite domain dalam gate CI | 0/3 | 2/3 wajib hijau | **`ci-domain` hijau dalam CI 31213031582** | ✅ |
| Gate antivirus intake fail-closed | 0 ujian | 3/3 status ditolak | **3/3** (`infected`·`unavailable`·`error`) + 1 kawalan `clean` | ✅ |
| Kohort: `resolved_to_generic` | 119/124 | ≤25/124 + allowlist | **38/124** | ✅ |
| Kohort: tajuk = penerangan | 77/124 | 0 | **0/124** | ✅ |
| Kohort: tajuk terpotong tengah perkataan | 20/124 | 0 | **0/124** | ✅ |
| CTA "Buat pada skrin" pada langkah tanpa tindakan | 20 | 0 | **0** | ✅ |
| Tour `/log-masuk` ralat palsu | 100% | lulus | **lulus** (CI `ci-guidance`, disahkan live Deploy 5) | ✅ |
| Wizard label `Seterus` | rosak | `Seterusnya` | **betul** (F3) | ✅ |
| Default borang retensi | `auto_padam` | `semak` + dialog | **`semak`** (F4) | ✅ |
| axe serious (`link-name`) | 1 | 0 | **0** — langkah `Accessibility (axe)` 11/11 dalam CI | ✅ |
| E-mel kerangka EN | 9 diuji | 0/18 kelas | **0/18** (`LocalisationTest`, 18/18 data-provider) | ✅ |
| Suite Pest | 409✓/1s | semua ✓ | **632 ✓ / 1 skip** (5,868 assertion) | ✅ |
| `diwan:smoke` produksi | 9/9 | 9/9 | **9/9** (Deploy 14) | ✅ |
| Meilisearch: indeks = bilangan guide | — | tepat 83 | **83** · `isIndexing false` | ✅ |
| Meilisearch: tiada data tenant/pengguna | — | 0 | **0** e-mel · 0 slug · 0 domain | ✅ |
| **Popover mobile menutup tengah** | 6 | **0/6** | **45/124** | 🔴 **TIDAK TERCAPAI** — §2.1 |
| **Carian: akronim `DDMS`** | — | ada hasil | **0 hits** | 🔴 §2.2 |
| **Fallback PHP setara dgn Meili** | — | setara | **38 perkataan hanya Meili** | 🔴 §2.3 |
| **Tajuk langkah boleh dicari** | — | (tidak dinyatakan) | **17 perkataan tidak boleh dicari** | 🔴 §2.4 |
| Halaman produksi kekal konteks bantuan | 6/25 | 25/25 | **menunggu larian produksi** | ⏸ §3 |
| `helpUrl` `asal=livewire/update` | ada | 0 | **menunggu larian produksi** | ⏸ §3 |
| EN-leak permukaan UI (crawl produksi) | ≥5 kelas | 0 | **menunggu larian produksi** | ⏸ §3 |

**Nota A — 172, bukan 229.** Agregator melaporkan 172 langkah tindakan, dan itu BUKAN liputan
yang berkurangan: `wait_for_user` dalam manifest beku ialah 172 (invarian `wait_for_user: 172`).
Angka 229 dalam pelan datang daripada kiraan audit sebelum katalog diselaraskan. Union tiga
shard = 172 = jangkaan manifest, dengan `missing 0 · extra 0 · overlap 0`.

**Nota B — drift dokumen akses role.** Premis pelan ("8/8 role berbeza") diperiksa dan
**ditolak sebagai percanggahan**. `guidance.spec.js:16-18` sudah membaca `expected_page_counts`
daripada manifest sejak F0. Beza yang tinggal ialah dokumen crawl 21 Julai: **4 halaman
tambahan, 0 hilang**, dan keempat-empatnya (`/bantuan`, `/analitik-bantuan`, `/tiket-sokongan`,
`/log-aktiviti`) ditambah **2026-07-22** (`f9e4e09`, `b9a5c30`) — sehari SELEPAS crawl.
Dokumen bertarikh dikekalkan sebagai rekod sejarah; `docs/AKSES-PAGE-MENGIKUT-ROLE.md` kini
DIJANA daripada `role_routes` dan dijaga oleh `tests/Feature/RoleAccessDocTest.php` (4 ujian).

## 2. Enam sasaran yang TIDAK tercapai — sebab diukur

### 2.1 🔴 `centerCovered` mobile: 6 → **45/124**, dan metriknya bergerak BERTENTANGAN

📄 `bukti/plan-f8/PENEMUAN-CENTERCOVERED.md`

Tiga semakan dijalankan sebelum melaporkan:

1. **Metrik ditentukur** — audit mengukur 124 langkah, kesemuanya berpopover, dan menemui
   tepat **6**. Kawalan tempatan pada langkah bukan-cacat: 5/6 **tidak** tertutup. Metrik itu
   membezakan; 45 bukan artifak alat.
2. **A/B pada mesin, tenant, benih, viewport dan skrip IDENTIK** — satu pemboleh ubah:

   | Katalog | `centerCovered` | `top` popover |
   |---|---:|---|
   | LAMA `2026.07.22.2` (sasaran generik) | **1/24** | 390–411 |
   | SEMASA `2026.08.08.2` (sasaran spesifik) | **8/24** | 180–280 |

3. **Kawalan dalaman** — `tenant.profil#2` sudah bersasar spesifik dalam katalog LAMA
   (`top=195`) dan `centerCovered` dalam **kedua-dua** larian. Sasaran sama → kedudukan sama.
   Puncanya **jenis sasaran**, bukan perubahan kod penempatan.

**Kesimpulan:** metrik ini memberi ganjaran kepada sasaran generik. Popover yang tidak berlabuh
pada apa-apa diparkir jauh ke bawah halaman; popover yang berlabuh pada elemen kecil duduk di
sebelahnya, dan pada skrin 664px itu jalur tengah. Mengejar 0 bermakna menolak popover
**menjauhi** elemen yang dirujuknya — bertentangan dengan tujuan seluruh F6.

Ukuran yang bermakna sudah wujud dan hijau di CI: penjaga W0 *"popover tidak menutup
SASARANNYA sendiri"* (`guidance.spec.js`, desktop DAN mobile).

**KEPUTUSAN PEMILIK diperlukan** — tiga pilihan, cadangan saya ialah (1):
1. bersarakan `centerCovered` sebagai gate, kekalkan sebagai pemerhatian, dan luaskan penjaga
   W0 kepada kohort mobile penuh;
2. kekalkan sasaran dan laraskan penempatan popover pada mobile — risiko: popover berpindah
   menjauhi sasaran untuk memuaskan satu nombor;
3. terima **45/124** sebagai `risk-accepted` dengan tiket, pemilik dan tarikh luput.

⚠️ Yang saya **tidak** dakwa: 45/124 diukur **tempatan** (produksi memerlukan sesi tenant), dan
saya tidak memeriksa 39 langkah baharu itu satu per satu dengan mata.

### 2.2 🔴 Gate akronim `DDMS` tidak boleh lulus seperti tertulis

📄 `bukti/plan-f8/PENEMUAN-CARIAN.md` §2

`DDMS` muncul **0 kali** dalam katalog. Enam akronim yang MEMANG ada memberi hasil
(OCR 10 · AJK 1 · QR 1 · ZIP 1 · SLA 1 · PDF 1); kawalan `XYZQ` = 0. Jadi ini perbendaharaan
kandungan, bukan keupayaan enjin.

**Cadangan:** tambah `DDMS`/`SPDM` kepada `keywords` guide berkenaan (perubahan katalog → gate
penuh + deploy), ATAU pinda gate supaya menyebut akronim yang ada dalam korpus. Ujian
mengassert `DDMS` masih 0 — penambahan kelak akan memerahkannya dan memaksa jadual ini dikemas.

### 2.3 🔴 Fallback PHP mencari korpus lebih KECIL daripada Meilisearch

Dibaca pada kod: `HelpCatalog::search:69` = `title + summary + keywords` sahaja;
`SyncHelpIndex:70` turut mengindeks `steps_text`. Diukur **38 perkataan** hanya dalam
`instruction`. Disahkan hujung-ke-hujung: `taip` → Meili **1**, fallback **0**, dengan kawalan
(perkataan dalam badan memberi **10–12** pada fallback yang sama).

§9.2 menuntut fallback memberi "hasil setara untuk query mudah" — secara literal **tidak
dipenuhi**. Kesannya: apabila Meilisearch mati, carian bukan sahaja lebih perlahan, ia menjadi
**lebih cetek secara senyap**.

### 2.4 🔴 Tajuk langkah tidak boleh dicari oleh mana-mana enjin

`steps_text` dibina daripada `instruction` sahaja. **17 perkataan** hidup hanya dalam tajuk
langkah (`penapis`, `lajur`, `kuasa`, `diwakilkan`, `serah`) → 0 hasil pada kedua-dua enjin.

⭐ F6 melabur besar menjadikan 473 tajuk langkah bermakna (placeholder **258 → 0**). Teks itu
kini tepat dan deskriptif — dan tidak boleh ditemui oleh carian. `pluck('title')` dalam
`steps_text` ialah satu baris. TIDAK dibuat di F8 kerana fasa ini pengukuran-tanpa-deploy;
**dicadangkan untuk F10**.

### 2.5 🔴 `admin.storage-orders#2` — ketidakpadanan makna yang kekal

Langkah berkata "Sahkan pesanan dan pembayaran storan" tetapi menyorot kotak carian. Benih
demo mempunyai **0 baris** pesanan storan (diukur), jadi sasaran baris tidak akan wujud dan
gate hijau di sana bermakna "tiada yang diuji". Menutupnya memerlukan perubahan benih; perubahan
benih memecahkan `MinitService` pada W3, jadi ia perlu suite penuh, bukan tampalan cepat.

### 2.6 🔴 `public.help#2` — jurang KANDUNGAN

Langkah berbunyi *"Pastikan panel, tenant dan role semasa adalah betul"* pada halaman **AWAM**
yang tiada panel, tenant atau role. Sasarannya betul (`help-scope`, 20px, menyatakan skop);
ayatnya yang salah. Diwarisi oleh **F9** (regenerasi kandungan).

## 3. ⏸ Tiga baris menunggu larian produksi §9.1a

Larian `e2e/production-guidance-readonly.spec.js` melalui
`scripts/audit/run-production-guidance-readonly.ps1` memerlukan kredensial superadmin produksi
yang **hanya pemilik** boleh bekalkan. Saya tidak pernah menciptanya atau menaipnya.

| Baris | Pengganti tempatan yang SUDAH hijau |
|---|---|
| konteks bantuan 6/25 → 25/25 | `HelpLauncherContextTest` 10 ujian + e2e `guidance.spec.js` (dataset kekal selepas interaksi Livewire, berubah selepas navigasi) |
| `helpUrl asal=livewire/update` → 0 | mekanisme sama: `originPath` `#[Locked]` ditetapkan dalam `mount()`, `render()` tidak lagi membaca `request()` |
| EN-leak crawl | `LocalisationTest` 8 ujian (kesempurnaan 4 fail kunci, 18/18 e-mel, 5 label, validasi BM) |

Mekanismenya terbukti; **angka crawl produksi** itu sendiri belum. Kedua-duanya berbeza dan
laporan ini tidak mencampurkannya.

⚠️ **Telemetri diisytihar DAHULU** (§9.3, pengajaran RR-11-01): larian itu akan MENULIS
`help_events`, `guidance_progress` dan token log masuk pada produksi. Tenant larian ialah
`smoke-<run_uuid>` — **bukan** `smoke` (yang merupakan gate deploy). Cleanup hanya menyentuh ID
yang dicipta oleh `run_uuid` larian itu.

## 4. Regresi keselamatan & isolasi yang disemak semula

| Perkara | Bukti |
|---|---|
| Isolasi silang-tenant (RR-02-04) | probe 404 dalam manifest `role_routes` (10 identiti) + ujian §9.2 (d): dua tenant → hasil identik, tiada slug dalam hasil |
| Antivirus fail-closed (S7) | 3/3 status ditolak, 0 `Record`, 0 media, 0 log + 1 kawalan `clean` |
| Pertanyaan carian mentah tidak disimpan | `query_hash` 64 aksara, teks asal tiada dalam rekod |
| Korpus bantuan tiada data peribadi | 0 e-mel · 0 URL mutlak · 0 route memaku slug tenant |
| Matriks keselamatan §0.6 S1–S7 | dijalankan setiap fasa; suite penuh 632 ✓ |

## 5. Ringkasan jujur

Jadual §1 mengandungi **30 baris**: **23 tercapai** · **4 tidak tercapai** (§2.1–2.4) ·
**3 menunggu kredensial** (§3). Dua item lagi dalam §2 (**2.5** `admin.storage-orders#2`,
**2.6** `public.help#2`) **bukan** baris jadual §9 — ia penemuan yang dibawa ke F9/F10, dan
disenaraikan di sana supaya ia tidak hilang, bukan untuk membesarkan kiraan kegagalan.

Dua daripada empat yang gagal (**2.1**, **2.2**) gagal kerana **sasaran metriknya sendiri lapuk**
— bukan kerana produk mundur. Dua lagi (**2.3**, **2.4**) ialah jurang produk sebenar yang audit
asal tidak pernah mengukur, dan kedua-duanya ditemui hanya kerana F8 membaca kod dua laluan
carian dan membandingkannya, bukan mempercayai bahawa "carian berfungsi".

Empat penemuan itu tidak ada dalam senarai asal audit. Itu hujah untuk fasa pengukuran
berasingan: F8 menemui perkara yang F0–F7 tidak boleh melihat, kerana ia satu-satunya fasa yang
tugasnya menyoal ukuran itu sendiri.

## 6. Artifak

```
bukti/plan-f8/metrik-f8.json                jadual §9, tiga paras, dijana
bukti/plan-f8/PENEMUAN-CENTERCOVERED.md     A/B + tentukur + kawalan dalaman
bukti/plan-f8/PENEMUAN-CARIAN.md            tiga penemuan carian + kesilapan saya sendiri
bukti/plan-f8/mobile-kohort-f8.json         124 langkah, definisi audit tepat
bukti/plan-f8/{ab-lama,ab-semasa}.json      A/B mentah
bukti/plan-f8/skrip/                        6 skrip boleh-ulang
docs/AKSES-PAGE-MENGIKUT-ROLE.md            DIJANA daripada role_routes
scripts/audit/generate-role-access-doc.mjs  penjana
tests/Feature/RoleAccessDocTest.php         4 penjaga (2 regresi dibuktikan)
tests/Feature/HelpSearchGateTest.php        6 penjaga §9.2 (2 regresi dibuktikan)
```

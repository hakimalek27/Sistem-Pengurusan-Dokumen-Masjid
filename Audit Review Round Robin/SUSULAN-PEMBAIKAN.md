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
| Liputan gate diassert agregator | tiada | 473 · **229** · 83 union | **83/473/172 union · missing 0 · extra 0 · overlap 0 · pass true** | ⚠️ **LENCONGAN** — nota A |
| Mismatch `role_routes` (3 pasangan) | belum diukur | 0 | **0** pada **410 pasangan identiti×route** (41 route × 10 identiti) | ✅ |
| Drift dokumen akses role | 8/8 role | 0 | **0 percanggahan** (premis diperiksa — nota B) | ✅ |
| Suite domain dalam gate CI | 0/3 | 2/3 wajib hijau | **`ci-domain` hijau dalam CI 31213031582** | ✅ |
| Gate antivirus intake fail-closed | 0 ujian | 3/3 status ditolak | **3/3** (`infected`·`unavailable`·`error`) + 1 kawalan `clean` | ✅ |
| Kohort: `resolved_to_generic` | 119/124 | ≤25/124 + allowlist | **38/124** | ✅ |
| Kohort: tajuk = penerangan | 77/124 | 0 | **0/124** (RUNTIME) | ✅ nota C |
| Kohort: tajuk terpotong tengah perkataan | 20/124 | 0 | **0/124** (RUNTIME) | ✅ nota C |
| CTA "Buat pada skrin" pada langkah tanpa tindakan | 20 | 0 | **0** (RUNTIME) | ✅ nota C |
| Kohort: placeholder `Langkah N` pada popover | 118 (katalog) | 0 | **0/124** (RUNTIME) | ✅ |
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

**Nota A — ⚠️ LENCONGAN yang memerlukan tandatangan pemilik: 172, bukan 229.**
§9 (PELAN-PEMBAIKAN.md:3532, 3535) mewajibkan **229** langkah tindakan dan black-box **229/229**.
Yang dihantar ialah **172**, dan dua perkara mesti dipisahkan dengan jelas:

- **Denominator berubah, bukan liputan berkurangan.** Invarian manifest ialah
  `wait_for_user: 172`; angka 229 datang daripada kiraan audit SEBELUM katalog diselaraskan
  (F5/F6 menukar banyak langkah kepada `wait_for_user: false`). Union tiga shard = **172 =
  jangkaan manifest**, dengan `missing 0 · extra 0 · overlap 0` — artifak CI dikomit:
  `bukti/plan-f8/bukti-larian/ci-coverage-gate-31213031582.json`.
- **Yang agregator BUKTIKAN ialah liputan ID, bukan pelaksanaan black-box.** Ia mengesahkan
  setiap ID langkah tindakan muncul dalam union shard. Ia **tidak** membuktikan setiap satu
  dipandu sebagai aliran black-box penuh. Dakwaan "black-box 229/229" **tidak** dihantar, dan
  saya tidak menandakannya hijau.

**Keputusan pemilik diperlukan:** terima 172 sebagai denominator baharu (dengan sebab di atas
direkod), ATAU tetapkan bahawa 229 mesti dicapai — yang bermakna mengembalikan `wait_for_user`
pada 57 langkah, iaitu memundurkan keputusan reka bentuk F5/F6.

**Nota C — 🔴 tiga baris DITARIK daripada ✅ oleh tentukuran saya sendiri.**
📄 `bukti/plan-f8/PENEMUAN-TENTUKURAN.md`
`metrik-f8.mjs` mengira ketiga-tiganya daripada **katalog**; asas audit ialah ukuran **RUNTIME**.
Dijalankan pada katalog commit audit `4e07a70`, alat itu memberi **0 pada KEDUA-DUA belah** —
ia tidak boleh menunjukkan pergerakan. Puncanya: 118/124 tajuk kohort ialah placeholder
`"Langkah N"` pada masa itu, jadi tour MENERBITKAN tajuk daripada arahan; dan medan
`wait_for_user` **tidak wujud** sama sekali (disahkan bebas oleh Codex: `oldMissing: 124`).
Definisi audit DITENTUKUR tepat pada data auditnya sendiri (77 · 20 · 20 dihasilkan semula),
jadi definisinya betul dan hanya sumber saya yang salah.

✅ **DISELESAIKAN.** Sisi semasa diukur pada popover SEBENAR dengan definisi yang sama
(`skrip/ukur-runtime-kohort-f8.mjs`, desktop 1440×1000, **124/124 popover dirender**):

```
title == description   : 0   (asas 77)
tajuk terpotong        : 0   (asas 20)
CTA "Buat pada skrin"  : 0   (asas 20)
placeholder "Langkah N": 0   (asas 118 dalam katalog)
```

Kedua-dua belah kini diukur dengan kaedah yang SAMA pada permukaan yang SAMA. Kesimpulan asal
saya (0 pada ketiga-tiganya) ternyata **betul** — tetapi buktinya salah sehingga langkah ini.
Nombor yang betul atas sebab yang salah tetap perlu dibetulkan; jika tidak, alat yang cacat itu
kekal dalam repo dan akan menipu fasa seterusnya.

**Nota B — drift dokumen akses role.** Premis pelan ("8/8 role berbeza") diperiksa dan
**ditolak sebagai percanggahan**. `guidance.spec.js:16-18` sudah membaca `expected_page_counts`
daripada manifest sejak F0. Beza yang tinggal ialah dokumen crawl 21 Julai, dan ia kini
**DIKIRA oleh penjana** (bukan prosa hard-coded — Codex #7):

```
14 pasangan role-halaman ditambah · 4 halaman UNIK · 0 HILANG
/app/{tenant}/bantuan · /analitik-bantuan · /tiket-sokongan · /log-aktiviti
```

⚠️ Versi pertama nota ini menulis "4 tambahan" tanpa membezakan **pasangan** daripada **halaman
unik**. Kedua-dua angka betul untuk perkara berbeza; menyebut satu sahaja mengelirukan.
Keempat-empat halaman unik itu ditambah **2026-07-22** (`f9e4e09`, `b9a5c30`) — sehari SELEPAS
crawl. **`0 hilang`** ialah angka yang menentukan: tiada apa dalam dokumen 21 Julai yang hilang
daripada manifest, jadi tiada percanggahan.
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

Jadual §1 mengandungi **31 baris**: **24 tercapai** · **4 tidak tercapai** (§2.1–2.4) ·
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

## 5A. Kepatuhan setiap item §9.1 / §9.1a / §9.2 / §9.3 — audit pusingan 1 menuntutnya

Versi pertama laporan ini menandakan §9.1/§9.1a sebagai "3 baris menunggu" dan itu **melebihkan
kesiapan**: kontrak itu mempunyai banyak item, bukan tiga hasil akhir. Codex pusingan 1 (#18,
#19, #20, #21) betul. Setiap item disenaraikan di sini dengan statusnya.

### §9.1 — matriks produksi 20 BrowserContext

| # | Item | Status |
|---|---|---|
| 1 | tour per role × viewport | ⏸ dalam spec, larian belum dijalankan |
| 2 | 3 query (tepat/salah ejaan/akronim) + tapisan role | ⏸ dalam spec |
| 3 | `bukti/plan-f8/route-manifest.json` DIKOMIT | 🔴 **belum dihasilkan** — hanya larian produksi boleh menjananya |
| 4 | read-only mutlak (tiada `ensureInboxFixture`) | ✅ dikunci dalam spec sejak F0 |
| 5 | kontrak runner §9.1a dibekukan | ✅ dibekukan F0 |
| 6 | semua halaman desktop DAN mobile ikut `role_routes` | ⏸ dalam spec |
| 7 | assert tepat 8 role + superadmin berasingan + public tanpa login | ⏸ dalam spec |
| 8 | assert tepat **20** context (`toBe(20)`) | ⏸ dalam spec |
| — | setiap route: 200 · `<main>` · 0 console error · 0 overflow · bantuan/carian/tour | ⏸ dalam spec |
| — | probe silang-tenant 404 setiap role tenant | ✅ dalam manifest (410 pasangan) · ⏸ live |
| — | context/localStorage terasing · sela login ≥15s | ✅ dikunci `guidance.spec.js:10,37-40` |

### §9.1a — kontrak keselamatan runner

| Item | Status |
|---|---|
| nama spec + wrapper dibekukan | ✅ `e2e/production-guidance-readonly.spec.js` + `.ps1` wujud |
| `workers=1` dipaksa wrapper · `-RunUuid` opsyenal-tapi-direkod · `-CleanupOnly` | ✅ dalam wrapper (F0) |
| tenant larian `smoke-<run_uuid>`, **bukan** `smoke` | ✅ dikunci |
| `diwan:audit-fixture` prepare/cleanup/inventory + validasi UUID/slug | ✅ `app/Console/Commands/AuditFixture.php` + `AuditFixtureCommandTest` |
| kata laluan rawak, tidak pernah ke stdout · superadmin di luar skop | ✅ dikunci F0 |
| validasi env tanpa mencetak nilai | ✅ dikunci F0 |
| inventori `before/created/after` + cleanup idempotent + ujian recovery | ✅ ujian F0 · ⏸ larian live |
| **larian sebenar + set bukti fasa** | 🔴 **belum** — disekat pada kredensial pemilik |

### §9.2 — gate carian

| Item | Status |
|---|---|
| indeks tepat 83 dokumen | ✅ **83** live (`bukti-larian/produksi-smoke-meili.txt`) |
| tiada data tenant/pengguna dalam dokumen | ✅ 9 medan disenaraikan; 0 e-mel/slug/domain |
| query biasa · salah ejaan · akronim | ✅ 11 · 12 · OCR 10 — dan 🔴 `DDMS` 0 (§2.2) |
| Meili mati/timeout → fallback berfungsi | ✅ ujian (a); ⚠️ ia `connection refused`, **bukan timeout** — dinyatakan sebagai had |
| hasil setara fallback vs Meili | 🔴 **TIDAK** — §2.3 |
| tapisan role/panel/permission · awam tidak nampak tenant | ✅ ujian (c)+(e) + `HelpCatalogTest` |
| query mentah tidak disimpan | ✅ `HelpCatalogTest` (`query_hash` 64 aksara) |
| step CI Meili: `SCOUT_DRIVER=meilisearch sync --delete` + assert 83 sebelum e2e | ✅ `ci.yml` lapis 1 (F0) |

### §9.3 — disiplin penutup

| Item | Status |
|---|---|
| manifest beku F0, set SAMA diukur | ✅ |
| tiga paras (kohort · katalog penuh · **family × role × viewport**) | ✅ pecahan SILANG ditambah (26 sel) selepas Codex #6 |
| empat kategori dipisahkan, tiada "baki" | ✅ |
| setiap angka pada denominator penuh | ✅ |
| persampelan dilabel "smoke", tidak menutup ID penemuan | ✅ A/B dilabel sampel bertujuan 24 langkah |
| telemetri diisytihar DAHULU | ✅ §3 |
| **kiraan telemetri SELEPAS larian** (token, `intended_url`, zon KL, luput, senarai cleanup) | 🔴 **belum** — bergantung larian |
| regresi CSV RR-04-02 | 🔴 **tidak disemak dalam F8** — dibawa ke F10 |
| round-robin mini Claude↔Codex 2 pusingan | ⏳ pusingan 1 SELESAI (23 penemuan, §5B); pusingan 2 selepas pembaikan |

## 5B. Audit pusingan 1 (Codex) — 23 penemuan, dan apa yang saya buat dengannya

📄 Laporan penuh: `bukti/plan-f8/RR-P1-CODEX.md`

Codex mengaudit F8 secara adversarial dan mengembalikan **23 penemuan**. Dua daripadanya
(kaedah tajuk bukan apple-to-apple; `wait_for_user` bukan definisi CTA) mengesahkan secara
bebas apa yang saya sudah temui melalui tentukuran sendiri — dan Codex memberi rujukan kod yang
lebih tepat (`HelpCatalog.php` untuk tajuk terbitan, `step-advance-plan.js` untuk CTA).

Yang paling memalukan, dan betul: **`ab-lama.json` dan `ab-semasa.json` yang laporan ini namakan
tidak wujud.** `cp` saya menggunakan `2>/dev/null` yang menelan kegagalannya, `/tmp` kemudian
dibersihkan, dan data A/B mentah hilang. Skrip kini menulis TERUS ke folder bukti (tiada langkah
salinan untuk gagal senyap), A/B dijalankan semula dan **menghasilkan semula 1/24 lawan 8/24
tepat**, dengan data per-langkah dan kriteria pemilihan sampel yang dinyatakan.

### Pendirian ke atas setiap penemuan

| # | Ringkasan Codex | Pendirian |
|---|---|---|
| 1 | kaedah tajuk bukan apple-to-apple | ✅ **DITERIMA** — sudah ditemui sendiri; kini diukur RUNTIME |
| 2 | `wait_for_user` bukan definisi CTA | ✅ **DITERIMA** — rujukan `step-advance-plan.js` lebih tepat drp saya; CTA kini dibaca drp teks butang popover |
| 3 | `metrik-f8.json` bercanggah dgn laporan (mobile 6 vs 45) | ✅ **DITERIMA** — medan dinamakan `mobile_defects_asas_beku` + rujukan ukuran sebenar |
| 4 | "410 entri × 10 identiti" melebihkan 10× | ✅ **DITERIMA** — dibetulkan kepada 410 pasangan (41 × 10) |
| 5 | 229 → 172 ditanda hijau | ✅ **DITERIMA** — kini LENCONGAN yang perlu tandatangan (nota A) |
| 6 | tiga paras bukan pecahan silang | ✅ **DITERIMA** — silang `family × role × viewport` ditambah (26 sel) |
| 7 | "4 tambahan, 0 hilang" hard-coded | ⚠️ **SEBAHAGIAN** — nombor memang hard-coded (dibetulkan: kini DIKIRA + diuji). Tetapi dakwaan "fail sejarah tiada dalam repo" **SALAH**: ia di root, `AKSES-PAGE-MENGIKUT-ROLE-PRODUCTION-2026-07-21.md` |
| 8 | gate dokumen boleh lulus walau role salah | ✅ **DITERIMA** — label→identiti + route per identiti/panel ditambah |
| 9 | ujian (a) terlalu longgar | ✅ **DITERIMA** — relevansi hasil diassert |
| 10 | ujian (b) tidak menguji korpus yang diindeks | ✅ **DITERIMA** — kini membina dokumen melalui laluan `SyncHelpIndex` yang sama |
| 11 | ujian (c) lulus secara vakum | ✅ **DITERIMA** — assert hasil awam TIDAK kosong dahulu |
| 12 | ujian (d) `pluck('id')` membuang data | ✅ **DITERIMA** — kini membandingkan objek penuh |
| 13 | ujian (e) tidak memanggil carian DDMS | ✅ **DITERIMA** — kini memanggil carian + case-insensitive |
| 14 | ujian (f) tidak menghubungi Meili | ⚠️ **SEBAHAGIAN** — betul; tetapi Meili tiada tempatan (§17 CLAUDE.md larang kredensial luar untuk lulus ujian). Bukti Meili kekal sebagai artifak produksi yang dikomit; ujian dilabel inferens struktur, bukan gate dua-enjin |
| 15 | 17/38 rapuh, tidak memodelkan carian | ✅ **DITERIMA** — dilonggarkan kepada arah + contoh yang disahkan, bukan kiraan tepat |
| 16 | dakwaan CI/live hanyalah prosa | ⚠️ **SEBAHAGIAN** — artifak `ci-playwright-json` MEMANG ada di CI (28 KB); ia tiada dalam set bukti saya. Kini dikomit: `ci-domain` 8/8, `ci-a11y` 11/11, coverage-gate, smoke+Meili produksi, log suite |
| 17–21 | banyak keperluan §9/§9.1/§9.1a/§9.2/§9.3 tidak disebut | ✅ **DITERIMA** — §5A menyenaraikan setiap item dengan status |
| 22 | A/B tidak boleh diaudit semula | ✅ **DITERIMA** — punca (cp gagal senyap) direkod; A/B dijana semula dgn artifak |
| 23 | kesimpulan centerCovered melampaui eksperimen | ✅ **DITERIMA** — had disenaraikan (§3A dalam PENEMUAN-CENTERCOVERED.md); cadangan "bersara" ditarik menjadi keputusan pemilik |

**Diterima 19 · sebahagian 3 · ditolak 0 · satu sub-dakwaan disangkal dengan bukti (#7).**

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

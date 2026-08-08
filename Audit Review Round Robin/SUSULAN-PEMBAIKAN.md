# SUSULAN-PEMBAIKAN — laporan F8 (§9) · ⚠️ **BELUM PENUTUP**

> **Status: F8 BELUM SIAP.** Round-robin §9.3 memberi **23 penemuan (pusingan 1)** dan
> **19 penemuan (pusingan 2)**; **8 kekal TERBUKA** (6 ditutup selepas itu) dan majoritinya **tidak** disekat oleh
> kredensial pemilik. Lihat **§5C**. Baris jadual yang terbukti melebih-lebih sudah ditukar
> daripada ✅ kepada ⚠️/🔴. Jangan baca dokumen ini sebagai pengesahan keluaran.

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
| Mismatch `role_routes` — lapisan A↔B | belum diukur | 0 | **0** pada **410 pasangan identiti×route** (41 route × 10 identiti) | ✅ |
| Mismatch `role_routes` — lapisan C (probe `actual_status`) | belum diukur | 0 | **TIDAK dalam manifest**: kesemua 410 `actual_status` = `null` | ⚠️ nota D |
| Drift dokumen akses role | 8/8 role | 0 | **0 percanggahan** (premis diperiksa — nota B) | ✅ |
| Suite domain dalam gate CI | 0/3 | 2/3 wajib hijau | **`ci-domain` hijau dalam CI 31213031582** | ✅ |
| Gate antivirus intake fail-closed | 0 ujian | 3/3 status ditolak | **3/3** (`infected`·`unavailable`·`error`) + 1 kawalan `clean` | ✅ |
| Kohort: `resolved_to_generic` | 119/124 | ≤25/124 + allowlist | **38/124** | ✅ |
| Kohort: tajuk = penerangan | 77/124 | 0 | **0/124** (RUNTIME) | ✅ nota C |
| Kohort: tajuk terpotong tengah perkataan | 20/124 | 0 | **0/124** (RUNTIME) | ✅ nota C |
| CTA "Buat pada skrin" pada langkah tanpa tindakan | 20 | 0 | **0** (RUNTIME **tempatan**) | ⚠️ nota E |
| Kohort: placeholder `Langkah N` pada popover | 118 (katalog) | 0 | **0/124** (RUNTIME) | ✅ |
| Tour `/log-masuk` ralat palsu | 100% | lulus | **lulus** (CI `ci-guidance`, disahkan live Deploy 5) | ✅ |
| Wizard label `Seterus` | rosak | `Seterusnya` | **betul** (F3) | ✅ |
| Default borang retensi | `auto_padam` | `semak` + dialog | **`semak`** (F4) | ✅ |
| axe serious (`link-name`) | 1 | 0 | **0** — langkah `Accessibility (axe)` 11/11 dalam CI | ✅ |
| E-mel kerangka EN | 9 diuji | 0/18 kelas **+ e-mel sebenar** | **0/18 render** · penghantaran sebenar **BELUM** | ⚠️ nota F |
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

**Nota F — ⚠️ e-mel: render diuji, PENGHANTARAN SEBENAR belum (Codex P2 #16).**
§9 menuntut *"Ujian render data-provider 18 entri **+ e-mel sebenar**"*. Yang dihantar ialah
bahagian render sahaja: `LocalisationTest` memanggil `toMail()` + `render()` bagi 18/18 kelas.
Laporan F3 sendiri menandakan penghantaran sebenar sebagai ⏳ *"semasa Deploy 3"*
(`bukti/plan-f3/LAPORAN-FASA-3.md:198`) dan tiada bukti ia pernah dijalankan.

⛔ **Saya tidak menjalankannya.** `diwan:staging-check --mail-to=<alamat>` MENGHANTAR e-mel
sebenar daripada sistem produksi; menghantar mesej bagi pihak pemilik memerlukan kebenaran
pemilik. Arahan tepat, untuk pemilik jalankan sendiri:

```
ssh ubuntu@43.156.242.188 'cd /opt/diwan && sudo docker compose exec -T -e HOME=/tmp app   php artisan diwan:staging-check --mail-to=<alamat-anda> --json'
```

Kemudian **baca** e-mel itu dan sahkan kerangkanya Bahasa Melayu (bukan "Hello!/Regards,").

**Nota E — ⚠️ CTA `0` diukur TEMPATAN dan metriknya BERGANTUNG DOM (Codex P2 #2).**
Saya menganggap CTA ditentukan oleh medan `wait_for_user`, jadi `0` akan bebas persekitaran.
**Salah**, dan kodnya menunjukkannya — `step-advance-plan.js:60`:

```js
const menungguTindakan = step.wait_for_user || next.target !== step.target;
```

"Buat pada skrin" boleh muncul walaupun `wait_for_user` **palsu**, apabila sasaran langkah
seterusnya BERBEZA dan **tidak kelihatan** (`isVisible(next)`, baris 55). Jadi CTA bergantung
pada apa yang ada dalam DOM — dan DOM bergantung pada benih dan tenant.

Asas audit ialah **produksi, tenant `smoke`**; ukuran saya **tempatan, tenant `mam`, benih demo**.
`0` yang saya ukur adalah benar **untuk persekitaran itu**, tetapi ia **bukan** perbandingan
apple-to-apple dengan 20 pada produksi. Baris ini kekal ⚠️ sehingga larian produksi §9.1
mengukurnya di tempat yang sama seperti audit.

⭐ Ini kali KEDUA andaian "medan katalog menentukan tingkah laku runtime" menipu saya dalam F8
yang sama (kali pertama: tajuk terbitan, nota C). Corak yang perlu diingat: **jika metrik
diberi nama mengikut apa yang PENGGUNA lihat, ukurlah pada apa yang pengguna lihat.**

**Nota D — 🔴 dakwaan "3 pasangan" saya MELEBIH-LEBIH (Codex P2 #18).**
§9 menuntut `expected_access` ↔ `declared_access` ↔ `actual_status` — **tiga** lapisan. Diukur:
kesemua **410** `actual_status` dalam manifest ialah `null`, dan pembina `mismatches` sengaja
mengabaikan `actual` yang null. Jadi manifest membuktikan **dua** lapisan, bukan tiga.

Lapisan C **memang** dijalankan — `PlanManifestTest` "role_routes lapisan C: probe HTTP sebenar
sepadan expected_status" berjalan bagi **10 identiti** pada SETIAP larian suite (dilihat hijau
dalam larian 632). Tetapi tiada artifak per-pasangan dikomit, jadi ia tidak boleh diaudit
selepas larian. Baris itu kini ⚠️, bukan ✅.

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

Jadual §1 mengandungi **32 baris** — dikira secara mekanikal terhadap jadual itu sendiri, bukan daripada ingatan:

```
✅ tercapai            21
🔴 tidak tercapai       4   (§2.1–2.4)
⚠️  bersyarat/lencongan  4   (A: 172 lawan 229 · D: lapisan C · E: CTA · F: e-mel sebenar)
⏸  menunggu kredensial  3   (§3)
```

Dua item lagi dalam §2 (**2.5** `admin.storage-orders#2`,
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
| 5 | kontrak runner §9.1a dibekukan | ✅ dibekukan F0 — **dan 7 itemnya kini DIUKUR tempatan**, lihat `bukti/plan-f8/LATIHAN-9.1-TEMPATAN.md` |
| — | **runner boleh menemui spec** | 🔴→✅ **DIBAIKI** — sebelum ini `No tests found` (`PENEMUAN-RUNNER-TIDAK-BOLEH-JALAN.md`); project kini BERSYARAT `E2E_PRODUCTION` selepas ia memecahkan kutipan semua project |
| — | **matriks tahan-gantung** | 🔴→✅ **DIBAIKI** — 1 monolit → **22 ujian** (20 konteks BERNAMA); inventori ditulis ke cakera per konteks, jadi gantung memberi "19/20 + yang hilang dinamakan" dan bukan sifar bukti |
| — | **gate carian bantuan hijau tanpa menguji** | 🔴→✅ **DIBAIKI** — status dibaca sebelum Livewire menggantikannya, jadi setiap keputusan tersasar SATU pertanyaan dan kedua-dua assertion lulus atas sebab yang salah. `LATIHAN-9.1-TEMPATAN.md` kecacatan 1 |
| — | **latihan matriks tempatan** | 🔴 **TERGANTUNG** selepas ~41 muatan halaman pada satu `POST /livewire/update`; `php -S` satu-benang; **0 bukti separa** kerana spec ialah SATU ujian monolitik |
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
| Meili mati/timeout → fallback berfungsi | ✅ ujian (a) `refused` + ujian (a2) **timeout sebenar**. Timeout DIUKUR 24,232 ms → **DIBAIKI kepada 2,085 ms** (`diwan.guidance.meilisearch_timeout`); (a2) bukan lagi opt-in |
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

## 5C. Audit pusingan 2 (Codex) — 19 penemuan · F8 **BELUM SIAP**

📄 `bukti/plan-f8/RR-P2-CODEX.md` · prompt `skrip/rr-f8-p2-prompt.txt`

Pusingan 2 bertanya satu soalan: adakah pembaikan pusingan 1 **benar-benar** menutup penemuan?
Codex menjalankan **counterexample sebenar** terhadap ujian yang saya kukuhkan, dan mendapati
beberapa daripadanya masih boleh hijau.

⭐ **#11 mengesahkan secara BEBAS** penemuan runner yang saya temui serentak: spec produksi tiada
dalam mana-mana project Playwright, jadi arahan wrapper memberi `No tests found`. Dua pengaudit,
dua laluan, satu kesimpulan. 📄 `bukti/plan-f8/PENEMUAN-RUNNER-TIDAK-BOLEH-JALAN.md`

### DITUTUP dalam pusingan ini (dengan counterexample dibuktikan merah)

| # | Perkara | Bukti penutupan |
|---|---|---|
| 5 | set medan hanya disemak pada dokumen PERTAMA | counterexample tepat Codex (`mosque_id`+`user_id` pada dokumen KEDUA) → MERAH, menamakan guide + medan |
| 10 | pembaca dokumen sejarah gagal-TERBUKA | heading dirosakkan → penjana kini **melempar**, bukan mengisytiharkan "KONSISTEN" |
| 11 | runner produksi tidak boleh berjalan | project `production-readonly` + `--project` dalam wrapper → `Total: 22 tests`. ⚠️ Pembaikan pertama memperkenalkan regresi (kutipan SEMUA project mati) — project kini BERSYARAT `E2E_PRODUCTION`; lihat `LATIHAN-9.1-TEMPATAN.md` kecacatan 2 |
| 18 | "0 mismatch pada 3 pasangan" | diukur: 410/410 `actual_status` = null → baris ditukar kepada ⚠️ (nota D) |
| 19 (sebahagian) | "23 assertion" lapuk | dibetulkan kepada 33 (kiraan sebenar) |

### DITUTUP selepas §5C mula ditulis (setiap satu dengan counterexample/ukuran)

| # | Perkara | Bukti penutupan |
|---|---|---|
| 8 | (f) bukan gate Meili — buang `steps_text` kekalkan semua hijau | senarai atribut diangkat jadi pemalar `SEARCHABLE_ATTRIBUTES` + dijaga; counterexample TEPAT Codex → MERAH |
| 9 | gate route dokumen vakum per-seksyen | setiap pasangan identiti×panel mesti HADIR; buang seksyen Juruaudit → MERAH `audit/app` |
| 15 | (a) refused, bukan timeout | ujian (a2) guna 192.0.2.1 (RFC 5737). **DIUKUR: 24,232 ms** sebelum fallback → halaman TERSEKAT 24s. **KINI DIBAIKI: 2,085 ms** (12×), hasil & enjin tidak berubah; had ujian diterbitkan drp config, jadi membuang tempoh = MERAH. Bukan lagi opt-in |
| 17 | `ab-mobile.sh` tanpa `-e`, artifak basi | `set -euo pipefail` + artifak dibuang sebelum larian |
| 1 (A/B) | artifak tiada provenance | `ab-lama` katalog **2026.07.22.2** hash `7c9b43ff…` → 1/24 · `ab-semasa` **2026.08.08.2** hash `bf0f8bcd…` → 8/24 — dalam fail |
| 4 (sebahagian) | pecahan silang bukan ukuran | blok `diukur_per_viewport` daripada dua artifak ukuran SEBENAR; dilabel SEPARA, status tidak dinaikkan ✅ |
| 23 (lanjutan) | "tidak dibuktikan tidak merosakkan UX" | 📸 bukti visual 390×664: sasaran kelihatan PENUH, tidak terlindung — `PENEMUAN-CENTERCOVERED.md` §3B |

### ✅ KESEMUA 19 penemuan pusingan 2 kini DITANGANI

| # | Penyelesaian | Bukti |
|---|---|---|
| 1 | provenance dalam artifak | `ab-*.json` bawa versi + hash katalog sendiri |
| 2 | CTA diturunkan ⚠️ | `step-advance-plan.js:60` — CTA bergantung DOM, bukan medan katalog (nota E) |
| 3 | tajuk diukur semula tanpa boilerplate | `p.diwan-tour-instruction`, 124/124 → masih 0 |
| 4 | pecahan viewport DIUKUR | `diukur_per_viewport` drp dua artifak sebenar; dilabel SEPARA |
| 5 | set medan pada SETIAP dokumen | counterexample `mosque_id` pada dokumen ke-2 → MERAH |
| 6 | objek PENUH dibanding | suntik nama tenant ke `summary` → MERAH |
| 7 | relevansi ikut KEDUDUKAN | hasil teratas mesti guide betul, diukur |
| 8 | atribut Meili dijaga | buang `steps_text` → MERAH |
| 9 | setiap identiti×panel mesti HADIR | buang seksyen Juruaudit → MERAH |
| 10 | pembaca sejarah gagal-TERTUTUP | heading rosak → MELEMPAR |
| 11 | runner boleh berjalan | project BERSYARAT `E2E_PRODUCTION` → `Total: 22 tests`; had menyeluruh dibuktikan (240s → tamat 246s) |
| 12 | fail dibaca/dipadam DALAM kontena | `Get/Set/Remove-ContainerFile` |
| 13 | cleanup ikut ID + delta diassert | inventori teredaksi; `run_scoped` disemak |
| 14 | jurang spec §9.1 ditutup | awam dapat main/overflow/tour/carian; superadmin dapat panel `app` |
| 15 | timeout SEBENAR diuji | 192.0.2.1 → **24,232 ms** diukur; opt-in |
| 16 | e-mel diturunkan ⚠️ (nota F) · axe DITOLAK dgn bukti | nama ujian membuktikan 5×2+1 |
| 17 | `set -euo pipefail` + artifak dibuang dahulu | A/B dijalankan semula, hasil sama |
| 18 | "3 pasangan" diturunkan ⚠️ | 410/410 `actual_status` = null (nota D) |
| 19 | nombor prosa jadi artifak | shard mentah (union 83/473/172) + dump Meili produksi |

**Bukan semuanya "dibaiki" — empat DITURUNKAN status kerana itulah jawapan yang jujur** (#2,
#16, #18, dan sebahagian #4). Menurunkan status ialah penyelesaian apabila dakwaan asal
melebih-lebih; menaikkannya semula tanpa ukuran baharu adalah yang tidak jujur.

### Pendirian jujur

**F8 BELUM SIAP.** Pusingan 2 menunjukkan laporan versi terdahulu menandakan beberapa baris ✅
yang sepatutnya ⚠️ atau 🔴.

**Kesemua 19 penemuan pusingan 2 kini ditangani** — 15 dibaiki dengan counterexample dibuktikan
MERAH, 4 diturunkan status kerana dakwaan asal saya melebih-lebih.

Yang KEKAL menghalang penutupan F8 bukan lagi senarai penemuan, tetapi **tiga keputusan pemilik**
dan **satu larian yang memerlukan kredensial**:

⭐ Satu perkara yang round-robin ini buktikan dengan jelas: **dua pusingan tidak mencukupi untuk
menutup fasa pengukuran ini.** Pusingan 1 memberi 23 penemuan, pusingan 2 memberi 19 selepas
pembaikan — kadar penemuan tidak menghampiri sifar. Audit pelan asal mengambil **27 pusingan**
untuk menumpu. §9.3 menetapkan "2 pusingan" sebagai minimum, bukan sebagai bukti konvergensi.

## 6. Artifak

Dikira terhadap direktori, bukan daripada ingatan: **8 skrip boleh-ulang + 2 prompt**,
**6 fail data**, **5 artifak larian**.

```
bukti/plan-f8/
  metrik-f8.json                    jadual §9, tiga paras, dijana
  runtime-kohort-f8.json            124 popover desktop (arahan teras, + provenance)
  mobile-kohort-f8.json             124 popover mobile 390x664
  mobile-centercovered-f8.json      enam langkah cacat asal
  ab-lama.json / ab-semasa.json     A/B, setiap satu membawa versi + hash katalognya
  gambar/                           skrinsyot beranotasi (TIDAK dikomit — bukti/.gitignore *.png)

  PENEMUAN-CENTERCOVERED.md         A/B + tentukur + kawalan dalaman + bukti VISUAL
  PENEMUAN-CARIAN.md                tiga penemuan carian + kesilapan saya sendiri
  PENEMUAN-TENTUKURAN.md            alat saya gagal tentukuran, dan pembetulannya
  PENEMUAN-RUNNER-TIDAK-BOLEH-JALAN.md   runner §9.1a `No tests found`
  LATIHAN-9.1-TEMPATAN.md           7 kontrak diukur · 1 gantung · 2 penemuan reka bentuk
  RR-P1-CODEX.md / RR-P2-CODEX.md   23 + 19 penemuan audit adversarial

  bukti-larian/
    ci-domain.json                  8/8 · 0 unexpected
    ci-a11y.json                    11/11 = 5 halaman × 2 viewport + 1 semakan-diri
    ci-coverage-gate-31213031582.json   83/473/172 · missing/extra/overlap 0 · pass true
    produksi-smoke-meili.txt        smoke 9/9 + stats & query Meili produksi
    pest-suite-632.txt              632 lulus / 2 skip

docs/AKSES-PAGE-MENGIKUT-ROLE.md            DIJANA + perbandingan sejarah DIKIRA
scripts/audit/generate-role-access-doc.mjs  penjana (gagal-tertutup)
tests/Feature/RoleAccessDocTest.php         4 penjaga — 3 regresi dibuktikan MERAH
tests/Feature/HelpSearchGateTest.php        7 penjaga — 6 regresi dibuktikan MERAH
playwright.config.js                        project `production-readonly` (bukan dalam CI)
scripts/audit/run-production-guidance-readonly.ps1   laluan kontena + cleanup ikut ID + delta
```

**Sepuluh regresi sengaja dibuktikan MERAH lalu dipulihkan** merentas kedua-dua fail ujian —
setiap satu menyasar lubang yang audit namakan, bukan lubang yang senang dibuktikan.

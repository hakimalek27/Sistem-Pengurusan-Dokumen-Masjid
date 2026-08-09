# F8 — enam perkara yang menunggu pemilik, dengan langkah TEPAT selepas setiap jawapan

**Tarikh:** 9 Ogos 2026 · **Kod:** `df4129d` · **Produksi:** `2325bec` (Deploy 14) · CI hijau
**Jadual §9:** 33 baris = **25 ✅ · 2 🔴 · 3 ⚠️ · 3 ⏸** · suite **635 lulus / 1 skip**

Setiap item di bawah tersekat pada anda, bukan pada kerja. Untuk setiap satu: apa yang diukur,
pilihan yang ada, dan **arahan tepat** yang saya jalankan sebaik anda menjawab. Tiada satu pun
memerlukan penyiasatan lanjut.

---

## 1. 🔴 `centerCovered` — bersara, laras, atau terima risiko?

**Diukur (kohort PENUH 124 langkah, 390×664):**
```
centerCovered ditanda MERAH              : 45/124
daripada 45 itu, sasaran TIDAK terlindung: 45/45     -> kadar positif-palsu 100%
metrik pengganti: menutup >=50% sasaran  : 0/124
metrik pengganti: sasaran luar viewport  : 0/124
```
📄 `bukti/plan-f8/PENEMUAN-CENTERCOVERED.md` §3B (bukti visual) dan §3C (kohort penuh)

| Jawapan anda | Yang saya buat |
|---|---|
| **(a) Bersarakan sebagai gate** *(cadangan saya)* | Tukar baris §9 kepada pemerhatian; luaskan penjaga "popover tidak menutup sasarannya sendiri" daripada 2 guide W0 kepada kohort mobile penuh; kemas `PELAN` metrik |
| (b) Kekalkan dan laras penempatan | Paksa `side: 'bottom'` pada viewport sempit dalam `help.js`, ukur semula 124 langkah, jangka popover berpindah menjauhi sasaran |
| (c) Terima risiko 45/124 | Tambah entri `risk-accepted` (6 medan + tarikh luput + tiket) mengikut prosedur manifest |

---

## 2. 🔴 Akronim `DDMS` — perbendaharaan kandungan, bukan keupayaan carian

**Diukur:** `DDMS` muncul **0 kali** dalam katalog. Enam akronim yang MEMANG ada semuanya
memberi hasil (`OCR` 10 · `AJK` 1 · `QR` 1 · `ZIP` 1 · `SLA` 1 · `PDF` 1).
📄 `bukti/plan-f8/PENEMUAN-CARIAN.md` §2

| Jawapan anda | Yang saya buat |
|---|---|
| (a) Tambah `DDMS`/`SPDM` ke `keywords` | Sunting `guides.json` + bump `catalog_version` + gate katalog penuh (3 shard + agregator) + deploy dengan `sync-help-index --delete` |
| (b) Pinda gate §9.2 guna akronim dalam korpus | Tukar gate kepada `OCR`; kemas §9.2 dan dokumen — **ini pindaan PELAN, jadi ia perlu kebenaran anda** |

---

## 3. ⚠️ Denominator **172** lawan **229**

**Diukur:** manifest F0 dibandingkan per kunci langkah — **58 hilang, 1 tambah** (bersih 57).
Kesemua 58 wajar; 3 yang dibendera diperiksa tangan dengan kriteria mekanikal (adakah sasaran
langkah BERIKUTNYA wujud tanpa pengguna bertindak?). Pembilang yang penting —
`action_steps_with_generic_target` — ialah **0 pada kedua-dua denominator**.
📄 `bukti/plan-f8/PENEMUAN-DENOMINATOR.md` · data: `denominator-229-vs-172.json`

| Jawapan anda | Yang saya buat |
|---|---|
| **(a) Guna 172** *(cadangan saya)* | Kekalkan invarian manifest; rekod 229 sebagai baseline F0 dalam dokumen |
| (b) Guna 229 | Kembalikan `wait_for_user: true` pada 57 langkah PEMERHATIAN — iaitu memulihkan CTA "Buat pada skrin" pada arahan BACA, aduan asal anda |

---

## 4. ⚠️ Nota E (CTA) — **sudah disediakan, tiada tindakan berasingan**

Metrik ini bergantung DOM, jadi ia mesti diukur pada produksi/tenant `smoke` seperti audit asal.
Spec §9.1a kini merakam `{guide, langkah, cta, wait_for_user, cacat_cta}` bagi setiap langkah
tour, jadi **ia tertutup dalam larian item 6** — tiada tetingkap kredensial kedua diperlukan.
⚠️ Data itu SEPARA (20 titik, bukan kohort 124 audit) dan akan dilaporkan begitu.

---

## 5. ⚠️ E-mel `diwan:staging-check --mail-to=`

Saya **tidak menjalankannya** kerana ia menghantar mesej bagi pihak anda. Jurang yang tinggal
ialah bukti SMTP Brevo mengekalkan Bahasa Melayu (pengekodan) — semua yang lain sudah disahkan
dengan merender templat di dalam kontena produksi.

**Anda jalankan:** `php artisan diwan:staging-check --mail-to=<alamat anda>` — kemudian beritahu
saya sama ada kerangka e-mel kekal BM, dan saya tutup baris itu.

---

## 6. ⏸ Kredensial superadmin produksi — 3 baris §9.1a

Runner sudah **bersedia dan terbukti**: matriks tempatan **20/20** (396 halaman, 0 ralat
console), had menyeluruh dibuktikan, inventori berperingkat, `cuba` menamakan laluan yang
menyekat, cleanup try/finally.

⚠️ **Tanpa pembaikan sesi ini, larian itu tidak akan pernah boleh hijau** — satu assertion
menuntut sifar ralat console sambil sengaja menjana satu (probe silang-tenant 404). Kesemua 16
konteks role tenant akan gagal. Sudah dibaiki dan dijaga.

**Anda bekalkan melalui `!` supaya nilainya tidak melalui saya:**
```
! $env:E2E_PROD_SUPERADMIN_EMAIL="<emel>"; $env:E2E_PROD_SUPERADMIN_PASSWORD="<kata laluan>"
```
kemudian saya jalankan wrapper §9.1a (`-TimeoutMinutes 120`), dan item 4 tertutup serentak.

---

## ⚠️ Dua perkara operasi yang mudah terlepas

1. **Deploy berikutnya WAJIB `diwan:sync-help-index --delete`.** F8 mengubah kandungan indeks
   (`steps_text` kini merangkumi tajuk langkah). Tanpa itu, dokumen lama kekal dan pembaikan
   kelihatan tidak berlaku. `guides.json` TIDAK disentuh, jadi tiada gate katalog diperlukan.
2. **Kata laluan yang pernah dihantar dalam sembang masih perlu ditukar.** Saya tidak
   menggunakannya dan tidak menyimpannya.

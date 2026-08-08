# F8 §9.2 — gate carian bantuan: keputusan, dan TIGA penemuan

**Tarikh:** 9 Ogos 2026 · **Produksi diukur:** `2325bec` (Deploy 14) · **Indeks:** `diwan_help_guides`
**Penjaga baharu:** `tests/Feature/HelpSearchGateTest.php` (6 ujian, 23 assertion)

## 1. Meilisearch produksi — LULUS

Diukur LIVE melalui klien Meilisearch di dalam kontena `app` (baca sahaja):

```
numberOfDocuments : 83          (tepat = bilangan guide katalog)   ✔
isIndexing        : false
medan dokumen     : document_id, guide_id, panel, roles, title, summary,
                    keywords, steps_text, troubleshooting_text
kebocoran         : e-mel 0 · slug `smoke` 0 · domain `bakwim` 0 · domain `mamkl` 0   ✔
```

Pertanyaan:

```
biasa       "klasifikasi surat"  11 hits
salah ejaan "klasifikas"         12 hits · "pelupusn" 7 · "kelulusn" 16     ✔ typo-tolerance
akronim DALAM korpus  OCR 10 · AJK 1 · QR 1 · ZIP 1 · SLA 1 · PDF 1         ✔
akronim TIADA korpus  DDMS 0 · SPDM 0 · XYZQ 0 (kawalan)
```

## 2. 🔴 Penemuan A — gate `DDMS` tidak boleh lulus seperti tertulis

§9.2 menuntut *"query akronim (`DDMS`) memulangkan hasil"*. Diukur: **`DDMS` muncul 0 kali
dalam katalog**. Jadi 0 hits bukan kegagalan enjin — istilah itu tiada dalam korpus, dan enam
akronim yang MEMANG ada semuanya memberi hasil.

**Ini soal perbendaharaan kandungan, bukan keupayaan carian.** Pilihan:
1. tambah `DDMS`/`SPDM` kepada `keywords` guide yang berkenaan (perubahan katalog → gate penuh
    + deploy) — masuk akal kerana sistem ini MEMANG sebuah DDMS dan pengguna mungkin menaipnya;
2. pinda gate supaya menggunakan akronim yang ada dalam korpus (`OCR`).

Ujian `(e)` mengassert `DDMS` masih 0 dalam katalog — jadi jika ia ditambah kelak, ujian MERAH
dan memaksa dokumen ini serta jadual §9 dikemas. Penemuan tidak boleh hilang secara senyap.

## 3. 🔴 Penemuan B — fallback PHP mencari korpus yang LEBIH KECIL daripada Meilisearch

Dibaca pada kod, bukan diandaikan:

```
fallback PHP  HelpCatalog::search:69      title + summary + keywords  SAHAJA
Meilisearch   SyncHelpIndex:70-71         + steps_text (INSTRUCTION) + troubleshooting_text
```

Diukur: **38 perkataan** hanya wujud dalam `instruction`. Meili menjumpainya; fallback tidak.
Disahkan hujung-ke-hujung dengan `taip` — **Meili 1 hit, fallback 0**.

Kawalan dijalankan supaya angka 0 itu bermakna: perkataan yang ADA dalam
`title/summary/keywords` memberi **10–12** hasil pada fallback yang sama, persona yang sama.

⚠️ §9.2 menuntut fallback memberi *"hasil setara untuk query mudah"*. Secara literal ini
**tidak dipenuhi**. Kesan sebenar: apabila Meilisearch mati, carian tidak sahaja jadi lebih
perlahan — ia jadi **lebih cetek**, secara senyap.

## 4. 🔴 Penemuan C — tajuk langkah TIDAK boleh dicari oleh mana-mana enjin

`steps_text` dibina daripada `instruction` sahaja. Tajuk langkah tidak diindeks, dan fallback
juga tidak melihatnya. Diukur: **17 perkataan** hidup HANYA dalam tajuk langkah — cth
`penapis`, `lajur`, `kuasa`, `diwakilkan`, `serah`. Kedua-dua enjin memberi **0**.

⭐ Ini bernilai kerana F6 melabur banyak untuk menjadikan tajuk langkah bermakna
(placeholder **258 → 0**). Teks itu kini tepat, deskriptif — dan **tidak boleh dicari**.
Menambah `pluck('title')` kepada `steps_text` ialah satu baris; ia akan menjadikan hasil kerja
F6 boleh ditemui. Saya tidak melakukannya di F8 kerana fasa ini ialah **pengukuran, tiada
deploy** — ia dicadangkan untuk F10 atau hotfix berasingan.

## 5. Apa yang penjaga baharu kunci

| Ujian | Menutup |
|---|---|
| (a) | Meili **MATI** (host ditolak) → fallback masih memberi hasil, dan telemetri merekod `engine=php` |
| (b) | tiada e-mel · tiada URL mutlak · tiada route memaku slug tenant sebenar |
| (c) | panel awam tidak pernah memulangkan guide tenant/admin |
| (d) | dua tenant → hasil IDENTIK (regresi isolasi RR-02-04) |
| (e) | akronim boleh dicari **dan** skop role dihormati; `DDMS` masih tiada dalam korpus |
| (f) | jurang J1=17 dan J2=38 dikunci — melebar/mengecil = ujian merah |

**Dua regresi sengaja dibuktikan** (bukan didakwa):
```
fallback dilumpuhkan (`if (false)`)        -> (a) MERAH
route dengan slug `mam` sebenar disisipkan -> (b) MERAH, mesej menamakan guide + route
kedua-duanya dipulihkan                    -> 6/6 hijau, git status 0 baris
```

## 6. Kesilapan saya sendiri dalam larian ini — direkod supaya tidak diulang

1. **Substring 3-aksara ialah fixture lemah.** Ujian (b) versi pertama memadan slug `mam`/`man`
   dan MERAH — bukan kerana kebocoran, tetapi kerana tiga aksara itu muncul dalam `mampu`,
   `mana`, `manual`. Diganti dengan vektor kebocoran sebenar (e-mel, URL mutlak, route berslug).
2. **Persona menentukan keputusan.** Ujian (e) mencari `AJK` sebagai `admin_masjid` dan mendapat
   kosong; saya hampir melaporkannya sebagai jurang fallback. Ukuran menunjukkan satu-satunya
   guide dengan `ajk` dalam badan carian ialah `workflow.ajk.*`, berskop role `ajk` — jadi kosong
   itu ialah **tapisan role yang berfungsi**. Ujian kini membuktikan kedua-dua arah.
3. **`engine` bukan kolum** — ia hidup dalam `metadata`. Membacanya sebagai atribut memberi
   `null`, yang akan LULUS jika assertion ditulis sebagai `not->toBe('meilisearch')`.
   Baca tempat sebenar, dan tulis assertion positif.
4. **"modal" ditolak sebagai contoh.** Ia memberi 36 hits pada Meili tetapi tidak hadir dalam
   mana-mana medan yang saya ekstrak — jadi hits itu datang daripada toleransi typo, bukan
   padanan sebenar. Contoh yang tidak difahami bukan bukti; `taip` digunakan sebaliknya.

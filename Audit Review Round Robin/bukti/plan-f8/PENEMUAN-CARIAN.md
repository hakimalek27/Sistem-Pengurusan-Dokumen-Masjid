# F8 §9.2 — gate carian bantuan: keputusan, dan EMPAT penemuan (B, C, D DIBAIKI)

**Tarikh:** 9 Ogos 2026 · **Produksi diukur:** `2325bec` (Deploy 14) · **Indeks:** `diwan_help_guides`
**Penjaga baharu:** `tests/Feature/HelpSearchGateTest.php` (6 ujian, 33 assertion)

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

## 3. ✅ Penemuan B (DIBAIKI) — fallback PHP dahulu mencari korpus yang LEBIH KECIL

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

**DIBAIKI.** Badan carian fallback kini merangkumi tajuk+arahan langkah dan teks penyelesaian
masalah, jadi korpusnya SEPADAN dengan `SEARCHABLE_ATTRIBUTES`. Dibuktikan dengan
counterexample: pulihkan badan lama → ujian (f) **MERAH** (`J2 dibuka semula: 'navigasi' …`).

## 4. ✅ Penemuan C (DIBAIKI) — tajuk langkah dahulu TIDAK boleh dicari oleh mana-mana enjin

`steps_text` dibina daripada `instruction` sahaja. Tajuk langkah tidak diindeks, dan fallback
juga tidak melihatnya. Diukur: **17 perkataan** hidup HANYA dalam tajuk langkah — cth
`penapis`, `lajur`, `kuasa`, `diwakilkan`, `serah`. Kedua-dua enjin memberi **0**.

⭐ Ini bernilai kerana F6 melabur banyak untuk menjadikan tajuk langkah bermakna
(placeholder **258 → 0**). Teks itu kini tepat, deskriptif — dan **tidak boleh dicari**.
**DIBAIKI.** `steps_text` kini merangkumi tajuk DAN arahan setiap langkah, jadi kerja F6
(placeholder 258 → 0) akhirnya boleh ditemui. Dibuktikan dengan counterexample: buang tajuk
daripada `steps_text` → ujian (f) **MERAH**.

⚠️ **Ini mengubah KANDUNGAN indeks.** Deploy MESTI menjalankan `diwan:sync-help-index --delete`;
tanpa itu dokumen lama kekal dan jurang kelihatan masih terbuka. Katalog (`guides.json`) TIDAK
disentuh, jadi tiada gate katalog diperlukan.
⚠️ **BELUM di-deploy** — produksi kekal `2325bec`.

## 4A. ✅ Penemuan D — Meili TERGANTUNG menyekat halaman 24 saat (DIBAIKI)

Ujian (a) asal menggunakan `connection refused` (port 1), yang kembali serta-merta. Laluan
**timeout** berbeza sama sekali, dan mengukurnya mendedahkan yang lebih buruk daripada
"carian jadi cetek":

```
DIUKUR SEBELUM : 24,232 ms   sebelum fallback PHP menyelamatkan hasil
DIUKUR SELEPAS :  2,085 ms   (12x)  — 10 guide, engine=php, tidak berubah
```

Klien Meilisearch dibina tanpa tempoh eksplisit, jadi ia mewarisi lalai yang panjang. Fallback
PHP sudah wujud dan pantas; satu-satunya yang hilang ialah keputusan untuk **berhenti menunggu**.

**Dibaiki:** `diwan.guidance.meilisearch_timeout` (lalai 2.0s, env `DIWAN_MEILISEARCH_TIMEOUT`)
dihantar kepada klien HTTP sebagai `timeout` + `connect_timeout`.
⚠️ **BELUM di-deploy** — produksi kekal `2325bec`; ia menunggu keputusan pemilik bersama baki F8.

Ujian (a2) menjaganya, dan hadnya DITERBITKAN daripada config (4× tempoh) supaya membuang
tempoh itu memerahkannya. Kerana laluan kini ~2s, ia berjalan setiap larian dan bukan opt-in.


## 4B. ⚠️ Kesan sampingan yang DIUKUR — dan pembaikan ketiga yang ia paksa

Menutup J2 sahaja **memecahkan dua ujian lain**, dan itu bukan gangguan — ia isyarat.

`(e) akronim` merah: **`SPDM` mula memberi 1 hasil** (`tenant.peti-masuk`) walaupun rentetan
`SPDM` **tiada** dalam katalog (assertion literal pada baris sebelumnya tetap hijau). Jadi ia
padanan **kabur**, bukan padanan sebenar.

Puncanya: fallback membenarkan **1 edit Levenshtein pada token 4-aksara**. Meilisearch tidak —
lalainya `minWordSizeForTypos` ialah 1 typo mulai **5** aksara, 2 typo mulai **9**. Itulah
sebabnya Meili produksi memberi `DDMS`/`SPDM` = **0**. Meluaskan korpus tanpa menyelaraskan
ambang typo menjadikan fallback **LEBIH LONGGAR** daripada Meili — iaitu divergensi baharu,
bukan pariti.

**Pembaikan ketiga:** ambang typo diselaraskan dengan Meilisearch. Diukur selepasnya:

```
SPDM  -> 0     DDMS -> 0          sepadan Meili produksi        ✔
taip  -> 1     penapis -> 1       J2 dan J1 DITUTUP (dahulu 0)  ✔
klasifikas -> 11   pelupusn -> 4  toleransi typo DIKEKALKAN     ✔
klasifikasi -> 11                 kawalan positif               ✔
zzqqxx-tiada-langsung -> 0        kawalan negatif               ✔
```

🔑 **Pelajaran:** "pariti" bermakna korpus yang sama **dan** peraturan padanan yang sama. Saya
menyelaraskan yang pertama sahaja dan hampir menghantar enjin yang lebih bising. Dua ujian yang
merah itulah yang menangkapnya — bukan saya.

## 5. Apa yang penjaga baharu kunci

| Ujian | Menutup |
|---|---|
| (a) | Meili **MATI** (host ditolak) → fallback masih memberi hasil, dan telemetri merekod `engine=php` |
| (b) | set medan dokumen dikunci pada **SETIAP** dokumen · tiada e-mel · tiada URL mutlak · tiada route memaku slug tenant |
| (c) | panel awam tidak pernah memulangkan guide tenant/admin — **dan** hasil awam mesti >0 dahulu |
| (d) | SET guide sama · laluan dikontekskan kepada tenant semasa · tiada silang slug |
| (e) | akronim boleh dicari **dan** skop role dihormati; `DDMS` masih tiada (katalog DAN carian) |
| (f) | jurang J1/J2 masih WUJUD + dua contoh disahkan pada laluan sebenar + kawalan positif |

**Empat regresi sengaja dibuktikan** (bukan didakwa):
```
fallback dilumpuhkan (`if (false)`)                  -> (a) MERAH
route dengan slug `mam` sebenar disisipkan            -> (b) MERAH, menamakan guide + route
mosque_id/user_id pada dokumen KEDUA (counterexample
  tepat Codex P2 #5)                                  -> (b) MERAH, menamakan guide + medan
carian awam dilumpuhkan (memulangkan kosong)          -> (c) MERAH — lubang vakum ditutup
semuanya dipulihkan                                   -> 6/6 hijau, git status 0 baris
```

⚠️ **Baris (d) dan (f) DIKEMAS selepas Codex pusingan 2.** Versi pertama mendakwa "hasil
IDENTIK" (premis salah — laluan memang dikontekskan) dan mengunci kiraan tepat 17/38 (rapuh
terhadap suntingan copy). Lihat `RR-P2-CODEX.md` #6, #8, #15.

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

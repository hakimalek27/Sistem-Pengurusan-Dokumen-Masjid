# F8 §9.1 — latihan matriks TEMPATAN: apa yang berjaya, dan apa yang TERGANTUNG

**Tarikh:** 9 Ogos 2026 · **Sasaran:** `http://127.0.0.1:8092` (BUKAN produksi)
**Tujuan:** melatih matriks 20 konteks tanpa kredensial produksi, supaya larian produksi kelak
menjadi satu arahan dan bukan satu eksperimen.
**Skrip:** `skrip/latihan-9.1-tempatan.sh` (menolak apa-apa sasaran selain 127.0.0.1).

## ✅ Yang DISAHKAN berfungsi

| Kontrak §9.1a | Bukti |
|---|---|
| `diwan:audit-fixture prepare` cipta tenant `smoke-<uuid>` + 8 akaun role | tenant id 3, akaun id 12–19, satu per role |
| kata laluan rawak, **tidak pernah** ke stdout | stdout memaparkan e-mel + id sahaja; *"Kredensial ditulis ke fail --json sahaja"* |
| slug mesti berawalan `smoke-` | `E2E_PROD_TENANT` divalidasi `^smoke-<uuid>$` oleh spec |
| runner boleh menemui spec | `Total: 1 test in 1 file` selepas pembaikan project (lihat `PENEMUAN-RUNNER-TIDAK-BOLEH-JALAN.md`) |
| `cleanup` padam ikut **inventori** | `{"users":8,"mosques":1,"login_tokens":0}` |
| `cleanup` **IDEMPOTENT** | larian kedua: `{"users":0,"mosques":0,"login_tokens":0}` · **exit 0** |
| tiada sisa | `smoke-*` baki **0** · akaun `@smoke.test` baki **0** |
| tenant lain tidak disentuh | tenant tinggal: `mam`, `man` — tepat seperti sebelum latihan |

Itu **tujuh** item kontrak §9.1a yang sebelum ini hanya "dalam spec"; kini ia diukur.

---

# Pusingan kedua (9 Ogos, petang) — latihan diulang selepas runner dibaiki

Pusingan pertama berhenti tanpa hasil. Tiga perkara dibaiki, dan **dua kecacatan sebenar
ditemui** dalam proses. Ini bahagian yang paling bernilai daripada keseluruhan latihan.

## 🔴 KECACATAN 1 — gate carian bantuan HIJAU tanpa menguji apa yang ia namakan

Ini kecacatan dalam **ujian**, bukan produk — jadi mengikut peraturan 9 CLAUDE.md ia dinyatakan
di sini secara eksplisit: ujian itu sendiri salah, dan ujian yang diubah.

`assertCarianBantuan()` menaip tiga pertanyaan, mengklik **Cari**, kemudian membaca
`.diwan-help-search-status` SERTA-MERTA. Livewire belum sempat menggantikan status, jadi yang
dirakam ialah keadaan LAMA. Diukur — inilah yang sebenarnya disimpan:

```
q1 "Peti Masuk"             -> "3 panduan disyorkan untuk Orang Awam."      <- teks AWAL
q2 "klasfikasi surat"       -> "2 hasil … untuk “Peti Masuk”"               <- hasil q1
q3 "zzqqxx-tiada-langsung"  -> "0 hasil … untuk “klasfikasi surat”"         <- hasil q2
```

Setiap keputusan **tersasar satu pertanyaan**. Kedua-dua assertion tetap LULUS:

- `hasil[2]` mengandungi `"0 hasil"` — tetapi itu hasil *q2*, bukan pertanyaan karut;
- `hasil[0]` tidak mengandungi `"0 hasil"` — tetapi itu teks awal halaman, bukan hasil carian.

⭐ Jadi gate ini hijau **walaupun carian tidak pernah dijalankan sama sekali**. Ia satu
daripada dua ujian yang sepatutnya membuktikan jurang §9.1 (2) ditutup pada produksi.

**Pembaikan:** menunggu status mencerminkan pertanyaan itu sendiri (status memaparkan
`… untuk "<query>"`, jadi ia penanda tepat untuk "kitaran INI selesai"). Selepas pembaikan,
larian yang sama pada halaman yang sama memberi:

```
q1 'Peti Masuk'            -> '2 hasil dalam skop Orang Awam untuk “Peti Masuk”'      SEJAJAR ✔
q2 'klasfikasi surat'      -> '0 hasil dalam skop Orang Awam untuk “klasfikasi surat”' SEJAJAR ✔
q3 'zzqqxx-tiada-langsung' -> '0 hasil dalam skop Orang Awam untuk “zzqqxx…”'          SEJAJAR ✔
```

Kini `q1` benar-benar memulangkan **2 hasil** dan `q3` benar-benar **0** — assertion lulus atas
sebab yang betul.

ℹ️ `q2` (salah ejaan) **DIREKOD tetapi TIDAK diassert**, dan sebabnya dinyatakan dalam kod:
toleransi typo datang daripada Meilisearch sahaja, jadi 0 hasil di atas ialah tingkah laku
fallback PHP yang SAH (bersetuju dengan `PENEMUAN-CARIAN.md` §3). Mengassertnya akan menjadikan
latihan tempatan mustahil — dan latihan itulah satu-satunya cara membuktikan runner sebelum
tetingkap kredensial pemilik dibuka. Nilainya ada dalam artifak (`carian[1]`) untuk dibaca pada
larian produksi.

## 🔴 KECACATAN 2 — `npx playwright test` tidak boleh mengutip apa-apa

Pembaikan runner pusingan pertama (mendaftarkan spec dalam project `production-readonly`)
memperkenalkan regresi yang saya sendiri sebabkan. Spec melempar pada peringkat **kutipan**
apabila env produksi tiada, dan kutipan yang melempar membatalkan **seluruh** larian:

```
npx playwright test --list   ->   Total: 0 tests in 0 files
```

**Pembaikan:** project itu kini bersyarat kepada `E2E_PRODUCTION` (wrapper menetapkannya).

⚠️ **Kejujuran ukuran:** ini BUKAN satu-satunya sebab larian telanjang gagal.
`guidance-full.spec.js:37` melempar dengan cara yang sama tanpa `GUIDANCE_SHARD`, dan itu
**sengaja** (F0(iv)(e): "skip senyap ialah gate palsu"). Jadi `npx playwright test` tanpa
argumen kekal gagal, dan itu tidak diubah — CI sentiasa membekalkan shard melalui matriks.
Yang diperbaiki hanyalah: spec produksi tidak lagi menjadi sebab **kedua**.

## 🔧 Reka bentuk: matriks dipecahkan kepada 20 ujian + bukti ditulis berperingkat

Pusingan pertama merekod bahawa §9.1 ialah satu `test()` monolitik dan mencadangkan
pemecahannya sebagai "perubahan spec, perlu keputusan". **Klasifikasi itu ditarik balik**, dan
sebabnya patut dinyatakan berbanding disembunyikan: pelan menetapkan fail ini sebagai
*"20 konteks, read-only mutlak, jarak login 15s"* — ia tidak pernah menetapkan **satu** `test()`.
Pemecahan mengekalkan ketiga-tiga syarat itu, jadi ia bukan lencongan.

Yang berubah:

- **22 ujian** = 1 kontrak set-role + **20 konteks bernama** + 1 kontrak penutup;
- setiap konteks menulis inventorinya ke **cakera** sebaik ia tamat — bukan di hujung larian;
- penulisan ke cakera (bukan memori) SENGAJA: apabila satu ujian tamat masa, Playwright boleh
  memulakan semula worker, dan kaunter dalam-memori akan hilang bersamanya. Keadaan dikunci pada
  `run_tenant` (`smoke-<uuid>` unik per larian), jadi fail larian lama dibuang sendiri sementara
  worker yang dimulakan semula menyambung — tiada pemadaman membuta;
- jarak log masuk 15s juga disimpan pada cakera, supaya worker yang dimulakan semula tidak
  melanggar had kadar 5/min produksi;
- kontrak penutup **MENAMAKAN** konteks yang hilang, bukan sekadar membandingkan kiraan.

⭐ **Dibuktikan berfungsi semasa larian, bukan selepasnya.** Diintai di tengah larian penuh:

```
selesai 2/20 · halaman terkumpul 41
sedang : ['desktop|admin_masjid']
```

Itu tepat maklumat yang pusingan pertama gagal hasilkan selepas ~41 muatan halaman.

## 📉 Gantung pembongkaran: DICIRIKAN, dan hipotesis saya sendiri DITOLAK

Playwright melaporkan `worker-0 process did not exit within 300000ms after stop, force-killed
it`. Empat kawalan dijalankan:

| Kawalan | Keputusan |
|---|---|
| Ujian tanpa pelayar (`kontrak: akaun fixture`) | **2s**, bersih — jadi ia milik penggunaan pelayar |
| Spec lain, mesin sama (`ci-guidance @session-canary`) | bersih — jadi ia bukan mesin semata-mata |
| Spec sebenar + config MINIMUM saya | **tergantung** — jadi ia bukan `playwright.config.js` |
| Repro minimum A–D (halaman biasa / tour / carian Livewire / pendengar console) | semua bersih |

Bisect laluan memberi jawapan yang kelihatan bersih — `/bantuan` tergantung, tiga laluan awam
lain bersih. **Ia tidak bertahan.** Kawalan seterusnya menjatuhkannya:

```
pelayan panduan-MATI  /bantuan (404)  ->  77s bersih
pelayan panduan-MATI  /              -> 306s TERGANTUNG      <- laluan yang sebelum ini bersih
ulangan  8092 /        pusingan 1/2   ->  22s / 188s  kedua-dua bersih
ulangan  8092 /bantuan pusingan 1/2   ->  73s / 307s  bersih, kemudian TERGANTUNG
```

Laluan yang sama memberi keputusan berbeza antara pusingan, dan masa dinding berayun 22s→188s
untuk arahan yang **identik**. Jadi ia **berselang-seli dan bergantung persekitaran**, bukan
ditentukan oleh URL. Hipotesis "puncanya `/bantuan`" DITOLAK oleh ukuran saya sendiri sebelum ia
sempat masuk ke mana-mana laporan.

Konteks mesin yang diukur: 47 proses `chrome.exe` hidup milik pengguna (~3 GB), termasuk
tetingkap kumpulan MCP yang beku — keluarga punca yang sama seperti penemuan **#73**. Hanya
**satu** proses Chrome tertinggal daripada Playwright, jadi ia bukan penimbunan sisa.

**Apa yang saya TIDAK dakwa:** saya tidak tahu punca akhirnya. Saya tidak menjalankannya pada
Linux CI untuk melihat sama ada ia Windows sahaja.

**Mengapa ia tidak lagi menjadi risiko keluaran:** kosnya **terbatas** (~5 minit sekali pada
hujung larian) dan ia **tidak lagi boleh memusnahkan bukti**, kerana inventori sudah berada di
cakera sebelum pembongkaran bermula. Itu perbezaan antara "larian produksi mungkin membazirkan
tetingkap kredensial pemilik" dan "larian produksi mungkin mengambil lima minit lebih lama".

## Nota kejujuran

Pusingan pertama menghasilkan tujuh pengesahan kontrak §9.1a dan dua penemuan reka bentuk.
Pusingan kedua menghasilkan **dua kecacatan sebenar** (satu gate palsu, satu regresi yang saya
sendiri perkenalkan), runner yang tahan-gantung, dan satu hipotesis yang ditolak sebelum
dilaporkan. Status §9.1 pada produksi kekal ⏸ — ia menunggu kredensial pemilik, bukan kod.

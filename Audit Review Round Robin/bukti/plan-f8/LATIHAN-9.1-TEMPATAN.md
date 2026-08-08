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


## ✅ Jaring keselamatan DIBUKTIKAN hujung-ke-hujung (bukan didakwa)

Had per-ujian **tidak mencukupi**, dan itu diukur: satu konteks kekal `mula` selama **11 minit
19 saat** melepasi hadnya sendiri (600s) tanpa gagal. Sebabnya struktur — had per-ujian
dikuatkuasakan DI DALAM worker yang terkunci itu. Wrapper produksi pula memanggil
`WaitForExit()` **tanpa argumen**, iaitu tunggu selama-lamanya. Itu tepat cara tetingkap
kredensial pemilik akan terbakar tanpa menghasilkan apa-apa.

Dibaiki pada dua aras, dan kedua-duanya diuji:

| Lapis | Bukti |
|---|---|
| `--global-timeout` (dikuatkuasakan proses UTAMA, bukan worker) | had 240s → larian tamat pada **246s** |
| `WaitForExit($ms)` + `Kill($true)` sebagai sandaran keras | wrapper `-TimeoutMinutes` (lalai 120); sintaks disahkan dengan parser PowerShell |
| bukti separa terselamat | `konteks selesai: 2/20 · halaman 41 · BELUM: desktop\|admin_masjid, desktop\|pengerusi` |
| cleanup tetap berjalan | `{"users":8,"mosques":1,"login_tokens":0}` · tenant kembali `mam, man` |
| kredensial fixture dipadam | fail `fixture-<uuid>.json` tiada selepas larian |

Playwright melaporkan `1 failed · 18 did not run · 3 passed` — iaitu hasil yang **boleh
dianalisis**, bukan kekosongan. Bandingkan dengan pusingan pertama: sifar bukti, tenant
tertinggal.

⚠️ Satu tenant fixture MEMANG tertinggal sekali dalam sesi ini — bukan oleh gantung, tetapi
kerana saya sendiri memotong shell latihan pada had 10 minit alat, jadi `trap` tidak sempat
berjalan. Ia dipadam serta-merta (`mam, man` · 0 akaun `@smoke.test`). Dicatat kerana ia
menunjukkan hadnya: trap melindungi daripada gantung dan Ctrl-C, bukan daripada shell yang
dibunuh dari luar. `-CleanupOnly -RunUuid <uuid>` ialah pemulihan untuk kes itu.

## 🟡 `desktop · admin_masjid` gagal — halaman SIHAT (lihat kesimpulan akhir di bawah)

Tiga larian, tiga kali konteks ini tidak selesai. Sekali ia memberi ralat yang boleh dibaca:

```
page.goto: net::ERR_ABORTED at /app/smoke-<uuid>/peti-masuk
  - navigating to "…/peti-masuk", waiting until "load"
```

Yang menjadikannya bernilai ialah kawalan dalam larian yang **sama**:

```
superadmin    -> /app/smoke-<uuid>/peti-masuk   status 200   ✔ (37 halaman lulus)
admin_masjid  -> URL yang SAMA                  ERR_ABORTED  ✘
```

URL sama, pelayan sama, larian sama — identiti berbeza, keputusan berbeza.

Diukur juga: **0** entri `local.ERROR`/`CRITICAL` dalam `laravel.log` hari itu, jadi pelayan
tidak melemparkan apa-apa. `ERR_ABORTED` ialah guguran pada aras pengangkutan, bukan 500.
Kiraan laluan menolak teori "terlalu berat": `admin_masjid` ada **29** laluan, superadmin **41**
dan lulus.

### Disiasat sehingga habis — halaman itu SIHAT

Daripada menghentikannya sebagai "mungkin php -S", laluan itu diprob terus sebagai
`admin_masjid` sebenar (tenant `mam`, akaun `admin_masjid@demo.test`), dengan pendengar
`requestfailed` yang memberi sebab guguran:

```
cubaan 1: status=200  722 ms
cubaan 2: status=200  713 ms
cubaan 3: status=200  754 ms
permintaan GAGAL: GET /favicon.svg :: net::ERR_ABORTED  (×2)
```

⭐ **`/peti-masuk` memberi 200 tiga kali daripada tiga, ~720 ms** — halaman itu tidak rosak untuk
role ini. Satu-satunya `ERR_ABORTED` dalam prob itu ialah **favicon**, dan favicon itu sendiri
sihat (`curl` → 200 dalam 1.5 ms, tiga kali). Chrome memang membatalkan permintaan favicon
apabila navigasi berikutnya bermula — iaitu tepat corak yang spec hasilkan, kerana ia melompat
dari halaman ke halaman tanpa henti.

**Kesimpulan yang disokong:** `ERR_ABORTED` dalam matriks ialah guguran pada aras **pengangkutan**
di bawah navigasi pantas pada pelayan `php -S` satu-benang — bukan kecacatan halaman. Bukti
menyokongnya dari tiga arah: halaman memberi 200 apabila diprob bersendirian, pelayan tidak
melemparkan apa-apa (0 `local.ERROR`), dan superadmin memuatkan URL yang SAMA dengan 200 dalam
larian yang sama.

**Yang masih TIDAK dibuktikan:** bahawa ia tidak akan berlaku pada produksi. Produksi berjalan
di belakang nginx + php-fpm berbilang-pekerja, jadi mod kegagalan `php -S` tidak wujud di sana —
tetapi itu alasan mekanistik, bukan ukuran. Larian produksi akan mengesahkannya, dan inventori
berperingkat akan menamakannya jika ia berulang.

**Langkah seterusnya yang dinamakan:** larian produksi berjalan di belakang nginx + php-fpm,
jadi kekeliruan `php -S` tidak wujud di sana. Jika `admin_masjid` menggugurkan `/peti-masuk`
pada produksi juga, ia kecacatan produk dan inventori berperingkat akan menamakannya. Jika
tidak, ia artifak pelayan latihan. Larian itu kini boleh menjawabnya kerana ia tidak lagi
kehilangan bukti.


## ⚡ Punca sebenar kelembapan tempatan: pertikaian kunci SQLite (27×)

Selepas `/peti-masuk` terbukti sihat, soalan yang tinggal ialah mengapa mesin ini tidak boleh
menjalankan larian panjang langsung. Jawapannya diukur, bukan diteka.

`php artisan serve` ialah `php -S` — satu-benang. Penyelesaian yang tidak memerlukan pakej
baharu: empat backend + proksi round-robin menggunakan modul `http` **terbina** Node
(`skrip/pelayan-berbilang.mjs`). Keputusan pertama mengecewakan:

```
8 permintaan selari -> 29,064 ms      (empat backend, sepatutnya ~2 pusingan)
```

Keselarian tidak menolong langsung, jadi sesuatu menyiri di belakang. `.env` tempatan:

```
DB_CONNECTION=sqlite
SESSION_DRIVER=database      <- setiap permintaan MENULIS baris sesi
CACHE_STORE=database         <- dan cache juga
```

Empat proses berebut satu kunci tulis SQLite. Dengan pemacu fail pada backend latihan sahaja
(`.env` TIDAK diubah):

```
8 permintaan selari ->  1,064 ms      27× lebih pantas
```

⭐ Ini menjelaskan tingkah laku yang sebelum ini kelihatan misteri: ayunan masa dinding
22s↔188s untuk arahan yang identik, `ERR_ABORTED` rawak pada dokumen utama, dan worker yang
terkunci pada larian panjang. Semuanya konsisten dengan permintaan yang beratur di belakang
kunci pangkalan data.

ℹ️ **Skop:** ini kekangan **mesin dev sahaja**. Produksi menggunakan PostgreSQL + Redis dan CI
menggunakan PostgreSQL, jadi tiada satu pun terjejas. Ia juga menerangkan sebab resipi CI dalam
pelan sendiri menetapkan `SESSION_DRIVER=file` pada langkah `serve` — nota itu kini mempunyai
angka di sebaliknya.


## 🧭 Kesimpulan akhir: gantung itu KUMULATIF, bukan milik mana-mana laluan

Penjejakan per-laluan (`cuba`) menjawab soalan yang tiga larian sebelumnya tidak dapat jawab.
Larian berjejak berhenti dengan artifak yang MENAMAKAN laluannya:

```
BELUM selesai : desktop|admin_masjid
cuba          : /app/smoke-ce3661af-…/peti-masuk        <- laluan ke-17 daripada 29
```

Kemudian laluan itu diprob bersendirian pada tenant `smoke` yang KOSONG — pemboleh ubah terakhir
yang belum diasingkan (prob "sihat" terdahulu menggunakan `mam` yang berdata demo):

```
cubaan 1: status=200  696 ms
cubaan 2: status=200  759 ms
cubaan 3: status=200  648 ms
kawalan /records: status=200  708 ms
permintaan GAGAL: (tiada)
```

**Hipotesis "tenant kosong" DITOLAK juga.** Kini tiga hipotesis saya sendiri telah gugur kepada
ukuran: "puncanya `/bantuan`", "`/peti-masuk` rosak untuk admin_masjid", dan "tenant kosong".

Yang tinggal, dan yang disokong oleh keseluruhan set ukuran:

| Bukti | Menolak |
|---|---|
| setiap laluan yang disyaki memberi 200 apabila diprob BERSENDIRIAN (3× setiap satu) | "laluan X rosak" |
| gantung mendarat pada laluan BERBEZA antara larian (`/bantuan`, kemudian `peti-masuk` ke-17) | "satu laluan tertentu" |
| ujian tanpa pelayar tamat 2s; spec lain keluar bersih | "mesin/persekitaran semata-mata" |
| CI Linux menjalankan matriks panduan penuh (83 guide / 473 langkah, 3 shard) dan HIJAU | "kod aplikasi" |

⭐ Maka: **gantung itu kumulatif dalam satu konteks pelayar** — ia muncul selepas belasan hingga
puluhan navigasi berturut-turut dalam konteks yang sama, pada Windows dengan
`channel: 'chrome'`, dan ia mendarat pada mana-mana navigasi yang kebetulan berikutnya. Ia bukan
kecacatan produk, dan bukan laluan tertentu.

**Kesan kepada §9.1:** matriks TEMPATAN tidak dapat disiapkan pada mesin ini — angka terbaik
kekal **2/20 + 41 halaman**, dan ia dilaporkan begitu. Larian produksi tidak lagi bergantung
kepada nasib untuk menghasilkan sesuatu: had menyeluruh menamatkannya, inventori berperingkat
mengekalkan apa yang sudah dilawati, `cuba` menamakan navigasi yang menyekat, dan cleanup
berjalan. Itu perbezaan antara larian yang boleh diulang secara berperingkat dan larian yang
mesti bermula semula dari kosong.

## Nota kejujuran

Pusingan pertama menghasilkan tujuh pengesahan kontrak §9.1a dan dua penemuan reka bentuk.
Pusingan kedua menghasilkan **dua kecacatan sebenar** (satu gate palsu, satu regresi yang saya
sendiri perkenalkan), runner yang tahan-gantung dengan had menyeluruh yang dibuktikan, satu
hipotesis yang ditolak sebelum dilaporkan, dan satu pemerhatian terbuka yang jujur
(`admin_masjid`). Matriks 20 konteks TEMPATAN **tidak** disiapkan — 2/20 ialah angka sebenar,
dan ia tidak dilaporkan sebagai apa-apa selain itu. Status §9.1 pada produksi kekal ⏸: ia
menunggu kredensial pemilik, dan kini runner sudah bersedia menerimanya.

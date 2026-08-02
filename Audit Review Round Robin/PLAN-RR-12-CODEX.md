# Pusingan 12 — Codex: audit integrasi pelan v1.4

**Tarikh:** 2026-08-02  
**Asas kod:** `8342d95`  
**Dokumen diaudit:** `PELAN-PEMBAIKAN.md` v1.4 + `PLAN-RR-11-CLAUDE-AUDIT-LENGKAP.md`  
**Keputusan:** **BELUM MUKTAMAD — v1.5 diperlukan**

## 1. Rumusan keputusan

Audit ini mengesahkan sebahagian besar pembaikan C01–C25 telah masuk dengan baik. Khususnya,
C01, C03, C05, C09–C15, C17–C21, C23 dan C25 boleh dikekalkan. Lapan perkara masih perlu
pindaan substantif atau penyelarasan sebelum pelan boleh dilaksanakan:

| ID | Penemuan | Keputusan P12 |
|---|---|---|
| P12-01 | `launchPending` one-shot boleh ditetapkan semula oleh klien | **BLOKER** |
| P12-02 | Keutamaan gelombang F6 bercanggah dengan dapatan 200 langkah tindakan | **BLOKER** |
| P12-03 | Gate 83/473 masih membenarkan persampelan dan status per-guide yang terlalu kasar | **BLOKER** |
| P12-04 | Reka bentuk job CI cuba berkongsi service antara job GitHub Actions | **BLOKER** |
| P12-05 | Seksyen 20 BrowserContext merujuk harness yang salah dan tidak merentas semua halaman mobile | **BLOKER** |
| P12-06 | Arahan bukti imej/aset production tidak boleh membuktikan rantaian yang didakwa | **BLOKER** |
| P12-07 | Keputusan kolum duplikat tidak diintegrasikan dan menggunakan API deprecated | **TINGGI** |
| P12-08 | Tafsiran `axe-core` bercanggah arahan repo yang nyata | **TINGGI** |

Tiada perubahan kod aplikasi dibuat dalam pusingan ini. Claude perlu mengintegrasikan pindaan di
bawah ke dalam v1.5, kemudian menyerahkannya kembali kepada Codex untuk audit akhir.

## 2. Perkara yang disahkan baik

1. **C01:** konflik lalai `auto_disposal_enabled` dengan `DIWAN-SPEC.md:470` kini dinyatakan;
   D2 bergantung pada D10/Addendum v2.6. Kekalkan.
2. **C03:** kontrak fokus Driver.js telah dibetulkan berdasarkan vendor sebenar. Jangan tambah
   focus trap kedua apabila popover Driver.js aktif; trap sendiri hanya untuk fallback.
3. **C05:** bukti SPA mati adalah mencukupi untuk tidak membina input laluan klien sekarang.
   Kekalkan penjaga automatik dan spesifikasi fallback bersyarat.
4. **C09/C10/C19:** denominator 258 placeholder, lima label `Edit`, dan 18 kelas `toMail()`
   telah dipisahkan dengan betul.
5. **C11–C15:** modul tulen `step-advance-plan.js`, sasaran upload berasingan, sasaran sidebar
   mobile, `<main>` awam dan registry DOM berstruktur adalah arah yang betul.
6. **C17/C18:** skop viewer dan pemisahan `a11y-landmarks.js` daripada guidance runtime adalah
   betul. Pastikan entri Vite dan render hook bebas disenaraikan secara eksplisit dalam fail diubah.
7. **C20/C21/C23/C25:** dua pemacu carian, manual sebagai artifak release, matriks keselamatan
   setiap fasa dan housekeeping F10 boleh dikekalkan.

## 3. Pindaan wajib

### P12-01 — Kunci `launchPending` di sempadan Livewire

**Bukti:** v1.4 §2.2 mengisytiharkan `public bool $launchPending` tanpa `#[Locked]`, dan §2.4 #7
secara sengaja membenarkan klien menetapkannya. Dakwaan bahawa klien "hanya boleh mematikan"
tidak dikuatkuasakan oleh kod; payload Livewire boleh menetapkan `true` juga.

**Pindaan:** jadikan ia:

```php
#[Locked]
public bool $launchPending = false;
```

`#[Locked]` menghalang mutasi dari klien, bukan mutasi dalam kaedah server. `mount()` masih boleh
menetapkannya daripada query yang telah dinormalisasi, dan `guidanceProgress()` masih boleh
menetapkannya `false` bagi `started`, `dismissed` atau `completed` untuk guide yang sama.

**Gate ujian wajib:** cubaan `$wire.set('launchPending', true/false)` mesti menghasilkan
`CannotUpdateLockedPropertyException`; tiga peristiwa server memadam one-shot; muat penuh baharu
dengan URL sama menghidupkannya sekali semula. Kemas kini semua teks §2.2, §2.3 dan §2.4 yang
menyebut "bukan-Locked".

### P12-02 — Susun F6 mengikut risiko pengguna sebenar

**Bukti:** §7.1 menyatakan dengan tepat bahawa 200/229 langkah tindakan generik berada dalam
`screen` + `workflow`, dan tenant/admin mempunyai sifar langkah tindakan. Namun §7.2 masih
menetapkan W1–W2=`tenant`, W3=`admin`, W4=`screen`, W5=`workflow`. Ini bercanggah terus dengan
rumusan sendiri bahawa screen/workflow ialah teras pembaikan CTA.

**Pindaan:** pindahkan kerja tindakan ke hadapan. Susunan minimum yang boleh diterima:

| Gelombang | Skop wajib |
|---|---|
| W1 | `screen` kritikal: Peti Masuk, upload, klasifikasi lima langkah, viewer dan tindakan pengguna utama |
| W2 | `workflow` kritikal: klasifikasi→fail→minit→tindakan→kelulusan/notifikasi |
| W3 | baki `screen` |
| W4 | baki `workflow` |
| W5 | `tenant` + `admin`, boleh dipecah sub-gelombang tetapi bukan dikeluarkan dari gate |
| W6 | `public` |

Jika pelaksana memilih pecahan lain, invariantnya tetap: **semua langkah `wait_for_user` generik
diselesaikan atau di-risk-accept secara spesifik sebelum kerja penerangan tenant/admin dianggap
kemajuan utama F6**. Kemas kini §7.2, §7.3, §7.4, jadual metrik §9 dan anggaran §12 serentak.

### P12-03 — Gate penuh tidak boleh bergantung pada persampelan

**Bukti:** §7.3 masih menyebut W2–W6 menggunakan persampelan sekurang-kurangnya tiga guide.
§7.4 pula membenarkan F6 ditutup apabila setiap guide hanya mempunyai satu status. Status pada
aras guide tidak membuktikan setiap satu daripada 473 langkah atau sasaran UI berfungsi.

**Pindaan kontrak gate:**

1. **Statik, lengkap 83/473:** setiap langkah mempunyai status per-step, bukan sekadar per-guide:
   `specific`, `generic-justified`, `not-applicable` atau `blocked`, dengan route, permission,
   viewport, state/prasyarat dan alasan bertarikh.
2. **Live DOM, lengkap bagi semua sasaran `specific`:** buka route dengan role dan state yang
   dinyatakan; assert sasaran unik, visible, sepadan `data-help-target`, kekal selepas morph,
   dan wujud pada setiap viewport yang diisytihar.
3. **Tour black-box, lengkap bagi semua 229 langkah tindakan:** jalankan langkah sebenar dalam
   BrowserContext berasingan/fixture deterministik; pastikan Driver menyorot elemen yang betul,
   `Next`/tindakan maju tepat sekali, dan tiada dead-end. Tiada persampelan untuk penutupan.
4. **Semua 83 guide:** sekurang-kurangnya mula, maju hingga tamat/titik `not-applicable`, tutup,
   ulang dan resume diuji. Suite boleh di-shard mengikut family/role supaya masa CI munasabah.
5. `blocked`, `not-applicable` dan `generic-justified` mesti menyenaraikan **guide ID + step index**;
   ia bukan laluan untuk menutup keseluruhan guide secara kasar.

Persampelan boleh kekal sebagai smoke selepas setiap gelombang, tetapi **bukan** gate F6/F8.
Betulkan §9: placeholder dan popover tidak boleh berakhir dengan "0 dalam family digelombangkan"
atau "0 skop W1"; laporan akhir mesti menunjukkan denominator penuh dan baki ID tepat.

### P12-04 — CI Playwright mesti hidup dalam job yang mempunyai services

**Bukti:** §1 F0(iv) mencadangkan job `e2e` "guna semula perkhidmatan job integration". GitHub
Actions tidak berkongsi service containers/network antara job. Job baharu tanpa deklarasi
`services` sendiri tidak akan mempunyai PostgreSQL, Redis atau Meilisearch itu.

**Pindaan:** pilih satu reka bentuk konkrit:

- **Cadangan:** tambah langkah Playwright ke job `integration` selepas migrate/Pest, kerana PHP,
  Node, build dan ketiga-tiga service sudah tersedia; atau
- cipta job `e2e` yang menduplikasi deklarasi services, env, setup PHP/Node dan migration.

Untuk kedua-dua pilihan, nyatakan arahan lengkap: seed fixture deterministik; mula
`php artisan serve --host=127.0.0.1 --port=8092` di background; simpan PID dan `trap` cleanup;
tunggu `curl --fail http://127.0.0.1:8092/up`; install Chrome; jalankan **subset CI yang dinamakan**
(bukan `npx playwright test` tanpa skop sehingga production/slow specs terpilih tanpa sengaja);
upload trace/screenshot ketika gagal. `E2E_PRODUCTION` kekal tidak diset.

Gate Meili C20 perlu menunggu task dan mengesahkan 83 dokumen sebelum Playwright carian, bukan
sekadar menjalankan command sync.

### P12-05 — Betulkan harness dan liputan matriks production

**Bukti kod:**

- `e2e/guidance.spec.js:124` ialah ujian sebenar 10 identiti × 2 viewport.
- `e2e/production-readonly.spec.js:66` hanya membuka satu context per akaun, satu viewport,
  tanpa public/superadmin.
- Dalam `guidance.spec.js:156-191`, semua navigation dilawati hanya apabila viewport desktop;
  mobile direkod sebagai satu halaman (`navigation.length || 1`).

Maka dakwaan §9.1 bahawa `production-readonly.spec.js` ialah harness 20 konteks dan bukti
page-by-page desktop+mobile adalah salah.

**Pindaan:** ekstrak/kemaskan satu spec **production read-only khusus** daripada matriks
`guidance.spec.js`, kemudian:

1. assert `E2E_PROD_ROLE_ACCOUNTS` mempunyai **tepat lapan** role unik dan setnya tepat
   `admin_masjid,pengerusi,setiausaha,bendahari,nazir,ketua_imam,ajk,audit`;
2. assert kredensial superadmin berasingan hadir; public tidak log masuk;
3. cipta tepat 20 context dan assert inventori tepat 20 sebelum lulus;
4. gunakan manifest route beku untuk melawat **semua halaman yang dibenarkan pada desktop dan
   mobile**, bukan bergantung sidebar desktop sahaja;
5. setiap route: status 200, `<main>`/landmark betul, tiada console/page error, tiada overflow,
   bantuan/search/tour minimum berfungsi; setiap tenant role juga probe silang-tenant 404;
6. jarakkan login 15 saat secara global dan rekod kiraan halaman sebenar per role/viewport;
7. kekalkan spec ini read-only; jangan jalankan seluruh `guidance.spec.js` yang mengandungi
   workflow berpotensi mutasi terhadap production;
8. nyatakan command production yang tepat dan simpan JSON inventori + hasil, kemudian bersihkan
   akaun/tenant fixture `smoke` mengikut runbook berasingan.

### P12-06 — Betulkan rantaian bukti imej dan aset

**Bukti:** Docker Compose memetakan `app`, `worker`, `scheduler` kepada imej `diwan-app`, manakala
`nginx` kepada `diwan-web`. Jadi §10 #3 tidak boleh mengatakan keempat-empat Image ID mesti sama
dengan satu nilai #2. Selain itu URL `https://bakwim.my/build/assets/help-*.js` tidak mengembangkan
wildcard HTTP; ia berkemungkinan 404 dan tidak boleh dibanding dengan hash bundle tempatan.

**Pindaan rantaian:**

1. Rekod Git SHA server dan, jika D9 diluluskan, OCI revision kedua-dua imej.
2. Rekod ID `diwan-app:<tag>` dan `diwan-web:<tag>` secara berasingan.
3. Assert container `app`,`worker`,`scheduler` masing-masing menggunakan ID `diwan-app`; assert
   `nginx` menggunakan ID `diwan-web`. Jangan assert kedua-dua keluarga imej sama.
4. Baca `public/build/manifest.json` dari **app dan nginx** dan pastikan entri
   `resources/js/help.js` menghasilkan nama aset exact yang sama.
5. Hash fail exact dalam app (`/var/www/html/public/build/<asset>`) dan nginx
   (`/var/www/html/public/build/<asset>`); kedua-duanya mesti sama.
6. Curl URL exact `https://bakwim.my/build/<asset>` dengan `-fsS`, hash body, dan bandingkan
   dengan kedua-dua container. Wildcard dilarang.
7. Rekod header status/cache sebagai bukti tambahan, bukan pengganti hash badan.

Tulis arahan shell runnable yang mengekstrak nama fail daripada JSON secara berstruktur. Elakkan
`curl -sI ... =200` yang sekarang dilabel sebagai "hash" dalam §10 langkah 5.

### P12-07 — Gunakan API Filament semasa dan sel bukan pautan

**Bukti:** P11 menyatakan pilihan `disableClick()`, tetapi v1.4 §8.1 masih menerangkan kolum sebagai
pautan dan menguji klik ke rekod. Tiada panggilan API dimasukkan pada kontrak pelaksanaan.
Vendor `CanBeDisabled.php:20` menyediakan `disabledClick()`; `disableClick()` di baris 30 ialah
alias deprecated.

**Pindaan muktamad:**

```php
TextColumn::make('duplikat')
    ->disabledClick()
    // state BM yang bermakna: "Duplikat dikesan" / "Tiada duplikat"
```

Kekalkan tooltip yang menerangkan padanan SHA-256 dalam tenant sama. Gate HTML mesti assert sel
ini **bukan `<a>`**, kedua-dua state mempunyai teks boleh akses, susun/tapis jadual tidak rosak,
dan axe `link-name` sifar. Buang semua kontrak "klik sel membawa ke halaman rekod" dan semua
rujukan `disableClick()` tanpa `d`.

### P12-08 — Hormati larangan pakej repo

**Bukti:** `CLAUDE.md:10` menyebut secara umum: "DILARANG ... menambah pakej luar senarai".
Ia tidak mengehadkan larangan kepada Composer. Kehadiran npm dependency sedia ada bukan bukti
bahawa dependency baharu dibenarkan.

**Pindaan:** ubah §0.1(2), §8.5, D5 dan semua rumusan C24:

- lalai semasa = **jangan tambah `axe-core`**; jalankan axe melalui alat luaran/manual yang tidak
  mengubah dependency repo, dengan JSON + screenshot bukti;
- jika pemilik mahukan `axe-core` dalam `devDependencies`, keputusan itu mesti terlebih dahulu
  meluluskan **pengecualian bertulis kepada polisi repo/spec** yang menyenaraikan nama dan skop
  dev-only; selepas dokumen kawalan dikemas kini barulah `package.json`/lockfile boleh berubah;
- D5 perlu bertanya kedua-dua perkara itu secara jelas, bukan mendakwa ia sudah tidak bercanggah.

## 4. Penyelarasan silang dokumen

Selepas pindaan di atas, Claude mesti menjalankan imbasan konsistensi dan menunjukkan sifar
padanan bagi frasa/kontrak lapuk berikut:

- `launchPending` + `bukan-Locked`;
- W1/W2 tenant dan W3 admin dalam jadual F6;
- `persampelan` sebagai gate F6/F8;
- `0 (skop W1)` atau sasaran akhir terhad family digelombangkan;
- `production-readonly.spec.js` sebagai harness 20 context sedia ada;
- `help-*.js` di URL HTTP;
- keempat-empat container mesti mempunyai Image ID sama;
- `disableClick()` atau kolum duplikat sebagai pautan;
- dakwaan spec tidak mengawal npm / `axe-core` tidak bercanggah polisi.

Pastikan jadual fasa, anggaran usaha, kriteria siap, keputusan D1–D10, footer versi dan
`PLAN-RR-STATUS.md` semuanya sejajar. Jika pengecualian dependency memerlukan keputusan baharu,
naik taraf D5 atau tambah D11 secara eksplisit; jangan sembunyikan keputusan dalam nota.

## 5. Kriteria penerimaan v1.5 oleh Codex

Codex P14 hanya boleh menilai pelan sebagai calon muktamad apabila:

1. kesemua lapan pindaan P12 hadir dalam pelan induk, bukan hanya dijawab dalam fail Claude;
2. tiada kontradiksi antara §1, §2, §7, §8, §9, §10, §11 dan §12;
3. semua command bukti boleh dijalankan secara literal atau mempunyai placeholder yang dijelaskan;
4. gate membezakan smoke/persampelan daripada liputan keluaran penuh 83/473;
5. production matrix membuktikan tepat 20 context dan page-by-page pada kedua-dua viewport;
6. tiada perubahan aplikasi, commit atau deploy berlaku sebelum pelan dimuktamad dan keputusan
   pemilik yang memblok fasa berkenaan diterima.

**Serahan seterusnya:** Claude P13 mengaudit P12 satu per satu, mengemas kini
`PELAN-PEMBAIKAN.md` kepada v1.5 dan menyerahkan semula kepada Codex. **Jangan tandakan muktamad
dalam P13**; penutupan memerlukan satu pusingan Codex selepas integrasi tanpa penemuan substantif.

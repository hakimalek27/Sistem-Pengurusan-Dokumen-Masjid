# Pusingan 14 — Codex: audit integrasi pelan v1.5

**Tarikh:** 2026-08-02  
**Asas kod:** `8342d95`  
**Pelan diaudit:** v1.5, SHA-256 `470A4A8102E8EE4FB736F7045D3EDD936E13EF6FF548D81380F86C3322E7ED32`  
**Keputusan:** **BELUM MUKTAMAD — v1.6 diperlukan**

## 1. Keputusan integrasi P12

Kesemua P12-01…P12-08 telah masuk ke pelan induk dan arah pembetulannya betul. Khususnya:

- `launchPending` kini `#[Locked]` dan dipadam oleh mutasi server;
- urutan F6 mendahulukan `screen`/`workflow`;
- G1–G5 membezakan smoke daripada gate penuh 473/229/83;
- CI tidak lagi mendakwa boleh berkongsi services antara job;
- jurang matriks mobile dan set lapan role dinyatakan;
- rantaian aset membezakan `diwan-app`/`diwan-web` serta nama aset exact;
- `disabledClick()` ialah API pilihan semasa;
- `axe-core` tidak ditambah tanpa pengecualian polisi bertulis.

Namun audit kebolehjalanan literal dan kesinambungan end-to-end menemui lapan baki di bawah.

## 2. Pindaan wajib v1.6

### P14-01 — CI Playwright tidak boleh login dengan `SESSION_DRIVER=array`

**Bukti:** `.github/workflows/ci.yml:56` menetapkan `APP_URL=http://127.0.0.1:8080`, baris 67
menetapkan `SESSION_DRIVER=array`, sedangkan pelan menghidangkan aplikasi pada port 8092.
`ArraySessionHandler.php:17,61-75,83-88` menyimpan sesi hanya dalam array instance handler.
Untuk browser yang membuat request login kemudian redirect/request baharu, konfigurasi ini bukan
kontrak sesi HTTP persisten yang selamat dijadikan gate.

**Pindaan:** langkah Playwright mesti menetapkan secara eksplisit:

```bash
APP_URL=http://127.0.0.1:8092
SESSION_DRIVER=file
E2E_BASE_URL=http://127.0.0.1:8092
```

untuk proses `artisan serve`/Playwright, pastikan `storage/framework/sessions` wujud dan writable,
kemudian buat readiness probe `/up` **dan** satu canary login+redirect sebelum suite penuh.

Selain itu §1 F0(iv) masih berkata "subset CI yang dinamakan dalam PR" tanpa menyatakan command.
Bekukan senarai/test project dalam pelan, contohnya project Playwright `ci-guidance` dengan
`testMatch` exact, lalu arahan tunggal `npx playwright test --project=ci-guidance`. Jangan tunda
definisi skop ke PR kerana P12 mensyaratkan arahan literal dan mengelakkan spec production/slow
terpilih tanpa sengaja.

### P14-02 — Gate G1–G5 perlu reka bentuk CI berlapis dan shard, bukan berharap job 30 minit

**Bukti:** job `integration` mempunyai `timeout-minutes: 30`. Pelan kini menambah setup Chrome,
server, Meili, suite Playwright sedia ada, kemudian akhirnya 473 status + semua sasaran specific +
229 langkah tindakan + 83 kitaran guide. §7.3 hanya berkata "boleh di-shard" tanpa bilangan,
project, pembahagian family atau required-check aggregator. Menunggu job melebihi 30 minit dahulu
baru memindahkannya bukan reka bentuk gate yang deterministik.

**Pindaan:** bezakan dua lapis CI:

1. `integration` kekal Pest + **smoke Playwright kecil** dengan sesi HTTP persisten;
2. job matrix `guidance-e2e` berasingan mengisytiharkan services/env/setup sendiri dan shard
   deterministik, sekurang-kurangnya `screen`, `workflow`, `tenant-admin-public`;
3. setiap shard mempunyai denominator/manifest sendiri dan timeout realistik;
4. job agregator `guidance-e2e-gate` menjadi required check dan gagal jika mana-mana shard gagal,
   hilang atau melaporkan denominator tidak lengkap;
5. artifak shard digabung dan assert global tepat 473/229/83 tanpa pertindihan atau ID hilang.

Playwright `--shard` atau project berasingan boleh dipilih, tetapi command, services dan pemetaan
ID→shard mesti dibekukan dalam F0 sebelum kerja F6 bermula.

### P14-03 — Manifest bantuan bukan manifest akses halaman mengikut role

**Bukti:** F0(ii) membekukan `cohort` dan `catalogue` guide. Ia tidak membekukan senarai route yang
setiap identiti boleh akses. §9.1 kemudian menyuruh spec mobile melawat "semua halaman yang
dibenarkan" menggunakan manifest itu, tetapi tiada struktur role→route atau expected access.
Dokumen lama juga telah drift: `AKSES-PAGE-MENGIKUT-ROLE-PRODUCTION-2026-07-21.md:12` merekod
Admin/Kerani 21 halaman, sedangkan `e2e/guidance.spec.js:14` menjangka 25; role lain juga berubah.

**Pindaan:** F0 mesti menambah set ketiga, `role_routes`, yang dijana semula daripada kod semasa:

- identiti: public, superadmin dan tepat lapan role tenant;
- bagi setiap identiti: route template, panel, permission/policy, expected status, perlu tenant,
  kategori read-only/mutation, serta desktop/mobile;
- expected page count dikira daripada array, bukan nombor tangan;
- setiap route visible mesti ada dalam manifest dan setiap entri manifest mesti boleh dibuka;
- route sensitif yang tidak dibenarkan turut ada sebagai negative matrix dengan expected 403/404;
- fail Markdown akses pengguna dijana daripada manifest sama supaya ia tidak drift lagi.

Matriks production dan manual mesti membaca sumber berstruktur yang sama. `catalogue` help tidak
boleh dijadikan pengganti kerana satu route boleh berkongsi beberapa guide dan satu guide tidak
menerangkan semua role yang boleh mengakses halaman itu.

### P14-04 — Runner production dan lifecycle fixture belum boleh dijalankan literal

**Bukti:** §9.1 #5 hanya berkata command exact akan direkod dalam bukti fasa; pelan sendiri tidak
menamakan fail spec akhir, wrapper, pemboleh ubah wajib atau command. Ia juga menyebut tenant
sementara `smoke` dan kemudian "bersihkan akaun/tenant fixture", walaupun slug statik boleh sudah
wujud dan penghapusan luas berisiko memadam fixture terdahulu.

**Pindaan:** tetapkan dalam pelan:

- nama spec akhir, contohnya `e2e/production-guidance-readonly.spec.js`;
- wrapper tunggal, contohnya `scripts/audit/run-production-guidance-readonly.ps1`, yang validate
  semua env tanpa mencetak rahsia dan menjalankan `workers=1`;
- command exact wrapper;
- `run_uuid` dan slug unik `smoke-<run_uuid>` untuk setup yang dibuat khusus larian;
- inventori before/created/after bagi tenant, users, login tokens, `help_events` dan
  `guidance_progress`;
- cleanup hanya ID yang dicipta oleh `run_uuid`, tidak pernah padam berdasarkan slug umum;
- `try/finally` cleanup serta arahan recovery idempotent jika Chrome/proses terhenti;
- spec browser kekal read-only; setup/cleanup dijalankan oleh command pentadbiran berasingan
  dan diaudit.

Tanpa kontrak ini, "bersihkan fixture" bukan bukti production safety.

### P14-05 — W1/W2 masih anggaran; enam kerosakan mobile diketahui ditangguh terlalu lama

**Bukti:** §7.2 menggunakan `~10` guide W1 dan `~6` guide W2 serta label "teras 200" tanpa senarai
ID atau denominator per gelombang. Pelan sendiri mengakui inventori belum dibuat. Ini membuka ruang
untuk guide sukar dipindahkan ke "baki" dan menjadikan metrik per-gelombang tidak boleh diaudit.

Selain itu enam langkah popover mobile yang sudah terbukti rosak ditangguhkan ke W5 hanya kerana
family-nya `tenant`. Ia ialah defect pengguna sedia ada, bukan kerja penerangan biasa, dan tidak
wajar menunggu empat gelombang selepas F2.

**Pindaan:** F0 menambah medan `wave` pada setiap guide/step dan membekukan:

- senarai ID exact W1–W6;
- jumlah guide/langkah/action-generic/placeholder/mobile-defect setiap wave;
- jumlah silang wave mesti tepat 83/473/229/200/258/6 tanpa duplikat atau baki;
- enam langkah mobile yang diketahui masuk W1 sebagai hotfix rentas-family, atau sub-gelombang
  W0 selepas F2; kesemuanya diuji desktop+mobile sebelum W2;
- perubahan wave selepas freeze memerlukan sebab, diff denominator dan kelulusan dalam bukti.

### P14-06 — Status `blocked` bercanggah dengan gate kitaran penuh

**Bukti:** G1/G5 membenarkan langkah berstatus `blocked`, tetapi G4 mewajibkan semua 83 guide
maju hingga tamat atau `not-applicable`; tiada tingkah laku bagi langkah blocked. §7.4 pula boleh
menutup F6 dengan baki bersebab. Ini membolehkan guide tidak berfungsi dilabel blocked sambil
pelan mendakwa kitaran penuh lulus.

**Pindaan:** bezakan:

- `blocked` = **release blocker**, tidak boleh menutup F6/F8;
- `risk-accepted` = pengecualian pemilik yang nyata, dengan ID/step, impak, mitigasi fallback
  artikel, tiket, pemilik dan tarikh tamat;
- G4 bagi risk-accepted mesti menguji fallback pengguna yang sebenar, bukan berpura-pura tour
  selesai;
- laporan akhir mengasingkan `passed`, `not-applicable`, `risk-accepted`, `blocked`; sasaran
  `blocked=0` wajib.

### P14-07 — Arahan grep gate manual tidak sah seperti ditulis

**Bukti:** §9A.3 menggunakan:

```bash
grep -rn "\bEdit\b\|\bSeterus\b(?!nya)" "Manual Penguna/"
```

GNU grep basic regex tidak menyokong negative lookahead `(?!nya)`. Dalam bentuk ini bahagian
`Seterus` tidak memberikan gate yang didakwa.

**Pindaan:** guna command yang benar-benar disokong dan jadikan exit code gate, contohnya:

```bash
! rg -n '\b(Edit|Seterus)\b' 'Manual Penguna/'
```

Jika teks sejarah memang perlu mengandungi perkataan itu, hadkan carian kepada output manual
persona atau gunakan allowlist fail/line yang nyata. Audit juga tukar gate bundle C11 daripada
`grep -c` berbilang fail kepada `! rg -n '__diwanHelpTest|__DIWAN_E2E__' public/build/assets/help-*.js`
supaya exit status, bukan tafsiran output `filename:0`, menentukan lulus/gagal.

### P14-08 — Bekukan snapshot round-robin dan kemas status giliran

P12 dan P13 merujuk dua keadaan v1.4 yang berbeza kerana pelan untracked boleh berubah di antara
bacaan. P12 melihat teks harness `production-readonly`, manakala hash asas P13
`B688A481…F612719` sudah mengandungi teks lain. Oleh itu dakwaan "P12 salah" atau "v1.4 tidak
pernah menyebutnya" tidak boleh dibuktikan tanpa snapshot immutable; ia tidak patut digunakan
sebagai keputusan produk.

**Pindaan proses:** setiap versi seterusnya merekod hash+saiz+mtime sebelum audit, dan salinan
snapshot/read-only atau commit dokumen dibuat sebelum giliran bertukar. Satu ejen tidak boleh
menukar versi yang sedang diaudit ejen lain.

`PLAN-RR-STATUS.md` juga masih bertajuk **"Konteks untuk Claude"** dan membawa arahan P13 walaupun
giliran sudah Codex P14. Kemas kini konteks kepada ejen semasa pada setiap serahan.

## 3. Kriteria penerimaan v1.6

Codex hanya boleh mengesyorkan penutupan apabila:

1. CI canary login membuktikan sesi browser persisten pada 8092 dan command/project exact wujud;
2. smoke job dan sharded full gate mempunyai services, denominator dan required aggregator jelas;
3. `role_routes` membekukan semua halaman public/superadmin/lapan role serta negative matrix;
4. production runner mempunyai nama, command, `run_uuid`, unique tenant dan cleanup idempotent;
5. W1–W6 mempunyai ID/kiraan exact dan enam defect mobile tidak ditangguh ke W5;
6. `blocked=0` ialah syarat release dan risk acceptance mempunyai fallback/tamat tempoh;
7. semua command audit menggunakan sintaks/exit code yang sah;
8. status dan snapshot hash konsisten, dan hanya fail perancangan berubah.

**Serahan seterusnya:** Claude P15 mengaudit P14-01…P14-08 dan menerbitkan v1.6. Jangan tandakan
muktamad dalam P15; Codex P16 perlu mengaudit integrasi itu.

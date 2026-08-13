# F8 — satu baris CSS, dua kecacatan: `body.driver-active` menyembunyikan butang bantuan

**Tarikh:** 12 Ogos 2026 · **Pencetus:** CI merah berselang-seli selepas `838b28a`

CI gagal pada TIGA larian berturut-turut dengan TIGA profil berbeza, pada kod yang
**bait-untuk-bait identik** dengan larian hijau terakhir:

```
4a99151        Guidance smoke   2 gagal   (login-submit strict-mode · F2d fokus→BODY)
008bcf4        Guidance smoke   1 gagal   (F2d fokus→BODY)
008bcf4 ulang  Session canary   1 gagal   (help-launcher HIDDEN, diselesaikan 61×)
bedb325        (semuanya)       HIJAU
```

`git diff 838b28a 008bcf4 -- e2e/guidance.spec.js resources/ app/ playwright.config.js
.github/ config/ package*.json` = **KOSONG**. Jadi bukan regresi — dua kecacatan **terpendam**
yang didedahkan oleh mesin CI yang lebih perlahan/sibuk.

## Pemboleh yang dikongsi

`resources/css/help.css:76` — satu-satunya peraturan yang menyembunyikan butang itu:

```css
body.driver-active .diwan-help-launcher-button { visibility: hidden; }
```

Reka bentuk itu betul (butang tidak sepatutnya menghalang tour). Masalahnya: **dua** perkara
bergantung pada butang yang sama, dan kedua-duanya tidak melindungi diri daripada peraturan ini.

---

## Kecacatan A — PRODUK: fokus tidak pulang selepas ESC

`resources/js/help.js` → `clearFocusManagement()` memulangkan fokus kepada butang Pembantu
Diwan selepas tour ditutup. Versi lama mencuba **SEKALI, secara buta, pada 50 ms**.

### Mekanisme dibuktikan pada pelayar sebenar (kawalan DUA HALA)

```
semasa tour AKTIF          : driverActive=true · launcher visibility=hidden
focus() semasa kelas ADA   : activeElement = BODY            ← no-op SENYAP
focus() selepas kelas TIADA: activeElement = help-launcher   ← focus() yang SAMA berjaya
```

Jadi apabila Driver.js lambat membuang kelasnya (mesin sibuk), percubaan 50 ms itu tidak
berkesan, **tiada percubaan kedua dijadualkan**, dan fokus kekal pada `<body>` selama-lamanya.
➡️ Pengguna papan kekunci yang menekan ESC kehilangan tempatnya sepenuhnya.

### Pembaikan

Berhenti meneka MASA; ukur HASIL. `focus()` dipanggil, kemudian **diperiksa** sama ada ia
mendarat; jika tidak, cuba semula setiap 50 ms sehingga 2 saat, dan berhenti serta-merta jika
pengguna memindahkan fokus sendiri. Kami sengaja TIDAK meramalkan kebolehfokusan — predikat itu
sendiri boleh salah untuk elemen `position:fixed`.

### Bukti

| Larian | Keputusan |
|---|---|
| Penjaga baharu terhadap kod **LAMA** | 🔴 `Expected: "help-launcher" · Received: "BODY"` |
| Penjaga + ujian F2d asal terhadap kod **BAHARU** | ✅ 2 lulus |
| Penjaga deterministik, **8×** bawah beban CPU | ✅ **8 lulus · 0 gagal** |

Penjaga baharu (`guidance.spec.js` — "teardown LAMBAT tidak boleh menghilangkan fokus")
**tidak menunggu perlumbaan menembak**: ia MEMAKSA keadaan lambat dengan mengekalkan
`body.driver-active` 400 ms selepas ESC, dan mengandungi kawalan anti-vakum yang menuntut
simulasi itu benar-benar berlaku.

### ⭐ Disahkan HIDUP pada produksi (MCP Chrome, 12 Ogos)

Ini bukan artifak CI. Diukur pada **bakwim.my**, halaman AWAM (`/log-masuk?panduan=public.login`),
tanpa kredensial dan tanpa sebarang mutasi — hanya bacaan DOM dan satu penukaran kelas dalam tab
saya sendiri yang dipulihkan serta-merta:

```
aset dihidang        : help-Cfwb6f_j.css · help-Ckg4e8Xm.js
peraturan dalam CSS  : body.driver-active .diwan-help-launcher-button { visibility: hidden; }

semasa tour aktif    : driverActive=true  · visibility=hidden  · focus() → BODY
selepas kelas dibuang: visibility=visible                      · focus() → help-launcher
```

➡️ Pengguna papan kekunci di bakwim.my yang menutup tour dengan ESC **hari ini** kehilangan
tempatnya. Kecacatan ini menunggu Deploy F10 bersama F8/F9.

⚠️ Nota kaedah: `KeyboardEvent` sintetik untuk ESC **tidak** diterima Driver.js (peristiwa tidak
dipercayai), jadi laluan ESC penuh tidak boleh diautomasikan dari konsol. Yang diuji di sini
ialah MEKANISMEnya — dan mekanisme itulah yang menentukan hasil ESC.

---

## Kecacatan B — HARNESS: canary menggunakan penanda yang tour sah menyembunyikannya

`e2e/ci-session-canary.spec.js` mengassert `[data-help-target="help-launcher"]` **KELIHATAN**
selepas log masuk. Pada DB yang baru di-seed, pengguna demo TIADA `GuidanceProgress`, jadi
`HelpLauncher::render()` menetapkan `autoStart` dan tour bermula sendiri.

### Diukur (kemajuan panduan dikosongkan dahulu — lihat perangkap di bawah)

```
    0 ms : driverActive=false · launcher=kelihatan
  300 ms : driverActive=false · launcher=kelihatan
  700 ms : driverActive=TRUE  · launcher=TERSEMBUNYI
 3000 ms : driverActive=TRUE  · launcher=TERSEMBUNYI
           sidebar · topbar · main · menuPengguna  KEKAL kelihatan sepanjang masa
```

Tour tidak tamat sendiri, jadi sebaik ia bermula assertion itu menjadi **mustahil dipenuhi** —
poll 30 saat habis. Itu tepat yang CI rakamkan: *61 × locator resolved to `<a … help-launcher>`*,
sentiasa hidden. Ia perlumbaan tulen: menang pada mesin pantas, kalah pada yang sibuk.

### Pembaikan

Canary kini mengassert penanda yang **DIUKUR** kekal kelihatan semasa tour aktif
(`.fi-user-menu` — dirender hanya untuk pengguna berautentikasi) dan menuntut launcher itu
**WUJUD** (susun atur panel dirender) tanpa bergantung pada keterlihatannya.

### Bukti — assertion lama lwn baharu, dalam keadaan yang SAMA (tour aktif)

```
{"driverActive":true,"lama_launcherKelihatan":false,"baharu_menuPenggunaKelihatan":true,
 "baharu_launcherWujud":1,"borangLogMasuk":0}

assertion LAMA  (launcher KELIHATAN)       ❌ GAGAL
assertion BAHARU (menu + wujud + 0 borang)  ✅ lulus
```

**Canary TIDAK dilemahkan** — kawalan: `.fi-user-menu` memberi **0 padanan** pada `/app/login`,
`/log-masuk`, dan `/`. Ia benar-benar penanda khusus-berautentikasi.

---

## Dua risiko yang pembaikan itu SENDIRI perkenalkan — dicari, diukur, ditutup

1. **Tetingkap 2 saat boleh merampas fokus tour BERIKUTNYA.** Pembaikan A menukar satu tembakan
   50 ms kepada gelung sehingga 2 saat. Jika tour baharu bermula dalam tempoh itu, fokus berada
   pada `body` seketika — jadi pengawal "pengguna sudah pindah" TIDAK menghalang gelung lama
   daripada merampas fokus ke launcher di tengah tour baharu. Tetingkap 50 ms yang lama
   menyembunyikan kelas pepijat ini; 2 saat tidak. Ditutup: ID pemasa disimpan dan DIBATALKAN
   pada kedua-dua laluan — `clearFocusManagement()` dan permulaan tour baharu (laluan kedua itu
   tidak melalui yang pertama).
2. **`.fi-user-menu` boleh memadan DUA elemen.** Filament merender komponen `user-menu` dalam
   `topbar.blade.php` **dan** `sidebar.blade.php`; dua padanan bermakna `toBeVisible()` melanggar
   mod ketat — iaitu kecacatan yang SAMA jenis dengan `.driver-active-element` yang memadan 2
   elemen dalam kegagalan CI asal. Diukur sebelum commit, bukan diandaikan:
   `desktop {"menu":1,"launcher":1} · mobile {"menu":1,"launcher":1}` — tunggal pada kedua-duanya.

## ⚠️ Dua perangkap ukuran yang hampir menyesatkan saya

1. **Probe pertama saya menunjukkan "tiada auto-mula" dan saya hampir menolak hipotesis B.**
   Puncanya: larian ujian F2d saya sendiri, beberapa minit sebelum itu, telah memulakan tour
   `tenant.dashboard` dan **mencipta baris `GuidanceProgress`** — yang kemudiannya menyekat
   auto-mula. Keadaan ujian tercemar oleh ujian terdahulu. Kemajuan MESTI dikosongkan sebelum
   setiap ukuran auto-mula; CI mendapat keadaan segar percuma melalui `migrate:fresh --seed`.
2. **Perbandingan "sebelum/selepas" saya bagi B pada mulanya TIDAK BERMAKNA.** Saya merekod
   asas "1 gagal drp 6" dan selepas pembaikan "1 gagal drp 8", dan hampir melaporkannya sebagai
   penambahbaikan. Apabila diperiksa, KEDUA-DUA kegagalan itu ialah **Canary (1)** — tamat masa
   log masuk 60s kerana beban CPU buatan melaparkan pelayan `php -S` satu-benang — bukan
   penanda langsung. Nisbah yang bertambah baik boleh mengukur perkara yang salah sepenuhnya.
   Bukti sebenar bagi B ialah perbandingan deterministik di atas, bukan nisbah itu.

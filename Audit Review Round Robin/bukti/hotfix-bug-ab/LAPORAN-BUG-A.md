# LAPORAN BUG-A — Pendaratan selepas log masuk + halaman awam mengabaikan sesi

**Sumber:** laporan pemilik, 5 Ogos 2026 (bukan penemuan audit, bukan fasa pelan).
**Asas kod:** `aaf381a` (produksi Deploy 6 = `cc9f0c7`).
**Skop:** dua gejala yang dilaporkan, dan HANYA itu.

> "admin panel, selepas log masuk kenapa terus ke masjid tenant, xke panel admin? tekan home
> di admin panel atau taip bakwim.my selepas log masuk. dia pergi ke page log masuk. sedangkan
> dh log masuk td."

---

## (a) Ringkasan

1. **Gejala 1 — mendarat dalam masjid tenant, bukan panel admin.** Punca: pautan "Log masuk
   dengan kata laluan" pada `/log-masuk` dipaku keras ke **`/app/login`**
   (`request-magic-link.blade.php:25`), dan `LoginResponse` lalai Filament ialah
   `redirect()->intended(Filament::getUrl())` — `getUrl()` memakai panel **SEMASA**. Untuk
   superadmin, `User::getTenants()` memulangkan **SEMUA** masjid aktif, jadi tenant lalai =
   masjid pertama platform. Hasilnya superadmin mendarat dalam sebuah masjid, tanpa ralat.
   Magic link (§15.1) SUDAH ada peraturan pendaratan yang betul (`superadmin → /admin`); hanya
   log masuk kata laluan tidak tahu peraturan itu.
   **Pembaikan:** peraturan dipusatkan dalam `App\Services\PanelLandingResolver` dan dipakai
   oleh KEDUA-DUA laluan. `intended()` dikekalkan supaya deep-link masih menang.

2. **Gejala 2 — halaman awam nampak seperti "dilog keluar".** Punca: `/`, `/log-masuk`,
   `/daftar`, `/bantuan` tidak pernah menyemak sesi; nav hanya ada "Log Masuk". Diukur pada
   produksi dengan sesi pemilik yang HIDUP: `adaSesi: false` pada `/`, dan `/log-masuk`
   memaparkan borang minta pautan seperti tetamu.
   **Pembaikan:** nav + kad "Akses Sistem" + kad log masuk kini menawarkan **"Ke Panel" /
   "Teruskan ke Panel"** apabila sesi aktif, menuju pendaratan peranan yang sama.

**Yang dinyatakan sebagai BUKAN punca (diukur, bukan diandaikan):**
- Pautan *home* dalam panel admin **tidak** menuju halaman log masuk. Diukur pada produksi:
  `/admin` = **19 anchor, 0** menuju `/`, `https://bakwim.my` atau `/log-masuk`; logo topbar
  DAN logo sidebar kedua-duanya `https://bakwim.my/admin`. Halaman `/admin/bantuan` = 15
  anchor, 0 keluar panel. Jadi laluan sebenar ialah nav awam / bar alamat → laman utama, dan
  laman utama itulah yang tidak mengenal sesi (gejala 2).
- `/` **tidak** mengalih ke log masuk. `GET /` = 200 dengan HTML laman utama.

---

## (b) Fail dicipta / diubah

| Fail | Perubahan |
|---|---|
| `app/Services/PanelLandingResolver.php` | **BAHARU** — satu sumber kebenaran pendaratan §9.A + `urlForCurrentUser()` |
| `app/Filament/Auth/PanelLandingLoginResponse.php` | **BAHARU** — ganti LoginResponse lalai Filament |
| `app/Filament/Auth/Login.php` | `mount()` (sesi aktif → pendaratan peranan) + `authenticate()` (guna respons baharu) + `landingUrl()` |
| `app/Http/Controllers/MagicLoginController.php` | `landingUrl()` mewakilkan kepada penyelesai (−20 baris pendua) |
| `resources/views/components/guest-layout.blade.php` | nav: "Ke Panel" apabila sesi aktif |
| `resources/views/welcome.blade.php` | kad Akses Sistem: "Teruskan ke Panel" + nama pengguna |
| `resources/views/livewire/request-magic-link.blade.php` | notis sesi aktif + pautan panel (borang tukar akaun KEKAL) |
| `e2e/panel-landing.spec.js` | **BAHARU** — 3 ujian pelayar (aliran sebenar pemilik) |
| `playwright.config.js` | spec baharu didaftarkan dalam project `ci-guidance` |
| `tests/Feature/Auth/PanelLandingTest.php` | **BAHARU** — 18 ujian Pest |

**Tiada** perubahan JS/CSS · **tiada** migrasi · **tiada** perubahan `guides.json`/katalog ·
**tiada** pakej baharu.

---

## (c) Keputusan reka bentuk yang PERLU dijustifikasikan

### 1. `/log-masuk` TIDAK dialih walaupun sesi aktif — sengaja
Manifest beku F0 `role_routes` menetapkan `/log-masuk` dan `/` sebagai **`allow` / 200 untuk
KESEPULUH identiti** (termasuk superadmin). Mengalihkan halaman itu menjadikan status **302**
untuk 9 identiti berautentikasi → memecahkan penjaga beku dan ujian lapisan C. Ia juga akan
menghalang **tukar akaun** (log masuk sebagai pengguna lain tanpa log keluar dahulu).
Maka: sesi diberitahu **di dalam halaman**, bukan dengan pengalihan. Kod status tidak berubah.

### 2. Jurang `withOnboarding` antara dua laluan log masuk — sengaja, dan diuji
Lonjakan §10 Aliran I ke wizard persediaan (`/app/{slug}/persediaan?mula=1`) dikekalkan HANYA
untuk magic link (pautan jemputan). Log masuk kata laluan dan pautan "Ke Panel" mendarat di
papan pemuka; banner persediaan (render hook `PAGE_START`) tetap menuntun pengguna.

Sebab: data demo **tidak** menetapkan `onboarding_done`, jadi simetri penuh akan memindahkan
destinasi log masuk **setiap** admin masjid yang belum selesai persediaan — perubahan kelakuan
produksi di luar skop BUG-A — dan memecahkan 6 spec e2e yang menunggu URL `/app/mam`
(`explore`, `guidance`, `ci-session-canary`, `ddms-extended`, `ocr-upload`, `office-workflow`).
Ujian **#3b/#7b/#7c** mengunci kedua-dua belah jurang ini supaya ia kekal keputusan, bukan
kesilapan.

### 3. Tiada ciri baharu ditambah
Diukur: `/admin/mosques` (Tenant / Organisasi) **sudah** memaut ke `/app/{slug}` bagi setiap
masjid. Jadi superadmin yang mendarat di `/admin` tetap ada jalan sah masuk ke panel masjid —
tiada butang baharu diperlukan.

---

## (d) Output verifikasi SEBENAR

### Punca gejala 1 dibuktikan daripada log produksi (24 jam, tanpa IP)

```
31 POST /livewire/update
 9 GET /log-masuk
 1 GET /app/login      ← satu-satunya laluan log masuk borang yang digunakan
 0 GET /admin/login    ← tiada halaman awam memaut ke sini
```

### Gejala 2 diukur pada sesi pemilik yang HIDUP (sebelum pembaikan)

```
GET https://bakwim.my/        → 200, title "Diwan — Sistem Pengurusan Dokumen Masjid"
                                links: Utama, Log Masuk, Daftar, Pembantu Diwan
                                adaSesi: false          ← sesi aktif TIDAK dikenali
GET https://bakwim.my/log-masuk → 200, "Masukkan e-mel atau no. telefon anda dan kami akan
                                hantar pautan log masuk selamat (tanpa kata laluan)."
GET https://bakwim.my/admin   → 200 "Papan pemuka - Diwan · Pentadbir Platform"  ← sesi HIDUP
```

### Pest — ujian baharu

```
Tests:    18 passed (58 assertions)
```

### Bukti PENJAGA: ujian menangkap kelakuan LAMA

Pendawaian ditanggalkan (`git stash` pada `Login.php` + 3 blade; kelas penyelesai dikekalkan):

```
Tests:    5 failed, 10 passed (44 assertions)
```

Lima yang gagal = **#6, #10, #12, #13, #15** — tepat setiap ujian yang mengunci kelakuan
baharu. Sepuluh yang lulus termasuk penyelesai (#1–#5) dan magic link (#11), membuktikan
laluan magic link TIDAK berubah.

### Suite penuh + pint + build

```
Tests:    1 skipped, 545 passed (5291 assertions)      (515 → 545: +18 BUG-A, +6 BUG-B, +6 BUG-C/D)
pint:     PASS
npm run build: ✓ built in 14.44s
  assets/help-D0185fq1.js    35.20 kB   ← KEKAL (ramalan: tiada JS disentuh)
  assets/help-CrH0eDM1.css   14.90 kB   ← KEKAL
```

⭐ Ramalan boleh-gagal yang dibuat SEBELUM build dan disahkan selepasnya: kerana **tiada** fail
JS/CSS disentuh, kedua-dua nama aset mesti **sama** seperti Deploy 6 — dan ia sama.

### role_routes lapisan C (penjaga manifest beku)

`PlanManifestTest` menjalankan probe HTTP sebenar sebagai SETIAP identiti pada setiap larian
suite dan menuntut `actual_status === expected_status`. Ia **hijau** dalam larian 533 di atas —
jadi keputusan "beritahu, jangan alih" pada `/log-masuk` terbukti mengekalkan kontrak beku,
bukan sekadar didakwa.

### e2e `ci-guidance` (tempatan, pelayan `php -S`, env sama seperti CI)

```
npx playwright test --project=ci-guidance        →  34 passed, 1 failed (20.8m)

✓  1 ci-session-canary  sesi HTTP kekal selepas log masuk dan reload            (10.5s)
✓  2 explore            inventori panel superadmin                              (16.8s)
✓ 4–11 guidance-f5      tour /log-masuk desktop+mobile · layout tetamu · nav-primary
                        desktop+mobile · dropzone/Hantar · matriks muat naik
✓ 12   guidance.spec    Chrome berasingan: superadmin + 8 role + public,
                        desktop DAN mobile (20 konteks)                           (9.2m)
✓ 13–31 guidance.spec   kitaran tour · pendaftaran · carian · imej · klasifikasi ·
                        konteks HelpLauncher · one-shot · F2a label=kelakuan ·
                        F6-W0 pelupusan/kegemaran desktop+mobile
✓ 32   panel-landing    BUG-A: superadmin log masuk /app/login → /admin           (5.2s)
✓ 33   panel-landing    BUG-A: admin masjid kekal /app/mam (tiada regresi)        (5.3s)
✓ 34   panel-landing    BUG-A: tetamu tidak nampak tawaran panel                  (1.6s)
✘  3   explore.spec.js:83  inventori dan smoke semua peranan tenant  — timeout 180s
```

### Kegagalan `explore.spec.js:83` — DIBUKTIKAN sedia ada, bukan kesan hotfix

Bacaan pertama saya boleh menyesatkan: Playwright melaporkan `Test timeout of 180000ms
exceeded` **dahulu**, kemudian tindakan yang tergendala (`page.goto(/app/mam/pembetulan-rekod)`).
Tindakan itu ialah **akibat**, bukan punca — dan kebetulan ia halaman yang sama dengan bidang
BUG-B, jadi ia perlu diuji, bukan diandaikan.

Tiga larian, satu kesimpulan:

| Larian | Kod | Keputusan | Tindakan tergendala |
|---|---|---|---|
| dalam suite penuh | dengan hotfix | ✘ timeout 180s | `page.goto(pembetulan-rekod)` |
| bersendirian (tiada persaingan CPU) | dengan hotfix | ✘ timeout 180s (3.0m) | `waitForURL(/app/mam)` |
| bersendirian | **`git checkout aaf381a --` (pra-hotfix, = kod LIVE)** | ✘ timeout 180s (3.0m) | — |

Tindakan tergendala **berubah antara larian** sedangkan kegagalan kekal = tanda bajet habis,
bukan satu halaman rosak. Larian ketiga memutuskannya: kod yang **sedang berjalan di produksi**
gagal serupa. Punca: ujian ini tiada `test.setTimeout()` (bajet global 180s) tetapi melalui
**8 peranan × ~10 halaman ≈ 80–100 muatan halaman**; `php -S` pada Windows satu-benang
(`PHP_CLI_SERVER_WORKERS` Unix-sahaja). CI (Linux, 4 worker) melaluinya — spec ini hijau di CI
pada setiap deploy sebelum ini, dan disemak semula pada larian CI hotfix ini.

Bukti bebas bahawa halaman itu SIHAT: `role_routes` mempunyai **10 entri** untuk
`/app/{tenant}/pembetulan-rekod` (`allow/200` untuk 9 identiti, `deny/302` untuk tetamu), dan
lapisan C memprobenya melalui HTTP **pada setiap larian suite** — hijau dalam 545.

⚠️ Direkod, tidak disentuh: `explore.spec.js` berjalan pada ~95% bajetnya di CI. Ia flake
terpendam. Mengubah bajet ujian hijau tanpa bukti kegagalan CI melanggar disiplin projek, jadi
ia dicatat di sini untuk F8, bukan diubah hari ini.

---

## (e) Lencongan dari pelan

**BUG-A bukan fasa pelan.** Ia laporan pemilik yang diterima semasa jeda antara Deploy 6 (F6-W1)
dan F6-W2, dan dikendalikan sebagai **hotfix berskop** — corak yang sama seperti F6-W0 ditarik
ke hadapan sebagai hotfix mobile. Ia **tidak** mengubah:
katalog panduan · gate G1–G5 · denominator beku · isolasi tenant · enjin retensi · mekanisme
sync tour (§0.3) · magic link (dibuktikan oleh ujian #7c/#11).

Selepas BUG-A selesai, urutan pelan disambung pada **F6-W2**.

---

## (f) Ditemui SEMASA siasatan ini (bukan dilaporkan)

Membaca log ralat produksi untuk mengesahkan punca BUG-A mendedahkan **BUG-B**: satu 500 hidup
pada aliran "Mohon Pembetulan" rekod (enum → string). Ia telah memukul pengguna sebenar tiga
kali pada 22 Julai dan tiada siapa melaporkannya. Lihat `LAPORAN-BUG-B.md` dan
`TRIAGE-LOG-PRODUKSI.md` (62 baris ERROR dikelaskan; hanya satu masih hidup — BUG-B; backup
disahkan **sihat**: 23 backup, terbaharu 4 jam lalu).

## (g) Nota / risiko

- **Pengesahan visual produksi bagi gejala 1** (log masuk → `/admin`) memerlukan sesi log masuk
  BAHARU. Kredensial produksi tidak pernah dicipta atau ditaip oleh saya, dan saya tidak
  memasukkan kata laluan ke dalam borang melalui alat pelayar. Ganti: 3 ujian pelayar Chromium
  tempatan melalui borang Filament yang SEBENAR + 18 ujian Pest. Pemilik boleh mengesahkan
  dalam satu langkah: log keluar → `/log-masuk` → "Log masuk dengan kata laluan" → mesti
  mendarat di `/admin`.
- Gejala 2 **boleh** disahkan hidup pada produksi selepas deploy menggunakan sesi pemilik yang
  sedia ada (tiada kredensial ditaip) — dan itu dilakukan.
- Deploy: PHP + Blade dibakar ke dalam imej → **rebuild `app` + `web`**, force-recreate nginx.
  Aset tidak berubah, jadi bukti = **kandungan dalam imej + ImageID berubah** (pelajaran
  Deploy 2), dengan hash aset dijangka **identik** dengan Deploy 6.
- Tiada migrasi → `migrate --force` mesti melaporkan `Nothing to migrate`.

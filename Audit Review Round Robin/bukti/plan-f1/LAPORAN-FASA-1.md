# Laporan Fasa 1 — Konteks HelpLauncher kekal merentas kitaran Livewire

**Tarikh:** 3 Ogos 2026 · **Menutup:** RR-01-02 (+gandingan) & RR-01-11 · **Keutamaan FINAL-RUMUSAN #1**
**Rujukan:** `PELAN-PEMBAIKAN.md` §2 (dibaca semula sepenuhnya sebelum membina)

---

## (a) Ringkasan apa dibina

Punca #1 audit — Pembantu Diwan hilang pada **19/25 halaman produksi** (semua 11 halaman
superadmin) — ditutup. `render()` dahulu membaca `request()`, yang semasa AJAX Livewire ialah
`POST /livewire/update`; konteks halaman sebenar hilang dan `currentGuide()` memulangkan `null`.
Tour memusnahkan konteksnya sendiri kerana setiap langkah menghantar event `guidanceProgress`.

Penyelesaian pelan (a)+(d): konteks ditawan **sekali** pada `mount()` sebagai **4 sifat
`#[Locked]`** (`originPath`, `requestedGuideId`, `requestedStep`, `launchPending`); `render()`
membaca sifat, bukan `request()`. `originPath` guna `'/'.trim(path,'/')` yang turut menutup
RR-01-11 (`//` pada root). Pencetus auto-start jadi **one-shot** dan dipadam **sebelum** guard
`findVisible()` supaya guide yang tidak lagi kelihatan tidak meninggalkannya melekat selamanya.
`guidanceProgress()` memanggil `skipRender()` — telemetri tidak perlu render semula HTML.

**Kod aplikasi: 1 fail, tiada migrasi, tiada perubahan data.** `help.js`, blade dan
`HelpCatalog` TIDAK disentuh (seperti §2.3).

## (b) Fail dicipta/diubah

| Fail | Perubahan |
|---|---|
| `app/Livewire/HelpLauncher.php` | 4 sifat `#[Locked]` + `mount()` menawan konteks + `render()` guna sifat + one-shot sebelum guard + `skipRender()` |
| `tests/Feature/Help/HelpLauncherContextTest.php` | **BAHARU** — 21 ujian (kes #1–#11 pelan, sebahagian ber-dataset) |
| `e2e/guidance.spec.js` | +2 ujian: konteks kekal/berubah betul; tour tertutup tidak muncul semula |

## (c) Output verifikasi sebenar

```
$ vendor/bin/pint --dirty
{"tool":"pint","result":"passed"}

$ php artisan test tests/Feature/Help/HelpLauncherContextTest.php
  Tests:  21 passed (52 assertions)

$ php artisan test                       # suite penuh
  Tests:  1 skipped, 453 passed (4790 assertions)      ← 432 sebelum F1 (+21)

$ npx playwright test --project=ci-guidance
  14 passed (9.8m)
OK [storage/app/plan-ci/ci-guidance.json]: 14 ujian, 0 skipped/timedOut/interrupted/unexpected/flaky.
```

**Bukti penjaga menangkap regresi** (disiplin: ujian yang tidak pernah gagal ialah ujian palsu).
Kod lama dipasang semula secara sengaja (`render()` baca `request()`):
```
  Tests:  6 failed, 15 passed (49 assertions)     ← regresi DITANGKAP
  (selepas dipulihkan)  Tests: 21 passed (52 assertions)
```

## (d) Kriteria Siap fasa (§2.5)

| Kriteria | Status |
|---|---|
| 10 ujian baharu lulus (+#11 penjaga) | ✔ 21 ujian (dataset) |
| Suite penuh hijau | ✔ 453/1 skip |
| e2e: dataset guide kekal selepas interaksi + betul selepas navigasi | ✔ |
| One-shot (C04): auto-start 0 selepas started/dismissed/completed | ✔ #5a ×3 + #5b + #5c |
| Sifat Locked menolak tamper (6 sifat, dua arah untuk `launchPending`) | ✔ #7a ×6 + #7b |
| Penjaga SPA mati (#11) | ✔ kedua-dua panel + 0 `wire:navigate` |
| S3 deep-link `?panduan=` merentas panel/role | ✔ #8 (tenant minta guide admin → tiada guide) |

Belum dilaksana (memerlukan deploy — Deploy 1 bersama F2): semakan manual produksi
`/admin/mosques` + crawl `helpRuntime` 5 halaman sampel pada produksi.

## (e) Lencongan dari spec

**TIADA.** Dua nota pelaksanaan:

1. `setOrigin()` **tidak dibina** — betul mengikut §2.2 nota 4: SPA terbukti mati (penjaga #11
   mengunci fakta itu). Ujian bersyarat #12 sengaja tidak ditulis; sebabnya direkod di sini.
2. Harness `Livewire::test()` mencipta requestnya sendiri (`/livewire-unit-test-endpoint/…`),
   jadi nilai **path sebenar** diuji melalui HTTP penuh (#1/#3/#4) manakala Testable digunakan
   untuk membuktikan konteks **kekal** merentas kitaran update. Ujian #2/#3 menukar request
   kepada `POST /livewire/update` sebelum kitaran kedua — meniru punca asal dengan tepat.

## (f) Nota/risiko untuk fasa seterusnya

**Penemuan e2e (baharu, bukan dalam pelan):** selepas tour ditutup, atribut `data-auto-start`
dalam **DOM** kekal `"1"` sehingga muat penuh berikutnya. Sebabnya `HelpLauncher` ialah komponen
Livewire **berasingan** — interaksi pada komponen lain tidak me-render semula ia, dan kitaran
telemetrinya sendiri memanggil `skipRender()` (kontrak §2.2 nota 3). Nilai **server** memang
sudah padam (ujian #5a–#5c). Ini selamat **selagi SPA mati**: `bootRuntime()` hanya berjalan
pada `DOMContentLoaded`, iaitu muat penuh yang mount() semula. **Jika SPA dihidupkan kelak,
`livewire:navigated` akan membaca DOM lama** → laksanakan spesifikasi beku §2.2 nota 4 dahulu.
Direkod dalam komen e2e supaya ia dijumpai oleh sesiapa yang menyentuh bahagian ini.

**Baki keutamaan audit dalam skop F2 (§3)** — tiga bug yang gate F0 temui masih terbuka:
auto-advance tour boleh mati; banner "Panduan menunggu" menolak klik tetikus (**disahkan hidup
di produksi**); nilai medan borang boleh berganda.

**Deploy:** F1 belum di-deploy — ia digabung dengan F2 sebagai **Deploy 1** (D7). Aset frontend
TIDAK berubah pada F1 (hanya PHP), tetapi F2 akan mengubah `help.js` → rebuild `app`+`nginx`
kedua-duanya wajib pada Deploy 1.

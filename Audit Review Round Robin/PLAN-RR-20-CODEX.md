# Pusingan 20 — Codex: audit integrasi v1.8

Tarikh: 2026-08-02  
Fail dinilai: `PELAN-PEMBAIKAN.md` v1.8, `PLAN-RR-19-CLAUDE.md`, kod semasa pada `8342d95`  
Keputusan: **BELUM MUKTAMAD — v1.9 diperlukan**

Kod aplikasi tidak disentuh. Saya menyemak kontrak baharu yang dimasukkan P19, terutamanya CI, reporter Playwright, status check GitHub Actions, Meilisearch dan D11.

## Integriti sebelum tulis

- Semakan pra-tulis #1 selepas P19: `PELAN-PEMBAIKAN.md` = `68F00B235775CE825C6E93E2D913FA5211716F6FF1C94FEF5AD7CD710F84E711`, 280,276 B, 3,673 baris LF, mtime `2026-08-02 05:45:10`.
- Semakan pra-tulis #2 pada `2026-08-02 05:56:43 +08:00`: hash, saiz, bilangan LF dan mtime kekal sama.
- `PLAN-RR-19-CLAUDE.md` = `793BF1D3401FC1AB10750D8733883D36B1D794645AF950820F5D78F11FCA0E82`.
- Tiada proses Claude baharu berjalan semasa tulis P20.

## Penemuan

### P20-01 — Lapis 1 `integration` masih belum dibekukan sebagai YAML boleh-tampal

P19 membekukan YAML penuh untuk job baharu `guidance-e2e` (`PELAN-PEMBAIKAN.md:903-1063`), tetapi lapis 1 yang dikatakan masuk job `integration` masih berupa senarai naratif dan command bash berasingan (`:1162-1198`, `:1299-1315`), bukan snippet `.github/workflows/ci.yml` yang boleh terus ditampal.

Ini masih meninggalkan ruang reka bentuk pada PR F0, khususnya:

- CI semasa mempunyai job env `APP_URL=http://127.0.0.1:8080`, `SESSION_DRIVER=array`, `SCOUT_DRIVER=collection` (`.github/workflows/ci.yml:56-70`).
- Pelan menyebut env override untuk "Langkah Playwright" (`PELAN-PEMBAIKAN.md:1169-1178`), tetapi proses `php artisan serve` juga mesti dilancar dengan `SESSION_DRIVER=file` dan `APP_URL=8092`. Env pada step Playwright sahaja tidak mengubah environment proses server yang sudah berjalan.
- Pelan naratif menyebut `trap 'kill "$serve_pid"' EXIT` untuk server (`:1194-1198`). Jika server dilancar dalam step berasingan dan trap dipasang dalam step itu, trap akan membunuh server pada penghujung step sebelum Playwright berjalan. YAML `guidance-e2e` v1.8 sendiri tidak memasang trap pada step serve (`:1012-1020`), jadi kontrak lapis 1 perlu memilih bentuk yang sama jelas: server+tests dalam satu `run` block dengan trap, atau serve step berasingan + cleanup step `if: always()`.
- Command literal lapis 1 (`:1299-1315`) hanya memaparkan `npx playwright ...` dan assert JSON; ia tidak menunjukkan `migrate:fresh --seed`, `playwright install`, `serve`, env server, atau cleanup.

**Pembaikan v1.9:** tambah snippet literal untuk perubahan job `integration` selepas `php artisan test`, sama tahap detail dengan YAML `guidance-e2e`. Sekurang-kurangnya ia mesti menunjukkan:

1. step `Prepare Playwright e2e` dengan `migrate:fresh --seed`, `mkdir -p storage/framework/sessions`, dan `npx playwright install --with-deps chrome`;
2. step `Serve application on 8092` dengan env `APP_URL=8092`, `SESSION_DRIVER=file`, `E2E_BASE_URL=8092`, `E2E_ROLE_LOGIN_DELAY_MS=0`, dan PID disimpan;
3. cleanup step `if: always()` yang membunuh PID, bukan trap yang membunuh server sebelum test;
4. step Playwright canary/smoke/domain dengan `DIWAN_PW_JSON` dan `assert-playwright-json.mjs`.

Tanpa ini, P14-01/P16-01 boleh berulang sebagai konfigurasi sesi HTTP yang salah.

### P20-02 — Gate Meilisearch C20 masih tertulis sebagai nota, bukan command CI yang mengikat

Pelan v1.8 betul menyatakan bahawa sebelum spec carian bantuan berjalan, CI mesti menjalankan `diwan:sync-help-index` dengan `SCOUT_DRIVER=meilisearch`, tunggu task Meili, dan assert 83 dokumen (`PELAN-PEMBAIKAN.md:1372-1375`).

Namun command literal lapis 1 (`:1299-1315`) tidak memasukkan langkah ini. CI semasa pula hanya menjalankan:

- `SCOUT_DRIVER=collection php artisan diwan:sync-help-index` (`.github/workflows/ci.yml:123-126`);
- command itu memang pulang awal jika driver bukan Meili (`app/Console/Commands/SyncHelpIndex.php:38`), jadi ia hanya validasi katalog, bukan indeks Meilisearch;
- `HelpSearchService` mencuba Meili jika `MEILISEARCH_HOST` terisi (`app/Services/HelpSearchService.php:24-30`), tetapi catch error dan fallback ke PHP (`:37-44`). Jadi e2e carian boleh lulus walaupun indeks Meili kosong/gagal.

**Pembaikan v1.9:** masukkan command literal dalam snippet `integration` sebelum `ci-guidance`/spec carian:

```bash
SCOUT_DRIVER=meilisearch php artisan diwan:sync-help-index --delete
```

Kemudian tambah assertion eksplisit bahawa indeks `diwan_help_guides` mengandungi tepat 83 dokumen. Boleh guna command artisan sedia ada jika ia sudah fail pada mismatch (`SyncHelpIndex.php:77-78` dan bahagian stats selepas itu), tetapi pelan perlu meletakkannya sebagai step CI yang wajib, bukan nota naratif.

### P20-03 — JSON Playwright untuk `guidance-full` dijana tetapi tidak di-assert, bercanggah dengan syarat "setiap gate"

v1.8 menyatakan "setiap gate Playwright turut memerlukan artifak JSONnya lulus `assert-playwright-json.mjs`" (`PELAN-PEMBAIKAN.md:1384-1385`). Namun YAML `guidance-e2e`:

- menetapkan `DIWAN_PW_JSON=bukti/plan-ci/guidance-full-${{ matrix.shard }}.json` (`:1030-1033`);
- hanya menjalankan `npx playwright test --project=guidance-full` (`:1033`);
- tidak memanggil `node scripts/audit/assert-playwright-json.mjs` selepas itu.

Canary matrix di-assert (`:1022-1028`), tetapi `guidance-full` sendiri tidak. Ini meninggalkan jurang tepat pada gate paling penting, walaupun artifact F6 shard mungkin masih di-upload.

**Pembaikan v1.9:** selepas `npx playwright test --project=guidance-full`, panggil:

```bash
node scripts/audit/assert-playwright-json.mjs --file "$DIWAN_PW_JSON" --min-tests 1
```

Selain itu, skrip assert patut turut memeriksa `stats.skipped === 0`. Saya sahkan dengan Playwright 1.61.1 temp run bahawa test `test.skip()` muncul sebagai `stats.skipped=1`, `test.status=skipped`, `results[0].status=skipped`; pemeriksaan `result.status` semasa cukup untuk versi ini, tetapi `stats.skipped === 0` ialah penjaga murah dan lebih langsung.

Nota bukan-isu: Playwright JSON reporter memang mencipta parent directory secara rekursif (`node_modules/playwright/lib/runner/index.js:4061-4065`), jadi ayat `PELAN-PEMBAIKAN.md:1077-1078` bahawa reporter JSON gagal jika direktori tiada tidak tepat. Yang masih perlu `mkdir -p` ialah `bukti/plan-f6` jika spec/agregator menulis sendiri tanpa mkdir.

### P20-04 — Bukti `bukti/plan-ci/*.json` tidak di-upload/di-retain walaupun pelan menjadikannya bukti audit

Pelan menggunakan `bukti/plan-ci/*.json` sebagai bukti bahawa `ci-domain`/`ci-ocr`/canary benar-benar berjalan (`PELAN-PEMBAIKAN.md:1141-1142`, `:3049`, `:3447-3449`). Tetapi YAML v1.8 hanya upload:

- `bukti/plan-f6/shard-${{ matrix.shard }}.json` (`:1035-1040`);
- `bukti/plan-f6/coverage-gate.json` (`:1058-1062`).

Tiada upload untuk `bukti/plan-ci/*.json` dalam job `guidance-e2e`, dan perubahan job `integration` pula belum ada YAML upload langsung. CI semasa hanya upload `storage/logs/*.log` apabila gagal (`.github/workflows/ci.yml:150-157`).

**Pembaikan v1.9:** tambah upload artifact `if: always()` untuk JSON Playwright:

- dalam `integration`: `bukti/plan-ci/ci-*.json`;
- dalam `guidance-e2e`: `bukti/plan-ci/*-${{ matrix.shard }}.json` atau path yang tepat;
- retention ringkas, contohnya 7-14 hari;
- `if-no-files-found: error` untuk job yang sepatutnya menghasilkan JSON, kecuali `ci-ocr` sebelum fixture jika ia tidak-required.

Jika fail ini hanya untuk assertion sementara dan bukan bukti audit, ubah metrik/teks pelan supaya tidak memanggilnya "artifak" bukti.

### P20-05 — Senarai required status check masih bercampur bilangan dan jenis check

`PELAN-PEMBAIKAN.md:1133` menyebut "Senarai required status check pada `main` (tepat tiga)", tetapi senarai itu sebenarnya mengandungi empat nama check jika Docker kekal required:

1. `PostgreSQL, Redis, Meili, OCR and tests`;
2. `guidance-e2e-gate`;
3. `Docker app image`;
4. `Docker web image`.

Baris `:1136-1138` meletakkan dua check Docker dalam satu bullet, tetapi branch protection perlu nama check satu per satu. §10 juga menyebut "kesemua empat" sambil mencampurkan check, step `ci-domain`, tiga check shard, dan `guidance-e2e-gate` (`:3439-3449`).

**Pembaikan v1.9:** asingkan dua senarai:

- **Required branch protection checks**: senaraikan nama sebenar satu per satu. Jika Docker app/web required, tulis "tepat empat nama check".
- **Release evidence checks**: integration hijau, `ci-domain` step terbukti melalui JSON, tiga shard `guidance-e2e (...)` hijau, `guidance-e2e-gate` hijau, Docker app/web hijau.

Ini mengelakkan admin repo menetapkan "tepat tiga" lalu tertinggal satu Docker check, atau cuba required-kan step yang bukan check.

### P20-06 — Laluan root `bukti/plan-ci` dan `bukti/plan-f6` perlu keputusan git/ignore yang jelas

Pelan awal sengaja meletakkan baseline di `Audit Review Round Robin/bukti/plan-baseline/` untuk elak folder `bukti/` baharu di root repo (`PELAN-PEMBAIKAN.md:525-526`). v1.8 kini memperkenalkan output CI di root `bukti/plan-ci` dan `bukti/plan-f6` (`:824`, `:864`, `:1009`, `:1024`, `:1039`, `:1091`, `:1303-1315`).

Root `.gitignore` hari ini tidak mengabaikan `/bukti` atau `/bukti/plan-*`. Jika developer menjalankan command literal tempatan dengan `DIWAN_PW_JSON`, ia akan menghasilkan untracked files. Ini bukan isu produksi, tetapi ia bercanggah dengan disiplin snapshot/perancangan yang mahu working tree jelas.

**Pembaikan v1.9:** pilih satu:

- tukar output transient CI ke folder sedia ignored seperti `test-results/plan-ci` dan `test-results/plan-f6`; atau
- tambah `.gitignore` untuk `/bukti/plan-ci/`, `/bukti/plan-f6/`, `/bukti/plan-f8/` dan kira `.gitignore` sebagai D11 file tambahan; atau
- letakkan semua bukti plan di bawah `Audit Review Round Robin/bukti/...` jika memang mahu dikomit sebagai bukti perancangan.

Jangan biarkan ia tersirat, kerana D11 kini sudah menjadi senarai kelulusan skop.

## Perkara Yang Disahkan Baik

- P19 betul bahawa Playwright JSON reporter 1.61.1 mempunyai `suites`, `specs`, `tests`, `results`, `status`, dan `stats` (`node_modules/playwright/lib/runner/index.js:3916-3928`, `:4011-4020`).
- P19 betul memilih nama env projek `DIWAN_PW_JSON`; reporter `outputFile` relatif kepada config dir (`node_modules/playwright/lib/runner/index.js:1493-1511`).
- P19 betul bahawa `test.skip()` boleh ditangkap daripada JSON pada Playwright 1.61.1; saya sahkan melalui temp run luar repo.
- P19 betul menolak superadmin sementara produksi dan memisahkan kredensial superadmin daripada `diwan:audit-fixture`.
- P19 betul menambah gate antivirus `InboxIngestService` sebagai item keselamatan, bukan sekadar pengukuran.

## Cadangan Untuk Claude P21

v1.9 perlu fokus pada CI contract sahaja:

1. Tulis YAML literal untuk penambahan job `integration`, termasuk env server dan cleanup yang tidak membunuh server terlalu awal.
2. Masukkan gate Meilisearch sebagai command CI literal sebelum spec carian bantuan.
3. Assert JSON Playwright untuk `guidance-full` dan tambah `stats.skipped === 0`.
4. Upload/retain `bukti/plan-ci/*.json` atau jangan panggil ia artifak bukti.
5. Betulkan bilangan/nama required status checks.
6. Putuskan lokasi/ignore untuk root `bukti/plan-*` dan selaraskan D11 jika `.gitignore` disentuh.

Selepas v1.9, Codex P22 patut semak semula hanya enam isu ini dan imbas corak: `tepat tiga`, `bukti/plan-ci`, `assert-playwright-json`, `SCOUT_DRIVER=meilisearch`, `trap 'kill`, dan `SESSION_DRIVER: file`.

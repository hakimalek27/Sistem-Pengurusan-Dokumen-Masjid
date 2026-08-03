# Laporan F6-W0 — Hotfix 6 defect mobile (2 guide / 10 langkah)

**Tarikh:** 3 Ogos 2026 · **Menutup:** RR-10-05/RR-11-05 · **Rujukan:** `PELAN-PEMBAIKAN.md` §7.2
**Masa:** sejurus selepas Deploy 1 (F2 memiliki tingkah laku popover/auto-minimize)

---

## (a) Ringkasan apa dibina

Enam langkah tour yang **diukur rosak pada produksi** (`centerCovered: true` dalam
`bukti/pusingan-11-codex/production-mobile-all-tour-steps.json`) berpunca daripada sasaran
generik: setiap langkah menyorot seluruh `<main>`, jadi pada 390×664 popover duduk di tengah
dan menutup apa yang ia rujuk. W0 memberi kedua-dua guide **sasaran spesifik** pada elemen
sebenar + **10 tajuk bermakna** menggantikan placeholder `Langkah 1…5`.

| Guide | Langkah | Sasaran baharu |
|---|---|---|
| `tenant.pelupusan` | 1 | `disposal-candidates` (perenggan calon cukup tempoh) |
| | 2–4 | `disposal-batches` (senarai batch — semak/lulus/laksana/cuba semula berlaku di sini) |
| | 5 | `disposal-warning` (kotak amaran §16.2 — sijil & metadata kekal) |
| `tenant.kegemaran` | 1, 2, 5 | `favourites-list` (senarai kegemaran) |
| | 3, 4 | `favourite-item` (item pertama **atau** mesej kosong) |

## (b) Fail dicipta/diubah

| Fail | Perubahan |
|---|---|
| `resources/views/filament/app/pages/pelupusan-manual.blade.php` | 5 `data-help-target` |
| `resources/views/filament/app/pages/kegemaran.blade.php` | 5 `data-help-target` |
| `resources/help/guides.json` | 10 sasaran + 10 tajuk; `catalog_version` `2026.08.03.2` |
| `resources/help/targets.json` + `docs/HELP-TARGETS.md` | 9 sasaran didaftar (dok dijana) |
| `Audit Review Round Robin/bukti/plan-baseline/manifest.json` | dijana semula |
| `Audit Review Round Robin/bukti/plan-baseline/tools/build-manifest.mjs` | **struktur vs kemajuan** (lihat (e)) |
| `scripts/audit/validate-plan-manifest.mjs` | sama — penjaga kedua diselaraskan |
| `tests/Feature/PlanManifestTest.php` | sama — penjaga ketiga diselaraskan |
| `e2e/guidance.spec.js` | +4 ujian W0 (2 guide × desktop/mobile) |

## (c) Output verifikasi sebenar

```
$ npx playwright test --project=ci-guidance --grep "F6-W0"
  4 passed (53.9s)          ← desktop + mobile, kedua-dua guide

$ node tools/build-manifest.mjs …
KEMAJUAN berbanding baseline F0:
  generic_declared 443 → 433 (−10)
  generic_pp 238 → 236 (−2)
  generic_pc 205 → 197 (−8)
  placeholder_titles 258 → 248 (−10)
  wave W0.placeholder 10 → 0 (−10)
OK: guides=83 steps=473 actionGeneric=200 placeholder=248

$ node scripts/audit/validate-plan-manifest.mjs --manifest …
KEMAJUAN berbanding baseline F0:
  placeholder 258 → 248 (−10)
OK: manifest sah — partition wave/shard sepadan pengiraan bebas, set-union exact.

$ vendor/bin/pint --dirty     {"tool":"pint","result":"passed"}
$ php artisan test            Tests: 1 skipped, 453 passed (4794 assertions)
$ node tools/generate-help-targets-doc.mjs   OK (22 aktif + 5 rizab)
```

## (d) Kriteria Siap (§7.2 gate W0)

| Kriteria | Status |
|---|---|
| 2 guide / 10 langkah diberi sasaran spesifik | ✔ |
| 10 tajuk placeholder → tajuk bermakna | ✔ (`wave W0.placeholder 10 → 0`) |
| Diuji **desktop DAN mobile 390×664** | ✔ 4 ujian |
| Popover tidak menutup sasarannya sendiri | ✔ diassert setiap langkah |
| Sorotan bukan generik (`page-content`/`page-primary`) | ✔ diassert setiap langkah |
| Struktur 83/473 + partition wave utuh | ✔ ketiga-tiga penjaga |

## (e) Lencongan dari spec

**TIADA lencongan skop.** Satu pembetulan **reka bentuk perkakas F0** diperlukan sebelum W0
boleh lulus, dan ia direkod di sini kerana ia menjejaskan semua wave berikutnya:

> Ketiga-tiga penjaga (`build-manifest.mjs`, `validate-plan-manifest.mjs`, `PlanManifestTest`)
> mengassert **kesamaan** untuk nilai seperti `placeholder_titles: 258` dan
> `action_steps_with_generic_target: 200`. Tetapi §7 menetapkan nilai itu **mesti turun ke 0**
> sepanjang F6. Dibiarkan begitu, gate akan **menolak setiap pembaikan** — songsang.
>
> Kini nilai dibahagi dua kelas: **STRUKTUR** (83 guide, 473 langkah, partition wave/shard,
> kohort 25/124, `wait_for_user`, `unique_step_ids`) diassert **sama**; **KEMAJUAN**
> (generic, placeholder, action-generic, defect mobile) diassert **≤ baseline** — turun
> dilaporkan sebagai delta, naik gagal sebagai regresi. Ketiga-tiga penjaga menggunakan
> peraturan yang sama.

Nota kedua: sasaran mesti **wujud dalam keadaan lalai halaman**. Percubaan pertama menyasarkan
butang `Lulus`/`Laksana` dan pautan item — yang hanya wujud bila ada data. Bagi majoriti
pengguna (tiada batch, belum ada kegemaran) langkah itu jatuh ke popover
"Tindakan belum tersedia". Sasaran akhir stabil untuk kedua-dua keadaan; `favourite-item`
sengaja diletak pada item pertama **dan** mesej kosong.

## (f) Nota/risiko untuk fasa seterusnya

1. **Sasaran bersyarat ialah perangkap** — sahkan setiap sasaran W1–W6 wujud dalam keadaan
   lalai halaman (data kosong), bukan hanya dalam persekitaran ujian yang berdata.
2. **Ukuran defect mobile:** `centerCovered` asal ialah gejala sasaran generik. Ukuran yang
   bermakna selepas sasaran spesifik ialah **popover tidak menutup sasarannya sendiri** —
   itulah yang diassert. Pada skrin kecil, popover yang bersebelahan sasaran kecil memang
   boleh melintasi titik tengah viewport tanpa menghalang apa-apa.
3. **Baki F6:** W1 (28 guide/140 langkah) → W2 (13/145) → W3 (1/11) → W4 (1/13) →
   W5 (35/146) → W6 (3/8). `action_steps_with_generic_target` kekal **200** selepas W0
   (W0 tiada langkah `wait_for_user`) — ia mula turun pada W1.
4. Deploy W0 = **Deploy 2**; aset frontend berubah (blade + katalog) → rebuild `app`+`nginx`,
   dan jalankan `diwan:sync-help-index --delete` kerana katalog berubah.


---

## (g) CI run 30776919686 — shard `workflow` gagal (1 guide), disiasat & TIDAK diubah

Run `39f2f33`: integration ✅ · Docker app ✅ · Docker web ✅ · shard `screen` ✅ ·
shard `tenant-admin-public` ✅ · **shard `workflow` ❌ 13/15** pada
`workflow.admin_masjid.muat-naik…` (upload tidak menghasilkan "1 dokumen dimuat naik").

**Siasatan (penting — keputusan akhir ialah TIDAK mengubah kod):**

1. Hipotesis pertama: F2a menukar makna CTA (dahulu "Buat pada skrin" = minimize; kini CTA
   pada langkah yang sasaran berikutnya kelihatan bermaksud `moveNext()`), jadi koreografi
   gate yang memanggil `cta()` sebagai alat kawalan akan melompat langkah. Saya membuang
   pergantungan CTA dan menggunakan `dispatchEvent` sahaja.
2. **Larian tempatan MENOLAK hipotesis itu:** guide yang sama gagal lebih awal (langkah 6
   "tidak maju"), kerana langkah 6–7 menyasarkan `inbox-upload-modal` — modal yang sudah
   DITUTUP oleh submit pada langkah 5. Tanpa `cta()` tiada apa yang memulihkan popover.
3. **Fakta yang menentukan:** shard `workflow` **LULUS** pada CI run 30774069928 (F2, 7/7
   hijau) dengan kod koreografi ASAL, dan komit W0 tidak menyentuh mana-mana guide
   `workflow.*` — ia hanya mengubah dua guide `tenant.*`, blade masing-masing, dan penjaga
   manifest. Maka kegagalan run W0 ialah **flake** pada guide 20-langkah yang berat (upload
   sebenar + wizard 5 langkah), bukan regresi F2 mahupun W0.

**Keputusan:** perubahan koreografi saya **dipulihkan sepenuhnya** (`git checkout` fail
`guidance-full.spec.js`) — jangan ubah kod yang terbukti hijau atas dasar satu kegagalan yang
belum terbukti berulang. Shard dijalankan semula pada CI untuk mengesahkan.

**Nota jujur untuk fasa berikut:** guide ini memang rapuh — langkah 6–7 menyasarkan modal yang
ditutup oleh tindakan langkah 5, jadi tour bergantung pada minimize/auto-advance untuk
melepasinya. Itu **kelemahan katalog**, bukan kelemahan runtime, dan tempat betul untuk
membaikinya ialah **W2** (`workflow.*`, 13 guide/145 langkah) — bukan W0. Rekod ini supaya
W2 tidak tersandung pada perkara yang sama.

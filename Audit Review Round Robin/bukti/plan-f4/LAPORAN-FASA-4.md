# Laporan Fasa 4 — Lalai retensi selamat (auto-padam → pilihan sedar)

**Tarikh:** 3 Ogos 2026 · **Rujukan:** `PELAN-PEMBAIKAN.md` §5 + **`ADDENDUM v2.6`**
**Menutup:** RR-08-01 + RR-09-01 · **Keputusan pemilik:** D1 Ya · D2 Ya · D3 Kekal · D4 Ya · **D10 LULUS**

---

## (a) Ringkasan apa dibina

Audit mendapati **auto-padam ialah tingkah laku LALAI** dalam sistem arkib rekod rasmi, melalui
**tiga lapisan bertindan**. Penting untuk difahami: **enjin retensi tidak rosak** — audit
membuktikannya betul (t30+t7 dihormati, 11/11 sijil dijana, legal hold dihormati, 0 rekod masjid
sebenar terjejas). Yang bermasalah ialah betapa mudahnya pemadaman kekal boleh dihidupkan tanpa
sesiapa menyedarinya.

| Lapisan | Sebelum | Selepas | Kuasa |
|---|---|---|---|
| **L1** borang peraturan | `->default('auto_padam')` | **`->default('semak')`** + pengesahan sedar berkiraan impak | D1 |
| **L2** suis per-masjid | `auto_disposal_enabled` default `true` | **default `false`** (masjid BAHARU sahaja) | D2 + **D10** |
| **L3** peraturan platform | 14/19 = `auto_padam` 7 tahun | **KEKAL** | D3 (patuh tatacara ANM §16.1) |

Kesan bersih: masjid baharu perlu **memilih masuk dua kali** — menghidupkan suis *dan* menetapkan
peraturan `auto_padam` — sebelum sebarang pemadaman automatik boleh berlaku. L3 tidak disentuh
kerana jadual retensi itu memang direka mengikut tatacara Arkib Negara.

Ironi yang paling ketara dalam L1: medan yang **memaparkan helperText AMARAN** tentang auto-padam
juga **memberikan nilai itu sebagai lalai**.

## (b) Fail dicipta/diubah

| Fail | Perubahan |
|---|---|
| `app/Concerns/ConfirmsAutoPadamRetention.php` | **BAHARU** — brek sedar + kiraan impak tenant-scoped |
| `.../RetentionRules/RetentionRuleResource.php` | `default('auto_padam')` → **`default('semak')`** |
| `.../RetentionRules/Pages/CreateRetentionRule.php` | `getCreateFormAction` + `getCreateAnotherFormAction` |
| `.../RetentionRules/Pages/EditRetentionRule.php` | `getSaveFormAction` |
| `database/migrations/2026_08_03_000001_change_auto_disposal_default.php` | **BAHARU** — `->default(false)->change()` |
| `resources/views/livewire/register-mosque.blade.php` | teks pengakuan §16.2 (ADDENDUM v2.6) |
| `tests/Feature/RetentionDefaultsTest.php` | **BAHARU** — 11 ujian |

## (c) Output verifikasi sebenar

```
$ php artisan test tests/Feature/RetentionDefaultsTest.php
  ✓ borang cipta peraturan bermula dengan Semak, bukan Auto Padam
  ✓ auto_padam mencetuskan pengesahan pada ketiga-tiga laluan simpan
  ✓ semak TIDAK mencetuskan pengesahan (brek hanya untuk yang berbahaya)
  ✓ simpan TIDAK putus — callback vendor kekal terpasang … with ('semak')
  ✓ simpan TIDAK putus — callback vendor kekal terpasang … with ('auto_padam')
  ✓ masjid BAHARU tanpa override bermula dengan pelupusan automatik DIMATIKAN
  ✓ masjid SEDIA ADA tidak disentuh oleh perubahan lalai
  ✓ teks pengakuan /daftar menerangkan keadaan sebenar (§16.2 dipinda v2.6)
  ✓ kiraan impak auto_padam mengira tenant sendiri sahaja
  ✓ kiraan impak mengikut awalan klasifikasi, juga tenant-scoped
  ✓ migrasi lalai boleh dirollback dan dijalankan semula
  Tests:  11 passed (35 assertions)

$ php artisan test                       (suite penuh)
  Tests:  1 skipped, 500 passed (5105 assertions)        [489 → 500]
  PASS  Tests\Feature\RetentionEngineTest        <- 7 ujian enjin TIDAK berubah
  PASS  Tests\Feature\RetentionTenantScopeTest

$ vendor/bin/pint --dirty                {"tool":"pint","result":"passed"}
```

### Migrasi pada pemacu SEBENAR (bukan andaian — §5.3)

```
$ php artisan migrate --force        (DB SQLite segar)
$ Mosque::create([...tanpa override...])->auto_disposal_enabled
  false                              <- lalai kolum sebenar berkuat kuasa

$ php artisan migrate --pretend      (petikan)
  2026_08_03_000001_change_auto_disposal_default
    ⇂ select … pragma_table_xinfo('mosques','main') …
    ⇂ create table "__temp__mosques" ()
```

**Nota jujur tentang pemacu:** pada SQLite, `->change()` melakukan **pembinaan semula jadual**
(`__temp__mosques`) — itu mekanisme SQLite, bukan pilihan kita. Pada PostgreSQL ia sepatutnya
`ALTER … SET DEFAULT` ringan sahaja. Pelan menuntut ini **diuji, bukan dijanjikan**: bukti pgsql
datang daripada job integrasi CI (yang menjalankan `migrate` pada PostgreSQL sebenar) dan
daripada `migrate --force` semasa deploy produksi. **Kedua-duanya direkod dalam bukti deploy.**
Ujian rollback → re-migrate lulus, dan masjid sedia ada mengekalkan nilainya.

### Bukti penjaga menangkap regresi (5/5)

| Regresi disuntik | Keputusan |
|---|---|
| Pulangkan `->default('auto_padam')` | **merah** |
| Buang `confirmAutoPadam` daripada Create | **merah**: `getCreateFormAction tiada pengesahan` |
| Pulangkan lalai migrasi ke `true` | **merah** |
| Pulangkan teks pengakuan lama | **merah**, menamakan frasa yang hilang |
| **Buang skop tenant daripada kiraan impak** | **merah** — kiraan menjadi **7** (2 sendiri + 5 masjid lain) |
| **Pulih semula** | **11 hijau** |

Penjaga terakhir itu bukan formaliti: ia menunjukkan kebocoran silang-tenant **sebenar** dalam
angka yang dipaparkan kepada pengguna, iaitu keperluan #1 (§0.6 S1).

## (d) Kriteria Siap §5.6

| Kriteria | Status |
|---|---|
| Ujian baharu + suite hijau (termasuk `RetentionEngineTest` tak berubah) | ✔ 11 baharu · 500 lulus · enjin 7/7 kekal |
| Produksi: borang default "Semak"; pilih "Auto Padam" → dialog amaran berkiraan | ⏳ Deploy 4 |
| `mamad` & peraturan platform TIDAK berubah dalam DB produksi | ⏳ query sebelum/selepas Deploy 4 |
| **Status spec direkod eksplisit (C01)** | ✔ **(a) Addendum v2.6 diluluskan & dikomit; migrasi + teks §16.2 dikemas dalam commit yang SAMA** |
| Tiada housekeeping dalam commit ini (A2/A3 milik F10) | ✔ |
| Matriks keselamatan §0.6 S1–S6 | ✔ suite penuh + penjaga isolasi kiraan impak |

## (e) Lencongan dari spec

**TIADA lencongan.** Empat keputusan pelaksanaan direkod:

1. **Trait berkongsi, bukan kod berganda.** Pelan menunjukkan konsep per-halaman; brek yang sama
   diperlukan pada **tiga** laluan simpan (`create`, `createAnother`, `save`). Diletak dalam
   `app/Concerns/ConfirmsAutoPadamRetention.php` — sama konvensyen dengan `BelongsToMosque`.
2. **`parent::` dihormati sepenuhnya.** Pelan memberi amaran keras: memanggil `->action()` /
   `->submit()` semula memutuskan fungsi simpan. Dua ujian data-provider (`semak`,
   `auto_padam`) membuktikan simpan **masih berfungsi** — bukan hanya bahawa dialog muncul.
3. **Kiraan impak diskop tenant pada SETIAP hop** (`records` → `registry_files` →
   `classification_nodes`), tidak bergantung pada global scope, supaya ia kekal betul walaupun
   dipanggil di luar konteks panel.
4. **Teks pengakuan ditemui pada langkah 3 stepper**, bukan pada GET awal `/daftar` — ujian
   memandu komponen ke langkah itu. Percubaan pertama saya mengasert HTML GET dan **gagal**;
   itu penemuan ujian yang betul, bukan alasan untuk melonggarkan assertion.

## (f) Nota/risiko untuk fasa seterusnya

1. **⚠️ JANGAN jalankan `RetentionRuleSeeder` pada produksi** semasa/selepas fasa ini —
   `updateOrCreate` seeder akan menimpa peraturan yang ada. Direkod dalam runbook deploy.
2. **L3 sengaja tidak disentuh** (D3). Jika pemilik kemudian mahu peraturan platform bertukar
   kepada `semak`, itu **skrip berasingan yang diluluskan**, bukan `db:seed` semula.
3. Masjid sedia ada (`mamad`, `smoke`) kekal `auto_disposal_enabled = true`. Menukarnya ialah
   keputusan operasi pemilik melalui Tetapan Masjid atau tinker terdokumen — **bukan** migrasi.
4. Dialog pengesahan ialah brek **UI sahaja** (§5.2). Seeder/console/ujian sengaja tidak
   melaluinya; penguatkuasaan domain kekal pada gate `auto_disposal_enabled`.
5. Deploy 4 tiada perubahan aset frontend **kecuali** blade `/daftar` — rebuild `app`+`nginx`
   tetap dilakukan mengikut disiplin deploy, dan `migrate --force` akan menjadi bukti pgsql.

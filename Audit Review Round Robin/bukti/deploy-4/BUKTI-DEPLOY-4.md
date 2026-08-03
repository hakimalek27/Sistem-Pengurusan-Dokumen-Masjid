# Bukti Deploy 4 (F4 — Lalai retensi selamat) — bakwim.my

**Tarikh:** 3 Ogos 2026 · **Komit:** `08d3643` (F4 `f3a494f` + instrumen CI) ·
**CI:** run 30811698382 **7/7 HIJAU** · **Kumpulan deploy:** D7 · Deploy 4 = F4

---

## Kelulusan gate sebelum deploy

```
RUN 30811698382: completed/success
  PostgreSQL, Redis, Meili, OCR and tests :: success
  guidance-e2e (screen / workflow / tenant-admin-public) :: success (ketiga-tiganya)
  guidance-e2e-gate                       :: success
  Docker app image / Docker web image      :: success
```

⚠️ **Nota jujur:** run F4 sebelum ini (`f3a494f`, run 30807808377) **gagal** pada shard
`workflow` — di titik muat naik UI, isu yang **tiada kaitan dengan F4**. Deploy ditahan.
Run ini hijau, jadi deploy diteruskan. **Punca kegagalan shard itu masih TIDAK DIKETAHUI**
(lihat `bukti/plan-f4/LAPORAN-FASA-4.md`); corak kegagalan setakat ini F,P,F,P,F,**P**.

## Rantaian bukti runtime 5A (§10)

| # | Bukti | SEBELUM | SELEPAS |
|---|---|---|---|
| 1 | Git SHA server | `cab951e` | **`08d3643`** |
| 2a | `diwan-app` ID | `6789fc80` | **`3df4c706`** |
| 2b | `diwan-web` ID | `daead59f` | **`efd9337d`** |
| 4b | sha256 manifest | `fbd220f8c298700d` | `fbd220f8c298700d` (app = nginx) |

```
3a app/worker/scheduler  3df4c706e182   (= #2a)
3b nginx                 efd9337d5799   (= #2b)
5a/5b/6  help-BceoIbJG.js   e5f44081c878eb7d3c86131679560ae1  (app = nginx = awam)
         help-CrH0eDM1.css  0447d0f566a11f4d3a21c56c73db77fb  (app = nginx = awam)
```

**Rantaian: `3a=2a ✓ · 3b=2b ✓ · 4b app=nginx ✓ · 5a=5b=6 ✓`**
Aset Vite tidak berubah (F4 mengubah PHP + satu blade teks sahaja) — bukti deploy datang
daripada imej ID + kandungan dalam imej, seperti Deploy 2 dan 3.

## ✅ Migrasi pada PostgreSQL PRODUKSI

```
$ docker compose run --rm app php artisan migrate --force
   INFO  Running migrations.
  2026_08_03_000001_change_auto_disposal_default ................ 68.93ms DONE
```

**68.93ms** — `ALTER … SET DEFAULT` ringan, **tiada rewrite jadual**, tepat seperti §5.3
jangkakan. (CI pgsql: 3.50ms; SQLite membina semula jadual — mekanisme SQLite.)

## ⭐ Kriteria §5.6 yang paling penting: data operasi TIDAK berubah

Cap jari sha256 dikira ke atas **setiap** peraturan retensi
(`id|mosque_id|record_type|classification_prefix|retain_years|action`), disusun ikut id:

```
SEBELUM : c4117664eec7fe8aea374426508c612591825ba4f52506b12008a29c57b2ce09
SELEPAS : c4117664eec7fe8aea374426508c612591825ba4f52506b12008a29c57b2ce09
SEPADAN : YA — L3 & data operasi TIDAK tersentuh
```

| | SEBELUM | SELEPAS |
|---|---|---|
| Peraturan: jumlah / platform / per-masjid | 19 / 18 / 1 | **19 / 18 / 1** |
| Platform ikut tindakan | `auto_padam` 14 · `kekal` 4 | **`auto_padam` 14 · `kekal` 4** |
| `mamad` auto_disposal | `true` | **`true`** |
| `smoke` auto_disposal | `true` | **`true`** |

Angka **14/19 `auto_padam`** sepadan tepat dengan laporan audit asal — L3 kekal seperti
keputusan D3 (patuh tatacara ANM §16.1).

### Lalai baharu berkuat kuasa — dibuktikan TANPA mutasi produksi

Mencipta "masjid ujian" pada produksi hanya untuk melihat lalai adalah mutasi yang tidak
diperlukan. Bukti diambil daripada katalog PostgreSQL sendiri:

```sql
select column_default from information_schema.columns
 where table_name = 'mosques' and column_name = 'auto_disposal_enabled';
→ 'false'
```

## Bukti L1 + teks pengakuan HIDUP dalam imej

```
$ docker compose exec app grep -n "default('semak')" …/RetentionRuleResource.php
  62:                ->default('semak')

$ docker compose exec app grep -c ConfirmsAutoPadamRetention …/CreateRetentionRule.php  → 2
$ docker compose exec app grep -c ConfirmsAutoPadamRetention …/EditRetentionRule.php    → 2
$ docker compose exec app ls app/Concerns/ConfirmsAutoPadamRetention.php                → ada

$ docker compose exec app grep -c "Pelupusan automatik dimatikan secara lalai untuk masjid baharu" \
    resources/views/livewire/register-mosque.blade.php                                  → 1
$ … grep -o "Akta Arkib Negara 2003"                                                    → ada
```

Teks pengakuan **tidak** muncul pada GET awam `/daftar` — ia berada pada **langkah 3** stepper
pendaftaran. Itu bukan kegagalan deploy; ia keadaan yang ujian F4 sendiri temui dan kunci.

## Kesihatan pasca-deploy

```
/up                        : 200
diwan:health               : OK
diwan:smoke                : 9 lulus, 0 gagal
failed_jobs                : 0
schedule:list "Has Mutex"  : 0
container                  : 8/8 berjalan
laluan awam HTTPS          : / 200 · /log-masuk 200 · /daftar 200 · /bantuan 200
```

## ⚠️ Satu kriteria §5.6 memerlukan pemilik

> "Produksi: buka borang cipta peraturan → default **Semak**; pilih **Auto Padam** → dialog
> amaran dengan kiraan muncul."

Ini memerlukan **sesi berautentikasi** pada panel tenant. Saya tidak pernah mencipta atau
menaip kredensial produksi (polisi kekal sepanjang pelaksanaan ini), jadi pengesahan **visual**
milik pemilik. Yang saya buktikan: kod L1 **hidup dalam imej** (default, trait pada kedua-dua
halaman, fail trait) dan **11 ujian** membuktikan tingkah lakunya — termasuk bahawa dialog muncul
untuk `auto_padam`, tidak muncul untuk `semak`, dan **simpan masih berfungsi** untuk kedua-duanya.

Untuk pemilik: `/app/{slug}/retention-rules` → **Cipta** → medan *Tindakan* sepatutnya
menunjukkan **Semak**; tukar ke **Auto Padam** → **Simpan** → dialog "Sahkan peraturan
pemadaman automatik" dengan kiraan rekod dalam skop.

## ⛔ JANGAN jalankan `RetentionRuleSeeder` pada produksi

`updateOrCreate` seeder akan **menimpa** peraturan yang ada dan memusnahkan bukti cap jari di
atas. Jika L3 perlu berubah kelak (bukan keputusan sekarang — D3 = Kekal), ia **skrip
berasingan yang diluluskan**, bukan `db:seed`.

## Rollback

`git reset --hard cab951e` → `docker compose build app nginx` →
`up -d --force-recreate app worker scheduler nginx` →
`docker compose run --rm app php artisan migrate:rollback --step=1 --force`
(`down()` memulihkan lalai `true`). Baris data tidak pernah disentuh, jadi rollback bersih.

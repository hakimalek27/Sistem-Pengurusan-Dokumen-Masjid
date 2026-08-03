# Bukti Deploy 5 (F5 — Kandungan katalog & tour halaman awam) — bakwim.my

**Tarikh:** 4 Ogos 2026 · **Komit:** `5f4247a` · **CI:** run 30857969395 **7/7 HIJAU**
**Kumpulan deploy:** D7 · Deploy 5 = F5 · 📄 `bukti/plan-f5/LAPORAN-FASA-5.md`

---

## Kelulusan gate sebelum deploy

```
RUN 30857969395: completed/success
  PostgreSQL, Redis, Meili, OCR and tests  :: success
  guidance-e2e (screen)                     :: success
  guidance-e2e (workflow)                   :: success
  guidance-e2e (tenant-admin-public)        :: success
  guidance-e2e-gate                         :: success
  Docker app image / Docker web image       :: success
```

⚠️ **Tujuh pusingan CI, tujuh punca berbeza — semuanya tulen, tiada satu pun dikecualikan.**
Butiran penuh setiap pusingan ada dalam `bukti/plan-f5/LAPORAN-FASA-5.md` §(f). Ringkasan:

| # | Komit | Punca | Milik |
|---|---|---|---|
| 1 | `142cb56` | Gate encode 2 andaian yang F5 sengaja langgar | F5 (dijangka) |
| 2 | `f0115a6` | **Punca flake `workflow` yang berlarutan sejak F3** | produk/harness |
| 3 | `01a0c7e` | Layout tetamu tiada reset imej → overflow 1066px | **sedia ada** |
| 4 | `9c1d59a` | Locator dijadikan seluruh halaman → padan nod modal BASI | **salah saya** |
| 5 | `fac824d` | Advisori Guzzle CVE-2026-69245 (diterbit 30 min lebih awal) | luaran |
| 6 | `857f7ea` | Klik tak-dipercayai menutup modal tanpa menghantar borang | produk/harness |
| 7 | `5f4247a` | — **HIJAU** | — |

## Rantaian bukti runtime 5A (§10)

| # | Bukti | SEBELUM | SELEPAS |
|---|---|---|---|
| 1 | Git SHA server | `16c3376` | **`5f4247a`** |
| 2a | `diwan-app` ID | `3df4c706e182` | **`2831c4c83616`** |
| 2b | `diwan-web` ID | `efd9337d5799` | **`6e8e3f5a9fb4`** |
| 4 | sha256 manifest | `fbd220f8c298700d` | **`4aa3b2e56dbea6ad`** |
| 5 | Aset help | `help-BceoIbJG.js` | **`help-Da8KtLOe.js`** |
| — | `catalog_version` | `2026.08.03.3` | **`2026.08.04.1`** |

```
#1  git SHA        : 5f4247a
#2a diwan-app      : 2831c4c83616
#2b diwan-web      : 6e8e3f5a9fb4
#3a app container  : 2831c4c83616      (= #2a)
#3b nginx container: 6e8e3f5a9fb4      (= #2b)
#4a manifest app   : 4aa3b2e56dbea6ad
#4b manifest nginx : 4aa3b2e56dbea6ad  (= #4a)
#5  aset help      : assets/help-Da8KtLOe.js
#5a hash dlm app   : fb71a2eb9e2bb6580942e997f711d246
#5b hash dlm nginx : fb71a2eb9e2bb6580942e997f711d246
#6  hash AWAM      : fb71a2eb9e2bb6580942e997f711d246   (curl https://bakwim.my/…)
```

**Rantaian: `3a=2a ✓ · 3b=2b ✓ · 4a=4b ✓ · 5a=5b=6 ✓`** — algoritma hash: **md5**.

Aset Vite **berubah** kali ini (`help.js` mendapat import `nav-target-plan.js`), jadi nama aset
memang bukti sah untuk deploy ini — tidak seperti Deploy 2/4 (rujuk pelajaran metodologi).

## Sync indeks bantuan (WAJIB — katalog berubah)

```
$ docker compose exec -T -e HOME=/tmp app php artisan diwan:sync-help-index --delete
83 guide disegerakkan ke indeks diwan_help_guides.

$ docker compose exec -T app grep -o '"catalog_version": *"[^"]*"' resources/help/guides.json
"catalog_version": "2026.08.04.1"
```

## Migrasi

```
$ docker compose exec -T -e HOME=/tmp app php artisan migrate --force
   INFO  Nothing to migrate.
```

F5 tidak menambah migrasi — perubahannya kandungan katalog, blade, JS dan ujian sahaja.
**Sifar baris data disentuh.**

## Kod F5 hidup dalam imej produksi

```
data-help-nav dlm help.js  : 4       (ruang nama nav berasingan)
reset imej guest-layout    : 1       (img, svg, video { max-width:100% })
sasaran login              : 2       (login-identity + login-submit)
sasaran muat naik          : 2       (inbox-upload-dropzone + inbox-upload-submit)
nav-target-plan.js         : ada     (modul tulen F5c)
```

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

## ✅ Kriteria §6.6 #4 — DISAHKAN LIVE (bukan hanya dalam imej)

### Markup pada HTML AWAM bakwim.my
```
$ curl -s https://bakwim.my/log-masuk | grep -oE '…'
img, svg, video { max-width:100%; }
<header class="brand">
<main data-help-target="page-content">
data-help-target="login-identity"
data-help-target="login-submit"

$ curl -s https://bakwim.my/bantuan | grep -c 'data-help-target="page-content"'
1                    ← unik (tiada lagi bertindan)
```

### Tour `/log-masuk` — disahkan VISUAL dalam Chrome pada bakwim.my

**Langkah 1** — popover **"Masukkan identiti"**, sorotan mengelilingi **medan input**
(`login-identity`), "1 daripada 2", CTA **"Seterusnya"**.
**TIADA "Tindakan belum tersedia"** — RR-01-01 **MATI di produksi**.

**Langkah 2** — popover **"Minta pautan"**, sorotan mengelilingi butang
**"Hantar Pautan Log Masuk"** (`login-submit`), "2 daripada 2", CTA **"Buat pada skrin"**
(betul: ia tindakan sebenar, bukan "Selesai").

Sebelum F5, kedua-dua langkah jatuh ke popover ralat palsu setiap kali — layout tetamu tiada
`<main>`, jadi `page-content` dan `page-primary` kedua-duanya tidak dapat diselesaikan.

⚠️ **Baki kriteria §6.6 #4 milik pemilik:** "Peti Masuk → tour muat naik membuka aliran butang
sebenar (3 sasaran berbeza)" memerlukan **sesi berautentikasi**. Saya tidak pernah mencipta
atau menaip kredensial produksi. Yang dibuktikan: sasaran hidup dalam imej + 8 ujian e2e
(termasuk DOM modal sebenar) + 3 ujian Pest + gate `guidance-full` melalui kesemua 5 langkah
guide itu dengan muat naik SEBENAR pada CI.
Untuk pemilik: `/app/{slug}/peti-masuk` → **Pembantu Diwan** → panduan **Muat naik dokumen** →
langkah 1 sorot butang **+ Muat Naik Dokumen**, langkah 2 sorot **ruang seret fail**,
langkah 3 sorot butang **Hantar** (tiga tempat BERBEZA, bukan seluruh modal).

## Rollback

`git reset --hard 16c3376` → `docker compose build app nginx` →
`up -d --force-recreate app worker scheduler nginx` →
`docker compose exec -T -e HOME=/tmp app php artisan diwan:sync-help-index --delete`
(mengembalikan katalog `2026.08.03.3`). **Tiada migrasi untuk dirollback** dan tiada baris data
disentuh, jadi rollback bersih sepenuhnya.

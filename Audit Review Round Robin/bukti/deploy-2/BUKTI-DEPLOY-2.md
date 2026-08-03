# Bukti Deploy 2 (F6-W0) — bakwim.my

**Tarikh:** 3 Ogos 2026 · **Komit:** `aae4c97` · **CI:** run 30778509859 **7/7 HIJAU**
**Kumpulan deploy:** D7 (Deploy 2 = F6-W0, hotfix mobile sejurus selepas Deploy 1)

---

## Kelulusan gate sebelum deploy

```
$ gh run view 30778509859 --json headSha,status,conclusion,jobs
SHA: aae4c9729a1fd928e5de12d383eeb8e7e4d2f281
STATUS: completed / success
  PostgreSQL, Redis, Meili, OCR and tests :: success
  Docker web image                        :: success
  Docker app image                        :: success
  guidance-e2e (screen)                   :: success
  guidance-e2e (tenant-admin-public)      :: success
  guidance-e2e (workflow)                 :: success      <-- yang gagal pada run 30776919686
  guidance-e2e-gate                        :: success
```

Shard `workflow` **lulus** dengan kod koreografi ASAL yang dipulihkan. Ini mengesahkan
keputusan §(g) `LAPORAN-F6-W0.md`: kegagalan run 30776919686 ialah **flake**, bukan regresi.
Mengubah kod yang terbukti hijau atas dasar satu kegagalan yang belum berulang akan menjadi
kesilapan.

---

## Rantaian bukti runtime 5A (§10)

| # | Bukti | SEBELUM | SELEPAS |
|---|---|---|---|
| 1 | Git SHA server | `9619509` | **`aae4c97`** |
| 2a | `diwan-app` ID | `916f302c` | **`37516fd1`** |
| 2b | `diwan-web` ID | `dd486028` | **`4c7dac3c`** |
| 4a | Aset help (JS) | `help-BceoIbJG.js` | `help-BceoIbJG.js` (tidak berubah) |
| 4a | Aset help (CSS) | `help-CrH0eDM1.css` | `help-CrH0eDM1.css` (tidak berubah) |
| 4b | sha256 manifest | `fbd220f8c298700d` | `fbd220f8c298700d` (app = nginx) |

```
3a container keluarga app (ketiga-tiganya = #2a):
   diwan-app-1        37516fd137e1
   diwan-worker-1     37516fd137e1
   diwan-scheduler-1  37516fd137e1
3b container nginx (= #2b):
   diwan-nginx-1      4c7dac3ce04c

5a/5b/6 — hash badan aset app = nginx = respons awam (md5):
   assets/help-BceoIbJG.js   e5f44081c878eb7d3c86131679560ae1
   assets/help-CrH0eDM1.css  0447d0f566a11f4d3a21c56c73db77fb
```

**Rantaian: `3a=2a ✓ · 3b=2b ✓ · 4b app=nginx ✓ · 5a=5b=6 ✓`**

### ⚠️ Aset TIDAK berubah kali ini — dan itu BETUL

Berbeza dengan Deploy 1 (di mana `help.js` berubah, jadi nama aset berubah), W0 mengubah
**blade + `guides.json`** sahaja — bukan mana-mana entri Vite. Nama aset yang kekal ialah
tingkah laku hashing kandungan Vite yang betul, bukan tanda deploy gagal.

Oleh itu **nama aset tidak boleh dijadikan bukti deploy berkuat kuasa untuk deploy jenis ini.**
Bukti sebenar mesti datang daripada **kandungan di dalam imej** (kod di-bake) — lihat seksyen
berikut. Imej ID yang berubah (#2a/#2b) + kandungan imej yang disahkan = deploy berkuat kuasa.

**Nota metodologi (menutup kekeliruan berpotensi):** `BUKTI-DEPLOY-1.md` merekod
`af79c0c512731a21dea4b71e99bb1c5e` / `f2406b313fca404825c3aabc40aec121` untuk fail yang SAMA.
Nilai itu **tepat** — ia `sha256sum | cut -c1-32`, bukan md5. Disahkan hari ini:

```
$ sha256sum public/build/assets/help-BceoIbJG.js  | cut -c1-32
af79c0c512731a21dea4b71e99bb1c5e        <-- sama dgn rekod Deploy 1
```

Ini sekali gus membuktikan aset help **bait-untuk-bait identik** sejak Deploy 1.
Pelajaran: **labelkan algoritma hash dalam rekod bukti**, jika tidak nilai yang sah kelihatan
seperti percanggahan pada sesi berikutnya.

---

## Bukti kandungan W0 HIDUP di dalam imej (kod di-bake)

```
$ docker compose exec app grep -o '"catalog_version": *"[^"]*"' resources/help/guides.json
"catalog_version": "2026.08.03.2"

$ docker compose exec app grep -c data-help-target <blade>
kegemaran.blade.php        : 5
pelupusan-manual.blade.php : 5

$ sasaran W0 dalam imej:
kegemaran        : favourites-list, favourite-item, favourite-open, favourite-remove
pelupusan-manual : disposal-candidates, disposal-batches, disposal-warning,
                   disposal-actions, disposal-status

$ tajuk katalog dalam imej (placeholder "Langkah 1…5" TAMAT):
tenant.pelupusan:
  1. [disposal-candidates] Semak calon cukup tempoh
  2. [disposal-batches]    Pengerusi semak dan luluskan batch
  3. [disposal-batches]    Laksana hanya selepas diluluskan
  4. [disposal-batches]    Batch gagal: cuba semula, jangan pendua
  5. [disposal-warning]    Sijil disimpan, metadata kekal
tenant.kegemaran:
  1. [favourites-list]  Senarai kegemaran anda
  2. [favourites-list]  Buka Kegemaran dari menu Tugasan
  3. [favourite-item]   Klik item untuk buka sumber asal
  4. [favourite-item]   Bintang penuh membuang kegemaran
  5. [favourites-list]  Kegemaran tidak mengatasi permission
```

## Indeks bantuan (katalog berubah → wajib disegerakkan)

```
$ php artisan diwan:sync-help-index --delete
83 guide disegerakkan ke indeks diwan_help_guides.

$ Meili langsung /indexes/diwan_help_guides/search
tenant.kegemaran  hits=1 guide_id=tenant.kegemaran
tenant.pelupusan  hits=1 (padanan teratas guide pelupusan terkawal)
```

**Nota jujur:** `steps_text` dibina daripada `pluck('instruction')`
(`SyncHelpIndex.php:71`) — **bukan** `title`. Maka 10 tajuk baharu W0 **tidak** mengubah
teks boleh-cari, dan itu memang betul: W0 membaiki *sasaran sorotan* + *tajuk langkah*,
bukan kandungan arahan. Indeks kekal 83 guide, sihat.

## Kesihatan pasca-deploy

```
migrate (imej BAHARU, sebelum trafik) : Nothing to migrate    (W0 tiada migrasi)
nginx -t                               : syntax ok, test successful
diwan:sync-help-index --delete         : 83 guide disegerakkan
config:cache                           : Configuration cached successfully
/up                                    : 200
diwan:health                           : OK
diwan:smoke                            : 9 lulus, 0 gagal
failed_jobs                            : 0
schedule:list "Has Mutex"              : 0
container                              : 8/8 running
laluan awam HTTPS                      : / 200 · /log-masuk 200 · /daftar 200 · /bantuan 200
```

## Yang berkuat kuasa untuk pengguna

Pada telefon (390×664), dua panduan ini dahulu menyorot seluruh `<main>` pada setiap langkah,
jadi popover duduk di tengah dan **menutup perkara yang ia rujuk** (6 defect `centerCovered`
diukur pada produksi, `bukti/pusingan-11-codex/production-mobile-all-tour-steps.json`).

Kini setiap langkah menyorot elemen sebenar, dan setiap langkah bertajuk bermakna dan bukan
lagi "Langkah 1", "Langkah 2"…

## Rollback

`git reset --hard 9619509` (Deploy 1) → `docker compose build app nginx` →
`up -d --force-recreate app worker scheduler nginx` → `diwan:sync-help-index --delete`
(katalog perlu disegerakkan semula ke versi lama). Tiada migrasi, tiada perubahan data.

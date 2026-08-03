# Bukti Deploy 3 (F3 — Bahasa) — bakwim.my

**Tarikh:** 3 Ogos 2026 · **Komit:** `cab951e` · **CI:** run 30798675244 **7/7 HIJAU**
**Kumpulan deploy:** D7 · Deploy 3 = F3

---

## Kelulusan gate sebelum deploy

```
$ gh run view 30798675244
RUN: completed/success
  PostgreSQL, Redis, Meili, OCR and tests :: success
  guidance-e2e (screen)                   :: success
  guidance-e2e (workflow)                 :: success     <- pembaikan dispatchEvent
  guidance-e2e (tenant-admin-public)      :: success
  guidance-e2e-gate                       :: success
  Docker app image                        :: success
  Docker web image                        :: success
```

Pusingan CI pertama (`c1823b5`) merah dengan **dua punca tulen** — kedua-duanya dibaiki dalam
`cab951e`; lihat `bukti/plan-f3/LAPORAN-FASA-3.md`.

## Bukti punca, ditangkap daripada imej yang SEDANG BERJALAN (sebelum deploy)

```
$ docker compose exec app ls lang/
vendor
```

`lang/ms/` memang **tidak wujud** dalam produksi. Itu punca sebenar semua permukaan Inggeris
yang audit laporkan — bukan teori, tetapi keadaan yang dibaca terus daripada imej hidup.

## Rantaian bukti runtime 5A (§10)

| # | Bukti | SEBELUM | SELEPAS |
|---|---|---|---|
| 1 | Git SHA server | `aae4c97` | **`cab951e`** |
| 2a | `diwan-app` ID | `37516fd1` | **`6789fc80`** |
| 2b | `diwan-web` ID | `4c7dac3c` | **`daead59f`** |
| 4a | Aset help (JS/CSS) | `help-BceoIbJG.js` / `help-CrH0eDM1.css` | sama (tiada entri Vite disentuh) |
| 4b | sha256 manifest | `fbd220f8c298700d` | `fbd220f8c298700d` (app = nginx) |

```
3a container keluarga app (ketiga-tiganya = #2a):
   diwan-app-1  diwan-worker-1  diwan-scheduler-1   6789fc80c922
3b container nginx (= #2b):
   diwan-nginx-1                                    daead59f2b9c

5a/5b/6 — hash badan aset app = nginx = respons awam (md5):
   assets/help-BceoIbJG.js    e5f44081c878eb7d3c86131679560ae1
   assets/help-CrH0eDM1.css   0447d0f566a11f4d3a21c56c73db77fb
```

**Rantaian: `3a=2a ✓ · 3b=2b ✓ · 4b app=nginx ✓ · 5a=5b=6 ✓`**

Seperti Deploy 2, **aset Vite tidak berubah** dan itu betul: F3 mengubah fail PHP (`lang/`),
satu blade (teks sahaja) dan `guides.json` — tiada entri Vite. Bukti deploy berkuat kuasa
datang daripada **imej ID yang berubah + kandungan di dalam imej**:

```
$ docker compose exec app ls lang/
en   ms   ms.json   vendor          <- lang/ms kini HADIR

$ docker compose exec app grep -o '"catalog_version": *"[^"]*"' resources/help/guides.json
"catalog_version": "2026.08.03.3"
```

## Bukti bahasa BERKUAT KUASA pada runtime produksi

Dijalankan di dalam container `app` produksi, locale `ms`:

```
locale aktif: ms

1) VALIDASI
   Medan Failkan Ke wajib diisi.
   Medan Kod Akronim mestilah sekurang-kurangnya 3 aksara.

2) AUTH + PAGINATION
   Maklumat log masuk ini tidak sepadan dengan rekod kami.
   « Sebelumnya | Seterusnya »

3) BUTANG WIZARD
   next=Seterusnya  prev=Sebelumnya

4) KERANGKA E-MEL (dirender, TIADA e-mel dihantar)
   salam     BM: "Salam sejahtera,"
   penutup   BM: "Sekian,"
   footer    BM: "Hak cipta terpelihara."
   bocor-EN: TIADA
```

Baris pertama ialah **mesej rojak paling teruk yang audit laporkan**, kini mati:

> `The failkan Ke field is required.` → **`Medan Failkan Ke wajib diisi.`**

## Kesihatan pasca-deploy

```
migrate (imej BAHARU, sebelum trafik) : Nothing to migrate
diwan:sync-help-index --delete        : 83 guide disegerakkan
nginx -t                               : syntax ok, test successful
config:cache                           : Configuration cached successfully
/up                                    : 200
diwan:health                           : OK
diwan:smoke                            : 9 lulus, 0 gagal
failed_jobs                            : 0
schedule:list "Has Mutex"              : 0
container                              : 8/8 running
laluan awam HTTPS                      : / 200 · /log-masuk 200 · /daftar 200 · /bantuan 200
aset panel (regresi UI)                : help CSS 200 · help JS 200
```

## ⚠️ Satu kriteria §4.8 dipenuhi secara BERBEZA — dinyatakan terang-terangan

Pelan §4.8 menyenaraikan: *"Hantar e-mel ujian sebenar di staging/produksi
(`diwan:staging-check --mail-to=`) → baca kandungan: BM penuh dari salam hingga footer."*

**Saya TIDAK menghantar e-mel keluar.** Sebabnya:

1. Menghantar mesej ke luar bagi pihak pemilik ialah tindakan yang memerlukan kebenaran
   eksplisit, per-tindakan. Kelulusan pelan diberi dalam sesi terdahulu, bukan sesi ini.
2. Untuk **membaca** mesej yang dihantar, seseorang perlu membuka peti masuk. Peti masuk intake
   sistem (`spdmediwan@gmail.com`) **tidak boleh** dibuka: membukanya menandakan e-mel sebagai
   *Seen*, dan Diwan akan **melangkaunya selama-lamanya** (risiko yang sudah direkod).
3. Merender templat di dalam container produksi memberi **bukti yang sama** — imej produksi
   sebenar, locale sebenar, templat vendor sebenar — dengan sifar kesan sampingan.

**Jurang baki yang jujur:** rendering membuktikan templat + terjemahan. Ia **tidak** membuktikan
penghantaran SMTP melalui Brevo mengekalkan kandungan BM (pengekodan, quoted-printable).
Jurang itu kecil tetapi nyata. Pemilik boleh menutupnya bila-bila dengan satu arahan:

```bash
docker compose exec -e HOME=/tmp app php artisan diwan:staging-check --mail-to=<alamat-anda>
```

## Rollback

`git reset --hard aae4c97` → `docker compose build app nginx` →
`up -d --force-recreate app worker scheduler nginx` → `diwan:sync-help-index --delete`.
Tiada migrasi, tiada perubahan data.

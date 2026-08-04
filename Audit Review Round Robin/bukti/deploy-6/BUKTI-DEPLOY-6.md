# BUKTI DEPLOY 6 — F6-W1 (`screen` bertindakan) LIVE di bakwim.my

**Tarikh:** 5 Ogos 2026 · **Komit:** `cc9f0c7` · **CI:** run 30946820894 **7/7 HIJAU**

---

## 1. Gate sebelum deploy (tiada deploy atas gate merah)

| Check WAJIB | Status |
|---|---|
| `PostgreSQL, Redis, Meili, OCR and tests` | ✅ success |
| `guidance-e2e-gate` | ✅ success |
| `Docker app image` | ✅ success |
| `Docker web image` | ✅ success |
| `guidance-e2e (screen)` · `(workflow)` · `(tenant-admin-public)` | ✅ ✅ ✅ |

Tempatan pada komit sama: shard `screen` **30/30** · `workflow` **15/15** (10.7m) ·
`tenant-admin-public` **41/41** (15.8m) · **AGREGATOR: GATE LULUS 83 guide / 473 langkah /
190 langkah tindakan** (perbandingan SET, bukan kiraan) exit 0 · `unit` **17/17** ·
`guidance.spec.js` **20/20** · Pest **515 lulus / 1 skip** · pint passed.

## 2. Rantaian bukti runtime 5A (§10) — LULUS PENUH

| # | Bukti | Sebelum (Deploy 5) | Selepas (Deploy 6) |
|---|---|---|---|
| 1 | git SHA server | `bc7cccc` | **`cc9f0c7`** |
| 2a | ImageID `diwan-app` | `2831c4c83616` | **`2d00c92e3cac`** |
| 2b | ImageID `diwan-web` | `6e8e3f5a9fb4` | **`4824bd182d3a`** |
| 3a | container app/worker/scheduler | — | ketiga-tiganya `2d00c92e3cac` = #2a ✅ |
| 3b | container nginx | — | `4824bd182d3a` = #2b, ≠ #2a ✅ |
| 4a | nama aset EXACT (`resources/js/help.js`) | `help-Da8KtLOe.js` + `help-CrH0eDM1.css` | **`help-D0185fq1.js`** + `help-CrH0eDM1.css` |
| 4b | sha256 `manifest.json` app vs nginx | `4aa3b2e5…` | **`1aa1b3f4b87ae4d204e86d003706f3de`** (app = nginx ✅) |

**#5a = #5b = #6 untuk KEDUA-DUA aset:**

```
assets/help-D0185fq1.js    app/nginx/awam = 753f7e263047d5d0df10f3501fe92a0d  ✅
assets/help-CrH0eDM1.css   app/nginx/awam = f2406b313fca404825c3aabc40aec121  ✅
```

⭐ **RAMALAN YANG BOLEH GAGAL, DIBUAT SEBELUM DEPLOY DAN DISAHKAN SELEPASNYA:** kerana hanya
`resources/js/help.js` disentuh, JS **mesti** berubah dan CSS **mesti KEKAL**. Itulah yang
berlaku (`help-Da8KtLOe.js` → `help-D0185fq1.js`; `help-CrH0eDM1.css` tidak berubah). Rantaian
5A dengan itu menguji sesuatu, bukan sekadar merekod apa sahaja yang keluar.

## 3. Kesihatan selepas deploy

```
migrate --force        → INFO  Nothing to migrate.      (SIFAR baris data disentuh)
config:cache           → Configuration cached successfully.
sync-help-index --delete → 83 guide disegerakkan ke indeks diwan_help_guides.
/up                    → 200
diwan:smoke            → SMOKE E2E: 9 lulus, 0 gagal.
failed_jobs            → 0
container              → 8/8 running (app, clamav, db, meilisearch, nginx, redis, scheduler, worker)
laluan awam            → / 200 · /log-masuk 200 · /bantuan 200 · /daftar 200
```

⛔ `DemoSeeder` berubah dalam W1 tetapi **TIDAK** dijalankan di produksi (deploy hanya
`migrate --force`).

## 4. Pengesahan LIVE dalam Chrome (bakwim.my, laluan awam, tiada kredensial ditaip)

Tour `public.login` dipandu pada produksi sebenar:

| Langkah | Sorotan | CTA | Ralat palsu |
|---|---|---|---|
| **1/2** | `login-identity` (medan input) | `Seterusnya` | tiada |
| **2/2** "Minta pautan" | `login-submit` (butang Hantar Pautan) | `Buat pada skrin` | tiada |

Aset yang **benar-benar dihidang kepada pelayar**: `help-D0185fq1.js` — sepadan #4a/#5/#6.

⚠️ Butang langkah 2 **sengaja TIDAK** ditekan: ia menghantar e-mel pautan log masuk sebenar.
Menghantar mesej bagi pihak pemilik memerlukan kebenaran eksplisit.
⚠️ Pengesahan tour di dalam panel berautentikasi kekal milik pemilik — kredensial produksi
tidak pernah dicipta atau ditaip oleh saya.

## 5. Apa yang Deploy 6 bawa

**Produk (pengguna nampak bezanya):**
- **27 guide `screen`** kini menyorot kawalan SEBENAR, bukan seluruh halaman: placeholder tajuk
  katalog **258 → 0**, langkah tindakan bersasar generik **200 → 60**, defect popover mobile
  **6 → 0**.
- **Bug produk #1 F0 DITUTUP BETUL-BETUL** — auto-advance tour boleh MATI. Apabila mana-mana
  re-highlight (`refresh()`, atau morph Livewire) berlaku sebelum jadual 120ms menembak, jadual
  itu dibunuh dan guard menolak penggantinya kerana sasaran sudah wujud → tour terkandas KEKAL
  walaupun kawalan seterusnya sudah ada. F2 dilaporkan menutup bug ini; penutupan itu tidak
  meliputi laluan ini, jadi ia hidup di produksi sejak Deploy 1.
- **Sorotan fallback tidak lagi MELEKAT** — jika sasaran tiada pada saat Driver.js
  menyelesaikannya, tour dahulu menyorot seluruh halaman dan tidak pernah pulih.

**Harness gate:** lima kecacatan ditutup (lihat `bukti/plan-f6-w1/LAPORAN-FASA-6-W1.md`),
termasuk perekam urutan tour yang menjadikan G3 kalis-perlumbaan.

## 6. Baseline untuk deploy seterusnya

```
git  cc9f0c7 · app 2d00c92e3cac · web 4824bd182d3a
aset assets/help-D0185fq1.js (753f7e26…) + assets/help-CrH0eDM1.css (f2406b31…)
manifest.json sha256 1aa1b3f4b87ae4d204e86d003706f3de (app = nginx)
catalog_version 2026.08.04.2 · 83 guide dalam indeks
```

# Bukti Deploy 1 (F1 + F2) — bakwim.my

**Tarikh:** 3 Ogos 2026 · **Komit:** `9619509` · **CI:** run 30774069928 **7/7 HIJAU**
**Kumpulan deploy:** D7 (F1+F2 digabung) · **Deploy runtime PERTAMA** sejak pelaksanaan bermula

---

## Rantaian bukti runtime 5A (§10) — LULUS SEPENUHNYA

| # | Bukti | SEBELUM | SELEPAS |
|---|---|---|---|
| 1 | Git SHA server | `3f94a90` | `9619509` |
| 2a | `diwan-app` ID | `dca1f6cb…` | `916f302c…` |
| 2b | `diwan-web` ID | `292e2aa9…` | `dd486028…` |
| 4a | Aset help (JS) | `help-pJkQNpPs.js` | **`help-BceoIbJG.js`** |
| 4a | Aset help (CSS) | `help-PP-ALO9e.css` | **`help-CrH0eDM1.css`** |
| 4b | sha manifest | `7b81a135…` | `fbd220f8…` (app = nginx) |

```
3a container keluarga app (ketiga-tiganya = #2a):
   /diwan-app-1        916f302c…
   /diwan-worker-1     916f302c…
   /diwan-scheduler-1  916f302c…
3b container nginx (= #2b, BERBEZA drp #2a — betul, dua keluarga imej):
   /diwan-nginx-1      dd486028…

5a/5b/6 — hash aset app = nginx = badan respons awam:
   assets/help-BceoIbJG.js   af79c0c512731a21dea4b71e99bb1c5e  (app = nginx = curl)
   assets/help-CrH0eDM1.css  f2406b313fca404825c3aabc40aec121  (app = nginx = curl)
```

**Rantaian penuh: `3a=2a ✓ · 3b=2b ✓ · 4b sama ✓ · 5a=5b=6 ✓`** — kod dalam repo = kod dalam
imej = kod yang pengguna terima. Aset **berubah** berbanding baseline F0, membuktikan deploy
benar-benar berkuat kuasa (bukan sekadar git server yang betul).

## Kesihatan pasca-deploy

```
migrate (dari imej BAHARU, sebelum trafik) : Nothing to migrate     (F1/F2 tiada migrasi)
nginx -t                                    : syntax ok, test successful
config:cache                                : Configuration cached successfully
/up (dalaman)                               : 200
diwan:health                                : OK
diwan:smoke                                 : 9 lulus, 0 gagal
failed_jobs                                 : 0
schedule:list "Has Mutex"                   : 0
container                                   : 8/8 running (app clamav db meilisearch nginx redis scheduler worker)
laluan awam HTTPS                           : / 200 · /log-masuk 200 · /daftar 200 · /bantuan 200
```

## Pembaikan disahkan HIDUP untuk pengguna (GET awam, 0 tulisan)

```
$ curl -fsS https://bakwim.my/build/assets/help-CrH0eDM1.css
.diwan-tour-waiting,.diwan-tour-waiting *{pointer-events:auto}     ← PEMBAIKAN F2 (VERIFIKASI-F0 §17/§20)
.driver-active *{pointer-events:none}                              ← peraturan vendor (kekal, dijangka)
```

Sebelum deploy ini, pengguna tetikus yang menekan "Buat pada skrin" lalu memerlukan arahan
semula **terkandas sepenuhnya** — popover tersembunyi dan butang "Tunjuk arahan" menolak klik.
Kini banner boleh diklik.

## Yang berkuat kuasa untuk pengguna hari ini

1. **F1** — Pembantu Diwan tidak lagi hilang selepas interaksi Livewire (19/25 halaman,
   termasuk semua 11 halaman superadmin); URL bantuan root tidak lagi `//`.
2. **F2a** — label butang tour kini bermaksud tepat satu perkara (20 CTA "Buat pada skrin"
   palsu pada langkah generik tamat).
3. **F2b** — popover fallback Bahasa Melayu penuh.
4. **F2c** — pada telefon, popover auto-mengecil apabila ia benar-benar menutup modal.
5. **F2d** — fokus papan kekunci masuk popover bila tour mula, pulang ke butang Pembantu
   Diwan bila tour tutup.
6. **Banner "Tunjuk arahan" boleh diklik** — laluan pemulihan pengguna dipulihkan.

## Rollback

`git reset --hard c4f2f74` (F0) → `docker compose build app nginx` → `up -d --force-recreate
app worker scheduler nginx`. Tiada migrasi, tiada perubahan data — rollback bersih.

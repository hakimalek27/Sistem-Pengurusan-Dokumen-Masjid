# BUKTI DEPLOY 10 — F6-W4 ke bakwim.my

**Komit:** `cea55da19026a568785fa6c0c5f6b9c5f9a496b4`
**CI:** run 31095436926 — **7/7 HIJAU** (termasuk `guidance-e2e-gate`)
**Tarikh:** 6 Ogos 2026

Gate yang membenarkan deploy ini:

```
success  PostgreSQL, Redis, Meili, OCR and tests
success  guidance-e2e (screen)
success  guidance-e2e (workflow)             ← 15 passed (7.7m) — KESEMUA 15 guide
success  guidance-e2e (tenant-admin-public)
success  guidance-e2e-gate                   ← agregator SET
success  Docker app image · Docker web image
```

---

## Ramalan ditulis SEBELUM deploy — 6/7 tepat

| # | Ramalan | Keputusan |
|---|---|---|
| 1 | aset JS berubah `help-D0185fq1.js` → `help-B9tTj0Zg.js`; CSS `help-CrH0eDM1.css` KEKAL | ✔ tepat |
| 2 | `catalog_version` `2026.08.05.2` → `2026.08.06.1` | ✔ tepat |
| 3 | langkah bersasar generik dalam imej 236 → 159 | ✔ tepat |
| 4 | `Nothing to migrate` | ✔ tepat |
| 5 | `sync-help-index --delete` → 83 guide | ✔ tepat |
| 6 | label revisi `2cd7ab8` → `cea55da`; kedua-dua ImageID berubah | ✔ tepat |
| 7 | cakera 82% → prune dijalankan | ✘ **SALAH** |

**Ramalan 7 salah:** cakera sebenar **53%** semasa deploy, jadi cabang prune (ambang >80%)
tidak dijalankan langsung. Angka 82% yang saya ukur awal sesi sudah lapuk apabila deploy
berlaku. Kesan: tiada — prune memang tidak diperlukan. Selepas deploy: **61%**.

---

## Rantaian bukti runtime 5A

```
#1  git pelayan   : e8bfd75 → cea55da  (merge --ff-only, TANPA sudo)
#2  revisi imej   : 2cd7ab8 → cea55da19026a568785fa6c0c5f6b9c5f9a496b4
    app  ImageID  : 2c43512f9004 → ba034a48e81a
    web  ImageID  : 8e02a4b00223 → 016f6b18c559

#2a kandungan DALAM imej app (bukti kandungan, bukan nama fail):
    catalog_version 2026.08.06.1 | guide 83 | langkah 473 | generik 159

#3a nama aset EXACT drp manifest dalam imej app:
    resources/js/help.js -> assets/help-B9tTj0Zg.js   css: assets/help-CrH0eDM1.css

#3b hash DALAM imej app:
    b3c3da555e6a6bdbe4ea79440c3f3993  public/build/assets/help-B9tTj0Zg.js
    f2406b313fca404825c3aabc40aec121  public/build/assets/help-CrH0eDM1.css

#4b hash SAMA di dalam imej nginx:
    b3c3da555e6a6bdbe4ea79440c3f3993  /var/www/html/public/build/assets/help-B9tTj0Zg.js
    f2406b313fca404825c3aabc40aec121  /var/www/html/public/build/assets/help-CrH0eDM1.css

#5a/#5b/#6 hash BADAN yang dihidang awam (curl -fsS, bukan -sI):
    help-B9tTj0Zg.js    b3c3da555e6a6bdbe4ea79440c3f3993
    help-CrH0eDM1.css   f2406b313fca404825c3aabc40aec121
```

**#3b = #4b = #5a untuk KEDUA-DUA aset** — imej app, imej nginx, dan apa yang pelayar terima
adalah bait-untuk-bait sama.

## Kesihatan selepas deploy

```
8/8 container running (app · clamav · db · meilisearch · nginx · redis · scheduler · worker)
diwan:health   OK
diwan:smoke    9 lulus, 0 gagal
GET /up        200
failed_jobs    0
cakera         53% → 61%
aset dihidang  35,857 bait · /bantuan 200
```

## Nota prosedur

- Skrip dijalankan sebagai **FAIL** (`scp` → `bash /tmp/deploy-10.sh <sha>`), bukan
  `ssh 'bash -s' <` — `docker compose exec` menelan baki skrip (pelajaran Deploy 7).
- Setiap `docker compose exec` membawa `< /dev/null`.
- Git di `/opt/diwan` dijalankan **tanpa sudo** (pelajaran Deploy 8); checkout kekal bersih.
- ⚠️ **Skrip keluar dengan kod 1** pada baris TERAKHIR sahaja: semakan `failed_jobs` melalui
  `artisan tinker` gagal dengan `Writing to directory /var/www/.config/psysh is not allowed`.
  Itu kecacatan skrip saya, bukan deploy — `tinker` memerlukan `-e HOME=/tmp` (pelajaran
  deploy terdahulu yang saya terlepas semasa menulis skrip ini). Dijalankan semula dengan
  `HOME=/tmp`: **`failed_jobs=0`**. Skrip perlu dibetulkan sebelum Deploy 11.

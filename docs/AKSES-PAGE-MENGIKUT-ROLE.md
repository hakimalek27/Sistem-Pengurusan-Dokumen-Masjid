# Senarai Page Mengikut Role — DIJANA

> ⚠️ **Fail ini DIJANA.** Jangan sunting dengan tangan.
> Sumber: `Audit Review Round Robin/bukti/plan-baseline/manifest.json` → `role_routes`.
> Jana semula: `node scripts/audit/generate-role-access-doc.mjs`
> Penjaga: `tests/Feature/RoleAccessDocTest.php` (gagal jika fail ini menyimpang).

**Sistem:** Diwan / SPDM · **catalog_version manifest:** `2026.08.08.2`
**Identiti:** 10 · **Entri route:** 410

Definisi "page terlihat" = route yang `expected_access = allow` **dan** muncul dalam
navigasi (`in_navigation`). Butang, modal dan tindakan DALAM sesuatu page masih
tertakluk kepada permission, policy, status rekod, sensitiviti dokumen dan keahlian
tenant — dokumen ini tidak membuat dakwaan tentangnya.

## Ringkasan

⚠️ Kiraan beku manifest ialah **panel `app`** — `guidance.spec.js` mengiranya melalui
sidebar panel tenant. Superadmin turut mempunyai halaman panel `admin`; ia dilajurkan
berasingan supaya tiada nombor dalam dokumen ini bercanggah dengan manifestnya.

| Role | Panel `app` | Panel `admin` | Kiraan beku (`app`) | Sepadan |
|---|---:|---:|---:|---|
| Superadmin (Pentadbir Platform) | 25 | 12 | 25 | ✔ |
| Admin / Kerani | 25 | 0 | 25 | ✔ |
| Pengerusi | 17 | 0 | 17 | ✔ |
| Setiausaha | 15 | 0 | 15 | ✔ |
| Bendahari | 15 | 0 | 15 | ✔ |
| Nazir | 13 | 0 | 13 | ✔ |
| Ketua Imam | 13 | 0 | 13 | ✔ |
| AJK | 13 | 0 | 13 | ✔ |
| Juruaudit | 14 | 0 | 14 | ✔ |
| Orang Awam (tidak log masuk) | 0 | 0 | 0 | ✔ |
| **Jumlah panel `app` (tanpa awam)** | **150** | | | |

## Perbandingan dengan crawl produksi 21 Julai 2026 — DIKIRA

`AKSES-PAGE-MENGIKUT-ROLE-PRODUCTION-2026-07-21.md` ialah rekod **bertarikh** crawl produksi dan **tidak diubah**.
Perbandingan di bawah dikira oleh penjana ini setiap kali ia dijalankan.

| Role | Dokumen 21 Jul | Manifest (`app` nav) | Tambahan | Hilang |
|---|---:|---:|---:|---:|
| admin_masjid | 21 | 25 | 4 | 0 |
| pengerusi | 15 | 17 | 2 | 0 |
| setiausaha | 13 | 15 | 2 | 0 |
| bendahari | 13 | 15 | 2 | 0 |
| nazir | 12 | 13 | 1 | 0 |
| ketua_imam | 12 | 13 | 1 | 0 |
| ajk | 12 | 13 | 1 | 0 |
| audit | 13 | 14 | 1 | 0 |

**Jumlah tambahan 14 · jumlah HILANG 0.**

Tiada halaman dalam dokumen 21 Julai yang hilang daripada manifest, jadi kedua-duanya KONSISTEN — bezanya masa, bukan percanggahan.

Halaman unik yang manifest ada tetapi dokumen 21 Julai tiada:

- `/app/{tenant}/analitik-bantuan`
- `/app/{tenant}/bantuan`
- `/app/{tenant}/log-aktiviti`
- `/app/{tenant}/tiket-sokongan`

*(Sejarah git bagi halaman ini menunjukkan ia ditambah 2026-07-22 — sehari*
*selepas crawl: `f9e4e09` dan `b9a5c30`. Itu fakta git, bukan dikira di sini.)*

## Superadmin (Pentadbir Platform) — 25 page (`app`) + 12 (`admin`)

Panel `app`:
1. `/app/{tenant}`
2. `/app/{tenant}/ahli-peranan`
3. `/app/{tenant}/analitik-bantuan`
4. `/app/{tenant}/bantuan`
5. `/app/{tenant}/carian`
6. `/app/{tenant}/classification-nodes`
7. `/app/{tenant}/delegasi`
8. `/app/{tenant}/kegemaran`
9. `/app/{tenant}/kelulusan`
10. `/app/{tenant}/laporan`
11. `/app/{tenant}/log-aktiviti`
12. `/app/{tenant}/minit-saya`
13. `/app/{tenant}/pelupusan`
14. `/app/{tenant}/pembetulan-rekod`
15. `/app/{tenant}/penggunaan`
16. `/app/{tenant}/persediaan`
17. `/app/{tenant}/peti-masuk`
18. `/app/{tenant}/profil`
19. `/app/{tenant}/records`
20. `/app/{tenant}/registry-files`
21. `/app/{tenant}/retensi`
22. `/app/{tenant}/retensi-peraturan`
23. `/app/{tenant}/sensitive-access-logs`
24. `/app/{tenant}/tetapan-masjid`
25. `/app/{tenant}/tiket-sokongan`

Panel `admin`:
1. `/admin`
2. `/admin/analitik-bantuan`
3. `/admin/bantuan`
4. `/admin/help-announcements`
5. `/admin/mosques`
6. `/admin/profil-saya`
7. `/admin/status-sambungan`
8. `/admin/storage-orders`
9. `/admin/tetapan-platform`
10. `/admin/tiket-sokongan`
11. `/admin/users`
12. `/admin/whatsapp-platform`

## Admin / Kerani — 25 page (`app`)

Panel `app`:
1. `/app/{tenant}`
2. `/app/{tenant}/ahli-peranan`
3. `/app/{tenant}/analitik-bantuan`
4. `/app/{tenant}/bantuan`
5. `/app/{tenant}/carian`
6. `/app/{tenant}/classification-nodes`
7. `/app/{tenant}/delegasi`
8. `/app/{tenant}/kegemaran`
9. `/app/{tenant}/kelulusan`
10. `/app/{tenant}/laporan`
11. `/app/{tenant}/log-aktiviti`
12. `/app/{tenant}/minit-saya`
13. `/app/{tenant}/pelupusan`
14. `/app/{tenant}/pembetulan-rekod`
15. `/app/{tenant}/penggunaan`
16. `/app/{tenant}/persediaan`
17. `/app/{tenant}/peti-masuk`
18. `/app/{tenant}/profil`
19. `/app/{tenant}/records`
20. `/app/{tenant}/registry-files`
21. `/app/{tenant}/retensi`
22. `/app/{tenant}/retensi-peraturan`
23. `/app/{tenant}/sensitive-access-logs`
24. `/app/{tenant}/tetapan-masjid`
25. `/app/{tenant}/tiket-sokongan`

## Pengerusi — 17 page (`app`)

Panel `app`:
1. `/app/{tenant}`
2. `/app/{tenant}/bantuan`
3. `/app/{tenant}/carian`
4. `/app/{tenant}/classification-nodes`
5. `/app/{tenant}/delegasi`
6. `/app/{tenant}/kegemaran`
7. `/app/{tenant}/kelulusan`
8. `/app/{tenant}/laporan`
9. `/app/{tenant}/log-aktiviti`
10. `/app/{tenant}/minit-saya`
11. `/app/{tenant}/pelupusan`
12. `/app/{tenant}/pembetulan-rekod`
13. `/app/{tenant}/penggunaan`
14. `/app/{tenant}/profil`
15. `/app/{tenant}/records`
16. `/app/{tenant}/registry-files`
17. `/app/{tenant}/sensitive-access-logs`

## Setiausaha — 15 page (`app`)

Panel `app`:
1. `/app/{tenant}`
2. `/app/{tenant}/bantuan`
3. `/app/{tenant}/carian`
4. `/app/{tenant}/classification-nodes`
5. `/app/{tenant}/delegasi`
6. `/app/{tenant}/kegemaran`
7. `/app/{tenant}/kelulusan`
8. `/app/{tenant}/laporan`
9. `/app/{tenant}/log-aktiviti`
10. `/app/{tenant}/minit-saya`
11. `/app/{tenant}/pembetulan-rekod`
12. `/app/{tenant}/peti-masuk`
13. `/app/{tenant}/profil`
14. `/app/{tenant}/records`
15. `/app/{tenant}/registry-files`

## Bendahari — 15 page (`app`)

Panel `app`:
1. `/app/{tenant}`
2. `/app/{tenant}/bantuan`
3. `/app/{tenant}/carian`
4. `/app/{tenant}/classification-nodes`
5. `/app/{tenant}/delegasi`
6. `/app/{tenant}/kegemaran`
7. `/app/{tenant}/kelulusan`
8. `/app/{tenant}/laporan`
9. `/app/{tenant}/log-aktiviti`
10. `/app/{tenant}/minit-saya`
11. `/app/{tenant}/pembetulan-rekod`
12. `/app/{tenant}/penggunaan`
13. `/app/{tenant}/profil`
14. `/app/{tenant}/records`
15. `/app/{tenant}/registry-files`

## Nazir — 13 page (`app`)

Panel `app`:
1. `/app/{tenant}`
2. `/app/{tenant}/bantuan`
3. `/app/{tenant}/carian`
4. `/app/{tenant}/classification-nodes`
5. `/app/{tenant}/delegasi`
6. `/app/{tenant}/kegemaran`
7. `/app/{tenant}/kelulusan`
8. `/app/{tenant}/laporan`
9. `/app/{tenant}/minit-saya`
10. `/app/{tenant}/pembetulan-rekod`
11. `/app/{tenant}/profil`
12. `/app/{tenant}/records`
13. `/app/{tenant}/registry-files`

## Ketua Imam — 13 page (`app`)

Panel `app`:
1. `/app/{tenant}`
2. `/app/{tenant}/bantuan`
3. `/app/{tenant}/carian`
4. `/app/{tenant}/classification-nodes`
5. `/app/{tenant}/delegasi`
6. `/app/{tenant}/kegemaran`
7. `/app/{tenant}/kelulusan`
8. `/app/{tenant}/laporan`
9. `/app/{tenant}/minit-saya`
10. `/app/{tenant}/pembetulan-rekod`
11. `/app/{tenant}/profil`
12. `/app/{tenant}/records`
13. `/app/{tenant}/registry-files`

## AJK — 13 page (`app`)

Panel `app`:
1. `/app/{tenant}`
2. `/app/{tenant}/bantuan`
3. `/app/{tenant}/carian`
4. `/app/{tenant}/classification-nodes`
5. `/app/{tenant}/delegasi`
6. `/app/{tenant}/kegemaran`
7. `/app/{tenant}/kelulusan`
8. `/app/{tenant}/laporan`
9. `/app/{tenant}/minit-saya`
10. `/app/{tenant}/pembetulan-rekod`
11. `/app/{tenant}/profil`
12. `/app/{tenant}/records`
13. `/app/{tenant}/registry-files`

## Juruaudit — 14 page (`app`)

Panel `app`:
1. `/app/{tenant}`
2. `/app/{tenant}/bantuan`
3. `/app/{tenant}/carian`
4. `/app/{tenant}/classification-nodes`
5. `/app/{tenant}/delegasi`
6. `/app/{tenant}/kegemaran`
7. `/app/{tenant}/kelulusan`
8. `/app/{tenant}/laporan`
9. `/app/{tenant}/minit-saya`
10. `/app/{tenant}/pembetulan-rekod`
11. `/app/{tenant}/profil`
12. `/app/{tenant}/records`
13. `/app/{tenant}/registry-files`
14. `/app/{tenant}/sensitive-access-logs`

## Orang Awam (tidak log masuk) — 0 page (`app`)

_Tiada halaman navigasi._

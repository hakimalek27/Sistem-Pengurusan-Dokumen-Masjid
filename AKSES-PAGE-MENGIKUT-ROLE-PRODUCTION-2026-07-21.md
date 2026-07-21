# Senarai Page Mengikut Role - Production

**Sistem:** Diwan / SPDM  
**Production:** `https://bakwim.my`  
**Tarikh pengesahan:** 21 Julai 2026  
**Kaedah:** Chrome Playwright sebenar, satu `BrowserContext` berasingan bagi setiap role di tenant ujian `smoke`.

## Ringkasan

| Role | Jumlah page terlihat | Keputusan live |
|---|---:|---|
| Admin / Kerani | 21 | Semua `200`, silang tenant `404`, tiada browser error |
| Pengerusi | 15 | Semua `200`, silang tenant `404`, tiada browser error |
| Setiausaha | 13 | Semua `200`, silang tenant `404`, tiada browser error |
| Bendahari | 13 | Semua `200`, silang tenant `404`, tiada browser error |
| Nazir | 12 | Semua `200`, silang tenant `404`, tiada browser error |
| Ketua Imam | 12 | Semua `200`, silang tenant `404`, tiada browser error |
| AJK | 12 | Semua `200`, silang tenant `404`, tiada browser error |
| Juruaudit | 13 | Semua `200`, silang tenant `404`, tiada browser error |
| **Jumlah** | **111** | **Lulus** |

> Nota: Jumlah Admin / Kerani yang direkodkan oleh live crawl ialah **21**, bukan 22.
> Senarai ini ialah page yang kelihatan pada sidebar dan boleh dibuka. Butang, modal dan
> tindakan dalam sesuatu page masih tertakluk kepada permission, policy, status rekod,
> sensitiviti dokumen dan keahlian tenant.

## 1. Admin / Kerani - 21 page

1. Dashboard - `/app/{tenant}`
2. Log Akses Sensitif - `/app/{tenant}/sensitive-access-logs`
3. Persediaan Berpandu - `/app/{tenant}/persediaan`
4. Ahli dan Peranan - `/app/{tenant}/ahli-peranan`
5. Klasifikasi Fail - `/app/{tenant}/classification-nodes`
6. Pelupusan - `/app/{tenant}/pelupusan`
7. Peraturan Retensi - `/app/{tenant}/retensi-peraturan`
8. Tetapan Masjid - `/app/{tenant}/tetapan-masjid`
9. Penggunaan dan Storan - `/app/{tenant}/penggunaan`
10. Retensi - `/app/{tenant}/retensi`
11. Delegasi - `/app/{tenant}/delegasi`
12. Profil - `/app/{tenant}/profil`
13. Peti Masuk - `/app/{tenant}/peti-masuk`
14. Rekod - `/app/{tenant}/records`
15. Fail - `/app/{tenant}/registry-files`
16. Minit Saya - `/app/{tenant}/minit-saya`
17. Kelulusan - `/app/{tenant}/kelulusan`
18. Carian - `/app/{tenant}/carian`
19. Kegemaran - `/app/{tenant}/kegemaran`
20. Laporan - `/app/{tenant}/laporan`
21. Pembetulan Rekod - `/app/{tenant}/pembetulan-rekod`

## 2. Pengerusi - 15 page

1. Dashboard - `/app/{tenant}`
2. Log Akses Sensitif - `/app/{tenant}/sensitive-access-logs`
3. Penggunaan dan Storan - `/app/{tenant}/penggunaan`
4. Delegasi - `/app/{tenant}/delegasi`
5. Profil - `/app/{tenant}/profil`
6. Minit Saya - `/app/{tenant}/minit-saya`
7. Kelulusan - `/app/{tenant}/kelulusan`
8. Carian - `/app/{tenant}/carian`
9. Kegemaran - `/app/{tenant}/kegemaran`
10. Laporan - `/app/{tenant}/laporan`
11. Pembetulan Rekod - `/app/{tenant}/pembetulan-rekod`
12. Klasifikasi Fail - `/app/{tenant}/classification-nodes`
13. Pelupusan - `/app/{tenant}/pelupusan`
14. Rekod - `/app/{tenant}/records`
15. Fail - `/app/{tenant}/registry-files`

## 3. Setiausaha - 13 page

1. Dashboard - `/app/{tenant}`
2. Peti Masuk - `/app/{tenant}/peti-masuk`
3. Rekod - `/app/{tenant}/records`
4. Fail - `/app/{tenant}/registry-files`
5. Minit Saya - `/app/{tenant}/minit-saya`
6. Kelulusan - `/app/{tenant}/kelulusan`
7. Carian - `/app/{tenant}/carian`
8. Kegemaran - `/app/{tenant}/kegemaran`
9. Laporan - `/app/{tenant}/laporan`
10. Pembetulan Rekod - `/app/{tenant}/pembetulan-rekod`
11. Klasifikasi Fail - `/app/{tenant}/classification-nodes`
12. Delegasi - `/app/{tenant}/delegasi`
13. Profil - `/app/{tenant}/profil`

## 4. Bendahari - 13 page

1. Dashboard - `/app/{tenant}`
2. Penggunaan dan Storan - `/app/{tenant}/penggunaan`
3. Delegasi - `/app/{tenant}/delegasi`
4. Profil - `/app/{tenant}/profil`
5. Minit Saya - `/app/{tenant}/minit-saya`
6. Kelulusan - `/app/{tenant}/kelulusan`
7. Carian - `/app/{tenant}/carian`
8. Kegemaran - `/app/{tenant}/kegemaran`
9. Laporan - `/app/{tenant}/laporan`
10. Pembetulan Rekod - `/app/{tenant}/pembetulan-rekod`
11. Klasifikasi Fail - `/app/{tenant}/classification-nodes`
12. Rekod - `/app/{tenant}/records`
13. Fail - `/app/{tenant}/registry-files`

## 5. Nazir - 12 page

1. Dashboard - `/app/{tenant}`
2. Minit Saya - `/app/{tenant}/minit-saya`
3. Kelulusan - `/app/{tenant}/kelulusan`
4. Carian - `/app/{tenant}/carian`
5. Kegemaran - `/app/{tenant}/kegemaran`
6. Laporan - `/app/{tenant}/laporan`
7. Pembetulan Rekod - `/app/{tenant}/pembetulan-rekod`
8. Klasifikasi Fail - `/app/{tenant}/classification-nodes`
9. Rekod - `/app/{tenant}/records`
10. Fail - `/app/{tenant}/registry-files`
11. Delegasi - `/app/{tenant}/delegasi`
12. Profil - `/app/{tenant}/profil`

## 6. Ketua Imam - 12 page

1. Dashboard - `/app/{tenant}`
2. Minit Saya - `/app/{tenant}/minit-saya`
3. Kelulusan - `/app/{tenant}/kelulusan`
4. Carian - `/app/{tenant}/carian`
5. Kegemaran - `/app/{tenant}/kegemaran`
6. Laporan - `/app/{tenant}/laporan`
7. Pembetulan Rekod - `/app/{tenant}/pembetulan-rekod`
8. Klasifikasi Fail - `/app/{tenant}/classification-nodes`
9. Rekod - `/app/{tenant}/records`
10. Fail - `/app/{tenant}/registry-files`
11. Delegasi - `/app/{tenant}/delegasi`
12. Profil - `/app/{tenant}/profil`

## 7. AJK - 12 page

1. Dashboard - `/app/{tenant}`
2. Minit Saya - `/app/{tenant}/minit-saya`
3. Kelulusan - `/app/{tenant}/kelulusan`
4. Carian - `/app/{tenant}/carian`
5. Kegemaran - `/app/{tenant}/kegemaran`
6. Laporan - `/app/{tenant}/laporan`
7. Pembetulan Rekod - `/app/{tenant}/pembetulan-rekod`
8. Klasifikasi Fail - `/app/{tenant}/classification-nodes`
9. Rekod - `/app/{tenant}/records`
10. Fail - `/app/{tenant}/registry-files`
11. Delegasi - `/app/{tenant}/delegasi`
12. Profil - `/app/{tenant}/profil`

## 8. Juruaudit - 13 page

1. Dashboard - `/app/{tenant}`
2. Log Akses Sensitif - `/app/{tenant}/sensitive-access-logs`
3. Minit Saya - `/app/{tenant}/minit-saya`
4. Kelulusan - `/app/{tenant}/kelulusan`
5. Carian - `/app/{tenant}/carian`
6. Kegemaran - `/app/{tenant}/kegemaran`
7. Laporan - `/app/{tenant}/laporan`
8. Pembetulan Rekod - `/app/{tenant}/pembetulan-rekod`
9. Klasifikasi Fail - `/app/{tenant}/classification-nodes`
10. Rekod - `/app/{tenant}/records`
11. Fail - `/app/{tenant}/registry-files`
12. Delegasi - `/app/{tenant}/delegasi`
13. Profil - `/app/{tenant}/profil`

## Pengasingan Tenant

Semua role di atas diuji daripada tenant `smoke`. Cubaan membuka
`/app/mamad/records` memberikan `HTTP 404` bagi setiap role. Ini mengesahkan halaman
tenant lain tidak didedahkan melalui URL silang tenant dalam ujian ini.

## Superadmin Platform

Superadmin menggunakan panel global `/admin` dan bukan salah satu daripada lapan role
tenant di atas. Akaun superadmin production tidak digunakan dalam crawl role ini kerana
kata laluan sebenar tidak diubah atau diteka.

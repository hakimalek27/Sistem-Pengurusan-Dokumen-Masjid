# RESTORE RUNBOOK — Diwan (SPDM) · bakwim.my

**Tujuan:** panduan pulih data apabila berlaku bencana (bug, data rosak/hilang, server musnah).
**Untuk:** pemilik sistem / superadmin sahaja. Bukan untuk admin masjid (tenant).

> Backup dan pulih adalah tanggungjawab **PLATFORM (superadmin)**, bukan tenant. Admin
> masjid tidak mempunyai akses ke backup — ini melindungi data semua tenant di satu tempat.

---

## 1. Strategi backup BERLAPIS (kenapa selamat)

Data Diwan disimpan berlapis supaya kegagalan satu lapisan tidak menghilangkan segalanya:

| Lapisan | Apa | Lokasi | Kekerapan | Sulit? |
|---|---|---|---|---|
| **Storan utama** | Fail/dokumen (blob) | COS `spdm-1455289506` (ap-singapore) | Masa nyata | Private |
| **Backup objek** | Salinan berversi | COS `spdm-backup-1455289506` (ap-jakarta) | Versioning + SSE | Ya (SSE-COS) |
| **Dump pangkalan data** | PostgreSQL + `.env` (zip) | `cos_backup` (ap-jakarta) | Harian **02:30** | AES-256 (`BACKUP_ARCHIVE_PASSWORD`) |
| **Mirror Google Drive** | Rekod + dump boleh-browse | Akaun Google **pemilik** | Setiap jam (minit 20) | Ikut akaun Google |
| **Pemantauan** | Alert jika backup gagal/lama | E-mel superadmin | Harian **08:30** | — |

**Nota mirror Google Drive:** ini backup **keseluruhan sistem** untuk pemilik/superadmin —
*bukan* per-tenant untuk admin masjid. Ia menyalin semua rekod (plaintext, kelulusan PDPA
pemilik 20 Jul) + salinan dump DB ke `SPDM/Backup/…` dalam Google Drive pemilik supaya data
tidak tertumpu di satu server sahaja. OAuth disambung HANYA di panel superadmin
(`/admin` → Tetapan Platform → Sambung Google Drive). Panel tenant tiada UI Drive langsung.

**RPO (Recovery Point Objective):** ≤ 1 jam untuk rekod (mirror Drive) / ≤ 24 jam untuk dump DB penuh.
**RTO (Recovery Time Objective):** ~30–60 minit (restore DB + sahkan).

---

## 2. Prasyarat pulih

- Akses SSH: `ssh ubuntu@43.156.242.188` → `cd /opt/diwan`.
- `BACKUP_ARCHIVE_PASSWORD` (dari pengurus kata laluan) untuk nyahzip arkib backup.
- Kredensial COS dalam `.env` (`COS_SECRET_ID`/`COS_SECRET_KEY`) — untuk tarik backup.
- Docker + `postgres:16-alpine` (untuk restore drill terpencil).

---

## 3. Prosedur pulih pangkalan data

### 3.1 Senaraikan & tarik backup terkini
```bash
cd /opt/diwan
docker compose exec -T app php artisan backup:list          # lihat backup + tarikh + saiz
# Tarik zip terkini dari cos_backup ke cakera (guna tinker/COS CLI atau konsol COS).
```

### 3.2 Uji dalam bekas terpencil DAHULU (jangan terus ke DB hidup)
```bash
# Skrip drill: nyahzip → postgres:16-alpine sementara → restore → kira baris.
bash scripts/restore-drill.sh /path/ke/backup-diwan.zip
# Jangka: "LULUS restore drill" + kiraan mosques/records/users munasabah.
```
Drill ini **terbukti berjaya 19 Jul 2026** (32 CREATE TABLE + 32 COPY, jadual utama hadir).

### 3.3 Pulih ke DB produksi (HANYA jika perlu, selepas drill lulus)
```bash
# 1. Henti trafik: docker compose stop nginx worker scheduler
# 2. Nyahzip arkib (perlu BACKUP_ARCHIVE_PASSWORD):
unzip -P "$BACKUP_ARCHIVE_PASSWORD" backup-diwan.zip -d /tmp/restore
# 3. Cari dump: /tmp/restore/db-dumps/postgresql-diwan.sql
# 4. Pulih ke container db:
docker compose exec -T db psql -U diwan -d diwan < /tmp/restore/db-dumps/postgresql-diwan.sql
# 5. config:cache + hidupkan semula:
docker compose exec -T app php artisan config:cache
docker compose up -d nginx worker scheduler
```

---

## 4. Prosedur pulih fail/dokumen (blob)

- **Sumber utama:** COS `spdm-1455289506`. Jika objek terpadam tak sengaja, gunakan
  **versioning** bucket backup `spdm-backup-1455289506` (ap-jakarta) untuk pulih versi lama.
- **Alternatif boleh-browse:** Google Drive pemilik `SPDM/Backup/{masjid}/{klasifikasi}/{fail}/…`
  — muat turun terus dari drive.google.com bila COS tidak dapat diakses.
- Selepas pulih blob, jalankan `php artisan diwan:reconcile-storage` untuk selaraskan kiraan storan.

---

## 5. Verifikasi selepas pulih (WAJIB)
```bash
curl -s -o /dev/null -w '%{http_code}' https://bakwim.my/up      # 200
docker compose exec -T app php artisan diwan:smoke               # 9/9
docker compose exec -T app php artisan backup:list               # backup wujud
docker compose exec -T app php artisan tinker --execute="dump(['mosques'=>App\Models\Mosque::withoutGlobalScopes()->count(),'records'=>App\Models\Record::withoutGlobalScopes()->count()]);"
```
Sahkan isolasi tenant kekal utuh (rekod tenant A tidak bocor ke tenant B) selepas restore.

---

## 6. Jadual automatik (rujukan)
- `02:30` — `backup:run` (dump DB + `.env` → `cos_backup`, tersulit AES-256).
- `08:30` — `backup:monitor` (alert e-mel superadmin jika backup gagal/terlalu lama).
- Setiap jam minit `20` — `diwan:drive-reconcile` (mirror rekod + salinan dump ke Google Drive).

## 7. Nota / hadangan
- Gmail percuma 15GB — pantau kuota Drive; naik taraf Google One bila tenant membesar.
- OAuth consent mesti **PUBLISHED (Production)** — mod Testing = refresh token mati 7 hari.
- Ambang `monitor_backups` (umur maksimum, storan) dalam `config/backup.php` → `cos_backup`.
- Jangan restore terus ke DB hidup tanpa `restore-drill.sh` lulus dahulu.

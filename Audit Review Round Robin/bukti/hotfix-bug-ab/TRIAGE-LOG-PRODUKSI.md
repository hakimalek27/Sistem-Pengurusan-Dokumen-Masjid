# TRIAGE LOG RALAT PRODUKSI — 5 Ogos 2026

Dijalankan semasa menyiasat BUG-A, atas arahan tetap "semak di server dan log".
`storage/logs/laravel.log` = 901,391 bait · **62 baris ERROR/CRITICAL** sejak sistem live.
Setiap kelas dikelaskan dan ditarikhkan — tiada satu pun dibiar sebagai "mungkin tidak penting".

| # | Kelas ralat | Bil | Tarikh | Keputusan |
|---:|---|---:|---|---|
| 1 | `Writing to directory /var/www/.config/psysh is not allowed` | 16 | 22 Jul, 1 & 5 Ogos | **BUKAN aplikasi** — kesan sampingan `artisan tinker` yang SAYA jalankan semasa audit/diagnostik. Pelajaran: guna `php -r`/command khusus pada produksi, bukan tinker |
| 2 | `PHP Parse error … T_NS_SEPARATOR` / `unexpected '='` | 5 | 1 Ogos | **BUKAN aplikasi** — satu-baris tinker saya yang tersalah petik |
| 3 | `The "-e" option does not exist` · `Command "schedule:clear-mutex" is not defined` | 2 | Julai | **BUKAN aplikasi** — percubaan command saya semasa insiden mutex |
| 4 | `[backup] Backup failed` (dump + ZipArchive + Permission denied + scheduled cmd) | 10 | **18 Jul sahaja** | Sejarah. **Disahkan sihat hari ini** (lihat di bawah) |
| 5 | `duplicate key … users_phone_wa_unique` (+ SQLSTATE 23505) | 12 | **20 Jul sahaja** | Sudah dibaiki — validasi telefon kini diuji (`AdminUserPhoneValidationTest`) |
| 6 | `invalid input syntax for type uuid: "1"` (+ SQLSTATE 22P02) | 6 | **20 Jul sahaja** | Sejarah (tempoh probe audit / fixture jenis-salah yang sudah direkod) |
| 7 | `Filament\Forms\Components\Select::modifyQueryUsing does not exist` | 3 | **19 Jul sahaja** | Sudah dibaiki pada gate go-live (borang Klasifikasi) |
| 8 | `rename(storage/framework/views/…)` | 2 | Julai | Perlumbaan cache view Blade, sembuh sendiri; tiada kesan pengguna direkod |
| 9 | `Unable to locate file in Vite manifest: resources/js/help.js` | 1 | 22 Jul | Insiden "UI pecah" (nginx tidak dibina semula) — sudah dibaiki & direkod |
| 10 | **`Object of class App\Enums\RecordDirection could not be converted to string`** | 3 | 22 Jul | 🔴 **SATU-SATUNYA yang MASIH HIDUP dalam kod** → dibaiki hari ini sebagai **BUG-B** |

**Kesimpulan:** daripada 62 baris, **23 ialah bunyi diagnostik saya sendiri**, 36 ialah insiden
18–22 Julai yang sudah ditutup, dan **3 menunjuk satu pepijat yang masih hidup** — kini
dibaiki dengan reproduksi merah→hijau (`LAPORAN-BUG-B.md`).

## Pengesahan POSITIF (ketiadaan ralat bukan bukti kesihatan)

Kegagalan backup 18 Julai boleh juga bermakna backup berhenti berjalan sama sekali. Diperiksa:

```
$ php artisan backup:list
+-------+------------+-----------+---------+--------------+-------------------------+--------------+
| Name  | Disk       | Reachable | Healthy | # of backups | Newest backup           | Used storage |
+-------+------------+-----------+---------+--------------+-------------------------+--------------+
| Diwan | cos_backup | ✅        | ✅      |           23 | 0.17 (4 jam yang lepas) |    428.22 MB |
+-------+------------+-----------+---------+--------------+-------------------------+--------------+
```

Backup pergi ke **`cos_backup`** (objek storan Tencent COS) — itulah sebab `storage/app` tiada
fail `.zip`; ia BUKAN tanda backup hilang. Jadual disahkan hidup: `backup:run` 02:30 harian,
`backup:monitor` 08:30 harian.

⚠️ `backup:monitor` **sengaja tidak** dijalankan secara manual: ia boleh menghantar notifikasi
e-mel, dan menghantar mesej bagi pihak pemilik memerlukan kebenaran eksplisit. `backup:list`
memberi jawapan yang sama tanpa kesan sampingan.

## Kesihatan lain (hari ini)

```
failed_jobs           → 0  (php artisan queue:failed → "No failed jobs found.")
container             → 8/8 running (app clamav db meilisearch nginx redis scheduler worker)
git pelayan           → aaf381a  (= Deploy 6)
```

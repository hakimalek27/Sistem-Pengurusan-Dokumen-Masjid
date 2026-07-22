<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Jadual Tugasan (§17.24) — 8 tugasan operasi + pangkas log bulanan (§15.5)
|--------------------------------------------------------------------------
*/

/*
 * ⚠️ MUTEX JADUAL — SENTIASA beri tempoh luput eksplisit.
 *
 * `withoutOverlapping()` TANPA argumen = lalai 1440 minit (24 JAM). Jika satu
 * larian terbunuh sebelum melepaskan kunci (cth container di-recreate semasa
 * deploy di tengah larian), tugasan itu dilangkau SENYAP sehingga 24 jam.
 * Ini punca sebenar insiden intake e-mel 19-20 Jul: kunci fetch-mail tersangkut
 * (baki TTL 14 jam) → sifar e-mel diterima, tanpa satu pun ralat dilog.
 * Beri nilai yang hampir dengan kadar larian sebenar tugasan.
 */

// 1. Ingest e-mel pengimbas — setiap minit (§11.3). withoutOverlapping(2): larian
//    normal <1 min; FetchMailJob sendiri dipagar expireAfter(600). Tetingkap
//    kecil = tempoh gagal-senyap maksimum juga kecil jika kunci tersangkut.
Schedule::command('diwan:fetch-mail')->everyMinute()->withoutOverlapping(2);

// 2. Reconcile storan — 03:00 (§5.14).
Schedule::command('diwan:reconcile-storage')->dailyAt('03:00');

// 3. Sandaran pangkalan data + .env — 02:30 (§4.6).
Schedule::command('backup:run')->dailyAt('02:30');

// 3b. Pemantauan kesihatan backup — 08:30 (§4.6). Alert e-mel superadmin jika
//     backup terkini gagal/terlalu lama/melebihi had storan (monitor_backups → cos_backup).
Schedule::command('backup:monitor')->dailyAt('08:30');

// 4. Notis retensi t90/t30/t7 — 07:00 (§16.3).
Schedule::command('diwan:run-retention-notices')->dailyAt('07:00');

// 5. Pelupusan automatik — 07:30 (§16.3).
Schedule::command('diwan:run-retention-execute')->dailyAt('07:30');

// 6. Notis & luput add-on storan — 06:00 (§5.14).
Schedule::command('diwan:expire-addons')->dailyAt('06:00');

// 7. Peringatan minit — 08:00 (§9.C.5).
Schedule::command('diwan:send-minit-reminders')->dailyAt('08:00');

// 7b. Digest bantuan opt-in; tidak memasukkan peringatan minit sedia ada.
Schedule::command('diwan:send-guidance-digests')->dailyAt('08:15');

// 8. Ping gateway WhatsApp — setiap 5 minit (§11.1).
Schedule::command('diwan:ping-gateway')->everyFiveMinutes();

// 9. Pemantauan sesi WhatsApp per-masjid + platform & kesihatan IMAP — setiap 10 minit (§11.1).
//     withoutOverlapping(10): padan kadar larian. Tugasan ini juga pembawa alert
//     "intake e-mel tersekat" — jika mutexnya sendiri tersangkut 24 jam, pengesan
//     kegagalan turut mati senyap. Tempoh luput pendek = wajib.
Schedule::command('diwan:check-wa-sessions')->everyTenMinutes()->withoutOverlapping(10);

// 10. Reconcile mirror Google Drive + muat naik DB dump — setiap jam, minit 20 (§4.6′).
//     withoutOverlapping(55): reconcile boleh panjang (banyak fail) tetapi mesti
//     lepas sebelum larian jam berikutnya.
Schedule::command('diwan:drive-reconcile')->hourlyAt(20)->withoutOverlapping(55);

// Pangkas log > 24 bulan — bulanan (§15.5).
Schedule::command('diwan:prune-logs')->monthlyOn(1, '04:00');

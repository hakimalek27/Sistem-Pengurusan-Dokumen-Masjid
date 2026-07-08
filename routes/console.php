<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Jadual Tugasan (§17.24) — 8 tugasan operasi + pangkas log bulanan (§15.5)
|--------------------------------------------------------------------------
*/

// 1. Ingest e-mel pengimbas — setiap minit (§11.3).
Schedule::command('diwan:fetch-mail')->everyMinute()->withoutOverlapping();

// 2. Reconcile storan — 03:00 (§5.14).
Schedule::command('diwan:reconcile-storage')->dailyAt('03:00');

// 3. Sandaran pangkalan data + .env — 02:30 (§4.6).
Schedule::command('backup:run')->dailyAt('02:30');

// 4. Notis retensi t90/t30/t7 — 07:00 (§16.3).
Schedule::command('diwan:run-retention-notices')->dailyAt('07:00');

// 5. Pelupusan automatik — 07:30 (§16.3).
Schedule::command('diwan:run-retention-execute')->dailyAt('07:30');

// 6. Notis & luput add-on storan — 06:00 (§5.14).
Schedule::command('diwan:expire-addons')->dailyAt('06:00');

// 7. Peringatan minit — 08:00 (§9.C.5).
Schedule::command('diwan:send-minit-reminders')->dailyAt('08:00');

// 8. Ping gateway WhatsApp — setiap 5 minit (§11.1).
Schedule::command('diwan:ping-gateway')->everyFiveMinutes();

// Pangkas log > 24 bulan — bulanan (§15.5).
Schedule::command('diwan:prune-logs')->monthlyOn(1, '04:00');

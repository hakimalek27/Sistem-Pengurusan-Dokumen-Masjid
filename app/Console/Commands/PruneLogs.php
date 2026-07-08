<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// §15.5 — Pangkas log > 24 bulan (activity_log, sensitive_access_logs, notification_logs).
// Log ≠ rekod; snapshot pelupusan TIDAK dipangkas.
class PruneLogs extends Command
{
    protected $signature = 'diwan:prune-logs';

    protected $description = 'Padam log lebih 24 bulan (aktiviti, akses sulit, notifikasi)';

    public function handle(): int
    {
        $cutoff = now()->subMonths(24);
        $total = 0;

        foreach (['activity_log', 'sensitive_access_logs', 'notification_logs'] as $table) {
            $total += DB::table($table)->where('created_at', '<', $cutoff)->delete();
        }

        $this->info("{$total} baris log dipangkas (> 24 bulan).");

        return self::SUCCESS;
    }
}

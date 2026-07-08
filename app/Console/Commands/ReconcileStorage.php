<?php

namespace App\Console\Commands;

use App\Jobs\ReconcileStorageJob;
use Illuminate\Console\Command;

// §5.14 — Selaraskan kaunter storan (dijadualkan 03:00 di Fasa 8).
class ReconcileStorage extends Command
{
    protected $signature = 'diwan:reconcile-storage';

    protected $description = 'Selaraskan storage_used_bytes setiap masjid dengan Σ media sebenar';

    public function handle(): int
    {
        ReconcileStorageJob::dispatchSync();
        $this->info('Selarasan storan selesai.');

        return self::SUCCESS;
    }
}

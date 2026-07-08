<?php

namespace App\Console\Commands;

use App\Services\BillingService;
use Illuminate\Console\Command;

// §5.14 — Notis & luput add-on storan (dijadualkan 06:00 di Fasa 8).
class ExpireAddons extends Command
{
    protected $signature = 'diwan:expire-addons';

    protected $description = 'Hantar notis T-30/T-7 dan luputkan add-on storan yang cukup tempoh';

    public function handle(BillingService $billing): int
    {
        $result = $billing->processExpiringAddons();

        $this->info("Add-on luput: {$result['expired']}, notis dihantar: {$result['notified']}.");

        return self::SUCCESS;
    }
}

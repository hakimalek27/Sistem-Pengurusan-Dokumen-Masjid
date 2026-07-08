<?php

namespace App\Jobs;

use App\Models\Mosque;
use App\Services\QuotaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// §5.14 — Selaraskan kaunter storan vs Σ media sebenar (03:00 harian di Fasa 8).
class ReconcileStorageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(QuotaService $quota): void
    {
        Mosque::query()->chunkById(50, function ($mosques) use ($quota) {
            foreach ($mosques as $mosque) {
                $quota->reconcile($mosque);
            }
        });
    }
}

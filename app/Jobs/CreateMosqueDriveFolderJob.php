<?php

namespace App\Jobs;

use App\Models\Mosque;
use App\Services\GoogleDrive\DriveSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * §4.6′ — Cipta folder masjid (SPDM/Backup/{slug}) bila masjid diluluskan.
 * Senyap bila mirror dimatikan; reconcile setiap jam menjadi jaring keselamatan.
 */
class CreateMosqueDriveFolderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    public function __construct(public int $mosqueId) {}

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(DriveSyncService $sync): void
    {
        if (! $sync->enabled()) {
            return;
        }

        $mosque = Mosque::query()->find($this->mosqueId);
        if (! $mosque) {
            return;
        }

        $sync->mosqueFolderId($mosque);
    }
}

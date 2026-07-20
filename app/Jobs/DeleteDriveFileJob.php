<?php

namespace App\Jobs;

use App\Services\GoogleDrive\DriveSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * §4.6′ — Padam salinan Drive apabila rekod dilupus (selaras sijil pelupusan
 * ANM/DDMS — dokumen benar-benar dilupus di semua lokasi). mosque_id untuk audit.
 */
class DeleteDriveFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    /** @param  array<int, string>  $driveFileIds */
    public function __construct(public int $mosqueId, public array $driveFileIds) {}

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(DriveSyncService $sync): void
    {
        if (! $sync->enabled() || empty($this->driveFileIds)) {
            return;
        }

        $sync->deleteFiles($this->driveFileIds);
    }
}

<?php

namespace App\Jobs;

use App\Models\Record;
use App\Services\GoogleDrive\DriveSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * §4.6′ — Mirror satu rekod ke Google Drive (queue backup). Payload membawa
 * record_id + mosque_id; refetch BERSKOP mosque_id (isolasi tenant — rekod tenant
 * lain tidak akan dijumpai walau id dipalsukan). Senyap bila mirror dimatikan.
 */
class SyncRecordToDriveJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 5;

    public function __construct(public int $recordId, public int $mosqueId) {}

    public function uniqueId(): string
    {
        return 'drive-sync-'.$this->recordId;
    }

    public function uniqueFor(): int
    {
        return 600;
    }

    public function backoff(): array
    {
        return [60, 300, 900, 1800];
    }

    public function handle(DriveSyncService $sync): void
    {
        if (! $sync->enabled()) {
            return;
        }

        $record = Record::query()->withoutGlobalScope('mosque')
            ->where('mosque_id', $this->mosqueId)
            ->find($this->recordId);

        if (! $record) {
            return;
        }

        $sync->syncRecord($record);
    }
}

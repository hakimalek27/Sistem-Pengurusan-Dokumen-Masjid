<?php

namespace App\Jobs;

use App\Models\Mosque;
use App\Models\Record;
use App\Models\User;
use App\Notifications\ExportReadyNotification;
use App\Services\ExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

// §16.4 — Bina Eksport ZIP (queue exports, timeout 1800).
class BuildExportZipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;

    public function __construct(public int $mosqueId, public array $recordIds, public ?int $userId = null, public string $label = 'eksport') {}

    public function handle(ExportService $export): void
    {
        $mosque = Mosque::query()->find($this->mosqueId);
        if (! $mosque) {
            return;
        }

        $records = Record::query()->withoutGlobalScope('mosque')
            ->where('mosque_id', $mosque->id)
            ->whereIn('id', $this->recordIds)
            ->get();

        $path = $export->build($mosque, $records, $this->label);

        if ($user = User::query()->find($this->userId)) {
            Notification::send([$user], new ExportReadyNotification($mosque, $path));
        }
    }
}

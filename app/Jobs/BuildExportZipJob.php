<?php

namespace App\Jobs;

use App\Models\Mosque;
use App\Models\Record;
use App\Models\StoredExport;
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

        $user = $this->userId ? User::query()->find($this->userId) : null;
        if ($this->userId !== null
            && (! $user || ! $user->is_active || ! $user->isMemberOf($mosque) || ! ($user->canIn($mosque, 'export.create') || $user->canIn($mosque, 'audit.view')))) {
            return;
        }

        $query = Record::query()->withoutGlobalScope('mosque')->whereIn('id', $this->recordIds);
        $records = $user
            ? $query->visibleTo($user, $mosque)->get()
            : $query->where('mosque_id', $mosque->id)->get();

        if ($records->isEmpty()) {
            return;
        }

        $path = $export->build($mosque, $records, $this->label);

        $storedExport = StoredExport::query()->create([
            'mosque_id' => $mosque->id,
            'requested_by' => $this->userId,
            'label' => $this->label,
            'path' => $path,
            'expires_at' => now()->addDays(14),
        ]);

        if ($user) {
            Notification::send([$user], new ExportReadyNotification($mosque, $storedExport));
        }
    }
}

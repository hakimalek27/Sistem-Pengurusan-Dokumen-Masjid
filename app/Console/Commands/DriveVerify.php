<?php

namespace App\Console\Commands;

use App\Enums\MosqueStatus;
use App\Enums\RecordStatus;
use App\Models\Mosque;
use App\Models\Record;
use App\Services\GoogleDrive\DriveSyncService;
use Illuminate\Console\Command;

/**
 * §4.6′ — Semak liputan mirror Google Drive per masjid: layak vs sudah-sync vs
 * tertunggak + sampel exists() 10. Ringkasan disimpan ke mosques.settings.
 */
class DriveVerify extends Command
{
    protected $signature = 'diwan:drive-verify {--mosque=}';

    protected $description = 'Semak liputan mirror Google Drive per masjid (§4.6′).';

    public function handle(DriveSyncService $sync): int
    {
        $mosques = Mosque::query()->where('status', MosqueStatus::Aktif)
            ->when($this->option('mosque'), fn ($q) => $q->whereKey($this->option('mosque')))
            ->get();

        $rows = [];
        foreach ($mosques as $mosque) {
            $base = fn () => Record::query()->withoutGlobalScope('mosque')
                ->where('mosque_id', $mosque->id)
                ->whereIn('status', [RecordStatus::Difailkan, RecordStatus::Diganti]);

            $eligible = $base()->count();
            $synced = $base()->whereNotNull('gdrive_file_id')->count();
            $pending = $eligible - $synced;

            $ok = 0;
            $checked = 0;
            if ($sync->enabled()) {
                foreach ($base()->whereNotNull('gdrive_file_id')->limit(10)->pluck('gdrive_file_id') as $id) {
                    $checked++;
                    if ($sync->driveExists((string) $id)) {
                        $ok++;
                    }
                }
            }

            $rows[] = [$mosque->slug, $eligible, $synced, $pending, $checked ? "{$ok}/{$checked}" : '—'];

            $mosque->forceFill(['settings' => array_merge($mosque->settings ?? [], [
                'gdrive_verify' => ['eligible' => $eligible, 'synced' => $synced, 'pending' => $pending, 'at' => now()->toIso8601String()],
            ])])->saveQuietly();
        }

        $this->table(['Masjid', 'Layak', 'Sudah sync', 'Tertunggak', 'Sampel exists'], $rows);

        return self::SUCCESS;
    }
}

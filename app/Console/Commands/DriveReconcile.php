<?php

namespace App\Console\Commands;

use App\Enums\MosqueStatus;
use App\Enums\RecordStatus;
use App\Jobs\CreateMosqueDriveFolderJob;
use App\Jobs\SyncRecordToDriveJob;
use App\Models\Mosque;
use App\Models\PlatformSetting;
use App\Models\Record;
use App\Models\User;
use App\Notifications\DriveBackupAlertNotification;
use App\Services\GoogleDrive\DriveConfig;
use App\Services\GoogleDrive\DriveSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * §4.6′ — Reconcile mirror Google Drive setiap jam: hantar sync untuk rekod
 * tercicir/berubah (bounded per larian; backlog susut setiap jam) + muat naik DB
 * dump terkini. Ralat maut (token dibatal/kuota penuh) → litar + alert superadmin.
 */
class DriveReconcile extends Command
{
    protected $signature = 'diwan:drive-reconcile {--mosque=} {--limit=200}';

    protected $description = 'Segerak semula rekod ke Google Drive + muat naik DB dump (§4.6′).';

    public function handle(DriveSyncService $sync): int
    {
        if (! $sync->enabled()) {
            $this->warn('Mirror Google Drive tidak aktif / belum disambung / litar terputus.');

            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));
        $mosques = Mosque::query()->where('status', MosqueStatus::Aktif)
            ->when($this->option('mosque'), fn ($q) => $q->whereKey($this->option('mosque')))
            ->get();

        $dispatched = 0;
        foreach ($mosques as $mosque) {
            if (blank($mosque->gdrive_folder_id)) {
                CreateMosqueDriveFolderJob::dispatch($mosque->id)->onQueue('backup');
            }

            Record::query()->withoutGlobalScope('mosque')
                ->where('mosque_id', $mosque->id)
                ->whereIn('status', [RecordStatus::Difailkan, RecordStatus::Diganti])
                ->where(fn ($q) => $q->whereNull('gdrive_file_id')
                    ->orWhereNull('gdrive_synced_at')
                    ->orWhereColumn('updated_at', '>', 'gdrive_synced_at'))
                ->orderBy('id')->limit($limit)
                ->pluck('id')
                ->each(function ($id) use ($mosque, &$dispatched) {
                    SyncRecordToDriveJob::dispatch((int) $id, $mosque->id)->onQueue('backup');
                    $dispatched++;
                });
        }

        $this->info("Dihantar {$dispatched} tugasan sync.");

        try {
            $sync->syncDatabaseDump(DriveConfig::keepDumps());
            PlatformSetting::put('gdrive_status', ['ok' => true, 'at' => now()->toIso8601String()]);
            Cache::forget('gdrive_circuit');
            Cache::forget('gdrive_alerted');
        } catch (\Throwable $e) {
            $this->handleFailure($e);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function handleFailure(\Throwable $e): void
    {
        $msg = $e->getMessage();
        $fatal = str_contains($msg, 'invalid_grant')
            || str_contains(strtolower($msg), 'storagequota')
            || str_contains(strtolower($msg), 'quota');

        PlatformSetting::put('gdrive_status', ['ok' => false, 'at' => now()->toIso8601String(), 'message' => mb_substr($msg, 0, 300)]);
        $this->error('Mirror Google Drive gagal: '.$msg);

        if (! $fatal) {
            return;
        }

        // Litar 6 jam supaya job berhenti hammer token yang dibatal / kuota penuh.
        Cache::put('gdrive_circuit', true, now()->addHours(6));

        // Alert superadmin (throttle 24 jam).
        if (Cache::add('gdrive_alerted', true, now()->addHours(24))) {
            User::query()->where('is_superadmin', true)->where('is_active', true)->get()
                ->each(fn (User $u) => $u->notify(new DriveBackupAlertNotification($msg)));
        }
    }
}

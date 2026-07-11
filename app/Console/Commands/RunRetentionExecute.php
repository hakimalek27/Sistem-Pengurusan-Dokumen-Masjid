<?php

namespace App\Console\Commands;

use App\Models\Mosque;
use App\Models\User;
use App\Notifications\AutoDisposalDoneNotification;
use App\Services\DisposalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

// §16.3 / Aliran L — Pelupusan automatik (07:30). Syarat penuh disemak per rekod di DisposalService.
class RunRetentionExecute extends Command
{
    protected $signature = 'diwan:run-retention-execute';

    protected $description = 'Laksana pelupusan automatik rekod yang cukup tempoh (syarat penuh §16.3)';

    public function handle(DisposalService $disposal): int
    {
        $totalRecords = 0;
        $failedMosques = 0;

        // Masjid digantung DIJEDA; hanya masjid aktif + auto_disposal_enabled.
        Mosque::query()
            ->where('status', 'aktif')
            ->where('auto_disposal_enabled', true)
            ->cursor()
            ->each(function (Mosque $mosque) use ($disposal, &$totalRecords, &$failedMosques) {
                try {
                    $batch = $disposal->executeAuto($mosque);
                } catch (\Throwable $e) {
                    $failedMosques++;
                    report($e);
                    $this->error("Pelupusan {$mosque->code} gagal; snapshot disimpan dan akan dicuba semula.");

                    return;
                }

                if ($batch) {
                    $count = $batch->items()->count();
                    $totalRecords += $count;
                    $this->notifyDone($mosque, $batch->items()->count(), (int) config('diwan.default_retention_years', 7));
                }
            });

        $this->info("{$totalRecords} rekod dilupuskan automatik.");

        if ($failedMosques > 0) {
            $this->warn("{$failedMosques} masjid gagal dan dijadualkan untuk retry.");
        }

        return self::SUCCESS;
    }

    protected function notifyDone(Mosque $mosque, int $count, int $years): void
    {
        $admins = $mosque->users()->get()->filter(fn (User $u) => $u->canIn($mosque, 'retention.manage') || $u->canIn($mosque, 'mosque.settings'));
        $superadmins = User::query()->where('is_superadmin', true)->where('is_active', true)->get();

        $recipients = $admins->merge($superadmins)->unique('id');

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new AutoDisposalDoneNotification($mosque, $count, $years));
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\SupportRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

// §15.5 — Log operasi 24 bulan, analitik bantuan 90 hari dan tiket 24 bulan.
// Log ≠ rekod; snapshot pelupusan TIDAK dipangkas.
class PruneLogs extends Command
{
    protected $signature = 'diwan:prune-logs';

    protected $description = 'Padam log lebih 24 bulan (aktiviti, akses sulit, notifikasi)';

    public function handle(): int
    {
        $cutoff = now()->subMonths(24);
        $total = 0;

        foreach (['activity_log', 'sensitive_access_logs', 'notification_logs'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $total += DB::table($table)->where('created_at', '<', $cutoff)->delete();
        }

        if (Schema::hasTable('help_events')) {
            $total += DB::table('help_events')
                ->where('created_at', '<', now()->subDays((int) config('diwan.guidance.analytics_retention_days', 90)))
                ->delete();
        }

        if (Schema::hasTable('support_requests')) {
            SupportRequest::query()
                ->with('attachments')
                ->where('created_at', '<', now()->subMonths((int) config('diwan.guidance.support_retention_months', 24)))
                ->chunkById(100, function ($requests) use (&$total): void {
                    foreach ($requests as $request) {
                        foreach ($request->attachments as $attachment) {
                            Storage::disk($attachment->disk)->delete($attachment->path);
                        }

                        $request->delete();
                        $total++;
                    }
                });
        }

        $this->info("{$total} baris log dipangkas (> 24 bulan).");

        return self::SUCCESS;
    }
}

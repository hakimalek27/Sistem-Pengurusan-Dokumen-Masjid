<?php

namespace App\Console\Commands;

use App\Enums\RetentionAction;
use App\Models\Mosque;
use App\Models\Record;
use App\Models\User;
use App\Notifications\RetentionNoticeNotification;
use App\Services\RetentionEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

// §16.3 / Aliran L — Notis retensi t90/t30/t7 (07:00). Notis dihantar walau masjid digantung (§10.M).
class RunRetentionNotices extends Command
{
    protected $signature = 'diwan:run-retention-notices';

    protected $description = 'Segarkan tarikh cukup tempoh & hantar notis retensi t90/t30/t7';

    public function handle(RetentionEngine $engine): int
    {
        $today = now()->startOfDay();
        $sent = 0;

        Record::query()->withoutGlobalScope('mosque')
            ->whereIn('status', ['difailkan', 'diganti'])
            ->whereNotNull('retention_due_at')
            ->cursor()
            ->each(function (Record $record) use ($engine, $today, &$sent) {
                $mosque = $record->mosque;
                if (! $mosque) {
                    return;
                }

                $rule = $engine->effectiveRule($record);
                if (! $rule || $rule->action !== RetentionAction::AutoPadam) {
                    return;
                }

                $daysLeft = $today->diffInDays($record->retention_due_at->copy()->startOfDay(), false);
                $notified = $record->retention_notified ?? [];
                $changed = false;

                foreach ([90 => 't90', 30 => 't30', 7 => 't7'] as $threshold => $key) {
                    if ($daysLeft <= $threshold && empty($notified[$key])) {
                        $notified[$key] = now()->toIso8601String();
                        $changed = true;
                        $this->notify($mosque, $record, $threshold, (int) $rule->retain_years);
                        $sent++;
                    }
                }

                if ($changed) {
                    $record->updateQuietly(['retention_notified' => $notified]);
                }
            });

        $this->info("{$sent} notis retensi dihantar.");

        return self::SUCCESS;
    }

    protected function notify(Mosque $mosque, Record $record, int $threshold, int $years): void
    {
        $admins = $mosque->users()->get()->filter(fn (User $u) => $u->canIn($mosque, 'retention.manage') || $u->canIn($mosque, 'mosque.settings'));
        $superadmins = User::query()->where('is_superadmin', true)->where('is_active', true)->get();

        $recipients = $admins->merge($superadmins)->unique('id');

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new RetentionNoticeNotification($mosque, 1, $threshold, $years));
        }
    }
}

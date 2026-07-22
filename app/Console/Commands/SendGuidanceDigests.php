<?php

namespace App\Console\Commands;

use App\Models\GuidancePreference;
use App\Models\HelpEvent;
use App\Notifications\GuidanceDigestNotification;
use App\Services\UserTaskService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendGuidanceDigests extends Command
{
    protected $signature = 'diwan:send-guidance-digests';

    protected $description = 'Hantar digest bantuan opt-in maksimum sekali sehari tanpa mengulang minit';

    public function handle(UserTaskService $tasks): int
    {
        if (! config('diwan.guidance.nudges_enabled')) {
            return self::SUCCESS;
        }

        $sent = 0;
        GuidancePreference::query()
            ->where(fn ($query) => $query->where('digest_email', true)->orWhere('digest_whatsapp', true)->orWhere('digest_telegram', true))
            ->where('mode', '!=', 'dimatikan')
            ->whereNotNull('mosque_id')
            ->with(['user', 'mosque'])
            ->chunkById(100, function ($preferences) use ($tasks, &$sent): void {
                foreach ($preferences as $preference) {
                    $user = $preference->user;
                    $mosque = $preference->mosque;
                    if (! $user?->is_active || ! $mosque?->isActive() || ! $user->isMemberOf($mosque)) {
                        continue;
                    }
                    if ($preference->snoozed_until?->isFuture() || $this->insideQuietHours($preference)) {
                        continue;
                    }
                    if (HelpEvent::query()->where('user_id', $user->id)->where('mosque_id', $mosque->id)
                        ->where('event', 'digest_sent')->whereDate('created_at', today())->exists()) {
                        continue;
                    }

                    $summary = $tasks->for($user, 'app', $mosque)
                        ->reject(fn (array $task): bool => $task['type'] === 'minit')
                        ->whereIn('status', ['Lewat', 'Perlu tindakan', 'Cadangan'])
                        ->map(fn (array $task): string => '• '.$task['label'].': '.$task['count'])
                        ->values()->all();
                    if ($summary === []) {
                        continue;
                    }

                    $channels = array_keys(array_filter([
                        'mail' => $preference->digest_email,
                        'whatsapp' => $preference->digest_whatsapp,
                        'telegram' => $preference->digest_telegram,
                    ]));
                    $notification = new GuidanceDigestNotification($mosque, $summary, $channels);
                    if ($notification->via($user) === []) {
                        continue;
                    }
                    $user->notify($notification);
                    HelpEvent::query()->create([
                        'user_id' => $user->id, 'mosque_id' => $mosque->id, 'panel' => 'app',
                        'event' => 'digest_sent', 'result_count' => count($summary), 'metadata' => ['channels' => $channels],
                    ]);
                    $sent++;
                }
            });

        $this->info("{$sent} digest dihantar.");

        return self::SUCCESS;
    }

    protected function insideQuietHours(GuidancePreference $preference): bool
    {
        if (! $preference->quiet_hours_start || ! $preference->quiet_hours_end) {
            return false;
        }
        $now = now();
        $start = Carbon::parse($now->toDateString().' '.$preference->quiet_hours_start);
        $end = Carbon::parse($now->toDateString().' '.$preference->quiet_hours_end);
        if ($end->lessThanOrEqualTo($start)) {
            return $now->greaterThanOrEqualTo($start) || $now->lessThan($end);
        }

        return $now->betweenIncluded($start, $end);
    }
}

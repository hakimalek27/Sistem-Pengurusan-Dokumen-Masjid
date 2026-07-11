<?php

namespace App\Console\Commands;

use App\Enums\MinitStatus;
use App\Models\Minit;
use App\Notifications\MinitReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

// §9.C.5 / Aliran E — Peringatan minit (due esok / lewat). Dijadualkan 08:00 di Fasa 8.
class SendMinitReminders extends Command
{
    protected $signature = 'diwan:send-minit-reminders';

    protected $description = 'Hantar peringatan minit yang akan cukup tempoh esok atau telah lewat';

    public function handle(): int
    {
        $today = now()->startOfDay();
        $sent = 0;

        $minits = Minit::query()
            ->withoutGlobalScope('mosque')
            ->where('status', MinitStatus::Terbuka->value)
            ->whereNotNull('due_at')
            ->get();

        foreach ($minits as $minit) {
            $recipients = $minit->recipients()
                ->where('jenis', 'tindakan')
                ->where('status', '!=', 'selesai')
                ->whereHas('user', fn ($query) => $query
                    ->where('is_active', true)
                    ->whereHas('mosques', fn ($mosques) => $mosques->where('mosques.id', $minit->mosque_id)))
                ->with('user')
                ->get()
                ->pluck('user')
                ->filter(fn ($user) => $user?->can('view', $minit->record) ?? false);

            if ($recipients->isEmpty()) {
                continue;
            }

            $due = $minit->due_at->copy()->startOfDay();

            if ($due->isSameDay($today->copy()->addDay())) {
                Notification::send($recipients, new MinitReminderNotification($minit, false));
                $sent++;
            } elseif ($due->lt($today)) {
                $lateDays = (int) $due->diffInDays($today);
                Notification::send($recipients, new MinitReminderNotification($minit, true, $lateDays));
                $sent++;
            }
        }

        $this->info("{$sent} peringatan minit dihantar.");

        return self::SUCCESS;
    }
}

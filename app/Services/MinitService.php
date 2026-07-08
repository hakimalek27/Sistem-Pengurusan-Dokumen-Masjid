<?php

namespace App\Services;

use App\Enums\MinitPriority;
use App\Enums\MinitStatus;
use App\Models\Minit;
use App\Models\MinitRecipient;
use App\Models\Record;
use App\Models\User;
use App\Notifications\MinitRoutedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * §9.C.5 — Minit / routing + SLA. Dilindungi MinitTest.
 */
class MinitService
{
    /** Cipta minit + recipients + notifikasi (§14 MinitRouted). */
    public function create(Record $record, User $from, array $actionUserIds, array $ccUserIds, string $body, MinitPriority $priority, ?Minit $parent = null): Minit
    {
        return DB::transaction(function () use ($record, $from, $actionUserIds, $ccUserIds, $body, $priority, $parent) {
            $minit = Minit::query()->create([
                'mosque_id' => $record->mosque_id,
                'record_id' => $record->id,
                'from_user_id' => $from->id,
                'body' => $body,
                'priority' => $priority,
                'due_at' => now()->addDays($priority->slaDays())->toDateString(),
                'status' => MinitStatus::Terbuka,
                'parent_id' => $parent?->id,
            ]);

            foreach ($actionUserIds as $uid) {
                MinitRecipient::query()->create(['minit_id' => $minit->id, 'user_id' => $uid, 'jenis' => 'tindakan', 'status' => 'belum']);
            }
            foreach ($ccUserIds as $uid) {
                MinitRecipient::query()->create(['minit_id' => $minit->id, 'user_id' => $uid, 'jenis' => 'makluman', 'status' => 'belum']);
            }

            Notification::send(User::query()->whereIn('id', $actionUserIds)->get(), new MinitRoutedNotification($minit, 'tindakan'));
            Notification::send(User::query()->whereIn('id', $ccUserIds)->get(), new MinitRoutedNotification($minit, 'makluman'));

            return $minit;
        });
    }

    /** Balas & Edarkan — minit anak dalam bebenang sama. */
    public function replyAndRoute(Minit $parent, User $from, array $actionUserIds, array $ccUserIds, string $body, MinitPriority $priority): Minit
    {
        return $this->create($parent->record, $from, $actionUserIds, $ccUserIds, $body, $priority, $parent);
    }

    /** Tanda Selesai oleh penerima tindakan; semua selesai → minit selesai + notifikasi pengirim. */
    public function markDone(Minit $minit, User $user): void
    {
        DB::transaction(function () use ($minit, $user) {
            $minit->recipients()
                ->where('user_id', $user->id)
                ->where('jenis', 'tindakan')
                ->update(['status' => 'selesai', 'read_at' => now()]);

            $pending = $minit->recipients()->where('jenis', 'tindakan')->where('status', '!=', 'selesai')->count();

            if ($pending === 0) {
                $minit->update([
                    'status' => MinitStatus::Selesai,
                    'completed_at' => now(),
                    'completed_by' => $user->id,
                ]);

                Log::info("[Minit] #{$minit->id} selesai — maklum pengirim {$minit->from_user_id}.");
            }
        });
    }

    /** Tandakan minit dibaca oleh penerima. */
    public function markRead(Minit $minit, User $user): void
    {
        $minit->recipients()
            ->where('user_id', $user->id)
            ->where('status', 'belum')
            ->update(['status' => 'dibaca', 'read_at' => now()]);
    }
}

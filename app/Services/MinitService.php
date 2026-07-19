<?php

namespace App\Services;

use App\Enums\MinitPriority;
use App\Enums\MinitStatus;
use App\Models\Minit;
use App\Models\MinitRecipient;
use App\Models\Record;
use App\Models\User;
use App\Notifications\MinitCompletedNotification;
use App\Notifications\MinitRoutedNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * §9.C.5 — Minit / routing + SLA. Dilindungi MinitTest.
 */
class MinitService
{
    /** Cipta minit + recipients + notifikasi (§14 MinitRouted). */
    public function create(Record $record, User $from, array $actionUserIds, array $ccUserIds, string $body, MinitPriority $priority, ?Minit $parent = null): Minit
    {
        if (! $from->is_active || ! $from->can('routeMinit', $record) || ! $from->can('view', $record)) {
            throw new AuthorizationException('Tiada kebenaran mengedarkan minit bagi rekod ini.');
        }

        if ($parent && ($parent->mosque_id !== $record->mosque_id || $parent->record_id !== $record->id)) {
            throw ValidationException::withMessages(['parent' => 'Bebenang minit tidak sepadan dengan rekod atau tenant.']);
        }

        $actionUserIds = collect($actionUserIds)->map(fn ($id) => (int) $id)->unique()->values()->all();
        $ccUserIds = collect($ccUserIds)->map(fn ($id) => (int) $id)->unique()->diff($actionUserIds)->values()->all();
        $recipientIds = collect($actionUserIds)->merge($ccUserIds)->unique()->values();

        if (empty($actionUserIds) || trim($body) === '') {
            throw ValidationException::withMessages(['recipients' => 'Sekurang-kurangnya seorang penerima tindakan dan catatan diperlukan.']);
        }

        $recipients = $record->mosque->users()
            ->where('users.is_active', true)
            ->whereIn('users.id', $recipientIds)
            ->get();

        if ($recipients->count() !== $recipientIds->count()
            || $recipients->contains(fn (User $user) => ! $user->can('view', $record))) {
            throw ValidationException::withMessages(['recipients' => 'Semua penerima mesti ahli aktif tenant dan dibenarkan melihat rekod.']);
        }

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
        if (! $user->can('complete', $minit)) {
            throw new AuthorizationException('Pengguna bukan penerima tindakan minit ini.');
        }

        $completed = DB::transaction(function () use ($minit, $user): bool {
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

                return true;
            }

            return false;
        });

        if ($completed) {
            $sender = $minit->fromUser;
            if ($sender?->is_active && $sender->isMemberOf($minit->mosque) && $sender->can('view', $minit->record)) {
                Notification::send($sender, new MinitCompletedNotification($minit->fresh()));
            }

            Log::info("[Minit] #{$minit->id} selesai — pengirim {$minit->from_user_id} dimaklumkan.");
        }
    }

    /** Tandakan minit dibaca oleh penerima. */
    public function markRead(Minit $minit, User $user): void
    {
        if (! $user->isMemberOf($minit->mosque)
            || ! $minit->recipients()->where('user_id', $user->id)->exists()) {
            throw new AuthorizationException('Pengguna bukan penerima minit ini.');
        }

        $minit->recipients()
            ->where('user_id', $user->id)
            ->where('status', 'belum')
            ->update(['status' => 'dibaca', 'read_at' => now()]);
    }
}

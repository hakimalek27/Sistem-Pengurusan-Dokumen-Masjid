<?php

namespace App\Services;

use App\Models\Delegation;
use App\Models\Minit;
use App\Models\MinitRecipient;
use App\Models\Mosque;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DelegationService
{
    public const CAPABILITIES = ['minit', 'approvals'];

    public function create(User $actor, Mosque $mosque, array $data): Delegation
    {
        if (! $actor->canIn($mosque, 'records.view')) {
            throw new AuthorizationException('Tiada kebenaran mengurus delegasi tenant ini.');
        }

        $principal = $this->member($mosque, (int) $data['principal_user_id']);
        $delegate = $this->member($mosque, (int) $data['delegate_user_id']);
        $capabilities = collect($data['capabilities'] ?? [])->intersect(self::CAPABILITIES)->values()->all();

        if ($capabilities === [] || $principal->id === $delegate->id || (! $actor->canIn($mosque, 'users.manage') && $principal->id !== $actor->id)) {
            throw ValidationException::withMessages(['delegation' => 'Principal, delegate dan sekurang-kurangnya satu tugas mesti sah.']);
        }
        foreach ($capabilities as $capability) {
            $permission = $capability === 'minit' ? 'minit.respond' : 'approvals.decide';
            if (! $principal->canIn($mosque, $permission)) {
                throw ValidationException::withMessages(['capabilities' => 'Principal tidak mempunyai kebenaran bagi tugas yang dipilih.']);
            }
        }

        $starts = now()->parse($data['starts_at']);
        $ends = now()->parse($data['ends_at']);
        if ($ends->lte($starts)) {
            throw ValidationException::withMessages(['ends_at' => 'Tarikh tamat mesti selepas tarikh mula.']);
        }

        $delegation = DB::transaction(fn () => Delegation::query()->create([
            'mosque_id' => $mosque->id,
            'principal_user_id' => $principal->id,
            'delegate_user_id' => $delegate->id,
            'capabilities' => $capabilities,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'is_active' => true,
            'reason' => $data['reason'] ?? null,
            'created_by' => $actor->id,
        ]));

        Notification::make()->title('Delegasi tugas baharu')
            ->body($principal->name.' mewakilkan '.collect($capabilities)->join(', ').' sehingga '.$ends->format('d/m/Y H:i').'.')
            ->info()->sendToDatabase($delegate);

        return $delegation;
    }

    public function revoke(Delegation $delegation, User $actor): void
    {
        if (! $actor->canIn($delegation->mosque, 'users.manage') && $delegation->principal_user_id !== $actor->id) {
            throw new AuthorizationException('Hanya principal atau pentadbir boleh membatalkan delegasi.');
        }
        $delegation->update(['is_active' => false]);
        if ($delegation->delegate?->is_active) {
            Notification::make()->title('Delegasi dibatalkan')
                ->body('Delegasi daripada '.($delegation->principal?->name ?? 'principal').' telah dibatalkan.')
                ->warning()->sendToDatabase($delegation->delegate);
        }
    }

    public function principalIdsFor(User $delegate, Mosque $mosque, string $capability): array
    {
        return Delegation::query()->forMosque($mosque)->active($capability)
            ->where('delegate_user_id', $delegate->id)->pluck('principal_user_id')->map(fn ($id) => (int) $id)->all();
    }

    public function canActFor(User $delegate, User $principal, Mosque $mosque, string $capability): bool
    {
        return $delegate->id === $principal->id
            || (in_array($principal->id, $this->principalIdsFor($delegate, $mosque, $capability), true));
    }

    public function recipientFor(Minit $minit, User $actor, string $capability = 'minit'): ?MinitRecipient
    {
        $ids = collect([$actor->id])->merge($this->principalIdsFor($actor, $minit->mosque, $capability))->unique();

        return $minit->recipients()->whereIn('user_id', $ids)->oldest('id')->first();
    }

    protected function member(Mosque $mosque, int $id): User
    {
        $user = $mosque->users()->where('users.id', $id)->where('users.is_active', true)->first();
        if (! $user) {
            throw ValidationException::withMessages(['user' => 'Pengguna mesti ahli aktif tenant ini.']);
        }

        return $user;
    }
}

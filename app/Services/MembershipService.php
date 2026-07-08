<?php

namespace App\Services;

use App\Models\Mosque;
use App\Models\User;
use RuntimeException;

/**
 * §9.C.11 / §6.4 — Ahli & peranan masjid. Sekatan §6.4 dikuatkuasakan.
 */
class MembershipService
{
    public function __construct(protected MagicLinkService $magic) {}

    /** Jemput ahli: wujud → attach; baharu → cipta + magic link. */
    public function invite(Mosque $mosque, string $email, string $name, string $role, ?string $phoneWa = null): User
    {
        $user = User::query()->firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->fill(['name' => $name, 'phone_wa' => $phoneWa, 'is_active' => true, 'password' => null])->save();
        }

        $mosque->users()->syncWithoutDetaching([$user->id => ['role' => $role, 'joined_at' => now()]]);

        $this->magic->sendTo($user->email);

        return $user;
    }

    /** Tukar peranan ahli — §6.4 sekatan. */
    public function changeRole(Mosque $mosque, User $target, string $newRole, User $actor): void
    {
        $this->guard($mosque, $target, $actor, $newRole);

        $mosque->users()->updateExistingPivot($target->id, ['role' => $newRole]);

        activity()->performedOn($target)->causedBy($actor)
            ->withProperties(['mosque_id' => $mosque->id, 'role' => $newRole, 'ip' => request()->ip()])
            ->log('tukar_peranan');
    }

    /** Keluarkan ahli (detach pivot sahaja; akaun global kekal §15.9). */
    public function remove(Mosque $mosque, User $target, User $actor): void
    {
        $this->guard($mosque, $target, $actor, null);

        $mosque->users()->detach($target->id);

        activity()->performedOn($target)->causedBy($actor)
            ->withProperties(['mosque_id' => $mosque->id, 'ip' => request()->ip()])
            ->log('keluar_ahli');
    }

    /** §6.4 — kuatkuasa sekatan. $newRole null = keluarkan. */
    protected function guard(Mosque $mosque, User $target, User $actor, ?string $newRole): void
    {
        // Tidak boleh sentuh akaun superadmin.
        if ($target->is_superadmin) {
            throw new RuntimeException('Tidak boleh menyentuh akaun superadmin.');
        }

        $currentRole = $target->roleIn($mosque);

        // Turunkan diri sendiri dari admin_masjid tidak dibenarkan.
        if ($actor->id === $target->id && $currentRole === 'admin_masjid' && $newRole !== 'admin_masjid') {
            throw new RuntimeException('Anda tidak boleh menurunkan peranan admin masjid anda sendiri.');
        }

        // Tidak boleh buang/turunkan admin_masjid terakhir.
        if ($currentRole === 'admin_masjid' && $newRole !== 'admin_masjid') {
            $adminCount = $mosque->users()->wherePivot('role', 'admin_masjid')->count();
            if ($adminCount <= 1) {
                throw new RuntimeException('Tidak boleh membuang atau menurunkan admin_masjid terakhir masjid.');
            }
        }
    }
}

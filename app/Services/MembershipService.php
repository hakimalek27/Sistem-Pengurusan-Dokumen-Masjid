<?php

namespace App\Services;

use App\Models\Mosque;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * §9.C.11 / §6.4 — Ahli & peranan masjid. Sekatan §6.4 dikuatkuasakan.
 */
class MembershipService
{
    public function __construct(protected MagicLinkService $magic) {}

    /** Jemput ahli: wujud → attach; baharu → cipta + magic link. */
    public function invite(Mosque $mosque, string $email, string $name, string $role, ?string $phoneWa = null, ?User $actor = null): User
    {
        if (! $actor?->canIn($mosque, 'users.manage')) {
            throw new AuthorizationException('Tiada kebenaran menjemput ahli untuk tenant ini.');
        }
        if (! in_array($role, Roles::all(), true)) {
            throw ValidationException::withMessages(['role' => 'Peranan tidak sah.']);
        }

        $originalPhone = $phoneWa;
        $phoneWa = app(WhatsAppRecipientResolver::class)->normalize($phoneWa);
        if (filled($originalPhone) && ! $phoneWa) {
            throw ValidationException::withMessages(['phone_wa' => 'Nombor WhatsApp tidak sah.']);
        }
        $user = User::query()->firstOrNew(['email' => $email]);

        if ($phoneWa && DB::table('mosque_user')
            ->where('mosque_id', $mosque->id)
            ->where('phone_wa', $phoneWa)
            ->when($user->exists, fn ($query) => $query->where('user_id', '!=', $user->id))
            ->exists()) {
            throw ValidationException::withMessages(['phone_wa' => 'Nombor WhatsApp telah digunakan oleh ahli lain dalam tenant ini.']);
        }

        DB::transaction(function () use ($user, $name, $phoneWa, $mosque, $role) {
            if (! $user->exists) {
                $user->fill(['name' => $name, 'phone_wa' => $phoneWa, 'is_active' => true, 'password' => null])->save();
            }

            $membership = [
                'role' => $role,
                'phone_wa' => $phoneWa,
                'notify_whatsapp' => true,
                'joined_at' => now(),
            ];
            $mosque->users()->syncWithoutDetaching([$user->id => $membership]);
            // syncWithoutDetaching tidak mengemas kini pivot jika ahli sudah wujud.
            $mosque->users()->updateExistingPivot($user->id, $membership);
        });

        $this->magic->sendTo($user->email);

        return $user;
    }

    /** Tetapan nombor/opt-in per keahlian; tidak mengubah tenant lain pengguna sama. */
    public function updateWhatsAppRouting(Mosque $mosque, User $target, ?string $phone, bool $enabled, User $actor): void
    {
        if (! $actor->canIn($mosque, 'users.manage') || ! $target->isMemberOf($mosque)) {
            throw new AuthorizationException('Tiada kebenaran mengubah tetapan notifikasi ahli ini.');
        }

        $normalized = app(WhatsAppRecipientResolver::class)->normalize($phone);
        if (filled($phone) && ! $normalized) {
            throw ValidationException::withMessages(['phone_wa' => 'Nombor WhatsApp tidak sah.']);
        }

        $duplicate = $normalized && DB::table('mosque_user')
            ->where('mosque_id', $mosque->id)
            ->where('phone_wa', $normalized)
            ->where('user_id', '!=', $target->id)
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['phone_wa' => 'Nombor WhatsApp telah digunakan oleh ahli lain dalam tenant ini.']);
        }

        $mosque->users()->updateExistingPivot($target->id, [
            'phone_wa' => $normalized,
            'notify_whatsapp' => $enabled,
        ]);

        activity()->performedOn($target)->causedBy($actor)
            ->withProperties(['mosque_id' => $mosque->id, 'notify_whatsapp' => $enabled, 'ip' => request()->ip()])
            ->log('tetapan_notifikasi_whatsapp');
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
        if (! $actor->canIn($mosque, 'users.manage') || ! $target->isMemberOf($mosque)) {
            throw new AuthorizationException('Tiada kebenaran mengurus ahli tenant ini.');
        }
        if ($newRole !== null && ! in_array($newRole, Roles::all(), true)) {
            throw ValidationException::withMessages(['role' => 'Peranan tidak sah.']);
        }

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

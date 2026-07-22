<?php

namespace App\Services;

use App\Models\Mosque;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * §9.C.11 / §6.4 — Ahli & peranan masjid. Sekatan §6.4 dikuatkuasakan.
 */
class MembershipService
{
    public function __construct(protected MagicLinkService $magic) {}

    /**
     * Jemput ahli: wujud → attach; baharu → cipta + pautan log masuk.
     * E-mel PILIHAN — ahli boleh guna telefon sahaja (admin selalunya tahu
     * nombor, bukan e-mel). Sekurang-kurangnya satu saluran diperlukan.
     */
    public function invite(Mosque $mosque, ?string $email, string $name, string $role, ?string $phoneWa = null, ?User $actor = null): User
    {
        if (! $actor?->canIn($mosque, 'users.manage')) {
            throw new AuthorizationException('Tiada kebenaran menjemput ahli untuk tenant ini.');
        }
        $role = Roles::canonical($role);
        if (! in_array($role, Roles::all(), true)) {
            throw ValidationException::withMessages(['role' => 'Peranan tidak sah.']);
        }

        $email = filled($email) ? mb_strtolower(trim($email)) : null;

        $originalPhone = $phoneWa;
        $phoneWa = app(WhatsAppRecipientResolver::class)->normalize($phoneWa);
        if (filled($originalPhone) && ! $phoneWa) {
            throw ValidationException::withMessages(['phone_wa' => 'Nombor WhatsApp tidak sah.']);
        }

        if (! $email && ! $phoneWa) {
            throw ValidationException::withMessages(['phone_wa' => 'Sila beri sekurang-kurangnya e-mel atau nombor telefon.']);
        }

        // Identiti: cari ikut e-mel dahulu, kemudian nombor telefon global —
        // orang sama boleh jadi ahli beberapa masjid dengan satu akaun global.
        $user = ($email ? User::query()->where('email', $email)->first() : null)
            ?? ($phoneWa ? User::query()->where('phone_wa', $phoneWa)->first() : null)
            ?? new User;

        if ($phoneWa && DB::table('mosque_user')
            ->where('mosque_id', $mosque->id)
            ->where('phone_wa', $phoneWa)
            ->when($user->exists, fn ($query) => $query->where('user_id', '!=', $user->id))
            ->exists()) {
            throw ValidationException::withMessages(['phone_wa' => 'Nombor WhatsApp telah digunakan oleh ahli lain dalam tenant ini.']);
        }

        DB::transaction(function () use ($user, $name, $email, $phoneWa, $mosque, $role) {
            if (! $user->exists) {
                $user->fill([
                    'name' => $name,
                    'email' => $email,
                    'phone_wa' => $phoneWa,
                    'is_active' => true,
                    'password' => null,
                ])->save();
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

        $this->magic->sendToUser($user);

        app(MosqueActivityLogger::class)->log(
            $mosque,
            'member_invited',
            ($actor?->name ?? 'Sistem').' menjemput '.$user->name.' sebagai '.Roles::label($role).'.',
            $actor,
            $user,
            metadata: ['target_user_id' => $user->id, 'target_name' => $user->name, 'role' => $role],
        );

        return $user;
    }

    /**
     * Set/set semula kata laluan ahli oleh admin (§6.4 users.manage). Guard
     * ringkas — bukan sekatan penuh guard() (semakan admin terakhir tidak
     * relevan untuk menetapkan kata laluan).
     */
    public function resetPassword(Mosque $mosque, User $target, string $password, User $actor): void
    {
        $this->guardManage($mosque, $target, $actor);

        $target->update(['password' => Hash::make($password)]);

        activity()->performedOn($target)->causedBy($actor)
            ->withProperties(['mosque_id' => $mosque->id, 'ip' => request()->ip()])
            ->log('set_semula_kata_laluan');

        app(MosqueActivityLogger::class)->log(
            $mosque,
            'member_password_reset',
            $actor->name.' menetapkan semula kata laluan '.$target->name.'.',
            $actor,
            $target,
            metadata: ['target_user_id' => $target->id, 'target_name' => $target->name],
        );
    }

    /** Hantar semula pautan log masuk (magic link) kepada ahli. */
    public function resendLoginLink(Mosque $mosque, User $target, User $actor): void
    {
        $this->guardManage($mosque, $target, $actor);

        $this->magic->sendToUser($target);

        activity()->performedOn($target)->causedBy($actor)
            ->withProperties(['mosque_id' => $mosque->id, 'ip' => request()->ip()])
            ->log('hantar_semula_pautan_log_masuk');

        app(MosqueActivityLogger::class)->log(
            $mosque,
            'member_login_link_resent',
            $actor->name.' menghantar semula pautan log masuk kepada '.$target->name.'.',
            $actor,
            $target,
            metadata: ['target_user_id' => $target->id, 'target_name' => $target->name],
        );
    }

    /** Guard ringkas untuk operasi kredensial ahli (bukan sekatan §6.4 penuh). */
    protected function guardManage(Mosque $mosque, User $target, User $actor): void
    {
        if (! $actor->canIn($mosque, 'users.manage') || ! $target->isMemberOf($mosque)) {
            throw new AuthorizationException('Tiada kebenaran mengurus ahli tenant ini.');
        }
        if ($target->is_superadmin) {
            throw new RuntimeException('Tidak boleh menyentuh akaun superadmin.');
        }
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

        app(MosqueActivityLogger::class)->log(
            $mosque,
            'member_whatsapp_settings_updated',
            $actor->name.' mengemas kini tetapan notifikasi WhatsApp '.$target->name.'.',
            $actor,
            $target,
            metadata: ['target_user_id' => $target->id, 'target_name' => $target->name, 'notify_whatsapp' => $enabled],
        );
    }

    /** Tukar peranan ahli — §6.4 sekatan. */
    public function changeRole(Mosque $mosque, User $target, string $newRole, User $actor): void
    {
        $newRole = Roles::canonical($newRole);
        $this->guard($mosque, $target, $actor, $newRole);

        $oldRole = $target->roleIn($mosque);

        $mosque->users()->updateExistingPivot($target->id, ['role' => $newRole]);

        activity()->performedOn($target)->causedBy($actor)
            ->withProperties(['mosque_id' => $mosque->id, 'role' => $newRole, 'ip' => request()->ip()])
            ->log('tukar_peranan');

        app(MosqueActivityLogger::class)->log(
            $mosque,
            'member_role_changed',
            $actor->name.' menukar peranan '.$target->name.' daripada '.Roles::label($oldRole ?? '').' kepada '.Roles::label($newRole).'.',
            $actor,
            $target,
            metadata: ['target_user_id' => $target->id, 'target_name' => $target->name, 'old_role' => $oldRole, 'new_role' => $newRole],
        );
    }

    /** Keluarkan ahli (detach pivot sahaja; akaun global kekal §15.9). */
    public function remove(Mosque $mosque, User $target, User $actor): void
    {
        $this->guard($mosque, $target, $actor, null);

        $oldRole = $target->roleIn($mosque);

        $mosque->users()->detach($target->id);

        activity()->performedOn($target)->causedBy($actor)
            ->withProperties(['mosque_id' => $mosque->id, 'ip' => request()->ip()])
            ->log('keluar_ahli');

        app(MosqueActivityLogger::class)->log(
            $mosque,
            'member_removed',
            $actor->name.' mengeluarkan '.$target->name.' daripada keahlian masjid.',
            $actor,
            $target,
            metadata: ['target_user_id' => $target->id, 'target_name' => $target->name, 'old_role' => $oldRole],
        );
    }

    /** §6.4 — kuatkuasa sekatan. $newRole null = keluarkan. */
    protected function guard(Mosque $mosque, User $target, User $actor, ?string $newRole): void
    {
        $newRole = $newRole !== null ? Roles::canonical($newRole) : null;
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

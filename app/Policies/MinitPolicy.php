<?php

namespace App\Policies;

use App\Models\Minit;
use App\Models\Mosque;
use App\Models\User;
use App\Services\DelegationService;
use Filament\Facades\Filament;

class MinitPolicy
{
    protected function tenant(): ?Mosque
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Mosque ? $tenant : null;
    }

    public function viewAny(User $user): bool
    {
        $mosque = $this->tenant();

        return $mosque ? $user->canIn($mosque, 'records.view') : false;
    }

    public function view(User $user, Minit $minit): bool
    {
        return $user->can('view', $minit->record)
            && ($minit->from_user_id === $user->id
                || app(DelegationService::class)->recipientFor($minit, $user) !== null);
    }

    public function create(User $user): bool
    {
        $mosque = $this->tenant();

        return $mosque ? $user->canIn($mosque, 'minit.create') : false;
    }

    public function respond(User $user, Minit $minit): bool
    {
        $recipient = app(DelegationService::class)->recipientFor($minit, $user);

        return $recipient !== null && $recipient->jenis === 'tindakan'
            && ($user->canIn($minit->mosque, 'minit.respond') || $recipient->user_id !== $user->id)
            && $user->can('view', $minit->record);
    }

    public function complete(User $user, Minit $minit): bool
    {
        return $this->respond($user, $minit)
            && app(DelegationService::class)->recipientFor($minit, $user)?->status !== 'selesai';
    }

    // §6.4.2 — penerima s.k. (makluman) yang ingin minit boleh "Balas & Edarkan",
    // bukan hanya penerima tindakan. Tanda Selesai kekal terhad kepada penerima tindakan
    // (lihat respond()/complete()). Pengirim asal juga boleh menyusuli.
    public function reply(User $user, Minit $minit): bool
    {
        return ($user->canIn($minit->mosque, 'minit.respond') || app(DelegationService::class)->recipientFor($minit, $user) !== null)
            && $user->can('view', $minit->record)
            && ($minit->from_user_id === $user->id
                || app(DelegationService::class)->recipientFor($minit, $user) !== null);
    }

    public function update(User $user, Minit $minit): bool
    {
        return $this->respond($user, $minit);
    }
}

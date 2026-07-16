<?php

namespace App\Policies;

use App\Models\Minit;
use App\Models\Mosque;
use App\Models\User;
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
                || $minit->recipients()->where('user_id', $user->id)->exists());
    }

    public function create(User $user): bool
    {
        $mosque = $this->tenant();

        return $mosque ? $user->canIn($mosque, 'minit.create') : false;
    }

    public function respond(User $user, Minit $minit): bool
    {
        return $user->canIn($minit->mosque, 'minit.respond')
            && $minit->recipients()->where('user_id', $user->id)->where('jenis', 'tindakan')->exists();
    }

    public function complete(User $user, Minit $minit): bool
    {
        return $this->respond($user, $minit)
            && $minit->recipients()->where('user_id', $user->id)->where('status', '!=', 'selesai')->exists();
    }

    public function reply(User $user, Minit $minit): bool
    {
        return $this->respond($user, $minit);
    }

    public function update(User $user, Minit $minit): bool
    {
        return $this->respond($user, $minit);
    }
}

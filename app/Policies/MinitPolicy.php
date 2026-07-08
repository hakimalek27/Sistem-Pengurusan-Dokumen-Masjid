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
        return $user->canIn($minit->mosque, 'records.view');
    }

    public function create(User $user): bool
    {
        $mosque = $this->tenant();

        return $mosque ? $user->canIn($mosque, 'minit.create') : false;
    }

    public function respond(User $user, Minit $minit): bool
    {
        return $user->canIn($minit->mosque, 'minit.respond');
    }

    public function update(User $user, Minit $minit): bool
    {
        return $user->canIn($minit->mosque, 'minit.respond');
    }
}

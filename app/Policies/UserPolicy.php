<?php

namespace App\Policies;

use App\Models\Mosque;
use App\Models\User;
use Filament\Facades\Filament;

class UserPolicy
{
    protected function tenant(): ?Mosque
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Mosque ? $tenant : null;
    }

    public function viewAny(User $user): bool
    {
        $mosque = $this->tenant();

        return $mosque ? $user->canIn($mosque, 'users.manage') : false;
    }

    public function manage(User $user): bool
    {
        $mosque = $this->tenant();

        return $mosque ? $user->canIn($mosque, 'users.manage') : false;
    }

    public function update(User $user, User $target): bool
    {
        $mosque = $this->tenant();

        return $mosque ? $user->canIn($mosque, 'users.manage') : false;
    }

    public function view(User $user, User $target): bool
    {
        return $user->is_superadmin || $this->update($user, $target);
    }

    public function create(User $user): bool
    {
        return $user->is_superadmin;
    }

    public function delete(User $user, User $target): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

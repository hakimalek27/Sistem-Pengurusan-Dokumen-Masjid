<?php

namespace App\Policies;

use App\Models\FileAccessGrant;
use App\Models\Mosque;
use App\Models\User;
use Filament\Facades\Filament;

class FileAccessGrantPolicy
{
    protected function tenant(): ?Mosque
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Mosque ? $tenant : null;
    }

    public function viewAny(User $user): bool
    {
        $mosque = $this->tenant();

        return $mosque ? $user->canIn($mosque, 'files.grant_access') : false;
    }

    public function view(User $user, FileAccessGrant $grant): bool
    {
        return $user->canIn($grant->registryFile->mosque, 'files.grant_access');
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, FileAccessGrant $grant): bool
    {
        return $this->view($user, $grant);
    }
}

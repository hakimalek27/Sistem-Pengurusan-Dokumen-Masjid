<?php

namespace App\Policies;

use App\Models\Mosque;
use App\Models\RegistryFile;
use App\Models\User;
use Filament\Facades\Filament;

class RegistryFilePolicy
{
    protected function tenant(): ?Mosque
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Mosque ? $tenant : null;
    }

    public function viewAny(User $user): bool
    {
        $mosque = $this->tenant();

        return $mosque ? $user->canIn($mosque, 'files.view') : false;
    }

    public function view(User $user, RegistryFile $file): bool
    {
        return $user->canIn($file->mosque, 'files.view');
    }

    public function create(User $user): bool
    {
        $mosque = $this->tenant();

        return $mosque ? $user->canIn($mosque, 'files.open') : false;
    }

    public function update(User $user, RegistryFile $file): bool
    {
        return $user->canIn($file->mosque, 'files.open');
    }

    public function close(User $user, RegistryFile $file): bool
    {
        return $user->canIn($file->mosque, 'files.close');
    }

    public function grantAccess(User $user, RegistryFile $file): bool
    {
        return $user->canIn($file->mosque, 'files.grant_access');
    }

    public function delete(User $user, RegistryFile $file): bool
    {
        return $user->canIn($file->mosque, 'files.close');
    }
}

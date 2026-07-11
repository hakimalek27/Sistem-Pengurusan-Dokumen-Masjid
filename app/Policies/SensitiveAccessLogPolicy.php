<?php

namespace App\Policies;

use App\Models\Mosque;
use App\Models\SensitiveAccessLog;
use App\Models\User;
use Filament\Facades\Filament;

class SensitiveAccessLogPolicy
{
    protected function tenant(): ?Mosque
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Mosque ? $tenant : null;
    }

    public function viewAny(User $user): bool
    {
        $mosque = $this->tenant();

        return $mosque ? $user->canIn($mosque, 'audit.view') : false;
    }

    public function view(User $user, SensitiveAccessLog $log): bool
    {
        return $user->canIn($log->mosque, 'audit.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, SensitiveAccessLog $log): bool
    {
        return false;
    }

    public function delete(User $user, SensitiveAccessLog $log): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

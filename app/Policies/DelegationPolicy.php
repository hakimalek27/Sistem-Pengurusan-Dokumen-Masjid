<?php

namespace App\Policies;

use App\Models\Delegation;
use App\Models\Mosque;
use App\Models\User;
use Filament\Facades\Filament;

class DelegationPolicy
{
    protected function tenant(): ?Mosque
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Mosque ? $tenant : null;
    }

    public function viewAny(User $user): bool
    {
        return (bool) ($this->tenant() && $user->canIn($this->tenant(), 'records.view'));
    }

    public function view(User $user, Delegation $delegation): bool
    {
        return $user->is_superadmin || $delegation->principal_user_id === $user->id
            || $delegation->delegate_user_id === $user->id || $user->canIn($delegation->mosque, 'users.manage');
    }

    public function create(User $user): bool
    {
        return (bool) ($this->tenant() && $user->canIn($this->tenant(), 'records.view'));
    }

    public function update(User $user, Delegation $delegation): bool
    {
        return $user->is_superadmin || $delegation->principal_user_id === $user->id
            || $user->canIn($delegation->mosque, 'users.manage');
    }

    public function delete(User $user, Delegation $delegation): bool
    {
        return $this->update($user, $delegation);
    }
}

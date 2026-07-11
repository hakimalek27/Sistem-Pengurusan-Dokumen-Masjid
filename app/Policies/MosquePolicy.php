<?php

namespace App\Policies;

use App\Models\Mosque;
use App\Models\User;

class MosquePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_superadmin;
    }

    public function view(User $user, Mosque $mosque): bool
    {
        return $user->isMemberOf($mosque);
    }

    public function update(User $user, Mosque $mosque): bool
    {
        return $user->canIn($mosque, 'mosque.settings');
    }

    public function viewUsage(User $user, Mosque $mosque): bool
    {
        return $user->canIn($mosque, 'usage.view');
    }

    public function create(User $user): bool
    {
        return $user->is_superadmin;
    }

    public function delete(User $user, Mosque $mosque): bool
    {
        return $user->is_superadmin;
    }

    public function restore(User $user, Mosque $mosque): bool
    {
        return $user->is_superadmin;
    }

    public function forceDelete(User $user, Mosque $mosque): bool
    {
        return $user->is_superadmin;
    }

    public function deleteAny(User $user): bool
    {
        return $user->is_superadmin;
    }

    public function restoreAny(User $user): bool
    {
        return $user->is_superadmin;
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->is_superadmin;
    }
}

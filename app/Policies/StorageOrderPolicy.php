<?php

namespace App\Policies;

use App\Models\StorageOrder;
use App\Models\User;

class StorageOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_superadmin;
    }

    public function view(User $user, StorageOrder $order): bool
    {
        return $user->is_superadmin;
    }

    public function create(User $user): bool
    {
        return $user->is_superadmin;
    }

    public function update(User $user, StorageOrder $order): bool
    {
        return $user->is_superadmin;
    }

    public function delete(User $user, StorageOrder $order): bool
    {
        return $user->is_superadmin;
    }

    public function deleteAny(User $user): bool
    {
        return $user->is_superadmin;
    }
}

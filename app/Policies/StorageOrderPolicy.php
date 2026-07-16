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
        return false;
    }

    public function update(User $user, StorageOrder $order): bool
    {
        return false;
    }

    public function delete(User $user, StorageOrder $order): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function download(User $user, StorageOrder $order): bool
    {
        if ($user->is_superadmin) {
            return true;
        }

        return $user->isMemberOf($order->mosque)
            && ($order->ordered_by === $user->id
                || $user->canIn($order->mosque, 'usage.view')
                || $user->canIn($order->mosque, 'storage.order'));
    }
}

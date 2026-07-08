<?php

namespace App\Policies;

use App\Models\Mosque;
use App\Models\User;

class MosquePolicy
{
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
}

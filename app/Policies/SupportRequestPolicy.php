<?php

namespace App\Policies;

use App\Models\SupportRequest;
use App\Models\User;

class SupportRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_superadmin || $user->mosques()->get()->contains(fn ($mosque) => $user->canIn($mosque, 'support.manage'));
    }

    public function view(User $user, SupportRequest $request): bool
    {
        if ($user->is_superadmin || $request->user_id === $user->id) {
            return true;
        }
        if (! $request->mosque) {
            return false;
        }

        return $user->canIn($request->mosque, 'support.manage');
    }

    public function update(User $user, SupportRequest $request): bool
    {
        if ($user->is_superadmin) {
            return true;
        }

        return $request->mosque && $user->canIn($request->mosque, 'support.manage');
    }

    public function create(User $user): bool
    {
        return config('diwan.guidance.support_enabled') && $user->is_active;
    }

    public function delete(User $user, SupportRequest $request): bool
    {
        return false;
    }
}

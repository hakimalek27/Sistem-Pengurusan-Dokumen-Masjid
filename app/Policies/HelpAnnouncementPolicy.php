<?php

namespace App\Policies;

use App\Models\HelpAnnouncement;
use App\Models\User;

class HelpAnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_superadmin;
    }

    public function view(User $user, HelpAnnouncement $announcement): bool
    {
        return $user->is_superadmin;
    }

    public function create(User $user): bool
    {
        return $user->is_superadmin;
    }

    public function update(User $user, HelpAnnouncement $announcement): bool
    {
        return $user->is_superadmin;
    }

    public function delete(User $user, HelpAnnouncement $announcement): bool
    {
        return $user->is_superadmin;
    }
}

<?php

namespace App\Policies;

use App\Models\Mosque;
use App\Models\MosqueActivityLog;
use App\Models\User;
use Filament\Facades\Filament;

class MosqueActivityLogPolicy
{
    protected function tenant(): ?Mosque
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Mosque ? $tenant : null;
    }

    public function viewAny(User $user): bool
    {
        $mosque = $this->tenant();

        return $mosque ? $user->canIn($mosque, 'activity.view') : false;
    }

    public function view(User $user, MosqueActivityLog $log): bool
    {
        return $user->canIn($log->mosque, 'activity.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, MosqueActivityLog $log): bool
    {
        return false;
    }

    public function delete(User $user, MosqueActivityLog $log): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

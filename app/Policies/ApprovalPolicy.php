<?php

namespace App\Policies;

use App\Models\Approval;
use App\Models\Mosque;
use App\Models\User;
use Filament\Facades\Filament;

class ApprovalPolicy
{
    protected function tenant(): ?Mosque
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Mosque ? $tenant : null;
    }

    public function viewAny(User $user): bool
    {
        $mosque = $this->tenant();

        return $mosque ? $user->canIn($mosque, 'records.view') : false;
    }

    public function view(User $user, Approval $approval): bool
    {
        return $user->canIn($approval->mosque, 'records.view');
    }

    public function create(User $user): bool
    {
        $mosque = $this->tenant();

        return $mosque ? $user->canIn($mosque, 'approvals.request') : false;
    }

    public function decide(User $user, Approval $approval): bool
    {
        return $user->canIn($approval->mosque, 'approvals.decide');
    }

    public function update(User $user, Approval $approval): bool
    {
        return $this->decide($user, $approval);
    }
}

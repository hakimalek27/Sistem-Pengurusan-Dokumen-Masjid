<?php

namespace App\Policies;

use App\Models\Approval;
use App\Models\Mosque;
use App\Models\User;
use App\Services\DelegationService;
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
        return $user->can('view', $approval->record)
            && ($approval->requested_by === $user->id
                || app(DelegationService::class)->canActFor($user, $approval->approver, $approval->mosque, 'approvals'));
    }

    public function create(User $user): bool
    {
        $mosque = $this->tenant();

        return $mosque ? $user->canIn($mosque, 'approvals.request') : false;
    }

    public function decide(User $user, Approval $approval): bool
    {
        return app(DelegationService::class)->canActFor($user, $approval->approver, $approval->mosque, 'approvals')
            && $user->can('view', $approval->record);
    }

    public function update(User $user, Approval $approval): bool
    {
        return $this->decide($user, $approval);
    }
}

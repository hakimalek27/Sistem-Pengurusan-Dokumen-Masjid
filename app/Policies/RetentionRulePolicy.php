<?php

namespace App\Policies;

use App\Models\Mosque;
use App\Models\RetentionRule;
use App\Models\User;
use Filament\Facades\Filament;

class RetentionRulePolicy
{
    protected function tenant(): ?Mosque
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Mosque ? $tenant : null;
    }

    public function viewAny(User $user): bool
    {
        $mosque = $this->tenant();

        return $mosque ? $user->canIn($mosque, 'retention.manage') : false;
    }

    public function view(User $user, RetentionRule $rule): bool
    {
        // Peraturan platform (mosque_id NULL) hanya superadmin (Gate::before).
        return $rule->mosque_id !== null && $user->canIn($rule->mosque, 'retention.manage');
    }

    public function create(User $user): bool
    {
        $mosque = $this->tenant();

        return $mosque ? $user->canIn($mosque, 'retention.manage') : false;
    }

    public function update(User $user, RetentionRule $rule): bool
    {
        return $rule->mosque_id !== null && $user->canIn($rule->mosque, 'retention.manage');
    }

    public function delete(User $user, RetentionRule $rule): bool
    {
        return $this->update($user, $rule);
    }
}

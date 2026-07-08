<?php

namespace App\Policies;

use App\Models\ClassificationNode;
use App\Models\Mosque;
use App\Models\User;
use Filament\Facades\Filament;

class ClassificationNodePolicy
{
    protected function tenant(): ?Mosque
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Mosque ? $tenant : null;
    }

    public function viewAny(User $user): bool
    {
        $mosque = $this->tenant();

        return $mosque ? $user->canIn($mosque, 'files.view') : false;
    }

    public function view(User $user, ClassificationNode $node): bool
    {
        return $user->canIn($node->mosque, 'files.view');
    }

    public function create(User $user): bool
    {
        $mosque = $this->tenant();

        return $mosque ? $user->canIn($mosque, 'classification.manage') : false;
    }

    public function update(User $user, ClassificationNode $node): bool
    {
        return $user->canIn($node->mosque, 'classification.manage');
    }

    public function delete(User $user, ClassificationNode $node): bool
    {
        return $user->canIn($node->mosque, 'classification.manage');
    }
}

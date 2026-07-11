<?php

namespace App\Policies;

use App\Models\DisposalBatch;
use App\Models\Mosque;
use App\Models\User;
use Filament\Facades\Filament;

class DisposalBatchPolicy
{
    protected function tenant(): ?Mosque
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Mosque ? $tenant : null;
    }

    public function viewAny(User $user): bool
    {
        $mosque = $this->tenant();

        return $mosque ? $user->canIn($mosque, 'disposal.prepare') : false;
    }

    public function view(User $user, DisposalBatch $batch): bool
    {
        return $user->canIn($batch->mosque, 'disposal.prepare')
            || $user->canIn($batch->mosque, 'disposal.approve');
    }

    public function create(User $user): bool
    {
        $mosque = $this->tenant();

        return $mosque ? $user->canIn($mosque, 'disposal.prepare') : false;
    }

    public function approve(User $user, DisposalBatch $batch): bool
    {
        return $user->canIn($batch->mosque, 'disposal.approve');
    }

    public function execute(User $user, DisposalBatch $batch): bool
    {
        return $user->canIn($batch->mosque, 'disposal.execute');
    }

    public function downloadCertificate(User $user, DisposalBatch $batch): bool
    {
        return $user->is_superadmin
            || $user->canIn($batch->mosque, 'disposal.prepare')
            || $user->canIn($batch->mosque, 'disposal.approve')
            || $user->canIn($batch->mosque, 'disposal.execute')
            || $user->canIn($batch->mosque, 'audit.view');
    }
}

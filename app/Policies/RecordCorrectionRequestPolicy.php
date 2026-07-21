<?php

namespace App\Policies;

use App\Models\Mosque;
use App\Models\RecordCorrectionRequest;
use App\Models\User;
use Filament\Facades\Filament;

class RecordCorrectionRequestPolicy
{
    protected function tenant(): ?Mosque
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Mosque ? $tenant : null;
    }

    public function viewAny(User $user): bool
    {
        $mosque = $this->tenant();

        return $mosque && $user->canIn($mosque, 'records.view');
    }

    public function view(User $user, RecordCorrectionRequest $request): bool
    {
        return $user->can('view', $request->record)
            && ($request->requested_by === $user->id || $user->can('update', $request->record));
    }

    public function create(User $user): bool
    {
        $mosque = $this->tenant();

        return $mosque && $user->canIn($mosque, 'records.view');
    }

    public function review(User $user, RecordCorrectionRequest $request): bool
    {
        return $request->status === 'menunggu' && $user->can('update', $request->record);
    }

    public function update(User $user, RecordCorrectionRequest $request): bool
    {
        return $this->review($user, $request);
    }
}

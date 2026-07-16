<?php

namespace App\Policies;

use App\Concerns\ChecksSensitivity;
use App\Models\Mosque;
use App\Models\Record;
use App\Models\User;
use Filament\Facades\Filament;

class RecordPolicy
{
    use ChecksSensitivity;

    protected function tenant(?Record $record = null): ?Mosque
    {
        if ($record) {
            return $record->mosque;
        }

        $tenant = Filament::getTenant();

        return $tenant instanceof Mosque ? $tenant : null;
    }

    public function viewAny(User $user): bool
    {
        $mosque = $this->tenant();

        return $mosque ? $user->canIn($mosque, 'records.view') : false;
    }

    public function view(User $user, Record $record): bool
    {
        return $user->canIn($record->mosque, 'records.view')
            && $this->canSeeSensitivity($user, $record);
    }

    public function create(User $user): bool
    {
        $mosque = $this->tenant();

        return $mosque ? $user->canIn($mosque, 'records.create') : false;
    }

    public function update(User $user, Record $record): bool
    {
        if (! $user->canIn($record->mosque, 'records.update')) {
            return false;
        }

        // §6.2* bendahari: create/update terhad rekod dalam fail klasifikasi 200 (Kewangan).
        if (! $user->is_superadmin && $user->roleIn($record->mosque) === 'bendahari') {
            return $this->fileHasPrefix($record->registryFile, ['200']);
        }

        return true;
    }

    public function delete(User $user, Record $record): bool
    {
        // Padam-spam peti masuk.
        return $record->status->value === 'peti_masuk'
            && $user->canIn($record->mosque, 'inbox.classify');
    }

    public function classify(User $user, Record $record): bool
    {
        return $record->status->value === 'peti_masuk'
            && $user->canIn($record->mosque, 'inbox.classify');
    }

    public function move(User $user, Record $record): bool
    {
        return $user->canIn($record->mosque, 'records.move');
    }

    public function supersede(User $user, Record $record): bool
    {
        return $user->canIn($record->mosque, 'records.supersede');
    }

    public function download(User $user, Record $record): bool
    {
        return $this->view($user, $record);
    }

    public function legalHold(User $user, Record $record): bool
    {
        return $user->canIn($record->mosque, 'retention.hold');
    }

    public function routeMinit(User $user, Record $record): bool
    {
        return $this->view($user, $record) && $user->canIn($record->mosque, 'minit.create');
    }

    public function requestApproval(User $user, Record $record): bool
    {
        return $this->view($user, $record) && $user->canIn($record->mosque, 'approvals.request');
    }

    public function generateQr(User $user, Record $record): bool
    {
        return $this->view($user, $record);
    }
}

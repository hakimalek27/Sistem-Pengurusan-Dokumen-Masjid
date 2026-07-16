<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User $record */
        $record = $this->getRecord();
        $willBeActive = (bool) ($data['is_active'] ?? $record->is_active);
        $willBeSuperadmin = (bool) ($data['is_superadmin'] ?? $record->is_superadmin);

        if ($record->is(Auth::user()) && (! $willBeActive || ! $willBeSuperadmin)) {
            throw ValidationException::withMessages([
                'is_active' => 'Anda tidak boleh menyahaktif atau menurunkan akaun superadmin sendiri.',
            ]);
        }

        if ($record->is_superadmin && $record->is_active
            && (! $willBeActive || ! $willBeSuperadmin)
            && User::query()->where('is_superadmin', true)->where('is_active', true)->count() <= 1) {
            throw ValidationException::withMessages([
                'is_superadmin' => 'Superadmin aktif terakhir tidak boleh dinyahaktif atau diturunkan.',
            ]);
        }

        return $data;
    }
}

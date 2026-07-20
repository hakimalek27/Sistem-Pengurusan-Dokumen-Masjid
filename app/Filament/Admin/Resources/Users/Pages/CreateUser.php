<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use App\Services\WhatsAppRecipientResolver;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->normalizePhone($data);
    }

    /** @param array<string, mixed> $data */
    private function normalizePhone(array $data): array
    {
        if (blank($data['phone_wa'] ?? null)) {
            $data['phone_wa'] = null;

            return $data;
        }

        $phone = app(WhatsAppRecipientResolver::class)->normalize((string) $data['phone_wa']);
        if ($phone === null) {
            throw ValidationException::withMessages([
                'phone_wa' => 'Nombor WhatsApp tidak sah.',
            ]);
        }

        if (User::query()->where('phone_wa', $phone)->exists()) {
            throw ValidationException::withMessages([
                'phone_wa' => 'Nombor WhatsApp ini sudah digunakan oleh akaun lain.',
            ]);
        }

        $data['phone_wa'] = $phone;

        return $data;
    }
}

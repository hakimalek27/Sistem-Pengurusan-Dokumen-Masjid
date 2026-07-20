<?php

use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Models\User;
use Illuminate\Validation\ValidationException;

function callUserPhoneMutation(EditUser $page, array $data): array
{
    $method = new ReflectionMethod(EditUser::class, 'mutateFormDataBeforeSave');
    $method->setAccessible(true);

    return $method->invoke($page, $data);
}

it('menolak nombor WhatsApp akaun lain sebelum database menghasilkan 500', function () {
    $super = User::factory()->create([
        'is_superadmin' => true,
        'is_active' => true,
        'phone_wa' => '60111111111',
    ]);
    $other = User::factory()->create([
        'is_active' => true,
        'phone_wa' => '60122222222',
    ]);

    $page = app(EditUser::class);
    $page->record = $super;

    expect(fn () => callUserPhoneMutation($page, [
        'is_active' => true,
        'is_superadmin' => true,
        'phone_wa' => $other->phone_wa,
    ]))->toThrow(ValidationException::class);
});

it('menormalkan nombor WhatsApp tempatan sebelum simpan', function () {
    $super = User::factory()->create([
        'is_superadmin' => true,
        'is_active' => true,
        'phone_wa' => '60111111111',
    ]);

    $page = app(EditUser::class);
    $page->record = $super;

    expect(callUserPhoneMutation($page, [
        'is_active' => true,
        'is_superadmin' => true,
        'phone_wa' => '0113333333',
    ])['phone_wa'])->toBe('60113333333');
});

<?php

use App\Enums\MosqueStatus;
use App\Livewire\RegisterMosque;
use App\Models\LoginToken;
use App\Models\Mosque;
use App\Models\User;
use App\Services\MosqueProvisioningService;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    RateLimiter::clear('daftar:127.0.0.1');
});

function daftarMasjidUjian(): void
{
    Livewire::test(RegisterMosque::class)
        ->set('name', 'Masjid Ujian Wangsa')
        ->set('state', 'Selangor')
        ->set('district', 'Gombak')
        ->set('code', 'MUJ')
        ->set('slug', 'muji')
        ->set('admin_name', 'Ali bin Ahmad')
        ->set('email', 'ali@ujian.test')
        ->set('phone_wa', '60199999999')
        ->set('agree_terms', true)
        ->set('agree_retention', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);
}

it('pendaftaran → masjid menunggu + admin_masjid pivot + admin belum aktif + KF belum disalin', function () {
    daftarMasjidUjian();

    $mosque = Mosque::query()->where('slug', 'muji')->first();
    $admin = User::query()->where('email', 'ali@ujian.test')->first();

    expect($mosque)->not->toBeNull()
        ->and($mosque->status)->toBe(MosqueStatus::Menunggu)
        ->and($admin)->not->toBeNull()
        ->and($admin->is_active)->toBeFalse()
        ->and($admin->roleIn($mosque))->toBe('admin_masjid')
        ->and($mosque->classificationNodes()->count())->toBe(0);
});

it('menolak pendaftaran tanpa dua pengakuan (checkbox)', function () {
    Livewire::test(RegisterMosque::class)
        ->set('name', 'Masjid Tanpa Setuju')
        ->set('state', 'Selangor')
        ->set('code', 'MTS')
        ->set('slug', 'mts')
        ->set('admin_name', 'Abu')
        ->set('email', 'abu@ujian.test')
        ->set('phone_wa', '60188888888')
        ->set('agree_terms', false)
        ->set('agree_retention', false)
        ->call('submit')
        ->assertHasErrors(['agree_terms', 'agree_retention']);

    expect(Mosque::query()->where('slug', 'mts')->exists())->toBeFalse();
});

it('kelulusan superadmin → aktif + KF 40 nod + admin aktif + magic link dihantar', function () {
    daftarMasjidUjian();

    $mosque = Mosque::query()->where('slug', 'muji')->first();
    $admin = User::query()->where('email', 'ali@ujian.test')->first();

    app(MosqueProvisioningService::class)->approve($mosque->fresh());

    expect($mosque->fresh()->status)->toBe(MosqueStatus::Aktif)
        ->and($mosque->classificationNodes()->count())->toBe(40)
        ->and($admin->fresh()->is_active)->toBeTrue()
        ->and(LoginToken::query()->where('email', 'ali@ujian.test')->exists())->toBeTrue();
});

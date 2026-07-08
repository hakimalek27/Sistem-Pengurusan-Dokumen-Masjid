<?php

use App\Models\LoginToken;
use App\Models\User;
use App\Services\MembershipService;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    $this->svc = app(MembershipService::class);
    $this->mam = makeMosque('MAM', 'mam');
    $this->admin = makeMember($this->mam, 'admin_masjid', 'a@mam.test');
    $this->kerani = makeMember($this->mam, 'kerani', 'k@mam.test');
});

it('jemput ahli baharu → cipta + attach peranan + magic link', function () {
    $user = $this->svc->invite($this->mam, 'baru@mam.test', 'Ali', 'ajk');

    expect($user->roleIn($this->mam))->toBe('ajk')
        ->and(LoginToken::query()->where('email', 'baru@mam.test')->exists())->toBeTrue();
});

it('TIDAK boleh membuang admin_masjid terakhir (§6.4)', function () {
    expect(fn () => $this->svc->remove($this->mam, $this->admin, $this->admin))
        ->toThrow(RuntimeException::class);

    expect($this->admin->fresh()->roleIn($this->mam))->toBe('admin_masjid');
});

it('boleh membuang admin jika ada admin_masjid lain', function () {
    $admin2 = makeMember($this->mam, 'admin_masjid', 'a2@mam.test');

    $this->svc->remove($this->mam, $this->admin, $admin2);

    expect($this->admin->fresh()->roleIn($this->mam))->toBeNull();
});

it('TIDAK boleh menurunkan admin_masjid terakhir', function () {
    expect(fn () => $this->svc->changeRole($this->mam, $this->admin, 'kerani', $this->admin))
        ->toThrow(RuntimeException::class);
});

it('TIDAK boleh menurunkan peranan diri sendiri', function () {
    makeMember($this->mam, 'admin_masjid', 'a2@mam.test'); // ada admin lain
    expect(fn () => $this->svc->changeRole($this->mam, $this->admin, 'kerani', $this->admin))
        ->toThrow(RuntimeException::class);
});

it('TIDAK boleh menyentuh akaun superadmin', function () {
    $super = User::query()->create(['name' => 'S', 'email' => 's@x.test', 'is_superadmin' => true, 'is_active' => true]);
    $this->mam->users()->attach($super->id, ['role' => 'ajk', 'joined_at' => now()]);

    expect(fn () => $this->svc->remove($this->mam, $super, $this->admin))
        ->toThrow(RuntimeException::class);
});

it('boleh menukar peranan ahli biasa', function () {
    $this->svc->changeRole($this->mam, $this->kerani, 'setiausaha', $this->admin);

    expect($this->kerani->fresh()->roleIn($this->mam))->toBe('setiausaha');
});

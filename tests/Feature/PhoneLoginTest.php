<?php

use App\Filament\Auth\Login;
use App\Livewire\SetFirstPassword;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

// Kunci had kadar log masuk (danharrin) dikongsi semua ujian kerana IP tetap
// 127.0.0.1. Cache Redis CI kekal merentas ujian serial → hit terkumpul.
// Bersihkan sebelum setiap ujian supaya setiap kes bermula dari sifar percubaan.
beforeEach(function () {
    RateLimiter::clear('livewire-rate-limiter:'.sha1(Login::class.'|authenticate|127.0.0.1'));
});

it('log masuk panel masjid dengan nombor telefon (0 dinormal ke 60)', function () {
    $mam = makeMosque('MAM', 'mam');
    $user = makeMember($mam, 'kerani', 'k@mam.test', ['phone_wa' => '60123456789', 'password' => bcrypt('rahsia123')]);

    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(Login::class)
        ->set('data.login', '0123456789')
        ->set('data.password', 'rahsia123')
        ->call('authenticate');

    $this->assertAuthenticatedAs($user->fresh());
});

it('log masuk dengan e-mel masih berfungsi', function () {
    $mam = makeMosque('MAM', 'mam');
    $user = makeMember($mam, 'kerani', 'k@mam.test', ['password' => bcrypt('rahsia123')]);

    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(Login::class)
        ->set('data.login', 'k@mam.test')
        ->set('data.password', 'rahsia123')
        ->call('authenticate');

    $this->assertAuthenticatedAs($user->fresh());
});

it('akaun telefon-sahaja (tanpa e-mel) boleh log masuk', function () {
    $mam = makeMosque('MAM', 'mam');
    $user = makeMember($mam, 'ajk', 'x@mam.test', ['email' => null, 'phone_wa' => '60111222333', 'password' => bcrypt('rahsia123')]);

    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(Login::class)
        ->set('data.login', '60111222333')
        ->set('data.password', 'rahsia123')
        ->call('authenticate');

    $this->assertAuthenticatedAs($user->fresh());
});

it('telefon tidak wujud → ralat, kekal guest', function () {
    makeMosque('MAM', 'mam');
    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(Login::class)
        ->set('data.login', '60999999999')
        ->set('data.password', 'apa-apa')
        ->call('authenticate')
        ->assertHasErrors('data.login');

    $this->assertGuest();
});

it('superadmin log masuk dengan telefon di panel /admin', function () {
    $super = User::query()->create([
        'name' => 'S', 'email' => 's@x.test', 'phone_wa' => '60155556666',
        'password' => bcrypt('rahsia123'), 'is_superadmin' => true, 'is_active' => true,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(Login::class)
        ->set('data.login', '60155556666')
        ->set('data.password', 'rahsia123')
        ->call('authenticate');

    $this->assertAuthenticatedAs($super->fresh());
});

it('gate: akaun tanpa kata laluan dipaksa tetapkan kata laluan dahulu', function () {
    $mam = makeMosque('MAM', 'mam');
    $user = makeMember($mam, 'kerani', 'k@mam.test', ['password' => null]);

    $this->actingAs($user)->get('/app/mam/peti-masuk')
        ->assertRedirect(route('password.first'));
});

it('gate: akaun berkata laluan tidak terganggu', function () {
    $mam = makeMosque('MAM', 'mam');
    $user = makeMember($mam, 'kerani', 'k@mam.test');

    $this->actingAs($user)->get('/app/mam/peti-masuk')->assertOk();
});

it('set kata laluan pertama → teruskan ke destinasi asal', function () {
    $mam = makeMosque('MAM', 'mam');
    $user = makeMember($mam, 'admin_masjid', 'a@mam.test', ['password' => null]);

    $this->actingAs($user);
    session()->put('url.intended', '/app/mam');

    Livewire::test(SetFirstPassword::class)
        ->set('password', 'RahsiaBaru123!')
        ->set('password_confirmation', 'RahsiaBaru123!')
        ->call('save')
        ->assertRedirect('/app/mam');

    expect($user->fresh()->password)->not->toBeNull();
});

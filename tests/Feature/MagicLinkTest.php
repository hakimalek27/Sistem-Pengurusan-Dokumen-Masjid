<?php

use App\Models\LoginToken;
use App\Models\User;
use App\Services\MagicLinkService;

beforeEach(function () {
    $this->svc = app(MagicLinkService::class);
});

it('menghantar & menggunakan token sah → pulangkan pengguna dan tandakan used', function () {
    $user = User::query()->create(['name' => 'A', 'email' => 'a@ujian.test', 'is_active' => true]);

    $raw = $this->svc->sendTo('a@ujian.test');
    expect($raw)->not->toBeNull();

    $resolved = $this->svc->consume($raw);
    expect($resolved?->id)->toBe($user->id)
        ->and(LoginToken::query()->first()->used_at)->not->toBeNull();
});

it('menolak token yang telah tamat tempoh (15 minit)', function () {
    User::query()->create(['name' => 'A', 'email' => 'a@ujian.test', 'is_active' => true]);

    $raw = $this->svc->createToken('a@ujian.test');
    LoginToken::query()->first()->update(['expires_at' => now()->subMinute()]);

    expect($this->svc->consume($raw))->toBeNull();
});

it('menolak token yang telah digunakan (sekali guna)', function () {
    User::query()->create(['name' => 'A', 'email' => 'a@ujian.test', 'is_active' => true]);

    $raw = $this->svc->sendTo('a@ujian.test');
    $this->svc->consume($raw);

    expect($this->svc->consume($raw))->toBeNull();
});

it('tidak menghantar pautan kepada pengguna dinyahaktif', function () {
    User::query()->create(['name' => 'A', 'email' => 'a@ujian.test', 'is_active' => false]);

    expect($this->svc->sendTo('a@ujian.test'))->toBeNull()
        ->and(LoginToken::query()->count())->toBe(0);
});

it('/masuk/{token} log masuk & mendarat di panel masjid tunggal', function () {
    $mam = makeMosque('MAM', 'mam');
    $user = makeMember($mam, 'kerani', 'k@ujian.test');

    $raw = $this->svc->sendTo('k@ujian.test');

    $this->get('/masuk/'.$raw)->assertRedirect('/app/mam');
    $this->assertAuthenticatedAs($user->fresh());
});

it('/masuk/{token} tidak sah → 403', function () {
    $this->get('/masuk/'.str_repeat('x', 64))->assertForbidden();
});

it('menghantar pautan melalui pengecam telefon (0 → 60)', function () {
    $mam = makeMosque('MAM', 'mam');
    $user = makeMember($mam, 'kerani', 'k@mam.test', ['phone_wa' => '60123456789']);

    $raw = $this->svc->sendTo('0123456789');
    expect($raw)->not->toBeNull();

    $resolved = $this->svc->consume($raw);
    expect($resolved?->id)->toBe($user->id)
        ->and(LoginToken::query()->where('user_id', $user->id)->exists())->toBeTrue();
});

it('token terikat user_id boleh diguna walau akaun tiada e-mel', function () {
    $mam = makeMosque('MAM', 'mam');
    $user = makeMember($mam, 'ajk', 'x@mam.test', ['email' => null, 'phone_wa' => '60111222333']);

    $raw = $this->svc->sendToUser($user);
    $resolved = $this->svc->consume($raw);

    expect($resolved?->id)->toBe($user->id);
});

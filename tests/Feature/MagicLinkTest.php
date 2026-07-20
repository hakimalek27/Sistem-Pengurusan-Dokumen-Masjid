<?php

use App\Models\LoginToken;
use App\Models\User;
use App\Services\MagicLinkService;

beforeEach(function () {
    cache()->flush(); // reset kaunter throttle /masuk (CI cache redis dikongsi antara ujian serial)
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

it('/masuk/{token} interstisial (GET) tidak guna token; POST log masuk & mendarat', function () {
    $mam = makeMosque('MAM', 'mam');
    $user = makeMember($mam, 'kerani', 'k@ujian.test');

    $raw = $this->svc->sendTo('k@ujian.test');

    // GET = interstisial (elak bot pratonton bakar token); token BELUM diguna.
    $this->get('/masuk/'.$raw)->assertOk()->assertSee('Teruskan');
    expect(LoginToken::query()->first()->used_at)->toBeNull();

    // POST = guna token + log masuk.
    $this->post('/masuk/'.$raw)->assertRedirect('/app/mam');
    $this->assertAuthenticatedAs($user->fresh());
    expect(LoginToken::query()->first()->used_at)->not->toBeNull();
});

it('/masuk/{token} tidak sah → halaman 410 (bukan 403 kosong)', function () {
    $this->get('/masuk/'.str_repeat('x', 64))->assertStatus(410);
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

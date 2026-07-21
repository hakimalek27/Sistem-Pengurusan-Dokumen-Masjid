<?php

use App\Models\LoginToken;
use App\Services\MagicLinkService;
use Illuminate\Support\Facades\Auth;

/*
 * §15.1 — Magic link deep-link + interstisial + fix bounce.
 * Pautan notifikasi (minit/kelulusan/peti masuk) auto-login penerima terus ke
 * sasaran; GET tidak guna token (elak bot pratonton bakar); POST guna sekali.
 */

beforeEach(function () {
    cache()->flush(); // reset kaunter throttle /masuk (CI cache redis dikongsi antara ujian serial)
    $this->svc = app(MagicLinkService::class);
});

it('deepLinkFor menjana pautan notifikasi (purpose=notification, TTL ~72 jam, intended)', function () {
    $mam = makeMosque('MAM', 'mam');
    $user = makeMember($mam, 'pengerusi', 'p@mam.test');

    $url = $this->svc->deepLinkFor($user, '/r/ABC123');

    expect($url)->toStartWith(url('/masuk/'));
    $token = LoginToken::query()->first();
    expect($token->purpose)->toBe('notification')
        ->and($token->intended_url)->toBe('/r/ABC123')
        ->and($token->user_id)->toBe($user->id)
        ->and((int) round(abs($token->expires_at->diffInHours(now()))))->toBeGreaterThanOrEqual(71);
});

it('GET interstisial tidak guna token — dua GET berturut kekal selamat', function () {
    $mam = makeMosque('MAM', 'mam');
    $user = makeMember($mam, 'kerani', 'k@mam.test');
    $raw = $this->svc->createTokenForUser($user, null, '/app/mam/peti-masuk', 4320);

    $this->get('/masuk/'.$raw)->assertOk();
    $this->get('/masuk/'.$raw)->assertOk(); // GET kedua (bot pratonton)
    expect(LoginToken::query()->first()->used_at)->toBeNull();

    $this->post('/masuk/'.$raw)->assertRedirect('/app/mam/peti-masuk');
});

it('POST deep-link mendarat pada destinasi (intended_url) + log masuk', function () {
    $mam = makeMosque('MAM', 'mam');
    $user = makeMember($mam, 'pengerusi', 'p@mam.test');
    $raw = $this->svc->createTokenForUser($user, null, '/r/XYZ789', 4320);

    $this->post('/masuk/'.$raw)->assertRedirect('/r/XYZ789');
    $this->assertAuthenticatedAs($user->fresh());
});

it('token deep-link sekali guna — POST kedua → 410', function () {
    $mam = makeMosque('MAM', 'mam');
    $user = makeMember($mam, 'kerani', 'k@mam.test');
    $raw = $this->svc->createTokenForUser($user, null, '/app/mam', 4320);

    $this->post('/masuk/'.$raw)->assertRedirect('/app/mam');
    Auth::logout();
    $this->post('/masuk/'.$raw)->assertStatus(410);
});

it('token tamat tempoh → halaman 410 (GET dan POST)', function () {
    $mam = makeMosque('MAM', 'mam');
    $user = makeMember($mam, 'kerani', 'k@mam.test');
    $raw = $this->svc->createTokenForUser($user, null, '/app/mam', 4320);
    LoginToken::query()->first()->update(['expires_at' => now()->subMinute()]);

    $this->get('/masuk/'.$raw)->assertStatus(410);
    $this->post('/masuk/'.$raw)->assertStatus(410);
});

it('menyekat open redirect — intended luar domain jatuh ke pendaratan peranan', function () {
    $mam = makeMosque('MAM', 'mam');
    $user = makeMember($mam, 'kerani', 'k@mam.test');

    foreach (['https://evil.test/x', '//evil.test', '/\\evil.test', 'javascript:alert(1)'] as $bad) {
        LoginToken::query()->delete();
        Auth::logout();
        $raw = $this->svc->createTokenForUser($user, null, $bad, 60);
        $this->post('/masuk/'.$raw)->assertRedirect('/app/mam/persediaan?mula=1'); // bukan $bad
    }
});

it('klik semula sebagai pengguna sama → redirect tanpa guna token', function () {
    $mam = makeMosque('MAM', 'mam');
    $user = makeMember($mam, 'kerani', 'k@mam.test');
    $raw = $this->svc->createTokenForUser($user, null, '/app/mam/peti-masuk', 4320);

    $this->actingAs($user);
    $this->get('/masuk/'.$raw)->assertRedirect('/app/mam/peti-masuk');
    expect(LoginToken::query()->first()->used_at)->toBeNull(); // TIDAK diguna
});

it('membaiki bounce — magic login selaras password_hash_web walau sesi lama wujud', function () {
    $mam = makeMosque('MAM', 'mam');
    $a = makeMember($mam, 'kerani', 'a@mam.test');
    $b = makeMember($mam, 'pengerusi', 'b@mam.test');

    // Simulasi sesi lama: hash kata laluan A masih dalam sesi.
    $this->withSession(['password_hash_web' => $a->getAuthPassword()]);

    $raw = $this->svc->sendToUser($b);
    $this->post('/masuk/'.$raw)->assertRedirect('/app/mam');

    // Sesi kini pegang hash B (bukan A basi) → panel tidak paksa-logout.
    expect(session('password_hash_web'))->toBe($b->getAuthPassword());
    $this->get('/app/mam')->assertOk();
    $this->assertAuthenticatedAs($b->fresh());
});

it('gate kata laluan — akaun tanpa kata laluan magic login → dipaksa set kata laluan di panel', function () {
    $mam = makeMosque('MAM', 'mam');
    $user = makeMember($mam, 'ajk', 'ajk@mam.test', ['password' => null]);
    $raw = $this->svc->createTokenForUser($user, null, '/app/mam/peti-masuk', 4320);

    $this->post('/masuk/'.$raw);
    $this->assertAuthenticatedAs($user->fresh());

    // Melawat panel → EnsurePasswordIsSet paksa ke set-kata-laluan dahulu.
    $this->get('/app/mam/peti-masuk')->assertRedirect(route('password.first'));
});

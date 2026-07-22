<?php

it('halaman utama papar jenama Diwan (BM)', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Daftar Masjid')
        ->assertSee('Log Masuk');
});

it('halaman /daftar boleh diakses & memaparkan medan BM', function () {
    $this->get('/daftar')
        ->assertOk()
        ->assertSee('Nama Masjid')
        ->assertSee('Kod Akronim');
});

it('halaman /log-masuk papar borang magic link (BM)', function () {
    $this->get('/log-masuk')
        ->assertOk()
        ->assertSee('Hantar Pautan Log Masuk');
});

it('halaman log masuk panel superadmin boleh diakses', function () {
    $this->get('/admin/login')->assertOk();
});

it('response awam membawa header keselamatan asas', function () {
    $this->get('/')
        ->assertOk()
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
});

it('log masuk /admin papar hint ke panel masjid', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('panel masjid')
        ->assertSee('/app/login');
});

it('log masuk /app papar hint ke panel pentadbir & magic link', function () {
    $this->get('/app/login')
        ->assertOk()
        ->assertSee('Pentadbir platform')
        ->assertSee('Dapatkan pautan log masuk');
});

it('guest ke /admin dialih ke log masuk', function () {
    $this->get('/admin')->assertRedirect();
});

it('mengasingkan bucket kadar pendaftaran, halaman login dan magic login', function () {
    cache()->flush();

    $this->get('/daftar')->assertOk();
    $this->get('/log-masuk')->assertOk();

    foreach (range(1, 10) as $attempt) {
        $this->get('/masuk/token-tidak-sah-'.$attempt)->assertStatus(410);
    }

    $limited = $this->get('/masuk/token-tidak-sah-11')->assertStatus(429);

    expect((int) $limited->headers->get('Retry-After'))->toBeLessThanOrEqual(60);
});

it('memisahkan bucket imej bantuan supaya thumbnail tidak mengunci halaman bantuan', function () {
    cache()->flush();

    foreach (range(1, 61) as $attempt) {
        $this->get('/bantuan/imej/public.registration')->assertOk();
    }
});

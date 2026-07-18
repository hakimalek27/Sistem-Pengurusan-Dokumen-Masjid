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

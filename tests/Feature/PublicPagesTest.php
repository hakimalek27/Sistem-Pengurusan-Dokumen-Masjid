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

it('guest ke /admin dialih ke log masuk', function () {
    $this->get('/admin')->assertRedirect();
});

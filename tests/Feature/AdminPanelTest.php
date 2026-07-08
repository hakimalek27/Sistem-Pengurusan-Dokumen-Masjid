<?php

use App\Models\User;

beforeEach(function () {
    $this->super = User::query()->create(['name' => 'Super', 'email' => 's@x.test', 'is_superadmin' => true, 'is_active' => true]);
    makeMosque('MAM', 'mam');
});

it('papan pemuka superadmin render', function () {
    $this->actingAs($this->super)->get('/admin')->assertOk();
});

it('senarai Masjid render', function () {
    $this->actingAs($this->super)->get('/admin/mosques')->assertOk();
});

it('senarai Pesanan Storan render', function () {
    $this->actingAs($this->super)->get('/admin/storage-orders')->assertOk();
});

it('senarai Pengguna render', function () {
    $this->actingAs($this->super)->get('/admin/users')->assertOk();
});

it('halaman Tetapan Platform render', function () {
    $this->actingAs($this->super)->get('/admin/tetapan-platform')->assertOk();
});

it('bukan superadmin tidak boleh akses /admin', function () {
    $user = makeMember(makeMosque('MAN', 'man'), 'kerani', 'k@man.test');

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

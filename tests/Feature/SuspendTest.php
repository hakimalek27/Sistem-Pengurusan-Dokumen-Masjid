<?php

use App\Enums\MosqueStatus;
use App\Models\User;

it('ahli masjid digantung tidak boleh akses panel (§10.M)', function () {
    $mam = makeMosque('MAM', 'mam', MosqueStatus::Digantung);
    $member = makeMember($mam, 'kerani', 'k@mam.test');

    $this->actingAs($member)->get('/app/mam')->assertForbidden();
});

it('ahli masjid aktif boleh akses panel', function () {
    $mam = makeMosque('MAM', 'mam', MosqueStatus::Aktif);
    $member = makeMember($mam, 'kerani', 'k@mam.test');

    $this->actingAs($member)->get('/app/mam')->assertOk();
});

it('superadmin mengurus tenant digantung dari panel platform dan tidak memasuki panel tenant', function () {
    $mam = makeMosque('MAM', 'mam', MosqueStatus::Digantung);
    $super = User::query()->create(['name' => 'Super', 'email' => 's@x.test', 'password' => bcrypt('secret'), 'is_superadmin' => true, 'is_active' => true]);

    $this->actingAs($super)->get('/app/mam')->assertNotFound();
    $this->actingAs($super)->get('/admin/mosques')->assertOk();
});

it('pengguna dinyahaktif tidak boleh akses panel (§15.1)', function () {
    $mam = makeMosque('MAM', 'mam');
    $member = makeMember($mam, 'kerani', 'k@mam.test', ['is_active' => false]);

    $this->actingAs($member)->get('/app/mam')->assertForbidden();
});

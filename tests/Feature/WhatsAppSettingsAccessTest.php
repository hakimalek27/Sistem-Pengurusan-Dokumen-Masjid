<?php

use App\Models\WhatsAppIntegration;

beforeEach(function () {
    config()->set('imap.accounts.default.username', 'scan.diwan@gmail.com');
    $this->mam = makeMosque('MAM', 'mam');
    $this->man = makeMosque('MAN', 'man');
    $this->adminMam = makeMember($this->mam, 'admin_masjid', 'admin@mam.test');
    $this->keraniMam = makeMember($this->mam, 'kerani', 'kerani@mam.test');
    $this->adminMan = makeMember($this->man, 'admin_masjid', 'admin@man.test');
});

it('hanya pemegang kebenaran tenant boleh membuka tetapan WhatsApp dan ahli', function () {
    $this->actingAs($this->adminMam)->get('/app/mam/tetapan-masjid')
        ->assertOk()
        ->assertSee('scan.diwan+mam@gmail.com');
    $this->actingAs($this->adminMam)->get('/app/mam/ahli-peranan')->assertOk();

    $this->actingAs($this->keraniMam)->get('/app/mam/tetapan-masjid')->assertForbidden();
    $this->actingAs($this->keraniMam)->get('/app/mam/ahli-peranan')->assertForbidden();
});

it('admin satu tenant tidak boleh membuka halaman tenant lain', function () {
    $this->actingAs($this->adminMam)->get('/app/man/tetapan-masjid')->assertNotFound();
    $this->actingAs($this->adminMam)->get('/app/man/ahli-peranan')->assertNotFound();

    $this->actingAs($this->adminMan)->get('/app/mam/tetapan-masjid')->assertNotFound();
});

it('nilai penuh api key tidak pernah dirender pada halaman tenant', function () {
    $plainKey = 'sk_'.str_repeat('s', 40);
    WhatsAppIntegration::query()->create([
        'mosque_id' => $this->mam->id,
        'external_id' => 'test:mam',
        'gateway_tenant_id' => 'gateway:mam',
        'api_key' => $plainKey,
        'api_key_prefix' => 'sk_ssssssss...',
        'enabled' => true,
        'status' => 'connected',
        'session_id' => 'sess_mam',
    ]);

    $this->actingAs($this->adminMam)
        ->get('/app/mam/tetapan-masjid')
        ->assertOk()
        ->assertSee('sk_ssssssss...')
        ->assertDontSee($plainKey);
});

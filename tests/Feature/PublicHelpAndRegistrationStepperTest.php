<?php

use App\Livewire\RegisterMosque;
use Livewire\Livewire;

it('memaparkan pusat bantuan awam dan pendaftaran tiga langkah', function () {
    $this->get('/bantuan')->assertOk()->assertSee('Cari panduan')->assertSee('Diagnosis masalah');
    $this->get('/daftar')->assertOk()->assertSee('Maklumat masjid')->assertSee('Pentadbir')->assertSee('Persetujuan');
});

it('mengesahkan setiap langkah pendaftaran sebelum bergerak', function () {
    Livewire::test(RegisterMosque::class)
        ->call('nextStep')
        ->assertHasErrors(['name', 'state', 'code', 'slug'])
        ->set('name', 'Masjid Stepper')
        ->set('state', 'Selangor')
        ->set('code', 'MST')
        ->set('slug', 'masjid-stepper')
        ->call('nextStep')
        ->assertSet('step', 2)
        ->call('nextStep')
        ->assertHasErrors(['admin_name', 'email', 'phone_wa'])
        ->call('previousStep')
        ->assertSet('step', 1);
});

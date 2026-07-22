<?php

use App\Livewire\HelpCenter;
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

it('memberi maklum balas carian awam dan menerangkan sempadan panduan role', function () {
    Livewire::test(HelpCenter::class, ['panel' => 'public'])
        ->assertSee('Skop panduan:')
        ->assertSee('Orang Awam')
        ->set('query', 'daftar masjid')
        ->call('search')
        ->assertSet('searchPerformed', true)
        ->assertSet('lastQuery', 'daftar masjid')
        ->assertSee('Daftar Masjid')
        ->set('query', 'klasifikasi surat dalaman')
        ->call('search')
        ->assertSet('results', [])
        ->assertSee('Panduan kerja masjid hanya tersedia selepas')
        ->call('clearSearch')
        ->assertSet('searchPerformed', false)
        ->assertSet('lastQuery', '');
});

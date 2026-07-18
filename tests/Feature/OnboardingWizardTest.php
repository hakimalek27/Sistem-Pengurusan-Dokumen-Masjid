<?php

use App\Filament\App\Pages\OnboardingWizard;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    $this->mam = makeMosque('MAM', 'mam');
    $this->admin = makeMember($this->mam, 'admin_masjid', 'a@mam.test');
});

it('hanya admin (mosque.settings) boleh akses persediaan', function () {
    $this->actingAs($this->admin)->get('/app/mam/persediaan')->assertOk();

    $kerani = makeMember($this->mam, 'kerani', 'k@mam.test');
    $this->actingAs($kerani)->get('/app/mam/persediaan')->assertForbidden();
});

it('wizard mendaftar ahli, set telefon masjid & tandakan onboarding selesai', function () {
    Filament::setTenant($this->mam, isQuiet: true);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    $this->actingAs($this->admin);

    Livewire::test(OnboardingWizard::class)
        ->callAction('mula', [
            'jawatan' => 'Pentadbir / Setiausaha',
            'mosque_phone' => '60312340000',
            'wa_choice' => 'dedicated',
            'members' => [
                ['name' => 'Ali AJK', 'role' => 'ajk', 'phone_wa' => '011-1222333', 'email' => null, 'jawatan' => 'AJK Kariah'],
                ['name' => 'Siti Pengerusi', 'role' => 'pengerusi', 'phone_wa' => '60199888777', 'email' => 'siti@mam.test', 'jawatan' => ''],
            ],
        ]);

    $mam = $this->mam->fresh();

    expect($mam->phone)->toBe('60312340000')
        ->and(data_get($mam->settings, 'onboarding_done'))->not->toBeNull()
        ->and($mam->users()->wherePivot('role', 'ajk')->exists())->toBeTrue()
        ->and($mam->users()->wherePivot('role', 'pengerusi')->exists())->toBeTrue()
        ->and($this->admin->fresh()->jawatan)->toBe('Pentadbir / Setiausaha');

    Filament::setTenant(null, isQuiet: true);
});

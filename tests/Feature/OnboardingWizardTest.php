<?php

use App\Filament\App\Pages\OnboardingWizard;
use App\Services\MagicLinkService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    cache()->flush(); // reset kaunter throttle /masuk (CI cache redis dikongsi antara ujian serial)
    $this->mam = makeMosque('MAM', 'mam');
    $this->admin = makeMember($this->mam, 'admin_masjid', 'a@mam.test');
});

it('magic link admin yang belum selesai persediaan → mendarat di wizard (?mula=1)', function () {
    $raw = app(MagicLinkService::class)->createTokenForUser($this->admin);

    // §15.1 — GET = interstisial; pendaratan berlaku pada POST (guna token).
    $this->post('/masuk/'.$raw)->assertRedirect('/app/mam/persediaan?mula=1');
});

it('magic link admin yang sudah selesai persediaan → mendarat di dashboard', function () {
    $this->mam->update(['settings' => array_merge($this->mam->settings, ['onboarding_done' => now()->toIso8601String()])]);
    $raw = app(MagicLinkService::class)->createTokenForUser($this->admin->fresh());

    $this->post('/masuk/'.$raw)->assertRedirect('/app/mam');
});

it('dashboard papar banner persediaan bila belum selesai, tiada selepas selesai', function () {
    $this->actingAs($this->admin)->get('/app/mam')->assertOk()->assertSee('Siapkan persediaan masjid');

    $this->mam->update(['settings' => array_merge($this->mam->settings, ['onboarding_done' => now()->toIso8601String()])]);
    $this->actingAs($this->admin->fresh())->get('/app/mam')->assertOk()->assertDontSee('Siapkan persediaan masjid');
});

it('Admin / Kerani nampak banner persediaan', function () {
    $kerani = makeMember($this->mam, 'kerani', 'k@mam.test');

    $this->actingAs($kerani)->get('/app/mam')->assertOk()->assertSee('Siapkan persediaan masjid');
});

it('admin yang mendarat di wizard boleh melangkau (tandakan selesai)', function () {
    Filament::setTenant($this->mam, isQuiet: true);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    $this->actingAs($this->admin);

    Livewire::test(OnboardingWizard::class)->callAction('langkau');

    expect(data_get($this->mam->fresh()->settings, 'onboarding_done'))->not->toBeNull();

    Filament::setTenant(null, isQuiet: true);
});

it('hanya admin (mosque.settings) boleh akses persediaan', function () {
    $this->actingAs($this->admin)->get('/app/mam/persediaan')->assertOk();

    $ajk = makeMember($this->mam, 'ajk', 'ajk@mam.test');
    $this->actingAs($ajk)->get('/app/mam/persediaan')->assertForbidden();
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

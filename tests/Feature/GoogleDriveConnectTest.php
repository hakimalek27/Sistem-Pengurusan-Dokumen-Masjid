<?php

use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\GoogleDrive\DriveConfig;
use App\Services\GoogleDrive\GoogleOAuthService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/*
 * §4.6′ — Sambungan Google Drive (superadmin): kredensial tersulit, aliran OAuth
 * callback, dan logik DriveConfig. Tiada panggilan Google sebenar (exchangeCode
 * di-mock).
 */

function makeSuperadmin(): User
{
    return User::query()->create([
        'name' => 'Super', 'email' => 'super-gdrive@ujian.test',
        'password' => bcrypt('rahsia'), 'is_superadmin' => true, 'is_active' => true,
    ]);
}

it('DriveConfig: configured/connected/enabled ikut tetapan tersulit', function () {
    expect(DriveConfig::configured())->toBeFalse();

    PlatformSetting::put('gdrive_client_id', 'cid.apps.googleusercontent.com');
    PlatformSetting::putEncrypted('gdrive_client_secret', 'SEKRETRAHSIA123');
    DriveConfig::forget();
    expect(DriveConfig::configured())->toBeTrue()
        ->and(DriveConfig::connected())->toBeFalse()
        ->and(DriveConfig::enabled())->toBeFalse();

    PlatformSetting::putEncrypted('gdrive_refresh_token', 'rt-xyz');
    DriveConfig::forget();
    expect(DriveConfig::connected())->toBeTrue()
        ->and(DriveConfig::enabled())->toBeFalse(); // enabled belum ON

    PlatformSetting::put('gdrive_enabled', true);
    DriveConfig::forget();
    expect(DriveConfig::enabled())->toBeTrue();

    // Secret betul-betul tersulit dalam DB (bukan plaintext).
    $raw = (string) DB::table('platform_settings')->where('key', 'gdrive_client_secret')->value('value');
    expect($raw)->not->toContain('SEKRETRAHSIA123');
});

it('callback OAuth (superadmin, state sah) menyimpan sambungan + redirect', function () {
    $super = makeSuperadmin();
    $this->actingAs($super);

    PlatformSetting::put('gdrive_client_id', 'cid.apps.googleusercontent.com');
    PlatformSetting::putEncrypted('gdrive_client_secret', 'sekret');
    DriveConfig::forget();

    // Mock exchangeCode (elak Google sebenar); storeConnection dijalankan sebenar.
    $this->partialMock(GoogleOAuthService::class, function ($mock) {
        $mock->shouldReceive('exchangeCode')->once()->andReturn([
            'refresh_token' => 'rtok-123',
            'email' => 'pemilik@gmail.com',
            'limit' => 15 * (1024 ** 3),
            'usage' => 1024,
        ]);
    });

    Cache::put('gdrive_oauth_state:'.$super->id, 'state-abc', now()->addMinutes(10));

    $this->get('/gdrive/callback?code=xyz&state=state-abc')->assertRedirect('/admin/tetapan-platform');

    expect(PlatformSetting::getEncrypted('gdrive_refresh_token'))->toBe('rtok-123')
        ->and(PlatformSetting::get('gdrive_account')['email'])->toBe('pemilik@gmail.com')
        ->and(DriveConfig::connected())->toBeTrue();
});

it('callback OAuth menolak bukan-superadmin (403)', function () {
    $mam = makeMosque('MAM', 'mam');
    $user = makeMember($mam, 'admin_masjid', 'a@mam.test');
    $this->actingAs($user);
    Cache::put('gdrive_oauth_state:'.$user->id, 'st', now()->addMinutes(10));

    $this->get('/gdrive/callback?code=x&state=st')->assertForbidden();
});

it('callback OAuth menolak state tidak sah (419 CSRF)', function () {
    $super = makeSuperadmin();
    $this->actingAs($super);
    Cache::put('gdrive_oauth_state:'.$super->id, 'betul', now()->addMinutes(10));

    $this->get('/gdrive/callback?code=x&state=salah')->assertStatus(419);
});

it('putus Google Drive mengosongkan refresh token + status', function () {
    PlatformSetting::put('gdrive_client_id', 'cid');
    PlatformSetting::putEncrypted('gdrive_client_secret', 'sec');
    PlatformSetting::putEncrypted('gdrive_refresh_token', 'rt');
    PlatformSetting::put('gdrive_enabled', true);
    DriveConfig::forget();
    expect(DriveConfig::enabled())->toBeTrue();

    // Simulasi tindakan putus.
    PlatformSetting::putEncrypted('gdrive_refresh_token', null);
    PlatformSetting::put('gdrive_enabled', false);
    DriveConfig::forget();

    expect(DriveConfig::connected())->toBeFalse()
        ->and(DriveConfig::enabled())->toBeFalse();
});

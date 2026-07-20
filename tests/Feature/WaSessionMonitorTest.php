<?php

use App\Filament\Admin\Widgets\ChannelStatusOverview;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\WhatsAppIntegration;
use App\Notifications\ConnectionAlertNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    $this->super = User::query()->create(['name' => 'S', 'email' => 's@x.test', 'phone_wa' => '60100000000', 'password' => bcrypt('x'), 'is_superadmin' => true, 'is_active' => true]);
    $this->mam = makeMosque('MAM', 'mam');
    $this->admin = makeMember($this->mam, 'admin_masjid', 'a@mam.test');
});

function mamIntegration(array $attrs = []): WhatsAppIntegration
{
    return WhatsAppIntegration::query()->create(array_merge([
        'mosque_id' => test()->mam->id,
        'external_id' => 'spdm:mosque:'.test()->mam->id,
        'api_key' => 'sk_x',
        'gateway_tenant_id' => '5',
        'enabled' => true,
        'status' => 'connected',
        'session_id' => 'sess-mam',
    ], $attrs));
}

it('widget Kesihatan Saluran papar IMAP "Dimatikan" bila IMAP_ENABLED=false (§11.3)', function () {
    // Tanpa keadaan ini widget tersilap papar "OK" hijau walau saluran mati
    // (streak kekal 0 kerana FetchMailJob kembali awal).
    config()->set('diwan.imap_enabled', false);
    $widget = new ChannelStatusOverview;
    $method = new ReflectionMethod($widget, 'getStats');
    $method->setAccessible(true);

    // Susunan tetap: [0]=Gateway, [1]=IMAP, [2]=Sesi WhatsApp.
    expect($method->invoke($widget)[1]->getValue())->toBe('Dimatikan');

    config()->set('diwan.imap_enabled', true);
    expect($method->invoke($widget)[1]->getValue())->toBe('OK');
});

it('sesi terputus → alert superadmin + admin masjid, tandakan down', function () {
    mamIntegration();
    Http::fake(['*' => Http::response(['success' => true, 'data' => ['status' => 'disconnected']], 200)]);

    $this->artisan('diwan:check-wa-sessions')->assertSuccessful();

    Notification::assertSentTo($this->super, ConnectionAlertNotification::class);
    Notification::assertSentTo($this->admin, ConnectionAlertNotification::class);

    expect(WhatsAppIntegration::query()->forMosque($this->mam)->first()->last_alert_status)->toBe('down');
});

it('cooldown menghalang alert berulang', function () {
    mamIntegration(['status' => 'disconnected', 'last_alert_status' => 'down', 'last_alerted_at' => now()->subMinutes(10)]);
    Http::fake(['*' => Http::response(['success' => true, 'data' => ['status' => 'disconnected']], 200)]);

    $this->artisan('diwan:check-wa-sessions')->assertSuccessful();

    Notification::assertNothingSent();
});

it('sesi pulih → alert pulih sekali, tandakan up', function () {
    mamIntegration(['last_alert_status' => 'down', 'last_alerted_at' => now()->subMinutes(90)]);
    Http::fake(['*' => Http::response(['success' => true, 'data' => ['status' => 'connected']], 200)]);

    $this->artisan('diwan:check-wa-sessions')->assertSuccessful();

    Notification::assertSentTo($this->super, ConnectionAlertNotification::class);
    expect(WhatsAppIntegration::query()->forMosque($this->mam)->first()->last_alert_status)->toBe('up');
});

it('IMAP gagal berterusan → alert superadmin sekali', function () {
    PlatformSetting::put('imap_failure_streak', 6);

    $this->artisan('diwan:check-wa-sessions')->assertSuccessful();

    Notification::assertSentTo($this->super, ConnectionAlertNotification::class);
    expect((bool) PlatformSetting::get('imap_alerted'))->toBeTrue();
});

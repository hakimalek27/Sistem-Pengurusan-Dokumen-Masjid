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

    // DIPINDA (20 Jul, peraturan #9): dahulu ujian ini menjangka "OK" hanya
    // kerana streak===0. Andaian itu TEPAT punca kegagalan senyap 14 jam —
    // job yang langsung tidak berjalan mengekalkan streak 0, jadi widget
    // memaparkan hijau sementara intake mati. "OK" kini menuntut BUKTI larian
    // berjaya terkini (detak jantung), bukan sekadar ketiadaan kegagalan.
    config()->set('diwan.imap_enabled', true);
    PlatformSetting::put('imap_last_success_at', now()->subMinute()->toIso8601String());
    expect($method->invoke($widget)[1]->getValue())->toBe('OK');

    // Tanpa detak jantung terkini → mesti amaran, bukan "OK".
    PlatformSetting::put('imap_last_success_at', now()->subHours(3)->toIso8601String());
    expect($method->invoke($widget)[1]->getValue())->toBe('Tersekat');
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
    // DIPINDA (20 Jul, peraturan #9): alert kini dipagar IMAP_ENABLED. Bila IMAP
    // dimatikan, FetchMailJob kembali awal jadi streak MUSTAHIL bertambah —
    // streak>0 pada ketika itu hanyalah data basi dan tidak wajar menjerit.
    config()->set('diwan.imap_enabled', true);
    PlatformSetting::put('imap_failure_streak', 6);

    $this->artisan('diwan:check-wa-sessions')->assertSuccessful();

    Notification::assertSentTo($this->super, ConnectionAlertNotification::class);
    expect((bool) PlatformSetting::get('imap_alerted'))->toBeTrue();
});

it('IMAP dimatikan → tiada alert walaupun streak lama tinggi (§11.3)', function () {
    config()->set('diwan.imap_enabled', false);
    PlatformSetting::put('imap_failure_streak', 9);

    $this->artisan('diwan:check-wa-sessions')->assertSuccessful();

    Notification::assertNotSentTo($this->super, ConnectionAlertNotification::class);
});

<?php

use App\Models\Record;
use App\Models\User;
use App\Models\WhatsAppIntegration;
use App\Services\WhatsAppInboundService;
use App\Services\WhatsAppIntegrationService;
use Illuminate\Support\Facades\Http;

it('provision platform mencipta integrasi mosque_id null terasing daripada tenant', function () {
    config()->set('diwan.whatsapp.provisioning_secret', str_repeat('a', 64));
    config()->set('diwan.whatsapp.webhook_url', 'https://bakwim.my/api/webhooks/whatsapp');
    config()->set('diwan.whatsapp.webhook_secret', str_repeat('b', 32));
    Http::fake(['*' => Http::response(['success' => true, 'data' => ['tenantId' => '99']], 200)]);

    $integration = app(WhatsAppIntegrationService::class)->provision(null);

    expect($integration->mosque_id)->toBeNull()
        ->and($integration->external_id)->toContain(':platform')
        ->and($integration->status)->toBe('linked');

    // Pengasingan: integrasi platform TIDAK muncul dalam skop tenant.
    $mam = makeMosque('MAM', 'mam');
    expect(WhatsAppIntegration::query()->forMosque($mam)->exists())->toBeFalse()
        ->and(WhatsAppIntegration::query()->platform()->count())->toBe(1);
});

it('halaman WhatsApp Platform & Status Sambungan superadmin sahaja', function () {
    $super = User::query()->create(['name' => 'S', 'email' => 's@x.test', 'password' => bcrypt('x'), 'is_superadmin' => true, 'is_active' => true]);

    $this->actingAs($super)->get('/admin/whatsapp-platform')->assertOk();
    $this->actingAs($super)->get('/admin/status-sambungan')->assertOk();

    $mam = makeMosque('MAM', 'mam');
    $admin = makeMember($mam, 'admin_masjid', 'a@mam.test');
    $this->actingAs($admin)->get('/admin/whatsapp-platform')->assertForbidden();
});

it('mesej masuk ke sesi platform diabaikan (tiada intake dokumen)', function () {
    WhatsAppIntegration::query()->create([
        'mosque_id' => null,
        'external_id' => 'spdm-production:platform',
        'api_key' => 'sk_platform',
        'enabled' => true,
        'status' => 'connected',
        'session_id' => 'platform-sess',
    ]);

    app(WhatsAppInboundService::class)->handle([
        'event' => 'message.received',
        'session_id' => 'platform-sess',
        'from' => '60123456789',
        'message_id' => 'msg-1',
        'text' => 'spdm',
    ]);

    expect(Record::query()->withoutGlobalScopes()->count())->toBe(0);
});

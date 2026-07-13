<?php

use App\Models\WhatsAppIntegration;
use App\Services\WhatsAppGateway;
use App\Services\WhatsAppIntegrationService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('diwan.whatsapp.driver', 'gateway');
    config()->set('diwan.whatsapp.gateway_url', 'https://wassap.wehdah.my');
    config()->set('diwan.whatsapp.provisioning_secret', 'provisioning-test');
    config()->set('diwan.whatsapp.webhook_secret', 'whsec_'.str_repeat('w', 40));
    config()->set('diwan.whatsapp.webhook_url', 'https://spdm.example/api/webhooks/whatsapp');
    config()->set('diwan.whatsapp.instance_id', 'spdm-test');
    Http::preventStrayRequests();
    $this->service = app(WhatsAppIntegrationService::class);
    $this->mosque = makeMosque('MAM', 'mam');
});

it('auto-provision tenant dengan HMAC dan menyimpan API key secara encrypted', function () {
    Http::fake(function (Request $request) {
        $timestamp = $request->header('X-Diwan-Timestamp')[0] ?? '';
        $signature = $request->header('X-Diwan-Signature')[0] ?? '';
        $payload = $request->data();

        expect($request->url())->toBe('https://wassap.wehdah.my/internal/v1/tenants/provision')
            ->and($payload['externalId'])->toBe('spdm-test:mosque:'.$this->mosque->id)
            ->and($payload['organizationName'])->toBe($this->mosque->name)
            ->and($payload['apiKey'])->toMatch('/^sk_[a-z0-9]{40}$/')
            ->and($payload['webhookUrl'])->toBe('https://spdm.example/api/webhooks/whatsapp')
            ->and($payload['webhookSecret'])->toBe('whsec_'.str_repeat('w', 40))
            ->and($signature)->toBe('sha256='.hash_hmac('sha256', $timestamp.'.'.$request->body(), 'provisioning-test'));

        return Http::response([
            'success' => true,
            'data' => ['tenantId' => '7001', 'externalId' => $payload['externalId'], 'status' => 'active', 'maxDevices' => 2],
            'links' => ['sessions' => '/v1/sessions'],
        ]);
    });

    $integration = $this->service->provision($this->mosque);
    $rawKey = DB::table('whatsapp_integrations')->where('id', $integration->id)->value('api_key');

    expect($integration->enabled)->toBeTrue()
        ->and($integration->status)->toBe('linked')
        ->and($integration->gateway_tenant_id)->toBe('7001')
        ->and($integration->api_key)->toStartWith('sk_')
        ->and($rawKey)->not->toBe($integration->api_key)
        ->and($rawKey)->not->toContain(substr($integration->api_key, 3));
});

it('mengasingkan external id dan kunci API bagi dua tenant', function () {
    $man = makeMosque('MAN', 'man');
    $counter = 0;

    Http::fake(function (Request $request) use (&$counter) {
        $counter++;

        return Http::response([
            'success' => true,
            'data' => ['tenantId' => (string) (7000 + $counter), 'externalId' => $request->data()['externalId'], 'status' => 'active', 'maxDevices' => 2],
        ]);
    });

    $mamIntegration = $this->service->provision($this->mosque);
    $manIntegration = $this->service->provision($man);

    expect($mamIntegration->mosque_id)->toBe($this->mosque->id)
        ->and($manIntegration->mosque_id)->toBe($man->id)
        ->and($mamIntegration->external_id)->not->toBe($manIntegration->external_id)
        ->and($mamIntegration->api_key)->not->toBe($manIntegration->api_key)
        ->and(WhatsAppIntegration::forMosque($this->mosque)->pluck('id')->all())->toBe([$mamIntegration->id])
        ->and(WhatsAppIntegration::forMosque($man)->pluck('id')->all())->toBe([$manIntegration->id]);
});

it('pair QR dan sync status hanya menggunakan kunci tenant sendiri', function () {
    $integration = WhatsAppIntegration::query()->create([
        'mosque_id' => $this->mosque->id,
        'external_id' => 'spdm-test:mosque:'.$this->mosque->id,
        'gateway_tenant_id' => '7001',
        'api_key' => 'sk_'.str_repeat('a', 40),
        'api_key_prefix' => 'sk_aaaaaaaa...',
        'enabled' => true,
        'status' => 'linked',
    ]);

    Http::fake([
        'https://wassap.wehdah.my/v1/sessions' => Http::response([
            'success' => true,
            'data' => ['session_id' => 'sess_mam', 'status' => 'pending', 'qr_code_base64' => 'QRDATA'],
        ], 201),
        'https://wassap.wehdah.my/v1/sessions/sess_mam/status' => Http::response([
            'success' => true,
            'data' => ['session_id' => 'sess_mam', 'status' => 'connected', 'phone' => '60123456789'],
        ]),
    ]);

    $pair = $this->service->beginPairing($this->mosque, 'Telefon Pejabat');
    $synced = $this->service->syncStatus($this->mosque);

    expect($pair['qr_code_base64'])->toBe('QRDATA')
        ->and($synced->session_id)->toBe('sess_mam')
        ->and($synced->status)->toBe('connected')
        ->and($synced->phone)->toBe('60123456789')
        ->and($this->mosque->fresh()->wa_session_id)->toBe('sess_mam')
        ->and($this->mosque->fresh()->wa_number)->toBe('60123456789');

    Http::assertSent(fn (Request $request) => $request->header('X-API-Key')[0] === $integration->api_key);
});

it('gateway menolak sesi tenant lain dan menghantar melalui API key tenant tepat', function () {
    $integration = WhatsAppIntegration::query()->create([
        'mosque_id' => $this->mosque->id,
        'external_id' => 'spdm-test:mosque:'.$this->mosque->id,
        'gateway_tenant_id' => '7001',
        'api_key' => 'sk_'.str_repeat('b', 40),
        'enabled' => true,
        'status' => 'connected',
        'session_id' => 'sess_mam',
        'phone' => '60111111111',
    ]);
    Http::fake(['https://wassap.wehdah.my/v1/messages/send' => Http::response(['success' => true, 'data' => ['message_id' => 'MSG1']])]);

    $gateway = app(WhatsAppGateway::class);
    expect($gateway->send('sess_tenant_lain', '60199999999', 'uji', $this->mosque->id))->toBeFalse()
        ->and($gateway->send('sess_mam', '60199999999', 'uji', $this->mosque->id))->toBeTrue();

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => $request->header('X-API-Key')[0] === $integration->api_key
        && $request['session_id'] === 'sess_mam');
});

it('provisioning gagal tertutup apabila rahsia belum dikonfigurasi', function () {
    config()->set('diwan.whatsapp.provisioning_secret', null);

    $integration = $this->service->provision($this->mosque);

    expect($integration->enabled)->toBeFalse()
        ->and($integration->status)->toBe('error')
        ->and($integration->last_error)->toContain('belum dikonfigurasi');
    Http::assertNothingSent();
});

it('provisioning gagal tertutup apabila webhook tidak selamat', function () {
    config()->set('diwan.whatsapp.webhook_url', 'http://spdm.example/api/webhooks/whatsapp');

    $integration = $this->service->provision($this->mosque);

    expect($integration->enabled)->toBeFalse()
        ->and($integration->status)->toBe('error')
        ->and($integration->last_error)->toContain('webhook');
    Http::assertNothingSent();
});

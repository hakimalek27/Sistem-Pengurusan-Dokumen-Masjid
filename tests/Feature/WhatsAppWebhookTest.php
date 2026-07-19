<?php

use App\Enums\SourceChannel;
use App\Models\Record;
use App\Models\WhatsAppIntegration;
use App\Services\WhatsAppGateway;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    Cache::flush();
    Storage::fake(config('diwan.storage_disk'));
    Notification::fake();
    config()->set('diwan.whatsapp.webhook_secret', 'rahsia-ujian');

    $this->mam = makeMosque('MAM', 'mam'); // wa_session_id = 'mam'
    $this->man = makeMosque('MAN', 'man'); // wa_session_id = 'man'
    $this->member = makeMember($this->mam, 'kerani', 'k@mam.test', ['phone_wa' => '60110000001']);
    $this->mam->users()->updateExistingPivot($this->member->id, ['phone_wa' => '60110000001', 'notify_whatsapp' => true]);
    foreach ([$this->mam, $this->man] as $mosque) {
        WhatsAppIntegration::query()->create([
            'mosque_id' => $mosque->id,
            'external_id' => 'test:'.$mosque->id,
            'gateway_tenant_id' => 'gateway:'.$mosque->id,
            'api_key' => 'sk_'.str_repeat((string) $mosque->id, 40),
            'enabled' => true,
            'status' => 'connected',
            'session_id' => $mosque->slug,
        ]);
    }

    // Spy gateway untuk mengesan ack/balasan.
    $this->gateway = Mockery::spy(WhatsAppGateway::class);
    app()->instance(WhatsAppGateway::class, $this->gateway);
});

function postWebhook(array $payload, ?string $secret = 'rahsia-ujian'): TestResponse
{
    $raw = json_encode($payload);
    $signature = hash_hmac('sha256', $raw, (string) $secret);

    return test()->call('POST', '/api/webhooks/whatsapp', [], [], [], [
        'HTTP_X_DIWAN_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $raw);
}

function postGatewayWebhook(array $data, string $event = 'message.received', ?string $secret = 'rahsia-ujian'): TestResponse
{
    $payload = ['event' => $event, 'id' => 'evt-'.uniqid(), 'timestamp' => now()->toIso8601String(), 'data' => $data];
    $raw = json_encode($payload);
    $signature = 'sha256='.hash_hmac('sha256', $raw, (string) $secret);

    return test()->call('POST', '/api/webhooks/whatsapp', [], [], [], [
        'HTTP_X_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $raw);
}

function waPayload(array $override = []): array
{
    return array_merge([
        'session' => 'mam',
        'from' => '60110000001',
        'type' => 'image',
        'media_base64' => base64_encode('bait-imej-palsu'),
        'media_mime' => 'image/jpeg',
        'filename' => 'surat.jpg',
        'caption' => 'spdm',
        'message_id' => 'MSG'.uniqid(),
        'timestamp' => 1720252800,
    ], $override);
}

it('menolak HMAC salah dengan 401 tanpa maklumat', function () {
    postWebhook(waPayload(), secret: 'salah')->assertStatus(401);

    expect(Record::query()->count())->toBe(0);
});

it('fail-closed jika rahsia webhook WhatsApp belum dikonfigurasi', function () {
    config()->set('diwan.whatsapp.webhook_secret', '');

    postWebhook(waPayload(), secret: '')->assertStatus(401);

    expect(Record::query()->count())->toBe(0);
});

it('sesi tidak dikenali → 200 + tiada rekod', function () {
    postWebhook(waPayload(['session' => 'tiada-sesi']))->assertOk();

    expect(Record::query()->count())->toBe(0);
});

it('nombor asing tanpa kata kunci → SENYAP sepenuhnya (tiada balasan, tiada gelung §11.1)', function () {
    $asing = '60174632511'; // orang luar tiada akaun — simulasi auto-reply/pengirim berulang

    for ($i = 0; $i < 6; $i++) {
        postWebhook(waPayload([
            'from' => $asing,
            'message_id' => 'ASING'.$i,
            'media_base64' => null,
            'type' => 'text',
            'caption' => 'assalamualaikum, ini mesej panjang tanpa kata kunci intake',
        ]))->assertOk();
    }

    // Diwan LANGSUNG tidak balas nombor yang tak hantar kata kunci tunggal — punca spam dihapus.
    $this->gateway->shouldNotHaveReceived('send');
    expect(Record::query()->count())->toBe(0);
});

it('orang luar hantar kata kunci + dokumen → submission awam diterima (creator null)', function () {
    postWebhook(waPayload(['from' => '60199999999']))->assertOk(); // kapsyen 'spdm' + media

    $record = Record::query()->where('mosque_id', $this->mam->id)->first();
    expect($record)->not->toBeNull()
        ->and($record->created_by)->toBeNull()               // submission tanpa akaun
        ->and($record->source_meta['from'])->toBe('60199999999');

    $this->gateway->shouldHaveReceived('send')
        ->withArgs(fn ($session, $to, $message, $mosqueId, $userId, $type) => $type === 'wa_ack');
});

it('mod ahli-sahaja (allow_public_intake=false): orang luar + kata kunci + dokumen → SENYAP, tiada rekod', function () {
    config()->set('diwan.whatsapp.allow_public_intake', false);

    postWebhook(waPayload(['from' => '60199999999']))->assertOk();

    expect(Record::query()->count())->toBe(0);
    $this->gateway->shouldNotHaveReceived('send');
});

it('imej daripada ahli sah → peti masuk + ack sebut nama masjid', function () {
    postWebhook(waPayload())->assertOk();

    $record = Record::query()->where('mosque_id', $this->mam->id)->first();
    expect($record)->not->toBeNull()
        ->and($record->status->value)->toBe('peti_masuk')
        ->and($record->source_channel)->toBe(SourceChannel::WhatsApp)
        ->and($record->source_meta['from'])->toBe('60110000001');

    $this->gateway->shouldHaveReceived('send')
        ->withArgs(fn ($session, $to, $message) => $session === 'mam'
            && str_contains($message, 'Masjid MAM')
            && str_contains($message, 'Diterima'));
});

it('menerima envelope dan tandatangan webhook sebenar gateway termasuk media', function () {
    postGatewayWebhook([
        'session_id' => 'mam',
        'message_id' => 'GATEWAY-1',
        'from_phone' => '60110000001',
        'chat_jid' => '60110000001@s.whatsapp.net',
        'text' => '',
        'from_me' => false,
        'is_group' => false,
        'media' => [
            'type' => 'document',
            'mime_type' => 'application/pdf',
            'filename' => 'surat-gateway.pdf',
            'caption' => 'SPDM surat mesyuarat',
            'base64' => base64_encode('%PDF-bait-ujian'),
        ],
    ])->assertOk();

    $record = Record::query()->where('mosque_id', $this->mam->id)->first();
    expect($record)->not->toBeNull()
        ->and($record->source_meta['from'])->toBe('60110000001')
        ->and($record->source_meta['caption'])->toBe('SPDM surat mesyuarat');
});

it('menyokong aliran dua langkah spdm kemudian dokumen dalam sesi tenant sama', function () {
    postGatewayWebhook([
        'session_id' => 'mam',
        'message_id' => 'ARM-MAM',
        'from_phone' => '60110000001',
        'chat_jid' => '60110000001@s.whatsapp.net',
        'text' => 'spdm',
    ])->assertOk();

    $this->gateway->shouldHaveReceived('send')
        ->withArgs(fn ($session, $to, $message) => $session === 'mam' && str_contains($message, '10 minit'));

    postGatewayWebhook([
        'session_id' => 'mam',
        'message_id' => 'DOC-MAM',
        'from_phone' => '60110000001',
        'chat_jid' => '60110000001@s.whatsapp.net',
        'text' => '',
        'media' => [
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'filename' => 'selepas-keyword.jpg',
            'caption' => '',
            'base64' => base64_encode('imej-selepas-keyword'),
        ],
    ])->assertOk();

    expect(Record::forMosque($this->mam)->count())->toBe(1)
        ->and(Record::forMosque($this->man)->count())->toBe(0);
});

it('slot upload WhatsApp tidak boleh digunakan oleh nombor atau tenant lain', function () {
    $manMember = makeMember($this->man, 'kerani', 'man2@test.local', ['phone_wa' => '60110000002']);
    $this->man->users()->updateExistingPivot($manMember->id, ['phone_wa' => '60110000002']);

    postGatewayWebhook([
        'session_id' => 'mam', 'message_id' => 'ARM-ONLY-MAM',
        'from_phone' => '60110000001', 'text' => 'spdm',
    ])->assertOk();
    postGatewayWebhook([
        'session_id' => 'man', 'message_id' => 'DOC-WRONG-TENANT',
        'from_phone' => '60110000002',
        'media' => ['mime_type' => 'image/jpeg', 'filename' => 'x.jpg', 'caption' => '', 'base64' => base64_encode('x')],
    ])->assertOk();

    expect(Record::query()->count())->toBe(0);
});

it('mengabaikan event lain, group, echo sendiri dan media tanpa kata kunci', function () {
    $base = [
        'session_id' => 'mam',
        'message_id' => 'IGNORED',
        'from_phone' => '60110000001',
        'media' => ['mime_type' => 'image/jpeg', 'caption' => 'bukan kata kunci', 'base64' => base64_encode('x')],
    ];

    postGatewayWebhook($base, 'message.status')->assertOk();
    postGatewayWebhook(array_merge($base, ['message_id' => 'GROUP', 'is_group' => true]))->assertOk();
    postGatewayWebhook(array_merge($base, ['message_id' => 'SELF', 'from_me' => true]))->assertOk();
    postGatewayWebhook(array_merge($base, ['message_id' => 'NO-KEYWORD']))->assertOk();

    expect(Record::query()->count())->toBe(0);
});

it('message_id ulang → idempotensi, tiada rekod pendua', function () {
    $payload = waPayload(['message_id' => 'SAMA-123']);

    postWebhook($payload)->assertOk();
    postWebhook($payload)->assertOk();

    expect(Record::query()->where('mosque_id', $this->mam->id)->count())->toBe(1);
});

it('message_id sama pada dua sesi tenant tidak saling menyekat', function () {
    $manMember = makeMember($this->man, 'kerani', 'k@man.test', ['phone_wa' => '60110000002']);
    $this->man->users()->updateExistingPivot($manMember->id, ['phone_wa' => '60110000002', 'notify_whatsapp' => true]);

    postWebhook(waPayload(['message_id' => 'SAMA-RENTAS', 'session' => 'mam', 'from' => '60110000001']))->assertOk();
    postWebhook(waPayload(['message_id' => 'SAMA-RENTAS', 'session' => 'man', 'from' => '60110000002']))->assertOk();

    expect(Record::query()->where('mosque_id', $this->mam->id)->count())->toBe(1)
        ->and(Record::query()->where('mosque_id', $this->man->id)->count())->toBe(1);
});

it('dokumen format tidak disokong (.zip) → tolak dengan mesej format, tiada rekod', function () {
    postWebhook(waPayload([
        'filename' => 'jahat.zip',
        'media_mime' => 'application/zip',
        'caption' => 'spdm',
    ]))->assertOk();

    expect(Record::query()->count())->toBe(0);
    $this->gateway->shouldHaveReceived('send')
        ->withArgs(fn ($session, $to, $message) => str_contains($message, 'Format fail tidak disokong'));
});

it('kuota penuh → balasan tolak, tiada rekod', function () {
    $this->mam->update(['storage_used_bytes' => $this->mam->effectiveQuotaBytes()]);

    postWebhook(waPayload())->assertOk();

    expect(Record::query()->count())->toBe(0);
    $this->gateway->shouldHaveReceived('send')
        ->withArgs(fn ($session, $to, $message) => str_contains($message, 'Kuota storan masjid penuh'));
});

it('intake dimatikan → balasan tolak, tiada rekod', function () {
    $this->mam->update(['settings' => array_merge($this->mam->settings, ['wa_intake_enabled' => false])]);

    postWebhook(waPayload())->assertOk();

    expect(Record::query()->count())->toBe(0);
});

it('ahli MAM hantar kata kunci+dokumen ke sesi MAN → SENYAP, tiada rekod (isolasi §18.37)', function () {
    // $this->member ahli MAM (60110000001) — pengguna berdaftar masjid LAIN. Walau intake
    // awam dihidupkan, nombor berdaftar di tenant lain TIDAK boleh submit ke MAN → senyap.
    postWebhook(waPayload(['session' => 'man']))->assertOk();

    expect(Record::query()->count())->toBe(0);
    $this->gateway->shouldNotHaveReceived('send');
});

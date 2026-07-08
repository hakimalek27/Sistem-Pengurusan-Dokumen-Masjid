<?php

use App\Enums\SourceChannel;
use App\Models\Record;
use App\Services\WhatsAppGateway;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    Storage::fake(config('diwan.storage_disk'));
    Notification::fake();
    config()->set('diwan.whatsapp.webhook_secret', 'rahsia-ujian');

    $this->mam = makeMosque('MAM', 'mam'); // wa_session_id = 'mam'
    $this->man = makeMosque('MAN', 'man'); // wa_session_id = 'man'
    $this->member = makeMember($this->mam, 'kerani', 'k@mam.test', ['phone_wa' => '60110000001']);

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

it('sesi tidak dikenali → 200 + tiada rekod', function () {
    postWebhook(waPayload(['session' => 'tiada-sesi']))->assertOk();

    expect(Record::query()->count())->toBe(0);
});

it('penghantar BUKAN ahli masjid sesi itu → balasan tolak, tiada rekod', function () {
    postWebhook(waPayload(['from' => '60199999999']))->assertOk();

    expect(Record::query()->count())->toBe(0);
    $this->gateway->shouldHaveReceived('send')
        ->withArgs(fn ($session, $to, $message) => str_contains($message, 'tidak berdaftar sebagai ahli'));
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

it('message_id ulang → idempotensi, tiada rekod pendua', function () {
    $payload = waPayload(['message_id' => 'SAMA-123']);

    postWebhook($payload)->assertOk();
    postWebhook($payload)->assertOk();

    expect(Record::query()->where('mosque_id', $this->mam->id)->count())->toBe(1);
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

it('ahli MAM hantar ke sesi MAN (bukan ahli MAN) → tolak, tiada rekod (§18.37)', function () {
    // $this->member ahli MAM sahaja; hantar ke sesi 'man'
    postWebhook(waPayload(['session' => 'man']))->assertOk();

    expect(Record::query()->count())->toBe(0);
    $this->gateway->shouldHaveReceived('send')
        ->withArgs(fn ($session, $to, $message) => $session === 'man'
            && str_contains($message, 'tidak berdaftar sebagai ahli'));
});

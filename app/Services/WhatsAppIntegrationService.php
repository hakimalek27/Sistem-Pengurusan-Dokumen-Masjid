<?php

namespace App\Services;

use App\Models\Mosque;
use App\Models\WhatsAppIntegration;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/** Onboarding, pairing dan sync gateway WhatsApp tanpa keluar dari SPDM. */
class WhatsAppIntegrationService
{
    private const API_KEY_LENGTH = 40;

    public function integrationFor(Mosque $mosque): WhatsAppIntegration
    {
        return WhatsAppIntegration::query()->forMosque($mosque)->firstOrCreate(
            ['mosque_id' => $mosque->id],
            ['external_id' => $this->externalId($mosque)],
        );
    }

    public function provision(Mosque $mosque): WhatsAppIntegration
    {
        $integration = $this->integrationFor($mosque);
        $plainKey = $integration->api_key ?: 'sk_'.Str::lower(Str::random(self::API_KEY_LENGTH));

        // Simpan dahulu supaya retry selepas timeout menggunakan kunci sama.
        $integration->forceFill([
            'api_key' => $plainKey,
            'api_key_prefix' => substr($plainKey, 0, 11).'...',
            'status' => 'provisioning',
            'last_error' => null,
        ])->save();

        $payload = [
            'externalId' => $integration->external_id,
            'organizationName' => $mosque->name,
            'apiKey' => $plainKey,
            'webhookUrl' => (string) config('diwan.whatsapp.webhook_url'),
            'webhookSecret' => (string) config('diwan.whatsapp.webhook_secret'),
        ];
        $raw = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $secret = (string) config('diwan.whatsapp.provisioning_secret');

        if ($secret === '') {
            return $this->fail($integration, 'Rahsia provisioning WhatsApp belum dikonfigurasi.');
        }
        if (! str_starts_with($payload['webhookUrl'], 'https://') || strlen($payload['webhookSecret']) < 32) {
            return $this->fail($integration, 'URL HTTPS atau rahsia webhook WhatsApp belum dikonfigurasi dengan selamat.');
        }

        try {
            $response = $this->baseRequest()
                ->withHeaders([
                    'X-Diwan-Timestamp' => $timestamp,
                    'X-Diwan-Signature' => 'sha256='.hash_hmac('sha256', $timestamp.'.'.$raw, $secret),
                ])
                ->withBody($raw, 'application/json')
                ->post('/internal/v1/tenants/provision');
        } catch (\Throwable $e) {
            return $this->fail($integration, 'Gateway tidak dapat dihubungi.');
        }

        if (! $response->successful() || $response->json('success') !== true) {
            return $this->fail($integration, (string) ($response->json('error') ?: 'Provisioning gateway gagal.'));
        }

        $integration->forceFill([
            'gateway_tenant_id' => (string) $response->json('data.tenantId'),
            'enabled' => true,
            'status' => 'linked',
            'last_synced_at' => now(),
            'last_error' => null,
        ])->save();

        return $integration->fresh();
    }

    /** @return array<string, mixed> */
    public function beginPairing(Mosque $mosque, string $deviceName, ?string $phone = null): array
    {
        $integration = $this->requireLinked($mosque);
        $payload = ['device_name' => $deviceName];
        if (filled($phone)) {
            $payload['phone'] = $this->normalizePhone($phone);
        }

        $data = $this->tenantRequest($integration)->post('/v1/sessions', $payload);
        $result = $this->responseData($integration, $data, 'Gagal memulakan pairing WhatsApp.');

        $integration->forceFill([
            'session_id' => $result['session_id'] ?? null,
            'status' => $result['status'] ?? 'pending',
            'last_synced_at' => now(),
            'last_error' => null,
        ])->save();

        return $result;
    }

    /** @return array<string, mixed> */
    public function refreshQr(Mosque $mosque): array
    {
        $integration = $this->requireSession($mosque);
        $response = $this->tenantRequest($integration)->get('/v1/sessions/'.$integration->session_id.'/qr');

        return $this->responseData($integration, $response, 'Gagal mendapatkan QR WhatsApp.');
    }

    public function syncStatus(Mosque $mosque): WhatsAppIntegration
    {
        $integration = $this->requireSession($mosque);
        $response = $this->tenantRequest($integration)->get('/v1/sessions/'.$integration->session_id.'/status');
        $result = $this->responseData($integration, $response, 'Gagal menyegerakkan status WhatsApp.');

        $integration->forceFill([
            'status' => $result['status'] ?? $integration->status,
            'phone' => $result['phone'] ?? $integration->phone,
            'last_synced_at' => now(),
            'last_error' => null,
        ])->save();

        if ($integration->status === 'connected') {
            $mosque->forceFill([
                'wa_session_id' => $integration->session_id,
                'wa_number' => $integration->phone,
            ])->save();
        }

        return $integration->fresh();
    }

    public function setEnabled(Mosque $mosque, bool $enabled): WhatsAppIntegration
    {
        $integration = $this->integrationFor($mosque);
        $integration->update(['enabled' => $enabled]);

        return $integration->fresh();
    }

    protected function requireLinked(Mosque $mosque): WhatsAppIntegration
    {
        $integration = $this->integrationFor($mosque);
        if (! $integration->enabled || blank($integration->api_key) || blank($integration->gateway_tenant_id)) {
            throw new RuntimeException('Aktifkan dan pautkan integrasi WhatsApp dahulu.');
        }

        return $integration;
    }

    protected function requireSession(Mosque $mosque): WhatsAppIntegration
    {
        $integration = $this->requireLinked($mosque);
        if (blank($integration->session_id)) {
            throw new RuntimeException('Tiada sesi WhatsApp untuk disegerakkan.');
        }

        return $integration;
    }

    protected function tenantRequest(WhatsAppIntegration $integration): PendingRequest
    {
        return $this->baseRequest()->withHeaders(['X-API-Key' => $integration->api_key]);
    }

    protected function baseRequest(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('diwan.whatsapp.gateway_url'), '/'))
            ->connectTimeout(3)
            ->timeout((int) config('diwan.whatsapp.timeout', 8))
            ->acceptJson()
            ->asJson();
    }

    /** @return array<string, mixed> */
    protected function responseData(WhatsAppIntegration $integration, Response $response, string $fallback): array
    {
        if (! $response->successful() || $response->json('success') !== true) {
            $message = (string) ($response->json('error') ?: $fallback);
            $this->fail($integration, $message);
            throw new RuntimeException($message);
        }

        return (array) $response->json('data', []);
    }

    protected function fail(WhatsAppIntegration $integration, string $message): WhatsAppIntegration
    {
        $integration->forceFill(['status' => 'error', 'last_error' => mb_substr($message, 0, 1000)])->save();

        return $integration->fresh();
    }

    protected function externalId(Mosque $mosque): string
    {
        return (string) config('diwan.whatsapp.instance_id', 'spdm').':mosque:'.$mosque->id;
    }

    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        if (str_starts_with($digits, '0')) {
            $digits = '60'.substr($digits, 1);
        }
        if (strlen($digits) < 8 || strlen($digits) > 15) {
            throw new RuntimeException('Nombor WhatsApp tidak sah.');
        }

        return $digits;
    }
}

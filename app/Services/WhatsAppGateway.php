<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\WhatsAppIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * §11.1 — Adapter gateway WhatsApp whatsmeow. Diasingkan sepenuhnya di sini
 * (WHATSAPP_DRIVER=gateway|log). Jika API sebenar gateway berbeza, HANYA fail ini diubah.
 * Penghantaran mengikut SESI masjid (nombor masjid yang menghantar).
 */
class WhatsAppGateway
{
    /** Hantar mesej melalui sesi masjid. Pulangkan true jika berjaya. */
    public function send(string $session, string $to, string $message, ?int $mosqueId = null, ?int $userId = null, string $type = 'whatsapp'): bool
    {
        if (config('diwan.whatsapp.driver', 'gateway') === 'log') {
            Log::info('[WA:log] session='.$session.' to='.$to.' | '.$message);
            $this->record($mosqueId, $userId, $to, $type, 'sent');

            return true;
        }

        try {
            $integration = $mosqueId
                ? WhatsAppIntegration::query()->forMosque($mosqueId)->first()
                : null;

            if (! $integration?->isReady() || ! hash_equals((string) $integration->session_id, $session)) {
                $this->record($mosqueId, $userId, $to, $type, 'failed', 'integrasi/sesi tenant tidak sah atau tidak bersambung');

                return false;
            }

            $response = Http::baseUrl(rtrim((string) config('diwan.whatsapp.gateway_url'), '/'))
                ->connectTimeout(3)
                ->timeout(config('diwan.whatsapp.timeout', 8))
                ->acceptJson()
                ->asJson()
                ->withHeaders(['X-API-Key' => $integration->api_key])
                ->post('/v1/messages/send', [
                    'session_id' => $integration->session_id,
                    'to' => $to,
                    'message' => $message,
                ]);

            $ok = $response->successful() && $response->json('success') === true;
            $this->record($mosqueId, $userId, $to, $type, $ok ? 'sent' : 'failed', $ok ? null : 'HTTP '.$response->status());

            return $ok;
        } catch (\Throwable $e) {
            $this->record($mosqueId, $userId, $to, $type, 'failed', $e->getMessage());

            return false;
        }
    }

    /** §11.1 — Pemantauan kesihatan gateway. */
    public function ping(): bool
    {
        if (config('diwan.whatsapp.driver', 'gateway') === 'log') {
            return true;
        }

        $base = rtrim((string) config('diwan.whatsapp.gateway_url'), '/');

        try {
            if (Http::timeout(5)->get($base.'/health')->successful()) {
                return true;
            }

            return Http::timeout(5)->get($base.'/')->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function record(?int $mosqueId, ?int $userId, string $to, string $type, string $status, ?string $error = null): void
    {
        NotificationLog::query()->create([
            'mosque_id' => $mosqueId,
            'user_id' => $userId,
            'channel' => 'whatsapp',
            'to' => $to,
            'notification_type' => $type,
            'status' => $status,
            'error' => $error,
        ]);
    }
}

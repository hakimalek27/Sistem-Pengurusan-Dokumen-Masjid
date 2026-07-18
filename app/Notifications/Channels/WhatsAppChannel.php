<?php

namespace App\Notifications\Channels;

use App\Jobs\SendWhatsAppJob;
use App\Models\NotificationLog;
use App\Models\WhatsAppIntegration;
use App\Services\WhatsAppRecipientResolver;
use Illuminate\Notifications\Notification;

/**
 * §14 / §11.1 — Saluran WhatsApp. Menghantar MELALUI SESI MASJID berkaitan.
 * Masjid tanpa wa_session_id → dilangkau senyap + dilog (e-mel tetap sampai).
 */
class WhatsAppChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $payload = $notification->toWhatsApp($notifiable);
        $mosqueId = $payload['mosque_id'] ?? null;
        $to = app(WhatsAppRecipientResolver::class)->resolve($notifiable, $mosqueId);

        if (! $to) {
            return;
        }

        $integration = $mosqueId
            ? WhatsAppIntegration::query()->forMosque($mosqueId)->first()
            : WhatsAppIntegration::query()->platform()->first();
        $session = $integration?->isReady() ? $integration->session_id : null;

        if (! $session) {
            NotificationLog::query()->create([
                'mosque_id' => $mosqueId,
                'user_id' => $notifiable->id ?? null,
                'channel' => 'whatsapp',
                'to' => $to,
                'notification_type' => class_basename($notification),
                'status' => 'failed',
                'error' => 'integrasi WhatsApp tenant tidak aktif/bersambung — WA dilangkau (e-mel dihantar)',
            ]);

            return;
        }

        SendWhatsAppJob::dispatch(
            $session,
            $to,
            $payload['message'],
            $mosqueId,
            $notifiable->id ?? null,
            class_basename($notification),
        );
    }
}

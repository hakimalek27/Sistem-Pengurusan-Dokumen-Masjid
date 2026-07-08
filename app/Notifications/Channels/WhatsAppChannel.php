<?php

namespace App\Notifications\Channels;

use App\Jobs\SendWhatsAppJob;
use App\Models\NotificationLog;
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
        $to = $notifiable->phone_wa ?? null;

        if (! $to) {
            return;
        }

        $session = $payload['session'] ?? null;

        if (! $session) {
            NotificationLog::query()->create([
                'mosque_id' => $payload['mosque_id'] ?? null,
                'user_id' => $notifiable->id ?? null,
                'channel' => 'whatsapp',
                'to' => $to,
                'notification_type' => class_basename($notification),
                'status' => 'failed',
                'error' => 'tiada wa_session_id — WA dilangkau (e-mel dihantar)',
            ]);

            return;
        }

        SendWhatsAppJob::dispatch(
            $session,
            $to,
            $payload['message'],
            $payload['mosque_id'] ?? null,
            $notifiable->id ?? null,
            class_basename($notification),
        );
    }
}

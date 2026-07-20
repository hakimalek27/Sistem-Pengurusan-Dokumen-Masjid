<?php

namespace App\Notifications\Channels;

use App\Models\NotificationLog;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel as BaseTelegramChannel;

/**
 * §14 / §11.2 — Bungkus saluran Telegram vendor untuk:
 *  (1) merekod NotificationLog (sent/failed) seperti WhatsAppChannel — sebelum ini
 *      penghantaran Telegram tidak dilog langsung (kegagalan halimunan);
 *  (2) MENELAN ralat hantar supaya kegagalan Telegram TIDAK memecahkan aliran
 *      notifikasi (mail/WhatsApp sudah dihantar). Vendor melontar
 *      CouldNotSendNotification yang boleh merebak ke permintaan web.
 */
class TelegramChannel extends BaseTelegramChannel
{
    public function send(mixed $notifiable, Notification $notification): ?array
    {
        if (! method_exists($notification, 'toTelegram')) {
            return null;
        }

        $chatId = (string) ($notifiable->telegram_chat_id ?? '');
        $mosqueId = method_exists($notification, 'toWhatsApp')
            ? ($notification->toWhatsApp($notifiable)['mosque_id'] ?? null)
            : null;

        try {
            $response = parent::send($notifiable, $notification);

            // null = dilangkau oleh vendor (tiada penerima sah) — jangan log.
            if ($response !== null) {
                $this->log($notifiable, $notification, $mosqueId, $chatId, 'sent', null);
            }

            return $response;
        } catch (\Throwable $e) {
            $this->log($notifiable, $notification, $mosqueId, $chatId, 'failed', mb_substr($e->getMessage(), 0, 500));

            return null; // jangan biar Telegram memecahkan penghantaran saluran lain
        }
    }

    protected function log(object $notifiable, Notification $notification, ?int $mosqueId, string $to, string $status, ?string $error): void
    {
        NotificationLog::query()->create([
            'mosque_id' => $mosqueId,
            'user_id' => $notifiable->id ?? null,
            'channel' => 'telegram',
            'to' => $to,
            'notification_type' => class_basename($notification),
            'status' => $status,
            'error' => $error,
        ]);
    }
}

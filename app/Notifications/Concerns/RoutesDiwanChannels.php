<?php

namespace App\Notifications\Concerns;

use App\Notifications\Channels\WhatsAppChannel;
use NotificationChannels\Telegram\TelegramChannel;

/**
 * §14 — Saluran per pengguna: mail (jika notify_email) + whatsapp (jika notify_whatsapp &&
 * phone_wa) + telegram (jika notify_telegram && telegram_chat_id). E-mel lalai ON.
 */
trait RoutesDiwanChannels
{
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->notify_email ?? true) {
            $channels[] = 'mail';
        }

        if (($notifiable->notify_whatsapp ?? false) && ($notifiable->phone_wa ?? null)) {
            $channels[] = WhatsAppChannel::class;
        }

        if (($notifiable->notify_telegram ?? false) && ($notifiable->telegram_chat_id ?? null)) {
            $channels[] = TelegramChannel::class;
        }

        return $channels ?: ['mail'];
    }
}

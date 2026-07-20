<?php

namespace App\Notifications;

use App\Notifications\Channels\TelegramChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

// §14 [GatewayDown → superadmin; e-mel + Telegram SAHAJA]
class GatewayDownNotification extends Notification
{
    public function __construct(public string $since) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if (($notifiable->notify_telegram ?? false) && ($notifiable->telegram_chat_id ?? null)) {
            $channels[] = TelegramChannel::class;
        }

        return $channels;
    }

    protected function message(): string
    {
        return "⚠️ Gateway WhatsApp gagal dihubungi sejak {$this->since}. Notifikasi WA dijeda; e-mel/Telegram beroperasi.";
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Diwan — Gateway WhatsApp gagal dihubungi')
            ->line($this->message());
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        return TelegramMessage::create($this->message())->to($notifiable->telegram_chat_id);
    }
}

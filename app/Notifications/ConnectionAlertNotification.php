<?php

namespace App\Notifications;

use App\Notifications\Channels\WhatsAppChannel;
use App\Services\WhatsAppRecipientResolver;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

/**
 * §14 — Alert kesihatan sambungan kepada superadmin/admin (sesi WhatsApp
 * terputus, IMAP gagal, dsb). Saluran: e-mel + Telegram; WhatsApp (melalui
 * SESI PLATFORM) hanya bila $viaPlatformWa (superadmin) — sesi masjid yang
 * terputus tidak boleh menghantar alert tentang dirinya sendiri.
 */
class ConnectionAlertNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $body,
        public bool $viaPlatformWa = false,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->notify_email ?? true) {
            $channels[] = 'mail';
        }

        if (($notifiable->notify_telegram ?? false) && ($notifiable->telegram_chat_id ?? null)) {
            $channels[] = TelegramChannel::class;
        }

        if ($this->viaPlatformWa) {
            $to = app(WhatsAppRecipientResolver::class)->resolve($notifiable, null);
            if ($to) {
                $channels[] = WhatsAppChannel::class;
            }
        }

        return $channels ?: ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Diwan — '.$this->title)
            ->line($this->body);
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        return TelegramMessage::create('⚠️ '.$this->title."\n".$this->body)
            ->to($notifiable->telegram_chat_id);
    }

    /** @return array{mosque_id: null, message: string} */
    public function toWhatsApp(object $notifiable): array
    {
        // mosque_id null → dihantar melalui sesi WhatsApp platform.
        return ['mosque_id' => null, 'message' => '⚠️ '.$this->title.' — '.$this->body];
    }
}

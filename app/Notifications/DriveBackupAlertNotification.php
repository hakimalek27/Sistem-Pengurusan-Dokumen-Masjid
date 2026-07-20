<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

// §4.6′ [DriveBackupAlert → superadmin; e-mel + Telegram SAHAJA]
class DriveBackupAlertNotification extends Notification
{
    public function __construct(public string $reason) {}

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
        return "⚠️ Mirror Google Drive gagal: {$this->reason}. Backup COS + pangkalan data KEKAL berfungsi. "
            .'Sila semak /admin → Tetapan Platform → Google Drive (sambung semula jika token dibatalkan atau kuota penuh).';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Diwan — Mirror Google Drive gagal')
            ->line($this->message());
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        return TelegramMessage::create($this->message())->to($notifiable->telegram_chat_id);
    }
}

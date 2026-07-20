<?php

namespace App\Notifications;

use App\Models\StorageOrder;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

// §14 [NewStorageOrder → superadmin; e-mel + Telegram SAHAJA]
class NewStorageOrderNotification extends Notification
{
    public function __construct(public StorageOrder $order) {}

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
        $o = $this->order->loadMissing('mosque');

        return "Pesanan storan baharu: {$o->mosque->name} — {$o->gb}GB ({$o->invoice_no}).";
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Diwan — Pesanan storan baharu')
            ->line($this->message())
            ->action('Semak di Panel', rtrim((string) config('app.url'), '/').'/admin');
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        return TelegramMessage::create($this->message())->to($notifiable->telegram_chat_id);
    }
}

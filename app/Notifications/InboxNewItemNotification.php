<?php

namespace App\Notifications;

use App\Models\Mosque;
use App\Notifications\Concerns\RoutesDiwanChannels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

// §14 [InboxNewItem]
class InboxNewItemNotification extends Notification
{
    use RoutesDiwanChannels;

    public function __construct(public Mosque $mosque, public int $count, public string $source) {}

    protected function appUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    public function waMessage(): string
    {
        return "📥 *Diwan · {$this->mosque->code}*\n"
            ."{$this->count} dokumen baharu dalam Peti Masuk ({$this->source}).\n"
            .'Sila klasifikasikan: '.$this->appUrl().'/app/'.$this->mosque->slug;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Diwan · {$this->mosque->code} — {$this->count} dokumen baharu dalam Peti Masuk")
            ->line("{$this->count} dokumen baharu dalam Peti Masuk ({$this->source}).")
            ->action('Klasifikasikan', $this->appUrl().'/app/'.$this->mosque->slug);
    }

    public function toWhatsApp(object $notifiable): array
    {
        return ['session' => $this->mosque->wa_session_id, 'mosque_id' => $this->mosque->id, 'message' => $this->waMessage()];
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        return TelegramMessage::create($this->waMessage())->to($notifiable->telegram_chat_id);
    }
}

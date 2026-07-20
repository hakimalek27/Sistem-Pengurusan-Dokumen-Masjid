<?php

namespace App\Notifications;

use App\Models\Mosque;
use App\Notifications\Concerns\HasMagicDeepLink;
use App\Notifications\Concerns\RoutesDiwanChannels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

// §14 [InboxNewItem]
class InboxNewItemNotification extends Notification
{
    use HasMagicDeepLink;
    use RoutesDiwanChannels;

    public function __construct(public Mosque $mosque, public int $count, public string $source) {}

    protected function appUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    public function waMessage(object $notifiable): string
    {
        return "📥 *Diwan · {$this->mosque->code}*\n"
            ."{$this->count} dokumen baharu dalam Peti Masuk ({$this->source}).\n"
            .'Sila klasifikasikan: '.$this->deepLink($notifiable, '/app/'.$this->mosque->slug.'/peti-masuk');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Diwan · {$this->mosque->code} — {$this->count} dokumen baharu dalam Peti Masuk")
            ->line("{$this->count} dokumen baharu dalam Peti Masuk ({$this->source}).")
            ->action('Klasifikasikan', $this->deepLink($notifiable, '/app/'.$this->mosque->slug.'/peti-masuk'));
    }

    public function toWhatsApp(object $notifiable): array
    {
        return ['session' => $this->mosque->wa_session_id, 'mosque_id' => $this->mosque->id, 'message' => $this->waMessage($notifiable)];
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        return TelegramMessage::create($this->waMessage($notifiable))->to($notifiable->telegram_chat_id);
    }
}

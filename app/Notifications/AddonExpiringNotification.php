<?php

namespace App\Notifications;

use App\Models\Mosque;
use App\Notifications\Concerns\RoutesDiwanChannels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

// §14 [AddonExpiring T-30/T-7 / AddonExpired]
class AddonExpiringNotification extends Notification
{
    use RoutesDiwanChannels;

    /** $when = 30 | 7 (hari) atau 'luput'. */
    public function __construct(public Mosque $mosque, public int $gb, public int|string $when, public string $expiryDate) {}

    protected function appUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    protected function phrase(): string
    {
        return $this->when === 'luput'
            ? 'telah luput'
            : "akan luput pada {$this->expiryDate}";
    }

    public function waMessage(): string
    {
        return "📦 *Diwan · {$this->mosque->code}* — Add-on storan {$this->gb}GB {$this->phrase()}. "
            .'Perbaharui: '.$this->appUrl().'/app/'.$this->mosque->slug;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Diwan · {$this->mosque->code} — Add-on storan {$this->phrase()}")
            ->line("Add-on storan {$this->gb}GB {$this->phrase()}.")
            ->action('Perbaharui', $this->appUrl().'/app/'.$this->mosque->slug);
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

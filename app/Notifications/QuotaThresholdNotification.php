<?php

namespace App\Notifications;

use App\Models\Mosque;
use App\Notifications\Concerns\RoutesDiwanChannels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

// §14 [QuotaThreshold → admin_masjid + superadmin]
class QuotaThresholdNotification extends Notification
{
    use RoutesDiwanChannels;

    public function __construct(public Mosque $mosque, public int $threshold, public float $usedGb, public float $quotaGb) {}

    protected function appUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    protected function blockedLine(): string
    {
        return $this->threshold >= 100 ? "\nMuat naik baharu DISEKAT sehingga storan ditambah." : '';
    }

    public function waMessage(): string
    {
        return "📦 *Diwan · {$this->mosque->code}* — Storan mencapai {$this->threshold}% ({$this->usedGb} / {$this->quotaGb} GB)."
            .$this->blockedLine()."\n"
            .'Tambah storan: '.$this->appUrl().'/app/'.$this->mosque->slug;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Diwan · {$this->mosque->code} — Storan mencapai {$this->threshold}%")
            ->line("Storan mencapai {$this->threshold}% ({$this->usedGb} / {$this->quotaGb} GB).".$this->blockedLine())
            ->action('Tambah Storan', $this->appUrl().'/app/'.$this->mosque->slug);
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

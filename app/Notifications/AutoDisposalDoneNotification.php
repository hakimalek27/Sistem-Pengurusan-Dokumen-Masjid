<?php

namespace App\Notifications;

use App\Models\Mosque;
use App\Notifications\Concerns\RoutesDiwanChannels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

// §14 [AutoDisposalDone → admin_masjid + superadmin]
class AutoDisposalDoneNotification extends Notification
{
    use RoutesDiwanChannels;

    public function __construct(public Mosque $mosque, public int $count, public int $years) {}

    public function waMessage(): string
    {
        return "🗄️ *Diwan · {$this->mosque->code}* — {$this->count} rekod telah dilupuskan automatik (cukup tempoh {$this->years} tahun).\n"
            .'Sijil pelupusan tersedia dalam sistem. Metadata rekod kekal tersimpan.';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Diwan · {$this->mosque->code} — Pelupusan automatik selesai")
            ->line("{$this->count} rekod telah dilupuskan automatik (cukup tempoh {$this->years} tahun).")
            ->line('Sijil pelupusan tersedia dalam sistem. Metadata rekod kekal tersimpan.');
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

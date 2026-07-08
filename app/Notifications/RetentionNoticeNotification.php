<?php

namespace App\Notifications;

use App\Models\Mosque;
use App\Notifications\Concerns\RoutesDiwanChannels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

// §14 [RetentionNotice T-90/T-30/T-7 → admin_masjid + superadmin]
class RetentionNoticeNotification extends Notification
{
    use RoutesDiwanChannels;

    public function __construct(public Mosque $mosque, public int $count, public int $days, public int $years) {}

    protected function appUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    public function waMessage(): string
    {
        return "🗄️ *Diwan · {$this->mosque->code}* — {$this->count} rekod akan mencapai tempoh simpanan {$this->years} tahun dalam {$this->days} hari "
            ."dan AKAN DIPADAM AUTOMATIK. Sila eksport untuk sandaran luar atau tetapkan pegangan:\n"
            .$this->appUrl().'/app/'.$this->mosque->slug;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Diwan · {$this->mosque->code} — Notis retensi ({$this->days} hari)")
            ->line("{$this->count} rekod akan mencapai tempoh simpanan {$this->years} tahun dalam {$this->days} hari dan AKAN DIPADAM AUTOMATIK.")
            ->line('Sila eksport untuk sandaran luar atau tetapkan pegangan (Legal Hold).')
            ->action('Urus Retensi', $this->appUrl().'/app/'.$this->mosque->slug);
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

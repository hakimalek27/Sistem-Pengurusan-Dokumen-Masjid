<?php

namespace App\Notifications;

use App\Models\Mosque;
use App\Models\StoredExport;
use App\Notifications\Concerns\RoutesDiwanChannels;
use App\Services\SecureDownloadUrl;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

// §14 [ExportReady]
class ExportReadyNotification extends Notification
{
    use RoutesDiwanChannels;

    public function __construct(public Mosque $mosque, public StoredExport $export) {}

    protected function link(): string
    {
        return app(SecureDownloadUrl::class)->export($this->export);
    }

    public function waMessage(): string
    {
        return "📦 *Diwan · {$this->mosque->code}* — Eksport ZIP anda sedia (pautan luput 14 hari): ".$this->link();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Diwan · {$this->mosque->code} — Eksport ZIP sedia")
            ->line('Eksport ZIP anda telah siap (pautan luput 14 hari).')
            ->action('Muat Turun', $this->link());
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

<?php

namespace App\Notifications;

use App\Models\Approval;
use App\Notifications\Concerns\HasMagicDeepLink;
use App\Notifications\Concerns\RoutesDiwanChannels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

// §14 [ApprovalRequested]
class ApprovalRequestedNotification extends Notification
{
    use HasMagicDeepLink;
    use RoutesDiwanChannels;

    public function __construct(public Approval $approval) {}

    protected function appUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    public function waMessage(object $notifiable): string
    {
        $a = $this->approval->loadMissing(['mosque', 'record', 'requestedBy']);
        $tajuk = mb_substr($a->record?->title ?? '', 0, 60);

        return "✍️ *Diwan · {$a->mosque->code}*\n"
            .'Permohonan kelulusan daripada '.($a->requestedBy?->name ?? '—').": \"{$tajuk}\".\n"
            .$this->deepLink($notifiable, '/r/'.$a->record?->ulid);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $a = $this->approval->loadMissing(['mosque', 'record', 'requestedBy']);
        $tajuk = mb_substr($a->record?->title ?? '', 0, 60);

        return (new MailMessage)
            ->subject("Diwan · {$a->mosque->code} — Permohonan kelulusan: {$tajuk}")
            ->line('Permohonan kelulusan daripada '.($a->requestedBy?->name ?? '—').": \"{$tajuk}\".")
            ->action('Semak', $this->deepLink($notifiable, '/r/'.$a->record?->ulid));
    }

    public function toWhatsApp(object $notifiable): array
    {
        return ['session' => $this->approval->mosque->wa_session_id, 'mosque_id' => $this->approval->mosque_id, 'message' => $this->waMessage($notifiable)];
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        return TelegramMessage::create($this->waMessage($notifiable))->to($notifiable->telegram_chat_id);
    }
}

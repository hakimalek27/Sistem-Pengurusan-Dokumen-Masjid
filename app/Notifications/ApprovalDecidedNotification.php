<?php

namespace App\Notifications;

use App\Enums\ApprovalStatus;
use App\Models\Approval;
use App\Notifications\Concerns\RoutesDiwanChannels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

// §14 [ApprovalDecided]
class ApprovalDecidedNotification extends Notification
{
    use RoutesDiwanChannels;

    public function __construct(public Approval $approval) {}

    protected function appUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    protected function badge(): string
    {
        return $this->approval->status === ApprovalStatus::Lulus ? '✅ Diluluskan' : '❌ Ditolak';
    }

    public function waMessage(): string
    {
        $a = $this->approval->loadMissing(['mosque', 'record', 'approver']);
        $tajuk = mb_substr($a->record?->title ?? '', 0, 60);

        return "{$this->badge()} — \"{$tajuk}\" oleh ".($a->approver?->name ?? '—').".\n"
            .$this->appUrl().'/r/'.$a->record?->ulid;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $a = $this->approval->loadMissing(['mosque', 'record', 'approver']);
        $tajuk = mb_substr($a->record?->title ?? '', 0, 60);

        return (new MailMessage)
            ->subject("Diwan · {$a->mosque->code} — Kelulusan: {$this->badge()}")
            ->line("{$this->badge()} — \"{$tajuk}\" oleh ".($a->approver?->name ?? '—').'.')
            ->action('Lihat', $this->appUrl().'/r/'.$a->record?->ulid);
    }

    public function toWhatsApp(object $notifiable): array
    {
        return ['session' => $this->approval->mosque->wa_session_id, 'mosque_id' => $this->approval->mosque_id, 'message' => $this->waMessage()];
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        return TelegramMessage::create($this->waMessage())->to($notifiable->telegram_chat_id);
    }
}

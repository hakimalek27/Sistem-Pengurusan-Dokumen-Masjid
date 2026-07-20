<?php

namespace App\Notifications;

use App\Models\Minit;
use App\Notifications\Concerns\HasMagicDeepLink;
use App\Notifications\Concerns\RoutesDiwanChannels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

// §14 [MinitReminder]
class MinitReminderNotification extends Notification
{
    use HasMagicDeepLink;
    use RoutesDiwanChannels;

    public function __construct(public Minit $minit, public bool $late = false, public int $lateDays = 0) {}

    protected function appUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    protected function timing(): string
    {
        return $this->late ? "LEWAT {$this->lateDays} hari" : 'esok';
    }

    public function waMessage(object $notifiable): string
    {
        $m = $this->minit->loadMissing(['mosque', 'record']);
        $tajuk = mb_substr($m->record?->title ?? '', 0, 60);

        return "⏰ *Diwan · {$m->mosque->code}*\n"
            ."Peringatan: tindakan minit \"{$tajuk}\" perlu diselesaikan {$this->timing()}.\n"
            .$this->deepLink($notifiable, '/r/'.$m->record?->ulid);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $m = $this->minit->loadMissing(['mosque', 'record']);
        $tajuk = mb_substr($m->record?->title ?? '', 0, 60);

        return (new MailMessage)
            ->subject("Diwan · {$m->mosque->code} — Peringatan tindakan minit")
            ->line("Peringatan: tindakan minit \"{$tajuk}\" perlu diselesaikan {$this->timing()}.")
            ->action('Log masuk', $this->deepLink($notifiable, '/r/'.$m->record?->ulid));
    }

    public function toWhatsApp(object $notifiable): array
    {
        return ['session' => $this->minit->mosque->wa_session_id, 'mosque_id' => $this->minit->mosque_id, 'message' => $this->waMessage($notifiable)];
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        return TelegramMessage::create($this->waMessage($notifiable))->to($notifiable->telegram_chat_id);
    }
}

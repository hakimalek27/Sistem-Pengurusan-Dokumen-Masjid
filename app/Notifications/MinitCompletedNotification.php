<?php

namespace App\Notifications;

use App\Models\Minit;
use App\Notifications\Concerns\HasMagicDeepLink;
use App\Notifications\Concerns\RoutesDiwanChannels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

/** Maklumkan pengirim apabila semua penerima tindakan telah selesai. */
class MinitCompletedNotification extends Notification
{
    use HasMagicDeepLink;
    use RoutesDiwanChannels;

    public function __construct(public Minit $minit) {}

    public function message(object $notifiable): string
    {
        $minit = $this->minit->loadMissing(['mosque', 'record', 'completedBy']);
        $title = mb_substr($minit->record?->title ?? '(tiada tajuk)', 0, 80);

        return "✅ *Diwan · {$minit->mosque->code}*\n"
            ."Semua tindakan minit telah selesai.\n"
            ."Perkara: {$title}\n"
            .'Diselesaikan oleh: '.($minit->completedBy?->name ?? '—')."\n"
            .'Semak rekod: '.$this->deepLink($notifiable, '/r/'.$minit->record?->ulid);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minit = $this->minit->loadMissing(['mosque', 'record']);

        return (new MailMessage)
            ->subject("Diwan · {$minit->mosque->code} — Tindakan minit selesai")
            ->line('Semua penerima tindakan telah menandakan minit selesai.')
            ->line('Perkara: '.($minit->record?->title ?? '—'))
            ->action('Semak Rekod', $this->deepLink($notifiable, '/r/'.$minit->record?->ulid));
    }

    public function toWhatsApp(object $notifiable): array
    {
        return [
            'session' => $this->minit->mosque->wa_session_id,
            'mosque_id' => $this->minit->mosque_id,
            'message' => $this->message($notifiable),
        ];
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        return TelegramMessage::create($this->message($notifiable))->to($notifiable->telegram_chat_id);
    }
}

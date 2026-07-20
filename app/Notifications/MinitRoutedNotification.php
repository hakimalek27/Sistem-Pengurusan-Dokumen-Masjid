<?php

namespace App\Notifications;

use App\Models\Minit;
use App\Notifications\Concerns\HasMagicDeepLink;
use App\Notifications\Concerns\RoutesDiwanChannels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

// §14 [MinitRouted → tindakan / makluman]
class MinitRoutedNotification extends Notification
{
    use HasMagicDeepLink;
    use RoutesDiwanChannels;

    public function __construct(public Minit $minit, public string $jenis = 'tindakan') {}

    protected function appUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    protected function line2(): string
    {
        return $this->jenis === 'makluman'
            ? 'Minit baharu untuk makluman (s.k.) anda.'
            : 'Minit baharu untuk tindakan anda.';
    }

    public function waMessage(object $notifiable): string
    {
        $m = $this->minit->loadMissing(['mosque', 'record', 'fromUser']);
        $tajuk = mb_substr($m->record?->title ?? '(tiada tajuk)', 0, 60);
        $due = $m->due_at ? $m->due_at->format('d/m/Y') : '—';

        return "📄 *Diwan · {$m->mosque->code}*\n"
            .$this->line2()."\n"
            .'Daripada: '.($m->fromUser?->name ?? '—')."\n"
            ."Perkara: {$tajuk}\n"
            .'Keutamaan: '.$m->priority->getLabel()." | Tindakan sebelum: {$due}\n"
            .'Log masuk: '.$this->deepLink($notifiable, '/r/'.$m->record?->ulid);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $m = $this->minit->loadMissing(['mosque', 'record', 'fromUser']);
        $tajuk = mb_substr($m->record?->title ?? '(tiada tajuk)', 0, 60);

        return (new MailMessage)
            ->subject("Diwan · {$m->mosque->code} — Minit baharu: {$tajuk}")
            ->line($this->line2())
            ->line('Daripada: '.($m->fromUser?->name ?? '—'))
            ->line("Perkara: {$tajuk}")
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

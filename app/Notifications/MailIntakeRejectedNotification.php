<?php

namespace App\Notifications;

use App\Models\Mosque;
use App\Notifications\Concerns\RoutesDiwanChannels;
use App\Support\AllowedFormats;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

/**
 * §11.3 — Maklum admin masjid apabila e-mel intake TIDAK diproses, supaya
 * e-mel tidak lesap senyap. Sebab: sender_not_allowed | keyword_missing |
 * quota | rejected_format. Dithrottle 1 jam/masjid+sebab oleh pemanggil.
 */
class MailIntakeRejectedNotification extends Notification
{
    use RoutesDiwanChannels;

    public function __construct(
        public Mosque $mosque,
        public string $reason,
        public string $from,
        public string $subject,
    ) {}

    protected function appUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    protected function reasonText(): string
    {
        return match ($this->reason) {
            'sender_not_allowed' => "pengirim ({$this->from}) tiada dalam senarai dibenarkan",
            'keyword_missing' => 'subjek/isi tiada kata kunci intake yang ditetapkan',
            'quota' => 'kuota storan masjid penuh',
            'rejected_format' => 'lampiran bukan format disokong ('.AllowedFormats::label().')',
            default => 'sebab tidak diketahui',
        };
    }

    protected function settingsUrl(): string
    {
        return $this->appUrl().'/app/'.$this->mosque->slug.'/tetapan-masjid';
    }

    public function waMessage(): string
    {
        return "⚠️ *Diwan · {$this->mosque->code}*\n"
            ."E-mel masuk TIDAK diproses: {$this->reasonText()}.\n"
            .'Subjek: '.mb_substr($this->subject, 0, 80)."\n"
            .'Semak Tetapan Masjid → E-mel Pengimbas: '.$this->settingsUrl();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Diwan · {$this->mosque->code} — e-mel intake ditolak")
            ->line('Satu e-mel masuk ke alamat intake masjid TIDAK diproses.')
            ->line('Sebab: '.$this->reasonText().'.')
            ->line("Daripada: {$this->from}")
            ->line('Subjek: '.mb_substr($this->subject, 0, 120))
            ->action('Semak Tetapan Masjid', $this->settingsUrl());
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

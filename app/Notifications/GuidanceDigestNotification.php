<?php

namespace App\Notifications;

use App\Models\Mosque;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\Channels\WhatsAppChannel;
use App\Services\WhatsAppRecipientResolver;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

class GuidanceDigestNotification extends Notification
{
    public function __construct(public Mosque $mosque, public array $summary, public array $enabledChannels) {}

    public function via(object $notifiable): array
    {
        $channels = [];
        if (in_array('mail', $this->enabledChannels, true) && $notifiable->notify_email && filled($notifiable->email)) {
            $channels[] = 'mail';
        }
        if (in_array('whatsapp', $this->enabledChannels, true)
            && app(WhatsAppRecipientResolver::class)->resolve($notifiable, $this->mosque->id)) {
            $channels[] = WhatsAppChannel::class;
        }
        if (in_array('telegram', $this->enabledChannels, true) && $notifiable->notify_telegram && filled($notifiable->telegram_chat_id)) {
            $channels[] = TelegramChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Diwan · '.$this->mosque->code.' — Ringkasan tugasan')
            ->line('Ringkasan ini hanya mengandungi kiraan tugasan dan tidak menyertakan kandungan dokumen.');
        foreach ($this->summary as $line) {
            $message->line($line);
        }

        return $message->action('Buka Diwan', rtrim((string) config('app.url'), '/').'/app/'.$this->mosque->slug);
    }

    public function toWhatsApp(object $notifiable): array
    {
        return ['mosque_id' => $this->mosque->id, 'message' => $this->message()];
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        return TelegramMessage::create($this->message())->to($notifiable->telegram_chat_id);
    }

    protected function message(): string
    {
        return "*Diwan · {$this->mosque->code} — Ringkasan tugasan*\n"
            .implode("\n", $this->summary)
            ."\nBuka: ".rtrim((string) config('app.url'), '/').'/app/'.$this->mosque->slug;
    }
}

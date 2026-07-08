<?php

namespace App\Notifications;

use App\Notifications\Concerns\RoutesDiwanChannels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// §9.C.15 — Notifikasi ujian (semua saluran aktif).
class TestNotification extends Notification
{
    use RoutesDiwanChannels;

    public function waMessage(): string
    {
        return '🔔 Diwan — Ini notifikasi ujian. Saluran notifikasi anda berfungsi.';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Diwan — Notifikasi Ujian')
            ->line('Ini notifikasi ujian. Saluran notifikasi anda berfungsi.');
    }

    public function toWhatsApp(object $notifiable): array
    {
        // Tiada sesi masjid untuk notifikasi peribadi ujian → dilangkau senyap.
        return ['session' => null, 'message' => $this->waMessage()];
    }
}

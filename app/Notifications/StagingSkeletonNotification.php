<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * F8 (§4.8 / nota F) — e-mel ujian BERKERANGKA untuk `diwan:staging-check --mail-to=`.
 *
 * Gate itu dahulu menghantar `Mail::raw(...)`: teks kosong tanpa salam, penutup atau nota hak
 * cipta. Ia membuktikan SMTP menghantar, tetapi bukan perkara yang gate itu wujud untuk
 * buktikan — bahawa kerangka e-mel kekal Bahasa Melayu selepas melalui penghantar sebenar.
 * Pemilik yang membuka e-mel mentah tidak mempunyai apa-apa untuk disahkan.
 *
 * Kelas ini menggunakan `MailMessage` biasa, jadi ia dirender oleh templat markdown yang SAMA
 * seperti 18 kelas `toMail()` produk. Apa yang pemilik lihat di sini ialah kerangka yang sama
 * yang setiap notifikasi Diwan gunakan.
 */
class StagingSkeletonNotification extends Notification
{
    /** @return array<int, string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Diwan — ujian kerangka e-mel')
            ->line('Ini e-mel ujian daripada `diwan:staging-check`. Ia menggunakan kerangka yang sama seperti setiap notifikasi Diwan.')
            ->line('Jika salam di atas dan penutup di bawah dalam Bahasa Melayu, kerangka e-mel bertahan melalui penghantar sebenar.');
    }
}

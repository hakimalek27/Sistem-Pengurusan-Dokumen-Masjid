<?php

namespace App\Filament\Admin\Widgets;

use App\Models\PlatformSetting;
use App\Models\WhatsAppIntegration;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * §11.1 — Ringkasan kesihatan saluran platform pada dashboard superadmin:
 * gateway WhatsApp, IMAP intake e-mel, dan sesi WhatsApp yang tersambung.
 * Guna sumber data yang sama seperti halaman Status Sambungan.
 */
class ChannelStatusOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected ?string $heading = 'Kesihatan Saluran';

    protected function getStats(): array
    {
        $gatewayOk = PlatformSetting::get('gateway_status', ['ok' => null])['ok'] ?? null;
        $imapStreak = (int) PlatformSetting::get('imap_failure_streak', 0);

        $connected = WhatsAppIntegration::query()->withoutMosqueScope()->where('status', 'connected')->count();
        $total = WhatsAppIntegration::query()->withoutMosqueScope()->whereNotNull('session_id')->count();

        return [
            Stat::make('Gateway WhatsApp', is_null($gatewayOk) ? 'Belum diuji' : ($gatewayOk ? 'OK' : 'GAGAL'))
                ->description(is_null($gatewayOk) ? 'Uji di Tetapan Platform' : 'wassap.wehdah.my')
                ->color(is_null($gatewayOk) ? 'gray' : ($gatewayOk ? 'success' : 'danger'))
                ->descriptionIcon('heroicon-o-signal'),

            Stat::make('IMAP Intake E-mel', $imapStreak === 0 ? 'OK' : "Gagal ({$imapStreak}×)")
                ->description($imapStreak === 0 ? 'Poll setiap minit' : 'Semak App Password')
                ->color($imapStreak === 0 ? 'success' : 'danger')
                ->descriptionIcon('heroicon-o-envelope'),

            Stat::make('Sesi WhatsApp Tersambung', "{$connected} / {$total}")
                ->description('Sesi masjid + platform')
                ->color($total > 0 && $connected === $total ? 'success' : ($connected === 0 ? 'gray' : 'warning'))
                ->descriptionIcon('heroicon-o-chat-bubble-left-right'),
        ];
    }
}

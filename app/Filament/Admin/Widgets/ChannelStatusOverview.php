<?php

namespace App\Filament\Admin\Widgets;

use App\Models\PlatformSetting;
use App\Models\WhatsAppIntegration;
use App\Services\TelegramService;
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
        $imapEnabled = (bool) config('diwan.imap_enabled');
        $imapStreak = (int) PlatformSetting::get('imap_failure_streak', 0);

        $connected = WhatsAppIntegration::query()->withoutMosqueScope()->where('status', 'connected')->count();
        $total = WhatsAppIntegration::query()->withoutMosqueScope()->whereNotNull('session_id')->count();
        $telegramConfigured = app(TelegramService::class)->isConfigured();

        // IMAP: "Dimatikan" (kelabu) bila IMAP_ENABLED=false — job kembali awal jadi
        // streak kekal 0; tanpa keadaan ini widget tersilap papar "OK" hijau (§11.3).
        $imapValue = ! $imapEnabled ? 'Dimatikan' : ($imapStreak === 0 ? 'OK' : "Gagal ({$imapStreak}×)");
        $imapDesc = ! $imapEnabled ? 'IMAP_ENABLED=false' : ($imapStreak === 0 ? 'Poll setiap minit' : 'Semak App Password');
        $imapColor = ! $imapEnabled ? 'gray' : ($imapStreak === 0 ? 'success' : 'danger');

        return [
            Stat::make('Gateway WhatsApp', is_null($gatewayOk) ? 'Belum diuji' : ($gatewayOk ? 'OK' : 'GAGAL'))
                ->description(is_null($gatewayOk) ? 'Uji di Tetapan Platform' : 'wassap.wehdah.my')
                ->color(is_null($gatewayOk) ? 'gray' : ($gatewayOk ? 'success' : 'danger'))
                ->descriptionIcon('heroicon-o-signal'),

            Stat::make('IMAP Intake E-mel', $imapValue)
                ->description($imapDesc)
                ->color($imapColor)
                ->descriptionIcon('heroicon-o-envelope'),

            Stat::make('Sesi WhatsApp Tersambung', "{$connected} / {$total}")
                ->description('Sesi masjid + platform')
                ->color($total > 0 && $connected === $total ? 'success' : ($connected === 0 ? 'gray' : 'warning'))
                ->descriptionIcon('heroicon-o-chat-bubble-left-right'),

            Stat::make('Telegram', $telegramConfigured ? 'OK' : 'Belum dikonfigur')
                ->description($telegramConfigured ? '@'.(config('diwan.telegram.bot_username') ?: 'bot') : 'Tetapkan di Tetapan Platform')
                ->color($telegramConfigured ? 'success' : 'gray')
                ->descriptionIcon('heroicon-o-paper-airplane'),
        ];
    }
}

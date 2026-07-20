<?php

namespace App\Filament\Admin\Widgets;

use App\Models\PlatformSetting;
use App\Models\WhatsAppIntegration;
use App\Services\TelegramService;
use App\Support\MailIntakeHealth;
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

        $connected = WhatsAppIntegration::query()->withoutMosqueScope()->where('status', 'connected')->count();
        $total = WhatsAppIntegration::query()->withoutMosqueScope()->whereNotNull('session_id')->count();
        $telegramConfigured = app(TelegramService::class)->isConfigured();

        // IMAP: dinilai oleh MailIntakeHealth — merangkumi keadaan TERSEKAT (job
        // tidak berjalan langsung). Semakan streak sahaja memaparkan "OK" hijau
        // palsu sepanjang insiden mutex tersangkut 14 jam pada 20 Jul (§11.3).
        $imap = MailIntakeHealth::evaluate();

        return [
            Stat::make('Gateway WhatsApp', is_null($gatewayOk) ? 'Belum diuji' : ($gatewayOk ? 'OK' : 'GAGAL'))
                ->description(is_null($gatewayOk) ? 'Uji di Tetapan Platform' : 'wassap.wehdah.my')
                ->color(is_null($gatewayOk) ? 'gray' : ($gatewayOk ? 'success' : 'danger'))
                ->descriptionIcon('heroicon-o-signal'),

            Stat::make('IMAP Intake E-mel', $imap['label'])
                ->description($imap['description'])
                ->color($imap['color'])
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

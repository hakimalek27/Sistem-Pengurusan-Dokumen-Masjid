<?php

namespace App\Console\Commands;

use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\WhatsAppIntegration;
use App\Notifications\ConnectionAlertNotification;
use App\Services\WhatsAppIntegrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * §11.1 — Pemantauan kesihatan sambungan setiap 10 minit: segerakkan status
 * setiap sesi WhatsApp (masjid + platform) dan maklumkan superadmin pada
 * transisi connected → terputus (dengan cooldown), serta semak kesihatan IMAP.
 */
class CheckWaSessions extends Command
{
    protected $signature = 'diwan:check-wa-sessions';

    protected $description = 'Segerakkan status sesi WhatsApp & alert superadmin pada gangguan sambungan';

    private const COOLDOWN_MINUTES = 60;

    public function handle(WhatsAppIntegrationService $service): int
    {
        $this->checkWhatsApp($service);
        $this->checkImap();

        return self::SUCCESS;
    }

    protected function checkWhatsApp(WhatsAppIntegrationService $service): void
    {
        $integrations = WhatsAppIntegration::query()->withoutMosqueScope()
            ->where('enabled', true)
            ->whereNotNull('session_id')
            ->get();

        foreach ($integrations as $integration) {
            try {
                $service->syncStatus($integration->mosque); // null → platform
            } catch (\Throwable $e) {
                // status/last_error dikemas kini dalam service; teruskan.
            }

            $this->evaluateTransition($integration->fresh());
        }
    }

    protected function evaluateTransition(WhatsAppIntegration $integration): void
    {
        $name = $integration->mosque?->name ?? 'Platform';
        $isDown = $integration->status !== 'connected';

        if ($isDown) {
            $cooldownActive = $integration->last_alerted_at
                && $integration->last_alerted_at->gt(now()->subMinutes(self::COOLDOWN_MINUTES));

            if ($integration->last_alert_status !== 'down' && ! $cooldownActive) {
                $this->alertSessionDown($integration, $name);
                $integration->forceFill(['last_alert_status' => 'down', 'last_alerted_at' => now()])->save();
            }

            return;
        }

        if ($integration->last_alert_status === 'down') {
            $this->notifySuperadmins(new ConnectionAlertNotification(
                "WhatsApp masjid {$name} pulih",
                "Sambungan WhatsApp untuk {$name} telah pulih.",
                viaPlatformWa: true,
            ));
            $integration->forceFill(['last_alert_status' => 'up', 'last_alerted_at' => now()])->save();
        }
    }

    protected function alertSessionDown(WhatsAppIntegration $integration, string $name): void
    {
        // Superadmin: e-mel + Telegram + WA platform.
        $this->notifySuperadmins(new ConnectionAlertNotification(
            "WhatsApp masjid {$name} terputus",
            "Sesi WhatsApp untuk {$name} terputus (status: {$integration->status}). Sila maklumkan admin masjid untuk imbas semula QR.",
            viaPlatformWa: true,
        ));

        // Admin masjid berkenaan: e-mel + Telegram sahaja (sesi WA mereka mati).
        if ($integration->mosque_id && $integration->mosque) {
            $admins = $integration->mosque->users()->wherePivot('role', 'admin_masjid')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new ConnectionAlertNotification(
                    'Sambungan WhatsApp masjid terputus',
                    "Nombor WhatsApp masjid {$name} terputus. Sila pergi ke Tetapan Masjid → Pasangkan Nombor untuk imbas semula QR.",
                    viaPlatformWa: false,
                ));
            }
        }
    }

    protected function checkImap(): void
    {
        $streak = (int) PlatformSetting::get('imap_failure_streak', 0);
        $alerted = (bool) PlatformSetting::get('imap_alerted', false);

        // Gagal berterusan (≥5 kitaran ~5 min) & belum dimaklumkan → alert.
        if ($streak >= 5 && ! $alerted) {
            $this->notifySuperadmins(new ConnectionAlertNotification(
                'IMAP intake e-mel gagal',
                "Sambungan IMAP gagal {$streak} kali berturut. Penerimaan dokumen e-mel terjeda — sila semak kata laluan aplikasi.",
                viaPlatformWa: true,
            ));
            PlatformSetting::put('imap_alerted', true);
        }

        // Streak reset (pulih) selepas pernah alert → maklumkan pulih.
        if ($streak === 0 && $alerted) {
            $this->notifySuperadmins(new ConnectionAlertNotification(
                'IMAP intake e-mel pulih',
                'Sambungan IMAP telah pulih. Penerimaan dokumen e-mel beroperasi semula.',
                viaPlatformWa: true,
            ));
            PlatformSetting::put('imap_alerted', false);
        }
    }

    protected function notifySuperadmins(ConnectionAlertNotification $notification): void
    {
        $superadmins = User::query()->where('is_superadmin', true)->where('is_active', true)->get();

        if ($superadmins->isNotEmpty()) {
            Notification::send($superadmins, $notification);
        }
    }
}

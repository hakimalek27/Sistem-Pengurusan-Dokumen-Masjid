<?php

namespace App\Console\Commands;

use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\WhatsAppIntegration;
use App\Notifications\ConnectionAlertNotification;
use App\Services\WhatsAppIntegrationService;
use App\Support\MailIntakeHealth;
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

    /**
     * Kesihatan intake e-mel. DUA mod kegagalan berbeza dipantau:
     *
     *  (a) GAGAL  — sambungan IMAP ditolak (kredential/rangkaian). Dikesan
     *      melalui imap_failure_streak, iaitu job BERJALAN tetapi gagal.
     *  (b) TERSEKAT — job langsung TIDAK berjalan (cth mutex jadual tersangkut,
     *      insiden 19-20 Jul ~14 jam). Streak kekal 0 di sini, jadi semakan
     *      streak sahaja BUTA sepenuhnya terhadap mod ini — inilah sebabnya
     *      kegagalan itu senyap. Dikesan melalui detak jantung
     *      imap_last_success_at (MailIntakeHealth).
     *
     * IMAP dimatikan (dev/staging) tidak pernah menghasilkan alert.
     */
    protected function checkImap(): void
    {
        $health = MailIntakeHealth::evaluate();
        $alerted = (bool) PlatformSetting::get('imap_alerted', false);
        $streak = $health['streak'];

        if ($health['state'] === MailIntakeHealth::STATE_DISABLED) {
            return;
        }

        $shouldAlert = match ($health['state']) {
            // Gagal berterusan (≥5 kitaran ~5 min) — elak alert pada gangguan seketika.
            MailIntakeHealth::STATE_FAILING => $streak >= 5,
            MailIntakeHealth::STATE_STALLED => true,
            default => false,
        };

        if ($shouldAlert && ! $alerted) {
            [$title, $body] = $health['state'] === MailIntakeHealth::STATE_FAILING
                ? [
                    'IMAP intake e-mel gagal',
                    "Sambungan IMAP gagal {$streak} kali berturut. Penerimaan dokumen e-mel terjeda — sila semak kata laluan aplikasi.",
                ]
                : [
                    'Intake e-mel tersekat',
                    'Tiada larian fetch-mail berjaya sejak '.($health['minutes_since'] !== null
                        ? $health['minutes_since'].' minit lalu'
                        : 'sekian lama').
                    '. Dokumen yang dihantar melalui e-mel TIDAK diterima. Semak `php artisan schedule:list` untuk mutex tersangkut pada diwan:fetch-mail.',
                ];

            $this->notifySuperadmins(new ConnectionAlertNotification($title, $body, viaPlatformWa: true));
            PlatformSetting::put('imap_alerted', true);
        }

        // Pulih sepenuhnya selepas pernah alert → maklumkan.
        if ($health['state'] === MailIntakeHealth::STATE_OK && $alerted) {
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

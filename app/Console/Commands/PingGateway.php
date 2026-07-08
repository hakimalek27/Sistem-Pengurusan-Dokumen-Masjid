<?php

namespace App\Console\Commands;

use App\Models\PlatformSetting;
use App\Models\User;
use App\Notifications\GatewayDownNotification;
use App\Services\WhatsAppGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

// §11.1 — Ping gateway setiap 5 minit → platform_settings.gateway_status + banner + notifikasi.
class PingGateway extends Command
{
    protected $signature = 'diwan:ping-gateway';

    protected $description = 'Semak kesihatan gateway WhatsApp dan kemas kini status platform';

    public function handle(WhatsAppGateway $gateway): int
    {
        $previous = PlatformSetting::get('gateway_status', ['ok' => true]);
        $ok = $gateway->ping();

        PlatformSetting::put('gateway_status', [
            'ok' => $ok,
            'checked_at' => now()->toIso8601String(),
        ]);

        // Beritahu superadmin hanya pada transisi OK → GAGAL.
        if (! $ok && ($previous['ok'] ?? true)) {
            $superadmins = User::query()->where('is_superadmin', true)->where('is_active', true)->get();
            Notification::send($superadmins, new GatewayDownNotification(now()->format('d/m/Y H:i')));
        }

        $this->info('Status gateway: '.($ok ? 'OK' : 'GAGAL'));

        return self::SUCCESS;
    }
}

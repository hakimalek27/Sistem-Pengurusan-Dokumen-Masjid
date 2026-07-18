<?php

namespace App\Filament\Admin\Pages;

use App\Models\PlatformSetting;
use App\Models\WhatsAppIntegration;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * §11.1 — Papar status sambungan semua saluran untuk superadmin: sesi WhatsApp
 * setiap masjid + platform, gateway global, dan IMAP intake e-mel.
 */
class StatusSambungan extends Page
{
    protected string $view = 'filament.admin.pages.status-sambungan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static ?string $slug = 'status-sambungan';

    protected static ?string $navigationLabel = 'Status Sambungan';

    protected static ?string $title = 'Status Sambungan';

    protected static string|\UnitEnum|null $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 12;

    public static function canAccess(): bool
    {
        return (bool) Auth::user()?->is_superadmin;
    }

    protected function getViewData(): array
    {
        $integrations = WhatsAppIntegration::query()->withoutMosqueScope()
            ->with('mosque')
            ->orderByRaw('mosque_id IS NULL DESC')
            ->orderBy('mosque_id')
            ->get();

        return [
            'integrations' => $integrations,
            'gatewayStatus' => PlatformSetting::get('gateway_status', ['ok' => null]),
            'imapStreak' => (int) PlatformSetting::get('imap_failure_streak', 0),
            'imapError' => PlatformSetting::get('imap_last_error'),
        ];
    }
}

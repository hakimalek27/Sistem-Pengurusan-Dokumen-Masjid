<?php

namespace App\Filament\Admin\Pages;

use App\Filament\App\Pages\AnalitikBantuan as TenantAnalytics;
use App\Models\HelpEvent;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class AnalitikBantuan extends Page
{
    protected string $view = 'filament.pages.help-analytics';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Bantuan';

    protected static ?string $navigationLabel = 'Analitik Bantuan';

    protected static ?string $title = 'Analitik Bantuan Platform';

    protected static ?string $slug = 'analitik-bantuan';

    public static function canAccess(): bool
    {
        return config('diwan.guidance.enabled') && (bool) Auth::user()?->is_superadmin;
    }

    protected function getViewData(): array
    {
        return TenantAnalytics::metrics(HelpEvent::query()->where('created_at', '>=', now()->subDays(30)));
    }
}

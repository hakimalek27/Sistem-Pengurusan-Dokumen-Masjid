<?php

namespace App\Filament\App\Pages;

use App\Models\HelpEvent;
use BackedEnum;
use Filament\Facades\Filament;
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

    protected static ?string $title = 'Analitik Bantuan';

    protected static ?string $slug = 'analitik-bantuan';

    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();

        return config('diwan.guidance.enabled') && $tenant && (Auth::user()?->canIn($tenant, 'help.analytics') ?? false);
    }

    protected function getViewData(): array
    {
        return self::metrics(HelpEvent::query()->where('mosque_id', Filament::getTenant()->id)->where('created_at', '>=', now()->subDays(30)));
    }

    public static function metrics($query): array
    {
        $base = clone $query;

        return [
            'searches' => (clone $base)->where('event', 'search')->count(),
            'noResults' => (clone $base)->where('event', 'search')->where('result_count', 0)->count(),
            'started' => (clone $base)->where('event', 'started')->count(),
            'completed' => (clone $base)->where('event', 'completed')->count(),
            'missingTargets' => (clone $base)->where('event', 'target_missing')->count(),
            'topGuides' => (clone $base)->whereNotNull('guide_id')->selectRaw('guide_id, COUNT(*) as total')
                ->groupBy('guide_id')->orderByDesc('total')->limit(10)->get(),
        ];
    }
}

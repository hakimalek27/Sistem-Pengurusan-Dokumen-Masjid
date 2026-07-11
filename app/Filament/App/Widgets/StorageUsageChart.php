<?php

namespace App\Filament\App\Widgets;

use App\Services\QuotaService;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class StorageUsageChart extends ChartWidget
{
    protected static bool $isLazy = false;

    protected ?string $heading = 'Penggunaan Storan';

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        $mosque = Filament::getTenant();

        return $mosque && (Auth::user()?->canIn($mosque, 'usage.view') ?? false);
    }

    protected function getData(): array
    {
        $mosque = Filament::getTenant();
        $quota = app(QuotaService::class)->effectiveQuota($mosque);
        $used = min((int) $mosque->storage_used_bytes, $quota);

        return [
            'datasets' => [[
                'data' => [round($used / (1024 ** 3), 2), round(max(0, $quota - $used) / (1024 ** 3), 2)],
                'backgroundColor' => ['#059669', '#d1d5db'],
            ]],
            'labels' => ['Digunakan (GB)', 'Baki (GB)'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}

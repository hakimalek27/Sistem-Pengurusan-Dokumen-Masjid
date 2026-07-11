<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Mosque;
use Filament\Widgets\ChartWidget;

class TenantGrowthChart extends ChartWidget
{
    protected static bool $isLazy = false;

    protected ?string $heading = 'Pendaftaran Masjid — 6 Bulan';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(fn (int $offset) => now()->subMonths($offset)->startOfMonth());

        return [
            'datasets' => [[
                'label' => 'Masjid Baharu',
                'data' => $months->map(fn ($month) => Mosque::query()->whereBetween('created_at', [$month, $month->copy()->endOfMonth()])->count())->all(),
                'backgroundColor' => '#059669',
            ]],
            'labels' => $months->map(fn ($month) => $month->translatedFormat('M Y'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

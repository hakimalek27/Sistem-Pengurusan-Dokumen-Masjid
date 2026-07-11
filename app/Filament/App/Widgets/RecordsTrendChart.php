<?php

namespace App\Filament\App\Widgets;

use App\Models\Record;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class RecordsTrendChart extends ChartWidget
{
    protected static bool $isLazy = false;

    protected ?string $heading = 'Rekod Difailkan — 6 Bulan';

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(fn (int $offset) => now()->subMonths($offset)->startOfMonth());
        $mosque = Filament::getTenant();

        return [
            'datasets' => [[
                'label' => 'Rekod',
                'data' => $months->map(fn ($month) => Record::query()
                    ->visibleTo(Auth::user(), $mosque)
                    ->whereBetween('filed_at', [$month, $month->copy()->endOfMonth()])
                    ->count())->all(),
                'borderColor' => '#059669',
                'backgroundColor' => 'rgba(5, 150, 105, .15)',
                'fill' => true,
            ]],
            'labels' => $months->map(fn ($month) => $month->translatedFormat('M Y'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

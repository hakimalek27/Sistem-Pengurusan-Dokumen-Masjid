<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Mosque;
use App\Models\Record;
use App\Models\StorageOrder;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected ?string $heading = 'Ringkasan Platform';

    protected function getStats(): array
    {
        return [
            Stat::make('Masjid Aktif', Mosque::query()->where('status', 'aktif')->count()),
            Stat::make('Menunggu Kelulusan', Mosque::query()->where('status', 'menunggu')->count()),
            Stat::make('Jumlah Pengguna Aktif', User::query()->where('is_active', true)->count()),
            Stat::make('Jumlah Rekod', Record::query()->withoutGlobalScope('mosque')->count()),
            Stat::make('Pesanan Belum Dibayar', StorageOrder::query()->withoutGlobalScope('mosque')->where('status', 'menunggu_bayaran')->count()),
            Stat::make('Storan Digunakan', number_format(Mosque::query()->sum('storage_used_bytes') / (1024 ** 3), 2).' GB'),
        ];
    }
}

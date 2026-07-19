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
        $pendingTenants = Mosque::query()->where('status', 'menunggu')->count();
        $pendingOrders = StorageOrder::query()->withoutGlobalScope('mosque')->where('status', 'menunggu_bayaran')->count();

        return [
            Stat::make('Masjid Aktif', Mosque::query()->where('status', 'aktif')->count())
                ->description('Tenant boleh digunakan')
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('success'),
            Stat::make('Menunggu Kelulusan', $pendingTenants)
                ->description($pendingTenants ? 'Perlu semakan superadmin' : 'Tiada permohonan baharu')
                ->descriptionIcon('heroicon-o-clipboard-document-check')
                ->color($pendingTenants ? 'warning' : 'success'),
            Stat::make('Pengguna Aktif', User::query()->where('is_active', true)->count())
                ->description('Semua panel')
                ->descriptionIcon('heroicon-o-users')
                ->color('info'),
            Stat::make('Jumlah Rekod', Record::query()->withoutGlobalScope('mosque')->count())
                ->description('Merentas semua tenant')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('gray'),
            Stat::make('Pesanan Belum Dibayar', $pendingOrders)
                ->description($pendingOrders ? 'Menunggu pengesahan bayaran' : 'Tiada tindakan bayaran')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color($pendingOrders ? 'warning' : 'success'),
            Stat::make('Storan Digunakan', number_format(Mosque::query()->sum('storage_used_bytes') / (1024 ** 3), 2).' GB')
                ->description('Jumlah objek dokumen')
                ->descriptionIcon('heroicon-o-server-stack')
                ->color('info'),
        ];
    }
}

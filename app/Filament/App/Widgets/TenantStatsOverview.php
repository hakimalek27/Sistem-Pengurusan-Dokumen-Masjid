<?php

namespace App\Filament\App\Widgets;

use App\Models\Minit;
use App\Models\MinitRecipient;
use App\Models\Record;
use App\Services\QuotaService;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class TenantStatsOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected ?string $heading = 'Ringkasan Pejabat';

    protected function getStats(): array
    {
        $mosque = Filament::getTenant();
        $user = Auth::user();
        $visible = Record::query()->visibleTo($user, $mosque);
        $overdueIds = MinitRecipient::query()
            ->where('user_id', $user->id)
            ->where('jenis', 'tindakan')
            ->where('status', '!=', 'selesai')
            ->pluck('minit_id');
        $overdue = Minit::query()->whereIn('id', $overdueIds)
            ->where('status', 'terbuka')->whereDate('due_at', '<', today())->count();
        $quota = app(QuotaService::class);

        $stats = [
            Stat::make('Rekod Boleh Dilihat', (clone $visible)->whereIn('status', ['difailkan', 'diganti'])->count())
                ->description('Mengikut peranan dan sensitiviti')
                ->descriptionIcon('heroicon-o-document-magnifying-glass')
                ->color('info'),
            Stat::make('Minit Lewat Saya', $overdue)
                ->description($overdue ? 'Perlu tindakan segera' : 'Tiada minit lewat')
                ->descriptionIcon('heroicon-o-clock')
                ->color($overdue ? 'danger' : 'success'),
        ];

        if ($user->canIn($mosque, 'inbox.view')) {
            $inbox = Record::query()->where('status', 'peti_masuk')->count();
            array_unshift($stats, Stat::make('Peti Masuk', $inbox)
                ->description('Belum diklasifikasikan')
                ->descriptionIcon('heroicon-o-inbox-stack')
                ->color($inbox ? 'warning' : 'success'));
        }

        if ($user->canIn($mosque, 'retention.manage') || $user->canIn($mosque, 'retention.hold')) {
            $expiring = (clone $visible)->whereNotNull('retention_due_at')->whereDate('retention_due_at', '<=', now()->addDays(90))->count();
            $stats[] = Stat::make('Akan Luput ≤90 Hari', $expiring)
                ->description($expiring ? 'Semak eksport atau pegangan' : 'Tiada rekod hampir luput')
                ->descriptionIcon('heroicon-o-archive-box')
                ->color($expiring ? 'warning' : 'success');
        }

        if ($user->canIn($mosque, 'usage.view')) {
            $usage = $quota->usagePercent($mosque);
            $stats[] = Stat::make('Penggunaan Storan', number_format($usage, 1).'%')
                ->description(round($mosque->storage_used_bytes / (1024 ** 3), 2).' GB digunakan')
                ->descriptionIcon('heroicon-o-server-stack')
                ->color($usage >= 100 ? 'danger' : ($usage >= 80 ? 'warning' : 'success'));
        }

        return $stats;
    }
}

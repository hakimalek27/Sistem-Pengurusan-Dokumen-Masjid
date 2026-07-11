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
            Stat::make('Rekod Boleh Dilihat', (clone $visible)->whereIn('status', ['difailkan', 'diganti'])->count()),
            Stat::make('Minit Lewat Saya', $overdue)->color($overdue ? 'danger' : 'success'),
        ];

        if ($user->canIn($mosque, 'inbox.view')) {
            $inbox = Record::query()->where('status', 'peti_masuk')->count();
            array_unshift($stats, Stat::make('Peti Masuk', $inbox)->description('Belum diklasifikasikan')->color($inbox ? 'warning' : 'success'));
        }

        if ($user->canIn($mosque, 'retention.manage') || $user->canIn($mosque, 'retention.hold')) {
            $stats[] = Stat::make('Akan Luput ≤90 Hari', (clone $visible)->whereNotNull('retention_due_at')->whereDate('retention_due_at', '<=', now()->addDays(90))->count());
        }

        if ($user->canIn($mosque, 'usage.view')) {
            $stats[] = Stat::make('Penggunaan Storan', number_format($quota->usagePercent($mosque), 1).'%')
                ->description(round($mosque->storage_used_bytes / (1024 ** 3), 2).' GB digunakan');
        }

        return $stats;
    }
}

<?php

namespace App\Filament\App\Pages;

use App\Models\Minit;
use App\Models\Record;
use App\Models\SensitiveAccessLog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class Laporan extends Page
{
    protected string $view = 'filament.app.pages.laporan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Laporan';

    protected static ?string $title = 'Laporan Pejabat';

    protected static string|UnitEnum|null $navigationGroup = 'Tugasan';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'laporan';

    public static function canAccess(): bool
    {
        $mosque = Filament::getTenant();

        return $mosque && (Auth::user()?->canIn($mosque, 'records.view') ?? false);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('eksportCsv')
                ->label('Eksport CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->authorize(fn () => Auth::user()?->canIn(Filament::getTenant(), 'export.create') ?? false)
                ->action(fn (): StreamedResponse => response()->streamDownload(function (): void {
                    $output = fopen('php://output', 'wb');
                    fputcsv($output, ['Rujukan', 'Tajuk', 'Jenis', 'Tarikh', 'Sensitiviti', 'Status']);

                    $this->visibleRecords()->with('registryFile')->orderBy('id')->cursor()->each(function (Record $record) use ($output): void {
                        fputcsv($output, [
                            $this->csvSafe(($record->registryFile?->file_no ?? '—').'('.$record->enclosure_no.')'),
                            $this->csvSafe($record->title),
                            $record->record_type,
                            $record->record_date?->format('d/m/Y'),
                            $record->sensitivity?->value,
                            $record->status?->value,
                        ]);
                    });

                    fclose($output);
                }, 'laporan-rekod-'.Filament::getTenant()->slug.'-'.today()->format('Ymd').'.csv')),
        ];
    }

    protected function visibleRecords(): Builder
    {
        return Record::query()
            ->visibleTo(Auth::user(), Filament::getTenant())
            ->where('status', '!=', 'peti_masuk');
    }

    protected function getViewData(): array
    {
        $mosque = Filament::getTenant();
        $records = $this->visibleRecords()->get();

        return [
            'total' => $records->count(),
            'byType' => $records->groupBy('record_type')->map->count()->sortDesc(),
            'byStatus' => $records->groupBy(fn (Record $record) => $record->status?->getLabel() ?? '—')->map->count(),
            'bySource' => $records->groupBy(fn (Record $record) => $record->source_channel?->getLabel() ?? '—')->map->count(),
            'expiring90' => $records->filter(fn (Record $record) => $record->retention_due_at?->lte(now()->addDays(90)))->count(),
            'overdueMinits' => Minit::query()
                ->where('mosque_id', $mosque->id)
                ->whereIn('record_id', $records->pluck('id'))
                ->where('status', 'terbuka')
                ->whereDate('due_at', '<', today())
                ->count(),
            'sensitiveViews30' => Auth::user()->canIn($mosque, 'audit.view')
                ? SensitiveAccessLog::query()->where('mosque_id', $mosque->id)->where('created_at', '>=', now()->subDays(30))->count()
                : null,
        ];
    }

    protected function csvSafe(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) ? "'{$value}" : $value;
    }
}

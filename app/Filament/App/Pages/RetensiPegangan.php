<?php

namespace App\Filament\App\Pages;

use App\Jobs\BuildExportZipJob;
use App\Models\Record;
use App\Models\StoredExport;
use App\Services\RetentionEngine;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class RetensiPegangan extends Page
{
    protected string $view = 'filament.app.pages.retensi-pegangan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $slug = 'retensi';

    protected static ?string $navigationLabel = 'Retensi & Pegangan';

    protected static ?string $title = 'Retensi & Pegangan';

    protected static string|UnitEnum|null $navigationGroup = 'Akaun';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();

        return $tenant && (Auth::user()?->canIn($tenant, 'retention.manage') || Auth::user()?->canIn($tenant, 'retention.hold'));
    }

    public function toggleHold(int $recordId): void
    {
        $mosque = Filament::getTenant();
        $record = Record::query()->where('mosque_id', $mosque->id)->findOrFail($recordId);

        if (! Auth::user()->canIn($mosque, 'retention.hold')) {
            return;
        }

        $newState = ! $record->legal_hold;
        $record->update(['legal_hold' => $newState]);
        app(RetentionEngine::class)->refreshForRecord($record->fresh());

        activity()->performedOn($record)->causedBy(Auth::user())
            ->withProperties(['legal_hold' => $newState, 'ip' => request()->ip()])->log('legal_hold');

        Notification::make()->title($newState ? 'Legal Hold diaktifkan.' : 'Legal Hold ditarik.')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('eksportAkanLuput')
                ->label('Eksport ZIP (Akan Luput ≤90 hari)')
                ->icon('heroicon-o-arrow-down-tray')
                ->authorize(fn () => Auth::user()?->canIn(Filament::getTenant(), 'export.create') ?? false)
                ->visible(fn () => Auth::user()->canIn(Filament::getTenant(), 'export.create'))
                ->requiresConfirmation()
                ->action(function () {
                    $mosque = Filament::getTenant();
                    $ids = $this->expiringRecords(90)->pluck('id')->all();

                    if (empty($ids)) {
                        Notification::make()->title('Tiada rekod akan luput dalam 90 hari.')->warning()->send();

                        return;
                    }

                    BuildExportZipJob::dispatch($mosque->id, $ids, Auth::id(), 'retensi-akan-luput')->onQueue('exports');
                    Notification::make()->title('Eksport ZIP sedang dijana — pautan akan dihantar.')->success()->send();
                }),
        ];
    }

    public function expiringRecords(int $days = 365)
    {
        $mosque = Filament::getTenant();

        return Record::query()
            ->where('mosque_id', $mosque->id)
            ->whereIn('status', ['difailkan', 'diganti'])
            ->whereNotNull('retention_due_at')
            ->whereDate('retention_due_at', '<=', now()->addDays($days))
            ->orderBy('retention_due_at')
            ->get();
    }

    protected function getViewData(): array
    {
        $mosque = Filament::getTenant();
        $records = $this->expiringRecords(365);
        $engine = app(RetentionEngine::class);

        return [
            'autoDisposal' => (bool) $mosque->auto_disposal_enabled,
            'canHold' => Auth::user()->canIn($mosque, 'retention.hold'),
            'records' => $records,
            'effectiveRetention' => $records->map(function (Record $record) use ($engine) {
                $rule = $engine->effectiveRule($record);

                return [
                    'reference' => ($record->registryFile?->file_no ?? '—').'('.$record->enclosure_no.')',
                    'title' => $record->title,
                    'source' => $rule?->mosque_id ? 'Override Masjid' : 'Lalai Platform',
                    'years' => $rule?->retain_years ?? 'Kekal',
                    'action' => $rule?->action?->getLabel() ?? 'Kekal',
                    'due' => $record->retention_due_at?->format('d/m/Y') ?? '—',
                ];
            }),
            'exports' => StoredExport::query()
                ->where('mosque_id', $mosque->id)
                ->where('requested_by', Auth::id())
                ->where('expires_at', '>', now())
                ->latest()
                ->get(),
        ];
    }
}

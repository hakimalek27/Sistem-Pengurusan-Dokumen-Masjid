<?php

namespace App\Filament\App\Pages;

use App\Models\DisposalBatch;
use App\Models\Record;
use App\Services\DisposalService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class PelupusanManual extends Page
{
    protected string $view = 'filament.app.pages.pelupusan-manual';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrash;

    protected static ?string $slug = 'pelupusan';

    protected static ?string $navigationLabel = 'Pelupusan';

    protected static ?string $title = 'Pelupusan Manual';

    protected static string|UnitEnum|null $navigationGroup = 'Pentadbiran';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();

        return $tenant && (Auth::user()?->canIn($tenant, 'disposal.prepare')
            || Auth::user()?->canIn($tenant, 'disposal.approve')
            || Auth::user()?->canIn($tenant, 'disposal.execute'));
    }

    protected function candidates()
    {
        return Record::query()->where('mosque_id', Filament::getTenant()->id)
            ->visibleTo(Auth::user(), Filament::getTenant())
            ->where('status', 'difailkan')
            ->whereNotNull('retention_due_at')
            ->whereDate('retention_due_at', '<=', now())
            ->get();
    }

    public function approve(int $batchId): void
    {
        $mosque = Filament::getTenant();
        if (! Auth::user()->canIn($mosque, 'disposal.approve')) {
            return;
        }
        $batch = DisposalBatch::query()->where('mosque_id', $mosque->id)->findOrFail($batchId);
        app(DisposalService::class)->approveManual($batch, Auth::user());
        Notification::make()->title('Batch diluluskan.')->success()->send();
    }

    public function execute(int $batchId): void
    {
        $mosque = Filament::getTenant();
        if (! Auth::user()->canIn($mosque, 'disposal.execute')) {
            Notification::make()->title('Anda tidak dibenarkan melaksana pelupusan.')->danger()->send();

            return;
        }
        $batch = DisposalBatch::query()->where('mosque_id', $mosque->id)->whereIn('status', ['lulus', 'gagal', 'memproses'])->findOrFail($batchId);

        try {
            app(DisposalService::class)->executeManual($batch, Auth::user());
            Notification::make()->title('Pelupusan dilaksanakan — sijil dijana.')->success()->send();
        } catch (\Throwable $e) {
            report($e);
            Notification::make()
                ->title('Pelupusan belum selesai')
                ->body('Snapshot kekal selamat. Baiki sambungan storan dan tekan Cuba Semula.')
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sediaBatch')
                ->label('Sedia Senarai Semakan')
                ->icon('heroicon-o-clipboard-document-check')
                ->authorize(fn () => Auth::user()?->canIn(Filament::getTenant(), 'disposal.prepare') ?? false)
                ->visible(fn () => Auth::user()->canIn(Filament::getTenant(), 'disposal.prepare'))
                ->requiresConfirmation()
                ->modalDescription('AMARAN: Selepas kelulusan & pelupusan, rekod dipadam kekal dan tidak boleh dikembalikan; metadata kekal. Pastikan sandaran luar telah dibuat.')
                ->schema([
                    CheckboxList::make('record_ids')
                        ->label('Pilih Rekod Satu per Satu')
                        ->options(fn () => $this->candidates()->mapWithKeys(fn ($record) => [
                            $record->id => ($record->registryFile?->file_no ?? '—').'('.$record->enclosure_no.') — '.$record->title,
                        ]))
                        ->searchable()
                        ->bulkToggleable()
                        ->required(),
                ])
                ->action(function (array $data) {
                    $ids = array_map('intval', $data['record_ids']);
                    app(DisposalService::class)->prepareManual(Filament::getTenant(), $ids, Auth::user());
                    Notification::make()->title(count($ids).' rekod dimasukkan ke batch (menunggu kelulusan).')->success()->send();
                }),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'candidatesCount' => $this->candidates()->count(),
            'batches' => DisposalBatch::query()->where('mosque_id', Filament::getTenant()->id)->latest()->limit(20)->get(),
            'canApprove' => Auth::user()->canIn(Filament::getTenant(), 'disposal.approve'),
            'canExecute' => Auth::user()->canIn(Filament::getTenant(), 'disposal.execute'),
        ];
    }
}

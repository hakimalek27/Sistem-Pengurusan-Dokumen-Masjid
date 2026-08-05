<?php

namespace App\Filament\App\Resources\RecordCorrections\Tables;

use App\Services\RecordCorrectionService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RecordCorrectionsTable
{
    /** Baris pertama render SEMASA. Diset semula setiap `configure()` — lihat nota InboxTable. */
    protected static ?int $barisPertamaId = null;

    protected static function baris1($record, string $target): array
    {
        self::$barisPertamaId ??= (int) $record->getKey();

        return self::$barisPertamaId === (int) $record->getKey()
            ? ['data-help-target' => $target]
            : [];
    }

    public static function configure(Table $table): Table
    {
        self::$barisPertamaId = null;

        return $table->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('record.title')->label('Rekod')->wrap()->limit(45),
                TextColumn::make('requestedBy.name')->label('Pemohon'),
                TextColumn::make('reason')->label('Sebab')->wrap()->limit(55),
                // F6-W4: guide `betulkan-rekod` langkah 8 ("Bandingkan nilai asal dengan
                // cadangan") membaca lajur Perubahan pada baris pertama.
                TextColumn::make('proposed_changes')->label('Perubahan')
                    ->formatStateUsing(fn ($state) => collect($state)->map(fn ($value, $key) => $key.': '.(is_array($value) ? json_encode($value) : ($value ?: 'kosong')))->join('; '))
                    ->wrap()->limit(100)
                    ->extraCellAttributes(fn ($record): array => self::baris1($record, 'correction-diff')),
                // Langkah 10 ("Sahkan status dan catatan semakan").
                TextColumn::make('status')->label('Status')->badge()
                    ->extraCellAttributes(fn ($record): array => self::baris1($record, 'correction-status')),
                TextColumn::make('created_at')->label('Dimohon')->dateTime('d/m/Y H:i'),
            ])
            ->recordActions([
                // Langkah 9 ("Reviewer berkuasa memilih Luluskan atau Tolak") — sasaran pada
                // butang Luluskan; `Tolak` bersebelahannya jadi popover meliputi kedua-duanya.
                self::reviewAction('lulus', 'Luluskan', 'success', true)
                    ->extraAttributes(['data-help-target' => 'correction-decision']),
                self::reviewAction('tolak', 'Tolak', 'danger', false),
            ]);
    }

    protected static function reviewAction(string $name, string $label, string $color, bool $approve): Action
    {
        return Action::make($name)->label($label)->color($color)->authorize('review')
            ->visible(fn ($record) => $record->status === 'menunggu')
            ->requiresConfirmation()
            ->schema([Textarea::make('note')->label('Catatan Semakan')->required(! $approve)])
            ->action(function ($record, array $data) use ($approve): void {
                app(RecordCorrectionService::class)->review($record, Auth::user(), $approve, $data['note'] ?? null);
                Notification::make()->title('Keputusan pembetulan direkodkan.')->success()->send();
            });
    }
}

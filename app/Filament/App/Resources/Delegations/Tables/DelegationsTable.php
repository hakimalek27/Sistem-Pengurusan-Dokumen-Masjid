<?php

namespace App\Filament\App\Resources\Delegations\Tables;

use App\Services\DelegationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class DelegationsTable
{
    /** F6-W5 — sasaran hanya pada baris pertama (rujuk MinitsTable::baris1). */
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
        // Sifat statik hidup sepanjang PROSES — set semula pada titik masuk render (W3).
        self::$barisPertamaId = null;

        return $table->defaultSort('starts_at', 'desc')->columns([
            TextColumn::make('principal.name')->label('Principal'),
            TextColumn::make('delegate.name')->label('Delegate'),
            TextColumn::make('capabilities')->label('Tugas')->formatStateUsing(fn ($state) => collect($state)->map(fn ($v) => $v === 'minit' ? 'Minit' : 'Kelulusan')->join(', ')),
            TextColumn::make('starts_at')->label('Mula')->dateTime('d/m/Y H:i'),
            TextColumn::make('ends_at')->label('Tamat')->dateTime('d/m/Y H:i'),
            TextColumn::make('is_active')->label('Status')->formatStateUsing(fn ($state, $record) => $state && $record->ends_at?->isFuture() ? 'Aktif' : 'Tidak aktif')->badge(),
        ])->recordActions([
            // F6-W5: `tenant.delegasi` #6 ("Batal delegasi sebaik keperluan tamat").
            // ⚠️ Butang ini `visible()` HANYA untuk delegasi aktif yang belum tamat — benih
            // demo diperluas serentak supaya sekurang-kurangnya satu baris memenuhi syarat
            // itu. Tanpa baris, sasaran tidak wujud dan gate hijau bermakna "tiada yang diuji"
            // (pelajaran W4: butang Laksana yang tidak pernah dirender).
            Action::make('revoke')->label('Batal')->icon('heroicon-o-no-symbol')->color('danger')->authorize('delete')
                ->extraAttributes(fn ($record): array => self::baris1($record, 'delegation-revoke'))
                ->visible(fn ($record) => $record->is_active && $record->ends_at?->isFuture())
                ->requiresConfirmation()->action(function ($record): void {
                    app(DelegationService::class)->revoke($record, Auth::user());
                    Notification::make()->title('Delegasi dibatalkan.')->success()->send();
                }),
        ]);
    }
}

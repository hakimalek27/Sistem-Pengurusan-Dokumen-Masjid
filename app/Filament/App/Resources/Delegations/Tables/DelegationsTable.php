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
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('starts_at', 'desc')->columns([
            TextColumn::make('principal.name')->label('Principal'),
            TextColumn::make('delegate.name')->label('Delegate'),
            TextColumn::make('capabilities')->label('Tugas')->formatStateUsing(fn ($state) => collect($state)->map(fn ($v) => $v === 'minit' ? 'Minit' : 'Kelulusan')->join(', ')),
            TextColumn::make('starts_at')->label('Mula')->dateTime('d/m/Y H:i'),
            TextColumn::make('ends_at')->label('Tamat')->dateTime('d/m/Y H:i'),
            TextColumn::make('is_active')->label('Status')->formatStateUsing(fn ($state, $record) => $state && $record->ends_at?->isFuture() ? 'Aktif' : 'Tidak aktif')->badge(),
        ])->recordActions([
            Action::make('revoke')->label('Batal')->icon('heroicon-o-no-symbol')->color('danger')->authorize('delete')
                ->visible(fn ($record) => $record->is_active && $record->ends_at?->isFuture())
                ->requiresConfirmation()->action(function ($record): void {
                    app(DelegationService::class)->revoke($record, Auth::user());
                    Notification::make()->title('Delegasi dibatalkan.')->success()->send();
                }),
        ]);
    }
}

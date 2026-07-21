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
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('record.title')->label('Rekod')->wrap()->limit(45),
                TextColumn::make('requestedBy.name')->label('Pemohon'),
                TextColumn::make('reason')->label('Sebab')->wrap()->limit(55),
                TextColumn::make('proposed_changes')->label('Perubahan')
                    ->formatStateUsing(fn ($state) => collect($state)->map(fn ($value, $key) => $key.': '.(is_array($value) ? json_encode($value) : ($value ?: 'kosong')))->join('; '))
                    ->wrap()->limit(100),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('created_at')->label('Dimohon')->dateTime('d/m/Y H:i'),
            ])
            ->recordActions([
                self::reviewAction('lulus', 'Luluskan', 'success', true),
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

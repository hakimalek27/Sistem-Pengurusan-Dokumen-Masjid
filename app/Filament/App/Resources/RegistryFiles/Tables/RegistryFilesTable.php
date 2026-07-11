<?php

namespace App\Filament\App\Resources\RegistryFiles\Tables;

use App\Services\RecordNumberingService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RegistryFilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('file_no')
            ->columns([
                TextColumn::make('file_no')->label('No. Fail')->searchable()->sortable(),
                TextColumn::make('title')->label('Tajuk')->searchable()->wrap(),
                TextColumn::make('sensitivity')->label('Sensitiviti')->badge(),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn ($state) => $state === 'terbuka' ? 'success' : 'gray'),
                TextColumn::make('enclosure_count')->label('Kandungan')->badge(),
            ])
            ->recordActions([
                ViewAction::make(),
                // §10.F — Buka jilid baharu bila enclosure ≥ 100.
                Action::make('bukaJilid')
                    ->label('Buka Jld. Baharu')
                    ->icon('heroicon-o-plus-circle')
                    ->color('warning')
                    ->authorize('openNextVolume')
                    ->visible(fn ($record) => $record->status === 'terbuka'
                        && $record->enclosure_count >= config('diwan.enclosure_volume_limit', 100))
                    ->requiresConfirmation()
                    ->modalDescription('Tutup jilid ini dan buka jilid baharu (nombor jilid+1).')
                    ->action(fn ($record) => app(RecordNumberingService::class)->openNextVolume($record, Auth::id())),
                Action::make('tutup')
                    ->label('Tutup Fail')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->authorize('close')
                    ->visible(fn ($record) => $record->status === 'terbuka')
                    ->schema([
                        Textarea::make('reason')->label('Sebab Tutup')->required(),
                    ])
                    ->action(fn ($record, array $data) => $record->update([
                        'status' => 'tutup',
                        'closed_at' => now(),
                        'closed_reason' => $data['reason'],
                    ])),
            ]);
    }
}

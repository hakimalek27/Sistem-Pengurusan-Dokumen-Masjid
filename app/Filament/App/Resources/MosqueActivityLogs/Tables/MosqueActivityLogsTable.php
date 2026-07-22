<?php

namespace App\Filament\App\Resources\MosqueActivityLogs\Tables;

use App\Enums\SourceChannel;
use App\Models\MosqueActivityLog;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MosqueActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tarikh & Masa')
                    ->dateTime('d/m/Y h:i:s A')
                    ->sortable()
                    ->wrap(),
                TextColumn::make('actor_name')
                    ->label('Pelaku')
                    ->description(fn (MosqueActivityLog $record) => $record->actor_role ?: ($record->actor_id ? 'Ahli masjid' : 'Sistem / penghantar luar'))
                    ->placeholder('Sistem')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Aktiviti')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('record_title')
                    ->label('Rekod')
                    ->description(fn (MosqueActivityLog $record) => $record->record_reference)
                    ->placeholder('—')
                    ->wrap()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('file_no')
                    ->label('Fail')
                    ->description(fn (MosqueActivityLog $record) => $record->file_title)
                    ->placeholder('—')
                    ->wrap()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('source_channel')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        SourceChannel::MuatNaik->value => 'Dashboard',
                        SourceChannel::Emel->value => 'E-mel',
                        SourceChannel::WhatsApp->value => 'WhatsApp',
                        SourceChannel::Imbasan->value => 'Imbasan',
                        default => $state ?: '—',
                    })
                    ->description(fn (MosqueActivityLog $record) => $record->source_identifier)
                    ->toggleable(),
                TextColumn::make('ip_address')
                    ->label('Alamat IP')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('action')
                    ->label('Kod')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Jenis Aktiviti')
                    ->options(fn () => MosqueActivityLog::query()
                        ->withoutGlobalScope('mosque')
                        ->where('mosque_id', Filament::getTenant()->id)
                        ->distinct()->orderBy('action')->pluck('action', 'action')->all()),
                SelectFilter::make('actor_id')
                    ->label('Pelaku')
                    ->options(fn () => User::query()->whereHas('mosques', fn (Builder $query) => $query
                        ->where('mosques.id', Filament::getTenant()->id))
                        ->orderBy('name')->pluck('name', 'users.id')->all()),
                SelectFilter::make('source_channel')
                    ->label('Saluran')
                    ->options([
                        SourceChannel::MuatNaik->value => 'Dashboard',
                        SourceChannel::Emel->value => 'E-mel',
                        SourceChannel::WhatsApp->value => 'WhatsApp',
                        SourceChannel::Imbasan->value => 'Imbasan',
                    ]),
                Filter::make('tarikh')
                    ->label('Julat Tarikh')
                    ->schema([
                        DatePicker::make('dari')->label('Dari')->native(false)->displayFormat('d/m/Y'),
                        DatePicker::make('hingga')->label('Hingga')->native(false)->displayFormat('d/m/Y'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['dari'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['hingga'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([
                Action::make('butiran')
                    ->label('Butiran')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Butiran Log Aktiviti')
                    ->modalContent(fn (MosqueActivityLog $record) => view('filament.app.activity-log-details', ['log' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('Belum ada aktiviti direkodkan')
            ->emptyStateDescription('Aktiviti baharu masjid akan muncul di sini secara kronologi.');
    }
}

<?php

namespace App\Filament\App\Resources\Records\Tables;

use App\Enums\RecordStatus;
use App\Enums\Sensitivity;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->label('Rujukan')
                    ->state(fn ($record) => $record->registryFile
                        ? $record->registryFile->file_no.'('.$record->enclosure_no.')'
                        : '—')
                    ->searchable(query: fn ($query, $search) => $query->whereHas('registryFile', fn ($q) => $q->where('file_no', 'like', "%{$search}%"))),
                TextColumn::make('title')
                    ->label('Tajuk')
                    ->searchable()
                    ->wrap()
                    ->limit(60),
                TextColumn::make('record_type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn ($state) => config("record_types.{$state}.label", $state)),
                TextColumn::make('registryFile.file_no')
                    ->label('Fail')
                    ->toggleable(),
                TextColumn::make('record_date')
                    ->label('Tarikh')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('sensitivity')
                    ->label('Sensitiviti')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('record_type')
                    ->label('Jenis')
                    ->options(collect(config('record_types'))->mapWithKeys(fn ($t, $k) => [$k => $t['label']])),
                SelectFilter::make('sensitivity')
                    ->label('Sensitiviti')
                    ->options(collect(Sensitivity::cases())->mapWithKeys(fn ($c) => [$c->value => $c->getLabel()])),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(RecordStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->getLabel()])),
            ])
            // F7 §8.3 (axe `empty-table-header` minor) — sel header lajur tindakan
            // kosong walaupun `aria-label` wujud; axe menuntut TEKS atau `aria-hidden`.
            // API semasa: `recordActionsColumnLabel()` (HasRecordActions.php:76);
            // `actionsColumnLabel()` ialah alias @deprecated (:162-164) — jangan guna.
            ->recordActionsColumnLabel('Tindakan')
            ->recordActions([
                // F6-W4: tiga guide `workflow` bermula dengan "Buka Lihat pada rekod yang
                // tepat" / "Buka rekod yang dibenarkan sahaja" pada SENARAI. `page-actions`
                // sedia ada ialah bekas tindakan header BUTIRAN (`state: detail:records`),
                // jadi ia bukan sasaran yang betul untuk langkah senarai ini.
                ViewAction::make()
                    ->extraAttributes(['data-help-target' => 'records-view']),
            ]);
    }
}

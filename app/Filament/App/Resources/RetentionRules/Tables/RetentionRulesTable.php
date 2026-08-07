<?php

namespace App\Filament\App\Resources\RetentionRules\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RetentionRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mosque.name')
                    ->searchable(),
                TextColumn::make('record_type')
                    ->searchable(),
                TextColumn::make('classification_prefix')
                    ->searchable(),
                TextColumn::make('retain_years')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('action')
                    ->badge()
                    ->searchable(),
                TextColumn::make('note')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            // F7 §8.3 (axe `empty-table-header` minor) — sel header lajur tindakan
            // kosong walaupun `aria-label` wujud; axe menuntut TEKS atau `aria-hidden`.
            // API semasa: `recordActionsColumnLabel()` (HasRecordActions.php:76);
            // `actionsColumnLabel()` ialah alias @deprecated (:162-164) — jangan guna.
            ->recordActionsColumnLabel('Tindakan')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

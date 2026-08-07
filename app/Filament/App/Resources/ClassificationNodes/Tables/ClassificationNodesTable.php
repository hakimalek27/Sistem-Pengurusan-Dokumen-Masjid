<?php

namespace App\Filament\App\Resources\ClassificationNodes\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClassificationNodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')
                    ->label('Kod')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Tajuk')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('level')
                    ->label('Peringkat')
                    ->badge(),
                TextColumn::make('default_sensitivity')
                    ->label('Sensitiviti')
                    ->badge(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            // F7 §8.3 (axe `empty-table-header` minor) — sel header lajur tindakan
            // kosong walaupun `aria-label` wujud; axe menuntut TEKS atau `aria-hidden`.
            // API semasa: `recordActionsColumnLabel()` (HasRecordActions.php:76);
            // `actionsColumnLabel()` ialah alias @deprecated (:162-164) — jangan guna.
            ->recordActionsColumnLabel('Tindakan')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}

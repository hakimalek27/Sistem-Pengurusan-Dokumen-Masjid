<?php

namespace App\Filament\App\Resources\SensitiveAccessLogs\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SensitiveAccessLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mosque.name')
                    ->label('Masjid')
                    ->searchable(),
                IconColumn::make('is_superadmin')
                    ->label('Superadmin')
                    ->boolean(),
                TextColumn::make('user.name')
                    ->label('Pengguna')
                    ->searchable(),
                TextColumn::make('record.title')
                    ->label('Rekod')
                    ->searchable(),
                TextColumn::make('action')
                    ->label('Tindakan')
                    ->searchable(),
                TextColumn::make('ip')
                    ->label('Alamat IP')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Masa')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}

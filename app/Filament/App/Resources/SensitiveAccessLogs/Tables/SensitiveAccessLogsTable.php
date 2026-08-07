<?php

namespace App\Filament\App\Resources\SensitiveAccessLogs\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SensitiveAccessLogsTable
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

        return $table
            ->columns([
                // F6-W4: guide `workflow.audit` langkah 8 ("Semak pengguna, tindakan, IP dan
                // masa") membaca baris pertama log akses sulit.
                TextColumn::make('mosque.name')
                    ->label('Masjid')
                    ->searchable()
                    ->extraCellAttributes(fn ($record): array => self::baris1($record, 'sensitive-log-record')),
                IconColumn::make('is_superadmin')
                    ->label('Superadmin')
                    ->boolean(),
                TextColumn::make('user.name')
                    ->label('Pengguna')
                    ->searchable(),
                // F6-W5: `tenant.sensitive-access-logs` #3 ("Siasat akses luar biasa melalui
                // rekod asal") — sel REKOD baris pertama, bukan sel Masjid.
                TextColumn::make('record.title')
                    ->label('Rekod')
                    ->searchable()
                    ->extraCellAttributes(fn ($record): array => self::baris1($record, 'sensitive-log-target')),
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
            // F7 §8.3 (axe `empty-table-header` minor) — sel header lajur tindakan
            // kosong walaupun `aria-label` wujud; axe menuntut TEKS atau `aria-hidden`.
            // API semasa: `recordActionsColumnLabel()` (HasRecordActions.php:76);
            // `actionsColumnLabel()` ialah alias @deprecated (:162-164) — jangan guna.
            ->recordActionsColumnLabel('Tindakan')
            ->recordActions([])
            ->toolbarActions([]);
    }
}

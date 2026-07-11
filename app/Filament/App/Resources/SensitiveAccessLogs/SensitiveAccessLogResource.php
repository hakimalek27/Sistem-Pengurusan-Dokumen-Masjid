<?php

namespace App\Filament\App\Resources\SensitiveAccessLogs;

use App\Filament\App\Resources\SensitiveAccessLogs\Pages\ListSensitiveAccessLogs;
use App\Filament\App\Resources\SensitiveAccessLogs\Tables\SensitiveAccessLogsTable;
use App\Models\SensitiveAccessLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SensitiveAccessLogResource extends Resource
{
    protected static ?string $model = SensitiveAccessLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Log Akses Sulit';

    protected static ?string $modelLabel = 'Log Akses Sulit';

    protected static ?string $pluralModelLabel = 'Log Akses Sulit';

    public static function table(Table $table): Table
    {
        return SensitiveAccessLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSensitiveAccessLogs::route('/'),
        ];
    }
}

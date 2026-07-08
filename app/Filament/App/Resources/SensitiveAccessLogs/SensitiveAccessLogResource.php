<?php

namespace App\Filament\App\Resources\SensitiveAccessLogs;

use App\Filament\App\Resources\SensitiveAccessLogs\Pages\CreateSensitiveAccessLog;
use App\Filament\App\Resources\SensitiveAccessLogs\Pages\EditSensitiveAccessLog;
use App\Filament\App\Resources\SensitiveAccessLogs\Pages\ListSensitiveAccessLogs;
use App\Filament\App\Resources\SensitiveAccessLogs\Schemas\SensitiveAccessLogForm;
use App\Filament\App\Resources\SensitiveAccessLogs\Tables\SensitiveAccessLogsTable;
use App\Models\SensitiveAccessLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SensitiveAccessLogResource extends Resource
{
    protected static ?string $model = SensitiveAccessLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SensitiveAccessLogForm::configure($schema);
    }

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
            'create' => CreateSensitiveAccessLog::route('/create'),
            'edit' => EditSensitiveAccessLog::route('/{record}/edit'),
        ];
    }
}

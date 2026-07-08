<?php

namespace App\Filament\App\Resources\Records;

use App\Enums\RecordStatus;
use App\Filament\App\Resources\Records\Pages\ListRecords;
use App\Filament\App\Resources\Records\Pages\ViewRecord;
use App\Filament\App\Resources\Records\Schemas\RecordInfolist;
use App\Filament\App\Resources\Records\Tables\RecordsTable;
use App\Models\Record;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RecordResource extends Resource
{
    protected static ?string $model = Record::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Rekod';

    protected static ?string $modelLabel = 'Rekod';

    protected static ?string $pluralModelLabel = 'Rekod';

    protected static string|UnitEnum|null $navigationGroup = 'Registri';

    protected static ?int $navigationSort = 2;

    public static function infolist(Schema $schema): Schema
    {
        return RecordInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecordsTable::configure($table);
    }

    /** Senarai Rekod tidak termasuk item Peti Masuk (belum difailkan). */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('status', '!=', RecordStatus::PetiMasuk->value);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecords::route('/'),
            'view' => ViewRecord::route('/{record}'),
        ];
    }
}

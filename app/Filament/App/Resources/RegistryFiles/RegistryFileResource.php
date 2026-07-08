<?php

namespace App\Filament\App\Resources\RegistryFiles;

use App\Filament\App\Resources\RegistryFiles\Pages\CreateRegistryFile;
use App\Filament\App\Resources\RegistryFiles\Pages\ListRegistryFiles;
use App\Filament\App\Resources\RegistryFiles\Pages\ViewRegistryFile;
use App\Filament\App\Resources\RegistryFiles\RelationManagers\AccessGrantsRelationManager;
use App\Filament\App\Resources\RegistryFiles\Schemas\RegistryFileForm;
use App\Filament\App\Resources\RegistryFiles\Schemas\RegistryFileInfolist;
use App\Filament\App\Resources\RegistryFiles\Tables\RegistryFilesTable;
use App\Models\RegistryFile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RegistryFileResource extends Resource
{
    protected static ?string $model = RegistryFile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?string $navigationLabel = 'Fail';

    protected static ?string $modelLabel = 'Fail';

    protected static ?string $pluralModelLabel = 'Fail';

    protected static string|UnitEnum|null $navigationGroup = 'Registri';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'file_no';

    public static function form(Schema $schema): Schema
    {
        return RegistryFileForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RegistryFileInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RegistryFilesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AccessGrantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegistryFiles::route('/'),
            'create' => CreateRegistryFile::route('/create'),
            'view' => ViewRegistryFile::route('/{record}'),
        ];
    }
}

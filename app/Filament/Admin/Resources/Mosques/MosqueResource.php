<?php

namespace App\Filament\Admin\Resources\Mosques;

use App\Filament\Admin\Resources\Mosques\Pages\CreateMosque;
use App\Filament\Admin\Resources\Mosques\Pages\EditMosque;
use App\Filament\Admin\Resources\Mosques\Pages\ListMosques;
use App\Filament\Admin\Resources\Mosques\Pages\ViewMosque;
use App\Filament\Admin\Resources\Mosques\Schemas\MosqueForm;
use App\Filament\Admin\Resources\Mosques\Schemas\MosqueInfolist;
use App\Filament\Admin\Resources\Mosques\Tables\MosquesTable;
use App\Models\Mosque;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MosqueResource extends Resource
{
    protected static ?string $model = Mosque::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return MosqueForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MosqueInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MosquesTable::configure($table);
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
            'index' => ListMosques::route('/'),
            'create' => CreateMosque::route('/create'),
            'view' => ViewMosque::route('/{record}'),
            'edit' => EditMosque::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

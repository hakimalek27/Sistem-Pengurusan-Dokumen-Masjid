<?php

namespace App\Filament\Admin\Resources\StorageOrders;

use App\Filament\Admin\Resources\StorageOrders\Pages\CreateStorageOrder;
use App\Filament\Admin\Resources\StorageOrders\Pages\EditStorageOrder;
use App\Filament\Admin\Resources\StorageOrders\Pages\ListStorageOrders;
use App\Filament\Admin\Resources\StorageOrders\Schemas\StorageOrderForm;
use App\Filament\Admin\Resources\StorageOrders\Tables\StorageOrdersTable;
use App\Models\StorageOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StorageOrderResource extends Resource
{
    protected static ?string $model = StorageOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return StorageOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StorageOrdersTable::configure($table);
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
            'index' => ListStorageOrders::route('/'),
            'create' => CreateStorageOrder::route('/create'),
            'edit' => EditStorageOrder::route('/{record}/edit'),
        ];
    }
}

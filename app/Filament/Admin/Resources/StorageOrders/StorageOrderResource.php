<?php

namespace App\Filament\Admin\Resources\StorageOrders;

use App\Filament\Admin\Resources\StorageOrders\Pages\ListStorageOrders;
use App\Filament\Admin\Resources\StorageOrders\Tables\StorageOrdersTable;
use App\Models\StorageOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StorageOrderResource extends Resource
{
    protected static ?string $model = StorageOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Operasi';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Pesanan Storan';

    protected static ?string $modelLabel = 'Pesanan Storan';

    protected static ?string $pluralModelLabel = 'Pesanan Storan';

    public static function canCreate(): bool
    {
        return false;
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
        ];
    }
}

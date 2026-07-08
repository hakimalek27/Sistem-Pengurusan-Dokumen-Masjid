<?php

namespace App\Filament\Admin\Resources\StorageOrders\Pages;

use App\Filament\Admin\Resources\StorageOrders\StorageOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStorageOrders extends ListRecords
{
    protected static string $resource = StorageOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

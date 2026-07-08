<?php

namespace App\Filament\Admin\Resources\StorageOrders\Pages;

use App\Filament\Admin\Resources\StorageOrders\StorageOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStorageOrder extends EditRecord
{
    protected static string $resource = StorageOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

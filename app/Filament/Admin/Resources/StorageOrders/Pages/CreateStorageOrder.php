<?php

namespace App\Filament\Admin\Resources\StorageOrders\Pages;

use App\Filament\Admin\Resources\StorageOrders\StorageOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStorageOrder extends CreateRecord
{
    protected static string $resource = StorageOrderResource::class;
}

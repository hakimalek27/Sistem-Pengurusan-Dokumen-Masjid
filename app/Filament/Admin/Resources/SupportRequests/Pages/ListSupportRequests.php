<?php

namespace App\Filament\Admin\Resources\SupportRequests\Pages;

use App\Filament\Admin\Resources\SupportRequests\SupportRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListSupportRequests extends ListRecords
{
    protected static string $resource = SupportRequestResource::class;
}

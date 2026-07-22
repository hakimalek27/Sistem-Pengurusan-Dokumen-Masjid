<?php

namespace App\Filament\App\Resources\SupportRequests\Pages;

use App\Filament\App\Resources\SupportRequests\SupportRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListSupportRequests extends ListRecords
{
    protected static string $resource = SupportRequestResource::class;
}

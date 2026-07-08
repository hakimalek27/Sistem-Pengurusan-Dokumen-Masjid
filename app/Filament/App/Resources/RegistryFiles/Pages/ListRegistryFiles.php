<?php

namespace App\Filament\App\Resources\RegistryFiles\Pages;

use App\Filament\App\Resources\RegistryFiles\RegistryFileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRegistryFiles extends ListRecords
{
    protected static string $resource = RegistryFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

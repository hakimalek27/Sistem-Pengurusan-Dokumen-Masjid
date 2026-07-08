<?php

namespace App\Filament\App\Resources\RegistryFiles\Pages;

use App\Filament\App\Resources\RegistryFiles\RegistryFileResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRegistryFile extends ViewRecord
{
    protected static string $resource = RegistryFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

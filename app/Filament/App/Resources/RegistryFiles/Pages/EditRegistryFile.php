<?php

namespace App\Filament\App\Resources\RegistryFiles\Pages;

use App\Filament\App\Resources\RegistryFiles\RegistryFileResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRegistryFile extends EditRecord
{
    protected static string $resource = RegistryFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

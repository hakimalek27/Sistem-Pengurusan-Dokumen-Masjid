<?php

namespace App\Filament\App\Resources\SensitiveAccessLogs\Pages;

use App\Filament\App\Resources\SensitiveAccessLogs\SensitiveAccessLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSensitiveAccessLogs extends ListRecords
{
    protected static string $resource = SensitiveAccessLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\App\Resources\SensitiveAccessLogs\Pages;

use App\Filament\App\Resources\SensitiveAccessLogs\SensitiveAccessLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSensitiveAccessLog extends EditRecord
{
    protected static string $resource = SensitiveAccessLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\App\Resources\SensitiveAccessLogs\Pages;

use App\Filament\App\Resources\SensitiveAccessLogs\SensitiveAccessLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSensitiveAccessLog extends CreateRecord
{
    protected static string $resource = SensitiveAccessLogResource::class;
}

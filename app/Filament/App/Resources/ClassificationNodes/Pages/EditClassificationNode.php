<?php

namespace App\Filament\App\Resources\ClassificationNodes\Pages;

use App\Filament\App\Resources\ClassificationNodes\ClassificationNodeResource;
use Filament\Resources\Pages\EditRecord;

class EditClassificationNode extends EditRecord
{
    protected static string $resource = ClassificationNodeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

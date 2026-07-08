<?php

namespace App\Filament\App\Resources\ClassificationNodes\Pages;

use App\Filament\App\Resources\ClassificationNodes\ClassificationNodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClassificationNodes extends ListRecords
{
    protected static string $resource = ClassificationNodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

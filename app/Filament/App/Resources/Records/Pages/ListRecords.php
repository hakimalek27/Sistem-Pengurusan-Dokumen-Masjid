<?php

namespace App\Filament\App\Resources\Records\Pages;

use App\Filament\App\Resources\Records\RecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords as BaseListRecords;

class ListRecords extends BaseListRecords
{
    protected static string $resource = RecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

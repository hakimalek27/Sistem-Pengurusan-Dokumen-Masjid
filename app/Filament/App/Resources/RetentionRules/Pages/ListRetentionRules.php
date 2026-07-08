<?php

namespace App\Filament\App\Resources\RetentionRules\Pages;

use App\Filament\App\Resources\RetentionRules\RetentionRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRetentionRules extends ListRecords
{
    protected static string $resource = RetentionRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

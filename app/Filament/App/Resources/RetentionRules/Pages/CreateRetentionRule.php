<?php

namespace App\Filament\App\Resources\RetentionRules\Pages;

use App\Filament\App\Resources\RetentionRules\RetentionRuleResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateRetentionRule extends CreateRecord
{
    protected static string $resource = RetentionRuleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['mosque_id'] = Filament::getTenant()->id;

        return $data;
    }
}

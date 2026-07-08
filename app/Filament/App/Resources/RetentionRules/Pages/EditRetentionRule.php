<?php

namespace App\Filament\App\Resources\RetentionRules\Pages;

use App\Filament\App\Resources\RetentionRules\RetentionRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRetentionRule extends EditRecord
{
    protected static string $resource = RetentionRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

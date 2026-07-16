<?php

namespace App\Filament\Admin\Resources\Mosques\Pages;

use App\Filament\Admin\Resources\Mosques\MosqueResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMosque extends EditRecord
{
    protected static string $resource = MosqueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('Paparan'),
            DeleteAction::make()->label('Arkibkan Tenant'),
            RestoreAction::make()->label('Pulihkan Tenant'),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\Mosques\Pages;

use App\Filament\Admin\Resources\Mosques\MosqueResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMosque extends ViewRecord
{
    protected static string $resource = MosqueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

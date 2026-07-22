<?php

namespace App\Filament\Admin\Resources\HelpAnnouncements\Pages;

use App\Filament\Admin\Resources\HelpAnnouncements\HelpAnnouncementResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateHelpAnnouncement extends CreateRecord
{
    protected static string $resource = HelpAnnouncementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $data;
    }
}

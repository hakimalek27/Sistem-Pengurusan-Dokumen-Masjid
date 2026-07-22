<?php

namespace App\Filament\Admin\Resources\HelpAnnouncements\Pages;

use App\Filament\Admin\Resources\HelpAnnouncements\HelpAnnouncementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHelpAnnouncements extends ListRecords
{
    protected static string $resource = HelpAnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

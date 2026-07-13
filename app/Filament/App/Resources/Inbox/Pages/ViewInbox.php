<?php

namespace App\Filament\App\Resources\Inbox\Pages;

use App\Filament\App\Resources\Inbox\InboxResource;
use App\Services\SensitiveAccessLogger;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewInbox extends ViewRecord
{
    protected static string $resource = InboxResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        app(SensitiveAccessLogger::class)->log($this->getRecord(), Auth::user(), 'view', request());
    }
}

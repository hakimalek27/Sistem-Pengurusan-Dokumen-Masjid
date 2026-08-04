<?php

namespace App\Filament\App\Resources\Delegations\Pages;

use App\Filament\App\Resources\Delegations\DelegationResource;
use App\Services\DelegationService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateDelegation extends CreateRecord
{
    protected static string $resource = DelegationResource::class;

    /** F6-W1 — sasaran langkah akhir `screen.cipta-delegasi`; `parent::` dikekalkan. */
    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->extraAttributes(['data-help-target' => 'delegation-submit']);
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(DelegationService::class)->create(Auth::user(), Filament::getTenant(), $data);
    }
}

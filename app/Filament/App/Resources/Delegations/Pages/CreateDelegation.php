<?php

namespace App\Filament\App\Resources\Delegations\Pages;

use App\Filament\App\Resources\Delegations\DelegationResource;
use App\Services\DelegationService;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateDelegation extends CreateRecord
{
    protected static string $resource = DelegationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(DelegationService::class)->create(Auth::user(), Filament::getTenant(), $data);
    }
}

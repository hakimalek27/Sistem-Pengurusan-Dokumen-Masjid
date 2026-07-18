<?php

namespace App\Filament\App\Pages;

use App\Filament\Concerns\ProfileActions;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class Profil extends Page
{
    use ProfileActions;

    protected string $view = 'filament.app.pages.profil';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $slug = 'profil';

    protected static ?string $navigationLabel = 'Profil Saya';

    protected static ?string $title = 'Profil Saya';

    protected static string|UnitEnum|null $navigationGroup = 'Akaun';

    protected static ?int $navigationSort = 99;

    protected function getHeaderActions(): array
    {
        return $this->profileActions();
    }

    protected function getViewData(): array
    {
        return ['user' => Auth::user()];
    }
}

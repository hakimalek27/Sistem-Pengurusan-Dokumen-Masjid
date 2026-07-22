<?php

namespace App\Filament\App\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Bantuan extends Page
{
    protected string $view = 'filament.app.pages.bantuan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static ?string $navigationLabel = 'Pusat Bantuan';

    protected static ?string $title = 'Pusat Bantuan Diwan';

    protected static string|UnitEnum|null $navigationGroup = 'Bantuan';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'bantuan';

    public static function canAccess(): bool
    {
        return (bool) config('diwan.guidance.enabled');
    }
}

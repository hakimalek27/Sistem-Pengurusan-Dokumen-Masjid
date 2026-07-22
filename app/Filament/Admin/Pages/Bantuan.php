<?php

namespace App\Filament\Admin\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class Bantuan extends Page
{
    protected string $view = 'filament.admin.pages.bantuan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?string $navigationLabel = 'Pusat Bantuan';

    protected static ?string $title = 'Pusat Bantuan Platform';

    protected static string|UnitEnum|null $navigationGroup = 'Bantuan';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'bantuan';

    public static function canAccess(): bool
    {
        return (bool) config('diwan.guidance.enabled') && (bool) Auth::user()?->is_superadmin;
    }
}

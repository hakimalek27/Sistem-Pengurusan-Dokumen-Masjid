<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Concerns\ProfileActions;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * §11.2 — Profil superadmin di panel /admin: konfigurasi saluran notifikasi
 * (e-mel, WhatsApp, Telegram) + kata laluan tanpa perlu masuk mana-mana tenant.
 */
class ProfilSaya extends Page
{
    use ProfileActions;

    protected string $view = 'filament.app.pages.profil';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $slug = 'profil-saya';

    protected static ?string $navigationLabel = 'Profil Saya';

    protected static ?string $title = 'Profil Saya';

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

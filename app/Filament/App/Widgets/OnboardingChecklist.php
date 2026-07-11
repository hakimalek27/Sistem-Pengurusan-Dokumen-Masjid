<?php

namespace App\Filament\App\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class OnboardingChecklist extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.app.widgets.onboarding-checklist';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $mosque = Filament::getTenant();

        return $mosque && (Auth::user()?->canIn($mosque, 'mosque.settings') ?? false);
    }

    protected function getViewData(): array
    {
        $mosque = Filament::getTenant();
        $settings = $mosque->settings ?? [];
        $items = [
            ['Tetapan dan telefon masjid', filled($mosque->phone)],
            ['Wakil Perlindungan Data', filled(data_get($settings, 'data_protection_rep.name')) && filled(data_get($settings, 'data_protection_rep.email'))],
            ['Sekurang-kurangnya 3 ahli didaftarkan', $mosque->users()->where('users.is_active', true)->count() >= 3],
            ['Pengerusi ditetapkan', $mosque->users()->wherePivot('role', 'pengerusi')->exists()],
            ['Nombor WhatsApp masjid disambung', filled($mosque->wa_session_id) && filled($mosque->wa_number)],
            ['Klasifikasi fail tersedia', $mosque->classificationNodes()->where('is_active', true)->exists()],
        ];

        return ['items' => $items, 'complete' => collect($items)->where(1, true)->count()];
    }
}

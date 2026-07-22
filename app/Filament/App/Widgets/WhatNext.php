<?php

namespace App\Filament\App\Widgets;

use App\Services\GuidanceService;
use App\Services\UserTaskService;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class WhatNext extends Widget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = -10;

    protected string $view = 'filament.widgets.what-next';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Auth::user();
        $tenant = Filament::getTenant();
        if (! config('diwan.guidance.nudges_enabled') || ! $user || ! $tenant) {
            return false;
        }
        $preference = app(GuidanceService::class)->preference($user, 'app', $tenant);

        return $preference->mode !== 'dimatikan'
            && $preference->nudges_enabled
            && ! ($preference->snoozed_until?->isFuture() ?? false);
    }

    protected function getViewData(): array
    {
        return ['tasks' => app(UserTaskService::class)->for(Auth::user(), 'app', Filament::getTenant()), 'panel' => 'app'];
    }
}

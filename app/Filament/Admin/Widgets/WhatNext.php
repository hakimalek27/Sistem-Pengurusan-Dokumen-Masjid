<?php

namespace App\Filament\Admin\Widgets;

use App\Services\GuidanceService;
use App\Services\UserTaskService;
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
        if (! config('diwan.guidance.nudges_enabled') || ! $user?->is_superadmin) {
            return false;
        }
        $preference = app(GuidanceService::class)->preference($user, 'admin', null);

        return $preference->mode !== 'dimatikan'
            && $preference->nudges_enabled
            && ! ($preference->snoozed_until?->isFuture() ?? false);
    }

    protected function getViewData(): array
    {
        return ['tasks' => app(UserTaskService::class)->for(Auth::user(), 'admin'), 'panel' => 'admin'];
    }
}

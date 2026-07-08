<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RetentionAction: string implements HasColor, HasLabel
{
    case Kekal = 'kekal';
    case Semak = 'semak';
    case AutoPadam = 'auto_padam';

    public function getLabel(): string
    {
        return match ($this) {
            self::Kekal => 'Kekal',
            self::Semak => 'Semak',
            self::AutoPadam => 'Auto Padam',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Kekal => 'success',
            self::Semak => 'warning',
            self::AutoPadam => 'danger',
        };
    }
}

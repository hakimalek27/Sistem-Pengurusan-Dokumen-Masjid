<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ApprovalStatus: string implements HasColor, HasLabel
{
    case Menunggu = 'menunggu';
    case Lulus = 'lulus';
    case Tolak = 'tolak';

    public function getLabel(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu',
            self::Lulus => 'Lulus',
            self::Tolak => 'Tolak',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Menunggu => 'warning',
            self::Lulus => 'success',
            self::Tolak => 'danger',
        };
    }
}

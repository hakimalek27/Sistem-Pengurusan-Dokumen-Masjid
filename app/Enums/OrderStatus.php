<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case MenungguBayaran = 'menunggu_bayaran';
    case Dibayar = 'dibayar';
    case Dibatalkan = 'dibatalkan';

    public function getLabel(): string
    {
        return match ($this) {
            self::MenungguBayaran => 'Menunggu Bayaran',
            self::Dibayar => 'Dibayar',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::MenungguBayaran => 'warning',
            self::Dibayar => 'success',
            self::Dibatalkan => 'gray',
        };
    }
}

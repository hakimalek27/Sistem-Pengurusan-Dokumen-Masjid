<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RecordStatus: string implements HasColor, HasLabel
{
    case PetiMasuk = 'peti_masuk';
    case Difailkan = 'difailkan';
    case Diganti = 'diganti';
    case Dilupus = 'dilupus';

    public function getLabel(): string
    {
        return match ($this) {
            self::PetiMasuk => 'Peti Masuk',
            self::Difailkan => 'Difailkan',
            self::Diganti => 'Diganti',
            self::Dilupus => 'Dilupus',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PetiMasuk => 'gray',
            self::Difailkan => 'success',
            self::Diganti => 'warning',
            self::Dilupus => 'danger',
        };
    }
}

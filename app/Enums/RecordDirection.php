<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RecordDirection: string implements HasLabel
{
    case Masuk = 'masuk';
    case Keluar = 'keluar';
    case Dalaman = 'dalaman';

    public function getLabel(): string
    {
        return match ($this) {
            self::Masuk => 'Masuk',
            self::Keluar => 'Keluar',
            self::Dalaman => 'Dalaman',
        };
    }
}

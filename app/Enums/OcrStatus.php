<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OcrStatus: string implements HasColor, HasLabel
{
    case Belum = 'belum';
    case DalamProses = 'dalam_proses';
    case Siap = 'siap';
    case Gagal = 'gagal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Belum => 'Belum',
            self::DalamProses => 'Dalam Proses',
            self::Siap => 'Siap',
            self::Gagal => 'Gagal',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Belum => 'gray',
            self::DalamProses => 'info',
            self::Siap => 'success',
            self::Gagal => 'danger',
        };
    }
}

<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MosqueStatus: string implements HasColor, HasLabel
{
    case Menunggu = 'menunggu';
    case Aktif = 'aktif';
    case Digantung = 'digantung';
    case Ditutup = 'ditutup';

    public function getLabel(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu',
            self::Aktif => 'Aktif',
            self::Digantung => 'Digantung',
            self::Ditutup => 'Ditutup',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Menunggu => 'warning',
            self::Aktif => 'success',
            self::Digantung => 'danger',
            self::Ditutup => 'gray',
        };
    }
}

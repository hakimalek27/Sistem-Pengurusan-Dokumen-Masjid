<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Sensitivity: string implements HasColor, HasLabel
{
    case Umum = 'umum';
    case Dalaman = 'dalaman';
    case Sulit = 'sulit';

    public function getLabel(): string
    {
        return match ($this) {
            self::Umum => 'Umum',
            self::Dalaman => 'Dalaman',
            self::Sulit => 'Sulit',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Umum => 'success',
            self::Dalaman => 'warning',
            self::Sulit => 'danger',
        };
    }

    /** Pangkat untuk perbandingan (§6.3 waris max). */
    public function rank(): int
    {
        return match ($this) {
            self::Umum => 1,
            self::Dalaman => 2,
            self::Sulit => 3,
        };
    }

    /** Pulangkan sensitiviti paling tinggi antara dua (§6.3). */
    public static function max(self $a, self $b): self
    {
        return $a->rank() >= $b->rank() ? $a : $b;
    }

    /** Tahap yang seseorang boleh lihat, diberi tahap paling sulit dibenarkan. */
    public static function upTo(self $ceiling): array
    {
        return array_filter(self::cases(), fn (self $c) => $c->rank() <= $ceiling->rank());
    }
}

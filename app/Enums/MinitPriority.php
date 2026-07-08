<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MinitPriority: string implements HasColor, HasLabel
{
    case Biasa = 'biasa';
    case Segera = 'segera';
    case Kritikal = 'kritikal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Biasa => 'Biasa',
            self::Segera => 'Segera',
            self::Kritikal => 'Kritikal',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Biasa => 'gray',
            self::Segera => 'warning',
            self::Kritikal => 'danger',
        };
    }

    /** Bilangan hari SLA lalai (§9.C.5, config/diwan.php). */
    public function slaDays(): int
    {
        return (int) config("diwan.sla.{$this->value}", 7);
    }
}

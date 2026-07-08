<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SourceChannel: string implements HasLabel
{
    case MuatNaik = 'muat_naik';
    case Emel = 'emel';
    case WhatsApp = 'whatsapp';
    case Imbasan = 'imbasan';

    public function getLabel(): string
    {
        return match ($this) {
            self::MuatNaik => 'Muat Naik',
            self::Emel => 'E-mel',
            self::WhatsApp => 'WhatsApp',
            self::Imbasan => 'Imbasan',
        };
    }

    /** Badge ikon sumber (§9.C.3). */
    public function badge(): string
    {
        return match ($this) {
            self::MuatNaik => '📤 Muat Naik',
            self::Emel => '📧 E-mel',
            self::WhatsApp => '💬 WhatsApp',
            self::Imbasan => '🖨️ Imbasan',
        };
    }
}

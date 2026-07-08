<?php

namespace App\Filament\App\Resources\RegistryFiles\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RegistryFileInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('file_no')->label('No. Fail'),
                TextEntry::make('title')->label('Tajuk'),
                TextEntry::make('classificationNode.title')->label('Klasifikasi')->placeholder('—'),
                TextEntry::make('sensitivity')->label('Sensitiviti')->badge(),
                TextEntry::make('status')->label('Status')->badge(),
                TextEntry::make('enclosure_count')->label('Bil. Kandungan'),
                TextEntry::make('volume')->label('Jilid'),
                TextEntry::make('opened_at')->label('Dibuka')->dateTime('d/m/Y')->placeholder('—'),
            ])
            ->columns(2);
    }
}

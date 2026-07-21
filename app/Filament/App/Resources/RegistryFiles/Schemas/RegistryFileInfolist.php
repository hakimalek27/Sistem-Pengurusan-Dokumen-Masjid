<?php

namespace App\Filament\App\Resources\RegistryFiles\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
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
                TextEntry::make('medium')->label('Medium')->badge(),
                TextEntry::make('physical_reference')->label('Rujukan Fizikal')->placeholder('—'),
                TextEntry::make('physical_location')->label('Lokasi')->placeholder('—'),
                TextEntry::make('custody_status')->label('Penjagaan')->badge(),
                TextEntry::make('currentHolder.name')->label('Pemegang')->placeholder(fn ($record) => $record->current_holder_name ?: '—'),
                TextEntry::make('custody_due_at')->label('Perlu Pulang')->dateTime('d/m/Y H:i')->placeholder('—'),
                RepeatableEntry::make('movements')->label('Sejarah Pergerakan')->schema([
                    TextEntry::make('action')->label('Tindakan')->badge(),
                    TextEntry::make('from_location')->label('Dari')->placeholder('—'),
                    TextEntry::make('to_location')->label('Ke')->placeholder('—'),
                    TextEntry::make('holder_name')->label('Pemegang')->placeholder('—'),
                    TextEntry::make('handledBy.name')->label('Dikendalikan Oleh'),
                    TextEntry::make('created_at')->label('Masa')->dateTime('d/m/Y H:i'),
                    TextEntry::make('notes')->label('Catatan')->placeholder('—')->columnSpanFull(),
                ])->columns(3)->columnSpanFull(),
            ])
            ->columns(2);
    }
}

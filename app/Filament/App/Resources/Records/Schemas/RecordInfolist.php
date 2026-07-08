<?php

namespace App\Filament\App\Resources\Records\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class RecordInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Rekod')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Maklumat')
                            ->schema([
                                TextEntry::make('title')->label('Tajuk'),
                                TextEntry::make('record_type')->label('Jenis')
                                    ->formatStateUsing(fn ($state) => config("record_types.{$state}.label", $state))->badge(),
                                TextEntry::make('registryFile.file_no')->label('Fail')->placeholder('—'),
                                TextEntry::make('enclosure_no')->label('No. Kandungan')->placeholder('—'),
                                TextEntry::make('our_ref')->label('Ruj. Kami')->placeholder('—'),
                                TextEntry::make('their_ref')->label('Ruj. Tuan')->placeholder('—'),
                                TextEntry::make('record_date')->label('Tarikh Rekod')->date('d/m/Y')->placeholder('—'),
                                TextEntry::make('sender_name')->label('Pengirim')->placeholder('—'),
                                TextEntry::make('sensitivity')->label('Sensitiviti')->badge(),
                                TextEntry::make('status')->label('Status')->badge(),
                                KeyValueEntry::make('metadata')->label('Medan Khusus Jenis')->columnSpanFull(),
                            ])
                            ->columns(2),
                        Tab::make('Teks OCR')
                            ->schema([
                                TextEntry::make('ocr_status')->label('Status OCR')->badge(),
                                TextEntry::make('ocr_text')->label('Teks Diekstrak')
                                    ->placeholder('Belum ada teks OCR.')
                                    ->columnSpanFull(),
                            ]),
                        Tab::make('Lampiran & Versi')
                            ->schema([
                                TextEntry::make('_lampiran')->hiddenLabel()
                                    ->state('Pratonton & muat turun fail — Fasa 5 (signed URL / SecureFileController).'),
                            ]),
                        Tab::make('Minit')
                            ->schema([
                                TextEntry::make('_minit')->hiddenLabel()->state('Modul minit — Fasa 4.'),
                            ]),
                        Tab::make('Kelulusan')
                            ->schema([
                                TextEntry::make('_kelulusan')->hiddenLabel()->state('Modul kelulusan — Fasa 6.'),
                            ]),
                    ]),
            ]);
    }
}

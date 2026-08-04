<?php

namespace App\Filament\App\Resources\ClassificationNodes\Schemas;

use App\Enums\Sensitivity;
use App\Models\ClassificationNode;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ClassificationNodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // §15.2 — Select relationship DISKOP tenant secara eksplisit.
                // Nota Filament v4: skop query MESTI dihantar sebagai argumen ke-3
                // relationship() (modifyQueryUsing) — TIADA method berantai ->modifyQueryUsing()
                // pada Select (BadMethodCallException). Global scope BelongsToMosque juga aktif.
                // F6-W1 (§7.2) — sasaran tour spesifik. Prefix `classnode-` SENGAJA bukan
                // `classification-`: `help.js:204` memaksa mana-mana sasaran `classification-*`
                // naik ke `.fi-modal-window` terdekat (peraturan wizard peti masuk), yang salah
                // untuk borang halaman penuh ini.
                Select::make('parent_id')
                    ->label('Nod Induk')
                    ->relationship('parent', 'title', fn ($query) => $query->where('mosque_id', Filament::getTenant()?->id))
                    ->searchable()
                    ->disabled(fn (?ClassificationNode $record) => $record?->isUsed() ?? false)
                    ->extraFieldWrapperAttributes(['data-help-target' => 'classnode-parent'])
                    ->nullable(),
                Select::make('level')
                    ->label('Peringkat')
                    ->options([
                        'fungsi' => 'Fungsi',
                        'aktiviti' => 'Aktiviti',
                        'sub_aktiviti' => 'Sub-Aktiviti',
                    ])
                    ->disabled(fn (?ClassificationNode $record) => $record?->isUsed() ?? false)
                    ->extraFieldWrapperAttributes(['data-help-target' => 'classnode-level'])
                    ->required(),
                TextInput::make('code')
                    ->label('Kod')
                    ->helperText('cth 500 (fungsi), 500-1 (aktiviti), 500-1/2 (sub)')
                    ->disabled(fn (?ClassificationNode $record) => $record?->isUsed() ?? false)
                    ->extraFieldWrapperAttributes(['data-help-target' => 'classnode-code'])
                    ->required(),
                TextInput::make('title')
                    ->label('Tajuk')
                    ->extraFieldWrapperAttributes(['data-help-target' => 'classnode-title'])
                    ->required(),
                Select::make('default_sensitivity')
                    ->label('Sensitiviti Lalai')
                    ->options(collect(Sensitivity::cases())->mapWithKeys(fn (Sensitivity $c) => [$c->value => $c->getLabel()]))
                    ->default('dalaman')
                    ->extraFieldWrapperAttributes(['data-help-target' => 'classnode-sensitivity'])
                    ->required(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->helperText('Nyahaktifkan nod lama; rekod dan fail sedia ada kekal.')
                    ->default(true),
                TextInput::make('sort')
                    ->label('Susunan')
                    ->numeric()
                    ->default(0),
            ]);
    }
}

<?php

namespace App\Filament\App\Resources\ClassificationNodes\Schemas;

use App\Enums\Sensitivity;
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
                Select::make('parent_id')
                    ->label('Nod Induk')
                    ->relationship('parent', 'title')
                    ->modifyQueryUsing(fn ($query) => $query->where('mosque_id', Filament::getTenant()?->id))
                    ->searchable()
                    ->nullable(),
                Select::make('level')
                    ->label('Peringkat')
                    ->options([
                        'fungsi' => 'Fungsi',
                        'aktiviti' => 'Aktiviti',
                        'sub_aktiviti' => 'Sub-Aktiviti',
                    ])
                    ->required(),
                TextInput::make('code')
                    ->label('Kod')
                    ->helperText('cth 500 (fungsi), 500-1 (aktiviti), 500-1/2 (sub)')
                    ->required(),
                TextInput::make('title')
                    ->label('Tajuk')
                    ->required(),
                Select::make('default_sensitivity')
                    ->label('Sensitiviti Lalai')
                    ->options(collect(Sensitivity::cases())->mapWithKeys(fn (Sensitivity $c) => [$c->value => $c->getLabel()]))
                    ->default('dalaman')
                    ->required(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                TextInput::make('sort')
                    ->label('Susunan')
                    ->numeric()
                    ->default(0),
            ]);
    }
}

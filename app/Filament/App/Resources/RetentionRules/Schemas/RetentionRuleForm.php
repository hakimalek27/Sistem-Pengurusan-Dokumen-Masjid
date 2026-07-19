<?php

namespace App\Filament\App\Resources\RetentionRules\Schemas;

use App\Enums\RetentionAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RetentionRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('mosque_id')
                    ->default(fn () => Filament::getTenant()?->id),
                Select::make('record_type')
                    ->label('Jenis Rekod')
                    ->options(collect(config('record_types'))->mapWithKeys(fn ($t, $k) => [$k => $t['label']]))
                    ->nullable(),
                TextInput::make('classification_prefix')
                    ->label('Prefix Klasifikasi')
                    ->nullable(),
                TextInput::make('retain_years')
                    ->label('Tahun Simpanan')
                    ->numeric()
                    ->nullable(),
                Select::make('action')
                    ->label('Tindakan')
                    ->options(RetentionAction::class)
                    ->required(),
                TextInput::make('note')
                    ->label('Catatan')
                    ->nullable(),
            ]);
    }
}

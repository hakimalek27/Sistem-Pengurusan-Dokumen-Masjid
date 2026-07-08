<?php

namespace App\Filament\App\Resources\RetentionRules\Schemas;

use App\Enums\RetentionAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RetentionRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('mosque_id')
                    ->relationship('mosque', 'name'),
                TextInput::make('record_type'),
                TextInput::make('classification_prefix'),
                TextInput::make('retain_years')
                    ->numeric(),
                Select::make('action')
                    ->options(RetentionAction::class)
                    ->required(),
                TextInput::make('note'),
            ]);
    }
}

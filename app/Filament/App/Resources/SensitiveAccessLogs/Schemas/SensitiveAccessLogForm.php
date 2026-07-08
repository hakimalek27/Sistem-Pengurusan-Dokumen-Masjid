<?php

namespace App\Filament\App\Resources\SensitiveAccessLogs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SensitiveAccessLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('mosque_id')
                    ->relationship('mosque', 'name')
                    ->required(),
                Toggle::make('is_superadmin')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name'),
                Select::make('record_id')
                    ->relationship('record', 'title'),
                TextInput::make('action')
                    ->required(),
                TextInput::make('ip'),
                Textarea::make('user_agent')
                    ->columnSpanFull(),
            ]);
    }
}

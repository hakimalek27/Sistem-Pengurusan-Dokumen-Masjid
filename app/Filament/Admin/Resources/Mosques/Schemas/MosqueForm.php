<?php

namespace App\Filament\Admin\Resources\Mosques\Schemas;

use App\Enums\MosqueStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MosqueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('code')
                    ->required(),
                TextInput::make('state'),
                TextInput::make('district'),
                TextInput::make('address'),
                TextInput::make('phone')
                    ->tel(),
                Select::make('status')
                    ->options(MosqueStatus::class)
                    ->default('menunggu')
                    ->required(),
                TextInput::make('storage_quota_bytes')
                    ->required()
                    ->numeric()
                    ->default(21474836480),
                TextInput::make('storage_used_bytes')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('auto_disposal_enabled')
                    ->required(),
                DateTimePicker::make('retention_ack_at'),
                TextInput::make('retention_ack_by')
                    ->numeric(),
                TextInput::make('wa_session_id')
                    ->label('Sesi WhatsApp (urus di panel tenant)')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('wa_number')
                    ->label('Nombor WhatsApp (urus di panel tenant)')
                    ->disabled()
                    ->dehydrated(false),
                Textarea::make('settings')
                    ->required()
                    ->default('{}')
                    ->columnSpanFull(),
                DateTimePicker::make('approved_at'),
                TextInput::make('approved_by')
                    ->numeric(),
            ]);
    }
}

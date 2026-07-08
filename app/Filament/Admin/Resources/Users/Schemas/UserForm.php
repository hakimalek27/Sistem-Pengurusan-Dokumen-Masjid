<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password(),
                Toggle::make('is_superadmin')
                    ->required(),
                TextInput::make('phone_wa')
                    ->tel(),
                TextInput::make('telegram_chat_id')
                    ->tel(),
                TextInput::make('jawatan'),
                Toggle::make('notify_whatsapp')
                    ->required(),
                Toggle::make('notify_telegram')
                    ->required(),
                Toggle::make('notify_email')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
                DateTimePicker::make('last_login_at'),
            ]);
    }
}

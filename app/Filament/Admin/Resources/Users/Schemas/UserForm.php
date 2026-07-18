<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identiti & Akses')
                    ->schema([
                        TextInput::make('name')->label('Nama')->required()->maxLength(255),
                        TextInput::make('email')->label('Alamat E-mel (pilihan)')->email()->unique(ignoreRecord: true)->maxLength(255)
                            ->helperText('Pilihan — akaun boleh guna telefon sahaja.'),
                        TextInput::make('password')
                            ->label('Kata Laluan')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->helperText('Biarkan kosong semasa edit untuk mengekalkan kata laluan sedia ada.'),
                        Toggle::make('is_active')->label('Akaun Aktif')->default(true),
                        Toggle::make('is_superadmin')->label('Superadmin Platform')->default(false),
                        DateTimePicker::make('email_verified_at')->label('E-mel Disahkan Pada'),
                        DateTimePicker::make('last_login_at')->label('Log Masuk Terakhir')->disabled()->dehydrated(false),
                    ])
                    ->columns(2),
                Section::make('Profil & Notifikasi')
                    ->schema([
                        TextInput::make('phone_wa')->label('Nombor WhatsApp')->tel()->maxLength(20),
                        TextInput::make('telegram_chat_id')->label('ID Chat Telegram')->maxLength(255),
                        TextInput::make('jawatan')->label('Jawatan')->maxLength(255),
                        Toggle::make('notify_whatsapp')->label('Notifikasi WhatsApp'),
                        Toggle::make('notify_telegram')->label('Notifikasi Telegram'),
                        Toggle::make('notify_email')->label('Notifikasi E-mel')->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}

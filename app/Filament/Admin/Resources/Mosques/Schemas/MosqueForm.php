<?php

namespace App\Filament\Admin\Resources\Mosques\Schemas;

use App\Enums\MosqueStatus;
use App\Models\Mosque;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MosqueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identiti Tenant')
                    ->description('Kod dan slug hendaklah stabil. Kod tidak boleh ditukar selepas fail registri digunakan.')
                    ->schema([
                        TextInput::make('name')->label('Nama Masjid / Organisasi')->required()->maxLength(255),
                        TextInput::make('slug')->label('Slug URL')->required()->alphaDash()->unique(ignoreRecord: true)->maxLength(255),
                        TextInput::make('code')->label('Kod Akronim')->required()->alpha()->unique(ignoreRecord: true)->minLength(3)->maxLength(6)
                            ->disabled(fn (?Mosque $record): bool => $record?->registryFiles()->exists() ?? false)
                            ->dehydrated(fn (?Mosque $record): bool => ! ($record?->registryFiles()->exists() ?? false)),
                        TextInput::make('state')->label('Negeri')->maxLength(255),
                        TextInput::make('district')->label('Daerah')->maxLength(255),
                        TextInput::make('address')->label('Alamat')->maxLength(255),
                        TextInput::make('phone')->label('Telefon Organisasi')->tel()->maxLength(20),
                    ])
                    ->columns(2),
                Section::make('Kawalan Akaun')
                    ->schema([
                        Select::make('status')->label('Status Tenant')->options(MosqueStatus::class)->required(),
                        Toggle::make('auto_disposal_enabled')
                            ->label('Pelupusan Automatik')
                            ->helperText('Kekalkan dimatikan sehingga pengesahan retensi dan UAT pelupusan selesai.'),
                        TextInput::make('storage_quota_bytes')
                            ->label('Kuota Asas (bait)')
                            ->helperText('Gunakan tindakan “Ubah Kuota” pada senarai tenant untuk perubahan dalam GB dan sebab audit.')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('storage_used_bytes')
                            ->label('Storan Digunakan (bait)')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
                Section::make('Integrasi (Baca Sahaja)')
                    ->schema([
                        TextInput::make('wa_session_id')->label('ID Sesi WhatsApp')->disabled()->dehydrated(false),
                        TextInput::make('wa_number')->label('Nombor WhatsApp Dipasang')->disabled()->dehydrated(false),
                    ])
                    ->columns(2),
            ]);
    }
}

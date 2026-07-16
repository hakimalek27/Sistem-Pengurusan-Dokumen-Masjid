<?php

namespace App\Filament\Admin\Resources\Mosques\Schemas;

use App\Models\Mosque;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MosqueInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')->label('Nama Tenant'),
                TextEntry::make('slug')->label('Slug URL'),
                TextEntry::make('code')->label('Kod Akronim'),
                TextEntry::make('state')->label('Negeri')
                    ->placeholder('-'),
                TextEntry::make('district')->label('Daerah')
                    ->placeholder('-'),
                TextEntry::make('address')->label('Alamat')
                    ->placeholder('-'),
                TextEntry::make('phone')->label('Telefon')
                    ->placeholder('-'),
                TextEntry::make('status')->label('Status')
                    ->badge(),
                TextEntry::make('usage_summary')->label('Penggunaan Storan')
                    ->state(fn (Mosque $record) => round($record->storage_used_bytes / (1024 ** 3), 2).' / '.round($record->effectiveQuotaBytes() / (1024 ** 3), 2).' GB'),
                TextEntry::make('member_count')->label('Bilangan Ahli')
                    ->state(fn (Mosque $record) => $record->users()->count()),
                TextEntry::make('record_count')->label('Bilangan Rekod')
                    ->state(fn (Mosque $record) => $record->records()->count()),
                TextEntry::make('file_count')->label('Bilangan Fail Registri')
                    ->state(fn (Mosque $record) => $record->registryFiles()->count()),
                IconEntry::make('auto_disposal_enabled')->label('Pelupusan Automatik')
                    ->boolean(),
                TextEntry::make('retention_ack_at')->label('Akuan Retensi')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('retentionAckBy.name')->label('Akuan Oleh')
                    ->placeholder('-'),
                TextEntry::make('whatsappIntegration.status')->label('Status WhatsApp')
                    ->placeholder('-'),
                TextEntry::make('wa_number')->label('Nombor WhatsApp')
                    ->placeholder('-'),
                IconEntry::make('mail_intake')->label('Intake E-mel')
                    ->state(fn (Mosque $record) => $record->mailIntakeEnabled())
                    ->boolean(),
                TextEntry::make('approved_at')->label('Diluluskan Pada')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('approvedBy.name')->label('Diluluskan Oleh')
                    ->placeholder('-'),
                TextEntry::make('created_at')->label('Dicipta')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')->label('Dikemas Kini')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')->label('Diarkibkan Pada')
                    ->dateTime()
                    ->visible(fn (Mosque $record): bool => $record->trashed()),
            ]);
    }
}

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
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('code'),
                TextEntry::make('state')
                    ->placeholder('-'),
                TextEntry::make('district')
                    ->placeholder('-'),
                TextEntry::make('address')
                    ->placeholder('-'),
                TextEntry::make('phone')
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('storage_quota_bytes')
                    ->numeric(),
                TextEntry::make('storage_used_bytes')
                    ->numeric(),
                IconEntry::make('auto_disposal_enabled')
                    ->boolean(),
                TextEntry::make('retention_ack_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('retention_ack_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('wa_session_id')
                    ->placeholder('-'),
                TextEntry::make('wa_number')
                    ->placeholder('-'),
                TextEntry::make('settings')
                    ->columnSpanFull(),
                TextEntry::make('approved_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('approved_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Mosque $record): bool => $record->trashed()),
            ]);
    }
}

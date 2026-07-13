<?php

namespace App\Filament\App\Resources\Inbox;

use App\Enums\RecordStatus;
use App\Filament\App\Resources\Inbox\Pages\ListInbox;
use App\Filament\App\Resources\Inbox\Pages\ViewInbox;
use App\Filament\App\Resources\Inbox\Tables\InboxTable;
use App\Filament\App\Resources\Records\Schemas\RecordInfolist;
use App\Models\Record;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class InboxResource extends Resource
{
    protected static ?string $model = Record::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?string $slug = 'peti-masuk';

    protected static ?string $navigationLabel = 'Peti Masuk';

    protected static ?string $modelLabel = 'Item Peti Masuk';

    protected static ?string $pluralModelLabel = 'Peti Masuk';

    protected static string|UnitEnum|null $navigationGroup = 'Registri';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('status', RecordStatus::PetiMasuk->value);
    }

    public static function canViewAny(): bool
    {
        $mosque = Filament::getTenant();

        return $mosque && (Auth::user()?->canIn($mosque, 'inbox.view') ?? false);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return InboxTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RecordInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInbox::route('/'),
            'view' => ViewInbox::route('/{record}'),
        ];
    }
}

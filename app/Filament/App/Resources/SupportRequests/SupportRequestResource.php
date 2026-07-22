<?php

namespace App\Filament\App\Resources\SupportRequests;

use App\Filament\App\Resources\SupportRequests\Pages\ListSupportRequests;
use App\Filament\App\Resources\SupportRequests\Pages\ViewSupportRequest;
use App\Filament\Support\SupportRequestInfolist;
use App\Filament\Support\SupportRequestsTable;
use App\Models\SupportRequest;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class SupportRequestResource extends Resource
{
    protected static ?string $model = SupportRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static string|UnitEnum|null $navigationGroup = 'Bantuan';

    protected static ?string $navigationLabel = 'Tiket Sokongan';

    protected static ?string $pluralModelLabel = 'Tiket Sokongan';

    protected static ?string $slug = 'tiket-sokongan';

    public static function canViewAny(): bool
    {
        $tenant = Filament::getTenant();

        return config('diwan.guidance.support_enabled')
            && $tenant
            && (Auth::user()?->canIn($tenant, 'support.manage') ?? false);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('mosque_id', Filament::getTenant()->id);
    }

    public static function table(Table $table): Table
    {
        return SupportRequestsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SupportRequestInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return ['index' => ListSupportRequests::route('/'), 'view' => ViewSupportRequest::route('/{record}')];
    }
}

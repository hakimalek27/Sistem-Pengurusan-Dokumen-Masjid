<?php

namespace App\Filament\App\Resources\Delegations;

use App\Filament\App\Resources\Delegations\Pages\CreateDelegation;
use App\Filament\App\Resources\Delegations\Pages\ListDelegations;
use App\Filament\App\Resources\Delegations\Schemas\DelegationForm;
use App\Filament\App\Resources\Delegations\Tables\DelegationsTable;
use App\Models\Delegation;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class DelegationResource extends Resource
{
    protected static ?string $model = Delegation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $navigationLabel = 'Delegasi';

    protected static ?string $modelLabel = 'Delegasi';

    protected static ?string $pluralModelLabel = 'Delegasi';

    protected static string|UnitEnum|null $navigationGroup = 'Akaun';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'delegasi';

    public static function form(Schema $schema): Schema
    {
        return DelegationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DelegationsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();
        $user = Auth::user();
        $query = parent::getEloquentQuery()->where('mosque_id', $tenant?->id);

        return $user?->canIn($tenant, 'users.manage') ? $query : $query->where(function (Builder $q) use ($user) {
            $q->where('principal_user_id', $user->id)->orWhere('delegate_user_id', $user->id);
        });
    }

    public static function getPages(): array
    {
        return ['index' => ListDelegations::route('/'), 'create' => CreateDelegation::route('/create')];
    }
}

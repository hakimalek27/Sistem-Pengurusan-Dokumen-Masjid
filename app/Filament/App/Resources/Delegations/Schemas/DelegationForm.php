<?php

namespace App\Filament\App\Resources\Delegations\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class DelegationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('principal_user_id')->label('Principal')->options(fn () => self::principalUsers())->searchable()->required(),
            Select::make('delegate_user_id')->label('Delegate')->options(fn () => self::users())->searchable()->required(),
            Select::make('capabilities')->label('Tugas yang diwakilkan')->multiple()->options(['minit' => 'Minit / tindakan', 'approvals' => 'Keputusan kelulusan'])->required(),
            DateTimePicker::make('starts_at')->label('Mula')->seconds(false)->default(now())->required(),
            DateTimePicker::make('ends_at')->label('Tamat')->seconds(false)->default(now()->addDays(7))->required(),
            Textarea::make('reason')->label('Sebab / catatan')->maxLength(1000),
        ])->columns(2);
    }

    protected static function users(): array
    {
        return Filament::getTenant()?->users()->where('users.is_active', true)->orderBy('name')->pluck('name', 'users.id')->all() ?? [];
    }

    protected static function principalUsers(): array
    {
        $tenant = Filament::getTenant();
        $user = Auth::user();
        if (! $tenant || ! $user) {
            return [];
        }

        return $user->canIn($tenant, 'users.manage')
            ? self::users()
            : [$user->id => $user->name];
    }
}

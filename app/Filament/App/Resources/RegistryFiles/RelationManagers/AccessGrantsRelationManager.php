<?php

namespace App\Filament\App\Resources\RegistryFiles\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

// §9.C.4 / §6.3 — Akses khas fail sulit kepada individu (file_access_grants).
class AccessGrantsRelationManager extends RelationManager
{
    protected static string $relationship = 'accessGrants';

    protected static ?string $title = 'Akses Khas (Fail Sulit)';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('Ahli Masjid')
                ->options(fn () => Filament::getTenant()->users()->pluck('name', 'users.id'))
                ->searchable()
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('user.name')->label('Ahli'),
                TextColumn::make('grantedBy.name')->label('Diberi Oleh')->placeholder('—'),
                TextColumn::make('created_at')->label('Tarikh')->dateTime('d/m/Y'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Beri Akses')
                    ->authorize('create')
                    ->mutateDataUsing(function (array $data) {
                        $data['granted_by'] = Auth::id();

                        return $data;
                    }),
            ])
            ->recordActions([
                DeleteAction::make()->label('Tarik Balik')->authorize('delete'),
            ]);
    }
}

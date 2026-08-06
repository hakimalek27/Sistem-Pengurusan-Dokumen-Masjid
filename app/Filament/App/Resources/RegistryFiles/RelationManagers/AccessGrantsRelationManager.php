<?php

namespace App\Filament\App\Resources\RegistryFiles\RelationManagers;

use Filament\Actions\Action;
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
                // F6-W1 (§7.2) — `screen.beri-akses-khas-fail-sulit`.
                ->extraFieldWrapperAttributes(['data-help-target' => 'file-access-member'])
                ->required(),
        ]);
    }

    /** F6-W1 — sasaran hanya pada baris pertama (rujuk MinitsTable::baris1). */
    protected static ?int $barisPertamaId = null;

    protected static function baris1($record, string $target): array
    {
        self::$barisPertamaId ??= (int) $record->getKey();

        return self::$barisPertamaId === (int) $record->getKey()
            ? ['data-help-target' => $target]
            : [];
    }

    public function table(Table $table): Table
    {
        // F6-W5: lihat nota sama dalam MosqueActivityLogsTable — memo statik mesti diset
        // semula pada titik masuk render, bukan bergantung pada kitaran permintaan.
        self::$barisPertamaId = null;

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
                    ->extraAttributes(['data-help-target' => 'file-access-grant'])
                    ->modalSubmitAction(fn (Action $action): Action => $action
                        ->extraAttributes(['data-help-target' => 'file-access-submit']))
                    ->authorize('create')
                    ->mutateDataUsing(function (array $data) {
                        $data['granted_by'] = Auth::id();

                        return $data;
                    }),
            ])
            ->recordActions([
                DeleteAction::make()->label('Tarik Balik')
                    ->extraAttributes(fn ($record): array => self::baris1($record, 'file-access-revoke'))
                    ->authorize('delete'),
            ]);
    }
}

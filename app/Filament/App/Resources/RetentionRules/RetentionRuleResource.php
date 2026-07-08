<?php

namespace App\Filament\App\Resources\RetentionRules;

use App\Enums\RetentionAction;
use App\Filament\App\Resources\RetentionRules\Pages\CreateRetentionRule;
use App\Filament\App\Resources\RetentionRules\Pages\EditRetentionRule;
use App\Filament\App\Resources\RetentionRules\Pages\ListRetentionRules;
use App\Models\RetentionRule;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RetentionRuleResource extends Resource
{
    protected static ?string $model = RetentionRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $slug = 'retensi-peraturan';

    protected static ?string $navigationLabel = 'Peraturan Retensi';

    protected static ?string $modelLabel = 'Peraturan Retensi (Override)';

    protected static ?string $pluralModelLabel = 'Peraturan Retensi';

    protected static string|UnitEnum|null $navigationGroup = 'Pentadbiran';

    protected static ?int $navigationSort = 5;

    /** Hanya peraturan OVERRIDE masjid ini (bukan lalai platform). */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('mosque_id', Filament::getTenant()?->id);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('record_type')
                ->label('Jenis Rekod (kosong = ikut prefix)')
                ->options(collect(config('record_types'))->mapWithKeys(fn ($t, $k) => [$k => $t['label']]))
                ->nullable(),
            TextInput::make('classification_prefix')->label('Prefix Klasifikasi (cth 200)')->nullable(),
            TextInput::make('retain_years')->label('Tahun Simpanan (kosong = kekal)')->numeric()->nullable(),
            Select::make('action')->label('Tindakan')
                ->options(collect(RetentionAction::cases())->mapWithKeys(fn ($c) => [$c->value => $c->getLabel()]))
                ->default('auto_padam')
                ->helperText('AMARAN: menukar jenis kekal kepada auto_padam membenarkan pemadaman automatik.')
                ->required(),
            TextInput::make('note')->label('Catatan')->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('record_type')->label('Jenis')->placeholder('—'),
            TextColumn::make('classification_prefix')->label('Prefix')->placeholder('—'),
            TextColumn::make('retain_years')->label('Tahun')->placeholder('Kekal'),
            TextColumn::make('action')->label('Tindakan')->badge(),
        ])->recordActions([
            EditAction::make(),
            DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRetentionRules::route('/'),
            'create' => CreateRetentionRule::route('/create'),
            'edit' => EditRetentionRule::route('/{record}/edit'),
        ];
    }
}

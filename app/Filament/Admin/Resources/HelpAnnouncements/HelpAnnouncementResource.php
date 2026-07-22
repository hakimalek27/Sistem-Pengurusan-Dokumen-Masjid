<?php

namespace App\Filament\Admin\Resources\HelpAnnouncements;

use App\Filament\Admin\Resources\HelpAnnouncements\Pages\CreateHelpAnnouncement;
use App\Filament\Admin\Resources\HelpAnnouncements\Pages\EditHelpAnnouncement;
use App\Filament\Admin\Resources\HelpAnnouncements\Pages\ListHelpAnnouncements;
use App\Models\HelpAnnouncement;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class HelpAnnouncementResource extends Resource
{
    protected static ?string $model = HelpAnnouncement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Bantuan';

    protected static ?string $navigationLabel = 'Makluman Bantuan';

    protected static ?string $modelLabel = 'Makluman Bantuan';

    protected static ?string $pluralModelLabel = 'Makluman Bantuan';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Makluman Pelengkap')
                ->description('Makluman ini tidak mengubah atau menindih langkah workflow keselamatan dalam katalog versi kod.')
                ->schema([
                    Select::make('mosque_id')->label('Tenant Khusus')->relationship('mosque', 'name')->searchable()->preload()
                        ->helperText('Kosongkan untuk makluman global.'),
                    Select::make('panel')->options(['all' => 'Semua', 'public' => 'Awam', 'app' => 'Panel Masjid', 'admin' => 'Superadmin'])->default('all')->required(),
                    Select::make('roles')->label('Role')->multiple()->options(config('roles.labels'))->helperText('Kosongkan untuk semua role pada panel dipilih.'),
                    TextInput::make('title')->label('Tajuk')->required()->maxLength(255)->columnSpanFull(),
                    Textarea::make('body')->label('Kandungan')->required()->maxLength(3000)->rows(5)->columnSpanFull(),
                    DateTimePicker::make('starts_at')->label('Mula')->seconds(false),
                    DateTimePicker::make('ends_at')->label('Tamat')->seconds(false)->after('starts_at'),
                    Toggle::make('is_active')->label('Aktif')->default(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            TextColumn::make('title')->label('Tajuk')->searchable()->wrap(),
            TextColumn::make('panel')->label('Panel')->badge(),
            TextColumn::make('mosque.name')->label('Tenant')->placeholder('Global'),
            TextColumn::make('starts_at')->label('Mula')->dateTime('d/m/Y H:i')->placeholder('Serta-merta'),
            TextColumn::make('ends_at')->label('Tamat')->dateTime('d/m/Y H:i')->placeholder('Tiada tamat'),
            IconColumn::make('is_active')->label('Aktif')->boolean(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHelpAnnouncements::route('/'),
            'create' => CreateHelpAnnouncement::route('/create'),
            'edit' => EditHelpAnnouncement::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\App\Resources\RecordCorrections;

use App\Filament\App\Resources\RecordCorrections\Pages\ListRecordCorrections;
use App\Filament\App\Resources\RecordCorrections\Tables\RecordCorrectionsTable;
use App\Models\Record;
use App\Models\RecordCorrectionRequest;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class RecordCorrectionResource extends Resource
{
    protected static ?string $model = RecordCorrectionRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?string $navigationLabel = 'Pembetulan Rekod';

    protected static ?string $modelLabel = 'Pembetulan Rekod';

    protected static ?string $pluralModelLabel = 'Pembetulan Rekod';

    protected static string|UnitEnum|null $navigationGroup = 'Tugasan';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'pembetulan-rekod';

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $mosque = Filament::getTenant();
        $recordIds = Record::query()->visibleTo($user, $mosque)->pluck('id');
        $query = parent::getEloquentQuery()->whereIn('record_id', $recordIds);

        return $user->canIn($mosque, 'records.update') ? $query : $query->where('requested_by', $user->id);
    }

    public static function table(Table $table): Table
    {
        return RecordCorrectionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListRecordCorrections::route('/')];
    }
}

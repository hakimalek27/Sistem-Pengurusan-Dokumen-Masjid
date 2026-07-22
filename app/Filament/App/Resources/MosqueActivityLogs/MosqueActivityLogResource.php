<?php

namespace App\Filament\App\Resources\MosqueActivityLogs;

use App\Filament\App\Resources\MosqueActivityLogs\Pages\ListMosqueActivityLogs;
use App\Filament\App\Resources\MosqueActivityLogs\Tables\MosqueActivityLogsTable;
use App\Models\MosqueActivityLog;
use App\Models\Record;
use App\Models\RegistryFile;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class MosqueActivityLogResource extends Resource
{
    protected static ?string $model = MosqueActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Log Aktiviti Masjid';

    protected static ?string $modelLabel = 'Log Aktiviti';

    protected static ?string $pluralModelLabel = 'Log Aktiviti Masjid';

    protected static string|UnitEnum|null $navigationGroup = 'Tugasan';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'log-aktiviti';

    public static function getEloquentQuery(): Builder
    {
        $mosque = Filament::getTenant();
        $user = Auth::user();

        $query = parent::getEloquentQuery()
            ->withoutGlobalScope('mosque')
            ->where('mosque_activity_logs.mosque_id', $mosque->id);

        // Bendahari boleh memantau aktiviti organisasi, tetapi tajuk rekod/fail
        // di luar skop kewangan atau geran aksesnya tidak boleh bocor melalui log.
        if ($user->roleIn($mosque) === 'bendahari') {
            $visibleRecordIds = Record::query()->visibleTo($user, $mosque)->pluck('id');
            $visibleFileIds = RegistryFile::query()->visibleTo($user, $mosque)->pluck('id');

            $query
                ->where(fn (Builder $records) => $records
                    ->whereNull('record_id')
                    ->orWhereIn('record_id', $visibleRecordIds))
                ->where(fn (Builder $files) => $files
                    ->whereNull('registry_file_id')
                    ->orWhereIn('registry_file_id', $visibleFileIds));
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return MosqueActivityLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListMosqueActivityLogs::route('/')];
    }
}

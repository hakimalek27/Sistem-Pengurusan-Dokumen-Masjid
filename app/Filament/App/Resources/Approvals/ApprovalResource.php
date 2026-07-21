<?php

namespace App\Filament\App\Resources\Approvals;

use App\Filament\App\Resources\Approvals\Pages\ListApprovals;
use App\Filament\App\Resources\Approvals\Tables\ApprovalsTable;
use App\Models\Approval;
use App\Models\Record;
use App\Services\DelegationService;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ApprovalResource extends Resource
{
    protected static ?string $model = Approval::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static ?string $slug = 'kelulusan';

    protected static ?string $navigationLabel = 'Kelulusan';

    protected static ?string $modelLabel = 'Kelulusan';

    protected static ?string $pluralModelLabel = 'Kelulusan';

    protected static string|UnitEnum|null $navigationGroup = 'Tugasan';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        // Kelulusan yang ditujukan kepada saya (§9.C.7).
        $recordIds = Record::query()->visibleTo(Auth::user(), Filament::getTenant())->pluck('id');

        $ids = collect([Auth::id()])->merge(app(DelegationService::class)->principalIdsFor(Auth::user(), Filament::getTenant(), 'approvals'))->unique();

        return parent::getEloquentQuery()->whereIn('approver_id', $ids)->whereIn('record_id', $recordIds);
    }

    public static function getNavigationBadge(): ?string
    {
        $recordIds = Record::query()->visibleTo(Auth::user(), Filament::getTenant())->pluck('id');
        $ids = collect([Auth::id()])->merge(app(DelegationService::class)->principalIdsFor(Auth::user(), Filament::getTenant(), 'approvals'))->unique();
        $count = Approval::query()->whereIn('record_id', $recordIds)->whereIn('approver_id', $ids)->where('status', 'menunggu')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return ApprovalsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApprovals::route('/'),
        ];
    }
}

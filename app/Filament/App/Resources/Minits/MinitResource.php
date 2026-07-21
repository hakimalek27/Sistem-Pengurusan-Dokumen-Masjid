<?php

namespace App\Filament\App\Resources\Minits;

use App\Filament\App\Resources\Minits\Pages\ListMinits;
use App\Filament\App\Resources\Minits\Tables\MinitsTable;
use App\Models\Minit;
use App\Models\MinitRecipient;
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

class MinitResource extends Resource
{
    protected static ?string $model = Minit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $slug = 'minit-saya';

    protected static ?string $navigationLabel = 'Minit Saya';

    protected static ?string $modelLabel = 'Minit';

    protected static ?string $pluralModelLabel = 'Minit Saya';

    protected static string|UnitEnum|null $navigationGroup = 'Tugasan';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $uid = Auth::id();
        $recipientIds = collect([$uid])->merge(app(DelegationService::class)->principalIdsFor(Auth::user(), Filament::getTenant(), 'minit'))->unique();
        $ids = MinitRecipient::query()->whereIn('user_id', $recipientIds)->pluck('minit_id')->all();
        $recordIds = Record::query()->visibleTo(Auth::user(), Filament::getTenant())->pluck('id');

        return parent::getEloquentQuery()->whereIn('record_id', $recordIds)->where(function (Builder $q) use ($uid, $ids) {
            $q->where('from_user_id', $uid)->orWhereIn('id', $ids);
        });
    }

    public static function getNavigationBadge(): ?string
    {
        $recipientIds = collect([Auth::id()])->merge(app(DelegationService::class)->principalIdsFor(Auth::user(), Filament::getTenant(), 'minit'))->unique();
        $recordIds = Record::query()->visibleTo(Auth::user(), Filament::getTenant())->pluck('id');
        $count = Minit::query()
            ->whereIn('record_id', $recordIds)
            ->where('status', 'terbuka')
            ->whereIn('id', fn ($sub) => $sub->select('minit_id')->from('minit_recipients')
                ->whereIn('user_id', $recipientIds)->where('jenis', 'tindakan')->where('status', '!=', 'selesai'))
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return MinitsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMinits::route('/'),
        ];
    }
}

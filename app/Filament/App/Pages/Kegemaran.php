<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Resources\RegistryFiles\RegistryFileResource;
use App\Models\Favourite;
use App\Services\FavouriteService;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class Kegemaran extends Page
{
    protected string $view = 'filament.app.pages.kegemaran';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?string $navigationLabel = 'Kegemaran';

    protected static ?string $title = 'Rekod & Fail Kegemaran';

    protected static string|UnitEnum|null $navigationGroup = 'Tugasan';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'kegemaran';

    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();

        return $tenant && Auth::user()?->canIn($tenant, 'records.view');
    }

    public function remove(string $type, int $id): void
    {
        $favourite = Favourite::query()->forMosque(Filament::getTenant())
            ->where('user_id', Auth::id())->where('target_type', $type)->where('target_id', $id)->first();
        $favourite?->delete();
        Notification::make()->title('Dibuang daripada kegemaran.')->success()->send();
    }

    protected function getViewData(): array
    {
        $user = Auth::user();
        $mosque = Filament::getTenant();
        $resolver = app(FavouriteService::class);

        $items = Favourite::query()->forMosque($mosque)->where('user_id', $user->id)->latest()->get()
            ->map(function (Favourite $favourite) use ($resolver, $user, $mosque) {
                $target = $resolver->resolveVisible($user, $mosque, $favourite->target_type, $favourite->target_id);
                if (! $target) {
                    return null;
                }

                return [
                    'type' => $favourite->target_type,
                    'id' => $target->id,
                    'label' => $favourite->target_type === Favourite::RECORD ? 'Rekod' : 'Fail',
                    'title' => $target->title ?: '(tiada tajuk)',
                    'reference' => $favourite->target_type === Favourite::RECORD ? ($target->our_ref ?: $target->ulid) : $target->file_no,
                    'url' => $favourite->target_type === Favourite::RECORD
                        ? url('/r/'.$target->ulid)
                        : RegistryFileResource::getUrl('view', ['record' => $target], panel: 'app', tenant: $mosque),
                ];
            })->filter()->values();

        return ['items' => $items];
    }
}

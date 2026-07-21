<?php

namespace App\Filament\App\Pages;

use App\Models\Favourite;
use App\Models\RegistryFile;
use App\Models\SavedSearch;
use App\Services\FavouriteService;
use App\Services\SavedSearchService;
use App\Services\SearchService;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class CariRekod extends Page
{
    protected string $view = 'filament.app.pages.cari-rekod';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?string $navigationLabel = 'Carian';

    protected static ?string $title = 'Carian Rekod';

    protected static string|UnitEnum|null $navigationGroup = 'Tugasan';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'carian';

    public string $query = '';

    public string $recordType = '';

    public string $registryFileId = '';

    public string $direction = '';

    public string $sensitivity = '';

    public string $status = '';

    public string $sourceChannel = '';

    public string $recordDateFrom = '';

    public string $recordDateTo = '';

    public string $receivedDateFrom = '';

    public string $receivedDateTo = '';

    public string $sender = '';

    public string $reference = '';

    public string $recipient = '';

    public string $savedSearchName = '';

    public bool $savedSearchDefault = false;

    public array $results = [];

    public bool $searched = false;

    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();

        return $tenant && Auth::user()?->canIn($tenant, 'records.view');
    }

    public function recordTypeOptions(): array
    {
        return collect(config('record_types', []))
            ->mapWithKeys(fn (array $type, string $key) => [$key => $type['label'] ?? $key])
            ->all();
    }

    public function registryFileOptions(): array
    {
        $tenant = Filament::getTenant();

        return RegistryFile::query()
            ->visibleTo(Auth::user(), $tenant)
            ->orderBy('file_no')
            ->get()
            ->mapWithKeys(fn (RegistryFile $file) => [$file->id => "{$file->file_no} — {$file->title}"])
            ->all();
    }

    public function savedSearchOptions(): array
    {
        return SavedSearch::query()->forMosque(Filament::getTenant())
            ->where('user_id', Auth::id())->orderByDesc('is_default')->orderBy('name')->pluck('name', 'id')->all();
    }

    public function saveSearch(): void
    {
        $this->validate(['savedSearchName' => ['required', 'string', 'max:100']]);
        app(SavedSearchService::class)->save(
            Auth::user(), Filament::getTenant(), $this->savedSearchName, $this->criteria(), $this->savedSearchDefault,
        );
        Notification::make()->title('Carian disimpan.')->success()->send();
    }

    public function loadSearch(int $id): void
    {
        $saved = app(SavedSearchService::class)->use(Auth::user(), Filament::getTenant(), $id);
        foreach ($saved->criteria as $property => $value) {
            if (property_exists($this, $property) && is_string($value)) {
                $this->{$property} = $value;
            }
        }
        $this->savedSearchName = $saved->name;
        $this->savedSearchDefault = $saved->is_default;
        $this->search();
    }

    public function deleteSearch(int $id): void
    {
        app(SavedSearchService::class)->delete(Auth::user(), Filament::getTenant(), $id);
        Notification::make()->title('Carian tersimpan dipadam.')->success()->send();
    }

    public function toggleFavourite(int $recordId): void
    {
        $active = app(FavouriteService::class)->toggle(Auth::user(), Filament::getTenant(), Favourite::RECORD, $recordId);
        Notification::make()->title($active ? 'Ditambah ke kegemaran.' : 'Dibuang daripada kegemaran.')->success()->send();
        $this->search();
    }

    public function search(): void
    {
        $this->searched = true;

        $svc = app(SearchService::class);

        $records = $svc->for(Auth::user(), Filament::getTenant(), $this->query, array_filter([
            'record_type' => $this->recordType ?: null,
            'registry_file_id' => $this->registryFileId ?: null,
            'direction' => $this->direction ?: null,
            'sensitivity' => $this->sensitivity ?: null,
            'status' => $this->status ?: null,
            'source_channel' => $this->sourceChannel ?: null,
            'record_date_from' => $this->recordDateFrom ?: null,
            'record_date_to' => $this->recordDateTo ?: null,
            'received_date_from' => $this->receivedDateFrom ?: null,
            'received_date_to' => $this->receivedDateTo ?: null,
            'sender' => $this->sender ?: null,
            'reference' => $this->reference ?: null,
            'recipient' => $this->recipient ?: null,
        ]));

        $favouriteIds = Favourite::query()->forMosque(Filament::getTenant())
            ->where('user_id', Auth::id())->where('target_type', Favourite::RECORD)
            ->whereIn('target_id', $records->pluck('id'))->pluck('target_id')->map(fn ($id) => (int) $id)->all();

        $this->results = $records->map(fn ($r) => [
            'id' => $r->id,
            'ulid' => $r->ulid,
            'title' => $r->title ?? '(tiada tajuk)',
            'ref' => $r->registryFile?->file_no ?? '—',
            'type' => config("record_types.{$r->record_type}.label", $r->record_type),
            'sensitivity' => $r->sensitivity?->getLabel() ?? '—',
            'date' => $r->record_date?->format('d/m/Y') ?? '—',
            'sender' => collect([$r->sender_name, $r->sender_org])->filter()->join(', ') ?: '—',
            'source' => $r->source_channel?->getLabel() ?? '—',
            'favourite' => in_array($r->id, $favouriteIds, true),
            'snippet' => SearchService::highlight($svc->snippetFor($r, $this->query), $this->query),
        ])->all();
    }

    protected function criteria(): array
    {
        return collect([
            'query', 'recordType', 'registryFileId', 'direction', 'sensitivity', 'status', 'sourceChannel',
            'recordDateFrom', 'recordDateTo', 'receivedDateFrom', 'receivedDateTo', 'sender', 'reference', 'recipient',
        ])->mapWithKeys(fn (string $property) => [$property => $this->{$property}])->all();
    }
}

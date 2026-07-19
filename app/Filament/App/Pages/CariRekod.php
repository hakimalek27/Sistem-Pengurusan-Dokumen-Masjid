<?php

namespace App\Filament\App\Pages;

use App\Models\RegistryFile;
use App\Services\SearchService;
use BackedEnum;
use Filament\Facades\Filament;
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

    public array $results = [];

    public bool $searched = false;

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
            ->where('mosque_id', $tenant?->id)
            ->orderBy('file_no')
            ->get()
            ->mapWithKeys(fn (RegistryFile $file) => [$file->id => "{$file->file_no} — {$file->title}"])
            ->all();
    }

    public function search(): void
    {
        $this->searched = true;

        if (trim($this->query) === '') {
            $this->results = [];

            return;
        }

        $records = app(SearchService::class)->for(Auth::user(), Filament::getTenant(), $this->query, array_filter([
            'record_type' => $this->recordType ?: null,
            'registry_file_id' => $this->registryFileId ?: null,
        ]));

        $this->results = $records->map(fn ($r) => [
            'ulid' => $r->ulid,
            'title' => $r->title ?? '(tiada tajuk)',
            'ref' => $r->registryFile?->file_no ?? '—',
            'type' => config("record_types.{$r->record_type}.label", $r->record_type),
            'sensitivity' => $r->sensitivity?->getLabel() ?? '—',
        ])->all();
    }
}

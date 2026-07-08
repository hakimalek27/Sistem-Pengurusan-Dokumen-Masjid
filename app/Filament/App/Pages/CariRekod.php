<?php

namespace App\Filament\App\Pages;

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

    public array $results = [];

    public bool $searched = false;

    public function search(): void
    {
        $this->searched = true;

        if (trim($this->query) === '') {
            $this->results = [];

            return;
        }

        $records = app(SearchService::class)->for(Auth::user(), Filament::getTenant(), $this->query);

        $this->results = $records->map(fn ($r) => [
            'ulid' => $r->ulid,
            'title' => $r->title ?? '(tiada tajuk)',
            'ref' => $r->registryFile?->file_no ?? '—',
            'type' => config("record_types.{$r->record_type}.label", $r->record_type),
            'sensitivity' => $r->sensitivity?->getLabel() ?? '—',
        ])->all();
    }
}

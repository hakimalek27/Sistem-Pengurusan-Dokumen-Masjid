<?php

namespace App\Filament\App\Resources\RegistryFiles\Tables;

use App\Models\Favourite;
use App\Services\FavouriteService;
use App\Services\RecordNumberingService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RegistryFilesTable
{
    /**
     * Baris pertama render SEMASA — sasaran bantuan baris mesti UNIK (gate G2).
     *
     * ⚠️ Sifat statik hidup selama PROSES, bukan permintaan (punca CI W3, run 31001766297):
     * dalam satu proses ujian ia mengekalkan ID daripada render TERDAHULU. Kerana itu ia
     * DISET SEMULA pada permulaan `configure()`.
     */
    protected static ?int $barisPertamaId = null;

    protected static function baris1($record, string $target): array
    {
        self::$barisPertamaId ??= (int) $record->getKey();

        return self::$barisPertamaId === (int) $record->getKey()
            ? ['data-help-target' => $target]
            : [];
    }

    public static function configure(Table $table): Table
    {
        self::$barisPertamaId = null;

        return $table
            ->defaultSort('file_no')
            ->columns([
                TextColumn::make('file_no')->label('No. Fail')->searchable()->sortable(),
                TextColumn::make('title')->label('Tajuk')->searchable()->wrap(),
                TextColumn::make('sensitivity')->label('Sensitiviti')->badge(),
                // F6-W5: `tenant.registry-files` #2 ("Semak status terbuka/tutup dan bilangan
                // kandungan") — sel Status baris pertama.
                TextColumn::make('status')->label('Status')->badge()
                    ->extraCellAttributes(fn ($record): array => self::baris1($record, 'regfiles-status'))
                    ->color(fn ($state) => $state === 'terbuka' ? 'success' : 'gray'),
                TextColumn::make('enclosure_count')->label('Kandungan')->badge(),
                // F6-W4: guide `urus-fail-fizikal` langkah 2 ("Semak Medium dan Status")
                // membaca lajur Medium pada baris SENARAI. `file-medium` sedia ada ialah
                // infolist BUTIRAN (`state: detail:registry-files`), jadi ia tidak boleh
                // dipakai di sini — sasaran senarai berasingan diperlukan.
                TextColumn::make('medium')->label('Medium')->badge()
                    ->extraCellAttributes(fn ($record): array => self::baris1($record, 'regfiles-medium')),
                TextColumn::make('physical_location')->label('Lokasi')->placeholder('—')->toggleable(),
                TextColumn::make('custody_status')->label('Penjagaan')->badge()->toggleable(),
            ])
            ->recordActions([
                // F6-W4: langkah 3 ("Buka Lihat"). Atribut STATIK seperti `inbox-classify`
                // (corak terbukti W2) — bukan `baris1()`: tindakan jadual Filament tidak
                // menyuntik `$record` ke dalam `extraAttributes()`, dan gate menyelesaikan
                // sasaran dengan `.first()` jadi butang Lihat baris pertama yang dipakai.
                ViewAction::make()
                    ->extraAttributes(['data-help-target' => 'regfiles-view']),
                // §10.F — Buka jilid baharu bila enclosure ≥ 100.
                Action::make('bukaJilid')
                    ->label('Buka Jld. Baharu')
                    ->icon('heroicon-o-plus-circle')
                    ->color('warning')
                    ->authorize('openNextVolume')
                    ->visible(fn ($record) => $record->status === 'terbuka'
                        && $record->enclosure_count >= config('diwan.enclosure_volume_limit', 100))
                    ->requiresConfirmation()
                    ->modalDescription('Tutup jilid ini dan buka jilid baharu (nombor jilid+1).')
                    ->action(fn ($record) => app(RecordNumberingService::class)->openNextVolume($record, Auth::id())),
                Action::make('tutup')
                    ->label('Tutup Fail')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->authorize('close')
                    ->visible(fn ($record) => $record->status === 'terbuka')
                    ->schema([
                        Textarea::make('reason')->label('Sebab Tutup')->required(),
                    ])
                    ->action(fn ($record, array $data) => app(RecordNumberingService::class)
                        ->closeFile($record, $data['reason'], Auth::user())),
                Action::make('kegemaran')
                    ->label('Kegemaran')
                    ->icon('heroicon-o-star')
                    ->authorize('view')
                    ->action(function ($record): void {
                        $active = app(FavouriteService::class)->toggle(Auth::user(), $record->mosque, Favourite::REGISTRY_FILE, $record->id);
                        Notification::make()->title($active ? 'Fail ditambah ke kegemaran.' : 'Fail dibuang daripada kegemaran.')->success()->send();
                    }),
            ]);
    }
}

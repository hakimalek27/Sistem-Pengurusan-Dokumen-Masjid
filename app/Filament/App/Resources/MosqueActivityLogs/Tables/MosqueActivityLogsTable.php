<?php

namespace App\Filament\App\Resources\MosqueActivityLogs\Tables;

use App\Enums\SourceChannel;
use App\Models\MosqueActivityLog;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MosqueActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        // F6-W5: sifat `static` hidup sepanjang PROSES, bukan permintaan. Kelas ini
        // mengisytiharkan memo `baris1()` sejak W1 tetapi TIDAK PERNAH menetapkannya semula —
        // jurang yang sama yang memerahkan CI tiga pusingan pada W3 (lulus SQLite, gagal
        // PostgreSQL kerana jujukan ID tidak dirollback). Ia belum menggigit di sini kerana
        // hanya satu sasaran menggunakannya; W5 menambah yang kedua. Diset semula pada titik
        // masuk render, sama seperti Inbox/Minits/Approvals/RegistryFiles/Corrections.
        self::$barisPertamaId = null;

        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->columns([
                // F6-W5: `tenant.log-aktiviti` #4 ("Bandingkan masa peristiwa secara
                // kronologi") — sel masa baris pertama, jadual sudah `defaultSort` menurun.
                TextColumn::make('created_at')
                    ->label('Tarikh & Masa')
                    ->dateTime('d/m/Y h:i:s A')
                    ->sortable()
                    ->wrap()
                    ->extraCellAttributes(fn ($record): array => self::baris1($record, 'log-time')),
                TextColumn::make('actor_name')
                    ->label('Pelaku')
                    ->description(fn (MosqueActivityLog $record) => $record->actor_role ?: ($record->actor_id ? 'Ahli masjid' : 'Sistem / penghantar luar'))
                    ->placeholder('Sistem')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Aktiviti')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('record_title')
                    ->label('Rekod')
                    ->description(fn (MosqueActivityLog $record) => $record->record_reference)
                    ->placeholder('—')
                    ->wrap()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('file_no')
                    ->label('Fail')
                    ->description(fn (MosqueActivityLog $record) => $record->file_title)
                    ->placeholder('—')
                    ->wrap()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('source_channel')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        SourceChannel::MuatNaik->value => 'Dashboard',
                        SourceChannel::Emel->value => 'E-mel',
                        SourceChannel::WhatsApp->value => 'WhatsApp',
                        SourceChannel::Imbasan->value => 'Imbasan',
                        default => $state ?: '—',
                    })
                    ->description(fn (MosqueActivityLog $record) => $record->source_identifier)
                    ->toggleable(),
                TextColumn::make('ip_address')
                    ->label('Alamat IP')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('action')
                    ->label('Kod')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Jenis Aktiviti')
                    ->options(fn () => MosqueActivityLog::query()
                        ->withoutGlobalScope('mosque')
                        ->where('mosque_id', Filament::getTenant()->id)
                        ->distinct()->orderBy('action')->pluck('action', 'action')->all()),
                SelectFilter::make('actor_id')
                    ->label('Pelaku')
                    ->options(fn () => User::query()->whereHas('mosques', fn (Builder $query) => $query
                        ->where('mosques.id', Filament::getTenant()->id))
                        ->orderBy('name')->pluck('name', 'users.id')->all()),
                SelectFilter::make('source_channel')
                    ->label('Saluran')
                    ->options([
                        SourceChannel::MuatNaik->value => 'Dashboard',
                        SourceChannel::Emel->value => 'E-mel',
                        SourceChannel::WhatsApp->value => 'WhatsApp',
                        SourceChannel::Imbasan->value => 'Imbasan',
                    ]),
                Filter::make('tarikh')
                    ->label('Julat Tarikh')
                    ->schema([
                        DatePicker::make('dari')->label('Dari')->native(false)->displayFormat('d/m/Y'),
                        DatePicker::make('hingga')->label('Hingga')->native(false)->displayFormat('d/m/Y'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['dari'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['hingga'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            // F7 §8.3 (axe `empty-table-header` minor) — sel header lajur tindakan
            // kosong walaupun `aria-label` wujud; axe menuntut TEKS atau `aria-hidden`.
            // API semasa: `recordActionsColumnLabel()` (HasRecordActions.php:76);
            // `actionsColumnLabel()` ialah alias @deprecated (:162-164) — jangan guna.
            ->recordActionsColumnLabel('Tindakan')
            ->recordActions([
                // F6-W1 (§7.2) — `screen.butiran-log-aktiviti`; baris pertama sahaja (keunikan G2).
                Action::make('butiran')
                    ->label('Butiran')
                    ->icon('heroicon-o-eye')
                    ->extraAttributes(fn ($record): array => self::baris1($record, 'log-detail'))
                    ->modalHeading('Butiran Log Aktiviti')
                    ->modalContent(fn (MosqueActivityLog $record) => view('filament.app.activity-log-details', ['log' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('Belum ada aktiviti direkodkan')
            ->emptyStateDescription('Aktiviti baharu masjid akan muncul di sini secara kronologi.');
    }

    /** F6-W1 — sasaran hanya pada baris pertama (rujuk MinitsTable::baris1). */
    protected static ?int $barisPertamaId = null;

    protected static function baris1($record, string $target): array
    {
        self::$barisPertamaId ??= (int) $record->getKey();

        return self::$barisPertamaId === (int) $record->getKey()
            ? ['data-help-target' => $target]
            : [];
    }
}

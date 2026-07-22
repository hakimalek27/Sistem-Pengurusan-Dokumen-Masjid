<?php

namespace App\Filament\App\Resources\Inbox\Tables;

use App\Enums\MinitPriority;
use App\Enums\Sensitivity;
use App\Enums\SourceChannel;
use App\Filament\App\Resources\Minits\MinitResource;
use App\Filament\App\Resources\MosqueActivityLogs\MosqueActivityLogResource;
use App\Filament\App\Resources\Records\RecordResource;
use App\Filament\App\Support\RecordTypeSchema;
use App\Models\ClassificationNode;
use App\Models\Record;
use App\Models\RegistryFile;
use App\Services\InboxIngestService;
use App\Services\MinitService;
use App\Services\MosqueActivityLogger;
use App\Services\RecordNumberingService;
use App\Services\WhatsAppRecipientResolver;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InboxTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('source_channel')
                    ->label('Sumber')
                    ->formatStateUsing(fn ($state) => $state instanceof SourceChannel ? $state->badge() : $state)
                    ->badge(),
                TextColumn::make('title')
                    ->label('Tajuk / Fail')
                    ->searchable()
                    ->wrap()
                    ->limit(50),
                TextColumn::make('received_date')
                    ->label('Tarikh Terima')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                TextColumn::make('provenance')
                    ->label('Penghantar / Sumber')
                    ->state(fn (Record $record) => self::provenance($record))
                    ->wrap()->limit(80),
                TextColumn::make('created_at')->label('Diterima')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('virus_scan_status')->label('Antivirus')->badge()
                    ->color(fn ($state) => $state === 'clean' ? 'success' : ($state === 'infected' ? 'danger' : 'warning')),
                TextColumn::make('ocr_status')
                    ->label('OCR')
                    ->badge(),
                TextColumn::make('duplikat')
                    ->label('Duplikat')
                    ->state(fn ($record) => app(InboxIngestService::class)->isFlaggedDuplicate($record) ? '⚠' : '')
                    ->tooltip('Amaran: sha256 sama wujud pada rekod lain masjid ini'),
            ])
            ->recordActions([
                ViewAction::make()->label('Lihat Dokumen / OCR'),
                self::classifyAction(),
                self::deleteSpamAction(),
            ]);
    }

    protected static function classifyAction(): Action
    {
        return Action::make('klasifikasi')
            ->label('Klasifikasikan')
            ->icon('heroicon-o-tag')
            ->color('primary')
            ->authorize('classify')
            ->extraAttributes(['data-help-target' => 'inbox-classify'])
            ->extraModalWindowAttributes(['data-help-target' => 'inbox-classification-modal'])
            ->modalWidth('4xl')
            ->modalSubmitActionLabel('Klasifikasikan')
            ->fillForm(fn ($record) => [
                'record_type' => $record->record_type,
                'title' => $record->title,
                'record_date' => optional($record->record_date)->toDateString() ?? now()->toDateString(),
                // Carta ANM 8.1 — surat dicap tarikh penerimaan; prefill = tarikh masuk Peti Masuk.
                'received_date' => optional($record->received_date)->toDateString()
                    ?? optional($record->created_at)->toDateString(),
            ])
            ->steps([
                Step::make('Semak Dokumen & Sumber')
                    ->description('Pastikan dokumen benar dan tenant tepat')
                    ->schema([
                        Placeholder::make('source_summary')->label('Asal dokumen')
                            ->content(fn (Record $record): string => self::sourceSummary($record)),
                        Placeholder::make('safety_summary')->label('Pemeriksaan awal')
                            ->content(fn (Record $record): string => 'Antivirus: '.($record->virus_scan_status ?: '—').' | OCR: '.($record->ocr_status?->getLabel() ?? '—').' | Diterima: '.($record->created_at?->format('d/m/Y H:i') ?? '—')),
                        Placeholder::make('source_instruction')->label('Semakan wajib')
                            ->content('Buka dokumen/OCR pada halaman Peti Masuk terlebih dahulu. Hentikan klasifikasi jika salah tenant, fail tidak boleh dibaca atau sumber meragukan.'),
                    ]),
                Step::make('Jenis & Metadata')
                    ->description('Tawan butiran rasmi dokumen')
                    ->schema([
                        Select::make('record_type')
                            ->label('Jenis Rekod')
                            ->options(collect(config('record_types'))->mapWithKeys(fn ($type, $key) => [$key => $type['label']]))
                            ->live()
                            ->required(),
                        Section::make('Butiran Rekod')
                            ->description('Ruj. Kami ialah rujukan masjid; Ruj. Tuan ialah rujukan pihak pengirim. u.p. bukan penerima minit sehingga dipilih pada langkah edaran.')
                            ->schema(fn (Get $get) => $get('record_type')
                                ? array_merge(RecordTypeSchema::coreFields($get('record_type')), RecordTypeSchema::metadataFields($get('record_type')))
                                : [])
                            ->columns(2),
                    ]),
                Step::make('Fail & Sensitiviti')
                    ->description('Pilih fail registri dan tahap akses efektif')
                    ->schema([
                        Select::make('registry_file_id')
                            ->label('Failkan Ke')
                            ->options(fn () => RegistryFile::query()
                                ->where('mosque_id', Filament::getTenant()?->id)
                                ->where('status', 'terbuka')
                                ->orderBy('file_no')
                                ->get()
                                ->mapWithKeys(fn ($file) => [$file->id => "{$file->file_no} — {$file->title}"]))
                            ->searchable()->live()->required()
                            ->helperText(fn (Get $get): ?string => self::fileCapacityWarning($get('registry_file_id')))
                            ->createOptionForm([
                                Select::make('classification_node_id')
                                    ->label('Nod Klasifikasi')
                                    ->options(fn () => ClassificationNode::query()
                                        ->where('mosque_id', Filament::getTenant()?->id)
                                        ->whereIn('level', ['aktiviti', 'sub_aktiviti'])
                                        ->where('is_active', true)
                                        ->orderBy('code')
                                        ->get()
                                        ->mapWithKeys(fn ($node) => [$node->id => "{$node->code} — {$node->title}"]))
                                    ->searchable()->required(),
                                TextInput::make('title')->label('Tajuk Fail')->required(),
                            ])
                            ->createOptionUsing(function (array $data) {
                                $node = ClassificationNode::query()->where('mosque_id', Filament::getTenant()->id)->findOrFail($data['classification_node_id']);

                                return app(RecordNumberingService::class)->openFile(Filament::getTenant(), $node, $data['title'], Auth::id())->id;
                            }),
                        Select::make('sensitivity')
                            ->label('Tahap Akses Rekod')
                            ->options(collect(Sensitivity::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()]))
                            ->default('dalaman')
                            ->helperText('Tahap akhir ialah nilai tertinggi antara rekod dan fail; penerima tidak layak ditapis keluar secara automatik.')
                            ->live()->required(),
                    ]),
                Step::make('Edaran Minit')
                    ->description('Bezakan tindakan dan makluman')
                    ->schema([
                        Select::make('minit_action_ids')
                            ->label('Untuk Tindakan (Minit)')
                            ->helperText('Penerima memikul tanggungjawab, SLA dan perlu menanda selesai.')
                            ->multiple()->options(fn (Get $get) => self::memberOptions($get))->searchable(),
                        Select::make('minit_cc_ids')
                            ->label('Untuk Makluman (s.k.)')
                            ->helperText('Penerima dimaklumkan tetapi bukan pemilik tindakan asal.')
                            ->multiple()->options(fn (Get $get) => self::memberOptions($get))->searchable(),
                        Textarea::make('minit_body')
                            ->label('Catatan / Arahan Minit')
                            ->helperText('Nyatakan tindakan, hasil yang diperlukan dan konteks; elakkan arahan kabur.')
                            ->required(fn (Get $get): bool => filled($get('minit_action_ids'))),
                        Select::make('minit_priority')
                            ->label('Keutamaan Minit')
                            ->options(['biasa' => 'Biasa (7 hari)', 'segera' => 'Segera (3 hari)', 'kritikal' => 'Kritikal (1 hari)'])
                            ->default('biasa')->required(),
                    ])->columns(2),
                Step::make('Semakan Akhir')
                    ->description('Sahkan kesan sebelum transaksi dihantar')
                    ->schema([
                        Placeholder::make('review_file')->label('Fail & akses efektif')
                            ->content(fn (Get $get): string => self::fileReview($get)),
                        Placeholder::make('review_action')->label('Penerima tindakan')
                            ->content(fn (Get $get): string => self::recipientReview((array) ($get('minit_action_ids') ?? []))),
                        Placeholder::make('review_cc')->label('Penerima makluman (s.k.)')
                            ->content(fn (Get $get): string => self::recipientReview((array) ($get('minit_cc_ids') ?? []))),
                        Placeholder::make('review_channels')->label('Saluran notifikasi tersedia')
                            ->content(fn (Get $get): string => self::notificationReview(array_merge((array) ($get('minit_action_ids') ?? []), (array) ($get('minit_cc_ids') ?? [])))),
                        Placeholder::make('review_atomic')->label('Kesan hantar')
                            ->content('Rekod, nombor kandungan dan minit disimpan sebagai satu transaksi. Jika mana-mana langkah gagal, keseluruhan perubahan dibatalkan.'),
                    ])->columns(2),
            ])
            ->action(function ($record, array $data) {
                $file = RegistryFile::query()
                    ->where('mosque_id', Filament::getTenant()->id)
                    ->findOrFail($data['registry_file_id']);

                $core = collect($data)
                    ->only(['title', 'our_ref', 'their_ref', 'record_date', 'received_date', 'direction', 'sender_name', 'sender_org', 'recipient_name'])
                    ->filter(fn ($v) => $v !== null && $v !== '')
                    ->toArray();
                $core['record_type'] = $data['record_type'];
                $core['metadata'] = $data['metadata'] ?? [];

                $minitActionIds = collect($data['minit_action_ids'] ?? [])->filter()->values()->all();
                $minitCcIds = collect($data['minit_cc_ids'] ?? [])->filter()->values()->all();

                $filed = DB::transaction(function () use ($record, $file, $core, $data, $minitActionIds, $minitCcIds) {
                    $filed = app(InboxIngestService::class)->fileRecord(
                        $record,
                        $file,
                        $core,
                        Auth::user(),
                        Sensitivity::from($data['sensitivity']),
                    );

                    if ($minitActionIds !== []) {
                        app(MinitService::class)->create(
                            $filed,
                            Auth::user(),
                            $minitActionIds,
                            $minitCcIds,
                            (string) ($data['minit_body'] ?? ''),
                            MinitPriority::from($data['minit_priority'] ?? 'biasa'),
                        );
                    }

                    return $filed;
                });

                Notification::make()
                    ->title('Difailkan sebagai '.$filed->registryFile->file_no.'('.$filed->enclosure_no.')')
                    ->body($minitActionIds === []
                        ? 'Rekod difailkan. Minit boleh diedarkan dari halaman rekod.'
                        : 'Rekod difailkan dan minit tindakan telah dihantar.')
                    ->actions([
                        Action::make('bukaRekod')->label('Buka Rekod')->url(RecordResource::getUrl('view', ['record' => $filed], panel: 'app', tenant: Filament::getTenant())),
                        Action::make('bukaMinit')->label('Minit Saya')->url(MinitResource::getUrl('index', panel: 'app', tenant: Filament::getTenant())),
                        Action::make('bukaLog')->label('Log Aktiviti')->url(MosqueActivityLogResource::getUrl('index', panel: 'app', tenant: Filament::getTenant())),
                    ])
                    ->success()
                    ->send();
            });
    }

    protected static function sourceSummary(Record $record): string
    {
        $meta = $record->source_meta ?? [];
        $source = self::provenance($record);
        $received = $record->created_at?->format('d/m/Y H:i') ?? '—';
        $detail = match ($record->source_channel?->value) {
            'emel' => filled($meta['subject'] ?? null) ? ' | Subjek: '.mb_substr((string) $meta['subject'], 0, 120) : '',
            'whatsapp' => filled($meta['message_id'] ?? null) ? ' | ID mesej tersedia' : '',
            default => filled($record->createdBy?->name) ? ' | Akaun: '.$record->createdBy->name : '',
        };

        return "{$source}{$detail} | Diterima: {$received}";
    }

    protected static function fileCapacityWarning(mixed $id): ?string
    {
        if (blank($id)) {
            return null;
        }
        $file = RegistryFile::query()->where('mosque_id', Filament::getTenant()?->id)->find($id);
        if (! $file) {
            return null;
        }
        $count = (int) $file->enclosure_count;
        if ($count >= 100) {
            return "Fail ini sudah {$count} kandungan. Tutup dan buka jilid baharu sebelum meneruskan.";
        }
        if ($count >= 90) {
            return "Fail ini sudah {$count} kandungan (had cadangan 100). Pertimbang jilid baharu.";
        }

        return "Fail mempunyai {$count} kandungan.";
    }

    protected static function fileReview(Get $get): string
    {
        $file = RegistryFile::query()->where('mosque_id', Filament::getTenant()?->id)->find($get('registry_file_id'));
        if (! $file) {
            return 'Belum memilih fail.';
        }
        $chosen = Sensitivity::tryFrom((string) ($get('sensitivity') ?: 'dalaman')) ?? Sensitivity::Dalaman;
        $effective = Sensitivity::max($chosen, $file->sensitivity ?? Sensitivity::Dalaman);

        return "{$file->file_no} — {$file->title} | Sensitiviti efektif: {$effective->getLabel()}";
    }

    protected static function recipientReview(array $ids): string
    {
        if ($ids === []) {
            return 'Tiada.';
        }
        $tenant = Filament::getTenant();
        if (! $tenant) {
            return 'Tiada.';
        }

        $names = $tenant->users()->where('users.is_active', true)->whereIn('users.id', array_map('intval', $ids))->pluck('name')->all();

        return $names === [] ? 'Tiada penerima layak.' : implode(', ', $names);
    }

    protected static function notificationReview(array $ids): string
    {
        $tenant = Filament::getTenant();
        if (! $tenant || $ids === []) {
            return 'Tiada notifikasi minit pada masa ini.';
        }

        return $tenant->users()->where('users.is_active', true)->whereIn('users.id', array_values(array_unique(array_map('intval', $ids))))
            ->get()->map(function ($user) use ($tenant): string {
                $channels = collect();
                if ($user->notify_email && filled($user->email)) {
                    $channels->push('E-mel');
                }
                if ($user->notify_whatsapp && app(WhatsAppRecipientResolver::class)->resolve($user, $tenant->id)) {
                    $channels->push('WhatsApp');
                }
                if ($user->notify_telegram && filled($user->telegram_chat_id)) {
                    $channels->push('Telegram');
                }

                return $user->name.': '.($channels->isEmpty() ? 'tiada saluran luar aktif' : $channels->join(', '));
            })->join(' | ');
    }

    protected static function memberOptions(Get $get): array
    {
        $tenant = Filament::getTenant();
        if (! $tenant) {
            return [];
        }

        $file = filled($get('registry_file_id'))
            ? RegistryFile::query()
                ->where('mosque_id', $tenant->id)
                ->with('classificationNode')
                ->find($get('registry_file_id'))
            : null;
        $chosen = Sensitivity::tryFrom((string) ($get('sensitivity') ?: 'dalaman')) ?? Sensitivity::Dalaman;
        $effective = $file ? Sensitivity::max($chosen, $file->sensitivity ?? Sensitivity::Dalaman) : $chosen;

        $preview = new Record([
            'mosque_id' => $tenant->id,
            'registry_file_id' => $file?->id,
            'sensitivity' => $effective,
        ]);
        $preview->setRelation('mosque', $tenant);
        if ($file) {
            $preview->setRelation('registryFile', $file);
        }

        return $tenant->users()
            ->where('users.is_active', true)
            ->orderBy('name')
            ->get()
            ->filter(fn ($user) => $user->can('view', $preview))
            ->pluck('name', 'id')
            ->toArray() ?? [];
    }

    protected static function provenance(Record $record): string
    {
        $meta = $record->source_meta ?? [];

        return match ($record->source_channel?->value) {
            'emel' => 'E-mel: '.($meta['from'] ?? '—'),
            'whatsapp' => 'WhatsApp: '.($meta['from'] ?? '—'),
            default => 'UI: '.($record->createdBy?->name ?? '—'),
        };
    }

    protected static function deleteSpamAction(): Action
    {
        return Action::make('padam')
            ->label('Padam (Spam)')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->authorize('delete')
            ->schema([
                Textarea::make('reason')->label('Sebab (spam / tidak berkaitan)')->required(),
            ])
            ->requiresConfirmation()
            ->action(function ($record, array $data) {
                activity()
                    ->performedOn($record)
                    ->causedBy(Auth::user())
                    ->withProperties(['reason' => $data['reason'], 'ip' => request()->ip()])
                    ->log('padam_spam');

                app(MosqueActivityLogger::class)->log(
                    $record->mosque,
                    'inbox_spam_deleted',
                    Auth::user()->name.' memadam item Peti Masuk "'.$record->title.'" sebagai spam atau tidak berkaitan.',
                    Auth::user(),
                    $record,
                    $record,
                    metadata: ['reason' => $data['reason']],
                );

                $record->delete();

                Notification::make()->title('Item peti masuk dipadam.')->success()->send();
            });
    }
}

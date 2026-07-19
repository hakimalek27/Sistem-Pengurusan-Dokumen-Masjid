<?php

namespace App\Filament\App\Resources\Inbox\Tables;

use App\Enums\MinitPriority;
use App\Enums\Sensitivity;
use App\Enums\SourceChannel;
use App\Filament\App\Support\RecordTypeSchema;
use App\Models\ClassificationNode;
use App\Models\Record;
use App\Models\RegistryFile;
use App\Services\InboxIngestService;
use App\Services\MinitService;
use App\Services\RecordNumberingService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
            ->modalWidth('2xl')
            ->fillForm(fn ($record) => [
                'record_type' => $record->record_type,
                'title' => $record->title,
                'record_date' => optional($record->record_date)->toDateString() ?? now()->toDateString(),
            ])
            ->schema([
                Select::make('record_type')
                    ->label('Jenis Rekod')
                    ->options(collect(config('record_types'))->mapWithKeys(fn ($t, $k) => [$k => $t['label']]))
                    ->live()
                    ->required(),
                Section::make('Butiran Rekod')
                    ->schema(fn (Get $get) => $get('record_type')
                        ? array_merge(
                            RecordTypeSchema::coreFields($get('record_type')),
                            RecordTypeSchema::metadataFields($get('record_type')),
                        )
                        : [])
                    ->columns(2),
                Select::make('registry_file_id')
                    ->label('Failkan Ke')
                    ->options(fn () => RegistryFile::query()
                        ->where('mosque_id', Filament::getTenant()?->id)
                        ->where('status', 'terbuka')
                        ->get()
                        ->mapWithKeys(fn ($f) => [$f->id => "{$f->file_no} — {$f->title}"]))
                    ->searchable()
                    ->live()
                    ->required()
                    ->createOptionForm([
                        Select::make('classification_node_id')
                            ->label('Nod Klasifikasi')
                            ->options(fn () => ClassificationNode::query()
                                ->where('mosque_id', Filament::getTenant()?->id)
                                ->whereIn('level', ['aktiviti', 'sub_aktiviti'])
                                ->where('is_active', true)
                                ->orderBy('code')
                                ->get()
                                ->mapWithKeys(fn ($n) => [$n->id => "{$n->code} — {$n->title}"]))
                            ->searchable()
                            ->required(),
                        TextInput::make('title')->label('Tajuk Fail')->required(),
                    ])
                    ->createOptionUsing(function (array $data) {
                        $node = ClassificationNode::query()
                            ->where('mosque_id', Filament::getTenant()->id)
                            ->findOrFail($data['classification_node_id']);

                        return app(RecordNumberingService::class)
                            ->openFile(Filament::getTenant(), $node, $data['title'], Auth::id())->id;
                    }),
                Select::make('sensitivity')
                    ->label('Tahap Akses Rekod')
                    ->options(collect(Sensitivity::cases())->mapWithKeys(fn ($c) => [$c->value => $c->getLabel()]))
                    ->default('dalaman')
                    ->helperText('Tahap akhir mengikut nilai tertinggi antara pilihan ini dan sensitiviti fail.')
                    ->live()
                    ->required(),
                Section::make('Edaran Minit Selepas Failkan')
                    ->schema([
                        Select::make('minit_action_ids')
                            ->label('Untuk Tindakan (Minit)')
                            ->multiple()
                            ->options(fn (Get $get) => self::memberOptions($get))
                            ->searchable(),
                        Select::make('minit_cc_ids')
                            ->label('Untuk Makluman (s.k.)')
                            ->multiple()
                            ->options(fn (Get $get) => self::memberOptions($get))
                            ->searchable(),
                        Textarea::make('minit_body')
                            ->label('Catatan / Arahan Minit')
                            ->required(fn (Get $get): bool => filled($get('minit_action_ids'))),
                        Select::make('minit_priority')
                            ->label('Keutamaan Minit')
                            ->options(['biasa' => 'Biasa', 'segera' => 'Segera', 'kritikal' => 'Kritikal'])
                            ->default('biasa'),
                    ])
                    ->columns(2),
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
                    ->success()
                    ->send();
            });
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

                $record->delete();

                Notification::make()->title('Item peti masuk dipadam.')->success()->send();
            });
    }
}

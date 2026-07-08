<?php

namespace App\Filament\App\Resources\Inbox\Tables;

use App\Enums\Sensitivity;
use App\Enums\SourceChannel;
use App\Filament\App\Support\RecordTypeSchema;
use App\Models\ClassificationNode;
use App\Models\RegistryFile;
use App\Services\InboxIngestService;
use App\Services\RecordNumberingService;
use Filament\Actions\Action;
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
                    ->label('Sensitiviti (lalai = max waris fail)')
                    ->options(collect(Sensitivity::cases())->mapWithKeys(fn ($c) => [$c->value => $c->getLabel()]))
                    ->default('dalaman')
                    ->required(),
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

                $filed = app(InboxIngestService::class)->fileRecord(
                    $record,
                    $file,
                    $core,
                    Auth::user(),
                    Sensitivity::from($data['sensitivity']),
                );

                Notification::make()
                    ->title('Difailkan sebagai '.$filed->registryFile->file_no.'('.$filed->enclosure_no.')')
                    ->body('Mahu edarkan minit sekarang? Buka rekod → Edarkan Minit (Fasa 4).')
                    ->success()
                    ->send();
            });
    }

    protected static function deleteSpamAction(): Action
    {
        return Action::make('padam')
            ->label('Padam (Spam)')
            ->icon('heroicon-o-trash')
            ->color('danger')
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

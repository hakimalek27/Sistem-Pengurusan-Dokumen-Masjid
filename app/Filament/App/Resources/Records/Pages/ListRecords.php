<?php

namespace App\Filament\App\Resources\Records\Pages;

use App\Enums\Sensitivity;
use App\Enums\SourceChannel;
use App\Filament\App\Resources\Records\RecordResource;
use App\Filament\App\Support\RecordTypeSchema;
use App\Models\ClassificationNode;
use App\Models\RegistryFile;
use App\Services\InboxIngestService;
use App\Services\RecordNumberingService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords as BaseListRecords;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ListRecords extends BaseListRecords
{
    protected static string $resource = RecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('rekodBaharu')
                ->label('Rekod Baharu')
                ->icon('heroicon-o-document-plus')
                ->authorize(fn () => Auth::user()?->canIn(Filament::getTenant(), 'records.create') ?? false)
                ->steps([
                    Step::make('Dokumen')
                        ->description('Muat naik fail dan pilih jenis rekod')
                        ->schema([
                            FileUpload::make('file')
                                ->label('Fail Dokumen')
                                ->disk('local')
                                ->directory('record-tmp')
                                ->acceptedFileTypes([
                                    'application/pdf', 'image/jpeg', 'image/png', 'image/webp',
                                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                                ])
                                ->maxSize((int) config('diwan.max_upload_mb', 25) * 1024)
                                ->required(),
                            Select::make('record_type')
                                ->label('Jenis Rekod')
                                ->options(collect(config('record_types'))->mapWithKeys(fn ($type, $key) => [$key => $type['label']]))
                                ->live()
                                ->required(),
                            Section::make('Butiran Rekod')
                                ->schema(fn (Get $get) => $get('record_type')
                                    ? array_merge(RecordTypeSchema::coreFields($get('record_type')), RecordTypeSchema::metadataFields($get('record_type')))
                                    : [])
                                ->columns(2),
                        ]),
                    Step::make('Klasifikasi')
                        ->description('Letakkan rekod dalam fail registri sebenar')
                        ->schema([
                            Select::make('registry_file_id')
                                ->label('Fail Registri')
                                ->options(fn () => RegistryFile::query()
                                    ->where('mosque_id', Filament::getTenant()->id)
                                    ->where('status', 'terbuka')
                                    ->orderBy('file_no')
                                    ->get()
                                    ->mapWithKeys(fn ($file) => [$file->id => "{$file->file_no} — {$file->title}"]))
                                ->searchable()
                                ->required()
                                ->createOptionForm([
                                    Select::make('classification_node_id')
                                        ->label('Nod Klasifikasi')
                                        ->options(fn () => ClassificationNode::query()
                                            ->where('mosque_id', Filament::getTenant()->id)
                                            ->whereIn('level', ['aktiviti', 'sub_aktiviti'])
                                            ->where('is_active', true)
                                            ->orderBy('code')
                                            ->get()
                                            ->mapWithKeys(fn ($node) => [$node->id => "{$node->code} — {$node->title}"]))
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
                                ->label('Sensitiviti Rekod')
                                ->options(collect(Sensitivity::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()]))
                                ->default('dalaman')
                                ->required(),
                        ]),
                    Step::make('Pengesahan')
                        ->description('Sistem akan jana nombor kandungan dan mula OCR')
                        ->schema([
                            Section::make('Semakan Akhir')
                                ->description('Selepas simpan, rekod terus difailkan. Sensitiviti efektif ialah tahap tertinggi antara rekod dan fail.'),
                        ]),
                ])
                ->action(function (array $data) {
                    $mosque = Filament::getTenant();
                    $path = Storage::disk('local')->path($data['file']);
                    $service = app(InboxIngestService::class);

                    try {
                        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                        $mime = match ($extension) {
                            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                            default => mime_content_type($path) ?: 'application/octet-stream',
                        };
                        $record = $service->ingest(
                            $mosque,
                            file_get_contents($path),
                            basename($path),
                            $mime,
                            Auth::user(),
                            SourceChannel::MuatNaik,
                        );
                        $file = RegistryFile::query()->where('mosque_id', $mosque->id)->where('status', 'terbuka')->findOrFail($data['registry_file_id']);
                        $attributes = collect($data)->only([
                            'record_type', 'title', 'our_ref', 'their_ref', 'record_date', 'received_date',
                            'direction', 'sender_name', 'sender_org', 'recipient_name', 'metadata',
                        ])->all();
                        $record = $service->fileRecord($record, $file, $attributes, Auth::user(), Sensitivity::from($data['sensitivity']));
                    } finally {
                        Storage::disk('local')->delete($data['file']);
                    }

                    Notification::make()->title('Rekod berjaya difailkan.')->success()->send();

                    return redirect(RecordResource::getUrl('view', ['record' => $record]));
                }),
        ];
    }
}

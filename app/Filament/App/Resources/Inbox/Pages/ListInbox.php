<?php

namespace App\Filament\App\Resources\Inbox\Pages;

use App\Enums\SourceChannel;
use App\Filament\App\Resources\Inbox\InboxResource;
use App\Services\InboxIngestService;
use App\Services\QuotaService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ListInbox extends ListRecords
{
    protected static string $resource = InboxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('muatNaik')
                ->label('+ Muat Naik Dokumen')
                ->icon('heroicon-o-arrow-up-tray')
                ->authorize(fn () => Auth::user()?->canIn(Filament::getTenant(), 'records.create') ?? false)
                ->schema([
                    FileUpload::make('files')
                        ->label('Dokumen (boleh berbilang)')
                        ->multiple()
                        ->required()
                        ->disk('local')
                        ->directory('inbox-tmp')
                        ->acceptedFileTypes([
                            'application/pdf', 'image/jpeg', 'image/png', 'image/webp',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        ])
                        ->maxSize((int) config('diwan.max_upload_mb', 25) * 1024),
                ])
                ->action(function (array $data) {
                    $mosque = Filament::getTenant();

                    // §5.14 pintu 1 — sekat muat naik jika kuota penuh (baca kekal OK).
                    if (app(QuotaService::class)->isFull($mosque)) {
                        Notification::make()
                            ->title('Kuota storan penuh')
                            ->body('Muat naik disekat. Sila Tambah Storan di halaman Penggunaan & Storan.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $service = app(InboxIngestService::class);
                    $count = 0;

                    foreach ((array) $data['files'] as $path) {
                        $fullPath = Storage::disk('local')->path($path);
                        $contents = file_get_contents($fullPath);
                        $mime = mime_content_type($fullPath) ?: 'application/octet-stream';

                        $service->ingest($mosque, $contents, basename($path), $mime, Auth::user(), SourceChannel::MuatNaik);
                        Storage::disk('local')->delete($path);
                        $count++;
                    }

                    Notification::make()
                        ->title("{$count} dokumen dimuat naik ke Peti Masuk.")
                        ->success()
                        ->send();
                }),
        ];
    }
}

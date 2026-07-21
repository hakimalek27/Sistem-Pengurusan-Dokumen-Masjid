<?php

namespace App\Filament\App\Resources\Records\Pages;

use App\Enums\MinitPriority;
use App\Filament\App\Resources\Records\RecordResource;
use App\Models\Favourite;
use App\Models\RecordCorrectionRequest;
use App\Models\RegistryFile;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\FavouriteService;
use App\Services\InboxIngestService;
use App\Services\MinitService;
use App\Services\QrLabelService;
use App\Services\RecordCorrectionService;
use App\Services\SensitiveAccessLogger;
use App\Support\AllowedFormats;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord as BaseViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ViewRecord extends BaseViewRecord
{
    protected static string $resource = RecordResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $rec = $this->getRecord();

        app(SensitiveAccessLogger::class)->log($rec, Auth::user(), 'view', request());
    }

    protected function getHeaderActions(): array
    {
        $mosque = fn () => $this->getRecord()->mosque;

        return [
            Action::make('kegemaran')
                ->label('Kegemaran')
                ->icon('heroicon-o-star')
                ->action(function () use ($mosque): void {
                    $active = app(FavouriteService::class)->toggle(Auth::user(), $mosque(), Favourite::RECORD, $this->getRecord()->id);
                    Notification::make()->title($active ? 'Ditambah ke kegemaran.' : 'Dibuang daripada kegemaran.')->success()->send();
                }),

            Action::make('mohonPembetulan')
                ->label('Mohon Pembetulan')
                ->icon('heroicon-o-pencil-square')
                ->authorize(fn () => Auth::user()->can('create', RecordCorrectionRequest::class))
                ->schema([
                    Textarea::make('reason')->label('Sebab Rekod Salah Tawan')->required()->minLength(10),
                    TextInput::make('title')->label('Tajuk')->default(fn () => $this->getRecord()->title),
                    Select::make('record_type')->label('Jenis Rekod')->options(fn () => collect(config('record_types'))->mapWithKeys(fn ($type, $key) => [$key => $type['label']])->all())->default(fn () => $this->getRecord()->record_type)->required(),
                    TextInput::make('our_ref')->label('Ruj. Kami')->default(fn () => $this->getRecord()->our_ref),
                    TextInput::make('their_ref')->label('Ruj. Tuan')->default(fn () => $this->getRecord()->their_ref),
                    DatePicker::make('record_date')->label('Tarikh Rekod')->default(fn () => $this->getRecord()->record_date),
                    DatePicker::make('received_date')->label('Tarikh Terima')->default(fn () => $this->getRecord()->received_date),
                    Select::make('direction')->label('Arah')->options(['masuk' => 'Masuk', 'keluar' => 'Keluar', 'dalaman' => 'Dalaman'])->default(fn () => $this->getRecord()->direction?->value),
                    TextInput::make('sender_name')->label('Nama Pengirim')->default(fn () => $this->getRecord()->sender_name),
                    TextInput::make('sender_org')->label('Organisasi Pengirim')->default(fn () => $this->getRecord()->sender_org),
                    TextInput::make('recipient_name')->label('Penerima')->default(fn () => $this->getRecord()->recipient_name),
                    Select::make('sensitivity')->label('Sensitiviti')->options(['umum' => 'Umum', 'dalaman' => 'Dalaman', 'sulit' => 'Sulit'])->default(fn () => $this->getRecord()->sensitivity?->value)->required(),
                ])
                ->action(function (array $data): void {
                    $reason = $data['reason'];
                    unset($data['reason']);
                    app(RecordCorrectionService::class)->request($this->getRecord(), Auth::user(), $reason, $data);
                    Notification::make()->title('Permohonan pembetulan dihantar untuk semakan.')->success()->send();
                }),

            Action::make('edarkanMinit')
                ->label('Edarkan Minit')
                ->icon('heroicon-o-paper-airplane')
                ->authorize('routeMinit')
                ->visible(fn () => Auth::user()->canIn($mosque(), 'minit.create'))
                ->schema([
                    Select::make('action')->label('Penerima Tindakan')->multiple()->options(fn () => $this->memberOptions())->required(),
                    Select::make('cc')->label('Makluman (s.k.)')->multiple()->options(fn () => $this->memberOptions()),
                    Textarea::make('body')->label('Catatan / Arahan')->required(),
                    Select::make('priority')->label('Keutamaan')->options(['biasa' => 'Biasa', 'segera' => 'Segera', 'kritikal' => 'Kritikal'])->default('biasa')->required(),
                ])
                ->action(function (array $data) {
                    app(MinitService::class)->create($this->getRecord(), Auth::user(), $data['action'], $data['cc'] ?? [], $data['body'], MinitPriority::from($data['priority']));
                    $this->getRecord()->unsetRelation('minits')->load('minits');
                    Notification::make()->title('Minit diedarkan.')->success()->send();
                }),

            Action::make('mohonKelulusan')
                ->label('Mohon Kelulusan')
                ->icon('heroicon-o-check-badge')
                ->authorize('requestApproval')
                ->visible(fn () => Auth::user()->canIn($mosque(), 'approvals.request'))
                ->schema([
                    Select::make('approver_id')->label('Kepada')->options(fn () => $this->approverOptions())->required(),
                    Textarea::make('note')->label('Nota'),
                ])
                ->action(function (array $data) {
                    app(ApprovalService::class)->request($this->getRecord(), Auth::user(), User::findOrFail($data['approver_id']), $data['note'] ?? null);
                    $this->getRecord()->unsetRelation('approvals')->load('approvals');
                    Notification::make()->title('Permohonan kelulusan dihantar.')->success()->send();
                }),

            Action::make('gantiVersi')
                ->label('Ganti Versi')
                ->icon('heroicon-o-arrow-path')
                ->authorize('supersede')
                ->visible(fn () => Auth::user()->canIn($mosque(), 'records.supersede'))
                ->schema([
                    FileUpload::make('file')->label('Versi Baharu')->disk('local')->directory('ver-tmp')
                        ->acceptedFileTypes(AllowedFormats::acceptedFileTypes())
                        ->helperText('Format sah: '.AllowedFormats::label().'.')
                        ->maxSize((int) config('diwan.max_upload_mb', 25) * 1024)
                        ->storeFileNamesIn('file_name')->required(),
                ])
                ->action(function (array $data) {
                    $filename = $data['file_name'] ?? basename((string) $data['file']);
                    $mime = AllowedFormats::mimeForExtension(pathinfo($filename, PATHINFO_EXTENSION))
                        ?? (mime_content_type(Storage::disk('local')->path($data['file'])) ?: 'application/octet-stream');
                    $path = Storage::disk('local')->path($data['file']);
                    $new = app(InboxIngestService::class)->supersede(
                        $this->getRecord(),
                        file_get_contents($path),
                        $filename,
                        $mime,
                        Auth::user(),
                    );
                    Storage::disk('local')->delete($data['file']);
                    Notification::make()->title('Versi baharu dicipta.')->success()->send();

                    return redirect(RecordResource::getUrl('view', ['record' => $new]));
                }),

            Action::make('pindahFail')
                ->label('Pindah Fail')
                ->icon('heroicon-o-folder-arrow-down')
                ->authorize('move')
                ->visible(fn () => Auth::user()->canIn($mosque(), 'records.move') && $this->getRecord()->registry_file_id)
                ->schema([
                    Select::make('registry_file_id')->label('Fail Baharu')
                        ->options(fn () => RegistryFile::query()->where('mosque_id', $mosque()->id)->where('status', 'terbuka')->get()->mapWithKeys(fn ($f) => [$f->id => "{$f->file_no} — {$f->title}"]))
                        ->searchable()->required(),
                    Textarea::make('reason')->label('Sebab')->required(),
                ])
                ->action(function (array $data) use ($mosque) {
                    $target = RegistryFile::query()->where('mosque_id', $mosque()->id)->findOrFail($data['registry_file_id']);
                    app(InboxIngestService::class)->moveToFile($this->getRecord(), $target, $data['reason'], Auth::user());
                    Notification::make()->title('Rekod dipindahkan.')->success()->send();
                }),

            Action::make('janaQr')
                ->label('Jana Kod QR')
                ->icon('heroicon-o-qr-code')
                ->authorize('generateQr')
                ->action(function () {
                    $pdf = app(QrLabelService::class)->recordPdf($this->getRecord());

                    return response()->streamDownload(fn () => print ($pdf), 'qr-'.substr($this->getRecord()->ulid, -6).'.pdf');
                }),
        ];
    }

    protected function memberOptions(): array
    {
        $record = $this->getRecord();

        return $record->mosque->users()->where('users.is_active', true)->get()
            ->filter(fn (User $user) => $user->can('view', $record))
            ->pluck('name', 'id')
            ->toArray();
    }

    protected function approverOptions(): array
    {
        $mosque = $this->getRecord()->mosque;

        return $mosque->users()->where('users.is_active', true)->get()
            ->filter(fn (User $u) => $u->canIn($mosque, 'approvals.decide') && $u->can('view', $this->getRecord()))
            ->pluck('name', 'id')
            ->toArray();
    }
}

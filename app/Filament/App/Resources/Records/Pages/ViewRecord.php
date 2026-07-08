<?php

namespace App\Filament\App\Resources\Records\Pages;

use App\Concerns\ChecksSensitivity;
use App\Enums\MinitPriority;
use App\Enums\Sensitivity;
use App\Filament\App\Resources\Records\RecordResource;
use App\Models\SensitiveAccessLog;
use App\Services\MinitService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord as BaseViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewRecord extends BaseViewRecord
{
    use ChecksSensitivity;

    protected static string $resource = RecordResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // §15.4 — Log akses rekod sulit ditulis pada MOUNT (bukan dalam policy).
        $rec = $this->getRecord();

        if ($this->effectiveSensitivity($rec) === Sensitivity::Sulit) {
            SensitiveAccessLog::query()->create([
                'mosque_id' => $rec->mosque_id,
                'is_superadmin' => (bool) Auth::user()?->is_superadmin,
                'user_id' => Auth::id(),
                'record_id' => $rec->id,
                'action' => 'view',
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edarkanMinit')
                ->label('Edarkan Minit')
                ->icon('heroicon-o-paper-airplane')
                ->visible(fn () => Auth::user()->canIn($this->getRecord()->mosque, 'minit.create'))
                ->schema([
                    Select::make('action')
                        ->label('Penerima Tindakan')
                        ->multiple()
                        ->options(fn () => $this->memberOptions())
                        ->required(),
                    Select::make('cc')
                        ->label('Penerima Makluman (s.k.)')
                        ->multiple()
                        ->options(fn () => $this->memberOptions()),
                    Textarea::make('body')->label('Catatan / Arahan')->required(),
                    Select::make('priority')
                        ->label('Keutamaan')
                        ->options(['biasa' => 'Biasa', 'segera' => 'Segera', 'kritikal' => 'Kritikal'])
                        ->default('biasa')
                        ->required(),
                ])
                ->action(function (array $data) {
                    app(MinitService::class)->create(
                        $this->getRecord(),
                        Auth::user(),
                        $data['action'],
                        $data['cc'] ?? [],
                        $data['body'],
                        MinitPriority::from($data['priority']),
                    );

                    Notification::make()->title('Minit diedarkan kepada penerima.')->success()->send();
                }),
        ];
    }

    protected function memberOptions(): array
    {
        return $this->getRecord()->mosque->users()->pluck('name', 'users.id')->toArray();
    }
}

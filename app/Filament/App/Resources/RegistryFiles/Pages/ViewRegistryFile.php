<?php

namespace App\Filament\App\Resources\RegistryFiles\Pages;

use App\Filament\App\Resources\RegistryFiles\RegistryFileResource;
use App\Services\FileTrackingService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ViewRegistryFile extends ViewRecord
{
    protected static string $resource = RegistryFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            // F6-W1 (§7.2) — `screen.keluarkan-fail-fizikal` / `screen.pindah-lokasi-fizikal`.
            // Kedua-dua aksi hanya dirender untuk fail bermedium fizikal/hibrid; tenant demo
            // kini mempunyai satu fail hibrid supaya sasaran ini benar-benar wujud.
            Action::make('keluarFizikal')->label('Keluarkan Fail')->icon('heroicon-o-arrow-up-tray')->authorize('track')
                ->extraAttributes(['data-help-target' => 'file-checkout'])
                ->modalSubmitAction(fn (Action $action): Action => $action
                    ->extraAttributes(['data-help-target' => 'file-checkout-submit']))
                ->visible(fn () => in_array($this->getRecord()->medium, ['fizikal', 'hibrid'], true) && $this->getRecord()->custody_status !== 'dipinjam')
                ->schema([
                    Select::make('holder_user_id')->label('Pemegang Ahli')->options(fn () => $this->getRecord()->mosque->users()->where('users.is_active', true)->pluck('name', 'users.id'))->searchable()
                        ->extraFieldWrapperAttributes(['data-help-target' => 'file-checkout-holder']),
                    TextInput::make('holder_name')->label('Nama Pemegang Luar / Tambahan'),
                    TextInput::make('to_location')->label('Lokasi Tujuan')
                        ->extraFieldWrapperAttributes(['data-help-target' => 'file-checkout-location']),
                    DateTimePicker::make('due_at')->label('Perlu Dipulangkan')->seconds(false)
                        ->extraFieldWrapperAttributes(['data-help-target' => 'file-checkout-due']),
                    Textarea::make('notes')->label('Catatan')->required()
                        ->extraFieldWrapperAttributes(['data-help-target' => 'file-checkout-notes']),
                ])->action(function (array $data): void {
                    // F6-W2 — KEGAGALAN SENYAP (keluarga sama seperti Mohon Pembetulan).
                    // `FileTrackingService::checkout()` menolak dengan ValidationException
                    // berkunci `holder`/`file` — kunci itu BUKAN medan borang ini
                    // (`holder_user_id`, `holder_name`), jadi Filament tiada tempat untuk
                    // merender mesejnya: modal kekal terbuka tanpa sebarang maklum balas.
                    // Diukur pada gate: 4 permintaan `/livewire/update`, 0 mesej ralat.
                    try {
                        app(FileTrackingService::class)->checkout($this->getRecord(), Auth::user(), $data);
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title('Pergerakan tidak dapat direkodkan')
                            ->body(collect($e->errors())->flatten()->first() ?? 'Semak medan pemegang fail.')
                            ->danger()->send();

                        throw new Halt;
                    }

                    Notification::make()->title('Pergerakan keluar direkodkan.')->success()->send();
                }),
            Action::make('masukFizikal')->label('Terima Pulangan')->icon('heroicon-o-arrow-down-tray')->authorize('track')
                ->visible(fn () => in_array($this->getRecord()->medium, ['fizikal', 'hibrid'], true) && $this->getRecord()->custody_status === 'dipinjam')
                ->schema([TextInput::make('location')->label('Lokasi Simpanan'), Textarea::make('notes')->label('Catatan')])
                ->action(function (array $data): void {
                    app(FileTrackingService::class)->return($this->getRecord(), Auth::user(), $data['location'] ?? null, $data['notes'] ?? null);
                    Notification::make()->title('Pulangan fail direkodkan.')->success()->send();
                }),
            Action::make('pindahFizikal')->label('Pindah Lokasi')->icon('heroicon-o-map-pin')->authorize('track')
                ->extraAttributes(['data-help-target' => 'file-relocate'])
                ->modalSubmitAction(fn (Action $action): Action => $action
                    ->extraAttributes(['data-help-target' => 'file-relocate-submit']))
                ->visible(fn () => in_array($this->getRecord()->medium, ['fizikal', 'hibrid'], true))
                ->schema([
                    TextInput::make('location')->label('Lokasi Baharu')->required()
                        ->extraFieldWrapperAttributes(['data-help-target' => 'file-relocate-location']),
                    Textarea::make('notes')->label('Catatan')
                        ->extraFieldWrapperAttributes(['data-help-target' => 'file-relocate-notes']),
                ])
                ->action(function (array $data): void {
                    app(FileTrackingService::class)->relocate($this->getRecord(), Auth::user(), $data['location'], $data['notes'] ?? null);
                    Notification::make()->title('Lokasi fail dikemaskini.')->success()->send();
                }),
        ];
    }
}

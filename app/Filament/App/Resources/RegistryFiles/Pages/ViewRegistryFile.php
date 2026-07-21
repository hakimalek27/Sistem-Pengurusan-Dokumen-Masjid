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
use Illuminate\Support\Facades\Auth;

class ViewRegistryFile extends ViewRecord
{
    protected static string $resource = RegistryFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('keluarFizikal')->label('Keluarkan Fail')->icon('heroicon-o-arrow-up-tray')->authorize('track')
                ->visible(fn () => in_array($this->getRecord()->medium, ['fizikal', 'hibrid'], true) && $this->getRecord()->custody_status !== 'dipinjam')
                ->schema([
                    Select::make('holder_user_id')->label('Pemegang Ahli')->options(fn () => $this->getRecord()->mosque->users()->where('users.is_active', true)->pluck('name', 'users.id'))->searchable(),
                    TextInput::make('holder_name')->label('Nama Pemegang Luar / Tambahan'),
                    TextInput::make('to_location')->label('Lokasi Tujuan'),
                    DateTimePicker::make('due_at')->label('Perlu Dipulangkan')->seconds(false),
                    Textarea::make('notes')->label('Catatan')->required(),
                ])->action(function (array $data): void {
                    app(FileTrackingService::class)->checkout($this->getRecord(), Auth::user(), $data);
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
                ->visible(fn () => in_array($this->getRecord()->medium, ['fizikal', 'hibrid'], true))
                ->schema([TextInput::make('location')->label('Lokasi Baharu')->required(), Textarea::make('notes')->label('Catatan')])
                ->action(function (array $data): void {
                    app(FileTrackingService::class)->relocate($this->getRecord(), Auth::user(), $data['location'], $data['notes'] ?? null);
                    Notification::make()->title('Lokasi fail dikemaskini.')->success()->send();
                }),
        ];
    }
}

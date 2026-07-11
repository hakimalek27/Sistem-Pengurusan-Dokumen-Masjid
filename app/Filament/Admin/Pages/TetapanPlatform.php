<?php

namespace App\Filament\Admin\Pages;

use App\Models\PlatformSetting;
use App\Services\WhatsAppGateway;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TetapanPlatform extends Page
{
    protected string $view = 'filament.admin.pages.tetapan-platform';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog8Tooth;

    protected static ?string $slug = 'tetapan-platform';

    protected static ?string $navigationLabel = 'Tetapan Platform';

    protected static ?string $title = 'Tetapan Platform';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit')
                ->label('Edit Tetapan')
                ->icon('heroicon-o-pencil')
                ->authorize(fn () => Auth::user()?->is_superadmin ?? false)
                ->fillForm(fn () => [
                    'per_gb_year_rm' => PlatformSetting::get('pricing', [])['per_gb_year_rm'] ?? null,
                    'block_gb' => PlatformSetting::get('pricing', [])['block_gb'] ?? 10,
                    'bank' => PlatformSetting::get('bank_details', [])['bank'] ?? null,
                    'account_name' => PlatformSetting::get('bank_details', [])['account_name'] ?? null,
                    'account_no' => PlatformSetting::get('bank_details', [])['account_no'] ?? null,
                    'dpo_name' => PlatformSetting::get('data_protection_officer', [])['name'] ?? null,
                    'dpo_email' => PlatformSetting::get('data_protection_officer', [])['email'] ?? null,
                    'registration_open' => (bool) PlatformSetting::get('registration_open', true),
                ])
                ->schema([
                    TextInput::make('per_gb_year_rm')->label('Harga RM/GB/tahun')->numeric()->nullable(),
                    TextInput::make('block_gb')->label('Saiz Blok (GB)')->numeric()->default(10),
                    TextInput::make('bank')->label('Bank')->nullable(),
                    TextInput::make('account_name')->label('Nama Akaun')->nullable(),
                    TextInput::make('account_no')->label('No. Akaun')->nullable(),
                    TextInput::make('dpo_name')->label('DPO Platform — Nama')->nullable(),
                    TextInput::make('dpo_email')->label('DPO Platform — E-mel')->email()->nullable(),
                    Toggle::make('registration_open')->label('Pendaftaran Terbuka'),
                ])
                ->action(function (array $data) {
                    PlatformSetting::put('pricing', ['per_gb_year_rm' => $data['per_gb_year_rm'], 'block_gb' => (int) $data['block_gb']]);
                    PlatformSetting::put('bank_details', ['bank' => $data['bank'], 'account_name' => $data['account_name'], 'account_no' => $data['account_no']]);
                    PlatformSetting::put('data_protection_officer', ['name' => $data['dpo_name'], 'email' => $data['dpo_email'], 'phone' => null]);
                    PlatformSetting::put('registration_open', (bool) $data['registration_open']);
                    Notification::make()->title('Tetapan platform dikemas kini.')->success()->send();
                }),

            Action::make('ujiGateway')
                ->label('Uji Gateway WA')
                ->icon('heroicon-o-signal')
                ->authorize(fn () => Auth::user()?->is_superadmin ?? false)
                ->action(function () {
                    $ok = app(WhatsAppGateway::class)->ping();
                    Notification::make()->title('Gateway: '.($ok ? 'OK' : 'GAGAL'))->status($ok ? 'success' : 'danger')->send();
                }),

            Action::make('ujiCos')
                ->label('Uji COS')
                ->icon('heroicon-o-cloud')
                ->authorize(fn () => Auth::user()?->is_superadmin ?? false)
                ->action(function () {
                    try {
                        $disk = Storage::disk(config('diwan.storage_disk'));
                        $key = 'platform/_test_'.uniqid().'.txt';
                        $disk->put($key, 'ujian');
                        $ok = $disk->exists($key);
                        $disk->delete($key);
                        Notification::make()->title('COS: '.($ok ? 'OK (tulis/baca/padam)' : 'GAGAL'))->status($ok ? 'success' : 'danger')->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('COS GAGAL: '.$e->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'pricing' => PlatformSetting::get('pricing', []),
            'bank' => PlatformSetting::get('bank_details', []),
            'gatewayStatus' => PlatformSetting::get('gateway_status', ['ok' => null]),
            'registrationOpen' => (bool) PlatformSetting::get('registration_open', true),
        ];
    }
}

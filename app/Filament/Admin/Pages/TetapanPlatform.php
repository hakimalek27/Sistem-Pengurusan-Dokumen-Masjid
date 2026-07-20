<?php

namespace App\Filament\Admin\Pages;

use App\Contracts\DriveClient;
use App\Models\PlatformSetting;
use App\Services\GoogleDrive\DriveConfig;
use App\Services\GoogleDrive\GoogleOAuthService;
use App\Services\TelegramService;
use App\Services\WhatsAppGateway;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TetapanPlatform extends Page
{
    protected string $view = 'filament.admin.pages.tetapan-platform';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog8Tooth;

    protected static ?string $slug = 'tetapan-platform';

    protected static ?string $navigationLabel = 'Tetapan Platform';

    protected static string|\UnitEnum|null $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 10;

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

            Action::make('telegram')
                ->label('Tetapan Telegram')
                ->icon('heroicon-o-paper-airplane')
                ->authorize(fn () => Auth::user()?->is_superadmin ?? false)
                ->fillForm(fn () => [
                    'bot_username' => config('diwan.telegram.bot_username'),
                    'webhook_secret' => config('diwan.telegram.webhook_secret'),
                ])
                ->schema([
                    TextInput::make('bot_token')
                        ->label('Token Bot (BotFather)')
                        ->password()
                        ->revealable()
                        ->autocomplete(false)
                        ->helperText('Biarkan kosong untuk kekalkan token sedia ada.'),
                    TextInput::make('bot_username')
                        ->label('Nama Pengguna Bot (tanpa @)')
                        ->placeholder('DiwanNotifBot'),
                    TextInput::make('webhook_secret')
                        ->label('Rahsia Webhook')
                        ->helperText('Auto-jana rawak jika dibiarkan kosong.'),
                ])
                ->action(function (array $data) {
                    if (filled($data['bot_token'] ?? null)) {
                        PlatformSetting::putEncrypted('telegram_bot_token', $data['bot_token']);
                    }
                    PlatformSetting::put('telegram_bot_username', $data['bot_username'] ?: null);
                    $secret = filled($data['webhook_secret'] ?? null) ? $data['webhook_secret'] : Str::random(32);
                    PlatformSetting::putEncrypted('telegram_webhook_secret', $secret);
                    Cache::forget('platform:telegram');
                    Notification::make()->title('Tetapan Telegram disimpan. Klik "Set Webhook Telegram" untuk mengaktifkannya.')->success()->send();
                }),

            Action::make('setTelegramWebhook')
                ->label('Set Webhook Telegram')
                ->icon('heroicon-o-link')
                ->authorize(fn () => Auth::user()?->is_superadmin ?? false)
                ->action(function () {
                    // Muat nilai terkini dari DB (bypass cache) sebelum panggil API.
                    Cache::forget('platform:telegram');
                    TelegramService::hydrateRuntimeConfig(false);
                    $result = app(TelegramService::class)->setWebhook(true);
                    Notification::make()->title($result['message'])->status($result['ok'] ? 'success' : 'danger')->send();
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

            // §4.6′ — Mirror backup Google Drive (akaun pemilik, boleh browse).
            Action::make('gdrive')
                ->label('Tetapan Google Drive')
                ->icon('heroicon-o-cloud-arrow-up')
                ->authorize(fn () => Auth::user()?->is_superadmin ?? false)
                ->fillForm(fn () => [
                    'gdrive_client_id' => PlatformSetting::get('gdrive_client_id'),
                    'gdrive_enabled' => (bool) PlatformSetting::get('gdrive_enabled', false),
                    'gdrive_keep_dumps' => (int) PlatformSetting::get('gdrive_keep_dumps', 7),
                ])
                ->schema([
                    TextInput::make('gdrive_client_id')
                        ->label('Client ID (Google Cloud OAuth)')
                        ->autocomplete(false),
                    TextInput::make('gdrive_client_secret')
                        ->label('Client Secret')
                        ->password()
                        ->revealable()
                        ->autocomplete(false)
                        ->helperText('Biarkan kosong untuk kekalkan secret sedia ada.'),
                    Toggle::make('gdrive_enabled')
                        ->label('Aktifkan mirror ke Google Drive')
                        ->helperText('Dokumen setiap masjid disalin ke folder SPDM/Backup dalam Drive anda.'),
                    TextInput::make('gdrive_keep_dumps')
                        ->label('Simpan berapa salinan DB dump')
                        ->numeric()->default(7)->minValue(1),
                ])
                ->action(function (array $data) {
                    PlatformSetting::put('gdrive_client_id', $data['gdrive_client_id'] ?: null);
                    if (filled($data['gdrive_client_secret'] ?? null)) {
                        PlatformSetting::putEncrypted('gdrive_client_secret', $data['gdrive_client_secret']);
                    }
                    PlatformSetting::put('gdrive_enabled', (bool) ($data['gdrive_enabled'] ?? false));
                    PlatformSetting::put('gdrive_keep_dumps', max(1, (int) ($data['gdrive_keep_dumps'] ?? 7)));
                    DriveConfig::forget();
                    Notification::make()->title('Tetapan Google Drive disimpan. Klik "Sambung Google Drive" untuk membenarkan akses.')->success()->send();
                }),

            Action::make('sambungGdrive')
                ->label('Sambung Google Drive')
                ->icon('heroicon-o-link')
                ->authorize(fn () => Auth::user()?->is_superadmin ?? false)
                ->visible(fn () => DriveConfig::configured())
                ->action(function () {
                    $state = Str::random(40);
                    Cache::put('gdrive_oauth_state:'.Auth::id(), $state, now()->addMinutes(10));

                    return redirect()->away(app(GoogleOAuthService::class)->authUrl($state));
                }),

            Action::make('ujiGdrive')
                ->label('Uji Google Drive')
                ->icon('heroicon-o-cloud')
                ->authorize(fn () => Auth::user()?->is_superadmin ?? false)
                ->visible(fn () => DriveConfig::connected())
                ->action(function () {
                    try {
                        $about = app(DriveClient::class)->about();
                        $gb = fn ($b) => $b !== null ? round($b / (1024 ** 3), 2).' GB' : '—';
                        Notification::make()
                            ->title('Google Drive OK — '.($about['email'] ?? '—'))
                            ->body('Guna: '.$gb($about['usage']).' / '.$gb($about['limit']))
                            ->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Google Drive GAGAL: '.$e->getMessage())->danger()->send();
                    }
                }),

            Action::make('putusGdrive')
                ->label('Putus Google Drive')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->authorize(fn () => Auth::user()?->is_superadmin ?? false)
                ->visible(fn () => DriveConfig::connected())
                ->action(function () {
                    PlatformSetting::putEncrypted('gdrive_refresh_token', null);
                    PlatformSetting::put('gdrive_enabled', false);
                    PlatformSetting::put('gdrive_account', null);
                    PlatformSetting::put('gdrive_status', ['ok' => false, 'at' => now()->toIso8601String()]);
                    DriveConfig::forget();
                    Notification::make()->title('Google Drive diputuskan.')->success()->send();
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
            'telegramConfigured' => filled(config('diwan.telegram.bot_token')) && filled(config('diwan.telegram.webhook_secret')),
            'telegramUsername' => config('diwan.telegram.bot_username'),
            'telegramWebhookStatus' => PlatformSetting::get('telegram_webhook_status'),
            'gdriveConfigured' => DriveConfig::configured(),
            'gdriveConnected' => DriveConfig::connected(),
            'gdriveEnabled' => (bool) PlatformSetting::get('gdrive_enabled', false),
            'gdriveAccount' => PlatformSetting::get('gdrive_account'),
            'gdriveStatus' => PlatformSetting::get('gdrive_status'),
        ];
    }
}

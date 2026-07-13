<?php

namespace App\Filament\App\Pages;

use App\Models\WhatsAppIntegration;
use App\Services\MailIngestService;
use App\Services\WhatsAppIntegrationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class TetapanMasjid extends Page
{
    public ?string $whatsappQr = null;

    public ?string $whatsappLinkingCode = null;

    public ?string $whatsappPairStatus = null;

    protected string $view = 'filament.app.pages.tetapan-masjid';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $slug = 'tetapan-masjid';

    protected static ?string $navigationLabel = 'Tetapan Masjid';

    protected static ?string $title = 'Tetapan Masjid';

    protected static string|UnitEnum|null $navigationGroup = 'Pentadbiran';

    protected static ?int $navigationSort = 6;

    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();

        return $tenant && Auth::user()?->canIn($tenant, 'mosque.settings');
    }

    protected function getHeaderActions(): array
    {
        $mosque = Filament::getTenant();

        return [
            Action::make('edit')
                ->label('Edit Tetapan')
                ->icon('heroicon-o-pencil')
                ->authorize(fn () => Auth::user()?->canIn($mosque, 'mosque.settings') ?? false)
                ->fillForm(fn () => [
                    'phone' => $mosque->phone,
                    'dpr_name' => $mosque->settings['data_protection_rep']['name'] ?? null,
                    'dpr_email' => $mosque->settings['data_protection_rep']['email'] ?? null,
                    'wa_intake_keyword' => $mosque->waIntakeKeyword(),
                    'wa_intake_enabled' => $mosque->waIntakeEnabled(),
                    'mail_intake_keyword' => $mosque->mailIntakeKeyword(),
                    'mail_intake_enabled' => $mosque->mailIntakeEnabled(),
                    'mail_intake_senders' => $mosque->mailIntakeSenders(),
                ])
                ->schema([
                    TextInput::make('phone')->label('Telefon Masjid')->nullable(),
                    TextInput::make('dpr_name')->label('Wakil Perlindungan Data — Nama')->nullable(),
                    TextInput::make('dpr_email')->label('Wakil Perlindungan Data — E-mel')->email()->nullable(),
                    TextInput::make('wa_intake_keyword')->label('Kata Kunci Intake')->default('spdm'),
                    Toggle::make('wa_intake_enabled')->label('Terima dokumen WhatsApp')->default(true),
                    Toggle::make('mail_intake_enabled')->label('Terima dokumen melalui e-mel')->default(false),
                    TextInput::make('mail_intake_keyword')->label('Kata Kunci E-mel')->default('spdm')->required(),
                    TagsInput::make('mail_intake_senders')
                        ->label('E-mel Pengirim Dibenarkan')
                        ->placeholder('admin@masjid.org')
                        ->helperText('Tekan Enter selepas setiap alamat. Hanya pengirim ini boleh memasukkan dokumen.'),
                ])
                ->action(function (array $data) use ($mosque) {
                    $mailSenders = collect($data['mail_intake_senders'] ?? [])
                        ->map(fn ($email) => strtolower(trim((string) $email)))
                        ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
                        ->unique()
                        ->values()
                        ->all();
                    if (($data['mail_intake_enabled'] ?? false) && $mailSenders === []) {
                        throw ValidationException::withMessages([
                            'mail_intake_senders' => 'Tetapkan sekurang-kurangnya satu e-mel pengirim dibenarkan.',
                        ]);
                    }

                    $settings = $mosque->settings ?? [];
                    $settings['data_protection_rep'] = ['name' => $data['dpr_name'], 'email' => $data['dpr_email'], 'phone' => null];
                    $settings['wa_intake_keyword'] = $data['wa_intake_keyword'];
                    $settings['wa_intake_enabled'] = (bool) $data['wa_intake_enabled'];
                    $settings['mail_intake_enabled'] = (bool) ($data['mail_intake_enabled'] ?? false);
                    $settings['mail_intake_keyword'] = trim((string) ($data['mail_intake_keyword'] ?? 'spdm'));
                    $settings['mail_intake_senders'] = $mailSenders;

                    $mosque->update([
                        'phone' => $data['phone'],
                        'settings' => $settings,
                    ]);

                    Notification::make()->title('Tetapan masjid dikemas kini.')->success()->send();
                }),
            Action::make('aktifkan_whatsapp')
                ->label('Aktifkan WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->authorize(fn () => Auth::user()?->canIn($mosque, 'mosque.settings') ?? false)
                ->visible(fn () => ! WhatsAppIntegration::query()->forMosque($mosque)->where('enabled', true)->exists())
                ->requiresConfirmation()
                ->modalDescription('SPDM akan mewujudkan akaun tenant gateway secara automatik. Tiada kata laluan anda dihantar atau dikongsi.')
                ->action(function () use ($mosque) {
                    $integration = app(WhatsAppIntegrationService::class)->provision($mosque);
                    $integration->status === 'linked'
                        ? Notification::make()->title('Integrasi WhatsApp berjaya diaktifkan. Seterusnya, pasangkan nombor WhatsApp.')->success()->send()
                        : Notification::make()->title($integration->last_error ?: 'Integrasi WhatsApp gagal diaktifkan.')->danger()->send();
                }),
            Action::make('pasangkan_whatsapp')
                ->label('Pasangkan Nombor')
                ->icon('heroicon-o-qr-code')
                ->authorize(fn () => Auth::user()?->canIn($mosque, 'mosque.settings') ?? false)
                ->visible(fn () => WhatsAppIntegration::query()->forMosque($mosque)->where('enabled', true)->whereNotNull('gateway_tenant_id')->exists())
                ->schema([
                    TextInput::make('device_name')->label('Nama Peranti')->default('Telefon Pejabat')->required()->maxLength(100),
                    Select::make('method')->label('Kaedah Pairing')->options(['qr' => 'Imbas Kod QR', 'phone' => 'Kod Pautan Telefon'])->default('qr')->live()->required(),
                    TextInput::make('phone')->label('Nombor WhatsApp')->placeholder('60123456789')
                        ->visible(fn (Get $get) => $get('method') === 'phone')
                        ->required(fn (Get $get) => $get('method') === 'phone'),
                ])
                ->action(function (array $data) use ($mosque) {
                    try {
                        $result = app(WhatsAppIntegrationService::class)->beginPairing(
                            $mosque,
                            $data['device_name'],
                            $data['method'] === 'phone' ? ($data['phone'] ?? null) : null,
                        );
                        $this->whatsappQr = $result['qr_code_base64'] ?? null;
                        $this->whatsappLinkingCode = $result['linking_code'] ?? null;
                        $this->whatsappPairStatus = $result['status'] ?? 'pending';
                        Notification::make()->title('Pairing dimulakan. Ikut arahan pada kad WhatsApp di halaman ini.')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
            Action::make('sync_whatsapp')
                ->label('Segerakkan Status')
                ->icon('heroicon-o-arrow-path')
                ->authorize(fn () => Auth::user()?->canIn($mosque, 'mosque.settings') ?? false)
                ->visible(fn () => WhatsAppIntegration::query()->forMosque($mosque)->whereNotNull('session_id')->exists())
                ->action(fn () => $this->pollWhatsAppStatus()),
            Action::make('toggle_whatsapp')
                ->label(fn () => WhatsAppIntegration::query()->forMosque($mosque)->value('enabled') ? 'Matikan Notifikasi WA' : 'Aktifkan Semula WA')
                ->color(fn () => WhatsAppIntegration::query()->forMosque($mosque)->value('enabled') ? 'danger' : 'success')
                ->authorize(fn () => Auth::user()?->canIn($mosque, 'mosque.settings') ?? false)
                ->visible(fn () => WhatsAppIntegration::query()->forMosque($mosque)->whereNotNull('gateway_tenant_id')->exists())
                ->requiresConfirmation()
                ->action(function () use ($mosque) {
                    $integration = WhatsAppIntegration::query()->forMosque($mosque)->firstOrFail();
                    app(WhatsAppIntegrationService::class)->setEnabled($mosque, ! $integration->enabled);
                    Notification::make()->title($integration->enabled ? 'Notifikasi WhatsApp dimatikan.' : 'Notifikasi WhatsApp diaktifkan semula.')->success()->send();
                }),
        ];
    }

    public function pollWhatsAppStatus(): void
    {
        $mosque = Filament::getTenant();
        abort_unless(Auth::user()?->canIn($mosque, 'mosque.settings'), 403);

        try {
            $before = WhatsAppIntegration::query()->forMosque($mosque)->value('status');
            $integration = app(WhatsAppIntegrationService::class)->syncStatus($mosque);
            $this->whatsappPairStatus = $integration->status;

            if ($integration->status === 'connected') {
                $this->whatsappQr = null;
                $this->whatsappLinkingCode = null;
                if ($before !== 'connected') {
                    Notification::make()->title('Nombor WhatsApp berjaya disambungkan.')->success()->send();
                }
            }
        } catch (\Throwable $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    public function refreshWhatsAppQr(): void
    {
        $mosque = Filament::getTenant();
        abort_unless(Auth::user()?->canIn($mosque, 'mosque.settings'), 403);

        try {
            $result = app(WhatsAppIntegrationService::class)->refreshQr($mosque);
            $this->whatsappQr = $result['qr_code_base64'] ?? $this->whatsappQr;
            $this->whatsappPairStatus = $result['status'] ?? $this->whatsappPairStatus;
        } catch (\Throwable $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    protected function getViewData(): array
    {
        $mosque = Filament::getTenant();

        return [
            'mosque' => $mosque,
            'scanEmail' => app(MailIngestService::class)->intakeAddress($mosque),
            'whatsappIntegration' => WhatsAppIntegration::query()->forMosque($mosque)->first(),
        ];
    }
}

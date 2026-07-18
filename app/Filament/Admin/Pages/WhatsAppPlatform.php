<?php

namespace App\Filament\Admin\Pages;

use App\Models\WhatsAppIntegration;
use App\Services\WhatsAppIntegrationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * §11.1 / §14 — Sesi WhatsApp peringkat PLATFORM (bukan milik mana-mana masjid).
 * Digunakan untuk menghantar alert kepada superadmin (cth sesi masjid terputus).
 * Superadmin pair satu nombor khas platform di sini.
 */
class WhatsAppPlatform extends Page
{
    public ?string $whatsappQr = null;

    public ?string $whatsappLinkingCode = null;

    public ?string $whatsappPairStatus = null;

    protected string $view = 'filament.admin.pages.whatsapp-platform';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $slug = 'whatsapp-platform';

    protected static ?string $navigationLabel = 'WhatsApp Platform';

    protected static ?string $title = 'WhatsApp Platform';

    protected static ?int $navigationSort = 90;

    public static function canAccess(): bool
    {
        return (bool) Auth::user()?->is_superadmin;
    }

    protected function service(): WhatsAppIntegrationService
    {
        return app(WhatsAppIntegrationService::class);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aktifkan')
                ->label('Aktifkan WhatsApp Platform')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->visible(fn () => ! WhatsAppIntegration::query()->platform()->where('enabled', true)->exists())
                ->requiresConfirmation()
                ->modalDescription('SPDM akan mewujudkan akaun tenant gateway peringkat platform.')
                ->action(function () {
                    $integration = $this->service()->provision(null);
                    $integration->status === 'linked'
                        ? Notification::make()->title('Integrasi platform diaktifkan. Seterusnya, pasangkan nombor.')->success()->send()
                        : Notification::make()->title($integration->last_error ?: 'Integrasi gagal diaktifkan.')->danger()->send();
                }),

            Action::make('pasangkan')
                ->label('Pasangkan Nombor')
                ->icon('heroicon-o-qr-code')
                ->visible(fn () => WhatsAppIntegration::query()->platform()->where('enabled', true)->whereNotNull('gateway_tenant_id')->exists())
                ->schema([
                    TextInput::make('device_name')->label('Nama Peranti')->default('Nombor Platform')->required()->maxLength(100),
                    Select::make('method')->label('Kaedah Pairing')->options(['qr' => 'Imbas Kod QR', 'phone' => 'Kod Pautan Telefon'])->default('qr')->live()->required(),
                    TextInput::make('phone')->label('Nombor WhatsApp')->placeholder('60123456789')
                        ->visible(fn (Get $get) => $get('method') === 'phone')
                        ->required(fn (Get $get) => $get('method') === 'phone'),
                ])
                ->action(function (array $data) {
                    try {
                        $result = $this->service()->beginPairing(
                            null,
                            $data['device_name'],
                            $data['method'] === 'phone' ? ($data['phone'] ?? null) : null,
                        );
                        $this->whatsappQr = $result['qr_code_base64'] ?? null;
                        $this->whatsappLinkingCode = $result['linking_code'] ?? null;
                        $this->whatsappPairStatus = $result['status'] ?? 'pending';
                        Notification::make()->title('Pairing dimulakan. Ikut arahan di halaman ini.')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('sync')
                ->label('Segerakkan Status')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn () => WhatsAppIntegration::query()->platform()->whereNotNull('session_id')->exists())
                ->action(function () {
                    try {
                        $integration = $this->service()->syncStatus(null);
                        $this->whatsappPairStatus = $integration->status;
                        if ($integration->status === 'connected') {
                            $this->whatsappQr = null;
                            $this->whatsappLinkingCode = null;
                        }
                        Notification::make()->title('Status platform: '.$integration->status)->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('toggle')
                ->label(fn () => WhatsAppIntegration::query()->platform()->value('enabled') ? 'Matikan WA Platform' : 'Aktifkan Semula WA Platform')
                ->color(fn () => WhatsAppIntegration::query()->platform()->value('enabled') ? 'danger' : 'success')
                ->visible(fn () => WhatsAppIntegration::query()->platform()->whereNotNull('gateway_tenant_id')->exists())
                ->requiresConfirmation()
                ->action(function () {
                    $integration = WhatsAppIntegration::query()->platform()->firstOrFail();
                    $this->service()->setEnabled(null, ! $integration->enabled);
                    Notification::make()->title('Tetapan WhatsApp platform dikemas kini.')->success()->send();
                }),
        ];
    }

    protected function getViewData(): array
    {
        $integration = WhatsAppIntegration::query()->platform()->first();

        return [
            'integration' => $integration,
            'qr' => $this->whatsappQr,
            'linkingCode' => $this->whatsappLinkingCode,
            'pairStatus' => $this->whatsappPairStatus ?? $integration?->status,
        ];
    }
}

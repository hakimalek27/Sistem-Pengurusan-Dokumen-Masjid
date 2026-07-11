<?php

namespace App\Filament\App\Pages;

use App\Models\StorageAddon;
use App\Models\StorageOrder;
use App\Services\BillingService;
use App\Services\QuotaService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class PenggunaanStoran extends Page
{
    protected string $view = 'filament.app.pages.penggunaan-storan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?string $slug = 'penggunaan';

    protected static ?string $navigationLabel = 'Penggunaan & Storan';

    protected static ?string $title = 'Penggunaan & Storan';

    protected static string|UnitEnum|null $navigationGroup = 'Akaun';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();

        return $tenant && Auth::user()?->canIn($tenant, 'usage.view');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tambahStoran')
                ->label('Tambah Storan')
                ->icon('heroicon-o-plus')
                ->authorize(fn () => Auth::user()?->canIn(Filament::getTenant(), 'storage.order') ?? false)
                ->visible(fn () => Auth::user()->canIn(Filament::getTenant(), 'storage.order'))
                ->schema([
                    TextInput::make('blocks')
                        ->label('Bilangan Blok ('.app(BillingService::class)->blockGb().' GB setiap satu)')
                        ->numeric()->minValue(1)->default(1)->required(),
                ])
                ->action(function (array $data) {
                    $order = app(BillingService::class)->createOrder(Filament::getTenant(), Auth::user(), (int) $data['blocks']);
                    Notification::make()
                        ->title('Pesanan dijana: '.$order->invoice_no)
                        ->body('Invois PDF disediakan. Status: menunggu pengesahan bayaran.')
                        ->success()->send();
                }),
        ];
    }

    protected function getViewData(): array
    {
        $mosque = Filament::getTenant();
        $quota = app(QuotaService::class);

        return [
            'usedGb' => round((int) $mosque->storage_used_bytes / (1024 ** 3), 3),
            'quotaGb' => round($quota->effectiveQuota($mosque) / (1024 ** 3), 2),
            'percent' => round($quota->usagePercent($mosque), 1),
            'orders' => StorageOrder::query()->where('mosque_id', $mosque->id)->latest()->get(),
            'addons' => StorageAddon::query()->where('mosque_id', $mosque->id)->latest()->get(),
        ];
    }
}

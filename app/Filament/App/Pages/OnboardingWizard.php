<?php

namespace App\Filament\App\Pages;

use App\Models\WhatsAppIntegration;
use App\Services\MembershipService;
use App\Support\Roles;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * §10 Aliran I — Persediaan berpandu untuk admin masjid selepas kelulusan:
 * jawatan, nombor WhatsApp masjid, dan daftar ahli sekali gus. Kekal 1 peranan
 * per ahli (Pentadbir sudah merangkumi kuasa Kerani/Setiausaha).
 */
class OnboardingWizard extends Page
{
    protected string $view = 'filament.app.pages.onboarding-wizard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    protected static ?string $slug = 'persediaan';

    protected static ?string $navigationLabel = 'Persediaan Berpandu';

    protected static ?string $title = 'Persediaan Berpandu Masjid';

    protected static string|UnitEnum|null $navigationGroup = 'Pentadbiran';

    protected static ?int $navigationSort = 0;

    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();

        return $tenant && Auth::user()?->canIn($tenant, 'mosque.settings');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('mula')
                ->label('Mula Persediaan Berpandu')
                ->icon('heroicon-o-rocket-launch')
                ->authorize(fn () => Auth::user()?->canIn(Filament::getTenant(), 'mosque.settings') ?? false)
                ->modalWidth('3xl')
                ->steps([
                    Step::make('Peranan Anda')
                        ->description('Jawatan anda dalam masjid')
                        ->schema([
                            Placeholder::make('info_peranan')
                                ->label('')
                                ->content('Sebagai Pentadbir Masjid, anda sudah merangkumi semua kuasa Kerani dan Setiausaha — tidak perlu akaun berasingan untuk kerja tersebut. Isi jawatan anda untuk paparan sahaja (cth "Pentadbir / Setiausaha").'),
                            TextInput::make('jawatan')
                                ->label('Jawatan Anda')
                                ->default(fn () => Auth::user()->jawatan)
                                ->maxLength(255),
                        ]),
                    Step::make('WhatsApp Masjid')
                        ->description('Nombor untuk notifikasi & peringatan')
                        ->schema([
                            Placeholder::make('info_wa')
                                ->label('')
                                ->content('Masjid perlu satu nombor WhatsApp untuk menghantar notifikasi & peringatan kepada ahli. Anda boleh gunakan nombor sendiri atau satu nombor khas masjid. Selepas persediaan ini, pergi ke Tetapan Masjid → "Aktifkan WhatsApp" → "Pasangkan Nombor" dan sediakan telefon nombor tersebut untuk mengimbas kod QR / kod pautan.'),
                            TextInput::make('mosque_phone')
                                ->label('Nombor Telefon Masjid')
                                ->tel()
                                ->placeholder('60123456789')
                                ->default(fn () => Filament::getTenant()->phone),
                            Radio::make('wa_choice')
                                ->label('Nombor WhatsApp untuk notifikasi')
                                ->options([
                                    'own' => 'Guna nombor saya sendiri buat sementara',
                                    'dedicated' => 'Guna nombor khas masjid (saya akan sediakan telefon)',
                                ])
                                ->default('dedicated'),
                        ]),
                    Step::make('Daftar Ahli')
                        ->description('Cipta akaun AJK & pegawai')
                        ->schema([
                            Placeholder::make('info_ahli')
                                ->label('')
                                ->content('Daftarkan ahli dengan nombor telefon (wajib) — mereka akan terima pautan log masuk melalui WhatsApp/e-mel dan tetapkan kata laluan sendiri semasa masuk kali pertama. E-mel adalah pilihan.'),
                            Repeater::make('members')
                                ->label('Ahli untuk didaftarkan')
                                ->schema([
                                    TextInput::make('name')->label('Nama')->required(),
                                    Select::make('role')->label('Peranan')->options(Roles::options())->required(),
                                    TextInput::make('phone_wa')->label('No. Telefon (WhatsApp)')->tel()->required(),
                                    TextInput::make('email')->label('E-mel (pilihan)')->email(),
                                    TextInput::make('jawatan')->label('Jawatan (pilihan)'),
                                ])
                                ->addActionLabel('Tambah ahli')
                                ->default([])
                                ->columns(2),
                        ]),
                    Step::make('Selesai')
                        ->description('Semak & simpan')
                        ->schema([
                            Placeholder::make('info_selesai')
                                ->label('')
                                ->content('Klik "Selesai" untuk menyimpan jawatan anda, nombor masjid, dan mendaftar ahli. Anda boleh membuka semula persediaan ini bila-bila masa dari menu Pentadbiran.'),
                        ]),
                ])
                ->action(fn (array $data) => $this->completeOnboarding($data)),

            Action::make('langkau')
                ->label('Langkau Buat Sementara')
                ->icon('heroicon-o-forward')
                ->color('gray')
                ->link()
                ->visible(fn () => blank(data_get(Filament::getTenant()?->settings, 'onboarding_done')))
                ->requiresConfirmation()
                ->modalDescription('Persediaan akan ditandakan selesai. Anda masih boleh membukanya semula bila-bila masa dari menu Pentadbiran.')
                ->action(function () {
                    $mosque = Filament::getTenant();
                    $mosque->update(['settings' => array_merge($mosque->settings ?? [], [
                        'onboarding_done' => now()->toIso8601String(),
                    ])]);
                    Notification::make()->title('Persediaan dilangkau. Buka semula dari menu Pentadbiran bila perlu.')->success()->send();

                    return redirect('/app/'.$mosque->slug);
                }),
        ];
    }

    protected function completeOnboarding(array $data): void
    {
        $mosque = Filament::getTenant();
        $actor = Auth::user();

        if (filled($data['jawatan'] ?? null)) {
            $actor->update(['jawatan' => $data['jawatan']]);
        }

        if (filled($data['mosque_phone'] ?? null)) {
            $mosque->update(['phone' => $data['mosque_phone']]);
        }

        $created = 0;
        foreach ($data['members'] ?? [] as $member) {
            try {
                $user = app(MembershipService::class)->invite(
                    $mosque,
                    $member['email'] ?? null,
                    $member['name'],
                    $member['role'],
                    $member['phone_wa'] ?? null,
                    $actor,
                );
                if (filled($member['jawatan'] ?? null) && $user->wasRecentlyCreated) {
                    $user->update(['jawatan' => $member['jawatan']]);
                }
                $created++;
            } catch (\Throwable $e) {
                Notification::make()->title("Gagal daftar {$member['name']}: {$e->getMessage()}")->warning()->send();
            }
        }

        $mosque->update([
            'settings' => array_merge($mosque->settings ?? [], [
                'onboarding_done' => now()->toIso8601String(),
            ]),
        ]);

        Notification::make()
            ->title("Persediaan selesai. {$created} ahli didaftarkan — pautan log masuk telah dihantar.")
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        $mosque = Filament::getTenant();
        $settings = $mosque->settings ?? [];
        $whatsappReady = WhatsAppIntegration::query()->forMosque($mosque)->first()?->isReady() ?? false;

        $items = [
            ['Nombor telefon masjid ditetapkan', filled($mosque->phone)],
            ['Wakil Perlindungan Data', filled(data_get($settings, 'data_protection_rep.name'))],
            ['Sekurang-kurangnya 3 ahli aktif', $mosque->users()->where('users.is_active', true)->count() >= 3],
            ['Pengerusi ditetapkan', $mosque->users()->wherePivot('role', 'pengerusi')->exists()],
            ['Nombor WhatsApp masjid disambung', $whatsappReady],
            ['Klasifikasi fail tersedia', $mosque->classificationNodes()->where('is_active', true)->exists()],
        ];

        return [
            'items' => $items,
            'complete' => (bool) data_get($settings, 'onboarding_done'),
        ];
    }
}

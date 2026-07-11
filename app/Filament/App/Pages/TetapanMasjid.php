<?php

namespace App\Filament\App\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class TetapanMasjid extends Page
{
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
                    'wa_session_id' => $mosque->wa_session_id,
                    'wa_number' => $mosque->wa_number,
                    'wa_intake_keyword' => $mosque->waIntakeKeyword(),
                    'wa_intake_enabled' => $mosque->waIntakeEnabled(),
                ])
                ->schema([
                    TextInput::make('phone')->label('Telefon Masjid')->nullable(),
                    TextInput::make('dpr_name')->label('Wakil Perlindungan Data — Nama')->nullable(),
                    TextInput::make('dpr_email')->label('Wakil Perlindungan Data — E-mel')->email()->nullable(),
                    TextInput::make('wa_session_id')->label('ID Sesi WhatsApp (gateway)')->nullable(),
                    TextInput::make('wa_number')->label('Nombor WhatsApp Rasmi')->nullable(),
                    TextInput::make('wa_intake_keyword')->label('Kata Kunci Intake')->default('spdm'),
                    Toggle::make('wa_intake_enabled')->label('Terima dokumen WhatsApp')->default(true),
                ])
                ->action(function (array $data) use ($mosque) {
                    $settings = $mosque->settings ?? [];
                    $settings['data_protection_rep'] = ['name' => $data['dpr_name'], 'email' => $data['dpr_email'], 'phone' => null];
                    $settings['wa_intake_keyword'] = $data['wa_intake_keyword'];
                    $settings['wa_intake_enabled'] = (bool) $data['wa_intake_enabled'];

                    $mosque->update([
                        'phone' => $data['phone'],
                        'wa_session_id' => $data['wa_session_id'] ?: null,
                        'wa_number' => $data['wa_number'],
                        'settings' => $settings,
                    ]);

                    Notification::make()->title('Tetapan masjid dikemas kini.')->success()->send();
                }),
        ];
    }

    protected function getViewData(): array
    {
        $mosque = Filament::getTenant();

        return [
            'mosque' => $mosque,
            'scanEmail' => 'scan.diwan+'.$mosque->slug.'@gmail.com',
        ];
    }
}

<?php

namespace App\Filament\App\Pages;

use App\Notifications\TestNotification;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class Profil extends Page
{
    protected string $view = 'filament.app.pages.profil';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $slug = 'profil';

    protected static ?string $navigationLabel = 'Profil';

    protected static ?string $title = 'Profil Saya';

    protected static bool $shouldRegisterNavigation = false;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('notifikasi')
                ->label('Tetapan Notifikasi')
                ->icon('heroicon-o-bell')
                ->fillForm(fn () => Auth::user()->only(['notify_email', 'notify_whatsapp', 'notify_telegram']))
                ->schema([
                    Toggle::make('notify_email')->label('E-mel'),
                    Toggle::make('notify_whatsapp')->label('WhatsApp'),
                    Toggle::make('notify_telegram')->label('Telegram'),
                ])
                ->action(function (array $data) {
                    Auth::user()->update($data);
                    Notification::make()->title('Tetapan notifikasi dikemas kini.')->success()->send();
                }),

            Action::make('ujian')
                ->label('Hantar Notifikasi Ujian')
                ->icon('heroicon-o-paper-airplane')
                ->action(function () {
                    Auth::user()->notify(new TestNotification);
                    Notification::make()->title('Notifikasi ujian dihantar ke saluran aktif.')->success()->send();
                }),
        ];
    }

    protected function getViewData(): array
    {
        return ['user' => Auth::user()];
    }
}

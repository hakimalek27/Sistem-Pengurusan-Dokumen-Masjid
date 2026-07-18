<?php

namespace App\Filament\App\Pages;

use App\Notifications\TestNotification;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

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
                ->authorize(fn () => Auth::check())
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
                ->authorize(fn () => Auth::check())
                ->action(function () {
                    Auth::user()->notify(new TestNotification);
                    Notification::make()->title('Notifikasi ujian dihantar ke saluran aktif.')->success()->send();
                }),

            // §15.1 — Kata laluan ialah laluan log masuk fallback (magic link kekal utama).
            Action::make('kata_laluan')
                ->label('Tetapkan Kata Laluan')
                ->icon('heroicon-o-key')
                ->authorize(fn () => Auth::check())
                ->modalDescription('Tetapkan kata laluan untuk log masuk tanpa pautan e-mel. Pautan log masuk e-mel tetap boleh digunakan.')
                ->schema([
                    TextInput::make('password')
                        ->label('Kata Laluan Baharu')
                        ->password()
                        ->revealable()
                        ->required()
                        ->rule(Password::default())
                        ->same('password_confirmation'),
                    TextInput::make('password_confirmation')
                        ->label('Sahkan Kata Laluan')
                        ->password()
                        ->revealable()
                        ->required()
                        ->dehydrated(false),
                ])
                ->action(function (array $data) {
                    Auth::user()->update(['password' => Hash::make($data['password'])]);
                    Notification::make()
                        ->title('Kata laluan dikemas kini. Anda kini boleh log masuk dengan kata laluan.')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getViewData(): array
    {
        return ['user' => Auth::user()];
    }
}

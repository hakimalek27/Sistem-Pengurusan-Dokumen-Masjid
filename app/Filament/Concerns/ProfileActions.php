<?php

namespace App\Filament\Concerns;

use App\Notifications\TestNotification;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Aksi profil dikongsi antara panel masjid (Profil) dan panel superadmin
 * (Profil Saya): tetapan notifikasi, ujian, sambung/putus Telegram, kata laluan.
 */
trait ProfileActions
{
    protected function profileActions(): array
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

            // §11.2 — Sambung akaun Telegram (token pendek → deep link t.me).
            Action::make('sambung_telegram')
                ->label('Sambung Telegram')
                ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
                ->visible(fn () => blank(Auth::user()->telegram_chat_id) && filled(config('diwan.telegram.bot_username')))
                ->action(function () {
                    $token = Str::random(48);
                    // TTL 60 minit (dinaikkan dari 15): AJK yang jarang buka telefon
                    // sempat tekan Start sebelum pautan tamat tempoh.
                    Cache::put('telegram_connect:'.$token, Auth::id(), now()->addMinutes(60));
                    $url = 'https://t.me/'.config('diwan.telegram.bot_username').'?start='.$token;

                    Notification::make()
                        ->title('Sambung Telegram')
                        ->body(new HtmlString(
                            'Buka pautan ini di telefon anda &amp; tekan <strong>Start</strong> (sah 60 minit):<br>'
                            .'<a href="'.e($url).'" target="_blank" class="underline text-primary-600">'.e($url).'</a>'
                        ))
                        ->persistent()
                        ->success()
                        ->send();
                }),

            Action::make('putus_telegram')
                ->label('Putuskan Telegram')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => filled(Auth::user()->telegram_chat_id))
                ->requiresConfirmation()
                ->action(function () {
                    Auth::user()->update(['telegram_chat_id' => null, 'notify_telegram' => false]);
                    Notification::make()->title('Telegram diputuskan.')->success()->send();
                }),

            // §15.1 — Kata laluan ialah laluan log masuk fallback (magic link kekal utama).
            Action::make('kata_laluan')
                ->label('Tetapkan Kata Laluan')
                ->icon('heroicon-o-key')
                ->authorize(fn () => Auth::check())
                ->modalDescription('Tetapkan kata laluan untuk log masuk tanpa pautan e-mel. Pautan log masuk tetap boleh digunakan.')
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
}

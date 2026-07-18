<?php

namespace App\Filament\Auth;

use App\Services\WhatsAppRecipientResolver;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Validation\ValidationException;

/**
 * Log masuk kedua-dua panel: satu medan menerima E-MEL ATAU NO. TELEFON.
 * Telefon = laluan utama untuk ahli masjid (admin selalunya tahu nombor,
 * bukan e-mel); e-mel kekal untuk superadmin & akaun lama. Magic link
 * (§15.1) kekal laluan utama; ini fallback kata laluan sahaja.
 */
class Login extends BaseLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('E-mel atau No. Telefon')
            ->required()
            ->autocomplete('username')
            ->autofocus()
            ->extraInputAttributes(['inputmode' => 'text']);
    }

    /**
     * Kesan sama ada input ialah nombor telefon (hanya digit/+/ruang/sengkang)
     * atau e-mel. Telefon dinormalkan (0→60) melalui resolver yang sama dengan
     * routing WhatsApp, jadi ia padan lajur users.phone_wa (E.164 tanpa '+').
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        $login = trim((string) ($data['login'] ?? ''));

        if (preg_match('/^[+0-9][0-9 \-]*$/', $login)) {
            $phone = app(WhatsAppRecipientResolver::class)->normalize($login);

            return [
                'phone_wa' => $phone ?? $login,
                'password' => $data['password'] ?? '',
            ];
        }

        return [
            'email' => mb_strtolower($login),
            'password' => $data['password'] ?? '',
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.login' => 'E-mel/telefon atau kata laluan tidak sah.',
        ]);
    }

    /**
     * Jadikan had kadar log masuk boleh dikonfigurasi (produksi kekal 5/min;
     * persekitaran e2e naikkan via DIWAN_LOGIN_RATE_LIMIT untuk elak flake
     * apabila banyak peranan log masuk berturut dari satu IP).
     */
    protected function rateLimit($maxAttempts, $decaySeconds = 60, $method = null, $component = null)
    {
        return parent::rateLimit(
            (int) config('diwan.login_rate_limit', $maxAttempts),
            $decaySeconds,
            $method,
            $component,
        );
    }
}

<?php

namespace App\Filament\Auth;

use App\Models\User;
use App\Services\PanelLandingResolver;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * Ganti LoginResponse lalai Filament (`redirect()->intended(Filament::getUrl())`).
 *
 * Lalai itu memakai panel SEMASA: sesiapa yang log masuk di /app/login mendarat dalam
 * tenant lalai panel `app`. Untuk superadmin, `getTenants()` memulangkan SEMUA masjid
 * aktif, jadi tenant lalai ialah masjid pertama platform — bukan /admin (§9.A).
 *
 * `intended()` DIKEKALKAN: deep-link yang menyebabkan pengalihan ke halaman log masuk
 * (cth /app/{slug}/rekod/123 semasa tamat sesi) tetap dihormati selepas log masuk.
 */
class PanelLandingLoginResponse implements LoginResponse
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $user = Filament::auth()->user();

        return redirect()->intended(
            $user instanceof User
                ? app(PanelLandingResolver::class)->urlFor($user, withOnboarding: false)
                : Filament::getUrl(),
        );
    }
}

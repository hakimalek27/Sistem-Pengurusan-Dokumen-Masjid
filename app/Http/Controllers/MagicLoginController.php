<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\MagicLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * §9.A / §15.1 — Magic link auto-login.
 * GET  /masuk/{token} = interstisial (elak bot pratonton WhatsApp/Telegram
 *      membakar token sekali-guna pada GET). Token TIDAK diguna di sini.
 * POST /masuk/{token} = guna token → log masuk → deep-link ke sasaran (intended)
 *      jika ada, atau pendaratan ikut peranan.
 */
class MagicLoginController extends Controller
{
    public function show(Request $request, string $token, MagicLinkService $magic)
    {
        $peek = $magic->peek($token);

        if (! $peek) {
            return response()->view('auth.magic-invalid', [], 410);
        }

        $user = $peek['user'];
        $intended = $this->safeIntended($peek['token']->intended_url);

        // Sudah log masuk sebagai pengguna SAMA → terus tanpa guna token
        // (token kekal sah untuk klik seterusnya).
        if (Auth::check() && Auth::id() === $user->id) {
            return redirect($intended ?? $this->landingUrl($user));
        }

        // Interstisial: hanya POST (butang "Teruskan" / auto-submit JS) guna token.
        return response()->view('auth.magic-continue', [
            'token' => $token,
            'name' => $user->name,
        ]);
    }

    public function consume(Request $request, string $token, MagicLinkService $magic)
    {
        $peek = $magic->peek($token);
        $intended = $peek ? $this->safeIntended($peek['token']->intended_url) : null;

        $user = $magic->consume($token, $request->ip());

        if (! $user) {
            return response()->view('auth.magic-invalid', [], 410);
        }

        Auth::guard('web')->login($user, remember: true);
        $request->session()->regenerate();

        // Fix bounce (§15.1): selaraskan password_hash_web dengan pengguna baharu
        // supaya Filament AuthenticateSession tidak paksa-logout kerana hash basi
        // daripada sesi pengguna lain yang masih aktif dalam pelayar yang sama.
        if ($user->getAuthPassword() !== null) {
            $request->session()->put('password_hash_web', $user->getAuthPassword());
        } else {
            $request->session()->forget('password_hash_web');
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return redirect($intended ?? $this->landingUrl($user));
    }

    /**
     * Hanya benarkan destinasi RELATIF dalam aplikasi ini (elak open redirect).
     * Mesti bermula '/', bukan '//' (protokol-relatif), tiada '\', dan dalam
     * senarai putih awalan panel/deep-link.
     */
    protected function safeIntended(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        if (! str_starts_with($url, '/') || str_starts_with($url, '//') || str_contains($url, '\\')) {
            return null;
        }

        foreach (['/app', '/admin', '/r'] as $prefix) {
            if ($url === $prefix || str_starts_with($url, $prefix.'/')) {
                return $url;
            }
        }

        return null;
    }

    /** Pendaratan §9.A: superadmin → /admin; 1 masjid → /app/{slug}; >1 → pemilih tenant. */
    protected function landingUrl(User $user): string
    {
        if ($user->is_superadmin) {
            return '/admin';
        }

        $mosques = $user->mosques()->where('status', 'aktif')->get();

        if ($mosques->count() === 1) {
            $mosque = $mosques->first();

            // §10 Aliran I — admin masjid yang belum selesai persediaan dibawa
            // terus ke wizard onboarding (auto-buka melalui ?mula=1).
            if (blank(data_get($mosque->settings, 'onboarding_done'))
                && $user->canIn($mosque, 'mosque.settings')) {
                return '/app/'.$mosque->slug.'/persediaan?mula=1';
            }

            return '/app/'.$mosque->slug;
        }

        return '/app';
    }
}

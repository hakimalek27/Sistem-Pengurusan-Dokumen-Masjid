<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\MagicLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// §9.A / §15.1 — /masuk/{token}: sahkan magic link → log masuk → pendaratan ikut peranan.
class MagicLoginController extends Controller
{
    public function __invoke(Request $request, string $token, MagicLinkService $magic): RedirectResponse
    {
        $user = $magic->consume($token, $request->ip());

        if (! $user) {
            abort(403, 'Pautan log masuk tidak sah atau telah tamat tempoh.');
        }

        Auth::guard('web')->login($user, remember: true);
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect($this->landingUrl($user));
    }

    /** Pendaratan §9.A: superadmin → /admin; 1 masjid → /app/{slug}; >1 → pemilih tenant. */
    protected function landingUrl(User $user): string
    {
        if ($user->is_superadmin) {
            return '/admin';
        }

        $mosques = $user->mosques()->where('status', 'aktif')->get();

        if ($mosques->count() === 1) {
            return '/app/'.$mosques->first()->slug;
        }

        return '/app';
    }
}

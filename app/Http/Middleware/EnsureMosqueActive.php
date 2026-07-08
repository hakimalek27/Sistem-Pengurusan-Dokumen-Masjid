<?php

namespace App\Http\Middleware;

use App\Enums\MosqueStatus;
use App\Models\Mosque;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// §15.1 / §10.M — Masjid bukan aktif → halaman gantung. Superadmin dikecualikan.
class EnsureMosqueActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();
        $user = Auth::user();

        if ($tenant instanceof Mosque && $tenant->status !== MosqueStatus::Aktif) {
            // Superadmin masih boleh masuk panel masjid digantung (§10.M).
            if ($user && $user->is_superadmin) {
                return $next($request);
            }

            abort(403, 'Akaun masjid ini '.$tenant->status->getLabel().' — hubungi platform.');
        }

        return $next($request);
    }
}

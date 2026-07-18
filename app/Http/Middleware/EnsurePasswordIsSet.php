<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fasa B / §15.1 — Pengguna yang log masuk melalui magic link tetapi belum
 * menetapkan kata laluan MESTI menetapkannya kali pertama sebelum meneruskan
 * (aliran: klik pautan surat → tetapkan kata laluan → baru boleh baca surat).
 * Dipasang pada authMiddleware kedua-dua panel; route password.first di luar
 * panel (tiada middleware ini) supaya tiada gelung.
 */
class EnsurePasswordIsSet
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->password === null) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('password.first');
        }

        return $next($request);
    }
}

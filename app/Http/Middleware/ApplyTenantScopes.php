<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * §15.2 — Corak rasmi Filament untuk memastikan konteks tenant tersedia bagi
 * SEMUA request panel tenant (termasuk AJAX Livewire). Global scope + auto-isi
 * mosque_id dikendalikan trait BelongsToMosque melalui Filament::getTenant();
 * middleware ini didaftar sebagai tenant-persistent supaya konteks itu kekal.
 */
class ApplyTenantScopes
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}

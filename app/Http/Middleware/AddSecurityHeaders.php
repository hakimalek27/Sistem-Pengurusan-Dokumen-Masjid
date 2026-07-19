<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', $response->headers->get('X-Frame-Options') ?: 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', $response->headers->get('X-Content-Type-Options') ?: 'nosniff');
        $response->headers->set('Referrer-Policy', $response->headers->get('Referrer-Policy') ?: 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', $response->headers->get('Permissions-Policy') ?: 'camera=(), microphone=(), geolocation=()');

        return $response;
    }
}

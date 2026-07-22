<?php

use App\Http\Middleware\AddSecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Percayai proxy hadapan (Cloudflare / nginx dalaman) supaya skema HTTPS
        // dan IP klien sebenar dikesan daripada header X-Forwarded-*. App container
        // hanya dicapai melalui nginx dalaman, jadi 'at: *' selamat di sini.
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_AWS_ELB
        );

        // Guest pada laluan bukan-panel (cth /r/{ulid}) → halaman log masuk magic link.
        $middleware->redirectGuestsTo(fn () => route('log-masuk'));
        $middleware->append(AddSecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            if ($requestId = $request->attributes->get('request_id')) {
                $response->headers->set('X-Request-ID', (string) $requestId);
            }

            return $response;
        });
    })->create();

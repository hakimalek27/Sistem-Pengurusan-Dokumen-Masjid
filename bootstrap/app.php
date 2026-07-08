<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Guest pada laluan bukan-panel (cth /r/{ulid}) → halaman log masuk magic link.
        $middleware->redirectGuestsTo(fn () => route('log-masuk'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

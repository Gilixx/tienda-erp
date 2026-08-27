<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Vercel (y cualquier proxy TLS) reenvia por http interno con
        // X-Forwarded-Proto: https. Confiar en el proxy para que el request
        // se detecte como seguro (URLs https, cookies secure, etc.).
        $middleware->trustProxies(at: '*');

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // Middleware global de seguridad (headers HTTP) — web y API.
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
        $middleware->api(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        // Register custom middleware aliases
        $middleware->alias([
            'service' => \App\Http\Middleware\CheckServiceAccess::class,
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'password.change' => \App\Http\Middleware\ForcePasswordChange::class,
        ]);

        // Redirect unauthenticated users to login
        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

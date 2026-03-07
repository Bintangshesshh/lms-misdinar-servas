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
        $trustedProxies = env('TRUSTED_PROXIES');
        if ($trustedProxies === null || trim((string) $trustedProxies) === '') {
            // Local default is permissive for convenience; production should set TRUSTED_PROXIES explicitly.
            $trustedProxies = env('APP_ENV') === 'local' ? '*' : ['127.0.0.1', '::1'];
        } elseif ($trustedProxies !== '*') {
            $trustedProxies = array_values(array_filter(array_map('trim', explode(',', (string) $trustedProxies))));
            if ($trustedProxies === []) {
                $trustedProxies = ['127.0.0.1', '::1'];
            }
        }

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
        $middleware->trustProxies(at: $trustedProxies);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

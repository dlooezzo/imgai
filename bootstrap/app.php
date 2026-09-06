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
        $middleware->trustProxies(at: '*');

        $middleware->prepend(\App\Http\Middleware\TrustProxies::class);

        $middleware->alias([
            'tool.auth' => \App\Http\Middleware\EnsureToolAuthenticated::class,
            'admin.auth' => \App\Http\Middleware\EnsureAdmin::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'tools/image-generator/*',
            'tools/video-generator/*',
            'tools/image-to-video/*',
            'profile/*',
            'auth/*',
            'webhooks/*',
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

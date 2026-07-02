<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind the Cloudflare tunnel / IIS, trust the forwarding proxy so
        // Laravel detects HTTPS and the correct client IP.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'system-role' => \App\Http\Middleware\EnsureSystemRole::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'password.changed' => \App\Http\Middleware\EnsurePasswordChanged::class,
        ]);

        // Audit every state-changing web request.
        $middleware->web(append: [
            \App\Http\Middleware\LogActivity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();

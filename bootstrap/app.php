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
    ->withMiddleware(function (Middleware $middleware) {
        // --- TAMBAHKAN BAGIAN INI ---
        // Ini memuat middleware 'web' yang penting, termasuk StartSession
        // dan middleware Inertia.
        $middleware->web(append: [
            App\Http\Middleware\HandleInertiaRequests::class,
        ]);
        // --- AKHIR DARI BAGIAN YANG DITAMBAHKAN ---

        // Alias Anda sudah benar, biarkan seperti ini.
        $middleware->alias([
            'role' => App\Http\Middleware\CheckRole::class,
            'guest' => App\Http\Middleware\RedirectIfAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
    })->create();

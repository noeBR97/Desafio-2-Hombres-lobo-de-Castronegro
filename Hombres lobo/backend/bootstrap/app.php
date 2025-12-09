<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AdminOnly;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
    // middleware globales de tu Kernel
    $middleware->append([
        \Illuminate\Http\Middleware\TrustHosts::class,
        \Illuminate\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Http\Middleware\ValidatePostSize::class,
        \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ]);

    // grupos de middleware
    $middleware->group('web', [
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ]);

    $middleware->group('api', [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ]);

    // middleware de ruta (lo que antes estaba en $routeMiddleware)
    $middleware->alias([
        'auth'     => \App\Http\Middleware\Authenticate::class,
        'abilities'=> \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
        'ability'  => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        'guest'    => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'signed'   => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
    ]);
})
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Alias de middlewares de ruta
        $middleware->alias([
            'admin' => AdminOnly::class,
        ]);

    })
    ->withExceptions(function ($exceptions) {
        //
    })->create();

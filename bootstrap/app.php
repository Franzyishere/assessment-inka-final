<?php

use App\Http\Middleware\EnsureUserHasRole;
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
        // Ngrok and production reverse proxies terminate HTTPS before the
        // request reaches Laravel. Trust their forwarded scheme so generated
        // asset URLs remain HTTPS and are not blocked as mixed content.
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [\App\Http\Middleware\EnsureAssessmentInvitation::class]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['otp']);
    })->create();

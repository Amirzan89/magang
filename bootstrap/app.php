<?php
// use App\Http\Middleware\Authenticate;
// use App\Http\Middleware\Authorization;
use App\Http\Middleware\DecryptResponse;
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
        $middleware->validateCsrfTokens(except: [
            '/verify/create/*',
        ]);
        $middleware->alias([
            // 'auth' => Authenticate::class,
            // 'authorized' => Authorization::class,
        ]);
        $middleware->prepend(DecryptResponse::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

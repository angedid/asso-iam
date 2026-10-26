<?php

use App\Http\Middleware\CatchAccessTokenMiddleware;
use App\Http\Middleware\SanitizeAuthorizeRequestMiddleware;
use App\Http\Middleware\SuperAdminMiddleware;
use App\Http\Middleware\UserVerifierMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Passport\Http\Middleware\CheckToken;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(UserVerifierMiddleware::class);
        $middleware->append(CatchAccessTokenMiddleware::class);
        //$middleware->append(SanitizeAuthorizeRequestMiddleware::class);
        $middleware->alias([
            'check-user' => UserVerifierMiddleware::class,
            'super-admins-auth' => SuperAdminMiddleware::class,
            'rabbitmq.client' => CheckToken::class,
            'android.client' => CheckToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

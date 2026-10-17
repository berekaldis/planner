<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

require_once __DIR__ . '/../app/Helpers/helpers.php';

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\AutoScheduleRunner::class,
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'telegram/webhook',
            'telegram/run-automation',
            'api/cron/run',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

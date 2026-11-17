<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

require_once __DIR__ . '/../app/Helpers/helpers.php';

// Ensure required Laravel storage directories exist
$storageDirs = [
    dirname(__DIR__) . '/storage/framework/views',
    dirname(__DIR__) . '/storage/framework/cache/data',
    dirname(__DIR__) . '/storage/framework/sessions',
    dirname(__DIR__) . '/storage/logs',
];
foreach ($storageDirs as $sDir) {
    if (!is_dir($sDir)) {
        @mkdir($sDir, 0775, true);
    }
}

// Auto-heal: Invalidate stale config cache if database config or .env were updated
try {
    $configCache = dirname(__DIR__) . '/bootstrap/cache/config.php';
    $envFile = dirname(__DIR__) . '/.env';
    $dbConfigFile = dirname(__DIR__) . '/config/database.php';
    if (file_exists($configCache)) {
        $cTime = filemtime($configCache);
        if ((file_exists($envFile) && filemtime($envFile) > $cTime) ||
            (file_exists($dbConfigFile) && filemtime($dbConfigFile) > $cTime)) {
            @unlink($configCache);
        }
    }
} catch (\Throwable $e) {}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [
            \App\Http\Middleware\AutoScheduleRunner::class,
            \App\Http\Middleware\EnsurePasswordIsChanged::class,
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'telegram/webhook',
            'telegram/webhook/*',
            'telegram/webhook.php',
            'telegram/run-automation',
            'api/cron/run',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

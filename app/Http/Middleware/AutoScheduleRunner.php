<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AutoScheduleRunner
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     * Guarantees zero latency/slowdown for end-users while automating Telegram reminders.
     */
    public function terminate(Request $request, Response $response): void
    {
        try {
            // Run at most once every 60 seconds
            $lockKey = 'kaldis_auto_schedule_lock';
            if (Cache::add($lockKey, time(), 50)) {
                Artisan::call('schedule:run');
            }
        } catch (\Throwable $e) {
            Log::error('AutoScheduleRunner error: ' . $e->getMessage());
        }
    }
}

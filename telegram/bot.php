<?php
/**
 * Telegram Bot CLI Long-Polling Runner
 * Kaldis Coffee PLC
 *
 * Usage: php telegram/bot.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

exit(\Illuminate\Support\Facades\Artisan::call('telegram:poll', [], new \Symfony\Component\Console\Output\ConsoleOutput()));

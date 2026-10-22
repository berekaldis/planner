<?php
/**
 * Kaldis Coffee PLC — Automated Telegram Notification & Compliance Cron Runner
 * 
 * Can be executed via:
 * 1. CLI: php api/cron.php [--type=weekly|monthly|all] [--force]
 * 2. HTTP: GET /Planner/api/cron.php?action=run&type=all[&key=kaldis_secret]
 */

// Allow CLI execution or HTTP execution with appropriate headers
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
}

// 1. Bootstrap Laravel Kernel
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
$bootstrapPath = __DIR__ . '/../bootstrap/app.php';

if (!file_exists($autoloadPath) || !file_exists($bootstrapPath)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Autoload or bootstrap file not found.']);
    exit(1);
}

require_once $autoloadPath;
$app = require_once $bootstrapPath;

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 2. Parse arguments & options
$type = 'all';
$force = false;

if (php_sapi_name() === 'cli') {
    $options = getopt('', ['type:', 'force']);
    $type = $options['type'] ?? 'all';
    $force = isset($options['force']);
} else {
    $type = $_GET['type'] ?? ($_GET['action'] ?? 'all');
    $force = isset($_GET['force']);
    
    // Optional secret key check if configured
    $configuredKey = config('kaldis.cron_key', env('CRON_KEY', ''));
    if (!empty($configuredKey)) {
        $suppliedKey = $_GET['key'] ?? ($_SERVER['HTTP_X_CRON_KEY'] ?? '');
        if ($suppliedKey !== $configuredKey) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized cron key.']);
            exit(1);
        }
    }
}

$et = function_exists('get_ethiopian_period') ? get_ethiopian_period() : [
    'year' => '2019 E.C.',
    'month' => 'Meskerem',
    'week' => 1,
    'day' => (int)date('j'),
];

$year = $et['year'];
$month = $et['month'];
$week = $et['week'];
$day = $et['day'] ?? (int)date('j');

$results = [];

// 3. Weekly Automation
if ($type === 'weekly' || $type === 'all') {
    $dayOfWeek = (int)date('N'); // 1 = Monday, 7 = Sunday
    $currentTime = date('H:i');

    // Run weekly reminder if Monday morning before 12:30 (6:30 LT) or forced
    if ($force || ($dayOfWeek === 1 && $currentTime < '12:30')) {
        \Illuminate\Support\Facades\Artisan::call('kaldis:weekly-reminder', [
            '--year' => $year,
            '--month' => $month,
            '--week' => $week,
        ]);
        $results['weekly_reminder'] = trim(\Illuminate\Support\Facades\Artisan::output());
    }

    // Run weekly deadline check if Monday after 12:30 (6:30 LT cutoff) or forced
    // Evaluates On-Time, Late, and Missing reports; GM executive summary dispatches at or after 15:00 (9:00 LT)
    if ($force || ($dayOfWeek === 1 && $currentTime >= '12:30')) {
        $target = ($currentTime >= '15:00' || $force) ? 'all' : 'heads';
        \Illuminate\Support\Facades\Artisan::call('kaldis:deadline-check', [
            '--year' => $year,
            '--month' => $month,
            '--week' => $week,
            '--target' => $target,
            '--force' => $force,
        ]);
        $results['weekly_deadline_check'] = trim(\Illuminate\Support\Facades\Artisan::output());
    }
}

// 4. Monthly Automation
if ($type === 'monthly' || $type === 'all') {
    // Days 1-4: Routine reminders
    if ($force || ($day >= 1 && $day <= 4)) {
        \Illuminate\Support\Facades\Artisan::call('kaldis:monthly-reminder', [
            '--type' => 'reminder',
            '--year' => $year,
            '--month' => $month,
        ]);
        $results['monthly_reminder'] = trim(\Illuminate\Support\Facades\Artisan::output());
    }

    // Day 5: Cutoff check & GM Executive summary
    if ($force || $day >= 5) {
        \Illuminate\Support\Facades\Artisan::call('kaldis:monthly-reminder', [
            '--type' => 'cutoff',
            '--year' => $year,
            '--month' => $month,
        ]);
        $results['monthly_cutoff_check'] = trim(\Illuminate\Support\Facades\Artisan::output());
    }
}

$outputData = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'period' => [
        'year' => $year,
        'month' => $month,
        'week' => $week,
        'day' => $day,
    ],
    'type' => $type,
    'results' => $results,
];

if (php_sapi_name() === 'cli') {
    echo "====================================================\n";
    echo "Kaldis Coffee PLC — Automated Telegram Cron Executed\n";
    echo "Period: {$month} {$year} (Week {$week}, Day {$day})\n";
    echo "====================================================\n";
    foreach ($results as $k => $msg) {
        echo "[$k]: $msg\n";
    }
    echo "Done.\n";
} else {
    echo json_encode($outputData, JSON_PRETTY_PRINT);
}

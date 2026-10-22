<?php
/**
 * Kaldis Coffee PLC
 * Strategic Planning & Performance Management System
 * Master Configuration
 */

// Auto-load .env file if present
if (file_exists(__DIR__ . '/../.env')) {
    $envContent = file_get_contents(__DIR__ . '/../.env');
    foreach (explode("\n", $envContent) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v, " \t\n\r\0\x0B\"'");
        if (!isset($_ENV[$k])) {
            $_ENV[$k] = $v;
            putenv("{$k}={$v}");
        }
    }
}

return [
    // Database Configuration (Reads .env, falls back to defaults)
    'db_host' => getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '127.0.0.1'),
    'db_port' => (int)(getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? 3306)),
    'db_name' => getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? 'kaldis_performance'),
    'db_user' => getenv('DB_USERNAME') ?: ($_ENV['DB_USERNAME'] ?? 'root'),
    'db_pass' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : ($_ENV['DB_PASSWORD'] ?? ''),
    'db_charset' => 'utf8mb4',

    // App Information
    'app_name' => 'Kaldis Strategic Planning & Performance Management System',
    'app_short_name' => 'Kaldis PMS',
    'company_name' => 'Kaldis Coffee PLC',
    'app_version' => '1.0.0',
    'base_url' => '/Planner', // Adjust if running in root or subdirectory

    // Localization & Calendar
    'timezone' => 'Africa/Addis_Ababa',
    'default_calendar' => 'EC', // 'EC' (Ethiopian Calendar) or 'GC' (Gregorian)
    'current_planning_year' => 'AUTO',
    'current_planning_month' => 'AUTO',

    // Telegram Bot Integration
    'telegram_bot_token' => '',
    'telegram_bot_username' => 'KaldisPlannerBot',
    'telegram_webhook_url' => '',

    // Weekly Reporting Deadlines
    'weekly_deadline_day' => 'Monday',
    'weekly_deadline_time' => '12:30', // 24-hour format

    // Monthly Plan Submission Deadlines (Ethiopian Calendar Day 26)
    'monthly_plan_deadline_day' => 26,
    'monthly_plan_deadline_time' => '17:00',
    'monthly_deadline_day' => 26,

    // Escalation Thresholds (Consecutive missed weeks)
    'escalation_rules' => [
        1 => 'Department Head Reminder',
        2 => 'HR & Operations Notification',
        3 => 'Senior Management / GM Escalation'
    ],

    // File Upload Limits
    'max_upload_size' => 10 * 1024 * 1024, // 10MB
    'allowed_upload_extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'],

    // Session Lifetime
    'session_lifetime' => 7200, // 2 hours
];

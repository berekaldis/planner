<?php
/**
 * Kaldis Coffee PLC
 * Strategic Planning & Performance Management System
 * Master Configuration
 */

return [
    // Database Configuration (Default local WampServer / cPanel settings)
    'db_host' => '127.0.0.1',
    'db_port' => 3307,
    'db_name' => 'kaldis_performance',
    'db_user' => 'root',
    'db_pass' => '',
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
    'current_planning_year' => '2019 E.C.',
    'current_planning_month' => 'Meskerem',

    // Telegram Bot Integration
    'telegram_bot_token' => '',
    'telegram_bot_username' => 'KaldisPlannerBot',
    'telegram_webhook_url' => '',

    // Weekly Reporting Deadlines
    'weekly_deadline_day' => 'Monday',
    'weekly_deadline_time' => '12:00', // 24-hour format

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

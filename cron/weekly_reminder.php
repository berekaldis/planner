<?php
/**
 * Cron: Weekly Report Reminders (Sunday & Monday Mornings)
 * Kaldis Coffee PLC
 *
 * cPanel Crontab Example:
 * 0 10 * * 0 php /path/to/Planner/cron/weekly_reminder.php  (Sunday 10 AM)
 * 0 9 * * 1 php /path/to/Planner/cron/weekly_reminder.php   (Monday 9 AM)
 * 0 11 * * 1 php /path/to/Planner/cron/weekly_reminder.php  (Monday 11 AM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/telegram.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';

$db = Database::getConnection();
$config = require __DIR__ . '/../config/config.php';
$telegram = new TelegramNotifier();

$period = get_ethiopian_period();
$year = $period['year'];
$month = $period['month'];
$week = $period['week'];

echo "Running Weekly Reminder Cron for {$month} {$year} (Week {$week})...\n";

// Find departments that have NOT submitted a report for this week
$stmt = $db->prepare("
    SELECT d.id as dept_id, d.department_name, d.department_code,
           u.id as user_id, u.full_name, u.telegram_chat_id
    FROM departments d
    JOIN users u ON d.head_user_id = u.id
    LEFT JOIN weekly_reports wr 
           ON d.id = wr.department_id 
          AND wr.year = ? 
          AND wr.month = ? 
          AND wr.week_number = ?
          AND wr.status = 'SUBMITTED'
    WHERE d.active = 1 AND wr.id IS NULL
");
$stmt->execute([$year, $month, $week]);
$pendingDepts = $stmt->fetchAll();

echo "Found " . count($pendingDepts) . " department(s) pending report submission.\n";

foreach ($pendingDepts as $dept) {
    $msg = "⏰ <b>REMINDER: Weekly Department Report Due!</b>\n\n"
         . "Dear {$dept['full_name']},\n"
         . "Your weekly performance report for <b>{$dept['department_name']} ({$dept['department_code']})</b> is due by <b>{$config['weekly_deadline_day']} at {$config['weekly_deadline_time']}</b>.\n\n"
         . "Please ensure all planned tasks are evaluated as DONE or NOT DONE (with explanation) and submit on the Kaldis portal.\n\n"
         . "Thank you,\nKaldis Coffee Planning & Management Office";

    // 1. Send Telegram if Chat ID exists
    if (!empty($dept['telegram_chat_id']) && $telegram->isConfigured()) {
        $res = $telegram->sendMessage($dept['telegram_chat_id'], $msg);
        echo "Sent Telegram to {$dept['full_name']} ({$dept['department_code']}): " . ($res['ok'] ? 'OK' : $res['description']) . "\n";
    }

    // 2. Insert In-App Notification
    $db->prepare("
        INSERT INTO notifications (user_id, department_id, title, message, notification_type, created_at)
        VALUES (?, ?, ?, ?, 'REMINDER', NOW())
    ")->execute([
        $dept['user_id'],
        $dept['dept_id'],
        "Weekly Report Due: {$config['weekly_deadline_day']} {$config['weekly_deadline_time']}",
        "Please complete and submit your weekly report for Week {$week}."
    ]);

    // 3. Audit Log
    audit_log(null, 'REMINDER_SENT', 'cron', "dept_{$dept['dept_id']}_w{$week}", null, [
        'recipient' => $dept['full_name'],
        'department' => $dept['department_code']
    ]);
}

echo "Weekly reminder cron completed successfully.\n";

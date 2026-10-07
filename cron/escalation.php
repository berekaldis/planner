<?php
/**
 * Cron / Service: Multi-Tier Escalation Engine for Missing Weekly Reports
 * Kaldis Coffee PLC
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/telegram.php';
require_once __DIR__ . '/../includes/audit.php';

require_once __DIR__ . '/../includes/functions.php';

$db = Database::getConnection();
$config = require __DIR__ . '/../config/config.php';
$telegram = new TelegramNotifier();

// Fetch system settings for escalation recipients
$settings = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_group = 'ESCALATION'")->fetchAll(PDO::FETCH_KEY_PAIR);
$hrRecipients = $settings['escalation_recipients_hr'] ?? 'hr@kaldiscoffee.com';
$gmRecipients = $settings['escalation_recipients_gm'] ?? 'gm@kaldiscoffee.com';

$period = get_ethiopian_period();
$year = $period['year'];
$month = $period['month'];

// Find all departments with MISSING status in latest week
$stmt = $db->prepare("
    SELECT s.*, d.department_name, d.department_code,
           u.id as head_id, u.full_name as head_name, u.telegram_chat_id as head_telegram
    FROM submissions s
    JOIN departments d ON s.department_id = d.id
    LEFT JOIN users u ON d.head_user_id = u.id
    WHERE s.status = 'MISSING' AND s.year = ? AND s.month = ?
");
$stmt->execute([$year, $month]);
$missingSubmissions = $stmt->fetchAll();

foreach ($missingSubmissions as $sub) {
    $missedWeeks = (int)$sub['consecutive_missed_count'];
    $escalationLevel = min(3, max(1, $missedWeeks));

    // Check if escalation was already logged for this period and level
    $checkEsc = $db->prepare("
        SELECT id FROM escalations 
        WHERE department_id = ? AND year = ? AND month = ? AND week_number = ? AND escalation_level = ?
    ");
    $checkEsc->execute([$sub['department_id'], $sub['year'], $sub['month'], $sub['week_number'], $escalationLevel]);
    if ($checkEsc->fetch()) {
        continue; // Already triggered
    }

    $recipientsSnapshot = '';
    $alertMessage = '';

    if ($escalationLevel === 1) {
        // Level 1: Warning to Department Head
        $recipientsSnapshot = "Dept Head: {$sub['head_name']}";
        $alertMessage = "⚠️ <b>WARNING: Weekly Report Overdue</b>\n\n"
                      . "Dear {$sub['head_name']},\n"
                      . "Your weekly department report for <b>{$sub['department_name']}</b> is MISSING for Week {$sub['week_number']}.\n"
                      . "Please log in and submit immediately to prevent administrative escalation.";

        if (!empty($sub['head_telegram']) && $telegram->isConfigured()) {
            $telegram->sendMessage($sub['head_telegram'], $alertMessage);
        }
    } elseif ($escalationLevel === 2) {
        // Level 2: HR & Operations Alert
        $recipientsSnapshot = "HR Recipients: {$hrRecipients}";
        $alertMessage = "🚨 <b>ESCALATION LEVEL 2: 2 Consecutive Missed Reports</b>\n\n"
                      . "Department: <b>{$sub['department_name']} ({$sub['department_code']})</b>\n"
                      . "Head: <b>{$sub['head_name']}</b>\n"
                      . "Has missed reporting for 2 consecutive weeks ({$sub['month']} {$sub['year']}).\n\n"
                      . "Action required by HR / Operations.";

        // In-app alert to HR users
        $hrUsers = $db->query("SELECT id FROM users WHERE role_id IN (SELECT id FROM roles WHERE name IN ('hr', 'super_admin'))")->fetchAll();
        foreach ($hrUsers as $hru) {
            $db->prepare("
                INSERT INTO notifications (user_id, department_id, title, message, notification_type, created_at)
                VALUES (?, ?, 'Level 2 Escalation: Overdue Reports', ?, 'ESCALATION', NOW())
            ")->execute([$hru['id'], $sub['department_id'], "Department {$sub['department_code']} missed 2 consecutive weeks."]);
        }
    } else {
        // Level 3: Executive GM Escalation
        $recipientsSnapshot = "GM / Senior Management: {$gmRecipients}";
        $alertMessage = "🛑 <b>HIGH PRIORITY ESCALATION LEVEL 3: 3+ Missed Reports</b>\n\n"
                      . "Department: <b>{$sub['department_name']} ({$sub['department_code']})</b>\n"
                      . "Head: <b>{$sub['head_name']}</b>\n"
                      . "Has failed to submit weekly reports for <b>{$missedWeeks} consecutive weeks</b>.\n\n"
                      . "Immediate executive intervention required.";

        // In-app alert to GM and Super Admin
        $gmUsers = $db->query("SELECT id FROM users WHERE role_id IN (SELECT id FROM roles WHERE name IN ('gm_management', 'super_admin'))")->fetchAll();
        foreach ($gmUsers as $gmu) {
            $db->prepare("
                INSERT INTO notifications (user_id, department_id, title, message, notification_type, created_at)
                VALUES (?, ?, 'Level 3 Executive Escalation: Severe Non-Reporting', ?, 'ESCALATION', NOW())
            ")->execute([$gmu['id'], $sub['department_id'], "Department {$sub['department_code']} has missed {$missedWeeks} consecutive weekly reports."]);
        }
    }

    // Record escalation
    $insEsc = $db->prepare("
        INSERT INTO escalations (
            department_id, year, month, week_number, missed_weeks_count,
            escalation_level, triggered_at, recipients_snapshot, channel, status
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, NOW(), ?, 'ALL', 'OPEN'
        )
    ");
    $insEsc->execute([
        $sub['department_id'], $sub['year'], $sub['month'], $sub['week_number'], $missedWeeks,
        $escalationLevel, $recipientsSnapshot
    ]);
    $escId = $db->lastInsertId();

    audit_log(null, 'ESCALATION_TRIGGERED', 'cron', (string)$escId, null, [
        'department' => $sub['department_code'],
        'level' => $escalationLevel,
        'missed_weeks' => $missedWeeks,
        'recipients' => $recipientsSnapshot
    ]);

    echo "Escalation Level {$escalationLevel} triggered for {$sub['department_code']}.\n";
}

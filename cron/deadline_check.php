<?php
/**
 * Cron: Submission Deadline Evaluation & Missing Status Trigger
 * Kaldis Coffee PLC
 *
 * Runs after Monday 12:00 PM (e.g. 12:05 PM) to detect missing submissions.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = Database::getConnection();
$config = require __DIR__ . '/../config/config.php';

$period = get_ethiopian_period();
$year = $period['year'];
$month = $period['month'];
$week = $period['week'];

echo "Running Deadline Check Cron for {$month} {$year} (Week {$week})...\n";

// Fetch all active departments
$departments = $db->query("SELECT id, department_name, department_code, head_user_id FROM departments WHERE active = 1")->fetchAll();

foreach ($departments as $dept) {
    // Check if report was submitted
    $repStmt = $db->prepare("
        SELECT id, status FROM weekly_reports 
        WHERE department_id = ? AND year = ? AND month = ? AND week_number = ? AND status = 'SUBMITTED'
    ");
    $repStmt->execute([$dept['id'], $year, $month, $week]);
    $isSubmitted = (bool)$repStmt->fetch();

    // Check existing record in submissions table
    $subStmt = $db->prepare("
        SELECT id, status, consecutive_missed_count FROM submissions 
        WHERE department_id = ? AND year = ? AND month = ? AND week_number = ?
    ");
    $subStmt->execute([$dept['id'], $year, $month, $week]);
    $existingSub = $subStmt->fetch();

    $deadlineAt = date('Y-m-d 12:00:00');

    if (!$isSubmitted) {
        $newMissedCount = ($existingSub ? (int)$existingSub['consecutive_missed_count'] : 0) + 1;
        $oldStatus = $existingSub['status'] ?? 'WAITING';

        if ($existingSub) {
            $db->prepare("
                UPDATE submissions SET
                    status = 'MISSING',
                    consecutive_missed_count = ?,
                    remarks = 'Deadline passed without report submission.',
                    updated_at = NOW()
                WHERE id = ?
            ")->execute([$newMissedCount, $existingSub['id']]);
            $subId = $existingSub['id'];
        } else {
            $insStmt = $db->prepare("
                INSERT INTO submissions (
                    department_id, year, month, week_number, deadline_at, status, consecutive_missed_count, remarks
                ) VALUES (?, ?, ?, ?, ?, 'MISSING', ?, 'Deadline passed without report submission.')
            ");
            $insStmt->execute([$dept['id'], $year, $month, $week, $deadlineAt, $newMissedCount]);
            $subId = $db->lastInsertId();
        }

        // Log to submission_status_logs
        $db->prepare("
            INSERT INTO submission_status_logs (
                submission_id, department_id, year, month, week_number, old_status, new_status, reason, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'MISSING', 'Automatic deadline check marked missing', NOW())
        ")->execute([$subId, $dept['id'], $year, $month, $week, $oldStatus]);

        audit_log(null, 'REPORT_MARKED_MISSING', 'cron', "dept_{$dept['id']}_w{$week}", [
            'status' => $oldStatus
        ], [
            'status' => 'MISSING',
            'consecutive_missed' => $newMissedCount
        ]);

        echo "Department {$dept['department_code']} marked MISSING (Consecutive missed: {$newMissedCount})\n";
    } else {
        echo "Department {$dept['department_code']} already submitted.\n";
    }
}

// Trigger escalation check
require_once __DIR__ . '/escalation.php';

echo "Deadline check finished.\n";

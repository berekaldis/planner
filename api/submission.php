<?php
/**
 * API: Weekly Report Submission Controller
 * Kaldis Coffee PLC
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../config/database.php';

if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!verify_csrf()) {
    echo json_encode(['success' => false, 'message' => 'CSRF verification failed']);
    exit;
}

$deptId = filter_input(INPUT_POST, 'department_id', FILTER_VALIDATE_INT);
$year = trim($_POST['year'] ?? '');
$month = trim($_POST['month'] ?? '');
$week = filter_input(INPUT_POST, 'week_number', FILTER_VALIDATE_INT);
$notes = trim($_POST['submission_notes'] ?? '');

if (!$deptId || empty($year) || empty($month) || !$week) {
    echo json_encode(['success' => false, 'message' => 'Missing period or department parameters.']);
    exit;
}

if (!Permissions::canAccessDepartment($deptId)) {
    echo json_encode(['success' => false, 'message' => 'Forbidden: Access denied to this department.']);
    exit;
}

$userId = Auth::id();
$db = Database::getConnection();

try {
    // 1. Fetch all planned tasks for this week
    $tasksStmt = $db->prepare("
        SELECT wt.id, wt.task_title, wtr.result, wtr.not_done_reason_id, wtr.not_done_explanation
        FROM weekly_tasks wt
        LEFT JOIN weekly_task_results wtr ON wt.id = wtr.weekly_task_id
        WHERE wt.department_id = ? AND wt.year = ? AND wt.month = ? AND wt.week_number = ?
    ");
    $tasksStmt->execute([$deptId, $year, $month, $week]);
    $tasks = $tasksStmt->fetchAll();

    $totalTasks = count($tasks);

    if ($totalTasks === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot submit: No planned tasks exist for this week. Please plan weekly tasks first.'
        ]);
        exit;
    }

    // 2. Validate that EVERY planned task has a result
    $unratedTasks = [];
    $doneCount = 0;
    $notDoneCount = 0;

    foreach ($tasks as $t) {
        if (empty($t['result'])) {
            $unratedTasks[] = $t['task_title'];
        } elseif ($t['result'] === 'DONE') {
            $doneCount++;
        } elseif ($t['result'] === 'NOT_DONE') {
            $notDoneCount++;
            // Check explanation requirement
            if (empty($t['not_done_reason_id']) || empty($t['not_done_explanation'])) {
                echo json_encode([
                    'success' => false,
                    'message' => "Task '{$t['task_title']}' is marked NOT DONE but is missing the mandatory reason or explanation."
                ]);
                exit;
            }
        }
    }

    if (!empty($unratedTasks)) {
        echo json_encode([
            'success' => false,
            'message' => "Cannot submit: " . count($unratedTasks) . " planned task(s) do not have a performance result recorded. All tasks must be marked DONE or NOT DONE.",
            'unrated_tasks' => $unratedTasks
        ]);
        exit;
    }

    // 3. Compute completion %
    $completionPct = round(($doneCount / $totalTasks) * 100, 2);

    // 4. Determine Deadline & Submission Status (ON_TIME vs LATE)
    // Check if deadline is in submissions table, or calculate
    $subStmt = $db->prepare("
        SELECT id, deadline_at, status 
        FROM submissions 
        WHERE department_id = ? AND year = ? AND month = ? AND week_number = ?
        LIMIT 1
    ");
    $subStmt->execute([$deptId, $year, $month, $week]);
    $existingSub = $subStmt->fetch();

    $now = date('Y-m-d H:i:s');
    $deadlineAt = $existingSub['deadline_at'] ?? date('Y-m-d 12:00:00', strtotime('next Monday'));

    $submissionStatus = (strtotime($now) <= strtotime($deadlineAt)) ? 'ON_TIME' : 'LATE';

    // 5. Save or update weekly_reports table
    $reportStmt = $db->prepare("
        INSERT INTO weekly_reports (
            department_id, year, month, week_number, status, total_tasks, done_tasks, not_done_tasks,
            completion_percentage, submitted_by, submitted_at, submission_status, notes
        ) VALUES (
            :dept_id, :year, :month, :week, 'SUBMITTED', :total, :done, :not_done,
            :pct, :user_id, NOW(), :sub_status, :notes
        ) ON DUPLICATE KEY UPDATE
            status = 'SUBMITTED',
            total_tasks = :total,
            done_tasks = :done,
            not_done_tasks = :not_done,
            completion_percentage = :pct,
            submitted_by = :user_id,
            submitted_at = NOW(),
            submission_status = :sub_status,
            notes = :notes,
            updated_at = NOW()
    ");
    $reportStmt->execute([
        ':dept_id' => $deptId,
        ':year' => $year,
        ':month' => $month,
        ':week' => $week,
        ':total' => $totalTasks,
        ':done' => $doneCount,
        ':not_done' => $notDoneCount,
        ':pct' => $completionPct,
        ':user_id' => $userId,
        ':sub_status' => $submissionStatus,
        ':notes' => $notes
    ]);

    // 6. Update submissions tracker table
    if ($existingSub) {
        $oldSubStatus = $existingSub['status'];
        $updSubStmt = $db->prepare("
            UPDATE submissions SET
                status = :status,
                submitted_at = NOW(),
                submission_channel = 'WEB',
                submitted_by = :user_id,
                consecutive_missed_count = 0,
                remarks = :remarks,
                updated_at = NOW()
            WHERE id = :id
        ");
        $updSubStmt->execute([
            ':status' => $submissionStatus,
            ':user_id' => $userId,
            ':remarks' => "Submitted via web portal. {$doneCount}/{$totalTasks} tasks done ({$completionPct}%)",
            ':id' => $existingSub['id']
        ]);

        // Log status change
        $db->prepare("
            INSERT INTO submission_status_logs (
                submission_id, department_id, year, month, week_number, old_status, new_status, changed_by, reason, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ")->execute([$existingSub['id'], $deptId, $year, $month, $week, $oldSubStatus, $submissionStatus, $userId, 'Report submitted by department head']);
    } else {
        $insSubStmt = $db->prepare("
            INSERT INTO submissions (
                department_id, year, month, week_number, deadline_at, submitted_at,
                status, submission_channel, submitted_by, consecutive_missed_count, remarks
            ) VALUES (
                ?, ?, ?, ?, ?, NOW(),
                ?, 'WEB', ?, 0, ?
            )
        ");
        $insSubStmt->execute([
            $deptId, $year, $month, $week, $deadlineAt,
            $submissionStatus, $userId, "Submitted via web portal. {$doneCount}/{$totalTasks} tasks done ({$completionPct}%)"
        ]);
    }

    // 7. Audit log
    audit_log($userId, 'WEEKLY_REPORT_SUBMITTED', 'reports', "dept_{$deptId}_w{$week}", null, [
        'year' => $year,
        'month' => $month,
        'week' => $week,
        'total_tasks' => $totalTasks,
        'done' => $doneCount,
        'not_done' => $notDoneCount,
        'completion_percentage' => $completionPct,
        'submission_status' => $submissionStatus
    ]);

    echo json_encode([
        'success' => true,
        'message' => "Weekly performance report submitted successfully! Status: {$submissionStatus} ({$completionPct}% complete)",
        'completion_percentage' => $completionPct,
        'total_tasks' => $totalTasks,
        'done_tasks' => $doneCount,
        'not_done_tasks' => $notDoneCount,
        'submission_status' => $submissionStatus
    ]);
} catch (Exception $e) {
    error_log("Submission API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

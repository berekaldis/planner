<?php
/**
 * API: Weekly Performance Execution, Task Results, Achievements & Challenges
 * Kaldis Coffee PLC
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../config/database.php';

if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Please log in']);
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

$action = trim($_POST['action'] ?? '');
$db = Database::getConnection();
$userId = Auth::id();

try {
    // -------------------------------------------------------------
    // ACTION 1: SAVE TASK RESULT (DONE / NOT_DONE)
    // -------------------------------------------------------------
    if ($action === 'save_task_result') {
        $taskId = filter_input(INPUT_POST, 'weekly_task_id', FILTER_VALIDATE_INT);
        $result = strtoupper(trim($_POST['result'] ?? ''));

        if (!$taskId || !in_array($result, ['DONE', 'NOT_DONE'], true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid task ID or result value. Must be DONE or NOT DONE.']);
            exit;
        }

        // Fetch task info to verify department permissions
        $taskStmt = $db->prepare("SELECT * FROM weekly_tasks WHERE id = ?");
        $taskStmt->execute([$taskId]);
        $task = $taskStmt->fetch();

        if (!$task) {
            echo json_encode(['success' => false, 'message' => 'Weekly task not found']);
            exit;
        }

        if (!Permissions::canAccessDepartment($task['department_id'])) {
            echo json_encode(['success' => false, 'message' => 'Forbidden: You cannot modify tasks for this department']);
            exit;
        }

        $reasonId = null;
        $explanation = null;
        $nextAction = null;
        $expectedCompletion = null;

        if ($result === 'NOT_DONE') {
            $reasonId = filter_input(INPUT_POST, 'not_done_reason_id', FILTER_VALIDATE_INT);
            $explanation = trim($_POST['not_done_explanation'] ?? '');
            $nextAction = trim($_POST['next_action'] ?? '');
            $expectedCompletion = trim($_POST['expected_completion_date'] ?? '') ?: null;

            if (!$reasonId || empty($explanation)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'For NOT DONE tasks, both Reason Category and Explanation are strictly mandatory.'
                ]);
                exit;
            }
        }

        // Check existing result
        $chkStmt = $db->prepare("SELECT id, result FROM weekly_task_results WHERE weekly_task_id = ?");
        $chkStmt->execute([$taskId]);
        $existing = $chkStmt->fetch();

        if ($existing) {
            $updateStmt = $db->prepare("
                UPDATE weekly_task_results SET
                    result = :result,
                    completed_at = NOW(),
                    completed_by = :user_id,
                    not_done_reason_id = :reason_id,
                    not_done_explanation = :explanation,
                    next_action = :next_action,
                    expected_completion_date = :expected_date,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $updateStmt->execute([
                ':result' => $result,
                ':user_id' => $userId,
                ':reason_id' => $reasonId,
                ':explanation' => $explanation,
                ':next_action' => $nextAction,
                ':expected_date' => $expectedCompletion,
                ':id' => $existing['id']
            ]);

            audit_log($userId, 'TASK_RESULT_UPDATED', 'performance', (string)$existing['id'], [
                'task_id' => $taskId,
                'old_result' => $existing['result']
            ], [
                'new_result' => $result,
                'reason_id' => $reasonId,
                'explanation' => $explanation
            ]);
        } else {
            $insertStmt = $db->prepare("
                INSERT INTO weekly_task_results (
                    weekly_task_id, department_id, year, month, week_number,
                    result, completed_at, completed_by,
                    not_done_reason_id, not_done_explanation, next_action, expected_completion_date
                ) VALUES (
                    :task_id, :dept_id, :year, :month, :week,
                    :result, NOW(), :user_id,
                    :reason_id, :explanation, :next_action, :expected_date
                )
            ");
            $insertStmt->execute([
                ':task_id' => $taskId,
                ':dept_id' => $task['department_id'],
                ':year' => $task['year'],
                ':month' => $task['month'],
                ':week' => $task['week_number'],
                ':result' => $result,
                ':user_id' => $userId,
                ':reason_id' => $reasonId,
                ':explanation' => $explanation,
                ':next_action' => $nextAction,
                ':expected_date' => $expectedCompletion
            ]);
            $resId = $db->lastInsertId();

            audit_log($userId, 'TASK_RESULT_RECORDED', 'performance', (string)$resId, null, [
                'task_id' => $taskId,
                'result' => $result,
                'reason_id' => $reasonId,
                'explanation' => $explanation
            ]);
        }

        echo json_encode([
            'success' => true,
            'message' => "Task marked as {$result}",
            'result' => $result
        ]);
        exit;
    }

    // -------------------------------------------------------------
    // ACTION 2: ADD WEEKLY ACHIEVEMENT
    // -------------------------------------------------------------
    elseif ($action === 'add_achievement') {
        $deptId = filter_input(INPUT_POST, 'department_id', FILTER_VALIDATE_INT);
        $year = trim($_POST['year'] ?? '');
        $month = trim($_POST['month'] ?? '');
        $week = filter_input(INPUT_POST, 'week_number', FILTER_VALIDATE_INT);
        $text = trim($_POST['achievement_text'] ?? '');

        if (!$deptId || empty($year) || empty($month) || !$week || empty($text)) {
            echo json_encode(['success' => false, 'message' => 'Achievement description is required.']);
            exit;
        }

        if (!Permissions::canAccessDepartment($deptId)) {
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit;
        }

        $stmt = $db->prepare("
            INSERT INTO weekly_achievements (
                department_id, year, month, week_number, achievement_text, created_by, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, NOW()
            )
        ");
        $stmt->execute([$deptId, $year, $month, $week, $text, $userId]);
        $achId = $db->lastInsertId();

        audit_log($userId, 'ACHIEVEMENT_ADDED', 'achievements', (string)$achId, null, [
            'department_id' => $deptId,
            'achievement' => $text
        ]);

        echo json_encode(['success' => true, 'message' => 'Achievement added successfully', 'id' => $achId]);
        exit;
    }

    // -------------------------------------------------------------
    // ACTION 3: DELETE ACHIEVEMENT
    // -------------------------------------------------------------
    elseif ($action === 'delete_achievement') {
        $id = filter_input(INPUT_POST, 'achievement_id', FILTER_VALIDATE_INT);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            exit;
        }

        $achStmt = $db->prepare("SELECT department_id FROM weekly_achievements WHERE id = ?");
        $achStmt->execute([$id]);
        $ach = $achStmt->fetch();

        if ($ach && Permissions::canAccessDepartment($ach['department_id'])) {
            $db->prepare("DELETE FROM weekly_achievements WHERE id = ?")->execute([$id]);
            audit_log($userId, 'ACHIEVEMENT_DELETED', 'achievements', (string)$id);
            echo json_encode(['success' => true, 'message' => 'Achievement deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Not found or forbidden']);
        }
        exit;
    }

    // -------------------------------------------------------------
    // ACTION 4: ADD WEEKLY CHALLENGE
    // -------------------------------------------------------------
    elseif ($action === 'add_challenge') {
        $deptId = filter_input(INPUT_POST, 'department_id', FILTER_VALIDATE_INT);
        $year = trim($_POST['year'] ?? '');
        $month = trim($_POST['month'] ?? '');
        $week = filter_input(INPUT_POST, 'week_number', FILTER_VALIDATE_INT);
        $text = trim($_POST['challenge_text'] ?? '');
        $relatedTaskId = filter_input(INPUT_POST, 'related_weekly_task_id', FILTER_VALIDATE_INT) ?: null;

        if (!$deptId || empty($year) || empty($month) || !$week || empty($text)) {
            echo json_encode(['success' => false, 'message' => 'Challenge description is required.']);
            exit;
        }

        if (!Permissions::canAccessDepartment($deptId)) {
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit;
        }

        $stmt = $db->prepare("
            INSERT INTO weekly_challenges (
                department_id, year, month, week_number, challenge_text, related_weekly_task_id, created_by, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, NOW()
            )
        ");
        $stmt->execute([$deptId, $year, $month, $week, $text, $relatedTaskId, $userId]);
        $chId = $db->lastInsertId();

        audit_log($userId, 'CHALLENGE_ADDED', 'challenges', (string)$chId, null, [
            'department_id' => $deptId,
            'challenge' => $text,
            'related_task_id' => $relatedTaskId
        ]);

        echo json_encode(['success' => true, 'message' => 'Challenge added successfully', 'id' => $chId]);
        exit;
    }

    // -------------------------------------------------------------
    // ACTION 5: DELETE CHALLENGE
    // -------------------------------------------------------------
    elseif ($action === 'delete_challenge') {
        $id = filter_input(INPUT_POST, 'challenge_id', FILTER_VALIDATE_INT);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            exit;
        }

        $chStmt = $db->prepare("SELECT department_id FROM weekly_challenges WHERE id = ?");
        $chStmt->execute([$id]);
        $ch = $chStmt->fetch();

        if ($ch && Permissions::canAccessDepartment($ch['department_id'])) {
            $db->prepare("DELETE FROM weekly_challenges WHERE id = ?")->execute([$id]);
            audit_log($userId, 'CHALLENGE_DELETED', 'challenges', (string)$id);
            echo json_encode(['success' => true, 'message' => 'Challenge deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Not found or forbidden']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
} catch (Exception $e) {
    error_log("Performance API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
}

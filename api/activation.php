<?php
/**
 * API: Monthly Strategy Activation Toggle & Multi-Department Activation
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

if (!Permissions::canActivateStrategy()) {
    echo json_encode(['success' => false, 'message' => 'Forbidden: Only General Manager and Super Admin can activate strategies']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check CSRF
if (!verify_csrf()) {
    echo json_encode(['success' => false, 'message' => 'CSRF verification failed']);
    exit;
}

$db = Database::getConnection();
$userId = Auth::id();

$year = trim($_POST['year'] ?? '');
$month = trim($_POST['month'] ?? '');
$active = strtoupper(trim($_POST['active'] ?? 'NO'));

if (!in_array($active, ['YES', 'NO'], true)) {
    $active = 'NO';
}

if (empty($year) || empty($month)) {
    echo json_encode(['success' => false, 'message' => 'Missing planning year or month']);
    exit;
}

// Check for bulk activation vs single activation
$goalIds = [];
if (!empty($_POST['goal_ids']) && is_array($_POST['goal_ids'])) {
    $goalIds = array_filter(array_map('intval', $_POST['goal_ids']));
} elseif (!empty($_POST['annual_goal_id'])) {
    $goalIds = [(int)$_POST['annual_goal_id']];
}

if (empty($goalIds)) {
    echo json_encode(['success' => false, 'message' => 'No annual goal(s) specified']);
    exit;
}

try {
    $db->beginTransaction();

    $upsertStmt = $db->prepare("
        INSERT INTO monthly_strategy_activations (
            annual_goal_id, department_id, year, month, active, activated_by, activated_at, updated_by, updated_at
        ) VALUES (
            :goal_id, :dept_id, :year, :month, :active, :user_id, NOW(), :user_id, NOW()
        )
        ON DUPLICATE KEY UPDATE 
            active = :active_upd,
            updated_by = :user_id_upd,
            updated_at = NOW()
    ");

    $ownerDeptsQuery = $db->prepare("
        SELECT DISTINCT d.id, d.department_name, d.department_code
        FROM departments d
        JOIN annual_goal_departments agd ON d.id = agd.department_id
        WHERE agd.annual_goal_id = :goal_id1
        UNION
        SELECT DISTINCT d.id, d.department_name, d.department_code
        FROM departments d
        JOIN annual_goals ag ON d.id = ag.responsible_department_id
        WHERE ag.id = :goal_id2
    ");

    $results = [];
    $totalDeptsActivated = 0;

    foreach ($goalIds as $gid) {
        $ownerDeptsQuery->execute([':goal_id1' => $gid, ':goal_id2' => $gid]);
        $ownerDepts = $ownerDeptsQuery->fetchAll(PDO::FETCH_ASSOC);

        // If goal has no explicit owner records, check passed department_id or fallback
        if (empty($ownerDepts)) {
            $fallbackDeptId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : 1;
            $ownerDepts = [['id' => $fallbackDeptId, 'department_code' => 'ALL', 'department_name' => 'All']];
        }

        $deptCodes = [];
        foreach ($ownerDepts as $dept) {
            $upsertStmt->execute([
                ':goal_id' => $gid,
                ':dept_id' => $dept['id'],
                ':year' => $year,
                ':month' => $month,
                ':active' => $active,
                ':user_id' => $userId,
                ':active_upd' => $active,
                ':user_id_upd' => $userId
            ]);
            $deptCodes[] = $dept['department_code'];
            $totalDeptsActivated++;
        }

        audit_log($userId, 'MONTHLY_STRATEGY_MULTI_ACTIVATION', 'activation', (string)$gid, null, [
            'annual_goal_id' => $gid,
            'year' => $year,
            'month' => $month,
            'active' => $active,
            'owner_departments' => $deptCodes
        ]);

        $results[] = [
            'goal_id' => $gid,
            'active' => $active,
            'owner_departments' => $deptCodes
        ];
    }

    $db->commit();

    $goalCount = count($goalIds);
    $statusText = $active === 'YES' ? 'TRUE (Active)' : 'FALSE (Inactive)';
    $msg = $goalCount === 1 
        ? "Strategy set to {$statusText} across all owner departments (" . implode(', ', $results[0]['owner_departments']) . ")."
        : "{$goalCount} strategies set to {$statusText} across {$totalDeptsActivated} department assignments.";

    echo json_encode([
        'success' => true,
        'message' => $msg,
        'active' => $active,
        'results' => $results
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Strategy activation API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

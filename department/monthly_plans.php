<?php
/**
 * Department Monthly Plans (Strategy & Operational Plans)
 * Kaldis Coffee PLC
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/csrf.php';

$user = Auth::user();
$db = Database::getConnection();
$userId = Auth::id();

$years = get_planning_years();
$months = get_ethiopian_months();
$allDepartments = $db->query("SELECT id, department_name, department_code FROM departments WHERE active = 1 ORDER BY department_name ASC")->fetchAll();

// Scoping: Department heads can only manage their own department
$canViewAll = Permissions::canViewAllDepartments();
$deptId = Permissions::getActiveDepartmentId(!empty($_GET['department_id']) ? (int)$_GET['department_id'] : null);

if (!$deptId) {
    $deptId = $allDepartments[0]['id'] ?? 1;
}

$selectedYear = $_GET['year'] ?? app_config('current_planning_year', '2019 E.C.');
$selectedMonth = $_GET['month'] ?? app_config('current_planning_month', 'Meskerem');
$selectedType = strtoupper(trim($_GET['type'] ?? 'ALL'));

// Handle Form Submissions (Create / Edit Monthly Plan)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_plan') {
        $planDeptId = (int)($_POST['department_id'] ?? $deptId);
        Permissions::requireDepartmentAccess($planDeptId);

        $planType = strtoupper(trim($_POST['plan_type'] ?? 'STRATEGY'));
        $title = trim($_POST['title'] ?? '');
        $monthlyTarget = trim($_POST['monthly_target'] ?? '');
        $dod = trim($_POST['definition_of_done'] ?? '');
        $targetPct = (int)($_POST['target_percentage'] ?? 100);
        $priority = strtoupper(trim($_POST['priority'] ?? 'MEDIUM'));
        $dependency = trim($_POST['dependency'] ?? '');
        $responsible = trim($_POST['responsible_person'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $year = trim($_POST['year'] ?? $selectedYear);
        $month = trim($_POST['month'] ?? $selectedMonth);

        $annualGoalId = null;

        if ($planType === 'STRATEGY') {
            $annualGoalId = filter_input(INPUT_POST, 'annual_goal_id', FILTER_VALIDATE_INT);
            if (!$annualGoalId) {
                set_flash('danger', 'Strategy Plans must be linked to an active Annual Goal.');
                redirect("/department/monthly_plans.php?year={$year}&month={$month}&department_id={$planDeptId}");
            }

            // Verify this annual goal is ACTIVE for this month by GM
            $checkActStmt = $db->prepare("
                SELECT active FROM monthly_strategy_activations
                WHERE annual_goal_id = ? AND (department_id = ? OR department_id IS NULL) AND year = ? AND month = ?
            ");
            $checkActStmt->execute([$annualGoalId, $planDeptId, $year, $month]);
            $actStatus = $checkActStmt->fetchColumn();

            if ($actStatus !== 'YES') {
                set_flash('danger', 'Cannot create Strategy Plan: The selected Annual Strategy has NOT been activated by the GM for this month.');
                redirect("/department/monthly_plans.php?year={$year}&month={$month}&department_id={$planDeptId}");
            }
        }

        if (!empty($title) && !empty($monthlyTarget) && !empty($dod)) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO monthly_plans (
                        year, month, department_id, plan_type, annual_goal_id, title,
                        monthly_target, definition_of_done, target_percentage, priority,
                        dependency, responsible_person, notes, created_by, created_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
                    )
                ");
                $stmt->execute([
                    $year, $month, $planDeptId, $planType, $annualGoalId, $title,
                    $monthlyTarget, $dod, $targetPct, $priority,
                    $dependency, $responsible, $notes, $userId
                ]);
                $newPlanId = $db->lastInsertId();

                audit_log($userId, 'MONTHLY_PLAN_CREATED', 'monthly_plans', (string)$newPlanId, null, [
                    'department_id' => $planDeptId,
                    'type' => $planType,
                    'title' => $title,
                    'annual_goal_id' => $annualGoalId
                ]);

                // Notify GM of Monthly Plan Submission
                $deptInfo = $db->query("SELECT department_name, department_code FROM departments WHERE id = {$planDeptId}")->fetch(PDO::FETCH_ASSOC);
                $deptCode = $deptInfo['department_code'] ?? "Dept #{$planDeptId}";
                $deptName = $deptInfo['department_name'] ?? "Department";

                $notifTitle = "Monthly {$planType} Plan Submitted: {$deptCode}";
                $notifMessage = "{$deptName} has submitted a new Monthly {$planType} Plan for {$month} {$year}.\n"
                              . "• Title: {$title}\n"
                              . "• Target: {$monthlyTarget}\n"
                              . "• Priority: {$priority}\n"
                              . "• Definition of Done: {$dod}";

                notify_gm_submission($notifTitle, $notifMessage, $planDeptId);

                set_flash('success', "Monthly {$planType} Plan created successfully.");
                redirect("/department/monthly_plans.php?year={$year}&month={$month}&department_id={$planDeptId}");
            } catch (PDOException $e) {
                set_flash('danger', "Error creating plan: " . $e->getMessage());
            }
        } else {
            set_flash('danger', 'Please complete all required fields (Title, Monthly Target, Definition of Done).');
        }
    } elseif ($action === 'delete_plan') {
        $delId = (int)($_POST['plan_id'] ?? 0);
        $planDeptId = (int)($_POST['department_id'] ?? $deptId);
        Permissions::requireDepartmentAccess($planDeptId);

        if ($delId > 0) {
            $delStmt = $db->prepare("DELETE FROM monthly_plans WHERE id = ? AND department_id = ?");
            $delStmt->execute([$delId, $planDeptId]);
            audit_log($userId, 'MONTHLY_PLAN_DELETED', 'monthly_plans', (string)$delId);
            set_flash('success', 'Monthly plan removed.');
            redirect("/department/monthly_plans.php?year={$selectedYear}&month={$selectedMonth}&department_id={$planDeptId}");
        }
    } elseif ($action === 'submit_package') {
        $subDeptId = (int)($_POST['department_id'] ?? $deptId);
        Permissions::requireDepartmentAccess($subDeptId);
        $notes = trim($_POST['notes'] ?? '');
        $year = trim($_POST['year'] ?? $selectedYear);
        $month = trim($_POST['month'] ?? $selectedMonth);

        $chkPlans = $db->prepare("SELECT COUNT(*) FROM monthly_plans WHERE department_id = ? AND year = ? AND month = ?");
        $chkPlans->execute([$subDeptId, $year, $month]);
        $totalPlans = (int)$chkPlans->fetchColumn();

        if ($totalPlans === 0) {
            set_flash('danger', 'Cannot submit an empty plan package. Please create at least one monthly plan first.');
            redirect("/department/monthly_plans.php?year={$year}&month={$month}&department_id={$subDeptId}");
        }

        $deadlineDay = 26;
        try {
            $stD = $db->query("SELECT setting_value FROM system_settings WHERE setting_key IN ('monthly_plan_deadline_day', 'monthly_deadline_day') ORDER BY id ASC LIMIT 1");
            $vD = $stD ? $stD->fetchColumn() : null;
            if ($vD !== false && $vD !== null && is_numeric($vD)) {
                $deadlineDay = (int)$vD;
            }
        } catch (\Throwable $e) {}

        $currentEt = get_ethiopian_period();
        $status = 'ON_TIME';
        if ($currentEt['month'] === $month && $currentEt['year'] === $year && $currentEt['day'] > $deadlineDay) {
            $status = 'LATE';
        }

        $now = date('Y-m-d H:i:s');
        $upsert = $db->prepare("
            INSERT INTO monthly_submissions (department_id, year, month, submitted_at, status, submitted_by, remarks, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                submitted_at = VALUES(submitted_at),
                status = VALUES(status),
                submitted_by = VALUES(submitted_by),
                remarks = VALUES(remarks),
                updated_at = VALUES(updated_at)
        ");
        $upsert->execute([$subDeptId, $year, $month, $now, $status, $userId, $notes, $now, $now]);

        audit_log($userId, 'SUBMIT_MONTHLY_PACKAGE', 'monthly_submissions', "{$subDeptId}-{$year}-{$month}", null, [
            'status' => $status,
            'total_plans' => $totalPlans
        ]);

        $deptInfo = $db->query("SELECT department_name, department_code FROM departments WHERE id = {$subDeptId}")->fetch(PDO::FETCH_ASSOC);
        $statusEmoji = ($status === 'ON_TIME') ? '🟢 [ON-TIME]' : '🟡 [LATE]';
        $notifTitle = "{$statusEmoji} Monthly Plan Package: " . ($deptInfo['department_code'] ?? "Dept #{$subDeptId}");
        $notifMessage = ($deptInfo['department_name'] ?? 'Department') . " has submitted their Monthly Plan Package for {$month} {$year}.\n"
                      . "• Submission Timing: " . ($status === 'ON_TIME' ? 'ON-TIME SUBMISSION' : 'LATE SUBMISSION') . "\n"
                      . "• Total Plans: {$totalPlans}";
        if (!empty($notes)) {
            $notifMessage .= "\n• Department Remarks: {$notes}";
        }
        $voucherUrl = url("/department/monthly_voucher.php?department_id={$subDeptId}&year=" . urlencode($year) . "&month=" . urlencode($month));
        notify_gm_submission($notifTitle, $notifMessage, $subDeptId, $voucherUrl);

        set_flash('success', "Monthly Plan package submitted successfully to General Management! Timing Status: {$status}.");
        redirect("/department/monthly_plans.php?year={$year}&month={$month}&department_id={$subDeptId}");
    }
}

$pageTitle = 'Monthly Department Plans';
require_once __DIR__ . '/../includes/header.php';

// Fetch Monthly Submission Record
$subStmt = $db->prepare("SELECT * FROM monthly_submissions WHERE department_id = ? AND year = ? AND month = ?");
$subStmt->execute([$deptId, $selectedYear, $selectedMonth]);
$monthlySubmission = $subStmt->fetch();

// Fetch Active Annual Goals for Strategy Plan Selection (Multi-Department Ownership)
$activeGoalsStmt = $db->prepare("
    SELECT DISTINCT ag.id, ag.goal_code, ag.title, ag.definition_of_done, ag.annual_target,
           d.department_name as lead_dept_name, d.department_code as lead_dept_code
    FROM annual_goals ag
    LEFT JOIN departments d ON ag.responsible_department_id = d.id
    JOIN monthly_strategy_activations msa 
      ON ag.id = msa.annual_goal_id 
     AND (msa.department_id = :dept_id OR msa.department_id IS NULL)
     AND msa.year = :year 
     AND msa.month = :month 
     AND msa.active = 'YES'
    WHERE ag.year = :year
      AND (
        ag.responsible_department_id = :dept_id
        OR ag.responsible_department_id IS NULL
        OR EXISTS (
            SELECT 1 FROM annual_goal_departments agd 
            WHERE agd.annual_goal_id = ag.id AND agd.department_id = :dept_id
        )
        OR NOT EXISTS (
            SELECT 1 FROM annual_goal_departments agd WHERE agd.annual_goal_id = ag.id
        )
      )
    ORDER BY ag.goal_code ASC
");
$activeGoalsStmt->execute([
    ':dept_id' => $deptId,
    ':year' => $selectedYear,
    ':month' => $selectedMonth
]);
$activeAnnualGoals = $activeGoalsStmt->fetchAll();

// Fetch Monthly Plans for Selected Department, Year, Month
$planQuery = "
    SELECT mp.*, ag.goal_code, ag.title as annual_goal_title, d.department_name, d.department_code,
           (SELECT COUNT(*) FROM weekly_tasks wt WHERE wt.monthly_plan_id = mp.id) as weekly_task_count
    FROM monthly_plans mp
    JOIN departments d ON mp.department_id = d.id
    LEFT JOIN annual_goals ag ON mp.annual_goal_id = ag.id
    WHERE mp.department_id = :dept_id AND mp.year = :year AND mp.month = :month
";
$planParams = [
    ':dept_id' => $deptId,
    ':year' => $selectedYear,
    ':month' => $selectedMonth
];

if (in_array($selectedType, ['STRATEGY', 'OPERATIONAL'], true)) {
    $planQuery .= " AND mp.plan_type = :plan_type";
    $planParams[':plan_type'] = $selectedType;
}

$planQuery .= " ORDER BY mp.plan_type ASC, mp.created_at DESC";

$planStmt = $db->prepare($planQuery);
$planStmt->execute($planParams);
$monthlyPlans = $planStmt->fetchAll();

// Summary stats
$strategyCount = 0;
$operationalCount = 0;
foreach ($monthlyPlans as $p) {
    if ($p['plan_type'] === 'STRATEGY') $strategyCount++;
    else $operationalCount++;
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="mb-1 fw-bold text-dark"><i class="fas fa-calendar-alt text-warning me-2"></i> Monthly Department Plans</h3>
        <p class="text-muted mb-0">Manage both Strategy Plans (originating from GM-activated annual goals) and Operational Plans.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= url('/department/monthly_voucher.php?department_id=' . $deptId . '&year=' . urlencode($selectedYear) . '&month=' . urlencode($selectedMonth)) ?>" class="btn btn-outline-dark">
            <i class="fas fa-file-invoice me-1"></i> View Voucher
        </a>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#submitPackageModal">
            <i class="fas fa-paper-plane me-1"></i> Submit to GM
        </button>
        <button class="btn btn-kaldis" data-bs-toggle="modal" data-bs-target="#createMonthlyPlanModal">
            <i class="fas fa-plus-circle me-1"></i> Add Monthly Plan
        </button>
        <a href="<?= url('/department/weekly_plans.php?year=' . urlencode($selectedYear) . '&month=' . urlencode($selectedMonth) . '&department_id=' . $deptId) ?>" class="btn btn-kaldis-dark">
            <i class="fas fa-arrow-right me-1"></i> Go to Weekly Plans
        </a>
    </div>
</div>

<!-- Monthly Submission Status Banner -->
<?php if (!empty($monthlySubmission) && in_array($monthlySubmission['status'], ['ON_TIME', 'LATE', 'SUBMITTED'])): ?>
    <div class="alert alert-success border-0 shadow-sm d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
        <div>
            <i class="fas fa-check-circle me-2 text-success"></i>
            <strong>Plan Package Submitted:</strong> Monthly plans for <?= e($selectedMonth) ?> <?= e($selectedYear) ?> have been officially submitted to GM.
            <span class="badge <?= $monthlySubmission['status'] === 'ON_TIME' ? 'bg-success' : 'bg-warning text-dark' ?> ms-2">
                <?= e($monthlySubmission['status']) ?>
            </span>
            <?php if (!empty($monthlySubmission['approval_status'])): ?>
                <span class="badge <?= $monthlySubmission['approval_status'] === 'APPROVED' ? 'bg-success' : ($monthlySubmission['approval_status'] === 'REVISION_REQUESTED' ? 'bg-danger' : 'bg-secondary') ?> ms-1">
                    GM: <?= e($monthlySubmission['approval_status']) ?>
                </span>
            <?php endif; ?>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="small text-muted">
                Submitted on <?= date('M d, Y H:i', strtotime($monthlySubmission['submitted_at'])) ?>
            </div>
            <a href="<?= url('/department/monthly_voucher.php?department_id=' . $deptId . '&year=' . urlencode($selectedYear) . '&month=' . urlencode($selectedMonth)) ?>" class="btn btn-sm btn-dark">
                <i class="fas fa-file-invoice me-1"></i> View Voucher
            </a>
            <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#submitPackageModal">
                <i class="fas fa-redo me-1"></i> Re-Submit
            </button>
        </div>
    </div>
    <?php if (!empty($monthlySubmission['gm_feedback'])): ?>
        <div class="card border-primary mb-4 shadow-sm">
            <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                <span class="fw-bold small"><i class="fas fa-comment-dots me-1"></i> Executive Review Comments from General Manager</span>
                <span class="small opacity-75"><?= !empty($monthlySubmission['gm_feedback_at']) ? date('M d, Y H:i', strtotime($monthlySubmission['gm_feedback_at'])) : '' ?></span>
            </div>
            <div class="card-body bg-light py-2 px-3 small">
                <p class="mb-0 text-dark"><?= nl2br(e($monthlySubmission['gm_feedback'])) ?></p>
            </div>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="alert alert-warning border-0 shadow-sm d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
        <div>
            <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
            <strong>Monthly Plan Package Pending Submission:</strong> These plans have not yet been officially transmitted to General Management.
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/department/monthly_voucher.php?department_id=' . $deptId . '&year=' . urlencode($selectedYear) . '&month=' . urlencode($selectedMonth)) ?>" class="btn btn-sm btn-outline-dark">
                <i class="fas fa-file-invoice me-1"></i> Preview Voucher
            </a>
            <button type="button" class="btn btn-sm btn-kaldis" data-bs-toggle="modal" data-bs-target="#submitPackageModal">
                <i class="fas fa-paper-plane me-1"></i> Submit Package to GM
            </button>
        </div>
    </div>
<?php endif; ?>

<!-- Filters Bar -->
<div class="filter-bar mb-4">
    <form method="GET" action="monthly_plans.php" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">PLANNING YEAR</label>
            <select name="year" class="form-select">
                <?php foreach ($years as $y): ?>
                    <option value="<?= e($y) ?>" <?= $selectedYear === $y ? 'selected' : '' ?>><?= e($y) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">PLANNING MONTH</label>
            <select name="month" class="form-select">
                <?php foreach ($months as $m): ?>
                    <option value="<?= e($m) ?>" <?= $selectedMonth === $m ? 'selected' : '' ?>><?= e($m) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($canViewAll): ?>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">DEPARTMENT</label>
                <select name="department_id" class="form-select">
                    <?php foreach ($allDepartments as $d): ?>
                        <option value="<?= (int)$d['id'] ?>" <?= $deptId === (int)$d['id'] ? 'selected' : '' ?>>
                            <?= e($d['department_name']) ?> (<?= e($d['department_code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php else: ?>
            <input type="hidden" name="department_id" value="<?= (int)$deptId ?>">
        <?php endif; ?>

        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-kaldis w-100">
                <i class="fas fa-filter me-1"></i> Filter
            </button>
            <a href="monthly_plans.php" class="btn btn-outline-secondary" title="Reset">
                <i class="fas fa-undo"></i>
            </a>
        </div>
    </form>
</div>

<!-- Two Plan Types Navigation Tabs -->
<ul class="nav nav-pills mb-4 gap-2">
    <li class="nav-item">
        <a class="nav-link <?= $selectedType === 'ALL' ? 'active bg-dark' : 'bg-white border' ?>" 
           href="monthly_plans.php?year=<?= urlencode($selectedYear) ?>&month=<?= urlencode($selectedMonth) ?>&department_id=<?= $deptId ?>&type=ALL">
            <i class="fas fa-layer-group me-1"></i> ALL PLANS (<?= count($monthlyPlans) ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $selectedType === 'STRATEGY' ? 'active bg-primary' : 'bg-white border' ?>" 
           href="monthly_plans.php?year=<?= urlencode($selectedYear) ?>&month=<?= urlencode($selectedMonth) ?>&department_id=<?= $deptId ?>&type=STRATEGY">
            <i class="fas fa-chess-knight me-1"></i> STRATEGY PLANS (<?= $strategyCount ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $selectedType === 'OPERATIONAL' ? 'active bg-secondary' : 'bg-white border' ?>" 
           href="monthly_plans.php?year=<?= urlencode($selectedYear) ?>&month=<?= urlencode($selectedMonth) ?>&department_id=<?= $deptId ?>&type=OPERATIONAL">
            <i class="fas fa-cogs me-1"></i> OPERATIONAL PLANS (<?= $operationalCount ?>)
        </a>
    </li>
</ul>

<!-- Plans Table -->
<div class="card">
    <div class="card-header kaldis-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fs-6 fw-bold">
            <i class="fas fa-table-list me-2"></i> Monthly Plans for <?= e($selectedMonth) ?> <?= e($selectedYear) ?>
        </span>
        <div class="d-flex align-items-center gap-2">
            <div class="search-input-wrapper" style="width: 220px;">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control form-control-sm live-search-input live-table-search" placeholder="Filter plans..." data-target-table=".table-kaldis">
            </div>
            <span class="badge bg-warning text-dark">
                Showing: <?= count($monthlyPlans) ?> Plan(s)
            </span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-kaldis mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 120px;">Type</th>
                    <th style="width: 100px;">Goal ID</th>
                    <th>Monthly Activity & Target</th>
                    <th style="width: 250px;">Definition of Done (DoD)</th>
                    <th style="width: 100px;">Target %</th>
                    <th style="width: 100px;">Priority</th>
                    <th style="width: 130px;">Responsible</th>
                    <th style="width: 100px;" class="text-center">Tasks</th>
                    <th style="width: 80px;" class="text-end no-print">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($monthlyPlans)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fas fa-clipboard-list fa-3x mb-3 d-block opacity-25"></i>
                            No monthly plans found for this period. Click <strong>Add Monthly Plan</strong> above to create Strategy or Operational plans.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($monthlyPlans as $plan): ?>
                        <tr>
                            <td>
                                <?= render_plan_type_badge($plan['plan_type']) ?>
                            </td>
                            <td>
                                <?php if ($plan['plan_type'] === 'STRATEGY' && !empty($plan['goal_code'])): ?>
                                    <span class="badge bg-dark font-monospace"><?= e($plan['goal_code']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($plan['title']) ?></div>
                                <div class="small text-muted">
                                    <strong>Target:</strong> <?= e($plan['monthly_target']) ?>
                                </div>
                                <?php if ($plan['plan_type'] === 'STRATEGY' && !empty($plan['annual_goal_title'])): ?>
                                    <div class="small text-primary mt-1">
                                        <i class="fas fa-link me-1"></i> <em>Parent: <?= e($plan['annual_goal_title']) ?></em>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small text-secondary"><?= e($plan['definition_of_done']) ?></div>
                            </td>
                            <td>
                                <div class="progress" style="height: 18px;">
                                    <div class="progress-bar bg-warning text-dark fw-bold" role="progressbar" 
                                         style="width: <?= (int)$plan['target_percentage'] ?>%;">
                                        <?= (int)$plan['target_percentage'] ?>%
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?= render_priority_badge($plan['priority']) ?>
                            </td>
                            <td>
                                <small class="fw-semibold text-secondary"><?= e($plan['responsible_person'] ?: 'Department') ?></small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">
                                    <?= (int)$plan['weekly_task_count'] ?> Tasks
                                </span>
                            </td>
                            <td class="text-end no-print">
                                <form method="POST" action="monthly_plans.php" class="d-inline" onsubmit="return confirm('Delete this monthly plan? Associated weekly tasks will be removed.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_plan">
                                    <input type="hidden" name="plan_id" value="<?= (int)$plan['id'] ?>">
                                    <input type="hidden" name="department_id" value="<?= (int)$deptId ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Plan">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Create Monthly Plan -->
<div class="modal fade" id="createMonthlyPlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="monthly_plans.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create_plan">
                <input type="hidden" name="department_id" value="<?= (int)$deptId ?>">
                <input type="hidden" name="year" value="<?= e($selectedYear) ?>">
                <input type="hidden" name="month" value="<?= e($selectedMonth) ?>">

                <div class="modal-header kaldis-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i> Create Monthly Plan (<?= e($selectedMonth) ?> <?= e($selectedYear) ?>)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Step 1: Select Plan Type -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">PLAN TYPE <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="form-check p-3 border rounded bg-light">
                                    <input class="form-check-input" type="radio" name="plan_type" id="typeStrategy" value="STRATEGY" checked onchange="togglePlanTypeFields()">
                                    <label class="form-check-label fw-bold text-primary" for="typeStrategy">
                                        <i class="fas fa-chess-knight me-1"></i> STRATEGY PLAN
                                    </label>
                                    <div class="small text-muted mt-1">Originate from an Annual Strategy that has been activated by the GM for this month.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check p-3 border rounded bg-light">
                                    <input class="form-check-input" type="radio" name="plan_type" id="typeOperational" value="OPERATIONAL" onchange="togglePlanTypeFields()">
                                    <label class="form-check-label fw-bold text-secondary" for="typeOperational">
                                        <i class="fas fa-cogs me-1"></i> OPERATIONAL PLAN
                                    </label>
                                    <div class="small text-muted mt-1">Routine departmental activities (PM, maintenance, follow-ups, user support). No Annual Goal required.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Annual Goal Selector (For STRATEGY only) -->
                    <div id="strategyGoalSection" class="mb-4 p-3 border rounded bg-white shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label small fw-bold text-dark text-uppercase mb-0">
                                <i class="fas fa-chess-knight text-warning me-1"></i> SELECT GM-ACTIVATED ANNUAL STRATEGY <span class="text-danger">*</span>
                            </label>
                            <span class="badge bg-success-subtle text-success border border-success-subtle small">
                                <i class="fas fa-check-circle me-1"></i> GM Activated Only
                            </span>
                        </div>
                        <?php if (empty($activeAnnualGoals)): ?>
                            <div class="alert alert-light border-warning d-flex align-items-start gap-3 p-3 mb-0">
                                <i class="fas fa-circle-exclamation text-warning fs-4 mt-1"></i>
                                <div>
                                    <strong>No Active Annual Strategies Available:</strong> The General Manager has not activated any Annual Strategies for this department for <strong><?= e($selectedMonth) ?> <?= e($selectedYear) ?></strong>.
                                    <div class="mt-2 d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('typeOperational').click()">
                                            <i class="fas fa-cogs me-1"></i> Switch to Operational Plan
                                        </button>
                                        <?php if (Permissions::isGM() || Permissions::isSuperAdmin()): ?>
                                            <a href="<?= url('/admin/monthly_activation.php?month=' . urlencode($selectedMonth) . '&year=' . urlencode($selectedYear)) ?>" class="btn btn-sm btn-kaldis" target="_blank">
                                                <i class="fas fa-toggle-on me-1"></i> Open GM Activation Portal
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <select name="annual_goal_id" id="annualGoalSelect" class="form-select mb-3" data-no-search="true" onchange="handleGoalSelected(this)">
                                <option value="">-- Choose an Activated Annual Strategy --</option>
                                <?php foreach ($activeAnnualGoals as $ag): ?>
                                    <option value="<?= (int)$ag['id'] ?>" 
                                            data-code="<?= e($ag['goal_code']) ?>"
                                            data-title="<?= e($ag['title']) ?>" 
                                            data-dod="<?= e($ag['definition_of_done']) ?>"
                                            data-target="<?= e($ag['annual_target']) ?>">
                                        [<?= e($ag['goal_code']) ?>] <?= e($ag['title']) ?> &mdash; Target: <?= e($ag['annual_target']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <!-- Dynamic Strategy Info & One-Click Auto-Fill Box -->
                            <div id="selectedStrategyPreview" class="p-3 bg-light rounded border d-none">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span id="previewGoalCode" class="badge bg-dark font-monospace fs-6 px-2 py-1">G00</span>
                                        <span class="badge bg-warning text-dark" id="previewTarget">Target</span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-kaldis py-1 px-2" onclick="autoFillPlanFields()">
                                        <i class="fas fa-wand-magic-sparkles me-1"></i> Auto-fill Title &amp; DoD Below
                                    </button>
                                </div>
                                <div id="previewTitle" class="fw-bold text-dark mb-2"></div>
                                <div class="small text-muted p-2 bg-white rounded border">
                                    <strong>Annual Target Definition of Done:</strong>
                                    <div id="previewDod" class="text-secondary mt-1"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">MONTHLY ACTIVITY / PLAN TITLE <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="planTitle" class="form-control" placeholder="e.g. Develop customer registration module or Hardware PM" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">MONTHLY TARGET <span class="text-danger">*</span></label>
                            <input type="text" name="monthly_target" class="form-control" placeholder="e.g. Complete first 8 branches or 30% milestone" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">TARGET PERCENTAGE (%)</label>
                            <input type="number" name="target_percentage" class="form-control" value="100" min="1" max="100">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">MONTHLY DEFINITION OF DONE (DoD) <span class="text-danger">*</span></label>
                            <textarea name="definition_of_done" id="planDod" class="form-control" rows="2" placeholder="Specific criteria for this month's deliverable..." required></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">PRIORITY</label>
                            <select name="priority" class="form-select" data-no-search="true">
                                <option value="HIGH">HIGH</option>
                                <option value="MEDIUM" selected>MEDIUM</option>
                                <option value="LOW">LOW</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">RESPONSIBLE PERSON</label>
                            <input type="text" name="responsible_person" class="form-control" placeholder="e.g. Henok Girma">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">DEPENDENCIES</label>
                            <input type="text" name="dependency" class="form-control" placeholder="e.g. Operations data approval">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">NOTES</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-kaldis"><i class="fas fa-save me-1"></i> Save Monthly Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Submit Monthly Plan Package to GM -->
<div class="modal fade" id="submitPackageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="monthly_plans.php?year=<?= urlencode($selectedYear) ?>&month=<?= urlencode($selectedMonth) ?>&department_id=<?= (int)$deptId ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="submit_package">
                <input type="hidden" name="department_id" value="<?= (int)$deptId ?>">
                <input type="hidden" name="year" value="<?= e($selectedYear) ?>">
                <input type="hidden" name="month" value="<?= e($selectedMonth) ?>">

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark fs-6">
                        <i class="fas fa-paper-plane text-success me-2"></i> Submit Monthly Plan Package to GM
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 small mb-3 border-0">
                        <i class="fas fa-info-circle me-1"></i>
                        Submitting for <strong><?= e($monthlyPlans[0]['department_name'] ?? 'Department') ?></strong> &bull; <strong><?= e($selectedMonth) ?> <?= e($selectedYear) ?></strong>.
                        This will notify the General Manager and record your formal submission timing.
                    </div>

                    <div class="bg-light p-3 rounded-3 mb-3 border">
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Total Monthly Plans:</span>
                            <span class="fw-bold text-dark"><?= count($monthlyPlans) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Strategy Plans:</span>
                            <span class="badge bg-primary"><?= $strategyCount ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Operational Plans:</span>
                            <span class="badge bg-info text-dark"><?= $operationalCount ?></span>
                        </div>
                        <div class="d-flex justify-content-between small pt-1 border-top mt-1">
                            <span class="text-muted">Official Cutoff:</span>
                            <span class="text-secondary fw-semibold">Day 26 of Ethiopian Month (17:00)</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Department Remarks / Notes to GM (Optional)</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Add executive summary notes, key focus areas, or remarks for the General Manager..."></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success" <?= empty($monthlyPlans) ? 'disabled' : '' ?>>
                        <i class="fas fa-check-circle me-1"></i> Transmit Package to GM
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function togglePlanTypeFields() {
    const isStrategy = document.getElementById('typeStrategy').checked;
    const goalSection = document.getElementById('strategyGoalSection');
    const goalSelect = document.getElementById('annualGoalSelect');

    if (isStrategy) {
        goalSection.style.display = 'block';
        if (goalSelect) goalSelect.required = true;
    } else {
        goalSection.style.display = 'none';
        if (goalSelect) {
            goalSelect.required = false;
            goalSelect.value = '';
        }
        const previewBox = document.getElementById('selectedStrategyPreview');
        if (previewBox) previewBox.classList.add('d-none');
    }
}

function handleGoalSelected(select) {
    const opt = select.options[select.selectedIndex];
    const previewBox = document.getElementById('selectedStrategyPreview');
    if (!previewBox) return;

    if (opt && opt.value) {
        document.getElementById('previewGoalCode').textContent = opt.getAttribute('data-code') || '';
        document.getElementById('previewTitle').textContent = opt.getAttribute('data-title') || '';
        document.getElementById('previewTarget').textContent = 'Annual Target: ' + (opt.getAttribute('data-target') || '');
        document.getElementById('previewDod').textContent = opt.getAttribute('data-dod') || '';
        previewBox.classList.remove('d-none');
    } else {
        previewBox.classList.add('d-none');
    }
}

function autoFillPlanFields() {
    const select = document.getElementById('annualGoalSelect');
    if (!select) return;
    const opt = select.options[select.selectedIndex];
    if (opt && opt.value) {
        const titleField = document.getElementById('planTitle');
        const dodField = document.getElementById('planDod');
        const targetField = document.querySelector('input[name="monthly_target"]');

        titleField.value = opt.getAttribute('data-title') || '';
        dodField.value = opt.getAttribute('data-dod') || '';
        if (targetField && !targetField.value) {
            targetField.value = opt.getAttribute('data-target') || '';
        }

        // Visual flash feedback
        [titleField, dodField, targetField].forEach(el => {
            if (el) {
                el.classList.add('is-valid');
                setTimeout(() => el.classList.remove('is-valid'), 1800);
            }
        });

        if (typeof showToast === 'function') {
            showToast('Monthly plan fields auto-populated from strategy.', 'success');
        }
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

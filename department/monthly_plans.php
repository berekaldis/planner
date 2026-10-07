<?php
/**
 * Department Monthly Plans (Strategy & Operational Plans)
 * Kaldis Coffee PLC
 */

$pageTitle = 'Monthly Department Plans';
require_once __DIR__ . '/../includes/header.php';

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
$selectedMonth = $_GET['month'] ?? app_config('current_planning_month', 'Nehase');
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
                WHERE annual_goal_id = ? AND department_id = ? AND year = ? AND month = ?
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
    }
}

// Fetch Active Annual Goals for Strategy Plan Selection (Multi-Department Ownership)
$activeGoalsStmt = $db->prepare("
    SELECT DISTINCT ag.id, ag.goal_code, ag.title, ag.definition_of_done, ag.annual_target,
           d.department_name as lead_dept_name, d.department_code as lead_dept_code
    FROM annual_goals ag
    JOIN departments d ON ag.responsible_department_id = d.id
    JOIN monthly_strategy_activations msa 
      ON ag.id = msa.annual_goal_id 
     AND msa.department_id = :dept_id 
     AND msa.year = :year 
     AND msa.month = :month 
     AND msa.active = 'YES'
    WHERE ag.year = :year
      AND (
        ag.responsible_department_id = :dept_id
        OR EXISTS (
            SELECT 1 FROM annual_goal_departments agd 
            WHERE agd.annual_goal_id = ag.id AND agd.department_id = :dept_id
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
    <div class="d-flex gap-2">
        <button class="btn btn-kaldis" data-bs-toggle="modal" data-bs-target="#createMonthlyPlanModal">
            <i class="fas fa-plus-circle me-1"></i> Add Monthly Plan
        </button>
        <a href="<?= url('/department/weekly_plans.php?year=' . urlencode($selectedYear) . '&month=' . urlencode($selectedMonth) . '&department_id=' . $deptId) ?>" class="btn btn-kaldis-dark">
            <i class="fas fa-arrow-right me-1"></i> Go to Weekly Plans
        </a>
    </div>
</div>

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
                            <select name="annual_goal_id" id="annualGoalSelect" class="form-select mb-3" onchange="handleGoalSelected(this)">
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
                            <select name="priority" class="form-select">
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

<?php
/**
 * Department Weekly Plans & Tasks Breakdown
 * Kaldis Coffee PLC
 */

$pageTitle = 'Weekly Department Plans';
require_once __DIR__ . '/../includes/header.php';

$user = Auth::user();
$db = Database::getConnection();
$userId = Auth::id();

$years = get_planning_years();
$months = get_ethiopian_months();
$weeks = get_month_weeks();
$allDepartments = $db->query("SELECT id, department_name, department_code FROM departments WHERE active = 1 ORDER BY department_name ASC")->fetchAll();

$canViewAll = Permissions::canViewAllDepartments();
$deptId = Permissions::getActiveDepartmentId(!empty($_GET['department_id']) ? (int)$_GET['department_id'] : null);

if (!$deptId) {
    $deptId = $allDepartments[0]['id'] ?? 1;
}

$selectedYear = $_GET['year'] ?? app_config('current_planning_year', '2019 E.C.');
$selectedMonth = $_GET['month'] ?? app_config('current_planning_month', 'Nehase');
$selectedWeek = !empty($_GET['week']) ? (int)$_GET['week'] : 1;

// Handle Form Submissions (Create / Delete Task)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_task') {
        $taskDeptId = (int)($_POST['department_id'] ?? $deptId);
        Permissions::requireDepartmentAccess($taskDeptId);

        $monthlyPlanId = filter_input(INPUT_POST, 'monthly_plan_id', FILTER_VALIDATE_INT);
        $taskTitle = trim($_POST['task_title'] ?? '');
        $expectedResult = trim($_POST['expected_result'] ?? '');
        $responsible = trim($_POST['responsible_person'] ?? '');
        $priority = strtoupper(trim($_POST['priority'] ?? 'MEDIUM'));
        $dueDate = trim($_POST['due_date'] ?? '') ?: null;
        $dependency = trim($_POST['dependency'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $year = trim($_POST['year'] ?? $selectedYear);
        $month = trim($_POST['month'] ?? $selectedMonth);
        $week = (int)($_POST['week_number'] ?? $selectedWeek);

        if (!$monthlyPlanId || empty($taskTitle) || empty($expectedResult) || empty($responsible)) {
            set_flash('danger', 'Please complete all required fields (Parent Plan, Task, Expected Result, Responsible Person).');
        } else {
            try {
                // Fetch parent monthly plan to inherit plan_type and annual_goal_id
                $mpStmt = $db->prepare("SELECT plan_type, annual_goal_id FROM monthly_plans WHERE id = ? AND department_id = ?");
                $mpStmt->execute([$monthlyPlanId, $taskDeptId]);
                $parentPlan = $mpStmt->fetch();

                if (!$parentPlan) {
                    set_flash('danger', 'Selected monthly plan does not belong to your department.');
                    redirect("/department/weekly_plans.php?year={$year}&month={$month}&week={$week}&department_id={$taskDeptId}");
                }

                // Ensure weekly_plan master record exists
                $wpStmt = $db->prepare("
                    INSERT INTO weekly_plans (department_id, year, month, week_number, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE updated_at = NOW()
                ");
                $wpStmt->execute([$taskDeptId, $year, $month, $week, $userId]);

                // Fetch weekly_plan_id
                $wpGet = $db->prepare("SELECT id FROM weekly_plans WHERE department_id = ? AND year = ? AND month = ? AND week_number = ?");
                $wpGet->execute([$taskDeptId, $year, $month, $week]);
                $weeklyPlanId = $wpGet->fetchColumn();

                // Insert weekly_task
                $insTask = $db->prepare("
                    INSERT INTO weekly_tasks (
                        weekly_plan_id, monthly_plan_id, department_id, year, month, week_number,
                        plan_type, annual_goal_id, task_title, expected_result,
                        responsible_person, priority, due_date, dependency, notes, created_by, created_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, NOW()
                    )
                ");
                $insTask->execute([
                    $weeklyPlanId, $monthlyPlanId, $taskDeptId, $year, $month, $week,
                    $parentPlan['plan_type'], $parentPlan['annual_goal_id'], $taskTitle, $expectedResult,
                    $responsible, $priority, $dueDate, $dependency, $notes, $userId
                ]);
                $newTaskId = $db->lastInsertId();

                audit_log($userId, 'WEEKLY_TASK_CREATED', 'weekly_tasks', (string)$newTaskId, null, [
                    'department_id' => $taskDeptId,
                    'year' => $year,
                    'month' => $month,
                    'week' => $week,
                    'task' => $taskTitle,
                    'type' => $parentPlan['plan_type']
                ]);

                set_flash('success', "Weekly task '{$taskTitle}' planned for Week {$week}.");
                redirect("/department/weekly_plans.php?year={$year}&month={$month}&week={$week}&department_id={$taskDeptId}");
            } catch (PDOException $e) {
                set_flash('danger', "Database error creating weekly task: " . $e->getMessage());
            }
        }
    } elseif ($action === 'delete_task') {
        $delId = (int)($_POST['task_id'] ?? 0);
        $taskDeptId = (int)($_POST['department_id'] ?? $deptId);
        Permissions::requireDepartmentAccess($taskDeptId);

        if ($delId > 0) {
            $db->prepare("DELETE FROM weekly_tasks WHERE id = ? AND department_id = ?")->execute([$delId, $taskDeptId]);
            audit_log($userId, 'WEEKLY_TASK_DELETED', 'weekly_tasks', (string)$delId);
            set_flash('success', 'Weekly task removed.');
            redirect("/department/weekly_plans.php?year={$selectedYear}&month={$selectedMonth}&week={$selectedWeek}&department_id={$taskDeptId}");
        }
    }
}

// Fetch available monthly plans for this department & period (to assign tasks to)
$availablePlansStmt = $db->prepare("
    SELECT mp.id, mp.title, mp.plan_type, mp.monthly_target, ag.goal_code
    FROM monthly_plans mp
    LEFT JOIN annual_goals ag ON mp.annual_goal_id = ag.id
    WHERE mp.department_id = ? AND mp.year = ? AND mp.month = ?
    ORDER BY mp.plan_type ASC, mp.title ASC
");
$availablePlansStmt->execute([$deptId, $selectedYear, $selectedMonth]);
$availableMonthlyPlans = $availablePlansStmt->fetchAll();

// Fetch tasks for the selected week
$tasksStmt = $db->prepare("
    SELECT wt.*, mp.title as monthly_plan_title, ag.goal_code,
           wtr.result as performance_result, wtr.not_done_explanation, rc.name as reason_category_name
    FROM weekly_tasks wt
    JOIN monthly_plans mp ON wt.monthly_plan_id = mp.id
    LEFT JOIN annual_goals ag ON wt.annual_goal_id = ag.id
    LEFT JOIN weekly_task_results wtr ON wt.id = wtr.weekly_task_id
    LEFT JOIN reason_categories rc ON wtr.not_done_reason_id = rc.id
    WHERE wt.department_id = ? AND wt.year = ? AND wt.month = ? AND wt.week_number = ?
    ORDER BY wt.plan_type ASC, wt.created_at ASC
");
$tasksStmt->execute([$deptId, $selectedYear, $selectedMonth, $selectedWeek]);
$weeklyTasks = $tasksStmt->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="mb-1 fw-bold text-dark"><i class="fas fa-list-check text-warning me-2"></i> Weekly Department Plans</h3>
        <p class="text-muted mb-0">Break down your Monthly Strategy & Operational plans into weekly actionable tasks.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-kaldis" data-bs-toggle="modal" data-bs-target="#createTaskModal">
            <i class="fas fa-plus-circle me-1"></i> Add Weekly Task
        </button>
        <a href="<?= url('/department/performance.php?year=' . urlencode($selectedYear) . '&month=' . urlencode($selectedMonth) . '&week=' . $selectedWeek . '&department_id=' . $deptId) ?>" class="btn btn-success">
            <i class="fas fa-clipboard-check me-1"></i> Weekly Performance Execution &rarr;
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar mb-4">
    <form method="GET" action="weekly_plans.php" class="row g-3 align-items-end">
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

        <input type="hidden" name="week" value="<?= $selectedWeek ?>">

        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-kaldis w-100">
                <i class="fas fa-filter me-1"></i> Filter
            </button>
            <a href="weekly_plans.php" class="btn btn-outline-secondary" title="Reset">
                <i class="fas fa-undo"></i>
            </a>
        </div>
    </form>
</div>

<!-- Week Tabs (Week 1 to Week 5) -->
<ul class="nav nav-tabs mb-4">
    <?php foreach ($weeks as $wkNum => $wkLabel): ?>
        <li class="nav-item">
            <a class="nav-link fw-bold <?= $selectedWeek === $wkNum ? 'active text-warning bg-white border-bottom-0' : 'text-dark' ?>" 
               href="weekly_plans.php?year=<?= urlencode($selectedYear) ?>&month=<?= urlencode($selectedMonth) ?>&department_id=<?= $deptId ?>&week=<?= $wkNum ?>">
                <i class="far fa-calendar-check me-1"></i> <?= e($wkLabel) ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<!-- Weekly Tasks Table -->
<div class="card">
    <div class="card-header kaldis-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fs-6 fw-bold">
            <i class="fas fa-tasks me-2"></i> Planned Tasks for Week <?= $selectedWeek ?> &mdash; <?= e($selectedMonth) ?> <?= e($selectedYear) ?>
        </span>
        <div class="d-flex align-items-center gap-2">
            <div class="search-input-wrapper" style="width: 220px;">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control form-control-sm live-search-input live-table-search" placeholder="Filter tasks..." data-target-table=".table-kaldis">
            </div>
            <span class="badge bg-warning text-dark font-monospace">
                Total Tasks: <?= count($weeklyTasks) ?>
            </span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-kaldis mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 100px;">Type</th>
                    <th style="width: 100px;">Goal Code</th>
                    <th>Weekly Task Title & Expected Result</th>
                    <th style="width: 200px;">Parent Monthly Plan</th>
                    <th style="width: 130px;">Responsible</th>
                    <th style="width: 100px;">Due Date</th>
                    <th style="width: 90px;">Priority</th>
                    <th style="width: 120px;" class="text-center">Execution Status</th>
                    <th style="width: 70px;" class="text-end no-print">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($weeklyTasks)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fas fa-tasks fa-3x mb-3 d-block opacity-25"></i>
                            No tasks planned yet for Week <?= $selectedWeek ?>. Click <strong>Add Weekly Task</strong> to assign work from your monthly plans.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($weeklyTasks as $task): ?>
                        <tr>
                            <td>
                                <?= render_plan_type_badge($task['plan_type']) ?>
                            </td>
                            <td>
                                <?php if ($task['plan_type'] === 'STRATEGY' && !empty($task['goal_code'])): ?>
                                    <span class="badge bg-dark font-monospace"><?= e($task['goal_code']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($task['task_title']) ?></div>
                                <div class="small text-muted">
                                    <strong>Expected Result:</strong> <?= e($task['expected_result']) ?>
                                </div>
                                <?php if (!empty($task['dependency'])): ?>
                                    <small class="text-secondary d-block mt-1"><i class="fas fa-link me-1"></i> Dep: <?= e($task['dependency']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small fw-semibold text-secondary"><?= e($task['monthly_plan_title']) ?></div>
                            </td>
                            <td>
                                <small class="fw-semibold text-dark"><?= e($task['responsible_person']) ?></small>
                            </td>
                            <td>
                                <small class="text-muted"><?= $task['due_date'] ? date('M d', strtotime($task['due_date'])) : '&mdash;' ?></small>
                            </td>
                            <td>
                                <?= render_priority_badge($task['priority']) ?>
                            </td>
                            <td class="text-center">
                                <?= render_task_badge($task['performance_result']) ?>
                            </td>
                            <td class="text-end no-print">
                                <form method="POST" action="weekly_plans.php" class="d-inline" onsubmit="return confirm('Delete this weekly task?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_task">
                                    <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
                                    <input type="hidden" name="department_id" value="<?= (int)$deptId ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Task">
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

<!-- Modal: Create Weekly Task -->
<div class="modal fade" id="createTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="weekly_plans.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create_task">
                <input type="hidden" name="department_id" value="<?= (int)$deptId ?>">
                <input type="hidden" name="year" value="<?= e($selectedYear) ?>">
                <input type="hidden" name="month" value="<?= e($selectedMonth) ?>">
                <input type="hidden" name="week_number" value="<?= $selectedWeek ?>">

                <div class="modal-header kaldis-header">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-plus-circle me-2"></i> Add Task for Week <?= $selectedWeek ?> (<?= e($selectedMonth) ?> <?= e($selectedYear) ?>)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">SELECT PARENT MONTHLY PLAN <span class="text-danger">*</span></label>
                        <?php if (empty($availableMonthlyPlans)): ?>
                            <div class="alert alert-light border-warning d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between p-3 mb-0 gap-2">
                                <div>
                                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                    <strong>No Monthly Plans Created:</strong> You must create a Monthly Plan for <strong><?= e($selectedMonth) ?> <?= e($selectedYear) ?></strong> before scheduling weekly tasks.
                                </div>
                                <a href="monthly_plans.php?year=<?= urlencode($selectedYear) ?>&month=<?= urlencode($selectedMonth) ?>&department_id=<?= (int)$deptId ?>" class="btn btn-sm btn-kaldis text-nowrap">
                                    <i class="fas fa-plus me-1"></i> Create Monthly Plan &rarr;
                                </a>
                            </div>
                        <?php else: ?>
                            <select name="monthly_plan_id" id="monthlyPlanSelect" class="form-select" required onchange="autoFillTaskFromMonthlyPlan(this)">
                                <option value="">-- Choose Monthly Plan --</option>
                                <?php foreach ($availableMonthlyPlans as $mp): ?>
                                    <option value="<?= (int)$mp['id'] ?>"
                                            data-title="<?= htmlspecialchars($mp['title'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-result="<?= htmlspecialchars($mp['definition_of_done'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-responsible="<?= htmlspecialchars($mp['responsible_person'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                        [<?= e($mp['plan_type']) ?><?= $mp['goal_code'] ? ' - ' . e($mp['goal_code']) : '' ?>] 
                                        <?= e($mp['title']) ?> (Target: <?= e($mp['monthly_target']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Tip: Selecting a plan auto-fills suggested task title & result details!</small>
                        <?php endif; ?>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">TASK DESCRIPTION <span class="text-danger">*</span></label>
                            <input type="text" name="task_title" id="task_title" class="form-control" placeholder="e.g. Collect requirements from operations or Complete PM for Bole branch" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">EXPECTED RESULT <span class="text-danger">*</span></label>
                            <textarea name="expected_result" id="expected_result" class="form-control" rows="2" placeholder="Concrete, measurable deliverable expected by end of week..." required></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">RESPONSIBLE PERSON <span class="text-danger">*</span></label>
                            <input type="text" name="responsible_person" id="responsible_person" class="form-control" placeholder="Staff name..." required>
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
                            <label class="form-label small fw-bold">DUE DATE</label>
                            <input type="date" name="due_date" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">DEPENDENCY</label>
                            <input type="text" name="dependency" class="form-control" placeholder="e.g. Network cable delivery">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">NOTES</label>
                            <input type="text" name="notes" class="form-control" placeholder="Additional instructions...">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-kaldis" <?= empty($availableMonthlyPlans) ? 'disabled' : '' ?>>
                        <i class="fas fa-save me-1"></i> Save Task
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function autoFillTaskFromMonthlyPlan(selectEl) {
    const selectedOpt = selectEl.options[selectEl.selectedIndex];
    if (!selectedOpt || !selectedOpt.value) return;

    const title = selectedOpt.getAttribute('data-title') || '';
    const result = selectedOpt.getAttribute('data-result') || '';
    const responsible = selectedOpt.getAttribute('data-responsible') || '';

    const titleInput = document.getElementById('task_title');
    const resultInput = document.getElementById('expected_result');
    const respInput = document.getElementById('responsible_person');

    if (titleInput && !titleInput.value) titleInput.value = title;
    if (resultInput && !resultInput.value) resultInput.value = result;
    if (respInput && !respInput.value && responsible) respInput.value = responsible;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

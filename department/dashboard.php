<?php
/**
 * Department Head Dashboard & Workspace
 * Kaldis Coffee PLC
 */

$pageTitle = 'Department Head Workspace';
require_once __DIR__ . '/../includes/header.php';

$user = Auth::user();
$db = Database::getConnection();

$year = app_config('current_planning_year', '2019 E.C.');
$month = app_config('current_planning_month', 'Nehase');
$canViewAll = Permissions::canViewAllDepartments();
$allDepartments = $db->query("SELECT id, department_name, department_code FROM departments WHERE active = 1 ORDER BY department_name ASC")->fetchAll();
$deptId = Permissions::getActiveDepartmentId(!empty($_GET['department_id']) ? (int)$_GET['department_id'] : null);

if (!$deptId) {
    $deptId = $user['department_id'] ?? ($allDepartments[0]['id'] ?? 1);
}

// Department details
$deptStmt = $db->prepare("SELECT * FROM departments WHERE id = ?");
$deptStmt->execute([$deptId]);
$department = $deptStmt->fetch();

// Determine Current Planning Week (default to 1 or active week)
$currentWeek = !empty($_GET['week']) ? (int)$_GET['week'] : 1;

// 1. Department's Monthly Plans for this Month
$monthlyPlansStmt = $db->prepare("
    SELECT mp.*, ag.goal_code,
           (SELECT COUNT(*) FROM weekly_tasks wt WHERE wt.monthly_plan_id = mp.id) as task_count
    FROM monthly_plans mp
    LEFT JOIN annual_goals ag ON mp.annual_goal_id = ag.id
    WHERE mp.department_id = ? AND mp.year = ? AND mp.month = ?
    ORDER BY mp.plan_type ASC
");
$monthlyPlansStmt->execute([$deptId, $year, $month]);
$monthlyPlans = $monthlyPlansStmt->fetchAll();

$stratPlansCount = 0;
$opsPlansCount = 0;
foreach ($monthlyPlans as $mp) {
    if ($mp['plan_type'] === 'STRATEGY') $stratPlansCount++;
    else $opsPlansCount++;
}

// 2. Department's Weekly Tasks for Current Week
$tasksStmt = $db->prepare("
    SELECT wt.*, ag.goal_code, wtr.result, wtr.not_done_explanation, rc.name as reason_name
    FROM weekly_tasks wt
    LEFT JOIN annual_goals ag ON wt.annual_goal_id = ag.id
    LEFT JOIN weekly_task_results wtr ON wt.id = wtr.weekly_task_id
    LEFT JOIN reason_categories rc ON wtr.not_done_reason_id = rc.id
    WHERE wt.department_id = ? AND wt.year = ? AND wt.month = ? AND wt.week_number = ?
    ORDER BY wt.plan_type ASC, wt.id ASC
");
$tasksStmt->execute([$deptId, $year, $month, $currentWeek]);
$weekTasks = $tasksStmt->fetchAll();

$totTasks = count($weekTasks);
$doneTasks = 0;
$notDoneTasks = 0;
$unratedTasks = 0;

foreach ($weekTasks as $t) {
    if ($t['result'] === 'DONE') $doneTasks++;
    elseif ($t['result'] === 'NOT_DONE') $notDoneTasks++;
    else $unratedTasks++;
}
$completionPct = $totTasks > 0 ? round(($doneTasks / $totTasks) * 100, 1) : 0;

// 3. Weekly Report Submission Status
$repStmt = $db->prepare("
    SELECT * FROM weekly_reports 
    WHERE department_id = ? AND year = ? AND month = ? AND week_number = ?
");
$repStmt->execute([$deptId, $year, $month, $currentWeek]);
$weeklyReport = $repStmt->fetch();

// 4. Achievements & Challenges this week
$achStmt = $db->prepare("SELECT * FROM weekly_achievements WHERE department_id = ? AND year = ? AND month = ? AND week_number = ?");
$achStmt->execute([$deptId, $year, $month, $currentWeek]);
$achievements = $achStmt->fetchAll();

$chStmt = $db->prepare("SELECT * FROM weekly_challenges WHERE department_id = ? AND year = ? AND month = ? AND week_number = ?");
$chStmt->execute([$deptId, $year, $month, $currentWeek]);
$challenges = $chStmt->fetchAll();
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="mb-1 fw-bold text-dark">
            <i class="fas fa-building-user text-warning me-2"></i> <?= e($department['department_name'] ?? 'Department') ?> Workspace
        </h3>
        <p class="text-muted mb-0"><i class="fas fa-calendar-check text-success me-1"></i> Departmental Planning &amp; Execution Workspace</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?php if ($canViewAll): ?>
            <form method="GET" action="dashboard.php" class="d-flex align-items-center gap-1">
                <input type="hidden" name="week" value="<?= $currentWeek ?>">
                <select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($allDepartments as $ad): ?>
                        <option value="<?= (int)$ad['id'] ?>" <?= $deptId === (int)$ad['id'] ? 'selected' : '' ?>>
                            <?= e($ad['department_name']) ?> (<?= e($ad['department_code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
        <a href="<?= url('/department/performance.php?year=' . urlencode($year) . '&month=' . urlencode($month) . '&week=' . $currentWeek . '&department_id=' . $deptId) ?>" class="btn btn-success fw-bold btn-sm py-2 px-3">
            <i class="fas fa-clipboard-check me-1"></i> Weekly Performance Sheet &rarr;
        </a>
    </div>
</div>

<!-- Status Banner -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card stat-gold">
            <i class="fas fa-calendar-alt stat-icon text-warning"></i>
            <div class="stat-label">Monthly Plans</div>
            <div class="stat-value text-dark"><?= count($monthlyPlans) ?></div>
            <small class="text-muted"><?= $stratPlansCount ?> Strategy &bull; <?= $opsPlansCount ?> Operational</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-primary">
            <i class="fas fa-tasks stat-icon text-primary"></i>
            <div class="stat-label">Week <?= $currentWeek ?> Tasks</div>
            <div class="stat-value text-primary"><?= $totTasks ?></div>
            <small class="text-muted"><?= $unratedTasks ?> Awaiting evaluation</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-done">
            <i class="fas fa-check-circle stat-icon text-success"></i>
            <div class="stat-label">Tasks Completed</div>
            <div class="stat-value text-success"><?= $doneTasks ?> / <?= $totTasks ?></div>
            <small class="text-muted"><?= $completionPct ?>% Weekly completion</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-gold">
            <i class="fas fa-paper-plane stat-icon text-warning"></i>
            <div class="stat-label">Submission Status</div>
            <div class="stat-value text-dark" style="font-size: 1.25rem; margin-top: 0.5rem;">
                <?php if ($weeklyReport && $weeklyReport['status'] === 'SUBMITTED'): ?>
                    <?= render_submission_badge($weeklyReport['submission_status']) ?>
                <?php else: ?>
                    <span class="badge bg-warning text-dark"><i class="fas fa-pencil me-1"></i> IN DRAFT</span>
                <?php endif; ?>
            </div>
            <small class="text-muted">Due Mon 12:00 PM</small>
        </div>
    </div>
</div>

<!-- Quick Workflow Actions -->
<div class="card mb-4">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-3"><i class="fas fa-route me-2 text-warning"></i> Department Planning & Reporting Flow</h5>
        <div class="row g-3 text-center">
            <div class="col-md-3">
                <a href="<?= url('/department/monthly_plans.php?year=' . urlencode($year) . '&month=' . urlencode($month) . '&department_id=' . $deptId) ?>" 
                   class="p-3 border rounded d-block text-decoration-none text-dark bg-light hover-shadow">
                    <i class="fas fa-calendar-days fa-2x text-primary mb-2"></i>
                    <div class="fw-bold">1. Monthly Plans</div>
                    <small class="text-muted">Review Strategy & Ops plans</small>
                </a>
            </div>
            <div class="col-md-3">
                <a href="<?= url('/department/weekly_plans.php?year=' . urlencode($year) . '&month=' . urlencode($month) . '&week=' . $currentWeek . '&department_id=' . $deptId) ?>" 
                   class="p-3 border rounded d-block text-decoration-none text-dark bg-light hover-shadow">
                    <i class="fas fa-list-check fa-2x text-warning mb-2"></i>
                    <div class="fw-bold">2. Weekly Breakdown</div>
                    <small class="text-muted">Plan tasks for Week <?= $currentWeek ?></small>
                </a>
            </div>
            <div class="col-md-3">
                <a href="<?= url('/department/performance.php?year=' . urlencode($year) . '&month=' . urlencode($month) . '&week=' . $currentWeek . '&department_id=' . $deptId) ?>" 
                   class="p-3 border rounded d-block text-decoration-none text-dark bg-light hover-shadow">
                    <i class="fas fa-clipboard-check fa-2x text-success mb-2"></i>
                    <div class="fw-bold">3. Execute & Record</div>
                    <small class="text-muted">Mark DONE / NOT DONE</small>
                </a>
            </div>
            <div class="col-md-3">
                <a href="<?= url('/department/reports.php?department_id=' . $deptId) ?>" 
                   class="p-3 border rounded d-block text-decoration-none text-dark bg-light hover-shadow">
                    <i class="fas fa-file-invoice fa-2x text-secondary mb-2"></i>
                    <div class="fw-bold">4. Submission Archive</div>
                    <small class="text-muted">Past department reports</small>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Department Analytics Charts -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header kaldis-header d-flex justify-content-between align-items-center">
                <span class="fs-6 fw-bold"><i class="fas fa-chart-pie me-2"></i> Week <?= $currentWeek ?> Execution Scorecard</span>
                <span class="badge bg-warning text-dark font-monospace"><?= $completionPct ?>% Completed</span>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-sm-6 text-center">
                        <div style="height: 160px; position: relative;">
                            <canvas id="deptWeekExecChart"></canvas>
                        </div>
                    </div>
                    <div class="col-sm-6 mt-3 mt-sm-0">
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span><i class="fas fa-check-circle text-success me-1"></i> DONE Tasks</span>
                                <span class="badge bg-success font-monospace fs-6"><?= $doneTasks ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span><i class="fas fa-times-circle text-danger me-1"></i> NOT DONE Tasks</span>
                                <span class="badge bg-danger font-monospace fs-6"><?= $notDoneTasks ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span><i class="fas fa-hourglass-half text-warning me-1"></i> Pending / In Progress</span>
                                <span class="badge bg-warning text-dark font-monospace fs-6"><?= $unratedTasks ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header kaldis-header d-flex justify-content-between align-items-center">
                <span class="fs-6 fw-bold"><i class="fas fa-layer-group me-2"></i> Monthly Planning Portfolio (<?= e($month) ?>)</span>
                <span class="badge bg-light text-dark font-monospace"><?= count($monthlyPlans) ?> Plans</span>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-sm-6 text-center">
                        <div style="height: 160px; position: relative;">
                            <canvas id="deptPlanMixChart"></canvas>
                        </div>
                    </div>
                    <div class="col-sm-6 mt-3 mt-sm-0">
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span><i class="fas fa-chess-knight text-primary me-1"></i> Strategy Plans</span>
                                <span class="badge bg-primary font-monospace fs-6"><?= $stratPlansCount ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span><i class="fas fa-cogs text-secondary me-1"></i> Operational Plans</span>
                                <span class="badge bg-secondary font-monospace fs-6"><?= $opsPlansCount ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span><i class="fas fa-calendar text-muted me-1"></i> Total Scheduled</span>
                                <span class="badge bg-dark font-monospace fs-6"><?= count($monthlyPlans) ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Current Week Planned Tasks Summary -->
<div class="card mb-4">
    <div class="card-header kaldis-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fs-6 fw-bold">
            <i class="fas fa-tasks me-2"></i> Planned Tasks for Week <?= $currentWeek ?> (<?= e($month) ?> <?= e($year) ?>)
        </span>
        <div class="d-flex align-items-center gap-2">
            <div class="search-input-wrapper" style="width: 200px;">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control form-control-sm live-search-input live-table-search" placeholder="Filter tasks..." data-target-table=".table-kaldis">
            </div>
            <a href="<?= url('/department/performance.php?year=' . urlencode($year) . '&month=' . urlencode($month) . '&week=' . $currentWeek . '&department_id=' . $deptId) ?>" 
               class="btn btn-sm btn-light text-dark fw-bold">
                Open Execution Sheet &rarr;
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-kaldis mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 100px;">Type</th>
                    <th>Weekly Task & Deliverable</th>
                    <th style="width: 140px;">Responsible</th>
                    <th style="width: 130px;" class="text-center">Status</th>
                    <th>Why Not Done / Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($weekTasks)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            No tasks scheduled for Week <?= $currentWeek ?>. 
                            <a href="<?= url('/department/weekly_plans.php?year=' . urlencode($year) . '&month=' . urlencode($month) . '&week=' . $currentWeek . '&department_id=' . $deptId) ?>" class="fw-bold">
                                Add Weekly Tasks &rarr;
                            </a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($weekTasks as $t): ?>
                        <tr>
                            <td><?= render_plan_type_badge($t['plan_type']) ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($t['task_title']) ?></div>
                                <small class="text-muted"><?= e($t['expected_result']) ?></small>
                            </td>
                            <td><small class="fw-semibold text-secondary"><?= e($t['responsible_person']) ?></small></td>
                            <td class="text-center"><?= render_task_badge($t['result']) ?></td>
                            <td>
                                <?php if ($t['result'] === 'NOT_DONE'): ?>
                                    <span class="badge bg-danger mb-1"><?= e($t['reason_name'] ?? 'Unspecified') ?></span>
                                    <small class="d-block text-muted"><?= e($t['not_done_explanation']) ?></small>
                                <?php elseif ($t['result'] === 'DONE'): ?>
                                    <small class="text-success"><i class="fas fa-check me-1"></i> Completed</small>
                                <?php else: ?>
                                    <span class="text-muted small">Pending review</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Department Week Execution Donut
    const execCtx = document.getElementById('deptWeekExecChart');
    if (execCtx && typeof Chart !== 'undefined') {
        const hasTaskData = <?= ($doneTasks + $notDoneTasks + $unratedTasks) > 0 ? 'true' : 'false' ?>;
        new Chart(execCtx, {
            type: 'doughnut',
            data: hasTaskData ? {
                labels: ['DONE', 'NOT DONE', 'PENDING'],
                datasets: [{
                    data: [<?= $doneTasks ?>, <?= $notDoneTasks ?>, <?= $unratedTasks ?>],
                    backgroundColor: ['#198754', '#dc3545', '#ffc107'],
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            } : {
                labels: ['No Tasks Scheduled'],
                datasets: [{
                    data: [1],
                    backgroundColor: ['#e2e8f0'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                cutout: '70%'
            }
        });
    }

    // 2. Department Plan Mix Pie
    const mixCtx = document.getElementById('deptPlanMixChart');
    if (mixCtx && typeof Chart !== 'undefined') {
        const hasPlanData = <?= ($stratPlansCount + $opsPlansCount) > 0 ? 'true' : 'false' ?>;
        new Chart(mixCtx, {
            type: 'doughnut',
            data: hasPlanData ? {
                labels: ['STRATEGY', 'OPERATIONAL'],
                datasets: [{
                    data: [<?= $stratPlansCount ?>, <?= $opsPlansCount ?>],
                    backgroundColor: ['#0d6efd', '#6c757d'],
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            } : {
                labels: ['No Plans Created'],
                datasets: [{
                    data: [1],
                    backgroundColor: ['#e2e8f0'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                cutout: '70%'
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

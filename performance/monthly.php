<?php
/**
 * Monthly Performance Aggregation & Department Scorecard
 * Kaldis Coffee PLC
 */

$pageTitle = 'Monthly Performance Scorecard';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getConnection();

$years = get_planning_years();
$months = get_ethiopian_months();
$departments = $db->query("SELECT id, department_name, department_code FROM departments WHERE active = 1 ORDER BY department_name ASC")->fetchAll();

$selectedYear = $_GET['year'] ?? app_config('current_planning_year', '2019 E.C.');
$selectedMonth = $_GET['month'] ?? app_config('current_planning_month', 'Nehase');
$selectedDept = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : 0;

// Aggregate metrics by department for the selected month
$query = "
    SELECT d.id as dept_id, d.department_name, d.department_code,
           COUNT(wt.id) as total_planned,
           SUM(CASE WHEN wtr.result = 'DONE' THEN 1 ELSE 0 END) as total_done,
           SUM(CASE WHEN wtr.result = 'NOT_DONE' THEN 1 ELSE 0 END) as total_not_done,
           (SELECT COUNT(*) FROM weekly_achievements wa WHERE wa.department_id = d.id AND wa.year = :year AND wa.month = :month) as total_achievements,
           (SELECT COUNT(*) FROM weekly_challenges wc WHERE wc.department_id = d.id AND wc.year = :year AND wc.month = :month) as total_challenges
    FROM departments d
    LEFT JOIN weekly_tasks wt ON d.id = wt.department_id AND wt.year = :year AND wt.month = :month
    LEFT JOIN weekly_task_results wtr ON wt.id = wtr.weekly_task_id
    WHERE d.active = 1
";

$params = [
    ':year' => $selectedYear,
    ':month' => $selectedMonth
];

if ($selectedDept > 0) {
    $query .= " AND d.id = :dept_id";
    $params[':dept_id'] = $selectedDept;
}

$query .= " GROUP BY d.id, d.department_name, d.department_code ORDER BY total_done DESC, d.department_name ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$monthlyStats = $stmt->fetchAll();

// Outstanding Not Done Tasks for this month
$outstandingStmt = $db->prepare("
    SELECT wt.task_title, wt.week_number, d.department_code, rc.name as reason_name,
           wtr.not_done_explanation, wtr.next_action, wtr.expected_completion_date
    FROM weekly_task_results wtr
    JOIN weekly_tasks wt ON wtr.weekly_task_id = wt.id
    JOIN departments d ON wt.department_id = d.id
    LEFT JOIN reason_categories rc ON wtr.not_done_reason_id = rc.id
    WHERE wtr.result = 'NOT_DONE' AND wtr.year = ? AND wtr.month = ?
    ORDER BY wt.week_number ASC, wt.id ASC
");
$outstandingStmt->execute([$selectedYear, $selectedMonth]);
$outstandingTasks = $outstandingStmt->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="mb-1 fw-bold text-dark">
            <i class="fas fa-calendar-check text-warning me-2"></i> Monthly Performance Aggregation
        </h3>
        <p class="text-muted mb-0">Departmental rollup of weekly executions for <strong><?= e($selectedMonth) ?> <?= e($selectedYear) ?></strong>.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" onclick="window.print()">
            <i class="fas fa-print me-1"></i> Print Monthly Scorecard
        </button>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar mb-4">
    <form method="GET" action="monthly.php" class="row g-3 align-items-end">
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

        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">DEPARTMENT</label>
            <select name="department_id" class="form-select">
                <option value="0">All Departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= (int)$d['id'] ?>" <?= $selectedDept === (int)$d['id'] ? 'selected' : '' ?>>
                        <?= e($d['department_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-kaldis w-100">
                <i class="fas fa-filter me-1"></i> Filter Scorecard
            </button>
        </div>
    </form>
</div>

<!-- Department Rollup Table -->
<div class="card mb-4">
    <div class="card-header kaldis-header d-flex justify-content-between align-items-center">
        <span class="fs-6 fw-bold">
            <i class="fas fa-chart-bar me-2"></i> Monthly Departmental Rollup (<?= e($selectedMonth) ?> <?= e($selectedYear) ?>)
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-kaldis mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 100px;">Code</th>
                    <th>Department</th>
                    <th style="width: 120px;" class="text-center">Total Planned</th>
                    <th style="width: 100px;" class="text-center">DONE</th>
                    <th style="width: 110px;" class="text-center">NOT DONE</th>
                    <th style="width: 200px;">Monthly Completion %</th>
                    <th style="width: 120px;" class="text-center">Achievements</th>
                    <th style="width: 120px;" class="text-center">Challenges</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($monthlyStats as $stat): 
                    $tot = (int)$stat['total_planned'];
                    $done = (int)$stat['total_done'];
                    $notDone = (int)$stat['total_not_done'];
                    $pct = $tot > 0 ? round(($done / $tot) * 100, 1) : 0;
                    $barClass = $tot == 0 ? 'bg-secondary-subtle' : ($pct >= 80 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger'));
                ?>
                    <tr>
                        <td>
                            <span class="badge bg-dark font-monospace fs-6 px-2"><?= e($stat['department_code']) ?></span>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= e($stat['department_name']) ?></div>
                        </td>
                        <td class="text-center fw-bold"><?= $tot ?></td>
                        <td class="text-center text-success fw-bold"><?= $done ?></td>
                        <td class="text-center text-danger fw-bold"><?= $notDone ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 10px;">
                                    <div class="progress-bar <?= $barClass ?>" 
                                         style="width: <?= $tot > 0 ? $pct : 0 ?>%;"></div>
                                </div>
                                <span class="fw-bold font-monospace small text-muted"><?= $tot > 0 ? $pct . '%' : '&mdash;' ?></span>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-warning text-dark font-monospace"><?= (int)$stat['total_achievements'] ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-danger font-monospace"><?= (int)$stat['total_challenges'] ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Outstanding Not Done Tasks Section -->
<div class="card">
    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
        <span class="fs-6 fw-bold">
            <i class="fas fa-triangle-exclamation me-2"></i> Outstanding NOT DONE Tasks for <?= e($selectedMonth) ?> (Total: <?= count($outstandingTasks) ?>)
        </span>
        <a href="<?= url('/performance/not_done.php?year=' . urlencode($selectedYear) . '&month=' . urlencode($selectedMonth)) ?>" class="btn btn-sm btn-light text-danger fw-bold">
            Full Analysis &rarr;
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 80px;">Dept</th>
                    <th style="width: 80px;">Week</th>
                    <th>Task Description</th>
                    <th style="width: 180px;">Reason Category</th>
                    <th>Explanation & Blocker</th>
                    <th style="width: 200px;">Corrective Next Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($outstandingTasks)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">Zero uncompleted tasks recorded for this month!</td></tr>
                <?php else: ?>
                    <?php foreach ($outstandingTasks as $ot): ?>
                        <tr>
                            <td><span class="badge bg-dark font-monospace"><?= e($ot['department_code']) ?></span></td>
                            <td><span class="badge bg-light text-dark border">Wk <?= $ot['week_number'] ?></span></td>
                            <td class="fw-bold text-dark"><?= e($ot['task_title']) ?></td>
                            <td><span class="badge bg-danger"><?= e($ot['reason_name'] ?? 'Unspecified') ?></span></td>
                            <td class="small text-muted"><?= e($ot['not_done_explanation']) ?></td>
                            <td class="small text-secondary fw-semibold">
                                <?= e($ot['next_action'] ?: 'No next action specified') ?>
                                <?php if (!empty($ot['expected_completion_date'])): ?>
                                    <div class="small text-muted">Target: <?= date('M d', strtotime($ot['expected_completion_date'])) ?></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

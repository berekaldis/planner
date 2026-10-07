<?php
/**
 * Not Done Root-Cause Analysis Report
 * Kaldis Coffee PLC
 */

$pageTitle = 'Not Done Root-Cause Analysis';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getConnection();

$years = get_planning_years();
$months = get_ethiopian_months();
$weeks = get_month_weeks();
$departments = $db->query("SELECT id, department_name, department_code FROM departments WHERE active = 1 ORDER BY department_name ASC")->fetchAll();
$reasons = $db->query("SELECT id, name FROM reason_categories WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();

// Filters
$selectedYear = $_GET['year'] ?? app_config('current_planning_year', '2019 E.C.');
$selectedMonth = $_GET['month'] ?? '';
$selectedWeek = !empty($_GET['week']) ? (int)$_GET['week'] : 0;
$selectedDept = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$selectedReason = !empty($_GET['reason_id']) ? (int)$_GET['reason_id'] : 0;
$selectedType = strtoupper(trim($_GET['plan_type'] ?? ''));

// Query NOT DONE records
$query = "
    SELECT wtr.*, wt.task_title, wt.expected_result, wt.responsible_person, wt.plan_type, wt.due_date,
           d.department_name, d.department_code,
           ag.goal_code, ag.title as annual_goal_title,
           rc.name as reason_category_name,
           u.full_name as evaluated_by_name
    FROM weekly_task_results wtr
    JOIN weekly_tasks wt ON wtr.weekly_task_id = wt.id
    JOIN departments d ON wtr.department_id = d.id
    LEFT JOIN annual_goals ag ON wt.annual_goal_id = ag.id
    LEFT JOIN reason_categories rc ON wtr.not_done_reason_id = rc.id
    LEFT JOIN users u ON wtr.completed_by = u.id
    WHERE wtr.result = 'NOT_DONE' AND wtr.year = :year
";
$params = [':year' => $selectedYear];

if (!empty($selectedMonth)) {
    $query .= " AND wtr.month = :month";
    $params[':month'] = $selectedMonth;
}
if ($selectedWeek > 0) {
    $query .= " AND wtr.week_number = :week";
    $params[':week'] = $selectedWeek;
}
if ($selectedDept > 0) {
    $query .= " AND wtr.department_id = :dept_id";
    $params[':dept_id'] = $selectedDept;
}
if ($selectedReason > 0) {
    $query .= " AND wtr.not_done_reason_id = :reason_id";
    $params[':reason_id'] = $selectedReason;
}
if (in_array($selectedType, ['STRATEGY', 'OPERATIONAL'], true)) {
    $query .= " AND wt.plan_type = :plan_type";
    $params[':plan_type'] = $selectedType;
}

$query .= " ORDER BY wtr.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$notDoneTasks = $stmt->fetchAll();

// Group by Reason Category for the Analytical Breakdown Chart
$reasonCounts = [];
foreach ($notDoneTasks as $t) {
    $rcName = $t['reason_category_name'] ?: 'Unspecified';
    $reasonCounts[$rcName] = ($reasonCounts[$rcName] ?? 0) + 1;
}
arsort($reasonCounts);

// Export CSV handler
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="Kaldis_Not_Done_Analysis_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Department', 'Year', 'Month', 'Week', 'Plan Type', 'Goal Code', 'Task', 'Responsible', 'Result', 'Reason Category', 'Explanation', 'Next Action', 'Expected Date']);
    foreach ($notDoneTasks as $row) {
        fputcsv($out, [
            $row['department_name'],
            $row['year'],
            $row['month'],
            'Week ' . $row['week_number'],
            $row['plan_type'],
            $row['goal_code'] ?? '-',
            $row['task_title'],
            $row['responsible_person'],
            'NOT DONE',
            $row['reason_category_name'] ?? 'Unspecified',
            $row['not_done_explanation'],
            $row['next_action'] ?? '',
            $row['expected_completion_date'] ?? ''
        ]);
    }
    fclose($out);
    exit;
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="mb-1 fw-bold text-dark">
            <i class="fas fa-circle-exclamation text-danger me-2"></i> Not Done Root-Cause Analysis
        </h3>
        <p class="text-muted mb-0">Executive inquiry into uncompleted weekly tasks, reason categorization, and corrective next actions.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-outline-success">
            <i class="fas fa-file-excel me-1"></i> Export to CSV / Excel
        </a>
        <button class="btn btn-outline-secondary" onclick="window.print()">
            <i class="fas fa-print me-1"></i> Print Report
        </button>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar mb-4">
    <form method="GET" action="not_done.php" class="row g-3 align-items-end">
        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted">PLANNING YEAR</label>
            <select name="year" class="form-select">
                <?php foreach ($years as $y): ?>
                    <option value="<?= e($y) ?>" <?= $selectedYear === $y ? 'selected' : '' ?>><?= e($y) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted">MONTH</label>
            <select name="month" class="form-select">
                <option value="">All Months</option>
                <?php foreach ($months as $m): ?>
                    <option value="<?= e($m) ?>" <?= $selectedMonth === $m ? 'selected' : '' ?>><?= e($m) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted">WEEK</label>
            <select name="week" class="form-select">
                <option value="0">All Weeks</option>
                <?php foreach ($weeks as $wkNum => $wkLabel): ?>
                    <option value="<?= $wkNum ?>" <?= $selectedWeek === $wkNum ? 'selected' : '' ?>><?= e($wkLabel) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
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

        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted">REASON CATEGORY</label>
            <select name="reason_id" class="form-select">
                <option value="0">All Reasons</option>
                <?php foreach ($reasons as $r): ?>
                    <option value="<?= (int)$r['id'] ?>" <?= $selectedReason === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-kaldis-dark w-100">
                <i class="fas fa-search me-1"></i> Filter
            </button>
            <a href="not_done.php" class="btn btn-outline-secondary" title="Reset">
                <i class="fas fa-undo"></i>
            </a>
        </div>
    </form>
</div>

<!-- Reason Breakdown Metrics -->
<?php if (!empty($reasonCounts)): ?>
    <div class="row g-2 mb-4">
        <?php foreach (array_slice($reasonCounts, 0, 4, true) as $rName => $cnt): ?>
            <div class="col-md-3">
                <div class="card p-3 border-danger bg-light">
                    <div class="small text-danger fw-bold text-uppercase"><?= e($rName) ?></div>
                    <div class="fs-3 fw-bold text-dark"><?= $cnt ?> <small class="fs-6 text-muted">Tasks</small></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Not Done Analysis Table -->
<div class="card">
    <div class="card-header kaldis-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fs-6 fw-bold">
            <i class="fas fa-list-ul me-2"></i> Uncompleted Tasks Log (Total: <?= count($notDoneTasks) ?>)
        </span>
        <div class="d-flex align-items-center gap-2">
            <div class="search-input-wrapper" style="width: 220px;">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control form-control-sm live-search-input live-table-search" placeholder="Filter tasks..." data-target-table=".table-kaldis">
            </div>
            <span class="badge bg-danger">Status: NOT DONE</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-kaldis mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 130px;">Dept / Period</th>
                    <th style="width: 100px;">Plan Type</th>
                    <th>Weekly Task & Parent Goal</th>
                    <th style="width: 130px;">Responsible</th>
                    <th style="width: 150px;">Reason Category</th>
                    <th>Detailed Explanation</th>
                    <th style="width: 170px;">Next Action & Target</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($notDoneTasks)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-circle-check fa-3x mb-3 d-block text-success opacity-50"></i>
                            Excellent! No tasks marked as <strong>NOT DONE</strong> under the selected filters.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($notDoneTasks as $row): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= e($row['department_code']) ?></div>
                                <small class="text-muted"><?= e($row['month']) ?> &bull; Wk <?= $row['week_number'] ?></small>
                            </td>
                            <td>
                                <?= render_plan_type_badge($row['plan_type']) ?>
                                <?php if (!empty($row['goal_code'])): ?>
                                    <div class="small font-monospace text-muted mt-1"><?= e($row['goal_code']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($row['task_title']) ?></div>
                                <?php if (!empty($row['annual_goal_title'])): ?>
                                    <small class="text-primary d-block">
                                        <i class="fas fa-bullseye me-1"></i> <?= e($row['annual_goal_title']) ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="fw-semibold text-secondary"><?= e($row['responsible_person']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-danger text-wrap text-start">
                                    <?= e($row['reason_category_name'] ?? 'Unspecified') ?>
                                </span>
                            </td>
                            <td>
                                <div class="small text-dark fw-semibold"><?= e($row['not_done_explanation']) ?></div>
                            </td>
                            <td>
                                <?php if (!empty($row['next_action'])): ?>
                                    <div class="small text-secondary"><i class="fas fa-arrow-right me-1"></i> <?= e($row['next_action']) ?></div>
                                <?php else: ?>
                                    <span class="text-muted small">No next action specified</span>
                                <?php endif; ?>
                                <?php if (!empty($row['expected_completion_date'])): ?>
                                    <small class="badge bg-light text-dark border mt-1">Due: <?= date('M d, Y', strtotime($row['expected_completion_date'])) ?></small>
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

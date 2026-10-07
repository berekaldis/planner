<?php
/**
 * Company-Wide Weekly Performance Review & Submission Tracker
 * Kaldis Coffee PLC
 */

$pageTitle = 'Weekly Performance Review';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getConnection();
$user = Auth::user();

$years = get_planning_years();
$months = get_ethiopian_months();
$weeks = get_month_weeks();
$departments = $db->query("SELECT id, department_name, department_code FROM departments WHERE active = 1 ORDER BY department_name ASC")->fetchAll();

$selectedYear = $_GET['year'] ?? app_config('current_planning_year', '2019 E.C.');
$selectedMonth = $_GET['month'] ?? app_config('current_planning_month', 'Nehase');
$selectedWeek = !empty($_GET['week']) ? (int)$_GET['week'] : 1;
$selectedDept = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : 0;

// Fetch Company-Wide Department Submission & Completion Matrix
$matrixStmt = $db->prepare("
    SELECT d.id as dept_id, d.department_name, d.department_code,
           COALESCE(u.full_name, 'Unassigned') as head_name,
           wr.status as report_status, wr.total_tasks, wr.done_tasks, wr.not_done_tasks,
           wr.completion_percentage, wr.submitted_at, wr.submission_status,
           (SELECT COUNT(*) FROM weekly_achievements wa WHERE wa.department_id = d.id AND wa.year = :year AND wa.month = :month AND wa.week_number = :week) as achievement_count,
           (SELECT COUNT(*) FROM weekly_challenges wc WHERE wc.department_id = d.id AND wc.year = :year AND wc.month = :month AND wc.week_number = :week) as challenge_count
    FROM departments d
    LEFT JOIN users u ON d.head_user_id = u.id
    LEFT JOIN weekly_reports wr 
           ON d.id = wr.department_id 
          AND wr.year = :year 
          AND wr.month = :month 
          AND wr.week_number = :week
    WHERE d.active = 1
    ORDER BY wr.completion_percentage DESC, d.department_name ASC
");
$matrixStmt->execute([
    ':year' => $selectedYear,
    ':month' => $selectedMonth,
    ':week' => $selectedWeek
]);
$deptMatrix = $matrixStmt->fetchAll();

// Calculate Company Aggregates
$totCompanyTasks = 0;
$totCompanyDone = 0;
$totCompanyNotDone = 0;
$submittedCount = 0;
$onTimeCount = 0;
$lateCount = 0;
$missingCount = 0;

foreach ($deptMatrix as $row) {
    $totCompanyTasks += (int)$row['total_tasks'];
    $totCompanyDone += (int)$row['done_tasks'];
    $totCompanyNotDone += (int)$row['not_done_tasks'];

    if ($row['report_status'] === 'SUBMITTED') {
        $submittedCount++;
        if ($row['submission_status'] === 'ON_TIME') $onTimeCount++;
        else $lateCount++;
    } else {
        $missingCount++;
    }
}

$companyCompletionPct = $totCompanyTasks > 0 ? round(($totCompanyDone / $totCompanyTasks) * 100, 1) : 0;
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="mb-1 fw-bold text-dark">
            <i class="fas fa-chart-pie text-warning me-2"></i> Weekly Department Performance Review
        </h3>
        <p class="text-muted mb-0">Cross-department execution scorecard, completion rates, and submission compliance.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/performance/not_done.php?year=' . urlencode($selectedYear) . '&month=' . urlencode($selectedMonth) . '&week=' . $selectedWeek) ?>" class="btn btn-outline-danger">
            <i class="fas fa-circle-exclamation me-1"></i> Not Done Analysis
        </a>
        <button class="btn btn-outline-secondary" onclick="window.print()">
            <i class="fas fa-print me-1"></i> Print Scorecard
        </button>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar mb-4">
    <form method="GET" action="weekly.php" class="row g-3 align-items-end">
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
            <label class="form-label small fw-bold text-muted">WEEK NUMBER</label>
            <select name="week" class="form-select">
                <?php foreach ($weeks as $wkNum => $wkLabel): ?>
                    <option value="<?= $wkNum ?>" <?= $selectedWeek === $wkNum ? 'selected' : '' ?>><?= e($wkLabel) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-kaldis w-100">
                <i class="fas fa-filter me-1"></i> Apply Filter
            </button>
        </div>
    </form>
</div>

<!-- Company Aggregate Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card stat-primary">
            <i class="fas fa-building stat-icon text-primary"></i>
            <div class="stat-label">Departments Reporting</div>
            <div class="stat-value text-dark"><?= $submittedCount ?> / <?= count($deptMatrix) ?></div>
            <small class="text-muted"><?= round(($submittedCount / max(1, count($deptMatrix))) * 100) ?>% Compliance</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-gold">
            <i class="fas fa-chart-line stat-icon text-warning"></i>
            <div class="stat-label">Overall Completion</div>
            <div class="stat-value text-dark"><?= $companyCompletionPct ?>%</div>
            <small class="text-muted"><?= $totCompanyDone ?> Done / <?= $totCompanyTasks ?> Total Tasks</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-done">
            <i class="fas fa-clock-check stat-icon text-success"></i>
            <div class="stat-label">On-Time Submissions</div>
            <div class="stat-value text-success"><?= $onTimeCount ?></div>
            <small class="text-muted"><?= $lateCount ?> Late submissions</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-notdone">
            <i class="fas fa-triangle-exclamation stat-icon text-danger"></i>
            <div class="stat-label">Missing / Pending</div>
            <div class="stat-value text-danger"><?= $missingCount ?></div>
            <small class="text-muted">Requires reminder/escalation</small>
        </div>
    </div>
</div>

<!-- Department Performance Scorecard Table -->
<div class="card mb-4">
    <div class="card-header kaldis-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fs-6 fw-bold">
            <i class="fas fa-ranking-star me-2"></i> Department Performance Scorecard &mdash; Week <?= $selectedWeek ?> (<?= e($selectedMonth) ?> <?= e($selectedYear) ?>)
        </span>
        <div class="d-flex align-items-center gap-2">
            <div class="search-input-wrapper" style="width: 220px;">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control form-control-sm live-search-input live-table-search" placeholder="Filter departments..." data-target-table=".table-kaldis">
            </div>
            <span class="badge bg-warning text-dark font-monospace"><?= count($deptMatrix) ?> Departments</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-kaldis mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 100px;">Code</th>
                    <th>Department & Head</th>
                    <th style="width: 120px;" class="text-center">Submission</th>
                    <th style="width: 90px;" class="text-center">Planned</th>
                    <th style="width: 90px;" class="text-center">DONE</th>
                    <th style="width: 90px;" class="text-center">NOT DONE</th>
                    <th style="width: 160px;">Completion %</th>
                    <th style="width: 110px;" class="text-center">Achievements</th>
                    <th style="width: 100px;" class="text-center">Challenges</th>
                    <th style="width: 120px;" class="text-end no-print">Drilldown</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($deptMatrix as $row): ?>
                    <tr>
                        <td>
                            <span class="badge bg-dark font-monospace fs-6 px-2"><?= e($row['department_code']) ?></span>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= e($row['department_name']) ?></div>
                            <small class="text-muted">Head: <?= e($row['head_name']) ?></small>
                        </td>
                        <td class="text-center">
                            <?php if ($row['report_status'] === 'SUBMITTED'): ?>
                                <?= render_submission_badge($row['submission_status']) ?>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="fas fa-hourglass-start me-1"></i> PENDING</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center fw-bold"><?= (int)$row['total_tasks'] ?></td>
                        <td class="text-center text-success fw-bold"><?= (int)$row['done_tasks'] ?></td>
                        <td class="text-center text-danger fw-bold"><?= (int)$row['not_done_tasks'] ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 8px;">
                                    <div class="progress-bar <?= $row['completion_percentage'] >= 80 ? 'bg-success' : ($row['completion_percentage'] >= 50 ? 'bg-warning' : 'bg-danger') ?>" 
                                         style="width: <?= (float)$row['completion_percentage'] ?>%;"></div>
                                </div>
                                <span class="small fw-bold font-monospace"><?= (float)$row['completion_percentage'] ?>%</span>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-warning text-dark font-monospace"><?= (int)$row['achievement_count'] ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-danger font-monospace"><?= (int)$row['challenge_count'] ?></span>
                        </td>
                        <td class="text-end no-print">
                            <a href="<?= url('/department/performance.php?year=' . urlencode($selectedYear) . '&month=' . urlencode($selectedMonth) . '&week=' . $selectedWeek . '&department_id=' . $row['dept_id']) ?>" 
                               class="btn btn-sm btn-outline-primary" title="View Full Weekly Report">
                                <i class="fas fa-eye me-1"></i> View
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

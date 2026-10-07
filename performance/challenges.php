<?php
/**
 * Cross-Department Challenge & Recurring Blocker Analysis
 * Kaldis Coffee PLC
 */

$pageTitle = 'Weekly Challenges & Blockers';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getConnection();

$years = get_planning_years();
$months = get_ethiopian_months();
$weeks = get_month_weeks();
$departments = $db->query("SELECT id, department_name, department_code FROM departments WHERE active = 1 ORDER BY department_name ASC")->fetchAll();

$selectedYear = $_GET['year'] ?? app_config('current_planning_year', '2019 E.C.');
$selectedMonth = $_GET['month'] ?? '';
$selectedWeek = !empty($_GET['week']) ? (int)$_GET['week'] : 0;
$selectedDept = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : 0;

$query = "
    SELECT wc.*, d.department_name, d.department_code, 
           wt.task_title as related_task_title, wt.responsible_person as task_responsible,
           u.full_name as author_name
    FROM weekly_challenges wc
    JOIN departments d ON wc.department_id = d.id
    LEFT JOIN weekly_tasks wt ON wc.related_weekly_task_id = wt.id
    LEFT JOIN users u ON wc.created_by = u.id
    WHERE wc.year = :year
";
$params = [':year' => $selectedYear];

if (!empty($selectedMonth)) {
    $query .= " AND wc.month = :month";
    $params[':month'] = $selectedMonth;
}
if ($selectedWeek > 0) {
    $query .= " AND wc.week_number = :week";
    $params[':week'] = $selectedWeek;
}
if ($selectedDept > 0) {
    $query .= " AND wc.department_id = :dept_id";
    $params[':dept_id'] = $selectedDept;
}

$query .= " ORDER BY wc.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$challenges = $stmt->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="mb-1 fw-bold text-dark"><i class="fas fa-triangle-exclamation text-danger me-2"></i> Weekly Challenges & Blockers</h3>
        <p class="text-muted mb-0">Cross-departmental visibility into operational hurdles, supplier delays, and resource bottlenecks.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" onclick="window.print()">
            <i class="fas fa-print me-1"></i> Print Challenges
        </button>
    </div>
</div>

<!-- Important Rule Context -->
<div class="alert alert-light border border-secondary small d-flex align-items-center mb-4">
    <i class="fas fa-info-circle fa-2x text-primary me-3"></i>
    <div>
        <strong>Principle 8:</strong> Challenges are separate from task performance. A challenge does <em>not</em> automatically mark a task as NOT DONE. A department can overcome a severe challenge and still achieve 100% completion.
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar mb-4">
    <form method="GET" action="challenges.php" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">PLANNING YEAR</label>
            <select name="year" class="form-select">
                <?php foreach ($years as $y): ?>
                    <option value="<?= e($y) ?>" <?= $selectedYear === $y ? 'selected' : '' ?>><?= e($y) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
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

        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-kaldis-dark w-100">
                <i class="fas fa-filter me-1"></i> Filter
            </button>
            <a href="challenges.php" class="btn btn-outline-secondary" title="Reset">
                <i class="fas fa-undo"></i>
            </a>
        </div>
    </form>
</div>

<!-- Challenges Table -->
<div class="card">
    <div class="card-header kaldis-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fs-6 fw-bold">
            <i class="fas fa-triangle-exclamation me-2"></i> Logged Challenges (Total: <?= count($challenges) ?>)
        </span>
        <div class="d-flex align-items-center gap-2">
            <div class="search-input-wrapper" style="width: 220px;">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control form-control-sm live-search-input live-table-search" placeholder="Filter challenges..." data-target-table=".table-kaldis">
            </div>
            <span class="badge bg-warning text-dark font-monospace"><?= e($selectedYear) ?></span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-kaldis mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 150px;">Department</th>
                    <th style="width: 140px;">Period</th>
                    <th>Challenge Description & Hurdle</th>
                    <th style="width: 220px;">Related Weekly Task</th>
                    <th style="width: 140px;">Reported By</th>
                    <th style="width: 140px;">Date Logged</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($challenges)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fas fa-shield-heart fa-3x mb-3 d-block opacity-25"></i>
                            No challenges recorded under the selected filter criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($challenges as $ch): ?>
                        <tr>
                            <td>
                                <span class="badge bg-dark font-monospace"><?= e($ch['department_code']) ?></span>
                                <div class="small fw-semibold mt-1"><?= e($ch['department_name']) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= e($ch['month']) ?> &bull; Week <?= $ch['week_number'] ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-danger fs-6">
                                    <i class="fas fa-circle-exclamation me-2"></i><?= e($ch['challenge_text']) ?>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($ch['related_task_title'])): ?>
                                    <div class="small fw-bold text-dark"><?= e($ch['related_task_title']) ?></div>
                                    <small class="text-muted">Owner: <?= e($ch['task_responsible'] ?? 'Dept') ?></small>
                                <?php else: ?>
                                    <span class="text-muted small">General department challenge</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-secondary"><?= e($ch['author_name'] ?? 'Staff') ?></small>
                            </td>
                            <td>
                                <small class="text-muted"><?= date('M d, Y H:i', strtotime($ch['created_at'])) ?></small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
/**
 * Organization-Wide Achievements Tracker
 * Kaldis Coffee PLC
 */

$pageTitle = 'Weekly & Monthly Achievements';
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
    SELECT wa.*, d.department_name, d.department_code, u.full_name as author_name
    FROM weekly_achievements wa
    JOIN departments d ON wa.department_id = d.id
    LEFT JOIN users u ON wa.created_by = u.id
    WHERE wa.year = :year
";
$params = [':year' => $selectedYear];

if (!empty($selectedMonth)) {
    $query .= " AND wa.month = :month";
    $params[':month'] = $selectedMonth;
}
if ($selectedWeek > 0) {
    $query .= " AND wa.week_number = :week";
    $params[':week'] = $selectedWeek;
}
if ($selectedDept > 0) {
    $query .= " AND wa.department_id = :dept_id";
    $params[':dept_id'] = $selectedDept;
}

$query .= " ORDER BY wa.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$achievements = $stmt->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="mb-1 fw-bold text-dark"><i class="fas fa-award text-warning me-2"></i> Department Achievements Report</h3>
        <p class="text-muted mb-0">Record of tangible milestones and accomplishments delivered across all departments.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" onclick="window.print()">
            <i class="fas fa-print me-1"></i> Print Achievements
        </button>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar mb-4">
    <form method="GET" action="achievements.php" class="row g-3 align-items-end">
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
            <button type="submit" class="btn btn-kaldis w-100">
                <i class="fas fa-filter me-1"></i> Filter
            </button>
            <a href="achievements.php" class="btn btn-outline-secondary" title="Reset">
                <i class="fas fa-undo"></i>
            </a>
        </div>
    </form>
</div>

<!-- Achievements List -->
<div class="card">
    <div class="card-header kaldis-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fs-6 fw-bold">
            <i class="fas fa-trophy me-2"></i> Logged Achievements (Total: <?= count($achievements) ?>)
        </span>
        <div class="d-flex align-items-center gap-2">
            <div class="search-input-wrapper" style="width: 220px;">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control form-control-sm live-search-input live-table-search" placeholder="Filter achievements..." data-target-table=".table-kaldis">
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
                    <th>Achievement Description</th>
                    <th style="width: 150px;">Recorded By</th>
                    <th style="width: 140px;">Date Logged</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($achievements)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fas fa-award fa-3x mb-3 d-block opacity-25"></i>
                            No achievements recorded for the selected period.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($achievements as $ach): ?>
                        <tr>
                            <td>
                                <span class="badge bg-dark font-monospace"><?= e($ach['department_code']) ?></span>
                                <div class="small fw-semibold mt-1"><?= e($ach['department_name']) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= e($ach['month']) ?> &bull; Week <?= $ach['week_number'] ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark fs-6">
                                    <i class="fas fa-star text-warning me-2"></i><?= e($ach['achievement_text']) ?>
                                </div>
                            </td>
                            <td>
                                <small class="text-secondary"><?= e($ach['author_name'] ?? 'Staff') ?></small>
                            </td>
                            <td>
                                <small class="text-muted"><?= date('M d, Y H:i', strtotime($ach['created_at'])) ?></small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

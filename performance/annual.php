<?php
/**
 * Complete Annual Strategic Planning Hierarchy & Performance Scorecard
 * Kaldis Coffee PLC
 */

$pageTitle = 'Annual Strategic Performance';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getConnection();

$years = get_planning_years();
$departments = $db->query("SELECT id, department_name, department_code FROM departments WHERE active = 1 ORDER BY department_name ASC")->fetchAll();

$selectedYear = $_GET['year'] ?? app_config('current_planning_year', '2019 E.C.');
$selectedDept = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : 0;

// Query Annual Goals with complete hierarchy rollup:
// Annual Goal -> Monthly Strategy Activations -> Monthly Plans -> Weekly Tasks -> Results
$query = "
    SELECT ag.*, d.department_name, d.department_code,
           (SELECT GROUP_CONCAT(DISTINCT msa.month SEPARATOR ', ') 
            FROM monthly_strategy_activations msa 
            WHERE msa.annual_goal_id = ag.id AND msa.year = :year AND msa.active = 'YES') as active_months,
           (SELECT COUNT(DISTINCT mp.id) 
            FROM monthly_plans mp 
            WHERE mp.annual_goal_id = ag.id AND mp.year = :year) as monthly_plan_count,
           (SELECT COUNT(wt.id) 
            FROM weekly_tasks wt 
            WHERE wt.annual_goal_id = ag.id AND wt.year = :year) as weekly_task_count,
           (SELECT COUNT(wtr.id) 
            FROM weekly_task_results wtr 
            JOIN weekly_tasks wt2 ON wtr.weekly_task_id = wt2.id 
            WHERE wt2.annual_goal_id = ag.id AND wt2.year = :year AND wtr.result = 'DONE') as done_task_count,
           (SELECT COUNT(wtr.id) 
            FROM weekly_task_results wtr 
            JOIN weekly_tasks wt3 ON wtr.weekly_task_id = wt3.id 
            WHERE wt3.annual_goal_id = ag.id AND wt3.year = :year AND wtr.result = 'NOT_DONE') as not_done_task_count
    FROM annual_goals ag
    JOIN departments d ON ag.responsible_department_id = d.id
    WHERE ag.year = :year
";
$params = [':year' => $selectedYear];

if ($selectedDept > 0) {
    $query .= " AND ag.responsible_department_id = :dept_id";
    $params[':dept_id'] = $selectedDept;
}

$query .= " ORDER BY ag.goal_code ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$annualHierarchy = $stmt->fetchAll();

// Company Annual Totals
$totGoals = count($annualHierarchy);
$totHierTasks = 0;
$totHierDone = 0;
$totHierNotDone = 0;

foreach ($annualHierarchy as $item) {
    $totHierTasks += (int)$item['weekly_task_count'];
    $totHierDone += (int)$item['done_task_count'];
    $totHierNotDone += (int)$item['not_done_task_count'];
}
$overallStrategyPct = $totHierTasks > 0 ? round(($totHierDone / $totHierTasks) * 100, 1) : 0;
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="mb-1 fw-bold text-dark">
            <i class="fas fa-diagram-project text-warning me-2"></i> Annual Strategic Hierarchy Scorecard
        </h3>
        <p class="text-muted mb-0">
            Traces: <strong>Annual Strategy &rarr; GM Monthly Activation &rarr; Monthly Plans &rarr; Weekly Tasks &rarr; Results</strong>
        </p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" onclick="window.print()">
            <i class="fas fa-print me-1"></i> Print Hierarchy
        </button>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar mb-4">
    <form method="GET" action="annual.php" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label small fw-bold text-muted">PLANNING YEAR</label>
            <select name="year" class="form-select">
                <?php foreach ($years as $y): ?>
                    <option value="<?= e($y) ?>" <?= $selectedYear === $y ? 'selected' : '' ?>><?= e($y) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-5">
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
                <i class="fas fa-filter me-1"></i> Filter Annual Report
            </button>
        </div>
    </form>
</div>

<!-- Overview Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card stat-gold">
            <i class="fas fa-bullseye stat-icon text-warning"></i>
            <div class="stat-label">Strategic Goals</div>
            <div class="stat-value text-dark"><?= $totGoals ?></div>
            <small class="text-muted">Master annual objectives</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-primary">
            <i class="fas fa-list-check stat-icon text-primary"></i>
            <div class="stat-label">Weekly Strategy Tasks</div>
            <div class="stat-value text-primary"><?= $totHierTasks ?></div>
            <small class="text-muted">Derived actionable items</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-done">
            <i class="fas fa-check-circle stat-icon text-success"></i>
            <div class="stat-label">Completed Tasks</div>
            <div class="stat-value text-success"><?= $totHierDone ?></div>
            <small class="text-muted"><?= $totHierNotDone ?> Uncompleted / NOT DONE</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-gold">
            <i class="fas fa-chart-line stat-icon text-warning"></i>
            <div class="stat-label">Overall Completion %</div>
            <div class="stat-value text-dark"><?= $overallStrategyPct ?>%</div>
            <div class="progress mt-2" style="height: 6px;">
                <div class="progress-bar bg-success" style="width: <?= $overallStrategyPct ?>%;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Hierarchy Table -->
<div class="card">
    <div class="card-header kaldis-header d-flex justify-content-between align-items-center">
        <span class="fs-6 fw-bold">
            <i class="fas fa-sitemap me-2"></i> Strategic Alignment & Execution Breakdown &mdash; <?= e($selectedYear) ?>
        </span>
        <span class="badge bg-warning text-dark font-monospace"><?= count($annualHierarchy) ?> Annual Strategies</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-kaldis mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 90px;">Goal ID</th>
                    <th>Annual Strategic Goal & Target</th>
                    <th style="width: 140px;">Department</th>
                    <th style="width: 150px;">GM Active Months</th>
                    <th style="width: 110px;" class="text-center">Monthly Plans</th>
                    <th style="width: 110px;" class="text-center">Weekly Tasks</th>
                    <th style="width: 90px;" class="text-center">DONE</th>
                    <th style="width: 90px;" class="text-center">NOT DONE</th>
                    <th style="width: 160px;">Strategic %</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($annualHierarchy)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fas fa-bullseye fa-3x mb-3 d-block opacity-25"></i>
                            No annual strategies defined for <?= e($selectedYear) ?>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($annualHierarchy as $row): 
                        $tasks = (int)$row['weekly_task_count'];
                        $done = (int)$row['done_task_count'];
                        $notDone = (int)$row['not_done_task_count'];
                        $pct = $tasks > 0 ? round(($done / $tasks) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td>
                                <span class="badge bg-dark font-monospace fs-6 px-2"><?= e($row['goal_code']) ?></span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($row['title']) ?></div>
                                <small class="text-muted"><strong>Target:</strong> <?= e($row['annual_target']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= e($row['department_code']) ?></span>
                                <small class="d-block text-muted" style="font-size: 0.75rem;"><?= e($row['department_name']) ?></small>
                            </td>
                            <td>
                                <?php if (!empty($row['active_months'])): ?>
                                    <span class="badge bg-success text-wrap text-start"><?= e($row['active_months']) ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">None Activated</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-semibold"><?= (int)$row['monthly_plan_count'] ?></td>
                            <td class="text-center fw-bold"><?= $tasks ?></td>
                            <td class="text-center text-success fw-bold"><?= $done ?></td>
                            <td class="text-center text-danger fw-bold"><?= $notDone ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar <?= $pct >= 80 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger') ?>" 
                                             style="width: <?= $pct ?>%;"></div>
                                    </div>
                                    <span class="small fw-bold font-monospace"><?= $pct ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

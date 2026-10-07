<?php
/**
 * Executive Management Dashboard (GM View)
 * Kaldis Coffee PLC
 */

$pageTitle = 'Executive Management Dashboard';
require_once __DIR__ . '/../includes/header.php';

// Accessible by GM, Super Admin, and HR
Permissions::requireRole([Permissions::ROLE_SUPER_ADMIN, Permissions::ROLE_GM, Permissions::ROLE_HR]);

$db = Database::getConnection();

$year = app_config('current_planning_year', '2019 E.C.');
$month = app_config('current_planning_month', 'Nehase');

// 1. Planning Metrics
$totAnnualGoals = $db->query("SELECT COUNT(*) FROM annual_goals WHERE year = '{$year}' AND status = 'ACTIVE'")->fetchColumn();
$totActiveStrategiesThisMonth = (int)$db->query("
    SELECT COUNT(DISTINCT annual_goal_id) FROM monthly_strategy_activations 
    WHERE year = '{$year}' AND month = '{$month}' AND active = 'YES'
")->fetchColumn();
$totMonthlyPlans = $db->query("SELECT COUNT(*) FROM monthly_plans WHERE year = '{$year}' AND month = '{$month}'")->fetchColumn();
$totWeeklyTasks = $db->query("SELECT COUNT(*) FROM weekly_tasks WHERE year = '{$year}' AND month = '{$month}'")->fetchColumn();

// 2. Execution Metrics
$execStats = $db->query("
    SELECT 
        COUNT(wt.id) as planned,
        SUM(CASE WHEN wtr.result = 'DONE' THEN 1 ELSE 0 END) as done,
        SUM(CASE WHEN wtr.result = 'NOT_DONE' THEN 1 ELSE 0 END) as not_done
    FROM weekly_tasks wt
    LEFT JOIN weekly_task_results wtr ON wt.id = wtr.weekly_task_id
    WHERE wt.year = '{$year}' AND wt.month = '{$month}'
")->fetch();

$plannedTasks = (int)($execStats['planned'] ?? 0);
$doneTasks = (int)($execStats['done'] ?? 0);
$notDoneTasks = (int)($execStats['not_done'] ?? 0);
$completionPct = $plannedTasks > 0 ? round(($doneTasks / $plannedTasks) * 100, 1) : 0;

// 3. Reporting Submission Tracker Counts
$subCounts = $db->query("
    SELECT 
        SUM(CASE WHEN s.status = 'WAITING' THEN 1 ELSE 0 END) as waiting,
        SUM(CASE WHEN s.status = 'ON_TIME' THEN 1 ELSE 0 END) as on_time,
        SUM(CASE WHEN s.status = 'LATE' THEN 1 ELSE 0 END) as late,
        SUM(CASE WHEN s.status = 'MISSING' THEN 1 ELSE 0 END) as missing
    FROM departments d
    LEFT JOIN submissions s ON d.id = s.department_id AND s.year = '{$year}' AND s.month = '{$month}'
    WHERE d.active = 1
")->fetch();

// 4. Recent Achievements
$recentAchievements = $db->query("
    SELECT wa.*, d.department_name, d.department_code, u.full_name as author_name
    FROM weekly_achievements wa
    JOIN departments d ON wa.department_id = d.id
    LEFT JOIN users u ON wa.created_by = u.id
    WHERE wa.year = '{$year}' AND wa.month = '{$month}'
    ORDER BY wa.created_at DESC
    LIMIT 5
")->fetchAll();

// 5. Recent Challenges & Blockers
$recentChallenges = $db->query("
    SELECT wc.*, d.department_name, d.department_code, wt.task_title as related_task_title
    FROM weekly_challenges wc
    JOIN departments d ON wc.department_id = d.id
    LEFT JOIN weekly_tasks wt ON wc.related_weekly_task_id = wt.id
    WHERE wc.year = '{$year}' AND wc.month = '{$month}'
    ORDER BY wc.created_at DESC
    LIMIT 5
")->fetchAll();

// 6. Top Not Done Reasons
$topReasons = $db->query("
    SELECT rc.name as reason_name, COUNT(wtr.id) as cnt
    FROM weekly_task_results wtr
    JOIN reason_categories rc ON wtr.not_done_reason_id = rc.id
    WHERE wtr.result = 'NOT_DONE' AND wtr.year = '{$year}' AND wtr.month = '{$month}'
    GROUP BY rc.id, rc.name
    ORDER BY cnt DESC
    LIMIT 6
")->fetchAll();

// 7. Department Execution Comparison
$deptExecData = $db->query("
    SELECT d.department_code, d.department_name,
        COUNT(wt.id) as total_tasks,
        SUM(CASE WHEN wtr.result = 'DONE' THEN 1 ELSE 0 END) as done_tasks,
        SUM(CASE WHEN wtr.result = 'NOT_DONE' THEN 1 ELSE 0 END) as not_done_tasks
    FROM departments d
    LEFT JOIN weekly_tasks wt ON d.id = wt.department_id AND wt.year = '{$year}' AND wt.month = '{$month}'
    LEFT JOIN weekly_task_results wtr ON wt.id = wtr.weekly_task_id
    WHERE d.active = 1
    GROUP BY d.id, d.department_code, d.department_name
    ORDER BY d.department_name ASC
")->fetchAll();
?>

<!-- Page Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="mb-1 fw-bold text-dark">
            <i class="fas fa-chart-line text-warning me-2"></i> General Manager Executive Dashboard
        </h3>
        <p class="text-muted mb-0">High-level strategic alignment, execution KPIs, and submission oversight for <strong><?= e($month) ?> <?= e($year) ?></strong>.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/admin/monthly_activation.php') ?>" class="btn btn-kaldis">
            <i class="fas fa-toggle-on me-1"></i> Monthly Activation
        </a>
        <a href="<?= url('/performance/weekly.php') ?>" class="btn btn-kaldis-dark">
            <i class="fas fa-eye me-1"></i> Weekly Scorecard
        </a>
    </div>
</div>

<!-- Smart Monthly Lifecycle Workflow Stepper -->
<div class="card border-0 shadow-sm mb-4 bg-white">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3 gap-2">
            <div>
                <h6 class="fw-bold text-dark mb-0">
                    <i class="fas fa-diagram-project text-warning me-2"></i> Monthly Planning &amp; Execution Lifecycle (<?= e($month) ?> <?= e($year) ?>)
                </h6>
                <small class="text-muted">Standard Kaldis Coffee enterprise operational workflow progress</small>
            </div>
            <span class="badge <?= $totActiveStrategiesThisMonth > 0 ? ($totMonthlyPlans > 0 ? 'bg-success' : 'bg-primary') : 'bg-warning text-dark' ?> px-3 py-2">
                <?= $totActiveStrategiesThisMonth > 0 ? ($totMonthlyPlans > 0 ? ($plannedTasks > 0 ? 'Phase 3: Execution Active' : 'Phase 2: Monthly Plans Active') : 'Phase 1: GM Activation Complete') : 'Phase 1: Pending GM Activation' ?>
            </span>
        </div>
        <div class="row g-3 text-center">
            <div class="col-md-3">
                <div class="p-3 border rounded <?= $totActiveStrategiesThisMonth > 0 ? 'bg-success-subtle border-success' : 'bg-light border-warning' ?>">
                    <div class="fw-bold <?= $totActiveStrategiesThisMonth > 0 ? 'text-success' : 'text-dark' ?> small mb-1">
                        <i class="fas fa-toggle-on me-1"></i> 1. GM Activation
                    </div>
                    <small class="text-muted d-block mb-2"><?= $totActiveStrategiesThisMonth ?> strategies active</small>
                    <a href="<?= url('/admin/monthly_activation.php') ?>" class="btn btn-xs btn-kaldis py-1 px-2">
                        <?= $totActiveStrategiesThisMonth > 0 ? 'Review & Activate' : 'Activate Strategies' ?>
                    </a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 border rounded <?= $totMonthlyPlans > 0 ? 'bg-success-subtle border-success' : 'bg-light' ?>">
                    <div class="fw-bold <?= $totMonthlyPlans > 0 ? 'text-success' : 'text-dark' ?> small mb-1">
                        <i class="fas fa-calendar-days me-1"></i> 2. Monthly Plans
                    </div>
                    <small class="text-muted d-block mb-2"><?= $totMonthlyPlans ?> plans created</small>
                    <a href="<?= url('/admin/monthly_plans.php') ?>" class="btn btn-xs btn-outline-secondary py-1 px-2">
                        View Department Plans
                    </a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 border rounded <?= $totWeeklyTasks > 0 ? 'bg-success-subtle border-success' : 'bg-light' ?>">
                    <div class="fw-bold <?= $totWeeklyTasks > 0 ? 'text-success' : 'text-dark' ?> small mb-1">
                        <i class="fas fa-list-check me-1"></i> 3. Weekly Breakdown
                    </div>
                    <small class="text-muted d-block mb-2"><?= $totWeeklyTasks ?> tasks planned</small>
                    <a href="<?= url('/performance/weekly.php') ?>" class="btn btn-xs btn-outline-secondary py-1 px-2">
                        Weekly Tasks Matrix
                    </a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 border rounded <?= $doneTasks > 0 ? 'bg-success-subtle border-success' : 'bg-light' ?>">
                    <div class="fw-bold <?= $doneTasks > 0 ? 'text-success' : 'text-dark' ?> small mb-1">
                        <i class="fas fa-chart-line me-1"></i> 4. Performance &amp; Reports
                    </div>
                    <small class="text-muted d-block mb-2"><?= $completionPct ?>% completion</small>
                    <a href="<?= url('/admin/reports.php') ?>" class="btn btn-xs btn-outline-secondary py-1 px-2">
                        Reports Compliance
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 1: PLANNING HIERARCHY OVERVIEW -->
<h5 class="fw-bold text-dark mb-3"><i class="fas fa-sitemap me-2 text-warning"></i> 1. Strategic Planning Funnel</h5>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card stat-gold">
            <i class="fas fa-bullseye stat-icon text-warning"></i>
            <div class="stat-label">Active Annual Goals</div>
            <div class="stat-value text-dark"><?= $totAnnualGoals ?></div>
            <small class="text-muted">Master organizational goals (<?= e($year) ?>)</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-done">
            <i class="fas fa-toggle-on stat-icon text-success"></i>
            <div class="stat-label">GM Activated This Month</div>
            <div class="stat-value text-success"><?= $totActiveStrategiesThisMonth ?></div>
            <small class="text-muted">Authorized for <?= e($month) ?> planning</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-primary">
            <i class="fas fa-calendar-days stat-icon text-primary"></i>
            <div class="stat-label">Monthly Plans</div>
            <div class="stat-value text-primary"><?= $totMonthlyPlans ?></div>
            <small class="text-muted">Strategy & Operational plans</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-gold">
            <i class="fas fa-tasks stat-icon text-warning"></i>
            <div class="stat-label">Weekly Tasks</div>
            <div class="stat-value text-dark"><?= $totWeeklyTasks ?></div>
            <small class="text-muted">Planned across 5 weeks</small>
        </div>
    </div>
</div>

<!-- SECTION 2: PERFORMANCE & SUBMISSION TRACKER -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header kaldis-header d-flex justify-content-between align-items-center">
                <span class="fs-6 fw-bold"><i class="fas fa-gauge-high me-2"></i> Monthly Execution Performance</span>
                <span class="badge bg-warning text-dark font-monospace"><?= $completionPct ?>% Overall</span>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-sm-7 border-end-sm">
                        <div class="row text-center mb-3">
                            <div class="col-4 border-end">
                                <div class="text-muted small fw-bold">PLANNED</div>
                                <div class="fs-3 fw-bold text-dark"><?= $plannedTasks ?></div>
                            </div>
                            <div class="col-4 border-end">
                                <div class="text-muted small fw-bold text-success">DONE</div>
                                <div class="fs-3 fw-bold text-success"><?= $doneTasks ?></div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted small fw-bold text-danger">NOT DONE</div>
                                <div class="fs-3 fw-bold text-danger"><?= $notDoneTasks ?></div>
                            </div>
                        </div>

                        <div class="progress mb-2" style="height: 20px;">
                            <div class="progress-bar bg-success fw-bold" role="progressbar" style="width: <?= $completionPct ?>%;">
                                <?= $completionPct ?>% DONE
                            </div>
                        </div>

                        <div class="small text-muted text-center" style="font-size: 0.75rem;">
                            <code>DONE Tasks &divide; Planned Tasks &times; 100</code>
                        </div>
                    </div>

                    <div class="col-sm-5 mt-3 mt-sm-0 text-center">
                        <div style="height: 130px; position: relative;">
                            <canvas id="monthlyExecChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header kaldis-header d-flex justify-content-between align-items-center">
                <span class="fs-6 fw-bold"><i class="fas fa-clock-rotate-left me-2"></i> Weekly Report Submission Status</span>
                <span class="badge bg-light text-dark">Deadline: Mon 12:00 PM</span>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-sm-7">
                        <div class="row text-center g-2 mb-2">
                            <div class="col-6">
                                <div class="p-2 border rounded bg-light">
                                    <div class="small text-info fw-bold">WAITING</div>
                                    <div class="fs-4 fw-bold text-dark"><?= (int)($subCounts['waiting'] ?? 0) ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 border rounded bg-light">
                                    <div class="small text-success fw-bold">ON TIME</div>
                                    <div class="fs-4 fw-bold text-success"><?= (int)($subCounts['on_time'] ?? 0) ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 border rounded bg-light">
                                    <div class="small text-warning fw-bold">LATE</div>
                                    <div class="fs-4 fw-bold text-warning"><?= (int)($subCounts['late'] ?? 0) ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 border rounded bg-light">
                                    <div class="small text-danger fw-bold">MISSING</div>
                                    <div class="fs-4 fw-bold text-danger"><?= (int)($subCounts['missing'] ?? 0) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-5 text-center">
                        <div style="height: 130px; position: relative;">
                            <canvas id="subDonutChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                    <span class="small text-muted"><i class="fab fa-telegram text-primary me-1"></i> Telegram Escalations Active</span>
                    <a href="<?= url('/performance/weekly.php') ?>" class="btn btn-sm btn-outline-dark">
                        Compliance Matrix &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 3: STRATEGIC & OPERATIONAL VISUAL ANALYTICS -->
<h5 class="fw-bold text-dark mb-3"><i class="fas fa-chart-pie me-2 text-warning"></i> 2. Operational Intelligence & Analytics</h5>
<div class="row g-4 mb-4">
    <!-- Reasons Bar Chart -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header kaldis-header d-flex justify-content-between align-items-center">
                <span class="fs-6 fw-bold"><i class="fas fa-triangle-exclamation me-2 text-danger"></i> Root Causes: Why Tasks Were Not Done</span>
                <a href="<?= url('/performance/not_done.php') ?>" class="small text-decoration-none text-primary fw-semibold">Deep Dive &rarr;</a>
            </div>
            <div class="card-body">
                <?php if (empty($topReasons)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-circle-check fa-3x text-success mb-2 opacity-50"></i>
                        <p class="mb-0">Excellent! No tasks marked as NOT DONE for <?= e($month) ?> <?= e($year) ?>.</p>
                    </div>
                <?php else: ?>
                    <div style="height: 220px; position: relative;">
                        <canvas id="reasonsBarChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Department Comparison Chart -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header kaldis-header d-flex justify-content-between align-items-center">
                <span class="fs-6 fw-bold"><i class="fas fa-chart-column me-2 text-warning"></i> Department Execution Performance Comparison</span>
                <span class="badge bg-light text-dark font-monospace"><?= count($deptExecData) ?> Depts</span>
            </div>
            <div class="card-body">
                <div style="height: 220px; position: relative;">
                    <canvas id="deptCompChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 4: RECENT ACHIEVEMENTS & BLOCKERS -->
<h5 class="fw-bold text-dark mb-3"><i class="fas fa-comments me-2 text-warning"></i> 3. Qualitative Weekly Updates</h5>
<div class="row g-4 mb-4">
    <!-- Achievements Column -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header kaldis-header d-flex justify-content-between align-items-center">
                <span class="fs-6 fw-bold"><i class="fas fa-award me-2 text-warning"></i> Recent Achievements (Company-Wide)</span>
                <a href="<?= url('/performance/achievements.php') ?>" class="small text-decoration-none text-primary fw-semibold">View All &rarr;</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (empty($recentAchievements)): ?>
                        <li class="list-group-item text-center py-4 text-muted small">No achievements recorded yet this month.</li>
                    <?php else: ?>
                        <?php foreach ($recentAchievements as $ach): ?>
                            <li class="list-group-item py-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge bg-dark font-monospace"><?= e($ach['department_code']) ?></span>
                                    <small class="text-muted">Week <?= $ach['week_number'] ?></small>
                                </div>
                                <div class="fw-semibold text-dark">
                                    <i class="fas fa-star text-warning me-1"></i> <?= e($ach['achievement_text']) ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Challenges & Top Not Done Reasons -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header kaldis-header d-flex justify-content-between align-items-center">
                <span class="fs-6 fw-bold"><i class="fas fa-triangle-exclamation me-2 text-danger"></i> Recent Challenges & Root-Causes</span>
                <a href="<?= url('/performance/challenges.php') ?>" class="small text-decoration-none text-primary fw-semibold">View All &rarr;</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (empty($recentChallenges)): ?>
                        <li class="list-group-item text-center py-4 text-muted small">No challenges logged this month.</li>
                    <?php else: ?>
                        <?php foreach ($recentChallenges as $ch): ?>
                            <li class="list-group-item py-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge bg-danger font-monospace"><?= e($ch['department_code']) ?></span>
                                    <small class="text-muted">Week <?= $ch['week_number'] ?></small>
                                </div>
                                <div class="fw-semibold text-danger">
                                    <i class="fas fa-circle-exclamation me-1"></i> <?= e($ch['challenge_text']) ?>
                                </div>
                                <?php if (!empty($ch['related_task_title'])): ?>
                                    <small class="text-muted d-block mt-1">Related Task: <?= e($ch['related_task_title']) ?></small>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Monthly Execution Donut Chart
    const execCtx = document.getElementById('monthlyExecChart');
    if (execCtx && typeof Chart !== 'undefined') {
        const hasExecData = <?= $plannedTasks > 0 ? 'true' : 'false' ?>;
        new Chart(execCtx, {
            type: 'doughnut',
            data: hasExecData ? {
                labels: ['DONE', 'NOT DONE', 'PENDING'],
                datasets: [{
                    data: [
                        <?= $doneTasks ?>,
                        <?= $notDoneTasks ?>,
                        <?= max(0, $plannedTasks - ($doneTasks + $notDoneTasks)) ?>
                    ],
                    backgroundColor: ['#198754', '#dc3545', '#ffc107'],
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            } : {
                labels: ['No Tasks Scheduled Yet'],
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
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, padding: 8, font: { size: 10 } }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // 2. Submission Status Donut Chart
    const subCtx = document.getElementById('subDonutChart');
    if (subCtx && typeof Chart !== 'undefined') {
        const subTotal = <?= (int)($subCounts['on_time'] ?? 0) + (int)($subCounts['late'] ?? 0) + (int)($subCounts['waiting'] ?? 0) + (int)($subCounts['missing'] ?? 0) ?>;
        new Chart(subCtx, {
            type: 'doughnut',
            data: subTotal > 0 ? {
                labels: ['ON TIME', 'LATE', 'WAITING', 'MISSING'],
                datasets: [{
                    data: [
                        <?= (int)($subCounts['on_time'] ?? 0) ?>,
                        <?= (int)($subCounts['late'] ?? 0) ?>,
                        <?= (int)($subCounts['waiting'] ?? 0) ?>,
                        <?= (int)($subCounts['missing'] ?? 0) ?>
                    ],
                    backgroundColor: ['#198754', '#fd7e14', '#0dcaf0', '#dc3545'],
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            } : {
                labels: ['Awaiting Submissions'],
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
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, padding: 8, font: { size: 10 } }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // 3. Reasons Bar Chart
    const reasonsCtx = document.getElementById('reasonsBarChart');
    if (reasonsCtx && typeof Chart !== 'undefined') {
        new Chart(reasonsCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($topReasons, 'reason_name')) ?>,
                datasets: [{
                    label: 'Uncompleted Tasks',
                    data: <?= json_encode(array_map('intval', array_column($topReasons, 'cnt'))) ?>,
                    backgroundColor: '#d97706',
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, precision: 0 }
                    }
                }
            }
        });
    }

    // 4. Department Comparison Chart
    const deptCtx = document.getElementById('deptCompChart');
    if (deptCtx && typeof Chart !== 'undefined') {
        new Chart(deptCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($deptExecData, 'department_code')) ?>,
                datasets: [
                    {
                        label: 'DONE',
                        data: <?= json_encode(array_map('intval', array_column($deptExecData, 'done_tasks'))) ?>,
                        backgroundColor: '#198754',
                        borderRadius: 4
                    },
                    {
                        label: 'NOT DONE',
                        data: <?= json_encode(array_map('intval', array_column($deptExecData, 'not_done_tasks'))) ?>,
                        backgroundColor: '#dc3545',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, padding: 10, font: { size: 11 } }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, precision: 0 }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

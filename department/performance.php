<?php
/**
 * Weekly Performance Execution & Reporting Screen
 * Kaldis Coffee PLC
 */

$pageTitle = 'Weekly Performance Execution';
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

// Fetch Department details
$deptStmt = $db->prepare("SELECT * FROM departments WHERE id = ?");
$deptStmt->execute([$deptId]);
$department = $deptStmt->fetch();

// Fetch Reason Categories for NOT DONE modal
$reasons = $db->query("SELECT id, name FROM reason_categories WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();

// Fetch Weekly Tasks with their execution results
$tasksStmt = $db->prepare("
    SELECT wt.*, ag.goal_code, mp.title as monthly_plan_title,
           wtr.id as result_id, wtr.result, wtr.completed_at, wtr.completed_by,
           wtr.not_done_reason_id, wtr.not_done_explanation, wtr.next_action, wtr.expected_completion_date,
           rc.name as reason_name, u.full_name as marked_by_name
    FROM weekly_tasks wt
    JOIN monthly_plans mp ON wt.monthly_plan_id = mp.id
    LEFT JOIN annual_goals ag ON wt.annual_goal_id = ag.id
    LEFT JOIN weekly_task_results wtr ON wt.id = wtr.weekly_task_id
    LEFT JOIN reason_categories rc ON wtr.not_done_reason_id = rc.id
    LEFT JOIN users u ON wtr.completed_by = u.id
    WHERE wt.department_id = ? AND wt.year = ? AND wt.month = ? AND wt.week_number = ?
    ORDER BY wt.plan_type ASC, wt.id ASC
");
$tasksStmt->execute([$deptId, $selectedYear, $selectedMonth, $selectedWeek]);
$weeklyTasks = $tasksStmt->fetchAll();

// Fetch Achievements for this week
$achStmt = $db->prepare("
    SELECT wa.*, u.full_name as author_name
    FROM weekly_achievements wa
    LEFT JOIN users u ON wa.created_by = u.id
    WHERE wa.department_id = ? AND wa.year = ? AND wa.month = ? AND wa.week_number = ?
    ORDER BY wa.created_at ASC
");
$achStmt->execute([$deptId, $selectedYear, $selectedMonth, $selectedWeek]);
$achievements = $achStmt->fetchAll();

// Fetch Challenges for this week
$chStmt = $db->prepare("
    SELECT wc.*, wt.task_title as related_task_title, u.full_name as author_name
    FROM weekly_challenges wc
    LEFT JOIN weekly_tasks wt ON wc.related_weekly_task_id = wt.id
    LEFT JOIN users u ON wc.created_by = u.id
    WHERE wc.department_id = ? AND wc.year = ? AND wc.month = ? AND wc.week_number = ?
    ORDER BY wc.created_at ASC
");
$chStmt->execute([$deptId, $selectedYear, $selectedMonth, $selectedWeek]);
$challenges = $chStmt->fetchAll();

// Fetch Report Submission Status
$reportStmt = $db->prepare("
    SELECT wr.*, u.full_name as submitter_name
    FROM weekly_reports wr
    LEFT JOIN users u ON wr.submitted_by = u.id
    WHERE wr.department_id = ? AND wr.year = ? AND wr.month = ? AND wr.week_number = ?
");
$reportStmt->execute([$deptId, $selectedYear, $selectedMonth, $selectedWeek]);
$weeklyReport = $reportStmt->fetch();

// Fetch Submission Tracker record
$subStmt = $db->prepare("
    SELECT * FROM submissions 
    WHERE department_id = ? AND year = ? AND month = ? AND week_number = ?
");
$subStmt->execute([$deptId, $selectedYear, $selectedMonth, $selectedWeek]);
$submissionRecord = $subStmt->fetch();

// Performance Calculations
$totalPlanned = count($weeklyTasks);
$doneCount = 0;
$notDoneCount = 0;
$unratedCount = 0;

foreach ($weeklyTasks as $t) {
    if ($t['result'] === 'DONE') {
        $doneCount++;
    } elseif ($t['result'] === 'NOT_DONE') {
        $notDoneCount++;
    } else {
        $unratedCount++;
    }
}

$completionPct = $totalPlanned > 0 ? round(($doneCount / $totalPlanned) * 100, 1) : 0;
$isSubmitted = ($weeklyReport && $weeklyReport['status'] === 'SUBMITTED');
$canSubmit = ($totalPlanned > 0 && $unratedCount === 0);
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="mb-1 fw-bold text-dark">
            <i class="fas fa-clipboard-check text-success me-2"></i> Weekly Department Performance
        </h3>
        <p class="text-muted mb-0">
            <?= e($department['department_name'] ?? 'Department') ?> &mdash; Week <?= $selectedWeek ?> (<?= e($selectedMonth) ?> <?= e($selectedYear) ?>)
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/department/weekly_plans.php?year=' . urlencode($selectedYear) . '&month=' . urlencode($selectedMonth) . '&week=' . $selectedWeek . '&department_id=' . $deptId) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-list-check me-1"></i> Edit Planned Tasks
        </a>
        <button class="btn btn-outline-dark" onclick="window.print()">
            <i class="fas fa-print me-1"></i> Print Report
        </button>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar mb-4">
    <form method="GET" action="performance.php" class="row g-3 align-items-end">
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
                <i class="fas fa-filter me-1"></i> Change Period
            </button>
        </div>
    </form>
</div>

<!-- Week Tabs -->
<ul class="nav nav-tabs mb-4">
    <?php foreach ($weeks as $wkNum => $wkLabel): ?>
        <li class="nav-item">
            <a class="nav-link fw-bold <?= $selectedWeek === $wkNum ? 'active text-success bg-white border-bottom-0' : 'text-dark' ?>" 
               href="performance.php?year=<?= urlencode($selectedYear) ?>&month=<?= urlencode($selectedMonth) ?>&department_id=<?= $deptId ?>&week=<?= $wkNum ?>">
                <i class="far fa-calendar-check me-1"></i> <?= e($wkLabel) ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<!-- Executive KPI Banner -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card stat-primary">
            <i class="fas fa-list-check stat-icon text-primary"></i>
            <div class="stat-label">Planned Tasks</div>
            <div class="stat-value text-primary"><?= $totalPlanned ?></div>
            <small class="text-muted">Total tasks committed</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-done">
            <i class="fas fa-check-circle stat-icon text-success"></i>
            <div class="stat-label">DONE Tasks</div>
            <div class="stat-value text-success"><?= $doneCount ?></div>
            <small class="text-muted">Successfully completed</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-notdone">
            <i class="fas fa-times-circle stat-icon text-danger"></i>
            <div class="stat-label">NOT DONE Tasks</div>
            <div class="stat-value text-danger"><?= $notDoneCount ?></div>
            <small class="text-muted">Requires explanation</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-gold">
            <i class="fas fa-percent stat-icon text-warning"></i>
            <div class="stat-label">Completion %</div>
            <div class="stat-value text-dark"><?= $completionPct ?>%</div>
            <div class="progress mt-2" style="height: 6px;">
                <div class="progress-bar bg-success" style="width: <?= $completionPct ?>%;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Submission Status Alert -->
<?php if ($isSubmitted): ?>
    <div class="alert alert-success d-flex align-items-center justify-content-between mb-4 shadow-sm bg-white border-success">
        <div>
            <i class="fas fa-circle-check fa-2x text-success me-3"></i>
            <span>
                <strong>Weekly Report Submitted!</strong> Status: 
                <?= render_submission_badge($weeklyReport['submission_status']) ?> &bull; 
                Submitted on <strong><?= date('M d, Y H:i', strtotime($weeklyReport['submitted_at'])) ?></strong> by 
                <strong><?= e($weeklyReport['submitter_name'] ?? 'Staff') ?></strong>.
            </span>
        </div>
        <span class="badge bg-dark fs-6 font-monospace"><?= $weeklyReport['completion_percentage'] ?>% Final Score</span>
    </div>
<?php elseif ($totalPlanned === 0): ?>
    <div class="alert alert-info border-info d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4 shadow-sm bg-white gap-3">
        <div class="d-flex align-items-center">
            <i class="fas fa-info-circle fa-2x text-info me-3"></i>
            <div>
                <strong>No Tasks Planned for Week <?= $selectedWeek ?>:</strong> You have not scheduled weekly deliverables for this period yet.
                <div class="small text-muted mt-1">Break down your Monthly Plan into weekly tasks to evaluate and submit your performance.</div>
            </div>
        </div>
        <a href="weekly_plans.php?year=<?= urlencode($selectedYear) ?>&month=<?= urlencode($selectedMonth) ?>&week=<?= $selectedWeek ?>&department_id=<?= (int)$deptId ?>" class="btn btn-sm btn-kaldis text-nowrap">
            <i class="fas fa-plus me-1"></i> Plan Tasks for Week <?= $selectedWeek ?> &rarr;
        </a>
    </div>
<?php else: ?>
    <div class="alert alert-warning d-flex align-items-center justify-content-between mb-4 shadow-sm bg-white border-warning">
        <div>
            <i class="fas fa-clock fa-2x text-warning me-3"></i>
            <span>
                <strong>Draft Mode:</strong> Weekly performance is not submitted yet. All planned tasks must be evaluated before final submission.
            </span>
        </div>
        <span class="badge bg-warning text-dark font-monospace"><?= $unratedCount ?> Task(s) Remaining</span>
    </div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- SECTION A — WEEKLY PLAN / TASK PERFORMANCE                                -->
<!-- ========================================================================= -->
<div class="card mb-4">
    <div class="card-header kaldis-header d-flex justify-content-between align-items-center">
        <span class="fs-6 fw-bold text-uppercase">
            <i class="fas fa-clipboard-list me-2"></i> Section A &mdash; Weekly Plan / Task Performance
        </span>
        <span class="badge bg-light text-dark">
            Binary Evaluation: DONE / NOT DONE Only
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-kaldis mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 100px;">Plan Type</th>
                    <th>Weekly Task & Expected Result</th>
                    <th style="width: 140px;">Responsible</th>
                    <th style="width: 150px;" class="text-center">Official Result</th>
                    <th>Why Not Done / Explanation</th>
                    <th style="width: 160px;">Next Action</th>
                    <th style="width: 150px;" class="text-center no-print">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($weeklyTasks)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-tasks fa-3x mb-3 d-block opacity-25"></i>
                            No tasks planned for Week <?= $selectedWeek ?>. 
                            <a href="weekly_plans.php?year=<?= urlencode($selectedYear) ?>&month=<?= urlencode($selectedMonth) ?>&week=<?= $selectedWeek ?>&department_id=<?= $deptId ?>" class="fw-bold">
                                Create Weekly Plan &rarr;
                            </a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($weeklyTasks as $task): ?>
                        <tr id="task-row-<?= $task['id'] ?>">
                            <td>
                                <?= render_plan_type_badge($task['plan_type']) ?>
                                <?php if ($task['plan_type'] === 'STRATEGY' && !empty($task['goal_code'])): ?>
                                    <div class="small font-monospace fw-bold text-muted mt-1"><?= e($task['goal_code']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($task['task_title']) ?></div>
                                <small class="text-muted"><strong>Deliverable:</strong> <?= e($task['expected_result']) ?></small>
                            </td>
                            <td>
                                <small class="fw-semibold text-secondary"><?= e($task['responsible_person']) ?></small>
                            </td>
                            <td class="text-center" id="badge-col-<?= $task['id'] ?>">
                                <?= render_task_badge($task['result']) ?>
                            </td>
                            <td id="reason-col-<?= $task['id'] ?>">
                                <?php if ($task['result'] === 'NOT_DONE'): ?>
                                    <div><span class="badge bg-danger text-uppercase mb-1"><?= e($task['reason_name'] ?? 'Unspecified') ?></span></div>
                                    <small class="text-dark"><strong>Reason:</strong> <?= e($task['not_done_explanation']) ?></small>
                                <?php elseif ($task['result'] === 'DONE'): ?>
                                    <small class="text-success"><i class="fas fa-check me-1"></i> Completed successfully</small>
                                <?php else: ?>
                                    <span class="text-muted small">Not evaluated</span>
                                <?php endif; ?>
                            </td>
                            <td id="nextaction-col-<?= $task['id'] ?>">
                                <?php if ($task['result'] === 'NOT_DONE' && !empty($task['next_action'])): ?>
                                    <small class="text-muted"><i class="fas fa-forward me-1"></i> <?= e($task['next_action']) ?></small>
                                    <?php if (!empty($task['expected_completion_date'])): ?>
                                        <div class="small text-secondary">Target: <?= date('M d', strtotime($task['expected_completion_date'])) ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center no-print">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn <?= $task['result'] === 'DONE' ? 'btn-success' : 'btn-outline-success' ?>" 
                                            onclick="markTaskDone(<?= $task['id'] ?>)" title="Mark Task DONE">
                                        <i class="fas fa-check me-1"></i> DONE
                                    </button>
                                    <button type="button" class="btn <?= $task['result'] === 'NOT_DONE' ? 'btn-danger' : 'btn-outline-danger' ?>" 
                                            onclick="openNotDoneModal(<?= htmlspecialchars(json_encode($task), ENT_QUOTES, 'UTF-8') ?>)" title="Mark Task NOT DONE">
                                        <i class="fas fa-times me-1"></i> NOT DONE
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- ========================================================================= -->
    <!-- SECTION B — ACHIEVEMENTS                                                  -->
    <!-- ========================================================================= -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header kaldis-header d-flex justify-content-between align-items-center">
                <span class="fs-6 fw-bold text-uppercase">
                    <i class="fas fa-award me-2 text-warning"></i> Section B &mdash; Weekly Achievements
                </span>
                <span class="badge bg-warning text-dark font-monospace"><?= count($achievements) ?> Added</span>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">
                    Record specific accomplishments achieved by the department this week. (Independent from task completion).
                </p>

                <!-- Add Achievement Form -->
                <form id="achievementForm" class="mb-3">
                    <div class="input-group">
                        <input type="text" id="achievementText" class="form-control" placeholder="e.g. Completed preventive maintenance for 8 branches..." required>
                        <button type="button" class="btn btn-kaldis" onclick="submitAchievement()">
                            <i class="fas fa-plus me-1"></i> Add
                        </button>
                    </div>
                </form>

                <!-- List of Achievements -->
                <div id="achievementsList">
                    <?php if (empty($achievements)): ?>
                        <div class="text-center py-4 text-muted small empty-notice">
                            <i class="fas fa-trophy fa-2x mb-2 d-block opacity-25"></i>
                            No weekly achievements added yet.
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($achievements as $ach): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2" id="ach-item-<?= $ach['id'] ?>">
                                    <div>
                                        <i class="fas fa-star text-warning me-2"></i>
                                        <span class="fw-semibold text-dark"><?= e($ach['achievement_text']) ?></span>
                                        <div class="small text-muted ms-4">Added by <?= e($ach['author_name'] ?? 'Staff') ?></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2 no-print" onclick="deleteAchievement(<?= $ach['id'] ?>)">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- SECTION C — CHALLENGES                                                    -->
    <!-- ========================================================================= -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header kaldis-header d-flex justify-content-between align-items-center">
                <span class="fs-6 fw-bold text-uppercase">
                    <i class="fas fa-triangle-exclamation me-2 text-warning"></i> Section C &mdash; Weekly Challenges
                </span>
                <span class="badge bg-warning text-dark font-monospace"><?= count($challenges) ?> Added</span>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">
                    Record blockers, hurdles, or resource constraints. (A challenge does NOT automatically mean a task was NOT DONE).
                </p>

                <!-- Add Challenge Form -->
                <form id="challengeForm" class="mb-3">
                    <div class="mb-2">
                        <input type="text" id="challengeText" class="form-control" placeholder="e.g. Hardware PM affected by spare part availability..." required>
                    </div>
                    <div class="row g-2 align-items-center">
                        <div class="col-md-8">
                            <select id="challengeRelatedTask" class="form-select form-select-sm">
                                <option value="">-- Optionally Link to Planned Task --</option>
                                <?php foreach ($weeklyTasks as $t): ?>
                                    <option value="<?= $t['id'] ?>"><?= e($t['task_title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-kaldis-dark btn-sm w-100" onclick="submitChallenge()">
                                <i class="fas fa-plus me-1"></i> Add Challenge
                            </button>
                        </div>
                    </div>
                </form>

                <!-- List of Challenges -->
                <div id="challengesList">
                    <?php if (empty($challenges)): ?>
                        <div class="text-center py-4 text-muted small empty-notice">
                            <i class="fas fa-shield-cat fa-2x mb-2 d-block opacity-25"></i>
                            No challenges recorded for this week.
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($challenges as $ch): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-start px-0 py-2" id="ch-item-<?= $ch['id'] ?>">
                                    <div>
                                        <i class="fas fa-circle-exclamation text-danger me-2"></i>
                                        <span class="fw-semibold text-dark"><?= e($ch['challenge_text']) ?></span>
                                        <?php if (!empty($ch['related_task_title'])): ?>
                                            <div class="small text-primary ms-4">
                                                <i class="fas fa-link me-1"></i> Related Task: <?= e($ch['related_task_title']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="small text-muted ms-4">Logged by <?= e($ch['author_name'] ?? 'Staff') ?></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2 no-print" onclick="deleteChallenge(<?= $ch['id'] ?>)">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- SECTION D — FINAL SUBMISSION PANEL                                        -->
<!-- ========================================================================= -->
<div class="card border-success shadow-sm">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <span class="fs-6 fw-bold text-uppercase">
            <i class="fas fa-paper-plane me-2"></i> Weekly Performance Final Submission
        </span>
        <span class="badge bg-light text-success font-monospace">Deadline: Monday 12:00 PM</span>
    </div>
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h5 class="fw-bold mb-1">Ready to Submit Week <?= $selectedWeek ?> Performance Report?</h5>
                <p class="text-muted small mb-2">
                    Once submitted, the official performance result (<?= $completionPct ?>% completion) is calculated and locked for executive GM and HR reporting.
                </p>
                <div class="d-flex gap-3 text-secondary small">
                    <div><i class="fas fa-check-circle text-success me-1"></i> <?= $doneCount ?> DONE</div>
                    <div><i class="fas fa-times-circle text-danger me-1"></i> <?= $notDoneCount ?> NOT DONE</div>
                    <div><i class="fas fa-hourglass-start text-warning me-1"></i> <?= $unratedCount ?> Unrated</div>
                    <div><i class="fas fa-award text-warning me-1"></i> <?= count($achievements) ?> Achievements</div>
                    <div><i class="fas fa-triangle-exclamation text-danger me-1"></i> <?= count($challenges) ?> Challenges</div>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <div class="mb-2">
                    <input type="text" id="submissionNotes" class="form-control form-control-sm" placeholder="Optional submission remarks for GM...">
                </div>
                <button type="button" id="btnSubmitReport" class="btn btn-success btn-lg w-100 fw-bold" 
                        <?= !$canSubmit ? 'disabled' : '' ?> onclick="submitWeeklyReport()">
                    <i class="fas fa-cloud-upload-alt me-2"></i> Submit Weekly Report
                </button>
                <?php if ($totalPlanned === 0): ?>
                    <small class="text-muted d-block mt-1">
                        * Please schedule weekly tasks before submitting a performance report.
                    </small>
                <?php elseif (!$canSubmit): ?>
                    <small class="text-danger d-block mt-1">
                        * All <?= $totalPlanned ?> planned tasks must be rated DONE or NOT DONE before submitting (<?= $unratedCount ?> remaining).
                    </small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: WHY NOT DONE REASON & EXPLANATION                                  -->
<!-- ========================================================================= -->
<div class="modal fade" id="notDoneModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="notDoneForm" onsubmit="submitNotDone(event)">
                <input type="hidden" id="notDoneTaskId">
                
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-circle-exclamation me-2"></i> Mandatory "Why Not Done?" Justification
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert alert-warning small mb-3">
                        <i class="fas fa-info-circle me-1"></i> <strong>Mandatory Rule:</strong> The official performance result will be <strong>NOT DONE</strong>. Management requires the reason category and detailed explanation to remove blockers and assist the department.
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">TASK</label>
                        <div id="notDoneTaskTitleDisplay" class="fw-bold text-dark fs-6"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-danger">WHY NOT DONE? (REASON CATEGORY) <span class="text-danger">*</span></label>
                        <select id="notDoneReasonId" class="form-select" required>
                            <option value="">-- Select Mandatory Reason Category --</option>
                            <?php foreach ($reasons as $r): ?>
                                <option value="<?= (int)$r['id'] ?>"><?= e($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-danger">EXPLANATION <span class="text-danger">*</span></label>
                        <textarea id="notDoneExplanation" class="form-control" rows="3" 
                                  placeholder="Provide clear root-cause details (e.g. 'Required vendor quotation was delayed due to public holidays')..." required></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-bold text-dark">NEXT ACTION</label>
                            <input type="text" id="notDoneNextAction" class="form-control" placeholder="e.g. Escalate to procurement manager on Tuesday morning">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-dark">EXPECTED COMPLETION DATE</label>
                            <input type="date" id="notDoneExpectedDate" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold">
                        <i class="fas fa-save me-1"></i> Save as NOT DONE
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const DEPT_ID = <?= (int)$deptId ?>;
const YEAR = '<?= e($selectedYear) ?>';
const MONTH = '<?= e($selectedMonth) ?>';
const WEEK = <?= (int)$selectedWeek ?>;

let notDoneModalInstance = null;
document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('notDoneModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        notDoneModalInstance = new bootstrap.Modal(modalEl);
    }
});

// Mark Task DONE
function markTaskDone(taskId) {
    const formData = new FormData();
    formData.append('action', 'save_task_result');
    formData.append('weekly_task_id', taskId);
    formData.append('result', 'DONE');
    formData.append('csrf_token', CSRF_TOKEN);

    fetch(window.APP_BASE_URL + '/api/performance.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Task marked as DONE', 'success');
            // Update row UI
            document.getElementById(`badge-col-${taskId}`).innerHTML = '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> DONE</span>';
            document.getElementById(`reason-col-${taskId}`).innerHTML = '<small class="text-success"><i class="fas fa-check me-1"></i> Completed successfully</small>';
            document.getElementById(`nextaction-col-${taskId}`).innerHTML = '<span class="text-muted">&mdash;</span>';
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message || 'Error saving result', 'error');
        }
    })
    .catch(() => showToast('Network error saving task result', 'error'));
}

// Open Not Done Modal
function openNotDoneModal(task) {
    document.getElementById('notDoneTaskId').value = task.id;
    document.getElementById('notDoneTaskTitleDisplay').innerText = task.task_title;
    document.getElementById('notDoneReasonId').value = task.not_done_reason_id || '';
    document.getElementById('notDoneExplanation').value = task.not_done_explanation || '';
    document.getElementById('notDoneNextAction').value = task.next_action || '';
    document.getElementById('notDoneExpectedDate').value = task.expected_completion_date || '';

    if (notDoneModalInstance) notDoneModalInstance.show();
}

// Submit NOT DONE form
function submitNotDone(e) {
    e.preventDefault();
    const taskId = document.getElementById('notDoneTaskId').value;
    const reasonId = document.getElementById('notDoneReasonId').value;
    const explanation = document.getElementById('notDoneExplanation').value.trim();
    const nextAction = document.getElementById('notDoneNextAction').value.trim();
    const expectedDate = document.getElementById('notDoneExpectedDate').value;

    if (!reasonId || !explanation) {
        alert('Reason category and explanation are mandatory for NOT DONE tasks.');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'save_task_result');
    formData.append('weekly_task_id', taskId);
    formData.append('result', 'NOT_DONE');
    formData.append('not_done_reason_id', reasonId);
    formData.append('not_done_explanation', explanation);
    formData.append('next_action', nextAction);
    formData.append('expected_completion_date', expectedDate);
    formData.append('csrf_token', CSRF_TOKEN);

    fetch(window.APP_BASE_URL + '/api/performance.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (notDoneModalInstance) notDoneModalInstance.hide();
            showToast('Task marked as NOT DONE with explanation', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            alert(data.message || 'Error saving NOT DONE');
        }
    })
    .catch(() => alert('Network error'));
}

// Add Achievement
function submitAchievement() {
    const text = document.getElementById('achievementText').value.trim();
    if (!text) {
        alert('Please enter achievement text.');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'add_achievement');
    formData.append('department_id', DEPT_ID);
    formData.append('year', YEAR);
    formData.append('month', MONTH);
    formData.append('week_number', WEEK);
    formData.append('achievement_text', text);
    formData.append('csrf_token', CSRF_TOKEN);

    fetch(window.APP_BASE_URL + '/api/performance.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Achievement recorded!', 'success');
            setTimeout(() => location.reload(), 600);
        } else {
            alert(data.message || 'Error adding achievement');
        }
    });
}

// Delete Achievement
function deleteAchievement(id) {
    if (!confirm('Delete this achievement?')) return;
    const formData = new FormData();
    formData.append('action', 'delete_achievement');
    formData.append('achievement_id', id);
    formData.append('csrf_token', CSRF_TOKEN);

    fetch(window.APP_BASE_URL + '/api/performance.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById(`ach-item-${id}`)?.remove();
            showToast('Achievement removed', 'success');
        }
    });
}

// Add Challenge
function submitChallenge() {
    const text = document.getElementById('challengeText').value.trim();
    const relatedTask = document.getElementById('challengeRelatedTask').value;
    if (!text) {
        alert('Please enter challenge description.');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'add_challenge');
    formData.append('department_id', DEPT_ID);
    formData.append('year', YEAR);
    formData.append('month', MONTH);
    formData.append('week_number', WEEK);
    formData.append('challenge_text', text);
    formData.append('related_weekly_task_id', relatedTask);
    formData.append('csrf_token', CSRF_TOKEN);

    fetch(window.APP_BASE_URL + '/api/performance.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Challenge recorded!', 'success');
            setTimeout(() => location.reload(), 600);
        } else {
            alert(data.message || 'Error adding challenge');
        }
    });
}

// Delete Challenge
function deleteChallenge(id) {
    if (!confirm('Delete this challenge?')) return;
    const formData = new FormData();
    formData.append('action', 'delete_challenge');
    formData.append('challenge_id', id);
    formData.append('csrf_token', CSRF_TOKEN);

    fetch(window.APP_BASE_URL + '/api/performance.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById(`ch-item-${id}`)?.remove();
            showToast('Challenge removed', 'success');
        }
    });
}

// Final Weekly Report Submission
function submitWeeklyReport() {
    if (!confirm('Are you sure you want to officially submit this weekly report to General Management?')) {
        return;
    }

    const notes = document.getElementById('submissionNotes').value.trim();
    const btn = document.getElementById('btnSubmitReport');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Submitting...';

    const formData = new FormData();
    formData.append('department_id', DEPT_ID);
    formData.append('year', YEAR);
    formData.append('month', MONTH);
    formData.append('week_number', WEEK);
    formData.append('submission_notes', notes);
    formData.append('csrf_token', CSRF_TOKEN);

    fetch(window.APP_BASE_URL + '/api/submission.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(`Report Submitted Successfully!\nCompletion: ${data.completion_percentage}%\nStatus: ${data.submission_status}`);
            location.reload();
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-cloud-upload-alt me-2"></i> Submit Weekly Report';
            alert(data.message || 'Submission failed');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-cloud-upload-alt me-2"></i> Submit Weekly Report';
        alert('Network error submitting report');
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

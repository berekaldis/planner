<?php
/**
 * Official Weekly Performance Voucher - Vanilla PHP
 * Kaldis Coffee PLC
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/csrf.php';

$user = Auth::user();
$userId = Auth::id();
$db = Database::getConnection();

$deptId = Permissions::getActiveDepartmentId(!empty($_GET['department_id']) ? (int)$_GET['department_id'] : null);
if (!$deptId) {
    $firstDept = $db->query("SELECT id FROM departments WHERE active = 1 ORDER BY id ASC LIMIT 1")->fetch();
    $deptId = $firstDept['id'] ?? 1;
}

Permissions::requireDepartmentAccess($deptId);

$selectedYear = $_GET['year'] ?? app_config('current_planning_year', '2019 E.C.');
$selectedMonth = $_GET['month'] ?? app_config('current_planning_month', 'Meskerem');
$selectedWeek = !empty($_GET['week']) ? (int)$_GET['week'] : 1;

// Handle GM Feedback & Approval submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_feedback') {
    require_csrf();
    if (!Permissions::isGM() && !Permissions::isSuperAdmin()) {
        set_flash('danger', 'Only General Management can submit executive review comments.');
        redirect($_SERVER['REQUEST_URI']);
    }

    $approvalStatus = in_array($_POST['approval_status'] ?? '', ['APPROVED', 'PENDING', 'REVISION_REQUESTED']) ? $_POST['approval_status'] : 'PENDING';
    $gmFeedback = trim($_POST['gm_feedback'] ?? '');
    $now = date('Y-m-d H:i:s');

    // Update submissions table
    $updateSub = $db->prepare("
        UPDATE submissions 
        SET approval_status = ?, gm_feedback = ?, gm_feedback_at = ?, updated_at = ?
        WHERE department_id = ? AND year = ? AND month = ? AND week_number = ?
    ");
    $updateSub->execute([$approvalStatus, $gmFeedback, $now, $now, $deptId, $selectedYear, $selectedMonth, $selectedWeek]);

    // Update weekly_reports table
    $updateRep = $db->prepare("
        UPDATE weekly_reports 
        SET approval_status = ?, gm_feedback = ?, gm_feedback_at = ?, updated_at = ?
        WHERE department_id = ? AND year = ? AND month = ? AND week_number = ?
    ");
    $updateRep->execute([$approvalStatus, $gmFeedback, $now, $now, $deptId, $selectedYear, $selectedMonth, $selectedWeek]);

    audit_log($userId, 'GM_FEEDBACK_WEEKLY', 'submissions', "{$deptId}-{$selectedYear}-{$selectedMonth}-W{$selectedWeek}", null, [
        'approval_status' => $approvalStatus,
        'has_feedback' => !empty($gmFeedback)
    ]);

    set_flash('success', 'General Manager review comments and approval status saved successfully.');
    redirect($_SERVER['REQUEST_URI']);
}

// Fetch Department Details
$deptStmt = $db->prepare("SELECT * FROM departments WHERE id = ?");
$deptStmt->execute([$deptId]);
$department = $deptStmt->fetch();

// Fetch Submission Record
$subStmt = $db->prepare("
    SELECT s.*, u.full_name as submitter_name
    FROM submissions s
    LEFT JOIN users u ON s.submitted_by = u.id
    WHERE s.department_id = ? AND s.year = ? AND s.month = ? AND s.week_number = ?
");
$subStmt->execute([$deptId, $selectedYear, $selectedMonth, $selectedWeek]);
$submission = $subStmt->fetch();

// Fetch Weekly Report Record
$repStmt = $db->prepare("
    SELECT wr.*, u.full_name as submitter_name
    FROM weekly_reports wr
    LEFT JOIN users u ON wr.submitted_by = u.id
    WHERE wr.department_id = ? AND wr.year = ? AND wr.month = ? AND wr.week_number = ?
");
$repStmt->execute([$deptId, $selectedYear, $selectedMonth, $selectedWeek]);
$weeklyReport = $repStmt->fetch();

// Merge feedback from either record
$gmFeedback = $submission['gm_feedback'] ?? ($weeklyReport['gm_feedback'] ?? '');
$gmFeedbackAt = $submission['gm_feedback_at'] ?? ($weeklyReport['gm_feedback_at'] ?? null);
$approvalStatus = $submission['approval_status'] ?? ($weeklyReport['approval_status'] ?? 'PENDING');

// Fetch Weekly Tasks with Results
$tasksStmt = $db->prepare("
    SELECT wt.*, mp.title as monthly_plan_title, ag.goal_code,
           wtr.result, wtr.not_done_explanation, wtr.next_action, rc.name as reason_category_name
    FROM weekly_tasks wt
    LEFT JOIN monthly_plans mp ON wt.monthly_plan_id = mp.id
    LEFT JOIN annual_goals ag ON wt.annual_goal_id = ag.id
    LEFT JOIN weekly_task_results wtr ON wt.id = wtr.weekly_task_id
    LEFT JOIN reason_categories rc ON wtr.not_done_reason_id = rc.id
    WHERE wt.department_id = ? AND wt.year = ? AND wt.month = ? AND wt.week_number = ?
    ORDER BY wt.plan_type ASC, wt.id ASC
");
$tasksStmt->execute([$deptId, $selectedYear, $selectedMonth, $selectedWeek]);
$tasks = $tasksStmt->fetchAll();

// Fetch Achievements
$achStmt = $db->prepare("
    SELECT * FROM weekly_achievements 
    WHERE department_id = ? AND year = ? AND month = ? AND week_number = ?
    ORDER BY id ASC
");
$achStmt->execute([$deptId, $selectedYear, $selectedMonth, $selectedWeek]);
$achievements = $achStmt->fetchAll();

// Fetch Challenges
$chStmt = $db->prepare("
    SELECT * FROM weekly_challenges 
    WHERE department_id = ? AND year = ? AND month = ? AND week_number = ?
    ORDER BY id ASC
");
$chStmt->execute([$deptId, $selectedYear, $selectedMonth, $selectedWeek]);
$challenges = $chStmt->fetchAll();

// Calculations
$totalTasks = count($tasks);
$doneTasks = 0;
$notDoneTasks = 0;
foreach ($tasks as $t) {
    if ($t['result'] === 'DONE') $doneTasks++;
    elseif ($t['result'] === 'NOT_DONE') $notDoneTasks++;
}
$completionRate = $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100, 1) : 0;

// Determine timing status
$status = 'NOT_SUBMITTED';
if ($submission) {
    $status = in_array($submission['status'], ['ON_TIME', 'LATE']) ? $submission['status'] : ($submission['status'] === 'WAITING' ? 'NOT_SUBMITTED' : 'ON_TIME');
} elseif ($weeklyReport) {
    $status = in_array($weeklyReport['submission_status'] ?? '', ['ON_TIME', 'LATE']) ? $weeklyReport['submission_status'] : 'ON_TIME';
}

$cleanYear = preg_replace('/[^A-Za-z0-9]/', '', $selectedYear);
$voucherRef = sprintf('VCH-WPR-%s-%s-%s-W%d', $department['department_code'] ?? 'GEN', $cleanYear, strtoupper(substr($selectedMonth, 0, 3)), $selectedWeek);

$pageTitle = 'Weekly Performance Voucher - ' . ($department['department_name'] ?? 'Department');
require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ══════════════════════════════════════════════════════════
   OFFICIAL VOUCHER STYLING (KALDIS COFFEE PLC)
   ══════════════════════════════════════════════════════════ */
.voucher-wrapper {
    max-width: 1080px;
    margin: 0 auto 3rem auto;
}

.voucher-card {
    background: #ffffff;
    border: 2px solid #D1D5DB;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
    position: relative;
    overflow: hidden;
}

.voucher-header {
    background: linear-gradient(135deg, #0A1128 0%, #1C2541 100%);
    color: #ffffff;
    padding: 2rem 2.5rem;
    border-bottom: 4px solid #D97706;
}

.voucher-brand-logo {
    width: 68px;
    height: 68px;
    object-fit: contain;
    border-radius: 50%;
    border: 3px solid #F59E0B;
    background: #ffffff;
    padding: 4px;
}

.voucher-ref-badge {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.25);
    padding: 0.35rem 0.85rem;
    border-radius: 6px;
    font-family: monospace;
    font-size: 0.9rem;
    letter-spacing: 0.05em;
}

.voucher-status-stamp {
    display: inline-block;
    padding: 0.5rem 1.25rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    border-radius: 8px;
    font-size: 0.85rem;
    border: 2px solid currentColor;
}
.stamp-ontime {
    color: #059669;
    background: #ECFDF5;
    border-color: #10B981;
}
.stamp-late {
    color: #D97706;
    background: #FFFBEB;
    border-color: #F59E0B;
}
.stamp-notsubmitted {
    color: #DC2626;
    background: #FEF2F2;
    border-color: #EF4444;
}

.voucher-meta-box {
    background: #F9FAFB;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    padding: 1.25rem 1.5rem;
    margin: 1.75rem 2rem 1rem 2rem;
}

.voucher-kpi-card {
    background: #ffffff;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
}

.voucher-table {
    border: 1px solid #E5E7EB;
    width: 100%;
    margin-bottom: 1.5rem;
}
.voucher-table th {
    background: #F3F4F6;
    color: #374151;
    font-weight: 700;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 0.65rem 0.85rem;
    border: 1px solid #E5E7EB;
}
.voucher-table td {
    padding: 0.75rem 0.85rem;
    border: 1px solid #E5E7EB;
    font-size: 0.85rem;
    vertical-align: top;
}

.voucher-sign-block {
    margin: 2rem;
    padding-top: 1.5rem;
    border-top: 2px dashed #D1D5DB;
}

.signature-line {
    border-top: 1.5px solid #4B5563;
    margin-top: 2.5rem;
    padding-top: 0.4rem;
    text-align: center;
    font-size: 0.82rem;
    font-weight: 600;
    color: #374151;
}

@media print {
    body {
        background: #ffffff !important;
        color: #000000 !important;
        font-size: 11pt !important;
    }
    .navbar, .sidebar, .btn, .no-print, header, footer {
        display: none !important;
    }
    .voucher-wrapper {
        max-width: 100% !important;
        margin: 0 !important;
    }
    .voucher-card {
        border: none !important;
        box-shadow: none !important;
    }
    .voucher-header {
        background: #1C2541 !important;
        color: #ffffff !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>

<div class="voucher-wrapper">
    <!-- Top Action Bar (No Print) -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <a href="<?= url('/department/performance.php?department_id=' . $deptId . '&year=' . urlencode($selectedYear) . '&month=' . urlencode($selectedMonth) . '&week=' . $selectedWeek) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to Execution Sheet
        </a>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-dark btn-sm" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Print / Export Voucher
            </button>
        </div>
    </div>

    <!-- The Voucher Document -->
    <div class="voucher-card">
        <!-- Header -->
        <div class="voucher-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <img src="<?= url('/assets/img/kaldis-logo.png') ?>" alt="Kaldis" class="voucher-brand-logo" onerror="this.outerHTML='<div class=\'voucher-brand-logo d-flex align-items-center justify-content-center text-warning fw-bold fs-3\'>☕</div>'">
                    <div>
                        <div class="text-uppercase small tracking-widest text-warning fw-bold" style="letter-spacing: 0.15em;">
                            Kaldis Coffee PLC &bull; Strategic Execution System
                        </div>
                        <h3 class="fw-bold mb-0 text-white">WEEKLY PERFORMANCE VOUCHER</h3>
                        <small class="text-light opacity-75">Official Weekly Execution, Deliverables &amp; Accountability Record</small>
                    </div>
                </div>
                <div class="text-end">
                    <div class="voucher-ref-badge mb-1"><?= e($voucherRef) ?></div>
                    <div class="small text-light opacity-75">Generated: <?= format_ethiopian_datetime() ?></div>
                </div>
            </div>
        </div>

        <!-- Status & Meta Bar -->
        <div class="voucher-meta-box">
            <div class="row g-3 align-items-center">
                <div class="col-md-7">
                    <div class="row g-2">
                        <div class="col-6">
                            <span class="text-muted small text-uppercase d-block">Department</span>
                            <strong class="text-dark fs-6"><?= e($department['department_name']) ?> (<?= e($department['department_code']) ?>)</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small text-uppercase d-block">Planning Period</span>
                            <strong class="text-dark fs-6">Week <?= $selectedWeek ?> &bull; <?= e($selectedMonth) ?> <?= e($selectedYear) ?></strong>
                        </div>
                        <div class="col-6 mt-2">
                            <span class="text-muted small text-uppercase d-block">Submission Status</span>
                            <span class="small">
                                <?php if ($submission && !empty($submission['submitted_at'])): ?>
                                    Submitted on <strong><?= format_ethiopian_datetime($submission['submitted_at']) ?></strong>
                                    <?php if (!empty($submission['submitter_name'])): ?>
                                        by <?= e($submission['submitter_name']) ?>
                                    <?php endif; ?>
                                <?php elseif ($weeklyReport && !empty($weeklyReport['submitted_at'])): ?>
                                    Submitted on <strong><?= format_ethiopian_datetime($weeklyReport['submitted_at']) ?></strong>
                                    <?php if (!empty($weeklyReport['submitter_name'])): ?>
                                        by <?= e($weeklyReport['submitter_name']) ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-danger fw-semibold">Not Yet Officially Submitted</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="col-6 mt-2">
                            <span class="text-muted small text-uppercase d-block">Weekly Cutoff</span>
                            <span class="small text-secondary">Every Monday at 12:00 PM (Noon)</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-5 text-md-end">
                    <div class="mb-2">
                        <?php if ($status === 'ON_TIME'): ?>
                            <span class="voucher-status-stamp stamp-ontime">
                                <i class="fas fa-check-circle me-1"></i> Submitted On-Time
                            </span>
                        <?php elseif ($status === 'LATE'): ?>
                            <span class="voucher-status-stamp stamp-late">
                                <i class="fas fa-exclamation-triangle me-1"></i> Late Submission
                            </span>
                        <?php else: ?>
                            <span class="voucher-status-stamp stamp-notsubmitted">
                                <i class="fas fa-times-circle me-1"></i> Not Submitted
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($approvalStatus)): ?>
                        <div>
                            <span class="badge <?= $approvalStatus === 'APPROVED' ? 'bg-success' : ($approvalStatus === 'REVISION_REQUESTED' ? 'bg-danger' : 'bg-secondary') ?> px-2 py-1">
                                GM Approval: <?= e($approvalStatus) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Scorecard KPI Metrics -->
        <div class="px-4 py-2">
            <div class="row g-3">
                <div class="col-3">
                    <div class="voucher-kpi-card">
                        <small class="text-muted text-uppercase fw-bold d-block">Total Tasks</small>
                        <h4 class="fw-bold my-1 text-dark"><?= $totalTasks ?></h4>
                        <span class="small text-muted">Scheduled deliverables</span>
                    </div>
                </div>
                <div class="col-3">
                    <div class="voucher-kpi-card" style="border-left: 4px solid #10B981;">
                        <small class="text-success text-uppercase fw-bold d-block">DONE Tasks</small>
                        <h4 class="fw-bold my-1 text-success"><?= $doneTasks ?></h4>
                        <span class="small text-muted">Targets reached</span>
                    </div>
                </div>
                <div class="col-3">
                    <div class="voucher-kpi-card" style="border-left: 4px solid #EF4444;">
                        <small class="text-danger text-uppercase fw-bold d-block">NOT DONE Tasks</small>
                        <h4 class="fw-bold my-1 text-danger"><?= $notDoneTasks ?></h4>
                        <span class="small text-muted">With root-cause logged</span>
                    </div>
                </div>
                <div class="col-3">
                    <div class="voucher-kpi-card" style="border-left: 4px solid #3B82F6;">
                        <small class="text-primary text-uppercase fw-bold d-block">Completion Rate</small>
                        <h4 class="fw-bold my-1 text-primary"><?= $completionRate ?>%</h4>
                        <span class="small text-muted">Weekly performance</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 1: Binary Task Evaluation Table -->
        <div class="px-4 pt-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-dark text-uppercase mb-0" style="letter-spacing: 0.05em;">
                    <i class="fas fa-check-circle text-success me-2"></i> Section 1: Task Execution Scorecard (<?= $totalTasks ?>)
                </h6>
                <span class="badge bg-light text-secondary border small">Binary Execution: DONE / NOT DONE</span>
            </div>
            <div class="table-responsive">
                <table class="voucher-table">
                    <thead>
                        <tr>
                            <th style="width: 35px;" class="text-center">#</th>
                            <th style="width: 90px;">Type</th>
                            <th>Task Description &amp; Target</th>
                            <th style="width: 100px;" class="text-center">Result</th>
                            <th>Evaluation / Root-Cause / Corrective Next Action</th>
                            <th>Responsible</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tasks)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3 small fst-italic">
                                    No weekly tasks recorded for this week.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tasks as $idx => $t): ?>
                                <tr>
                                    <td class="text-center fw-bold text-muted"><?= $idx + 1 ?></td>
                                    <td>
                                        <span class="badge <?= $t['plan_type'] === 'STRATEGY' ? 'bg-primary' : 'bg-info text-dark' ?>">
                                            <?= e($t['plan_type']) ?>
                                        </span>
                                        <?php if (!empty($t['goal_code'])): ?>
                                            <div class="font-monospace text-muted mt-1" style="font-size: 0.72rem;"><?= e($t['goal_code']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="text-dark"><?= e($t['task_title']) ?></strong>
                                        <div class="small text-muted"><strong>Target:</strong> <?= e($t['expected_result']) ?></div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($t['result'] === 'DONE'): ?>
                                            <span class="badge bg-success px-2 py-1"><i class="fas fa-check me-1"></i> DONE</span>
                                        <?php elseif ($t['result'] === 'NOT_DONE'): ?>
                                            <span class="badge bg-danger px-2 py-1"><i class="fas fa-times me-1"></i> NOT DONE</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary px-2 py-1"><i class="fas fa-clock me-1"></i> PENDING</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($t['result'] === 'DONE'): ?>
                                            <span class="text-success small fw-semibold"><i class="fas fa-circle-check me-1"></i> Output Achieved On-Target</span>
                                        <?php elseif ($t['result'] === 'NOT_DONE'): ?>
                                            <div class="small">
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle mb-1">
                                                    <?= e($t['reason_category_name'] ?? 'Root Cause Not Specified') ?>
                                                </span>
                                                <div class="text-secondary"><strong>Why:</strong> <?= e($t['not_done_explanation']) ?></div>
                                                <div class="text-primary mt-0.5"><strong>Next Action:</strong> <?= e($t['next_action']) ?></div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">Pending evaluation</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?= e($t['responsible_person'] ?: 'Department') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 2: Qualitative Achievements & Challenges -->
        <div class="px-4 pt-1">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="card h-100 border">
                        <div class="card-header bg-light py-2">
                            <strong class="small text-success text-uppercase"><i class="fas fa-trophy text-warning me-1"></i> Key Operational Achievements (<?= count($achievements) ?>)</strong>
                        </div>
                        <div class="card-body p-2.5">
                            <?php if (empty($achievements)): ?>
                                <div class="text-muted small fst-italic py-2 text-center">No independent achievements recorded.</div>
                            <?php else: ?>
                                <ul class="list-unstyled mb-0 small">
                                    <?php foreach ($achievements as $ach): ?>
                                        <li class="mb-1.5 pb-1.5 border-bottom">
                                            <i class="fas fa-check text-success me-1"></i> <?= e($ach['achievement_text']) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100 border">
                        <div class="card-header bg-light py-2">
                            <strong class="small text-danger text-uppercase"><i class="fas fa-flag text-danger me-1"></i> Operational Challenges &amp; Blockers (<?= count($challenges) ?>)</strong>
                        </div>
                        <div class="card-body p-2.5">
                            <?php if (empty($challenges)): ?>
                                <div class="text-muted small fst-italic py-2 text-center">No operational challenges logged.</div>
                            <?php else: ?>
                                <ul class="list-unstyled mb-0 small">
                                    <?php foreach ($challenges as $ch): ?>
                                        <li class="mb-1.5 pb-1.5 border-bottom">
                                            <i class="fas fa-circle-exclamation text-danger me-1"></i> <?= e($ch['challenge_text']) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Executive Review & GM Comments -->
        <div class="px-4 pt-2">
            <div class="card border-primary mb-3">
                <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                    <span class="fw-bold small text-uppercase">
                        <i class="fas fa-user-tie me-1"></i> Section 3: General Management Review &amp; Approval
                    </span>
                    <span class="small opacity-75">Executive Authority</span>
                </div>
                <div class="card-body bg-light p-3">
                    <?php if (Permissions::isGM() || Permissions::isSuperAdmin()): ?>
                        <form method="POST" action="weekly_voucher.php?department_id=<?= (int)$deptId ?>&year=<?= urlencode($selectedYear) ?>&month=<?= urlencode($selectedMonth) ?>&week=<?= $selectedWeek ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="save_feedback">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark text-uppercase">Executive Approval Decision</label>
                                    <select name="approval_status" class="form-select form-select-sm">
                                        <option value="APPROVED" <?= $approvalStatus === 'APPROVED' ? 'selected' : '' ?>>APPROVED</option>
                                        <option value="PENDING" <?= empty($approvalStatus) || $approvalStatus === 'PENDING' ? 'selected' : '' ?>>PENDING REVIEW</option>
                                        <option value="REVISION_REQUESTED" <?= $approvalStatus === 'REVISION_REQUESTED' ? 'selected' : '' ?>>REVISION REQUESTED</option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label small fw-bold text-dark text-uppercase">GM Executive Review Comments / Directives</label>
                                    <textarea name="gm_feedback" rows="2" class="form-control form-control-sm" placeholder="Enter executive feedback, guidance, or revision instructions for the department head..."><?= e($gmFeedback) ?></textarea>
                                </div>
                                <div class="col-12 text-end no-print">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fas fa-check-double me-1"></i> Save Executive Review Decision
                                    </button>
                                </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <span class="text-muted small text-uppercase d-block">Approval Status</span>
                                <span class="badge <?= $approvalStatus === 'APPROVED' ? 'bg-success' : ($approvalStatus === 'REVISION_REQUESTED' ? 'bg-danger' : 'bg-secondary') ?> px-2 py-1 mt-1">
                                    <?= e($approvalStatus ?: 'PENDING REVIEW') ?>
                                </span>
                            </div>
                            <div class="col-md-8">
                                <span class="text-muted small text-uppercase d-block">Executive Review Comments</span>
                                <p class="small text-dark mb-0 mt-1">
                                    <?= !empty($gmFeedback) ? nl2br(e($gmFeedback)) : '<span class="text-muted fst-italic">No executive feedback comments provided yet.</span>' ?>
                                </p>
                                <?php if (!empty($gmFeedbackAt)): ?>
                                    <small class="text-muted d-block mt-1">Recorded on: <?= date('M d, Y H:i', strtotime($gmFeedbackAt)) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Section 4: Sign-off & Certification Block -->
        <div class="voucher-sign-block">
            <div class="row text-center">
                <div class="col-4">
                    <div class="signature-line">
                        <div><?= e($submission['submitter_name'] ?? ($weeklyReport['submitter_name'] ?? 'Department Head')) ?></div>
                        <small class="text-muted">Prepared &amp; Submitted By</small>
                        <div class="text-muted small mt-1"><?= !empty($submission['submitted_at']) ? format_ethiopian_date($submission['submitted_at']) : (!empty($weeklyReport['submitted_at']) ? format_ethiopian_date($weeklyReport['submitted_at']) : 'Date: _____________') ?></div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="signature-line">
                        <div>Corporate Strategic Planning Office</div>
                        <small class="text-muted">Compliance &amp; Verification</small>
                        <div class="text-muted small mt-1">Date: _____________</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="signature-line">
                        <div>General Manager (GM)</div>
                        <small class="text-muted">Approved &amp; Ratified</small>
                        <div class="text-muted small mt-1"><?= !empty($gmFeedbackAt) ? date('M d, Y', strtotime($gmFeedbackAt)) : 'Date: _____________' ?></div>
                    </div>
                </div>
            </div>
            <div class="text-center text-muted small mt-4 pt-2 border-top" style="font-size: 0.72rem;">
                Official Corporate Voucher &bull; Kaldis Coffee PLC &bull; Generated from Kaldis Strategic Planning &amp; Execution System &bull; Confidential &amp; Proprietary
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

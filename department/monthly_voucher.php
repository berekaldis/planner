<?php
/**
 * Official Monthly Plan Voucher - Vanilla PHP
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

    $chkSub = $db->prepare("SELECT id FROM monthly_submissions WHERE department_id = ? AND year = ? AND month = ?");
    $chkSub->execute([$deptId, $selectedYear, $selectedMonth]);
    $existing = $chkSub->fetch();

    if ($existing) {
        $updateStmt = $db->prepare("
            UPDATE monthly_submissions 
            SET approval_status = ?, gm_feedback = ?, gm_feedback_at = ?, updated_at = ?
            WHERE department_id = ? AND year = ? AND month = ?
        ");
        $updateStmt->execute([$approvalStatus, $gmFeedback, $now, $now, $deptId, $selectedYear, $selectedMonth]);
    } else {
        $insertStmt = $db->prepare("
            INSERT INTO monthly_submissions (department_id, year, month, status, approval_status, gm_feedback, gm_feedback_at, created_at, updated_at)
            VALUES (?, ?, ?, 'SUBMITTED', ?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([$deptId, $selectedYear, $selectedMonth, $approvalStatus, $gmFeedback, $now, $now, $now]);
    }

    audit_log($userId, 'GM_FEEDBACK_MONTHLY', 'monthly_submissions', "{$deptId}-{$selectedYear}-{$selectedMonth}", null, [
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
    SELECT ms.*, u.full_name as submitter_name
    FROM monthly_submissions ms
    LEFT JOIN users u ON ms.submitted_by = u.id
    WHERE ms.department_id = ? AND ms.year = ? AND ms.month = ?
");
$subStmt->execute([$deptId, $selectedYear, $selectedMonth]);
$submission = $subStmt->fetch();

// Fetch Strategy Plans
$stratStmt = $db->prepare("
    SELECT mp.*, ag.goal_code, ag.title as goal_title
    FROM monthly_plans mp
    LEFT JOIN annual_goals ag ON mp.annual_goal_id = ag.id
    WHERE mp.department_id = ? AND mp.year = ? AND mp.month = ? AND mp.plan_type = 'STRATEGY'
    ORDER BY mp.id ASC
");
$stratStmt->execute([$deptId, $selectedYear, $selectedMonth]);
$strategyPlans = $stratStmt->fetchAll();

// Fetch Operational Plans
$opsStmt = $db->prepare("
    SELECT mp.*
    FROM monthly_plans mp
    WHERE mp.department_id = ? AND mp.year = ? AND mp.month = ? AND mp.plan_type = 'OPERATIONAL'
    ORDER BY mp.id ASC
");
$opsStmt->execute([$deptId, $selectedYear, $selectedMonth]);
$operationalPlans = $opsStmt->fetchAll();

$totalPlans = count($strategyPlans) + count($operationalPlans);

// Determine timing status
$status = 'NOT_SUBMITTED';
if ($submission) {
    $status = in_array($submission['status'], ['ON_TIME', 'LATE']) ? $submission['status'] : 'ON_TIME';
}

$cleanYear = preg_replace('/[^A-Za-z0-9]/', '', $selectedYear);
$voucherRef = sprintf('VCH-MPL-%s-%s-%s', $department['department_code'] ?? 'GEN', $cleanYear, strtoupper(substr($selectedMonth, 0, 3)));

$pageTitle = 'Monthly Plan Voucher - ' . ($department['department_name'] ?? 'Department');
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
        <a href="<?= url('/department/monthly_plans.php?department_id=' . $deptId . '&year=' . urlencode($selectedYear) . '&month=' . urlencode($selectedMonth)) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to Monthly Plans
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
                        <h3 class="fw-bold mb-0 text-white">MONTHLY PLAN VOUCHER</h3>
                        <small class="text-light opacity-75">Official Department Strategy &amp; Operational Commitment Document</small>
                    </div>
                </div>
                <div class="text-end">
                    <div class="voucher-ref-badge mb-1"><?= e($voucherRef) ?></div>
                    <div class="small text-light opacity-75">Generated: <?= date('M d, Y H:i') ?></div>
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
                            <strong class="text-dark fs-6"><?= e($selectedMonth) ?> <?= e($selectedYear) ?></strong>
                        </div>
                        <div class="col-6 mt-2">
                            <span class="text-muted small text-uppercase d-block">Submission Status</span>
                            <span class="small">
                                <?php if ($submission && !empty($submission['submitted_at'])): ?>
                                    Submitted on <strong><?= date('M d, Y H:i', strtotime($submission['submitted_at'])) ?></strong>
                                    <?php if (!empty($submission['submitter_name'])): ?>
                                        by <?= e($submission['submitter_name']) ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-danger fw-semibold">Not Yet Officially Submitted</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="col-6 mt-2">
                            <span class="text-muted small text-uppercase d-block">Plan Package</span>
                            <strong class="text-dark"><?= $totalPlans ?> Total</strong>
                            <span class="text-muted small">(<?= count($strategyPlans) ?> Strategy, <?= count($operationalPlans) ?> Operational)</span>
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
                    <?php if ($submission && !empty($submission['approval_status'])): ?>
                        <div>
                            <span class="badge <?= $submission['approval_status'] === 'APPROVED' ? 'bg-success' : ($submission['approval_status'] === 'REVISION_REQUESTED' ? 'bg-danger' : 'bg-secondary') ?> px-2 py-1">
                                GM Approval: <?= e($submission['approval_status']) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($submission && !empty($submission['remarks'])): ?>
                <div class="mt-3 pt-2 border-top">
                    <span class="text-muted small fw-bold">Department Head Remarks:</span>
                    <div class="small text-secondary fst-italic mt-1"><?= nl2br(e($submission['remarks'])) ?></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Section 1: Strategic Alignment Plans -->
        <div class="px-4 pt-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-dark text-uppercase mb-0" style="letter-spacing: 0.05em;">
                    <i class="fas fa-chess-knight text-primary me-2"></i> Section 1: Strategic Alignment Plans (<?= count($strategyPlans) ?>)
                </h6>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle small">Mandatory Strategy</span>
            </div>
            <div class="table-responsive">
                <table class="voucher-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="text-center">#</th>
                            <th style="width: 90px;">Goal Code</th>
                            <th>Strategic Plan Title</th>
                            <th>Monthly Target</th>
                            <th>Definition of Done (DoD)</th>
                            <th style="width: 80px;" class="text-center">Target %</th>
                            <th style="width: 80px;" class="text-center">Priority</th>
                            <th>Responsible</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($strategyPlans)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-3 small fst-italic">
                                    No strategic alignment plans registered for this period.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($strategyPlans as $idx => $sp): ?>
                                <tr>
                                    <td class="text-center fw-bold text-muted"><?= $idx + 1 ?></td>
                                    <td>
                                        <span class="badge bg-dark font-monospace"><?= e($sp['goal_code'] ?? '—') ?></span>
                                    </td>
                                    <td>
                                        <strong class="text-dark"><?= e($sp['title']) ?></strong>
                                    </td>
                                    <td><?= e($sp['monthly_target']) ?></td>
                                    <td class="small text-secondary"><?= e($sp['definition_of_done']) ?></td>
                                    <td class="text-center fw-bold text-primary"><?= (int)$sp['target_percentage'] ?>%</td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border"><?= e($sp['priority']) ?></span>
                                    </td>
                                    <td class="small text-muted"><?= e($sp['responsible_person'] ?: 'Department') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 2: Department Operational Plans -->
        <div class="px-4 pt-2">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-dark text-uppercase mb-0" style="letter-spacing: 0.05em;">
                    <i class="fas fa-cogs text-info me-2"></i> Section 2: Department Operational Plans (<?= count($operationalPlans) ?>)
                </h6>
                <span class="badge bg-info-subtle text-info border border-info-subtle small">Operations &amp; Maintenance</span>
            </div>
            <div class="table-responsive">
                <table class="voucher-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="text-center">#</th>
                            <th>Plan Title</th>
                            <th>Monthly Target</th>
                            <th>Definition of Done (DoD)</th>
                            <th style="width: 80px;" class="text-center">Target %</th>
                            <th style="width: 80px;" class="text-center">Priority</th>
                            <th>Responsible</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($operationalPlans)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3 small fst-italic">
                                    No operational plans registered for this period.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($operationalPlans as $idx => $op): ?>
                                <tr>
                                    <td class="text-center fw-bold text-muted"><?= $idx + 1 ?></td>
                                    <td>
                                        <strong class="text-dark"><?= e($op['title']) ?></strong>
                                    </td>
                                    <td><?= e($op['monthly_target']) ?></td>
                                    <td class="small text-secondary"><?= e($op['definition_of_done']) ?></td>
                                    <td class="text-center fw-bold text-primary"><?= (int)$op['target_percentage'] ?>%</td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border"><?= e($op['priority']) ?></span>
                                    </td>
                                    <td class="small text-muted"><?= e($op['responsible_person'] ?: 'Department') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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
                        <form method="POST" action="monthly_voucher.php?department_id=<?= (int)$deptId ?>&year=<?= urlencode($selectedYear) ?>&month=<?= urlencode($selectedMonth) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="save_feedback">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark text-uppercase">Executive Approval Decision</label>
                                    <select name="approval_status" class="form-select form-select-sm">
                                        <option value="APPROVED" <?= ($submission['approval_status'] ?? '') === 'APPROVED' ? 'selected' : '' ?>>APPROVED</option>
                                        <option value="PENDING" <?= empty($submission['approval_status']) || $submission['approval_status'] === 'PENDING' ? 'selected' : '' ?>>PENDING REVIEW</option>
                                        <option value="REVISION_REQUESTED" <?= ($submission['approval_status'] ?? '') === 'REVISION_REQUESTED' ? 'selected' : '' ?>>REVISION REQUESTED</option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label small fw-bold text-dark text-uppercase">GM Executive Review Comments / Directives</label>
                                    <textarea name="gm_feedback" rows="2" class="form-control form-control-sm" placeholder="Enter executive feedback, guidance, or revision instructions for the department head..."><?= e($submission['gm_feedback'] ?? '') ?></textarea>
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
                                <span class="badge <?= ($submission['approval_status'] ?? '') === 'APPROVED' ? 'bg-success' : (($submission['approval_status'] ?? '') === 'REVISION_REQUESTED' ? 'bg-danger' : 'bg-secondary') ?> px-2 py-1 mt-1">
                                    <?= e($submission['approval_status'] ?? 'PENDING REVIEW') ?>
                                </span>
                            </div>
                            <div class="col-md-8">
                                <span class="text-muted small text-uppercase d-block">Executive Review Comments</span>
                                <p class="small text-dark mb-0 mt-1">
                                    <?= !empty($submission['gm_feedback']) ? nl2br(e($submission['gm_feedback'])) : '<span class="text-muted fst-italic">No executive feedback comments provided yet.</span>' ?>
                                </p>
                                <?php if (!empty($submission['gm_feedback_at'])): ?>
                                    <small class="text-muted d-block mt-1">Recorded on: <?= date('M d, Y H:i', strtotime($submission['gm_feedback_at'])) ?></small>
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
                        <div><?= e($submission['submitter_name'] ?? 'Department Head') ?></div>
                        <small class="text-muted">Prepared &amp; Submitted By</small>
                        <div class="text-muted small mt-1"><?= !empty($submission['submitted_at']) ? date('M d, Y', strtotime($submission['submitted_at'])) : 'Date: _____________' ?></div>
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
                        <div class="text-muted small mt-1"><?= !empty($submission['gm_feedback_at']) ? date('M d, Y', strtotime($submission['gm_feedback_at'])) : 'Date: _____________' ?></div>
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

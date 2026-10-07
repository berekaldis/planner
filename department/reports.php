<?php
/**
 * Department Reports Archive
 * Kaldis Coffee PLC
 */

$pageTitle = 'Department Reports Archive';
require_once __DIR__ . '/../includes/header.php';

$user = Auth::user();
$db = Database::getConnection();

$deptId = Permissions::getActiveDepartmentId(!empty($_GET['department_id']) ? (int)$_GET['department_id'] : null);
if (!$deptId) $deptId = 1;

$deptStmt = $db->prepare("SELECT * FROM departments WHERE id = ?");
$deptStmt->execute([$deptId]);
$department = $deptStmt->fetch();

$reportsStmt = $db->prepare("
    SELECT wr.*, u.full_name as submitter_name
    FROM weekly_reports wr
    LEFT JOIN users u ON wr.submitted_by = u.id
    WHERE wr.department_id = ?
    ORDER BY wr.year DESC, wr.month DESC, wr.week_number DESC
");
$reportsStmt->execute([$deptId]);
$reports = $reportsStmt->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="mb-1 fw-bold text-dark">
            <i class="fas fa-file-invoice text-warning me-2"></i> <?= e($department['department_name']) ?> &mdash; Reports Archive
        </h3>
        <p class="text-muted mb-0">Historical weekly performance submissions, completion records, and compliance stamps.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/department/dashboard.php') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Workspace
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header kaldis-header d-flex justify-content-between align-items-center">
        <span class="fs-6 fw-bold"><i class="fas fa-archive me-2"></i> Submitted Weekly Reports</span>
        <span class="badge bg-warning text-dark font-monospace"><?= count($reports) ?> Reports</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-kaldis mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 140px;">Period</th>
                    <th style="width: 130px;" class="text-center">Submission Status</th>
                    <th style="width: 100px;" class="text-center">Planned</th>
                    <th style="width: 90px;" class="text-center">DONE</th>
                    <th style="width: 100px;" class="text-center">NOT DONE</th>
                    <th style="width: 180px;">Completion %</th>
                    <th style="width: 180px;">Submitted By</th>
                    <th style="width: 160px;">Submitted At</th>
                    <th style="width: 100px;" class="text-end no-print">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reports)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fas fa-folder-open fa-3x mb-3 d-block opacity-25"></i>
                            No submitted reports found in this department's archive yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reports as $r): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= e($r['year']) ?></div>
                                <small class="text-muted"><?= e($r['month']) ?> &bull; Week <?= $r['week_number'] ?></small>
                            </td>
                            <td class="text-center">
                                <?= render_submission_badge($r['submission_status']) ?>
                            </td>
                            <td class="text-center fw-bold"><?= (int)$r['total_tasks'] ?></td>
                            <td class="text-center text-success fw-bold"><?= (int)$r['done_tasks'] ?></td>
                            <td class="text-center text-danger fw-bold"><?= (int)$r['not_done_tasks'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar <?= $r['completion_percentage'] >= 80 ? 'bg-success' : 'bg-warning' ?>" 
                                             style="width: <?= (float)$r['completion_percentage'] ?>%;"></div>
                                    </div>
                                    <span class="small fw-bold font-monospace"><?= (float)$r['completion_percentage'] ?>%</span>
                                </div>
                            </td>
                            <td><small class="text-secondary fw-semibold"><?= e($r['submitter_name'] ?? 'Staff') ?></small></td>
                            <td><small class="text-muted"><?= date('M d, Y H:i', strtotime($r['submitted_at'])) ?></small></td>
                            <td class="text-end no-print">
                                <a href="<?= url('/department/performance.php?year=' . urlencode($r['year']) . '&month=' . urlencode($r['month']) . '&week=' . $r['week_number'] . '&department_id=' . $deptId) ?>" 
                                   class="btn btn-sm btn-outline-primary" title="View Full Report">
                                    <i class="fas fa-eye me-1"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
/**
 * Master Reports & Compliance Hub
 * Kaldis Coffee PLC
 */

$pageTitle = 'Master Management Reports';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getConnection();

$year = app_config('current_planning_year', '2019 E.C.');
$month = app_config('current_planning_month', 'Nehase');

// Submissions compliance tracker summary
$submissionsStmt = $db->query("
    SELECT s.*, d.department_name, d.department_code, u.full_name as submitter_name
    FROM submissions s
    JOIN departments d ON s.department_id = d.id
    LEFT JOIN users u ON s.submitted_by = u.id
    WHERE s.year = '{$year}' AND s.month = '{$month}'
    ORDER BY s.week_number DESC, s.status ASC
");
$allSubmissions = $submissionsStmt->fetchAll();

// Handle CSV Export for All Reports
if (isset($_GET['export'])) {
    $reportType = $_GET['export'];
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="Kaldis_' . ucfirst($reportType) . '_Report_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');

    if ($reportType === 'strategy') {
        fputcsv($out, ['Goal ID', 'Title', 'Definition of Done', 'Department', 'Target', 'Priority', 'Status', 'Year']);
        $rows = $db->query("SELECT ag.*, d.department_name FROM annual_goals ag JOIN departments d ON ag.responsible_department_id = d.id WHERE ag.year = '{$year}'")->fetchAll();
        foreach ($rows as $r) {
            fputcsv($out, [$r['goal_code'], $r['title'], $r['definition_of_done'], $r['department_name'], $r['annual_target'], $r['priority'], $r['status'], $r['year']]);
        }
    } elseif ($reportType === 'activations') {
        fputcsv($out, ['Goal ID', 'Department', 'Year', 'Month', 'Active', 'Activated At']);
        $rows = $db->query("
            SELECT ag.goal_code, d.department_name, msa.year, msa.month, msa.active, msa.activated_at 
            FROM monthly_strategy_activations msa 
            JOIN annual_goals ag ON msa.annual_goal_id = ag.id 
            JOIN departments d ON msa.department_id = d.id
        ")->fetchAll();
        foreach ($rows as $r) {
            fputcsv($out, [$r['goal_code'], $r['department_name'], $r['year'], $r['month'], $r['active'], $r['activated_at']]);
        }
    } elseif ($reportType === 'submissions') {
        fputcsv($out, ['Department', 'Year', 'Month', 'Week', 'Status', 'Deadline', 'Submitted At', 'Channel', 'Remarks']);
        foreach ($allSubmissions as $r) {
            fputcsv($out, [$r['department_name'], $r['year'], $r['month'], 'Week ' . $r['week_number'], $r['status'], $r['deadline_at'], $r['submitted_at'], $r['submission_channel'], $r['remarks']]);
        }
    }
    fclose($out);
    exit;
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="mb-1 fw-bold text-dark">
            <i class="fas fa-file-invoice text-warning me-2"></i> Master Management Reports Hub
        </h3>
        <p class="text-muted mb-0">Export official executive documentation across Strategy, Planning, Execution, and Compliance.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" onclick="window.print()">
            <i class="fas fa-print me-1"></i> Print Directory
        </button>
    </div>
</div>

<!-- 11 Standard Reports Directory Grid -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100 p-3">
            <div class="d-flex align-items-center mb-2">
                <i class="fas fa-bullseye fa-2x text-warning me-3"></i>
                <div>
                    <h6 class="fw-bold mb-0">1. Annual Strategy Report</h6>
                    <small class="text-muted">Master goal codes G01..G58 & DoD</small>
                </div>
            </div>
            <div class="mt-auto pt-2 d-flex gap-2">
                <a href="<?= url('/admin/annual_goals.php') ?>" class="btn btn-sm btn-outline-dark w-50">View Report</a>
                <a href="?export=strategy" class="btn btn-sm btn-outline-success w-50"><i class="fas fa-file-excel me-1"></i> CSV</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 p-3">
            <div class="d-flex align-items-center mb-2">
                <i class="fas fa-toggle-on fa-2x text-success me-3"></i>
                <div>
                    <h6 class="fw-bold mb-0">2. Monthly Activation Report</h6>
                    <small class="text-muted">GM Monthly Strategy Matrix</small>
                </div>
            </div>
            <div class="mt-auto pt-2 d-flex gap-2">
                <a href="<?= url('/admin/monthly_activation.php') ?>" class="btn btn-sm btn-outline-dark w-50">View Report</a>
                <a href="?export=activations" class="btn btn-sm btn-outline-success w-50"><i class="fas fa-file-excel me-1"></i> CSV</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 p-3">
            <div class="d-flex align-items-center mb-2">
                <i class="fas fa-calendar-days fa-2x text-primary me-3"></i>
                <div>
                    <h6 class="fw-bold mb-0">3. Monthly Plan Report</h6>
                    <small class="text-muted">Strategy vs Operational breakdown</small>
                </div>
            </div>
            <div class="mt-auto pt-2 d-flex gap-2">
                <a href="<?= url('/admin/monthly_plans.php') ?>" class="btn btn-sm btn-outline-dark w-100">View Report</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 p-3">
            <div class="d-flex align-items-center mb-2">
                <i class="fas fa-list-check fa-2x text-warning me-3"></i>
                <div>
                    <h6 class="fw-bold mb-0">4. Weekly Plan Report</h6>
                    <small class="text-muted">Weekly work breakdown by department</small>
                </div>
            </div>
            <div class="mt-auto pt-2 d-flex gap-2">
                <a href="<?= url('/admin/weekly_plans.php') ?>" class="btn btn-sm btn-outline-dark w-100">View Report</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 p-3">
            <div class="d-flex align-items-center mb-2">
                <i class="fas fa-clipboard-check fa-2x text-success me-3"></i>
                <div>
                    <h6 class="fw-bold mb-0">5. Weekly Performance Report</h6>
                    <small class="text-muted">DONE / NOT DONE scorecard</small>
                </div>
            </div>
            <div class="mt-auto pt-2 d-flex gap-2">
                <a href="<?= url('/performance/weekly.php') ?>" class="btn btn-sm btn-outline-dark w-100">View Report</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 p-3">
            <div class="d-flex align-items-center mb-2">
                <i class="fas fa-circle-exclamation fa-2x text-danger me-3"></i>
                <div>
                    <h6 class="fw-bold mb-0">6. Not Done Analysis Report</h6>
                    <small class="text-muted">Root-causes, blockers & next actions</small>
                </div>
            </div>
            <div class="mt-auto pt-2 d-flex gap-2">
                <a href="<?= url('/performance/not_done.php') ?>" class="btn btn-sm btn-outline-danger w-50">View Report</a>
                <a href="<?= url('/performance/not_done.php?export=csv') ?>" class="btn btn-sm btn-outline-success w-50"><i class="fas fa-file-excel me-1"></i> CSV</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 p-3">
            <div class="d-flex align-items-center mb-2">
                <i class="fas fa-award fa-2x text-warning me-3"></i>
                <div>
                    <h6 class="fw-bold mb-0">7. Achievement Report</h6>
                    <small class="text-muted">Weekly, monthly, department highlights</small>
                </div>
            </div>
            <div class="mt-auto pt-2 d-flex gap-2">
                <a href="<?= url('/performance/achievements.php') ?>" class="btn btn-sm btn-outline-dark w-100">View Report</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 p-3">
            <div class="d-flex align-items-center mb-2">
                <i class="fas fa-triangle-exclamation fa-2x text-danger me-3"></i>
                <div>
                    <h6 class="fw-bold mb-0">8. Challenge Analysis Report</h6>
                    <small class="text-muted">Cross-departmental recurring issues</small>
                </div>
            </div>
            <div class="mt-auto pt-2 d-flex gap-2">
                <a href="<?= url('/performance/challenges.php') ?>" class="btn btn-sm btn-outline-dark w-100">View Report</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 p-3">
            <div class="d-flex align-items-center mb-2">
                <i class="fas fa-diagram-project fa-2x text-dark me-3"></i>
                <div>
                    <h6 class="fw-bold mb-0">9. Annual Performance Hierarchy</h6>
                    <small class="text-muted">Strategy &rarr; Month &rarr; Week &rarr; Result</small>
                </div>
            </div>
            <div class="mt-auto pt-2 d-flex gap-2">
                <a href="<?= url('/performance/annual.php') ?>" class="btn btn-sm btn-outline-dark w-100">View Report</a>
            </div>
        </div>
    </div>
</div>

<!-- Department Submission Compliance Tracker Table -->
<div class="card mb-4">
    <div class="card-header kaldis-header d-flex justify-content-between align-items-center">
        <span class="fs-6 fw-bold">
            <i class="fas fa-clock-rotate-left me-2"></i> Department Submission Compliance Tracker (<?= e($month) ?> <?= e($year) ?>)
        </span>
        <a href="?export=submissions" class="btn btn-sm btn-light text-success fw-bold">
            <i class="fas fa-file-excel me-1"></i> Export Submissions CSV
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-kaldis mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 140px;">Department</th>
                    <th style="width: 100px;">Week</th>
                    <th style="width: 160px;">Deadline</th>
                    <th style="width: 160px;">Submitted At</th>
                    <th style="width: 140px;" class="text-center">Status</th>
                    <th style="width: 100px;" class="text-center">Channel</th>
                    <th>Remarks / Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($allSubmissions)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No submission records logged for <?= e($month) ?> <?= e($year) ?> yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($allSubmissions as $sub): ?>
                        <tr>
                            <td>
                                <span class="badge bg-dark font-monospace"><?= e($sub['department_code']) ?></span>
                                <div class="small fw-semibold mt-1"><?= e($sub['department_name']) ?></div>
                            </td>
                            <td><span class="badge bg-light text-dark border">Week <?= $sub['week_number'] ?></span></td>
                            <td><small class="text-muted"><?= date('M d, H:i', strtotime($sub['deadline_at'])) ?></small></td>
                            <td>
                                <?php if ($sub['submitted_at']): ?>
                                    <small class="text-dark fw-semibold"><?= date('M d, H:i', strtotime($sub['submitted_at'])) ?></small>
                                <?php else: ?>
                                    <span class="text-muted small">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?= render_submission_badge($sub['status']) ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border"><?= e($sub['submission_channel']) ?></span>
                            </td>
                            <td><small class="text-secondary"><?= e($sub['remarks'] ?: 'Standard weekly report') ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

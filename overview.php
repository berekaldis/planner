<?php
/**
 * System Overview & Smart Launchpad
 * Kaldis Coffee PLC
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';

Auth::requireLogin();

$currentUser = Auth::user();
$pageTitle   = 'Overview';
$period      = get_ethiopian_period();
$year        = app_config('current_planning_year', $period['year'] . ' E.C.');
$month       = app_config('current_planning_month', $period['month']);
$week        = (int)($period['week'] ?? 1);

// Ethiopian Calendar & Deadlines
$ethToday            = gregorian_to_ethiopian_date((int)date('Y'), (int)date('n'), (int)date('j'));
$currentEthDay       = (int)($ethToday['day'] ?? 1);
$monthlyDeadlineDay  = (int)app_config('monthly_plan_deadline_day', 26);
$monthlyDeadlineTime = app_config('monthly_plan_deadline_time', '17:00');
$weeklyDeadlineDay   = app_config('weekly_deadline_day', 'Monday');
$weeklyDeadlineTime  = app_config('weekly_deadline_time', '12:00');
$monthlyDaysLeft     = max(0, $monthlyDeadlineDay - $currentEthDay);

// Smart snapshot stats
$userDeptId = $currentUser['department_id'] ?? null;
$stats = [
    'deptTasks' => 0,
    'deptDone' => 0,
    'deptMonthlyPlans' => 0,
    'submissionStatus' => 'DRAFT',
    'monthlySubmitted' => false,
    'totDepts' => 0,
    'totGoals' => 0,
];

try {
    $db = Database::getConnection();
    if ($userDeptId && (Permissions::isDeptHead() || Permissions::canViewAllDepartments())) {
        $taskQ = $db->prepare("
            SELECT COUNT(wt.id) as tot,
                   SUM(CASE WHEN wtr.result = 'DONE' THEN 1 ELSE 0 END) as done
            FROM weekly_tasks wt
            LEFT JOIN weekly_task_results wtr ON wt.id = wtr.weekly_task_id
            WHERE wt.department_id = ? AND wt.year = ? AND wt.month = ? AND wt.week_number = ?
        ");
        $taskQ->execute([$userDeptId, $year, $month, $week]);
        $tRow = $taskQ->fetch();
        $stats['deptTasks'] = (int)($tRow['tot'] ?? 0);
        $stats['deptDone']  = (int)($tRow['done'] ?? 0);

        $mQ = $db->prepare("SELECT COUNT(*) FROM monthly_plans WHERE department_id = ? AND year = ? AND month = ?");
        $mQ->execute([$userDeptId, $year, $month]);
        $stats['deptMonthlyPlans'] = (int)$mQ->fetchColumn();

        $subQ = $db->prepare("SELECT status, submission_status FROM weekly_reports WHERE department_id = ? AND year = ? AND month = ? AND week_number = ?");
        $subQ->execute([$userDeptId, $year, $month, $week]);
        $subRow = $subQ->fetch();
        if ($subRow && $subRow['status'] === 'SUBMITTED') {
            $stats['submissionStatus'] = $subRow['submission_status'] ?? 'ON_TIME';
        }

        $mSubQ = $db->prepare("SELECT status FROM monthly_submissions WHERE department_id = ? AND year = ? AND month = ?");
        $mSubQ->execute([$userDeptId, $year, $month]);
        $mSubRow = $mSubQ->fetch();
        if ($mSubRow && $mSubRow['status'] === 'SUBMITTED') {
            $stats['monthlySubmitted'] = true;
        }
    }
    
    $stats['totDepts'] = (int)$db->query("SELECT COUNT(*) FROM departments WHERE active = 1")->fetchColumn();
    $stats['totGoals'] = (int)$db->query("SELECT COUNT(*) FROM annual_goals WHERE status = 'ACTIVE' OR status IS NULL")->fetchColumn();
} catch (Exception $e) {
    // Graceful fallback with zeroed defaults
}

$completionPct = $stats['deptTasks'] > 0 ? round(($stats['deptDone'] / $stats['deptTasks']) * 100) : 0;

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid py-3" style="max-width: 1280px; margin: 0 auto;">

    <!-- ── Smart Hero Section ── -->
    <div class="overview-hero-wrapper text-center animate-fade-up">
        
        <!-- Live Calendar & Ethiopian Time Window Pills -->
        <div class="d-inline-flex align-items-center gap-2 mb-3 flex-wrap justify-content-center">
            <span class="status-pill status-done shadow-xs">
                <span class="status-dot dot-done status-dot-pulse"></span>
                <span>Active Planning Cycle</span>
            </span>
            <span class="badge period-badge shadow-xs">
                <i class="far fa-calendar-alt text-warning me-1"></i>
                <?= e($period['month']) ?> <?= e($period['year']) ?> &bull; Week <?= (int)$period['week'] ?>
            </span>
            <span class="badge period-badge shadow-xs" title="Ethiopian Time (East Africa Time)">
                <i class="far fa-clock text-warning me-1"></i>
                <?= format_ethiopian_time() ?>
            </span>
        </div>

        <h1 class="overview-greeting-title mb-2">
            <?= get_greeting() ?>, <?= e(explode(' ', $currentUser['full_name'] ?? 'Colleague')[0]) ?>!
        </h1>
        <p class="overview-subtitle mb-4">
            Kaldis Coffee Strategic Planning &amp; Execution Command Center
        </p>

        <!-- Brand Identity Orb -->
        <div class="overview-image-container mb-4">
            <img src="<?= url('/assets/images/logo.png') ?>" alt="Kaldis Coffee" class="overview-coffee-img">
        </div>

        <!-- User Role & Department Capsules -->
        <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap mb-2">
            <span class="badge py-2 px-3 shadow-xs d-inline-flex align-items-center gap-1.5" 
                  style="background: var(--k-gold-soft); color: var(--k-gold); border: 1px solid rgba(245,158,11,.3); font-size: 0.82rem; border-radius: 9999px;">
                <i class="fas fa-shield-halved"></i>
                <span><?= e($currentUser['role_display_name'] ?? 'Staff') ?></span>
            </span>
            <span class="badge py-2 px-3 shadow-xs d-inline-flex align-items-center gap-1.5" 
                  style="background: var(--kaldis-card-bg); color: var(--kaldis-text-secondary); border: 1px solid var(--kaldis-border); font-size: 0.82rem; border-radius: 9999px;">
                <i class="fas fa-building text-secondary"></i>
                <span><?= e($currentUser['department_name'] ?? 'Corporate Office') ?></span>
            </span>
        </div>
    </div>

    <!-- ── Dual Corporate Deadlines Alert Strip ── -->
    <div class="row g-3 mb-4">
        <!-- Monthly Plan Deadline Card (Day 26 in Ethiopian Calendar) -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="background: var(--kaldis-card-bg); border-radius: 14px; border-left: 5px solid #d97706 !important;">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" style="background: rgba(217,119,6,0.12); color: #d97706; width: 42px; height: 42px;">
                                    <i class="fas fa-calendar-check fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0">Monthly Plan Submission Due Day</h6>
                                    <small class="text-muted">Cycle: <strong><?= e($month) ?> <?= e($year) ?></strong></small>
                                </div>
                            </div>
                            <span class="badge bg-warning-subtle text-warning fw-bold px-2.5 py-1.5" style="border-radius: 8px;">
                                Day <?= $monthlyDeadlineDay ?> (<?= e($monthlyDeadlineTime) ?>)
                            </span>
                        </div>

                        <p class="text-muted small mb-3">
                            All departmental operational initiatives must be finalized and transmitted by <strong>Day <?= $monthlyDeadlineDay ?> in Ethiopian calendar (<?= e($monthlyDeadlineTime) ?>)</strong>.
                        </p>

                        <!-- Progress toward Day 26 -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small text-secondary fw-semibold mb-1">
                                <span>Cycle Progress: Day <?= $currentEthDay ?> of 30</span>
                                <span>
                                    <?php if ($currentEthDay <= $monthlyDeadlineDay): ?>
                                        <strong class="text-warning"><?= $monthlyDaysLeft ?> days remaining</strong> until Day <?= $monthlyDeadlineDay ?> cutoff
                                    <?php else: ?>
                                        <strong class="text-danger">Cutoff elapsed for <?= e($month) ?></strong>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="progress" style="height: 8px; border-radius: 9999px; background: rgba(0,0,0,0.06);">
                                <?php $mProg = min(100, round(($currentEthDay / $monthlyDeadlineDay) * 100)); ?>
                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $mProg ?>%;"></div>
                            </div>
                        </div>

                        <!-- Current Submission Status -->
                        <div class="p-2.5 rounded-3 mb-3 d-flex align-items-center justify-content-between" style="background: rgba(0,0,0,0.02); border: 1px dashed var(--kaldis-border);">
                            <span class="small text-muted">
                                <i class="fas fa-info-circle me-1 text-primary"></i> 
                                <?= e($currentUser['department_name'] ?? 'Your Department') ?> Status:
                            </span>
                            <?php if ($stats['monthlySubmitted']): ?>
                                <span class="badge bg-success-subtle text-success fw-bold">
                                    <i class="fas fa-check-circle me-1"></i> Package Submitted
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-dark fw-bold">
                                    <i class="fas fa-pen-to-square me-1"></i> Drafting (<?= $stats['deptMonthlyPlans'] ?> Plans Added)
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= url('/department/monthly_plans.php') ?>" class="btn btn-sm btn-kaldis flex-grow-1">
                            <i class="fas fa-plus me-1"></i> Add / Manage Monthly Plan
                        </a>
                        <?php if ($stats['monthlySubmitted']): ?>
                            <a href="<?= url('/department/monthly_voucher.php') ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-receipt me-1"></i> View Official Voucher
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Weekly Reporting Deadline Card (Monday 12:00 PM) -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="background: var(--kaldis-card-bg); border-radius: 14px; border-left: 5px solid #2563eb !important;">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" style="background: rgba(37,99,235,0.12); color: #2563eb; width: 42px; height: 42px;">
                                    <i class="fas fa-clock fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0">Weekly Reporting Deadline</h6>
                                    <small class="text-muted">Cycle: <strong>Week <?= $week ?> (<?= e($month) ?> <?= e($year) ?>)</strong></small>
                                </div>
                            </div>
                            <span class="badge bg-primary-subtle text-primary fw-bold px-2.5 py-1.5" style="border-radius: 8px;">
                                <?= e($weeklyDeadlineDay) ?> (<?= e($weeklyDeadlineTime) ?>)
                            </span>
                        </div>

                        <p class="text-muted small mb-3">
                            Weekly execution performance reports are audited every <strong><?= e($weeklyDeadlineDay) ?> at <?= e($weeklyDeadlineTime) ?></strong>.
                        </p>

                        <!-- Task Execution Overview -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small text-secondary fw-semibold mb-1">
                                <span>Week <?= $week ?> Tasks: <?= $stats['deptDone'] ?> DONE / <?= $stats['deptTasks'] ?> Scheduled</span>
                                <span class="text-primary fw-bold"><?= $completionPct ?>% Complete</span>
                            </div>
                            <div class="progress" style="height: 8px; border-radius: 9999px; background: rgba(0,0,0,0.06);">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $completionPct ?>%;"></div>
                            </div>
                        </div>

                        <!-- Current Submission Status -->
                        <div class="p-2.5 rounded-3 mb-3 d-flex align-items-center justify-content-between" style="background: rgba(0,0,0,0.02); border: 1px dashed var(--kaldis-border);">
                            <span class="small text-muted">
                                <i class="fas fa-paper-plane me-1 text-primary"></i> Report Submission Status:
                            </span>
                            <?php if ($stats['submissionStatus'] === 'ON_TIME'): ?>
                                <span class="badge bg-success-subtle text-success fw-bold">
                                    <i class="fas fa-check-double me-1"></i> Submitted On-Time
                                </span>
                            <?php elseif ($stats['submissionStatus'] === 'LATE'): ?>
                                <span class="badge bg-warning-subtle text-warning fw-bold">
                                    <i class="fas fa-exclamation-triangle me-1"></i> Submitted Late
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary fw-bold">
                                    <i class="fas fa-clock me-1"></i> In Progress (Due <?= e($weeklyDeadlineDay) ?>)
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= url('/department/performance.php') ?>" class="btn btn-sm btn-primary flex-grow-1">
                            <i class="fas fa-check me-1"></i> Record Weekly Performance
                        </a>
                        <a href="<?= url('/department/weekly_plans.php') ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-list-check me-1"></i> Schedule Tasks
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Smart Metrics Snapshot Strip ── -->
    <div class="overview-stat-strip animate-fade-in mb-4">
        <!-- Dept Tasks Metric -->
        <div class="overview-stat-item">
            <div class="overview-stat-item-icon" style="background: rgba(37,99,235,0.1); color: var(--k-blue);">
                <i class="fas fa-list-check"></i>
            </div>
            <div>
                <div class="overview-stat-item-label">Week <?= $week ?> Tasks</div>
                <div class="overview-stat-item-val"><?= $stats['deptDone'] ?> / <?= $stats['deptTasks'] ?> <span class="badge bg-primary-subtle text-primary" style="font-size:0.7rem"><?= $completionPct ?>%</span></div>
            </div>
        </div>

        <!-- Dept Monthly Plans -->
        <div class="overview-stat-item">
            <div class="overview-stat-item-icon" style="background: rgba(217,119,6,0.1); color: var(--k-gold);">
                <i class="fas fa-calendar-days"></i>
            </div>
            <div>
                <div class="overview-stat-item-label"><?= e($period['month']) ?> Plans</div>
                <div class="overview-stat-item-val"><?= $stats['deptMonthlyPlans'] ?> Plans</div>
            </div>
        </div>

        <!-- Enterprise Departments -->
        <div class="overview-stat-item">
            <div class="overview-stat-item-icon" style="background: rgba(16,185,129,0.1); color: var(--k-done);">
                <i class="fas fa-sitemap"></i>
            </div>
            <div>
                <div class="overview-stat-item-label">Active Departments</div>
                <div class="overview-stat-item-val"><?= $stats['totDepts'] ?> Units</div>
            </div>
        </div>

        <!-- Universal Goals -->
        <div class="overview-stat-item">
            <div class="overview-stat-item-icon" style="background: rgba(124,58,237,0.1); color: var(--k-purple);">
                <i class="fas fa-bullseye"></i>
            </div>
            <div>
                <div class="overview-stat-item-label">Strategic Goals (<?= e($period['year']) ?>)</div>
                <div class="overview-stat-item-val"><?= $stats['totGoals'] ?> Goals</div>
            </div>
        </div>
    </div>

    <!-- ── Launchpad Section: Operations ── -->
    <?php if (Permissions::isDeptHead() || Permissions::canViewAllDepartments()): ?>
    <div class="mb-4">
        <div class="overview-section-title">
            <i class="fas fa-layer-group text-primary"></i>
            <span>Department Operations Command</span>
        </div>
        <div class="row g-3">
            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= url('/department/dashboard.php') ?>" class="overview-launcher-card" style="--card-accent: #2563EB; --card-icon-bg: rgba(37,99,235,0.1);">
                    <div class="overview-launcher-icon-wrap">
                        <i class="fas fa-building-user"></i>
                    </div>
                    <div class="overview-launcher-title">My Workspace</div>
                    <div class="overview-launcher-sub">Plans, weekly tasks and team deliverables</div>
                    <div class="overview-launcher-arrow">Launch &rarr;</div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= url('/department/performance.php') ?>" class="overview-launcher-card" style="--card-accent: #10B981; --card-icon-bg: rgba(16,185,129,0.1);">
                    <div class="overview-launcher-icon-wrap">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="overview-launcher-title">Weekly Performance</div>
                    <div class="overview-launcher-sub">Record task execution: DONE or NOT DONE</div>
                    <div class="overview-launcher-arrow">Launch &rarr;</div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= url('/department/monthly_plans.php') ?>" class="overview-launcher-card" style="--card-accent: #D97706; --card-icon-bg: rgba(217,119,6,0.1);">
                    <div class="overview-launcher-icon-wrap">
                        <i class="fas fa-calendar-days"></i>
                    </div>
                    <div class="overview-launcher-title">Monthly Plans (Day 26)</div>
                    <div class="overview-launcher-sub">Strategic goals breakdown &amp; operational initiatives</div>
                    <div class="overview-launcher-arrow">Launch &rarr;</div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= url('/department/weekly_plans.php') ?>" class="overview-launcher-card" style="--card-accent: #0284C7; --card-icon-bg: rgba(2,132,199,0.1);">
                    <div class="overview-launcher-icon-wrap">
                        <i class="fas fa-list-check"></i>
                    </div>
                    <div class="overview-launcher-title">Weekly Breakdown</div>
                    <div class="overview-launcher-sub">Assign and schedule specific tasks per week</div>
                    <div class="overview-launcher-arrow">Launch &rarr;</div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= url('/department/monthly_voucher.php') ?>" target="_blank" class="overview-launcher-card" style="--card-accent: #F59E0B; --card-icon-bg: rgba(245,158,11,0.12);">
                    <div class="overview-launcher-icon-wrap">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="overview-launcher-title">Official Monthly Voucher</div>
                    <div class="overview-launcher-sub">Printable cryptographically-verified monthly plan voucher</div>
                    <div class="overview-launcher-arrow">View Official Voucher &rarr;</div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= url('/department/weekly_voucher.php') ?>" target="_blank" class="overview-launcher-card" style="--card-accent: #10B981; --card-icon-bg: rgba(16,185,129,0.12);">
                    <div class="overview-launcher-icon-wrap">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="overview-launcher-title">Official Weekly Voucher</div>
                    <div class="overview-launcher-sub">Printable signed official weekly performance voucher</div>
                    <div class="overview-launcher-arrow">View Official Voucher &rarr;</div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= url('/admin/annual_goals.php') ?>" class="overview-launcher-card" style="--card-accent: #7C3AED; --card-icon-bg: rgba(124,58,237,0.1);">
                    <div class="overview-launcher-icon-wrap">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div class="overview-launcher-title">Strategy Map</div>
                    <div class="overview-launcher-sub">Annual goals and strategic objective mapping</div>
                    <div class="overview-launcher-arrow">Launch &rarr;</div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= url('/performance/not_done.php') ?>" class="overview-launcher-card" style="--card-accent: #F43F5E; --card-icon-bg: rgba(244,63,94,0.1);">
                    <div class="overview-launcher-icon-wrap">
                        <i class="fas fa-circle-exclamation"></i>
                    </div>
                    <div class="overview-launcher-title">Why Not Done</div>
                    <div class="overview-launcher-sub">Root cause categorization &amp; recovery plans</div>
                    <div class="overview-launcher-arrow">Launch &rarr;</div>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Launchpad Section: Executive & Performance Oversight ── -->
    <div class="mb-4">
        <div class="overview-section-title">
            <i class="fas fa-chart-line text-warning"></i>
            <span>Executive &amp; Performance Oversight</span>
        </div>
        <div class="row g-3">
            <?php if (Permissions::isGM() || Permissions::isSuperAdmin() || Permissions::isHR()): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= url('/management/dashboard.php') ?>" class="overview-launcher-card" style="--card-accent: #F59E0B; --card-icon-bg: rgba(245,158,11,0.12);">
                    <div class="overview-launcher-icon-wrap">
                        <i class="fas fa-gauge"></i>
                    </div>
                    <div class="overview-launcher-title">GM Dashboard</div>
                    <div class="overview-launcher-sub">Executive oversight, KPI targets &amp; reminders</div>
                    <div class="overview-launcher-arrow">Launch &rarr;</div>
                </a>
            </div>
            <?php endif; ?>

            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= url('/performance/weekly.php') ?>" class="overview-launcher-card" style="--card-accent: #7C3AED; --card-icon-bg: rgba(124,58,237,0.1);">
                    <div class="overview-launcher-icon-wrap">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div class="overview-launcher-title">Weekly Reports</div>
                    <div class="overview-launcher-sub">Organization-wide departmental summary</div>
                    <div class="overview-launcher-arrow">Launch &rarr;</div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= url('/performance/monthly.php') ?>" class="overview-launcher-card" style="--card-accent: #2563EB; --card-icon-bg: rgba(37,99,235,0.1);">
                    <div class="overview-launcher-icon-wrap">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="overview-launcher-title">Monthly Reports</div>
                    <div class="overview-launcher-sub">Submission tracker &amp; compliance status</div>
                    <div class="overview-launcher-arrow">Launch &rarr;</div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= url('/performance/not_done.php') ?>" class="overview-launcher-card" style="--card-accent: #F43F5E; --card-icon-bg: rgba(244,63,94,0.1);">
                    <div class="overview-launcher-icon-wrap">
                        <i class="fas fa-circle-exclamation"></i>
                    </div>
                    <div class="overview-launcher-title">Why Not Done</div>
                    <div class="overview-launcher-sub">Root cause categorization &amp; recovery plans</div>
                    <div class="overview-launcher-arrow">Launch &rarr;</div>
                </a>
            </div>
        </div>
    </div>

    <!-- ── Launchpad Section: System Administration ── -->
    <?php if (Permissions::isSuperAdmin() || Permissions::isITAdmin()): ?>
    <div class="mb-4">
        <div class="overview-section-title">
            <i class="fas fa-sliders text-secondary"></i>
            <span>System Governance &amp; Administration</span>
        </div>
        <div class="row g-3">
            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= url('/admin/dashboard.php') ?>" class="overview-launcher-card" style="--card-accent: #64748B; --card-icon-bg: rgba(100,116,139,0.12);">
                    <div class="overview-launcher-icon-wrap">
                        <i class="fas fa-gauge-high"></i>
                    </div>
                    <div class="overview-launcher-title">Admin Panel</div>
                    <div class="overview-launcher-sub">Master dashboard, security &amp; parameters</div>
                    <div class="overview-launcher-arrow">Launch &rarr;</div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= url('/admin/users.php') ?>" class="overview-launcher-card" style="--card-accent: #3B82F6; --card-icon-bg: rgba(59,130,246,0.1);">
                    <div class="overview-launcher-icon-wrap">
                        <i class="fas fa-users-gear"></i>
                    </div>
                    <div class="overview-launcher-title">User Accounts</div>
                    <div class="overview-launcher-sub">User accounts, roles, permissions &amp; Telegram</div>
                    <div class="overview-launcher-arrow">Launch &rarr;</div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= url('/admin/annual_goals.php') ?>" class="overview-launcher-card" style="--card-accent: #10B981; --card-icon-bg: rgba(16,185,129,0.1);">
                    <div class="overview-launcher-icon-wrap">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div class="overview-launcher-title">Strategy Map</div>
                    <div class="overview-launcher-sub">Annual goals and strategic objective mapping</div>
                    <div class="overview-launcher-arrow">Launch &rarr;</div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= url('/admin/settings.php') ?>" class="overview-launcher-card" style="--card-accent: #8B5CF6; --card-icon-bg: rgba(139,92,246,0.1);">
                    <div class="overview-launcher-icon-wrap">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="overview-launcher-title">Settings &amp; Permissions</div>
                    <div class="overview-launcher-sub">Day 26 deadline, RBAC permissions &amp; Telegram</div>
                    <div class="overview-launcher-arrow">Launch &rarr;</div>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Corporate Planning Lifecycle Roadmap ── -->
    <div class="card border-0 shadow-sm mb-4" style="background: var(--kaldis-card-bg); border-radius: 14px;">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
            <h6 class="fw-bold text-dark mb-1">
                <i class="fas fa-diagram-project text-primary me-2"></i> Kaldis Planning &amp; Execution Lifecycle Roadmap
            </h6>
            <p class="text-muted small mb-0">The 5-stage closed-loop corporate governance protocol.</p>
        </div>
        <div class="card-body p-4">
            <div class="row g-3 text-center">
                <div class="col-md-2 col-6">
                    <div class="p-3 rounded-3 h-100" style="background: rgba(37,99,235,0.04); border: 1px solid rgba(37,99,235,0.12);">
                        <div class="badge bg-primary rounded-circle mb-2" style="width: 26px; height: 26px; line-height: 18px;">1</div>
                        <div class="fw-bold text-dark small mb-1">Annual Strategy</div>
                        <div class="text-muted" style="font-size: 0.72rem;">Executive goals defined for <?= e($period['year']) ?></div>
                    </div>
                </div>

                <div class="col-md-2 col-6">
                    <div class="p-3 rounded-3 h-100" style="background: rgba(124,58,237,0.04); border: 1px solid rgba(124,58,237,0.12);">
                        <div class="badge bg-purple rounded-circle mb-2 text-white" style="width: 26px; height: 26px; line-height: 18px; background: #7c3aed;">2</div>
                        <div class="fw-bold text-dark small mb-1">Monthly Activation</div>
                        <div class="text-muted" style="font-size: 0.72rem;">GM prioritizes monthly goals</div>
                    </div>
                </div>

                <div class="col-md-3 col-12">
                    <div class="p-3 rounded-3 h-100" style="background: rgba(217,119,6,0.06); border: 2px solid #d97706;">
                        <div class="badge bg-warning text-dark rounded-circle mb-2 fw-bold" style="width: 26px; height: 26px; line-height: 18px;">3</div>
                        <div class="fw-bold text-warning small mb-1">Monthly Plan Package</div>
                        <div class="fw-bold text-dark" style="font-size: 0.75rem;">Due: Day <?= $monthlyDeadlineDay ?> Ethiopian (<?= e($monthlyDeadlineTime) ?>)</div>
                    </div>
                </div>

                <div class="col-md-2 col-6">
                    <div class="p-3 rounded-3 h-100" style="background: rgba(2,132,199,0.04); border: 1px solid rgba(2,132,199,0.12);">
                        <div class="badge bg-info rounded-circle mb-2 text-white" style="width: 26px; height: 26px; line-height: 18px;">4</div>
                        <div class="fw-bold text-dark small mb-1">Weekly Execution</div>
                        <div class="text-muted" style="font-size: 0.72rem;">Schedule &amp; record DONE / NOT DONE</div>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="p-3 rounded-3 h-100" style="background: rgba(16,185,129,0.04); border: 1px solid rgba(16,185,129,0.12);">
                        <div class="badge bg-success rounded-circle mb-2" style="width: 26px; height: 26px; line-height: 18px;">5</div>
                        <div class="fw-bold text-dark small mb-1">Monday Reporting</div>
                        <div class="text-muted" style="font-size: 0.72rem;">Due <?= e($weeklyDeadlineDay) ?> <?= e($weeklyDeadlineTime) ?> &amp; Vouchers</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Smart Footer Strip ── -->
    <div class="card p-3 text-center border-0 shadow-xs mb-3" style="background: var(--kaldis-card-bg);">
        <div class="d-flex align-items-center justify-content-center flex-wrap gap-3 text-muted small">
            <span>
                <i class="fas fa-calendar-check text-warning me-1"></i>
                Monthly plan deadline: <strong>Day <?= $monthlyDeadlineDay ?> of Ethiopian Month at <?= e($monthlyDeadlineTime) ?></strong>
            </span>
            <span class="d-none d-md-inline text-muted opacity-50">&bull;</span>
            <span>
                <i class="fas fa-clock text-primary me-1"></i>
                Weekly reporting deadline: <strong>Every <?= e($weeklyDeadlineDay) ?> at <?= e($weeklyDeadlineTime) ?></strong>
            </span>
            <span class="d-none d-md-inline text-muted opacity-50">&bull;</span>
            <span class="d-none d-md-inline">
                Theme: <span class="kbd-smart">Alt</span> + <span class="kbd-smart">D</span> Light/Dark
            </span>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

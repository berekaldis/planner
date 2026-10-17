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

// Smart snapshot stats
$userDeptId = $currentUser['department_id'] ?? null;
$stats = [
    'deptTasks' => 0,
    'deptDone' => 0,
    'deptMonthlyPlans' => 0,
    'submissionStatus' => 'DRAFT',
    'totDepts' => 0,
    'totGoals' => 0,
];

try {
    $db = Database::getConnection();
    if ($userDeptId && Permissions::isDeptHead()) {
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
    } else {
        $stats['totDepts'] = (int)$db->query("SELECT COUNT(*) FROM departments WHERE active = 1")->fetchColumn();
        $stats['totGoals'] = (int)$db->query("SELECT COUNT(*) FROM annual_goals WHERE status = 'ACTIVE' OR status IS NULL")->fetchColumn();
    }
} catch (Exception $e) {
    // Graceful fallback with zeroed defaults
}

$completionPct = $stats['deptTasks'] > 0 ? round(($stats['deptDone'] / $stats['deptTasks']) * 100) : 0;

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid py-3" style="max-width: 1200px; margin: 0 auto;">

    <!-- ── Smart Hero Section ── -->
    <div class="overview-hero-wrapper text-center animate-fade-up">
        
        <!-- Live Calendar Window Pill -->
        <div class="d-inline-flex align-items-center gap-2 mb-3">
            <span class="status-pill status-done shadow-xs">
                <span class="status-dot dot-done status-dot-pulse"></span>
                <span>Active Planning Cycle</span>
            </span>
            <span class="badge period-badge shadow-xs">
                <i class="far fa-calendar-alt text-warning me-1"></i>
                <?= e($period['month']) ?> <?= e($period['year']) ?> &bull; Week <?= (int)$period['week'] ?>
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

    <!-- ── Smart Metrics Snapshot Strip ── -->
    <div class="overview-stat-strip animate-fade-in mb-4">
        <?php if (Permissions::isDeptHead() && $userDeptId): ?>
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

            <!-- Submission Status -->
            <div class="overview-stat-item">
                <div class="overview-stat-item-icon" style="background: rgba(16,185,129,0.1); color: var(--k-done);">
                    <i class="fas fa-paper-plane"></i>
                </div>
                <div>
                    <div class="overview-stat-item-label">Weekly Submission</div>
                    <div class="overview-stat-item-val">
                        <?php if ($stats['submissionStatus'] === 'ON_TIME'): ?>
                            <span class="status-pill status-done"><span class="status-dot dot-done"></span> On-Time</span>
                        <?php elseif ($stats['submissionStatus'] === 'LATE'): ?>
                            <span class="status-pill status-late"><span class="status-dot dot-late"></span> Submitted Late</span>
                        <?php else: ?>
                            <span class="status-pill status-draft"><span class="status-dot dot-gold"></span> In Progress</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Enterprise Overview Metrics -->
            <div class="overview-stat-item">
                <div class="overview-stat-item-icon" style="background: rgba(37,99,235,0.1); color: var(--k-blue);">
                    <i class="fas fa-sitemap"></i>
                </div>
                <div>
                    <div class="overview-stat-item-label">Active Departments</div>
                    <div class="overview-stat-item-val"><?= $stats['totDepts'] ?> Units</div>
                </div>
            </div>

            <div class="overview-stat-item">
                <div class="overview-stat-item-icon" style="background: rgba(16,185,129,0.1); color: var(--k-done);">
                    <i class="fas fa-bullseye"></i>
                </div>
                <div>
                    <div class="overview-stat-item-label">Strategic Goals (<?= e($period['year']) ?>)</div>
                    <div class="overview-stat-item-val"><?= $stats['totGoals'] ?> Goals</div>
                </div>
            </div>

            <div class="overview-stat-item">
                <div class="overview-stat-item-icon" style="background: rgba(217,119,6,0.1); color: var(--k-gold);">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <div class="overview-stat-item-label">Reporting Deadline</div>
                    <div class="overview-stat-item-val">Mon 12:00 PM</div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Universal Execution Window Badge -->
        <div class="overview-stat-item d-none d-md-flex">
            <div class="overview-stat-item-icon" style="background: rgba(124,58,237,0.1); color: var(--k-purple);">
                <i class="fas fa-shield-halved"></i>
            </div>
            <div>
                <div class="overview-stat-item-label">System Mode</div>
                <div class="overview-stat-item-val" style="font-size:0.95rem; font-weight:700">Production Live</div>
            </div>
        </div>
    </div>

    <!-- ── Launchpad Section: Operations ── -->
    <?php if (Permissions::isDeptHead() || Permissions::canViewAllDepartments()): ?>
    <div class="mb-4">
        <div class="overview-section-title">
            <i class="fas fa-layer-group text-primary"></i>
            <span>Department Operations</span>
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
                    <div class="overview-launcher-title">Monthly Plans</div>
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
                    <div class="overview-launcher-title">Bot &amp; Settings</div>
                    <div class="overview-launcher-sub">Telegram automation, reminders &amp; cron schedules</div>
                    <div class="overview-launcher-arrow">Launch &rarr;</div>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Smart Footer Strip & Shortcuts ── -->
    <div class="card p-3 text-center border-0 shadow-xs mb-3" style="background: var(--kaldis-card-bg);">
        <div class="d-flex align-items-center justify-content-center flex-wrap gap-3 text-muted small">
            <span>
                <i class="fas fa-clock text-warning me-1"></i>
                Reporting deadline: Every <strong>Monday at 12:00 PM</strong>
            </span>
            <span class="d-none d-md-inline text-muted opacity-50">&bull;</span>
            <span class="d-none d-md-inline">
                Navigation: <span class="kbd-smart">Alt</span> + <span class="kbd-smart">S</span> Sidebar
            </span>
            <span class="d-none d-md-inline text-muted opacity-50">&bull;</span>
            <span class="d-none d-md-inline">
                Theme: <span class="kbd-smart">Alt</span> + <span class="kbd-smart">D</span> Light/Dark
            </span>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

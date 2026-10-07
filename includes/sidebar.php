<?php
/**
 * Role-Based Dynamic Sidebar Navigation with Collapse Support & Tooltips
 * Kaldis Coffee PLC
 */

require_once __DIR__ . '/permissions.php';

$currentUri = $_SERVER['REQUEST_URI'] ?? '';
function is_active(string $path): string {
    global $currentUri;
    return str_contains($currentUri, $path) ? 'active' : '';
}
?>

<aside class="kaldis-sidebar">
    <div class="sidebar-heading">Workflow & Dashboards</div>
    <ul class="nav flex-column">
        <?php if (Permissions::isGM()): ?>
            <li class="nav-item">
                <a class="nav-link <?= is_active('/management/dashboard.php') ?>" href="<?= url('/management/dashboard.php') ?>" 
                   data-bs-toggle="tooltip" data-bs-placement="right" title="GM Dashboard">
                    <i class="fas fa-chart-line"></i>
                    <span class="nav-text">GM Dashboard</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if (Permissions::isDeptHead() || Permissions::isSuperAdmin()): ?>
            <li class="nav-item">
                <a class="nav-link <?= is_active('/department/dashboard.php') ?>" href="<?= url('/department/dashboard.php') ?>" 
                   data-bs-toggle="tooltip" data-bs-placement="right" title="Dept Workspace">
                    <i class="fas fa-building-user"></i>
                    <span class="nav-text">Dept Workspace</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if (Permissions::isSuperAdmin() || Permissions::isITAdmin()): ?>
            <li class="nav-item">
                <a class="nav-link <?= is_active('/admin/dashboard.php') ?>" href="<?= url('/admin/dashboard.php') ?>" 
                   data-bs-toggle="tooltip" data-bs-placement="right" title="Admin Dashboard">
                    <i class="fas fa-gauge-high"></i>
                    <span class="nav-text">Admin Dashboard</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <div class="sidebar-heading mt-3">Strategic Alignment</div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= is_active('/admin/annual_goals.php') || is_active('/management/strategy.php') ?>" 
               href="<?= Permissions::isGM() || Permissions::isSuperAdmin() ? url('/admin/annual_goals.php') : url('/management/strategy.php') ?>" 
               data-bs-toggle="tooltip" data-bs-placement="right" title="Annual Strategy Plan">
                <i class="fas fa-bullseye"></i>
                <span class="nav-text">Annual Strategy Plan</span>
            </a>
        </li>

        <?php if (Permissions::canActivateStrategy()): ?>
            <li class="nav-item">
                <a class="nav-link <?= is_active('/admin/monthly_activation.php') || is_active('/management/activation.php') ?>" 
                   href="<?= url('/admin/monthly_activation.php') ?>" 
                   data-bs-toggle="tooltip" data-bs-placement="right" title="GM Monthly Activation">
                    <i class="fas fa-toggle-on text-warning"></i>
                    <span class="nav-text">GM Monthly Activation</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <div class="sidebar-heading mt-3">Operational Planning</div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= is_active('monthly_plans.php') ?>" 
               href="<?= Permissions::isDeptHead() ? url('/department/monthly_plans.php') : url('/admin/monthly_plans.php') ?>" 
               data-bs-toggle="tooltip" data-bs-placement="right" title="Monthly Plans">
                <i class="fas fa-calendar-days"></i>
                <span class="nav-text">Monthly Plans</span>
            </a>
        </li>
        <?php if (!Permissions::isGM()): ?>
            <li class="nav-item">
                <a class="nav-link <?= is_active('weekly_plans.php') ?>" 
                   href="<?= Permissions::isDeptHead() ? url('/department/weekly_plans.php') : url('/admin/weekly_plans.php') ?>" 
                   data-bs-toggle="tooltip" data-bs-placement="right" title="Weekly Plans">
                    <i class="fas fa-list-check"></i>
                    <span class="nav-text">Weekly Plans</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <div class="sidebar-heading mt-3">Execution & Performance</div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= is_active('/department/performance.php') || is_active('/performance/weekly.php') ?>" 
               href="<?= Permissions::isDeptHead() ? url('/department/performance.php') : url('/performance/weekly.php') ?>" 
               data-bs-toggle="tooltip" data-bs-placement="right" title="Weekly Performance">
                <i class="fas fa-clipboard-check text-success"></i>
                <span class="nav-text">Weekly Performance</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= is_active('/performance/not_done.php') ?>" href="<?= url('/performance/not_done.php') ?>" 
               data-bs-toggle="tooltip" data-bs-placement="right" title="Why Not Done Analysis">
                <i class="fas fa-circle-exclamation text-danger"></i>
                <span class="nav-text">Why Not Done Analysis</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= is_active('achievements.php') ?>" 
               href="<?= Permissions::isDeptHead() ? url('/department/achievements.php') : url('/performance/achievements.php') ?>" 
               data-bs-toggle="tooltip" data-bs-placement="right" title="Weekly Achievements">
                <i class="fas fa-award text-warning"></i>
                <span class="nav-text">Weekly Achievements</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= is_active('challenges.php') ?>" 
               href="<?= Permissions::isDeptHead() ? url('/department/challenges.php') : url('/performance/challenges.php') ?>" 
               data-bs-toggle="tooltip" data-bs-placement="right" title="Weekly Challenges">
                <i class="fas fa-triangle-exclamation text-warning"></i>
                <span class="nav-text">Weekly Challenges</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-heading mt-3">Management & Oversight</div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= is_active('/performance/monthly.php') ?>" href="<?= url('/performance/monthly.php') ?>" 
               data-bs-toggle="tooltip" data-bs-placement="right" title="Monthly Performance">
                <i class="fas fa-calendar-check"></i>
                <span class="nav-text">Monthly Performance</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= is_active('/performance/annual.php') ?>" href="<?= url('/performance/annual.php') ?>" 
               data-bs-toggle="tooltip" data-bs-placement="right" title="Annual Performance">
                <i class="fas fa-diagram-project"></i>
                <span class="nav-text">Annual Performance</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= is_active('/admin/reports.php') ?>" href="<?= url('/admin/reports.php') ?>" 
               data-bs-toggle="tooltip" data-bs-placement="right" title="Reports & Tracking">
                <i class="fas fa-file-invoice"></i>
                <span class="nav-text">Reports & Tracking</span>
            </a>
        </li>
    </ul>

    <?php if (Permissions::isSuperAdmin() || Permissions::isITAdmin()): ?>
        <div class="sidebar-heading mt-3">System Administration</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= is_active('/admin/departments.php') ?>" href="<?= url('/admin/departments.php') ?>" 
                   data-bs-toggle="tooltip" data-bs-placement="right" title="Departments">
                    <i class="fas fa-sitemap"></i>
                    <span class="nav-text">Departments</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_active('/admin/users.php') ?>" href="<?= url('/admin/users.php') ?>" 
                   data-bs-toggle="tooltip" data-bs-placement="right" title="User Accounts">
                    <i class="fas fa-users-gear"></i>
                    <span class="nav-text">User Accounts</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_active('/admin/settings.php') ?>" href="<?= url('/admin/settings.php') ?>" 
                   data-bs-toggle="tooltip" data-bs-placement="right" title="Settings & Telegram">
                    <i class="fas fa-sliders"></i>
                    <span class="nav-text">Settings & Telegram</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_active('/admin/audit_logs.php') ?>" href="<?= url('/admin/audit_logs.php') ?>" 
                   data-bs-toggle="tooltip" data-bs-placement="right" title="Audit Trail">
                    <i class="fas fa-fingerprint"></i>
                    <span class="nav-text">Audit Trail</span>
                </a>
            </li>
        </ul>
    <?php endif; ?>

    <!-- Sidebar Bottom Collapse / Expand Button -->
    <div class="sidebar-footer">
        <button id="sidebarCollapseBtn" class="btn btn-sm btn-outline-secondary w-100 d-flex align-items-center justify-content-center" type="button" title="Collapse / Expand Sidebar">
            <i id="sidebarCollapseIcon" class="fas fa-chevron-left"></i>
            <span class="footer-text ms-2 small fw-bold">Collapse</span>
        </button>
    </div>
</aside>

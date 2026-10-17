<?php
/**
 * Dynamic Full-Height Sidebar Navigation - Kaldis Coffee PLC
 */

require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/auth.php';

$currentUri = $_SERVER['REQUEST_URI'] ?? '';
function is_active(string $path): string {
    global $currentUri;
    return str_contains($currentUri, $path) ? 'active' : '';
}

$currentUser = Auth::user();
?>

<aside class="kaldis-sidebar">

    <!-- Floating Edge Toggle (Desktop only) — OUTSIDE inner-wrapper so overflow:hidden cannot clip it -->
    <button id="sidebarEdgeToggle" class="sidebar-edge-toggle d-none d-lg-flex" type="button"
            title="Toggle Sidebar (Alt+S)" aria-label="Toggle Sidebar">
        <i class="fas fa-chevron-left edge-chevron"></i>
    </button>

    <div class="sidebar-inner-wrapper">
        <!-- Mobile Drawer Close Header (Only shown on mobile screen when drawer is open) -->
        <div class="sidebar-mobile-header d-flex align-items-center justify-content-between px-3 py-3 border-bottom border-light border-opacity-10 d-lg-none">
            <div class="d-flex align-items-center gap-2">
                <img src="<?= url('/assets/images/logo.png') ?>" alt="Kaldis Coffee" style="width: 28px; height: 28px; border-radius: 50%;">
                <span class="fw-bold font-monospace small text-dark">KALDIS PLANNER</span>
            </div>
            <button class="btn btn-sm btn-link text-muted p-1 border-0" id="sidebarMobileClose" type="button" aria-label="Close Sidebar">
                <i class="fas fa-xmark fs-5"></i>
            </button>
        </div>

        <!-- Modern Search Menu Box with Shortcut Badge -->
        <div class="sidebar-search-box">
            <div class="input-group input-group-sm">
                <span class="input-group-text">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" id="sidebarMenuSearch" class="form-control" placeholder="Search menu..." autocomplete="off">
                <span class="input-group-text pe-1 d-none d-lg-flex">
                    <kbd class="bg-white border text-muted px-1.5 py-0.5 rounded shadow-xs" style="font-size: 0.65rem;">Alt+S</kbd>
                </span>
            </div>
        </div>

        <!-- Navigation List -->
        <div class="sidebar-nav-scroll flex-grow-1">
            
            <!-- Dashboards -->
            <div class="sidebar-heading">Dashboards</div>
            <ul class="nav flex-column mb-3">
                <li class="nav-item">
                    <a class="nav-link <?= is_active('/overview.php') ?>" href="<?= url('/overview.php') ?>" 
                       data-bs-toggle="tooltip" data-bs-placement="right" title="OverView">
                        <span class="nav-icon-wrap icon-dashboard"><i class="fas fa-house"></i></span>
                        <span class="nav-text">OverView</span>
                        <i class="fas fa-chevron-right sidebar-chevron"></i>
                    </a>
                </li>
                <?php if (Permissions::isDeptHead() || Permissions::canViewAllDepartments()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= is_active('/department/dashboard.php') ?>" href="<?= url('/department/dashboard.php') ?>" 
                           data-bs-toggle="tooltip" data-bs-placement="right" title="Department Workspace">
                            <span class="nav-icon-wrap icon-dashboard"><i class="fas fa-building-user"></i></span>
                            <span class="nav-text">Department Workspace</span>
                            <i class="fas fa-chevron-right sidebar-chevron"></i>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (Permissions::isGM() || Permissions::isSuperAdmin() || Permissions::isHR()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= is_active('/management/dashboard.php') ?>" href="<?= url('/management/dashboard.php') ?>" 
                           data-bs-toggle="tooltip" data-bs-placement="right" title="GM Executive Dashboard">
                            <span class="nav-icon-wrap icon-dashboard"><i class="fas fa-chart-line"></i></span>
                            <span class="nav-text">GM Executive Dashboard</span>
                            <i class="fas fa-chevron-right sidebar-chevron"></i>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (Permissions::isSuperAdmin() || Permissions::isITAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= is_active('/admin/dashboard.php') ?>" href="<?= url('/admin/dashboard.php') ?>" 
                           data-bs-toggle="tooltip" data-bs-placement="right" title="Admin Dashboard">
                            <span class="nav-icon-wrap icon-admin"><i class="fas fa-gauge-high"></i></span>
                            <span class="nav-text">Admin Dashboard</span>
                            <i class="fas fa-chevron-right sidebar-chevron"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- Strategic Alignment -->
            <div class="sidebar-heading">Strategic Alignment</div>
            <ul class="nav flex-column mb-3">
                <li class="nav-item">
                    <a class="nav-link <?= is_active('/admin/annual_goals.php') || is_active('/management/strategy.php') ?>" 
                       href="<?= Permissions::isGM() || Permissions::isSuperAdmin() ? url('/admin/annual_goals.php') : url('/management/strategy.php') ?>" 
                       data-bs-toggle="tooltip" data-bs-placement="right" title="Annual Strategy Plan">
                        <span class="nav-icon-wrap icon-strategy"><i class="fas fa-bullseye"></i></span>
                        <span class="nav-text">Annual Strategy Plan</span>
                        <i class="fas fa-chevron-right sidebar-chevron"></i>
                    </a>
                </li>

                <?php if (Permissions::canActivateStrategy()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= is_active('/admin/monthly_activation.php') || is_active('/management/activation.php') ?>" 
                           href="<?= url('/admin/monthly_activation.php') ?>" 
                           data-bs-toggle="tooltip" data-bs-placement="right" title="GM Monthly Activation">
                            <span class="nav-icon-wrap icon-strategy"><i class="fas fa-toggle-on"></i></span>
                            <span class="nav-text">GM Monthly Activation</span>
                            <i class="fas fa-chevron-right sidebar-chevron"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- Operational Planning -->
            <div class="sidebar-heading">Operational Planning</div>
            <ul class="nav flex-column mb-3">
                <li class="nav-item">
                    <a class="nav-link <?= is_active('monthly_plans.php') ?>" 
                       href="<?= Permissions::isDeptHead() ? url('/department/monthly_plans.php') : url('/admin/monthly_plans.php') ?>" 
                       data-bs-toggle="tooltip" data-bs-placement="right" title="Monthly Plans">
                        <span class="nav-icon-wrap icon-planning"><i class="fas fa-calendar-days"></i></span>
                        <span class="nav-text">Monthly Plans</span>
                        <i class="fas fa-chevron-right sidebar-chevron"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= is_active('weekly_plans.php') ?>" 
                       href="<?= Permissions::isDeptHead() ? url('/department/weekly_plans.php') : url('/admin/weekly_plans.php') ?>" 
                       data-bs-toggle="tooltip" data-bs-placement="right" title="Weekly Plans">
                        <span class="nav-icon-wrap icon-planning"><i class="fas fa-list-check"></i></span>
                        <span class="nav-text">Weekly Plans</span>
                        <i class="fas fa-chevron-right sidebar-chevron"></i>
                    </a>
                </li>
            </ul>

            <!-- Execution & Performance -->
            <div class="sidebar-heading">Execution &amp; Performance</div>
            <ul class="nav flex-column mb-3">
                <li class="nav-item">
                    <a class="nav-link <?= is_active('/department/performance.php') || is_active('/performance/weekly.php') ?>" 
                       href="<?= Permissions::isDeptHead() ? url('/department/performance.php') : url('/performance/weekly.php') ?>" 
                       data-bs-toggle="tooltip" data-bs-placement="right" title="Weekly Performance">
                        <span class="nav-icon-wrap icon-performance"><i class="fas fa-clipboard-check"></i></span>
                        <span class="nav-text">Weekly Performance</span>
                        <i class="fas fa-chevron-right sidebar-chevron"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= is_active('/performance/not_done.php') ?>" href="<?= url('/performance/not_done.php') ?>" 
                       data-bs-toggle="tooltip" data-bs-placement="right" title="Why Not Done Analysis">
                        <span class="nav-icon-wrap icon-performance"><i class="fas fa-circle-exclamation"></i></span>
                        <span class="nav-text">Why Not Done Analysis</span>
                        <i class="fas fa-chevron-right sidebar-chevron"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= is_active('achievements.php') ?>" 
                       href="<?= Permissions::isDeptHead() ? url('/department/achievements.php') : url('/performance/achievements.php') ?>" 
                       data-bs-toggle="tooltip" data-bs-placement="right" title="Weekly Achievements">
                        <span class="nav-icon-wrap icon-performance"><i class="fas fa-award"></i></span>
                        <span class="nav-text">Weekly Achievements</span>
                        <i class="fas fa-chevron-right sidebar-chevron"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= is_active('challenges.php') ?>" 
                       href="<?= Permissions::isDeptHead() ? url('/department/challenges.php') : url('/performance/challenges.php') ?>" 
                       data-bs-toggle="tooltip" data-bs-placement="right" title="Weekly Challenges">
                        <span class="nav-icon-wrap icon-performance"><i class="fas fa-triangle-exclamation"></i></span>
                        <span class="nav-text">Weekly Challenges</span>
                        <i class="fas fa-chevron-right sidebar-chevron"></i>
                    </a>
                </li>
            </ul>

            <!-- Management & Oversight -->
            <div class="sidebar-heading">Management &amp; Oversight</div>
            <ul class="nav flex-column mb-3">
                <li class="nav-item">
                    <a class="nav-link <?= is_active('/performance/monthly.php') ?>" href="<?= url('/performance/monthly.php') ?>" 
                       data-bs-toggle="tooltip" data-bs-placement="right" title="Monthly Performance">
                        <span class="nav-icon-wrap icon-performance"><i class="fas fa-calendar-check"></i></span>
                        <span class="nav-text">Monthly Performance</span>
                        <i class="fas fa-chevron-right sidebar-chevron"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= is_active('/performance/annual.php') ?>" href="<?= url('/performance/annual.php') ?>" 
                       data-bs-toggle="tooltip" data-bs-placement="right" title="Annual Performance">
                        <span class="nav-icon-wrap icon-performance"><i class="fas fa-diagram-project"></i></span>
                        <span class="nav-text">Annual Performance</span>
                        <i class="fas fa-chevron-right sidebar-chevron"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= is_active('/admin/reports.php') ?>" href="<?= url('/admin/reports.php') ?>" 
                       data-bs-toggle="tooltip" data-bs-placement="right" title="Reports & Tracking">
                        <span class="nav-icon-wrap icon-admin"><i class="fas fa-file-invoice"></i></span>
                        <span class="nav-text">Reports &amp; Tracking</span>
                        <i class="fas fa-chevron-right sidebar-chevron"></i>
                    </a>
                </li>
            </ul>

            <!-- System Administration -->
            <?php if (Permissions::isSuperAdmin() || Permissions::isITAdmin()): ?>
                <div class="sidebar-heading">System Administration</div>
                <ul class="nav flex-column mb-3">
                    <li class="nav-item">
                        <a class="nav-link <?= is_active('/admin/departments.php') ?>" href="<?= url('/admin/departments.php') ?>" 
                           data-bs-toggle="tooltip" data-bs-placement="right" title="Departments">
                            <span class="nav-icon-wrap icon-admin"><i class="fas fa-sitemap"></i></span>
                            <span class="nav-text">Departments</span>
                            <i class="fas fa-chevron-right sidebar-chevron"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= is_active('/admin/users.php') ?>" href="<?= url('/admin/users.php') ?>" 
                           data-bs-toggle="tooltip" data-bs-placement="right" title="User Accounts">
                            <span class="nav-icon-wrap icon-admin"><i class="fas fa-users-gear"></i></span>
                            <span class="nav-text">User Accounts</span>
                            <i class="fas fa-chevron-right sidebar-chevron"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= is_active('/admin/settings.php') ?>" href="<?= url('/admin/settings.php') ?>" 
                           data-bs-toggle="tooltip" data-bs-placement="right" title="Settings">
                            <span class="nav-icon-wrap icon-admin"><i class="fas fa-sliders"></i></span>
                            <span class="nav-text">Settings</span>
                            <i class="fas fa-chevron-right sidebar-chevron"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= is_active('/admin/audit_logs.php') ?>" href="<?= url('/admin/audit_logs.php') ?>" 
                           data-bs-toggle="tooltip" data-bs-placement="right" title="Audit Trail">
                            <span class="nav-icon-wrap icon-admin"><i class="fas fa-fingerprint"></i></span>
                            <span class="nav-text">Audit Trail</span>
                            <i class="fas fa-chevron-right sidebar-chevron"></i>
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Integrated User Profile Card -->
        <div class="sidebar-user-section">
            <div class="sidebar-user-card">
                <div class="sidebar-avatar-wrap">
                    <?= strtoupper(substr($currentUser['full_name'] ?? $currentUser['username'] ?? 'U', 0, 1)) ?>
                    <span class="sidebar-online-dot" title="Active"></span>
                </div>
                <div class="sidebar-user-info overflow-hidden">
                    <div class="fw-bold small text-white text-truncate"><?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['username'] ?? 'User') ?></div>
                    <div class="text-white-50" style="font-size: 0.7rem;"><?= htmlspecialchars($currentUser['role_name'] ?? $currentUser['role'] ?? 'User') ?></div>
                </div>
                <a href="<?= url('/logout.php') ?>" class="btn btn-sm btn-link text-muted p-1 border-0 ms-auto mb-0" title="Log Out">
                    <i class="fas fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        </div>
    </div>
</aside>






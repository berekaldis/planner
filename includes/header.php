<?php
/**
 * Global Header with Light/Dark Theme & Sidebar Toggle
 * Kaldis Coffee PLC
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/csrf.php';

$currentUser = Auth::user();
$flash = get_flash();
$config = app_config();

$pageTitle = $pageTitle ?? $config['app_name'];

// Fetch unread in-app notifications if logged in
$notifications = [];
if (Auth::check()) {
    try {
        $db = Database::getConnection();
        $notifStmt = $db->prepare("
            SELECT * FROM notifications 
            WHERE (user_id = :uid OR department_id = :did OR (user_id IS NULL AND department_id IS NULL))
            ORDER BY created_at DESC LIMIT 6
        ");
        $notifStmt->execute([
            ':uid' => Auth::id(),
            ':did' => $currentUser['department_id'] ?? 0
        ]);
        $notifications = $notifStmt->fetchAll();
    } catch (Exception $e) {
        // Silent fallback
    }
}
$unreadCount = count(array_filter($notifications, fn($n) => empty($n['is_read'])));
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= e($pageTitle) ?> &mdash; <?= e($config['company_name']) ?></title>
    
    <!-- Inline Theme & Sidebar State Initializer (Eliminates Flash) -->
    <script>
        window.APP_BASE_URL = '<?= url('') ?>';
        (function() {
            const savedTheme = localStorage.getItem('kaldis_theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
            if (localStorage.getItem('sidebar_collapsed') === 'true' && window.innerWidth >= 992) {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        })();
    </script>

    <!-- Google Fonts: Plus Jakarta Sans & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Kaldis Custom Stylesheet -->
    <link href="<?= url('/assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>

    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand kaldis-navbar sticky-top">
        <div class="container-fluid px-3">
            
            <!-- Left: Sidebar Toggle & Official Kaldis Brand Logo -->
            <div class="d-flex align-items-center">
                <button id="sidebarToggle" class="btn btn-outline-secondary btn-sm me-3 border shadow-sm" type="button" 
                        title="Toggle Sidebar Navigation (Alt+S)" aria-label="Toggle navigation">
                    <i class="fas fa-bars"></i>
                </button>
                <a class="navbar-brand me-3" href="<?= url('/index.php') ?>">
                    <img src="<?= url('/assets/images/logo.png') ?>" alt="Kaldis Coffee" class="kaldis-brand-img">
                    <div class="d-none d-md-block ms-2 lh-sm">
                        <div class="brand-title">KALDIS COFFEE PLC</div>
                        <div class="brand-subtitle">PLANNING & PERFORMANCE</div>
                    </div>
                </a>
            </div>

            <!-- Right: Active Period Badge, Theme Toggle, Notification Bell & User Profile -->
            <div class="d-flex align-items-center gap-2 gap-md-3 ms-auto">
                

                <!-- Planning Period Badge -->
                <?php $ethPeriod = get_ethiopian_period(); ?>
                <div class="d-none d-lg-flex align-items-center me-1">
                    <span class="badge period-badge me-2 py-2 px-3 shadow-sm">
                        <i class="far fa-calendar-alt text-warning me-1"></i> <?= e($ethPeriod['month']) ?> <?= e($ethPeriod['year']) ?> &bull; Week <?= (int)$ethPeriod['week'] ?>
                    </span>
                    <span class="badge dept-badge py-2 px-3 shadow-sm">
                        <i class="fas fa-building text-muted me-1"></i> <?= Permissions::isGM() ? 'Executive Management' : (Permissions::isSuperAdmin() ? 'Enterprise Administration' : e($currentUser['department_name'] ?? 'Corporate Office')) ?>
                    </span>
                </div>

                <!-- Theme Toggle Button (Light Mode / Dark Mode) -->
                <button id="themeToggle" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center shadow-sm" 
                        style="width: 36px; height: 36px;" type="button" title="Toggle Light / Dark Mode (Alt+D)">
                    <i id="themeToggleIcon" class="fas fa-moon text-secondary"></i>
                </button>

                <!-- Notifications Center Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm rounded-circle position-relative d-flex align-items-center justify-content-center shadow-sm" 
                            style="width: 36px; height: 36px;" type="button" data-bs-toggle="dropdown" title="Notifications">
                        <i class="fas fa-bell text-secondary"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light font-monospace" style="font-size: 0.65rem;">
                                <?= $unreadCount ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-lg notification-menu p-0">
                        <div class="p-3 bg-light text-dark d-flex justify-content-between align-items-center border-bottom border-warning">
                            <span class="fw-bold small"><i class="fas fa-bell me-1 text-warning"></i> Notifications</span>
                            <span class="badge bg-warning text-dark font-monospace"><?= count($notifications) ?> Total</span>
                        </div>
                        <?php if (empty($notifications)): ?>
                            <div class="p-4 text-center text-muted small">
                                <i class="fas fa-envelope-open fa-2x mb-2 opacity-25 d-block"></i>
                                No notifications at this time.
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $n): ?>
                                <div class="notification-item <?= empty($n['is_read']) ? 'bg-light-subtle' : '' ?>">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <strong class="text-dark small"><?= e($n['title']) ?></strong>
                                        <small class="text-muted ms-2" style="font-size: 0.7rem;"><?= date('M d', strtotime($n['created_at'])) ?></small>
                                    </div>
                                    <div class="small text-secondary"><?= e($n['message']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div class="p-2 text-center bg-body-tertiary border-top">
                            <a href="<?= url('/performance/weekly.php') ?>" class="small text-decoration-none fw-bold">Open Performance Tracker &rarr;</a>
                        </div>
                    </div>
                </div>

                <!-- User Profile Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle d-flex align-items-center gap-2 user-profile-btn shadow-sm" type="button" data-bs-toggle="dropdown">
                        <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center fw-bold" style="width: 26px; height: 26px; font-size: 0.8rem;">
                            <?= strtoupper(substr($currentUser['full_name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <span class="d-none d-sm-inline fw-semibold small user-profile-name">
                            <?= e($currentUser['full_name'] ?? 'User') ?>
                        </span>
                        <span class="badge bg-warning text-dark text-uppercase d-none d-md-inline" style="font-size: 0.65rem;">
                            <?= e($currentUser['role_display_name'] ?? 'Staff') ?>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li>
                            <div class="dropdown-item-text">
                                <small class="text-muted d-block">Signed in as</small>
                                <strong><?= e($currentUser['username'] ?? '') ?></strong>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if (Permissions::isSuperAdmin() || Permissions::isITAdmin()): ?>
                            <li><a class="dropdown-item" href="<?= url('/admin/settings.php') ?>"><i class="fas fa-sliders me-2 text-muted"></i> System Settings</a></li>
                            <li><a class="dropdown-item" href="<?= url('/admin/audit_logs.php') ?>"><i class="fas fa-history me-2 text-muted"></i> Audit Logs</a></li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item text-danger" href="<?= url('/logout.php') ?>"><i class="fas fa-sign-out-alt me-2"></i> Sign Out</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="sidebarBackdrop" class="sidebar-backdrop"></div>

    <!-- App Wrapper (Sidebar + Main Content) -->
    <div class="app-wrapper">
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <main class="main-content">
            <!-- Flash Alert -->
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

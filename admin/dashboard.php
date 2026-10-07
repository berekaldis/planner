<?php
/**
 * Administrator Dashboard
 * Kaldis Coffee PLC
 */

$pageTitle = 'Administrator Dashboard';
require_once __DIR__ . '/../includes/header.php';

Permissions::requireRole([Permissions::ROLE_SUPER_ADMIN, Permissions::ROLE_IT_ADMIN]);

$db = Database::getConnection();

// System Counts
$totUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totDepts = $db->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$totGoals = $db->query("SELECT COUNT(*) FROM annual_goals")->fetchColumn();
$totAudits = $db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();

// Telegram Users count
$totTelegramUsers = $db->query("SELECT COUNT(*) FROM telegram_users WHERE is_active = 1")->fetchColumn();

// Latest Audit Logs
$recentAudits = $db->query("
    SELECT al.*, u.full_name as user_name
    FROM audit_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 10
")->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="mb-1 fw-bold text-dark"><i class="fas fa-gauge-high text-warning me-2"></i> System Administration Dashboard</h3>
        <p class="text-muted mb-0">System health, master configurations, security parameters, and technical administration.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/admin/settings.php') ?>" class="btn btn-kaldis">
            <i class="fas fa-sliders me-1"></i> System Settings
        </a>
        <a href="<?= url('/admin/audit_logs.php') ?>" class="btn btn-outline-dark">
            <i class="fas fa-fingerprint me-1"></i> Full Audit Trail
        </a>
    </div>
</div>

<!-- Admin KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card stat-primary">
            <i class="fas fa-users-gear stat-icon text-primary"></i>
            <div class="stat-label">System Users</div>
            <div class="stat-value text-dark"><?= $totUsers ?></div>
            <small class="text-muted"><a href="users.php" class="text-decoration-none">Manage Accounts &rarr;</a></small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-gold">
            <i class="fas fa-sitemap stat-icon text-warning"></i>
            <div class="stat-label">Departments</div>
            <div class="stat-value text-dark"><?= $totDepts ?></div>
            <small class="text-muted"><a href="departments.php" class="text-decoration-none">Manage Departments &rarr;</a></small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-done">
            <i class="fas fa-bullseye stat-icon text-success"></i>
            <div class="stat-label">Annual Goals</div>
            <div class="stat-value text-success"><?= $totGoals ?></div>
            <small class="text-muted"><a href="annual_goals.php" class="text-decoration-none">Master Strategy &rarr;</a></small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-gold">
            <i class="fab fa-telegram stat-icon text-info"></i>
            <div class="stat-label">Telegram Mappings</div>
            <div class="stat-value text-dark"><?= $totTelegramUsers ?></div>
            <small class="text-muted"><a href="settings.php" class="text-decoration-none">Bot Config &rarr;</a></small>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

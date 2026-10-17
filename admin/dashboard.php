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
$totUsers  = (int)$db->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
$totDepts  = (int)$db->query("SELECT COUNT(*) FROM departments WHERE active = 1")->fetchColumn();
$totGoals  = (int)$db->query("SELECT COUNT(*) FROM annual_goals")->fetchColumn();
$totAudits = (int)$db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();

// Telegram-linked users — uses users.telegram_chat_id, not a separate telegram_users table
$totTelegramLinked = (int)$db->query(
    "SELECT COUNT(*) FROM users WHERE telegram_chat_id IS NOT NULL AND telegram_chat_id <> '' AND is_active = 1"
)->fetchColumn();

// Latest Audit Logs
$recentAudits = $db->query("
    SELECT al.*, u.full_name as user_name
    FROM audit_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 8
")->fetchAll();

$user = Auth::user();
?>

<!-- Page Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <h1 class="h3 mb-1 fw-bold" style="color:var(--k-text)">
            <i class="fas fa-gauge-high me-2" style="color:var(--k-gold)"></i>
            System Administration
        </h1>
        <p class="text-muted mb-0 small">
            System health, master configurations, security parameters and technical administration.
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= url('/admin/settings.php') ?>" class="btn btn-kaldis btn-sm">
            <i class="fas fa-sliders"></i> Settings
        </a>
        <a href="<?= url('/admin/audit_logs.php') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-fingerprint"></i> Audit Trail
        </a>
    </div>
</div>

<!-- KPI Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card stat-primary card-hoverable" onclick="location.href='<?= url('/admin/users.php') ?>'">
            <i class="fas fa-users-gear stat-icon" style="color:var(--k-blue)"></i>
            <div class="stat-label">Active Users</div>
            <div class="stat-value" style="color:var(--k-blue)"><?= $totUsers ?></div>
            <div class="stat-sub"><a href="<?= url('/admin/users.php') ?>" class="text-decoration-none" style="color:var(--k-blue)">Manage Accounts &rarr;</a></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-gold card-hoverable" onclick="location.href='<?= url('/admin/departments.php') ?>'">
            <i class="fas fa-sitemap stat-icon" style="color:var(--k-gold)"></i>
            <div class="stat-label">Departments</div>
            <div class="stat-value" style="color:var(--k-gold)"><?= $totDepts ?></div>
            <div class="stat-sub"><a href="<?= url('/admin/departments.php') ?>" class="text-decoration-none" style="color:var(--k-gold)">Manage Depts &rarr;</a></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-done card-hoverable" onclick="location.href='<?= url('/admin/annual_goals.php') ?>'">
            <i class="fas fa-bullseye stat-icon" style="color:var(--k-done)"></i>
            <div class="stat-label">Annual Goals</div>
            <div class="stat-value" style="color:var(--k-done)"><?= $totGoals ?></div>
            <div class="stat-sub"><a href="<?= url('/admin/annual_goals.php') ?>" class="text-decoration-none" style="color:var(--k-done)">Strategy Map &rarr;</a></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-purple card-hoverable" onclick="location.href='<?= url('/admin/settings.php') ?>'">
            <i class="fab fa-telegram stat-icon" style="color:var(--k-purple)"></i>
            <div class="stat-label">Telegram Linked</div>
            <div class="stat-value" style="color:var(--k-purple)"><?= $totTelegramLinked ?></div>
            <div class="stat-sub"><a href="<?= url('/admin/settings.php') ?>" class="text-decoration-none" style="color:var(--k-purple)">Bot Config &rarr;</a></div>
        </div>
    </div>
</div>

<!-- Quick Admin Action Cards -->
<div class="row g-3 mb-4">
    <?php
    $quickLinks = [
        ['icon'=>'fa-users-gear',   'color'=>'var(--k-blue)',    'label'=>'User Accounts',     'sub'=>'Add, edit & manage users',          'url'=>url('/admin/users.php')],
        ['icon'=>'fa-sitemap',      'color'=>'var(--k-gold)',    'label'=>'Departments',       'sub'=>'Manage org structure & codes',      'url'=>url('/admin/departments.php')],
        ['icon'=>'fa-bullseye',     'color'=>'var(--k-done)',    'label'=>'Annual Goals',      'sub'=>'Strategic objective mapping',       'url'=>url('/admin/annual_goals.php')],
        ['icon'=>'fa-toggle-on',    'color'=>'#F59E0B',          'label'=>'Monthly Activation','sub'=>'Activate strategy per department',  'url'=>url('/admin/monthly_activation.php')],
        ['icon'=>'fa-file-invoice', 'color'=>'var(--k-purple)',  'label'=>'Reports & Tracking','sub'=>'Submission compliance overview',    'url'=>url('/admin/reports.php')],
        ['icon'=>'fa-sliders',      'color'=>'var(--k-blue)',    'label'=>'System Settings',   'sub'=>'Telegram bot & system config',      'url'=>url('/admin/settings.php')],
        ['icon'=>'fa-fingerprint',  'color'=>'var(--k-notdone)', 'label'=>'Audit Trail',       'sub'=>'Full activity & change log',        'url'=>url('/admin/audit_logs.php')],
    ];
    foreach ($quickLinks as $ql): ?>
    <div class="col-6 col-md-4 col-lg-3">
        <a href="<?= $ql['url'] ?>" class="card card-hoverable text-decoration-none d-block p-3 text-center h-100"
           style="border-top: 3px solid <?= $ql['color'] ?>">
            <i class="fas <?= $ql['icon'] ?> fa-2x mb-2" style="color:<?= $ql['color'] ?>"></i>
            <div class="fw-bold small" style="color:var(--k-text);font-size:.88rem"><?= $ql['label'] ?></div>
            <div class="text-muted" style="font-size:.74rem;margin-top:2px"><?= $ql['sub'] ?></div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- Recent Audit Log -->
<?php if (!empty($recentAudits)): ?>
<div class="card">
    <div class="card-header kaldis-header d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="fas fa-history me-2"></i>Recent System Activity</span>
        <a href="<?= url('/admin/audit_logs.php') ?>" class="btn btn-sm btn-outline-secondary">View All &rarr;</a>
    </div>
    <div class="table-responsive">
        <table class="table table-kaldis mb-0">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentAudits as $log): ?>
                <tr>
                    <td><small class="text-muted font-monospace"><?= date('M d, H:i', strtotime($log['created_at'])) ?></small></td>
                    <td><span class="fw-semibold small"><?= e($log['user_name'] ?? 'System') ?></span></td>
                    <td>
                        <span class="badge" style="background:var(--k-gold-soft);color:var(--k-gold);font-size:.72rem">
                            <?= e($log['action']) ?>
                        </span>
                    </td>
                    <td><small class="text-muted"><?= e($log['module'] ?? '&mdash;') ?></small></td>
                    <td><small class="font-monospace text-muted"><?= e($log['ip_address'] ?? '&mdash;') ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
/**
 * System Audit Trail & Activity Inspection
 * Kaldis Coffee PLC
 */

$pageTitle = 'System Audit Trail';
require_once __DIR__ . '/../includes/header.php';

Permissions::requireRole([Permissions::ROLE_SUPER_ADMIN, Permissions::ROLE_IT_ADMIN]);

$db = Database::getConnection();

// Filters
$selectedModule = trim($_GET['module'] ?? '');
$search = trim($_GET['search'] ?? '');
$selectedUser = !empty($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

$usersList = $db->query("SELECT id, full_name, username FROM users ORDER BY full_name ASC")->fetchAll();

$query = "
    SELECT al.*, u.full_name as user_name, u.username
    FROM audit_logs al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE 1=1
";
$params = [];

if (!empty($selectedModule)) {
    $query .= " AND al.module = :module";
    $params[':module'] = $selectedModule;
}
if ($selectedUser > 0) {
    $query .= " AND al.user_id = :user_id";
    $params[':user_id'] = $selectedUser;
}
if (!empty($search)) {
    $query .= " AND (al.action LIKE :search OR al.old_values LIKE :search OR al.new_values LIKE :search)";
    $params[':search'] = "%{$search}%";
}

$query .= " ORDER BY al.created_at DESC LIMIT 150";

$stmt = $db->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="mb-1 fw-bold text-dark"><i class="fas fa-fingerprint text-warning me-2"></i> System Audit Trail</h3>
        <p class="text-muted mb-0">Immutable historical record of management actions, plan modifications, and performance evaluations.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" onclick="window.print()">
            <i class="fas fa-print me-1"></i> Print Audit Trail
        </button>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar mb-4">
    <form method="GET" action="audit_logs.php" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">MODULE</label>
            <select name="module" class="form-select">
                <option value="">All Modules</option>
                <option value="auth" <?= $selectedModule === 'auth' ? 'selected' : '' ?>>Authentication (Login/Logout)</option>
                <option value="activation" <?= $selectedModule === 'activation' ? 'selected' : '' ?>>Strategy Activation</option>
                <option value="annual_goals" <?= $selectedModule === 'annual_goals' ? 'selected' : '' ?>>Annual Goals</option>
                <option value="monthly_plans" <?= $selectedModule === 'monthly_plans' ? 'selected' : '' ?>>Monthly Plans</option>
                <option value="weekly_tasks" <?= $selectedModule === 'weekly_tasks' ? 'selected' : '' ?>>Weekly Tasks</option>
                <option value="performance" <?= $selectedModule === 'performance' ? 'selected' : '' ?>>Performance Execution</option>
                <option value="reports" <?= $selectedModule === 'reports' ? 'selected' : '' ?>>Reports & Submission</option>
                <option value="settings" <?= $selectedModule === 'settings' ? 'selected' : '' ?>>Settings & Config</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">USER</label>
            <select name="user_id" class="form-select">
                <option value="0">All Users</option>
                <?php foreach ($usersList as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $selectedUser === (int)$u['id'] ? 'selected' : '' ?>>
                        <?= e($u['full_name']) ?> (<?= e($u['username']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label small fw-bold text-muted">KEYWORD SEARCH</label>
            <input type="text" name="search" class="form-control" placeholder="Action name or JSON values..." value="<?= e($search) ?>">
        </div>

        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-kaldis-dark w-100">
                <i class="fas fa-search me-1"></i> Filter
            </button>
            <a href="audit_logs.php" class="btn btn-outline-secondary" title="Reset">
                <i class="fas fa-undo"></i>
            </a>
        </div>
    </form>
</div>

<!-- Audit Logs Table -->
<div class="card">
    <div class="card-header kaldis-header d-flex justify-content-between align-items-center">
        <span class="fs-6 fw-bold"><i class="fas fa-history me-2"></i> Audit Records (Showing Latest <?= count($logs) ?>)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-kaldis mb-0 align-middle small">
            <thead>
                <tr>
                    <th style="width: 140px;">Timestamp</th>
                    <th style="width: 160px;">Actor / User</th>
                    <th style="width: 130px;">Module</th>
                    <th style="width: 220px;">Action</th>
                    <th>Audit Details & Payload</th>
                    <th style="width: 120px;">IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No audit trail records matched the filter criteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="text-muted"><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($log['user_name'] ?? 'System / CLI') ?></div>
                                <?php if (!empty($log['username'])): ?>
                                    <small class="text-muted">@<?= e($log['username']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-light text-dark border font-monospace"><?= e($log['module']) ?></span></td>
                            <td><span class="badge bg-secondary font-monospace"><?= e($log['action']) ?></span></td>
                            <td>
                                <?php if (!empty($log['old_values'])): ?>
                                    <div class="text-muted"><strong>Old:</strong> <code><?= e($log['old_values']) ?></code></div>
                                <?php endif; ?>
                                <?php if (!empty($log['new_values'])): ?>
                                    <div><strong>New:</strong> <code><?= e($log['new_values']) ?></code></div>
                                <?php endif; ?>
                                <?php if (empty($log['old_values']) && empty($log['new_values'])): ?>
                                    <span class="text-muted">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td><code><?= e($log['ip_address']) ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

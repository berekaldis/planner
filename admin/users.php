<?php
/**
 * User & Role Management
 * Kaldis Coffee PLC
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/csrf.php';

Permissions::requireRole([Permissions::ROLE_SUPER_ADMIN, Permissions::ROLE_IT_ADMIN]);

$db = Database::getConnection();
$userId = Auth::id();

$roles = $db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();
$departments = $db->query("SELECT id, department_name, department_code FROM departments WHERE active = 1 ORDER BY department_name ASC")->fetchAll();

// Handle User Actions (Create, Update, Reset Password)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_user') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $password = trim($_POST['password'] ?? 'admin123');
        $roleId = (int)($_POST['role_id'] ?? 0);
        $deptId = filter_input(INPUT_POST, 'department_id', FILTER_VALIDATE_INT) ?: null;
        $telegramId = trim($_POST['telegram_chat_id'] ?? '') ?: null;

        if (!empty($username) && !empty($email) && !empty($fullName) && $roleId > 0) {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    INSERT INTO users (username, email, password_hash, full_name, role_id, department_id, telegram_chat_id, is_active, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
                ");
                $stmt->execute([$username, $email, $hash, $fullName, $roleId, $deptId, $telegramId]);
                $newUserId = $db->lastInsertId();

                audit_log($userId, 'USER_CREATED', 'users', (string)$newUserId, null, [
                    'username' => $username,
                    'role_id' => $roleId,
                    'department_id' => $deptId
                ]);

                set_flash('success', "User '{$username}' created successfully.");
                redirect('/admin/users.php');
            } catch (PDOException $e) {
                set_flash('danger', "Error: " . (str_contains($e->getMessage(), 'Duplicate') ? "Username or Email already exists." : $e->getMessage()));
            }
        }
    } elseif ($action === 'update_user') {
        $editId = (int)($_POST['user_id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $roleId = (int)($_POST['role_id'] ?? 0);
        $deptId = filter_input(INPUT_POST, 'department_id', FILTER_VALIDATE_INT) ?: null;
        $telegramId = trim($_POST['telegram_chat_id'] ?? '') ?: null;
        $isActive = (int)($_POST['is_active'] ?? 1);

        if ($editId > 0 && !empty($email) && !empty($fullName) && $roleId > 0) {
            try {
                $stmt = $db->prepare("
                    UPDATE users SET
                        email = ?, full_name = ?, role_id = ?, department_id = ?, telegram_chat_id = ?, is_active = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$email, $fullName, $roleId, $deptId, $telegramId, $isActive, $editId]);

                audit_log($userId, 'USER_UPDATED', 'users', (string)$editId, null, [
                    'role_id' => $roleId,
                    'department_id' => $deptId,
                    'is_active' => $isActive
                ]);

                set_flash('success', "User updated successfully.");
                redirect('/admin/users.php');
            } catch (PDOException $e) {
                set_flash('danger', "Error updating user: " . $e->getMessage());
            }
        }
    } elseif ($action === 'reset_password') {
        $editId = (int)($_POST['user_id'] ?? 0);
        $newPass = trim($_POST['new_password'] ?? '');

        if ($editId > 0 && !empty($newPass)) {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $editId]);
            audit_log($userId, 'PASSWORD_RESET', 'users', (string)$editId);
            set_flash('success', 'User password reset successfully.');
            redirect('/admin/users.php');
        }
    }
}

$pageTitle = 'User Accounts & Roles';
require_once __DIR__ . '/../includes/header.php';

// Fetch all users
$usersQuery = "
    SELECT u.*, r.display_name as role_name, d.department_name, d.department_code
    FROM users u
    JOIN roles r ON u.role_id = r.id
    LEFT JOIN departments d ON u.department_id = d.id
    ORDER BY u.id ASC
";
$users = $db->query($usersQuery)->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="mb-1 fw-bold text-dark"><i class="fas fa-users-gear text-warning me-2"></i> User Accounts & RBAC</h3>
        <p class="text-muted mb-0">Manage authorized accounts, role assignments, department scoping, and Telegram IDs.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-kaldis" data-bs-toggle="modal" data-bs-target="#createUserModal">
            <i class="fas fa-user-plus me-1"></i> Add New User
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header kaldis-header d-flex justify-content-between align-items-center">
        <span class="fs-6 fw-bold"><i class="fas fa-user-shield me-2"></i> System User Accounts</span>
        <span class="badge bg-warning text-dark font-monospace"><?= count($users) ?> Users</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-kaldis mb-0 align-middle">
            <thead>
                <tr>
                    <th>User & Email</th>
                    <th style="width: 140px;">Username</th>
                    <th style="width: 180px;">Role</th>
                    <th style="width: 160px;">Department</th>
                    <th style="width: 140px;">Telegram ID</th>
                    <th style="width: 100px;" class="text-center">Status</th>
                    <th style="width: 120px;" class="text-end no-print">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark fs-6"><?= e($u['full_name']) ?></div>
                            <small class="text-muted"><?= e($u['email']) ?></small>
                        </td>
                        <td><code><?= e($u['username']) ?></code></td>
                        <td>
                            <span class="badge bg-dark font-monospace"><?= e($u['role_name']) ?></span>
                        </td>
                        <td>
                            <?php if ($u['department_code']): ?>
                                <span class="badge bg-light text-dark border"><?= e($u['department_code']) ?></span>
                                <small class="text-muted d-block"><?= e($u['department_name']) ?></small>
                            <?php else: ?>
                                <span class="text-muted small">&mdash; Corporate &mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($u['telegram_chat_id'])): ?>
                                <span class="badge bg-info text-dark font-monospace"><i class="fab fa-telegram me-1"></i><?= e($u['telegram_chat_id']) ?></span>
                            <?php else: ?>
                                <span class="text-muted small">Not bound</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge <?= $u['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $u['is_active'] ? 'ACTIVE' : 'INACTIVE' ?>
                            </span>
                        </td>
                        <td class="text-end no-print">
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                    onclick="editUser(<?= htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8') ?>)" title="Edit User">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                    onclick="resetPassword(<?= $u['id'] ?>, '<?= e($u['username']) ?>')" title="Reset Password">
                                <i class="fas fa-key"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Create User -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="users.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create_user">

                <div class="modal-header kaldis-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i> Create System Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">FULL NAME <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" required placeholder="e.g. Solomon Desta">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">EMAIL ADDRESS <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required placeholder="name@kaldiscoffee.com">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">USERNAME <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control font-monospace" required placeholder="e.g. sdesta">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">INITIAL PASSWORD <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" value="admin123" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">USER ROLE <span class="text-danger">*</span></label>
                            <select name="role_id" class="form-select" required>
                                <option value="">Select Role...</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>"><?= e($r['display_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">ASSIGNED DEPARTMENT</label>
                            <select name="department_id" class="form-select">
                                <option value="">-- None / Corporate-Wide --</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= e($d['department_name']) ?> (<?= e($d['department_code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Mandatory for Department Heads.</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">TELEGRAM CHAT ID / USERNAME</label>
                            <input type="text" name="telegram_chat_id" class="form-control" placeholder="e.g. 123456789 or @username">
                            <small class="text-muted">Used for automatic reminder delivery and Telegram bot reporting.</small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-kaldis"><i class="fas fa-save me-1"></i> Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit User -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="users.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="edit_user_id">

                <div class="modal-header kaldis-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-user-pen me-2"></i> Edit User Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">FULL NAME <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">EMAIL ADDRESS <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">USER ROLE <span class="text-danger">*</span></label>
                            <select name="role_id" id="edit_role_id" class="form-select" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>"><?= e($r['display_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">ASSIGNED DEPARTMENT</label>
                            <select name="department_id" id="edit_department_id" class="form-select">
                                <option value="">-- None / Corporate-Wide --</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= e($d['department_name']) ?> (<?= e($d['department_code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">TELEGRAM CHAT ID</label>
                            <input type="text" name="telegram_chat_id" id="edit_telegram_chat_id" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">STATUS</label>
                            <select name="is_active" id="edit_is_active" class="form-select">
                                <option value="1">ACTIVE</option>
                                <option value="0">INACTIVE / LOCKED</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-kaldis"><i class="fas fa-save me-1"></i> Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Reset Password -->
<div class="modal fade" id="resetPassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="users.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="reset_user_id">

                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-key me-2"></i> Reset Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">Resetting password for user: <strong id="reset_user_name"></strong></p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">NEW PASSWORD <span class="text-danger">*</span></label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-check me-1"></i> Set New Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editUser(u) {
    document.getElementById('edit_user_id').value = u.id;
    document.getElementById('edit_full_name').value = u.full_name;
    document.getElementById('edit_email').value = u.email;
    document.getElementById('edit_role_id').value = u.role_id;
    document.getElementById('edit_department_id').value = u.department_id || '';
    document.getElementById('edit_telegram_chat_id').value = u.telegram_chat_id || '';
    document.getElementById('edit_is_active').value = u.is_active;

    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

function resetPassword(id, username) {
    document.getElementById('reset_user_id').value = id;
    document.getElementById('reset_user_name').innerText = username;
    new bootstrap.Modal(document.getElementById('resetPassModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

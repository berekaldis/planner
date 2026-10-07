<?php
/**
 * Department Management
 * Kaldis Coffee PLC
 */

$pageTitle = 'Department Management';
require_once __DIR__ . '/../includes/header.php';

Permissions::requireRole([Permissions::ROLE_SUPER_ADMIN, Permissions::ROLE_IT_ADMIN]);

$db = Database::getConnection();
$userId = Auth::id();

// Handle Department Creation / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_department') {
        $name = trim($_POST['department_name'] ?? '');
        $code = strtoupper(trim($_POST['department_code'] ?? ''));
        $headId = filter_input(INPUT_POST, 'head_user_id', FILTER_VALIDATE_INT) ?: null;

        if (!empty($name) && !empty($code)) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO departments (department_name, department_code, head_user_id, active, created_at)
                    VALUES (?, ?, ?, 1, NOW())
                ");
                $stmt->execute([$name, $code, $headId]);
                $deptId = $db->lastInsertId();

                if ($headId) {
                    $db->prepare("UPDATE users SET department_id = ? WHERE id = ?")->execute([$deptId, $headId]);
                }

                audit_log($userId, 'DEPARTMENT_CREATED', 'departments', (string)$deptId, null, [
                    'name' => $name,
                    'code' => $code,
                    'head_id' => $headId
                ]);

                set_flash('success', "Department '{$name}' created successfully.");
                redirect('/admin/departments.php');
            } catch (PDOException $e) {
                set_flash('danger', "Error: " . (str_contains($e->getMessage(), 'Duplicate') ? "Department code '{$code}' already exists." : $e->getMessage()));
            }
        }
    } elseif ($action === 'update_department') {
        $id = (int)($_POST['dept_id'] ?? 0);
        $name = trim($_POST['department_name'] ?? '');
        $code = strtoupper(trim($_POST['department_code'] ?? ''));
        $headId = filter_input(INPUT_POST, 'head_user_id', FILTER_VALIDATE_INT) ?: null;
        $active = (int)($_POST['active'] ?? 1);

        if ($id > 0 && !empty($name) && !empty($code)) {
            try {
                $stmt = $db->prepare("
                    UPDATE departments SET
                        department_name = ?, department_code = ?, head_user_id = ?, active = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$name, $code, $headId, $active, $id]);

                if ($headId) {
                    $db->prepare("UPDATE users SET department_id = ? WHERE id = ?")->execute([$id, $headId]);
                }

                audit_log($userId, 'DEPARTMENT_UPDATED', 'departments', (string)$id, null, [
                    'name' => $name,
                    'code' => $code,
                    'head_id' => $headId,
                    'active' => $active
                ]);

                set_flash('success', "Department '{$name}' updated.");
                redirect('/admin/departments.php');
            } catch (PDOException $e) {
                set_flash('danger', "Error updating department: " . $e->getMessage());
            }
        }
    }
}

// Fetch all departments
$deptsStmt = $db->query("
    SELECT d.*, u.full_name as head_name, u.email as head_email,
           (SELECT COUNT(*) FROM users u2 WHERE u2.department_id = d.id) as staff_count,
           (SELECT COUNT(*) FROM annual_goals ag WHERE ag.responsible_department_id = d.id) as goal_count
    FROM departments d
    LEFT JOIN users u ON d.head_user_id = u.id
    ORDER BY d.department_name ASC
");
$departmentsList = $deptsStmt->fetchAll();

// Fetch potential heads (users with role dept_head or staff)
$usersList = $db->query("SELECT id, full_name, username, email FROM users WHERE is_active = 1 ORDER BY full_name ASC")->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="mb-1 fw-bold text-dark"><i class="fas fa-sitemap text-warning me-2"></i> Department Management</h3>
        <p class="text-muted mb-0">Configure organizational departments, short codes, and appointed Department Heads.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-kaldis" data-bs-toggle="modal" data-bs-target="#createDeptModal">
            <i class="fas fa-plus-circle me-1"></i> Add New Department
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header kaldis-header d-flex justify-content-between align-items-center">
        <span class="fs-6 fw-bold"><i class="fas fa-building me-2"></i> Registered Departments</span>
        <span class="badge bg-warning text-dark font-monospace"><?= count($departmentsList) ?> Departments</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-kaldis mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th style="width: 110px;">Code</th>
                    <th>Department Name</th>
                    <th style="width: 200px;">Department Head</th>
                    <th style="width: 100px;" class="text-center">Staff</th>
                    <th style="width: 120px;" class="text-center">Annual Goals</th>
                    <th style="width: 100px;" class="text-center">Status</th>
                    <th style="width: 80px;" class="text-end no-print">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departmentsList as $d): ?>
                    <tr>
                        <td class="text-muted font-monospace">#<?= $d['id'] ?></td>
                        <td><span class="badge bg-dark font-monospace fs-6 px-2"><?= e($d['department_code']) ?></span></td>
                        <td>
                            <div class="fw-bold text-dark fs-6"><?= e($d['department_name']) ?></div>
                            <small class="text-muted">Created: <?= date('M d, Y', strtotime($d['created_at'])) ?></small>
                        </td>
                        <td>
                            <?php if ($d['head_name']): ?>
                                <div class="fw-semibold text-dark"><?= e($d['head_name']) ?></div>
                                <small class="text-muted"><?= e($d['head_email']) ?></small>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Unassigned</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center fw-bold"><?= (int)$d['staff_count'] ?></td>
                        <td class="text-center fw-bold text-primary"><?= (int)$d['goal_count'] ?></td>
                        <td class="text-center">
                            <span class="badge <?= $d['active'] ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $d['active'] ? 'ACTIVE' : 'INACTIVE' ?>
                            </span>
                        </td>
                        <td class="text-end no-print">
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                    onclick="editDept(<?= htmlspecialchars(json_encode($d), ENT_QUOTES, 'UTF-8') ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add Department -->
<div class="modal fade" id="createDeptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="departments.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create_department">

                <div class="modal-header kaldis-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i> Add Department</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">DEPARTMENT NAME <span class="text-danger">*</span></label>
                        <input type="text" name="department_name" class="form-control" placeholder="e.g. Quality Assurance & Roastery" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">DEPARTMENT CODE (SHORT CODE) <span class="text-danger">*</span></label>
                        <input type="text" name="department_code" class="form-control font-monospace" placeholder="e.g. QAR, OPS, IT" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">APPOINT DEPARTMENT HEAD</label>
                        <select name="head_user_id" class="form-select">
                            <option value="">-- Choose User (Optional) --</option>
                            <?php foreach ($usersList as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= e($u['full_name']) ?> (<?= e($u['username']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-kaldis"><i class="fas fa-save me-1"></i> Save Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Department -->
<div class="modal fade" id="editDeptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="departments.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_department">
                <input type="hidden" name="dept_id" id="edit_dept_id">

                <div class="modal-header kaldis-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i> Edit Department</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">DEPARTMENT NAME <span class="text-danger">*</span></label>
                        <input type="text" name="department_name" id="edit_dept_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">DEPARTMENT CODE <span class="text-danger">*</span></label>
                        <input type="text" name="department_code" id="edit_dept_code" class="form-control font-monospace" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">APPOINT DEPARTMENT HEAD</label>
                        <select name="head_user_id" id="edit_head_id" class="form-select">
                            <option value="">-- Choose User --</option>
                            <?php foreach ($usersList as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= e($u['full_name']) ?> (<?= e($u['username']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">STATUS</label>
                        <select name="active" id="edit_active" class="form-select">
                            <option value="1">ACTIVE</option>
                            <option value="0">INACTIVE</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-kaldis"><i class="fas fa-save me-1"></i> Update Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editDept(d) {
    document.getElementById('edit_dept_id').value = d.id;
    document.getElementById('edit_dept_name').value = d.department_name;
    document.getElementById('edit_dept_code').value = d.department_code;
    document.getElementById('edit_head_id').value = d.head_user_id || '';
    document.getElementById('edit_active').value = d.active;
    new bootstrap.Modal(document.getElementById('editDeptModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

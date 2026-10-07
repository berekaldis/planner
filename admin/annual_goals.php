<?php
/**
 * Annual Strategy Goals Management (Master Plan)
 * Kaldis Coffee PLC
 *
 * Supports multi-department ownership (2 or more departments as owners per strategic plan).
 */

$pageTitle = 'Annual Strategy Plan';
require_once __DIR__ . '/../includes/header.php';

// Check edit permissions for master strategy goals
$canEditStrategy = Permissions::isSuperAdmin() || Permissions::isGM() || Permissions::isITAdmin();

$db = Database::getConnection();
$userId = Auth::id();

$years = get_planning_years();
$departments = $db->query("SELECT id, department_name, department_code FROM departments WHERE active = 1 ORDER BY department_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Handle Form Submissions (Add / Edit Goal)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$canEditStrategy) {
        Permissions::denyAccess('Only General Manager and Administrators can modify Master Strategy Goals.');
    }
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_goal') {
        $goalCode = strtoupper(trim($_POST['goal_code'] ?? ''));
        $title = trim($_POST['title'] ?? '');
        $dod = trim($_POST['definition_of_done'] ?? '');
        $deptId = (int)($_POST['responsible_department_id'] ?? 0);
        $coOwnerDeptIds = isset($_POST['co_owner_department_ids']) && is_array($_POST['co_owner_department_ids'])
            ? array_map('intval', $_POST['co_owner_department_ids'])
            : [];
        $target = trim($_POST['annual_target'] ?? '');
        $priority = strtoupper(trim($_POST['priority'] ?? 'MEDIUM'));
        $year = trim($_POST['year'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if (!empty($goalCode) && !empty($title) && !empty($dod) && $deptId > 0 && !empty($year)) {
            try {
                $db->beginTransaction();

                $stmt = $db->prepare("
                    INSERT INTO annual_goals (
                        goal_code, title, definition_of_done, responsible_department_id, annual_target,
                        priority, notes, status, year, created_by, created_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, 'ACTIVE', ?, ?, NOW()
                    )
                ");
                $stmt->execute([$goalCode, $title, $dod, $deptId, $target, $priority, $notes, $year, $userId]);
                $newId = (int)$db->lastInsertId();

                // Save Lead Department in annual_goal_departments
                $insertLead = $db->prepare("INSERT INTO annual_goal_departments (annual_goal_id, department_id, is_primary) VALUES (?, ?, 1)");
                $insertLead->execute([$newId, $deptId]);

                // Save Co-Owners in annual_goal_departments
                $insertCo = $db->prepare("INSERT IGNORE INTO annual_goal_departments (annual_goal_id, department_id, is_primary) VALUES (?, ?, 0)");
                foreach ($coOwnerDeptIds as $coId) {
                    if ($coId > 0 && $coId !== $deptId) {
                        $insertCo->execute([$newId, $coId]);
                    }
                }

                $db->commit();

                audit_log($userId, 'ANNUAL_GOAL_CREATED', 'annual_goals', (string)$newId, null, [
                    'goal_code' => $goalCode,
                    'title' => $title,
                    'lead_department_id' => $deptId,
                    'co_owner_departments' => $coOwnerDeptIds,
                    'year' => $year
                ]);

                set_flash('success', "Annual Strategic Goal {$goalCode} created with multi-department ownership.");
                redirect('/admin/annual_goals.php?year=' . urlencode($year));
            } catch (PDOException $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                set_flash('danger', "Error creating goal: " . (str_contains($e->getMessage(), 'Duplicate') ? "Goal Code '{$goalCode}' already exists for {$year}." : $e->getMessage()));
            }
        } else {
            set_flash('danger', 'Please fill in all mandatory fields.');
        }
    } elseif ($action === 'update_goal') {
        $id = (int)($_POST['goal_id'] ?? 0);
        $goalCode = strtoupper(trim($_POST['goal_code'] ?? ''));
        $title = trim($_POST['title'] ?? '');
        $dod = trim($_POST['definition_of_done'] ?? '');
        $deptId = (int)($_POST['responsible_department_id'] ?? 0);
        $coOwnerDeptIds = isset($_POST['co_owner_department_ids']) && is_array($_POST['co_owner_department_ids'])
            ? array_map('intval', $_POST['co_owner_department_ids'])
            : [];
        $target = trim($_POST['annual_target'] ?? '');
        $priority = strtoupper(trim($_POST['priority'] ?? 'MEDIUM'));
        $status = strtoupper(trim($_POST['status'] ?? 'ACTIVE'));
        $year = trim($_POST['year'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($id > 0 && !empty($goalCode) && !empty($title) && !empty($dod) && $deptId > 0) {
            try {
                $db->beginTransaction();

                $oldStmt = $db->prepare("SELECT * FROM annual_goals WHERE id = ?");
                $oldStmt->execute([$id]);
                $oldGoal = $oldStmt->fetch();

                $updStmt = $db->prepare("
                    UPDATE annual_goals SET
                        goal_code = ?, title = ?, definition_of_done = ?, responsible_department_id = ?,
                        annual_target = ?, priority = ?, status = ?, year = ?, notes = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $updStmt->execute([$goalCode, $title, $dod, $deptId, $target, $priority, $status, $year, $notes, $id]);

                // Sync annual_goal_departments
                $db->prepare("DELETE FROM annual_goal_departments WHERE annual_goal_id = ?")->execute([$id]);

                $insertLead = $db->prepare("INSERT INTO annual_goal_departments (annual_goal_id, department_id, is_primary) VALUES (?, ?, 1)");
                $insertLead->execute([$id, $deptId]);

                $insertCo = $db->prepare("INSERT IGNORE INTO annual_goal_departments (annual_goal_id, department_id, is_primary) VALUES (?, ?, 0)");
                foreach ($coOwnerDeptIds as $coId) {
                    if ($coId > 0 && $coId !== $deptId) {
                        $insertCo->execute([$id, $coId]);
                    }
                }

                $db->commit();

                audit_log($userId, 'ANNUAL_GOAL_UPDATED', 'annual_goals', (string)$id, $oldGoal, [
                    'goal_code' => $goalCode,
                    'title' => $title,
                    'lead_department_id' => $deptId,
                    'co_owner_departments' => $coOwnerDeptIds,
                    'status' => $status
                ]);

                set_flash('success', "Annual Goal {$goalCode} updated successfully with owner departments.");
                redirect('/admin/annual_goals.php?year=' . urlencode($year));
            } catch (PDOException $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                set_flash('danger', "Error updating goal: " . $e->getMessage());
            }
        }
    }
}

// Filter inputs
$selectedYear = $_GET['year'] ?? app_config('current_planning_year', '2019 E.C.');
$selectedDept = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$selectedStatus = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

// Fetch all owner departments
$ownerDeptsStmt = $db->query("
    SELECT agd.annual_goal_id, d.id as department_id, d.department_name, d.department_code, agd.is_primary
    FROM annual_goal_departments agd
    JOIN departments d ON agd.department_id = d.id
    ORDER BY agd.is_primary DESC, d.department_name ASC
");
$goalOwnerDepts = [];
while ($row = $ownerDeptsStmt->fetch(PDO::FETCH_ASSOC)) {
    $goalOwnerDepts[$row['annual_goal_id']][] = $row;
}

$query = "
    SELECT ag.*, d.department_name as lead_dept_name, d.department_code as lead_dept_code, u.full_name as author_name
    FROM annual_goals ag
    JOIN departments d ON ag.responsible_department_id = d.id
    LEFT JOIN users u ON ag.created_by = u.id
    WHERE ag.year = :year
";
$params = [':year' => $selectedYear];

if ($selectedDept > 0) {
    $query .= " AND (
        ag.responsible_department_id = :dept_id 
        OR ag.id IN (SELECT annual_goal_id FROM annual_goal_departments WHERE department_id = :dept_id)
    )";
    $params[':dept_id'] = $selectedDept;
}
if (!empty($selectedStatus)) {
    $query .= " AND ag.status = :status";
    $params[':status'] = $selectedStatus;
}
if (!empty($search)) {
    $query .= " AND (ag.goal_code LIKE :search OR ag.title LIKE :search OR ag.definition_of_done LIKE :search)";
    $params[':search'] = "%{$search}%";
}
$query .= " ORDER BY ag.goal_code ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$goals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="mb-1 fw-bold text-dark"><i class="fas fa-bullseye text-warning me-2"></i> Annual Strategy Plan (Master)</h3>
        <p class="text-muted mb-0">Define organizational strategic goals (G01..G59) with multi-department ownership.</p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($canEditStrategy): ?>
            <button class="btn btn-kaldis shadow-sm" data-bs-toggle="modal" data-bs-target="#createGoalModal">
                <i class="fas fa-plus-circle me-1"></i> New Annual Goal
            </button>
        <?php endif; ?>
        <a href="<?= url('/admin/monthly_activation.php') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-toggle-on text-warning me-1"></i> Monthly Activation
        </a>
        <button class="btn btn-outline-secondary" onclick="window.print()">
            <i class="fas fa-print me-1"></i> Print / Export
        </button>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar mb-4 bg-white p-3 rounded shadow-sm border">
    <form method="GET" action="annual_goals.php" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted text-uppercase">Planning Year</label>
            <select name="year" class="form-select form-select-sm">
                <?php foreach ($years as $y): ?>
                    <option value="<?= e($y) ?>" <?= $selectedYear === $y ? 'selected' : '' ?>><?= e($y) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted text-uppercase">Owner Department</label>
            <select name="department_id" class="form-select form-select-sm">
                <option value="0">All Departments (Lead & Co-Owners)</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= (int)$d['id'] ?>" <?= $selectedDept === (int)$d['id'] ? 'selected' : '' ?>>
                        <?= e($d['department_name']) ?> (<?= e($d['department_code']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted text-uppercase">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <option value="ACTIVE" <?= $selectedStatus === 'ACTIVE' ? 'selected' : '' ?>>ACTIVE</option>
                <option value="INACTIVE" <?= $selectedStatus === 'INACTIVE' ? 'selected' : '' ?>>INACTIVE</option>
                <option value="COMPLETED" <?= $selectedStatus === 'COMPLETED' ? 'selected' : '' ?>>COMPLETED</option>
            </select>
        </div>

        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-kaldis w-100">
                <i class="fas fa-filter me-1"></i> Apply Filters
            </button>
            <a href="annual_goals.php" class="btn btn-sm btn-outline-secondary" title="Reset">
                <i class="fas fa-undo"></i>
            </a>
        </div>
    </form>
</div>

<!-- Goals Table -->
<div class="card shadow-sm border-0 mb-4 bg-white">
    <div class="card-header kaldis-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
        <span class="fs-6 fw-bold">
            <i class="fas fa-list-ol me-2 text-warning"></i> Master Strategy List &mdash; <?= e($selectedYear) ?>
        </span>
        <div class="d-flex align-items-center gap-2">
            <div class="search-input-wrapper" style="width: 250px;">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control form-control-sm live-search-input live-table-search" placeholder="Search strategy or code..." data-target-table=".table-goals">
            </div>
            <span class="badge bg-warning text-dark font-monospace">
                Total Goals: <?= count($goals) ?>
            </span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-goals mb-0 align-middle">
            <thead class="table-light text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                <tr>
                    <th style="width: 90px;">Goal ID</th>
                    <th>Annual Strategic Goal</th>
                    <th style="width: 240px;">Definition of Done (DoD)</th>
                    <th style="width: 200px;">Owner Departments</th>
                    <th style="width: 140px;">Annual Target</th>
                    <th style="width: 90px;">Priority</th>
                    <th style="width: 90px;">Status</th>
                    <th style="width: 100px;" class="text-end no-print">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($goals)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-bullseye fa-3x mb-3 d-block opacity-25"></i>
                            No annual goals recorded for <strong><?= e($selectedYear) ?></strong>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($goals as $goal): ?>
                        <?php
                            $gId = (int)$goal['id'];
                            $owners = $goalOwnerDepts[$gId] ?? [[
                                'department_id' => $goal['responsible_department_id'],
                                'department_code' => $goal['lead_dept_code'],
                                'department_name' => $goal['lead_dept_name'],
                                'is_primary' => 1
                            ]];
                            $coOwnerIds = [];
                            foreach ($owners as $ow) {
                                if (empty($ow['is_primary'])) {
                                    $coOwnerIds[] = (int)$ow['department_id'];
                                }
                            }
                            $goal['co_owner_ids'] = $coOwnerIds;
                        ?>
                        <tr>
                            <td>
                                <span class="badge bg-dark font-monospace fs-6 px-2 py-1"><?= e($goal['goal_code']) ?></span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($goal['title']) ?></div>
                                <?php if (!empty($goal['notes'])): ?>
                                    <small class="text-muted d-block mt-1"><em><?= e($goal['notes']) ?></em></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small text-secondary"><?= e($goal['definition_of_done']) ?></div>
                            </td>
                            <!-- Owner Departments Badges -->
                            <td>
                                <div class="d-flex flex-wrap gap-1 align-items-center">
                                    <?php foreach ($owners as $owner): ?>
                                        <?php if (!empty($owner['is_primary'])): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" title="Lead Owner: <?= e($owner['department_name']) ?>">
                                                <i class="fas fa-star text-warning me-1"></i><?= e($owner['department_code']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark border" title="Co-Owner: <?= e($owner['department_name']) ?>">
                                                <?= e($owner['department_code']) ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                                    <?= count($owners) > 1 ? count($owners) . ' department owners' : 'Single owner' ?>
                                </small>
                            </td>
                            <td>
                                <span class="fw-semibold small text-dark"><?= e($goal['annual_target']) ?></span>
                            </td>
                            <td>
                                <?= render_priority_badge($goal['priority']) ?>
                            </td>
                            <td>
                                <span class="badge <?= $goal['status'] === 'ACTIVE' ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= e($goal['status']) ?>
                                </span>
                            </td>
                            <td class="text-end no-print">
                                <?php if ($canEditStrategy): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary shadow-sm"
                                            onclick="editGoal(<?= htmlspecialchars(json_encode($goal), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted small">&mdash;</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Create Goal with Multi-Department Ownership -->
<div class="modal fade" id="createGoalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg border-0">
            <form method="POST" action="annual_goals.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create_goal">
                
                <div class="modal-header kaldis-header py-3">
                    <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2 text-warning"></i> Add New Annual Strategy Goal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">GOAL ID <span class="text-danger">*</span></label>
                            <input type="text" name="goal_code" class="form-control font-monospace" placeholder="e.g. G01, G02..." required>
                            <small class="text-muted">Unique annual goal code.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">PLANNING YEAR <span class="text-danger">*</span></label>
                            <select name="year" class="form-select" required>
                                <?php foreach ($years as $y): ?>
                                    <option value="<?= e($y) ?>" <?= $selectedYear === $y ? 'selected' : '' ?>><?= e($y) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">LEAD RESPONSIBLE DEPT <span class="text-danger">*</span></label>
                            <select name="responsible_department_id" id="create_lead_dept" class="form-select" required>
                                <option value="">Select Primary Lead...</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= (int)$d['id'] ?>"><?= e($d['department_name']) ?> (<?= e($d['department_code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Multi-Department Ownership: Co-Owner Departments -->
                        <div class="col-12">
                            <div class="p-3 bg-light border rounded">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    <i class="fas fa-users text-primary me-1"></i> ADDITIONAL OWNER / CO-RESPONSIBLE DEPARTMENTS
                                </label>
                                <div class="small text-muted mb-2">
                                    Select any additional departments that share ownership of this strategic plan. When activated by GM, it will be added to all selected departments.
                                </div>
                                <div class="row g-2">
                                    <?php foreach ($departments as $d): ?>
                                        <div class="col-md-4 col-sm-6">
                                            <div class="form-check">
                                                <input class="form-check-input create-co-owner" type="checkbox" name="co_owner_department_ids[]" value="<?= (int)$d['id'] ?>" id="create_co_<?= (int)$d['id'] ?>">
                                                <label class="form-check-label small" for="create_co_<?= (int)$d['id'] ?>">
                                                    <strong><?= e($d['department_code']) ?></strong> &mdash; <?= e($d['department_name']) ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">ANNUAL STRATEGIC GOAL <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Open 5 new stores or Implement ERP Automation" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">DEFINITION OF DONE (DoD) <span class="text-danger">*</span></label>
                            <textarea name="definition_of_done" class="form-control" rows="3" placeholder="Measurable completion criteria and deliverable..." required></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">ANNUAL TARGET <span class="text-danger">*</span></label>
                            <input type="text" name="annual_target" class="form-control" placeholder="e.g. 5 stores operational" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">PRIORITY</label>
                            <select name="priority" class="form-select">
                                <option value="HIGH">HIGH</option>
                                <option value="MEDIUM" selected>MEDIUM</option>
                                <option value="LOW">LOW</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">NOTES / STRATEGIC CONTEXT</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Optional strategic notes..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-kaldis"><i class="fas fa-save me-1"></i> Save Annual Goal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Goal with Multi-Department Ownership -->
<div class="modal fade" id="editGoalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg border-0">
            <form method="POST" action="annual_goals.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_goal">
                <input type="hidden" name="goal_id" id="edit_goal_id">
                
                <div class="modal-header kaldis-header py-3">
                    <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2 text-warning"></i> Edit Annual Strategy Goal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">GOAL ID <span class="text-danger">*</span></label>
                            <input type="text" name="goal_code" id="edit_goal_code" class="form-control font-monospace" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">PLANNING YEAR <span class="text-danger">*</span></label>
                            <select name="year" id="edit_year" class="form-select" required>
                                <?php foreach ($years as $y): ?>
                                    <option value="<?= e($y) ?>"><?= e($y) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">LEAD RESPONSIBLE DEPT <span class="text-danger">*</span></label>
                            <select name="responsible_department_id" id="edit_dept_id" class="form-select" required>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= (int)$d['id'] ?>"><?= e($d['department_name']) ?> (<?= e($d['department_code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Multi-Department Ownership: Co-Owner Departments -->
                        <div class="col-12">
                            <div class="p-3 bg-light border rounded">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    <i class="fas fa-users text-primary me-1"></i> ADDITIONAL OWNER / CO-RESPONSIBLE DEPARTMENTS
                                </label>
                                <div class="small text-muted mb-2">
                                    Check all additional departments that co-own this goal. When activated by GM, it will automatically appear for all selected departments.
                                </div>
                                <div class="row g-2">
                                    <?php foreach ($departments as $d): ?>
                                        <div class="col-md-4 col-sm-6">
                                            <div class="form-check">
                                                <input class="form-check-input edit-co-owner" type="checkbox" name="co_owner_department_ids[]" value="<?= (int)$d['id'] ?>" id="edit_co_<?= (int)$d['id'] ?>">
                                                <label class="form-check-label small" for="edit_co_<?= (int)$d['id'] ?>">
                                                    <strong><?= e($d['department_code']) ?></strong> &mdash; <?= e($d['department_name']) ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">ANNUAL STRATEGIC GOAL <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="edit_title" class="form-control" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">DEFINITION OF DONE (DoD) <span class="text-danger">*</span></label>
                            <textarea name="definition_of_done" id="edit_dod" class="form-control" rows="3" required></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">ANNUAL TARGET <span class="text-danger">*</span></label>
                            <input type="text" name="annual_target" id="edit_target" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">PRIORITY</label>
                            <select name="priority" id="edit_priority" class="form-select">
                                <option value="HIGH">HIGH</option>
                                <option value="MEDIUM">MEDIUM</option>
                                <option value="LOW">LOW</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">STATUS</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="ACTIVE">ACTIVE</option>
                                <option value="INACTIVE">INACTIVE</option>
                                <option value="COMPLETED">COMPLETED</option>
                                <option value="ARCHIVED">ARCHIVED</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">NOTES / STRATEGIC CONTEXT</label>
                            <textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-kaldis"><i class="fas fa-save me-1"></i> Update Goal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editGoal(goal) {
    document.getElementById('edit_goal_id').value = goal.id;
    document.getElementById('edit_goal_code').value = goal.goal_code;
    document.getElementById('edit_year').value = goal.year;
    document.getElementById('edit_dept_id').value = goal.responsible_department_id;
    document.getElementById('edit_title').value = goal.title;
    document.getElementById('edit_dod').value = goal.definition_of_done;
    document.getElementById('edit_target').value = goal.annual_target;
    document.getElementById('edit_priority').value = goal.priority;
    document.getElementById('edit_status').value = goal.status;
    document.getElementById('edit_notes').value = goal.notes || '';

    // Reset and populate co-owner checkboxes
    const coOwnerCheckboxes = document.querySelectorAll('.edit-co-owner');
    const coOwnerIds = goal.co_owner_ids || [];
    coOwnerCheckboxes.forEach(cb => {
        cb.checked = coOwnerIds.includes(parseInt(cb.value));
    });

    new bootstrap.Modal(document.getElementById('editGoalModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

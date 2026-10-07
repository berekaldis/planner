<?php
/**
 * GM Monthly Strategy Activation
 * Kaldis Coffee PLC
 *
 * General Manager authorization for active Annual Strategies per department & month.
 * Default status is FALSE (Inactive) until the GM explicitly turns it to TRUE (Active).
 * When GM activates a strategic plan, it activates for ALL owned/co-owner departments.
 */

$pageTitle = 'Monthly Strategy Activation';
require_once __DIR__ . '/../includes/header.php';

// Only GM and Super Admin can access this page
Permissions::requireRole([Permissions::ROLE_SUPER_ADMIN, Permissions::ROLE_GM]);

$db = Database::getConnection();

// Available Filter Choices
$years = get_planning_years();
$months = get_ethiopian_months();
$departments = $db->query("SELECT id, department_name, department_code FROM departments WHERE active = 1 ORDER BY department_name ASC")->fetchAll();

// Selected Filters: default department is 0 (ALL DEPARTMENTS) so GM sees all strategic plans
$selectedYear = $_GET['year'] ?? app_config('current_planning_year', '2019 E.C.');
$selectedMonth = $_GET['month'] ?? app_config('current_planning_month', 'Nehase');
$selectedDept = isset($_GET['department_id']) && $_GET['department_id'] !== '' ? (int)$_GET['department_id'] : 0;
$selectedStatus = strtoupper(trim($_GET['status'] ?? 'ALL')); // ALL, TRUE, FALSE
$search = trim($_GET['search'] ?? '');

// Fetch all owner departments for all goals to render multi-department badges
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

// Fetch Annual Strategies for Selected Year and Month
// A strategy is considered ACTIVE (TRUE) if active = 'YES' in monthly_strategy_activations for this goal, year, and month
$query = "
    SELECT ag.*,
           d.department_name as lead_department_name,
           d.department_code as lead_department_code,
           (
               SELECT COUNT(*) 
               FROM monthly_strategy_activations msa 
               WHERE msa.annual_goal_id = ag.id 
                 AND msa.year = :year 
                 AND msa.month = :month 
                 AND msa.active = 'YES'
           ) as active_owner_count,
           (
               SELECT MAX(msa.updated_at) 
               FROM monthly_strategy_activations msa 
               WHERE msa.annual_goal_id = ag.id 
                 AND msa.year = :year 
                 AND msa.month = :month
           ) as last_activated_at,
           (
               SELECT u.full_name 
               FROM monthly_strategy_activations msa 
               JOIN users u ON msa.activated_by = u.id 
               WHERE msa.annual_goal_id = ag.id 
                 AND msa.year = :year 
                 AND msa.month = :month 
               ORDER BY msa.updated_at DESC LIMIT 1
           ) as activated_by_name
    FROM annual_goals ag
    JOIN departments d ON ag.responsible_department_id = d.id
    WHERE ag.year = :year
";

$params = [
    ':year' => $selectedYear,
    ':month' => $selectedMonth
];

if ($selectedDept > 0) {
    // Show if department is primary owner OR listed in annual_goal_departments
    $query .= " AND (
        ag.responsible_department_id = :dept_id 
        OR ag.id IN (SELECT annual_goal_id FROM annual_goal_departments WHERE department_id = :dept_id)
    )";
    $params[':dept_id'] = $selectedDept;
}

if (!empty($search)) {
    $query .= " AND (ag.goal_code LIKE :search OR ag.title LIKE :search OR ag.definition_of_done LIKE :search)";
    $params[':search'] = "%{$search}%";
}

$query .= " ORDER BY ag.goal_code ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$rawStrategies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process strategies to attach status (TRUE vs FALSE)
$strategies = [];
$totalGoals = count($rawStrategies);
$activeCount = 0;
$inactiveCount = 0;

foreach ($rawStrategies as $strat) {
    // If active_owner_count > 0, it has been activated as TRUE by GM
    $isTrue = ((int)$strat['active_owner_count'] > 0);
    $strat['status_bool'] = $isTrue;
    $strat['status_text'] = $isTrue ? 'TRUE' : 'FALSE';

    if ($isTrue) {
        $activeCount++;
    } else {
        $inactiveCount++;
    }

    // Apply status filter if specified
    if ($selectedStatus === 'TRUE' && !$isTrue) {
        continue;
    }
    if ($selectedStatus === 'FALSE' && $isTrue) {
        continue;
    }

    $strategies[] = $strat;
}
?>

<!-- Page Header with Clean Light Style -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="mb-1 fw-bold text-dark">
            <i class="fas fa-toggle-on text-warning me-2"></i> Monthly Strategy Activation
        </h3>
        <p class="text-muted mb-0">General Manager authorization portal for activating Annual Strategies for <strong><?= e($selectedMonth) ?> <?= e($selectedYear) ?></strong>.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/admin/annual_goals.php') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-bullseye me-1"></i> Annual Goals Master
        </a>
        <a href="<?= url('/admin/monthly_plans.php') ?>" class="btn btn-kaldis">
            <i class="fas fa-calendar-check me-1"></i> View Monthly Plans
        </a>
    </div>
</div>

<!-- Rule Highlight Alert -->
<div class="alert alert-warning border-warning d-flex align-items-center mb-4 shadow-sm bg-white">
    <div class="p-2 bg-warning-subtle text-warning rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
        <i class="fas fa-shield-halved fa-lg"></i>
    </div>
    <div>
        <strong>Mandatory Workflow Rule (Rule 2):</strong> Strategies are <strong>FALSE (Inactive)</strong> by default. When the General Manager turns a strategic plan to <strong>TRUE (Active)</strong>, it automatically activates for <strong>all owned and co-owner departments</strong>. Only activated strategies can be selected for Monthly Strategy Plans.
    </div>
</div>

<!-- Executive Metric Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm bg-white">
            <div class="card-body d-flex align-items-center">
                <div class="p-3 bg-light text-dark rounded-circle me-3">
                    <i class="fas fa-list-check fa-lg text-primary"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Total Strategic Plans</div>
                    <div class="fs-4 fw-bold text-dark" id="statTotalCount"><?= $totalGoals ?></div>
                    <small class="text-muted">Master annual goals for <?= e($selectedYear) ?></small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm bg-white border-start border-4 border-success">
            <div class="card-body d-flex align-items-center">
                <div class="p-3 bg-success-subtle text-success rounded-circle me-3">
                    <i class="fas fa-check-circle fa-lg"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Active for <?= e($selectedMonth) ?></div>
                    <div class="fs-4 fw-bold text-success" id="statActiveCount"><?= $activeCount ?> <span class="badge bg-success text-white fs-6 align-middle ms-1">TRUE</span></div>
                    <small class="text-muted">Activated by GM</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm bg-white border-start border-4 border-secondary">
            <div class="card-body d-flex align-items-center">
                <div class="p-3 bg-secondary-subtle text-secondary rounded-circle me-3">
                    <i class="fas fa-times-circle fa-lg"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Inactive for <?= e($selectedMonth) ?></div>
                    <div class="fs-4 fw-bold text-secondary" id="statInactiveCount"><?= $inactiveCount ?> <span class="badge bg-secondary text-white fs-6 align-middle ms-1">FALSE</span></div>
                    <small class="text-muted">Default inactive status</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm bg-white border-start border-4 border-warning">
            <div class="card-body d-flex align-items-center">
                <div class="p-3 bg-warning-subtle text-warning rounded-circle me-3">
                    <i class="fas fa-sitemap fa-lg"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Owner Departments</div>
                    <div class="fs-4 fw-bold text-dark"><?= count($departments) ?></div>
                    <small class="text-muted">Multi-dept collaboration enabled</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters Bar with Light Corporate Styling -->
<div class="filter-bar mb-4 bg-white p-3 rounded shadow-sm border">
    <form method="GET" action="monthly_activation.php" class="row g-3 align-items-end">
        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted text-uppercase">Planning Year</label>
            <select name="year" class="form-select form-select-sm">
                <?php foreach ($years as $y): ?>
                    <option value="<?= e($y) ?>" <?= $selectedYear === $y ? 'selected' : '' ?>><?= e($y) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted text-uppercase">Planning Month</label>
            <select name="month" class="form-select form-select-sm">
                <?php foreach ($months as $m): ?>
                    <option value="<?= e($m) ?>" <?= $selectedMonth === $m ? 'selected' : '' ?>><?= e($m) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted text-uppercase">Owner Department</label>
            <select name="department_id" class="form-select form-select-sm">
                <option value="0" <?= $selectedDept === 0 ? 'selected' : '' ?>>All Departments (Complete Strategy List)</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= (int)$d['id'] ?>" <?= $selectedDept === (int)$d['id'] ? 'selected' : '' ?>>
                        <?= e($d['department_name']) ?> (<?= e($d['department_code']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted text-uppercase">Status Filter</label>
            <select name="status" class="form-select form-select-sm">
                <option value="ALL" <?= $selectedStatus === 'ALL' ? 'selected' : '' ?>>All Statuses</option>
                <option value="TRUE" <?= $selectedStatus === 'TRUE' ? 'selected' : '' ?>>TRUE (Active Only)</option>
                <option value="FALSE" <?= $selectedStatus === 'FALSE' ? 'selected' : '' ?>>FALSE (Inactive Only)</option>
            </select>
        </div>

        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-kaldis w-100">
                <i class="fas fa-filter me-1"></i> Apply Filters
            </button>
            <a href="monthly_activation.php" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                <i class="fas fa-undo"></i>
            </a>
        </div>
    </form>
</div>

<!-- Strategy Master Activation Card -->
<div class="card shadow-sm border-0 mb-4 bg-white">
    <div class="card-header kaldis-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
        <div class="d-flex align-items-center gap-2">
            <span class="fs-6 fw-bold">
                <i class="fas fa-list-check me-2 text-warning"></i> Strategic Plans for <?= e($selectedMonth) ?> <?= e($selectedYear) ?>
            </span>
            <span class="badge bg-warning text-dark font-monospace">
                Showing: <?= count($strategies) ?> of <?= $totalGoals ?>
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="search-input-wrapper" style="width: 250px;">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control form-control-sm live-search-input live-table-search" placeholder="Quick search strategy, code or dept..." data-target-table=".table-activation">
            </div>
            <!-- Bulk Action Dropdown -->
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-light dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bolt me-1"></i> Batch Actions
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li>
                        <button type="button" class="dropdown-item text-success fw-semibold" onclick="bulkSetActivation('YES')">
                            <i class="fas fa-check-circle me-2"></i> Set Selected to TRUE (Active)
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item text-secondary fw-semibold" onclick="bulkSetActivation('NO')">
                            <i class="fas fa-times-circle me-2"></i> Set Selected to FALSE (Inactive)
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Quick Action / Selection Bar -->
    <div class="bg-light px-3 py-2 border-bottom d-flex justify-content-between align-items-center text-muted small">
        <div class="d-flex align-items-center gap-3">
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
                <label class="form-check-label fw-semibold text-dark" for="selectAllCheckbox">Select All</label>
            </div>
            <span id="selectedCountBadge" class="badge bg-secondary-subtle text-secondary d-none">0 selected</span>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-xs btn-outline-success py-1 px-2" onclick="bulkSetActivation('YES')">
                <i class="fas fa-check me-1"></i> Turn Selected TRUE
            </button>
            <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2" onclick="bulkSetActivation('NO')">
                <i class="fas fa-times me-1"></i> Turn Selected FALSE
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-activation mb-0 align-middle">
            <thead class="table-light text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                <tr>
                    <th style="width: 40px;" class="text-center no-print">#</th>
                    <th style="width: 90px;">Goal ID</th>
                    <th>Annual Strategic Goal & Definition of Done</th>
                    <th style="width: 220px;">Owner Departments</th>
                    <th style="width: 140px;">Annual Target</th>
                    <th style="width: 90px;">Priority</th>
                    <th style="width: 140px;" class="text-center">Status (<?= e($selectedMonth) ?>)</th>
                    <th style="width: 150px;" class="text-center no-print">GM Activation</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($strategies)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-folder-open fa-3x mb-3 d-block opacity-25"></i>
                            No annual strategies found matching the selected filters for <strong><?= e($selectedYear) ?></strong>.
                            <div class="mt-2">
                                <a href="monthly_activation.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-undo me-1"></i> Reset Filters
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($strategies as $strat): ?>
                        <?php 
                            $goalId = (int)$strat['id'];
                            $isTrue = $strat['status_bool'];
                            $owners = $goalOwnerDepts[$goalId] ?? [];
                            if (empty($owners)) {
                                $owners = [[
                                    'department_id' => $strat['responsible_department_id'],
                                    'department_code' => $strat['lead_department_code'],
                                    'department_name' => $strat['lead_department_name'],
                                    'is_primary' => 1
                                ]];
                            }
                        ?>
                        <tr id="strat-row-<?= $goalId ?>" class="<?= $isTrue ? 'table-success-subtle' : '' ?>">
                            <!-- Checkbox -->
                            <td class="text-center no-print">
                                <input type="checkbox" class="form-check-input goal-select-checkbox" value="<?= $goalId ?>" onchange="updateSelectedCount()">
                            </td>
                            <!-- Goal ID -->
                            <td>
                                <span class="badge bg-dark font-monospace fs-6 px-2 py-1"><?= e($strat['goal_code']) ?></span>
                            </td>
                            <!-- Title & DoD -->
                            <td>
                                <div class="fw-bold text-dark mb-1"><?= e($strat['title']) ?></div>
                                <div class="small text-muted">
                                    <span class="fw-semibold text-secondary">DoD:</span> <?= e($strat['definition_of_done']) ?>
                                </div>
                            </td>
                            <!-- Owner Departments (Multi-Department Support) -->
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
                                    <?= count($owners) > 1 ? count($owners) . ' owner departments' : 'Single department' ?>
                                </small>
                            </td>
                            <!-- Annual Target -->
                            <td>
                                <small class="fw-semibold text-dark"><?= e($strat['annual_target']) ?></small>
                            </td>
                            <!-- Priority -->
                            <td>
                                <?= render_priority_badge($strat['priority']) ?>
                            </td>
                            <!-- Status for Month: Default is FALSE, GM turns TRUE -->
                            <td class="text-center status-col">
                                <?php if ($isTrue): ?>
                                    <span class="badge bg-success text-white px-2 py-1 fs-7 shadow-sm status-badge">
                                        <i class="fas fa-check-circle me-1"></i> TRUE (Active)
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 fs-7 status-badge">
                                        <i class="fas fa-times-circle me-1"></i> FALSE (Inactive)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <!-- 1-Click Action Toggle -->
                            <td class="text-center no-print action-col">
                                <button type="button" 
                                        class="btn btn-sm <?= $isTrue ? 'btn-outline-danger' : 'btn-success' ?> btn-activation-toggle px-3 shadow-sm"
                                        data-active="<?= $isTrue ? 'YES' : 'NO' ?>"
                                        data-goal-id="<?= $goalId ?>"
                                        onclick="handleActivationToggle(this, <?= $goalId ?>)">
                                    <?php if ($isTrue): ?>
                                        <i class="fas fa-times me-1"></i> Set FALSE
                                    <?php else: ?>
                                        <i class="fas fa-check me-1"></i> Turn TRUE
                                    <?php endif; ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Light Theme JavaScript Handler for Fast Multi-Department Toggle & Batch Actions -->
<script>
const PLANNING_YEAR = '<?= e($selectedYear) ?>';
const PLANNING_MONTH = '<?= e($selectedMonth) ?>';

/**
 * Handle 1-Click Toggle for a single strategy
 */
function handleActivationToggle(btn, goalId) {
    const currentActive = btn.getAttribute('data-active');
    const newActive = currentActive === 'YES' ? 'NO' : 'YES';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    btn.disabled = true;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    const formData = new FormData();
    formData.append('annual_goal_id', goalId);
    formData.append('year', PLANNING_YEAR);
    formData.append('month', PLANNING_MONTH);
    formData.append('active', newActive);
    formData.append('csrf_token', csrfToken);

    fetch((window.APP_BASE_URL || '') + '/api/activation.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        if (data.success) {
            updateRowUI(goalId, newActive);
            recalculateStats();
            if (typeof showToast === 'function') {
                showToast(data.message, 'success');
            }
        } else {
            btn.innerHTML = originalHtml;
            if (typeof showToast === 'function') {
                showToast(data.message || 'Error updating status', 'error');
            } else {
                alert(data.message || 'Error updating status');
            }
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        console.error(err);
        if (typeof showToast === 'function') {
            showToast('Network or server error', 'error');
        }
    });
}

/**
 * Update row UI elements to reflect new status
 */
function updateRowUI(goalId, newActive) {
    const row = document.getElementById(`strat-row-${goalId}`);
    if (!row) return;

    const btn = row.querySelector('.btn-activation-toggle');
    const statusCol = row.querySelector('.status-col');

    btn.setAttribute('data-active', newActive);

    if (newActive === 'YES') {
        row.classList.add('table-success-subtle');
        statusCol.innerHTML = `
            <span class="badge bg-success text-white px-2 py-1 fs-7 shadow-sm status-badge">
                <i class="fas fa-check-circle me-1"></i> TRUE (Active)
            </span>
        `;
        btn.className = 'btn btn-sm btn-outline-danger btn-activation-toggle px-3 shadow-sm';
        btn.innerHTML = '<i class="fas fa-times me-1"></i> Set FALSE';
    } else {
        row.classList.remove('table-success-subtle');
        statusCol.innerHTML = `
            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 fs-7 status-badge">
                <i class="fas fa-times-circle me-1"></i> FALSE (Inactive)
            </span>
        `;
        btn.className = 'btn btn-sm btn-success btn-activation-toggle px-3 shadow-sm';
        btn.innerHTML = '<i class="fas fa-check me-1"></i> Turn TRUE';
    }
}

/**
 * Bulk Activation / Deactivation
 */
function bulkSetActivation(activeState) {
    const selectedBoxes = document.querySelectorAll('.goal-select-checkbox:checked');
    if (selectedBoxes.length === 0) {
        if (typeof showToast === 'function') {
            showToast('Please select at least one strategy plan.', 'warning');
        } else {
            alert('Please select at least one strategy plan.');
        }
        return;
    }

    const goalIds = Array.from(selectedBoxes).map(cb => cb.value);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const formData = new FormData();
    goalIds.forEach(id => formData.append('goal_ids[]', id));
    formData.append('year', PLANNING_YEAR);
    formData.append('month', PLANNING_MONTH);
    formData.append('active', activeState);
    formData.append('csrf_token', csrfToken);

    fetch((window.APP_BASE_URL || '') + '/api/activation.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            goalIds.forEach(id => updateRowUI(id, activeState));
            recalculateStats();
            if (typeof showToast === 'function') {
                showToast(data.message, 'success');
            }
        } else {
            if (typeof showToast === 'function') {
                showToast(data.message || 'Error processing batch update', 'error');
            }
        }
    })
    .catch(err => {
        console.error(err);
        if (typeof showToast === 'function') {
            showToast('Network error during batch update', 'error');
        }
    });
}

/**
 * Select All Checkboxes
 */
function toggleSelectAll(masterCb) {
    const checkboxes = document.querySelectorAll('.goal-select-checkbox');
    checkboxes.forEach(cb => {
        const row = cb.closest('tr');
        if (row && row.style.display !== 'none') {
            cb.checked = masterCb.checked;
        }
    });
    updateSelectedCount();
}

/**
 * Update Selected Count Badge
 */
function updateSelectedCount() {
    const selectedBoxes = document.querySelectorAll('.goal-select-checkbox:checked');
    const badge = document.getElementById('selectedCountBadge');
    if (selectedBoxes.length > 0) {
        badge.textContent = `${selectedBoxes.length} selected`;
        badge.classList.remove('d-none');
    } else {
        badge.classList.add('d-none');
    }
}

/**
 * Recalculate dynamic statistics
 */
function recalculateStats() {
    const allButtons = document.querySelectorAll('.btn-activation-toggle');
    let active = 0;
    let inactive = 0;

    allButtons.forEach(btn => {
        if (btn.getAttribute('data-active') === 'YES') {
            active++;
        } else {
            inactive++;
        }
    });

    const activeEl = document.getElementById('statActiveCount');
    const inactiveEl = document.getElementById('statInactiveCount');

    if (activeEl) {
        activeEl.innerHTML = `${active} <span class="badge bg-success text-white fs-6 align-middle ms-1">TRUE</span>`;
    }
    if (inactiveEl) {
        inactiveEl.innerHTML = `${inactive} <span class="badge bg-secondary text-white fs-6 align-middle ms-1">FALSE</span>`;
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

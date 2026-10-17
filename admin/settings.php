<?php
/**
 * System Settings, Escalations & Telegram Configuration
 * Kaldis Coffee PLC
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/csrf.php';

Permissions::requireRole([Permissions::ROLE_SUPER_ADMIN, Permissions::ROLE_IT_ADMIN]);

$db = Database::getConnection();
$userId = Auth::id();

// Handle Settings Update before outputting HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        $settingsToSave = [
            'weekly_deadline_day' => trim($_POST['weekly_deadline_day'] ?? 'Monday'),
            'weekly_deadline_time' => trim($_POST['weekly_deadline_time'] ?? '12:00'),
            'monthly_plan_deadline_day' => (int)($_POST['monthly_plan_deadline_day'] ?? 26),
            'monthly_plan_deadline_time' => trim($_POST['monthly_plan_deadline_time'] ?? '17:00'),
            'monthly_deadline_day' => (int)($_POST['monthly_plan_deadline_day'] ?? 26),
            'current_planning_year' => trim($_POST['current_planning_year'] ?? '2019 E.C.'),
            'current_planning_month' => trim($_POST['current_planning_month'] ?? 'Meskerem'),
            'escalation_recipients_hr' => trim($_POST['escalation_recipients_hr'] ?? 'hr@kaldiscoffee.com'),
            'escalation_recipients_gm' => trim($_POST['escalation_recipients_gm'] ?? 'gm@kaldiscoffee.com'),
            'telegram_bot_token' => trim($_POST['telegram_bot_token'] ?? ''),
            'telegram_bot_username' => trim($_POST['telegram_bot_username'] ?? 'KaldisPlannerBot'),
            'telegram_webhook_url' => trim($_POST['telegram_webhook_url'] ?? ''),
        ];

        $updStmt = $db->prepare("
            INSERT INTO system_settings (setting_key, setting_value, updated_by, updated_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = NOW()
        ");

        foreach ($settingsToSave as $key => $val) {
            $updStmt->execute([$key, $val, $userId]);
        }

        // Auto register/update Telegram Webhook
        if (!empty($settingsToSave['telegram_bot_token'])) {
            require_once __DIR__ . '/../config/telegram.php';
            $telegram = new TelegramNotifier($settingsToSave['telegram_bot_token']);
            if (!empty($settingsToSave['telegram_webhook_url'])) {
                $whRes = $telegram->setWebhook($settingsToSave['telegram_webhook_url']);
                if (!empty($whRes['ok'])) {
                    set_flash('success', 'System configurations saved & Telegram Webhook registered successfully!');
                } else {
                    set_flash('warning', 'Settings saved, but Telegram Webhook registration returned: ' . ($whRes['description'] ?? 'Unknown error'));
                }
            }
        } else {
            set_flash('success', 'System configurations saved successfully.');
        }

        audit_log($userId, 'SYSTEM_SETTINGS_UPDATED', 'settings', null, null, $settingsToSave);
        redirect('/admin/settings.php');
    } elseif ($action === 'test_telegram') {
        $chatId = trim($_POST['test_chat_id'] ?? '');
        $token = trim($_POST['test_bot_token'] ?? '');

        require_once __DIR__ . '/../config/telegram.php';
        $telegram = new TelegramNotifier($token ?: null);

        if (!$telegram->isConfigured()) {
            set_flash('danger', 'Telegram Bot Token is not configured yet.');
        } else {
            $msg = "<b>Kaldis Coffee PLC &mdash; Planning System</b>\n\n"
                 . "☕ <i>Test Message Received!</i>\n"
                 . "Ethiopian Time: " . format_ethiopian_datetime() . "\n"
                 . "Your Telegram connection is working properly.";
            $res = $telegram->sendMessage($chatId, $msg);

            if (!empty($res['ok'])) {
                set_flash('success', "Test message sent to Telegram Chat ID {$chatId} successfully!");
            } else {
                set_flash('danger', "Telegram API Error: " . ($res['description'] ?? 'Unknown error'));
            }
        }
        redirect('/admin/settings.php');
    }
}

$pageTitle = 'System & Telegram Settings';
require_once __DIR__ . '/../includes/header.php';

// Fetch all existing settings
$settingsRows = $db->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$deadlineDay = $settingsRows['weekly_deadline_day'] ?? app_config('weekly_deadline_day', 'Monday');
$deadlineTime = $settingsRows['weekly_deadline_time'] ?? app_config('weekly_deadline_time', '12:00');
$monthlyDeadlineDay = (int)($settingsRows['monthly_plan_deadline_day'] ?? $settingsRows['monthly_deadline_day'] ?? app_config('monthly_plan_deadline_day', 26));
$monthlyDeadlineTime = $settingsRows['monthly_plan_deadline_time'] ?? app_config('monthly_plan_deadline_time', '17:00');
$ethPeriod = get_ethiopian_period();
$currentYear = $settingsRows['current_planning_year'] ?? 'AUTO';
$currentMonth = $settingsRows['current_planning_month'] ?? 'AUTO';
$hrRecipients = $settingsRows['escalation_recipients_hr'] ?? 'hr@kaldiscoffee.com';
$gmRecipients = $settingsRows['escalation_recipients_gm'] ?? 'gm@kaldiscoffee.com';
$botToken = $settingsRows['telegram_bot_token'] ?? '';
$botUser = $settingsRows['telegram_bot_username'] ?? 'KaldisPlannerBot';
$webhookUrl = $settingsRows['telegram_webhook_url'] ?? '';

$years = get_planning_years();
$months = get_ethiopian_months();

// Fetch roles with user count
$roles = $db->query("
    SELECT r.*, (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id) as users_count 
    FROM roles r 
    ORDER BY r.id
")->fetchAll(PDO::FETCH_ASSOC);

// Master Permission Matrix
$permissionMatrix = [
    'Strategy & Executive Governance' => [
        'View Corporate Annual Goals' => [
            'desc' => 'View high-level corporate strategic objectives and department assignments',
            'super_admin' => true, 'gm_management' => true, 'hr' => true, 'dept_head' => true, 'it_admin' => true,
        ],
        'Create & Edit Annual Goals' => [
            'desc' => 'Define annual strategic goals, targets and departmental weightings',
            'super_admin' => true, 'gm_management' => true, 'hr' => false, 'dept_head' => false, 'it_admin' => false,
        ],
        'Monthly Strategic Activation' => [
            'desc' => 'Activate and prioritize specific annual goals for current Ethiopian month',
            'super_admin' => true, 'gm_management' => true, 'hr' => false, 'dept_head' => false, 'it_admin' => false,
        ],
        'Access GM Executive Dashboard' => [
            'desc' => 'Executive KPI tracking, cross-department submission heatmaps & scorecards',
            'super_admin' => true, 'gm_management' => true, 'hr' => true, 'dept_head' => false, 'it_admin' => false,
        ],
        'Send Executive Telegram Reminders' => [
            'desc' => 'Dispatch direct executive alerts and nudges to missing department heads',
            'super_admin' => true, 'gm_management' => true, 'hr' => false, 'dept_head' => false, 'it_admin' => true,
        ],
    ],
    'Department Operations & Planning' => [
        'Formulate Monthly Plans' => [
            'desc' => 'Create and draft departmental operational initiatives linked to active goals',
            'super_admin' => true, 'gm_management' => false, 'hr' => false, 'dept_head' => true, 'it_admin' => false,
        ],
        'Submit Monthly Package (Day 26)' => [
            'desc' => 'Transmit final monthly plan package before Day 26 (17:00) cutoff in Ethiopian Calendar',
            'super_admin' => true, 'gm_management' => false, 'hr' => false, 'dept_head' => true, 'it_admin' => false,
        ],
        'Schedule Weekly Tasks' => [
            'desc' => 'Breakdown monthly initiatives into actionable weekly deliverables for staff',
            'super_admin' => true, 'gm_management' => false, 'hr' => false, 'dept_head' => true, 'it_admin' => false,
        ],
        'Evaluate Weekly Execution' => [
            'desc' => 'Grade task results (DONE / NOT DONE) and assign root cause failure categories',
            'super_admin' => true, 'gm_management' => false, 'hr' => false, 'dept_head' => true, 'it_admin' => false,
        ],
    ],
    'Reporting, Compliance & Official Vouchers' => [
        'Submit Weekly Performance Report' => [
            'desc' => 'Transmit weekly execution scorecard before Monday 12:00 PM deadline',
            'super_admin' => true, 'gm_management' => false, 'hr' => false, 'dept_head' => true, 'it_admin' => false,
        ],
        'Generate Official Monthly Voucher' => [
            'desc' => 'Access and print cryptographically-verified official monthly plan voucher with security token',
            'super_admin' => true, 'gm_management' => true, 'hr' => true, 'dept_head' => true, 'it_admin' => true,
        ],
        'Generate Official Weekly Voucher' => [
            'desc' => 'Access and print signed official weekly execution confirmation voucher',
            'super_admin' => true, 'gm_management' => true, 'hr' => true, 'dept_head' => true, 'it_admin' => true,
        ],
        'Organization Scorecard Analytics' => [
            'desc' => 'View 13-department compliance, timely submission rates and historical trends',
            'super_admin' => true, 'gm_management' => true, 'hr' => true, 'dept_head' => false, 'it_admin' => false,
        ],
        'Why Not Done Root Cause Analysis' => [
            'desc' => 'Access organizational failure categorization and recovery action plans',
            'super_admin' => true, 'gm_management' => true, 'hr' => true, 'dept_head' => true, 'it_admin' => false,
        ],
        'Deadline Escalation Tracking' => [
            'desc' => 'Review multi-tier escalation triggers for consecutive missed submissions',
            'super_admin' => true, 'gm_management' => true, 'hr' => true, 'dept_head' => false, 'it_admin' => false,
        ],
    ],
    'System Administration & Security' => [
        'Master Administration Access' => [
            'desc' => 'Access core administrative panels, configuration and database parameters',
            'super_admin' => true, 'gm_management' => false, 'hr' => false, 'dept_head' => false, 'it_admin' => true,
        ],
        'Manage User Accounts & Roles' => [
            'desc' => 'Create, edit, reset passwords, and assign system roles and departments',
            'super_admin' => true, 'gm_management' => false, 'hr' => false, 'dept_head' => false, 'it_admin' => false,
        ],
        'Manage Departments' => [
            'desc' => 'Create, edit, and configure corporate operational departments',
            'super_admin' => true, 'gm_management' => false, 'hr' => false, 'dept_head' => false, 'it_admin' => false,
        ],
        'Configure Deadlines & Parameters' => [
            'desc' => 'Update Ethiopian calendar cycles, Day 26 cutoff, and Monday reporting rules',
            'super_admin' => true, 'gm_management' => false, 'hr' => false, 'dept_head' => false, 'it_admin' => true,
        ],
        'Telegram Bot & Webhook Setup' => [
            'desc' => 'Configure Bot Father token, webhook URL, diagnostic tester, and polling',
            'super_admin' => true, 'gm_management' => false, 'hr' => false, 'dept_head' => false, 'it_admin' => true,
        ],
        'Immutable Audit Trail' => [
            'desc' => 'Inspect comprehensive security audit logs and system trail',
            'super_admin' => true, 'gm_management' => false, 'hr' => false, 'dept_head' => false, 'it_admin' => true,
        ],
    ],
    'Data Scoping & Boundary Rules' => [
        'Enterprise-Wide Data Scope' => [
            'desc' => 'Unrestricted access to data across all 13 company operational units',
            'super_admin' => true, 'gm_management' => true, 'hr' => true, 'dept_head' => false, 'it_admin' => true,
        ],
        'Department-Scoped Isolation' => [
            'desc' => 'Access is strictly limited to user assigned department only',
            'super_admin' => false, 'gm_management' => false, 'hr' => false, 'dept_head' => true, 'it_admin' => false,
        ],
    ],
];
?>

<div class="container-fluid py-2" style="max-width: 1320px; margin: 0 auto;">

    <!-- ── React-Style Pill Navigation Switcher ── -->
    <div class="react-nav-pills mb-4 d-flex flex-wrap gap-2">
        <button class="nav-link active" id="btn-tab-parameters" onclick="switchSettingsTab('parameters', this)">
            <i class="fas fa-sliders-h text-warning me-1"></i> System Parameters &amp; Deadlines
        </button>
        <button class="nav-link" id="btn-tab-permissions" onclick="switchSettingsTab('permissions', this)">
            <i class="fas fa-shield-halved text-success me-1"></i> Role Permissions Matrix
        </button>
        <button class="nav-link" id="btn-tab-telegram" onclick="switchSettingsTab('telegram', this)">
            <i class="fab fa-telegram text-info me-1"></i> Telegram Bot &amp; Diagnostics
        </button>
    </div>

    <!-- ── Header ── -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
        <div>
            <h3 class="mb-1 fw-bold text-dark"><i class="fas fa-sliders text-warning me-2"></i> System Settings &amp; Permissions</h3>
            <p class="text-muted mb-0">Configure Day 26 Ethiopian deadlines, Monday reporting cutoffs, RBAC permissions, and Telegram Bot.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge period-badge py-2 px-3 shadow-xs">
                <i class="far fa-calendar-alt text-warning me-1"></i> <?= format_et_calendar() ?>
            </span>
            <span class="badge period-badge py-2 px-3 shadow-xs" title="Ethiopian Time (East Africa Time)">
                <i class="far fa-clock text-warning me-1"></i> <?= format_ethiopian_time() ?>
            </span>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         TAB 1: PARAMETERS & DEADLINES
    ══════════════════════════════════════════════════════════════ -->
    <div id="tab-parameters" class="settings-tab-pane">
        <form method="POST" action="settings.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_settings">

            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <!-- Planning Period Card -->
                    <div class="card border-0 shadow-sm mb-4" style="background: var(--kaldis-card-bg); border-radius: 14px;">
                        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                            <span class="fs-6 fw-bold text-dark"><i class="fas fa-calendar-check text-warning me-2"></i> Active Planning Period &amp; Cycles</span>
                            <p class="text-muted small mb-0">Corporate Ethiopian planning year and monthly operating period.</p>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">ACTIVE PLANNING YEAR</label>
                                    <select name="current_planning_year" class="form-select">
                                        <option value="AUTO" <?= ($currentYear === 'AUTO' || empty($currentYear)) ? 'selected' : '' ?>>
                                            Auto: Ethiopian Calendar (Currently <?= e($ethPeriod['year']) ?>)
                                        </option>
                                        <?php foreach ($years as $y): ?>
                                            <option value="<?= e($y) ?>" <?= $currentYear === $y ? 'selected' : '' ?>><?= e($y) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Active planning year in Ethiopian Calendar.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">ACTIVE PLANNING MONTH</label>
                                    <select name="current_planning_month" class="form-select">
                                        <option value="AUTO" <?= ($currentMonth === 'AUTO' || empty($currentMonth)) ? 'selected' : '' ?>>
                                            Auto: Ethiopian Calendar (Currently <?= e($ethPeriod['month']) ?>)
                                        </option>
                                        <?php foreach ($months as $m): ?>
                                            <option value="<?= e($m) ?>" <?= $currentMonth === $m ? 'selected' : '' ?>>
                                                <?= e($m) ?><?= $m === $ethPeriod['month'] ? ' (Current Month)' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Rolls over automatically when month elapses.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submission Deadlines Card -->
                    <div class="card border-0 shadow-sm mb-4" style="background: var(--kaldis-card-bg); border-radius: 14px;">
                        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                            <span class="fs-6 fw-bold text-dark"><i class="fas fa-hourglass-half text-primary me-2"></i> Corporate Submission Deadlines</span>
                            <p class="text-muted small mb-0">Enforce strict cutoff boundaries for monthly and weekly submissions.</p>
                        </div>
                        <div class="card-body p-4">
                            <!-- Monthly Plan Deadline (Day 26 Ethiopian) -->
                            <div class="p-3.5 rounded-3 mb-4" style="background: rgba(217,119,6,0.05); border: 1.5px solid rgba(217,119,6,0.25);">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="fw-bold text-warning small text-uppercase">
                                        <i class="far fa-calendar-check me-1"></i> Monthly Plan Submission Due Day (Ethiopian Calendar)
                                    </div>
                                    <span class="badge bg-warning text-dark fw-bold">Corporate Rule</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-secondary">MONTHLY CUTOFF DAY</label>
                                        <select name="monthly_plan_deadline_day" class="form-select" required>
                                            <?php for ($d = 1; $d <= 30; $d++): ?>
                                                <option value="<?= $d ?>" <?= $monthlyDeadlineDay === $d ? 'selected' : '' ?>>
                                                    Day <?= $d ?> of Ethiopian Month <?= $d === 26 ? '★ (Official Due Date: Day 26)' : '' ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                        <small class="text-muted">Official due date: <strong>Day 26 in Ethiopian calendar</strong>.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-secondary">MONTHLY CUTOFF TIME (24H)</label>
                                        <input type="time" name="monthly_plan_deadline_time" class="form-control" value="<?= e($monthlyDeadlineTime) ?>" required>
                                        <small class="text-muted">Cutoff: <strong>17:00 (11:00 Ethiopian Afternoon)</strong>.</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Weekly Report Deadline -->
                            <div class="p-3.5 rounded-3" style="background: rgba(37,99,235,0.05); border: 1.5px solid rgba(37,99,235,0.25);">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="fw-bold text-primary small text-uppercase">
                                        <i class="fas fa-clock me-1"></i> Weekly Report Deadline
                                    </div>
                                    <span class="badge bg-primary text-white fw-bold">Weekly Audit</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-secondary">WEEKLY DEADLINE DAY</label>
                                        <select name="weekly_deadline_day" class="form-select" required>
                                            <?php foreach (['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day): ?>
                                                <option value="<?= $day ?>" <?= $deadlineDay === $day ? 'selected' : '' ?>><?= $day ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Weekly submission due day (default: Monday).</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-secondary">WEEKLY CUTOFF TIME (24H)</label>
                                        <input type="time" name="weekly_deadline_time" class="form-control" value="<?= e($deadlineTime) ?>" required>
                                        <small class="text-muted">Weekly audit cutoff (default: 12:00 PM).</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Escalation Rules Card -->
                    <div class="card border-0 shadow-sm mb-4" style="background: var(--kaldis-card-bg); border-radius: 14px;">
                        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                            <span class="fs-6 fw-bold text-dark"><i class="fas fa-triangle-exclamation text-danger me-2"></i> Non-Submission Escalation Rules &amp; Recipients</span>
                            <p class="text-muted small mb-0">Multi-tier alerting system when weekly deadlines are breached.</p>
                        </div>
                        <div class="card-body p-4">
                            <div class="alert alert-light border small mb-3">
                                <div class="fw-bold mb-1"><i class="fas fa-info-circle text-primary me-1"></i> Configured Escalation Protocol:</div>
                                <ul class="mb-0 text-muted ps-3">
                                    <li><strong>Level 1 (1 missed week):</strong> Automated reminder sent to the Department Head.</li>
                                    <li><strong>Level 2 (2 consecutive missed weeks):</strong> Formal alert dispatched to HR &amp; Operations.</li>
                                    <li><strong>Level 3 (3+ consecutive missed weeks):</strong> High-priority executive escalation to General Management (GM).</li>
                                </ul>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">HR ESCALATION RECIPIENT(S)</label>
                                    <input type="text" name="escalation_recipients_hr" class="form-control" value="<?= e($hrRecipients) ?>" required>
                                    <small class="text-muted">Comma-separated emails or Telegram chat IDs.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">GM ESCALATION RECIPIENT(S)</label>
                                    <input type="text" name="escalation_recipients_gm" class="form-control" value="<?= e($gmRecipients) ?>" required>
                                    <small class="text-muted">Recipients for Level 3 severe escalations.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Telegram Credentials -->
                    <div class="card border-0 shadow-sm mb-4" style="background: var(--kaldis-card-bg); border-radius: 14px;">
                        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                            <span class="fs-6 fw-bold text-dark"><i class="fab fa-telegram text-info me-2"></i> Telegram Bot API</span>
                            <p class="text-muted small mb-0">Bot authentication credentials.</p>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-secondary">TELEGRAM BOT TOKEN</label>
                                <input type="password" name="telegram_bot_token" class="form-control font-monospace" 
                                       value="<?= e($botToken) ?>" placeholder="e.g. 123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ">
                                <small class="text-muted">Generated via <code>@BotFather</code>.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-secondary">BOT USERNAME</label>
                                <input type="text" name="telegram_bot_username" class="form-control font-monospace" 
                                       value="<?= e($botUser) ?>" placeholder="e.g. KaldisPlannerBot">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-secondary">WEBHOOK URL</label>
                                <input type="url" name="telegram_webhook_url" class="form-control font-monospace" 
                                       value="<?= e($webhookUrl) ?>" placeholder="https://domain.com/Planner/telegram/webhook.php">
                            </div>
                        </div>
                    </div>

                    <!-- Save Action Card -->
                    <div class="card border-0 shadow-sm mb-4 sticky-top" style="top: 80px; background: var(--kaldis-card-bg); border-radius: 14px;">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-dark mb-2">Save Parameters</h6>
                            <p class="text-muted small mb-3">Changes are committed to system_settings and logged in the audit trail.</p>
                            <button type="submit" class="btn btn-kaldis w-100 py-2.5 fw-bold shadow-sm">
                                <i class="fas fa-save me-1"></i> Save Configuration
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         TAB 2: ROLE PERMISSIONS MATRIX
    ══════════════════════════════════════════════════════════════ -->
    <div id="tab-permissions" class="settings-tab-pane d-none">
        
        <!-- Role Identity Cards Strip -->
        <div class="row g-3 mb-4">
            <?php foreach ($roles as $r): ?>
                <?php
                    $roleBadges = [
                        'super_admin' => ['color' => '#dc2626', 'bg' => 'rgba(220,38,38,0.1)', 'icon' => 'fa-crown', 'scope' => 'Enterprise (Company-Wide)'],
                        'gm_management' => ['color' => '#d97706', 'bg' => 'rgba(217,119,6,0.1)', 'icon' => 'fa-user-tie', 'scope' => 'Enterprise (Executive)'],
                        'hr' => ['color' => '#2563eb', 'bg' => 'rgba(37,99,235,0.1)', 'icon' => 'fa-users', 'scope' => 'Enterprise (Compliance)'],
                        'dept_head' => ['color' => '#10b981', 'bg' => 'rgba(16,185,129,0.1)', 'icon' => 'fa-building-user', 'scope' => 'Assigned Department Only'],
                        'it_admin' => ['color' => '#7c3aed', 'bg' => 'rgba(124,58,237,0.1)', 'icon' => 'fa-screwdriver-wrench', 'scope' => 'System Administration'],
                    ];
                    $meta = $roleBadges[strtolower($r['name'])] ?? ['color' => '#64748b', 'bg' => 'rgba(100,116,139,0.1)', 'icon' => 'fa-shield-halved', 'scope' => 'Custom Scope'];
                ?>
                <div class="col-md-6 col-lg">
                    <div class="card border-0 shadow-sm h-100" style="background: var(--kaldis-card-bg); border-radius: 12px; border-top: 3px solid <?= $meta['color'] ?> !important;">
                        <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                         style="width: 36px; height: 36px; background: <?= $meta['bg'] ?>; color: <?= $meta['color'] ?>; font-size: 1rem;">
                                        <i class="fas <?= $meta['icon'] ?>"></i>
                                    </div>
                                    <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.7rem;">
                                        <?= (int)($r['users_count'] ?? 0) ?> Users
                                    </span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1" style="font-size: 0.95rem;"><?= e($r['display_name']) ?></h6>
                                <div class="badge px-2 py-0.5 mb-2" style="background: <?= $meta['bg'] ?>; color: <?= $meta['color'] ?>; font-size: 0.68rem; font-weight: 600;">
                                    <?= $meta['scope'] ?>
                                </div>
                                <p class="text-muted small mb-0" style="font-size: 0.78rem; line-height: 1.4;">
                                    <?= e($r['description']) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Master Permissions Matrix Table -->
        <div class="card border-0 shadow-sm mb-4" style="background: var(--kaldis-card-bg); border-radius: 14px; overflow: hidden;">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                <div>
                    <h5 class="fw-bold text-dark mb-1">
                        <i class="fas fa-list-check text-success me-2"></i> Comprehensive Permissions Matrix
                    </h5>
                    <p class="text-muted small mb-0">Full catalogue of 25 system capabilities mapped across all user roles.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success-subtle text-success py-1.5 px-2.5">
                        <i class="fas fa-check me-1"></i> Authorized
                    </span>
                    <span class="badge bg-light text-muted border py-1.5 px-2.5">
                        <i class="fas fa-minus me-1"></i> Restricted
                    </span>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.86rem;">
                        <thead class="table-light text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.04em;">
                            <tr>
                                <th style="width: 32%; padding-left: 1.5rem;">Permission &amp; Responsibility Scope</th>
                                <th class="text-center" style="width: 13.6%;">Super Admin</th>
                                <th class="text-center" style="width: 13.6%;">General Manager</th>
                                <th class="text-center" style="width: 13.6%;">HR Officer</th>
                                <th class="text-center" style="width: 13.6%;">Dept Head</th>
                                <th class="text-center" style="width: 13.6%; padding-right: 1.5rem;">IT Admin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($permissionMatrix as $category => $permissions): ?>
                                <tr style="background: rgba(0,0,0,0.025);">
                                    <td colspan="6" class="fw-bold text-dark py-2.5" style="padding-left: 1.5rem; font-size: 0.8rem; letter-spacing: 0.02em;">
                                        <i class="fas fa-folder-open text-primary me-2"></i> <?= strtoupper($category) ?>
                                    </td>
                                </tr>

                                <?php foreach ($permissions as $permName => $data): ?>
                                    <tr>
                                        <td style="padding-left: 1.5rem;">
                                            <div class="fw-bold text-dark"><?= e($permName) ?></div>
                                            <div class="text-muted small" style="font-size: 0.76rem; line-height: 1.3;"><?= e($data['desc']) ?></div>
                                        </td>
                                        <td class="text-center">
                                            <?= $data['super_admin'] ? '<span class="badge bg-success-subtle text-success py-1 px-2"><i class="fas fa-check"></i></span>' : '<span class="text-muted opacity-25">&mdash;</span>' ?>
                                        </td>
                                        <td class="text-center">
                                            <?= $data['gm_management'] ? '<span class="badge bg-success-subtle text-success py-1 px-2"><i class="fas fa-check"></i></span>' : '<span class="text-muted opacity-25">&mdash;</span>' ?>
                                        </td>
                                        <td class="text-center">
                                            <?= $data['hr'] ? '<span class="badge bg-success-subtle text-success py-1 px-2"><i class="fas fa-check"></i></span>' : '<span class="text-muted opacity-25">&mdash;</span>' ?>
                                        </td>
                                        <td class="text-center">
                                            <?= $data['dept_head'] ? '<span class="badge bg-success-subtle text-success py-1 px-2"><i class="fas fa-check"></i></span>' : '<span class="text-muted opacity-25">&mdash;</span>' ?>
                                        </td>
                                        <td class="text-center" style="padding-right: 1.5rem;">
                                            <?= $data['it_admin'] ? '<span class="badge bg-success-subtle text-success py-1 px-2"><i class="fas fa-check"></i></span>' : '<span class="text-muted opacity-25">&mdash;</span>' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card-footer bg-light p-3 small text-muted d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span>
                    <i class="fas fa-lock text-secondary me-1"></i>
                    All permissions are strictly enforced server-side via Permissions::requireRole() and department scoping checks.
                </span>
                <span class="badge bg-secondary-subtle text-secondary font-monospace">Security Standard RBAC v2.4</span>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         TAB 3: TELEGRAM BOT & DIAGNOSTIC PING
    ══════════════════════════════════════════════════════════════ -->
    <div id="tab-telegram" class="settings-tab-pane d-none">
        <div class="card border-0 shadow-sm mb-4" style="background: var(--kaldis-card-bg); border-radius: 14px;">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold text-dark mb-1"><i class="fas fa-paper-plane text-info me-2"></i> Telegram Diagnostic Ping</h5>
                    <p class="text-muted small mb-0">Dispatch an immediate test notification to verify bot token and chat delivery.</p>
                </div>
                <span class="badge bg-info-subtle text-info fw-bold py-1.5 px-2.5">Live Ping Tester</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="settings.php" class="row g-3 align-items-end">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="test_telegram">
                    <input type="hidden" name="test_bot_token" value="<?= e($botToken) ?>">

                    <div class="col-md-7">
                        <label class="form-label small fw-bold text-secondary">TARGET TELEGRAM CHAT ID</label>
                        <input type="text" name="test_chat_id" class="form-control font-monospace" placeholder="e.g. 987654321" required>
                        <small class="text-muted">You can get your Telegram Chat ID by messaging <code>@userinfobot</code> on Telegram.</small>
                    </div>

                    <div class="col-md-5">
                        <button type="submit" class="btn btn-outline-info text-dark w-100 fw-bold py-2" <?= empty($botToken) ? 'disabled' : '' ?>>
                            <i class="fab fa-telegram-plane me-1"></i> Send Diagnostic Ping
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<!-- ── Tab Switcher Script ── -->
<script>
    function switchSettingsTab(tabName, btn) {
        document.querySelectorAll('.settings-tab-pane').forEach(el => el.classList.add('d-none'));
        document.querySelectorAll('.react-nav-pills .nav-link').forEach(el => el.classList.remove('active'));
        
        const target = document.getElementById('tab-' + tabName);
        if (target) {
            target.classList.remove('d-none');
        }
        if (btn) {
            btn.classList.add('active');
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

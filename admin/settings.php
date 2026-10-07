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

        audit_log($userId, 'SYSTEM_SETTINGS_UPDATED', 'settings', null, null, $settingsToSave);
        set_flash('success', 'System configurations saved successfully.');
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
                 . "Server Timestamp: " . date('Y-m-d H:i:s') . "\n"
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
$currentYear = $settingsRows['current_planning_year'] ?? app_config('current_planning_year', '2019 E.C.');
$currentMonth = $settingsRows['current_planning_month'] ?? app_config('current_planning_month', 'Nehase');
$hrRecipients = $settingsRows['escalation_recipients_hr'] ?? 'hr@kaldiscoffee.com';
$gmRecipients = $settingsRows['escalation_recipients_gm'] ?? 'gm@kaldiscoffee.com';
$botToken = $settingsRows['telegram_bot_token'] ?? '';
$botUser = $settingsRows['telegram_bot_username'] ?? 'KaldisPlannerBot';
$webhookUrl = $settingsRows['telegram_webhook_url'] ?? '';

$years = get_planning_years();
$months = get_ethiopian_months();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="mb-1 fw-bold text-dark"><i class="fas fa-sliders text-warning me-2"></i> System Settings & Telegram</h3>
        <p class="text-muted mb-0">Configure reporting cutoffs, multi-tier escalation recipients, and Telegram Bot credentials.</p>
    </div>
</div>

<form method="POST" action="settings.php">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_settings">

    <!-- Card 1: Planning & Deadlines -->
    <div class="card mb-4">
        <div class="card-header kaldis-header">
            <span class="fs-6 fw-bold"><i class="fas fa-calendar-check me-2"></i> Active Planning Period & Submission Deadlines</span>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">ACTIVE PLANNING YEAR</label>
                    <select name="current_planning_year" class="form-select">
                        <?php foreach ($years as $y): ?>
                            <option value="<?= e($y) ?>" <?= $currentYear === $y ? 'selected' : '' ?>><?= e($y) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">ACTIVE PLANNING MONTH</label>
                    <select name="current_planning_month" class="form-select">
                        <?php foreach ($months as $m): ?>
                            <option value="<?= e($m) ?>" <?= $currentMonth === $m ? 'selected' : '' ?>><?= e($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold">WEEKLY REPORT DEADLINE DAY <span class="text-danger">*</span></label>
                    <select name="weekly_deadline_day" class="form-select" required>
                        <?php foreach (['Monday', 'Tuesday', 'Friday', 'Saturday', 'Sunday'] as $day): ?>
                            <option value="<?= $day ?>" <?= $deadlineDay === $day ? 'selected' : '' ?>><?= $day ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Default is Monday.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">WEEKLY CUTOFF TIME (24H) <span class="text-danger">*</span></label>
                    <input type="time" name="weekly_deadline_time" class="form-control" value="<?= e($deadlineTime) ?>" required>
                    <small class="text-muted">Default is 12:00 PM.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Multi-Tier Escalations -->
    <div class="card mb-4">
        <div class="card-header kaldis-header">
            <span class="fs-6 fw-bold"><i class="fas fa-triangle-exclamation me-2"></i> Non-Submission Escalation Rules & Recipients</span>
        </div>
        <div class="card-body p-4">
            <div class="alert alert-light border small mb-3">
                <div class="fw-bold mb-1"><i class="fas fa-info-circle text-primary me-1"></i> Configured Escalation Protocol:</div>
                <ul class="mb-0">
                    <li><strong>Level 1 (1 missed week):</strong> Automated reminder sent to the Department Head.</li>
                    <li><strong>Level 2 (2 consecutive missed weeks):</strong> Formal alert dispatched to HR & Operations.</li>
                    <li><strong>Level 3 (3+ consecutive missed weeks):</strong> High-priority executive escalation to General Management (GM).</li>
                </ul>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">HR & OPERATIONS ESCALATION RECIPIENT(S)</label>
                    <input type="text" name="escalation_recipients_hr" class="form-control" value="<?= e($hrRecipients) ?>" required>
                    <small class="text-muted">Comma-separated emails or Telegram chat IDs.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">SENIOR MANAGEMENT / GM ESCALATION RECIPIENT(S)</label>
                    <input type="text" name="escalation_recipients_gm" class="form-control" value="<?= e($gmRecipients) ?>" required>
                    <small class="text-muted">Recipients for Level 3 severe escalations.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Telegram Bot API Configuration -->
    <div class="card mb-4">
        <div class="card-header kaldis-header">
            <span class="fs-6 fw-bold"><i class="fab fa-telegram me-2"></i> Telegram Bot API Integration</span>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">TELEGRAM BOT TOKEN</label>
                    <input type="text" name="telegram_bot_token" class="form-control font-monospace" 
                           value="<?= e($botToken) ?>" placeholder="e.g. 123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ">
                    <small class="text-muted">Generated via <code>@BotFather</code> on Telegram.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">BOT USERNAME</label>
                    <input type="text" name="telegram_bot_username" class="form-control font-monospace" 
                           value="<?= e($botUser) ?>" placeholder="e.g. KaldisPlannerBot">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">WEBHOOK URL</label>
                    <input type="url" name="telegram_webhook_url" class="form-control font-monospace" 
                           value="<?= e($webhookUrl) ?>" placeholder="https://yourdomain.com/Planner/telegram/webhook.php">
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-kaldis btn-lg mb-5 px-5">
        <i class="fas fa-save me-2"></i> Save System Configuration
    </button>
</form>

<!-- Card 4: Telegram Connectivity Test / Simulator -->
<div class="card mb-4 border-info">
    <div class="card-header bg-info text-dark d-flex justify-content-between align-items-center">
        <span class="fs-6 fw-bold"><i class="fas fa-paper-plane me-2"></i> Telegram Test Ping / Simulator</span>
        <span class="badge bg-dark text-white">Diagnostic Tool</span>
    </div>
    <div class="card-body p-4">
        <p class="small text-muted mb-3">
            Send an instant test notification to verify your bot token and chat connection.
        </p>

        <form method="POST" action="settings.php" class="row g-3 align-items-end">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="test_telegram">
            <input type="hidden" name="test_bot_token" value="<?= e($botToken) ?>">

            <div class="col-md-6">
                <label class="form-label small fw-bold">TARGET TELEGRAM CHAT ID</label>
                <input type="text" name="test_chat_id" class="form-control font-monospace" placeholder="e.g. 987654321" required>
                <small class="text-muted">You can get your Telegram Chat ID by messaging <code>@userinfobot</code> on Telegram.</small>
            </div>

            <div class="col-md-6">
                <button type="submit" class="btn btn-outline-info text-dark w-100 fw-bold" <?= empty($botToken) ? 'disabled' : '' ?>>
                    <i class="fab fa-telegram-plane me-1"></i> Send Diagnostic Ping
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

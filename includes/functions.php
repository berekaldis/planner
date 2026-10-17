<?php
/**
 * Global Helper Functions
 * Kaldis Coffee PLC
 */

if (ob_get_level() === 0) {
    ob_start();
}

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

require_once __DIR__ . '/audit.php';

/**
 * Escape HTML output safely
 */
if (!function_exists('e')) {
    function e(?string $string): string {
        return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Return application configuration array or specific key
 */
if (!function_exists('app_config')) {
    function app_config(?string $key = null, $default = null) {
        static $config = null;
        if ($config === null) {
            $config = require __DIR__ . '/../config/config.php';
        }
        if ($key === null) {
            return $config;
        }
        if ($key === 'current_planning_month') {
            return function_exists('get_current_planning_month') ? get_current_planning_month() : ($config[$key] ?? $default);
        }
        if ($key === 'current_planning_year') {
            return function_exists('get_current_planning_year') ? get_current_planning_year() : ($config[$key] ?? $default);
        }
        return $config[$key] ?? $default;
    }
}

if (!function_exists('get_base_url')) {
    function get_base_url(): string {
        static $base = null;
        if ($base !== null) {
            return $base;
        }

        $reqUri = $_SERVER['REQUEST_URI'] ?? '';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

        if (str_starts_with($reqUri, '/Planner') || str_starts_with($scriptName, '/Planner')) {
            $base = '/Planner';
        } else {
            $configured = rtrim((string)app_config('base_url', ''), '/');
            if ($configured !== '' && (str_starts_with($reqUri, $configured) || str_starts_with($scriptName, $configured))) {
                $base = $configured;
            } else {
                $base = '';
            }
        }
        return $base;
    }
}

if (class_exists('Illuminate\Container\Container')) {
    $container = Illuminate\Container\Container::getInstance();
    if (!$container->bound('url')) {
        $container->singleton('url', function() {
            return new class {
                public function to($path = '', $extra = [], $secure = null) {
                    $baseUrl = get_base_url();
                    $cleanPath = '/' . ltrim($path, '/');
                    return ($baseUrl ?: '') . $cleanPath;
                }
            };
        });
        $container->alias('url', \Illuminate\Contracts\Routing\UrlGenerator::class);
    }
    if (!$container->bound('redirect')) {
        $container->singleton('redirect', function() {
            return new class {
                public function to($path, $status = 302, $headers = [], $secure = null) {
                    $baseUrl = get_base_url();
                    $fullUrl = ($baseUrl ?: '') . '/' . ltrim($path, '/');
                    header('Location: ' . $fullUrl, true, $status);
                    exit;
                }
            };
        });
        $container->alias('redirect', \Illuminate\Routing\Redirector::class);
    }
}

/**
 * Base URL helper
 */
if (!function_exists('url')) {
    function url(string $path = ''): string {
        $baseUrl = get_base_url();
        $cleanPath = '/' . ltrim($path, '/');
        return ($baseUrl ?: '') . $cleanPath;
    }
}

/**
 * Redirect helper
 */
if (!function_exists('redirect')) {
    function redirect(string $path): void {
        header('Location: ' . url($path));
        exit;
    }
}

/**
 * Set flash alert message
 */
if (!function_exists('set_flash')) {
    function set_flash(string $type, string $message): void {
        $_SESSION['flash'] = [
            'type' => $type, // success, danger, warning, info
            'message' => $message
        ];
    }
}

/**
 * Get and clear flash message
 */
if (!function_exists('get_flash')) {
    function get_flash(): ?array {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}

/**
 * List of Ethiopian Calendar Months (Pagume excluded as requested)
 */
if (!function_exists('get_ethiopian_months')) {
    function get_ethiopian_months(): array {
        return [
            'Meskerem', 'Tikimt', 'Hidar', 'Tahsas',
            'Tir', 'Yakatit', 'Megabit', 'Miazia',
            'Ginbot', 'Sene', 'Hamle', 'Nehase'
        ];
    }
}

/**
 * List of Planning Years supported (2019 E.C. to 2030 E.C.)
 */
if (!function_exists('get_planning_years')) {
    function get_planning_years(): array {
        $years = [];
        for ($y = 2019; $y <= 2030; $y++) {
            $years[] = $y . ' E.C.';
        }
        return $years;
    }
}

/**
 * Get weeks list for a month (1 to 5)
 */
if (!function_exists('get_month_weeks')) {
    function get_month_weeks(): array {
        return [
            1 => 'Week 1',
            2 => 'Week 2',
            3 => 'Week 3',
            4 => 'Week 4',
            5 => 'Week 5'
        ];
    }
}

/**
 * Calculate current Ethiopian Year, Month, Week (1 to 5), and Day
 * 5 weeks per month mapping: Days 1-7 (Week 1), 8-14 (Week 2), 15-21 (Week 3), 22-28 (Week 4), 29-30 (Week 5)
 */
if (!function_exists('get_ethiopian_period')) {
    function get_ethiopian_period(?string $gregorianDate = null): array {
        $timestamp = $gregorianDate ? strtotime($gregorianDate) : time();
        $year = (int)date('Y', $timestamp);
        $dayOfYear = (int)date('z', $timestamp);

        $months = get_ethiopian_months();
        $sept11DayOfYear = (int)date('z', strtotime("{$year}-09-11"));
        
        if ($dayOfYear >= $sept11DayOfYear) {
            $ethYear = ($year - 7) . ' E.C.';
            $daysSinceNewYear = $dayOfYear - $sept11DayOfYear;
        } else {
            $ethYear = ($year - 8) . ' E.C.';
            $prevSept11 = (int)date('z', strtotime(($year - 1) . "-09-11"));
            $daysInPrevYear = (int)date('z', strtotime(($year - 1) . "-12-31")) + 1;
            $daysSinceNewYear = ($daysInPrevYear - $prevSept11) + $dayOfYear;
        }

        $monthIndex = (int)floor($daysSinceNewYear / 30);
        if ($monthIndex >= 12) {
            $monthIndex = 11;
        }
        $ethMonth = $months[$monthIndex] ?? 'Meskerem';
        $dayInMonth = ($daysSinceNewYear % 30) + 1;
        $week = (int)min(5, max(1, ceil($dayInMonth / 7)));

        return [
            'year' => $ethYear,
            'month' => $ethMonth,
            'week' => $week,
            'day' => $dayInMonth
        ];
    }
}

if (!function_exists('get_current_planning_week')) {
    function get_current_planning_week(): int {
        $period = get_ethiopian_period();
        return $period['week'];
    }
}

if (!function_exists('normalize_ethiopian_month')) {
    function normalize_ethiopian_month(?string $month): string {
        if (!$month) return 'Meskerem';
        $m = trim($month);
        $map = [
            'tikemt' => 'Tikimt',
            'tikemet' => 'Tikimt',
            'hidare' => 'Hidar',
            'magabit' => 'Megabit',
            'miyazya' => 'Miazia',
        ];
        $lower = strtolower($m);
        return $map[$lower] ?? ucfirst($lower);
    }
}

if (!function_exists('get_current_planning_month')) {
    function get_current_planning_month(): string {
        $setting = null;
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'current_planning_month'");
            $val = $stmt ? $stmt->fetchColumn() : null;
            if ($val !== false && $val !== null) {
                $setting = trim((string)$val);
            }
        } catch (\Throwable $e) {}

        if (empty($setting) || strtoupper($setting) === 'AUTO') {
            $eth = get_ethiopian_period();
            return $eth['month'];
        }

        return normalize_ethiopian_month($setting);
    }
}

if (!function_exists('get_current_planning_year')) {
    function get_current_planning_year(): string {
        $setting = null;
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'current_planning_year'");
            $val = $stmt ? $stmt->fetchColumn() : null;
            if ($val !== false && $val !== null) {
                $setting = trim((string)$val);
            }
        } catch (\Throwable $e) {}

        if (empty($setting) || strtoupper($setting) === 'AUTO') {
            $eth = get_ethiopian_period();
            return $eth['year'];
        }

        return $setting;
    }
}

/**
 * Render Status Badge for Tasks (DONE / NOT_DONE)
 */
if (!function_exists('render_task_badge')) {
    function render_task_badge(?string $result): string {
        if ($result === 'DONE') {
            return '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> DONE</span>';
        } elseif ($result === 'NOT_DONE') {
            return '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i> NOT DONE</span>';
        }
        return '<span class="badge bg-secondary"><i class="fas fa-hourglass-start me-1"></i> PENDING</span>';
    }
}

/**
 * Render Status Badge for Submission Tracking
 */
if (!function_exists('render_submission_badge')) {
    function render_submission_badge(?string $status): string {
        switch ($status) {
            case 'ON_TIME':
                return '<span class="badge bg-success"><i class="fas fa-clock-check me-1"></i> ON TIME</span>';
            case 'LATE':
                return '<span class="badge bg-warning text-dark"><i class="fas fa-clock-rotate-left me-1"></i> LATE</span>';
            case 'MISSING':
                return '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle me-1"></i> MISSING</span>';
            case 'WAITING':
            default:
                return '<span class="badge bg-info text-dark"><i class="fas fa-hourglass-half me-1"></i> WAITING</span>';
        }
    }
}

/**
 * Render Plan Type Badge
 */
if (!function_exists('render_plan_type_badge')) {
    function render_plan_type_badge(string $type): string {
        if (strtoupper($type) === 'STRATEGY') {
            return '<span class="badge bg-primary"><i class="fas fa-chess-knight me-1"></i> STRATEGY</span>';
        }
        return '<span class="badge bg-secondary"><i class="fas fa-cogs me-1"></i> OPERATIONAL</span>';
    }
}

/**
 * Render Priority Badge
 */
if (!function_exists('render_priority_badge')) {
    function render_priority_badge(string $priority): string {
        switch (strtoupper($priority)) {
            case 'HIGH':
                return '<span class="badge bg-danger">HIGH</span>';
            case 'MEDIUM':
                return '<span class="badge bg-warning text-dark">MEDIUM</span>';
            case 'LOW':
                return '<span class="badge bg-secondary">LOW</span>';
            default:
                return '<span class="badge bg-light text-dark">' . e($priority) . '</span>';
        }
    }
}

/**
 * Calculate weekly reporting deadline timestamp
 * Default: Current week's Monday 12:00 PM
 */
if (!function_exists('get_weekly_deadline')) {
    function get_weekly_deadline(string $year, string $month, int $week): string {
        // In production, can be configured in system_settings table.
        // For general tracking, Monday 12:00 PM is calculated or returned.
        return date('Y-m-d 12:00:00', strtotime('next Monday'));
    }
}

/**
 * Get dynamic greeting (Good Morning / Good Afternoon / Good Evening)
 */
if (!function_exists('get_greeting')) {
    function get_greeting(): string {
        $hour = (int)date('G');
        if ($hour >= 5 && $hour < 12) {
            return 'Good Morning';
        } elseif ($hour >= 12 && $hour < 18) {
            return 'Good Afternoon';
        } else {
            return 'Good Evening';
        }
    }
}

if (!function_exists('send_telegram_notification')) {
    function send_telegram_notification(string|int $chatId, string $message): bool {
        try {
            $token = '';
            try {
                if (!class_exists('Database')) {
                    @require_once __DIR__ . '/../config/database.php';
                }
                if (class_exists('Database')) {
                    $db = Database::getConnection();
                    $stmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'telegram_bot_token'");
                    $val = $stmt ? $stmt->fetchColumn() : null;
                    if (!empty($val)) {
                        $token = trim($val);
                    }
                }
            } catch (\Throwable $e) {}

            if (empty($token)) {
                $config = @include __DIR__ . '/../config/telegram.php';
                if (is_array($config) && !empty($config['bot_token'])) {
                    $token = trim($config['bot_token']);
                } else {
                    $token = getenv('TELEGRAM_BOT_TOKEN') ?: '8370988256:AAE8aG5s2vzEy6PfSH2b-Fiufr_num0A_rY';
                }
            }

            if (empty($token) || empty($chatId)) {
                return false;
            }

            $url = "https://api.telegram.org/bot{$token}/sendMessage";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 12);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if ($err) {
                error_log("send_telegram_notification curl error: " . $err);
                return false;
            }

            $res = json_decode($response, true);
            $ok = !empty($res['ok']);

            try {
                if (isset($db)) {
                    $logStmt = $db->prepare("
                        INSERT INTO telegram_messages (message_id, telegram_user_id, message_text, direction, status, created_at)
                        VALUES (:mid, :uid, :msg, 'OUTGOING', :st, NOW())
                    ");
                    $logStmt->execute([
                        ':mid' => $res['result']['message_id'] ?? 0,
                        ':uid' => (string)$chatId,
                        ':msg' => $message,
                        ':st' => $ok ? 'SENT' : 'FAILED',
                    ]);
                }
            } catch (\Throwable $e) {}

            return $ok;
        } catch (\Throwable $e) {
            error_log("send_telegram_notification error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Dispatch notification to General Management (GM) on Department submissions
 * Supports both in-app notifications and Telegram alerts
 */
if (!function_exists('notify_gm_submission')) {
    function notify_gm_submission(string $title, string $message, ?int $deptId = null, ?string $voucherUrl = null): void {
        try {
            if (!class_exists('Database')) {
                require_once __DIR__ . '/../config/database.php';
            }
            $db = Database::getConnection();

            // 1. Fetch GM users (role = gm_management or super_admin, or role_id IN (1, 2))
            $gmStmt = $db->query("
                SELECT u.id, u.telegram_chat_id, u.full_name
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE (r.name IN ('gm_management', 'super_admin', 'gm', 'general_manager') OR u.role_id IN (1, 2)) AND u.is_active = 1
            ");
            $gmUsers = $gmStmt->fetchAll(PDO::FETCH_ASSOC);

            // 2. Insert in-app notifications
            $insNotif = $db->prepare("
                INSERT INTO notifications (user_id, department_id, title, message, notification_type, created_at)
                VALUES (:uid, :did, :title, :msg, 'REPORT_SUBMISSION', NOW())
            ");

            $chatIdsToNotify = [];
            $fullMessage = $message . ($voucherUrl ? "\n\nVoucher: " . $voucherUrl : '');

            if (!empty($gmUsers)) {
                foreach ($gmUsers as $gm) {
                    $insNotif->execute([
                        ':uid' => $gm['id'],
                        ':did' => $deptId,
                        ':title' => $title,
                        ':msg' => $fullMessage
                    ]);
                    if (!empty($gm['telegram_chat_id'])) {
                        $chatIdsToNotify[] = trim($gm['telegram_chat_id']);
                    }
                }
            } else {
                $insNotif->execute([
                    ':uid' => null,
                    ':did' => $deptId,
                    ':title' => $title,
                    ':msg' => $fullMessage
                ]);
            }

            // Also check system_settings for escalation_recipients_gm
            $sysStmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'escalation_recipients_gm'");
            $gmRecipientsSetting = trim((string)($sysStmt->fetchColumn() ?: ''));
            if (!empty($gmRecipientsSetting)) {
                $recipients = array_map('trim', explode(',', $gmRecipientsSetting));
                foreach ($recipients as $rec) {
                    if (is_numeric($rec) || str_starts_with($rec, '@')) {
                        $chatIdsToNotify[] = $rec;
                    }
                }
            }

            $chatIdsToNotify = array_unique(array_filter($chatIdsToNotify));

            // 3. Send Telegram Notification if configured
            if (!empty($chatIdsToNotify)) {
                $telegramMsg = "<b>" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</b>\n\n"
                             . htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
                if ($voucherUrl) {
                    $telegramMsg .= "\n\n📄 <a href=\"" . htmlspecialchars($voucherUrl, ENT_QUOTES, 'UTF-8') . "\"><b>View Official Voucher</b></a>";
                }
                $telegramMsg .= "\n\n📅 <i>" . date('Y-m-d H:i') . "</i>";

                foreach ($chatIdsToNotify as $chatId) {
                    send_telegram_notification($chatId, $telegramMsg);
                }
            }
        } catch (\Throwable $e) {
            error_log("notify_gm_submission error: " . $e->getMessage());
        }
    }
}



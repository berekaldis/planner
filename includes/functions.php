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

if (!function_exists('get_ethiopian_month_names')) {
    function get_ethiopian_month_names(): array {
        return [
            1 => ['en' => 'Meskerem', 'am' => 'መስከረም'],
            2 => ['en' => 'Tikimt', 'am' => 'ጥቅምት'],
            3 => ['en' => 'Hidar', 'am' => 'ኅዳር'],
            4 => ['en' => 'Tahsas', 'am' => 'ታኅሣሥ'],
            5 => ['en' => 'Tir', 'am' => 'ጥር'],
            6 => ['en' => 'Yakatit', 'am' => 'የካቲት'],
            7 => ['en' => 'Megabit', 'am' => 'መጋቢት'],
            8 => ['en' => 'Miazia', 'am' => 'ሚያዝያ'],
            9 => ['en' => 'Ginbot', 'am' => 'ግንቦት'],
            10 => ['en' => 'Sene', 'am' => 'ሰኔ'],
            11 => ['en' => 'Hamle', 'am' => 'ሐምሌ'],
            12 => ['en' => 'Nehase', 'am' => 'ነሐሴ'],
            13 => ['en' => 'Pagume', 'am' => 'ጳጉሜን'],
        ];
    }
}

if (!function_exists('get_ethiopian_day_names')) {
    function get_ethiopian_day_names(): array {
        return [
            0 => ['en' => 'Sunday', 'am' => 'እሑድ'],
            1 => ['en' => 'Monday', 'am' => 'ሰኞ'],
            2 => ['en' => 'Tuesday', 'am' => 'ማክሰኞ'],
            3 => ['en' => 'Wednesday', 'am' => 'ረቡዕ'],
            4 => ['en' => 'Thursday', 'am' => 'ሐሙስ'],
            5 => ['en' => 'Friday', 'am' => 'ዓርብ'],
            6 => ['en' => 'Saturday', 'am' => 'ቅዳሜ'],
        ];
    }
}

if (!function_exists('gregorian_to_ethiopian_date')) {
    /**
     * Exact Gregorian to Ethiopian date conversion
     * Reference: https://www.metaappz.com/References/Time_and_Date_in_Ethiopia
     */
    function gregorian_to_ethiopian_date(int $gy, int $gm, int $gd): array {
        if ($gm <= 2) {
            $gy -= 1;
            $gm += 12;
        }
        $a = (int)floor($gy / 100);
        $b = 2 - $a + (int)floor($a / 4);
        $jdn = (int)floor(365.25 * ($gy + 4716)) + (int)floor(30.6001 * ($gm + 1)) + $gd + $b - 1524;

        $r = ($jdn - 1723856) % 1461;
        if ($r < 0) {
            $r += 1461;
        }
        $n = ($r % 365) + 365 * (int)floor($r / 1460);
        $ey = 4 * (int)floor(($jdn - 1723856) / 1461) + (int)floor($r / 365) - (int)floor($r / 1460);
        $em = (int)floor($n / 30) + 1;
        $ed = ($n % 30) + 1;

        return [$ey, $em, $ed];
    }
}

if (!function_exists('ethiopian_to_gregorian_date')) {
    /**
     * Exact Ethiopian to Gregorian date conversion
     */
    function ethiopian_to_gregorian_date(int $ey, int $em, int $ed): array {
        $jdn = (1723856 + 365) + 365 * ($ey - 1) + (int)floor($ey / 4) + 30 * ($em - 1) + $ed - 1;

        $l = $jdn + 68569;
        $n = (int)floor(4 * $l / 146097);
        $l = $l - (int)floor((146097 * $n + 3) / 4);
        $i = (int)floor(4000 * ($l + 1) / 1461001);
        $l = $l - (int)floor(1461 * $i / 4) + 31;
        $j = (int)floor(80 * $l / 2447);
        $gd = $l - (int)floor(2447 * $j / 80);
        $l = (int)floor($j / 11);
        $gm = $j + 2 - 12 * $l;
        $gy = 100 * ($n - 49) + $i + $l;

        return [$gy, $gm, $gd];
    }
}

if (!function_exists('western_to_ethiopian_time')) {
    /**
     * Exact Ethiopian Time conversion
     * Starts at dawn (06:00 Western = 12:00 Dawn Ethiopian morning)
     * Reference: https://www.metaappz.com/References/Time_and_Date_in_Ethiopia
     */
    function western_to_ethiopian_time(int $hour24, int $minute): array {
        $ethHour = $hour24 - 6;
        if ($ethHour < 0) {
            $ethHour += 12;
        } elseif ($ethHour >= 12) {
            $ethHour -= 12;
        }
        $ethHour = ($ethHour % 12) ?: 12;

        if ($hour24 >= 6 && $hour24 <= 11) {
            $periodEn = 'Morning';
            $periodAm = 'ጠዋት';
        } elseif ($hour24 >= 12 && $hour24 <= 17) {
            $periodEn = 'Afternoon';
            $periodAm = 'ከሰዓት';
        } elseif ($hour24 >= 18 && $hour24 <= 23) {
            $periodEn = 'Evening';
            $periodAm = 'ምሽት';
        } else {
            $periodEn = 'Night';
            $periodAm = 'ሌሊት';
        }

        return [
            'hour' => $ethHour,
            'minute' => $minute,
            'period_en' => $periodEn,
            'period_am' => $periodAm,
            'time_str' => sprintf('%02d:%02d %s', $ethHour, $minute, $periodAm)
        ];
    }
}

/**
 * Calculate current Ethiopian Year, Month, Week (1 to 5), and Day
 */
if (!function_exists('get_ethiopian_period')) {
    function get_ethiopian_period(?string $gregorianDate = null): array {
        $timestamp = $gregorianDate ? strtotime($gregorianDate) : time();
        $gy = (int)date('Y', $timestamp);
        $gm = (int)date('n', $timestamp);
        $gd = (int)date('j', $timestamp);
        $dotw = (int)date('w', $timestamp);

        [$ey, $em, $ed] = gregorian_to_ethiopian_date($gy, $gm, $gd);

        $monthNames = get_ethiopian_month_names();
        $ethMonth = $monthNames[$em]['en'] ?? 'Meskerem';
        $ethMonthAm = $monthNames[$em]['am'] ?? 'መስከረም';

        $dayNames = get_ethiopian_day_names();
        $dayNameEn = $dayNames[$dotw]['en'] ?? '';
        $dayNameAm = $dayNames[$dotw]['am'] ?? '';

        $week = (int)min(5, max(1, ceil($ed / 7)));

        return [
            'year' => "{$ey} E.C.",
            'year_num' => $ey,
            'year_am' => "{$ey} ዓ.ም.",
            'month' => $ethMonth,
            'month_num' => $em,
            'month_am' => $ethMonthAm,
            'week' => $week,
            'day' => $ed,
            'day_name' => $dayNameEn,
            'day_am' => $dayNameAm,
            'formatted' => "{$ethMonth} {$ed}, {$ey} E.C.",
            'formatted_am' => "{$ethMonthAm} {$ed} ቀን {$ey} ዓ.ም."
        ];
    }
}

if (!function_exists('format_ethiopian_date')) {
    function format_ethiopian_date($datetime = null, bool $includeWeekday = false): string {
        if ($datetime instanceof \DateTimeInterface) {
            $ts = $datetime->getTimestamp();
        } elseif (is_numeric($datetime)) {
            $ts = (int)$datetime;
        } elseif (is_string($datetime) && !empty($datetime)) {
            $ts = strtotime($datetime);
        } else {
            $ts = time();
        }

        $period = get_ethiopian_period(date('Y-m-d', $ts));
        if ($includeWeekday) {
            return "{$period['day_name']}, {$period['formatted']}";
        }
        return $period['formatted'];
    }
}

if (!function_exists('format_ethiopian_date_am')) {
    function format_ethiopian_date_am($datetime = null, bool $includeWeekday = false): string {
        if ($datetime instanceof \DateTimeInterface) {
            $ts = $datetime->getTimestamp();
        } elseif (is_numeric($datetime)) {
            $ts = (int)$datetime;
        } elseif (is_string($datetime) && !empty($datetime)) {
            $ts = strtotime($datetime);
        } else {
            $ts = time();
        }

        $period = get_ethiopian_period(date('Y-m-d', $ts));
        if ($includeWeekday) {
            return "{$period['day_am']}፣ {$period['formatted_am']}";
        }
        return $period['formatted_am'];
    }
}

if (!function_exists('format_ethiopian_time')) {
    function format_ethiopian_time($datetime = null, bool $includeEnglish = false): string {
        if ($datetime instanceof \DateTimeInterface) {
            $h = (int)$datetime->format('G');
            $m = (int)$datetime->format('i');
        } elseif (is_numeric($datetime)) {
            $h = (int)date('G', (int)$datetime);
            $m = (int)date('i', (int)$datetime);
        } elseif (is_string($datetime) && !empty($datetime)) {
            $ts = strtotime($datetime);
            $h = (int)date('G', $ts);
            $m = (int)date('i', $ts);
        } else {
            $h = (int)date('G');
            $m = (int)date('i');
        }

        $t = western_to_ethiopian_time($h, $m);
        if ($includeEnglish) {
            return sprintf('%02d:%02d %s (%s)', $t['hour'], $t['minute'], $t['period_am'], $t['period_en']);
        }
        return $t['time_str'];
    }
}

if (!function_exists('format_ethiopian_datetime')) {
    function format_ethiopian_datetime($datetime = null, bool $includeEat = true): string {
        if ($datetime instanceof \DateTimeInterface) {
            $ts = $datetime->getTimestamp();
            $eatStr = $datetime->format('H:i');
        } elseif (is_numeric($datetime)) {
            $ts = (int)$datetime;
            $eatStr = date('H:i', $ts);
        } elseif (is_string($datetime) && !empty($datetime)) {
            $ts = strtotime($datetime);
            $eatStr = date('H:i', $ts);
        } else {
            $ts = time();
            $eatStr = date('H:i');
        }

        $dateStr = format_ethiopian_date($ts);
        $timeStr = format_ethiopian_time($ts);

        if ($includeEat) {
            return "{$dateStr} {$timeStr} ({$eatStr} EAT)";
        }
        return "{$dateStr} {$timeStr}";
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
                    $cleanVoucherUrl = htmlspecialchars($voucherUrl, ENT_QUOTES, 'UTF-8');
                    $telegramMsg .= "\n\n📄 <a href=\"{$cleanVoucherUrl}\"><b>View Official Voucher</b></a>\n🔗 {$cleanVoucherUrl}";
                }
                $telegramMsg .= "\n\n📅 <i>" . (function_exists('format_ethiopian_datetime') ? format_ethiopian_datetime() : date('Y-m-d H:i')) . "</i>";

                foreach ($chatIdsToNotify as $chatId) {
                    send_telegram_notification($chatId, $telegramMsg);
                }
            }
        } catch (\Throwable $e) {
            error_log("notify_gm_submission error: " . $e->getMessage());
        }
    }
}

if (!function_exists('notify_department_head')) {
    function notify_department_head(int $deptId, string $title, string $message, ?string $url = null): void {
        try {
            if (!class_exists('Database')) {
                require_once __DIR__ . '/../config/database.php';
            }
            $db = Database::getConnection();

            // Fetch department head
            $stmt = $db->prepare("
                SELECT u.id, u.telegram_chat_id, u.full_name
                FROM departments d
                JOIN users u ON d.head_user_id = u.id
                WHERE d.id = ?
            ");
            $stmt->execute([$deptId]);
            $head = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$head) {
                return;
            }

            // 1. In-app notification
            $ins = $db->prepare("
                INSERT INTO notifications (user_id, department_id, title, message, notification_type, created_at)
                VALUES (?, ?, ?, ?, 'DEPARTMENT_NOTICE', NOW())
            ");
            $ins->execute([
                $head['id'],
                $deptId,
                $title,
                strip_tags($message) . ($url ? "\n\nLink: " . $url : '')
            ]);

            // 2. Telegram alert
            if (!empty($head['telegram_chat_id'])) {
                $telegramText = "<b>" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</b>\n\n" . $message;
                if ($url) {
                    $cleanUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
                    $telegramText .= "\n\n📄 <a href=\"{$cleanUrl}\"><b>View Official Voucher / Portal</b></a>\n🔗 {$cleanUrl}";
                }
                $telegramText .= "\n\n📅 <i>" . (function_exists('format_ethiopian_datetime') ? format_ethiopian_datetime() : date('Y-m-d H:i')) . "</i>";
                send_telegram_notification(trim($head['telegram_chat_id']), $telegramText);
            }
        } catch (\Throwable $e) {
            error_log("notify_department_head error: " . $e->getMessage());
        }
    }
}

if (!function_exists('notify_both_gm_and_head')) {
    function notify_both_gm_and_head(int $deptId, string $title, string $message, ?string $url = null): void {
        notify_gm_submission($title, $message, $deptId, $url);
        notify_department_head($deptId, $title, $message, $url);
    }
}



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
        return $config[$key] ?? $default;
    }
}

if (class_exists('Illuminate\Container\Container')) {
    $container = Illuminate\Container\Container::getInstance();
    if (!$container->bound('url')) {
        $container->singleton('url', function() {
            return new class {
                public function to($path = '', $extra = [], $secure = null) {
                    $baseUrl = rtrim(app_config('base_url', '/Planner'), '/');
                    $cleanPath = '/' . ltrim($path, '/');
                    return $baseUrl . $cleanPath;
                }
            };
        });
        $container->alias('url', \Illuminate\Contracts\Routing\UrlGenerator::class);
    }
    if (!$container->bound('redirect')) {
        $container->singleton('redirect', function() {
            return new class {
                public function to($path, $status = 302, $headers = [], $secure = null) {
                    $baseUrl = rtrim(app_config('base_url', '/Planner'), '/');
                    $fullUrl = $baseUrl . '/' . ltrim($path, '/');
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
        $baseUrl = rtrim(app_config('base_url', '/Planner'), '/');
        $cleanPath = '/' . ltrim($path, '/');
        return $baseUrl . $cleanPath;
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


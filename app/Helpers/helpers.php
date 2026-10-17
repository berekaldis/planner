<?php

if (!function_exists('get_ethiopian_months')) {
    function get_ethiopian_months(): array
    {
        return [
            'Meskerem', 'Tikimt', 'Hidar', 'Tahsas',
            'Tir', 'Yakatit', 'Megabit', 'Miazia',
            'Ginbot', 'Sene', 'Hamle', 'Nehase'
        ];
    }
}

if (!function_exists('get_planning_years')) {
    function get_planning_years(): array
    {
        $years = [];
        for ($y = 2019; $y <= 2030; $y++) {
            $years[] = "{$y} E.C.";
        }
        return $years;
    }
}

if (!function_exists('normalize_ethiopian_month')) {
    function normalize_ethiopian_month(?string $month): string
    {
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

if (!function_exists('get_ethiopian_period')) {
    function get_ethiopian_period(?string $gregorianDate = null): array
    {
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

if (!function_exists('get_current_planning_month')) {
    function get_current_planning_month(): string
    {
        $setting = null;
        try {
            $val = \Illuminate\Support\Facades\DB::table('system_settings')
                ->where('setting_key', 'current_planning_month')
                ->value('setting_value');
            if ($val !== null) {
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
    function get_current_planning_year(): string
    {
        $setting = null;
        try {
            $val = \Illuminate\Support\Facades\DB::table('system_settings')
                ->where('setting_key', 'current_planning_year')
                ->value('setting_value');
            if ($val !== null) {
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

if (!function_exists('get_current_planning_week')) {
    function get_current_planning_week(): int
    {
        $period = get_ethiopian_period();
        return $period['week'];
    }
}

if (!function_exists('kaldis_setting')) {
    function kaldis_setting(string $key, $default = null)
    {
        try {
            $setting = \Illuminate\Support\Facades\DB::table('system_settings')
                ->where('setting_key', $key)
                ->value('setting_value');
            if ($setting !== null && trim((string)$setting) !== '') {
                $val = trim((string)$setting);
                if ($key === 'current_planning_month' && strtoupper($val) === 'AUTO') {
                    return get_current_planning_month();
                }
                if ($key === 'current_planning_year' && strtoupper($val) === 'AUTO') {
                    return get_current_planning_year();
                }
                return $val;
            }
        } catch (\Throwable $e) {}

        if ($key === 'current_planning_month') {
            return get_current_planning_month();
        }
        if ($key === 'current_planning_year') {
            return get_current_planning_year();
        }

        return $default;
    }
}

if (!function_exists('format_et_calendar')) {
    function format_et_calendar(?string $month = null, ?string $year = null): string
    {
        $m = $month ?? get_current_planning_month();
        $y = $year ?? get_current_planning_year();
        return "{$m} {$y}";
    }
}

if (!function_exists('notify_gm_submission')) {
    function notify_gm_submission(string $title, string $message, ?int $deptId = null, ?string $voucherUrl = null): void
    {
        try {
            // 1. Fetch GM users (roles gm_management, super_admin, or role_id 1, 2)
            $gmUsers = \App\Models\User::where(function ($query) {
                $query->whereHas('role', function ($q) {
                    $q->whereIn('name', ['gm_management', 'super_admin', 'gm', 'general_manager']);
                })->orWhereIn('role_id', [1, 2]);
            })->where('is_active', 1)->get();

            // 2. Insert In-app notifications
            foreach ($gmUsers as $gm) {
                \Illuminate\Support\Facades\DB::table('notifications')->insert([
                    'user_id' => $gm->id,
                    'department_id' => $deptId,
                    'title' => $title,
                    'message' => $message . ($voucherUrl ? "\n\nVoucher: " . $voucherUrl : ''),
                    'notification_type' => 'REPORT_SUBMISSION',
                    'created_at' => now(),
                ]);
            }

            // 3. Telegram notification
            $telegram = app(\App\Services\TelegramService::class);
            if ($telegram && $telegram->isConfigured()) {
                $chatIds = [];
                foreach ($gmUsers as $gm) {
                    if (!empty($gm->telegram_chat_id)) {
                        $chatIds[] = trim($gm->telegram_chat_id);
                    }
                }

                $escalationRecipients = kaldis_setting('escalation_recipients_gm', '');
                if (!empty($escalationRecipients)) {
                    foreach (explode(',', $escalationRecipients) as $rec) {
                        $rec = trim($rec);
                        if (is_numeric($rec) || str_starts_with($rec, '@')) {
                            $chatIds[] = $rec;
                        }
                    }
                }

                $chatIds = array_unique(array_filter($chatIds));

                $telegramText = "<b>" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</b>\n\n"
                    . htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

                if ($voucherUrl) {
                    $telegramText .= "\n\n📄 <a href=\"" . htmlspecialchars($voucherUrl, ENT_QUOTES, 'UTF-8') . "\"><b>View Official Voucher</b></a>";
                }
                $telegramText .= "\n\n📅 <i>" . now()->format('Y-m-d H:i') . "</i>";

                foreach ($chatIds as $cid) {
                    $telegram->sendMessage($cid, $telegramText);
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("notify_gm_submission error: " . $e->getMessage());
        }
    }
}

if (!function_exists('notify_department_head')) {
    function notify_department_head(int $deptId, string $title, string $message, ?string $url = null): void
    {
        try {
            $dept = \App\Models\Department::with('head')->find($deptId);
            if (!$dept || !$dept->head) {
                return;
            }

            $head = $dept->head;

            // 1. In-app notification
            \Illuminate\Support\Facades\DB::table('notifications')->insert([
                'user_id' => $head->id,
                'department_id' => $deptId,
                'title' => $title,
                'message' => strip_tags($message) . ($url ? "\n\nLink: " . $url : ''),
                'notification_type' => 'DEPARTMENT_NOTICE',
                'created_at' => now(),
            ]);

            // 2. Telegram notification
            $telegram = app(\App\Services\TelegramService::class);
            if ($telegram && $telegram->isConfigured() && !empty($head->telegram_chat_id)) {
                $telegramText = "<b>" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</b>\n\n"
                    . $message;

                if ($url) {
                    $telegramText .= "\n\n🔗 <a href=\"" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "\"><b>Open in Portal</b></a>";
                }
                $telegramText .= "\n\n📅 <i>" . now()->format('Y-m-d H:i') . "</i>";

                $telegram->sendMessage($head->telegram_chat_id, $telegramText);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("notify_department_head error: " . $e->getMessage());
        }
    }
}

if (!function_exists('notify_both_gm_and_head')) {
    function notify_both_gm_and_head(int $deptId, string $title, string $message, ?string $url = null): void
    {
        notify_gm_submission($title, $message, $deptId, $url);
        notify_department_head($deptId, $title, $message, $url);
    }
}



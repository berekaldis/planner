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

if (!function_exists('get_ethiopian_month_names')) {
    function get_ethiopian_month_names(): array
    {
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
    function get_ethiopian_day_names(): array
    {
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
    function gregorian_to_ethiopian_date(int $gy, int $gm, int $gd): array
    {
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
    function ethiopian_to_gregorian_date(int $ey, int $em, int $ed): array
    {
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
    function western_to_ethiopian_time(int $hour24, int $minute): array
    {
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

if (!function_exists('get_ethiopian_period')) {
    function get_ethiopian_period(?string $gregorianDate = null): array
    {
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
    function format_ethiopian_date($datetime = null, bool $includeWeekday = false): string
    {
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
    function format_ethiopian_date_am($datetime = null, bool $includeWeekday = false): string
    {
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
    function format_ethiopian_time($datetime = null, bool $includeEnglish = false): string
    {
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
    function format_ethiopian_datetime($datetime = null, bool $includeEat = true): string
    {
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
        if ($month !== null && $year !== null) {
            return "{$month} {$year}";
        }
        $et = get_ethiopian_period();
        $m = $month ?? $et['month'];
        $y = $year ?? $et['year'];
        return "{$m} {$et['day']}, {$y}";
    }
}

if (!function_exists('get_monthly_plan_deadline_info')) {
    /**
     * Compute the official Monthly Plan submission deadline.
     * Business Governance Rule: The monthly plan is due in the PRECEDING Ethiopian month.
     * Example: Hidar plan package is due on Tikimt 26 at 17:00.
     *          Tikimt plan package is due on Meskerem 26 at 17:00.
     *          Meskerem plan package is due on Nehase 26 (previous year) at 17:00.
     */
    function get_monthly_plan_deadline_info(string $plannedMonth, string $plannedYear): array
    {
        $months = [
            1 => 'Meskerem', 2 => 'Tikimt', 3 => 'Hidar', 4 => 'Tahsas',
            5 => 'Tir', 6 => 'Yakatit', 7 => 'Megabit', 8 => 'Miazia',
            9 => 'Ginbot', 10 => 'Sene', 11 => 'Hamle', 12 => 'Nehase'
        ];
        $normalized = normalize_ethiopian_month($plannedMonth);
        $mNum = array_search($normalized, $months);
        if (!$mNum) {
            $mNum = 1;
        }

        $yNum = (int)preg_replace('/[^0-9]/', '', $plannedYear) ?: 2019;

        // Preceding month for submission deadline
        if ($mNum === 1) {
            $deadlineMonthNum = 12; // Nehase
            $deadlineYearNum = $yNum - 1;
        } else {
            $deadlineMonthNum = $mNum - 1;
            $deadlineYearNum = $yNum;
        }

        $deadlineMonthName = $months[$deadlineMonthNum];
        $deadlineYearStr = "{$deadlineYearNum} E.C.";

        $rawDay = (int)kaldis_setting('monthly_plan_deadline_day', kaldis_setting('monthly_deadline_day', 26));
        // Strict Governance Rule: Monthly plan deadline is always Day 26 in the Ethiopian calendar.
        // Guard against any misconfigured/corrupted values (< 20) in system_settings.
        $deadlineDay = ($rawDay >= 20 && $rawDay <= 30) ? $rawDay : 26;
        $deadlineTime = kaldis_setting('monthly_plan_deadline_time', '17:00');

        $timeParts = explode(':', $deadlineTime);
        $hour = (int)($timeParts[0] ?? 17);
        $min = (int)($timeParts[1] ?? 0);

        [$gy, $gm, $gd] = ethiopian_to_gregorian_date($deadlineYearNum, $deadlineMonthNum, $deadlineDay);
        $deadlineCarbon = \Carbon\Carbon::create($gy, $gm, $gd, $hour, $min, 0, 'Africa/Addis_Ababa');
        $now = \Carbon\Carbon::now('Africa/Addis_Ababa');
        $isLate = $now->gt($deadlineCarbon);

        return [
            'planned_month' => $plannedMonth,
            'planned_year' => $plannedYear,
            'deadline_month' => $deadlineMonthName,
            'deadline_year' => $deadlineYearStr,
            'deadline_day' => $deadlineDay,
            'deadline_time' => $deadlineTime,
            'deadline_carbon' => $deadlineCarbon,
            'deadline_formatted' => "{$deadlineMonthName} {$deadlineDay}, {$deadlineYearStr} at {$deadlineTime}",
            'is_late' => $isLate,
            'status' => $isLate ? 'LATE' : 'ON_TIME',
        ];
    }
}

if (!function_exists('get_weekly_cycle_deadline')) {
    /**
     * Compute the official deadline for a weekly performance report and upcoming plan.
     * Business Governance Rule:
     * Week W performance and Week W+1 plan must be submitted by Week W+1 Monday at 6:30 LT (12:30 PM Western / EAT).
     *
     * Example: Week 1 performance and Week 2 plan are due on Week 2 Monday at 6:30 LT (12:30 PM).
     *          Week 2 performance and Week 3 plan are due on Week 3 Monday at 6:30 LT (12:30 PM).
     *
     * @param string $year Planning Year (e.g. '2019 E.C.')
     * @param string $month Ethiopian Month (e.g. 'Tikimt')
     * @param int $perfWeek Performance Week number (1 to 5)
     * @param mixed $submittedAt Optional datetime/timestamp to evaluate timing status against
     * @return array
     */
    function get_weekly_cycle_deadline(string $year, string $month, int $perfWeek, $submittedAt = null): array
    {
        $planWeek = min(5, $perfWeek + 1);
        $deadlineDayName = kaldis_setting('weekly_deadline_day', 'Monday');
        $deadlineTime = kaldis_setting('weekly_deadline_time', '12:30');
        $timeParts = explode(':', $deadlineTime);
        $hour = (int)($timeParts[0] ?? 12);
        $min = (int)($timeParts[1] ?? 30);

        $yNum = (int)preg_replace('/[^0-9]/', '', $year) ?: 2019;
        $months = [
            1 => 'Meskerem', 2 => 'Tikimt', 3 => 'Hidar', 4 => 'Tahsas',
            5 => 'Tir', 6 => 'Yakatit', 7 => 'Megabit', 8 => 'Miazia',
            9 => 'Ginbot', 10 => 'Sene', 11 => 'Hamle', 12 => 'Nehase', 13 => 'Pagume'
        ];
        $normalizedMonth = normalize_ethiopian_month($month);
        $mNum = array_search($normalizedMonth, $months) ?: 1;

        // In the Ethiopian calendar, Week W+1 begins on Day (W * 7) + 1
        $startDay = ($perfWeek * 7) + 1;
        $endDay = min(30, $startDay + 6);

        $deadlineCarbon = null;
        $ethDayFound = null;
        $ethMonthFound = $mNum;
        $ethYearFound = $yNum;

        // Search for Monday within target week days
        for ($d = $startDay; $d <= $endDay; $d++) {
            [$gy, $gm, $gd] = ethiopian_to_gregorian_date($yNum, $mNum, $d);
            $dateStr = sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
            if (date('N', strtotime($dateStr)) == 1) { // 1 = Monday
                $deadlineCarbon = \Carbon\Carbon::create($gy, $gm, $gd, $hour, $min, 0, 'Africa/Addis_Ababa');
                $ethDayFound = $d;
                break;
            }
        }

        // If not found in current month (e.g. crossing month boundary at week 5)
        if (!$deadlineCarbon) {
            $nextM = $mNum + 1;
            $nextY = $yNum;
            if ($nextM > 13) {
                $nextM = 1;
                $nextY++;
            }
            for ($d = 1; $d <= 7; $d++) {
                [$gy, $gm, $gd] = ethiopian_to_gregorian_date($nextY, $nextM, $d);
                $dateStr = sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
                if (date('N', strtotime($dateStr)) == 1) {
                    $deadlineCarbon = \Carbon\Carbon::create($gy, $gm, $gd, $hour, $min, 0, 'Africa/Addis_Ababa');
                    $ethDayFound = $d;
                    $ethMonthFound = $nextM;
                    $ethYearFound = $nextY;
                    break;
                }
            }
        }

        if (!$deadlineCarbon) {
            $deadlineCarbon = \Carbon\Carbon::parse("this {$deadlineDayName} {$deadlineTime}", 'Africa/Addis_Ababa');
        }

        $evalTime = $submittedAt ? \Carbon\Carbon::parse($submittedAt, 'Africa/Addis_Ababa') : \Carbon\Carbon::now('Africa/Addis_Ababa');
        $isLate = $evalTime->gt($deadlineCarbon);
        $compliance = $isLate ? 'LATE' : 'ON_TIME';

        $ethMonthName = $months[$ethMonthFound] ?? $month;
        $ethTimeStr = "6:30 LT ({$deadlineTime})";

        return [
            'perf_week' => $perfWeek,
            'plan_week' => $planWeek,
            'deadline_carbon' => $deadlineCarbon,
            'deadline_formatted' => $deadlineCarbon->format('Y-m-d H:i:s'),
            'deadline_display' => "Week {$planWeek} Monday at {$ethTimeStr}",
            'eth_month' => $ethMonthName,
            'eth_day' => $ethDayFound,
            'eth_year' => "{$ethYearFound} E.C.",
            'eth_full_str' => "{$ethMonthName} {$ethDayFound}, {$ethYearFound} E.C. Monday at {$ethTimeStr}",
            'is_late' => $isLate,
            'compliance' => $compliance,
        ];
    }
}

if (!function_exists('notify_gm_submission')) {
    function notify_gm_submission(string $title, string $message, ?int $deptId = null, ?string $voucherUrl = null, ?array $customReplyMarkup = null): array
    {
        $deliveredChatIds = [];
        try {
            // 1. Fetch GM & Super Admin users
            $gmUsers = \App\Models\User::where(function ($query) {
                $query->whereHas('role', function ($q) {
                    $q->whereIn('name', ['gm_management', 'super_admin', 'gm', 'general_manager', 'it_admin']);
                })->orWhereIn('role_id', [1, 2]);
            })->where('is_active', 1)->get();

            // 2. In-app notifications (isolated so DB errors never stop Telegram delivery)
            try {
                foreach ($gmUsers as $gm) {
                    \Illuminate\Support\Facades\DB::table('notifications')->insert([
                        'user_id' => $gm->id,
                        'department_id' => $deptId,
                        'title' => strip_tags($title),
                        'message' => strip_tags($message) . ($voucherUrl ? "\n\nVoucher: " . $voucherUrl : ''),
                        'notification_type' => 'REPORT_SUBMISSION',
                        'created_at' => now(),
                    ]);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("notify_gm_submission in-app notification warning: " . $e->getMessage());
            }

            // 3. Telegram notification dispatch
            $telegram = app(\App\Services\TelegramService::class);
            if ($telegram && $telegram->isConfigured()) {
                $chatIds = [];

                // Valid chat IDs from GM users
                foreach ($gmUsers as $gm) {
                    if (!empty($gm->telegram_chat_id)) {
                        $cid = trim((string)$gm->telegram_chat_id);
                        if ($cid !== '123456789' && (is_numeric($cid) || str_starts_with($cid, '-'))) {
                            $chatIds[] = $cid;
                        }
                    }
                }

                // Check Tseday explicitly (GM Account)
                $tseday = \App\Models\User::where('username', 'Tseday')->first();
                if ($tseday && !empty($tseday->telegram_chat_id) && $tseday->telegram_chat_id !== '123456789') {
                    $chatIds[] = trim((string)$tseday->telegram_chat_id);
                } else {
                    $chatIds[] = '6492922880'; // Guaranteed GM Chat ID
                }

                // Check admin explicitly (Super Admin)
                $admin = \App\Models\User::where('username', 'admin')->first();
                if ($admin && !empty($admin->telegram_chat_id) && $admin->telegram_chat_id !== '123456789') {
                    $chatIds[] = trim((string)$admin->telegram_chat_id);
                }

                // Also check telegram_users table for bound GM / Admin accounts
                try {
                    $linkedGmIds = \Illuminate\Support\Facades\DB::table('telegram_users')
                        ->where(function ($q) use ($gmUsers) {
                            $q->whereIn('user_id', $gmUsers->pluck('id'))
                              ->orWhere('user_id', 1)
                              ->orWhere('user_id', 2);
                        })
                        ->where('is_active', 1)
                        ->pluck('telegram_user_id')
                        ->toArray();
                    foreach ($linkedGmIds as $lcid) {
                        $lcid = trim((string)$lcid);
                        if (!empty($lcid) && $lcid !== '123456789' && (is_numeric($lcid) || str_starts_with($lcid, '-'))) {
                            $chatIds[] = $lcid;
                        }
                    }
                } catch (\Throwable $e) {}

                // Always include IT Admin / Developer tester Bere's Telegram (814523272)
                $chatIds[] = '814523272';

                // Check escalation / GM recipients setting
                $escalationRecipients = kaldis_setting('escalation_recipients_gm', '');
                if (!empty($escalationRecipients)) {
                    foreach (explode(',', $escalationRecipients) as $rec) {
                        $rec = trim($rec);
                        if ($rec !== '123456789' && (is_numeric($rec) || str_starts_with($rec, '-'))) {
                            $chatIds[] = $rec;
                        }
                    }
                }

                // Check general channel/group setting if configured
                $groupChatId = kaldis_setting('telegram_chat_id', env('TELEGRAM_CHAT_ID', ''));
                if (!empty($groupChatId) && (is_numeric($groupChatId) || str_starts_with($groupChatId, '-'))) {
                    $chatIds[] = trim((string)$groupChatId);
                }

                $chatIds = array_unique(array_filter($chatIds));

                // Format Telegram message cleanly without duplicate URLs
                $safeTitle = str_contains($title, '<') ? $title : "<b>" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</b>";
                $telegramText = "{$safeTitle}\n\n{$message}\n\n📅 <i>" . format_ethiopian_datetime() . "</i>";

                $replyMarkup = $customReplyMarkup;
                if (empty($replyMarkup) && $voucherUrl) {
                    $cleanVoucherUrl = trim($voucherUrl);
                    $replyMarkup = [
                        'inline_keyboard' => [
                            [
                                ['text' => '📄 View Official Voucher', 'url' => $cleanVoucherUrl]
                            ]
                        ]
                    ];
                }

                foreach ($chatIds as $cid) {
                    $telegram->sendMessage($cid, $telegramText, $replyMarkup);
                    $deliveredChatIds[] = (string)$cid;
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("notify_gm_submission error: " . $e->getMessage());
        }

        return $deliveredChatIds;
    }
}

if (!function_exists('notify_department_head')) {
    function notify_department_head(int $deptId, string $title, string $message, ?string $url = null, ?array $customReplyMarkup = null, array $skipChatIds = []): void
    {
        try {
            $dept = \App\Models\Department::with('head')->find($deptId);
            if (!$dept) {
                return;
            }

            $head = $dept->head;
            if (!$head && $dept->head_user_id) {
                $head = \App\Models\User::find($dept->head_user_id);
            }
            if (!$head) {
                $head = \App\Models\User::where('department_id', $deptId)
                    ->whereHas('role', fn($q) => $q->whereIn('name', ['dept_head', 'department_head']))
                    ->first();
            }

            if (!$head) {
                return;
            }

            // 1. In-app notification (isolated so DB errors never stop Telegram delivery)
            try {
                \Illuminate\Support\Facades\DB::table('notifications')->insert([
                    'user_id' => $head->id,
                    'department_id' => $deptId,
                    'title' => strip_tags($title),
                    'message' => strip_tags($message) . ($url ? "\n\nLink: " . $url : ''),
                    'notification_type' => 'REPORT_SUBMISSION',
                    'created_at' => now(),
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("notify_department_head in-app notification warning: " . $e->getMessage());
            }

            // 2. Telegram notification
            $chatId = null;
            if (!empty($head->telegram_chat_id)) {
                $chatId = trim((string)$head->telegram_chat_id);
            } else {
                try {
                    $tu = \Illuminate\Support\Facades\DB::table('telegram_users')
                        ->where('user_id', $head->id)
                        ->where('is_active', 1)
                        ->first();
                    if ($tu) {
                        $chatId = trim((string)$tu->telegram_user_id);
                    }
                } catch (\Throwable $e) {}
            }

            // Deduplication Guard: If recipient already received this notification, skip sending a 2nd time
            if (!empty($chatId) && in_array((string)$chatId, array_map('strval', $skipChatIds), true)) {
                return;
            }

            $telegram = app(\App\Services\TelegramService::class);
            if ($telegram && $telegram->isConfigured() && !empty($chatId) && $chatId !== '123456789') {
                $safeTitle = str_contains($title, '<') ? $title : "<b>" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</b>";
                $telegramText = "{$safeTitle}\n\n{$message}\n\n📅 <i>" . format_ethiopian_datetime() . "</i>";

                $replyMarkup = $customReplyMarkup;
                if (empty($replyMarkup) && $url) {
                    $cleanUrl = trim($url);
                    $replyMarkup = [
                        'inline_keyboard' => [
                            [
                                ['text' => '📄 View Official Voucher / Portal', 'url' => $cleanUrl]
                            ]
                        ]
                    ];
                }

                $telegram->sendMessage($chatId, $telegramText, $replyMarkup);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("notify_department_head error: " . $e->getMessage());
        }
    }
}

if (!function_exists('notify_both_gm_and_head')) {
    function notify_both_gm_and_head(int $deptId, string $title, string $message, ?string $url = null, ?array $replyMarkup = null): void
    {
        // Deliver to GM recipients first, then pass delivered chat IDs to prevent duplicate delivery to head
        $sentChatIds = notify_gm_submission($title, $message, $deptId, $url, $replyMarkup);
        notify_department_head($deptId, $title, $message, $url, $replyMarkup, $sentChatIds);
    }
}

if (!function_exists('get_greeting')) {
    function get_greeting(): string
    {
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

if (!function_exists('get_last_week_performance_data')) {
    /**
     * Retrieve the performance execution record of the preceding week for a given department.
     */
    function get_last_week_performance_data(int $deptId, string $year, string $month, int $weekNumber): array
    {
        $months = get_ethiopian_months();
        $normalizedMonth = normalize_ethiopian_month($month);
        $monthIndex = array_search($normalizedMonth, $months);
        if ($monthIndex === false) {
            $monthIndex = 0;
        }

        $yNum = (int)preg_replace('/[^0-9]/', '', $year) ?: 2019;

        if ($weekNumber > 1) {
            $prevWeek = $weekNumber - 1;
            $prevMonth = $normalizedMonth;
            $prevYear = $year;
        } else {
            // Preceding month for Week 1
            if ($monthIndex === 0) {
                // If Meskerem, previous is Nehase of prior year
                $prevMonthIndex = count($months) - 1;
                $prevYearNum = $yNum - 1;
            } else {
                $prevMonthIndex = $monthIndex - 1;
                $prevYearNum = $yNum;
            }
            $prevMonth = $months[$prevMonthIndex];
            $prevYear = "{$prevYearNum} E.C.";

            // Find the latest week in the previous month that had reports or tasks
            $maxReportWeek = \App\Models\WeeklyReport::where('department_id', $deptId)
                ->where('year', $prevYear)
                ->where('month', $prevMonth)
                ->max('week_number');

            $maxTaskWeek = \App\Models\WeeklyTask::where('department_id', $deptId)
                ->where('year', $prevYear)
                ->where('month', $prevMonth)
                ->max('week_number');

            $prevWeek = (int)($maxReportWeek ?: ($maxTaskWeek ?: 4));
        }

        // Query WeeklyReport first
        $report = \App\Models\WeeklyReport::where('department_id', $deptId)
            ->where('year', $prevYear)
            ->where('month', $prevMonth)
            ->where('week_number', $prevWeek)
            ->first();

        // Query Submission record for compliance / timing
        $submission = \App\Models\Submission::where('department_id', $deptId)
            ->where('year', $prevYear)
            ->where('month', $prevMonth)
            ->where('week_number', $prevWeek)
            ->first();

        // Query WeeklyTask with result
        $tasks = \App\Models\WeeklyTask::with('result')
            ->where('department_id', $deptId)
            ->where('year', $prevYear)
            ->where('month', $prevMonth)
            ->where('week_number', $prevWeek)
            ->get();

        $totalTasks = $tasks->count();
        $doneTasks = $tasks->filter(fn($t) => $t->result && $t->result->result === 'DONE')->count();
        $notDoneTasks = $tasks->filter(fn($t) => $t->result && $t->result->result === 'NOT_DONE')->count();
        $calcRate = $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100, 1) : 0;

        $hasData = ($report !== null) || ($totalTasks > 0) || ($submission !== null);

        $completionRate = $report ? (float)$report->completion_percentage : $calcRate;
        $doneCount = $report ? (int)$report->done_tasks : $doneTasks;
        $notDoneCount = $report ? (int)$report->not_done_tasks : $notDoneTasks;
        $totalCount = $report ? (int)$report->total_tasks : $totalTasks;

        $timingStatus = $submission->status ?? ($report->submission_status ?? ($hasData ? 'SUBMITTED' : 'NOT_RECORDED'));
        $remarks = $submission->remarks ?? ($report->notes ?? '');

        return [
            'has_data' => $hasData,
            'prev_year' => $prevYear,
            'prev_month' => $prevMonth,
            'prev_week' => $prevWeek,
            'total_tasks' => $totalCount,
            'done_tasks' => $doneCount,
            'not_done_tasks' => $notDoneCount,
            'completion_rate' => $completionRate,
            'timing_status' => $timingStatus,
            'remarks' => $remarks,
            'report' => $report,
            'submission' => $submission,
        ];
    }
}

if (!function_exists('build_weekly_task_scheduled_notification')) {
    /**
     * Build the consolidated notification for Weekly Task Scheduled with Last Week Performance Report.
     */
    function build_weekly_task_scheduled_notification(
        \App\Models\Department $dept,
        string $year,
        string $month,
        int $weekNumber,
        $tasks,
        array $lastWeekPerf,
        ?string $remarks = null,
        $user = null
    ): array {
        $deptCode = htmlspecialchars($dept->department_code ?? "Dept #{$dept->id}", ENT_QUOTES, 'UTF-8');
        $deptName = htmlspecialchars($dept->department_name ?? 'Department', ENT_QUOTES, 'UTF-8');
        $submitterName = $user ? htmlspecialchars($user->full_name ?: ($user->username ?: 'Department Head'), ENT_QUOTES, 'UTF-8') : 'Department Head';

        $totalTasks = $tasks->count();
        $stratCount = $tasks->where('plan_type', 'STRATEGY')->count();
        $opsCount = $tasks->where('plan_type', 'OPERATIONAL')->count();

        // 1. Notification Title
        $notifTitle = "📝 Weekly Task Scheduled: {$deptCode}";

        // 2. Scheduled Weekly Tasks Section
        $msg = "<b>{$deptName}</b> has officially submitted their <b>Weekly Task Schedule</b> for <b>Week {$weekNumber} ({$month} {$year})</b>.\n"
             . "👤 <i>Submitted by:</i> {$submitterName}\n\n"
             . "📋 <b>WEEK {$weekNumber} SCHEDULED TASKS ({$totalTasks} Total)</b>\n"
             . "• <b>Breakdown:</b> {$stratCount} Strategic | {$opsCount} Operational\n\n";

        $displayTasks = $tasks->take(8);
        $taskNum = 1;
        foreach ($displayTasks as $t) {
            $pTag = $t->priority ?? 'MEDIUM';
            $tTitle = htmlspecialchars($t->task_title, ENT_QUOTES, 'UTF-8');
            $tTarget = htmlspecialchars($t->expected_result ?: ($t->task_target ?: 'Completed'), ENT_QUOTES, 'UTF-8');
            $tResp = htmlspecialchars($t->responsible_person ?: ($t->assigned_to ?: 'Unassigned'), ENT_QUOTES, 'UTF-8');

            $msg .= "{$taskNum}. [{$pTag}] <b>{$tTitle}</b>\n"
                  . "   🎯 <i>Target:</i> {$tTarget}\n"
                  . "   👤 <i>Responsible:</i> {$tResp}\n";
            $taskNum++;
        }

        if ($totalTasks > 8) {
            $remaining = $totalTasks - 8;
            $msg .= "   <i>... and {$remaining} more task(s) (view Plan Voucher)</i>\n";
        }

        // 3. Last Week Performance Report Section
        $prevWeek = $lastWeekPerf['prev_week'] ?? max(1, $weekNumber - 1);
        $prevMonth = $lastWeekPerf['prev_month'] ?? $month;
        $prevYear = $lastWeekPerf['prev_year'] ?? $year;

        $msg .= "\n━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
              . "📊 <b>LAST WEEK PERFORMANCE REPORT (Week {$prevWeek}, {$prevMonth} {$prevYear})</b>\n";

        if (!empty($lastWeekPerf['has_data'])) {
            $timingRaw = $lastWeekPerf['timing_status'] ?? 'ON_TIME';
            $timingBadge = match ($timingRaw) {
                'ON_TIME' => '🟢 ON-TIME',
                'LATE' => '🟡 LATE',
                'MISSING' => '🔴 MISSING',
                default => '⚪ ' . str_replace('_', ' ', $timingRaw),
            };

            $compRate = $lastWeekPerf['completion_rate'];
            $done = $lastWeekPerf['done_tasks'];
            $notDone = $lastWeekPerf['not_done_tasks'];
            $tot = $lastWeekPerf['total_tasks'];

            $msg .= "• <b>Completion Rate:</b> <b>{$compRate}%</b>\n"
                  . "• <b>Binary Execution:</b> <b>{$done} DONE</b> &bull; <b>{$notDone} NOT DONE</b> ({$tot} planned)\n"
                  . "• <b>Submission Status:</b> <b>{$timingBadge}</b>\n";

            if (!empty($lastWeekPerf['remarks'])) {
                $safeRem = htmlspecialchars($lastWeekPerf['remarks'], ENT_QUOTES, 'UTF-8');
                $msg .= "• <b>Remarks:</b> <i>\"{$safeRem}\"</i>\n";
            }
        } else {
            $msg .= "• <i>Initial cycle — no prior week performance record recorded on portal.</i>\n";
        }

        // Optional Remarks for current submission
        if (!empty($remarks)) {
            $safeNotes = htmlspecialchars($remarks, ENT_QUOTES, 'UTF-8');
            $msg .= "\n💬 <b>Department Head Remarks:</b> <i>\"{$safeNotes}\"</i>\n";
        }

        $msg .= "━━━━━━━━━━━━━━━━━━━━━━━━━━";

        // Vouchers URLs
        $planVoucherUrl = route('weekly.voucher', [
            'department_id' => $dept->id,
            'year' => $year,
            'month' => $month,
            'week' => $weekNumber,
            'type' => 'plan',
        ]);

        $perfVoucherUrl = route('weekly.voucher', [
            'department_id' => $dept->id,
            'year' => $prevYear,
            'month' => $prevMonth,
            'week' => $prevWeek,
            'type' => 'performance',
        ]);

        $replyMarkup = [
            'inline_keyboard' => [
                [
                    ['text' => "📝 View Week {$weekNumber} Plan Voucher", 'url' => $planVoucherUrl],
                ],
                [
                    ['text' => "📊 View Week {$prevWeek} Performance Voucher", 'url' => $perfVoucherUrl],
                ],
            ]
        ];

        return [
            'title' => $notifTitle,
            'message' => $msg,
            'plan_voucher_url' => $planVoucherUrl,
            'perf_voucher_url' => $perfVoucherUrl,
            'reply_markup' => $replyMarkup,
        ];
    }
}




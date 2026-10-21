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

if (!function_exists('notify_gm_submission')) {
    function notify_gm_submission(string $title, string $message, ?int $deptId = null, ?string $voucherUrl = null, ?array $customReplyMarkup = null): void
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
                    'title' => strip_tags($title),
                    'message' => strip_tags($message) . ($voucherUrl ? "\n\nVoucher: " . $voucherUrl : ''),
                    'notification_type' => 'REPORT_SUBMISSION',
                    'created_at' => now(),
                ]);
            }

            // 3. Telegram notification
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

                // Also check telegram_users table for bound GM accounts
                try {
                    $linkedGmIds = \Illuminate\Support\Facades\DB::table('telegram_users')
                        ->whereIn('user_id', $gmUsers->pluck('id'))
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

                // Check escalation / GM recipients setting (ignore non-numeric emails)
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

                // Format Telegram message
                $safeTitle = str_contains($title, '<') ? $title : "<b>" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</b>";
                $telegramText = "{$safeTitle}\n\n{$message}";

                $replyMarkup = $customReplyMarkup;
                if (empty($replyMarkup) && $voucherUrl) {
                    $cleanVoucherUrl = trim($voucherUrl);
                    $telegramText .= "\n\n📄 <a href=\"{$cleanVoucherUrl}\"><b>View Official Voucher</b></a>\n🔗 {$cleanVoucherUrl}";
                    $replyMarkup = [
                        'inline_keyboard' => [
                            [
                                ['text' => '📄 View Official Voucher', 'url' => $cleanVoucherUrl]
                            ]
                        ]
                    ];
                }
                $telegramText .= "\n\n📅 <i>" . format_ethiopian_datetime() . "</i>";

                foreach ($chatIds as $cid) {
                    $telegram->sendMessage($cid, $telegramText, $replyMarkup);
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("notify_gm_submission error: " . $e->getMessage());
        }
    }
}

if (!function_exists('notify_department_head')) {
    function notify_department_head(int $deptId, string $title, string $message, ?string $url = null, ?array $customReplyMarkup = null): void
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

            // 1. In-app notification
            \Illuminate\Support\Facades\DB::table('notifications')->insert([
                'user_id' => $head->id,
                'department_id' => $deptId,
                'title' => strip_tags($title),
                'message' => strip_tags($message) . ($url ? "\n\nLink: " . $url : ''),
                'notification_type' => 'DEPARTMENT_NOTICE',
                'created_at' => now(),
            ]);

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

            $telegram = app(\App\Services\TelegramService::class);
            if ($telegram && $telegram->isConfigured() && !empty($chatId) && $chatId !== '123456789') {
                $safeTitle = str_contains($title, '<') ? $title : "<b>" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</b>";
                $telegramText = "{$safeTitle}\n\n{$message}";

                $replyMarkup = $customReplyMarkup;
                if (empty($replyMarkup) && $url) {
                    $cleanUrl = trim($url);
                    $telegramText .= "\n\n📄 <a href=\"{$cleanUrl}\"><b>View Official Voucher / Portal</b></a>\n🔗 {$cleanUrl}";
                    $replyMarkup = [
                        'inline_keyboard' => [
                            [
                                ['text' => '📄 View Official Voucher', 'url' => $cleanUrl]
                            ]
                        ]
                    ];
                }
                $telegramText .= "\n\n📅 <i>" . format_ethiopian_datetime() . "</i>";

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
        notify_gm_submission($title, $message, $deptId, $url, $replyMarkup);
        notify_department_head($deptId, $title, $message, $url, $replyMarkup);
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




<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Kaldis Coffee PLC — Automated Telegram Notification & Compliance Schedules
|--------------------------------------------------------------------------
|
| 1. MASTER SCHEDULER DISPATCHER (Runs every minute with catch-up resilience)
|    - Dynamically evaluates weekly and monthly stages against current Ethiopian date & time
|    - Ensures reminders are never missed even with traffic gaps or infrequent server cron
|
| 2. WEEKLY PERFORMANCE & PLANNING REMINDERS (Dynamic Deadline: e.g. Monday 12:30 PM / 6:30 LT)
|    - 1st Advance Reminder: 2 days before deadline (Saturday 10:00 AM)
|    - 2nd Advance Reminder: 1 day before deadline (Sunday 16:30 PM)
|    - Final Morning Reminder: Deadline morning (Monday 08:30 AM / 2:30 LT)
|    - Cutoff Escalation Alerts: Deadline time + 5 min (Monday 12:35 PM / 6:35 LT)
|    - GM Executive Compliance Summary: Monday 15:00 PM (9:00 LT)
|
| 3. MONTHLY STRATEGIC & OPERATIONAL PLANS (Dynamic Deadline: e.g. Day 26 at 17:00 / 11:00 LT)
|    - 1st Advance Reminder: Day before deadline morning (Day 25 09:30 AM)
|    - 2nd Advance Reminder: Day before deadline afternoon (Day 25 16:00 PM)
|    - 3rd Final Day Reminder: Deadline morning (Day 26 09:00 AM)
|    - 4th Urgent 3-Hour Warning: Deadline afternoon (Day 26 14:00 PM)
|    - Cutoff Compliance Check & GM Summary: Deadline time + 5 min (Day 26 17:05 PM)
|
*/

// --- 0. MASTER CATCH-UP DISPATCHER (Runs every minute) ---
Schedule::command('kaldis:dispatch-reminders')
    ->everyMinute()
    ->timezone('Africa/Addis_Ababa')
    ->description('Master Intelligent Telegram Reminder Dispatcher (Catch-Up Resilient)');

// Dynamic parameter calculation for standard cron entries
$deadlineDayName = (string)kaldis_setting('weekly_deadline_day', 'Monday');
$deadlineDayMap = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2];
$deadDayNum = $deadlineDayMap[strtolower($deadlineDayName)] ?? 1;

$stage1DayNum = ($deadDayNum - 2 + 7) % 7;
$stage2DayNum = ($deadDayNum - 1 + 7) % 7;
$stageFinalDayNum = $deadDayNum;

$deadlineTime = (string)kaldis_setting('weekly_deadline_time', '12:30');
$cutoffTime = '12:35';
$gmTime = '15:00';
if (preg_match('/^(\d{1,2}):(\d{2})$/', $deadlineTime, $m)) {
    $totalM = (int)$m[1] * 60 + (int)$m[2];
    $cM = ($totalM + 5) % 1440;
    $gM = ($totalM + 150) % 1440;
    $cutoffTime = sprintf('%02d:%02d', intdiv($cM, 60), $cM % 60);
    $gmTime = sprintf('%02d:%02d', intdiv($gM, 60), $gM % 60);
}

// --- WEEKLY SCHEDULE ENTRIES ---

// 1. 1st Weekend Advance Reminder (both Performance & Plan)
Schedule::command('kaldis:weekly-reminder --type=all --stage=saturday')
    ->weeklyOn($stage1DayNum, (string)kaldis_setting('weekly_reminder_saturday_time', '10:00'))
    ->timezone('Africa/Addis_Ababa')
    ->when(fn() => kaldis_setting('enable_weekly_reminders', 'true') === 'true')
    ->description('Weekly Performance & Planning 1st Advance Reminder to Department Heads');

// 2. 2nd Advance Reminder
Schedule::command('kaldis:weekly-reminder --type=all --stage=sunday')
    ->weeklyOn($stage2DayNum, (string)kaldis_setting('weekly_reminder_sunday_time', '16:30'))
    ->timezone('Africa/Addis_Ababa')
    ->when(fn() => kaldis_setting('enable_weekly_reminders', 'true') === 'true')
    ->description('Weekly Performance & Planning 2nd Advance Reminder to Department Heads');

// 3. Final Morning Reminder before cutoff
Schedule::command('kaldis:weekly-reminder --type=all --stage=monday')
    ->weeklyOn($stageFinalDayNum, (string)kaldis_setting('weekly_reminder_monday_time', '08:30'))
    ->timezone('Africa/Addis_Ababa')
    ->when(fn() => kaldis_setting('enable_weekly_reminders', 'true') === 'true')
    ->description('Weekly Performance & Planning Final Morning Reminder to Department Heads');

// 4. Weekly Cutoff Escalation Alerts to Delinquent Heads
Schedule::command('kaldis:deadline-check --target=heads')
    ->weeklyOn($stageFinalDayNum, $cutoffTime)
    ->timezone('Africa/Addis_Ababa')
    ->when(fn() => kaldis_setting('enable_weekly_reminders', 'true') === 'true')
    ->description('Weekly Cutoff Head Escalation Alerts');

// 5. Weekly Compliance Check & GM Executive Summary
Schedule::command('kaldis:deadline-check --target=gm')
    ->weeklyOn($stageFinalDayNum, $gmTime)
    ->timezone('Africa/Addis_Ababa')
    ->when(fn() => kaldis_setting('enable_weekly_reminders', 'true') === 'true')
    ->description('Weekly Deadline Compliance Check & GM Executive Telegram Notification');

// 5.5 Daily Task Execution & Progress Reminder to Department Heads (Tuesday to Sunday)
Schedule::command('kaldis:weekly-progress-reminder')
    ->dailyAt((string)kaldis_setting('weekly_progress_reminder_time', '15:00'))
    ->timezone('Africa/Addis_Ababa')
    ->when(function () {
        if (kaldis_setting('enable_weekly_progress_reminder', 'true') !== 'true') {
            return false;
        }
        $progressDaysRaw = kaldis_setting('weekly_progress_reminder_days', 'Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday');
        $progressDays = array_map('strtolower', array_map('trim', explode(',', (string)$progressDaysRaw)));
        $dayName = strtolower(now('Africa/Addis_Ababa')->format('l'));
        return in_array($dayName, $progressDays, true);
    })
    ->description('Daily Task Execution & Progress Reminder to Department Heads (Tuesday to Sunday)');


// --- MONTHLY SCHEDULE ENTRIES ---

$rawMonthlyDay = (int)kaldis_setting('monthly_plan_deadline_day', 26);
$monthlyDeadlineDay = ($rawMonthlyDay >= 20 && $rawMonthlyDay <= 30) ? $rawMonthlyDay : 26;
$monthlyDeadlineTime = (string)kaldis_setting('monthly_plan_deadline_time', '17:00');
$monthlyCutoffTime = '17:05';
if (preg_match('/^(\d{1,2}):(\d{2})$/', $monthlyDeadlineTime, $m)) {
    $totalM = (int)$m[1] * 60 + (int)$m[2];
    $cM = ($totalM + 5) % 1440;
    $monthlyCutoffTime = sprintf('%02d:%02d', intdiv($cM, 60), $cM % 60);
}

// 6. Monthly Plan Reminder: Day before Deadline Morning (09:30 AM)
Schedule::command('kaldis:monthly-reminder --type=reminder --stage=day25_morning')
    ->dailyAt((string)kaldis_setting('monthly_reminder_day25_morning_time', '09:30'))
    ->timezone('Africa/Addis_Ababa')
    ->when(function () use ($monthlyDeadlineDay) {
        if (kaldis_setting('enable_monthly_reminders', 'true') !== 'true') return false;
        $et = function_exists('get_ethiopian_period') ? get_ethiopian_period() : ['day' => (int)date('j')];
        $day = $et['day'] ?? (int)date('j');
        return $day === ($monthlyDeadlineDay - 1);
    })
    ->description('Monthly Plan 1st Advance Reminder to Department Heads (Day before Deadline Morning)');

// 7. Monthly Plan Reminder: Day before Deadline Afternoon (16:00 PM)
Schedule::command('kaldis:monthly-reminder --type=reminder --stage=day25_afternoon')
    ->dailyAt((string)kaldis_setting('monthly_reminder_day25_afternoon_time', '16:00'))
    ->timezone('Africa/Addis_Ababa')
    ->when(function () use ($monthlyDeadlineDay) {
        if (kaldis_setting('enable_monthly_reminders', 'true') !== 'true') return false;
        $et = function_exists('get_ethiopian_period') ? get_ethiopian_period() : ['day' => (int)date('j')];
        $day = $et['day'] ?? (int)date('j');
        return $day === ($monthlyDeadlineDay - 1);
    })
    ->description('Monthly Plan 2nd Reminder to Department Heads (Day before Deadline Afternoon)');

// 8. Monthly Plan Reminder: Deadline Day Morning (09:00 AM)
Schedule::command('kaldis:monthly-reminder --type=reminder --stage=day26_morning')
    ->dailyAt((string)kaldis_setting('monthly_reminder_day26_morning_time', '09:00'))
    ->timezone('Africa/Addis_Ababa')
    ->when(function () use ($monthlyDeadlineDay) {
        if (kaldis_setting('enable_monthly_reminders', 'true') !== 'true') return false;
        $et = function_exists('get_ethiopian_period') ? get_ethiopian_period() : ['day' => (int)date('j')];
        $day = $et['day'] ?? (int)date('j');
        return $day === $monthlyDeadlineDay;
    })
    ->description('Monthly Plan Final Day Morning Reminder to Department Heads (Deadline Day Morning)');

// 9. Monthly Plan Reminder: Deadline Day Afternoon (14:00 PM)
Schedule::command('kaldis:monthly-reminder --type=reminder --stage=day26_afternoon')
    ->dailyAt((string)kaldis_setting('monthly_reminder_day26_afternoon_time', '14:00'))
    ->timezone('Africa/Addis_Ababa')
    ->when(function () use ($monthlyDeadlineDay) {
        if (kaldis_setting('enable_monthly_reminders', 'true') !== 'true') return false;
        $et = function_exists('get_ethiopian_period') ? get_ethiopian_period() : ['day' => (int)date('j')];
        $day = $et['day'] ?? (int)date('j');
        return $day === $monthlyDeadlineDay;
    })
    ->description('Monthly Plan Urgent 3-Hour Warning to Department Heads (Deadline Day Afternoon)');

// 10. Monthly Strategic & Operational Plans: Cutoff & GM Executive Summary
Schedule::command('kaldis:monthly-reminder --type=cutoff')
    ->dailyAt($monthlyCutoffTime)
    ->timezone('Africa/Addis_Ababa')
    ->when(function () use ($monthlyDeadlineDay) {
        $et = function_exists('get_ethiopian_period') ? get_ethiopian_period() : ['day' => (int)date('j')];
        $day = $et['day'] ?? (int)date('j');
        return $day === $monthlyDeadlineDay;
    })
    ->description('Monthly Plan Cutoff Compliance Check & GM Executive Telegram Summary');

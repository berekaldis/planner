<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Kaldis Coffee PLC — Automated Telegram Notification & Compliance Schedules
|--------------------------------------------------------------------------
|
| 1. WEEKLY PERFORMANCE & PLANNING REMINDERS (Deadline: Monday 12:30 PM / 6:30 LT)
|    - Saturday 10:00 AM: 1st Weekend Advance Reminder (Performance Report & Task Schedule)
|    - Sunday 16:30 PM: 2nd Advance Reminder before Monday
|    - Monday 08:30 AM (2:30 LT): 3rd Final Morning Reminder (4 hours before 12:30 PM cutoff)
|    - Monday 12:35 PM (6:35 LT): Cutoff Escalation Alerts to Delinquent Heads
|    - Monday 15:00 PM (9:00 LT): Weekly GM Executive Compliance Summary
|
| 2. MONTHLY STRATEGIC & OPERATIONAL PLANS (Deadline: Day 26 at 17:00 / 11:00 LT)
|    - Day 25 Morning 09:30 AM: 1st Advance Reminder to Department Heads
|    - Day 25 Afternoon 16:00 PM: 2nd Reminder ("Deadline is Tomorrow")
|    - Day 26 Morning 09:00 AM: 3rd Final Day Morning Reminder ("Deadline is Today 17:00")
|    - Day 26 Afternoon 14:00 PM: 4th Urgent Warning (3 hours before 17:00 cutoff)
|    - Day 26 at 17:05 PM: Cutoff Compliance Check & GM Executive Telegram Summary
|
*/

// --- WEEKLY SCHEDULE ---

// 1. Saturday 10:00 AM: 1st Weekend Reminder (both Performance & Plan)
Schedule::command('kaldis:weekly-reminder --type=all --stage=saturday')
    ->weeklyOn(6, (string)kaldis_setting('weekly_reminder_saturday_time', '10:00'))
    ->timezone('Africa/Addis_Ababa')
    ->when(fn() => kaldis_setting('enable_weekly_reminders', 'true') === 'true')
    ->description('Weekly Performance & Planning 1st Reminder to Department Heads (Saturday)');

// 2. Sunday 16:30 PM: 2nd Advance Reminder
Schedule::command('kaldis:weekly-reminder --type=all --stage=sunday')
    ->weeklyOn(0, (string)kaldis_setting('weekly_reminder_sunday_time', '16:30'))
    ->timezone('Africa/Addis_Ababa')
    ->when(fn() => kaldis_setting('enable_weekly_reminders', 'true') === 'true')
    ->description('Weekly Performance & Planning 2nd Reminder to Department Heads (Sunday)');

// 3. Monday 08:30 AM (2:30 LT): 3rd Final Morning Reminder before 12:30 PM (6:30 LT) cutoff
Schedule::command('kaldis:weekly-reminder --type=all --stage=monday')
    ->weeklyOn(1, (string)kaldis_setting('weekly_reminder_monday_time', '08:30'))
    ->timezone('Africa/Addis_Ababa')
    ->when(fn() => kaldis_setting('enable_weekly_reminders', 'true') === 'true')
    ->description('Weekly Performance & Planning Final Morning Reminder to Department Heads (Monday)');

// 4. Monday 12:35 PM (6:35 LT): Weekly Cutoff Escalation Alerts to Delinquent Heads
Schedule::command('kaldis:deadline-check --target=heads')
    ->weeklyOn(1, '12:35')
    ->timezone('Africa/Addis_Ababa')
    ->when(fn() => kaldis_setting('enable_weekly_reminders', 'true') === 'true')
    ->description('Weekly Cutoff Head Escalation Alerts (Monday 12:35 PM / 6:35 LT)');

// 5. Monday 15:00 PM (9:00 LT): Weekly Compliance Check & GM Executive Summary
Schedule::command('kaldis:deadline-check --target=gm')
    ->weeklyOn(1, '15:00')
    ->timezone('Africa/Addis_Ababa')
    ->description('Weekly Deadline Compliance Check & GM Executive Telegram Notification (Monday 15:00 / 9:00 LT)');


// --- MONTHLY SCHEDULE ---

// 6. Monthly Plan Reminder: Day 25 Morning (09:30 AM) - 1st Reminder
Schedule::command('kaldis:monthly-reminder --type=reminder --stage=day25_morning')
    ->dailyAt((string)kaldis_setting('monthly_reminder_day25_morning_time', '09:30'))
    ->timezone('Africa/Addis_Ababa')
    ->when(function () {
        if (kaldis_setting('enable_monthly_reminders', 'true') !== 'true') return false;
        $et = function_exists('get_ethiopian_period') ? get_ethiopian_period() : ['day' => (int)date('j')];
        $day = $et['day'] ?? (int)date('j');
        $rawDay = (int)kaldis_setting('monthly_plan_deadline_day', 26);
        $deadlineDay = ($rawDay >= 20 && $rawDay <= 30) ? $rawDay : 26;
        $startDay = (int)kaldis_setting('monthly_reminder_start_day', 25);
        return $day >= $startDay && $day === ($deadlineDay - 1);
    })
    ->description('Monthly Plan 1st Advance Reminder to Department Heads (Day 25 Morning)');

// 7. Monthly Plan Reminder: Day 25 Afternoon (16:00 PM) - 2nd Reminder
Schedule::command('kaldis:monthly-reminder --type=reminder --stage=day25_afternoon')
    ->dailyAt((string)kaldis_setting('monthly_reminder_day25_afternoon_time', '16:00'))
    ->timezone('Africa/Addis_Ababa')
    ->when(function () {
        if (kaldis_setting('enable_monthly_reminders', 'true') !== 'true') return false;
        $et = function_exists('get_ethiopian_period') ? get_ethiopian_period() : ['day' => (int)date('j')];
        $day = $et['day'] ?? (int)date('j');
        $rawDay = (int)kaldis_setting('monthly_plan_deadline_day', 26);
        $deadlineDay = ($rawDay >= 20 && $rawDay <= 30) ? $rawDay : 26;
        return $day === ($deadlineDay - 1);
    })
    ->description('Monthly Plan 2nd Reminder to Department Heads (Day 25 Afternoon)');

// 8. Monthly Plan Reminder: Day 26 Morning (09:00 AM) - 3rd Final Day Reminder
Schedule::command('kaldis:monthly-reminder --type=reminder --stage=day26_morning')
    ->dailyAt((string)kaldis_setting('monthly_reminder_day26_morning_time', '09:00'))
    ->timezone('Africa/Addis_Ababa')
    ->when(function () {
        if (kaldis_setting('enable_monthly_reminders', 'true') !== 'true') return false;
        $et = function_exists('get_ethiopian_period') ? get_ethiopian_period() : ['day' => (int)date('j')];
        $day = $et['day'] ?? (int)date('j');
        $rawDay = (int)kaldis_setting('monthly_plan_deadline_day', 26);
        $deadlineDay = ($rawDay >= 20 && $rawDay <= 30) ? $rawDay : 26;
        return $day === $deadlineDay;
    })
    ->description('Monthly Plan Final Day Morning Reminder to Department Heads (Day 26 Morning)');

// 9. Monthly Plan Reminder: Day 26 Afternoon (14:00 PM) - 4th Urgent 3-Hour Warning
Schedule::command('kaldis:monthly-reminder --type=reminder --stage=day26_afternoon')
    ->dailyAt((string)kaldis_setting('monthly_reminder_day26_afternoon_time', '14:00'))
    ->timezone('Africa/Addis_Ababa')
    ->when(function () {
        if (kaldis_setting('enable_monthly_reminders', 'true') !== 'true') return false;
        $et = function_exists('get_ethiopian_period') ? get_ethiopian_period() : ['day' => (int)date('j')];
        $day = $et['day'] ?? (int)date('j');
        $rawDay = (int)kaldis_setting('monthly_plan_deadline_day', 26);
        $deadlineDay = ($rawDay >= 20 && $rawDay <= 30) ? $rawDay : 26;
        return $day === $deadlineDay;
    })
    ->description('Monthly Plan Urgent 3-Hour Warning to Department Heads (Day 26 Afternoon)');

// 10. Monthly Strategic & Operational Plans: Cutoff on Day 26 at 17:05 & GM Executive Summary
Schedule::command('kaldis:monthly-reminder --type=cutoff')
    ->dailyAt('17:05')
    ->timezone('Africa/Addis_Ababa')
    ->when(function () {
        $et = function_exists('get_ethiopian_period') ? get_ethiopian_period() : ['day' => (int)date('j')];
        $day = $et['day'] ?? (int)date('j');
        $rawDay = (int)kaldis_setting('monthly_plan_deadline_day', 26);
        $deadlineDay = ($rawDay >= 20 && $rawDay <= 30) ? $rawDay : 26;
        return $day === $deadlineDay;
    })
    ->description('Monthly Plan Cutoff Compliance Check & GM Executive Telegram Summary (Day 26 17:05)');

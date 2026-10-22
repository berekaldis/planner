<?php

use Illuminate\Support\Facades\Schedule;

// 1. Weekly Performance Reports: Advance reminder on Sunday 18:00 & Final reminder on Monday 08:30 AM before 12:30 PM (6:30 LT) cutoff
Schedule::command('kaldis:weekly-reminder --type=performance')
    ->weeklyOn(0, '18:00')
    ->timezone('Africa/Addis_Ababa')
    ->description('Weekly Performance Report Advance Reminder to Department Heads (Sunday 18:00)');

Schedule::command('kaldis:weekly-reminder --type=performance')
    ->weeklyOn(1, '08:30')
    ->timezone('Africa/Addis_Ababa')
    ->description('Weekly Performance Report Final Reminder to Department Heads (Monday 08:30 / 2:30 LT)');

// 2. Weekly Deadline Cutoff: Monday 12:35 PM (6:35 LT) escalation alert to delinquent department heads
Schedule::command('kaldis:deadline-check --target=heads')
    ->weeklyOn(1, '12:35')
    ->timezone('Africa/Addis_Ababa')
    ->description('Weekly Cutoff Head Escalation Alerts (Monday 12:35 PM / 6:35 LT)');

// 3. Weekly Compliance Notification: Every Monday at 15:00 (9:00 LT) GM Executive Report
Schedule::command('kaldis:deadline-check --target=gm')
    ->weeklyOn(1, '15:00')
    ->timezone('Africa/Addis_Ababa')
    ->description('Weekly Deadline Compliance Check & GM Executive Telegram Notification (Every Monday 9:00 LT / 15:00)');

// 3. Weekly Task Planning: Reminders to schedule tasks for upcoming week (Friday 15:30 & Saturday 10:00)
Schedule::command('kaldis:weekly-reminder --type=plan')
    ->weeklyOn(5, '15:30')
    ->timezone('Africa/Addis_Ababa')
    ->description('Weekly Task Planning Reminder to Department Heads (Friday 15:30)');

Schedule::command('kaldis:weekly-reminder --type=plan')
    ->weeklyOn(6, '10:00')
    ->timezone('Africa/Addis_Ababa')
    ->description('Weekly Task Planning Weekend Reminder to Department Heads (Saturday 10:00)');

// 4. Monthly Strategic & Operational Plans: Daily morning reminder on Days 20-26 of each Ethiopian month before Day 26 17:00 cutoff
Schedule::command('kaldis:monthly-reminder --type=reminder')
    ->dailyAt('09:00')
    ->timezone('Africa/Addis_Ababa')
    ->when(function () {
        $et = function_exists('get_ethiopian_period') ? get_ethiopian_period() : ['day' => (int)date('j')];
        $day = $et['day'] ?? (int)date('j');
        $rawDay = (int)kaldis_setting('monthly_plan_deadline_day', 26);
        $deadlineDay = ($rawDay >= 20 && $rawDay <= 30) ? $rawDay : 26;
        return $day >= ($deadlineDay - 6) && $day <= $deadlineDay;
    })
    ->description('Monthly Plan Submission Reminder to Department Heads (Days 20 to 26 before 17:00)');

// 5. Monthly Strategic & Operational Plans: Cutoff on Day 26 at 17:05 & GM Executive Summary
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

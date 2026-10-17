<?php

use Illuminate\Support\Facades\Schedule;

// 1. Weekly Performance Reports: Monday 09:00 AM reminder before deadline
Schedule::command('kaldis:weekly-reminder')
    ->weeklyOn(1, '09:00')
    ->timezone('Africa/Addis_Ababa')
    ->description('Weekly Performance Report Reminder to Department Heads (Monday 09:00)');

// 2. Weekly Performance Reports: Monday 12:05 PM deadline evaluation cutoff & GM Executive Summary
Schedule::command('kaldis:deadline-check')
    ->weeklyOn(1, '12:05')
    ->timezone('Africa/Addis_Ababa')
    ->description('Weekly Cutoff Compliance Check & GM Executive Telegram Summary (Monday 12:05)');

// 3. Monthly Strategic & Operational Plans: Daily morning reminder on Days 20-25 of each Ethiopian month before Day 26
Schedule::command('kaldis:monthly-reminder --type=reminder')
    ->dailyAt('09:00')
    ->timezone('Africa/Addis_Ababa')
    ->when(function () {
        $et = function_exists('get_ethiopian_period') ? get_ethiopian_period() : ['day' => (int)date('j')];
        $day = $et['day'] ?? (int)date('j');
        $deadlineDay = (int)kaldis_setting('monthly_plan_deadline_day', 26);
        return $day >= ($deadlineDay - 6) && $day < $deadlineDay;
    })
    ->description('Monthly Plan Submission Reminder to Department Heads (Days before Day 26)');

// 4. Monthly Strategic & Operational Plans: Cutoff on Day 26 at 17:05 & GM Executive Summary
Schedule::command('kaldis:monthly-reminder --type=cutoff')
    ->dailyAt('17:05')
    ->timezone('Africa/Addis_Ababa')
    ->when(function () {
        $et = function_exists('get_ethiopian_period') ? get_ethiopian_period() : ['day' => (int)date('j')];
        $day = $et['day'] ?? (int)date('j');
        $deadlineDay = (int)kaldis_setting('monthly_plan_deadline_day', 26);
        return $day === $deadlineDay;
    })
    ->description('Monthly Plan Cutoff Compliance Check & GM Executive Telegram Summary (Day 26 17:05)');

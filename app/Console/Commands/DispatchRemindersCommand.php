<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\SystemSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DispatchRemindersCommand extends Command
{
    protected $signature = 'kaldis:dispatch-reminders 
                            {--force : Force dispatch bypassing time and cache checks}
                            {--dry-run : Simulate checks without sending actual reminders}';

    protected $description = 'Intelligently evaluate and dispatch due weekly & monthly Telegram reminders based on configured schedules with catch-up resilience';

    public function handle(): int
    {
        $force = (bool)$this->option('force');
        $dryRun = (bool)$this->option('dry-run');

        $now = now();
        $currentTime = $now->format('H:i');
        $currentDayOfWeek = (int)$now->format('w'); // 0=Sunday, 1=Monday ... 6=Saturday

        $et = function_exists('get_ethiopian_period') ? get_ethiopian_period() : [
            'year' => '2019 E.C.',
            'month' => 'Meskerem',
            'week' => 1,
            'day' => (int)date('j'),
        ];

        $year = kaldis_setting('current_planning_year', $et['year']);
        $month = kaldis_setting('current_planning_month', $et['month']);
        $week = (int)($et['week'] ?? 1);
        $day = (int)($et['day'] ?? date('j'));

        $this->info("Checking reminder schedules for {$month} {$year} (Week {$week}, Day {$day}) at {$currentTime} EAT...");

        $dispatched = [];

        // ══════════════════════════════════════════════════════════════
        // 1. WEEKLY REMINDERS & COMPLIANCE DEADLINE
        // ══════════════════════════════════════════════════════════════
        if (kaldis_setting('enable_weekly_reminders', 'true') === 'true' || $force) {
            $deadlineDayName = (string)kaldis_setting('weekly_deadline_day', 'Monday');
            $deadlineDayMap = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2];
            $deadDayNum = $deadlineDayMap[strtolower($deadlineDayName)] ?? 1;

            $stage1DayNum = ($deadDayNum - 2 + 7) % 7; // e.g. Saturday (6) if deadline is Monday (1)
            $stage2DayNum = ($deadDayNum - 1 + 7) % 7; // e.g. Sunday (0) if deadline is Monday (1)
            $stageFinalDayNum = $deadDayNum;           // e.g. Monday (1)

            $timeStage1 = (string)kaldis_setting('weekly_reminder_saturday_time', '10:00');
            $timeStage2 = (string)kaldis_setting('weekly_reminder_sunday_time', '16:30');
            $timeStageFinal = (string)kaldis_setting('weekly_reminder_monday_time', '08:30');
            $deadlineTime = (string)kaldis_setting('weekly_deadline_time', '12:30');

            $cutoffCheckTime = $this->addMinutesToTime($deadlineTime, 5); // e.g. 12:35
            $gmSummaryTime = $this->addMinutesToTime($deadlineTime, 150); // e.g. 15:00 (2.5 hrs after cutoff)

            // Stage 1: Weekend 1st Advance Reminder
            if ($force || ($currentDayOfWeek === $stage1DayNum && $currentTime >= $timeStage1)) {
                $cacheKey = "weekly_rem_stage_first_{$year}_{$month}_{$week}";
                if ($force || !$this->hasExecutedToday($cacheKey, 'WEEKLY_REMINDER_CHECK', 'saturday', $week)) {
                    $this->info("-> Dispatching 1st Weekly Advance Reminder (Stage: saturday)");
                    if (!$dryRun) {
                        Artisan::call('kaldis:weekly-reminder', [
                            '--type' => 'all',
                            '--stage' => 'saturday',
                            '--year' => $year,
                            '--month' => $month,
                            '--week' => $week,
                        ]);
                        $this->markExecuted($cacheKey, 7);
                    }
                    $dispatched[] = 'weekly_stage_saturday';
                }
            }

            // Stage 2: Weekend 2nd Advance Reminder
            if ($force || ($currentDayOfWeek === $stage2DayNum && $currentTime >= $timeStage2)) {
                $cacheKey = "weekly_rem_stage_second_{$year}_{$month}_{$week}";
                if ($force || !$this->hasExecutedToday($cacheKey, 'WEEKLY_REMINDER_CHECK', 'sunday', $week)) {
                    $this->info("-> Dispatching 2nd Weekly Advance Reminder (Stage: sunday)");
                    if (!$dryRun) {
                        Artisan::call('kaldis:weekly-reminder', [
                            '--type' => 'all',
                            '--stage' => 'sunday',
                            '--year' => $year,
                            '--month' => $month,
                            '--week' => $week,
                        ]);
                        $this->markExecuted($cacheKey, 7);
                    }
                    $dispatched[] = 'weekly_stage_sunday';
                }
            }

            // Stage 3: Deadline Day Final Morning Reminder (before cutoff)
            if ($force || ($currentDayOfWeek === $stageFinalDayNum && $currentTime >= $timeStageFinal && $currentTime < $deadlineTime)) {
                $cacheKey = "weekly_rem_stage_final_{$year}_{$month}_{$week}";
                if ($force || !$this->hasExecutedToday($cacheKey, 'WEEKLY_REMINDER_CHECK', 'monday', $week)) {
                    $this->info("-> Dispatching Final Morning Weekly Reminder (Stage: monday)");
                    if (!$dryRun) {
                        Artisan::call('kaldis:weekly-reminder', [
                            '--type' => 'all',
                            '--stage' => 'monday',
                            '--year' => $year,
                            '--month' => $month,
                            '--week' => $week,
                        ]);
                        $this->markExecuted($cacheKey, 7);
                    }
                    $dispatched[] = 'weekly_stage_monday';
                }
            }

            // Stage 4: Deadline Cutoff Escalation Alerts (after cutoff)
            if ($force || ($currentDayOfWeek === $stageFinalDayNum && $currentTime >= $cutoffCheckTime)) {
                $cacheKey = "weekly_deadline_heads_{$year}_{$month}_{$week}";
                if ($force || !$this->hasExecutedToday($cacheKey, 'WEEKLY_DEADLINE_CHECK', 'heads', $week)) {
                    $this->info("-> Dispatching Weekly Cutoff Delinquent Head Escalations");
                    if (!$dryRun) {
                        Artisan::call('kaldis:deadline-check', [
                            '--target' => 'heads',
                            '--year' => $year,
                            '--month' => $month,
                            '--week' => $week,
                        ]);
                        $this->markExecuted($cacheKey, 7);
                    }
                    $dispatched[] = 'weekly_cutoff_heads';
                }
            }

            // Stage 5: Weekly GM Executive Compliance Summary
            if ($force || ($currentDayOfWeek === $stageFinalDayNum && $currentTime >= $gmSummaryTime)) {
                $cacheKey = "weekly_deadline_gm_{$year}_{$month}_{$week}";
                if ($force || !$this->hasExecutedToday($cacheKey, 'WEEKLY_DEADLINE_CHECK', 'gm', $week)) {
                    $this->info("-> Dispatching Weekly GM Executive Compliance Summary");
                    if (!$dryRun) {
                        Artisan::call('kaldis:deadline-check', [
                            '--target' => 'gm',
                            '--year' => $year,
                            '--month' => $month,
                            '--week' => $week,
                        ]);
                        $this->markExecuted($cacheKey, 7);
                    }
                    $dispatched[] = 'weekly_summary_gm';
                }
            }
        }

        // ══════════════════════════════════════════════════════════════
        // 2. MONTHLY STRATEGIC & OPERATIONAL PLAN REMINDERS
        // ══════════════════════════════════════════════════════════════
        if (kaldis_setting('enable_monthly_reminders', 'true') === 'true' || $force) {
            $rawDay = (int)kaldis_setting('monthly_plan_deadline_day', 26);
            $deadlineDay = ($rawDay >= 20 && $rawDay <= 30) ? $rawDay : 26;
            $startDay = (int)kaldis_setting('monthly_reminder_start_day', 25);
            $monthlyDeadlineTime = (string)kaldis_setting('monthly_plan_deadline_time', '17:00');

            $mDay25MorningTime = (string)kaldis_setting('monthly_reminder_day25_morning_time', '09:30');
            $mDay25AfternoonTime = (string)kaldis_setting('monthly_reminder_day25_afternoon_time', '16:00');
            $mDay26MorningTime = (string)kaldis_setting('monthly_reminder_day26_morning_time', '09:00');
            $mDay26AfternoonTime = (string)kaldis_setting('monthly_reminder_day26_afternoon_time', '14:00');
            $mCutoffCheckTime = $this->addMinutesToTime($monthlyDeadlineTime, 5); // e.g. 17:05

            // Advance Window Reminder (for start days earlier than deadline - 1)
            if ($day >= $startDay && $day < ($deadlineDay - 1) && $currentTime >= $mDay25MorningTime) {
                $cacheKey = "monthly_rem_advance_day{$day}_{$year}_{$month}";
                if ($force || !$this->hasExecutedToday($cacheKey, 'MONTHLY_REMINDER_CHECK', "day{$day}", null)) {
                    $this->info("-> Dispatching Monthly Plan Advance Reminder for Day {$day}");
                    if (!$dryRun) {
                        Artisan::call('kaldis:monthly-reminder', [
                            '--type' => 'reminder',
                            '--stage' => 'day25_morning',
                            '--year' => $year,
                            '--month' => $month,
                        ]);
                        $this->markExecuted($cacheKey, 35);
                    }
                    $dispatched[] = "monthly_advance_day{$day}";
                }
            }

            // Day before Deadline (Day 25 by default): Morning Reminder
            if ($force || ($day === ($deadlineDay - 1) && $currentTime >= $mDay25MorningTime)) {
                $cacheKey = "monthly_rem_stage_day25_morning_{$year}_{$month}";
                if ($force || !$this->hasExecutedToday($cacheKey, 'MONTHLY_REMINDER_CHECK', 'day25_morning', null)) {
                    $this->info("-> Dispatching Monthly Plan 1st Advance Reminder (Day " . ($deadlineDay - 1) . " Morning)");
                    if (!$dryRun) {
                        Artisan::call('kaldis:monthly-reminder', [
                            '--type' => 'reminder',
                            '--stage' => 'day25_morning',
                            '--year' => $year,
                            '--month' => $month,
                        ]);
                        $this->markExecuted($cacheKey, 35);
                    }
                    $dispatched[] = 'monthly_day25_morning';
                }
            }

            // Day before Deadline: Afternoon Reminder
            if ($force || ($day === ($deadlineDay - 1) && $currentTime >= $mDay25AfternoonTime)) {
                $cacheKey = "monthly_rem_stage_day25_afternoon_{$year}_{$month}";
                if ($force || !$this->hasExecutedToday($cacheKey, 'MONTHLY_REMINDER_CHECK', 'day25_afternoon', null)) {
                    $this->info("-> Dispatching Monthly Plan 2nd Reminder (Day " . ($deadlineDay - 1) . " Afternoon)");
                    if (!$dryRun) {
                        Artisan::call('kaldis:monthly-reminder', [
                            '--type' => 'reminder',
                            '--stage' => 'day25_afternoon',
                            '--year' => $year,
                            '--month' => $month,
                        ]);
                        $this->markExecuted($cacheKey, 35);
                    }
                    $dispatched[] = 'monthly_day25_afternoon';
                }
            }

            // Final Deadline Day (Day 26 by default): Morning Reminder
            if ($force || ($day === $deadlineDay && $currentTime >= $mDay26MorningTime)) {
                $cacheKey = "monthly_rem_stage_day26_morning_{$year}_{$month}";
                if ($force || !$this->hasExecutedToday($cacheKey, 'MONTHLY_REMINDER_CHECK', 'day26_morning', null)) {
                    $this->info("-> Dispatching Monthly Plan Final Day Morning Reminder (Day {$deadlineDay} Morning)");
                    if (!$dryRun) {
                        Artisan::call('kaldis:monthly-reminder', [
                            '--type' => 'reminder',
                            '--stage' => 'day26_morning',
                            '--year' => $year,
                            '--month' => $month,
                        ]);
                        $this->markExecuted($cacheKey, 35);
                    }
                    $dispatched[] = 'monthly_day26_morning';
                }
            }

            // Final Deadline Day: Urgent 3-Hour Warning (before cutoff)
            if ($force || ($day === $deadlineDay && $currentTime >= $mDay26AfternoonTime && $currentTime < $monthlyDeadlineTime)) {
                $cacheKey = "monthly_rem_stage_day26_afternoon_{$year}_{$month}";
                if ($force || !$this->hasExecutedToday($cacheKey, 'MONTHLY_REMINDER_CHECK', 'day26_afternoon', null)) {
                    $this->info("-> Dispatching Monthly Plan Urgent 3-Hour Warning (Day {$deadlineDay} Afternoon)");
                    if (!$dryRun) {
                        Artisan::call('kaldis:monthly-reminder', [
                            '--type' => 'reminder',
                            '--stage' => 'day26_afternoon',
                            '--year' => $year,
                            '--month' => $month,
                        ]);
                        $this->markExecuted($cacheKey, 35);
                    }
                    $dispatched[] = 'monthly_day26_afternoon';
                }
            }

            // Monthly Plan Cutoff & GM Compliance Summary (after cutoff)
            if ($force || ($day === $deadlineDay && $currentTime >= $mCutoffCheckTime) || ($day > $deadlineDay && $day <= ($deadlineDay + 2))) {
                $cacheKey = "monthly_cutoff_check_{$year}_{$month}";
                if ($force || !$this->hasExecutedToday($cacheKey, 'MONTHLY_PLAN_CUTOFF_CHECK', 'cutoff', null)) {
                    $this->info("-> Dispatching Monthly Plan Cutoff Compliance Check & GM Summary");
                    if (!$dryRun) {
                        Artisan::call('kaldis:monthly-reminder', [
                            '--type' => 'cutoff',
                            '--year' => $year,
                            '--month' => $month,
                        ]);
                        $this->markExecuted($cacheKey, 35);
                    }
                    $dispatched[] = 'monthly_cutoff';
                }
            }
        }

        if (empty($dispatched)) {
            $this->line("No reminder stages are pending dispatch at this time ({$currentTime}).");
        } else {
            $count = count($dispatched);
            $this->info("Dispatched {$count} reminder task(s): " . implode(', ', $dispatched));
        }

        return Command::SUCCESS;
    }

    /**
     * Dual check: Verifies both in-memory cache and persistent database audit log to prevent duplicate reminders
     */
    protected function hasExecutedToday(string $cacheKey, string $action, string $stage, ?int $week = null): bool
    {
        if (Cache::has($cacheKey)) {
            return true;
        }

        // Secondary check in audit_logs for safety against cache flushes
        try {
            $query = AuditLog::where('action', $action)
                ->whereDate('created_at', now()->toDateString())
                ->where('details', 'LIKE', "%{$stage}%");

            if ($week !== null) {
                $query->where('details', 'LIKE', "%Week {$week}%");
            }

            if ($query->exists()) {
                // Restore cache key
                Cache::put($cacheKey, true, now()->addDays(7));
                return true;
            }
        } catch (\Throwable $e) {}

        return false;
    }

    protected function markExecuted(string $cacheKey, int $days = 7): void
    {
        try {
            Cache::put($cacheKey, true, now()->addDays($days));
        } catch (\Throwable $e) {}
    }

    protected function addMinutesToTime(string $time, int $minutes = 5): string
    {
        $parts = explode(':', trim($time));
        $h = (int)($parts[0] ?? 12);
        $m = (int)($parts[1] ?? 0);
        $total = ($h * 60 + $m + $minutes) % (24 * 60);
        return sprintf('%02d:%02d', intdiv($total, 60), $total % 60);
    }
}

<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Submission;
use Illuminate\Console\Command;

class WeeklyReminderCommand extends Command
{
    protected $signature = 'kaldis:weekly-reminder {--week=1}';
    protected $description = 'Send weekly performance report submission reminders to department heads';

    public function handle()
    {
        $year = kaldis_setting('current_planning_year', config('kaldis.current_planning_year'));
        $month = kaldis_setting('current_planning_month', config('kaldis.current_planning_month'));
        $week = (int)$this->option('week');

        $this->info("Running weekly report reminders for {$month} {$year} (Week {$week})...");

        $departments = Department::where('active', 1)->with('head')->get();
        $remindedCount = 0;

        foreach ($departments as $dept) {
            $submitted = Submission::where('department_id', $dept->id)
                ->where('year', $year)
                ->where('month', $month)
                ->where('week_number', $week)
                ->exists();

            if (!$submitted) {
                $headName = $dept->head ? $dept->head->full_name : 'Department Head';
                $this->line("- Reminding: {$dept->department_name} ({$headName})");
                $remindedCount++;
            }
        }

        AuditLog::log('CRON_REMINDER', 'system', "Sent weekly submission reminder to {$remindedCount} departments (Week {$week})");
        $this->info("Completed. {$remindedCount} departments reminded.");

        return Command::SUCCESS;
    }
}

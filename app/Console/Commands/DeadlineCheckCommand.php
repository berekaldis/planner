<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Escalation;
use App\Models\Submission;
use App\Models\WeeklyReport;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeadlineCheckCommand extends Command
{
    protected $signature = 'kaldis:deadline-check {--week=1}';
    protected $description = 'Evaluate weekly report deadline cutoff and flag missing departments';

    public function handle()
    {
        $year = kaldis_setting('current_planning_year', config('kaldis.current_planning_year'));
        $month = kaldis_setting('current_planning_month', config('kaldis.current_planning_month'));
        $week = (int)$this->option('week');

        $this->info("Checking Monday 12:00 PM deadline compliance for {$month} {$year} (Week {$week})...");

        $departments = Department::where('active', 1)->get();
        $missingCount = 0;

        foreach ($departments as $dept) {
            $submission = Submission::where('department_id', $dept->id)
                ->where('year', $year)
                ->where('month', $month)
                ->where('week_number', $week)
                ->first();

            if (!$submission) {
                // Create MISSING submission marker
                $report = WeeklyReport::firstOrCreate([
                    'department_id' => $dept->id,
                    'year' => $year,
                    'month' => $month,
                    'week_number' => $week,
                ], [
                    'total_tasks' => 0,
                    'done_tasks' => 0,
                    'not_done_tasks' => 0,
                    'completion_percentage' => 0,
                    'report_summary' => 'Report not submitted by deadline.',
                ]);

                Submission::create([
                    'weekly_report_id' => $report->id,
                    'department_id' => $dept->id,
                    'year' => $year,
                    'month' => $month,
                    'week_number' => $week,
                    'compliance_status' => 'MISSING',
                    'approval_status' => 'REJECTED',
                    'deadline_time' => Carbon::now(),
                ]);

                // Create Level 1 Escalation
                Escalation::create([
                    'department_id' => $dept->id,
                    'year' => $year,
                    'month' => $month,
                    'week_number' => $week,
                    'escalation_level' => 1,
                    'status' => 'OPEN',
                    'triggered_at' => Carbon::now(),
                ]);

                $this->warn("- Flagged MISSING: {$dept->department_name}");
                $missingCount++;
            }
        }

        AuditLog::log('DEADLINE_CHECK', 'system', "Evaluated deadline compliance. {$missingCount} departments flagged MISSING.");
        $this->info("Completed. {$missingCount} departments flagged.");

        return Command::SUCCESS;
    }
}

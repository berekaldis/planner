<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Escalation;
use App\Models\Submission;
use App\Models\User;
use App\Models\WeeklyReport;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeadlineCheckCommand extends Command
{
    protected $signature = 'kaldis:deadline-check 
                            {--week= : Inspection week number (1 to 5)}
                            {--month= : Specific Ethiopian Month}
                            {--year= : Specific Planning Year}';

    protected $description = 'Evaluate weekly report deadline cutoff, flag missing departments, and send escalation alerts and GM executive summary via Telegram';

    public function handle(TelegramService $telegram): int
    {
        $year = $this->option('year') ?: kaldis_setting('current_planning_year', config('kaldis.current_planning_year', '2019 E.C.'));
        $month = $this->option('month') ?: kaldis_setting('current_planning_month', config('kaldis.current_planning_month', 'Meskerem'));
        $week = (int)($this->option('week') ?: (function_exists('get_current_planning_week') ? get_current_planning_week() : 1) ?: 1);

        $this->info("Checking Monday 12:00 PM deadline compliance for {$month} {$year} (Week {$week})...");

        $departments = Department::where('active', 1)->with('head')->get();
        $totalDepts = $departments->count();

        $onTimeDepts = [];
        $lateDepts = [];
        $missingDepts = [];
        $escalationsSent = 0;

        foreach ($departments as $dept) {
            $submission = Submission::where('department_id', $dept->id)
                ->where('year', $year)
                ->where('month', $month)
                ->where('week_number', $week)
                ->first();

            $report = WeeklyReport::where('department_id', $dept->id)
                ->where('year', $year)
                ->where('month', $month)
                ->where('week_number', $week)
                ->first();

            $status = 'NOT_SUBMITTED';
            if ($submission && $submission->submitted_at) {
                $status = in_array($submission->status, ['ON_TIME', 'LATE']) ? $submission->status : 'ON_TIME';
            } elseif ($report && $report->submitted_at) {
                $status = in_array($report->submission_status, ['ON_TIME', 'LATE']) ? $report->submission_status : 'ON_TIME';
            }

            if ($status === 'ON_TIME') {
                $doneTasks = $report->done_tasks ?? 0;
                $totalTasks = $report->total_tasks ?? 0;
                $rate = $report->completion_percentage ?? 0;
                $onTimeDepts[] = [
                    'dept' => $dept,
                    'done' => $doneTasks,
                    'total' => $totalTasks,
                    'rate' => $rate,
                ];
            } elseif ($status === 'LATE') {
                $doneTasks = $report->done_tasks ?? 0;
                $totalTasks = $report->total_tasks ?? 0;
                $rate = $report->completion_percentage ?? 0;
                $lateDepts[] = [
                    'dept' => $dept,
                    'done' => $doneTasks,
                    'total' => $totalTasks,
                    'rate' => $rate,
                ];
            } else {
                // MISSING / NOT SUBMITTED
                $missingDepts[] = [
                    'dept' => $dept,
                ];

                // Create or update MISSING report record
                WeeklyReport::updateOrCreate([
                    'department_id' => $dept->id,
                    'year' => $year,
                    'month' => $month,
                    'week_number' => $week,
                ], [
                    'status' => 'DRAFT',
                    'submission_status' => 'MISSING',
                    'total_tasks' => 0,
                    'done_tasks' => 0,
                    'not_done_tasks' => 0,
                    'completion_percentage' => 0,
                    'notes' => 'Weekly performance report not submitted by Monday 12:00 PM cutoff.',
                ]);

                // Create or update MISSING submission record
                Submission::updateOrCreate([
                    'department_id' => $dept->id,
                    'year' => $year,
                    'month' => $month,
                    'week_number' => $week,
                ], [
                    'status' => 'MISSING',
                    'approval_status' => 'PENDING',
                    'deadline_at' => Carbon::now(),
                    'remarks' => 'Monday 12:00 PM cutoff expired without submission.',
                ]);

                // Record Level 1 Escalation
                Escalation::firstOrCreate([
                    'department_id' => $dept->id,
                    'year' => $year,
                    'month' => $month,
                    'week_number' => $week,
                    'escalation_level' => 1,
                ], [
                    'missed_weeks_count' => 1,
                    'channel' => 'TELEGRAM',
                    'status' => 'OPEN',
                    'triggered_at' => Carbon::now(),
                ]);

                $this->warn("- Flagged MISSING: {$dept->department_name}");

                // Send Telegram Escalation Alert to Department Head
                $head = $dept->head;
                if ($telegram->isConfigured() && $head && !empty($head->telegram_chat_id)) {
                    $msg = "🚨 <b>Kaldis Deadline Escalation Alert</b>\n\n"
                         . "Attention: <b>{$head->full_name}</b> ({$dept->department_name})\n\n"
                         . "The weekly submission deadline for <b>{$dept->department_name}</b> ({$month} {$year}, Week {$week}) has expired without a submitted report.\n\n"
                         . "⚠️ A <b>Level 1 Escalation</b> has been registered in the system.\n"
                         . "Please evaluate your tasks and submit your execution report immediately:\n"
                         . "<code>" . url('/weekly/performance?week=' . $week) . "</code>\n\n"
                         . "☕ <i>Kaldis Coffee PLC — Executive Operations</i>";

                    $res = $telegram->sendMessage($head->telegram_chat_id, $msg);
                    if (!empty($res['ok'])) {
                        $escalationsSent++;
                    }
                }
            }
        }

        $onTimeCount = count($onTimeDepts);
        $lateCount = count($lateDepts);
        $missingCount = count($missingDepts);
        $submittedCount = $onTimeCount + $lateCount;
        $complianceRate = $totalDepts > 0 ? round(($submittedCount / $totalDepts) * 100, 1) : 0;

        // Dispatch Company-Wide Executive Summary Report to General Management (GM)
        $gmSummaryDelivered = 0;
        if ($telegram->isConfigured()) {
            $gmChatIds = [];

            // Query GM users
            $gmUsers = User::where(function ($query) {
                $query->whereHas('role', function ($q) {
                    $q->whereIn('name', ['gm_management', 'super_admin', 'gm', 'general_manager']);
                })->orWhereIn('role_id', [1, 2]);
            })->where('is_active', 1)->get();

            foreach ($gmUsers as $gm) {
                if (!empty($gm->telegram_chat_id)) {
                    $gmChatIds[] = trim($gm->telegram_chat_id);
                }
            }

            // Check system settings for escalation_recipients_gm
            $recipients = kaldis_setting('escalation_recipients_gm', '');
            if (!empty($recipients)) {
                foreach (explode(',', $recipients) as $r) {
                    $r = trim($r);
                    if (is_numeric($r) || str_starts_with($r, '@')) {
                        $gmChatIds[] = $r;
                    }
                }
            }

            $gmChatIds = array_unique(array_filter($gmChatIds));

            if (!empty($gmChatIds)) {
                $execMsg = "📊 <b>Kaldis Executive Weekly Cutoff Report</b>\n";
                $execMsg .= "📅 <b>Period:</b> Week {$week} &bull; {$month} {$year}\n";
                $execMsg .= "⏰ <b>Cutoff Evaluation:</b> " . now()->format('M d, Y h:i A') . "\n\n";

                $execMsg .= "📈 <b>Company Submission Performance:</b>\n";
                $execMsg .= "• Total Departments: <b>{$totalDepts}</b>\n";
                $execMsg .= "• 🟢 Submitted On-Time: <b>{$onTimeCount}</b>\n";
                if ($lateCount > 0) {
                    $execMsg .= "• 🟡 Submitted Late: <b>{$lateCount}</b>\n";
                }
                $execMsg .= "• 🔴 Missing / Delinquent: <b>{$missingCount}</b>\n";
                $execMsg .= "• 🎯 Submission Compliance: <b>{$complianceRate}%</b>\n\n";

                if (!empty($onTimeDepts)) {
                    $execMsg .= "🟢 <b>On-Time Submissions:</b>\n";
                    foreach ($onTimeDepts as $item) {
                        $execMsg .= "• {$item['dept']->department_name} ({$item['rate']}% completion, {$item['done']}/{$item['total']} tasks)\n";
                    }
                    $execMsg .= "\n";
                }

                if (!empty($lateDepts)) {
                    $execMsg .= "🟡 <b>Late Submissions:</b>\n";
                    foreach ($lateDepts as $item) {
                        $execMsg .= "• {$item['dept']->department_name} ({$item['rate']}% completion)\n";
                    }
                    $execMsg .= "\n";
                }

                if (!empty($missingDepts)) {
                    $execMsg .= "🔴 <b>Delinquent Departments (NOT SUBMITTED):</b>\n";
                    foreach ($missingDepts as $item) {
                        $headName = $item['dept']->head ? $item['dept']->head->full_name : 'No Head Assigned';
                        $execMsg .= "• <b>{$item['dept']->department_name}</b> ({$headName})\n";
                    }
                    $execMsg .= "\n";
                }

                $dashboardUrl = url('/management/dashboard?year=' . urlencode($year) . '&month=' . urlencode($month) . '&week=' . $week);
                $execMsg .= "🌐 <a href=\"{$dashboardUrl}\"><b>Open GM Executive Dashboard & Vouchers</b></a>\n";
                $execMsg .= "☕ <i>Kaldis Coffee PLC — Executive Operations</i>";

                foreach ($gmChatIds as $cid) {
                    $res = $telegram->sendMessage($cid, $execMsg);
                    if (!empty($res['ok'])) {
                        $gmSummaryDelivered++;
                    }
                }
            }
        }

        AuditLog::log(
            'DEADLINE_CHECK',
            'system',
            "Evaluated Week {$week} cutoff ({$month} {$year}). On-Time: {$onTimeCount}, Late: {$lateCount}, Missing: {$missingCount}. Head alerts: {$escalationsSent}, GM Executive summaries: {$gmSummaryDelivered}"
        );

        $this->info("Completed. {$missingCount} departments flagged. Escalations sent: {$escalationsSent}. GM Executive summaries delivered: {$gmSummaryDelivered}.");

        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Escalation;
use App\Models\Submission;
use App\Models\User;
use App\Models\WeeklyPlan;
use App\Models\WeeklyReport;
use App\Models\WeeklyTask;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DeadlineCheckCommand extends Command
{
    protected $signature = 'kaldis:deadline-check 
                            {--week= : Inspection performance week number (1 to 5)}
                            {--month= : Specific Ethiopian Month}
                            {--year= : Specific Planning Year}
                            {--target=all : Target recipients: "all", "gm", or "heads"}
                            {--force : Force send and bypass cache debounce}';

    protected $description = 'Evaluate weekly report and planning deadline compliance, flag delinquent departments, and send escalation alerts and GM executive summary via Telegram';

    public function handle(TelegramService $telegram): int
    {
        $ethPeriod = function_exists('get_ethiopian_period') ? get_ethiopian_period() : ['week' => 1];
        $currentEthWeek = (int)($ethPeriod['week'] ?? 1);

        $year = $this->option('year') ?: kaldis_setting('current_planning_year', config('kaldis.current_planning_year', '2019 E.C.'));
        $month = $this->option('month') ?: kaldis_setting('current_planning_month', config('kaldis.current_planning_month', 'Meskerem'));
        
        // If week option is passed, evaluate that performance week.
        // Otherwise, in the weekly cycle, on Monday of Week W, the report due is for Week W - 1 (or 1 if W=1).
        $week = (int)($this->option('week') ?: ($currentEthWeek > 1 ? $currentEthWeek - 1 : 1));
        $planWeek = min(5, $week + 1);
        $target = strtolower((string)($this->option('target') ?: 'all'));
        $force = (bool)$this->option('force');

        $deadlineInfo = function_exists('get_weekly_cycle_deadline')
            ? get_weekly_cycle_deadline($year, $month, $week)
            : ['deadline_carbon' => Carbon::now(), 'deadline_display' => 'Monday 6:30 LT (12:30 PM)'];
        $deadlineCarbon = $deadlineInfo['deadline_carbon'] ?? Carbon::now();
        $deadlineFormatted = $deadlineInfo['deadline_display'] ?? "Monday 6:30 LT (12:30 PM)";

        $this->info("Checking Monday 6:30 LT (12:30 PM) deadline compliance for Week {$week} Performance & Week {$planWeek} Plan ({$month} {$year})...");

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

            $upcomingPlan = WeeklyPlan::where('department_id', $dept->id)
                ->where('year', $year)
                ->where('month', $month)
                ->where('week_number', $planWeek)
                ->first();

            $upcomingPlanTasks = WeeklyTask::where('department_id', $dept->id)
                ->where('year', $year)
                ->where('month', $month)
                ->where('week_number', $planWeek)
                ->count();

            $status = 'NOT_SUBMITTED';
            $submittedAt = null;

            if ($submission && $submission->submitted_at) {
                $submittedAt = $submission->submitted_at;
                if (in_array($submission->status, ['ON_TIME', 'LATE'])) {
                    $status = $submission->status;
                } else {
                    $status = Carbon::parse($submission->submitted_at)->lte($deadlineCarbon) ? 'ON_TIME' : 'LATE';
                }
            } elseif ($report && $report->submitted_at) {
                $submittedAt = $report->submitted_at;
                if (in_array($report->submission_status, ['ON_TIME', 'LATE'])) {
                    $status = $report->submission_status;
                } else {
                    $status = Carbon::parse($report->submitted_at)->lte($deadlineCarbon) ? 'ON_TIME' : 'LATE';
                }
            } elseif ($upcomingPlan && in_array($upcomingPlan->status, ['SUBMITTED', 'LOCKED']) && $upcomingPlanTasks > 0) {
                $submittedAt = $upcomingPlan->updated_at ?? now();
                $status = Carbon::parse($submittedAt)->lte($deadlineCarbon) ? 'ON_TIME' : 'LATE';
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
                    'plan_tasks' => $upcomingPlanTasks,
                    'submitted_at' => $submittedAt,
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
                    'plan_tasks' => $upcomingPlanTasks,
                    'submitted_at' => $submittedAt,
                ];
            } else {
                // MISSING / NOT SUBMITTED
                $missingDepts[] = [
                    'dept' => $dept,
                    'plan_tasks' => $upcomingPlanTasks,
                ];

                if ($target === 'all' || $target === 'heads') {
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
                        'notes' => 'Weekly performance report not submitted by Monday 6:30 LT (12:30 PM) cutoff.',
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
                        'deadline_at' => $deadlineCarbon,
                        'remarks' => 'Monday 6:30 LT (12:30 PM) cutoff expired without submission.',
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
                    if (!$head && $dept->head_user_id) {
                        $head = User::find($dept->head_user_id);
                    }
                    if (!$head) {
                        $head = User::where('department_id', $dept->id)
                            ->whereHas('role', fn($q) => $q->whereIn('name', ['dept_head', 'department_head']))
                            ->first();
                    }

                    $chatId = null;
                    if ($head && !empty($head->telegram_chat_id) && $head->telegram_chat_id !== '123456789') {
                        $chatId = trim((string)$head->telegram_chat_id);
                    } elseif ($head) {
                        $tu = DB::table('telegram_users')->where('user_id', $head->id)->where('is_active', 1)->first();
                        if ($tu && !empty($tu->telegram_user_id) && $tu->telegram_user_id !== '123456789') {
                            $chatId = trim((string)$tu->telegram_user_id);
                        }
                    }

                    if ($telegram->isConfigured() && !empty($chatId)) {
                        $safeDeptName = htmlspecialchars($dept->department_name, ENT_QUOTES, 'UTF-8');
                        $safeHeadName = htmlspecialchars($head ? $head->full_name : 'Department Head', ENT_QUOTES, 'UTF-8');
                        $perfUrl = url('/weekly/performance?week=' . $week . '&year=' . urlencode($year) . '&month=' . urlencode($month));

                        $msg = "🚨 <b>Kaldis Deadline Escalation Alert</b>\n\n"
                             . "Attention: <b>{$safeHeadName}</b> ({$safeDeptName})\n\n"
                             . "The official weekly submission deadline for <b>{$safeDeptName}</b> (Week {$week} Performance &amp; Week {$planWeek} Plan &bull; {$month} {$year}) expired on <b>Monday 6:30 LT (12:30 PM)</b> without a submitted report.\n\n"
                             . "⚠️ A <b>Level 1 Escalation</b> has been registered in the system.\n"
                             . "Please evaluate your tasks and submit your execution report immediately.\n\n"
                             . "☕ <i>Kaldis Coffee PLC — Executive Operations</i>\n"
                             . "📅 <i>" . format_ethiopian_datetime() . "</i>";

                        $replyMarkup = [
                            'inline_keyboard' => [
                                [
                                    ['text' => '🌐 Submit Performance Report Now', 'url' => $perfUrl]
                                ]
                            ]
                        ];

                        $res = $telegram->sendMessage($chatId, $msg, $replyMarkup);
                        if (!empty($res['ok'])) {
                            $escalationsSent++;
                        }
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
        if (($target === 'all' || $target === 'gm') && $telegram->isConfigured()) {
            $gmChatIds = [];

            // Query GM users
            $gmUsers = User::where(function ($query) {
                $query->whereHas('role', function ($q) {
                    $q->whereIn('name', ['gm_management', 'super_admin', 'gm', 'general_manager']);
                })->orWhereIn('role_id', [1, 2]);
            })->where('is_active', 1)->get();

            foreach ($gmUsers as $gm) {
                if (!empty($gm->telegram_chat_id) && $gm->telegram_chat_id !== '123456789') {
                    $gmChatIds[] = trim((string)$gm->telegram_chat_id);
                }
            }

            // Also check telegram_users table for linked GM accounts
            try {
                $linkedGmIds = DB::table('telegram_users')
                    ->whereIn('user_id', $gmUsers->pluck('id'))
                    ->where('is_active', 1)
                    ->pluck('telegram_user_id')
                    ->toArray();
                foreach ($linkedGmIds as $lcid) {
                    $lcid = trim((string)$lcid);
                    if (!empty($lcid) && $lcid !== '123456789') {
                        $gmChatIds[] = $lcid;
                    }
                }
            } catch (\Throwable $e) {}

            // Check system settings for escalation_recipients_gm
            $recipients = kaldis_setting('escalation_recipients_gm', '');
            if (!empty($recipients)) {
                foreach (explode(',', $recipients) as $r) {
                    $r = trim($r);
                    if ($r !== '123456789' && (is_numeric($r) || str_starts_with($r, '-') || str_starts_with($r, '@'))) {
                        $gmChatIds[] = $r;
                    }
                }
            }

            $groupChatId = kaldis_setting('telegram_chat_id', env('TELEGRAM_CHAT_ID', ''));
            if (!empty($groupChatId)) {
                $gmChatIds[] = trim((string)$groupChatId);
            }

            $gmChatIds = array_unique(array_filter($gmChatIds));

            $cacheKey = "gm_weekly_compliance_report_{$year}_{$month}_{$week}_" . date('Ymd');
            if ($force || !Cache::has($cacheKey)) {
                if (!empty($gmChatIds)) {
                    $execMsg = "📊 <b>Kaldis Executive Weekly Cutoff Report</b>\n";
                    $execMsg .= "📅 <b>Cycle:</b> Week {$week} Performance &amp; Week {$planWeek} Plan &bull; {$month} {$year}\n";
                    $execMsg .= "⏰ <b>Submission Cutoff:</b> Monday 6:30 LT (12:30 PM)\n";
                    $execMsg .= "📢 <b>Evaluation Broadcast:</b> Monday 9:00 LT (3:00 PM)\n\n";

                    $execMsg .= "📈 <b>Company Submission Performance:</b>\n";
                    $execMsg .= "• Total Departments: <b>{$totalDepts}</b>\n";
                    $execMsg .= "• 🟢 Submitted On-Time: <b>{$onTimeCount}</b>\n";
                    if ($lateCount > 0) {
                        $execMsg .= "• 🟡 Submitted Late: <b>{$lateCount}</b>\n";
                    }
                    $execMsg .= "• 🔴 Missing / Delinquent: <b>{$missingCount}</b>\n";
                    $execMsg .= "• 🎯 Submission Compliance: <b>{$complianceRate}%</b>\n\n";

                    if (!empty($onTimeDepts)) {
                        $execMsg .= "🟢 <b>ON-TIME SUBMISSIONS (በሰዓቱ ያስገቡ):</b>\n";
                        foreach ($onTimeDepts as $item) {
                            $safeDept = htmlspecialchars($item['dept']->department_name, ENT_QUOTES, 'UTF-8');
                            $planInfo = $item['plan_tasks'] > 0 ? " &bull; W{$planWeek} Plan: {$item['plan_tasks']} tasks" : "";
                            $execMsg .= "• <b>{$safeDept}</b> — {$item['rate']}% completion ({$item['done']}/{$item['total']} tasks){$planInfo}\n";
                        }
                        $execMsg .= "\n";
                    }

                    if (!empty($lateDepts)) {
                        $execMsg .= "🟡 <b>LATE SUBMISSIONS (ዘግይተው ያስገቡ):</b>\n";
                        foreach ($lateDepts as $item) {
                            $safeDept = htmlspecialchars($item['dept']->department_name, ENT_QUOTES, 'UTF-8');
                            $lateTimeStr = $item['submitted_at'] ? " &bull; Submitted " . format_ethiopian_time($item['submitted_at'], true) : "";
                            $execMsg .= "• <b>{$safeDept}</b> — {$item['rate']}% completion{$lateTimeStr}\n";
                        }
                        $execMsg .= "\n";
                    }

                    if (!empty($missingDepts)) {
                        $execMsg .= "🔴 <b>NOT SUBMITTED / DELINQUENT (ያላስገቡ / የቀሩ):</b>\n";
                        foreach ($missingDepts as $item) {
                            $safeDept = htmlspecialchars($item['dept']->department_name, ENT_QUOTES, 'UTF-8');
                            $headName = $item['dept']->head ? $item['dept']->head->full_name : 'No Head Assigned';
                            $safeHead = htmlspecialchars($headName, ENT_QUOTES, 'UTF-8');
                            $execMsg .= "• <b>{$safeDept}</b> ({$safeHead}) — <i>Missing Week {$week} Perf &amp; Week {$planWeek} Plan</i>\n";
                        }
                        $execMsg .= "\n";
                    }

                    $dashboardUrl = url('/management/dashboard?year=' . urlencode($year) . '&month=' . urlencode($month) . '&week=' . $week);
                    $execMsg .= "☕ <i>Kaldis Coffee PLC — Planning &amp; Operational Governance</i>";

                    $replyMarkup = [
                        'inline_keyboard' => [
                            [
                                ['text' => '🌐 Open GM Executive Dashboard & Vouchers', 'url' => $dashboardUrl]
                            ]
                        ]
                    ];

                    foreach ($gmChatIds as $cid) {
                        $res = $telegram->sendMessage($cid, $execMsg, $replyMarkup);
                        if (!empty($res['ok'])) {
                            $gmSummaryDelivered++;
                        }
                    }

                    Cache::put($cacheKey, true, now()->addHours(12));
                }
            } else {
                $this->info("GM Executive report already dispatched today for Week {$week}. Use --force to resend.");
            }
        }

        AuditLog::log(
            'DEADLINE_CHECK',
            'system',
            "Evaluated Week {$week} Perf & Week {$planWeek} Plan cutoff ({$month} {$year}). On-Time: {$onTimeCount}, Late: {$lateCount}, Missing: {$missingCount}. Head alerts: {$escalationsSent}, GM Executive summaries: {$gmSummaryDelivered}"
        );

        $this->info("Completed. {$missingCount} departments flagged. Escalations sent: {$escalationsSent}. GM Executive summaries delivered: {$gmSummaryDelivered}.");

        return Command::SUCCESS;
    }
}

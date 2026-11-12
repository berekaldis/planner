<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Submission;
use App\Models\User;
use App\Models\WeeklyPlan;
use App\Models\WeeklyReport;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WeeklyReminderCommand extends Command
{
    protected $signature = 'kaldis:weekly-reminder 
                            {--type=all : Reminder type: "all" (both performance & plan), "performance", or "plan"}
                            {--stage= : Reminder stage: "saturday", "sunday", "monday"}
                            {--week= : Specific week number (1 to 5)}
                            {--month= : Specific Ethiopian Month}
                            {--year= : Specific Planning Year}';

    protected $description = 'Send automated weekly performance report and weekly planning reminders to department heads and GM via Telegram';

    public function handle(TelegramService $telegram): int
    {
        $ethPeriod = function_exists('get_ethiopian_period') ? get_ethiopian_period() : ['week' => 1];
        $currentEthWeek = (int)($ethPeriod['week'] ?? 1);

        $year = $this->option('year') ?: kaldis_setting('current_planning_year', config('kaldis.current_planning_year', '2019 E.C.'));
        $month = $this->option('month') ?: kaldis_setting('current_planning_month', config('kaldis.current_planning_month', 'Meskerem'));
        $type = strtolower((string)($this->option('type') ?: 'all'));
        $stage = strtolower((string)($this->option('stage') ?? ''));

        // Performance report evaluates the week just completed/ending
        // Weekly planning schedules operational tasks for the upcoming week
        if ($this->option('week')) {
            $week = (int)$this->option('week');
            $planWeek = min(5, $week + 1);
        } else {
            $week = ($currentEthWeek > 1) ? $currentEthWeek - 1 : 1;
            $planWeek = ($currentEthWeek > 1) ? $currentEthWeek : min(5, 2);
        }

        $this->info("Executing Weekly Reminder [Type: {$type}, Stage: {$stage}] for {$month} {$year} (Report Week {$week}, Plan Week {$planWeek})...");

        $departments = Department::where('active', 1)->with('head')->get();
        $totalDepts = $departments->count();

        $performanceReminded = 0;
        $planReminded = 0;
        $unifiedReminded = 0;
        $unsubmittedDepts = [];

        foreach ($departments as $dept) {
            $head = $dept->head;
            if (!$head && $dept->head_user_id) {
                $head = User::find($dept->head_user_id);
            }
            if (!$head) {
                $head = User::where('department_id', $dept->id)
                    ->whereHas('role', fn($q) => $q->whereIn('name', ['dept_head', 'department_head']))
                    ->first();
            }

            // Find head chat ID
            $chatId = null;
            if ($head && !empty($head->telegram_chat_id) && $head->telegram_chat_id !== '123456789') {
                $chatId = trim((string)$head->telegram_chat_id);
            } elseif ($head) {
                $tu = DB::table('telegram_users')->where('user_id', $head->id)->where('is_active', 1)->first();
                if ($tu && !empty($tu->telegram_user_id) && $tu->telegram_user_id !== '123456789') {
                    $chatId = trim((string)$tu->telegram_user_id);
                }
            }

            $safeDeptName = htmlspecialchars($dept->department_name, ENT_QUOTES, 'UTF-8');
            $safeHeadName = htmlspecialchars($head ? $head->full_name : 'Department Head', ENT_QUOTES, 'UTF-8');

            // 1. Evaluate Weekly Performance Report Submission Status (Week $week)
            $sub = Submission::where('department_id', $dept->id)
                ->where('year', $year)
                ->where('month', $month)
                ->where('week_number', $week)
                ->first();

            $report = WeeklyReport::where('department_id', $dept->id)
                ->where('year', $year)
                ->where('month', $month)
                ->where('week_number', $week)
                ->first();

            $isPerfSubmitted = false;
            if ($sub && ($sub->isDelayApproved() || in_array($sub->status, ['ON_TIME', 'LATE', 'DELAY_APPROVED']))) {
                $isPerfSubmitted = true;
            } elseif ($report && ($report->isDelayApproved() || in_array($report->submission_status, ['ON_TIME', 'LATE', 'DELAY_APPROVED']) || in_array($report->status, ['SUBMITTED', 'APPROVED']))) {
                $isPerfSubmitted = true;
            }

            // 2. Evaluate Weekly Task Planning Submission Status (Week $planWeek)
            $wPlan = WeeklyPlan::where('department_id', $dept->id)
                ->where('year', $year)
                ->where('month', $month)
                ->where('week_number', $planWeek)
                ->first();

            $planTasksCount = \App\Models\WeeklyTask::where('department_id', $dept->id)
                ->where('year', $year)
                ->where('month', $month)
                ->where('week_number', $planWeek)
                ->count();

            $isPlanSubmitted = false;
            if ($wPlan && ($wPlan->isDelayApproved() || in_array($wPlan->submission_status, ['ON_TIME', 'LATE', 'DELAY_APPROVED']) || in_array($wPlan->status, ['SUBMITTED', 'APPROVED']) || !empty($wPlan->submitted_at))) {
                $isPlanSubmitted = true;
            }

            // Case A: Unified Reminder (Saturday, Sunday, Monday morning)
            if ($type === 'all') {
                if ($isPerfSubmitted && $isPlanSubmitted) {
                    // Both submitted on time - DO NOT REMIND!
                    continue;
                }

                $unsubmittedDepts[] = [
                    'dept' => $dept,
                    'head' => $head,
                    'pending_perf' => !$isPerfSubmitted,
                    'pending_plan' => !$isPlanSubmitted,
                    'plan_tasks_count' => $planTasksCount,
                ];

                $this->line("- Reminding {$dept->department_name} ({$safeHeadName}) [Perf: " . ($isPerfSubmitted ? 'OK' : 'PENDING') . ", Plan: " . ($isPlanSubmitted ? 'OK' : 'PENDING') . "]");

                if ($telegram->isConfigured() && !empty($chatId)) {
                    switch ($stage) {
                        case 'saturday':
                            $stageTitle = "📋 <b>Kaldis Weekly Planning & Report 1st Reminder (Saturday)</b>";
                            $stageLead = "This is your <b>weekend advance reminder</b> to prepare and submit your weekly reports and operational task schedule.";
                            break;
                        case 'sunday':
                            $stageTitle = "⏰ <b>Kaldis Weekly Submission 2nd Reminder (Sunday)</b>";
                            $stageLead = "The official weekly submission cutoff is <b>tomorrow (Monday) at 12:30 PM (6:30 LT Midday)</b>.";
                            break;
                        case 'monday':
                            $stageTitle = "🚨 <b>URGENT: Weekly Submission Deadline Today (Monday Morning)</b>";
                            $stageLead = "<b>Today is the weekly submission deadline!</b> Please finalize and submit before <b>12:30 PM (6:30 LT Midday)</b>.";
                            break;
                        default:
                            $stageTitle = "⏰ <b>Kaldis Coffee PLC — Weekly Submission Reminder</b>";
                            $stageLead = "This is a reminder to submit your weekly performance report and operational plan before Monday 12:30 PM (6:30 LT).";
                            break;
                    }

                    $perfBadge = $isPerfSubmitted ? "🟢 <b>Submitted</b>" : "🔴 <b>PENDING SUBMISSION</b>";
                    $planBadge = $isPlanSubmitted ? "🟢 <b>Submitted</b>" : ("🔴 <b>PENDING SUBMISSION</b>" . ($planTasksCount > 0 ? " (<i>{$planTasksCount} drafted - click 'Submit to GM'</i>)" : " (<i>0 tasks scheduled</i>)"));

                    $msg = "{$stageTitle}\n\n"
                         . "Dear <b>{$safeHeadName}</b> ({$safeDeptName}),\n\n"
                         . "{$stageLead}\n\n"
                         . "📅 <b>Submission Deadline:</b> Monday 6:30 LT (12:30 PM Midday)\n\n"
                         . "📊 <b>Your Department Status:</b>\n"
                         . "• Week {$week} Performance Report: {$perfBadge}\n"
                         . "• Week {$planWeek} Task Schedule: {$planBadge}\n\n"
                         . "🎯 Please finalize and submit all pending items to ensure departmental compliance.\n\n"
                         . "☕ <i>Kaldis Coffee PLC — Planning & Operational Governance</i>\n"
                         . "📅 <i>" . format_ethiopian_datetime() . "</i>";

                    $buttons = [];
                    $perfUrl = url("/weekly/performance?year=" . urlencode($year) . "&month=" . urlencode($month) . "&week=" . $week);
                    $planUrl = url("/weekly/plans?year=" . urlencode($year) . "&month=" . urlencode($month) . "&week=" . $planWeek);

                    if (!$isPerfSubmitted) {
                        $buttons[] = [['text' => "📊 Submit Week {$week} Performance", 'url' => $perfUrl]];
                    }
                    if (!$isPlanSubmitted) {
                        $buttons[] = [['text' => "📝 Submit Week {$planWeek} Task Plan", 'url' => $planUrl]];
                    }

                    $replyMarkup = ['inline_keyboard' => $buttons];
                    $res = $telegram->sendMessage($chatId, $msg, $replyMarkup);
                    if (!empty($res['ok'])) {
                        $unifiedReminded++;
                    }
                }
            } elseif ($type === 'performance') {
                if (!$isPerfSubmitted) {
                    $unsubmittedDepts[] = [
                        'dept' => $dept,
                        'head' => $head,
                        'pending_perf' => true,
                        'pending_plan' => false,
                        'plan_tasks_count' => 0,
                    ];

                    $this->line("- Reminding Performance: {$dept->department_name} ({$safeHeadName})");

                    if ($telegram->isConfigured() && !empty($chatId)) {
                        $perfUrl = url("/weekly/performance?year=" . urlencode($year) . "&month=" . urlencode($month) . "&week=" . $week);
                        $msg = "⏰ <b>Kaldis Coffee PLC — Weekly Performance Report Reminder</b>\n\n"
                             . "Dear <b>{$safeHeadName}</b> ({$safeDeptName}),\n\n"
                             . "This is a reminder to finalize and submit your department's <b>Weekly Performance Execution Report for Week {$week}</b> ({$month} {$year}).\n\n"
                             . "📅 <b>Submission Deadline:</b> Monday 6:30 LT (12:30 PM Midday)\n"
                             . "🎯 Please verify all binary task statuses (DONE / NOT DONE) with root-cause reasons and submit before the cutoff.\n\n"
                             . "☕ <i>Kaldis Coffee PLC — Planning &amp; Operational Governance</i>\n"
                             . "📅 <i>" . format_ethiopian_datetime() . "</i>";

                        $replyMarkup = [
                            'inline_keyboard' => [
                                [
                                    ['text' => '🌐 Open Weekly Performance in Portal', 'url' => $perfUrl]
                                ]
                            ]
                        ];

                        $res = $telegram->sendMessage($chatId, $msg, $replyMarkup);
                        if (!empty($res['ok'])) {
                            $performanceReminded++;
                        }
                    }
                }
            } elseif ($type === 'plan') {
                if (!$isPlanSubmitted) {
                    $unsubmittedDepts[] = [
                        'dept' => $dept,
                        'head' => $head,
                        'pending_perf' => false,
                        'pending_plan' => true,
                        'plan_tasks_count' => $planTasksCount,
                    ];

                    $this->line("- Reminding Task Scheduling: {$dept->department_name} ({$safeHeadName})");

                    if ($telegram->isConfigured() && !empty($chatId)) {
                        $planUrl = url("/weekly/plans?year=" . urlencode($year) . "&month=" . urlencode($month) . "&week=" . $planWeek);
                        $draftStatus = ($planTasksCount > 0) ? " (<i>{$planTasksCount} drafted - please click 'Submit to GM'</i>)" : "";
                        $msg = "📝 <b>Kaldis Coffee PLC — Weekly Planning Reminder</b>\n\n"
                             . "Dear <b>{$safeHeadName}</b> ({$safeDeptName}),\n\n"
                             . "This is a reminder to schedule your department's <b>Weekly Operational Tasks</b> for <b>{$month} {$year}, Week {$planWeek}</b>{$draftStatus}.\n\n"
                             . "📅 <b>Submission Deadline:</b> Monday 6:30 LT (12:30 PM Midday)\n"
                             . "🎯 Align your weekly deliverables with active monthly targets and strategic goals.\n\n"
                             . "☕ <i>Kaldis Coffee PLC — Planning & Operational Governance</i>\n"
                             . "📅 <i>" . format_ethiopian_datetime() . "</i>";

                        $replyMarkup = [
                            'inline_keyboard' => [
                                [
                                    ['text' => '📝 Schedule Tasks in Portal', 'url' => $planUrl]
                                ]
                            ]
                        ];

                        $res = $telegram->sendMessage($chatId, $msg, $replyMarkup);
                        if (!empty($res['ok'])) {
                            $planReminded++;
                        }
                    }
                }
            }
        }

        // Summary notification to GM / Management group if unsubmitted departments exist
        $gmSentCount = 0;
        if (!empty($unsubmittedDepts) && $telegram->isConfigured()) {
            $gmChatIds = [];

            // Collect GM chat IDs
            $gmUsers = User::where(function ($q) {
                $q->whereHas('role', fn($r) => $r->whereIn('name', ['gm_management', 'super_admin', 'gm']))
                  ->orWhereIn('role_id', [1, 2]);
            })->where('is_active', 1)->get();

            foreach ($gmUsers as $gm) {
                if (!empty($gm->telegram_chat_id) && $gm->telegram_chat_id !== '123456789') {
                    $gmChatIds[] = trim((string)$gm->telegram_chat_id);
                }
            }

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

            $groupChatId = kaldis_setting('telegram_chat_id', env('TELEGRAM_CHAT_ID', ''));
            if (!empty($groupChatId)) {
                $gmChatIds[] = trim((string)$groupChatId);
            }

            $gmChatIds = array_unique(array_filter($gmChatIds));

            if (!empty($gmChatIds)) {
                $unsubCount = count($unsubmittedDepts);
                $stageHeader = match($stage) {
                    'saturday' => "Saturday 1st Reminder",
                    'sunday' => "Sunday 2nd Reminder",
                    'monday' => "Monday Final Morning Reminder",
                    default => "Status Digest"
                };

                $gmMsg = "📊 <b>Kaldis Weekly Submission Status ({$stageHeader})</b>\n"
                       . "📅 <b>Period:</b> Week {$week} &bull; {$month} {$year}\n"
                       . "⏰ <b>Status Check:</b> " . format_ethiopian_datetime() . "\n\n"
                       . "• Total Active Departments: <b>{$totalDepts}</b>\n"
                       . "• 🔴 Pending Submissions: <b>{$unsubCount}</b>\n\n";

                $gmMsg .= "<b>Pending Departments:</b>\n";
                foreach ($unsubmittedDepts as $item) {
                    $hName = $item['head'] ? $item['head']->full_name : 'No Head Assigned';
                    $pendingItems = [];
                    if (!empty($item['pending_perf'])) $pendingItems[] = "Performance";
                    if (!empty($item['pending_plan'])) {
                        $draftNote = ($item['plan_tasks_count'] > 0) ? "Plan ({$item['plan_tasks_count']} drafted)" : "Plan (0 tasks)";
                        $pendingItems[] = $draftNote;
                    }
                    $pendingStr = !empty($pendingItems) ? implode(' & ', $pendingItems) : 'Pending';
                    $gmMsg .= "• " . htmlspecialchars($item['dept']->department_name, ENT_QUOTES, 'UTF-8') . " [{$pendingStr}] (<i>{$hName}</i>)\n";
                }

                $dashboardUrl = url("/management/dashboard?year=" . urlencode($year) . "&month=" . urlencode($month) . "&week=" . $week);
                $gmMsg .= "\n📌 <b>Cutoff:</b> Monday 12:30 PM (6:30 LT Midday)\n";
                $gmMsg .= "☕ <i>Kaldis Coffee PLC — Executive Operations</i>";

                $replyMarkup = [
                    'inline_keyboard' => [
                        [
                            ['text' => '🌐 Open Executive Dashboard', 'url' => $dashboardUrl]
                        ]
                    ]
                ];

                foreach ($gmChatIds as $cid) {
                    $res = $telegram->sendMessage($cid, $gmMsg, $replyMarkup);
                    if (!empty($res['ok'])) {
                        $gmSentCount++;
                    }
                }
            }
        }

        AuditLog::log(
            'WEEKLY_REMINDER_CHECK',
            'system',
            "Weekly reminders [Type: {$type}, Stage: {$stage}] for Week {$week}/{$planWeek} ({$month} {$year}). Unified alerts: {$unifiedReminded}, Performance: {$performanceReminded}, Plan: {$planReminded}, GM summaries: {$gmSentCount}"
        );

        $this->info("Completed. Unified alerts: {$unifiedReminded}, Performance: {$performanceReminded}, Planning: {$planReminded}, GM summaries: {$gmSentCount}");
        return Command::SUCCESS;
    }
}

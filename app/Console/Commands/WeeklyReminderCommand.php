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
                            {--type=performance : Reminder type: "performance" (Execution report), "plan" (Upcoming tasks scheduling), or "all"}
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
        $type = strtolower((string)$this->option('type'));

        // If type is performance, default week is the week that just completed (e.g. Week 1 when in Week 2)
        // If type is plan, default week is the upcoming week to be planned (e.g. Week 2 when in Week 1/2)
        if ($this->option('week')) {
            $week = (int)$this->option('week');
        } else {
            $week = ($type === 'performance' && $currentEthWeek > 1) ? $currentEthWeek - 1 : $currentEthWeek;
        }
        $planWeek = min(5, $week + 1);

        $this->info("Executing Weekly Reminder [Type: {$type}] for {$month} {$year} (Week {$week})...");

        $departments = Department::where('active', 1)->with('head')->get();
        $totalDepts = $departments->count();

        $performanceReminded = 0;
        $planReminded = 0;
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

            // 1. Weekly Performance Execution Reminder
            if ($type === 'performance' || $type === 'all') {
                $isSubmitted = Submission::where('department_id', $dept->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->where('week_number', $week)
                    ->whereIn('status', ['ON_TIME', 'LATE'])
                    ->exists();

                if (!$isSubmitted) {
                    $unsubmittedDepts[] = [
                        'dept' => $dept,
                        'head' => $head,
                    ];

                    $this->line("- Reminding Performance: {$dept->department_name} ({$safeHeadName})");

                    if ($telegram->isConfigured() && !empty($chatId)) {
                        $perfUrl = url("/weekly/performance?year=" . urlencode($year) . "&month=" . urlencode($month) . "&week=" . $week);
                        $msg = "⏰ <b>Kaldis Coffee PLC — Weekly Performance &amp; Plan Reminder</b>\n\n"
                             . "Dear <b>{$safeHeadName}</b> ({$safeDeptName}),\n\n"
                             . "This is a reminder to finalize and submit your department's <b>Weekly Performance Execution Report for Week {$week}</b> and <b>Weekly Task Schedule for Week {$planWeek}</b> ({$month} {$year}).\n\n"
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
            }

            // 2. Weekly Planning (Upcoming Week Task Scheduling) Reminder
            if ($type === 'plan' || $type === 'all') {
                $hasWeeklyPlan = WeeklyPlan::where('department_id', $dept->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->where('week_number', $week)
                    ->whereHas('tasks')
                    ->exists();

                if (!$hasWeeklyPlan) {
                    $this->line("- Reminding Task Scheduling: {$dept->department_name} ({$safeHeadName})");

                    if ($telegram->isConfigured() && !empty($chatId)) {
                        $planUrl = url("/weekly/plans?year=" . urlencode($year) . "&month=" . urlencode($month) . "&week=" . $week);
                        $msg = "📝 <b>Kaldis Coffee PLC — Weekly Planning Reminder</b>\n\n"
                             . "Dear <b>{$safeHeadName}</b> ({$safeDeptName}),\n\n"
                             . "This is a reminder to schedule your department's <b>Weekly Operational Tasks</b> for <b>{$month} {$year}, Week {$week}</b>.\n\n"
                             . "🎯 Align your weekly deliverables with the active monthly targets and strategic goals.\n\n"
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

            $groupChatId = kaldis_setting('telegram_chat_id', env('TELEGRAM_CHAT_ID', ''));
            if (!empty($groupChatId)) {
                $gmChatIds[] = trim((string)$groupChatId);
            }

            $gmChatIds = array_unique(array_filter($gmChatIds));

            if (!empty($gmChatIds)) {
                $unsubCount = count($unsubmittedDepts);
                $gmMsg = "📊 <b>Kaldis Weekly Submission Status Digest</b>\n"
                       . "📅 <b>Period:</b> Week {$week} &bull; {$month} {$year}\n"
                       . "⏰ <b>Status Check:</b> " . format_ethiopian_datetime() . "\n\n"
                       . "• Total Departments: <b>{$totalDepts}</b>\n"
                       . "• 🔴 Pending Submissions: <b>{$unsubCount}</b>\n\n";

                $gmMsg .= "<b>Pending Departments:</b>\n";
                foreach ($unsubmittedDepts as $item) {
                    $hName = $item['head'] ? $item['head']->full_name : 'No Head Assigned';
                    $gmMsg .= "• " . htmlspecialchars($item['dept']->department_name, ENT_QUOTES, 'UTF-8') . " (<i>{$hName}</i>)\n";
                }

                $dashboardUrl = url("/management/dashboard?year=" . urlencode($year) . "&month=" . urlencode($month) . "&week=" . $week);
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
            "Weekly reminders for Week {$week} ({$month} {$year}). Performance alerts: {$performanceReminded}, Planning alerts: {$planReminded}, GM summaries: {$gmSentCount}"
        );

        $this->info("Completed. Performance alerts sent: {$performanceReminded}, Planning alerts: {$planReminded}, GM summaries: {$gmSentCount}");
        return Command::SUCCESS;
    }
}

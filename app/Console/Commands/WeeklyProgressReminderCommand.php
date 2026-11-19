<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use App\Models\WeeklyTask;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WeeklyProgressReminderCommand extends Command
{
    protected $signature = 'kaldis:weekly-progress-reminder 
                            {--force : Force send reminders bypassing day or cache checks}
                            {--dry-run : Simulate checks without sending actual reminders}
                            {--dept= : Specific department ID or code}
                            {--week= : Specific Ethiopian week number (1 to 5)}
                            {--month= : Specific Ethiopian Month}
                            {--year= : Specific Planning Year}';

    protected $description = 'Send daily task execution and progress reminders (Tuesday–Sunday) to Department Heads to advance weekly plans and mark completed tasks as DONE';

    public function handle(TelegramService $telegram): int
    {
        $force = (bool)$this->option('force');
        $dryRun = (bool)$this->option('dry-run');

        // Check if enabled in settings
        if (!$force && kaldis_setting('enable_weekly_progress_reminder', 'true') !== 'true') {
            $this->info("Weekly progress reminders are disabled in system settings. Exiting.");
            return Command::SUCCESS;
        }

        $ethPeriod = function_exists('get_ethiopian_period') ? get_ethiopian_period() : ['week' => 1];
        $currentEthWeek = (int)($ethPeriod['week'] ?? 1);

        $year = $this->option('year') ?: kaldis_setting('current_planning_year', config('kaldis.current_planning_year', '2019 E.C.'));
        $month = $this->option('month') ?: kaldis_setting('current_planning_month', config('kaldis.current_planning_month', 'Meskerem'));
        $week = $this->option('week') ? (int)$this->option('week') : $currentEthWeek;

        $this->info("Executing Weekly Progress Reminders for {$month} {$year} (Active Week {$week})...");

        // Determine IT Department ID & Admin Chat ID to safeguard boundary isolation
        $itDept = Department::where(function ($q) {
            $q->where('department_code', 'IT')
              ->orWhere('department_name', 'LIKE', '%Information Technology%')
              ->orWhere('department_name', 'LIKE', '%IT%');
        })->first();
        $itDeptId = $itDept ? $itDept->id : null;
        $adminChatId = User::where('username', 'admin')->value('telegram_chat_id') ?: '814523272';

        $query = Department::where('active', 1)->with('head');
        if ($this->option('dept')) {
            $deptFilter = $this->option('dept');
            $query->where(function ($q) use ($deptFilter) {
                $q->where('id', $deptFilter)
                  ->orWhere('department_code', $deptFilter);
            });
        }
        $departments = $query->get();

        $sentCount = 0;
        $skippedCount = 0;

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

            if (empty($chatId)) {
                $tu = DB::table('telegram_users')->where('department_id', $dept->id)->where('is_active', 1)->first();
                if ($tu && !empty($tu->telegram_user_id) && $tu->telegram_user_id !== '123456789') {
                    $chatId = trim((string)$tu->telegram_user_id);
                }
            }

            // Strict boundary: IT Admin chat ID must ONLY receive reminders for the IT Department
            if ($dept->id !== $itDeptId && $chatId === (string)$adminChatId) {
                $chatId = null;
            }

            $safeDeptName = htmlspecialchars($dept->department_name, ENT_QUOTES, 'UTF-8');
            $safeHeadName = htmlspecialchars($head ? $head->full_name : 'Department Head', ENT_QUOTES, 'UTF-8');

            // Fetch weekly tasks for active week
            $tasks = WeeklyTask::where('department_id', $dept->id)
                ->where('year', $year)
                ->where('month', $month)
                ->where('week_number', $week)
                ->with('result')
                ->get();

            $totalCount = $tasks->count();
            $doneCount = $tasks->filter(fn($t) => $t->result && $t->result->result === 'DONE')->count();
            $notDoneCount = $tasks->filter(fn($t) => $t->result && $t->result->result === 'NOT_DONE')->count();
            $pendingCount = max(0, $totalCount - $doneCount - $notDoneCount);

            $perfUrl = url("/weekly/performance?year=" . urlencode($year) . "&month=" . urlencode($month) . "&week=" . $week);
            $planUrl = url("/weekly/plans?year=" . urlencode($year) . "&month=" . urlencode($month) . "&week=" . $week);

            // Construct progress message
            if ($totalCount === 0) {
                $msg = "📋 <b>Kaldis Coffee PLC — Weekly Plan Reminder</b>\n\n"
                     . "Dear <b>{$safeHeadName}</b> ({$safeDeptName}),\n\n"
                     . "You currently have <b>0 operational tasks scheduled</b> for <b>Week {$week}</b> ({$month} {$year}).\n\n"
                     . "👉 <b>Action Needed Today:</b>\n"
                     . "Please log in to the portal to schedule your department's weekly task plan so execution can be tracked and evaluated by the General Manager.\n\n"
                     . "☕ <i>Kaldis Coffee PLC — Planning & Operational Governance</i>\n"
                     . "📅 <i>" . format_ethiopian_datetime() . "</i>";

                $buttons = [
                    [['text' => "📝 Schedule Week {$week} Plan", 'url' => $planUrl]]
                ];
            } elseif ($doneCount === $totalCount && $totalCount > 0) {
                $msg = "🌟 <b>Kaldis Coffee PLC — Excellent Work!</b>\n\n"
                     . "Dear <b>{$safeHeadName}</b> ({$safeDeptName}),\n\n"
                     . "Outstanding progress! All <b>{$totalCount} scheduled tasks</b> for <b>Week {$week}</b> are currently completed and marked as 🟢 <b>DONE</b>.\n\n"
                     . "📊 <b>Week {$week} Execution Summary:</b>\n"
                     . "• Completed Deliverables: 🟢 <b>{$doneCount} of {$totalCount} DONE (100%)</b>\n\n"
                     . "If your team delivers additional accomplishments this week, feel free to add them in the performance portal before Monday's deadline.\n\n"
                     . "☕ <i>Kaldis Coffee PLC — Planning & Operational Governance</i>\n"
                     . "📅 <i>" . format_ethiopian_datetime() . "</i>";

                $buttons = [
                    [['text' => "📊 View Performance", 'url' => $perfUrl]],
                    [['text' => "📋 View Week {$week} Plan", 'url' => $planUrl]]
                ];
            } else {
                $pctDone = round(($doneCount / $totalCount) * 100);
                $msg = "☕ <b>Kaldis Coffee PLC — Daily Task Execution & Progress Reminder</b>\n\n"
                     . "Dear <b>{$safeHeadName}</b> ({$safeDeptName}),\n\n"
                     . "This is your daily progress reminder to review and advance your team's deliverables for <b>Week {$week}</b> ({$month} {$year}).\n\n"
                     . "📊 <b>Active Week {$week} Task Status:</b>\n"
                     . "• Total Planned Tasks: <b>{$totalCount}</b>\n"
                     . "• Completed: 🟢 <b>{$doneCount} DONE</b> ({$pctDone}%)\n"
                     . "• Pending / In Progress: 🟡 <b>{$pendingCount} tasks</b>\n"
                     . ($notDoneCount > 0 ? "• Marked NOT DONE: 🔴 <b>{$notDoneCount} tasks</b>\n" : "")
                     . "\n"
                     . "🎯 <b>Action Needed Today:</b>\n"
                     . "1️⃣ Work on and finish your ongoing weekly tasks.\n"
                     . "2️⃣ <b>Update completed tasks to DONE</b> in the portal so your weekly score reflects actual achievements.\n"
                     . "3️⃣ Ensure everything is up to date ahead of Monday's 6:30 LT deadline.\n\n"
                     . "☕ <i>Kaldis Coffee PLC — Planning & Operational Governance</i>\n"
                     . "📅 <i>" . format_ethiopian_datetime() . "</i>";

                $buttons = [
                    [['text' => "🟢 Update Completed Tasks to DONE", 'url' => $perfUrl]],
                    [['text' => "📋 View Week {$week} Plan", 'url' => $planUrl]]
                ];
            }

            // In-app notification for Department Head
            if (!$dryRun && $head) {
                try {
                    DB::table('notifications')->insert([
                        'user_id' => $head->id,
                        'department_id' => $dept->id,
                        'title' => "Weekly Execution Reminder (Week {$week})",
                        'message' => "Remember to work on your weekly deliverables and mark finished tasks as DONE in the portal ({$doneCount}/{$totalCount} completed).",
                        'notification_type' => 'WEEKLY_PROGRESS_REMINDER',
                        'created_at' => now(),
                    ]);
                } catch (\Throwable $e) {}
            }

            // Send Telegram message if configured
            if ($telegram->isConfigured() && !empty($chatId)) {
                $this->line("- Reminding Progress: {$dept->department_name} ({$safeHeadName}) [Done: {$doneCount}/{$totalCount}] -> Chat {$chatId}");

                if (!$dryRun) {
                    $replyMarkup = ['inline_keyboard' => $buttons];
                    $res = $telegram->sendMessage($chatId, $msg, $replyMarkup);
                    if (!empty($res['ok'])) {
                        $sentCount++;
                    } else {
                        $this->warn("  [!] Telegram error to {$safeDeptName} ({$chatId}): " . ($res['description'] ?? 'Delivery failed'));
                    }
                } else {
                    $sentCount++;
                }
            } else {
                $skippedCount++;
            }
        }

        AuditLog::log(
            'WEEKLY_PROGRESS_REMINDER',
            'system',
            "Dispatched mid-week progress reminders for Week {$week} ({$month} {$year}) to {$sentCount} department heads. Skipped without chat ID: {$skippedCount}."
        );

        $this->info("Completed. Progress alerts dispatched to {$sentCount} Department Heads ({$skippedCount} skipped without chat ID).");
        return Command::SUCCESS;
    }
}

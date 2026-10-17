<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Submission;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class WeeklyReminderCommand extends Command
{
    protected $signature = 'kaldis:weekly-reminder 
                            {--week= : Specific week number (1 to 5)}
                            {--month= : Specific Ethiopian Month}
                            {--year= : Specific Planning Year}';
    protected $description = 'Send weekly performance report submission reminders to department heads via Telegram & system logs';

    public function handle(TelegramService $telegram)
    {
        $year = $this->option('year') ?: kaldis_setting('current_planning_year', config('kaldis.current_planning_year', '2019 E.C.'));
        $month = $this->option('month') ?: kaldis_setting('current_planning_month', config('kaldis.current_planning_month', 'Meskerem'));
        $week = (int)($this->option('week') ?: (function_exists('get_current_planning_week') ? get_current_planning_week() : 1) ?: 1);

        $this->info("Running weekly report reminders for {$month} {$year} (Week {$week})...");

        $departments = Department::where('active', 1)->with('head')->get();
        $remindedCount = 0;
        $telegramSentCount = 0;

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

                // Send Telegram reminder if bot is configured and department head has chat ID
                if ($telegram->isConfigured() && $dept->head && !empty($dept->head->telegram_chat_id)) {
                    $msg = "⏰ *Kaldis Weekly Performance Reminder*\n\n"
                         . "Dear {$dept->head->full_name},\n"
                         . "This is a friendly reminder that the weekly report for *{$dept->department_name}* ({$month} {$year}, Week {$week}) is pending submission.\n\n"
                         . "Please review your tasks and submit on time.\n\n"
                         . "☕ _Kaldis Coffee PLC — Planning & Performance_";

                    $res = $telegram->sendMessage($dept->head->telegram_chat_id, $msg);
                    if (!empty($res['ok'])) {
                        $telegramSentCount++;
                    }
                }
            }
        }

        AuditLog::log('CRON_REMINDER', 'system', "Sent weekly submission reminder to {$remindedCount} departments (Week {$week}). Telegram delivered: {$telegramSentCount}");
        $this->info("Completed. {$remindedCount} departments reminded ({$telegramSentCount} via Telegram).");

        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Escalation;
use App\Models\MonthlyPlan;
use App\Models\MonthlySubmission;
use App\Models\User;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MonthlyPlanReminderCommand extends Command
{
    protected $signature = 'kaldis:monthly-reminder 
                            {--type=reminder : Either "reminder" (Days 1-4) or "cutoff" (Day 5 17:00)}
                            {--month= : Specific Ethiopian Month}
                            {--year= : Specific Planning Year}';

    protected $description = 'Automate monthly plan reminders to department heads and executive compliance summaries to General Management via Telegram';

    public function handle(TelegramService $telegram): int
    {
        $year = $this->option('year') ?: kaldis_setting('current_planning_year', config('kaldis.current_planning_year', '2019 E.C.'));
        $month = $this->option('month') ?: kaldis_setting('current_planning_month', config('kaldis.current_planning_month', 'Meskerem'));
        $type = strtolower((string)$this->option('type')) === 'cutoff' ? 'cutoff' : 'reminder';

        $this->info("Executing Monthly Plan Automated Check [Type: {$type}] for {$month} {$year}...");

        $departments = Department::where('active', 1)->with('head')->get();
        $totalDepts = $departments->count();

        $submittedDepts = [];
        $unsubmittedDepts = [];
        $remindedCount = 0;
        $escalatedCount = 0;

        foreach ($departments as $dept) {
            $mSub = MonthlySubmission::where('department_id', $dept->id)
                ->where('year', $year)
                ->where('month', $month)
                ->first();

            $deptPlansCount = MonthlyPlan::where('department_id', $dept->id)
                ->where('year', $year)
                ->where('month', $month)
                ->count();

            $isSubmitted = false;
            if ($mSub && in_array($mSub->status, ['ON_TIME', 'LATE'])) {
                $isSubmitted = true;
            } elseif ($deptPlansCount > 0) {
                $isSubmitted = true;
            }

            if ($isSubmitted) {
                $statusLabel = ($mSub && $mSub->status === 'LATE') ? 'LATE' : 'ON_TIME';
                $submittedDepts[] = [
                    'dept' => $dept,
                    'status' => $statusLabel,
                    'plans_count' => $deptPlansCount,
                ];
            } else {
                $unsubmittedDepts[] = [
                    'dept' => $dept,
                    'plans_count' => 0,
                ];

                $head = $dept->head;

                if ($type === 'reminder') {
                    // Send routine reminder to Department Head
                    if ($telegram->isConfigured() && $head && !empty($head->telegram_chat_id)) {
                        $msg = "⏰ <b>Kaldis Coffee PLC — Monthly Plan Reminder</b>\n\n"
                             . "Dear <b>{$head->full_name}</b> ({$dept->department_name}),\n\n"
                             . "This is a reminder to prepare and submit your department's Monthly Plan Package for <b>{$month} {$year}</b>.\n\n"
                             . "📅 <b>Submission Cutoff:</b> Day 5 of the month at 17:00\n"
                             . "🎯 Please align your departmental operational tasks with the active Annual Strategic Goals.\n\n"
                             . "🌐 <b>Submit via Portal:</b>\n"
                             . "<code>" . url('/monthly/plans') . "</code>\n\n"
                             . "☕ <i>Kaldis Coffee PLC — Planning & Performance</i>";

                        $res = $telegram->sendMessage($head->telegram_chat_id, $msg);
                        if (!empty($res['ok'])) {
                            $remindedCount++;
                        }
                    }
                } else {
                    // Cutoff: Flag MISSING, create Escalation, notify Department Head
                    MonthlySubmission::updateOrCreate([
                        'department_id' => $dept->id,
                        'year' => $year,
                        'month' => $month,
                    ], [
                        'status' => 'MISSING',
                        'approval_status' => 'PENDING',
                        'deadline_at' => Carbon::now(),
                        'remarks' => 'Monthly plan submission cutoff expired without submission.',
                    ]);

                    Escalation::firstOrCreate([
                        'department_id' => $dept->id,
                        'year' => $year,
                        'month' => $month,
                        'week_number' => 1,
                        'escalation_level' => 1,
                    ], [
                        'missed_weeks_count' => 1,
                        'channel' => 'TELEGRAM',
                        'status' => 'OPEN',
                        'triggered_at' => Carbon::now(),
                    ]);

                    if ($telegram->isConfigured() && $head && !empty($head->telegram_chat_id)) {
                        $msg = "🚨 <b>Kaldis Monthly Plan Cutoff Escalation</b>\n\n"
                             . "Attention: <b>{$head->full_name}</b> ({$dept->department_name})\n\n"
                             . "The official deadline for submitting your department's Monthly Plan Package for <b>{$month} {$year}</b> has expired.\n\n"
                             . "⚠️ A <b>Level 1 Escalation</b> has been registered in the system.\n"
                             . "Please contact General Management and submit your package immediately.\n\n"
                             . "☕ <i>Kaldis Coffee PLC — Office of the General Manager</i>";

                        $res = $telegram->sendMessage($head->telegram_chat_id, $msg);
                        if (!empty($res['ok'])) {
                            $escalatedCount++;
                        }
                    }
                }
            }
        }

        // Summary counts
        $onTimeCount = count(array_filter($submittedDepts, fn($d) => $d['status'] === 'ON_TIME'));
        $lateCount = count(array_filter($submittedDepts, fn($d) => $d['status'] === 'LATE'));
        $missingCount = count($unsubmittedDepts);
        $totalPlansCompany = MonthlyPlan::where('year', $year)->where('month', $month)->count();
        $complianceRate = $totalDepts > 0 ? round((count($submittedDepts) / $totalDepts) * 100, 1) : 0;

        // Dispatch Executive Summary Report to General Management (GM)
        $gmSentCount = 0;
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

            // Also check escalation_recipients_gm
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
                $headerTitle = $type === 'cutoff' 
                    ? "📑 <b>Kaldis Executive Monthly Plan Cutoff Report</b>" 
                    : "📊 <b>Kaldis Monthly Planning Progress Summary</b>";

                $gmReport = "{$headerTitle}\n";
                $gmReport .= "📅 <b>Period:</b> {$month} {$year}\n";
                $gmReport .= "⏰ <b>Evaluation Time:</b> " . now()->format('M d, Y h:i A') . "\n\n";

                $gmReport .= "📈 <b>Company Planning Status:</b>\n";
                $gmReport .= "• Total Active Departments: <b>{$totalDepts}</b>\n";
                $gmReport .= "• 🟢 Submitted (On-Time): <b>{$onTimeCount}</b>\n";
                if ($lateCount > 0) {
                    $gmReport .= "• 🟡 Submitted (Late): <b>{$lateCount}</b>\n";
                }
                $gmReport .= "• 🔴 Missing / Delinquent: <b>{$missingCount}</b>\n";
                $gmReport .= "• 📋 Total Plans Formulated: <b>{$totalPlansCompany}</b>\n";
                $gmReport .= "• 🎯 Planning Compliance Rate: <b>{$complianceRate}%</b>\n\n";

                if (!empty($submittedDepts)) {
                    $gmReport .= "🟢 <b>Submitted Departments:</b>\n";
                    foreach ($submittedDepts as $item) {
                        $badge = $item['status'] === 'LATE' ? '🟡 (Late)' : '🟢 (On-Time)';
                        $gmReport .= "• {$item['dept']->department_name} {$badge} — <i>{$item['plans_count']} plans</i>\n";
                    }
                    $gmReport .= "\n";
                }

                if (!empty($unsubmittedDepts)) {
                    $gmReport .= "🔴 <b>Delinquent Departments (NOT SUBMITTED):</b>\n";
                    foreach ($unsubmittedDepts as $item) {
                        $headName = $item['dept']->head ? $item['dept']->head->full_name : 'No Head Assigned';
                        $gmReport .= "• <b>{$item['dept']->department_name}</b> ({$headName})\n";
                    }
                    $gmReport .= "\n";
                }

                $dashboardUrl = url('/management/dashboard?year=' . urlencode($year) . '&month=' . urlencode($month));
                $gmReport .= "🌐 <a href=\"{$dashboardUrl}\"><b>Open GM Executive Dashboard & Vouchers</b></a>\n";
                $gmReport .= "☕ <i>Kaldis Coffee PLC — Executive Operations</i>";

                foreach ($gmChatIds as $cid) {
                    $res = $telegram->sendMessage($cid, $gmReport);
                    if (!empty($res['ok'])) {
                        $gmSentCount++;
                    }
                }
            }
        }

        AuditLog::log(
            'MONTHLY_REMINDER_CHECK',
            'system',
            "Automated monthly check [{$type}] for {$month} {$year}. Submitted: " . count($submittedDepts) . ", Missing: {$missingCount}. Telegram delivered: Head alerts={$remindedCount}/{$escalatedCount}, GM summaries={$gmSentCount}"
        );

        $this->info("Completed. Submitted: " . count($submittedDepts) . ", Missing: {$missingCount}. GM Executive Telegrams delivered: {$gmSentCount}");
        return Command::SUCCESS;
    }
}

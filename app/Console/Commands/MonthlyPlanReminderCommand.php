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
                            {--type=reminder : Either "reminder" (Days before cutoff) or "cutoff" (Day 26 17:00)}
                            {--stage= : Reminder stage: "day25_morning", "day25_afternoon", "day26_morning", "day26_afternoon"}
                            {--month= : Specific Ethiopian Month}
                            {--year= : Specific Planning Year}';

    protected $description = 'Automate monthly plan reminders to department heads and executive compliance summaries to General Management via Telegram';

    public function handle(TelegramService $telegram): int
    {
        $year = $this->option('year') ?: kaldis_setting('current_planning_year', config('kaldis.current_planning_year', '2019 E.C.'));
        $month = $this->option('month') ?: kaldis_setting('current_planning_month', config('kaldis.current_planning_month', 'Meskerem'));
        $type = strtolower((string)$this->option('type')) === 'cutoff' ? 'cutoff' : 'reminder';
        
        $deadlineInfo = get_monthly_plan_deadline_info($month, $year);
        $deadlineDay = $deadlineInfo['deadline_day'];
        $deadlineTime = $deadlineInfo['deadline_time'];
        $deadlineFormatted = $deadlineInfo['deadline_formatted'];

        $this->info("Executing Monthly Plan Automated Check [Type: {$type}] for {$month} {$year} (Cutoff: {$deadlineFormatted})...");

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
            $statusLabel = 'NOT_SUBMITTED';

            if ($mSub && ($mSub->isDelayApproved() || $mSub->status === 'DELAY_APPROVED')) {
                $isSubmitted = true;
                $statusLabel = 'DELAY_APPROVED';
            } elseif ($mSub && !empty($mSub->submitted_at) && in_array($mSub->status, ['ON_TIME', 'LATE', 'SUBMITTED', 'APPROVED'])) {
                $isSubmitted = true;
                $statusLabel = $mSub->status;
            }

            if ($isSubmitted) {
                $submittedDepts[] = [
                    'dept' => $dept,
                    'status' => $statusLabel,
                    'plans_count' => $deptPlansCount,
                    'delay_reason' => $mSub->delay_reason ?? null,
                ];
            } else {
                $unsubmittedDepts[] = [
                    'dept' => $dept,
                    'plans_count' => $deptPlansCount,
                ];

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
                    $tu = \Illuminate\Support\Facades\DB::table('telegram_users')->where('user_id', $head->id)->where('is_active', 1)->first();
                    if ($tu && !empty($tu->telegram_user_id) && $tu->telegram_user_id !== '123456789') {
                        $chatId = trim((string)$tu->telegram_user_id);
                    }
                }

                $safeDeptName = htmlspecialchars($dept->department_name, ENT_QUOTES, 'UTF-8');
                $safeHeadName = htmlspecialchars($head ? $head->full_name : 'Department Head', ENT_QUOTES, 'UTF-8');

                if ($type === 'reminder') {
                    // Send advance routine reminder to Department Head
                    if ($telegram->isConfigured() && !empty($chatId)) {
                        $portalUrl = url('/monthly/plans?year=' . urlencode($year) . '&month=' . urlencode($month));
                        $stage = strtolower((string)($this->option('stage') ?? ''));

                        switch ($stage) {
                            case 'day25_morning':
                                $stageTitle = "⏰ <b>Kaldis Monthly Plan Reminder (Day 25 Morning)</b>";
                                $stageLead = "This is your <b>1st advance reminder</b> to prepare and submit your department's Monthly Plan Package for <b>{$month} {$year}</b>.";
                                break;
                            case 'day25_afternoon':
                                $stageTitle = "⏰ <b>Kaldis Monthly Plan 2nd Reminder (Day 25 Afternoon)</b>";
                                $stageLead = "The official deadline for your <b>{$month} {$year}</b> Monthly Plan Package is <b>tomorrow (Day 26) at 5:00 PM (11:00 LT)</b>.";
                                break;
                            case 'day26_morning':
                                $stageTitle = "⚠️ <b>Kaldis Monthly Plan Final Day Reminder (Day 26 Morning)</b>";
                                $stageLead = "<b>Today is the final day</b> to submit your Monthly Plan Package for <b>{$month} {$year}</b>. The deadline is <b>today at 5:00 PM (11:00 LT)</b>.";
                                break;
                            case 'day26_afternoon':
                                $stageTitle = "🚨 <b>URGENT: Monthly Plan Cutoff in 3 Hours! (Day 26 14:00)</b>";
                                $stageLead = "Your department's Monthly Plan Package for <b>{$month} {$year}</b> has <b>NOT</b> been submitted yet. Final cutoff is <b>today at 5:00 PM (11:00 LT)</b>.";
                                break;
                            default:
                                $stageTitle = "⏰ <b>Kaldis Coffee PLC — Monthly Plan Reminder</b>";
                                $stageLead = "This is a reminder to prepare and submit your department's Monthly Plan Package for <b>{$month} {$year}</b>.";
                                break;
                        }

                        $draftStatusNote = ($deptPlansCount > 0)
                            ? "📝 <i>You have <b>{$deptPlansCount} drafted plan(s)</b> in the portal. Please review and click <b>'Submit to GM'</b> before the cutoff!</i>\n\n"
                            : "⚠️ <i>No monthly plans have been formulated yet. Please add your plans and submit to GM.</i>\n\n";

                        $msg = "{$stageTitle}\n\n"
                             . "Dear <b>{$safeHeadName}</b> ({$safeDeptName}),\n\n"
                             . "{$stageLead}\n\n"
                             . "📅 <b>Submission Cutoff:</b> {$deadlineFormatted}\n"
                             . "🎯 Please align your departmental operational tasks with the active Annual Strategic Goals.\n\n"
                             . $draftStatusNote
                             . "☕ <i>Kaldis Coffee PLC — Planning & Performance</i>\n"
                             . "📅 <i>" . format_ethiopian_datetime() . "</i>";

                        $replyMarkup = [
                            'inline_keyboard' => [
                                [
                                    ['text' => '🌐 Open Monthly Plans & Submit to GM', 'url' => $portalUrl]
                                ]
                            ]
                        ];

                        $res = $telegram->sendMessage($chatId, $msg, $replyMarkup);
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
                        'deadline_at' => $deadlineInfo['deadline_carbon'],
                        'remarks' => "Monthly plan submission cutoff ({$deadlineFormatted}) expired without submission.",
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

                    if ($telegram->isConfigured() && !empty($chatId)) {
                        $portalUrl = url('/monthly/plans?year=' . urlencode($year) . '&month=' . urlencode($month));
                        $msg = "🚨 <b>Kaldis Monthly Plan Cutoff Escalation</b>\n\n"
                             . "Attention: <b>{$safeHeadName}</b> ({$safeDeptName})\n\n"
                             . "The official deadline for submitting your department's Monthly Plan Package for <b>{$month} {$year}</b> (Cutoff: {$deadlineFormatted}) has expired.\n\n"
                             . "⚠️ A <b>Level 1 Escalation</b> has been registered in the system.\n"
                             . "Please contact General Management and submit your package immediately.\n\n"
                             . "☕ <i>Kaldis Coffee PLC — Office of the General Manager</i>\n"
                             . "📅 <i>" . format_ethiopian_datetime() . "</i>";

                        $replyMarkup = [
                            'inline_keyboard' => [
                                [
                                    ['text' => '🌐 Open Monthly Plans in Portal', 'url' => $portalUrl]
                                ]
                            ]
                        ];

                        $res = $telegram->sendMessage($chatId, $msg, $replyMarkup);
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
        $delayApprovedCount = count(array_filter($submittedDepts, fn($d) => $d['status'] === 'DELAY_APPROVED'));
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
                if (!empty($gm->telegram_chat_id) && $gm->telegram_chat_id !== '123456789') {
                    $gmChatIds[] = trim((string)$gm->telegram_chat_id);
                }
            }

            // Also check telegram_users table for linked GM accounts
            try {
                $linkedGmIds = \Illuminate\Support\Facades\DB::table('telegram_users')
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

            // Also check escalation_recipients_gm
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

            if (!empty($gmChatIds)) {
                $headerTitle = $type === 'cutoff' 
                    ? "📑 <b>Kaldis Executive Monthly Plan Cutoff Report</b>" 
                    : "📊 <b>Kaldis Monthly Planning Progress Summary</b>";

                $gmReport = "{$headerTitle}\n";
                $gmReport .= "📅 <b>Period:</b> {$month} {$year}\n";
                $gmReport .= "⏰ <b>Evaluation Time:</b> " . format_ethiopian_datetime() . "\n\n";

                $gmReport .= "📈 <b>Company Planning Status:</b>\n";
                $gmReport .= "• Total Active Departments: <b>{$totalDepts}</b>\n";
                $gmReport .= "• 🟢 Submitted (On-Time): <b>{$onTimeCount}</b>\n";
                if ($lateCount > 0) {
                    $gmReport .= "• 🟡 Submitted (Late): <b>{$lateCount}</b>\n";
                }
                if ($delayApprovedCount > 0) {
                    $gmReport .= "• 🔵 Delay Approved (Excused): <b>{$delayApprovedCount}</b>\n";
                }
                $gmReport .= "• 🔴 Missing / Delinquent: <b>{$missingCount}</b>\n";
                $gmReport .= "• 📋 Total Plans Formulated: <b>{$totalPlansCompany}</b>\n";
                $gmReport .= "• 🎯 Planning Compliance Rate: <b>{$complianceRate}%</b>\n\n";

                if (!empty($submittedDepts)) {
                    $gmReport .= "🟢 <b>Submitted & Excused Departments:</b>\n";
                    foreach ($submittedDepts as $item) {
                        $safeDept = htmlspecialchars($item['dept']->department_name, ENT_QUOTES, 'UTF-8');
                        $badge = $item['status'] === 'LATE' ? '🟡 (Late)' : ($item['status'] === 'DELAY_APPROVED' ? '🔵 (Delay Approved by GM)' : '🟢 (On-Time)');
                        $gmReport .= "• {$safeDept} {$badge} — <i>{$item['plans_count']} plans</i>\n";
                    }
                    $gmReport .= "\n";
                }

                if (!empty($unsubmittedDepts)) {
                    $gmReport .= "🔴 <b>Delinquent Departments (NOT SUBMITTED):</b>\n";
                    foreach ($unsubmittedDepts as $item) {
                        $safeDept = htmlspecialchars($item['dept']->department_name, ENT_QUOTES, 'UTF-8');
                        $headName = $item['dept']->head ? $item['dept']->head->full_name : 'No Head Assigned';
                        $safeHead = htmlspecialchars($headName, ENT_QUOTES, 'UTF-8');
                        $draftNote = ($item['plans_count'] > 0) ? " — <i>{$item['plans_count']} drafted (not submitted)</i>" : " — <i>0 plans</i>";
                        $gmReport .= "• <b>{$safeDept}</b> ({$safeHead}){$draftNote}\n";
                    }
                    $gmReport .= "\n";
                }

                $dashboardUrl = url('/management/dashboard?year=' . urlencode($year) . '&month=' . urlencode($month));
                $gmReport .= "📌 <b>Submission Cutoff:</b> {$deadlineFormatted}\n\n";
                $gmReport .= "☕ <i>Kaldis Coffee PLC — Executive Operations</i>";

                $replyMarkup = [
                    'inline_keyboard' => [
                        [
                            ['text' => '🌐 Open GM Executive Dashboard & Vouchers', 'url' => $dashboardUrl]
                        ]
                    ]
                ];

                foreach ($gmChatIds as $cid) {
                    $res = $telegram->sendMessage($cid, $gmReport, $replyMarkup);
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

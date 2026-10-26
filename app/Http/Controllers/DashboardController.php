<?php

namespace App\Http\Controllers;

use App\Models\AnnualGoal;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\MonthlyPlan;
use App\Models\MonthlyStrategyActivation;
use App\Models\MonthlySubmission;
use App\Models\Submission;
use App\Models\WeeklyReport;
use App\Models\WeeklyTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        return $this->overview($request);
    }

    public function overview(Request $request)
    {
        $user = Auth::user();
        $period = get_ethiopian_period();
        $selectedYear = $request->query('year', kaldis_setting('current_planning_year', config('kaldis.current_planning_year', $period['year'] . ' E.C.')));
        $selectedMonth = $request->query('month', kaldis_setting('current_planning_month', config('kaldis.current_planning_month', $period['month'])));
        $selectedWeek = (int)$request->query('week', $period['week'] ?? 1);

        $ethToday = function_exists('gregorian_to_ethiopian_date')
            ? gregorian_to_ethiopian_date((int)date('Y'), (int)date('n'), (int)date('j'))
            : ['year' => 2019, 'month' => 2, 'month_name' => 'Tikimt', 'day' => (int)date('d')];
        $currentEthDay = (int)($ethToday['day'] ?? 1);

        $monthlyDeadlineDay = (int)kaldis_setting('monthly_plan_deadline_day', 26);
        $monthlyDeadlineTime = kaldis_setting('monthly_plan_deadline_time', '17:00');
        $weeklyDeadlineDay = kaldis_setting('weekly_deadline_day', 'Monday');
        $weeklyDeadlineTime = kaldis_setting('weekly_deadline_time', '12:30');

        $monthlyDaysLeft = max(0, $monthlyDeadlineDay - $currentEthDay);

        $totalDepartments = Department::where('active', 1)->count();
        $totalGoals = AnnualGoal::where('year', $selectedYear)->where('status', 'ACTIVE')->count();

        // Department-specific statistics
        $userDeptId = $user->department_id;
        $userDept = $user->department;
        $deptTasksCount = 0;
        $deptDoneCount = 0;
        $deptNotDoneCount = 0;
        $deptMonthlyPlansCount = 0;
        $submissionStatus = 'DRAFT';
        $weeklyReport = null;
        $monthlySubmission = null;

        if ($userDeptId) {
            $deptTasksCount = WeeklyTask::where('department_id', $userDeptId)
                ->where('year', $selectedYear)
                ->where('month', $selectedMonth)
                ->where('week_number', $selectedWeek)
                ->count();

            $deptDoneCount = DB::table('weekly_tasks as wt')
                ->join('weekly_task_results as wtr', 'wt.id', '=', 'wtr.weekly_task_id')
                ->where('wt.department_id', $userDeptId)
                ->where('wt.year', $selectedYear)
                ->where('wt.month', $selectedMonth)
                ->where('wt.week_number', $selectedWeek)
                ->where('wtr.result', 'DONE')
                ->count();

            $deptNotDoneCount = max(0, $deptTasksCount - $deptDoneCount);

            $deptMonthlyPlansCount = MonthlyPlan::where('department_id', $userDeptId)
                ->where('year', $selectedYear)
                ->where('month', $selectedMonth)
                ->count();

            $weeklyReport = WeeklyReport::where('department_id', $userDeptId)
                ->where('year', $selectedYear)
                ->where('month', $selectedMonth)
                ->where('week_number', $selectedWeek)
                ->first();

            if ($weeklyReport && $weeklyReport->status === 'SUBMITTED') {
                $submissionStatus = $weeklyReport->submission_status ?? 'ON_TIME';
            }

            $monthlySubmission = MonthlySubmission::where('department_id', $userDeptId)
                ->where('year', $selectedYear)
                ->where('month', $selectedMonth)
                ->first();
        }

        $completionPct = $deptTasksCount > 0 ? round(($deptDoneCount / $deptTasksCount) * 100) : 0;

        // Enterprise-wide metrics
        $allMonthlyPlansCount = MonthlyPlan::where('year', $selectedYear)->where('month', $selectedMonth)->count();
        $submittedMonthlyPackagesCount = MonthlySubmission::where('year', $selectedYear)->where('month', $selectedMonth)->where('status', 'SUBMITTED')->count();
        $submittedWeeklyReportsCount = WeeklyReport::where('year', $selectedYear)->where('month', $selectedMonth)->where('week_number', $selectedWeek)->where('status', 'SUBMITTED')->count();

        $recentActivities = AuditLog::with('user')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $generalManager = \App\Models\User::whereHas('role', fn($r) => $r->whereIn('name', ['gm_management', 'gm']))->first();
        $departments = Department::where('active', 1)->with('head')->orderBy('id')->get();

        return view('overview', compact(
            'user',
            'period',
            'selectedYear',
            'selectedMonth',
            'selectedWeek',
            'currentEthDay',
            'monthlyDeadlineDay',
            'monthlyDeadlineTime',
            'weeklyDeadlineDay',
            'weeklyDeadlineTime',
            'monthlyDaysLeft',
            'totalDepartments',
            'totalGoals',
            'userDeptId',
            'userDept',
            'deptTasksCount',
            'deptDoneCount',
            'deptNotDoneCount',
            'completionPct',
            'deptMonthlyPlansCount',
            'submissionStatus',
            'weeklyReport',
            'monthlySubmission',
            'allMonthlyPlansCount',
            'submittedMonthlyPackagesCount',
            'submittedWeeklyReportsCount',
            'recentActivities',
            'generalManager',
            'departments'
        ));
    }

    public function gm(Request $request)
    {
        $user = Auth::user();
        if ($user->isDeptHead()) {
            return redirect()->route('dashboard.department');
        }

        $selectedYear = $request->query('year', kaldis_setting('current_planning_year', config('kaldis.current_planning_year')));
        $selectedMonth = $request->query('month', kaldis_setting('current_planning_month', config('kaldis.current_planning_month')));

        $totalDepartments = Department::where('active', 1)->count();
        $totalStrategies = AnnualGoal::where('year', $selectedYear)->where('status', 'ACTIVE')->count();

        // Count distinct active goals for this month
        $activeStrategiesCount = MonthlyStrategyActivation::where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->where('active', 'YES')
            ->distinct('annual_goal_id')
            ->count('annual_goal_id');

        $totalMonthlyPlans = MonthlyPlan::where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->count();

        // Weekly Tasks execution statistics
        $tasksQuery = WeeklyTask::where('year', $selectedYear)
            ->where('month', $selectedMonth);

        $totalTasks = (clone $tasksQuery)->count();

        $doneTasks = DB::table('weekly_tasks as wt')
            ->join('weekly_task_results as wtr', 'wt.id', '=', 'wtr.weekly_task_id')
            ->where('wt.year', $selectedYear)
            ->where('wt.month', $selectedMonth)
            ->where('wtr.result', 'DONE')
            ->count();

        $notDoneTasks = DB::table('weekly_tasks as wt')
            ->join('weekly_task_results as wtr', 'wt.id', '=', 'wtr.weekly_task_id')
            ->where('wt.year', $selectedYear)
            ->where('wt.month', $selectedMonth)
            ->where('wtr.result', 'NOT_DONE')
            ->count();

        $pendingTasks = max(0, $totalTasks - ($doneTasks + $notDoneTasks));

        // Submissions statistics
        $submissions = Submission::where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->get();

        $onTimeSubmissions = $submissions->where('status', 'ON_TIME')->count();
        $lateSubmissions = $submissions->where('status', 'LATE')->count();
        $delayApprovedSubmissions = $submissions->filter(fn($s) => $s->status === 'DELAY_APPROVED' || $s->delay_status === 'APPROVED')->count();
        $missingSubmissions = $submissions->filter(fn($s) => $s->status === 'MISSING' && $s->delay_status !== 'APPROVED')->count();
        $submittedDeptIds = $submissions->filter(fn($s) => in_array($s->status, ['ON_TIME', 'LATE', 'DELAY_APPROVED']) || $s->delay_status === 'APPROVED')->pluck('department_id')->unique()->toArray();
        $waitingSubmissions = max(0, $totalDepartments - count($submittedDeptIds));

        // Root cause breakdown
        $rootCauses = DB::table('reason_categories as rc')
            ->leftJoin('weekly_task_results as wtr', function ($join) use ($selectedYear, $selectedMonth) {
                $join->on('rc.id', '=', 'wtr.not_done_reason_id')
                    ->where('wtr.result', '=', 'NOT_DONE');
            })
            ->leftJoin('weekly_tasks as wt', 'wtr.weekly_task_id', '=', 'wt.id')
            ->where(function ($q) use ($selectedYear, $selectedMonth) {
                $q->whereNull('wt.id')
                  ->orWhere(function ($sub) use ($selectedYear, $selectedMonth) {
                      $sub->where('wt.year', $selectedYear)->where('wt.month', $selectedMonth);
                  });
            })
            ->select('rc.name as category_name', DB::raw('COUNT(wtr.id) as count'))
            ->groupBy('rc.id', 'rc.name')
            ->orderByDesc('count')
            ->get();

        // Department performance breakdown - optimized single aggregate query
        $departments = Department::where('active', 1)->orderBy('department_name')->get();
        $deptAggregates = DB::table('weekly_tasks as wt')
            ->leftJoin('weekly_task_results as wtr', 'wt.id', '=', 'wtr.weekly_task_id')
            ->where('wt.year', $selectedYear)
            ->where('wt.month', $selectedMonth)
            ->select(
                'wt.department_id',
                DB::raw('COUNT(wt.id) as total'),
                DB::raw("SUM(CASE WHEN wtr.result = 'DONE' THEN 1 ELSE 0 END) as done"),
                DB::raw("SUM(CASE WHEN wtr.result = 'NOT_DONE' THEN 1 ELSE 0 END) as not_done")
            )
            ->groupBy('wt.department_id')
            ->get()
            ->keyBy('department_id');

        $departmentPerformances = [];
        foreach ($departments as $dept) {
            $agg = $deptAggregates->get($dept->id);
            $dTotal = (int)($agg->total ?? 0);
            $dDone = (int)($agg->done ?? 0);
            $dNotDone = (int)($agg->not_done ?? 0);
            $rate = $dTotal > 0 ? round(($dDone / $dTotal) * 100, 1) : 0;

            $departmentPerformances[] = [
                'department' => $dept,
                'total' => $dTotal,
                'done' => $dDone,
                'not_done' => $dNotDone,
                'rate' => $rate,
            ];
        }

        $selectedWeek = (int)$request->query('week', (function_exists('get_current_planning_week') ? get_current_planning_week() : 1) ?: 1);

        // Monthly submissions by department
        $monthlySubmissions = \App\Models\MonthlySubmission::where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->get()
            ->keyBy('department_id');

        // All monthly plans across departments
        $allMonthlyPlans = MonthlyPlan::with(['department', 'annualGoal'])
            ->where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->orderBy('department_id')
            ->orderBy('plan_type', 'desc')
            ->get();

        $monthlyPlansByDept = $allMonthlyPlans->groupBy('department_id');

        // Weekly submissions & reports for selected week
        $weeklySubmissions = Submission::with('submitter')
            ->where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->where('week_number', $selectedWeek)
            ->get()
            ->keyBy('department_id');

        $weeklyReports = \App\Models\WeeklyReport::where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->where('week_number', $selectedWeek)
            ->get()
            ->keyBy('department_id');

        $weeklyPlans = \App\Models\WeeklyPlan::with('tasks')
            ->where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->where('week_number', $selectedWeek)
            ->get()
            ->keyBy('department_id');

        $pendingMonthlyDelays = MonthlySubmission::where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->where('delay_status', 'PENDING')
            ->count();

        $pendingWeeklyPlanDelays = \App\Models\WeeklyPlan::where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->where('week_number', $selectedWeek)
            ->where('delay_status', 'PENDING')
            ->count();

        $pendingWeeklyReportDelays = WeeklyReport::where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->where('week_number', $selectedWeek)
            ->where('delay_status', 'PENDING')
            ->count();

        $totalPendingDelays = $pendingMonthlyDelays + $pendingWeeklyPlanDelays + $pendingWeeklyReportDelays;

        // Stepper Phase logic
        $currentPhase = 1;
        if ($activeStrategiesCount > 0 && $totalMonthlyPlans > 0 && $totalTasks > 0) {
            $currentPhase = 3;
        } elseif ($activeStrategiesCount > 0 && $totalMonthlyPlans > 0) {
            $currentPhase = 2;
        }

        return view('dashboard.gm', compact(
            'selectedYear',
            'selectedMonth',
            'selectedWeek',
            'totalDepartments',
            'totalStrategies',
            'activeStrategiesCount',
            'totalMonthlyPlans',
            'totalTasks',
            'doneTasks',
            'notDoneTasks',
            'pendingTasks',
            'onTimeSubmissions',
            'lateSubmissions',
            'delayApprovedSubmissions',
            'waitingSubmissions',
            'missingSubmissions',
            'rootCauses',
            'departmentPerformances',
            'monthlySubmissions',
            'monthlyPlansByDept',
            'allMonthlyPlans',
            'weeklySubmissions',
            'weeklyReports',
            'weeklyPlans',
            'pendingMonthlyDelays',
            'pendingWeeklyPlanDelays',
            'pendingWeeklyReportDelays',
            'totalPendingDelays',
            'currentPhase'
        ));
    }

    public function reviewDelayReason(Request $request)
    {
        $user = Auth::user();
        if (!$user->isGM() && !$user->isSuperAdmin()) {
            abort(403, 'Only General Management can review and approve delay reasons.');
        }

        $validated = $request->validate([
            'type' => 'required|string|in:MONTHLY,monthly,WEEKLY_PLAN,weekly_plan,WEEKLY_PERFORMANCE,weekly_performance,QUARTERLY,quarterly',
            'action' => 'required|string|in:APPROVE,approve,REJECT,reject',
            'id' => 'nullable|integer',
            'department_id' => 'nullable|integer',
            'year' => 'nullable|string',
            'month' => 'nullable|string',
            'week_number' => 'nullable|integer',
            'notes' => 'nullable|string|max:1000',
        ]);

        $type = strtoupper($validated['type']);
        $action = strtoupper($validated['action']);
        $notes = $validated['notes'] ?? null;
        $isApproved = $action === 'APPROVE';
        $statusValue = $isApproved ? 'APPROVED' : 'REJECTED';

        $department = null;
        $periodLabel = '';

        if ($type === 'MONTHLY') {
            $submission = null;
            if (!empty($validated['id'])) {
                $submission = MonthlySubmission::find($validated['id']);
            }
            if (!$submission && !empty($validated['department_id'])) {
                $submission = MonthlySubmission::where('department_id', $validated['department_id'])
                    ->where('year', $validated['year'] ?? kaldis_setting('current_planning_year', config('kaldis.current_planning_year')))
                    ->where('month', $validated['month'] ?? kaldis_setting('current_planning_month', config('kaldis.current_planning_month')))
                    ->first();
            }

            if (!$submission && !empty($validated['department_id'])) {
                $submission = MonthlySubmission::create([
                    'department_id' => $validated['department_id'],
                    'year' => $validated['year'] ?? kaldis_setting('current_planning_year', config('kaldis.current_planning_year')),
                    'month' => $validated['month'] ?? kaldis_setting('current_planning_month', config('kaldis.current_planning_month')),
                    'status' => $isApproved ? 'DELAY_APPROVED' : 'MISSING',
                    'delay_status' => $statusValue,
                    'delay_approved_by' => $user->id,
                    'delay_approved_at' => now(),
                    'delay_gm_notes' => $notes,
                ]);
            }

            if (!$submission) {
                return back()->with('error', 'Monthly submission record not found.');
            }

            $submission->delay_status = $statusValue;
            $submission->delay_approved_by = $user->id;
            $submission->delay_approved_at = now();
            $submission->delay_gm_notes = $notes;
            if ($isApproved) {
                $submission->status = 'DELAY_APPROVED';
                if (!$submission->submitted_at) {
                    $submission->submitted_at = now();
                }
            } else {
                if ($submission->status === 'DELAY_APPROVED') {
                    $submission->status = 'LATE';
                }
            }
            $submission->save();

            $department = $submission->department ?: Department::find($submission->department_id);
            $periodLabel = "{$submission->month} {$submission->year}";
        } elseif ($type === 'WEEKLY_PLAN') {
            $plan = null;
            if (!empty($validated['id'])) {
                $plan = \App\Models\WeeklyPlan::find($validated['id']);
            }
            if (!$plan && !empty($validated['department_id'])) {
                $plan = \App\Models\WeeklyPlan::where('department_id', $validated['department_id'])
                    ->where('year', $validated['year'] ?? kaldis_setting('current_planning_year', config('kaldis.current_planning_year')))
                    ->where('month', $validated['month'] ?? kaldis_setting('current_planning_month', config('kaldis.current_planning_month')))
                    ->where('week_number', $validated['week_number'] ?? 1)
                    ->first();
            }

            if (!$plan && !empty($validated['department_id'])) {
                $plan = \App\Models\WeeklyPlan::create([
                    'department_id' => $validated['department_id'],
                    'year' => $validated['year'] ?? kaldis_setting('current_planning_year', config('kaldis.current_planning_year')),
                    'month' => $validated['month'] ?? kaldis_setting('current_planning_month', config('kaldis.current_planning_month')),
                    'week_number' => $validated['week_number'] ?? 1,
                    'status' => $isApproved ? 'SUBMITTED' : 'DRAFT',
                    'submission_status' => $isApproved ? 'DELAY_APPROVED' : 'LATE',
                    'submitted_at' => now(),
                    'delay_status' => $statusValue,
                    'delay_approved_by' => $user->id,
                    'delay_approved_at' => now(),
                    'delay_gm_notes' => $notes,
                ]);
            }

            if (!$plan) {
                return back()->with('error', 'Weekly plan record not found.');
            }

            $plan->delay_status = $statusValue;
            $plan->delay_approved_by = $user->id;
            $plan->delay_approved_at = now();
            $plan->delay_gm_notes = $notes;
            if ($isApproved) {
                $plan->submission_status = 'DELAY_APPROVED';
                if ($plan->status !== 'APPROVED') {
                    $plan->status = 'SUBMITTED';
                }
                if (!$plan->submitted_at) {
                    $plan->submitted_at = now();
                }
            } else {
                if ($plan->submission_status === 'DELAY_APPROVED') {
                    $plan->submission_status = 'LATE';
                }
            }
            $plan->save();

            $department = $plan->department ?: Department::find($plan->department_id);
            $periodLabel = "{$plan->month} {$plan->year} (Week {$plan->week_number} Plan)";
        } elseif ($type === 'WEEKLY_PERFORMANCE') {
            $deptId = $validated['department_id'] ?? null;
            $year = $validated['year'] ?? kaldis_setting('current_planning_year', config('kaldis.current_planning_year'));
            $month = $validated['month'] ?? kaldis_setting('current_planning_month', config('kaldis.current_planning_month'));
            $week = $validated['week_number'] ?? 1;

            if (!empty($validated['id'])) {
                $wRep = WeeklyReport::find($validated['id']);
                if ($wRep) {
                    $deptId = $wRep->department_id;
                    $year = $wRep->year;
                    $month = $wRep->month;
                    $week = $wRep->week_number;
                } else {
                    $wSub = Submission::find($validated['id']);
                    if ($wSub) {
                        $deptId = $wSub->department_id;
                        $year = $wSub->year;
                        $month = $wSub->month;
                        $week = $wSub->week_number;
                    }
                }
            }

            if (!$deptId) {
                return back()->with('error', 'Weekly performance record not found.');
            }

            $submission = Submission::firstOrNew([
                'department_id' => $deptId,
                'year' => $year,
                'month' => $month,
                'week_number' => $week,
            ]);

            $report = WeeklyReport::firstOrNew([
                'department_id' => $deptId,
                'year' => $year,
                'month' => $month,
                'week_number' => $week,
            ]);

            $submission->delay_status = $statusValue;
            $submission->delay_approved_by = $user->id;
            $submission->delay_approved_at = now();
            $submission->delay_gm_notes = $notes;
            if ($isApproved) {
                $submission->status = 'DELAY_APPROVED';
                if (!$submission->submitted_at) {
                    $submission->submitted_at = now();
                }
            } else {
                if ($submission->status === 'DELAY_APPROVED') {
                    $submission->status = 'LATE';
                }
            }
            $submission->save();

            $report->delay_status = $statusValue;
            $report->delay_approved_by = $user->id;
            $report->delay_approved_at = now();
            $report->delay_gm_notes = $notes;
            if ($isApproved) {
                $report->submission_status = 'DELAY_APPROVED';
                if ($report->status !== 'APPROVED') {
                    $report->status = 'SUBMITTED';
                }
                if (!$report->submitted_at) {
                    $report->submitted_at = now();
                }
            } else {
                if ($report->submission_status === 'DELAY_APPROVED') {
                    $report->submission_status = 'LATE';
                }
            }
            $report->save();

            $department = Department::find($deptId);
            $periodLabel = "{$month} {$year} (Week {$week} Performance)";
        }

        // Send In-App and Telegram notification to Department Head
        $head = $department ? $department->head : null;
        if (!$head && $department && $department->head_user_id) {
            $head = \App\Models\User::find($department->head_user_id);
        }

        $typeDisplayName = match($type) {
            'MONTHLY' => 'Monthly Plan Package',
            'WEEKLY_PLAN' => 'Weekly Task Schedule',
            'WEEKLY_PERFORMANCE' => 'Weekly Performance Report',
            default => 'Submission'
        };

        if ($head && $department) {
            $actionLabel = $isApproved ? 'Approved' : 'Rejected';
            $msgTitle = "Delay Request {$actionLabel}: {$typeDisplayName}";
            $msgBody = "General Management has {$actionLabel} your delay justification for {$periodLabel}."
                . ($notes ? " GM Notes: {$notes}" : "")
                . ($isApproved ? " In accordance with executive rules, this submission is NOT recorded as Not Submitted." : "");

            \Illuminate\Support\Facades\DB::table('notifications')->insert([
                'user_id' => $head->id,
                'department_id' => $department->id,
                'title' => $msgTitle,
                'message' => $msgBody,
                'notification_type' => 'APPROVAL',
                'created_at' => now(),
            ]);

            $telegram = app(\App\Services\TelegramService::class);
            if ($telegram && $telegram->isConfigured() && !empty($head->telegram_chat_id) && $head->telegram_chat_id !== '123456789') {
                $icon = $isApproved ? '✅' : '❌';
                $tgMsg = "{$icon} <b>Kaldis Executive Notice: Delay Request {$actionLabel}</b>\n\n"
                    . "Dear <b>{$head->full_name}</b> ({$department->department_name}),\n\n"
                    . "Your delay justification for <b>{$typeDisplayName}</b> ({$periodLabel}) has been <b>" . strtoupper($actionLabel) . "</b> by General Management.\n\n"
                    . ($isApproved ? "🟢 <b>Governance Status:</b> Excused on-time submission. This will <b>NOT</b> be marked or penalized as Not Submitted.\n" : "🔴 <b>Governance Status:</b> Please finalize and submit immediately.\n")
                    . ($notes ? "💬 <b>GM Remarks:</b> <i>{$notes}</i>\n\n" : "\n")
                    . "☕ <i>Kaldis Coffee PLC — Office of the General Manager</i>";

                $telegram->sendMessage($head->telegram_chat_id, $tgMsg);
            }
        }

        AuditLog::log(
            'REVIEW_DELAY_REASON',
            'management',
            "GM {$action} delay reason for Dept " . ($department->department_code ?? 'DEPT') . " ({$typeDisplayName} - {$periodLabel})"
        );

        $feedbackMsg = $isApproved
            ? "Delay request APPROVED for " . ($department ? $department->department_name : 'department') . ". It is officially recorded as DELAY APPROVED and will NOT be counted as Not Submitted."
            : "Delay request REJECTED for " . ($department ? $department->department_name : 'department') . ".";

        return back()->with('success', $feedbackMsg);
    }

    public function remind(Request $request)
    {
        $user = Auth::user();
        if (!$user->isGM() && !$user->isSuperAdmin()) {
            abort(403, 'Only General Management can send executive reminders.');
        }

        $deptId = (int)$request->input('department_id');
        $dept = Department::with('head')->findOrFail($deptId);

        $year = $request->input('year', kaldis_setting('current_planning_year', config('kaldis.current_planning_year')));
        $month = $request->input('month', kaldis_setting('current_planning_month', config('kaldis.current_planning_month')));
        $type = $request->input('type', 'WEEKLY'); // 'WEEKLY' or 'MONTHLY'
        $week = (int)$request->input('week', 1);

        $telegram = app(\App\Services\TelegramService::class);
        $head = $dept->head;

        $sent = false;
        if ($telegram && $telegram->isConfigured() && $head && !empty($head->telegram_chat_id)) {
            if ($type === 'MONTHLY') {
                $msg = "⚠️ <b>Kaldis Executive Reminder: Monthly Plan Required</b>\n\n"
                     . "Dear <b>{$head->full_name}</b>,\n"
                     . "The Monthly Plan package for <b>{$dept->department_name}</b> ({$month} {$year}) is currently <b>NOT SUBMITTED</b>.\n\n"
                     . "Please log in to the planning system and submit your department's monthly strategic & operational plans.\n\n"
                     . "☕ <i>Kaldis Coffee PLC — Office of the General Manager</i>";
            } else {
                $msg = "⚠️ <b>Kaldis Executive Reminder: Weekly Performance Report Required</b>\n\n"
                     . "Dear <b>{$head->full_name}</b>,\n"
                     . "The Weekly Performance Report for <b>{$dept->department_name}</b> ({$month} {$year}, Week {$week}) has not been submitted.\n\n"
                     . "Please evaluate your weekly planned tasks and submit your execution report as soon as possible.\n\n"
                     . "☕ <i>Kaldis Coffee PLC — Office of the General Manager</i>";
            }

            $res = $telegram->sendMessage($head->telegram_chat_id, $msg);
            $sent = !empty($res['ok']);
        }

        // Also add in-app reminder
        if ($head) {
            \Illuminate\Support\Facades\DB::table('notifications')->insert([
                'user_id' => $head->id,
                'department_id' => $deptId,
                'title' => "Executive Reminder: " . ($type === 'MONTHLY' ? "Monthly Plan Required" : "Weekly Report Required (Week {$week})"),
                'message' => "General Management has sent an urgent reminder to submit your department's " . ($type === 'MONTHLY' ? "Monthly Plan" : "Weekly Performance Report") . " for {$month} {$year}.",
                'notification_type' => 'REMINDER',
                'created_at' => now(),
            ]);
        }

        \App\Models\AuditLog::log('GM_SENT_REMINDER', 'management', "GM sent {$type} submission reminder to Dept {$dept->department_code} ({$dept->department_name})");

        if ($sent) {
            return back()->with('success', "Telegram & In-App reminder dispatched directly to {$head->full_name} ({$dept->department_name})!");
        } else {
            return back()->with('warning', "In-App reminder recorded for {$dept->department_name}. (Note: Telegram chat ID not registered for department head)");
        }
    }

    public function runTelegramCheck(Request $request)
    {
        $user = Auth::user();
        if (!$user->isGM() && !$user->isSuperAdmin()) {
            abort(403, 'Only General Management can trigger automated Telegram checks.');
        }

        $year = $request->input('year', kaldis_setting('current_planning_year', config('kaldis.current_planning_year')));
        $month = $request->input('month', kaldis_setting('current_planning_month', config('kaldis.current_planning_month')));
        $week = (int)$request->input('week', (function_exists('get_current_planning_week') ? get_current_planning_week() : 1) ?: 1);

        // 1. Weekly reminders to pending department heads
        \Illuminate\Support\Facades\Artisan::call('kaldis:weekly-reminder', [
            '--type' => 'all',
            '--year' => $year,
            '--month' => $month,
            '--week' => $week,
            '--force' => true,
        ]);

        // 2. Weekly cutoff check & GM summary
        \Illuminate\Support\Facades\Artisan::call('kaldis:deadline-check', [
            '--year' => $year,
            '--month' => $month,
            '--week' => $week,
            '--force' => true,
        ]);

        \Illuminate\Support\Facades\Artisan::call('kaldis:monthly-reminder', [
            '--type' => 'reminder',
            '--year' => $year,
            '--month' => $month,
        ]);

        \App\Models\AuditLog::log('GM_TRIGGERED_CRON', 'management', "GM manually triggered automated Telegram compliance check for {$month} {$year} (Week {$week})");

        $previousUrl = url()->previous();
        if ($previousUrl && !str_contains($previousUrl, 'run-telegram-check')) {
            return redirect()->to($previousUrl)->with('success', "Automated Telegram notifications & Executive Summary reports successfully dispatched to General Management and Department Heads for {$month} {$year} (Week {$week})!");
        }

        return redirect()->route('dashboard.gm', ['year' => $year, 'month' => $month, 'week' => $week])
            ->with('success', "Automated Telegram notifications & Executive Summary reports successfully dispatched to General Management and Department Heads for {$month} {$year} (Week {$week})!");
    }

    public function department(Request $request)
    {
        $user = Auth::user();
        $deptId = $request->query('department_id', $user->department_id);

        if (!$user->canAccessAllDepartments() && $user->department_id != $deptId) {
            $deptId = $user->department_id;
        }

        if (!$deptId) {
            $firstDept = Department::where('active', 1)->first();
            $deptId = $firstDept ? $firstDept->id : 1;
        }

        $department = Department::findOrFail($deptId);

        $selectedYear = $request->query('year', kaldis_setting('current_planning_year', config('kaldis.current_planning_year')));
        $selectedMonth = $request->query('month', kaldis_setting('current_planning_month', config('kaldis.current_planning_month')));
        $selectedWeek = (int)$request->query('week', 1);

        $monthlyPlans = MonthlyPlan::where('department_id', $deptId)
            ->where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->get();

        $strategyCount = $monthlyPlans->where('plan_type', 'STRATEGY')->count();
        $operationalCount = $monthlyPlans->where('plan_type', 'OPERATIONAL')->count();

        $weeklyTasks = WeeklyTask::with(['result', 'monthlyPlan'])
            ->where('department_id', $deptId)
            ->where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->where('week_number', $selectedWeek)
            ->get();

        $weekTotal = $weeklyTasks->count();
        $weekDone = $weeklyTasks->filter(fn($t) => $t->result && $t->result->result === 'DONE')->count();
        $weekNotDone = $weeklyTasks->filter(fn($t) => $t->result && $t->result->result === 'NOT_DONE')->count();
        $weekPending = max(0, $weekTotal - ($weekDone + $weekNotDone));

        $allDepartments = $user->canAccessAllDepartments()
            ? Department::where('active', 1)->orderBy('department_name')->get()
            : collect([$department]);

        return view('dashboard.department', compact(
            'department',
            'selectedYear',
            'selectedMonth',
            'selectedWeek',
            'monthlyPlans',
            'strategyCount',
            'operationalCount',
            'weeklyTasks',
            'weekTotal',
            'weekDone',
            'weekNotDone',
            'weekPending',
            'allDepartments'
        ));
    }
}

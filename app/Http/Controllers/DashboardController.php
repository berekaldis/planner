<?php

namespace App\Http\Controllers;

use App\Models\AnnualGoal;
use App\Models\Department;
use App\Models\MonthlyPlan;
use App\Models\MonthlyStrategyActivation;
use App\Models\Submission;
use App\Models\WeeklyTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->isGM()) {
            return redirect()->route('dashboard.gm');
        } elseif ($user->isSuperAdmin()) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->isDeptHead()) {
            return redirect()->route('dashboard.department');
        } elseif ($user->isHROfficer()) {
            return redirect()->route('performance.weekly');
        }

        return redirect()->route('dashboard.gm');
    }

    public function gm(Request $request)
    {
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
        $missingSubmissions = $submissions->where('status', 'MISSING')->count();
        $submittedDeptIds = $submissions->whereIn('status', ['ON_TIME', 'LATE'])->pluck('department_id')->toArray();
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

        // Department performance breakdown
        $departments = Department::where('active', 1)->orderBy('department_name')->get();
        $departmentPerformances = [];

        foreach ($departments as $dept) {
            $dTotal = WeeklyTask::where('department_id', $dept->id)
                ->where('year', $selectedYear)
                ->where('month', $selectedMonth)
                ->count();

            $dDone = DB::table('weekly_tasks as wt')
                ->join('weekly_task_results as wtr', 'wt.id', '=', 'wtr.weekly_task_id')
                ->where('wt.department_id', $dept->id)
                ->where('wt.year', $selectedYear)
                ->where('wt.month', $selectedMonth)
                ->where('wtr.result', 'DONE')
                ->count();

            $dNotDone = DB::table('weekly_tasks as wt')
                ->join('weekly_task_results as wtr', 'wt.id', '=', 'wtr.weekly_task_id')
                ->where('wt.department_id', $dept->id)
                ->where('wt.year', $selectedYear)
                ->where('wt.month', $selectedMonth)
                ->where('wtr.result', 'NOT_DONE')
                ->count();

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
            'waitingSubmissions',
            'missingSubmissions',
            'rootCauses',
            'departmentPerformances',
            'monthlySubmissions',
            'monthlyPlansByDept',
            'allMonthlyPlans',
            'weeklySubmissions',
            'weeklyReports',
            'currentPhase'
        ));
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

        // Run both weekly cutoff check & monthly plan check
        \Illuminate\Support\Facades\Artisan::call('kaldis:deadline-check', [
            '--year' => $year,
            '--month' => $month,
            '--week' => $week,
        ]);

        \Illuminate\Support\Facades\Artisan::call('kaldis:monthly-reminder', [
            '--type' => 'reminder',
            '--year' => $year,
            '--month' => $month,
        ]);

        \App\Models\AuditLog::log('GM_TRIGGERED_CRON', 'management', "GM manually triggered automated Telegram compliance check for {$month} {$year} (Week {$week})");

        return back()->with('success', "Automated Telegram notifications & Executive Summary reports successfully dispatched to General Management and Department Heads for {$month} {$year} (Week {$week})!");
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

<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\MonthlyPlan;
use App\Models\MonthlySubmission;
use App\Models\Submission;
use App\Models\WeeklyAchievement;
use App\Models\WeeklyChallenge;
use App\Models\WeeklyReport;
use App\Models\WeeklyTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoucherController extends Controller
{
    /**
     * Render the official Monthly Strategic & Operational Plan Voucher
     */
    public function monthly(Request $request)
    {
        $user = Auth::user();
        $deptId = (int)$request->query('department_id', $user->department_id ?: 1);

        if (!$user->canAccessAllDepartments() && $user->department_id != $deptId) {
            $deptId = $user->department_id;
        }

        $department = Department::with('head')->findOrFail($deptId);

        $selectedYear = $request->query('year', kaldis_setting('current_planning_year', config('kaldis.current_planning_year')));
        $selectedMonth = $request->query('month', kaldis_setting('current_planning_month', config('kaldis.current_planning_month')));

        // Fetch all monthly plans
        $plans = MonthlyPlan::with('annualGoal')
            ->where('department_id', $deptId)
            ->where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->orderBy('plan_type', 'desc')
            ->orderBy('id')
            ->get();

        $strategyPlans = $plans->where('plan_type', 'STRATEGY');
        $operationalPlans = $plans->where('plan_type', 'OPERATIONAL');

        // Fetch Submission record
        $submission = MonthlySubmission::with('submitter')
            ->where('department_id', $deptId)
            ->where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->first();

        // Calculate timing status if no submission yet
        $timingStatus = 'NOT_SUBMITTED';
        if ($submission && $submission->submitted_at) {
            $timingStatus = $submission->status ?: 'ON_TIME';
        }

        $allDepartments = $user->canAccessAllDepartments()
            ? Department::where('active', 1)->orderBy('department_name')->get()
            : collect([$department]);

        return view('vouchers.monthly', compact(
            'department',
            'plans',
            'strategyPlans',
            'operationalPlans',
            'submission',
            'timingStatus',
            'allDepartments',
            'selectedYear',
            'selectedMonth'
        ));
    }

    /**
     * Render the official Weekly Performance & Execution Report Voucher
     */
    public function weekly(Request $request)
    {
        $user = Auth::user();
        $deptId = (int)$request->query('department_id', $user->department_id ?: 1);

        if (!$user->canAccessAllDepartments() && $user->department_id != $deptId) {
            $deptId = $user->department_id;
        }

        $department = Department::with('head')->findOrFail($deptId);

        $selectedYear = $request->query('year', kaldis_setting('current_planning_year', config('kaldis.current_planning_year')));
        $selectedMonth = $request->query('month', kaldis_setting('current_planning_month', config('kaldis.current_planning_month')));
        $selectedWeek = (int)$request->query('week', 1);

        // Fetch Weekly Report and Submission
        $report = WeeklyReport::with('submitter')
            ->where('department_id', $deptId)
            ->where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->where('week_number', $selectedWeek)
            ->first();

        $submission = Submission::with('submitter')
            ->where('department_id', $deptId)
            ->where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->where('week_number', $selectedWeek)
            ->first();

        // Tasks list with results and root causes
        $tasks = WeeklyTask::with(['monthlyPlan.annualGoal', 'result.reasonCategory'])
            ->where('department_id', $deptId)
            ->where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->where('week_number', $selectedWeek)
            ->orderBy('plan_type', 'desc')
            ->orderBy('id')
            ->get();

        $achievements = WeeklyAchievement::where('department_id', $deptId)
            ->where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->where('week_number', $selectedWeek)
            ->get();

        $challenges = WeeklyChallenge::where('department_id', $deptId)
            ->where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->where('week_number', $selectedWeek)
            ->get();

        $totalTasks = $tasks->count();
        $doneTasks = $tasks->filter(fn($t) => $t->result && $t->result->result === 'DONE')->count();
        $notDoneTasks = $tasks->filter(fn($t) => $t->result && $t->result->result === 'NOT_DONE')->count();
        $completionPct = $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100, 1) : 0;

        // Timing status
        $timingStatus = 'NOT_SUBMITTED';
        if ($submission && $submission->submitted_at) {
            $timingStatus = $submission->status;
        } elseif ($report && $report->submitted_at) {
            $timingStatus = $report->submission_status;
        }

        $allDepartments = $user->canAccessAllDepartments()
            ? Department::where('active', 1)->orderBy('department_name')->get()
            : collect([$department]);

        return view('vouchers.weekly', compact(
            'department',
            'report',
            'submission',
            'tasks',
            'achievements',
            'challenges',
            'totalTasks',
            'doneTasks',
            'notDoneTasks',
            'completionPct',
            'timingStatus',
            'allDepartments',
            'selectedYear',
            'selectedMonth',
            'selectedWeek'
        ));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\MonthlyPlan;
use App\Models\Submission;
use App\Models\WeeklyPlan;
use App\Models\WeeklyTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WeeklyPlanController extends Controller
{
    public function index(Request $request)
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

        // Fetch monthly plans for this department & month
        $monthlyPlans = MonthlyPlan::with('annualGoal')
            ->where('department_id', $deptId)
            ->where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->orderBy('plan_type', 'desc')
            ->get();

        $weeklyPlan = WeeklyPlan::firstOrCreate([
            'department_id' => $deptId,
            'year' => $selectedYear,
            'month' => $selectedMonth,
            'week_number' => $selectedWeek,
        ], [
            'status' => 'DRAFT',
            'created_by' => $user->id,
        ]);

        $tasks = WeeklyTask::with(['monthlyPlan.annualGoal', 'result'])
            ->where('department_id', $deptId)
            ->where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->where('week_number', $selectedWeek)
            ->orderBy('plan_type', 'desc')
            ->orderBy('id')
            ->get();

        $allDepartments = $user->canAccessAllDepartments()
            ? Department::where('active', 1)->orderBy('department_name')->get()
            : collect([$department]);

        $submission = Submission::where('department_id', $deptId)
            ->where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->where('week_number', $selectedWeek)
            ->first();

        // Sync GM feedback from WeeklyReport if present
        $weeklyReport = \App\Models\WeeklyReport::where('department_id', $deptId)
            ->where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->where('week_number', $selectedWeek)
            ->first();

        if ($submission && $weeklyReport && empty($submission->gm_feedback) && !empty($weeklyReport->gm_feedback)) {
            $submission->gm_feedback = $weeklyReport->gm_feedback;
            $submission->gm_feedback_at = $weeklyReport->gm_feedback_at;
        }

        $lastWeekPerf = get_last_week_performance_data($deptId, $selectedYear, $selectedMonth, $selectedWeek);

        return view('weekly.plans', compact(
            'department',
            'weeklyPlan',
            'tasks',
            'monthlyPlans',
            'allDepartments',
            'selectedYear',
            'selectedMonth',
            'selectedWeek',
            'submission',
            'lastWeekPerf'
        ));
    }

    public function storeTask(Request $request)
    {
        $user = Auth::user();
        $deptId = $request->input('department_id', $user->department_id);

        if (!$user->canAccessAllDepartments() && $user->department_id != $deptId) {
            abort(403, 'Unauthorized department.');
        }

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'year' => 'required|string',
            'month' => 'required|string',
            'week_number' => 'required|integer|min:1|max:5',
            'monthly_plan_id' => 'required|exists:monthly_plans,id',
            'task_title' => 'required|string|max:255',
            'task_description' => 'nullable|string',
            'task_target' => 'nullable|string',
            'expected_result' => 'nullable|string',
            'priority' => 'required|in:HIGH,MEDIUM,LOW',
            'assigned_to' => 'nullable|string|max:150',
            'responsible_person' => 'nullable|string|max:150',
            'deadline_date' => 'nullable|date',
            'due_date' => 'nullable|date',
        ]);

        $monthlyPlan = MonthlyPlan::findOrFail($validated['monthly_plan_id']);

        $weeklyPlan = WeeklyPlan::firstOrCreate([
            'department_id' => $validated['department_id'],
            'year' => $validated['year'],
            'month' => $validated['month'],
            'week_number' => $validated['week_number'],
        ], [
            'status' => 'DRAFT',
            'created_by' => $user->id,
        ]);

        $task = WeeklyTask::create([
            'weekly_plan_id' => $weeklyPlan->id,
            'monthly_plan_id' => $monthlyPlan->id,
            'department_id' => $validated['department_id'],
            'year' => $validated['year'],
            'month' => $validated['month'],
            'week_number' => $validated['week_number'],
            'plan_type' => $monthlyPlan->plan_type,
            'annual_goal_id' => $monthlyPlan->annual_goal_id,
            'task_title' => $validated['task_title'],
            'task_description' => $validated['task_description'] ?? null,
            'expected_result' => $validated['expected_result'] ?? ($validated['task_target'] ?? 'Completed'),
            'priority' => $validated['priority'],
            'responsible_person' => $validated['responsible_person'] ?? ($validated['assigned_to'] ?? ($user->full_name ?: ($user->username ?: 'Unassigned'))),
            'due_date' => !empty($validated['due_date']) ? $validated['due_date'] : (!empty($validated['deadline_date']) ? $validated['deadline_date'] : null),
            'created_by' => $user->id,
        ]);

        AuditLog::log('CREATE_WEEKLY_TASK', 'weekly_plans', "Created weekly task: {$task->task_title} (Week {$task->week_number})", $task->id);

        // Task created without one-by-one Telegram spamming.
        // Full Weekly Task Schedule with Last Week Performance Report is dispatched when submitted to GM.
        return back()->with('success', 'Weekly task scheduled successfully. Remember to submit your weekly plan when all tasks are added.');
    }

    public function submitPlan(Request $request)
    {
        $user = Auth::user();
        $deptId = $request->input('department_id', $user->department_id);

        if (!$user->canAccessAllDepartments() && $user->department_id != $deptId) {
            abort(403, 'Unauthorized department.');
        }

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'year' => 'required|string',
            'month' => 'required|string',
            'week_number' => 'required|integer|min:1|max:5',
            'notes' => 'nullable|string',
        ]);

        $department = Department::findOrFail($validated['department_id']);

        $tasks = WeeklyTask::with(['monthlyPlan.annualGoal'])
            ->where('department_id', $validated['department_id'])
            ->where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->where('week_number', $validated['week_number'])
            ->orderBy('plan_type', 'desc')
            ->orderBy('id')
            ->get();

        if ($tasks->isEmpty()) {
            return back()->withErrors(['submission' => 'Cannot submit an empty weekly plan. Please schedule at least one task first.']);
        }

        $weeklyPlan = WeeklyPlan::updateOrCreate([
            'department_id' => $validated['department_id'],
            'year' => $validated['year'],
            'month' => $validated['month'],
            'week_number' => $validated['week_number'],
        ], [
            'status' => 'SUBMITTED',
            'notes' => $validated['notes'] ?? null,
            'created_by' => $user->id,
        ]);

        AuditLog::log(
            'SUBMIT_WEEKLY_PLAN',
            'weekly_plans',
            "Submitted {$tasks->count()} weekly scheduled tasks for Week {$validated['week_number']} ({$validated['month']} {$validated['year']})",
            $weeklyPlan->id
        );

        // Fetch Last Week's Performance Report data
        $lastWeekPerf = get_last_week_performance_data(
            (int)$validated['department_id'],
            $validated['year'],
            $validated['month'],
            (int)$validated['week_number']
        );

        // Build Consolidated Notification: Weekly Task Scheduled with Last Week Performance Report
        $notification = build_weekly_task_scheduled_notification(
            $department,
            $validated['year'],
            $validated['month'],
            (int)$validated['week_number'],
            $tasks,
            $lastWeekPerf,
            $validated['notes'] ?? null,
            $user
        );

        notify_both_gm_and_head(
            (int)$validated['department_id'],
            $notification['title'],
            $notification['message'],
            $notification['plan_voucher_url'],
            $notification['reply_markup']
        );

        return back()->with('success', "Weekly task schedule for Week {$validated['week_number']} submitted successfully with Last Week's Performance Report!");
    }

    public function destroyTask($id)
    {
        $user = Auth::user();
        $task = WeeklyTask::findOrFail($id);

        if (!$user->canAccessAllDepartments() && $user->department_id != $task->department_id) {
            abort(403, 'Unauthorized department.');
        }

        $title = $task->task_title;
        if ($task->result) {
            $task->result->delete();
        }
        $task->challenges()->delete();
        $task->delete();

        AuditLog::log('DELETE_WEEKLY_TASK', 'weekly_plans', "Deleted weekly task: {$title}", $id);

        return back()->with('success', 'Weekly task removed successfully.');
    }
}

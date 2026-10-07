<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\MonthlyPlan;
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

        return view('weekly.plans', compact(
            'department',
            'weeklyPlan',
            'tasks',
            'monthlyPlans',
            'allDepartments',
            'selectedYear',
            'selectedMonth',
            'selectedWeek'
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
            'responsible_person' => $validated['responsible_person'] ?? ($validated['assigned_to'] ?? null),
            'due_date' => $validated['due_date'] ?? ($validated['deadline_date'] ?? null),
            'created_by' => $user->id,
        ]);

        AuditLog::log('CREATE_WEEKLY_TASK', 'weekly_plans', "Created weekly task: {$task->task_title} (Week {$task->week_number})", $task->id);

        return back()->with('success', 'Weekly task scheduled successfully.');
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

<?php

namespace App\Http\Controllers;

use App\Models\AnnualGoal;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\MonthlyPlan;
use App\Models\MonthlyStrategyActivation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MonthlyPlanController extends Controller
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
        $planTypeFilter = $request->query('plan_type'); // 'STRATEGY', 'OPERATIONAL', or null

        $query = MonthlyPlan::with(['annualGoal', 'weeklyTasks'])
            ->where('department_id', $deptId)
            ->where('year', $selectedYear)
            ->where('month', $selectedMonth);

        if ($planTypeFilter) {
            $query->where('plan_type', $planTypeFilter);
        }

        $monthlyPlans = $query->orderBy('plan_type', 'desc')->orderBy('id')->get();

        // Fetch GM-Activated Annual Goals available for THIS department
        $activatedGoals = AnnualGoal::where('year', $selectedYear)
            ->where('status', 'ACTIVE')
            ->whereHas('departments', function ($q) use ($deptId) {
                $q->where('departments.id', $deptId);
            })
            ->whereHas('monthlyActivations', function ($q) use ($selectedYear, $selectedMonth, $deptId) {
                $q->where('year', $selectedYear)
                  ->where('month', $selectedMonth)
                  ->where('department_id', $deptId)
                  ->where('active', 'YES');
            })
            ->orderBy('goal_code')
            ->get();

        $allDepartments = $user->canAccessAllDepartments()
            ? Department::where('active', 1)->orderBy('department_name')->get()
            : collect([$department]);

        return view('monthly.index', compact(
            'department',
            'monthlyPlans',
            'activatedGoals',
            'allDepartments',
            'selectedYear',
            'selectedMonth',
            'planTypeFilter'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $deptId = $request->input('department_id', $user->department_id);

        if (!$user->canAccessAllDepartments() && $user->department_id != $deptId) {
            abort(403, 'Cannot create plans for another department.');
        }

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'year' => 'required|string',
            'month' => 'required|string',
            'plan_type' => 'required|in:STRATEGY,OPERATIONAL',
            'annual_goal_id' => 'nullable|exists:annual_goals,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'definition_of_done' => 'required|string',
            'monthly_target' => 'required|string',
            'target_percentage' => 'nullable|numeric|min:0|max:100',
            'priority' => 'required|in:HIGH,MEDIUM,LOW',
            'responsible_person' => 'nullable|string|max:100',
        ]);

        if ($validated['plan_type'] === 'STRATEGY') {
            if (empty($validated['annual_goal_id'])) {
                return back()->withErrors(['annual_goal_id' => 'Strategy plans must be linked to a GM-activated Annual Strategy Goal.'])->withInput();
            }

            // Verify GM Activation for this department and month
            $isActivated = MonthlyStrategyActivation::where('annual_goal_id', $validated['annual_goal_id'])
                ->where('department_id', $validated['department_id'])
                ->where('year', $validated['year'])
                ->where('month', $validated['month'])
                ->where('active', 'YES')
                ->exists();

            if (!$isActivated) {
                return back()->withErrors(['annual_goal_id' => 'The selected annual strategy has not been activated by the General Manager for your department this month.'])->withInput();
            }
        } else {
            $validated['annual_goal_id'] = null;
        }

        $validated['created_by'] = $user->id;
        $validated['target_percentage'] = $validated['target_percentage'] ?? 100.00;

        $plan = MonthlyPlan::create($validated);

        AuditLog::log('CREATE_MONTHLY_PLAN', 'monthly_plans', "Created {$plan->plan_type} monthly plan: {$plan->title}", $plan->id);

        return back()->with('success', 'Monthly plan created successfully.');
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $plan = MonthlyPlan::findOrFail($id);

        if (!$user->canAccessAllDepartments() && $user->department_id != $plan->department_id) {
            abort(403, 'Cannot delete plans for another department.');
        }

        $title = $plan->title;
        $plan->weeklyTasks()->delete();
        $plan->delete();

        AuditLog::log('DELETE_MONTHLY_PLAN', 'monthly_plans', "Deleted monthly plan: {$title}", $id);

        return back()->with('success', 'Monthly plan and associated weekly tasks removed successfully.');
    }
}

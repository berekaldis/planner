<?php

namespace App\Http\Controllers;

use App\Models\AnnualGoal;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\MonthlyStrategyActivation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StrategyController extends Controller
{
    public function annualGoals(Request $request)
    {
        $selectedYear = $request->query('year', kaldis_setting('current_planning_year', config('kaldis.current_planning_year')));
        $perspective = $request->query('perspective');
        $departmentId = $request->query('department_id');

        $query = AnnualGoal::with('departments')
            ->where('year', $selectedYear);

        if ($perspective) {
            $query->where('perspective', $perspective);
        }

        if ($departmentId) {
            $query->whereHas('departments', function ($q) use ($departmentId) {
                $q->where('departments.id', $departmentId);
            });
        }

        $goals = $query->orderBy('goal_code')->get();
        $departments = Department::where('active', 1)->orderBy('department_name')->get();

        $perspectives = AnnualGoal::where('year', $selectedYear)
            ->distinct()
            ->pluck('perspective')
            ->filter();

        return view('strategy.annual', compact('goals', 'departments', 'perspectives', 'selectedYear', 'perspective', 'departmentId'));
    }

    public function monthlyActivation(Request $request)
    {
        $selectedYear = $request->query('year', kaldis_setting('current_planning_year', config('kaldis.current_planning_year')));
        $selectedMonth = $request->query('month', kaldis_setting('current_planning_month', config('kaldis.current_planning_month')));

        $goals = AnnualGoal::with('departments')
            ->where('year', $selectedYear)
            ->where('status', 'ACTIVE')
            ->orderBy('goal_code')
            ->get();

        // Map goal_id => is_active (true if any department is YES)
        $activeGoalIds = MonthlyStrategyActivation::where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->where('active', 'YES')
            ->pluck('annual_goal_id')
            ->unique()
            ->toArray();

        $activeCount = count($activeGoalIds);
        $totalCount = $goals->count();

        return view('strategy.activation', compact(
            'goals',
            'activeGoalIds',
            'selectedYear',
            'selectedMonth',
            'activeCount',
            'totalCount'
        ));
    }

    public function toggleActivation(Request $request)
    {
        $validated = $request->validate([
            'annual_goal_id' => 'required|exists:annual_goals,id',
            'year' => 'required|string',
            'month' => 'required|string',
            'is_active' => 'required|boolean',
        ]);

        $goal = AnnualGoal::with('departments')->findOrFail($validated['annual_goal_id']);
        $newStatus = $validated['is_active'] ? 'YES' : 'NO';

        $ownerDepts = $goal->departments;
        if ($ownerDepts->isEmpty()) {
            MonthlyStrategyActivation::updateOrCreate(
                [
                    'annual_goal_id' => $goal->id,
                    'year' => $validated['year'],
                    'month' => $validated['month'],
                ],
                [
                    'active' => $newStatus,
                    'activated_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]
            );
        } else {
            foreach ($ownerDepts as $dept) {
                MonthlyStrategyActivation::updateOrCreate(
                    [
                        'annual_goal_id' => $goal->id,
                        'department_id' => $dept->id,
                        'year' => $validated['year'],
                        'month' => $validated['month'],
                    ],
                    [
                        'active' => $newStatus,
                        'activated_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]
                );
            }
        }

        $action = $validated['is_active'] ? 'ACTIVATED' : 'DEACTIVATED';
        AuditLog::log(
            'STRATEGY_ACTIVATION',
            'strategy',
            "General Manager {$action} strategy {$goal->goal_code} ({$goal->goal_title}) for {$validated['month']} {$validated['year']}",
            $goal->id
        );

        $activeCount = MonthlyStrategyActivation::where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->where('active', 'YES')
            ->distinct('annual_goal_id')
            ->count('annual_goal_id');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_active' => (bool)$validated['is_active'],
                'active_count' => $activeCount,
                'message' => "Strategy {$goal->goal_code} {$action} for {$validated['month']} {$validated['year']}.",
            ]);
        }

        return back()->with('success', "Strategy {$goal->goal_code} {$action} successfully.");
    }
}

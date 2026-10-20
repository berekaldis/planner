<?php

namespace App\Http\Controllers;

use App\Models\AnnualGoal;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\MonthlyStrategyActivation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class StrategyController extends Controller
{
    public function annualGoals(Request $request)
    {
        $user = Auth::user();
        $selectedYear = $request->query('year', kaldis_setting('current_planning_year', config('kaldis.current_planning_year')));
        $perspective = $request->query('perspective');
        $departmentId = $request->query('department_id');

        $query = AnnualGoal::with('departments')
            ->where('year', $selectedYear);

        $hasPerspectiveCol = Schema::hasColumn('annual_goals', 'perspective');

        if ($perspective && $hasPerspectiveCol) {
            $query->where('perspective', $perspective);
        }

        if ($user->isDeptHead()) {
            $userDeptId = $user->department_id;
            $departmentId = $userDeptId;
            $query->where(function ($q) use ($userDeptId) {
                $q->whereDoesntHave('departments')
                  ->orWhereHas('departments', fn($sub) => $sub->where('departments.id', $userDeptId));
            });
            $departments = Department::where('id', $userDeptId)->get();
        } else {
            if ($departmentId) {
                $query->whereHas('departments', function ($q) use ($departmentId) {
                    $q->where('departments.id', $departmentId);
                });
            }
            $departments = Department::where('active', 1)->orderBy('department_name')->get();
        }

        $goals = $query->orderBy('goal_code')->get();

        $perspectives = $hasPerspectiveCol
            ? AnnualGoal::where('year', $selectedYear)
                ->distinct()
                ->pluck('perspective')
                ->filter()
            : collect([]);

        return view('strategy.annual', compact('goals', 'departments', 'perspectives', 'selectedYear', 'perspective', 'departmentId'));
    }

    public function monthlyActivation(Request $request)
    {
        $selectedYear = $request->query('year', kaldis_setting('current_planning_year', config('kaldis.current_planning_year')));
        $selectedMonth = $request->query('month', kaldis_setting('current_planning_month', config('kaldis.current_planning_month')));
        $search = trim($request->query('search', ''));
        $selectedStatus = strtoupper(trim($request->query('status', 'ALL'))); // ALL, TRUE/ACTIVE, FALSE/INACTIVE
        $selectedDept = (int)$request->query('department_id', 0);
        $perPage = (int)$request->query('per_page', 10);
        if ($perPage < 5 || $perPage > 200) {
            $perPage = 10;
        }

        // Fast indexed lookup of active goal IDs for the selected period
        $activeGoalIds = MonthlyStrategyActivation::where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->where('active', 'YES')
            ->pluck('annual_goal_id')
            ->unique()
            ->toArray();

        // Overall master counts for this year & month
        $totalCount = AnnualGoal::where('year', $selectedYear)
            ->where('status', 'ACTIVE')
            ->count();
        $activeCount = count($activeGoalIds);
        $inactiveCount = max(0, $totalCount - $activeCount);

        // Build filtered query for the paginated table
        $query = AnnualGoal::with(['departments' => fn($q) => $q->orderBy('is_primary', 'desc')])
            ->where('year', $selectedYear)
            ->where('status', 'ACTIVE');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('goal_code', 'LIKE', "%{$search}%")
                  ->orWhere('title', 'LIKE', "%{$search}%")
                  ->orWhere('definition_of_done', 'LIKE', "%{$search}%")
                  ->orWhere('annual_target', 'LIKE', "%{$search}%")
                  ->orWhereHas('departments', function ($dq) use ($search) {
                      $dq->where('department_name', 'LIKE', "%{$search}%")
                         ->orWhere('department_code', 'LIKE', "%{$search}%");
                  });
            });
        }

        if ($selectedDept > 0) {
            $query->where(function ($q) use ($selectedDept) {
                $q->where('responsible_department_id', $selectedDept)
                  ->orWhereHas('departments', fn($dq) => $dq->where('departments.id', $selectedDept));
            });
        }

        if ($selectedStatus === 'TRUE' || $selectedStatus === 'ACTIVE') {
            $query->whereIn('id', $activeGoalIds);
        } elseif ($selectedStatus === 'FALSE' || $selectedStatus === 'INACTIVE') {
            $query->whereNotIn('id', $activeGoalIds);
        }

        $goals = $query->orderBy('goal_code')->paginate($perPage, ['*'], 'page', max(1, (int)$request->query('page', 1)))->withQueryString();

        $departments = Department::where('active', 1)->orderBy('department_name')->get();

        return view('strategy.activation', compact(
            'goals',
            'activeGoalIds',
            'selectedYear',
            'selectedMonth',
            'search',
            'selectedStatus',
            'selectedDept',
            'perPage',
            'activeCount',
            'inactiveCount',
            'totalCount',
            'departments'
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
        $userId = Auth::id();
        $now = now();

        $ownerDepts = $goal->departments;
        $records = [];
        if ($ownerDepts->isEmpty()) {
            $records[] = [
                'annual_goal_id' => $goal->id,
                'department_id' => $goal->responsible_department_id ?: null,
                'year' => $validated['year'],
                'month' => $validated['month'],
                'active' => $newStatus,
                'activated_by' => $userId,
                'activated_at' => $now,
                'updated_by' => $userId,
                'updated_at' => $now,
            ];
        } else {
            foreach ($ownerDepts as $dept) {
                $records[] = [
                    'annual_goal_id' => $goal->id,
                    'department_id' => $dept->id,
                    'year' => $validated['year'],
                    'month' => $validated['month'],
                    'active' => $newStatus,
                    'activated_by' => $userId,
                    'activated_at' => $now,
                    'updated_by' => $userId,
                    'updated_at' => $now,
                ];
            }
        }

        MonthlyStrategyActivation::upsert(
            $records,
            ['annual_goal_id', 'department_id', 'year', 'month'],
            ['active', 'updated_by', 'updated_at']
        );

        $action = $validated['is_active'] ? 'ACTIVATED' : 'DEACTIVATED';
        AuditLog::log(
            'STRATEGY_ACTIVATION',
            'strategy',
            "General Manager {$action} strategy {$goal->goal_code} ({$goal->goal_title}) for {$validated['month']} {$validated['year']}",
            $goal->id
        );

        $activeGoalIds = MonthlyStrategyActivation::where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->where('active', 'YES')
            ->pluck('annual_goal_id')
            ->unique()
            ->toArray();

        $activeCount = count($activeGoalIds);
        $totalCount = AnnualGoal::where('year', $validated['year'])->where('status', 'ACTIVE')->count();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_active' => (bool)$validated['is_active'],
                'goal_id' => $goal->id,
                'active_count' => $activeCount,
                'inactive_count' => max(0, $totalCount - $activeCount),
                'total_count' => $totalCount,
                'message' => "Strategy {$goal->goal_code} {$action} for {$validated['month']} {$validated['year']}.",
            ]);
        }

        return back()->with('success', "Strategy {$goal->goal_code} {$action} successfully.");
    }

    public function bulkActivation(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|string',
            'month' => 'required|string',
            'is_active' => 'required|boolean',
            'annual_goal_ids' => 'nullable|array',
            'annual_goal_ids.*' => 'integer',
            'all' => 'nullable|boolean',
        ]);

        $year = $validated['year'];
        $month = $validated['month'];
        $isActive = (bool)$validated['is_active'];
        $newStatus = $isActive ? 'YES' : 'NO';
        $userId = Auth::id();
        $now = now();

        if (!empty($validated['all'])) {
            $goals = AnnualGoal::with('departments')
                ->where('year', $year)
                ->where('status', 'ACTIVE')
                ->get();
        } elseif (!empty($validated['annual_goal_ids'])) {
            $goals = AnnualGoal::with('departments')
                ->whereIn('id', $validated['annual_goal_ids'])
                ->get();
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No strategy plans selected for bulk action.'
            ], 422);
        }

        if ($goals->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No matching active strategy plans found.'
            ], 404);
        }

        $records = [];
        $goalIds = [];
        foreach ($goals as $goal) {
            $goalIds[] = $goal->id;
            $depts = $goal->departments;
            if ($depts->isEmpty()) {
                $records[] = [
                    'annual_goal_id' => $goal->id,
                    'department_id' => $goal->responsible_department_id ?: null,
                    'year' => $year,
                    'month' => $month,
                    'active' => $newStatus,
                    'activated_by' => $userId,
                    'activated_at' => $now,
                    'updated_by' => $userId,
                    'updated_at' => $now,
                ];
            } else {
                foreach ($depts as $dept) {
                    $records[] = [
                        'annual_goal_id' => $goal->id,
                        'department_id' => $dept->id,
                        'year' => $year,
                        'month' => $month,
                        'active' => $newStatus,
                        'activated_by' => $userId,
                        'activated_at' => $now,
                        'updated_by' => $userId,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        if (!empty($records)) {
            MonthlyStrategyActivation::upsert(
                $records,
                ['annual_goal_id', 'department_id', 'year', 'month'],
                ['active', 'updated_by', 'updated_at']
            );
        }

        $actionWord = $isActive ? 'ACTIVATED' : 'DEACTIVATED';
        $actionTitle = $isActive ? 'TRUE (Active)' : 'FALSE (Inactive)';
        $count = count($goalIds);

        AuditLog::log(
            'BULK_STRATEGY_ACTIVATION',
            'strategy',
            "General Manager {$actionWord} {$count} strategy plan(s) in bulk for {$month} {$year}",
            null
        );

        $activeGoalIds = MonthlyStrategyActivation::where('year', $year)
            ->where('month', $month)
            ->where('active', 'YES')
            ->pluck('annual_goal_id')
            ->unique()
            ->toArray();

        $activeCount = count($activeGoalIds);
        $totalCount = AnnualGoal::where('year', $year)->where('status', 'ACTIVE')->count();

        return response()->json([
            'success' => true,
            'is_active' => $isActive,
            'goal_ids' => $goalIds,
            'active_count' => $activeCount,
            'inactive_count' => max(0, $totalCount - $activeCount),
            'total_count' => $totalCount,
            'message' => "Successfully set {$count} strategic plan(s) to {$actionTitle}."
        ]);
    }
}

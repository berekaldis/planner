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

        if (!$deptId) {
            $firstDept = Department::where('active', 1)->first();
            $deptId = $firstDept ? $firstDept->id : 1;
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

        // Fetch GM-Activated Annual Goals available for THIS department (or company-wide)
        $activatedGoals = AnnualGoal::where('year', $selectedYear)
            ->where('status', 'ACTIVE')
            ->where(function ($query) use ($deptId) {
                $query->whereDoesntHave('departments')
                      ->orWhereHas('departments', function ($q) use ($deptId) {
                          $q->where('departments.id', $deptId);
                      });
            })
            ->whereHas('monthlyActivations', function ($q) use ($selectedYear, $selectedMonth, $deptId) {
                $q->where('year', $selectedYear)
                  ->where('month', $selectedMonth)
                  ->where(function ($sub) use ($deptId) {
                      $sub->where('department_id', $deptId)
                          ->orWhereNull('department_id');
                  })
                  ->where('active', 'YES');
            })
            ->orderBy('goal_code')
            ->get();

        $allDepartments = $user->canAccessAllDepartments()
            ? Department::where('active', 1)->orderBy('department_name')->get()
            : collect([$department]);

        $submission = \App\Models\MonthlySubmission::where('department_id', $deptId)
            ->where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->first();

        return view('monthly.index', compact(
            'department',
            'monthlyPlans',
            'activatedGoals',
            'allDepartments',
            'selectedYear',
            'selectedMonth',
            'planTypeFilter',
            'submission'
        ));
    }

    public function submitPackage(Request $request)
    {
        $user = Auth::user();
        $deptId = (int)$request->input('department_id', $user->department_id);

        if (!$user->canAccessAllDepartments() && $user->department_id != $deptId) {
            abort(403, 'Cannot submit plans for another department.');
        }

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'year' => 'required|string',
            'month' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $plans = MonthlyPlan::where('department_id', $deptId)
            ->where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->get();

        if ($plans->isEmpty()) {
            return back()->withErrors(['submission' => 'Cannot submit an empty plan package. Please create at least one monthly plan first.']);
        }

        $stratCount = $plans->where('plan_type', 'STRATEGY')->count();
        $opsCount = $plans->where('plan_type', 'OPERATIONAL')->count();
        $totalPlans = $plans->count();

        // Check Monthly Deadline (default Day 26 of Ethiopian month at 17:00)
        $deadlineDay = (int)kaldis_setting('monthly_plan_deadline_day', kaldis_setting('monthly_deadline_day', 26));
        $deadlineTime = kaldis_setting('monthly_plan_deadline_time', '17:00');
        $currentEt = get_ethiopian_period();
        $isCurrentMonth = ($currentEt['month'] === $validated['month'] && $currentEt['year'] === $validated['year']);
        
        $status = 'ON_TIME';
        if ($isCurrentMonth && $currentEt['day'] > $deadlineDay) {
            $status = 'LATE';
        }

        // Calculate exact Gregorian deadline timestamp for Day 26 of the Ethiopian Month
        $monthsMap = array_flip(get_ethiopian_months());
        $mNum = isset($monthsMap[$validated['month']]) ? ($monthsMap[$validated['month']] + 1) : 1;
        $yNum = (int)preg_replace('/[^0-9]/', '', $validated['year']) ?: 2019;
        $timeParts = explode(':', $deadlineTime);
        $timeHour = (int)($timeParts[0] ?? 17);
        $timeMin = (int)($timeParts[1] ?? 0);
        [$gy, $gm, $gd] = ethiopian_to_gregorian_date($yNum, $mNum, $deadlineDay);
        $deadlineAt = \Carbon\Carbon::create($gy, $gm, $gd, $timeHour, $timeMin, 0, 'Africa/Addis_Ababa');

        \App\Models\MonthlySubmission::updateOrCreate([
            'department_id' => $deptId,
            'year' => $validated['year'],
            'month' => $validated['month'],
        ], [
            'deadline_at' => $deadlineAt,
            'submitted_at' => now(),
            'status' => $status,
            'submitted_by' => $user->id,
            'remarks' => $validated['notes'] ?? "Submitted package of {$totalPlans} monthly plans ({$stratCount} Strategy, {$opsCount} Operational).",
        ]);

        AuditLog::log('SUBMIT_MONTHLY_PLANS', 'monthly_plans', "Submitted monthly plan package for {$validated['month']} {$validated['year']} ({$status})");

        // Notify GM
        $dept = Department::find($deptId);
        $statusEmoji = ($status === 'ON_TIME') ? '🟢 [ON-TIME]' : '🟡 [LATE]';
        $notifTitle = "{$statusEmoji} Monthly Plan Package: " . ($dept->department_code ?? "Dept #{$deptId}");
        $notifMessage = ($dept->department_name ?? 'Department') . " has submitted their Monthly Plan Package for {$validated['month']} {$validated['year']}.\n"
                      . "• Submission Timing: " . ($status === 'ON_TIME' ? 'ON-TIME SUBMISSION' : 'LATE SUBMISSION') . "\n"
                      . "• Total Plans: {$totalPlans} ({$stratCount} Strategy, {$opsCount} Operational)";
        if (!empty($validated['notes'])) {
            $notifMessage .= "\n• Department Remarks: {$validated['notes']}";
        }

        $voucherUrl = route('monthly.voucher', [
            'department_id' => $deptId,
            'year' => $validated['year'],
            'month' => $validated['month'],
        ]);

        notify_both_gm_and_head($deptId, $notifTitle, $notifMessage, $voucherUrl);

        return back()->with('success', "Monthly Plan package submitted successfully to General Management! Timing Status: {$status}.");
    }

    public function saveFeedback(Request $request)
    {
        $user = Auth::user();
        if (!$user->isGM() && !$user->isSuperAdmin()) {
            abort(403, 'Only General Management can submit executive review comments.');
        }

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'year' => 'required|string',
            'month' => 'required|string',
            'approval_status' => 'required|in:PENDING,APPROVED,REVISION_REQUESTED',
            'gm_feedback' => 'nullable|string',
        ]);

        \App\Models\MonthlySubmission::updateOrCreate([
            'department_id' => $validated['department_id'],
            'year' => $validated['year'],
            'month' => $validated['month'],
        ], [
            'approval_status' => $validated['approval_status'],
            'gm_feedback' => $validated['gm_feedback'],
            'gm_feedback_at' => now(),
        ]);

        AuditLog::log('GM_FEEDBACK_MONTHLY', 'monthly_plans', "GM evaluated Monthly Plans for Dept {$validated['department_id']}: {$validated['approval_status']}");

        // Automated Telegram Notification to Department Head
        $dept = Department::find($validated['department_id']);
        $statusEmoji = $validated['approval_status'] === 'APPROVED' ? '✅' : ($validated['approval_status'] === 'REVISION_REQUESTED' ? '⚠️' : 'ℹ️');
        $notifTitle = "{$statusEmoji} GM Monthly Plan Review: " . ($dept->department_code ?? "Dept #{$validated['department_id']}");
        $notifMessage = "The General Manager has evaluated the Monthly Plan Package for <b>" . ($dept->department_name ?? 'Department') . "</b> ({$validated['month']} {$validated['year']}):\n\n"
            . "• <b>Decision:</b> <b>{$validated['approval_status']}</b>\n";
        if (!empty($validated['gm_feedback'])) {
            $notifMessage .= "• <b>GM Comments:</b> " . htmlspecialchars($validated['gm_feedback'], ENT_QUOTES, 'UTF-8') . "\n";
        }
        $voucherUrl = route('monthly.voucher', [
            'department_id' => $validated['department_id'],
            'year' => $validated['year'],
            'month' => $validated['month'],
        ]);
        notify_department_head((int)$validated['department_id'], $notifTitle, $notifMessage, $voucherUrl);

        return back()->with('success', 'General Manager feedback & approval status saved and notified on Telegram.');
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

            // Verify GM Activation for this department (or company-wide) and month
            $isActivated = MonthlyStrategyActivation::where('annual_goal_id', $validated['annual_goal_id'])
                ->where('year', $validated['year'])
                ->where('month', $validated['month'])
                ->where(function ($q) use ($validated) {
                    $q->where('department_id', $validated['department_id'])
                      ->orWhereNull('department_id');
                })
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

        // Automated Telegram Notification to GM and Department Head
        $dept = Department::find($validated['department_id']);
        $notifTitle = "📋 New Monthly Plan: " . ($dept->department_code ?? "Dept #{$validated['department_id']}");
        $notifMessage = "A new monthly plan has been created for <b>" . ($dept->department_name ?? 'Department') . "</b> ({$plan->month} {$plan->year}):\n\n"
            . "• <b>Title:</b> " . htmlspecialchars($plan->title, ENT_QUOTES, 'UTF-8') . "\n"
            . "• <b>Type:</b> {$plan->plan_type}\n"
            . "• <b>Target:</b> " . htmlspecialchars($plan->monthly_target, ENT_QUOTES, 'UTF-8') . "\n"
            . "• <b>Definition of Done:</b> " . htmlspecialchars($plan->definition_of_done, ENT_QUOTES, 'UTF-8') . "\n"
            . "• <b>Priority:</b> {$plan->priority}";

        $monthlyUrl = route('monthly.plans', [
            'department_id' => $validated['department_id'],
            'year' => $validated['year'],
            'month' => $validated['month'],
        ]);

        notify_both_gm_and_head((int)$validated['department_id'], $notifTitle, $notifMessage, $monthlyUrl);

        return back()->with('success', 'Monthly plan created successfully and notified on Telegram.');
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

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

        $deadlineInfo = get_monthly_plan_deadline_info($selectedMonth, $selectedYear);

        return view('monthly.index', compact(
            'department',
            'monthlyPlans',
            'activatedGoals',
            'allDepartments',
            'selectedYear',
            'selectedMonth',
            'planTypeFilter',
            'submission',
            'deadlineInfo'
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
            'delay_reason' => 'nullable|string|max:1000',
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

        // Check Monthly Plan Submission Deadline (Governance Rule: Due Day 26 of PRECEDING Ethiopian month)
        $deadlineInfo = get_monthly_plan_deadline_info($validated['month'], $validated['year']);
        $status = $deadlineInfo['status'];
        $deadlineAt = $deadlineInfo['deadline_carbon'];

        // Check existing submission to preserve or honor approved delay
        $existingSub = \App\Models\MonthlySubmission::where('department_id', $deptId)
            ->where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->first();

        $delayReason = $validated['delay_reason'] ?? ($existingSub->delay_reason ?? null);
        $delayStatus = $existingSub->delay_status ?? null;

        if ($existingSub && ($existingSub->delay_status === 'APPROVED' || $existingSub->status === 'DELAY_APPROVED')) {
            $status = 'DELAY_APPROVED';
            $delayStatus = 'APPROVED';
        } elseif ($status === 'LATE' && !empty($delayReason) && $delayStatus !== 'APPROVED') {
            $delayStatus = 'PENDING';
        }

        $subPayload = [
            'deadline_at' => $deadlineAt,
            'submitted_at' => now(),
            'status' => $status,
            'submitted_by' => $user->id,
            'remarks' => $validated['notes'] ?? "Submitted package of {$totalPlans} monthly plans ({$stratCount} Strategy, {$opsCount} Operational).",
        ];

        if (!empty($delayReason)) {
            $subPayload['delay_reason'] = $delayReason;
            $subPayload['delay_status'] = $delayStatus ?: 'PENDING';
        }

        \App\Models\MonthlySubmission::updateOrCreate([
            'department_id' => $deptId,
            'year' => $validated['year'],
            'month' => $validated['month'],
        ], $subPayload);

        AuditLog::log('SUBMIT_MONTHLY_PLANS', 'monthly_plans', "Submitted monthly plan package for {$validated['month']} {$validated['year']} ({$status})");

        // Notify GM & Department Head with Official Monthly Plan Voucher
        $dept = Department::find($deptId);
        $statusEmoji = ($status === 'ON_TIME' || $status === 'DELAY_APPROVED') ? '🟢 [ON-TIME]' : '🟡 [LATE]';
        $statusBadge = ($status === 'DELAY_APPROVED')
            ? '🟢 DELAY APPROVED (EXCUSED ON-TIME)'
            : (($status === 'ON_TIME') ? '🟢 ON-TIME SUBMISSION' : '🟡 LATE SUBMISSION');
        $deptCode = htmlspecialchars($dept->department_code ?? "Dept #{$deptId}", ENT_QUOTES, 'UTF-8');
        $deptName = htmlspecialchars($dept->department_name ?? 'Department', ENT_QUOTES, 'UTF-8');

        $voucherUrl = route('monthly.voucher', [
            'department_id' => $deptId,
            'year' => $validated['year'],
            'month' => $validated['month'],
        ]);

        $notifTitle = "{$statusEmoji} Monthly Plan Package: {$deptCode}";
        $notifMessage = "<b>{$deptName}</b> has officially submitted their <b>Monthly Plan Package</b> for <b>{$validated['month']} {$validated['year']}</b>.\n\n"
                      . "📋 <b>Package Overview:</b>\n"
                      . "• Timing Status: <b>{$statusBadge}</b>\n"
                      . "• Total Monthly Plans: <b>{$totalPlans}</b> ({$stratCount} Strategy, {$opsCount} Operational)\n"
                      . "• Submission Deadline: <b>{$deadlineInfo['deadline_formatted']}</b>";

        if (!empty($validated['notes'])) {
            $notifMessage .= "\n\n💬 <b>Remarks:</b> <i>" . htmlspecialchars($validated['notes'], ENT_QUOTES, 'UTF-8') . "</i>";
        }

        $replyMarkup = [
            'inline_keyboard' => [
                [
                    ['text' => '📄 View Official Monthly Plan Voucher', 'url' => $voucherUrl]
                ]
            ]
        ];

        notify_both_gm_and_head($deptId, $notifTitle, $notifMessage, $voucherUrl, $replyMarkup);

        return back()->with('success', "Monthly Plan package submitted successfully to General Management! Timing Status: {$status} (Deadline: {$deadlineInfo['deadline_formatted']}).");
    }

    public function submitDelayReason(Request $request)
    {
        $user = Auth::user();
        $deptId = (int)$request->input('department_id', $user->department_id);

        if (!$user->canAccessAllDepartments() && $user->department_id != $deptId) {
            abort(403, 'Unauthorized department.');
        }

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'year' => 'required|string',
            'month' => 'required|string',
            'delay_reason' => 'required|string|max:1000',
        ]);

        $deadlineInfo = get_monthly_plan_deadline_info($validated['month'], $validated['year']);
        $deadlineAt = $deadlineInfo['deadline_carbon'];

        $existingSub = \App\Models\MonthlySubmission::where('department_id', $deptId)
            ->where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->first();

        $delayStatus = ($existingSub && $existingSub->delay_status === 'APPROVED') ? 'APPROVED' : 'PENDING';
        $status = ($delayStatus === 'APPROVED') ? 'DELAY_APPROVED' : ($existingSub->status ?? 'DRAFT');

        \App\Models\MonthlySubmission::updateOrCreate([
            'department_id' => $deptId,
            'year' => $validated['year'],
            'month' => $validated['month'],
        ], [
            'deadline_at' => $existingSub->deadline_at ?? $deadlineAt,
            'delay_reason' => $validated['delay_reason'],
            'delay_status' => $delayStatus,
            'status' => $status,
            'submission_channel' => $existingSub->submission_channel ?? 'WEB',
        ]);

        AuditLog::log('SUBMIT_DELAY_REASON', 'monthly_plans', "Submitted delay reason for Monthly Plan ({$validated['month']} {$validated['year']}): {$validated['delay_reason']}");

        $dept = Department::find($deptId);
        $notifTitle = "⏳ Monthly Plan Delay Reason: {$dept->department_code}";
        $notifMessage = "<b>{$dept->department_name}</b> has submitted a <b>Delay Reason</b> for their Monthly Plan (<b>{$validated['month']} {$validated['year']}</b>):\n\n"
                      . "📝 <b>Reason:</b> <i>" . htmlspecialchars($validated['delay_reason'], ENT_QUOTES, 'UTF-8') . "</i>\n\n"
                      . "👉 Please review and approve/reject on the GM Management Dashboard.";
        $gmUrl = route('dashboard.gm', ['year' => $validated['year'], 'month' => $validated['month']]);
        notify_both_gm_and_head($deptId, $notifTitle, $notifMessage, $gmUrl);

        return back()->with('success', 'Delay reason submitted successfully to General Management for approval.');
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

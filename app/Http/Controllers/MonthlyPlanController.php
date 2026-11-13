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
                      . "• Submission Deadline: <b>{$deadlineInfo['deadline_formatted']}</b>\n\n"
                      . "📝 <b>Submitted Plans:</b>\n";

        $i = 1;
        foreach ($plans->take(8) as $p) {
            $badge = $p->plan_type === 'STRATEGY' ? '🎯' : '⚙️';
            $notifMessage .= "{$i}. {$badge} <b>" . htmlspecialchars($p->title, ENT_QUOTES, 'UTF-8') . "</b> (Target: " . htmlspecialchars($p->monthly_target, ENT_QUOTES, 'UTF-8') . ")\n";
            $i++;
        }
        if ($plans->count() > 8) {
            $remaining = $plans->count() - 8;
            $notifMessage .= "<i>...and {$remaining} more plan(s).</i>\n";
        }

        if (!empty($validated['notes'])) {
            $notifMessage .= "\n💬 <b>Remarks:</b> <i>" . htmlspecialchars($validated['notes'], ENT_QUOTES, 'UTF-8') . "</i>";
        }

        $voucherEnabled = kaldis_setting('voucher_enabled', 'true') === 'true';
        $portalUrl = route('monthly.plans', [
            'department_id' => $deptId,
            'year' => $validated['year'],
            'month' => $validated['month'],
        ]);
        $btnText = $voucherEnabled ? '📄 View Official Monthly Plan Voucher' : '🌐 View Submitted Monthly Plans';
        $targetUrl = $voucherEnabled ? $voucherUrl : $portalUrl;

        $replyMarkup = [
            'inline_keyboard' => [
                [
                    ['text' => $btnText, 'url' => $targetUrl]
                ]
            ]
        ];

        notify_both_gm_and_head($deptId, $notifTitle, $notifMessage, $targetUrl, $replyMarkup);

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
            'priority' => 'nullable|in:HIGH,MEDIUM,LOW',
            'responsible_person' => 'nullable|string|max:100',
        ]);

        $validated['priority'] = $validated['priority'] ?? 'MEDIUM';
        $validated['target_percentage'] = $validated['target_percentage'] ?? 100.00;
        $validated['responsible_person'] = $validated['responsible_person'] ?? ($user->full_name ?: ($user->username ?: 'Department'));
        $validated['approval_status'] = 'PENDING';

        $isStrategyMandatory = kaldis_setting('strategy_field_mandatory', 'true') === 'true';

        if ($validated['plan_type'] === 'STRATEGY') {
            if ($isStrategyMandatory && empty($validated['annual_goal_id'])) {
                return back()->withErrors(['annual_goal_id' => 'Strategy plans must be linked to a GM-activated Annual Strategy Goal (Mandatory by system policy).'])->withInput();
            }

            if (!empty($validated['annual_goal_id'])) {
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
        } else {
            $validated['annual_goal_id'] = !empty($validated['annual_goal_id']) ? $validated['annual_goal_id'] : null;
        }

        $validated['created_by'] = $user->id;
        $validated['target_percentage'] = $validated['target_percentage'] ?? 100.00;

        $plan = MonthlyPlan::create($validated);

        AuditLog::log('CREATE_MONTHLY_PLAN', 'monthly_plans', "Created {$plan->plan_type} monthly plan: {$plan->title}", $plan->id);

        // Monthly plan is drafted without one-by-one Telegram spamming.
        // The consolidated package notification is dispatched to GM & Head when "Submit to GM" is clicked.
        return back()->with('success', 'Monthly plan created successfully. Remember to click "Submit to GM" once your plan package is ready.');
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

    public function bulkApprove(Request $request)
    {
        $user = Auth::user();
        if (!$user->isGM() && !$user->isSuperAdmin()) {
            abort(403, 'Only the General Manager or Administrator can approve plans.');
        }

        $query = MonthlyPlan::query();

        if ($request->has('plan_ids') && is_array($request->input('plan_ids')) && count($request->input('plan_ids')) > 0) {
            $ids = array_filter(array_map('intval', $request->input('plan_ids')));
            $query->whereIn('id', $ids);
        } elseif ($request->filled('department_id')) {
            $query->where('department_id', (int)$request->input('department_id'));
            if ($request->filled('year')) {
                $query->where('year', $request->input('year'));
            }
            if ($request->filled('month')) {
                $query->where('month', $request->input('month'));
            }
        } else {
            return back()->with('error', 'No plans were selected for bulk approval.');
        }

        $query->where(function ($q) {
            $q->where('approval_status', '!=', 'APPROVED')
              ->orWhereNull('approval_status');
        });

        $count = $query->count();
        if ($count === 0) {
            return back()->with('info', 'All selected monthly plans are already approved.');
        }

        $affectedRows = $query->update([
            'approval_status' => 'APPROVED',
            'decline_reason' => null,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        AuditLog::log('BULK_APPROVE_MONTHLY_PLANS', 'monthly_plans', "GM approved {$affectedRows} monthly plans in bulk");

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Successfully approved {$affectedRows} monthly plans in bulk.",
                'count' => $affectedRows,
            ]);
        }

        return back()->with('success', "Successfully approved {$affectedRows} monthly plan(s) in bulk.");
    }

    public function approve($id)
    {
        $user = Auth::user();
        if (!$user->isGM() && !$user->isSuperAdmin()) {
            abort(403, 'Only the General Manager can approve plans.');
        }

        $plan = MonthlyPlan::findOrFail($id);
        $plan->update([
            'approval_status' => 'APPROVED',
            'decline_reason' => null,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        AuditLog::log('APPROVE_MONTHLY_PLAN', 'monthly_plans', "GM approved monthly plan #{$plan->id}: {$plan->title}", $plan->id);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Monthly plan approved.',
                'approval_status' => 'APPROVED',
            ]);
        }

        return back()->with('success', "Monthly plan \"{$plan->title}\" approved.");
    }

    public function decline(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isGM() && !$user->isSuperAdmin()) {
            abort(403, 'Only the General Manager can decline plans.');
        }

        $validated = $request->validate([
            'decline_reason' => 'required|string|max:1000',
        ]);

        $plan = MonthlyPlan::findOrFail($id);
        $plan->update([
            'approval_status' => 'DECLINED',
            'decline_reason' => $validated['decline_reason'],
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        AuditLog::log('DECLINE_MONTHLY_PLAN', 'monthly_plans', "GM declined monthly plan #{$plan->id}: {$validated['decline_reason']}", $plan->id);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Monthly plan declined.',
                'approval_status' => 'DECLINED',
                'decline_reason' => $validated['decline_reason'],
            ]);
        }

        return back()->with('success', "Monthly plan \"{$plan->title}\" declined.");
    }

    public function saveGmComment(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isGM() && !$user->isSuperAdmin()) {
            abort(403, 'Only the General Manager or Administrator can comment on plans.');
        }

        $validated = $request->validate([
            'gm_comment' => 'nullable|string|max:2000',
        ]);

        $plan = MonthlyPlan::findOrFail($id);
        $comment = !empty($validated['gm_comment']) ? trim($validated['gm_comment']) : null;

        $plan->update([
            'gm_comment' => $comment,
            'gm_comment_at' => $comment ? now() : null,
            'gm_comment_by' => $comment ? $user->id : null,
        ]);

        AuditLog::log('GM_COMMENT_MONTHLY_PLAN', 'monthly_plans', "GM commented on monthly plan #{$plan->id}: {$plan->title}", $plan->id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'GM comment saved successfully.',
                'gm_comment' => $plan->gm_comment,
                'gm_comment_at' => $plan->gm_comment_at ? $plan->gm_comment_at->format('M d, Y H:i') : null,
                'gm_commenter' => $user->full_name ?: $user->username,
            ]);
        }

        return back()->with('success', "GM comment for monthly plan \"{$plan->title}\" saved successfully.");
    }
}

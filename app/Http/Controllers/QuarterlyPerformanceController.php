<?php

namespace App\Http\Controllers;

use App\Models\AnnualGoal;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\MonthlyPlan;
use App\Models\QuarterlySubmission;
use App\Models\User;
use App\Models\WeeklyTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuarterlyPerformanceController extends Controller
{
    /**
     * Display the Bulk Quarterly Performance Submission & Review Module
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $deptId = (int)$request->query('department_id', $user->department_id ?: 1);

        if (!$user->canAccessAllDepartments() && $user->department_id != $deptId) {
            $deptId = $user->department_id;
        }

        $department = Department::findOrFail($deptId);

        $selectedYear = $request->query('year', kaldis_setting('current_planning_year', config('kaldis.current_planning_year')));
        $quarters = get_ethiopian_quarters();
        $selectedQuarter = $request->query('quarter', 'Q1');

        if (!array_key_exists($selectedQuarter, $quarters)) {
            $selectedQuarter = 'Q1';
        }

        $quarterInfo = $quarters[$selectedQuarter];
        $quarterMonths = $quarterInfo['months'];

        // Retrieve all monthly plans for the 3 months of the selected quarter
        $plans = MonthlyPlan::with(['annualGoal', 'weeklyTasks.result'])
            ->where('department_id', $deptId)
            ->where('year', $selectedYear)
            ->whereIn('month', $quarterMonths)
            ->orderByRaw("FIELD(month, '" . implode("','", $quarterMonths) . "')")
            ->orderBy('plan_type', 'desc')
            ->orderBy('id')
            ->get();

        // Retrieve existing Quarterly Submission record if exists
        $submission = QuarterlySubmission::with(['submitter', 'reviewer'])
            ->where('department_id', $deptId)
            ->where('year', $selectedYear)
            ->where('quarter', $selectedQuarter)
            ->first();

        // Active annual strategy goals available for this department
        $annualGoals = AnnualGoal::where('status', 'ACTIVE')
            ->where('year', $selectedYear)
            ->where(function ($query) use ($deptId) {
                $query->where('responsible_department_id', $deptId)
                      ->orWhereNull('responsible_department_id');
            })
            ->orderBy('goal_code')
            ->get();

        // Calculate performance metrics across the 3 months
        $totalPlans = $plans->count();
        $donePlans = $plans->filter(fn($p) => $p->performance_status === 'DONE')->count();
        $partialPlans = $plans->filter(fn($p) => $p->performance_status === 'PARTIAL')->count();
        $notDonePlans = $plans->filter(fn($p) => $p->performance_status === 'NOT_DONE')->count();
        $pendingPlans = $totalPlans - ($donePlans + $partialPlans + $notDonePlans);

        if ($totalPlans > 0) {
            $sumPct = $plans->sum(function ($p) {
                if ($p->achievement_percentage > 0) {
                    return min(100, (int)$p->achievement_percentage);
                }
                return match ($p->performance_status) {
                    'DONE' => 100,
                    'PARTIAL' => 50,
                    default => 0,
                };
            });
            $avgCompletionRate = round($sumPct / $totalPlans, 1);
        } else {
            $avgCompletionRate = 0.00;
        }

        // Monthly Plans grouped by month
        $plansByMonth = [];
        foreach ($quarterMonths as $m) {
            $plansByMonth[$m] = $plans->where('month', $m);
        }

        // Weekly Tasks stats rollup for context
        $weeklyStats = DB::table('weekly_tasks as wt')
            ->leftJoin('weekly_task_results as wtr', 'wt.id', '=', 'wtr.weekly_task_id')
            ->where('wt.department_id', $deptId)
            ->where('wt.year', $selectedYear)
            ->whereIn('wt.month', $quarterMonths)
            ->selectRaw("
                COUNT(wt.id) as total_tasks,
                SUM(CASE WHEN wtr.result = 'DONE' THEN 1 ELSE 0 END) as done_tasks,
                SUM(CASE WHEN wtr.result = 'NOT_DONE' THEN 1 ELSE 0 END) as not_done_tasks
            ")
            ->first();

        $departments = $user->canAccessAllDepartments()
            ? Department::where('active', 1)->orderBy('department_name')->get()
            : collect([$department]);
        $allDepartments = $departments;

        return view('performance.quarterly', compact(
            'department',
            'departments',
            'allDepartments',
            'annualGoals',
            'selectedYear',
            'selectedQuarter',
            'quarters',
            'quarterInfo',
            'quarterMonths',
            'plans',
            'plansByMonth',
            'submission',
            'totalPlans',
            'donePlans',
            'partialPlans',
            'notDonePlans',
            'pendingPlans',
            'avgCompletionRate',
            'weeklyStats'
        ));
    }

    /**
     * Add a Monthly Plan & Performance evaluation directly within the Quarterly module
     */
    public function addPlan(Request $request)
    {
        $user = Auth::user();
        $deptId = (int)$request->input('department_id', $user->department_id);

        if (!$user->canAccessAllDepartments() && $user->department_id != $deptId) {
            abort(403, 'Unauthorized to add plans for other departments.');
        }

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'year' => 'required|string',
            'month' => 'required|string',
            'plan_type' => 'required|in:STRATEGY,OPERATIONAL',
            'annual_goal_id' => 'nullable|exists:annual_goals,id',
            'title' => 'required|string|max:255',
            'monthly_target' => 'required|string|max:255',
            'definition_of_done' => 'nullable|string',
            'responsible_person' => 'nullable|string|max:100',
            'priority' => 'nullable|in:HIGH,MEDIUM,LOW',
            'performance_status' => 'required|in:DONE,PARTIAL,NOT_DONE,PENDING',
            'achievement_percentage' => 'nullable|integer|min:0|max:100',
            'actual_result' => 'nullable|string',
            'completion_notes' => 'nullable|string',
        ]);

        if ($validated['plan_type'] === 'STRATEGY' && empty($validated['annual_goal_id'])) {
            return back()->withErrors(['annual_goal_id' => 'Strategy plans must be linked to an Annual Strategy Goal.'])->withInput();
        }

        $pct = $validated['achievement_percentage'];
        if ($pct === null || $pct === '') {
            $pct = match ($validated['performance_status']) {
                'DONE' => 100,
                'PARTIAL' => 50,
                default => 0,
            };
        }

        $plan = MonthlyPlan::create([
            'department_id' => $deptId,
            'year' => $validated['year'],
            'month' => $validated['month'],
            'plan_type' => $validated['plan_type'],
            'annual_goal_id' => $validated['plan_type'] === 'STRATEGY' ? $validated['annual_goal_id'] : null,
            'title' => $validated['title'],
            'description' => $validated['title'],
            'monthly_target' => $validated['monthly_target'],
            'definition_of_done' => $validated['definition_of_done'] ?: $validated['monthly_target'],
            'responsible_person' => $validated['responsible_person'] ?: ($user->full_name ?? 'Department Head'),
            'priority' => $validated['priority'] ?? 'HIGH',
            'performance_status' => $validated['performance_status'],
            'achievement_percentage' => (int)$pct,
            'actual_result' => $validated['actual_result'] ?: null,
            'completion_notes' => $validated['completion_notes'] ?: null,
            'active' => 1,
            'created_by' => $user->id,
        ]);

        AuditLog::log('CREATE_QUARTERLY_MONTHLY_PLAN', 'monthly_plans', "Added {$validated['month']} plan #{$plan->id} with status {$validated['performance_status']} ({$pct}%)", $plan->id);

        return back()->with('success', "Plan '{$plan->title}' for {$validated['month']} was added successfully with {$validated['performance_status']} status ({$pct}%).");
    }

    /**
     * Delete a Monthly Plan from the Quarterly Module
     */
    public function deletePlan(Request $request, $id)
    {
        $user = Auth::user();
        $plan = MonthlyPlan::findOrFail($id);

        if (!$user->canAccessAllDepartments() && $user->department_id != $plan->department_id) {
            abort(403, 'Unauthorized to delete this plan.');
        }

        $month = $plan->month;
        $title = $plan->title;
        $plan->delete();

        AuditLog::log('DELETE_QUARTERLY_MONTHLY_PLAN', 'monthly_plans', "Deleted plan #{$id} ({$month} - {$title})", $id);

        return back()->with('success', "Plan '{$title}' for {$month} was removed successfully.");
    }

    /**
     * Save draft performance evaluations for the quarter's monthly plans
     */
    public function saveEvaluation(Request $request)
    {
        $user = Auth::user();
        $deptId = (int)$request->input('department_id', $user->department_id);

        if (!$user->canAccessAllDepartments() && $user->department_id != $deptId) {
            abort(403, 'Unauthorized to evaluate other departments.');
        }

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'year' => 'required|string',
            'quarter' => 'required|string|in:Q1,Q2,Q3,Q4',
            'evaluations' => 'nullable|array',
            'evaluations.*.performance_status' => 'required|string|in:PENDING,DONE,PARTIAL,NOT_DONE',
            'evaluations.*.achievement_percentage' => 'nullable|integer|min:0|max:100',
            'evaluations.*.actual_result' => 'nullable|string',
            'evaluations.*.completion_notes' => 'nullable|string',
            'executive_summary' => 'nullable|string',
            'challenges' => 'nullable|string',
            'next_quarter_priorities' => 'nullable|string',
        ]);

        $evaluations = $request->input('evaluations', []);
        foreach ($evaluations as $planId => $evalData) {
            MonthlyPlan::where('id', $planId)
                ->where('department_id', $deptId)
                ->update([
                    'performance_status' => $evalData['performance_status'] ?? 'PENDING',
                    'achievement_percentage' => (int)($evalData['achievement_percentage'] ?? 0),
                    'actual_result' => $evalData['actual_result'] ?? null,
                    'completion_notes' => $evalData['completion_notes'] ?? null,
                ]);
        }

        // Update or create draft Quarterly Submission record
        $quarters = get_ethiopian_quarters();
        $months = implode(', ', $quarters[$validated['quarter']]['months'] ?? []);

        $submission = QuarterlySubmission::firstOrNew([
            'department_id' => $deptId,
            'year' => $validated['year'],
            'quarter' => $validated['quarter'],
        ]);

        $submission->months = $months;
        $submission->executive_summary = $validated['executive_summary'] ?? $submission->executive_summary;
        $submission->challenges = $validated['challenges'] ?? $submission->challenges;
        $submission->next_quarter_priorities = $validated['next_quarter_priorities'] ?? $submission->next_quarter_priorities;
        if (!$submission->exists) {
            $submission->status = 'DRAFT';
            $submission->approval_status = 'PENDING';
        }
        $submission->save();

        AuditLog::log('SAVE_QUARTERLY_EVALUATION', 'quarterly', "Saved draft evaluation for {$validated['quarter']} {$validated['year']}", $submission->id);

        return back()->with('success', "Draft performance evaluations for {$validated['quarter']} saved successfully!");
    }

    /**
     * Bulk Submit All 3 Months' Performance to General Management
     */
    public function submitQuarter(Request $request)
    {
        $user = Auth::user();
        $deptId = (int)$request->input('department_id', $user->department_id);

        if (!$user->canAccessAllDepartments() && $user->department_id != $deptId) {
            abort(403, 'Unauthorized to submit for other departments.');
        }

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'year' => 'required|string',
            'quarter' => 'required|string|in:Q1,Q2,Q3,Q4',
            'evaluations' => 'nullable|array',
            'evaluations.*.performance_status' => 'required|string|in:PENDING,DONE,PARTIAL,NOT_DONE',
            'evaluations.*.achievement_percentage' => 'nullable|integer|min:0|max:100',
            'evaluations.*.actual_result' => 'nullable|string',
            'evaluations.*.completion_notes' => 'nullable|string',
            'executive_summary' => 'required|string|min:10',
            'challenges' => 'nullable|string',
            'next_quarter_priorities' => 'nullable|string',
        ]);

        $quarters = get_ethiopian_quarters();
        $quarterInfo = $quarters[$validated['quarter']] ?? null;
        if (!$quarterInfo) {
            return back()->withErrors(['quarter' => 'Invalid quarter specified.']);
        }
        $quarterMonths = $quarterInfo['months'];

        // Save incoming evaluations if provided
        $evaluations = $request->input('evaluations', []);
        foreach ($evaluations as $planId => $evalData) {
            MonthlyPlan::where('id', $planId)
                ->where('department_id', $deptId)
                ->update([
                    'performance_status' => $evalData['performance_status'] ?? 'PENDING',
                    'achievement_percentage' => (int)($evalData['achievement_percentage'] ?? 0),
                    'actual_result' => $evalData['actual_result'] ?? null,
                    'completion_notes' => $evalData['completion_notes'] ?? null,
                ]);
        }

        // Fetch all plans across the 3 months
        $plans = MonthlyPlan::where('department_id', $deptId)
            ->where('year', $validated['year'])
            ->whereIn('month', $quarterMonths)
            ->get();

        if ($plans->isEmpty()) {
            return back()->withErrors(['submission' => "No monthly plans found for {$validated['quarter']} ({$quarterInfo['description']}). Create plans first before submitting quarterly performance."]);
        }

        $totalPlans = $plans->count();
        $donePlans = $plans->where('performance_status', 'DONE')->count();
        $partialPlans = $plans->where('performance_status', 'PARTIAL')->count();
        $notDonePlans = $plans->where('performance_status', 'NOT_DONE')->count();

        $sumPct = $plans->sum(function ($p) {
            if ($p->achievement_percentage > 0) {
                return min(100, (int)$p->achievement_percentage);
            }
            return match ($p->performance_status) {
                'DONE' => 100,
                'PARTIAL' => 50,
                default => 0,
            };
        });
        $completionPct = round($sumPct / $totalPlans, 2);

        $monthsStr = implode(', ', $quarterMonths);

        // Update or create QuarterlySubmission
        $submission = QuarterlySubmission::updateOrCreate([
            'department_id' => $deptId,
            'year' => $validated['year'],
            'quarter' => $validated['quarter'],
        ], [
            'months' => $monthsStr,
            'total_plans' => $totalPlans,
            'done_plans' => $donePlans,
            'not_done_plans' => $notDonePlans,
            'completion_percentage' => $completionPct,
            'status' => 'SUBMITTED',
            'approval_status' => 'PENDING',
            'submission_channel' => 'WEB',
            'submitted_by' => $user->id,
            'submitted_at' => now(),
            'executive_summary' => $validated['executive_summary'],
            'challenges' => $validated['challenges'] ?? null,
            'next_quarter_priorities' => $validated['next_quarter_priorities'] ?? null,
        ]);

        AuditLog::log('SUBMIT_QUARTERLY_PERFORMANCE', 'quarterly', "Submitted {$validated['quarter']} ({$monthsStr}) quarterly performance report with {$completionPct}% completion", $submission->id);

        // Send Telegram Notification to GM and Department Head
        $dept = Department::find($deptId);
        $deptCode = htmlspecialchars($dept->department_code ?? "Dept #{$deptId}", ENT_QUOTES, 'UTF-8');
        $deptName = htmlspecialchars($dept->department_name ?? 'Department', ENT_QUOTES, 'UTF-8');

        $voucherUrl = route('quarterly.voucher', [
            'department_id' => $deptId,
            'year' => $validated['year'],
            'quarter' => $validated['quarter'],
        ]);

        $reviewUrl = route('performance.quarterly', [
            'department_id' => $deptId,
            'year' => $validated['year'],
            'quarter' => $validated['quarter'],
        ]);

        $scoreColor = $completionPct >= 80 ? '🟢' : ($completionPct >= 50 ? '🟡' : '🔴');
        $notifTitle = "📊 [QUARTERLY PERFORMANCE] {$validated['quarter']} - {$deptCode}";
        $notifMessage = "🏢 <b>{$deptName}</b> has officially submitted their <b>Quarterly Performance Report</b> for <b>{$validated['quarter']} ({$monthsStr}) - {$validated['year']}</b>.\n\n"
                      . "📈 <b>Performance Summary:</b>\n"
                      . "• Score: {$scoreColor} <b>{$completionPct}% Overall Completion</b>\n"
                      . "• Total Monthly Plans: <b>{$totalPlans}</b>\n"
                      . "• Completed (Done): <b>{$donePlans}</b> | Partial: <b>{$partialPlans}</b> | Not Done: <b>{$notDonePlans}</b>\n"
                      . "• Submitted By: <b>" . htmlspecialchars($user->full_name ?? $user->username, ENT_QUOTES, 'UTF-8') . "</b>\n"
                      . "• Timestamp: <b>" . now()->format('Y-m-d H:i') . "</b>\n\n"
                      . "📋 <b>Executive Summary:</b>\n"
                      . "<i>\"" . htmlspecialchars(substr($validated['executive_summary'], 0, 300), ENT_QUOTES, 'UTF-8') . (strlen($validated['executive_summary']) > 300 ? '...' : '') . "\"</i>\n";

        if (!empty($validated['challenges'])) {
            $notifMessage .= "\n⚠️ <b>Key Challenges:</b>\n"
                          . "<i>\"" . htmlspecialchars(substr($validated['challenges'], 0, 200), ENT_QUOTES, 'UTF-8') . "\"</i>\n";
        }

        $replyMarkup = [
            'inline_keyboard' => [
                [
                    ['text' => "📑 View Official Quarterly Voucher", 'url' => $voucherUrl],
                ],
                [
                    ['text' => "🔍 Review & Approve in PMS", 'url' => $reviewUrl],
                ],
            ]
        ];

        notify_both_gm_and_head($deptId, $notifTitle, $notifMessage, $voucherUrl, $replyMarkup);

        return back()->with('success', "Quarterly Performance for {$validated['quarter']} ({$monthsStr}) successfully submitted to General Management! Overall Completion: {$completionPct}%.");
    }

    /**
     * GM Review & Approval / Revision Request
     */
    public function saveFeedback(Request $request)
    {
        $user = Auth::user();
        if (!$user->isGM() && !$user->isSuperAdmin()) {
            abort(403, 'Only General Management or Super Administrator can review quarterly submissions.');
        }

        $validated = $request->validate([
            'submission_id' => 'required|exists:quarterly_submissions,id',
            'approval_status' => 'required|in:APPROVED,REVISION_REQUESTED',
            'gm_feedback' => 'required|string|min:5',
        ]);

        $submission = QuarterlySubmission::with('department')->findOrFail($validated['submission_id']);
        $submission->approval_status = $validated['approval_status'];
        $submission->gm_feedback = $validated['gm_feedback'];
        $submission->gm_feedback_at = now();
        $submission->gm_reviewed_by = $user->id;
        $submission->save();

        AuditLog::log('GM_REVIEW_QUARTERLY', 'quarterly', "GM {$validated['approval_status']} for {$submission->department->department_code} ({$submission->quarter} {$submission->year})", $submission->id);

        // Notify Department Head
        $statusIcon = ($validated['approval_status'] === 'APPROVED') ? '✅ [APPROVED]' : '⚠️ [REVISION REQUESTED]';
        $statusLabel = ($validated['approval_status'] === 'APPROVED') ? 'APPROVED' : 'NEEDS REVISION';
        $voucherUrl = route('quarterly.voucher', [
            'department_id' => $submission->department_id,
            'year' => $submission->year,
            'quarter' => $submission->quarter,
        ]);

        $notifTitle = "{$statusIcon} GM Quarterly Review: {$submission->department->department_code}";
        $notifMessage = "General Management has reviewed your <b>Quarterly Performance Report</b> for <b>{$submission->quarter} ({$submission->months}) - {$submission->year}</b>.\n\n"
                      . "⚖️ <b>Review Decision:</b> <b>{$statusLabel}</b>\n"
                      . "💬 <b>GM Directives & Feedback:</b>\n"
                      . "<i>\"" . htmlspecialchars($validated['gm_feedback'], ENT_QUOTES, 'UTF-8') . "\"</i>\n\n"
                      . "👤 <b>Reviewed By:</b> " . htmlspecialchars($user->full_name ?? $user->username, ENT_QUOTES, 'UTF-8') . " (" . now()->format('Y-m-d H:i') . ")";

        $replyMarkup = [
            'inline_keyboard' => [
                [
                    ['text' => "📑 View Updated Quarterly Voucher", 'url' => $voucherUrl],
                ],
            ]
        ];

        notify_department_head($submission->department_id, $notifTitle, $notifMessage, $voucherUrl, $replyMarkup);

        return back()->with('success', "Quarterly review saved and officially communicated to {$submission->department->department_name}!");
    }

    /**
     * Export Quarterly Performance Data to Excel (UTF-8 CSV format)
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $deptId = (int)$request->query('department_id', $user->department_id ?: 1);

        if (!$user->canAccessAllDepartments() && $user->department_id != $deptId) {
            $deptId = $user->department_id;
        }

        $department = Department::findOrFail($deptId);
        $selectedYear = $request->query('year', kaldis_setting('current_planning_year', config('kaldis.current_planning_year')));
        $quarters = get_ethiopian_quarters();
        $selectedQuarter = $request->query('quarter', 'Q1');
        $quarterInfo = $quarters[$selectedQuarter] ?? $quarters['Q1'];
        $quarterMonths = $quarterInfo['months'];

        $plans = MonthlyPlan::with('annualGoal')
            ->where('department_id', $deptId)
            ->where('year', $selectedYear)
            ->whereIn('month', $quarterMonths)
            ->orderByRaw("FIELD(month, '" . implode("','", $quarterMonths) . "')")
            ->orderBy('id')
            ->get();

        $submission = QuarterlySubmission::where('department_id', $deptId)
            ->where('year', $selectedYear)
            ->where('quarter', $selectedQuarter)
            ->first();

        $filename = "Kaldis_Quarterly_Performance_{$department->department_code}_{$selectedQuarter}_{$selectedYear}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($department, $selectedYear, $selectedQuarter, $quarterInfo, $plans, $submission) {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM so Excel opens Ethiopian Amharic characters flawlessly
            fputs($file, "\xEF\xBB\xBF");

            // Company & Report Metadata Headers
            fputcsv($file, ['KALDIS COFFEE PLC - STRATEGIC PLANNING & PERFORMANCE SYSTEM']);
            fputcsv($file, ['QUARTERLY PERFORMANCE REPORT (BULK 3-MONTH SUBMISSION)']);
            fputcsv($file, ['Department', $department->department_name . ' (' . $department->department_code . ')']);
            fputcsv($file, ['Planning Year', $selectedYear]);
            fputcsv($file, ['Quarter', $quarterInfo['name'] . ' [' . implode(', ', $quarterInfo['months']) . ']']);
            fputcsv($file, ['Submission Status', $submission ? $submission->status : 'NOT_SUBMITTED']);
            fputcsv($file, ['Approval Status', $submission ? $submission->approval_status : 'PENDING']);
            fputcsv($file, ['Overall Completion Rate', ($submission ? $submission->completion_percentage : 0) . '%']);
            fputcsv($file, ['Generated On', now()->format('Y-m-d H:i:s')]);
            fputcsv($file, []);

            // Executive Summary
            if ($submission && !empty($submission->executive_summary)) {
                fputcsv($file, ['EXECUTIVE SUMMARY']);
                fputcsv($file, [$submission->executive_summary]);
                fputcsv($file, []);
            }

            // Key Challenges
            if ($submission && !empty($submission->challenges)) {
                fputcsv($file, ['KEY CHALLENGES ENCOUNTERED']);
                fputcsv($file, [$submission->challenges]);
                fputcsv($file, []);
            }

            // Next Quarter Priorities
            if ($submission && !empty($submission->next_quarter_priorities)) {
                fputcsv($file, ['NEXT QUARTER PRIORITIES']);
                fputcsv($file, [$submission->next_quarter_priorities]);
                fputcsv($file, []);
            }

            // GM Feedback
            if ($submission && !empty($submission->gm_feedback)) {
                fputcsv($file, ['GM DIRECTIVES & FEEDBACK']);
                fputcsv($file, [$submission->gm_feedback]);
                fputcsv($file, []);
            }

            // Table Header
            fputcsv($file, [
                'Plan ID',
                'Month',
                'Type',
                'Annual Strategic Goal Alignment',
                'Monthly Plan Title',
                'Planned Monthly Target',
                'Definition of Done',
                'Responsible Person',
                'Performance Status',
                'Achievement %',
                'Actual Output / Quantitative Result',
                'Execution Remarks & Notes',
            ]);

            foreach ($plans as $p) {
                fputcsv($file, [
                    '#MP-' . str_pad($p->id, 4, '0', STR_PAD_LEFT),
                    $p->month,
                    $p->plan_type,
                    $p->annualGoal ? ($p->annualGoal->code . ' - ' . $p->annualGoal->title) : 'Direct Operational',
                    $p->title,
                    $p->monthly_target ?: 'N/A',
                    $p->definition_of_done ?: 'N/A',
                    $p->responsible_person ?: 'Department Head',
                    $p->performance_status ?: 'PENDING',
                    $p->achievement_percentage . '%',
                    $p->actual_result ?: 'N/A',
                    $p->completion_notes ?: '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

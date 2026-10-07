<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\ReasonCategory;
use App\Models\Submission;
use App\Models\WeeklyAchievement;
use App\Models\WeeklyChallenge;
use App\Models\WeeklyReport;
use App\Models\WeeklyTask;
use App\Models\WeeklyTaskResult;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PerformanceController extends Controller
{
    public function execution(Request $request)
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

        $tasks = WeeklyTask::with(['result.reasonCategory', 'monthlyPlan.annualGoal'])
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

        $submission = Submission::where('department_id', $deptId)
            ->where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->where('week_number', $selectedWeek)
            ->first();

        $reasonCategories = ReasonCategory::where('is_active', 1)->orderBy('display_order')->get();

        $totalTasks = $tasks->count();
        $doneTasks = $tasks->filter(fn($t) => $t->result && $t->result->result === 'DONE')->count();
        $notDoneTasks = $tasks->filter(fn($t) => $t->result && $t->result->result === 'NOT_DONE')->count();
        $unratedTasks = $totalTasks - ($doneTasks + $notDoneTasks);

        $completionRate = $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100, 1) : 0;

        $allDepartments = $user->canAccessAllDepartments()
            ? Department::where('active', 1)->orderBy('department_name')->get()
            : collect([$department]);

        return view('weekly.performance', compact(
            'department',
            'tasks',
            'achievements',
            'challenges',
            'submission',
            'reasonCategories',
            'totalTasks',
            'doneTasks',
            'notDoneTasks',
            'unratedTasks',
            'completionRate',
            'allDepartments',
            'selectedYear',
            'selectedMonth',
            'selectedWeek'
        ));
    }

    public function saveEvaluation(Request $request)
    {
        $statusInput = $request->input('status');
        // Accept either DONE/NOT DONE or DONE/NOT_DONE
        $status = ($statusInput === 'NOT DONE' || $statusInput === 'NOT_DONE') ? 'NOT_DONE' : 'DONE';

        $validated = $request->validate([
            'weekly_task_id' => 'required|exists:weekly_tasks,id',
            'status' => 'required',
            'actual_output' => 'nullable|string',
            'reason_category_id' => 'required_if:status,NOT DONE,NOT_DONE|nullable|exists:reason_categories,id',
            'not_done_reason_id' => 'nullable|exists:reason_categories,id',
            'reason_explanation' => 'required_if:status,NOT DONE,NOT_DONE|nullable|string',
            'not_done_explanation' => 'nullable|string',
            'next_action' => 'required_if:status,NOT DONE,NOT_DONE|nullable|string',
            'next_action_deadline' => 'nullable|date',
            'expected_completion_date' => 'nullable|date',
        ]);

        $task = WeeklyTask::findOrFail($validated['weekly_task_id']);
        $user = Auth::user();

        if (!$user->canAccessAllDepartments() && $user->department_id != $task->department_id) {
            abort(403, 'Unauthorized department.');
        }

        $reasonId = $validated['not_done_reason_id'] ?? ($validated['reason_category_id'] ?? null);
        $explanation = $validated['not_done_explanation'] ?? ($validated['reason_explanation'] ?? null);
        $deadline = $validated['expected_completion_date'] ?? ($validated['next_action_deadline'] ?? null);

        $result = WeeklyTaskResult::updateOrCreate(
            ['weekly_task_id' => $task->id],
            [
                'department_id' => $task->department_id,
                'year' => $task->year,
                'month' => $task->month,
                'week_number' => $task->week_number,
                'result' => $status,
                'completed_at' => now(),
                'completed_by' => $user->id,
                'not_done_reason_id' => $status === 'NOT_DONE' ? $reasonId : null,
                'not_done_explanation' => $status === 'NOT_DONE' ? $explanation : null,
                'next_action' => $status === 'NOT_DONE' ? ($validated['next_action'] ?? null) : null,
                'expected_completion_date' => $status === 'NOT_DONE' ? $deadline : null,
            ]
        );

        AuditLog::log(
            'EVALUATE_TASK',
            'performance',
            "Task '{$task->task_title}' evaluated as {$status}",
            $task->id
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'status' => $status,
                'message' => "Task status saved as {$status}.",
            ]);
        }

        return back()->with('success', "Task evaluated as " . ($status === 'NOT_DONE' ? 'NOT DONE' : 'DONE') . ".");
    }

    public function saveAchievement(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'year' => 'required|string',
            'month' => 'required|string',
            'week_number' => 'required|integer|min:1|max:5',
            'achievement_title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'achievement_text' => 'nullable|string',
            'quantified_result' => 'nullable|string|max:100',
        ]);

        $text = $validated['achievement_text'] ?? ($validated['achievement_title'] . (!empty($validated['description']) ? ' - ' . $validated['description'] : ''));

        $ach = WeeklyAchievement::create([
            'department_id' => $validated['department_id'],
            'year' => $validated['year'],
            'month' => $validated['month'],
            'week_number' => $validated['week_number'],
            'achievement_text' => $text,
            'created_by' => Auth::id(),
        ]);

        AuditLog::log('CREATE_ACHIEVEMENT', 'performance', "Recorded achievement", $ach->id);

        return back()->with('success', 'Weekly achievement logged successfully.');
    }

    public function deleteAchievement($id)
    {
        $ach = WeeklyAchievement::findOrFail($id);
        $user = Auth::user();

        if (!$user->canAccessAllDepartments() && $user->department_id != $ach->department_id) {
            abort(403);
        }

        $ach->delete();

        AuditLog::log('DELETE_ACHIEVEMENT', 'performance', "Deleted achievement", $id);
        return back()->with('success', 'Achievement removed.');
    }

    public function saveChallenge(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'year' => 'required|string',
            'month' => 'required|string',
            'week_number' => 'required|integer|min:1|max:5',
            'challenge_title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'challenge_text' => 'nullable|string',
            'related_weekly_task_id' => 'nullable|exists:weekly_tasks,id',
        ]);

        $text = $validated['challenge_text'] ?? ($validated['challenge_title'] . (!empty($validated['description']) ? ' - ' . $validated['description'] : ''));

        $challenge = WeeklyChallenge::create([
            'department_id' => $validated['department_id'],
            'year' => $validated['year'],
            'month' => $validated['month'],
            'week_number' => $validated['week_number'],
            'challenge_text' => $text,
            'related_weekly_task_id' => $validated['related_weekly_task_id'] ?? null,
            'created_by' => Auth::id(),
        ]);

        AuditLog::log('CREATE_CHALLENGE', 'performance', "Recorded challenge", $challenge->id);

        return back()->with('success', 'Weekly challenge logged successfully.');
    }

    public function deleteChallenge($id)
    {
        $challenge = WeeklyChallenge::findOrFail($id);
        $user = Auth::user();

        if (!$user->canAccessAllDepartments() && $user->department_id != $challenge->department_id) {
            abort(403);
        }

        $challenge->delete();

        AuditLog::log('DELETE_CHALLENGE', 'performance', "Deleted challenge", $id);
        return back()->with('success', 'Challenge removed.');
    }

    public function submitReport(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'year' => 'required|string',
            'month' => 'required|string',
            'week_number' => 'required|integer|min:1|max:5',
            'report_summary' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $deptId = $validated['department_id'];
        $user = Auth::user();

        if (!$user->canAccessAllDepartments() && $user->department_id != $deptId) {
            abort(403, 'Unauthorized department.');
        }

        $tasks = WeeklyTask::with('result')
            ->where('department_id', $deptId)
            ->where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->where('week_number', $validated['week_number'])
            ->get();

        $totalTasks = $tasks->count();

        if ($totalTasks === 0) {
            return back()->withErrors(['submission' => 'Cannot submit an empty report. Please add weekly tasks first.']);
        }

        $unratedTasks = $tasks->filter(fn($t) => !$t->result || !in_array($t->result->result, ['DONE', 'NOT_DONE']))->count();

        if ($unratedTasks > 0) {
            return back()->withErrors(['submission' => "Cannot submit report: {$unratedTasks} task(s) have not been evaluated as DONE or NOT DONE."]);
        }

        $doneTasks = $tasks->filter(fn($t) => $t->result->result === 'DONE')->count();
        $notDoneTasks = $tasks->filter(fn($t) => $t->result->result === 'NOT_DONE')->count();
        $completionRate = round(($doneTasks / $totalTasks) * 100, 2);

        // Determine ON_TIME or LATE compliance based on Monday 12:00 PM cutoff
        $deadlineDay = kaldis_setting('weekly_deadline_day', 'Monday');
        $deadlineTimeStr = kaldis_setting('weekly_deadline_time', '12:00');
        
        $now = Carbon::now();
        $deadline = Carbon::parse("this {$deadlineDay} {$deadlineTimeStr}");
        $compliance = $now->lte($deadline) ? 'ON_TIME' : 'LATE';

        DB::transaction(function () use ($validated, $totalTasks, $doneTasks, $notDoneTasks, $completionRate, $compliance, $deadline, $user) {
            WeeklyReport::updateOrCreate(
                [
                    'department_id' => $validated['department_id'],
                    'year' => $validated['year'],
                    'month' => $validated['month'],
                    'week_number' => $validated['week_number'],
                ],
                [
                    'status' => 'SUBMITTED',
                    'total_tasks' => $totalTasks,
                    'done_tasks' => $doneTasks,
                    'not_done_tasks' => $notDoneTasks,
                    'completion_percentage' => $completionRate,
                    'submitted_by' => $user->id,
                    'submitted_at' => now(),
                    'submission_status' => $compliance,
                    'notes' => $validated['notes'] ?? ($validated['report_summary'] ?? ''),
                ]
            );

            Submission::updateOrCreate(
                [
                    'department_id' => $validated['department_id'],
                    'year' => $validated['year'],
                    'month' => $validated['month'],
                    'week_number' => $validated['week_number'],
                ],
                [
                    'deadline_at' => $deadline,
                    'submitted_at' => now(),
                    'status' => $compliance,
                    'submission_channel' => 'WEB',
                    'submitted_by' => $user->id,
                    'remarks' => $validated['notes'] ?? ($validated['report_summary'] ?? ''),
                ]
            );

            AuditLog::log(
                'SUBMIT_WEEKLY_REPORT',
                'performance',
                "Submitted weekly report for Week {$validated['week_number']} ({$compliance}, {$completionRate}%)"
            );
        });

        return back()->with('success', "Weekly report submitted successfully! Status: {$compliance} ({$completionRate}% Completion).");
    }

    public function weeklyScorecard(Request $request)
    {
        $selectedYear = $request->query('year', kaldis_setting('current_planning_year', config('kaldis.current_planning_year')));
        $selectedMonth = $request->query('month', kaldis_setting('current_planning_month', config('kaldis.current_planning_month')));
        $selectedWeek = (int)$request->query('week', 1);

        $departments = Department::where('active', 1)->orderBy('department_name')->get();
        $matrix = [];

        foreach ($departments as $dept) {
            $tasks = WeeklyTask::with('result')
                ->where('department_id', $dept->id)
                ->where('year', $selectedYear)
                ->where('month', $selectedMonth)
                ->where('week_number', $selectedWeek)
                ->get();

            $total = $tasks->count();
            $done = $tasks->filter(fn($t) => $t->result && $t->result->result === 'DONE')->count();
            $notDone = $tasks->filter(fn($t) => $t->result && $t->result->result === 'NOT_DONE')->count();
            $rate = $total > 0 ? round(($done / $total) * 100, 1) : 0;

            $submission = Submission::where('department_id', $dept->id)
                ->where('year', $selectedYear)
                ->where('month', $selectedMonth)
                ->where('week_number', $selectedWeek)
                ->first();

            $matrix[] = [
                'department' => $dept,
                'total' => $total,
                'done' => $done,
                'not_done' => $notDone,
                'rate' => $rate,
                'submission' => $submission,
            ];
        }

        return view('performance.scorecard', compact(
            'departments',
            'matrix',
            'selectedYear',
            'selectedMonth',
            'selectedWeek'
        ));
    }

    public function monthlyScorecard(Request $request)
    {
        $selectedYear = $request->query('year', kaldis_setting('current_planning_year', config('kaldis.current_planning_year')));
        $selectedMonth = $request->query('month', kaldis_setting('current_planning_month', config('kaldis.current_planning_month')));

        $departments = Department::where('active', 1)->orderBy('department_name')->get();
        $monthlyData = [];

        foreach ($departments as $dept) {
            $total = WeeklyTask::where('department_id', $dept->id)
                ->where('year', $selectedYear)
                ->where('month', $selectedMonth)
                ->count();

            $done = DB::table('weekly_tasks as wt')
                ->join('weekly_task_results as wtr', 'wt.id', '=', 'wtr.weekly_task_id')
                ->where('wt.department_id', $dept->id)
                ->where('wt.year', $selectedYear)
                ->where('wt.month', $selectedMonth)
                ->where('wtr.result', 'DONE')
                ->count();

            $notDone = DB::table('weekly_tasks as wt')
                ->join('weekly_task_results as wtr', 'wt.id', '=', 'wtr.weekly_task_id')
                ->where('wt.department_id', $dept->id)
                ->where('wt.year', $selectedYear)
                ->where('wt.month', $selectedMonth)
                ->where('wtr.result', 'NOT_DONE')
                ->count();

            $rate = $total > 0 ? round(($done / $total) * 100, 1) : 0;

            $monthlyData[] = [
                'department' => $dept,
                'total' => $total,
                'done' => $done,
                'not_done' => $notDone,
                'rate' => $rate,
            ];
        }

        return view('performance.monthly', compact('departments', 'monthlyData', 'selectedYear', 'selectedMonth'));
    }

    public function notDoneAnalysis(Request $request)
    {
        $selectedYear = $request->query('year', kaldis_setting('current_planning_year', config('kaldis.current_planning_year')));
        $selectedMonth = $request->query('month', kaldis_setting('current_planning_month', config('kaldis.current_planning_month')));
        $categoryId = $request->query('category_id');

        $query = DB::table('weekly_tasks as wt')
            ->join('weekly_task_results as wtr', 'wt.id', '=', 'wtr.weekly_task_id')
            ->join('departments as d', 'wt.department_id', '=', 'd.id')
            ->leftJoin('reason_categories as rc', 'wtr.not_done_reason_id', '=', 'rc.id')
            ->where('wt.year', $selectedYear)
            ->where('wt.month', $selectedMonth)
            ->where('wtr.result', 'NOT_DONE');

        if ($categoryId) {
            $query->where('wtr.not_done_reason_id', $categoryId);
        }

        $notDoneList = $query->select(
            'wt.id as task_id',
            'wt.task_title',
            'wt.week_number',
            'wt.plan_type',
            'd.department_name',
            'd.department_code',
            'rc.name as category_name',
            'wtr.not_done_explanation as reason_explanation',
            'wtr.next_action',
            'wtr.expected_completion_date as next_action_deadline'
        )->get();

        $categories = ReasonCategory::where('is_active', 1)->orderBy('display_order')->get();

        return view('performance.not_done', compact('notDoneList', 'categories', 'selectedYear', 'selectedMonth', 'categoryId'));
    }

    public function achievementsFeed(Request $request)
    {
        $selectedYear = $request->query('year', kaldis_setting('current_planning_year', config('kaldis.current_planning_year')));
        $selectedMonth = $request->query('month', kaldis_setting('current_planning_month', config('kaldis.current_planning_month')));

        $achievements = WeeklyAchievement::with('department')
            ->where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->orderByDesc('id')
            ->get();

        return view('performance.achievements', compact('achievements', 'selectedYear', 'selectedMonth'));
    }

    public function challengesFeed(Request $request)
    {
        $selectedYear = $request->query('year', kaldis_setting('current_planning_year', config('kaldis.current_planning_year')));
        $selectedMonth = $request->query('month', kaldis_setting('current_planning_month', config('kaldis.current_planning_month')));

        $challenges = WeeklyChallenge::with('department')
            ->where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->orderByDesc('id')
            ->get();

        return view('performance.challenges', compact('challenges', 'selectedYear', 'selectedMonth'));
    }
}

@extends('layouts.app')

@section('title', 'Weekly Performance Sheet - ' . $department->department_name)

@section('content')
<!-- Header -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Weekly Performance Execution</h4>
        <p class="text-muted small mb-0">{{ $department->department_name }} &bull; Week {{ $selectedWeek }} ({{ $selectedMonth }} {{ $selectedYear }})</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if(kaldis_setting('voucher_enabled', 'true') === 'true')
            <a href="{{ route('weekly.voucher', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'week' => $selectedWeek]) }}" class="btn btn-outline-dark btn-sm" target="_blank" title="Open Official Voucher in new tab">
                <i class="fas fa-file-invoice me-1"></i> View Official Voucher
            </a>
        @endif
        <a href="{{ route('weekly.plans', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'week' => $selectedWeek]) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-tasks me-1"></i> Edit Planned Tasks
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar mb-4">
    <form method="GET" action="{{ route('weekly.performance') }}" class="row g-2 align-items-center">
        @if(auth()->user()->canAccessAllDepartments())
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Department</label>
                <select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($allDepartments as $d)
                        <option value="{{ $d->id }}" {{ $department->id == $d->id ? 'selected' : '' }}>
                            {{ $d->department_name }} ({{ $d->department_code }})
                        </option>
                    @endforeach
                </select>
            </div>
        @else
            <input type="hidden" name="department_id" value="{{ $department->id }}">
        @endif

        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Week Number</label>
            <select name="week" class="form-select form-select-sm" onchange="this.form.submit()">
                @for($w = 1; $w <= 5; $w++)
                    <option value="{{ $w }}" {{ $selectedWeek === $w ? 'selected' : '' }}>Week {{ $w }}</option>
                @endfor
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Planning Month</label>
            <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach(config('kaldis.ethiopian_months') as $m)
                    <option value="{{ $m }}" {{ $selectedMonth === $m ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Planning Year</label>
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach(config('kaldis.planning_years') as $y)
                    <option value="{{ $y }}" {{ $selectedYear === $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
    </form>
</div>

<!-- Submission & Delay Banners -->
@if($submission && ($submission->isDelayApproved() || $submission->status === 'DELAY_APPROVED'))
    <div class="alert alert-info border-0 shadow-sm d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="fas fa-shield-alt text-primary fs-5"></i>
                <strong class="text-dark">Weekly Performance Report Delay Approved by GM</strong>
                {!! format_submission_status_badge('DELAY_APPROVED', 'APPROVED', $submission->delay_reason) !!}
                @if(!empty($submission->approval_status))
                    <span class="badge {{ $submission->approval_status === 'APPROVED' ? 'bg-success' : 'bg-secondary' }}">
                        GM: {{ $submission->approval_status }}
                    </span>
                @endif
            </div>
            <div class="small text-muted">
                General Management has approved the delay for Week {{ $selectedWeek }} performance reporting. This report is officially excused.
            </div>
            @if(!empty($submission->delay_reason))
                <div class="small text-dark mt-1"><strong>Justification:</strong> {{ $submission->delay_reason }}</div>
            @endif
            @if(!empty($submission->delay_gm_notes))
                <div class="small text-primary mt-0.5"><strong>GM Remarks:</strong> {{ $submission->delay_gm_notes }}</div>
            @endif
        </div>
        <div class="small text-muted text-nowrap">
            Approved {{ $submission->delay_approved_at ? format_ethiopian_datetime($submission->delay_approved_at) : '' }}
        </div>
    </div>
@elseif($submission && $submission->isDelayPending())
    <div class="alert alert-warning border-0 shadow-sm d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="fas fa-hourglass-half text-warning fs-5"></i>
                <strong class="text-dark">Delay Request Submitted (Pending GM Review)</strong>
                {!! format_submission_status_badge($submission->status, 'PENDING', $submission->delay_reason) !!}
            </div>
            <div class="small text-muted">
                Your delay justification for Week {{ $selectedWeek }} performance execution has been submitted to General Management.
            </div>
            <div class="small text-dark mt-1"><strong>Submitted Justification:</strong> {{ $submission->delay_reason }}</div>
        </div>
        <div class="small text-muted text-nowrap">
            Pending GM Decision
        </div>
    </div>
@elseif($submission && in_array($submission->status, ['ON_TIME', 'LATE', 'SUBMITTED']))
    <div class="alert alert-success border-0 shadow-sm d-flex justify-content-between align-items-center mb-4">
        <div>
            <i class="fas fa-check-double me-2"></i>
            <strong>Report Submitted:</strong> Week {{ $selectedWeek }} performance report has been officially submitted.
            {!! format_submission_status_badge($submission->status, $submission->delay_status, $submission->delay_reason) !!}
            @if(!empty($submission->approval_status))
                <span class="badge {{ $submission->approval_status === 'APPROVED' ? 'bg-success' : ($submission->approval_status === 'REVISION_REQUESTED' ? 'bg-danger' : 'bg-secondary') }} ms-1">
                    GM: {{ $submission->approval_status }}
                </span>
            @endif
        </div>
        <div class="small text-muted">
            Submitted by {{ $submission->submitter->full_name ?? 'Department Head' }} on {{ $submission->submitted_at ? format_ethiopian_datetime($submission->submitted_at) : '' }}
        </div>
    </div>
@endif

@if(!empty($submission->gm_feedback))
    <div class="card border-primary mb-4 shadow-sm">
        <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
            <span class="fw-bold small"><i class="fas fa-comment-dots me-1"></i> Executive Review Comments from General Manager</span>
            <span class="small opacity-75">{{ $submission->gm_feedback_at ? format_ethiopian_datetime($submission->gm_feedback_at) : '' }}</span>
        </div>
        <div class="card-body bg-light py-2 px-3 small">
            <p class="mb-0 text-dark">{{ $submission->gm_feedback }}</p>
        </div>
    </div>
@endif

<!-- Sleek Metrics Summary Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <div class="row g-3 align-items-center text-center text-sm-start">
            <div class="col-6 col-md-3 border-end-md">
                <span class="text-muted small text-uppercase fw-semibold d-block">Planned Tasks</span>
                <div class="d-flex align-items-baseline gap-2 justify-content-center justify-content-sm-start">
                    <span class="fs-4 fw-bold text-dark">{{ $totalTasks }}</span>
                    @if($unratedTasks > 0)
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle small">{{ $unratedTasks }} pending</span>
                    @else
                        <span class="badge bg-success-subtle text-success border border-success-subtle small">All evaluated</span>
                    @endif
                </div>
            </div>

            <div class="col-6 col-md-3 border-end-md">
                <span class="text-muted small text-uppercase fw-semibold d-block">Completed (DONE)</span>
                <div class="d-flex align-items-baseline gap-2 justify-content-center justify-content-sm-start">
                    <span class="fs-4 fw-bold text-success">{{ $doneTasks }}</span>
                    <span class="small text-muted">achieved</span>
                </div>
            </div>

            <div class="col-6 col-md-3 border-end-md">
                <span class="text-muted small text-uppercase fw-semibold d-block">Not Done</span>
                <div class="d-flex align-items-baseline gap-2 justify-content-center justify-content-sm-start">
                    <span class="fs-4 fw-bold text-danger">{{ $notDoneTasks }}</span>
                    <span class="small text-muted">with reason</span>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small text-uppercase fw-semibold">Completion</span>
                    <span class="fw-bold {{ $completionRate >= 80 ? 'text-success' : ($completionRate >= 50 ? 'text-primary' : 'text-danger') }}">{{ $completionRate }}%</span>
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar {{ $completionRate >= 80 ? 'bg-success' : ($completionRate >= 50 ? 'bg-primary' : 'bg-danger') }}" 
                         role="progressbar" 
                         style="width: {{ $completionRate }}%" 
                         aria-valuenow="{{ $completionRate }}" 
                         aria-valuemin="0" 
                         aria-valuemax="100"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Task Execution & Evaluation Table -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fas fa-check-circle text-primary me-2"></i>Weekly Tasks Evaluation
            </h6>
        </div>
        <span class="badge bg-light text-secondary border">
            {{ $doneTasks + $notDoneTasks }} / {{ $totalTasks }} Evaluated
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 130px;">Type</th>
                    <th>Task &amp; Target</th>
                    <th style="width: 140px;" class="text-center">Status</th>
                    <th>Evaluation Details</th>
                    <th style="width: 170px;" class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                    <tr>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <div>
                                    @if($task->plan_type === 'STRATEGY')
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Strategy</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border">Operational</span>
                                    @endif
                                </div>
                                <div>
                                    @if($task->task_type === 'NEW_TASK')
                                        <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 0.68rem;">New Task</span>
                                    @else
                                        <span class="badge bg-light text-muted border" style="font-size: 0.68rem;">Planned</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $task->task_title }}</div>
                            <div class="small text-muted mt-0.5">
                                <span class="text-secondary fw-semibold">Target:</span> {{ $task->expected_result }}
                            </div>

                            @if($task->result && $task->result->gm_comment)
                                <div class="mt-1.5 p-2 rounded border bg-warning-subtle text-dark" style="font-size: 0.78rem;">
                                    <div class="d-flex align-items-center gap-1 fw-semibold text-warning-emphasis mb-0.5">
                                        <i class="fas fa-comment-dots text-warning"></i>
                                        <span>GM Feedback:</span>
                                    </div>
                                    <div class="text-secondary" style="white-space: pre-wrap;">{{ $task->result->gm_comment }}</div>
                                </div>
                            @elseif($task->gm_comment)
                                <div class="mt-1.5 p-2 rounded border bg-warning-subtle text-dark" style="font-size: 0.78rem;">
                                    <div class="d-flex align-items-center gap-1 fw-semibold text-warning-emphasis mb-0.5">
                                        <i class="fas fa-comment-dots text-warning"></i>
                                        <span>GM Feedback:</span>
                                    </div>
                                    <div class="text-secondary" style="white-space: pre-wrap;">{{ $task->gm_comment }}</div>
                                </div>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($task->result && $task->result->isDone())
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <i class="fas fa-check me-1"></i> DONE
                                </span>
                            @elseif($task->result && $task->result->isNotDone())
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                    <i class="fas fa-times me-1"></i> NOT DONE
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                    <i class="fas fa-clock me-1"></i> PENDING
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($task->result && $task->result->isDone())
                                <span class="small text-success fw-medium">
                                    <i class="fas fa-check-circle me-1"></i> Output Achieved
                                </span>
                            @elseif($task->result && $task->result->isNotDone())
                                <div class="small">
                                    <div class="text-danger fw-semibold">Reason: <span class="text-secondary fw-normal">{{ $task->result->not_done_explanation }}</span></div>
                                    <div class="text-primary fw-semibold mt-0.5">Next Action: <span class="text-secondary fw-normal">{{ $task->result->next_action }}</span></div>
                                </div>
                            @else
                                <span class="text-muted small fst-italic">Awaiting evaluation</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex align-items-center gap-1">
                                @if(auth()->user()->isGM() || auth()->user()->isSuperAdmin())
                                    <button type="button" class="btn btn-xs btn-outline-warning py-1 px-1.5" 
                                            onclick="openGmPerformanceCommentModal({{ $task->result ? $task->result->id : 0 }}, {{ $task->id }}, '{{ addslashes($task->task_title) }}', '{{ addslashes(($task->result ? $task->result->gm_comment : $task->gm_comment) ?? '') }}')" 
                                            title="Add / Edit GM Comment">
                                        <i class="fas fa-comment-dots"></i>
                                    </button>
                                @endif

                                @if($task->result && in_array($task->result->result, ['DONE', 'NOT_DONE']))
                                    <span class="badge bg-light text-secondary border py-1.5 px-2" title="Finalized (Permanent)">
                                        <i class="fas fa-lock text-muted me-1"></i> {{ $task->result->result === 'DONE' ? 'DONE' : 'NOT DONE' }}
                                    </span>
                                @else
                                    <div class="btn-group btn-group-sm">
                                        <form method="POST" action="{{ route('weekly.performance.evaluate') }}" class="d-inline" onsubmit="return confirm('Confirm marking task as DONE? Evaluation is permanent.');">
                                            @csrf
                                            <input type="hidden" name="weekly_task_id" value="{{ $task->id }}">
                                            <input type="hidden" name="status" value="DONE">
                                            <button type="submit" class="btn btn-outline-success btn-sm py-1 px-2" title="Mark Done">
                                                <i class="fas fa-check me-0.5"></i> Done
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#notDoneModal"
                                                onclick="setupNotDoneModal({{ $task->id }}, '{{ addslashes($task->task_title) }}')"
                                                title="Mark Not Done">
                                            <i class="fas fa-times me-0.5"></i> Not Done
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <i class="fas fa-tasks fa-2x text-muted opacity-50 mb-2 d-block"></i>
                            <h6 class="fw-semibold text-dark">No Tasks Planned for Week {{ $selectedWeek }}</h6>
                            <p class="small text-muted mb-3">Please schedule tasks in the Weekly Plans module before recording performance.</p>
                            <a href="{{ route('weekly.plans', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'week' => $selectedWeek]) }}" class="btn btn-sm btn-kaldis">
                                <i class="fas fa-plus me-1"></i> Go to Weekly Plans
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Compact Achievements & Challenges -->
<div class="row g-3 mb-4">
    <!-- Achievements -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2.5 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-semibold small text-dark">
                    <i class="fas fa-trophy text-warning me-1.5"></i> Independent Achievements
                </span>
                <button type="button" class="btn btn-xs btn-outline-secondary py-0.5 px-2" data-bs-toggle="modal" data-bs-target="#addAchievementModal">
                    <i class="fas fa-plus me-1"></i> Add
                </button>
            </div>
            <div class="card-body p-0">
                @forelse($achievements as $ach)
                    <div class="p-2.5 border-bottom d-flex justify-content-between align-items-start gap-2">
                        <div class="small text-dark">{{ $ach->achievement_text }}</div>
                        <form method="POST" action="{{ route('weekly.achievements.destroy', $ach->id) }}" onsubmit="return confirm('Remove achievement?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm text-danger border-0 p-0" title="Delete"><i class="fas fa-times"></i></button>
                        </form>
                    </div>
                @empty
                    <div class="text-muted small text-center py-3">
                        No independent achievements logged for Week {{ $selectedWeek }}.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Challenges -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2.5 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-semibold small text-dark">
                    <i class="fas fa-exclamation-triangle text-danger me-1.5"></i> Operational Challenges
                </span>
                <button type="button" class="btn btn-xs btn-outline-secondary py-0.5 px-2" data-bs-toggle="modal" data-bs-target="#addChallengeModal">
                    <i class="fas fa-plus me-1"></i> Log Challenge
                </button>
            </div>
            <div class="card-body p-0">
                @forelse($challenges as $ch)
                    <div class="p-2.5 border-bottom d-flex justify-content-between align-items-start gap-2">
                        <div class="small text-dark">{{ $ch->challenge_text }}</div>
                        <form method="POST" action="{{ route('weekly.challenges.destroy', $ch->id) }}" onsubmit="return confirm('Remove challenge?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm text-danger border-0 p-0" title="Delete"><i class="fas fa-times"></i></button>
                        </form>
                    </div>
                @empty
                    <div class="text-muted small text-center py-3">
                        No operational challenges logged for Week {{ $selectedWeek }}.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Submit to General Manager -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="fas fa-paper-plane text-primary me-2"></i>Submit Weekly Cycle to GM
        </h6>
        <span class="badge bg-light text-secondary border">
            <i class="fas fa-clock text-warning me-1"></i> Monday 12:30 PM (6:30 LT) Cutoff
        </span>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="{{ route('weekly.report.submit') }}">
            @csrf
            <input type="hidden" name="department_id" value="{{ $department->id }}">
            <input type="hidden" name="year" value="{{ $selectedYear }}">
            <input type="hidden" name="month" value="{{ $selectedMonth }}">
            <input type="hidden" name="week_number" value="{{ $selectedWeek }}">

            <!-- Evaluation Readiness Indicator -->
            @if($totalTasks === 0)
                <div class="alert alert-warning py-2.5 px-3 small mb-3 border-0">
                    <i class="fas fa-exclamation-triangle me-1 text-warning"></i>
                    <strong>No tasks scheduled:</strong> Please add weekly tasks before submitting to the General Manager.
                </div>
            @elseif($unratedTasks > 0)
                <div class="alert alert-warning py-2.5 px-3 small mb-3 border-0 d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-clock me-1 text-warning"></i>
                        <strong>Evaluation in progress:</strong> {{ $unratedTasks }} task(s) awaiting evaluation before report can be submitted.
                    </div>
                    <span class="badge bg-warning text-dark">{{ $doneTasks + $notDoneTasks }} / {{ $totalTasks }} Evaluated</span>
                </div>
            @else
                <div class="alert alert-success py-2.5 px-3 small mb-3 border-0 d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-check-circle me-1 text-success"></i>
                        <strong>All tasks evaluated!</strong> Ready to submit Week {{ $selectedWeek }} performance report ({{ $doneTasks }} Done, {{ $notDoneTasks }} Not Done &bull; <strong>{{ $completionRate }}%</strong> completion rate).
                    </div>
                    <span class="badge bg-success">Ready</span>
                </div>
            @endif

            <!-- Upcoming Week Plan Link Strip -->
            <div class="d-flex align-items-center justify-content-between bg-light p-2.5 rounded border small mb-3">
                <div>
                    <i class="fas fa-calendar-alt text-primary me-2"></i>
                    <strong>Week {{ $nextWeek }} Plan:</strong>
                    @if(($nextWeekTasksCount ?? 0) > 0)
                        <span>{{ $nextWeekTasksCount }} task(s) scheduled. <span class="text-muted">(Will be bundled with this submission)</span></span>
                    @else
                        <span class="text-muted">No tasks scheduled yet for Week {{ $nextWeek }}.</span>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('weekly.plans', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'week' => $nextWeek]) }}" class="btn btn-xs btn-outline-primary py-0.5 px-2" style="font-size: 0.75rem;">
                        <i class="fas fa-tasks me-1"></i> Manage Week {{ $nextWeek }}
                    </a>
                    @if(($nextWeekTasksCount ?? 0) > 0 && kaldis_setting('voucher_enabled', 'true') === 'true')
                        <a href="{{ route('weekly.voucher', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'week' => $nextWeek, 'type' => 'plan']) }}" target="_blank" class="btn btn-xs btn-outline-secondary py-0.5 px-2" style="font-size: 0.75rem;">
                            <i class="fas fa-file-invoice me-1"></i> Preview Plan Voucher
                        </a>
                    @endif
                </div>
            </div>

            <!-- Department Remarks / Executive Summary -->
            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Department Remarks for GM (Optional)</label>
                <textarea name="report_summary" class="form-control" rows="2" placeholder="Overall execution summary, operational highlights, or notes for General Management..."></textarea>
            </div>

            <!-- Delay Justification (if late or requesting extension) -->
            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">
                    <i class="fas fa-clock text-warning me-1"></i> Delay Reason / Justification (If submitting past Monday 12:30 PM cutoff)
                </label>
                <textarea name="delay_reason" class="form-control" rows="2" placeholder="State operational reason for delay. If approved by GM, this report will NOT be marked as Not Submitted.">{{ $submission->delay_reason ?? ($report->delay_reason ?? '') }}</textarea>
            </div>

            <!-- Submit Action -->
            <div class="d-flex justify-content-end align-items-center pt-2">
                <button type="submit" class="btn btn-kaldis px-4" {{ ($totalTasks === 0 || $unratedTasks > 0) ? 'disabled' : '' }}>
                    <i class="fas fa-paper-plane me-1"></i> Submit Report to GM
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Submit Weekly Performance Delay Reason -->
<div class="modal fade" id="weeklyPerfDelayReasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('weekly.performance.delay_reason') }}">
                @csrf
                <input type="hidden" name="department_id" value="{{ $department->id }}">
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                <input type="hidden" name="month" value="{{ $selectedMonth }}">
                <input type="hidden" name="week_number" value="{{ $selectedWeek }}">

                <div class="modal-header bg-warning bg-opacity-10 border-bottom border-warning border-opacity-25">
                    <h5 class="modal-title fw-bold text-dark fs-6">
                        <i class="fas fa-clock text-warning me-2"></i> Submit Weekly Performance Delay Reason to GM
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 small mb-3 border-0">
                        <i class="fas fa-shield-alt text-primary me-1"></i>
                        <strong>Executive Governance Rule:</strong> If you have an operational delay submitting the Week {{ $selectedWeek }} performance report and the <strong>General Manager Approves</strong>, this report will <strong>NOT</strong> be recorded as "Not Submitted".
                    </div>

                    <div class="bg-light p-3 rounded-3 mb-3 border small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Department:</span>
                            <span class="fw-bold text-dark">{{ $department->department_name }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Week Cycle:</span>
                            <span class="fw-semibold text-dark">Week {{ $selectedWeek }} ({{ $selectedMonth }} {{ $selectedYear }})</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Current Delay Status:</span>
                            <span>{!! format_submission_status_badge($submission->status ?? null, $submission->delay_status ?? null, $submission->delay_reason ?? null) !!}</span>
                        </div>
                        @if($submission && !empty($submission->delay_gm_notes))
                            <div class="pt-2 border-top mt-2 text-primary">
                                <strong>Last GM Remarks:</strong> {{ $submission->delay_gm_notes }}
                            </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">
                            Detailed Reason for Delay / Reporting Justification <span class="text-danger">*</span>
                        </label>
                        <textarea name="delay_reason" class="form-control" rows="3" required placeholder="State why the weekly execution report could not be submitted by Monday 12:30 PM (6:30 LT)...">{{ $submission->delay_reason ?? ($report->delay_reason ?? '') }}</textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-sm btn-warning fw-semibold">
                        <i class="fas fa-paper-plane me-1"></i> Submit Justification to GM
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Not Done Evaluation -->
<div class="modal fade" id="notDoneModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('weekly.performance.evaluate') }}">
                @csrf
                <input type="hidden" name="weekly_task_id" id="notDoneTaskId">
                <input type="hidden" name="status" value="NOT DONE">

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark fs-6"><i class="fas fa-exclamation-triangle text-danger me-2"></i> Record NOT DONE Reason</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning py-2 px-3 small mb-3 border-0">
                        <i class="fas fa-lock me-1"></i> <strong>Permanent Decision:</strong> In accordance with company governance, binary task performance evaluations are permanent. Once recorded as NOT DONE, this evaluation cannot be modified.
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Task Title</label>
                        <div id="notDoneTaskTitle" class="fw-bold text-dark"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Detailed Explanation <span class="text-danger">*</span></label>
                        <textarea name="reason_explanation" class="form-control" rows="2" placeholder="Specific explanation of why the target was not reached..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Corrective Next Action <span class="text-danger">*</span></label>
                        <textarea name="next_action" class="form-control" rows="2" placeholder="What specific action will be taken to resolve this?" required></textarea>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Target Completion Date</label>
                            <input type="date" name="next_action_deadline" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger">Save NOT DONE Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add Achievement -->
<div class="modal fade" id="addAchievementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('weekly.achievements.store') }}">
                @csrf
                <input type="hidden" name="department_id" value="{{ $department->id }}">
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                <input type="hidden" name="month" value="{{ $selectedMonth }}">
                <input type="hidden" name="week_number" value="{{ $selectedWeek }}">

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark fs-6"><i class="fas fa-trophy text-warning me-2"></i> Log Independent Achievement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Achievement Text <span class="text-danger">*</span></label>
                        <textarea name="achievement_text" class="form-control" rows="3" placeholder="Describe the accomplishment and impact..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-kaldis">Save Achievement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add Challenge -->
<div class="modal fade" id="addChallengeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('weekly.challenges.store') }}">
                @csrf
                <input type="hidden" name="department_id" value="{{ $department->id }}">
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                <input type="hidden" name="month" value="{{ $selectedMonth }}">
                <input type="hidden" name="week_number" value="{{ $selectedWeek }}">

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark fs-6"><i class="fas fa-flag text-danger me-2"></i> Log Operational Challenge</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Challenge Details <span class="text-danger">*</span></label>
                        <textarea name="challenge_text" class="form-control" rows="3" placeholder="Details of the operational blocker and needed resolution..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger">Save Challenge</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: GM Performance Feedback & Comment -->
<div class="modal fade" id="gmCommentPerformanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="gmCommentPerformanceForm" action="">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark fs-6">
                        <i class="fas fa-comment-dots text-warning me-2"></i> GM Feedback &amp; Comment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-secondary small mb-2">Item: <strong id="gmCommentPerfTaskTitle" class="text-dark"></strong></p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">General Manager Comment / Instructions</label>
                        <textarea name="gm_comment" id="gmCommentPerfInput" class="form-control" rows="4" placeholder="Enter performance feedback, observations, or directives..."></textarea>
                        <div class="small text-muted mt-1">Leave empty and save to remove the comment.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-warning text-dark fw-bold">
                        <i class="fas fa-save me-1"></i> Save GM Comment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function setupNotDoneModal(taskId, title) {
        document.getElementById('notDoneTaskId').value = taskId;
        document.getElementById('notDoneTaskTitle').textContent = title;
        const form = document.querySelector('#notDoneModal form');
        if (form) {
            const exp = form.querySelector('textarea[name="reason_explanation"]');
            if (exp) exp.value = '';
            const next = form.querySelector('textarea[name="next_action"]');
            if (next) next.value = '';
            const dl = form.querySelector('input[name="next_action_deadline"]');
            if (dl) dl.value = '';
        }
    }

    function openGmPerformanceCommentModal(resultId, taskId, title, comment) {
        const form = document.getElementById('gmCommentPerformanceForm');
        if (resultId > 0) {
            form.action = "{{ url('/weekly/results') }}/" + resultId + "/gm-comment";
        } else {
            form.action = "{{ url('/weekly/tasks') }}/" + taskId + "/gm-comment";
        }
        document.getElementById('gmCommentPerfTaskTitle').innerText = title;
        document.getElementById('gmCommentPerfInput').value = comment || '';
        const modal = new bootstrap.Modal(document.getElementById('gmCommentPerformanceModal'));
        modal.show();
    }
</script>
@endsection

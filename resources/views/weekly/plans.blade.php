@extends('layouts.app')

@section('title', 'Weekly Plans - ' . $department->department_name)

@section('content')
<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Weekly Task Planning</h4>
        <p class="text-muted small mb-0">{{ $department->department_name }} &bull; Week {{ $selectedWeek }} ({{ $selectedMonth }} {{ $selectedYear }})</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('weekly.voucher', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'week' => $selectedWeek, 'type' => 'plan']) }}" class="btn btn-outline-dark btn-sm" target="_blank" title="Open Official Plan Voucher in new tab">
            <i class="fas fa-file-invoice me-1"></i> View Plan Voucher
        </a>
        <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#weeklyPlanDelayReasonModal" title="Submit delay reason to GM if late or requesting extension">
            <i class="fas fa-clock me-1"></i> Delay Reason
        </button>
        <button type="button" class="btn btn-kaldis btn-sm" data-bs-toggle="modal" data-bs-target="#createTaskModal">
            <i class="fas fa-plus me-1"></i> Schedule Weekly Task
        </button>
        <button type="button" class="btn {{ ($weeklyPlan && $weeklyPlan->status === 'SUBMITTED') ? 'btn-outline-success' : 'btn-success' }} btn-sm" data-bs-toggle="modal" data-bs-target="#submitWeeklyPlanModal" {{ $tasks->isEmpty() ? 'disabled' : '' }}>
            <i class="fas {{ ($weeklyPlan && $weeklyPlan->status === 'SUBMITTED') ? 'fa-redo' : 'fa-paper-plane' }} me-1"></i> {{ ($weeklyPlan && $weeklyPlan->status === 'SUBMITTED') ? 'Re-Submit to GM' : 'Submit Plan to GM' }}
        </button>
        <a href="{{ route('weekly.performance', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'week' => $selectedWeek]) }}" class="btn btn-outline-primary btn-sm" title="Go to Performance & Submit Weekly Cycle to GM">
            Weekly Performance &rarr;
        </a>
    </div>
</div>

@if($weeklyPlan && ($weeklyPlan->isDelayApproved() || $weeklyPlan->submission_status === 'DELAY_APPROVED'))
    <div class="alert alert-info border-0 shadow-sm d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="fas fa-shield-alt text-primary fs-5"></i>
                <strong class="text-dark">Weekly Task Schedule Delay Approved by GM</strong>
                {!! format_submission_status_badge('DELAY_APPROVED', 'APPROVED', $weeklyPlan->delay_reason) !!}
            </div>
            <div class="small text-muted">
                General Management has approved the delay for scheduling Week {{ $selectedWeek }} tasks. In accordance with executive rules, this schedule is excused and <strong>NOT</strong> recorded as Not Submitted.
            </div>
            @if(!empty($weeklyPlan->delay_reason))
                <div class="small text-dark mt-1"><strong>Justification:</strong> {{ $weeklyPlan->delay_reason }}</div>
            @endif
            @if(!empty($weeklyPlan->delay_gm_notes))
                <div class="small text-primary mt-0.5"><strong>GM Remarks:</strong> {{ $weeklyPlan->delay_gm_notes }}</div>
            @endif
        </div>
        <div class="small text-muted text-nowrap">
            Approved {{ $weeklyPlan->delay_approved_at ? format_ethiopian_datetime($weeklyPlan->delay_approved_at) : '' }}
        </div>
    </div>
@elseif($weeklyPlan && $weeklyPlan->isDelayPending())
    <div class="alert alert-warning border-0 shadow-sm d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="fas fa-hourglass-half text-warning fs-5"></i>
                <strong class="text-dark">Delay Request Submitted (Pending GM Review)</strong>
                {!! format_submission_status_badge($weeklyPlan->submission_status, 'PENDING', $weeklyPlan->delay_reason) !!}
            </div>
            <div class="small text-muted">
                Your delay justification for Week {{ $selectedWeek }} tasks has been forwarded to General Management.
            </div>
            <div class="small text-dark mt-1"><strong>Justification:</strong> {{ $weeklyPlan->delay_reason }}</div>
        </div>
        <div class="small text-muted text-nowrap">
            Pending GM Review
        </div>
    </div>
@elseif($weeklyPlan && $weeklyPlan->status === 'SUBMITTED')
    <div class="alert alert-success border-0 shadow-sm d-flex justify-content-between align-items-center mb-3">
        <div>
            <i class="fas fa-clipboard-check me-2 text-success"></i>
            <strong>Weekly Tasks Scheduled &amp; Submitted:</strong> Planned tasks for Week {{ $selectedWeek }} ({{ $selectedMonth }} {{ $selectedYear }}) have been officially submitted to General Management.
            <span class="badge bg-success ms-2">SUBMITTED</span>
        </div>
        <div class="small text-muted">
            Submitted on {{ $weeklyPlan->updated_at ? format_ethiopian_datetime($weeklyPlan->updated_at) : '' }}
        </div>
    </div>
@elseif($tasks->isNotEmpty())
    <div class="alert alert-warning border-0 shadow-sm d-flex justify-content-between align-items-center mb-3">
        <div>
            <i class="fas fa-info-circle me-2 text-warning"></i>
            <strong>Planning in Progress ({{ $tasks->count() }} Tasks):</strong> You have scheduled tasks for Week {{ $selectedWeek }}. When ready, click <strong>Submit Plan to GM</strong> to notify General Management.
            <span class="badge bg-secondary ms-2">DRAFT</span>
        </div>
        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#submitWeeklyPlanModal">
            <i class="fas fa-paper-plane me-1"></i> Submit Plan to GM Now
        </button>
    </div>
@endif

@if(isset($submission) && $submission && in_array($submission->status, ['ON_TIME', 'LATE', 'SUBMITTED']))
    <div class="alert alert-success border-0 shadow-sm d-flex justify-content-between align-items-center mb-3">
        <div>
            <i class="fas fa-check-circle me-2 text-success"></i>
            <strong>Report Submitted:</strong> Week {{ $selectedWeek }} performance report has been officially submitted.
            <span class="badge {{ $submission->status === 'ON_TIME' ? 'bg-success' : 'bg-warning text-dark' }} ms-2">
                {{ $submission->status }}
            </span>
        </div>
        <div class="small text-muted">
            Submitted on {{ $submission->submitted_at ? format_ethiopian_datetime($submission->submitted_at) : '' }}
        </div>
    </div>
@endif

<!-- Filters Bar -->
<div class="filter-bar">
    <form method="GET" action="{{ route('weekly.plans') }}" class="row g-2 align-items-center">
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

        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Planning Month</label>
            <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach(config('kaldis.ethiopian_months') as $m)
                    <option value="{{ $m }}" {{ $selectedMonth === $m ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Planning Year</label>
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach(config('kaldis.planning_years') as $y)
                    <option value="{{ $y }}" {{ $selectedYear === $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Planning Week</label>
            <select name="week" class="form-select form-select-sm" onchange="this.form.submit()">
                @for($w = 1; $w <= 5; $w++)
                    <option value="{{ $w }}" {{ $selectedWeek == $w ? 'selected' : '' }}>Week {{ $w }}</option>
                @endfor
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Search Tasks</label>
            <input type="text" id="weeklyTaskSearchInput" class="form-control form-control-sm" placeholder="Filter tasks live...">
        </div>
    </form>
</div>

<!-- Tasks Table -->
<div class="card mb-4">
    <div class="card-header">
        <span><i class="fas fa-tasks me-1 text-secondary"></i> Planned Tasks for Week {{ $selectedWeek }} ({{ $tasks->count() }})</span>
        <span class="badge bg-light text-secondary border">Week {{ $selectedWeek }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="weeklyTasksTable">
            <thead>
                <tr>
                    <th style="width: 120px;">Task Type</th>
                    <th>Task Title</th>
                    <th>Parent Monthly Plan</th>
                    <th>Definition of Done (DOD)</th>
                    <th style="width: 150px;" class="text-center">Approval Status</th>
                    <th style="width: 150px;" class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                    @php
                        $isNewTask = ($task->task_type === 'NEW_TASK' || $task->plan_type === 'NEW_TASK' || empty($task->monthly_plan_id));
                        $approval = strtoupper($task->approval_status ?? 'PENDING');
                    @endphp
                    <tr>
                        <td>
                            @if($isNewTask)
                                <span class="badge bg-secondary px-2 py-1">New Task</span>
                            @else
                                <span class="badge bg-primary px-2 py-1">Planned</span>
                            @endif
                        </td>
                        <td>
                            <strong class="text-dark">{{ $task->task_title }}</strong>
                        </td>
                        <td>
                            @if($task->monthlyPlan)
                                <span class="small text-secondary">{{ $task->monthlyPlan->title }}</span>
                                @if($task->monthlyPlan->annualGoal)
                                    <span class="badge bg-dark font-monospace ms-1 small">{{ $task->monthlyPlan->annualGoal->goal_code }}</span>
                                @endif
                            @else
                                <span class="text-muted small">&mdash;</span>
                            @endif
                        </td>
                        <td>
                            <span class="small text-secondary">{{ $task->expected_result ?: ($task->task_target ?: '—') }}</span>
                        </td>
                        <td class="text-center">
                            @if($approval === 'APPROVED')
                                <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                    <i class="fas fa-check-circle me-1"></i> Approved
                                </span>
                            @elseif($approval === 'DECLINED')
                                <div>
                                    <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1 mb-1">
                                        <i class="fas fa-times-circle me-1"></i> Declined
                                    </span>
                                    @if(!empty($task->decline_reason))
                                        <div class="small text-danger fw-semibold" style="font-size: 0.72rem;" title="{{ $task->decline_reason }}">
                                            <strong>Why:</strong> {{ \Illuminate\Support\Str::limit($task->decline_reason, 40) }}
                                        </div>
                                    @endif
                                </div>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                    <i class="fas fa-clock me-1"></i> Pending GM
                                </span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex align-items-center justify-content-end gap-1">
                                @if(auth()->user()->isGM() || auth()->user()->isSuperAdmin())
                                    @if($approval !== 'APPROVED')
                                        <form method="POST" action="{{ route('weekly.tasks.approval', $task->id) }}" class="d-inline" onsubmit="return confirm('Approve this weekly task?');">
                                            @csrf
                                            <input type="hidden" name="action" value="APPROVE">
                                            <button type="submit" class="btn btn-xs btn-success" title="Approve Task">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if($approval !== 'DECLINED')
                                        <button type="button" class="btn btn-xs btn-outline-danger" title="Decline Task" onclick="openDeclineWeeklyModal({{ $task->id }}, '{{ addslashes($task->task_title) }}')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif
                                @endif

                                <form method="POST" action="{{ route('weekly.tasks.destroy', $task->id) }}" class="d-inline" onsubmit="return confirm('Delete this weekly task?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-secondary" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state py-5">
                            <i class="fas fa-calendar-plus d-block opacity-25"></i>
                            <h6>No tasks planned yet for Week {{ $selectedWeek }}</h6>
                            <p class="small text-muted mb-3">Add weekly tasks to plan deliverables for this week.</p>
                            <button type="button" class="btn btn-sm btn-kaldis" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                                <i class="fas fa-plus me-1"></i> Schedule Task Now
                            </button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Create Weekly Task -->
<div class="modal fade" id="createTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('weekly.tasks.store') }}">
                @csrf
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                <input type="hidden" name="month" value="{{ $selectedMonth }}">
                <input type="hidden" name="week_number" value="{{ $selectedWeek }}">

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark fs-6">
                        <i class="fas fa-plus-circle me-2 text-warning"></i> Schedule Weekly Task (Week {{ $selectedWeek }})
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Step 0: Department Selector for GM / Super Admin -->
                    @if(auth()->user()->isGM() || auth()->user()->isSuperAdmin())
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Assign To Department <span class="text-danger">*</span></label>
                            <select name="department_id" class="form-select" data-no-search="true">
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

                    <!-- Step 1: Task Type Selection -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Task Type <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="form-check p-2.5 border rounded-3 bg-light">
                                    <input class="form-check-input" type="radio" name="task_type" id="taskTypePlanned" value="PLANNED" checked onchange="toggleWeeklyTaskType()">
                                    <label class="form-check-label fw-semibold text-primary" for="taskTypePlanned">
                                        <i class="fas fa-calendar-check me-1"></i> Planned
                                    </label>
                                    <div class="small text-muted" style="font-size: 0.75rem;">Already planned in the monthly plan</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check p-2.5 border rounded-3 bg-light">
                                    <input class="form-check-input" type="radio" name="task_type" id="taskTypeNew" value="NEW_TASK" onchange="toggleWeeklyTaskType()">
                                    <label class="form-check-label fw-semibold text-secondary" for="taskTypeNew">
                                        <i class="fas fa-plus-circle me-1"></i> New Task
                                    </label>
                                    <div class="small text-muted" style="font-size: 0.75rem;">Unplanned in month, but planned for this week</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Parent Monthly Plan (Shown if Planned) -->
                    <div id="parentMonthlyPlanSection" class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">
                            Select Monthly Plan <span class="text-danger">*</span>
                        </label>
                        @if($monthlyPlans->isEmpty())
                            <div class="alert alert-warning py-2 px-3 small border-0 rounded-3">
                                <i class="fas fa-exclamation-triangle me-1"></i> No monthly plans found for {{ $selectedMonth }} {{ $selectedYear }}. You can create a <strong>New Task</strong>, or add a Monthly Plan first.
                            </div>
                        @else
                            <select name="monthly_plan_id" id="taskMonthlyPlanSelect" class="form-select" data-no-search="true" onchange="handleMonthlyPlanSelect(this)">
                                <option value="">-- Choose Parent Monthly Plan --</option>
                                @foreach($monthlyPlans as $mp)
                                    <option value="{{ $mp->id }}" data-title="{{ e($mp->title) }}" data-dod="{{ e($mp->definition_of_done) }}" data-target="{{ e($mp->monthly_target) }}">
                                        [{{ $mp->plan_type }}] {{ $mp->title }} (Target: {{ $mp->monthly_target }})
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <!-- Step 3: Task Information -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Task Title <span class="text-danger">*</span></label>
                        <input type="text" name="task_title" id="weeklyTaskTitleInput" class="form-control" placeholder="e.g. Complete core module deployment" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Definition of Done (DOD) <span class="text-danger">*</span></label>
                        <textarea name="definition_of_done" id="taskDodInput" class="form-control" rows="3" placeholder="Measurable deliverable and completion criteria..." required></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-kaldis">Save Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Decline Weekly Task -->
<div class="modal fade" id="declineWeeklyTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form id="declineWeeklyTaskForm" method="POST" action="">
                @csrf
                <input type="hidden" name="action" value="DECLINE">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fs-6 fw-bold">
                        <i class="fas fa-times-circle me-2"></i> Decline Weekly Task
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-2">You are declining the following weekly task:</p>
                    <div class="p-2.5 bg-light rounded border mb-3">
                        <strong class="text-dark" id="declineTaskTitle">—</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">
                            Reason for Decline <span class="text-danger">*</span>
                        </label>
                        <textarea name="decline_reason" class="form-control" rows="3" placeholder="State why this task is declined and what adjustment the department should make..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger fw-bold">
                        <i class="fas fa-times me-1"></i> Confirm Decline
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Submit Weekly Plan to GM -->
<div class="modal fade" id="submitWeeklyPlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('weekly.plans.submit') }}">
                @csrf
                <input type="hidden" name="department_id" value="{{ $department->id }}">
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                <input type="hidden" name="month" value="{{ $selectedMonth }}">
                <input type="hidden" name="week_number" value="{{ $selectedWeek }}">

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark fs-6">
                        <i class="fas fa-paper-plane text-success me-2"></i> Submit Weekly Plan to GM — Week {{ $selectedWeek }} ({{ $selectedMonth }} {{ $selectedYear }})
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 py-2 px-3 small mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Submitting this plan will notify General Management and the Department Head with the complete <strong>Weekly Task Schedule</strong>.
                    </div>

                    <div class="bg-light p-3 rounded-3 mb-3 border small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Department:</span>
                            <span class="fw-bold text-dark">{{ $department->department_name }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Scheduled Tasks:</span>
                            <span class="fw-bold text-dark">{{ $tasks->count() }} Tasks</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Period:</span>
                            <span class="fw-semibold text-dark">Week {{ $selectedWeek }} &bull; {{ $selectedMonth }} {{ $selectedYear }}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">
                            <i class="fas fa-clock text-warning me-1"></i> Delay Reason / Justification (If submitting after Monday 12:30)
                        </label>
                        <textarea name="delay_reason" class="form-control" rows="2" placeholder="If submitting late, explain the operational justification...">{{ $weeklyPlan->delay_reason ?? '' }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Executive Notes / Focus Areas (Optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Add any specific context or remarks for the General Manager..."></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="fas fa-check-circle me-1"></i> Transmit Schedule to GM
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Submit Weekly Plan Delay Reason -->
<div class="modal fade" id="weeklyPlanDelayReasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('weekly.plans.delay_reason') }}">
                @csrf
                <input type="hidden" name="department_id" value="{{ $department->id }}">
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                <input type="hidden" name="month" value="{{ $selectedMonth }}">
                <input type="hidden" name="week_number" value="{{ $selectedWeek }}">

                <div class="modal-header bg-warning bg-opacity-10 border-bottom border-warning border-opacity-25">
                    <h5 class="modal-title fw-bold text-dark fs-6">
                        <i class="fas fa-clock text-warning me-2"></i> Submit Delay Reason to General Management
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 small mb-3 border-0">
                        <i class="fas fa-shield-alt text-primary me-1"></i>
                        <strong>Executive Governance Rule:</strong> If your department has a justifiable delay reason and the <strong>General Manager Approves</strong>, your weekly schedule will <strong>NOT</strong> be recorded as "Not Submitted" or penalized.
                    </div>

                    <div class="bg-light p-3 rounded-3 mb-3 border small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Department:</span>
                            <span class="fw-bold text-dark">{{ $department->department_name }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Target Period:</span>
                            <span class="fw-semibold text-dark">Week {{ $selectedWeek }} ({{ $selectedMonth }} {{ $selectedYear }})</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Current Delay Status:</span>
                            <span>{!! format_submission_status_badge($weeklyPlan->submission_status ?? null, $weeklyPlan->delay_status ?? null, $weeklyPlan->delay_reason ?? null) !!}</span>
                        </div>
                        @if($weeklyPlan && !empty($weeklyPlan->delay_gm_notes))
                            <div class="pt-2 border-top mt-2 text-primary">
                                <strong>Last GM Remarks:</strong> {{ $weeklyPlan->delay_gm_notes }}
                            </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">
                            Detailed Reason for Delay / Scheduling Justification <span class="text-danger">*</span>
                        </label>
                        <textarea name="delay_reason" class="form-control" rows="4" required placeholder="State why weekly tasks could not be scheduled on time, dependencies, and expected completion...">{{ $weeklyPlan->delay_reason ?? '' }}</textarea>
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
@endsection

@section('scripts')
<script>
    setupLiveSearch('weeklyTaskSearchInput', 'weeklyTasksTable');

    function toggleWeeklyTaskType() {
        const plannedRadio = document.getElementById('taskTypePlanned');
        const isPlanned = plannedRadio ? plannedRadio.checked : true;
        const section = document.getElementById('parentMonthlyPlanSection');
        const planSelect = document.getElementById('taskMonthlyPlanSelect');

        if (section) {
            section.style.display = isPlanned ? 'block' : 'none';
        }
        if (planSelect) {
            planSelect.required = isPlanned;
            if (!isPlanned) {
                planSelect.value = '';
            }
        }
    }

    function handleMonthlyPlanSelect(selectEl) {
        if (!selectEl || !selectEl.value) return;
        const opt = selectEl.options[selectEl.selectedIndex];
        const title = opt.getAttribute('data-title');
        const dod = opt.getAttribute('data-dod');

        const titleInput = document.getElementById('weeklyTaskTitleInput');
        const dodInput = document.getElementById('taskDodInput');

        if (titleInput && (!titleInput.value || titleInput.value.length < 5)) {
            titleInput.value = title;
        }
        if (dod && dodInput && (!dodInput.value || dodInput.value.length < 5)) {
            dodInput.value = dod;
        }
    }

    function openDeclineWeeklyModal(taskId, title) {
        const form = document.getElementById('declineWeeklyTaskForm');
        form.action = "{{ url('/weekly/tasks') }}/" + taskId + "/approval";
        document.getElementById('declineTaskTitle').textContent = title;
        const modal = new bootstrap.Modal(document.getElementById('declineWeeklyTaskModal'));
        modal.show();
    }

    document.addEventListener('DOMContentLoaded', function() {
        toggleWeeklyTaskType();
        const createModal = document.getElementById('createTaskModal');
        if (createModal) {
            createModal.addEventListener('shown.bs.modal', function () {
                toggleWeeklyTaskType();
            });
            createModal.addEventListener('hidden.bs.modal', function () {
                const form = createModal.querySelector('form');
                if (form) {
                    form.reset();
                    toggleWeeklyTaskType();
                }
            });
        }
    });
</script>
@endsection

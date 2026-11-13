@extends('layouts.app')

@section('title', 'Weekly Plans - ' . $department->department_name)

@section('content')
<!-- Header -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Weekly Task Planning</h4>
        <p class="text-muted small mb-0">{{ $department->department_name }} &bull; Week {{ $selectedWeek }} ({{ $selectedMonth }} {{ $selectedYear }})</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if(kaldis_setting('voucher_enabled', 'true') === 'true')
            <a href="{{ route('weekly.voucher', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'week' => $selectedWeek, 'type' => 'plan']) }}" class="btn btn-outline-dark btn-sm" target="_blank" title="Open Official Plan Voucher in new tab">
                <i class="fas fa-file-invoice me-1"></i> View Plan Voucher
            </a>
        @endif
        <button type="button" class="btn btn-kaldis btn-sm" data-bs-toggle="modal" data-bs-target="#createTaskModal">
            <i class="fas fa-plus-circle me-1"></i> Add Task
        </button>
        <button type="button" class="btn {{ ($weeklyPlan && $weeklyPlan->status === 'SUBMITTED') ? 'btn-outline-success' : 'btn-success' }} btn-sm" data-bs-toggle="modal" data-bs-target="#submitWeeklyPlanModal" {{ $tasks->isEmpty() ? 'disabled' : '' }}>
            <i class="fas {{ ($weeklyPlan && $weeklyPlan->status === 'SUBMITTED') ? 'fa-redo' : 'fa-paper-plane' }} me-1"></i> {{ ($weeklyPlan && $weeklyPlan->status === 'SUBMITTED') ? 'Re-Submit to GM' : 'Submit Plan to GM' }}
        </button>
        <a href="{{ route('weekly.performance', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'week' => $selectedWeek]) }}" class="btn btn-outline-primary btn-sm" title="Go to Performance & Submit Weekly Cycle to GM">
            <i class="fas fa-chart-line me-1"></i> Weekly Performance
        </a>
    </div>
</div>

@if($weeklyPlan && ($weeklyPlan->isDelayApproved() || $weeklyPlan->submission_status === 'DELAY_APPROVED'))
    <div class="alert alert-info border-0 shadow-sm d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="fas fa-shield-alt text-primary fs-5"></i>
                <strong class="text-dark">Delay Approved by GM</strong>
                {!! format_submission_status_badge('DELAY_APPROVED', 'APPROVED', $weeklyPlan->delay_reason) !!}
            </div>
            <div class="small text-muted">
                Delay reason approved. This weekly schedule is excused.
            </div>
            @if(!empty($weeklyPlan->delay_reason))
                <div class="small text-dark mt-1"><strong>Reason:</strong> {{ $weeklyPlan->delay_reason }}</div>
            @endif
            @if(!empty($weeklyPlan->delay_gm_notes))
                <div class="small text-primary mt-0.5"><strong>GM Notes:</strong> {{ $weeklyPlan->delay_gm_notes }}</div>
            @endif
        </div>
        <div class="small text-muted text-nowrap">
            Approved: {{ $weeklyPlan->delay_approved_at ? format_ethiopian_datetime($weeklyPlan->delay_approved_at) : '' }}
        </div>
    </div>
@elseif($weeklyPlan && $weeklyPlan->isDelayPending())
    <div class="alert alert-warning border-0 shadow-sm d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="fas fa-hourglass-half text-warning fs-5"></i>
                <strong class="text-dark">Delay Request Pending GM Review</strong>
                {!! format_submission_status_badge($weeklyPlan->submission_status, 'PENDING', $weeklyPlan->delay_reason) !!}
            </div>
            <div class="small text-muted">
                Your delay justification is waiting for GM review.
            </div>
            <div class="small text-dark mt-1"><strong>Reason:</strong> {{ $weeklyPlan->delay_reason }}</div>
        </div>
        <div class="small text-muted text-nowrap">
            Pending Review
        </div>
    </div>
@elseif($weeklyPlan && $weeklyPlan->status === 'SUBMITTED')
    <div class="alert alert-success border-0 shadow-sm d-flex justify-content-between align-items-center mb-3">
        <div>
            <i class="fas fa-check-circle me-2 text-success"></i>
            <strong>Weekly Tasks Submitted:</strong> Tasks for Week {{ $selectedWeek }} are submitted to GM.
            <span class="badge bg-success ms-2">SUBMITTED</span>
        </div>
        <div class="small text-muted">
            Submitted: {{ $weeklyPlan->updated_at ? format_ethiopian_datetime($weeklyPlan->updated_at) : '' }}
        </div>
    </div>
@elseif($tasks->isNotEmpty())
    @php
        $isLastWeekMandatory = kaldis_setting('last_week_performance_mandatory', 'true') === 'true';
        $isLastWeekMissing = !empty($lastWeekPerf['is_mandatory_missing']) && $isLastWeekMandatory;
    @endphp
    <div class="alert alert-warning border-0 shadow-sm d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <i class="fas {{ $isLastWeekMissing ? 'fa-lock text-danger' : 'fa-info-circle text-warning' }} me-2"></i>
            <strong>Draft Tasks Saved ({{ $tasks->count() }} Tasks):</strong>
            @if($isLastWeekMissing)
                <span class="text-danger fw-semibold">Week {{ $lastWeekPerf['prev_week'] }} Performance Report is mandatory before submitting to GM.</span>
            @else
                <span>Click <strong>Submit Plan to GM</strong> when ready.</span>
            @endif
            <span class="badge bg-secondary ms-2">DRAFT</span>
        </div>
        <div class="d-flex gap-2">
            @if($isLastWeekMissing)
                <a href="{{ route('weekly.performance', ['department_id' => $department->id, 'year' => $lastWeekPerf['prev_year'], 'month' => $lastWeekPerf['prev_month'], 'week' => $lastWeekPerf['prev_week']]) }}" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-chart-line me-1"></i> Go to Week {{ $lastWeekPerf['prev_week'] }} Performance
                </a>
            @endif
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#submitWeeklyPlanModal">
                Submit Plan to GM
            </button>
        </div>
    </div>
@endif

@if(isset($submission) && $submission && in_array($submission->status, ['ON_TIME', 'LATE', 'SUBMITTED']))
    <div class="alert alert-success border-0 shadow-sm d-flex justify-content-between align-items-center mb-3">
        <div>
            <i class="fas fa-check-circle me-2 text-success"></i>
            <strong>Report Submitted:</strong> Week {{ $selectedWeek }} performance report is submitted.
            <span class="badge {{ $submission->status === 'ON_TIME' ? 'bg-success' : 'bg-warning text-dark' }} ms-2">
                {{ $submission->status }}
            </span>
            @if(!empty($submission->approval_status))
                <span class="badge {{ $submission->approval_status === 'APPROVED' ? 'bg-success' : ($submission->approval_status === 'REVISION_REQUESTED' ? 'bg-danger' : 'bg-secondary') }} ms-1">
                    GM: {{ $submission->approval_status }}
                </span>
            @endif
        </div>
        <div class="small text-muted">
            Submitted: {{ $submission->submitted_at ? format_ethiopian_datetime($submission->submitted_at) : '' }}
        </div>
    </div>
    @if(!empty($submission->gm_feedback))
        <div class="card border-primary mb-4 shadow-sm">
            <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                <span class="fw-bold small"><i class="fas fa-comment-dots me-1"></i> GM Feedback</span>
                <span class="small opacity-75">{{ $submission->gm_feedback_at ? \Carbon\Carbon::parse($submission->gm_feedback_at)->format('M d, Y H:i') : '' }}</span>
            </div>
            <div class="card-body bg-light py-2 px-3 small">
                <p class="mb-0 text-dark">{{ $submission->gm_feedback }}</p>
            </div>
        </div>
    @endif
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
            <label class="form-label small text-muted mb-1">Week</label>
            <select name="week" class="form-select form-select-sm" onchange="this.form.submit()">
                @for($w = 1; $w <= 5; $w++)
                    <option value="{{ $w }}" {{ $selectedWeek === $w ? 'selected' : '' }}>Week {{ $w }}</option>
                @endfor
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Month</label>
            <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach(config('kaldis.ethiopian_months') as $m)
                    <option value="{{ $m }}" {{ $selectedMonth === $m ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Year</label>
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach(config('kaldis.planning_years') as $y)
                    <option value="{{ $y }}" {{ $selectedYear === $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Search Tasks</label>
            <input type="text" id="weeklyTaskSearchInput" class="form-control form-control-sm" placeholder="Filter tasks live...">
        </div>
    </form>
</div>

@php
    $isReviewer = auth()->user()->isGM() || auth()->user()->isSuperAdmin();
    $pendingTasksCount = $tasks->where('approval_status', '!=', 'APPROVED')->count();
@endphp

<!-- Tasks Table -->
<div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2">
            <span><i class="fas fa-tasks me-1 text-secondary"></i> Planned Tasks for Week {{ $selectedWeek }} ({{ $tasks->count() }})</span>
            @if($isReviewer && $pendingTasksCount > 0)
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle small">
                    {{ $pendingTasksCount }} Pending
                </span>
            @endif
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @if($isReviewer && $pendingTasksCount > 0)
                <!-- Bulk Approve All Pending Tasks Form -->
                <form method="POST" action="{{ route('weekly.tasks.bulk_approve') }}" class="d-inline" onsubmit="return confirm('Approve ALL {{ $pendingTasksCount }} pending weekly tasks for {{ $department->department_name }} (Week {{ $selectedWeek }})?');">
                    @csrf
                    <input type="hidden" name="department_id" value="{{ $department->id }}">
                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <input type="hidden" name="week_number" value="{{ $selectedWeek }}">
                    <button type="submit" class="btn btn-xs btn-success fw-semibold shadow-xs" title="Approve all pending tasks for Week {{ $selectedWeek }}">
                        <i class="fas fa-check-double me-1"></i> Approve All Pending ({{ $pendingTasksCount }})
                    </button>
                </form>
                <!-- Bulk Approve Selected Button -->
                <button type="button" id="btnApproveSelectedTasks" class="btn btn-xs btn-outline-success fw-semibold d-none" onclick="submitSelectedTasks()">
                    <i class="fas fa-check-circle me-1"></i> Approve Selected (<span id="selectedTasksCount">0</span>)
                </button>
            @endif
            <span class="badge bg-light text-secondary border">Week {{ $selectedWeek }}</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="weeklyTasksTable">
            <thead>
                <tr>
                    @if($isReviewer)
                        <th style="width: 36px;" class="text-center">
                            <input type="checkbox" id="selectAllTasks" class="form-check-input" title="Select all pending tasks">
                        </th>
                    @endif
                    <th style="width: 140px;">Type</th>
                    <th>Task Title</th>
                    <th>Parent Monthly Plan</th>
                    <th>Definition of Done (DOD)</th>
                    <th style="width: 140px;" class="text-center">Approval Status</th>
                    <th style="width: 110px;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                    <tr>
                        @if($isReviewer)
                            <td class="text-center">
                                @if($task->approval_status !== 'APPROVED')
                                    <input type="checkbox" class="form-check-input task-chk" value="{{ $task->id }}" title="Select task #{{ $task->id }}">
                                @endif
                            </td>
                        @endif
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <div>
                                    @if($task->plan_type === 'STRATEGY')
                                        <span class="badge-strategy">Strategy</span>
                                    @else
                                        <span class="badge-operational">Operational</span>
                                    @endif
                                </div>
                                <div>
                                    @if($task->task_type === 'PLANNED')
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.7rem;">Planned</span>
                                    @else
                                        <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 0.7rem;">New Task</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong class="text-dark">{{ $task->task_title }}</strong>
                            @if($task->gm_comment)
                                <div class="mt-1.5 p-2 rounded-2 border bg-warning-subtle text-dark" style="font-size: 0.78rem;">
                                    <div class="d-flex align-items-center gap-1.5 fw-semibold text-warning-emphasis mb-0.5">
                                        <i class="fas fa-comment-dots text-warning"></i>
                                        <span>GM Comment</span>
                                        @if($task->gm_comment_at)
                                            <span class="text-muted fw-normal" style="font-size: 0.7rem;">({{ $task->gm_comment_at->diffForHumans() }})</span>
                                        @endif
                                    </div>
                                    <div class="text-secondary" style="white-space: pre-wrap;">{{ $task->gm_comment }}</div>
                                </div>
                            @endif
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
                            <span class="small fw-semibold text-dark">{{ $task->expected_result ?: $task->task_target }}</span>
                        </td>
                        <td class="text-center">
                            @if($task->approval_status === 'APPROVED')
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="fas fa-check-circle me-1"></i>Approved
                                </span>
                            @elseif($task->approval_status === 'DECLINED')
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="{{ $task->decline_reason }}">
                                    <i class="fas fa-circle-xmark me-1"></i>Declined
                                </span>
                                @if($task->decline_reason)
                                    <div class="text-danger small mt-0.5" style="font-size: 0.72rem;">{{ $task->decline_reason }}</div>
                                @endif
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border">Pending</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex align-items-center gap-1">
                                @if(auth()->user()->isGM() || auth()->user()->isSuperAdmin())
                                    <button type="button" class="btn btn-xs btn-outline-warning py-0.5 px-1.5" 
                                            onclick="openGmCommentModal({{ $task->id }}, '{{ addslashes($task->task_title) }}', '{{ addslashes($task->gm_comment ?? '') }}')" 
                                            title="Add / Edit GM Comment">
                                        <i class="fas fa-comment-dots"></i>
                                    </button>
                                    @if($task->approval_status !== 'APPROVED')
                                        <form method="POST" action="{{ route('weekly.tasks.approve', $task->id) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-xs btn-outline-success py-0.5 px-1.5" title="Approve Task">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if($task->approval_status !== 'DECLINED')
                                        <button type="button" class="btn btn-xs btn-outline-danger py-0.5 px-1.5" onclick="openDeclineWeeklyTaskModal({{ $task->id }}, '{{ addslashes($task->task_title) }}')" title="Decline Task">
                                            <i class="fas fa-xmark"></i>
                                        </button>
                                    @endif
                                @endif
                                <form method="POST" action="{{ route('weekly.tasks.destroy', $task->id) }}" onsubmit="return confirm('Delete this weekly task?');" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-secondary py-0.5 px-1.5" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isReviewer ? 7 : 6 }}" class="empty-state py-5 text-center">
                            <i class="fas fa-calendar-plus d-block opacity-25 fs-2 mb-2"></i>
                            <h6 class="fw-semibold text-dark">No tasks planned yet for Week {{ $selectedWeek }}</h6>
                            <p class="small text-muted mb-3">Add weekly tasks to plan deliverables for this week.</p>
                            <button type="button" class="btn btn-sm btn-kaldis" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                                <i class="fas fa-plus-circle me-1"></i> Add Task
                            </button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($isReviewer)
    <!-- Hidden Form for Bulk Approving Selected Tasks -->
    <form id="bulkApproveTasksForm" method="POST" action="{{ route('weekly.tasks.bulk_approve') }}" class="d-none">
        @csrf
        <div id="bulkApproveTasksInputs"></div>
    </form>
@endif

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
                    <h5 class="modal-title fw-bold text-dark fs-6"><i class="fas fa-plus-circle me-2 text-warning"></i> Add Task (Week {{ $selectedWeek }})</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    @if(auth()->user()->canAccessAllDepartments() && isset($allDepartments) && $allDepartments->count() > 1)
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Target Department <span class="text-danger">*</span></label>
                            <select name="department_id" class="form-select" required>
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

                    <!-- Step 1: Task Type (Planned vs New Task) -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Task Type <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="form-check p-2.5 border rounded-3 bg-light">
                                    <input class="form-check-input" type="radio" name="task_type" id="taskTypePlanned" value="PLANNED" checked onchange="toggleWeeklyTaskType()">
                                    <label class="form-check-label fw-semibold text-primary" for="taskTypePlanned">
                                        <i class="fas fa-calendar-check me-1"></i> Planned
                                    </label>
                                    <div class="small text-muted" style="font-size: 0.75rem;">Already planned in the month</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check p-2.5 border rounded-3 bg-light">
                                    <input class="form-check-input" type="radio" name="task_type" id="taskTypeNew" value="NEW_TASK" onchange="toggleWeeklyTaskType()">
                                    <label class="form-check-label fw-semibold text-info" for="taskTypeNew">
                                        <i class="fas fa-plus-circle me-1"></i> New Task
                                    </label>
                                    <div class="small text-muted" style="font-size: 0.75rem;">Unplanned in month, planned in week</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Parent Monthly Plan -->
                    @php
                        $isStratMandatory = kaldis_setting('strategy_field_mandatory', 'true') === 'true';
                    @endphp
                    <div class="mb-3" id="taskMonthlyPlanWrapper">
                        <label class="form-label small fw-semibold text-secondary">
                            Parent Monthly Plan 
                            @if($isStratMandatory)
                                <span class="badge bg-danger-subtle text-danger ms-1">Mandatory for Planned</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary ms-1">Optional</span>
                            @endif
                        </label>
                        <select name="monthly_plan_id" id="taskMonthlyPlanSelect" class="form-select" onchange="handleMonthlyPlanSelect(this)">
                            <option value="">-- Choose Monthly Plan {{ $isStratMandatory ? '' : '(Optional)' }} --</option>
                            @foreach($monthlyPlans as $mp)
                                <option value="{{ $mp->id }}" data-type="{{ $mp->plan_type }}" data-dod="{{ e($mp->definition_of_done) }}" data-target="{{ e($mp->monthly_target) }}">
                                    [{{ $mp->plan_type }}] {{ $mp->title }} (Target: {{ $mp->monthly_target }})
                                </option>
                            @endforeach
                        </select>
                        <div class="small text-muted mt-1">Select the monthly plan this weekly task executes.</div>
                    
                        <input type="hidden" name="plan_type" id="taskPlanTypeSelect" value="OPERATIONAL">
                    </div>

                    <!-- Step 3: Task Title -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Task Title <span class="text-danger">*</span></label>
                        <input type="text" name="task_title" id="taskTitleInput" class="form-control" placeholder="e.g. Run security scan on head office routers" required>
                    </div>

                    <!-- Step 4: Definition of Done (DOD) -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Definition of Done (DOD) <span class="text-danger">*</span></label>
                        <textarea name="expected_result" id="taskTargetInput" class="form-control" rows="2" placeholder="Clear output or criteria for when this weekly task is DONE..." required></textarea>
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

<!-- Modal: Decline Weekly Task (GM Action) -->
<div class="modal fade" id="declineWeeklyTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="declineWeeklyTaskForm" action="">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark fs-6">Decline Weekly Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-secondary small mb-3">Please specify the reason for declining this weekly task:</p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Why are you declining this task? <span class="text-danger">*</span></label>
                        <textarea name="decline_reason" id="weeklyTaskDeclineReasonInput" class="form-control" rows="3" placeholder="Provide reason or required adjustments..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger fw-semibold">Decline Task</button>
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
                    @php
                        $isLastWeekMandatory = kaldis_setting('last_week_performance_mandatory', 'true') === 'true';
                        $isLastWeekMissing = !empty($lastWeekPerf['is_mandatory_missing']) && $isLastWeekMandatory;
                    @endphp

                    @if($isLastWeekMissing)
                        <div class="alert alert-danger border-0 shadow-sm py-2.5 px-3 small mb-3">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="fas fa-lock text-danger fs-5"></i>
                                <strong class="text-danger">Mandatory Requirement: Last Week's Performance Missing</strong>
                            </div>
                            <div class="text-dark">
                                Company governance requires <strong>Week {{ $lastWeekPerf['prev_week'] }}</strong> Performance Report to be evaluated and submitted to General Management before <strong>Week {{ $selectedWeek }}</strong> plan can be submitted.
                            </div>
                            <div class="mt-2">
                                <a href="{{ route('weekly.performance', ['department_id' => $department->id, 'year' => $lastWeekPerf['prev_year'], 'month' => $lastWeekPerf['prev_month'], 'week' => $lastWeekPerf['prev_week']]) }}" class="btn btn-xs btn-danger text-white fw-bold">
                                    <i class="fas fa-chart-line me-1"></i> Go to Week {{ $lastWeekPerf['prev_week'] }} Performance &rarr;
                                </a>
                            </div>
                        </div>
                    @endif

                    <div class="alert alert-info border-0 py-2 px-3 small mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Submitting this plan will notify General Management and the Department Head via Telegram with the complete <strong>Weekly Task Schedule</strong> and <strong>Last Week's Performance Report</strong>.
                    </div>

                    <!-- Dual Summary Cards -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 bg-light border h-100">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold small text-dark">
                                        <i class="fas fa-calendar-check text-primary me-1"></i> Week {{ $selectedWeek }} Tasks Scheduled
                                    </span>
                                    <span class="badge bg-primary">{{ $tasks->count() }} Tasks</span>
                                </div>
                                <div class="small text-muted mb-1">
                                    • Strategy Tasks: <strong class="text-dark">{{ $tasks->where('plan_type', 'STRATEGY')->count() }}</strong>
                                </div>
                                <div class="small text-muted mb-1">
                                    • Operational Tasks: <strong class="text-dark">{{ $tasks->where('plan_type', 'OPERATIONAL')->count() }}</strong>
                                </div>
                                @if(kaldis_setting('voucher_enabled', 'true') === 'true')
                                    <div class="small text-muted">
                                        • Official Voucher: <a href="{{ route('weekly.voucher', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'week' => $selectedWeek, 'type' => 'plan']) }}" target="_blank" class="fw-semibold text-primary"><i class="fas fa-file-invoice me-1"></i> Preview Plan Voucher</a>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 rounded-3 bg-light border h-100 {{ $isLastWeekMissing ? 'border-danger border-opacity-50' : '' }}">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold small text-dark">
                                        <i class="fas fa-chart-line {{ $isLastWeekMissing ? 'text-danger' : 'text-success' }} me-1"></i> Last Week Performance Report
                                    </span>
                                    <span class="badge {{ $isLastWeekMissing ? 'bg-danger text-white' : (!empty($lastWeekPerf['has_data']) ? 'bg-success' : 'bg-secondary') }}">
                                        Week {{ $lastWeekPerf['prev_week'] ?? max(1, $selectedWeek - 1) }}
                                    </span>
                                </div>
                                @if(!empty($lastWeekPerf['has_data']))
                                    <div class="small text-muted mb-1">
                                        • Completion Rate: <strong class="text-dark">{{ $lastWeekPerf['completion_rate'] }}%</strong>
                                    </div>
                                    <div class="small text-muted mb-1">
                                        • Binary Execution: <span class="text-success fw-bold">{{ $lastWeekPerf['done_tasks'] }} DONE</span> &bull; <span class="text-danger fw-bold">{{ $lastWeekPerf['not_done_tasks'] }} NOT DONE</span> ({{ $lastWeekPerf['total_tasks'] }} total)
                                    </div>
                                    <div class="small text-muted">
                                        • Status: 
                                        @if($isLastWeekMissing)
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold">PENDING / NOT SUBMITTED</span>
                                        @else
                                            <span class="badge bg-light text-dark border">{{ $lastWeekPerf['timing_status'] }}</span>
                                        @endif
                                    </div>
                                @else
                                    <div class="small text-muted py-2">
                                        @if($isLastWeekMissing)
                                            <span class="text-danger fw-semibold"><i class="fas fa-exclamation-triangle me-1"></i> No performance report recorded for Week {{ $lastWeekPerf['prev_week'] }}.</span>
                                        @else
                                            <i>Initial cycle: No prior weekly report on file.</i>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">
                            <i class="fas fa-clock text-warning me-1"></i> Delay Reason / Justification (If scheduling past deadline or requesting extension)
                        </label>
                        <textarea name="delay_reason" class="form-control" rows="2" placeholder="State operational reason for delay. If approved by GM, this schedule will NOT be marked as Not Submitted.">{{ $weeklyPlan->delay_reason ?? '' }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Department Head Remarks for GM (Optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Operational context, key focus areas, or remarks for General Management...">{{ $weeklyPlan->notes ?? '' }}</textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light d-flex justify-content-between">
                    <div>
                        @if($isLastWeekMissing)
                            <span class="text-danger small fw-semibold"><i class="fas fa-lock me-1"></i> Week {{ $lastWeekPerf['prev_week'] }} performance required first.</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-success" {{ ($tasks->isEmpty() || $isLastWeekMissing) ? 'disabled' : '' }}>
                            <i class="fas fa-paper-plane me-1"></i> Submit Weekly Plan to GM
                        </button>
                    </div>
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
                        <i class="fas fa-clock text-warning me-2"></i> Submit Weekly Plan Delay Reason to GM
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 small mb-3 border-0">
                        <i class="fas fa-shield-alt text-primary me-1"></i>
                        <strong>Executive Governance Rule:</strong> If you have an operational delay in scheduling Week {{ $selectedWeek }} tasks and the <strong>General Manager Approves</strong>, this plan will <strong>NOT</strong> be recorded as "Not Submitted" or "Missing".
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

<!-- Modal: GM Weekly Task Comment (Inline Feedback) -->
<div class="modal fade" id="gmCommentWeeklyTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="gmCommentWeeklyTaskForm" action="">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark fs-6">
                        <i class="fas fa-comment-dots text-warning me-2"></i> GM Feedback &amp; Comment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-secondary small mb-2">Weekly Task: <strong id="gmCommentTaskTitle" class="text-dark"></strong></p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">General Manager Comment / Instructions</label>
                        <textarea name="gm_comment" id="gmCommentTaskInput" class="form-control" rows="4" placeholder="Enter constructive feedback, adjustments, or guidance for this task..."></textarea>
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
    setupLiveSearch('weeklyTaskSearchInput', 'weeklyTasksTable');

    function toggleWeeklyTaskType() {
        const plannedRadio = document.getElementById('taskTypePlanned');
        const isPlanned = plannedRadio ? plannedRadio.checked : true;
        const monthlyWrapper = document.getElementById('taskMonthlyPlanWrapper');
        const planSelect = document.getElementById('taskMonthlyPlanSelect');
        const typeSelect = document.getElementById('taskPlanTypeSelect');
        const hasPlans = planSelect && planSelect.options.length > 1;
        const isStratMandatory = {{ $isStratMandatory ? 'true' : 'false' }};

        if (monthlyWrapper) {
            monthlyWrapper.style.display = isPlanned ? '' : 'none';
        }
        if (planSelect) {
            planSelect.required = isPlanned && isStratMandatory && hasPlans;
            if (!isPlanned) {
                planSelect.value = '';
                if (typeSelect) typeSelect.value = 'OPERATIONAL';
            }
        }
    }

    function openDeclineWeeklyTaskModal(taskId, title) {
        const form = document.getElementById('declineWeeklyTaskForm');
        form.action = "{{ url('/weekly/tasks') }}/" + taskId + "/decline";
        document.getElementById('weeklyTaskDeclineReasonInput').value = '';
        const modal = new bootstrap.Modal(document.getElementById('declineWeeklyTaskModal'));
        modal.show();
    }

    function openGmCommentModal(taskId, title, comment) {
        const form = document.getElementById('gmCommentWeeklyTaskForm');
        form.action = "{{ url('/weekly/tasks') }}/" + taskId + "/gm-comment";
        document.getElementById('gmCommentTaskTitle').innerText = title;
        document.getElementById('gmCommentTaskInput').value = comment || '';
        const modal = new bootstrap.Modal(document.getElementById('gmCommentWeeklyTaskModal'));
        modal.show();
    }

    function handleMonthlyPlanSelect(selectEl) {
        if (!selectEl) return;
        const selectedOpt = selectEl.options[selectEl.selectedIndex];
        const planType = selectedOpt ? selectedOpt.getAttribute('data-type') : null;
        const dod = selectedOpt ? selectedOpt.getAttribute('data-dod') : null;
        const typeSelect = document.getElementById('taskPlanTypeSelect');
        const targetInput = document.getElementById('taskTargetInput');
        if (typeSelect && planType) {
            typeSelect.value = planType;
        }
        if (targetInput && dod && (!targetInput.value || targetInput.value.length < 5)) {
            targetInput.value = dod;
        }
    }

    function initTaskBulkSelection() {
        const selectAll = document.getElementById('selectAllTasks');
        const checkboxes = document.querySelectorAll('.task-chk');
        const btnSelected = document.getElementById('btnApproveSelectedTasks');
        const countSpan = document.getElementById('selectedTasksCount');

        if (!checkboxes.length) return;

        function updateState() {
            const checked = Array.from(checkboxes).filter(c => c.checked);
            if (countSpan) countSpan.textContent = checked.length;
            if (btnSelected) {
                if (checked.length > 0) {
                    btnSelected.classList.remove('d-none');
                } else {
                    btnSelected.classList.add('d-none');
                }
            }
            if (selectAll) {
                selectAll.checked = checked.length > 0 && checked.length === checkboxes.length;
                selectAll.indeterminate = checked.length > 0 && checked.length < checkboxes.length;
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checkboxes.forEach(c => c.checked = selectAll.checked);
                updateState();
            });
        }

        checkboxes.forEach(c => c.addEventListener('change', updateState));
    }

    function submitSelectedTasks() {
        const checked = Array.from(document.querySelectorAll('.task-chk:checked'));
        if (!checked.length) {
            alert('Please select at least one pending weekly task to approve.');
            return;
        }
        if (!confirm('Approve ' + checked.length + ' selected weekly task(s)?')) return;

        const form = document.getElementById('bulkApproveTasksForm');
        const container = document.getElementById('bulkApproveTasksInputs');
        if (!form || !container) return;

        container.innerHTML = '';
        checked.forEach(c => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'task_ids[]';
            input.value = c.value;
            container.appendChild(input);
        });
        form.submit();
    }

    document.addEventListener('DOMContentLoaded', function() {
        toggleWeeklyTaskType();
        initTaskBulkSelection();
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

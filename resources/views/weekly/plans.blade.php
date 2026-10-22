@extends('layouts.app')

@section('title', 'Weekly Plans - ' . $department->department_name)

@section('content')
<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Weekly Task Planning</h4>
        <p class="text-muted small mb-0">{{ $department->department_name }} &bull; Week {{ $selectedWeek }} ({{ $selectedMonth }} {{ $selectedYear }})</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('weekly.voucher', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'week' => $selectedWeek, 'type' => 'plan']) }}" class="btn btn-outline-dark btn-sm" target="_blank" title="Open Official Plan Voucher in new tab">
            <i class="fas fa-file-invoice me-1"></i> View Plan Voucher
        </a>
        <button type="button" class="btn btn-kaldis btn-sm" data-bs-toggle="modal" data-bs-target="#createTaskModal">
            <i class="fas fa-plus-circle me-1"></i> Schedule Weekly Task
        </button>
        <button type="button" class="btn {{ ($weeklyPlan && $weeklyPlan->status === 'SUBMITTED') ? 'btn-outline-success' : 'btn-success' }} btn-sm" data-bs-toggle="modal" data-bs-target="#submitWeeklyPlanModal" {{ $tasks->isEmpty() ? 'disabled' : '' }}>
            <i class="fas {{ ($weeklyPlan && $weeklyPlan->status === 'SUBMITTED') ? 'fa-redo' : 'fa-paper-plane' }} me-1"></i> {{ ($weeklyPlan && $weeklyPlan->status === 'SUBMITTED') ? 'Re-Submit to GM' : 'Submit Plan to GM' }}
        </button>
        <a href="{{ route('weekly.performance', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'week' => $selectedWeek]) }}" class="btn btn-outline-primary btn-sm" title="Go to Performance & Submit Weekly Cycle to GM">
            <i class="fas fa-chart-line me-1"></i> Weekly Performance
        </a>
    </div>
</div>

@if($weeklyPlan && $weeklyPlan->status === 'SUBMITTED')
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
            <strong>Planning in Progress ({{ $tasks->count() }} Tasks):</strong> You have scheduled tasks for Week {{ $selectedWeek }}. When ready, click <strong>Submit Plan to GM</strong> to notify General Management together with Last Week's Performance Report.
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
            @if(!empty($submission->approval_status))
                <span class="badge {{ $submission->approval_status === 'APPROVED' ? 'bg-success' : ($submission->approval_status === 'REVISION_REQUESTED' ? 'bg-danger' : 'bg-secondary') }} ms-1">
                    GM: {{ $submission->approval_status }}
                </span>
            @endif
        </div>
        <div class="small text-muted">
            Submitted on {{ $submission->submitted_at ? format_ethiopian_datetime($submission->submitted_at) : '' }}
        </div>
    </div>
    @if(!empty($submission->gm_feedback))
        <div class="card border-primary mb-4 shadow-sm">
            <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                <span class="fw-bold small"><i class="fas fa-comment-dots me-1"></i> Executive Review Comments from General Manager</span>
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
                    <th style="width: 110px;">Type</th>
                    <th>Task Title & Breakdown</th>
                    <th>Parent Monthly Plan</th>
                    <th>Task Target</th>
                    <th style="width: 90px;">Priority</th>
                    <th>Assigned To</th>
                    <th style="width: 60px;" class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                    <tr>
                        <td>
                            @if($task->plan_type === 'STRATEGY')
                                <span class="badge-strategy">Strategy</span>
                            @else
                                <span class="badge-operational">Operational</span>
                            @endif
                        </td>
                        <td>
                            <strong class="text-dark">{{ $task->task_title }}</strong>
                            @if($task->task_description)
                                <div class="small text-muted">{{ $task->task_description }}</div>
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
                        <td>
                            <span class="badge bg-light text-secondary border">{{ $task->priority }}</span>
                        </td>
                        <td>
                            <small class="text-muted">{{ $task->responsible_person ?: ($task->assigned_to ?: 'Unassigned') }}</small>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('weekly.tasks.destroy', $task->id) }}" onsubmit="return confirm('Delete this weekly task?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Delete">
                                    <i class="fas fa-trash-alt small"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state py-5">
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
                <input type="hidden" name="department_id" value="{{ $department->id }}">
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                <input type="hidden" name="month" value="{{ $selectedMonth }}">
                <input type="hidden" name="week_number" value="{{ $selectedWeek }}">

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark fs-6"><i class="fas fa-plus-circle me-2 text-warning"></i> Schedule Weekly Task (Week {{ $selectedWeek }})</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Step 1: Optional Parent Monthly Plan & Plan Type -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-secondary">
                                Parent Monthly Plan <span class="badge bg-light text-muted border ms-1">Optional</span>
                            </label>
                            <select name="monthly_plan_id" id="taskMonthlyPlanSelect" class="form-select" data-no-search="true" onchange="handleMonthlyPlanSelect(this)">
                                <option value="">-- Direct Weekly Task (No Monthly Plan Required) --</option>
                                @foreach($monthlyPlans as $mp)
                                    <option value="{{ $mp->id }}" data-type="{{ $mp->plan_type }}">
                                        [{{ $mp->plan_type }}] {{ $mp->title }} (Target: {{ $mp->monthly_target }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="small text-muted mt-1">Link to a monthly plan to inherit its strategic goal, or leave unselected for a standalone task.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Plan Type</label>
                            <select name="plan_type" id="taskPlanTypeSelect" class="form-select" data-no-search="true">
                                <option value="OPERATIONAL" selected>Operational</option>
                                <option value="STRATEGY">Strategy</option>
                            </select>
                            <div class="small text-muted mt-1">Classification for report breakdown.</div>
                        </div>
                    </div>

                    <!-- Step 2: Task Information -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Task Title <span class="text-danger">*</span></label>
                        <input type="text" name="task_title" class="form-control" placeholder="e.g. Run security scan on head office routers" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Weekly Target / Output <span class="text-danger">*</span></label>
                            <input type="text" name="task_target" class="form-control" placeholder="e.g. Scan 100% completed with report" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Priority <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select" data-no-search="true">
                                <option value="HIGH">HIGH</option>
                                <option value="MEDIUM" selected>MEDIUM</option>
                                <option value="LOW">LOW</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Assigned To</label>
                            <input type="text" name="assigned_to" class="form-control" placeholder="Staff Name">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Deadline Date</label>
                            <input type="date" name="deadline_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Task Description (Optional)</label>
                            <input type="text" name="task_description" class="form-control" placeholder="Additional details">
                        </div>
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
                                <div class="small text-muted">
                                    • Official Voucher: <a href="{{ route('weekly.voucher', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'week' => $selectedWeek, 'type' => 'plan']) }}" target="_blank" class="fw-semibold text-primary"><i class="fas fa-file-invoice me-1"></i> Preview Plan Voucher</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 rounded-3 bg-light border h-100">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold small text-dark">
                                        <i class="fas fa-chart-line text-success me-1"></i> Last Week Performance Report
                                    </span>
                                    <span class="badge {{ !empty($lastWeekPerf['has_data']) ? 'bg-success' : 'bg-secondary' }}">
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
                                        • Status: <span class="badge bg-light text-dark border">{{ $lastWeekPerf['timing_status'] }}</span>
                                    </div>
                                @else
                                    <div class="small text-muted py-2">
                                        <i>Initial cycle: No prior weekly report on file.</i>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Department Head Remarks for GM (Optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Operational context, key focus areas, or remarks for General Management...">{{ $weeklyPlan->notes ?? '' }}</textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="fas fa-paper-plane me-1"></i> Submit Weekly Plan to GM
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

    function handleMonthlyPlanSelect(selectEl) {
        if (!selectEl) return;
        const selectedOpt = selectEl.options[selectEl.selectedIndex];
        const planType = selectedOpt ? selectedOpt.getAttribute('data-type') : null;
        const typeSelect = document.getElementById('taskPlanTypeSelect');
        if (typeSelect && planType) {
            typeSelect.value = planType;
        }
    }
</script>
@endsection

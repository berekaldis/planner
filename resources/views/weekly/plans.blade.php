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
        <a href="{{ route('weekly.performance', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'week' => $selectedWeek]) }}" class="btn btn-outline-success btn-sm" title="Go to Performance & Submit Weekly Cycle to GM">
            <i class="fas fa-paper-plane me-1"></i> Performance &amp; Submit to GM
        </a>
    </div>
</div>

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
        <div class="d-flex align-items-center gap-2">
            <div class="small text-muted">
                Submitted on {{ $submission->submitted_at ? format_ethiopian_datetime($submission->submitted_at) : '' }}
            </div>
            <a href="{{ route('weekly.voucher', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'week' => $selectedWeek]) }}" class="btn btn-sm btn-dark" target="_blank" title="Open Official Voucher in new tab">
                <i class="fas fa-file-invoice me-1"></i> View Official Voucher
            </a>
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
                            <p class="small text-muted mb-3">Add weekly tasks broken down from your monthly objectives.</p>
                            @if($monthlyPlans->isEmpty())
                                <div class="alert alert-warning d-inline-block py-2 px-3 small border-0 mb-3 text-start">
                                    <i class="fas fa-exclamation-triangle me-1"></i> You must create monthly plans before scheduling weekly tasks.
                                </div>
                                <br>
                                <a href="{{ route('monthly.plans', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth]) }}" class="btn btn-sm btn-kaldis">
                                    <i class="fas fa-calendar-plus me-1"></i> Create Monthly Plan &rarr;
                                </a>
                            @else
                                <button type="button" class="btn btn-sm btn-kaldis" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                                    <i class="fas fa-plus me-1"></i> Schedule Task Now
                                </button>
                            @endif
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
                    @if($monthlyPlans->isEmpty())
                        <div class="alert alert-warning border-0 p-3 rounded-3 mb-3">
                            <h6 class="fw-bold mb-1"><i class="fas fa-exclamation-circle me-1"></i> No Monthly Plans Available</h6>
                            <p class="small mb-2">Weekly tasks must be linked to a parent monthly plan. Please create at least one Monthly Plan for <strong>{{ $department->department_name }}</strong> first.</p>
                            <a href="{{ route('monthly.plans', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth]) }}" class="btn btn-sm btn-warning fw-semibold">
                                Create Monthly Plan Now &rarr;
                            </a>
                        </div>
                    @else
                        <!-- Step 1: Select Monthly Plan -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Parent Monthly Plan <span class="text-danger">*</span></label>
                            <select name="monthly_plan_id" class="form-select" data-no-search="true" required>
                                <option value="">-- Choose Monthly Plan --</option>
                                @foreach($monthlyPlans as $mp)
                                    <option value="{{ $mp->id }}">
                                        [{{ $mp->plan_type }}] {{ $mp->title }} (Target: {{ $mp->monthly_target }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="small text-muted mt-1">The task will inherit the Plan Type (Strategy / Operational) and Annual Goal ID from the chosen monthly plan.</div>
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
                    @endif
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    @if(!$monthlyPlans->isEmpty())
                        <button type="submit" class="btn btn-sm btn-kaldis">Save Task</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    setupLiveSearch('weeklyTaskSearchInput', 'weeklyTasksTable');
</script>
@endsection

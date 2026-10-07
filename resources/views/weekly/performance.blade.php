@extends('layouts.app')

@section('title', 'Weekly Performance Sheet - ' . $department->department_name)

@section('content')
<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Weekly Performance Execution</h4>
        <p class="text-muted small mb-0">{{ $department->department_name }} &bull; Week {{ $selectedWeek }} ({{ $selectedMonth }} {{ $selectedYear }})</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('weekly.plans', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'week' => $selectedWeek]) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-tasks me-1"></i> Edit Planned Tasks
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar">
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

<!-- Submission Banner (if submitted) -->
@if($submission && in_array($submission->status, ['ON_TIME', 'LATE', 'SUBMITTED']))
    <div class="alert alert-success border-0 shadow-sm d-flex justify-content-between align-items-center mb-4">
        <div>
            <i class="fas fa-check-double me-2"></i>
            <strong>Report Submitted:</strong> Week {{ $selectedWeek }} performance report has been officially submitted.
            <span class="badge {{ $submission->status === 'ON_TIME' ? 'bg-success' : 'bg-warning text-dark' }} ms-2">
                {{ $submission->status }}
            </span>
        </div>
        <div class="small text-muted">
            Submitted by {{ $submission->submitter->full_name ?? 'Department Head' }} on {{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y H:i') : '' }}
        </div>
    </div>
@endif

<!-- Execution Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card p-3">
            <span class="text-muted small fw-semibold text-uppercase">Total Planned Tasks</span>
            <h3 class="fw-bold my-1 text-dark">{{ $totalTasks }}</h3>
            <div class="small text-muted">{{ $unratedTasks }} tasks pending evaluation</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <span class="text-muted small fw-semibold text-uppercase">Completed (DONE)</span>
            <h3 class="fw-bold my-1 text-success">{{ $doneTasks }}</h3>
            <div class="small text-muted">Achieved weekly targets</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <span class="text-muted small fw-semibold text-uppercase">Not Completed (NOT DONE)</span>
            <h3 class="fw-bold my-1 text-danger">{{ $notDoneTasks }}</h3>
            <div class="small text-muted">With mandatory root-cause logged</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <span class="text-muted small fw-semibold text-uppercase">Completion Rate</span>
            <h3 class="fw-bold my-1 text-primary">{{ $completionRate }}%</h3>
            <div class="small text-muted">(Done / Total Planned) &times; 100</div>
        </div>
    </div>
</div>

<!-- Section 1: Binary Task Evaluation -->
<div class="card mb-4">
    <div class="card-header">
        <span><i class="fas fa-check-circle me-1 text-secondary"></i> Binary Task Performance (DONE / NOT DONE)</span>
        <span class="badge bg-light text-secondary border">Section 1</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 100px;">Type</th>
                    <th>Task & Target</th>
                    <th style="width: 130px;" class="text-center">Status</th>
                    <th>Evaluation Details / Reason Category</th>
                    <th style="width: 160px;" class="text-end">Evaluate</th>
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
                            <div class="small text-muted"><strong>Target:</strong> {{ $task->expected_result }}</div>
                        </td>
                        <td class="text-center">
                            @if($task->result && $task->result->isDone())
                                <span class="badge-done"><i class="fas fa-check me-1"></i> DONE</span>
                            @elseif($task->result && $task->result->isNotDone())
                                <span class="badge-not-done"><i class="fas fa-times me-1"></i> NOT DONE</span>
                            @else
                                <span class="badge-pending"><i class="fas fa-clock me-1"></i> PENDING</span>
                            @endif
                        </td>
                        <td>
                            @if($task->result && $task->result->isDone())
                                <span class="small text-success"><i class="fas fa-check-circle me-1"></i> Output Achieved</span>
                            @elseif($task->result && $task->result->isNotDone())
                                <div class="small">
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle mb-1">
                                        {{ $task->result->reasonCategory->category_name ?? 'Reason Not Specified' }}
                                    </span>
                                    <div class="text-secondary"><strong>Why:</strong> {{ $task->result->not_done_explanation }}</div>
                                    <div class="text-primary mt-0.5"><strong>Next Action:</strong> {{ $task->result->next_action }}</div>
                                </div>
                            @else
                                <span class="text-muted small">Awaiting evaluation</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <form method="POST" action="{{ route('weekly.performance.evaluate') }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="weekly_task_id" value="{{ $task->id }}">
                                    <input type="hidden" name="status" value="DONE">
                                    <button type="submit" class="btn btn-outline-success btn-sm py-1 px-2" title="Mark Done">
                                        <i class="fas fa-check"></i> Done
                                    </button>
                                </form>
                                <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#notDoneModal"
                                        onclick="setupNotDoneModal({{ $task->id }}, '{{ addslashes($task->task_title) }}')">
                                    <i class="fas fa-times"></i> Not Done
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-state py-5">
                            <i class="fas fa-tasks d-block opacity-25"></i>
                            <h6>No Tasks Planned for Week {{ $selectedWeek }}</h6>
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

<!-- Section 2: Independent Achievements & Challenges Row -->
<div class="row g-3 mb-4">
    <!-- Achievements -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <span><i class="fas fa-trophy me-1 text-warning"></i> Independent Achievements</span>
                <button type="button" class="btn btn-xs btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addAchievementModal">
                    <i class="fas fa-plus me-1"></i> Add Achievement
                </button>
            </div>
            <div class="card-body p-0">
                @forelse($achievements as $ach)
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-dark fw-bold">{{ $ach->achievement_text }}</div>
                        </div>
                        <form method="POST" action="{{ route('weekly.achievements.destroy', $ach->id) }}" onsubmit="return confirm('Remove achievement?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm text-danger border-0 p-0"><i class="fas fa-times"></i></button>
                        </form>
                    </div>
                @empty
                    <div class="empty-state py-4">
                        <i class="fas fa-award d-block opacity-25"></i>
                        <p class="small text-muted mb-0">No independent achievements recorded for Week {{ $selectedWeek }}.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Challenges -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <span><i class="fas fa-exclamation-triangle me-1 text-danger"></i> Independent Challenges & Blockers</span>
                <button type="button" class="btn btn-xs btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addChallengeModal">
                    <i class="fas fa-plus me-1"></i> Log Challenge
                </button>
            </div>
            <div class="card-body p-0">
                @forelse($challenges as $ch)
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-dark fw-bold">{{ $ch->challenge_text }}</div>
                        </div>
                        <form method="POST" action="{{ route('weekly.challenges.destroy', $ch->id) }}" onsubmit="return confirm('Remove challenge?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm text-danger border-0 p-0"><i class="fas fa-times"></i></button>
                        </form>
                    </div>
                @empty
                    <div class="empty-state py-4">
                        <i class="fas fa-shield-alt d-block opacity-25"></i>
                        <p class="small text-muted mb-0">No operational challenges recorded for Week {{ $selectedWeek }}.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Section 3: Official Report Submission -->
<div class="card mb-4">
    <div class="card-header">
        <span><i class="fas fa-paper-plane me-1 text-secondary"></i> Submit Weekly Department Performance Report</span>
        <span class="badge bg-light text-secondary border">Monday 12:00 PM Deadline</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('weekly.report.submit') }}">
            @csrf
            <input type="hidden" name="department_id" value="{{ $department->id }}">
            <input type="hidden" name="year" value="{{ $selectedYear }}">
            <input type="hidden" name="month" value="{{ $selectedMonth }}">
            <input type="hidden" name="week_number" value="{{ $selectedWeek }}">

            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Executive Summary / General Remarks</label>
                <textarea name="report_summary" class="form-control" rows="2" placeholder="Overall summary of the week's department execution..."></textarea>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <div>
                    @if($totalTasks === 0)
                        <span class="text-danger small fw-semibold">* Please schedule weekly tasks before submitting a performance report.</span>
                    @elseif($unratedTasks > 0)
                        <span class="text-danger small fw-semibold">* Please evaluate all tasks ({{ $unratedTasks }} remaining) before submitting.</span>
                    @else
                        <span class="text-success small fw-semibold"><i class="fas fa-check-circle me-1"></i> All {{ $totalTasks }} tasks evaluated. Ready for official submission.</span>
                    @endif
                </div>
                <button type="submit" class="btn btn-kaldis" {{ ($totalTasks === 0 || $unratedTasks > 0) ? 'disabled' : '' }}>
                    <i class="fas fa-paper-plane me-1"></i> Submit Weekly Report
                </button>
            </div>
        </form>
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
                    <div class="mb-3">
                        <label class="form-label small text-muted">Task Title</label>
                        <div id="notDoneTaskTitle" class="fw-bold text-dark"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Mandatory Reason Category <span class="text-danger">*</span></label>
                        <select name="reason_category_id" class="form-select" required>
                            <option value="">-- Select Root Cause Category --</option>
                            @foreach($reasonCategories as $rc)
                                <option value="{{ $rc->id }}">{{ $rc->category_name }}</option>
                            @endforeach
                        </select>
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
@endsection

@section('scripts')
<script>
    function setupNotDoneModal(taskId, title) {
        document.getElementById('notDoneTaskId').value = taskId;
        document.getElementById('notDoneTaskTitle').textContent = title;
    }
</script>
@endsection

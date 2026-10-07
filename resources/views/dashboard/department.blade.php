@extends('layouts.app')

@section('title', $department->department_name . ' Workspace')

@section('content')
<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">{{ $department->department_name }}</h4>
        <p class="text-muted small mb-0">Department Planning & Performance Workspace</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('weekly.performance', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'week' => $selectedWeek]) }}" class="btn btn-kaldis btn-sm">
            <i class="fas fa-clipboard-check me-1"></i> Weekly Performance Sheet
        </a>
        <a href="{{ route('weekly.plans', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'week' => $selectedWeek]) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-plus me-1"></i> Add Weekly Tasks
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar">
    <form method="GET" action="{{ route('dashboard.department') }}" class="row g-2 align-items-center">
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
            <label class="form-label small text-muted mb-1">Planning Month</label>
            <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach(config('kaldis.ethiopian_months') as $m)
                    <option value="{{ $m }}" {{ $selectedMonth === $m ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Week Number</label>
            <select name="week" class="form-select form-select-sm" onchange="this.form.submit()">
                @for($w = 1; $w <= 5; $w++)
                    <option value="{{ $w }}" {{ $selectedWeek === $w ? 'selected' : '' }}>Week {{ $w }}</option>
                @endfor
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

<!-- Metrics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card p-3">
            <span class="text-muted small fw-semibold text-uppercase">Weekly Tasks</span>
            <h3 class="fw-bold my-1 text-dark">{{ $weekTotal }}</h3>
            <div class="small text-muted">Week {{ $selectedWeek }} ({{ $selectedMonth }})</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <span class="text-muted small fw-semibold text-uppercase">Completed Tasks</span>
            <h3 class="fw-bold my-1 text-success">{{ $weekDone }}</h3>
            <div class="small text-muted">{{ $weekTotal > 0 ? round(($weekDone / $weekTotal) * 100, 1) : 0 }}% Completion Rate</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <span class="text-muted small fw-semibold text-uppercase">Not Done Tasks</span>
            <h3 class="fw-bold my-1 text-danger">{{ $weekNotDone }}</h3>
            <div class="small text-muted">Requires next actions</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <span class="text-muted small fw-semibold text-uppercase">Monthly Portfolio</span>
            <h3 class="fw-bold my-1 text-primary">{{ $strategyCount + $operationalCount }}</h3>
            <div class="small text-muted">{{ $strategyCount }} Strategy &bull; {{ $operationalCount }} Operational</div>
        </div>
    </div>
</div>

<!-- Visual Doughnuts Row -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <span><i class="fas fa-chart-pie me-1 text-secondary"></i> Weekly Task Execution Status</span>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center" style="min-height: 220px;">
                <canvas id="deptTaskChart" style="max-height: 180px;"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <span><i class="fas fa-layer-group me-1 text-secondary"></i> Monthly Plan Distribution</span>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center" style="min-height: 220px;">
                <canvas id="deptPlanChart" style="max-height: 180px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Scheduled Tasks Table -->
<div class="card mb-4">
    <div class="card-header">
        <span><i class="fas fa-list-check me-1 text-secondary"></i> Tasks Scheduled for Week {{ $selectedWeek }}</span>
        <a href="{{ route('weekly.plans', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'week' => $selectedWeek]) }}" class="small text-decoration-none text-muted">Manage Tasks &rarr;</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Task Title</th>
                    <th>Target</th>
                    <th>Priority</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($weeklyTasks as $task)
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
                            @if($task->monthlyPlan)
                                <div class="small text-muted">Plan: {{ $task->monthlyPlan->title }}</div>
                            @endif
                        </td>
                        <td>{{ $task->task_target }}</td>
                        <td>
                            <span class="badge bg-light text-secondary border">{{ $task->priority }}</span>
                        </td>
                        <td>
                            @if($task->result && $task->result->status === 'DONE')
                                <span class="badge-done"><i class="fas fa-check me-1"></i> DONE</span>
                            @elseif($task->result && $task->result->status === 'NOT DONE')
                                <span class="badge-not-done"><i class="fas fa-times me-1"></i> NOT DONE</span>
                            @else
                                <span class="badge-pending"><i class="fas fa-clock me-1"></i> PENDING</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-state py-4">
                            <i class="fas fa-tasks d-block opacity-25"></i>
                            <h6>No tasks planned yet for Week {{ $selectedWeek }}</h6>
                            <p class="small text-muted mb-2">Schedule your department's weekly tasks from your monthly objectives.</p>
                            <a href="{{ route('weekly.plans', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'week' => $selectedWeek]) }}" class="btn btn-sm btn-kaldis">
                                <i class="fas fa-plus me-1"></i> Schedule Tasks Now
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Task Doughnut
    const taskCtx = document.getElementById('deptTaskChart').getContext('2d');
    const totalT = {{ $weekTotal }};
    if (totalT === 0) {
        new Chart(taskCtx, {
            type: 'doughnut',
            data: {
                labels: ['No Tasks Scheduled'],
                datasets: [{
                    data: [1],
                    backgroundColor: ['#E2E8F0'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    } else {
        new Chart(taskCtx, {
            type: 'doughnut',
            data: {
                labels: ['Done', 'Not Done', 'Pending'],
                datasets: [{
                    data: [{{ $weekDone }}, {{ $weekNotDone }}, {{ $weekPending }}],
                    backgroundColor: ['#10B981', '#EF4444', '#F59E0B'],
                    borderWidth: 2,
                    borderColor: '#FFFFFF'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // Plan Doughnut
    const planCtx = document.getElementById('deptPlanChart').getContext('2d');
    const totalP = {{ $strategyCount + $operationalCount }};
    if (totalP === 0) {
        new Chart(planCtx, {
            type: 'doughnut',
            data: {
                labels: ['No Plans Created'],
                datasets: [{
                    data: [1],
                    backgroundColor: ['#E2E8F0'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    } else {
        new Chart(planCtx, {
            type: 'doughnut',
            data: {
                labels: ['Strategy Plans', 'Operational Plans'],
                datasets: [{
                    data: [{{ $strategyCount }}, {{ $operationalCount }}],
                    backgroundColor: ['#4F46E5', '#0284C7'],
                    borderWidth: 2,
                    borderColor: '#FFFFFF'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
</script>
@endsection

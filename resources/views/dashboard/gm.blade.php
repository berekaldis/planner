@extends('layouts.app')

@section('title', 'General Manager Executive Dashboard')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Executive Dashboard</h4>
        <p class="text-muted small mb-0">Strategic Funnel, Monthly Activations & Execution Monitoring</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('strategy.activation', ['year' => $selectedYear, 'month' => $selectedMonth]) }}" class="btn btn-kaldis btn-sm">
            <i class="fas fa-toggle-on me-1"></i> Activate Monthly Strategies
        </a>
        <a href="{{ route('performance.weekly', ['year' => $selectedYear, 'month' => $selectedMonth]) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-table me-1"></i> Weekly Scorecard
        </a>
    </div>
</div>

<!-- Planning Lifecycle Stepper -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small fw-bold text-muted text-uppercase" style="letter-spacing: 0.05em;">Monthly Planning & Execution Lifecycle</span>
            <span class="badge bg-light text-secondary border small">Period: {{ $selectedMonth }} {{ $selectedYear }}</span>
        </div>
        <div class="row g-2 text-center small">
            <div class="col-md-4">
                <div class="p-2 rounded {{ $currentPhase === 1 ? 'bg-warning-subtle text-warning-emphasis fw-bold border border-warning' : 'bg-light text-muted' }}">
                    <i class="fas fa-check-circle me-1 {{ $currentPhase > 1 ? 'text-success' : '' }}"></i> Phase 1: Pending GM Activation
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-2 rounded {{ $currentPhase === 2 ? 'bg-primary-subtle text-primary-emphasis fw-bold border border-primary' : ($currentPhase > 2 ? 'bg-light text-muted' : 'bg-light text-muted opacity-50') }}">
                    <i class="fas fa-check-circle me-1 {{ $currentPhase > 2 ? 'text-success' : '' }}"></i> Phase 2: Monthly Plans Active
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-2 rounded {{ $currentPhase === 3 ? 'bg-success-subtle text-success-emphasis fw-bold border border-success' : 'bg-light text-muted opacity-50' }}">
                    <i class="fas fa-spinner fa-spin me-1 {{ $currentPhase === 3 ? 'text-success' : '' }}"></i> Phase 3: Execution Active
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Metric Overview Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="card h-100 p-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Active Strategies</span>
                    <h3 class="fw-bold my-1 text-primary">{{ $activeStrategiesCount }} <span class="text-muted fs-6 fw-normal">/ {{ $totalStrategies }}</span></h3>
                    <div class="small text-muted">Activated for {{ $selectedMonth }}</div>
                </div>
                <div class="p-2.5 bg-primary-subtle rounded-3 text-primary">
                    <i class="fas fa-chess-knight fs-5"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card h-100 p-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Monthly Plans</span>
                    <h3 class="fw-bold my-1 text-dark">{{ $totalMonthlyPlans }}</h3>
                    <div class="small text-muted">Across {{ $totalDepartments }} Departments</div>
                </div>
                <div class="p-2.5 bg-warning-subtle rounded-3 text-warning">
                    <i class="fas fa-calendar-alt fs-5"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card h-100 p-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Weekly Tasks</span>
                    <h3 class="fw-bold my-1 text-dark">{{ $totalTasks }}</h3>
                    <div class="small text-muted">
                        <span class="text-success fw-semibold">{{ $doneTasks }} Done</span> &bull; 
                        <span class="text-danger fw-semibold">{{ $notDoneTasks }} Not Done</span>
                    </div>
                </div>
                <div class="p-2.5 bg-success-subtle rounded-3 text-success">
                    <i class="fas fa-check-double fs-5"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card h-100 p-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Submissions</span>
                    <h3 class="fw-bold my-1 text-dark">{{ $onTimeSubmissions + $lateSubmissions }} <span class="text-muted fs-6 fw-normal">/ {{ $totalDepartments }}</span></h3>
                    <div class="small text-muted">
                        <span class="text-success fw-semibold">{{ $onTimeSubmissions }} On Time</span> &bull; 
                        <span class="text-warning fw-semibold">{{ $waitingSubmissions }} Pending</span>
                    </div>
                </div>
                <div class="p-2.5 bg-info-subtle rounded-3 text-info">
                    <i class="fas fa-file-invoice fs-5"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Visual Charts Row -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <span><i class="fas fa-chart-pie me-1 text-secondary"></i> Weekly Task Execution Status</span>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center" style="min-height: 250px;">
                <canvas id="taskStatusChart" style="max-height: 210px;"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <span><i class="fas fa-chart-pie me-1 text-secondary"></i> Report Submission Compliance</span>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center" style="min-height: 250px;">
                <canvas id="submissionChart" style="max-height: 210px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Department Execution Performance Comparison -->
<div class="card mb-4">
    <div class="card-header">
        <span><i class="fas fa-building me-1 text-secondary"></i> Department Execution Performance</span>
        <a href="{{ route('performance.monthly', ['year' => $selectedYear, 'month' => $selectedMonth]) }}" class="small text-decoration-none text-muted">View Full Rollup &rarr;</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Department</th>
                    <th class="text-center">Planned Tasks</th>
                    <th class="text-center">Done</th>
                    <th class="text-center">Not Done</th>
                    <th>Completion Rate</th>
                </tr>
            </thead>
            <tbody>
                @forelse($departmentPerformances as $dp)
                    <tr>
                        <td>
                            <strong class="text-dark">{{ $dp['department']->department_name }}</strong>
                            <span class="badge bg-light text-secondary ms-1">{{ $dp['department']->department_code }}</span>
                        </td>
                        <td class="text-center">{{ $dp['total'] }}</td>
                        <td class="text-center text-success fw-bold">{{ $dp['done'] }}</td>
                        <td class="text-center text-danger fw-bold">{{ $dp['not_done'] }}</td>
                        <td style="width: 250px;">
                            @if($dp['total'] > 0)
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar bg-success" style="width: {{ $dp['rate'] }}%;"></div>
                                    </div>
                                    <span class="small fw-semibold">{{ $dp['rate'] }}%</span>
                                </div>
                            @else
                                <span class="text-muted small">&mdash;</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No departments configured yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Task Donut Chart
    const taskCtx = document.getElementById('taskStatusChart').getContext('2d');
    const totalTasksVal = {{ $totalTasks }};
    
    if (totalTasksVal === 0) {
        new Chart(taskCtx, {
            type: 'doughnut',
            data: {
                labels: ['No Tasks Scheduled Yet'],
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
                    data: [{{ $doneTasks }}, {{ $notDoneTasks }}, {{ $pendingTasks }}],
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

    // Submission Donut Chart
    const subCtx = document.getElementById('submissionChart').getContext('2d');
    const totalSubsVal = {{ $onTimeSubmissions + $lateSubmissions + $missingSubmissions }};
    
    if (totalSubsVal === 0 && {{ $waitingSubmissions }} > 0) {
        new Chart(subCtx, {
            type: 'doughnut',
            data: {
                labels: ['Awaiting Submissions'],
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
        new Chart(subCtx, {
            type: 'doughnut',
            data: {
                labels: ['On Time', 'Late', 'Waiting', 'Missing'],
                datasets: [{
                    data: [{{ $onTimeSubmissions }}, {{ $lateSubmissions }}, {{ $waitingSubmissions }}, {{ $missingSubmissions }}],
                    backgroundColor: ['#10B981', '#F59E0B', '#94A3B8', '#EF4444'],
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

@extends('layouts.app')

@section('title', 'Reports & Tracking Hub - Kaldis PMS')

@section('content')
<!-- Header & Filter Bar -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                <i class="fas fa-chart-pie me-1"></i> Executive Governance
            </span>
            <span class="text-muted small">&bull;</span>
            <span class="text-muted small fw-semibold">Reports &amp; Organization Tracking</span>
        </div>
        <h4 class="fw-bold text-dark mb-0">Company Performance &amp; Compliance Hub</h4>
    </div>

    <!-- Filter Form & Actions -->
    <div class="d-flex flex-wrap align-items-center gap-2">
        <form method="GET" action="{{ route('admin.reports') }}" id="reportsFilterForm" class="d-flex gap-2">
            <select name="quarter" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach($quarters as $qCode => $qInfo)
                    <option value="{{ $qCode }}" {{ $selectedQuarter === $qCode ? 'selected' : '' }}>
                        {{ $qCode }} ({{ $qInfo['months'][0] }} - {{ $qInfo['months'][2] }})
                    </option>
                @endforeach
            </select>

            <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach(get_ethiopian_months() as $m)
                    <option value="{{ $m }}" {{ $selectedMonth === $m ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select>

            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach(get_planning_years() as $yr)
                    <option value="{{ $yr }}" {{ $selectedYear === $yr ? 'selected' : '' }}>{{ $yr }}</option>
                @endforeach
            </select>
        </form>

        <a href="{{ route('admin.reports.export', ['year' => $selectedYear, 'month' => $selectedMonth, 'quarter' => $selectedQuarter]) }}" 
           class="btn btn-sm btn-success fw-bold px-3 shadow-sm">
            <i class="fas fa-file-excel me-1"></i> Export to Excel
        </a>
    </div>
</div>

<!-- High-Level KPI Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold text-uppercase">Operating Departments</span>
                <span class="badge bg-secondary-subtle text-secondary"><i class="fas fa-building"></i> 13 Units</span>
            </div>
            <div class="fs-3 fw-bold text-dark">{{ $departments->count() }}</div>
            <div class="text-muted small">Active enterprise departments</div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-primary small fw-bold text-uppercase">Monthly Plan Compliance</span>
                <span class="badge bg-primary-subtle text-primary">{{ $selectedMonth }}</span>
            </div>
            <div class="fs-3 fw-bold text-primary">
                {{ $totalMonthlySubmitted }} / {{ $departments->count() }}
            </div>
            <div class="text-muted small">
                {{ $departments->count() > 0 ? round(($totalMonthlySubmitted / $departments->count()) * 100, 1) : 0 }}% departments submitted
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-success small fw-bold text-uppercase">Weekly Execution Rate</span>
                <span class="badge bg-success-subtle text-success"><i class="fas fa-chart-line"></i> Overall</span>
            </div>
            <div class="fs-3 fw-bold text-success">{{ $overallWeeklyRate }}%</div>
            <div class="text-muted small">{{ $totalWeeklyTasksAll }} total weekly tasks evaluated</div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-warning small fw-bold text-uppercase">Quarterly Packages</span>
                <span class="badge bg-warning-subtle text-warning">{{ $selectedQuarter }}</span>
            </div>
            <div class="fs-3 fw-bold text-dark">
                {{ $totalQuarterlySubmitted }} / {{ $departments->count() }}
            </div>
            <div class="text-muted small">
                {{ $departments->count() > 0 ? round(($totalQuarterlySubmitted / $departments->count()) * 100, 1) : 0 }}% quarterly completion
            </div>
        </div>
    </div>
</div>

<!-- Main Organization Compliance & Tracking Table -->
<div class="card border-0 shadow-sm mb-4 bg-white">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h6 class="fw-bold text-dark mb-0">
                <i class="fas fa-table-list text-primary me-2"></i> Department Performance &amp; Compliance Matrix
            </h6>
            <small class="text-muted">Comprehensive multi-tier tracking across Monthly, Weekly, and Quarterly deliverables</small>
        </div>
        <div>
            <a href="{{ route('admin.reports.export', ['year' => $selectedYear, 'month' => $selectedMonth, 'quarter' => $selectedQuarter]) }}" class="btn btn-outline-success btn-sm">
                <i class="fas fa-file-excel me-1"></i> Download Spreadsheet (.CSV)
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
            <thead class="table-light">
                <tr>
                    <th style="width: 180px;">Department</th>
                    <th style="width: 160px;">Monthly Plan ({{ $selectedMonth }})</th>
                    <th style="width: 220px;">Weekly Execution ({{ $selectedMonth }})</th>
                    <th style="width: 170px;">Quarterly Status ({{ $selectedQuarter }})</th>
                    <th style="width: 140px;" class="text-center">Quick Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($complianceRows as $row)
                    @php
                        $dept = $row['department'];
                        $mSub = $row['monthly_submission'];
                        $qSub = $row['quarterly_submission'];
                    @endphp
                    <tr>
                        <!-- Department Name & Code -->
                        <td>
                            <div class="fw-bold text-dark">{{ $dept->department_name }}</div>
                            <span class="badge bg-secondary-subtle text-secondary border" style="font-size: 0.72rem;">
                                {{ $dept->department_code }}
                            </span>
                        </td>

                        <!-- Monthly Plan Package -->
                        <td>
                            @if($mSub && $mSub->submitted_at)
                                <div class="d-flex align-items-center gap-1 mb-1">
                                    <span class="badge {{ $mSub->status === 'ON_TIME' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' }}">
                                        {{ $mSub->status === 'ON_TIME' ? '✓ On-Time' : '⚠ Late' }}
                                    </span>
                                    <span class="badge bg-light text-dark border">{{ $row['monthly_plans_total'] }} Plans</span>
                                </div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    {{ $row['monthly_strat_count'] }} Strat &bull; {{ $row['monthly_ops_count'] }} Ops
                                </div>
                            @elseif($row['monthly_plans_total'] > 0)
                                <span class="badge bg-info-subtle text-info border border-info-subtle">
                                    <i class="fas fa-file-pen me-1"></i> Draft ({{ $row['monthly_plans_total'] }} Plans)
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                    <i class="fas fa-circle-xmark me-1"></i> Not Created
                                </span>
                            @endif
                        </td>

                        <!-- Weekly Execution Rollup -->
                        <td>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-semibold text-dark">{{ $row['weekly_rate'] }}% Completed</span>
                                <span class="text-muted" style="font-size: 0.75rem;">{{ $row['weekly_done'] }}/{{ $row['weekly_tasks_total'] }} tasks</span>
                            </div>
                            <div class="progress mb-1" style="height: 5px;">
                                <div class="progress-bar {{ $row['weekly_rate'] >= 80 ? 'bg-success' : ($row['weekly_rate'] >= 50 ? 'bg-warning' : 'bg-danger') }}" 
                                     style="width: {{ $row['weekly_rate'] }}%;"></div>
                            </div>
                            <div class="text-muted" style="font-size: 0.72rem;">
                                Submissions: <strong>{{ $row['weeks_submitted'] }}</strong> Weeks reported
                            </div>
                        </td>

                        <!-- Quarterly Performance Rollup -->
                        <td>
                            @if($qSub && $qSub->status === 'SUBMITTED')
                                <div class="d-flex align-items-center gap-1 mb-1">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="fas fa-check-circle me-1"></i> {{ $qSub->completion_percentage }}%
                                    </span>
                                    @if($qSub->approval_status === 'APPROVED')
                                        <span class="badge bg-success text-white" style="font-size: 0.68rem;">Approved</span>
                                    @elseif($qSub->approval_status === 'REVISION_REQUESTED')
                                        <span class="badge bg-danger text-white" style="font-size: 0.68rem;">Revision</span>
                                    @else
                                        <span class="badge bg-warning text-dark" style="font-size: 0.68rem;">Review</span>
                                    @endif
                                </div>
                                <div class="text-muted" style="font-size: 0.72rem;">
                                    Submitted {{ $qSub->submitted_at ? $qSub->submitted_at->format('M d') : '' }}
                                </div>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border">
                                    <i class="fas fa-clock me-1"></i> Pending Submission
                                </span>
                            @endif
                        </td>

                        <!-- Action Links -->
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('monthly.voucher', ['department_id' => $dept->id, 'year' => $selectedYear, 'month' => $selectedMonth]) }}" 
                                   class="btn btn-outline-secondary" 
                                   title="View Monthly Plan Voucher" target="_blank">
                                    <i class="fas fa-calendar-days"></i>
                                </a>
                                <a href="{{ route('quarterly.voucher', ['department_id' => $dept->id, 'year' => $selectedYear, 'quarter' => $selectedQuarter]) }}" 
                                   class="btn btn-outline-primary" 
                                   title="View Quarterly Voucher" target="_blank">
                                    <i class="fas fa-layer-group"></i>
                                </a>
                                <a href="{{ route('performance.quarterly', ['department_id' => $dept->id, 'year' => $selectedYear, 'quarter' => $selectedQuarter]) }}" 
                                   class="btn btn-outline-dark" 
                                   title="Quarterly Performance Review">
                                    <i class="fas fa-magnifying-glass-chart"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            No operational departments found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Quick Reporting Links Cards -->
<div class="row g-3">
    <div class="col-md-3">
        <div class="card h-100 p-3 border-0 shadow-sm bg-white">
            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-file-alt text-primary me-2"></i> Weekly Scorecard</h6>
            <p class="small text-muted mb-3">Cross-department execution matrix, task statuses, and compliance rates.</p>
            <a href="{{ route('performance.weekly') }}" class="btn btn-sm btn-outline-secondary mt-auto">
                Open Scorecard &rarr;
            </a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 p-3 border-0 shadow-sm bg-white">
            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-calendar-alt text-success me-2"></i> Monthly Rollup</h6>
            <p class="small text-muted mb-3">Departmental monthly execution progress and task completion rates.</p>
            <a href="{{ route('performance.monthly') }}" class="btn btn-sm btn-outline-secondary mt-auto">
                Open Monthly Rollup &rarr;
            </a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 p-3 border-0 shadow-sm bg-white">
            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-layer-group text-warning me-2"></i> Quarterly Module</h6>
            <p class="small text-muted mb-3">Bulk 3-month performance submission, ratings, and executive approvals.</p>
            <a href="{{ route('performance.quarterly') }}" class="btn btn-sm btn-outline-secondary mt-auto">
                Open Quarterly &rarr;
            </a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 p-3 border-0 shadow-sm bg-white">
            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-triangle-exclamation text-danger me-2"></i> Root Cause Log</h6>
            <p class="small text-muted mb-3">Categorized breakdown of missed targets, explanations, and corrective actions.</p>
            <a href="{{ route('performance.not_done') }}" class="btn btn-sm btn-outline-secondary mt-auto">
                Open Analysis &rarr;
            </a>
        </div>
    </div>
</div>
@endsection

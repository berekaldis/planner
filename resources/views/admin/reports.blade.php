@extends('layouts.app')

@section('title', 'Reports & Tracking - Kaldis PMS')

@section('content')
<!-- Print Stylesheet -->
<style>
@media print {
    .app-sidebar, .app-header, .filter-bar, .nav-tabs, .btn, .no-print, .pagination, footer {
        display: none !important;
    }
    .main-content, .content-wrapper, .container-fluid {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
        break-inside: avoid;
    }
    .print-only-header {
        display: block !important;
    }
}
.print-only-header {
    display: none;
}
</style>

<!-- Print Only Header -->
<div class="print-only-header mb-4">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
        <div>
            <h4 class="fw-bold mb-0">Kaldis Coffee PLC - Enterprise Planning &amp; Tracking Report</h4>
            <div class="small text-muted">Period: {{ $selectedMonth }} {{ $selectedYear }} &bull; {{ $quarters[$selectedQuarter]['name'] ?? $selectedQuarter }}</div>
        </div>
        <div class="small text-muted text-end">
            Generated: {{ now()->format('Y-m-d H:i') }}<br>
            By: {{ auth()->user()->full_name ?? auth()->user()->username }}
        </div>
    </div>
</div>

<!-- Header & Period Filter Bar -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 no-print">
    <div>
        <h4 class="fw-bold text-dark mb-1">Reports &amp; Tracking</h4>
        <p class="text-muted small mb-0">Enterprise Compliance &amp; Performance Summary &bull; {{ $selectedMonth }} {{ $selectedYear }}</p>
    </div>

    <!-- Actions & Filter Form -->
    <div class="d-flex flex-wrap align-items-center gap-2">
        <form method="GET" action="{{ route('admin.reports') }}" class="d-flex flex-wrap gap-2">
            <select name="quarter" class="form-select form-select-sm" style="min-width: 140px;" onchange="this.form.submit()">
                @foreach($quarters as $qCode => $qInfo)
                    <option value="{{ $qCode }}" {{ $selectedQuarter === $qCode ? 'selected' : '' }}>
                        {{ $qCode }} ({{ $qInfo['months'][0] }} - {{ $qInfo['months'][2] }})
                    </option>
                @endforeach
            </select>

            <select name="month" class="form-select form-select-sm" style="min-width: 120px;" onchange="this.form.submit()">
                @foreach(get_ethiopian_months() as $m)
                    <option value="{{ $m }}" {{ $selectedMonth === $m ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select>

            <select name="year" class="form-select form-select-sm" style="min-width: 100px;" onchange="this.form.submit()">
                @foreach(get_planning_years() as $yr)
                    <option value="{{ $yr }}" {{ $selectedYear === $yr ? 'selected' : '' }}>{{ $yr }}</option>
                @endforeach
            </select>
        </form>

        <a href="{{ route('admin.reports.export', ['year' => $selectedYear, 'month' => $selectedMonth, 'quarter' => $selectedQuarter]) }}" 
           class="btn btn-outline-success btn-sm" 
           title="Export report to Excel CSV">
            <i class="fas fa-file-excel me-1"></i> Export Excel
        </a>

        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()" title="Print this report">
            <i class="fas fa-print me-1"></i> Print
        </button>
    </div>
</div>

<!-- High-Level KPI Summary Cards -->
<div class="row g-3 mb-4">
    <!-- Departments -->
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Departments</span>
            <div class="d-flex align-items-baseline gap-2">
                <span class="fs-4 fw-bold text-dark">{{ $departments->count() }}</span>
                <span class="badge bg-secondary-subtle text-secondary small">Active</span>
            </div>
            <div class="small text-muted mt-1">Operating business units</div>
        </div>
    </div>

    <!-- Monthly Submission -->
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Monthly Plans</span>
            <div class="d-flex align-items-baseline gap-2">
                <span class="fs-4 fw-bold text-primary">{{ $totalMonthlySubmitted }} / {{ $departments->count() }}</span>
                <span class="badge bg-primary-subtle text-primary small">
                    {{ $departments->count() > 0 ? round(($totalMonthlySubmitted / $departments->count()) * 100) : 0 }}%
                </span>
            </div>
            <div class="small text-muted mt-1">{{ $selectedMonth }} plan submissions</div>
        </div>
    </div>

    <!-- Weekly Tasks Completion -->
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Weekly Execution</span>
            <div class="d-flex align-items-baseline gap-2">
                <span class="fs-4 fw-bold {{ $overallWeeklyRate >= 80 ? 'text-success' : ($overallWeeklyRate >= 50 ? 'text-primary' : 'text-danger') }}">
                    {{ $overallWeeklyRate }}%
                </span>
                <span class="badge bg-success-subtle text-success small">{{ $doneWeeklyTasksCount }} Done</span>
            </div>
            <div class="small text-muted mt-1">Out of {{ $totalWeeklyTasksAll }} total weekly tasks</div>
        </div>
    </div>

    <!-- Quarterly Submission -->
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Quarterly Packages</span>
            <div class="d-flex align-items-baseline gap-2">
                <span class="fs-4 fw-bold text-dark">{{ $totalQuarterlySubmitted }} / {{ $departments->count() }}</span>
                <span class="badge bg-warning-subtle text-warning-emphasis small">{{ $selectedQuarter }}</span>
            </div>
            <div class="small text-muted mt-1">Executive quarterly reviews</div>
        </div>
    </div>
</div>

<!-- Search & Status Filter Bar -->
<div class="card border-0 shadow-sm mb-4 bg-white no-print">
    <div class="card-body p-3">
        <div class="row g-2 align-items-center">
            <!-- Search Box -->
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" 
                           id="reportSearchInput" 
                           class="form-control border-start-0" 
                           placeholder="Search department name or code..." 
                           oninput="filterReportRows()">
                    <button class="btn btn-outline-secondary" type="button" onclick="clearReportSearch()" title="Clear search">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Quick Status Filters -->
            <div class="col-md-7">
                <div class="d-flex flex-wrap gap-1 justify-content-md-end" id="statusFilterPills">
                    <button type="button" class="btn btn-xs btn-dark py-1 px-2.5 active-pill" onclick="setStatusFilter('ALL', this)">
                        All ({{ $departments->count() }})
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-success py-1 px-2.5" onclick="setStatusFilter('SUBMITTED', this)">
                        <i class="fas fa-check-circle me-1"></i> Submitted ({{ $totalMonthlySubmitted - $totalDelayed }})
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-warning py-1 px-2.5" onclick="setStatusFilter('DELAYED', this)">
                        <i class="fas fa-clock me-1"></i> Delayed ({{ $totalDelayed }})
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-danger py-1 px-2.5" onclick="setStatusFilter('PENDING', this)">
                        <i class="fas fa-exclamation-circle me-1"></i> Incomplete ({{ $departments->count() - $totalMonthlySubmitted }})
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reporting Views (Tabs) -->
<div class="card border-0 shadow-sm bg-white mb-4">
    <div class="card-header bg-white border-bottom p-0 no-print">
        <ul class="nav nav-tabs border-0 px-3 pt-2" id="reportTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-semibold text-dark py-2.5 px-3" id="overview-tab" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button" role="tab">
                    <i class="fas fa-table-list text-primary me-1.5"></i> Overview Matrix
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold text-dark py-2.5 px-3" id="monthly-tab" data-bs-toggle="tab" data-bs-target="#tab-monthly" type="button" role="tab">
                    <i class="fas fa-calendar-alt text-success me-1.5"></i> Monthly Plan Tracking
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold text-dark py-2.5 px-3" id="weekly-tab" data-bs-toggle="tab" data-bs-target="#tab-weekly" type="button" role="tab">
                    <i class="fas fa-tasks text-info me-1.5"></i> Weekly Execution Report
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold text-dark py-2.5 px-3" id="insights-tab" data-bs-toggle="tab" data-bs-target="#tab-insights" type="button" role="tab">
                    <i class="fas fa-award text-warning me-1.5"></i> Performance Insights &amp; Rankings
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-0">
        <div class="tab-content" id="reportTabsContent">

            <!-- TAB 1: OVERVIEW MATRIX -->
            <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="overviewReportTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 200px;">Department</th>
                                <th style="width: 180px;">Monthly Plan ({{ $selectedMonth }})</th>
                                <th style="width: 240px;">Weekly Execution Rate</th>
                                <th style="width: 170px;">Quarterly ({{ $selectedQuarter }})</th>
                                <th style="width: 140px;" class="text-end no-print">Quick Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($complianceRows as $row)
                                @php
                                    $dept = $row['department'];
                                    $mSub = $row['monthly_submission'];
                                    $qSub = $row['quarterly_submission'];
                                    $statusKey = $row['status_key'];
                                @endphp
                                <tr class="report-row" 
                                    data-dept-name="{{ strtolower($dept->department_name) }}" 
                                    data-dept-code="{{ strtolower($dept->department_code) }}"
                                    data-status="{{ $statusKey }}">
                                    <!-- Department -->
                                    <td>
                                        <div class="fw-bold text-dark">{{ $dept->department_name }}</div>
                                        <span class="badge bg-secondary-subtle text-secondary border font-monospace" style="font-size: 0.7rem;">
                                            {{ $dept->department_code }}
                                        </span>
                                    </td>

                                    <!-- Monthly Plan Status -->
                                    <td>
                                        @if($mSub && ($mSub->submitted_at || $mSub->isDelayApproved()))
                                            <div class="d-flex align-items-center gap-1 mb-1">
                                                @if($mSub->isDelayApproved())
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" title="{{ $mSub->delay_reason }}">
                                                        <i class="fas fa-shield-alt me-0.5"></i> Delay Approved
                                                    </span>
                                                @elseif($mSub->isDelayPending())
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" title="{{ $mSub->delay_reason }}">
                                                        <i class="fas fa-hourglass-half me-0.5"></i> Delay Pending
                                                    </span>
                                                @else
                                                    <span class="badge {{ $mSub->status === 'ON_TIME' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' }}">
                                                        <i class="fas {{ $mSub->status === 'ON_TIME' ? 'fa-check' : 'fa-clock' }} me-0.5"></i> {{ $mSub->status === 'ON_TIME' ? 'On-Time' : 'Late' }}
                                                    </span>
                                                @endif
                                                <span class="badge bg-light text-dark border">{{ $row['monthly_plans_total'] }} Plans</span>
                                            </div>
                                            <div class="text-muted small">
                                                {{ $row['monthly_strat_count'] }} Strat &bull; {{ $row['monthly_ops_count'] }} Ops
                                            </div>
                                        @elseif($row['monthly_plans_total'] > 0)
                                            <span class="badge bg-info-subtle text-info border border-info-subtle">
                                                <i class="fas fa-file-pen me-0.5"></i> Draft ({{ $row['monthly_plans_total'] }} Plans)
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                <i class="fas fa-times-circle me-0.5"></i> Not Started
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Weekly Execution -->
                                    <td>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold {{ $row['weekly_rate'] >= 80 ? 'text-success' : ($row['weekly_rate'] >= 50 ? 'text-primary' : 'text-danger') }}">
                                                {{ $row['weekly_rate'] }}%
                                            </span>
                                            <span class="text-muted small">{{ $row['weekly_done'] }} / {{ $row['weekly_tasks_total'] }} Done</span>
                                        </div>
                                        <div class="progress mb-1" style="height: 5px;">
                                            <div class="progress-bar {{ $row['weekly_rate'] >= 80 ? 'bg-success' : ($row['weekly_rate'] >= 50 ? 'bg-primary' : 'bg-danger') }}" 
                                                 style="width: {{ $row['weekly_rate'] }}%;"></div>
                                        </div>
                                        <div class="text-muted" style="font-size: 0.72rem;">
                                            {{ $row['weeks_submitted'] }} weekly report(s) submitted
                                        </div>
                                    </td>

                                    <!-- Quarterly -->
                                    <td>
                                        @if($qSub && $qSub->status === 'SUBMITTED')
                                            <div class="d-flex align-items-center gap-1 mb-1">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    {{ $qSub->completion_percentage }}%
                                                </span>
                                                <span class="badge {{ $qSub->approval_status === 'APPROVED' ? 'bg-success' : 'bg-secondary' }}" style="font-size: 0.68rem;">
                                                    {{ $qSub->approval_status ?? 'SUBMITTED' }}
                                                </span>
                                            </div>
                                            <div class="text-muted" style="font-size: 0.72rem;">
                                                Submitted {{ $qSub->submitted_at ? $qSub->submitted_at->format('M d') : '' }}
                                            </div>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border">
                                                Pending Submission
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Actions -->
                                    <td class="text-end no-print">
                                        <div class="btn-group btn-group-sm">
                                            @if(kaldis_setting('voucher_enabled', 'true') === 'true')
                                                <a href="{{ route('monthly.voucher', ['department_id' => $dept->id, 'year' => $selectedYear, 'month' => $selectedMonth]) }}" 
                                                   class="btn btn-outline-secondary btn-xs py-1 px-2" 
                                                   title="View Monthly Voucher" target="_blank">
                                                    <i class="fas fa-file-invoice"></i>
                                                </a>
                                            @endif
                                            <a href="{{ route('monthly.plans', ['department_id' => $dept->id, 'year' => $selectedYear, 'month' => $selectedMonth]) }}" 
                                               class="btn btn-outline-secondary btn-xs py-1 px-2" 
                                               title="View Department Monthly Plans">
                                                <i class="fas fa-calendar-alt"></i>
                                            </a>
                                            <a href="{{ route('weekly.performance', ['department_id' => $dept->id, 'year' => $selectedYear, 'month' => $selectedMonth]) }}" 
                                               class="btn btn-outline-primary btn-xs py-1 px-2" 
                                               title="View Weekly Execution">
                                                <i class="fas fa-chart-line"></i>
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

            <!-- TAB 2: MONTHLY PLANNING REPORT -->
            <div class="tab-pane fade" id="tab-monthly" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="monthlyReportTable">
                        <thead class="table-light">
                            <tr>
                                <th>Department</th>
                                <th>Submission Status</th>
                                <th class="text-center">Total Plans</th>
                                <th class="text-center">Strategic</th>
                                <th class="text-center">Operational</th>
                                <th>GM Review Status</th>
                                <th class="text-end no-print">View</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($complianceRows as $row)
                                @php
                                    $dept = $row['department'];
                                    $mSub = $row['monthly_submission'];
                                    $statusKey = $row['status_key'];
                                @endphp
                                <tr class="report-row" 
                                    data-dept-name="{{ strtolower($dept->department_name) }}" 
                                    data-dept-code="{{ strtolower($dept->department_code) }}"
                                    data-status="{{ $statusKey }}">
                                    <td>
                                        <div class="fw-bold text-dark">{{ $dept->department_name }}</div>
                                        <span class="badge bg-secondary-subtle text-secondary border font-monospace" style="font-size: 0.7rem;">
                                            {{ $dept->department_code }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($mSub && $mSub->isDelayApproved())
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                <i class="fas fa-shield-alt me-1"></i> Delay Approved
                                            </span>
                                        @elseif($mSub && $mSub->isDelayPending())
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                                <i class="fas fa-hourglass-half me-1"></i> Delay Pending
                                            </span>
                                        @elseif($mSub && in_array($mSub->status, ['ON_TIME', 'LATE', 'SUBMITTED']))
                                            <span class="badge {{ $mSub->status === 'ON_TIME' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' }}">
                                                <i class="fas {{ $mSub->status === 'ON_TIME' ? 'fa-check' : 'fa-clock' }} me-1"></i>
                                                {{ $mSub->status === 'ON_TIME' ? 'Submitted On-Time' : 'Submitted Late' }}
                                            </span>
                                        @elseif($row['monthly_plans_total'] > 0)
                                            <span class="badge bg-info-subtle text-info border border-info-subtle">
                                                <i class="fas fa-file-pen me-1"></i> Draft in Progress
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                <i class="fas fa-times me-1"></i> Not Started
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center fw-bold">{{ $row['monthly_plans_total'] }}</td>
                                    <td class="text-center text-primary fw-semibold">{{ $row['monthly_strat_count'] }}</td>
                                    <td class="text-center text-secondary fw-semibold">{{ $row['monthly_ops_count'] }}</td>
                                    <td>
                                        @if($mSub && !empty($mSub->approval_status))
                                            <span class="badge {{ $mSub->approval_status === 'APPROVED' ? 'bg-success' : ($mSub->approval_status === 'REVISION_REQUESTED' ? 'bg-danger' : 'bg-secondary') }}">
                                                {{ $mSub->approval_status }}
                                            </span>
                                        @else
                                            <span class="text-muted small fst-italic">Pending Submission</span>
                                        @endif
                                    </td>
                                    <td class="text-end no-print">
                                        <a href="{{ route('monthly.plans', ['department_id' => $dept->id, 'year' => $selectedYear, 'month' => $selectedMonth]) }}" 
                                           class="btn btn-outline-secondary btn-xs py-1 px-2">
                                            Open Plans &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 3: WEEKLY EXECUTION REPORT -->
            <div class="tab-pane fade" id="tab-weekly" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="weeklyReportTable">
                        <thead class="table-light">
                            <tr>
                                <th>Department</th>
                                <th class="text-center">Total Weekly Tasks</th>
                                <th class="text-center text-success">Completed (DONE)</th>
                                <th class="text-center text-danger">Not Done</th>
                                <th style="width: 220px;">Completion Rate</th>
                                <th class="text-center">Weeks Reported</th>
                                <th class="text-end no-print">View</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($complianceRows as $row)
                                @php
                                    $dept = $row['department'];
                                    $statusKey = $row['status_key'];
                                @endphp
                                <tr class="report-row" 
                                    data-dept-name="{{ strtolower($dept->department_name) }}" 
                                    data-dept-code="{{ strtolower($dept->department_code) }}"
                                    data-status="{{ $statusKey }}">
                                    <td>
                                        <div class="fw-bold text-dark">{{ $dept->department_name }}</div>
                                        <span class="badge bg-secondary-subtle text-secondary border font-monospace" style="font-size: 0.7rem;">
                                            {{ $dept->department_code }}
                                        </span>
                                    </td>
                                    <td class="text-center fw-bold">{{ $row['weekly_tasks_total'] }}</td>
                                    <td class="text-center text-success fw-bold">{{ $row['weekly_done'] }}</td>
                                    <td class="text-center text-danger fw-bold">{{ $row['weekly_not_done'] }}</td>
                                    <td>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold {{ $row['weekly_rate'] >= 80 ? 'text-success' : ($row['weekly_rate'] >= 50 ? 'text-primary' : 'text-danger') }}">
                                                {{ $row['weekly_rate'] }}%
                                            </span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar {{ $row['weekly_rate'] >= 80 ? 'bg-success' : ($row['weekly_rate'] >= 50 ? 'bg-primary' : 'bg-danger') }}" 
                                                 style="width: {{ $row['weekly_rate'] }}%;"></div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">
                                            {{ $row['weeks_submitted'] }} / 4 Weeks
                                        </span>
                                    </td>
                                    <td class="text-end no-print">
                                        <a href="{{ route('weekly.performance', ['department_id' => $dept->id, 'year' => $selectedYear, 'month' => $selectedMonth]) }}" 
                                           class="btn btn-outline-primary btn-xs py-1 px-2">
                                            Performance &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 4: PERFORMANCE INSIGHTS & RANKINGS -->
            <div class="tab-pane fade p-3" id="tab-insights" role="tabpanel">
                @php
                    $sortedByCompletion = collect($complianceRows)->sortByDesc('weekly_rate');
                    $topPerformers = $sortedByCompletion->filter(fn($r) => $r['weekly_tasks_total'] > 0)->take(5);
                    $pendingDepts = collect($complianceRows)->filter(fn($r) => !in_array($r['status_key'], ['SUBMITTED', 'DELAYED']));
                @endphp
                <div class="row g-4">
                    <!-- Top Performing Departments -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 bg-light h-100">
                            <h6 class="fw-bold text-dark mb-3">
                                <i class="fas fa-trophy text-warning me-2"></i> Top Performing Departments (Weekly Execution)
                            </h6>
                            @if($topPerformers->isNotEmpty())
                                <div class="list-group list-group-flush bg-transparent">
                                    @foreach($topPerformers as $index => $top)
                                        <div class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-dark rounded-circle me-1" style="width: 22px; height: 22px; line-height: 14px; text-align: center;">{{ $index + 1 }}</span>
                                                <strong class="text-dark">{{ $top['department']->department_name }}</strong>
                                                <div class="small text-muted ps-4">{{ $top['weekly_done'] }} of {{ $top['weekly_tasks_total'] }} tasks achieved</div>
                                            </div>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle fs-6">
                                                {{ $top['weekly_rate'] }}%
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted small mb-0">No weekly performance evaluations recorded yet for this month.</p>
                            @endif
                        </div>
                    </div>

                    <!-- Departments Requiring Attention -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 bg-light h-100">
                            <h6 class="fw-bold text-dark mb-3">
                                <i class="fas fa-exclamation-triangle text-danger me-2"></i> Action Required / Incomplete Monthly Plans
                            </h6>
                            @if($pendingDepts->isNotEmpty())
                                <div class="list-group list-group-flush bg-transparent">
                                    @foreach($pendingDepts as $pDept)
                                        <div class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong class="text-dark">{{ $pDept['department']->department_name }}</strong>
                                                <div class="small text-muted">
                                                    @if($pDept['monthly_plans_total'] > 0)
                                                        <span class="text-warning-emphasis">Draft saved ({{ $pDept['monthly_plans_total'] }} plans) &bull; Not yet submitted to GM</span>
                                                    @else
                                                        <span class="text-danger">Plan not started for {{ $selectedMonth }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <a href="{{ route('monthly.plans', ['department_id' => $pDept['department']->id, 'year' => $selectedYear, 'month' => $selectedMonth]) }}" 
                                               class="btn btn-xs btn-outline-secondary py-1 px-2">
                                                Follow Up
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-success border-0 small mb-0">
                                    <i class="fas fa-check-circle me-1"></i> All departments have submitted their monthly plans for {{ $selectedMonth }}.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Quick Navigation Links (Simplified) -->
<div class="row g-3 no-print">
    <div class="col-6 col-md-3">
        <a href="{{ route('performance.weekly') }}" class="card p-3 border-0 shadow-sm text-decoration-none text-dark h-100 bg-white hover-shadow transition">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="fas fa-file-alt text-primary fs-5"></i>
                <strong class="small">Weekly Scorecard</strong>
            </div>
            <p class="small text-muted mb-0">Cross-department execution matrix &rarr;</p>
        </a>
    </div>

    <div class="col-6 col-md-3">
        <a href="{{ route('performance.monthly') }}" class="card p-3 border-0 shadow-sm text-decoration-none text-dark h-100 bg-white hover-shadow transition">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="fas fa-calendar-alt text-success fs-5"></i>
                <strong class="small">Monthly Rollup</strong>
            </div>
            <p class="small text-muted mb-0">Department monthly delivery &rarr;</p>
        </a>
    </div>

    <div class="col-6 col-md-3">
        <a href="{{ route('performance.quarterly') }}" class="card p-3 border-0 shadow-sm text-decoration-none text-dark h-100 bg-white hover-shadow transition">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="fas fa-layer-group text-warning fs-5"></i>
                <strong class="small">Quarterly Review</strong>
            </div>
            <p class="small text-muted mb-0">Quarter performance ratings &rarr;</p>
        </a>
    </div>

    <div class="col-6 col-md-3">
        <a href="{{ route('performance.not_done') }}" class="card p-3 border-0 shadow-sm text-decoration-none text-dark h-100 bg-white hover-shadow transition">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="fas fa-triangle-exclamation text-danger fs-5"></i>
                <strong class="small">Root Cause Log</strong>
            </div>
            <p class="small text-muted mb-0">Missed targets &amp; next actions &rarr;</p>
        </a>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let currentStatusFilter = 'ALL';

    function setStatusFilter(status, btnElement) {
        currentStatusFilter = status;
        
        // Update pill styling
        const pills = document.querySelectorAll('#statusFilterPills button');
        pills.forEach(p => {
            p.classList.remove('btn-dark');
            if (p.getAttribute('onclick').includes('SUBMITTED')) {
                p.className = 'btn btn-xs btn-outline-success py-1 px-2.5';
            } else if (p.getAttribute('onclick').includes('DELAYED')) {
                p.className = 'btn btn-xs btn-outline-warning py-1 px-2.5';
            } else if (p.getAttribute('onclick').includes('PENDING')) {
                p.className = 'btn btn-xs btn-outline-danger py-1 px-2.5';
            } else {
                p.className = 'btn btn-xs btn-outline-secondary py-1 px-2.5';
            }
        });

        btnElement.classList.remove('btn-outline-success', 'btn-outline-warning', 'btn-outline-danger', 'btn-outline-secondary');
        btnElement.classList.add('btn-dark');

        filterReportRows();
    }

    function clearReportSearch() {
        const input = document.getElementById('reportSearchInput');
        if (input) {
            input.value = '';
            filterReportRows();
        }
    }

    function filterReportRows() {
        const query = (document.getElementById('reportSearchInput')?.value || '').toLowerCase().trim();
        const rows = document.querySelectorAll('.report-row');

        rows.forEach(row => {
            const name = row.getAttribute('data-dept-name') || '';
            const code = row.getAttribute('data-dept-code') || '';
            const status = row.getAttribute('data-status') || '';

            const matchesSearch = !query || name.includes(query) || code.includes(query);

            let matchesStatus = true;
            if (currentStatusFilter === 'SUBMITTED') {
                matchesStatus = status === 'SUBMITTED';
            } else if (currentStatusFilter === 'DELAYED') {
                matchesStatus = status === 'DELAYED';
            } else if (currentStatusFilter === 'PENDING') {
                matchesStatus = (status === 'DRAFT' || status === 'NOT_STARTED');
            }

            if (matchesSearch && matchesStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>
@endsection

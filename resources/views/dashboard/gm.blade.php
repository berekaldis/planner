@extends('layouts.app')

@section('title', 'General Manager Executive Dashboard')

@section('content')
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="fas fa-crown text-warning me-2"></i> General Manager Executive Dashboard
        </h4>
        <p class="text-muted small mb-0">Strategic Alignment, Monthly Plans &amp; Weekly Performance Oversight &bull; <strong>{{ $selectedMonth }} {{ $selectedYear }}</strong></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <form method="POST" action="{{ route('management.run_telegram_check') }}" class="d-inline" onsubmit="return confirm('Trigger automated Telegram reminders & executive summary report for {{ $selectedMonth }} {{ $selectedYear }} (Week {{ $selectedWeek }})?');">
            @csrf
            <input type="hidden" name="year" value="{{ $selectedYear }}">
            <input type="hidden" name="month" value="{{ $selectedMonth }}">
            <input type="hidden" name="week" value="{{ $selectedWeek }}">
            <button type="submit" class="btn btn-outline-primary btn-sm shadow-sm" title="Run automated Telegram reminders and dispatch GM executive summary">
                <i class="fab fa-telegram-plane text-primary me-1"></i> ⚡ Run Automated Telegram Check
            </button>
        </form>
        <a href="{{ route('strategy.activation', ['year' => $selectedYear, 'month' => $selectedMonth]) }}" class="btn btn-kaldis btn-sm">
            <i class="fas fa-toggle-on me-1"></i> Monthly Activation
        </a>
        <a href="{{ route('performance.weekly', ['year' => $selectedYear, 'month' => $selectedMonth]) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-table me-1"></i> Weekly Scorecard
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar mb-4">
    <form method="GET" action="{{ route('dashboard.gm') }}" class="row g-2 align-items-center">
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
        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Inspection Week</label>
            <select name="week" class="form-select form-select-sm" onchange="this.form.submit()">
                @for($w = 1; $w <= 5; $w++)
                    <option value="{{ $w }}" {{ $selectedWeek === $w ? 'selected' : '' }}>Week {{ $w }}</option>
                @endfor
            </select>
        </div>
        <div class="col-md-3 text-end pt-3">
            <span class="badge bg-light text-secondary border px-3 py-2">
                <i class="fas fa-calendar-check text-warning me-1"></i> Ethiopian Period: <strong>{{ $selectedMonth }} {{ $selectedYear }}</strong>
            </span>
        </div>
    </form>
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

<!-- ══════════════════════════════════════════════════════════
     EXECUTIVE PLANS & REPORTS COMMAND CENTER (GM EASY VIEW)
     ══════════════════════════════════════════════════════════ -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div>
                <h5 class="fw-bold text-dark mb-0">
                    <i class="fas fa-file-invoice text-warning me-2"></i> Executive Plans &amp; Performance Vouchers Command Center
                </h5>
                <small class="text-muted">Direct oversight, submission compliance tracking (On-Time / Late / Not Submitted), and official voucher inspection</small>
            </div>
            <ul class="nav nav-pills react-nav-pills small" id="gmExecutiveTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-weekly-reports" data-bs-toggle="pill" data-bs-target="#content-weekly-reports" type="button" role="tab" aria-controls="content-weekly-reports" aria-selected="true">
                        <i class="fas fa-calendar-check me-1"></i> Weekly Reports (Week {{ $selectedWeek }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-monthly-plans" data-bs-toggle="pill" data-bs-target="#content-monthly-plans" type="button" role="tab" aria-controls="content-monthly-plans" aria-selected="false">
                        <i class="fas fa-calendar-days me-1"></i> Monthly Plans ({{ $selectedMonth }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-all-plans" data-bs-toggle="pill" data-bs-target="#content-all-plans" type="button" role="tab" aria-controls="content-all-plans" aria-selected="false">
                        <i class="fas fa-sitemap me-1"></i> Master Plans Explorer ({{ $allMonthlyPlans->count() }})
                    </button>
                </li>
            </ul>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="tab-content" id="gmExecutiveTabContent">
            
            <!-- ─────────────────────────────────────────────────────────────
                 TAB 1: WEEKLY EXECUTION & PERFORMANCE REPORTS (WEEK X)
                 ───────────────────────────────────────────────────────────── -->
            <div class="tab-pane fade show active" id="content-weekly-reports" role="tabpanel" aria-labelledby="tab-weekly-reports">
                <div class="p-3 bg-light border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="small fw-bold text-dark">Switch Week:</span>
                        <div class="btn-group btn-group-sm">
                            @for($w = 1; $w <= 5; $w++)
                                <a href="{{ route('dashboard.gm', ['year' => $selectedYear, 'month' => $selectedMonth, 'week' => $w]) }}" 
                                   class="btn {{ $selectedWeek === $w ? 'btn-warning fw-bold text-dark' : 'btn-outline-secondary' }}">
                                    Week {{ $w }}
                                </a>
                            @endfor
                        </div>
                    </div>
                    <div class="small text-muted">
                        <span class="badge bg-success-subtle text-success border border-success me-1">🟢 On-Time</span>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning me-1">🟡 Late</span>
                        <span class="badge bg-danger-subtle text-danger border border-danger">🔴 Not Submitted</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Department</th>
                                <th>Department Head</th>
                                <th class="text-center">Submission Status</th>
                                <th class="text-center">Tasks (Done / Total)</th>
                                <th>Completion Rate</th>
                                <th class="text-center">GM Approval</th>
                                <th class="text-end pe-3">Official Voucher &amp; Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($departmentPerformances as $dp)
                                @php
                                    $dept = $dp['department'];
                                    $wSub = $weeklySubmissions[$dept->id] ?? null;
                                    $wRep = $weeklyReports[$dept->id] ?? null;
                                    
                                    $status = 'NOT_SUBMITTED';
                                    if ($wSub && $wSub->submitted_at) {
                                        $status = $wSub->status ?: 'ON_TIME';
                                    } elseif ($wRep && $wRep->submitted_at) {
                                        $status = $wRep->submission_status ?: 'ON_TIME';
                                    }

                                    $approval = $wRep->approval_status ?? ($wSub->approval_status ?? 'PENDING');
                                @endphp
                                <tr>
                                    <td>
                                        <strong class="text-dark">{{ $dept->department_name }}</strong>
                                        <span class="badge bg-light text-secondary border font-monospace ms-1">{{ $dept->department_code }}</span>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-dark">{{ $dept->head->full_name ?? 'N/A' }}</div>
                                        <small class="text-muted" style="font-size: 0.72rem;">{{ $dept->head->email ?? '' }}</small>
                                    </td>
                                    <td class="text-center">
                                        @if($status === 'ON_TIME')
                                            <span class="badge bg-success px-2.5 py-1.5"><i class="fas fa-check-circle me-1"></i> ON-TIME</span>
                                            @if($wSub && $wSub->submitted_at)
                                                <small class="d-block text-muted" style="font-size: 0.7rem;">{{ $wSub->submitted_at->format('M d, H:i') }}</small>
                                            @endif
                                        @elseif($status === 'LATE')
                                            <span class="badge bg-warning text-dark px-2.5 py-1.5"><i class="fas fa-clock me-1"></i> LATE</span>
                                            @if($wSub && $wSub->submitted_at)
                                                <small class="d-block text-muted" style="font-size: 0.7rem;">{{ $wSub->submitted_at->format('M d, H:i') }}</small>
                                            @endif
                                        @else
                                            <span class="badge bg-danger px-2.5 py-1.5"><i class="fas fa-times-circle me-1"></i> NOT SUBMITTED</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($dp['total'] > 0)
                                            <span class="text-success fw-bold">{{ $dp['done'] }}</span> / 
                                            <span class="fw-bold">{{ $dp['total'] }}</span>
                                            @if($dp['not_done'] > 0)
                                                <small class="text-danger d-block">({{ $dp['not_done'] }} not done)</small>
                                            @endif
                                        @else
                                            <span class="text-muted small">0 tasks</span>
                                        @endif
                                    </td>
                                    <td style="min-width: 170px;">
                                        @if($dp['total'] > 0)
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 8px;">
                                                    <div class="progress-bar {{ $dp['rate'] >= 80 ? 'bg-success' : ($dp['rate'] >= 50 ? 'bg-warning' : 'bg-danger') }}" 
                                                         style="width: {{ $dp['rate'] }}%;"></div>
                                                </div>
                                                <span class="small fw-bold">{{ $dp['rate'] }}%</span>
                                            </div>
                                        @else
                                            <span class="text-muted small">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $approval === 'APPROVED' ? 'bg-success-subtle text-success border border-success' : ($approval === 'REVISION_REQUESTED' ? 'bg-danger-subtle text-danger border border-danger' : 'bg-light text-muted border') }} small">
                                            {{ $approval }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="{{ route('weekly.voucher', ['department_id' => $dept->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'week' => $selectedWeek]) }}" 
                                               class="btn btn-xs btn-kaldis shadow-sm" title="Open Official Performance Voucher">
                                                <i class="fas fa-receipt me-1"></i> View Voucher
                                            </a>
                                            @if($status === 'NOT_SUBMITTED')
                                                <form method="POST" action="{{ route('management.remind') }}" class="d-inline" onsubmit="return confirm('Send Telegram reminder to {{ $dept->department_name }} Department Head?');">
                                                    @csrf
                                                    <input type="hidden" name="department_id" value="{{ $dept->id }}">
                                                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                                                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                                    <input type="hidden" name="week" value="{{ $selectedWeek }}">
                                                    <input type="hidden" name="type" value="WEEKLY">
                                                    <button type="submit" class="btn btn-xs btn-outline-danger" title="Send Telegram Escalation Reminder">
                                                        <i class="fab fa-telegram-plane"></i> Remind
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ─────────────────────────────────────────────────────────────
                 TAB 2: MONTHLY STRATEGIC & OPERATIONAL PLANS (BY DEPT)
                 ───────────────────────────────────────────────────────────── -->
            <div class="tab-pane fade" id="content-monthly-plans" role="tabpanel" aria-labelledby="tab-monthly-plans">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Department</th>
                                <th>Department Head</th>
                                <th class="text-center">Submission Status</th>
                                <th class="text-center">Plans Count</th>
                                <th class="text-center">Strategy / Operational</th>
                                <th class="text-center">GM Approval</th>
                                <th class="text-end pe-3">Official Voucher &amp; Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($departmentPerformances as $dp)
                                @php
                                    $dept = $dp['department'];
                                    $mSub = $monthlySubmissions[$dept->id] ?? null;
                                    $deptPlans = $monthlyPlansByDept[$dept->id] ?? collect();
                                    $stratCount = $deptPlans->where('plan_type', 'STRATEGY')->count();
                                    $opsCount = $deptPlans->where('plan_type', 'OPERATIONAL')->count();
                                    
                                    $mStatus = 'NOT_SUBMITTED';
                                    if ($mSub && $mSub->submitted_at) {
                                        $mStatus = $mSub->status ?: 'ON_TIME';
                                    } elseif ($deptPlans->count() > 0) {
                                        $mStatus = 'ON_TIME';
                                    }

                                    $mApproval = $mSub->approval_status ?? 'PENDING';
                                @endphp
                                <tr>
                                    <td>
                                        <strong class="text-dark">{{ $dept->department_name }}</strong>
                                        <span class="badge bg-light text-secondary border font-monospace ms-1">{{ $dept->department_code }}</span>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-dark">{{ $dept->head->full_name ?? 'N/A' }}</div>
                                        <small class="text-muted" style="font-size: 0.72rem;">{{ $dept->head->email ?? '' }}</small>
                                    </td>
                                    <td class="text-center">
                                        @if($mStatus === 'ON_TIME')
                                            <span class="badge bg-success px-2.5 py-1.5"><i class="fas fa-check-circle me-1"></i> ON-TIME</span>
                                            @if($mSub && $mSub->submitted_at)
                                                <small class="d-block text-muted" style="font-size: 0.7rem;">{{ $mSub->submitted_at->format('M d, H:i') }}</small>
                                            @endif
                                        @elseif($mStatus === 'LATE')
                                            <span class="badge bg-warning text-dark px-2.5 py-1.5"><i class="fas fa-clock me-1"></i> LATE</span>
                                            @if($mSub && $mSub->submitted_at)
                                                <small class="d-block text-muted" style="font-size: 0.7rem;">{{ $mSub->submitted_at->format('M d, H:i') }}</small>
                                            @endif
                                        @else
                                            <span class="badge bg-danger px-2.5 py-1.5"><i class="fas fa-times-circle me-1"></i> NOT SUBMITTED</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="fs-6 fw-bold text-dark">{{ $deptPlans->count() }}</span>
                                        <small class="text-muted d-block">Monthly Plans</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary-subtle text-primary border border-primary small">{{ $stratCount }} Strategy</span>
                                        <span class="badge bg-info-subtle text-info border border-info small ms-1">{{ $opsCount }} Ops</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $mApproval === 'APPROVED' ? 'bg-success-subtle text-success border border-success' : ($mApproval === 'REVISION_REQUESTED' ? 'bg-danger-subtle text-danger border border-danger' : 'bg-light text-muted border') }} small">
                                            {{ $mApproval }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="{{ route('monthly.voucher', ['department_id' => $dept->id, 'year' => $selectedYear, 'month' => $selectedMonth]) }}" 
                                               class="btn btn-xs btn-kaldis shadow-sm" title="Open Official Monthly Plan Voucher">
                                                <i class="fas fa-file-contract me-1"></i> View Voucher
                                            </a>
                                            @if($mStatus === 'NOT_SUBMITTED')
                                                <form method="POST" action="{{ route('management.remind') }}" class="d-inline" onsubmit="return confirm('Send Telegram reminder to {{ $dept->department_name }} Department Head?');">
                                                    @csrf
                                                    <input type="hidden" name="department_id" value="{{ $dept->id }}">
                                                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                                                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                                    <input type="hidden" name="type" value="MONTHLY">
                                                    <button type="submit" class="btn btn-xs btn-outline-danger" title="Send Telegram Monthly Plan Reminder">
                                                        <i class="fab fa-telegram-plane"></i> Remind
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ─────────────────────────────────────────────────────────────
                 TAB 3: MASTER MONTHLY PLANS EXPLORER (SEARCHABLE TABLE)
                 ───────────────────────────────────────────────────────────── -->
            <div class="tab-pane fade" id="content-all-plans" role="tabpanel" aria-labelledby="tab-all-plans">
                <div class="p-3 bg-light border-bottom">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-6">
                            <input type="text" id="gmAllPlansSearch" class="form-control form-control-sm" placeholder="🔍 Filter plans live by department, title, target, or responsible person...">
                        </div>
                        <div class="col-md-6 text-md-end text-muted small">
                            Total company-wide monthly plans: <strong>{{ $allMonthlyPlans->count() }}</strong>
                        </div>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0" id="gmAllPlansTable">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Dept</th>
                                <th>Type</th>
                                <th>Goal Link</th>
                                <th>Plan Title</th>
                                <th>Target Output</th>
                                <th>DOD (Definition of Done)</th>
                                <th class="text-center">Target %</th>
                                <th class="text-center">Priority</th>
                                <th>Responsible</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($allMonthlyPlans as $plan)
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-secondary border font-monospace">{{ $plan->department->department_code ?? 'DEPT' }}</span>
                                    </td>
                                    <td>
                                        @if($plan->plan_type === 'STRATEGY')
                                            <span class="badge bg-primary small">Strategy</span>
                                        @else
                                            <span class="badge bg-info text-dark small">Operational</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($plan->annualGoal)
                                            <span class="badge bg-secondary font-monospace" style="font-size: 0.72rem;">{{ $plan->annualGoal->goal_code }}</span>
                                        @else
                                            <span class="text-muted small">&mdash;</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong class="text-dark small">{{ $plan->title }}</strong>
                                    </td>
                                    <td class="text-primary small fw-semibold">{{ $plan->monthly_target }}</td>
                                    <td class="text-muted small" style="max-width: 250px;">{{ $plan->definition_of_done }}</td>
                                    <td class="text-center font-monospace small">{{ $plan->target_percentage }}%</td>
                                    <td class="text-center">
                                        <span class="badge {{ $plan->priority === 'HIGH' ? 'bg-danger' : ($plan->priority === 'LOW' ? 'bg-secondary' : 'bg-warning text-dark') }} small">
                                            {{ $plan->priority }}
                                        </span>
                                    </td>
                                    <td class="small text-muted">{{ $plan->responsible_person ?: 'Team' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No monthly plans found for {{ $selectedMonth }} {{ $selectedYear }}.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Master Plans Explorer Live Search
    const searchInput = document.getElementById('gmAllPlansSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#gmAllPlansTable tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = query === '' || text.includes(query) ? '' : 'none';
            });
        });
    }

    // Safely render charts if Chart.js is loaded and canvases exist
    if (typeof Chart !== 'undefined') {
        // Task Donut Chart
        const taskCanvas = document.getElementById('taskStatusChart');
        if (taskCanvas) {
            const taskCtx = taskCanvas.getContext('2d');
            const totalTasksVal = {{ (int)$totalTasks }};
            
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
                            data: [{{ (int)$doneTasks }}, {{ (int)$notDoneTasks }}, {{ (int)$pendingTasks }}],
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
        }

        // Submission Donut Chart
        const subCanvas = document.getElementById('submissionChart');
        if (subCanvas) {
            const subCtx = subCanvas.getContext('2d');
            const totalSubsVal = {{ (int)($onTimeSubmissions + $lateSubmissions + $missingSubmissions) }};
            
            if (totalSubsVal === 0 && {{ (int)$waitingSubmissions }} > 0) {
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
                            data: [{{ (int)$onTimeSubmissions }}, {{ (int)$lateSubmissions }}, {{ (int)$waitingSubmissions }}, {{ (int)$missingSubmissions }}],
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
        }
    }
});
</script>
@endsection

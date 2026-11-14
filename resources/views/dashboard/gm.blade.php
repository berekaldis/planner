@extends('layouts.app')

@section('title', 'General Manager Dashboard')

@section('content')
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold text-dark mb-1">General Manager Dashboard</h4>
        <p class="text-muted small mb-0">{{ $selectedMonth }} {{ $selectedYear }} &bull; Planning &amp; Execution Oversight</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('strategy.activation', ['year' => $selectedYear, 'month' => $selectedMonth]) }}" class="btn btn-outline-secondary btn-sm">
            Monthly Activation
        </a>
        <a href="{{ route('performance.weekly', ['year' => $selectedYear, 'month' => $selectedMonth]) }}" class="btn btn-outline-secondary btn-sm">
            Weekly Scorecard
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="card p-3 mb-4 border">
    <form method="GET" action="{{ route('dashboard.gm') }}" class="row g-2 align-items-center">
        <div class="col-sm-4 col-md-3">
            <label class="form-label small text-muted mb-1">Month</label>
            <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach(config('kaldis.ethiopian_months') as $m)
                    <option value="{{ $m }}" {{ $selectedMonth === $m ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-4 col-md-3">
            <label class="form-label small text-muted mb-1">Year</label>
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach(config('kaldis.planning_years') as $y)
                    <option value="{{ $y }}" {{ $selectedYear === $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-4 col-md-3">
            <label class="form-label small text-muted mb-1">Week</label>
            <select name="week" class="form-select form-select-sm" onchange="this.form.submit()">
                @for($w = 1; $w <= 5; $w++)
                    <option value="{{ $w }}" {{ $selectedWeek === $w ? 'selected' : '' }}>Week {{ $w }}</option>
                @endfor
            </select>
        </div>
    </form>
</div>

@if(($totalPendingDelays ?? 0) > 0)
    <div class="alert alert-warning py-2 px-3 small d-flex justify-content-between align-items-center mb-4 border">
        <div>
            <strong>{{ $totalPendingDelays }} delay request{{ $totalPendingDelays > 1 ? 's' : '' }} pending GM decision</strong>
            <span class="text-muted ms-1">(Monthly: {{ $pendingMonthlyDelays }}, Weekly Plans: {{ $pendingWeeklyPlanDelays }}, Reports: {{ $pendingWeeklyReportDelays }})</span>
        </div>
        <span class="badge bg-warning text-dark">Action Required</span>
    </div>
@endif

<!-- Metric Overview Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="card h-100 p-3 border">
            <div class="text-muted small fw-medium text-uppercase mb-1">Active Strategies</div>
            <div class="fs-4 fw-bold text-dark mb-1">{{ $activeStrategiesCount }} <span class="text-muted fs-6 fw-normal">/ {{ $totalStrategies }}</span></div>
            <div class="small text-muted">Activated for {{ $selectedMonth }}</div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card h-100 p-3 border">
            <div class="text-muted small fw-medium text-uppercase mb-1">Monthly Plans</div>
            <div class="fs-4 fw-bold text-dark mb-1">
                {{ $monthlySubmittedCount ?? 0 }} <span class="text-muted fs-6 fw-normal">/ {{ $totalDepartments }} Submitted</span>
            </div>
            <div class="small text-muted">
                <span class="text-success fw-medium">{{ $monthlyOnTimeCount ?? 0 }} On Time</span> &bull;
                <span class="text-warning fw-medium">{{ $monthlyLateCount ?? 0 }} Late</span>
                @if(($monthlyDelayApprovedCount ?? 0) > 0)
                    &bull; <span class="text-primary fw-medium">{{ $monthlyDelayApprovedCount }} Excused</span>
                @endif
                &bull; <span class="text-danger fw-medium">{{ $monthlyNotSubmittedCount ?? 0 }} Missing</span>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card h-100 p-3 border">
            <div class="text-muted small fw-medium text-uppercase mb-1">Weekly Tasks</div>
            <div class="fs-4 fw-bold text-dark mb-1">{{ $totalTasks }} <span class="text-muted fs-6 fw-normal">tasks</span></div>
            <div class="small text-muted">
                <span class="text-success fw-medium">{{ $doneTasks }} Done</span> &bull;
                <span class="text-danger fw-medium">{{ $notDoneTasks }} Not Done</span> &bull;
                <span>{{ $pendingTasks }} Pending</span>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card h-100 p-3 border">
            <div class="text-muted small fw-medium text-uppercase mb-1">Week {{ $selectedWeek }} Submissions</div>
            <div class="fs-4 fw-bold text-dark mb-1">
                {{ $onTimeSubmissions + $lateSubmissions + ($delayApprovedSubmissions ?? 0) }} <span class="text-muted fs-6 fw-normal">/ {{ $totalDepartments }} Submitted</span>
            </div>
            <div class="small text-muted">
                <span class="text-success fw-medium">{{ $onTimeSubmissions }} On Time</span> &bull;
                <span class="text-warning fw-medium">{{ $lateSubmissions }} Late</span>
                @if(($delayApprovedSubmissions ?? 0) > 0)
                    &bull; <span class="text-primary fw-medium">{{ $delayApprovedSubmissions }} Excused</span>
                @endif
                &bull; <span class="text-danger fw-medium">{{ $waitingSubmissions + $missingSubmissions }} Missing</span>
            </div>
        </div>
    </div>
</div>

<!-- Department Performance & Oversight Tabs -->
<div class="card mb-4 border">
    <div class="card-header bg-white border-bottom p-3">
        <ul class="nav nav-tabs card-header-tabs small border-0" id="gmExecutiveTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-medium" id="tab-monthly-plans" data-bs-toggle="tab" data-bs-target="#content-monthly-plans" type="button" role="tab">
                    Monthly Plans ({{ $selectedMonth }})
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-medium" id="tab-weekly-reports" data-bs-toggle="tab" data-bs-target="#content-weekly-reports" type="button" role="tab">
                    Weekly Performance (Week {{ $selectedWeek }})
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-medium" id="tab-weekly-plans" data-bs-toggle="tab" data-bs-target="#content-weekly-plans" type="button" role="tab">
                    Weekly Task Schedules (Week {{ $selectedWeek }})
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-medium" id="tab-all-plans" data-bs-toggle="tab" data-bs-target="#content-all-plans" type="button" role="tab">
                    Master Plans ({{ $allMonthlyPlans->count() }})
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-0">
        <div class="tab-content" id="gmExecutiveTabContent">
            
            <!-- TAB 1: MONTHLY PLANS -->
            <div class="tab-pane fade show active" id="content-monthly-plans" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Department</th>
                                <th>Head</th>
                                <th class="text-center">Submission Status</th>
                                <th class="text-center">Plans Count</th>
                                <th class="text-center">Strategy / Ops</th>
                                <th class="text-center">GM Approval</th>
                                <th class="text-end pe-3">Actions</th>
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
                                    
                                    $isMDelayApproved = $mSub && ($mSub->isDelayApproved() || $mSub->status === 'DELAY_APPROVED');
                                    $isMDelayPending = $mSub && $mSub->isDelayPending();
                                    $mDelayReason = $mSub->delay_reason ?? null;
                                    $mDelayGmNotes = $mSub->delay_gm_notes ?? null;

                                    $mStatus = 'NOT_SUBMITTED';
                                    $mSubmissionTime = null;
                                    if ($isMDelayApproved) {
                                        $mStatus = 'DELAY_APPROVED';
                                        $mSubmissionTime = $mSub->submitted_at ?? null;
                                    } elseif ($mSub && $mSub->submitted_at) {
                                        $mStatus = $mSub->status ?: 'ON_TIME';
                                        $mSubmissionTime = $mSub->submitted_at;
                                    } elseif ($mSub && in_array($mSub->status, ['ON_TIME', 'LATE', 'SUBMITTED', 'APPROVED'])) {
                                        $mStatus = $mSub->status === 'SUBMITTED' ? 'ON_TIME' : $mSub->status;
                                        $mSubmissionTime = $mSub->updated_at ?? null;
                                    } elseif ($deptPlans->count() > 0) {
                                        $deadlineInfo = function_exists('get_monthly_plan_deadline_info')
                                            ? get_monthly_plan_deadline_info($selectedMonth, $selectedYear)
                                            : ['status' => 'ON_TIME'];
                                        $mStatus = $deadlineInfo['status'] ?? 'ON_TIME';
                                        $mSubmissionTime = $deptPlans->first()->created_at ?? null;
                                    }

                                    $mApproval = $mSub->approval_status ?? 'PENDING';
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $dept->department_name }}</div>
                                        <small class="text-muted">{{ $dept->department_code }}</small>
                                    </td>
                                    <td>
                                        <div class="small text-dark">{{ $dept->head->full_name ?? 'N/A' }}</div>
                                    </td>
                                    <td class="text-center">
                                        @if($mStatus === 'DELAY_APPROVED')
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Delay Approved</span>
                                        @elseif($isMDelayPending)
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Delay Pending</span>
                                        @elseif($mStatus === 'ON_TIME')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">On Time</span>
                                            @if($mSubmissionTime)
                                                <small class="d-block text-muted" style="font-size: 0.7rem;">{{ $mSubmissionTime->format('M d, H:i') }}</small>
                                            @endif
                                        @elseif($mStatus === 'LATE')
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Late</span>
                                            @if($mSubmissionTime)
                                                <small class="d-block text-muted" style="font-size: 0.7rem;">{{ $mSubmissionTime->format('M d, H:i') }}</small>
                                            @endif
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Not Submitted</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-bold">{{ $deptPlans->count() }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="small text-muted">{{ $stratCount }} Strat / {{ $opsCount }} Ops</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $mApproval === 'APPROVED' ? 'bg-success-subtle text-success' : ($mApproval === 'REVISION_REQUESTED' ? 'bg-danger-subtle text-danger' : 'bg-light text-muted border') }} small">
                                            {{ $mApproval }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="d-flex justify-content-end align-items-center gap-1">
                                            @if($isMDelayPending || !empty($mDelayReason))
                                                <button type="button" 
                                                        class="btn btn-xs {{ $isMDelayPending ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary' }}"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#reviewDelayModal"
                                                        data-type="MONTHLY"
                                                        data-dept-id="{{ $dept->id }}"
                                                        data-dept-name="{{ $dept->department_name }}"
                                                        data-period="Monthly Plan ({{ $selectedMonth }} {{ $selectedYear }})"
                                                        data-reason="{{ e($mDelayReason ?? '') }}"
                                                        data-status="{{ $mSub->delay_status ?? 'PENDING' }}"
                                                        data-notes="{{ e($mDelayGmNotes ?? '') }}">
                                                    Delay Decision
                                                </button>
                                            @endif
                                            <a href="{{ route('monthly.voucher', ['department_id' => $dept->id, 'year' => $selectedYear, 'month' => $selectedMonth]) }}" 
                                               class="btn btn-xs btn-outline-secondary">
                                                Voucher
                                            </a>
                                            @if($mStatus === 'NOT_SUBMITTED' && !$isMDelayPending)
                                                <form method="POST" action="{{ route('management.remind') }}" class="d-inline" onsubmit="return confirm('Send Telegram reminder to {{ $dept->department_name }} Department Head?');">
                                                    @csrf
                                                    <input type="hidden" name="department_id" value="{{ $dept->id }}">
                                                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                                                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                                    <input type="hidden" name="type" value="MONTHLY">
                                                    <button type="submit" class="btn btn-xs btn-outline-danger">Remind</button>
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

            <!-- TAB 2: WEEKLY PERFORMANCE -->
            <div class="tab-pane fade" id="content-weekly-reports" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Department</th>
                                <th>Head</th>
                                <th class="text-center">Submission Status</th>
                                <th class="text-center">Tasks (Done / Total)</th>
                                <th>Completion Rate</th>
                                <th class="text-center">Approval</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($departmentPerformances as $dp)
                                @php
                                    $dept = $dp['department'];
                                    $wSub = $weeklySubmissions[$dept->id] ?? null;
                                    $wRep = $weeklyReports[$dept->id] ?? null;
                                    $wPlan = $weeklyPlans[$dept->id] ?? null;
                                    
                                    $isPlanSubmitted = $wPlan && ($wPlan->status === 'SUBMITTED' || !empty($wPlan->submitted_at));

                                    $isDelayApproved = ($wSub && $wSub->isDelayApproved()) 
                                        || ($wRep && $wRep->isDelayApproved()) 
                                        || ($wPlan && ($wPlan->isDelayApproved() || $wPlan->submission_status === 'DELAY_APPROVED'))
                                        || ($wSub && $wSub->status === 'DELAY_APPROVED');
                                    $isDelayPending = ($wSub && $wSub->isDelayPending()) 
                                        || ($wRep && $wRep->isDelayPending()) 
                                        || ($wPlan && $wPlan->delay_status === 'PENDING');
                                    $delayReason = $wSub->delay_reason ?? ($wRep->delay_reason ?? ($wPlan->delay_reason ?? null));
                                    $delayGmNotes = $wSub->delay_gm_notes ?? ($wRep->delay_gm_notes ?? ($wPlan->delay_gm_notes ?? null));

                                    $status = 'NOT_SUBMITTED';
                                    $submissionTime = null;
                                    if ($isDelayApproved) {
                                        $status = 'DELAY_APPROVED';
                                        $submissionTime = $wSub->submitted_at ?? ($wPlan->submitted_at ?? ($wRep->submitted_at ?? null));
                                    } elseif ($wSub && $wSub->submitted_at) {
                                        $status = $wSub->status ?: 'ON_TIME';
                                        $submissionTime = $wSub->submitted_at;
                                    } elseif ($wRep && $wRep->submitted_at) {
                                        $status = $wRep->submission_status ?: 'ON_TIME';
                                        $submissionTime = $wRep->submitted_at;
                                    } elseif ($isPlanSubmitted) {
                                        $status = $wPlan->submission_status ?: 'ON_TIME';
                                        $submissionTime = $wPlan->submitted_at ?? $wPlan->updated_at;
                                    } elseif ($dp['total'] > 0) {
                                        $status = 'ON_TIME';
                                    }

                                    $approval = $wRep->approval_status ?? ($wSub->approval_status ?? 'PENDING');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $dept->department_name }}</div>
                                        <small class="text-muted">{{ $dept->department_code }}</small>
                                    </td>
                                    <td>
                                        <div class="small text-dark">{{ $dept->head->full_name ?? 'N/A' }}</div>
                                    </td>
                                    <td class="text-center">
                                        @if($status === 'DELAY_APPROVED')
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Delay Approved</span>
                                        @elseif($isDelayPending)
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Delay Pending</span>
                                        @elseif($status === 'ON_TIME')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">On Time</span>
                                            @if($submissionTime)
                                                <small class="d-block text-muted" style="font-size: 0.7rem;">{{ $submissionTime->format('M d, H:i') }}</small>
                                            @endif
                                        @elseif($status === 'LATE')
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Late</span>
                                            @if($submissionTime)
                                                <small class="d-block text-muted" style="font-size: 0.7rem;">{{ $submissionTime->format('M d, H:i') }}</small>
                                            @endif
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Not Submitted</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($dp['total'] > 0)
                                            <span class="text-success fw-bold">{{ $dp['done'] }}</span> / 
                                            <span class="fw-bold">{{ $dp['total'] }}</span>
                                        @else
                                            <span class="text-muted small">0 tasks</span>
                                        @endif
                                    </td>
                                    <td style="min-width: 140px;">
                                        @if($dp['total'] > 0)
                                            <span class="fw-bold {{ $dp['rate'] >= 80 ? 'text-success' : ($dp['rate'] >= 50 ? 'text-warning' : 'text-danger') }}">{{ $dp['rate'] }}%</span>
                                        @else
                                            <span class="text-muted small">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $approval === 'APPROVED' ? 'bg-success-subtle text-success' : ($approval === 'REVISION_REQUESTED' ? 'bg-danger-subtle text-danger' : 'bg-light text-muted border') }} small">
                                            {{ $approval }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="d-flex justify-content-end align-items-center gap-1">
                                            @if($isDelayPending || !empty($delayReason))
                                                <button type="button" 
                                                        class="btn btn-xs {{ $isDelayPending ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary' }}"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#reviewDelayModal"
                                                        data-type="WEEKLY_PERFORMANCE"
                                                        data-dept-id="{{ $dept->id }}"
                                                        data-dept-name="{{ $dept->department_name }}"
                                                        data-period="Week {{ $selectedWeek }} Performance ({{ $selectedMonth }} {{ $selectedYear }})"
                                                        data-reason="{{ e($delayReason ?? '') }}"
                                                        data-status="{{ $wSub->delay_status ?? ($wRep->delay_status ?? 'PENDING') }}"
                                                        data-notes="{{ e($delayGmNotes ?? '') }}">
                                                    Delay Decision
                                                </button>
                                            @endif
                                            <a href="{{ route('weekly.voucher', ['department_id' => $dept->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'week' => $selectedWeek]) }}" 
                                               class="btn btn-xs btn-outline-secondary">
                                                Voucher
                                            </a>
                                            @if($status === 'NOT_SUBMITTED' && !$isDelayPending)
                                                <form method="POST" action="{{ route('management.remind') }}" class="d-inline" onsubmit="return confirm('Send Telegram reminder to {{ $dept->department_name }} Department Head?');">
                                                    @csrf
                                                    <input type="hidden" name="department_id" value="{{ $dept->id }}">
                                                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                                                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                                    <input type="hidden" name="week" value="{{ $selectedWeek }}">
                                                    <input type="hidden" name="type" value="WEEKLY">
                                                    <button type="submit" class="btn btn-xs btn-outline-danger">Remind</button>
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

            <!-- TAB 3: WEEKLY TASK SCHEDULES -->
            <div class="tab-pane fade" id="content-weekly-plans" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Department</th>
                                <th>Head</th>
                                <th class="text-center">Schedule Status</th>
                                <th class="text-center">Tasks Scheduled</th>
                                <th class="text-center">Strategy / Ops</th>
                                <th class="text-center">Plan Status</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($departmentPerformances as $dp)
                                @php
                                    $dept = $dp['department'];
                                    $wPlan = $weeklyPlans[$dept->id] ?? null;
                                    $planTasks = $wPlan ? $wPlan->tasks : \App\Models\WeeklyTask::where('department_id', $dept->id)->where('year', $selectedYear)->where('month', $selectedMonth)->where('week_number', $selectedWeek)->get();
                                    $wpStratCount = $planTasks->where('plan_type', 'STRATEGY')->count();
                                    $wpOpsCount = $planTasks->where('plan_type', 'OPERATIONAL')->count();

                                    $isWPDelayApproved = $wPlan && ($wPlan->isDelayApproved() || $wPlan->submission_status === 'DELAY_APPROVED');
                                    $isWPDelayPending = $wPlan && $wPlan->isDelayPending();
                                    $wpDelayReason = $wPlan->delay_reason ?? null;
                                    $wpDelayGmNotes = $wPlan->delay_gm_notes ?? null;

                                    $wpStatus = 'NOT_SUBMITTED';
                                    if ($isWPDelayApproved) {
                                        $wpStatus = 'DELAY_APPROVED';
                                    } elseif ($wPlan && in_array($wPlan->submission_status, ['ON_TIME', 'LATE'])) {
                                        $wpStatus = $wPlan->submission_status;
                                    } elseif ($wPlan && $wPlan->status === 'SUBMITTED') {
                                        $wpStatus = 'ON_TIME';
                                    } elseif ($planTasks->count() > 0) {
                                        $wpStatus = 'DRAFT';
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $dept->department_name }}</div>
                                        <small class="text-muted">{{ $dept->department_code }}</small>
                                    </td>
                                    <td>
                                        <div class="small text-dark">{{ $dept->head->full_name ?? 'N/A' }}</div>
                                    </td>
                                    <td class="text-center">
                                        @if($wpStatus === 'DELAY_APPROVED')
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Delay Approved</span>
                                        @elseif($isWPDelayPending)
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Delay Pending</span>
                                        @elseif($wpStatus === 'ON_TIME')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">On Time</span>
                                            @if($wPlan && $wPlan->submitted_at)
                                                <small class="d-block text-muted" style="font-size: 0.7rem;">{{ $wPlan->submitted_at->format('M d, H:i') }}</small>
                                            @endif
                                        @elseif($wpStatus === 'LATE')
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Late</span>
                                            @if($wPlan && $wPlan->submitted_at)
                                                <small class="d-block text-muted" style="font-size: 0.7rem;">{{ $wPlan->submitted_at->format('M d, H:i') }}</small>
                                            @endif
                                        @elseif($wpStatus === 'DRAFT')
                                            <span class="badge bg-secondary-subtle text-secondary border">Draft</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Not Submitted</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-bold">{{ $planTasks->count() }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="small text-muted">{{ $wpStratCount }} Strat / {{ $wpOpsCount }} Ops</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ ($wPlan && $wPlan->status === 'APPROVED') ? 'bg-success-subtle text-success' : (($wPlan && $wPlan->status === 'SUBMITTED') ? 'bg-primary-subtle text-primary' : 'bg-light text-muted border') }} small">
                                            {{ $wPlan->status ?? 'DRAFT' }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="d-flex justify-content-end align-items-center gap-1">
                                            @if($isWPDelayPending || !empty($wpDelayReason))
                                                <button type="button" 
                                                        class="btn btn-xs {{ $isWPDelayPending ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary' }}"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#reviewDelayModal"
                                                        data-type="WEEKLY_PLAN"
                                                        data-dept-id="{{ $dept->id }}"
                                                        data-dept-name="{{ $dept->department_name }}"
                                                        data-period="Week {{ $selectedWeek }} Plan Schedule ({{ $selectedMonth }} {{ $selectedYear }})"
                                                        data-reason="{{ e($wpDelayReason ?? '') }}"
                                                        data-status="{{ $wPlan->delay_status ?? 'PENDING' }}"
                                                        data-notes="{{ e($wpDelayGmNotes ?? '') }}">
                                                    Delay Decision
                                                </button>
                                            @endif
                                            <a href="{{ route('weekly.voucher', ['department_id' => $dept->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'week' => $selectedWeek, 'type' => 'plan']) }}" 
                                               class="btn btn-xs btn-outline-secondary">
                                                Voucher
                                            </a>
                                            @if($wpStatus === 'NOT_SUBMITTED' && !$isWPDelayPending)
                                                <form method="POST" action="{{ route('management.remind') }}" class="d-inline" onsubmit="return confirm('Send Telegram reminder to {{ $dept->department_name }} Department Head?');">
                                                    @csrf
                                                    <input type="hidden" name="department_id" value="{{ $dept->id }}">
                                                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                                                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                                    <input type="hidden" name="week" value="{{ $selectedWeek }}">
                                                    <input type="hidden" name="type" value="WEEKLY">
                                                    <button type="submit" class="btn btn-xs btn-outline-danger">Remind</button>
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

            <!-- TAB 4: MASTER PLANS EXPLORER -->
            <div class="tab-pane fade" id="content-all-plans" role="tabpanel">
                @php
                    $gmPendingPlansCount = $allMonthlyPlans->where('approval_status', '!=', 'APPROVED')->count();
                @endphp
                <div class="p-3 bg-light border-bottom">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-5">
                            <input type="text" id="gmAllPlansSearch" class="form-control form-control-sm" placeholder="Filter plans live by department, title, target, or responsible person...">
                        </div>
                        <div class="col-md-7 d-flex align-items-center justify-content-md-end gap-2 flex-wrap">
                            <span class="text-muted small">Total: <strong>{{ $allMonthlyPlans->count() }}</strong></span>
                            @if($gmPendingPlansCount > 0)
                                <button type="button" id="btnGmApproveSelected" class="btn btn-xs btn-outline-success fw-semibold d-none" onclick="submitGmSelectedPlans()">
                                    Approve Selected (<span id="gmSelectedPlansCount">0</span>)
                                </button>
                                <form method="POST" action="{{ route('monthly.plans.bulk_approve') }}" class="d-inline" onsubmit="return confirm('Approve ALL {{ $gmPendingPlansCount }} pending company-wide monthly plans for {{ $selectedMonth }} {{ $selectedYear }}?');">
                                    @csrf
                                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                    @foreach($allMonthlyPlans->where('approval_status', '!=', 'APPROVED') as $p)
                                        <input type="hidden" name="plan_ids[]" value="{{ $p->id }}">
                                    @endforeach
                                    <button type="submit" class="btn btn-xs btn-success fw-semibold">
                                        Approve All Pending ({{ $gmPendingPlansCount }})
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0" id="gmAllPlansTable">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 36px;" class="text-center">
                                    <input type="checkbox" id="gmSelectAllPlans" class="form-check-input" title="Select all pending">
                                </th>
                                <th>Dept</th>
                                <th>Type</th>
                                <th>Goal Link</th>
                                <th>Plan Title &amp; Target</th>
                                <th>Definition of Done (DOD)</th>
                                <th class="text-center" style="width: 140px;">Approval Status</th>
                                <th class="text-center" style="width: 100px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($allMonthlyPlans as $plan)
                                <tr>
                                    <td class="text-center">
                                        @if($plan->approval_status !== 'APPROVED')
                                            <input type="checkbox" class="form-check-input gm-plan-chk" value="{{ $plan->id }}" title="Select plan #{{ $plan->id }}">
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border font-monospace">{{ $plan->department->department_code ?? 'DEPT' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $plan->plan_type === 'STRATEGY' ? 'bg-primary-subtle text-primary' : 'bg-light text-secondary border' }} small">
                                            {{ $plan->plan_type === 'STRATEGY' ? 'Strategy' : 'Ops' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($plan->annualGoal)
                                            <span class="badge bg-secondary font-monospace" style="font-size: 0.72rem;">{{ $plan->annualGoal->goal_code }}</span>
                                        @else
                                            <span class="text-muted small">&mdash;</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark small">{{ $plan->title }}</div>
                                        <small class="text-muted">Target: {{ $plan->monthly_target }}</small>
                                    </td>
                                    <td class="text-muted small" style="max-width: 250px;">{{ $plan->definition_of_done }}</td>
                                    <td class="text-center">
                                        @if($plan->approval_status === 'APPROVED')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Approved</span>
                                        @elseif($plan->approval_status === 'DECLINED')
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="{{ $plan->decline_reason }}">Declined</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border">Pending</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center gap-1">
                                            @if($plan->approval_status !== 'APPROVED')
                                                <form method="POST" action="{{ route('monthly.plans.approve', $plan->id) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-xs btn-outline-success py-0.5 px-1.5" title="Approve Plan">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            @if($plan->approval_status !== 'DECLINED')
                                                <button type="button" class="btn btn-xs btn-outline-danger py-0.5 px-1.5" onclick="openGmDeclinePlanModal({{ $plan->id }}, '{{ addslashes($plan->title) }}')" title="Decline Plan">
                                                    <i class="fas fa-xmark"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No monthly plans found for {{ $selectedMonth }} {{ $selectedYear }}.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Hidden Form for GM Bulk Approving Selected Plans -->
                <form id="gmBulkApprovePlansForm" method="POST" action="{{ route('monthly.plans.bulk_approve') }}" class="d-none">
                    @csrf
                    <div id="gmBulkApprovePlansInputs"></div>
                </form>
            </div>

        </div>
    </div>
</div>

<!-- Modal: Decline Monthly Plan -->
<div class="modal fade" id="gmDeclinePlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="gmDeclinePlanForm" action="">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark fs-6">Decline Monthly Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-secondary small mb-3">Please specify the reason for declining this monthly plan:</p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Reason <span class="text-danger">*</span></label>
                        <textarea name="decline_reason" id="gmDeclineReasonInput" class="form-control" rows="3" placeholder="Provide feedback or required adjustments..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger fw-semibold">Decline Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Review Department Delay Reason -->
<div class="modal fade" id="reviewDelayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('management.delay_approval') }}">
                @csrf
                <input type="hidden" name="type" id="modalDelayType" value="">
                <input type="hidden" name="department_id" id="modalDelayDeptId" value="">
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                <input type="hidden" name="month" value="{{ $selectedMonth }}">
                <input type="hidden" name="week_number" value="{{ $selectedWeek }}">

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark fs-6">
                        Review Department Delay Request
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 small mb-3 border-0">
                        Approving this delay excuses the department. The submission will be officially recorded as <strong>DELAY APPROVED</strong> and will <strong>NOT</strong> appear as "Not Submitted".
                    </div>

                    <div class="bg-light p-3 rounded-3 mb-3 border small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Department:</span>
                            <span class="fw-bold text-dark" id="modalDelayDeptName">—</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Period:</span>
                            <span class="fw-semibold text-dark" id="modalDelayPeriod">—</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Status:</span>
                            <span class="badge bg-secondary" id="modalDelayStatus">PENDING</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Department Justification</label>
                        <div class="p-3 bg-light text-dark border rounded-3 small" id="modalDelayReasonBox">
                            No delay justification text provided.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">GM Remarks (Optional)</label>
                        <textarea name="notes" id="modalDelayNotes" class="form-control" rows="3" placeholder="Add GM notes or instructions..."></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <div class="d-flex gap-2">
                        <button type="submit" name="action" value="REJECT" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this delay justification?');">
                            Reject
                        </button>
                        <button type="submit" name="action" value="APPROVE" class="btn btn-sm btn-success fw-bold" onclick="return confirm('Approve this delay justification?');">
                            Approve (Excuse)
                        </button>
                    </div>
                </div>
            </form>
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

    const reviewDelayModalEl = document.getElementById('reviewDelayModal');
    if (reviewDelayModalEl) {
        reviewDelayModalEl.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            const type = button.getAttribute('data-type') || 'MONTHLY';
            const deptId = button.getAttribute('data-dept-id') || '';
            const deptName = button.getAttribute('data-dept-name') || '';
            const period = button.getAttribute('data-period') || '';
            const reason = button.getAttribute('data-reason') || 'No justification provided.';
            const status = button.getAttribute('data-status') || 'PENDING';
            const notes = button.getAttribute('data-notes') || '';

            document.getElementById('modalDelayType').value = type;
            document.getElementById('modalDelayDeptId').value = deptId;
            document.getElementById('modalDelayDeptName').textContent = deptName;
            document.getElementById('modalDelayPeriod').textContent = period;
            document.getElementById('modalDelayReasonBox').textContent = reason;
            document.getElementById('modalDelayNotes').value = notes;

            const statusBadge = document.getElementById('modalDelayStatus');
            if (statusBadge) {
                statusBadge.textContent = status;
                statusBadge.className = status === 'APPROVED' ? 'badge bg-success' : (status === 'REJECTED' ? 'badge bg-danger' : 'badge bg-warning text-dark');
            }
        });
    }
});

function openGmDeclinePlanModal(planId, title) {
    const form = document.getElementById('gmDeclinePlanForm');
    form.action = "{{ url('/monthly/plans') }}/" + planId + "/decline";
    document.getElementById('gmDeclineReasonInput').value = '';
    const modal = new bootstrap.Modal(document.getElementById('gmDeclinePlanModal'));
    modal.show();
}

function initGmPlanBulkSelection() {
    const selectAll = document.getElementById('gmSelectAllPlans');
    const checkboxes = document.querySelectorAll('.gm-plan-chk');
    const btnSelected = document.getElementById('btnGmApproveSelected');
    const countSpan = document.getElementById('gmSelectedPlansCount');

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

function submitGmSelectedPlans() {
    const checked = Array.from(document.querySelectorAll('.gm-plan-chk:checked'));
    if (!checked.length) {
        alert('Please select at least one pending monthly plan to approve.');
        return;
    }
    if (!confirm('Approve ' + checked.length + ' selected monthly plan(s)?')) return;

    const form = document.getElementById('gmBulkApprovePlansForm');
    const container = document.getElementById('gmBulkApprovePlansInputs');
    if (!form || !container) return;

    container.innerHTML = '';
    checked.forEach(c => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'plan_ids[]';
        input.value = c.value;
        container.appendChild(input);
    });
    form.submit();
}

document.addEventListener('DOMContentLoaded', initGmPlanBulkSelection);
</script>
@endsection

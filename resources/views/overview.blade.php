@extends('layouts.app')

@section('title', 'Corporate Planning Launchpad')

@section('content')
<style>
    /* ══════════════════════════════════════════════════════════
       FANCY EXECUTIVE LAUNCHPAD STYLING
       ══════════════════════════════════════════════════════════ */
    .overview-hero-card {
        background: linear-gradient(135deg, #0F141C 0%, #1A2230 60%, #241D14 100%);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 16px 36px -6px rgba(0, 0, 0, 0.25);
    }
    .overview-hero-card::before {
        content: '';
        position: absolute;
        top: -80px;
        right: -80px;
        width: 260px;
        height: 260px;
        background: radial-gradient(circle, rgba(217, 119, 6, 0.22) 0%, transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }
    .overview-hero-card::after {
        content: '';
        position: absolute;
        bottom: -90px;
        left: 20%;
        width: 320px;
        height: 180px;
        background: radial-gradient(ellipse, rgba(245, 158, 11, 0.12) 0%, transparent 65%);
        border-radius: 50%;
        pointer-events: none;
    }
    .overview-stat-card {
        background: var(--kaldis-card-bg);
        border: 1px solid rgba(0, 0, 0, 0.06);
        border-radius: 16px;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
        overflow: hidden;
    }
    .overview-stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 28px -4px rgba(15, 23, 42, 0.08);
        border-color: rgba(217, 119, 6, 0.35);
    }
    .overview-stat-icon-wrap {
        width: 50px;
        height: 50px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
        transition: transform 0.2s ease;
    }
    .overview-stat-card:hover .overview-stat-icon-wrap {
        transform: scale(1.08);
    }
    .lifecycle-step {
        position: relative;
        flex: 1;
        padding: 14px 16px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        transition: all 0.2s ease;
    }
    .lifecycle-step.active {
        background: linear-gradient(135deg, rgba(217, 119, 6, 0.25) 0%, rgba(180, 83, 9, 0.12) 100%);
        border-color: rgba(245, 158, 11, 0.45);
        box-shadow: 0 4px 16px rgba(217, 119, 6, 0.18);
    }
    .deadline-radar-item {
        padding: 12px 14px;
        border-radius: 12px;
        background: var(--kaldis-bg);
        border: 1px solid var(--kaldis-border);
        transition: border-color 0.2s ease;
    }
    .deadline-radar-item:hover {
        border-color: rgba(217, 119, 6, 0.4);
    }
    [data-bs-theme="dark"] .overview-stat-card {
        border-color: rgba(255, 255, 255, 0.08);
    }
</style>

<div class="container-fluid px-0" style="max-width: 1400px; margin: 0 auto;">

    <!-- ══════════════════════════════════════════════════════════════
         1. SLEEK EXECUTIVE HERO BANNER
    ══════════════════════════════════════════════════════════════ -->
    <div class="overview-hero-card text-white p-4 p-md-4.5 mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 position-relative" style="z-index: 2;">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <span class="badge bg-warning bg-opacity-20 text-warning px-2.5 py-1 rounded-pill small fw-semibold">
                        <i class="fas fa-crown me-1"></i> Kaldis Executive Portal
                    </span>
                    <span class="badge bg-white bg-opacity-10 text-light px-2.5 py-1 rounded-pill small font-monospace">
                        {{ $selectedMonth }} {{ $selectedYear }} &bull; Week {{ $selectedWeek }}
                    </span>
                </div>
                <h3 class="fw-bold mb-1 text-white" style="letter-spacing: -0.02em;">
                    {{ get_greeting() }}, {{ explode(' ', $user->full_name ?? $user->username)[0] }}!
                </h3>
                <p class="text-white-50 small mb-0" style="max-width: 620px; line-height: 1.55;">
                    Track company strategies, align department milestones, and execute weekly tasks under unified organizational governance.
                </p>
            </div>

            <!-- Action & Deadline Radar Pills -->
            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-2.5 w-100 w-lg-auto">
                @if($monthlyDaysLeft > 0)
                    <div class="d-flex align-items-center gap-2 px-3 py-2 rounded-pill" style="background: rgba(245, 158, 11, 0.16); border: 1px solid rgba(245, 158, 11, 0.35);">
                        <i class="far fa-clock text-warning fs-5"></i>
                        <div>
                            <div class="text-warning fw-bold small lh-1">Monthly Deadline: Day {{ $monthlyDeadlineDay }}</div>
                            <div class="text-white-50" style="font-size: 0.72rem;">{{ $monthlyDaysLeft }} {{ Str::plural('day', $monthlyDaysLeft) }} remaining for submission</div>
                        </div>
                    </div>
                @else
                    <div class="d-flex align-items-center gap-2 px-3 py-2 rounded-pill" style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4);">
                        <i class="fas fa-triangle-exclamation text-danger fs-5"></i>
                        <div>
                            <div class="text-danger fw-bold small lh-1">Deadline Today (Day {{ $monthlyDeadlineDay }})</div>
                            <div class="text-white-50" style="font-size: 0.72rem;">Submit monthly plan package now</div>
                        </div>
                    </div>
                @endif

                @if($user->isDeptHead())
                    <a href="{{ route('weekly.plans') }}" class="btn btn-warning text-dark fw-bold px-3 py-2 rounded-pill shadow-xs d-inline-flex align-items-center gap-1.5 text-nowrap">
                        <i class="fas fa-tasks"></i> My Week Tasks
                    </a>
                @elseif($user->isGM() || $user->isSuperAdmin())
                    <a href="{{ route('dashboard.gm') }}" class="btn btn-warning text-dark fw-bold px-3 py-2 rounded-pill shadow-xs d-inline-flex align-items-center gap-1.5 text-nowrap">
                        <i class="fas fa-chart-line"></i> GM Scorecard
                    </a>
                @endif
            </div>
        </div>

        <!-- 4-Stage Planning Lifecycle Pipeline -->
        <div class="row g-2 mt-3 pt-3 border-top border-white border-opacity-10">
            <div class="col-6 col-md-3">
                <a href="{{ route('strategy.annual') }}" class="text-decoration-none d-block">
                    <div class="lifecycle-step text-white">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="badge bg-warning bg-opacity-20 text-warning px-1.5 py-0.5 rounded font-monospace" style="font-size: 0.65rem;">STAGE 1</span>
                            <i class="fas fa-bullseye text-warning opacity-75"></i>
                        </div>
                        <div class="fw-bold small mb-0 text-white">Annual Strategy</div>
                        <div class="text-white-50" style="font-size: 0.72rem;">58 Strategic Goals</div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="{{ route('monthly.plans') }}" class="text-decoration-none d-block">
                    <div class="lifecycle-step active text-white">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="badge bg-warning text-dark px-1.5 py-0.5 rounded font-monospace fw-bold" style="font-size: 0.65rem;">ACTIVE STAGE 2</span>
                            <i class="fas fa-calendar-alt text-warning"></i>
                        </div>
                        <div class="fw-bold small mb-0 text-white">Monthly Planning</div>
                        <div class="text-white-50" style="font-size: 0.72rem;">Day 26 Submissions</div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="{{ route('weekly.plans') }}" class="text-decoration-none d-block">
                    <div class="lifecycle-step text-white">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="badge bg-success bg-opacity-20 text-success px-1.5 py-0.5 rounded font-monospace" style="font-size: 0.65rem;">STAGE 3</span>
                            <i class="fas fa-circle-check text-success opacity-75"></i>
                        </div>
                        <div class="fw-bold small mb-0 text-white">Weekly Execution</div>
                        <div class="text-white-50" style="font-size: 0.72rem;">Binary Done / Not Done</div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="{{ route('performance.quarterly') }}" class="text-decoration-none d-block">
                    <div class="lifecycle-step text-white">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="badge bg-info bg-opacity-20 text-info px-1.5 py-0.5 rounded font-monospace" style="font-size: 0.65rem;">STAGE 4</span>
                            <i class="fas fa-award text-info opacity-75"></i>
                        </div>
                        <div class="fw-bold small mb-0 text-white">Quarterly Review</div>
                        <div class="text-white-50" style="font-size: 0.72rem;">Executive Governance</div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         2. CORE EXECUTIVE KPI METRICS ROW
    ══════════════════════════════════════════════════════════════ -->
    <div class="row g-3 mb-4">
        <!-- Metric 1: Annual Goals -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm bg-white overview-stat-card p-3.5 mb-0 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.05em;">Strategic Goals</span>
                    <a href="{{ route('strategy.annual') }}" class="text-warning small text-decoration-none" title="View Strategic Goals">
                        <i class="fas fa-arrow-up-right-from-square"></i>
                    </a>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="overview-stat-icon-wrap bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark fs-4 mb-0">{{ $totalGoals }}</div>
                        <div class="text-muted small" style="font-size: 0.75rem;">Official Priorities for {{ $selectedYear }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric 2: Operating Departments -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm bg-white overview-stat-card p-3.5 mb-0 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.05em;">Operating Units</span>
                    <a href="{{ route('admin.departments') }}" class="text-primary small text-decoration-none" title="Manage Departments">
                        <i class="fas fa-arrow-up-right-from-square"></i>
                    </a>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="overview-stat-icon-wrap bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-sitemap"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark fs-4 mb-0">{{ $totalDepartments }}</div>
                        <div class="text-muted small" style="font-size: 0.75rem;">16 Corporate Departments</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric 3: Monthly Plans & Packages -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm bg-white overview-stat-card p-3.5 mb-0 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.05em;">Monthly Plans</span>
                    <a href="{{ route('monthly.plans') }}" class="text-success small text-decoration-none" title="View Monthly Plans">
                        <i class="fas fa-arrow-up-right-from-square"></i>
                    </a>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="overview-stat-icon-wrap bg-success bg-opacity-10 text-success">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark fs-4 mb-0">{{ $allMonthlyPlansCount }}</div>
                        <div class="text-muted small" style="font-size: 0.75rem;">{{ $submittedMonthlyPackagesCount }} submitted packages</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric 4: Weekly Execution Rate -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm bg-white overview-stat-card p-3.5 mb-0 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.05em;">Weekly Performance</span>
                    <a href="{{ route('weekly.performance') }}" class="text-info small text-decoration-none" title="View Weekly Performance">
                        <i class="fas fa-arrow-up-right-from-square"></i>
                    </a>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="overview-stat-icon-wrap bg-info bg-opacity-10 text-info">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div>
                        @if($userDeptId)
                            <div class="fw-bold text-dark fs-4 mb-0">{{ $completionPct }}%</div>
                            <div class="text-muted small" style="font-size: 0.75rem;">{{ $deptDoneCount }}/{{ $deptTasksCount }} tasks done (Week {{ $selectedWeek }})</div>
                        @else
                            <div class="fw-bold text-dark fs-4 mb-0">{{ $companyCompletionPct }}%</div>
                            <div class="text-muted small" style="font-size: 0.75rem;">{{ $allDoneTasksCount }}/{{ $allWeeklyTasksCount }} company tasks done</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         3. BALANCED TWO-COLUMN STRATEGIC HUB
    ══════════════════════════════════════════════════════════════ -->
    <div class="row g-4 mb-4">
        <!-- Left Column: Department Performance & Direct Vouchers (Col 7) -->
        <div class="col-lg-7">
            <!-- Execution Progress Card -->
            <div class="card border border-light-subtle shadow-sm bg-white rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-3 border-bottom border-light-subtle d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-3 bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="fas fa-list-check"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">
                                @if($userDept)
                                    {{ $userDept->department_name }} ({{ $userDept->department_code }})
                                @else
                                    Corporate Operational Status
                                @endif
                            </h6>
                            <span class="text-muted" style="font-size: 0.72rem;">Weekly Execution &amp; Strategy Activation</span>
                        </div>
                    </div>
                    <span class="badge bg-light text-secondary border font-monospace small">Week {{ $selectedWeek }}</span>
                </div>
                <div class="card-body p-4">
                    <!-- Progress Bar Component -->
                    @php
                        $activePct = $userDeptId ? $completionPct : $companyCompletionPct;
                        $activeTasks = $userDeptId ? $deptTasksCount : $allWeeklyTasksCount;
                        $activeDone = $userDeptId ? $deptDoneCount : $allDoneTasksCount;
                        $activeNotDone = max(0, $activeTasks - $activeDone);
                    @endphp
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="small fw-semibold text-dark">Task Execution Completion</span>
                        <span class="fw-bold text-dark font-monospace">{{ $activePct }}%</span>
                    </div>
                    <div class="progress mb-3" style="height: 10px; border-radius: 9999px; background-color: #E2E8F0;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $activePct }}%;" aria-valuenow="{{ $activePct }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>

                    <div class="row g-2 text-center mb-4">
                        <div class="col-4">
                            <div class="p-2.5 rounded-3 bg-light border border-light-subtle">
                                <div class="text-muted small fw-semibold" style="font-size: 0.72rem;">Planned Tasks</div>
                                <div class="fw-bold text-dark fs-5">{{ $activeTasks }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2.5 rounded-3 bg-success bg-opacity-10 border border-success-subtle">
                                <div class="text-success small fw-semibold" style="font-size: 0.72rem;">Done (Verified)</div>
                                <div class="fw-bold text-success fs-5">{{ $activeDone }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2.5 rounded-3 bg-danger bg-opacity-10 border border-danger-subtle">
                                <div class="text-danger small fw-semibold" style="font-size: 0.72rem;">Not Done / Pending</div>
                                <div class="fw-bold text-danger fs-5">{{ $activeNotDone }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Fast Operational Vouchers & Actions -->
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('weekly.plans') }}" class="btn btn-outline-dark btn-sm rounded-pill px-3 fw-semibold shadow-2xs">
                            <i class="fas fa-calendar-plus text-warning me-1"></i> Week Plans
                        </a>
                        <a href="{{ route('weekly.performance') }}" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-semibold shadow-2xs">
                            <i class="fas fa-check-double me-1"></i> Submit Performance
                        </a>
                        <a href="{{ route('monthly.plans') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold shadow-2xs">
                            <i class="fas fa-file-invoice text-primary me-1"></i> Monthly Voucher
                        </a>
                        @if($user->isGM() || $user->isSuperAdmin())
                            <a href="{{ route('admin.departments') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold shadow-2xs">
                                <i class="fas fa-sitemap me-1"></i> Departments Directory
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Department Overview Grid -->
            <div class="card border border-light-subtle shadow-sm bg-white rounded-4 overflow-hidden mb-0">
                <div class="card-header bg-white py-3 border-bottom border-light-subtle d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-building text-secondary me-1.5"></i> Corporate Planning Units
                    </h6>
                    <a href="{{ route('admin.departments') }}" class="small text-muted text-decoration-none fw-semibold">
                        View All (16) <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2">
                        @foreach($departments->take(8) as $dept)
                            <div class="col-sm-6">
                                <div class="p-2.5 rounded-3 border border-light-subtle d-flex align-items-center justify-content-between" style="background: var(--kaldis-bg);">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-dark font-monospace" style="font-size: 0.72rem;">{{ $dept->department_code }}</span>
                                        <div class="text-truncate fw-semibold text-dark small" style="max-width: 140px;">
                                            {{ $dept->department_name }}
                                        </div>
                                    </div>
                                    @if($dept->head)
                                        <div class="rounded-circle bg-warning bg-opacity-20 text-dark d-flex align-items-center justify-content-center fw-bold" style="width: 24px; height: 24px; font-size: 0.7rem;" title="{{ $dept->head->full_name }}">
                                            {{ strtoupper(substr($dept->head->full_name, 0, 1)) }}
                                        </div>
                                    @else
                                        <span class="badge bg-warning bg-opacity-10 text-warning" style="font-size: 0.65rem;">Vacant</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Deadlines Radar & Activity Stream (Col 5) -->
        <div class="col-lg-5">
            <!-- Deadlines & Submission Governance Radar -->
            <div class="card border border-light-subtle shadow-sm bg-white rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-3 border-bottom border-light-subtle d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-3 bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="far fa-bell"></i>
                        </div>
                        <h6 class="fw-bold mb-0 text-dark">Submission Governance Radar</h6>
                    </div>
                    <span class="badge bg-danger-subtle text-danger font-monospace small">Mandatory</span>
                </div>
                <div class="card-body p-3.5">
                    <div class="d-flex flex-column gap-2.5">
                        <!-- Deadline 1: Monthly Plan Package -->
                        <div class="deadline-radar-item">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-bold small text-dark d-flex align-items-center gap-1.5">
                                    <i class="fas fa-file-invoice text-warning"></i> Monthly Plan Package
                                </span>
                                <span class="badge bg-warning text-dark font-monospace" style="font-size: 0.72rem;">Day {{ $monthlyDeadlineDay }} (17:00)</span>
                            </div>
                            <p class="text-muted small mb-0" style="font-size: 0.78rem;">
                                Monthly milestone activation and department budget package submission to General Manager.
                            </p>
                        </div>

                        <!-- Deadline 2: Weekly Performance & Next Week Plan -->
                        <div class="deadline-radar-item">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-bold small text-dark d-flex align-items-center gap-1.5">
                                    <i class="fas fa-tasks text-success"></i> Weekly Tasks &amp; Report
                                </span>
                                <span class="badge bg-success-subtle text-success font-monospace" style="font-size: 0.72rem;">Every {{ $weeklyDeadlineDay }} ({{ $weeklyDeadlineTime }})</span>
                            </div>
                            <p class="text-muted small mb-0" style="font-size: 0.78rem;">
                                Concurrent submission: Previous week binary performance results + upcoming week task plan.
                            </p>
                        </div>

                        <!-- Telegram Live Reminders Notice -->
                        <div class="p-2.5 rounded-3 bg-info bg-opacity-10 border border-info-subtle d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fab fa-telegram text-info fs-5"></i>
                                <div>
                                    <div class="fw-semibold text-dark small">Telegram Automated Reminders</div>
                                    <div class="text-muted" style="font-size: 0.72rem;">Instant notifications for heads &amp; GM</div>
                                </div>
                            </div>
                            <a href="{{ route('telegram.status') }}" class="btn btn-sm btn-outline-info rounded-pill px-2.5 py-0.5" style="font-size: 0.72rem;">
                                Status
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent System Activity Audit Stream -->
            @if(!empty($recentActivities) && $recentActivities->isNotEmpty())
                <div class="card border border-light-subtle shadow-sm bg-white rounded-4 overflow-hidden mb-0">
                    <div class="card-header bg-white py-3 border-bottom border-light-subtle d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-3 bg-secondary bg-opacity-10 text-dark d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fas fa-clock-rotate-left text-warning"></i>
                            </div>
                            <h6 class="fw-bold mb-0 text-dark">Live Operational Stream</h6>
                        </div>
                        @if(auth()->user()->isSuperAdmin())
                            <a href="{{ route('admin.audit') }}" class="small text-muted text-decoration-none fw-semibold">
                                View Audit <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        @endif
                    </div>
                    <div class="card-body p-3">
                        <div class="d-flex flex-column gap-2">
                            @foreach($recentActivities->take(5) as $activity)
                                <div class="p-2 rounded-3 border border-light-subtle d-flex align-items-center justify-content-between" style="background: var(--kaldis-bg);">
                                    <div class="d-flex align-items-center gap-2.5 min-w-0">
                                        <div class="rounded-circle bg-warning bg-opacity-20 text-dark d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 28px; height: 28px; font-size: 0.72rem;">
                                            {{ strtoupper(substr($activity->user->full_name ?? ($activity->user->username ?? 'S'), 0, 1)) }}
                                        </div>
                                        <div class="text-truncate">
                                            <div class="fw-semibold text-dark small text-truncate">{{ $activity->user->full_name ?? ($activity->user->username ?? 'System Operator') }}</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">{{ json_decode($activity->new_values, true)['description'] ?? $activity->action }}</div>
                                        </div>
                                    </div>
                                    <span class="text-muted font-monospace flex-shrink-0 ms-2" style="font-size: 0.68rem;">
                                        {{ $activity->created_at ? $activity->created_at->diffForHumans(null, true) : 'recent' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection

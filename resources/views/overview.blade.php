@extends('layouts.app')

@section('title', 'Corporate Overview')

@section('content')
<div class="container-fluid px-0" style="max-width: 1400px; margin: 0 auto;">

    <!-- 1. Sleek Welcome Header Bar -->
    <div class="card border border-light-subtle shadow-sm bg-white rounded-4 mb-3 overflow-hidden">
        <div class="card-body p-3.5 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <img src="{{ asset('assets/images/logo.png') }}" 
                     alt="Kaldis Coffee" 
                     class="rounded-circle shadow-2xs flex-shrink-0" 
                     style="width: 46px; height: 46px; object-fit: cover;"
                     onerror="this.src='{{ asset('favicon.ico') }}'">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-0.5">
                        <h4 class="fw-bold text-dark mb-0 fs-5">
                            {{ get_greeting() }}, {{ explode(' ', $user->full_name ?? $user->username)[0] }}!
                        </h4>
                        <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-2.5 py-0.5 small fw-semibold">
                            <i class="fas fa-shield-halved me-1"></i> {{ $user->role->display_name ?? 'Corporate Staff' }}
                        </span>
                        @if($user->department)
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-0.5 small font-monospace">
                                <i class="fas fa-building me-1"></i> {{ $user->department->department_code }}
                            </span>
                        @endif
                    </div>
                    <p class="text-muted small mb-0">
                        Planning &amp; Operational Governance &bull; <strong>{{ $period['month'] }} {{ $period['year'] }} &bull; Week {{ $selectedWeek }}</strong>
                    </p>
                </div>
            </div>

            <!-- Date, Time & Quick Link -->
            <div class="d-flex align-items-center gap-2 flex-wrap ms-md-auto">
                <span class="badge period-badge py-2 px-3 shadow-xs" title="Ethiopian Date">
                    <i class="far fa-calendar-alt text-warning me-1"></i> {{ format_et_calendar() }}
                </span>
                <span class="badge period-badge py-2 px-3 shadow-xs" title="Ethiopian Time">
                    <i class="far fa-clock text-warning me-1"></i> {{ format_ethiopian_time() }}
                </span>
                @if(auth()->user()->isGM() || auth()->user()->isSuperAdmin())
                    <a href="{{ route('dashboard.gm') }}" class="btn btn-warning text-dark btn-sm rounded-pill fw-bold px-3 shadow-xs d-inline-flex align-items-center gap-1.5">
                        <i class="fas fa-chart-pie"></i> GM Dashboard
                    </a>
                @elseif(auth()->user()->isDeptHead())
                    <a href="{{ route('dashboard.department') }}" class="btn btn-warning text-dark btn-sm rounded-pill fw-bold px-3 shadow-xs d-inline-flex align-items-center gap-1.5">
                        <i class="fas fa-chart-pie"></i> Dept Dashboard
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- 2. Governance Deadlines Alert Banner -->
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card border border-light-subtle shadow-sm bg-white rounded-4 h-100 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; font-size: 1.15rem;">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex align-items-center justify-content-between mb-0.5">
                            <span class="fw-bold small text-dark">Monthly Plan Submission</span>
                            <span class="badge bg-warning bg-opacity-15 text-warning rounded-pill font-monospace" style="font-size: 0.72rem;">
                                Day {{ $monthlyDeadlineDay }} &bull; {{ $monthlyDeadlineTime }}
                            </span>
                        </div>
                        <div class="text-muted small" style="font-size: 0.8rem;">
                            @if($monthlyDaysLeft > 0)
                                Due on <strong>Day {{ $monthlyDeadlineDay }} of {{ $selectedMonth }}</strong> ({{ $monthlyDaysLeft }} {{ Str::plural('day', $monthlyDaysLeft) }} left)
                            @else
                                Submission window active for current period
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border border-light-subtle shadow-sm bg-white rounded-4 h-100 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; font-size: 1.15rem;">
                        <i class="fas fa-clock-rotate-left"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex align-items-center justify-content-between mb-0.5">
                            <span class="fw-bold small text-dark">Weekly Task Review &amp; Report</span>
                            <span class="badge bg-info bg-opacity-15 text-info rounded-pill font-monospace" style="font-size: 0.72rem;">
                                {{ $weeklyDeadlineDay }} &bull; {{ $weeklyDeadlineTime }}
                            </span>
                        </div>
                        <div class="text-muted small" style="font-size: 0.8rem;">
                            Review previous week performance &amp; submit next week operational plan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Key Metrics (4 Simple Snapshot KPI Cards) -->
    <div class="row g-3 mb-3">
        <!-- 1. Annual Goals -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm bg-white rounded-4 h-100 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; font-size: 1.2rem;">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Strategic Goals</div>
                        <h4 class="fw-bold text-dark mb-0">{{ $totalGoals }}</h4>
                        <div class="text-muted small" style="font-size: 0.72rem;">Annual corporate targets</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Operating Units -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm bg-white rounded-4 h-100 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; font-size: 1.2rem;">
                        <i class="fas fa-building"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Departments</div>
                        <h4 class="fw-bold text-dark mb-0">{{ $totalDepartments }}</h4>
                        <div class="text-muted small" style="font-size: 0.72rem;">Official planning units</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Monthly Plans -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm bg-white rounded-4 h-100 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; font-size: 1.2rem;">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Monthly Plans</div>
                        <h4 class="fw-bold text-dark mb-0">{{ $allMonthlyPlansCount }}</h4>
                        <div class="text-success small fw-medium" style="font-size: 0.72rem;">
                            {{ $submittedMonthlyPackagesCount }} / {{ $totalDepartments }} packages submitted
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Weekly Reports -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm bg-white rounded-4 h-100 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; font-size: 1.2rem;">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Weekly Reports</div>
                        <h4 class="fw-bold text-dark mb-0">{{ $submittedWeeklyReportsCount }}</h4>
                        <div class="text-muted small" style="font-size: 0.72rem;">
                            Week {{ $selectedWeek }} submissions
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Simple Quick Action Workspaces (4 Clean Cards) -->
    <div class="row g-3 mb-4">
        <!-- 1. Annual Strategy -->
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('strategy.annual') }}" class="card border border-light-subtle shadow-sm bg-white rounded-4 p-3.5 text-decoration-none h-100 quick-launch-card">
                <div class="d-flex align-items-center justify-content-between mb-2.5">
                    <div class="rounded-3 bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 1.1rem;">
                        <i class="fas fa-compass"></i>
                    </div>
                    <i class="fas fa-arrow-right text-muted small arrow-icon"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">Annual Strategy</h6>
                <p class="text-muted small mb-0" style="font-size: 0.8rem; line-height: 1.45;">
                    Master strategic priorities, perspective weights, and department mappings.
                </p>
            </a>
        </div>

        <!-- 2. Monthly Planning -->
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('monthly.plans') }}" class="card border border-light-subtle shadow-sm bg-white rounded-4 p-3.5 text-decoration-none h-100 quick-launch-card">
                <div class="d-flex align-items-center justify-content-between mb-2.5">
                    <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 1.1rem;">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <i class="fas fa-arrow-right text-muted small arrow-icon"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">Monthly Planning</h6>
                <p class="text-muted small mb-0" style="font-size: 0.8rem; line-height: 1.45;">
                    Milestone targets, strategy activations, and monthly submission packages.
                </p>
            </a>
        </div>

        <!-- 3. Weekly Execution -->
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('weekly.plans') }}" class="card border border-light-subtle shadow-sm bg-white rounded-4 p-3.5 text-decoration-none h-100 quick-launch-card">
                <div class="d-flex align-items-center justify-content-between mb-2.5">
                    <div class="rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 1.1rem;">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <i class="fas fa-arrow-right text-muted small arrow-icon"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">Weekly Tasks</h6>
                <p class="text-muted small mb-0" style="font-size: 0.8rem; line-height: 1.45;">
                    Deliverables, task execution (DONE / NOT DONE), and weekly reporting.
                </p>
            </a>
        </div>

        <!-- 4. Performance & Dashboards -->
        <div class="col-md-6 col-xl-3">
            <a href="{{ auth()->user()->isGM() || auth()->user()->isSuperAdmin() ? route('dashboard.gm') : route('dashboard.department') }}" class="card border border-light-subtle shadow-sm bg-white rounded-4 p-3.5 text-decoration-none h-100 quick-launch-card">
                <div class="d-flex align-items-center justify-content-between mb-2.5">
                    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 1.1rem; background: rgba(124, 58, 237, 0.1); color: #7c3aed;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <i class="fas fa-arrow-right text-muted small arrow-icon"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">Performance</h6>
                <p class="text-muted small mb-0" style="font-size: 0.8rem; line-height: 1.45;">
                    Department scorecards, submission compliance, and planning vouchers.
                </p>
            </a>
        </div>
    </div>

    <!-- 5. Recent System Activity (Simple & Compact) -->
    @if(!empty($recentActivities) && $recentActivities->isNotEmpty())
        <div class="card border border-light-subtle shadow-sm bg-white rounded-4 p-3.5 mb-3">
            <div class="d-flex align-items-center justify-content-between mb-2.5 border-bottom border-light-subtle pb-2">
                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2 fs-6">
                    <i class="fas fa-clock-rotate-left text-warning"></i> Recent Activity
                </h6>
                <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.72rem;">Audit Stream</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.84rem;">
                    <tbody>
                        @foreach($recentActivities->take(5) as $activity)
                            <tr>
                                <td style="width: 34px;" class="ps-0 py-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-light text-warning border" style="width: 28px; height: 28px; font-size: 0.72rem;">
                                        <i class="fas fa-bolt"></i>
                                    </div>
                                </td>
                                <td class="py-2">
                                    <span class="fw-semibold text-dark">{{ $activity->user->full_name ?? ($activity->user->username ?? 'System') }}</span>
                                    <span class="text-muted mx-1">&bull;</span>
                                    <span class="text-secondary">{{ json_decode($activity->new_values, true)['description'] ?? $activity->action }}</span>
                                </td>
                                <td class="text-end text-muted small font-monospace pe-0 py-2" style="font-size: 0.75rem;">
                                    {{ $activity->created_at ? $activity->created_at->diffForHumans() : 'Recently' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>

<style>
    .quick-launch-card {
        transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
    }
    .quick-launch-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -3px rgba(0, 0, 0, 0.08) !important;
        border-color: #D97706 !important;
    }
    .quick-launch-card:hover .arrow-icon {
        color: #D97706 !important;
        transform: translateX(3px);
    }
    .quick-launch-card .arrow-icon {
        transition: transform 0.16s ease, color 0.16s ease;
    }
</style>
@endsection

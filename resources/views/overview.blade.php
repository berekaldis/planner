@extends('layouts.app')

@section('title', 'Corporate Overview & Planning Launchpad')

@section('content')
<style>
    .overview-action-card {
        background: var(--kaldis-card-bg);
        border: 1px solid var(--kaldis-border);
        border-radius: 16px;
        transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .overview-action-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 28px -6px rgba(15, 23, 42, 0.10) !important;
        border-color: rgba(217, 119, 6, 0.4) !important;
    }
    .overview-action-card:hover .action-card-arrow {
        transform: translateX(4px);
        color: #D97706 !important;
    }
    .action-card-arrow {
        transition: transform 0.2s ease, color 0.2s ease;
    }
    .stat-badge-pill {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.78rem;
        padding: 4px 10px;
        border-radius: 8px;
    }
    .deadline-card-item {
        background: var(--kaldis-bg);
        border: 1px solid var(--kaldis-border);
        border-radius: 12px;
        transition: border-color 0.2s ease;
    }
    .deadline-card-item:hover {
        border-color: rgba(217, 119, 6, 0.35);
    }
</style>

<div class="container-fluid py-2" style="max-width: 1360px; margin: 0 auto;">

    <!-- ══════════════════════════════════════════════════════════════
         1. STREAMLINED EXECUTIVE WELCOME BANNER
    ══════════════════════════════════════════════════════════════ -->
    <div class="card border border-light-subtle shadow-sm mb-4 overflow-hidden rounded-4" style="background: var(--kaldis-card-bg);">
        <div class="card-body p-4 p-md-4.5 position-relative">
            <!-- Decorative Subtle Accent Glows -->
            <div style="position: absolute; top: -60px; right: -60px; width: 220px; height: 220px; background: radial-gradient(circle, rgba(217,119,6,0.12) 0%, transparent 70%); border-radius: 50%; pointer-events: none;"></div>
            <div style="position: absolute; bottom: -60px; left: -60px; width: 220px; height: 220px; background: radial-gradient(circle, rgba(14,165,233,0.08) 0%, transparent 70%); border-radius: 50%; pointer-events: none;"></div>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 position-relative" style="z-index: 2;">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                        <span class="badge bg-warning bg-opacity-10 text-warning px-2.5 py-1 rounded-pill small fw-semibold">
                            <i class="fas fa-compass me-1"></i> Planning Launchpad
                        </span>
                        <span class="badge bg-secondary-subtle text-secondary px-2.5 py-1 rounded-pill small font-monospace">
                            Period: {{ $selectedMonth }} {{ $selectedYear }} &bull; Week {{ $selectedWeek }}
                        </span>
                    </div>
                    <h2 class="fw-bold text-dark mb-1" style="letter-spacing: -0.02em;">
                        {{ get_greeting() }}, {{ explode(' ', $user->full_name ?? $user->username)[0] }}!
                    </h2>
                    <p class="text-muted small mb-0" style="max-width: 720px; line-height: 1.55;">
                        Welcome to the <strong>Kaldis Coffee PLC</strong> Enterprise Planning &amp; Operational Governance Portal. 
                        Execute annual strategies, align monthly milestones, and monitor weekly team performance across all corporate units.
                    </p>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @if(auth()->user()->isGM() || auth()->user()->isSuperAdmin())
                        <a href="{{ route('dashboard.gm') }}" class="btn btn-warning text-dark fw-bold btn-sm px-3.5 py-2 rounded-pill shadow-xs d-inline-flex align-items-center gap-2">
                            <i class="fas fa-chart-line"></i> GM Dashboard
                        </a>
                    @elseif(auth()->user()->department_id)
                        <a href="{{ route('dashboard.department') }}" class="btn btn-warning text-dark fw-bold btn-sm px-3.5 py-2 rounded-pill shadow-xs d-inline-flex align-items-center gap-2">
                            <i class="fas fa-building-user"></i> Department Workspace
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         2. CORE WORKFLOW PILLARS & LIVE METRICS (UNIFIED LAUNCHPAD)
    ══════════════════════════════════════════════════════════════ -->
    <div class="row g-3 mb-4">
        <!-- Pillar 1: Annual Strategy -->
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 overview-action-card shadow-sm p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center text-warning" style="width: 44px; height: 44px; background: rgba(217, 119, 6, 0.12); font-size: 1.25rem;">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <span class="badge bg-warning bg-opacity-10 text-warning stat-badge-pill border border-warning-subtle">
                            {{ $totalGoals }} Goals
                        </span>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Annual Strategy</h5>
                    <div class="text-muted small fw-semibold mb-2" style="font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.04em;">
                        {{ $totalDepartments }} Departments Linked
                    </div>
                    <p class="text-muted small mb-3" style="line-height: 1.5; font-size: 0.82rem;">
                        Master strategic priorities, perspective weights, definitions of done, and department goal mapping.
                    </p>
                </div>
                <a href="{{ route('strategy.annual') }}" class="btn btn-outline-warning btn-sm w-100 fw-bold rounded-pill d-flex align-items-center justify-content-center gap-1.5 py-1.5">
                    <span>Manage Strategy</span>
                    <i class="fas fa-arrow-right action-card-arrow"></i>
                </a>
            </div>
        </div>

        <!-- Pillar 2: Monthly Planning -->
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 overview-action-card shadow-sm p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center text-primary" style="width: 44px; height: 44px; background: rgba(37, 99, 235, 0.12); font-size: 1.25rem;">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <span class="badge bg-primary bg-opacity-10 text-primary stat-badge-pill border border-primary-subtle">
                            {{ $allMonthlyPlansCount }} Plans
                        </span>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Monthly Planning</h5>
                    <div class="text-muted small fw-semibold mb-2" style="font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.04em;">
                        {{ $submittedMonthlyPackagesCount }} Packages Submitted
                    </div>
                    <p class="text-muted small mb-3" style="line-height: 1.5; font-size: 0.82rem;">
                        Department milestone targets, strategic activations, and monthly submission packages for executive review.
                    </p>
                </div>
                <a href="{{ route('monthly.plans') }}" class="btn btn-outline-primary btn-sm w-100 fw-bold rounded-pill d-flex align-items-center justify-content-center gap-1.5 py-1.5">
                    <span>Monthly Workspace</span>
                    <i class="fas fa-arrow-right action-card-arrow"></i>
                </a>
            </div>
        </div>

        <!-- Pillar 3: Weekly Execution -->
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 overview-action-card shadow-sm p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center text-success" style="width: 44px; height: 44px; background: rgba(16, 185, 129, 0.12); font-size: 1.25rem;">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success stat-badge-pill border border-success-subtle">
                            {{ $submittedWeeklyReportsCount }} Reports
                        </span>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Weekly Tasks</h5>
                    <div class="text-muted small fw-semibold mb-2" style="font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.04em;">
                        {{ $allWeeklyTasksCount }} Total Tasks Active
                    </div>
                    <p class="text-muted small mb-3" style="line-height: 1.5; font-size: 0.82rem;">
                        Track weekly deliverables, verify execution status (DONE/NOT DONE), and submit team performance reports.
                    </p>
                </div>
                <a href="{{ route('weekly.plans') }}" class="btn btn-outline-success btn-sm w-100 fw-bold rounded-pill d-flex align-items-center justify-content-center gap-1.5 py-1.5">
                    <span>Weekly Execution</span>
                    <i class="fas fa-arrow-right action-card-arrow"></i>
                </a>
            </div>
        </div>

        <!-- Pillar 4: Executive Governance -->
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 overview-action-card shadow-sm p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center text-info" style="width: 44px; height: 44px; background: rgba(14, 165, 233, 0.12); font-size: 1.25rem;">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <span class="badge bg-info bg-opacity-10 text-info stat-badge-pill border border-info-subtle">
                            {{ $companyCompletionPct }}% Done
                        </span>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Corporate Governance</h5>
                    <div class="text-muted small fw-semibold mb-2" style="font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.04em;">
                        16 Corporate Units
                    </div>
                    <p class="text-muted small mb-3" style="line-height: 1.5; font-size: 0.82rem;">
                        Company-wide scorecards, department submission compliance, official vouchers, and executive reviews.
                    </p>
                </div>
                <a href="{{ auth()->user()->isGM() || auth()->user()->isSuperAdmin() ? route('dashboard.gm') : route('dashboard.department') }}" class="btn btn-outline-info btn-sm w-100 fw-bold rounded-pill d-flex align-items-center justify-content-center gap-1.5 py-1.5">
                    <span>{{ auth()->user()->isGM() || auth()->user()->isSuperAdmin() ? 'GM Oversight' : 'My Department' }}</span>
                    <i class="fas fa-arrow-right action-card-arrow"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         3. PLANNING DEADLINES RADAR & RECENT ACTIVITY
    ══════════════════════════════════════════════════════════════ -->
    <div class="row g-3 mb-4">
        <!-- Planning Deadlines Radar -->
        <div class="col-lg-6">
            <div class="card border border-light-subtle shadow-sm bg-white rounded-4 overflow-hidden h-100">
                <div class="card-header bg-white py-3 border-bottom border-light-subtle d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-3 bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="fas fa-business-time"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Planning Submission Radar</h6>
                            <span class="text-muted" style="font-size: 0.72rem;">Official organizational governance deadlines</span>
                        </div>
                    </div>
                    <span class="badge bg-light text-secondary border font-monospace small">Active Rules</span>
                </div>
                <div class="card-body p-3.5 d-flex flex-column gap-2.5">
                    <!-- Monthly Plan Deadline Item -->
                    <div class="p-3 rounded-3 deadline-card-item">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="fw-bold small text-dark d-flex align-items-center gap-1.5">
                                <i class="fas fa-calendar-alt text-primary"></i> Monthly Plan Submission
                            </span>
                            <span class="badge bg-primary-subtle text-primary font-monospace" style="font-size: 0.74rem;">
                                Day {{ $monthlyDeadlineDay }} ({{ $monthlyDeadlineTime }})
                            </span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted small" style="font-size: 0.78rem;">
                                Next month's strategic plan must be submitted before month start.
                            </span>
                            <span class="badge bg-light text-dark border font-monospace small ms-2 flex-shrink-0">
                                {{ $monthlyDaysLeft }} {{ Str::plural('day', $monthlyDaysLeft) }} left
                            </span>
                        </div>
                    </div>

                    <!-- Weekly Performance & Plan Deadline Item -->
                    <div class="p-3 rounded-3 deadline-card-item">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="fw-bold small text-dark d-flex align-items-center gap-1.5">
                                <i class="fas fa-tasks text-success"></i> Weekly Performance &amp; Tasks
                            </span>
                            <span class="badge bg-success-subtle text-success font-monospace" style="font-size: 0.74rem;">
                                Every {{ $weeklyDeadlineDay }} ({{ $weeklyDeadlineTime }})
                            </span>
                        </div>
                        <span class="text-muted small" style="font-size: 0.78rem;">
                            Concurrent submission: Previous week binary results + upcoming week plan.
                        </span>
                    </div>

                    <!-- Telegram Notifications Status -->
                    <div class="p-2.5 rounded-3 bg-info bg-opacity-10 border border-info-subtle d-flex align-items-center justify-content-between mt-auto">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fab fa-telegram text-info fs-5"></i>
                            <div>
                                <div class="fw-semibold text-dark small">Telegram Automated Notifications</div>
                                <div class="text-muted" style="font-size: 0.72rem;">Instant reminders sent to Department Heads &amp; GM</div>
                            </div>
                        </div>
                        <a href="{{ route('telegram.status') }}" class="btn btn-sm btn-outline-info rounded-pill px-3 py-1 font-monospace" style="font-size: 0.72rem;">
                            Status
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent System Activity Audit Stream -->
        <div class="col-lg-6">
            <div class="card border border-light-subtle shadow-sm bg-white rounded-4 overflow-hidden h-100">
                <div class="card-header bg-white py-3 border-bottom border-light-subtle d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-3 bg-secondary bg-opacity-10 text-dark d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="fas fa-clock-rotate-left text-warning"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Real-Time Operational Audit</h6>
                            <span class="text-muted" style="font-size: 0.72rem;">Latest governance and planning transactions</span>
                        </div>
                    </div>
                    @if(auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.audit') }}" class="small text-muted text-decoration-none fw-semibold">
                            Full Audit <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    @endif
                </div>
                <div class="card-body p-3">
                    @if(!empty($recentActivities) && $recentActivities->isNotEmpty())
                        <div class="d-flex flex-column gap-2">
                            @foreach($recentActivities->take(5) as $activity)
                                <div class="p-2.5 rounded-3 border border-light-subtle d-flex align-items-center justify-content-between" style="background: var(--kaldis-bg);">
                                    <div class="d-flex align-items-center gap-2.5 min-w-0">
                                        <div class="rounded-circle bg-warning bg-opacity-20 text-dark d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 30px; height: 30px; font-size: 0.75rem;">
                                            {{ strtoupper(substr($activity->user->full_name ?? ($activity->user->username ?? 'S'), 0, 1)) }}
                                        </div>
                                        <div class="text-truncate">
                                            <div class="fw-semibold text-dark small text-truncate">
                                                {{ $activity->user->full_name ?? ($activity->user->username ?? 'System Operator') }}
                                            </div>
                                            <div class="text-muted" style="font-size: 0.73rem;">
                                                {{ json_decode($activity->new_values, true)['description'] ?? $activity->action }}
                                            </div>
                                        </div>
                                    </div>
                                    <span class="text-muted font-monospace flex-shrink-0 ms-2 small" style="font-size: 0.7rem;">
                                        {{ $activity->created_at ? $activity->created_at->diffForHumans(null, true) : 'recent' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-muted small">
                            <i class="fas fa-inbox fa-2x mb-2 opacity-25 d-block"></i>
                            No recent system activity recorded.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

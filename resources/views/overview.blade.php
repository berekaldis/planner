@extends('layouts.app')

@section('title', 'Overview & Executive Launchpad')

@section('content')
<style>
    /* ══════════════════════════════════════════════════════════
       KALDIS LUXURY SIMPLE & FANCY OVERVIEW STYLES
       ══════════════════════════════════════════════════════════ */
    .fancy-hero-card {
        background: linear-gradient(135deg, #0F141C 0%, #151D2A 50%, #1E293B 100%) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        border-radius: 22px !important;
        position: relative;
        overflow: hidden;
        box-shadow: 0 18px 45px -10px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(245, 158, 11, 0.12) !important;
    }

    .fancy-hero-glow-1 {
        position: absolute;
        top: -60px;
        right: -40px;
        width: 320px;
        height: 320px;
        background: radial-gradient(circle, rgba(245, 158, 11, 0.18) 0%, transparent 68%);
        border-radius: 50%;
        pointer-events: none;
    }

    .fancy-hero-glow-2 {
        position: absolute;
        bottom: -80px;
        left: 20%;
        width: 260px;
        height: 260px;
        background: radial-gradient(circle, rgba(14, 165, 233, 0.12) 0%, transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }

    .fancy-hero-logo {
        width: 82px;
        height: 82px;
        border-radius: 50%;
        object-fit: cover;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.35), 0 10px 24px rgba(0, 0, 0, 0.4);
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
        background: transparent !important;
    }

    .fancy-hero-logo:hover {
        transform: scale(1.06) rotate(2deg);
        box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.6), 0 14px 30px rgba(245, 158, 11, 0.35);
    }

    .fancy-greeting-text {
        font-size: clamp(1.6rem, 2.8vw, 2.2rem);
        font-weight: 800;
        letter-spacing: -0.025em;
        color: #FFFFFF;
        line-height: 1.2;
    }

    .fancy-gold-gradient-text {
        background: linear-gradient(135deg, #FDE68A 0%, #F59E0B 50%, #D97706 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .fancy-card {
        background: var(--kaldis-card-bg);
        border: 1px solid var(--kaldis-border);
        border-radius: 18px;
        box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.04);
        transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.22s ease, border-color 0.22s ease;
    }

    .fancy-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 30px -4px rgba(15, 23, 42, 0.08), 0 0 0 1px rgba(217, 119, 6, 0.25);
        border-color: rgba(217, 119, 6, 0.35);
    }

    .fancy-kpi-icon-wrap {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .fancy-shortcut-card {
        background: var(--kaldis-card-bg);
        border: 1px solid var(--kaldis-border);
        border-radius: 18px;
        padding: 1.35rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
        transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
        text-decoration: none;
        color: inherit;
        position: relative;
        overflow: hidden;
    }

    .fancy-shortcut-card::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        height: 3px;
        width: 0;
        background: linear-gradient(90deg, #F59E0B, #D97706);
        transition: width 0.25s ease;
    }

    .fancy-shortcut-card:hover {
        transform: translateY(-4px);
        border-color: rgba(217, 119, 6, 0.35);
        box-shadow: 0 16px 32px -4px rgba(15, 23, 42, 0.08);
        color: inherit;
    }

    .fancy-shortcut-card:hover::after {
        width: 100%;
    }

    .fancy-activity-row:hover {
        background-color: rgba(245, 158, 11, 0.04);
        border-radius: 10px;
    }

    [data-bs-theme="dark"] .fancy-card,
    [data-bs-theme="dark"] .fancy-shortcut-card {
        background: #111827;
        border-color: #1F2937;
    }

    [data-bs-theme="dark"] .fancy-activity-row:hover {
        background-color: rgba(255, 255, 255, 0.03);
    }
</style>

<div class="container-fluid px-0" style="max-width: 1380px; margin: 0 auto;">

    <!-- ══════════════════════════════════════════════════════════════
         1. FANCY EXECUTIVE HERO HEADER
         ══════════════════════════════════════════════════════════════ -->
    <div class="fancy-hero-card p-4 p-md-5 mb-4">
        <div class="fancy-hero-glow-1"></div>
        <div class="fancy-hero-glow-2"></div>

        <div class="row align-items-center g-4 position-relative" style="z-index: 2;">
            <!-- Left: Greeting, Period Badges & Identity -->
            <div class="col-lg-8">
                <!-- Live Ethiopian Date & Operational Period Badges -->
                <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                    <span class="badge rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5" 
                          style="background: rgba(245, 158, 11, 0.18); color: #F59E0B; border: 1px solid rgba(245, 158, 11, 0.3);">
                        <i class="fas fa-sparkles"></i> Kaldis Planning Launchpad
                    </span>
                    <span class="badge rounded-pill px-3 py-1.5 font-monospace d-inline-flex align-items-center gap-1.5 text-white" 
                          style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.12);">
                        <i class="far fa-calendar-alt text-warning"></i> {{ $period['month'] }} {{ $period['year'] }} &bull; Week {{ $selectedWeek }}
                    </span>
                    <span class="badge rounded-pill px-3 py-1.5 font-monospace d-none d-sm-inline-flex align-items-center gap-1.5 text-white" 
                          style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.12);"
                          title="Ethiopian Local Time">
                        <i class="far fa-clock text-warning"></i> {{ format_ethiopian_time() }}
                    </span>
                </div>

                <!-- Personalized Welcome Message -->
                <h1 class="fancy-greeting-text mb-2">
                    {{ get_greeting() }}, <span class="fancy-gold-gradient-text">{{ explode(' ', $user->full_name ?? $user->username)[0] }}</span>!
                </h1>
                <p class="text-white-50 mb-3" style="font-size: 0.98rem; max-width: 640px; line-height: 1.55;">
                    Welcome to the <strong>Kaldis Coffee PLC</strong> Strategic Planning Portal. Track annual objectives, submit monthly milestones, and monitor weekly team execution with precision.
                </p>

                <!-- User Badges: Role & Department -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge rounded-pill px-3 py-1.5 text-white d-inline-flex align-items-center gap-1.5" 
                          style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.35); font-size: 0.8rem;">
                        <i class="fas fa-shield-halved text-warning"></i> {{ $user->role->display_name ?? 'Corporate Staff' }}
                    </span>
                    <span class="badge rounded-pill px-3 py-1.5 text-white-50 d-inline-flex align-items-center gap-1.5" 
                          style="background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.1); font-size: 0.8rem;">
                        <i class="fas fa-building text-warning"></i> {{ $user->department->department_name ?? 'Corporate Leadership' }}
                    </span>
                    @if(!empty($user->telegram_chat_id))
                        <span class="badge rounded-pill px-2.5 py-1.5 text-info d-inline-flex align-items-center gap-1" 
                              style="background: rgba(14, 165, 233, 0.12); border: 1px solid rgba(14, 165, 233, 0.25); font-size: 0.78rem;">
                            <i class="fab fa-telegram"></i> Bot Linked
                        </span>
                    @endif
                </div>
            </div>

            <!-- Right: Logo & Quick Launch CTA -->
            <div class="col-lg-4 d-flex flex-column align-items-lg-end align-items-start justify-content-center">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="text-lg-end d-none d-lg-block">
                        <div class="text-white fw-bold small text-uppercase" style="letter-spacing: 0.08em; font-size: 0.75rem;">KALDIS COFFEE PLC</div>
                        <div class="text-white-50 small" style="font-size: 0.7rem;">Planning &amp; Performance</div>
                    </div>
                    <img src="{{ asset('assets/images/logo.png') }}" 
                         alt="Kaldis Coffee PLC" 
                         class="fancy-hero-logo"
                         onerror="this.src='{{ asset('images/logo.png') }}'">
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    @if(auth()->user()->isDeptHead())
                        <a href="{{ route('weekly.plans') }}" class="btn btn-warning text-dark fw-bold rounded-pill px-3 py-2 shadow-sm d-inline-flex align-items-center gap-2">
                            <i class="fas fa-list-check"></i> Submit Week Plan
                        </a>
                        <a href="{{ route('monthly.plans') }}" class="btn btn-outline-light rounded-pill px-3 py-2 shadow-sm d-inline-flex align-items-center gap-1.5">
                            <i class="fas fa-calendar-alt text-warning"></i> Monthly Plan
                        </a>
                    @else
                        <a href="{{ route('dashboard.gm') }}" class="btn btn-warning text-dark fw-bold rounded-pill px-3 py-2 shadow-sm d-inline-flex align-items-center gap-2">
                            <i class="fas fa-chart-line"></i> GM Dashboard
                        </a>
                        <a href="{{ route('strategy.annual') }}" class="btn btn-outline-light rounded-pill px-3 py-2 shadow-sm d-inline-flex align-items-center gap-1.5">
                            <i class="fas fa-compass text-warning"></i> 58 Annual Goals
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         2. PLANNING DEADLINE & WEEKLY EXECUTION STATUS BARS
         ══════════════════════════════════════════════════════════════ -->
    <div class="row g-3 mb-4">
        <!-- Monthly Planning Deadline Countdown -->
        <div class="col-lg-6">
            <div class="fancy-card p-3.5 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="fancy-kpi-icon-wrap bg-warning bg-opacity-10 text-warning" style="width: 40px; height: 40px; font-size: 1.1rem;">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">Monthly Submission Deadline</span>
                            <h6 class="fw-bold text-dark mb-0">Every Month Day 26 &bull; {{ $monthlyDeadlineTime }}</h6>
                        </div>
                    </div>
                    @if($monthlySubmission && $monthlySubmission->status === 'SUBMITTED')
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 small">
                            <i class="fas fa-check-circle me-1"></i> Submitted to GM
                        </span>
                    @else
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2.5 py-1 small">
                            <i class="fas fa-clock me-1"></i> {{ $monthlyDaysLeft }} {{ Str::plural('Day', $monthlyDaysLeft) }} Left
                        </span>
                    @endif
                </div>

                <div class="d-flex align-items-center justify-content-between pt-2 border-top border-light-subtle">
                    <div class="small text-muted" style="font-size: 0.8rem;">
                        Target: Submit by <strong>{{ $selectedMonth }} 26, {{ $selectedYear }}</strong> before 17:00
                    </div>
                    <a href="{{ route('monthly.plans') }}" class="small fw-semibold text-warning text-decoration-none d-inline-flex align-items-center gap-1">
                        Go to Monthly Plan <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Weekly Task Execution Pace -->
        <div class="col-lg-6">
            <div class="fancy-card p-3.5 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="fancy-kpi-icon-wrap bg-success bg-opacity-10 text-success" style="width: 40px; height: 40px; font-size: 1.1rem;">
                            <i class="fas fa-circle-check"></i>
                        </div>
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">Weekly Task Execution Status</span>
                            <h6 class="fw-bold text-dark mb-0">Week {{ $selectedWeek }} Binary Performance</h6>
                        </div>
                    </div>
                    @if($deptTasksCount > 0)
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 small font-monospace">
                            {{ $completionPct }}% Complete
                        </span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2.5 py-1 small">
                            {{ $submittedWeeklyReportsCount }} / {{ $totalDepartments }} Depts Active
                        </span>
                    @endif
                </div>

                <div class="pt-2 border-top border-light-subtle">
                    @if($deptTasksCount > 0)
                        <div class="d-flex justify-content-between align-items-center mb-1 small text-muted" style="font-size: 0.78rem;">
                            <span>Tasks Done: <strong class="text-success">{{ $deptDoneCount }}</strong> / {{ $deptTasksCount }}</span>
                            <span class="text-danger">Not Done: {{ $deptNotDoneCount }}</span>
                        </div>
                        <div class="progress" style="height: 6px; border-radius: 999px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $completionPct }}%;" aria-valuenow="{{ $completionPct }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    @else
                        <div class="d-flex align-items-center justify-content-between small text-muted" style="font-size: 0.8rem;">
                            <span>Weekly Deadline: <strong>{{ $weeklyDeadlineDay }} at {{ $weeklyDeadlineTime }}</strong> (6:30 LT)</span>
                            <a href="{{ route('weekly.plans') }}" class="fw-semibold text-success text-decoration-none d-inline-flex align-items-center gap-1">
                                Open Weekly Workspace <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         3. CORE STRATEGIC KPI METRIC CARDS
         ══════════════════════════════════════════════════════════════ -->
    <div class="row g-3 mb-4">
        <!-- 1. Annual Strategic Goals -->
        <div class="col-sm-6 col-xl-3">
            <div class="fancy-card p-3.5 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="fancy-kpi-icon-wrap bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.04em;">Annual Goals</div>
                        <div class="d-flex align-items-baseline gap-1.5">
                            <h4 class="fw-bold text-dark mb-0">{{ $totalGoals }}</h4>
                            <span class="badge bg-warning-subtle text-warning small font-monospace">Active</span>
                        </div>
                        <div class="text-muted small" style="font-size: 0.72rem;">58 Strategic Objectives</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Corporate Operating Departments -->
        <div class="col-sm-6 col-xl-3">
            <div class="fancy-card p-3.5 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="fancy-kpi-icon-wrap bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-sitemap"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.04em;">Departments</div>
                        <div class="d-flex align-items-baseline gap-1.5">
                            <h4 class="fw-bold text-dark mb-0">{{ $totalDepartments }}</h4>
                            <span class="badge bg-primary-subtle text-primary small font-monospace">Units</span>
                        </div>
                        <div class="text-muted small" style="font-size: 0.72rem;">Strategic Planning Units</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Monthly Strategic Plans -->
        <div class="col-sm-6 col-xl-3">
            <div class="fancy-card p-3.5 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="fancy-kpi-icon-wrap bg-success bg-opacity-10 text-success">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.04em;">Monthly Plans</div>
                        <div class="d-flex align-items-baseline gap-1.5">
                            <h4 class="fw-bold text-dark mb-0">{{ $allMonthlyPlansCount }}</h4>
                            <span class="badge bg-success-subtle text-success small font-monospace">{{ $submittedMonthlyPackagesCount }} Sent</span>
                        </div>
                        <div class="text-muted small" style="font-size: 0.72rem;">Milestone Deliverables</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Weekly Submissions -->
        <div class="col-sm-6 col-xl-3">
            <div class="fancy-card p-3.5 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="fancy-kpi-icon-wrap bg-info bg-opacity-10 text-info">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.04em;">Weekly Reports</div>
                        <div class="d-flex align-items-baseline gap-1.5">
                            <h4 class="fw-bold text-dark mb-0">{{ $submittedWeeklyReportsCount }}</h4>
                            <span class="badge bg-info-subtle text-info small font-monospace">Week {{ $selectedWeek }}</span>
                        </div>
                        <div class="text-muted small" style="font-size: 0.72rem;">Performance Verified</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         4. STRATEGIC WORKFLOW QUICK LAUNCHPAD (SIMPLE & CRISP)
         ══════════════════════════════════════════════════════════════ -->
    <div class="row g-3 mb-4">
        <!-- 1. Annual Strategic Goals -->
        <div class="col-sm-6 col-lg-3">
            <a href="{{ route('strategy.annual') }}" class="fancy-shortcut-card">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="rounded-3 bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="fas fa-compass"></i>
                        </div>
                        <span class="badge bg-light text-secondary border font-monospace small">Strategy</span>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Annual Strategy</h5>
                    <p class="text-muted small mb-3" style="line-height: 1.45;">
                        Master strategic priorities, perspective weights, definitions of done, and department ownership.
                    </p>
                </div>
                <div class="fw-bold text-warning small d-flex align-items-center justify-content-between pt-2 border-top border-light-subtle">
                    <span>Explore Goals</span>
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
        </div>

        <!-- 2. Monthly Planning -->
        <div class="col-sm-6 col-lg-3">
            <a href="{{ route('monthly.plans') }}" class="fancy-shortcut-card">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <span class="badge bg-light text-secondary border font-monospace small">Monthly</span>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Monthly Workspace</h5>
                    <p class="text-muted small mb-3" style="line-height: 1.45;">
                        Configure milestone targets, strategic activations, and submit complete monthly package to GM.
                    </p>
                </div>
                <div class="fw-bold text-primary small d-flex align-items-center justify-content-between pt-2 border-top border-light-subtle">
                    <span>Manage Plans</span>
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
        </div>

        <!-- 3. Weekly Execution -->
        <div class="col-sm-6 col-lg-3">
            <a href="{{ route('weekly.plans') }}" class="fancy-shortcut-card">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <span class="badge bg-light text-secondary border font-monospace small">Weekly</span>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Weekly Execution</h5>
                    <p class="text-muted small mb-3" style="line-height: 1.45;">
                        Track weekly binary tasks (DONE/NOT DONE), verify results with one click, and report performance.
                    </p>
                </div>
                <div class="fw-bold text-success small d-flex align-items-center justify-content-between pt-2 border-top border-light-subtle">
                    <span>Execute Tasks</span>
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
        </div>

        <!-- 4. Executive Oversight & Scorecards -->
        <div class="col-sm-6 col-lg-3">
            <a href="{{ auth()->user()->isGM() || auth()->user()->isSuperAdmin() ? route('dashboard.gm') : route('dashboard.department') }}" class="fancy-shortcut-card">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="rounded-3 bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="fas fa-gauge-high"></i>
                        </div>
                        <span class="badge bg-light text-secondary border font-monospace small">Executive</span>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Executive Dashboard</h5>
                    <p class="text-muted small mb-3" style="line-height: 1.45;">
                        Company-wide scorecards, submission compliance, official planning vouchers, and GM reviews.
                    </p>
                </div>
                <div class="fw-bold text-info small d-flex align-items-center justify-content-between pt-2 border-top border-light-subtle">
                    <span>Open Dashboard</span>
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         5. VISUAL SNAPSHOT & RECENT ACTIVITY AUDIT STREAM
         ══════════════════════════════════════════════════════════════ -->
    <div class="row g-3 mb-4">
        <!-- Visual Progress Widget (Chart.js) -->
        <div class="col-lg-7">
            <div class="fancy-card p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom border-light-subtle">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-3 bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                            <i class="fas fa-chart-column"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-0">Operational Submission Activity</h6>
                            <span class="text-muted small" style="font-size: 0.72rem;">Department participation in current operating cycle</span>
                        </div>
                    </div>
                    <span class="badge bg-light text-secondary border font-monospace small">{{ $selectedMonth }} {{ $selectedYear }}</span>
                </div>

                <div style="height: 220px; position: relative;">
                    <canvas id="overviewActivityChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent System Activities Stream -->
        <div class="col-lg-5">
            <div class="fancy-card p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom border-light-subtle">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-3 bg-secondary bg-opacity-10 text-dark d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                            <i class="fas fa-clock-rotate-left text-warning"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-0">Recent Activity</h6>
                            <span class="text-muted small" style="font-size: 0.72rem;">Live governance audit stream</span>
                        </div>
                    </div>
                    <a href="{{ route('admin.audit') }}" class="small text-muted text-decoration-none">View All</a>
                </div>

                @if(!empty($recentActivities) && $recentActivities->isNotEmpty())
                    <div class="d-flex flex-column gap-2.5">
                        @foreach($recentActivities->take(5) as $activity)
                            <div class="p-2 d-flex align-items-center gap-2.5 fancy-activity-row">
                                <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                    <i class="fas fa-bolt"></i>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-dark text-truncate" style="font-size: 0.85rem;">
                                        {{ $activity->user->full_name ?? ($activity->user->username ?? 'System') }}
                                    </div>
                                    <div class="text-muted text-truncate" style="font-size: 0.75rem;">
                                        {{ json_decode($activity->new_values, true)['description'] ?? $activity->action }}
                                    </div>
                                </div>
                                <span class="text-muted small flex-shrink-0 font-monospace" style="font-size: 0.7rem;">
                                    {{ $activity->created_at ? $activity->created_at->diffForHumans(null, true) : 'just now' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4 text-muted small">
                        <i class="fas fa-circle-info fa-2x mb-2 opacity-25 d-block"></i>
                        No recent activity recorded yet today.
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Render Minimalist Chart.js Activity Snapshot
        const canvas = document.getElementById('overviewActivityChart');
        if (canvas && typeof Chart !== 'undefined') {
            const ctx = canvas.getContext('2d');

            const totalDepts = {{ $totalDepartments }};
            const monthlySubmitted = {{ $submittedMonthlyPackagesCount }};
            const monthlyPending = Math.max(0, totalDepts - monthlySubmitted);
            const weeklySubmitted = {{ $submittedWeeklyReportsCount }};
            const weeklyPending = Math.max(0, totalDepts - weeklySubmitted);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Monthly Submissions (Day 26)', 'Weekly Execution Reports (W{{ $selectedWeek }})'],
                    datasets: [
                        {
                            label: 'Submitted & Active',
                            data: [monthlySubmitted, weeklySubmitted],
                            backgroundColor: '#059669',
                            borderRadius: 6,
                            maxBarThickness: 38
                        },
                        {
                            label: 'Pending Submissions',
                            data: [monthlyPending, weeklyPending],
                            backgroundColor: '#F59E0B',
                            borderRadius: 6,
                            maxBarThickness: 38
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                boxHeight: 12,
                                font: { size: 11, family: 'Inter' }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return ' ' + context.dataset.label + ': ' + context.parsed.y + ' of ' + totalDepts + ' Departments';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { family: 'Inter', weight: '600', size: 11 } }
                        },
                        y: {
                            beginAtZero: true,
                            max: Math.max(totalDepts, 16),
                            ticks: { precision: 0, font: { family: 'JetBrains Mono', size: 10 } },
                            grid: { color: 'rgba(0, 0, 0, 0.05)' }
                        }
                    }
                }
            });
        }
    });
</script>
@endsection

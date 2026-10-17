@extends('layouts.app')

@section('title', 'Overview & Command Center')

@section('content')
<div class="container-fluid py-2" style="max-width: 1320px; margin: 0 auto;">

    <!-- ── Smart Hero Section ── -->
    <div class="card border-0 shadow-sm mb-4 overflow-hidden" style="background: var(--kaldis-card-bg); border-radius: 16px;">
        <div class="card-body p-4 p-md-5 text-center position-relative">
            <!-- Decorative Subtle Accent Lines -->
            <div style="position: absolute; top: -50px; right: -50px; width: 160px; height: 160px; background: radial-gradient(circle, rgba(217,119,6,0.12) 0%, transparent 70%); border-radius: 50%; pointer-events: none;"></div>
            <div style="position: absolute; bottom: -50px; left: -50px; width: 160px; height: 160px; background: radial-gradient(circle, rgba(37,99,235,0.08) 0%, transparent 70%); border-radius: 50%; pointer-events: none;"></div>

            <!-- Live Calendar & Time Window Pills -->
            <div class="d-inline-flex align-items-center gap-2 flex-wrap justify-content-center mb-3">
                <span class="status-pill status-done shadow-xs">
                    <span class="status-dot dot-done status-dot-pulse"></span>
                    <span>Active Planning Cycle</span>
                </span>
                <span class="badge period-badge shadow-xs py-2 px-3">
                    <i class="far fa-calendar-alt text-warning me-1"></i>
                    {{ $period['month'] }} {{ $period['year'] }} &bull; Week {{ $selectedWeek }}
                </span>
                <span class="badge period-badge shadow-xs py-2 px-3" title="Ethiopian Time (East Africa Time)">
                    <i class="far fa-clock text-warning me-1"></i>
                    {{ format_ethiopian_time() }}
                </span>
            </div>

            <!-- Personalized Greeting & Subtitle -->
            <h1 class="fw-extrabold text-dark mb-2" style="font-size: clamp(1.6rem, 3.5vw, 2.3rem); letter-spacing: -0.02em;">
                {{ get_greeting() }}, {{ explode(' ', $user->full_name ?? $user->username)[0] }}!
            </h1>
            <p class="text-muted mx-auto mb-4" style="max-width: 680px; font-size: 1rem; line-height: 1.5;">
                Welcome to the <strong>Kaldis Coffee PLC</strong> Enterprise Planning &amp; Operational Execution Portal. Monitor strategic goals, track weekly deliverables, and access official vouchers.
            </p>

            <!-- Brand Identity Emblem -->
            <div class="d-inline-block mb-3 p-2 rounded-circle shadow-xs" style="background: var(--kaldis-card-bg); border: 2px solid rgba(217,119,6,0.25);">
                <img src="{{ asset('assets/images/logo.png') }}" alt="Kaldis Coffee" style="width: 58px; height: 58px; object-fit: contain;" onerror="this.src='{{ asset('images/logo.png') }}'">
            </div>

            <!-- User Role, Department & Telegram Capsules -->
            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                <span class="badge py-2 px-3 shadow-xs d-inline-flex align-items-center gap-1.5" 
                      style="background: var(--k-gold-soft); color: var(--k-gold); border: 1px solid rgba(245,158,11,.3); font-size: 0.82rem; border-radius: 9999px;">
                    <i class="fas fa-shield-halved"></i>
                    <span>{{ $user->role->display_name ?? 'Corporate Staff' }}</span>
                </span>
                <span class="badge py-2 px-3 shadow-xs d-inline-flex align-items-center gap-1.5" 
                      style="background: var(--kaldis-card-bg); color: var(--kaldis-text-secondary); border: 1px solid var(--kaldis-border); font-size: 0.82rem; border-radius: 9999px;">
                    <i class="fas fa-building text-secondary"></i>
                    <span>{{ $user->department->department_name ?? 'Corporate Office' }}</span>
                </span>
                @if(!empty($user->telegram_chat_id))
                    <span class="badge py-2 px-3 shadow-xs d-inline-flex align-items-center gap-1.5" 
                          style="background: rgba(14,165,233,0.1); color: #0284c7; border: 1px solid rgba(14,165,233,0.3); font-size: 0.82rem; border-radius: 9999px;"
                          title="Telegram Bot linked for real-time submission alerts and reminders">
                        <i class="fab fa-telegram"></i>
                        <span>Telegram Linked</span>
                    </span>
                @else
                    <span class="badge py-2 px-3 shadow-xs d-inline-flex align-items-center gap-1.5" 
                          style="background: rgba(156,163,175,0.1); color: #6b7280; border: 1px solid rgba(156,163,175,0.3); font-size: 0.82rem; border-radius: 9999px;"
                          title="Telegram ID not linked yet">
                        <i class="fab fa-telegram"></i>
                        <span>Telegram Disconnected</span>
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- ── Dual Corporate Deadlines Alert Strip ── -->
    <div class="row g-3 mb-4">
        <!-- Monthly Plan Deadline Card (Day 26 in Ethiopian Calendar) -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="background: var(--kaldis-card-bg); border-radius: 14px; border-left: 5px solid #d97706 !important;">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" style="background: rgba(217,119,6,0.12); color: #d97706; width: 42px; height: 42px;">
                                    <i class="fas fa-calendar-check fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0">Monthly Plan Submission Due Day</h6>
                                    <small class="text-muted">Ethiopian Planning Cycle: <strong>{{ $selectedMonth }} {{ $selectedYear }}</strong></small>
                                </div>
                            </div>
                            <span class="badge bg-warning-subtle text-warning fw-bold px-2.5 py-1.5" style="border-radius: 8px;">
                                Day {{ $monthlyDeadlineDay }} ({{ $monthlyDeadlineTime }})
                            </span>
                        </div>

                        <p class="text-muted small mb-3">
                            All departmental operational initiatives must be finalized and transmitted by <strong>Day {{ $monthlyDeadlineDay }} of the Ethiopian calendar ({{ $monthlyDeadlineTime }})</strong>.
                        </p>

                        <!-- Progress toward Day 26 -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small text-secondary fw-semibold mb-1">
                                <span>Cycle Progress: Day {{ $currentEthDay }} of 30</span>
                                <span>
                                    @if($currentEthDay <= $monthlyDeadlineDay)
                                        <strong class="text-warning">{{ $monthlyDaysLeft }} days remaining</strong> until Day {{ $monthlyDeadlineDay }} cutoff
                                    @else
                                        <strong class="text-danger">Cutoff elapsed for {{ $selectedMonth }}</strong>
                                    @endif
                                </span>
                            </div>
                            <div class="progress" style="height: 8px; border-radius: 9999px; background: rgba(0,0,0,0.06);">
                                @php
                                    $monthlyProgressPct = min(100, round(($currentEthDay / $monthlyDeadlineDay) * 100));
                                @endphp
                                <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $monthlyProgressPct }}%;" aria-valuenow="{{ $monthlyProgressPct }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>

                        <!-- Current Submission Status -->
                        <div class="p-2.5 rounded-3 mb-3 d-flex align-items-center justify-content-between" style="background: rgba(0,0,0,0.02); border: 1px dashed var(--kaldis-border);">
                            <span class="small text-muted">
                                <i class="fas fa-info-circle me-1 text-primary"></i> 
                                {{ $user->department->department_name ?? 'Your Department' }} Status:
                            </span>
                            @if($monthlySubmission && $monthlySubmission->status === 'SUBMITTED')
                                <span class="badge bg-success-subtle text-success fw-bold">
                                    <i class="fas fa-check-circle me-1"></i> Package Submitted
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-dark fw-bold">
                                    <i class="fas fa-pen-to-square me-1"></i> Drafting ({{ $deptMonthlyPlansCount }} Plans Added)
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('monthly.plans') }}" class="btn btn-sm btn-kaldis flex-grow-1">
                            <i class="fas fa-plus me-1"></i> Add / Manage Monthly Plan
                        </a>
                        @if($monthlySubmission && $monthlySubmission->status === 'SUBMITTED')
                            <a href="{{ route('monthly.voucher') }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-receipt me-1"></i> View Official Voucher
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Weekly Reporting Deadline Card (Monday 12:00 PM) -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="background: var(--kaldis-card-bg); border-radius: 14px; border-left: 5px solid #2563eb !important;">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" style="background: rgba(37,99,235,0.12); color: #2563eb; width: 42px; height: 42px;">
                                    <i class="fas fa-clock fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0">Weekly Reporting Deadline</h6>
                                    <small class="text-muted">Weekly Cycle: <strong>Week {{ $selectedWeek }} ({{ $selectedMonth }} {{ $selectedYear }})</strong></small>
                                </div>
                            </div>
                            <span class="badge bg-primary-subtle text-primary fw-bold px-2.5 py-1.5" style="border-radius: 8px;">
                                {{ $weeklyDeadlineDay }} ({{ $weeklyDeadlineTime }})
                            </span>
                        </div>

                        <p class="text-muted small mb-3">
                            Weekly execution performance reports are audited every <strong>{{ $weeklyDeadlineDay }} at {{ $weeklyDeadlineTime }}</strong>. Non-submissions trigger multi-tier escalations.
                        </p>

                        <!-- Task Execution Overview -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small text-secondary fw-semibold mb-1">
                                <span>Week {{ $selectedWeek }} Tasks: {{ $deptDoneCount }} DONE / {{ $deptTasksCount }} Scheduled</span>
                                <span class="text-primary fw-bold">{{ $completionPct }}% Complete</span>
                            </div>
                            <div class="progress" style="height: 8px; border-radius: 9999px; background: rgba(0,0,0,0.06);">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $completionPct }}%;" aria-valuenow="{{ $completionPct }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>

                        <!-- Current Submission Status -->
                        <div class="p-2.5 rounded-3 mb-3 d-flex align-items-center justify-content-between" style="background: rgba(0,0,0,0.02); border: 1px dashed var(--kaldis-border);">
                            <span class="small text-muted">
                                <i class="fas fa-paper-plane me-1 text-primary"></i> Report Submission Status:
                            </span>
                            @if($weeklyReport && $weeklyReport->status === 'SUBMITTED')
                                @if($weeklyReport->submission_status === 'ON_TIME')
                                    <span class="badge bg-success-subtle text-success fw-bold">
                                        <i class="fas fa-check-double me-1"></i> Submitted On-Time
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning fw-bold">
                                        <i class="fas fa-exclamation-triangle me-1"></i> Submitted Late
                                    </span>
                                @endif
                            @else
                                <span class="badge bg-secondary-subtle text-secondary fw-bold">
                                    <i class="fas fa-clock me-1"></i> In Progress (Due Monday)
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('weekly.performance') }}" class="btn btn-sm btn-primary flex-grow-1">
                            <i class="fas fa-check me-1"></i> Record Weekly Performance
                        </a>
                        <a href="{{ route('weekly.plans') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-list-check me-1"></i> Schedule Tasks
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Smart Metrics Snapshot Strip ── -->
    <div class="row g-3 mb-4">
        <!-- Metric 1: Week Tasks -->
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100" style="background: var(--kaldis-card-bg); border-radius: 12px;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                         style="width: 48px; height: 48px; background: rgba(37,99,235,0.1); color: var(--k-blue); font-size: 1.25rem;">
                        <i class="fas fa-list-check"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted small text-truncate">Week {{ $selectedWeek }} Tasks</div>
                        <div class="fs-5 fw-bold text-dark mb-0">
                            {{ $deptDoneCount }} <span class="text-muted fw-normal fs-6">/ {{ $deptTasksCount }}</span>
                            <span class="badge bg-primary-subtle text-primary ms-1" style="font-size: 0.72rem;">{{ $completionPct }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric 2: Monthly Plans -->
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100" style="background: var(--kaldis-card-bg); border-radius: 12px;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                         style="width: 48px; height: 48px; background: rgba(217,119,6,0.1); color: var(--k-gold); font-size: 1.25rem;">
                        <i class="fas fa-calendar-days"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted small text-truncate">{{ $selectedMonth }} Plans</div>
                        <div class="fs-5 fw-bold text-dark mb-0">
                            {{ $deptMonthlyPlansCount }} <span class="text-muted fw-normal fs-6">Plans</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric 3: Active Operational Units -->
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100" style="background: var(--kaldis-card-bg); border-radius: 12px;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                         style="width: 48px; height: 48px; background: rgba(16,185,129,0.1); color: var(--k-done); font-size: 1.25rem;">
                        <i class="fas fa-sitemap"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted small text-truncate">Active Departments</div>
                        <div class="fs-5 fw-bold text-dark mb-0">
                            {{ $totalDepartments }} <span class="text-muted fw-normal fs-6">Units</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric 4: Strategic Alignment -->
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100" style="background: var(--kaldis-card-bg); border-radius: 12px;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                         style="width: 48px; height: 48px; background: rgba(124,58,237,0.1); color: #7c3aed; font-size: 1.25rem;">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted small text-truncate">Annual Goals ({{ $period['year'] }})</div>
                        <div class="fs-5 fw-bold text-dark mb-0">
                            {{ $totalGoals }} <span class="text-muted fw-normal fs-6">Strategic</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Launchpad Section: Department Operations ── -->
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <span class="rounded-2 p-1.5 d-inline-flex align-items-center justify-content-center" style="background: rgba(37,99,235,0.1); color: #2563eb;">
                <i class="fas fa-layer-group"></i>
            </span>
            <h5 class="fw-bold text-dark mb-0">Department Operations Command</h5>
        </div>

        <div class="row g-3">
            <!-- Launchpad 1: Workspace -->
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('dashboard.department') }}" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift" 
                   style="background: var(--kaldis-card-bg); border-radius: 12px; transition: all 0.2s ease;">
                    <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                        <div>
                            <div class="rounded-3 d-inline-flex align-items-center justify-content-center mb-3" 
                                 style="width: 42px; height: 42px; background: rgba(37,99,235,0.1); color: #2563eb; font-size: 1.15rem;">
                                <i class="fas fa-building-user"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">My Workspace</h6>
                            <p class="text-muted small mb-0">Department overview, active tasks and deliverables.</p>
                        </div>
                        <div class="fw-semibold text-primary small mt-3">Open Hub &rarr;</div>
                    </div>
                </a>
            </div>

            <!-- Launchpad 2: Monthly Plans -->
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('monthly.plans') }}" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift" 
                   style="background: var(--kaldis-card-bg); border-radius: 12px; transition: all 0.2s ease;">
                    <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                        <div>
                            <div class="rounded-3 d-inline-flex align-items-center justify-content-center mb-3" 
                                 style="width: 42px; height: 42px; background: rgba(217,119,6,0.1); color: #d97706; font-size: 1.15rem;">
                                <i class="fas fa-calendar-days"></i>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <h6 class="fw-bold text-dark mb-1">Monthly Plans</h6>
                                <span class="badge bg-warning-subtle text-warning" style="font-size: 0.65rem;">Day 26 Cutoff</span>
                            </div>
                            <p class="text-muted small mb-0">Formulate initiatives, set targets &amp; submit package.</p>
                        </div>
                        <div class="fw-semibold text-warning small mt-3">Add Monthly Plan &rarr;</div>
                    </div>
                </a>
            </div>

            <!-- Launchpad 3: Weekly Tasks -->
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('weekly.plans') }}" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift" 
                   style="background: var(--kaldis-card-bg); border-radius: 12px; transition: all 0.2s ease;">
                    <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                        <div>
                            <div class="rounded-3 d-inline-flex align-items-center justify-content-center mb-3" 
                                 style="width: 42px; height: 42px; background: rgba(2,132,199,0.1); color: #0284c7; font-size: 1.15rem;">
                                <i class="fas fa-list-check"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Weekly Tasks</h6>
                            <p class="text-muted small mb-0">Assign tasks and plan team execution per week.</p>
                        </div>
                        <div class="fw-semibold text-info small mt-3">Schedule Tasks &rarr;</div>
                    </div>
                </a>
            </div>

            <!-- Launchpad 4: Weekly Performance -->
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('weekly.performance') }}" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift" 
                   style="background: var(--kaldis-card-bg); border-radius: 12px; transition: all 0.2s ease;">
                    <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                        <div>
                            <div class="rounded-3 d-inline-flex align-items-center justify-content-center mb-3" 
                                 style="width: 42px; height: 42px; background: rgba(16,185,129,0.1); color: #10b981; font-size: 1.15rem;">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Weekly Execution</h6>
                            <p class="text-muted small mb-0">Record DONE / NOT DONE and submit performance report.</p>
                        </div>
                        <div class="fw-semibold text-success small mt-3">Evaluate Results &rarr;</div>
                    </div>
                </a>
            </div>

            <!-- Launchpad 5: Official Monthly Voucher -->
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('monthly.voucher') }}" target="_blank" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift" 
                   style="background: var(--kaldis-card-bg); border-radius: 12px; transition: all 0.2s ease;">
                    <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                        <div>
                            <div class="rounded-3 d-inline-flex align-items-center justify-content-center mb-3" 
                                 style="width: 42px; height: 42px; background: rgba(245,158,11,0.12); color: #f59e0b; font-size: 1.15rem;">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Monthly Voucher</h6>
                            <p class="text-muted small mb-0">Audited printable official certificate with security token.</p>
                        </div>
                        <div class="fw-semibold text-warning small mt-3">View Official Voucher &rarr;</div>
                    </div>
                </a>
            </div>

            <!-- Launchpad 6: Official Weekly Voucher -->
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('weekly.voucher') }}" target="_blank" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift" 
                   style="background: var(--kaldis-card-bg); border-radius: 12px; transition: all 0.2s ease;">
                    <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                        <div>
                            <div class="rounded-3 d-inline-flex align-items-center justify-content-center mb-3" 
                                 style="width: 42px; height: 42px; background: rgba(16,185,129,0.12); color: #10b981; font-size: 1.15rem;">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Weekly Voucher</h6>
                            <p class="text-muted small mb-0">Official weekly execution confirmation certificate.</p>
                        </div>
                        <div class="fw-semibold text-success small mt-3">View Official Voucher &rarr;</div>
                    </div>
                </a>
            </div>

            <!-- Launchpad 7: Strategic Annual Goals -->
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('strategy.annual') }}" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift" 
                   style="background: var(--kaldis-card-bg); border-radius: 12px; transition: all 0.2s ease;">
                    <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                        <div>
                            <div class="rounded-3 d-inline-flex align-items-center justify-content-center mb-3" 
                                 style="width: 42px; height: 42px; background: rgba(124,58,237,0.1); color: #7c3aed; font-size: 1.15rem;">
                                <i class="fas fa-bullseye"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Strategy Map</h6>
                            <p class="text-muted small mb-0">Corporate annual goals and departmental alignments.</p>
                        </div>
                        <div class="fw-semibold text-purple small mt-3" style="color: #7c3aed;">Inspect Goals &rarr;</div>
                    </div>
                </a>
            </div>

            <!-- Launchpad 8: Why Not Done Analysis -->
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('performance.not_done') }}" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift" 
                   style="background: var(--kaldis-card-bg); border-radius: 12px; transition: all 0.2s ease;">
                    <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                        <div>
                            <div class="rounded-3 d-inline-flex align-items-center justify-content-center mb-3" 
                                 style="width: 42px; height: 42px; background: rgba(239,68,68,0.1); color: #ef4444; font-size: 1.15rem;">
                                <i class="fas fa-circle-exclamation"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Why Not Done</h6>
                            <p class="text-muted small mb-0">Root cause categorization and corrective plans.</p>
                        </div>
                        <div class="fw-semibold text-danger small mt-3">Analyze Root Causes &rarr;</div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- ── Launchpad Section: Executive Oversight & Administration ── -->
    @if($user->isGM() || $user->isSuperAdmin() || $user->isHROfficer())
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <span class="rounded-2 p-1.5 d-inline-flex align-items-center justify-content-center" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                <i class="fas fa-chart-line"></i>
            </span>
            <h5 class="fw-bold text-dark mb-0">Executive Management &amp; System Governance</h5>
        </div>

        <div class="row g-3">
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('dashboard.gm') }}" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift" 
                   style="background: var(--kaldis-card-bg); border-radius: 12px; transition: all 0.2s ease;">
                    <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                        <div>
                            <div class="rounded-3 d-inline-flex align-items-center justify-content-center mb-3" 
                                 style="width: 42px; height: 42px; background: rgba(245,158,11,0.15); color: #d97706; font-size: 1.15rem;">
                                <i class="fas fa-gauge"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">GM Dashboard</h6>
                            <p class="text-muted small mb-0">Executive oversight, KPI targets and Telegram alerts.</p>
                        </div>
                        <div class="fw-semibold text-warning small mt-3">Executive View &rarr;</div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('performance.weekly') }}" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift" 
                   style="background: var(--kaldis-card-bg); border-radius: 12px; transition: all 0.2s ease;">
                    <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                        <div>
                            <div class="rounded-3 d-inline-flex align-items-center justify-content-center mb-3" 
                                 style="width: 42px; height: 42px; background: rgba(37,99,235,0.1); color: #2563eb; font-size: 1.15rem;">
                                <i class="fas fa-chart-simple"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Weekly Scorecards</h6>
                            <p class="text-muted small mb-0">Company-wide departmental submission scorecard.</p>
                        </div>
                        <div class="fw-semibold text-primary small mt-3">View Scorecards &rarr;</div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('strategy.activation') }}" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift" 
                   style="background: var(--kaldis-card-bg); border-radius: 12px; transition: all 0.2s ease;">
                    <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                        <div>
                            <div class="rounded-3 d-inline-flex align-items-center justify-content-center mb-3" 
                                 style="width: 42px; height: 42px; background: rgba(16,185,129,0.1); color: #10b981; font-size: 1.15rem;">
                                <i class="fas fa-toggle-on"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Monthly Activation</h6>
                            <p class="text-muted small mb-0">Turn strategic goals active/inactive per month.</p>
                        </div>
                        <div class="fw-semibold text-success small mt-3">Activate Goals &rarr;</div>
                    </div>
                </a>
            </div>

            @if($user->isSuperAdmin())
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('admin.settings') }}" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift" 
                   style="background: var(--kaldis-card-bg); border-radius: 12px; transition: all 0.2s ease;">
                    <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                        <div>
                            <div class="rounded-3 d-inline-flex align-items-center justify-content-center mb-3" 
                                 style="width: 42px; height: 42px; background: rgba(100,116,139,0.15); color: #64748b; font-size: 1.15rem;">
                                <i class="fas fa-sliders"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">System Settings</h6>
                            <p class="text-muted small mb-0">Configure Day 26 deadlines, permissions and Telegram bot.</p>
                        </div>
                        <div class="fw-semibold text-secondary small mt-3">Manage Settings &rarr;</div>
                    </div>
                </a>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- ── Corporate Planning Lifecycle Roadmap ── -->
    <div class="card border-0 shadow-sm mb-4" style="background: var(--kaldis-card-bg); border-radius: 14px;">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
            <h6 class="fw-bold text-dark mb-1">
                <i class="fas fa-diagram-project text-primary me-2"></i> Kaldis Planning &amp; Execution Lifecycle Roadmap
            </h6>
            <p class="text-muted small mb-0">The 5-stage closed-loop corporate governance protocol.</p>
        </div>
        <div class="card-body p-4">
            <div class="row g-3 text-center">
                <!-- Step 1 -->
                <div class="col-md-2 col-6">
                    <div class="p-3 rounded-3 h-100" style="background: rgba(37,99,235,0.04); border: 1px solid rgba(37,99,235,0.12);">
                        <div class="badge bg-primary rounded-circle mb-2" style="width: 26px; height: 26px; line-height: 18px;">1</div>
                        <div class="fw-bold text-dark small mb-1">Annual Strategy</div>
                        <div class="text-muted" style="font-size: 0.72rem;">Executive goals defined for {{ $period['year'] }}</div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="col-md-2 col-6">
                    <div class="p-3 rounded-3 h-100" style="background: rgba(124,58,237,0.04); border: 1px solid rgba(124,58,237,0.12);">
                        <div class="badge bg-purple rounded-circle mb-2" style="width: 26px; height: 26px; line-height: 18px; background: #7c3aed;">2</div>
                        <div class="fw-bold text-dark small mb-1">Monthly Activation</div>
                        <div class="text-muted" style="font-size: 0.72rem;">GM prioritizes monthly goals</div>
                    </div>
                </div>

                <!-- Step 3 (Crucial Day 26) -->
                <div class="col-md-3 col-12">
                    <div class="p-3 rounded-3 h-100" style="background: rgba(217,119,6,0.06); border: 2px solid #d97706;">
                        <div class="badge bg-warning text-dark rounded-circle mb-2 fw-bold" style="width: 26px; height: 26px; line-height: 18px;">3</div>
                        <div class="fw-bold text-warning small mb-1">Monthly Plan Package</div>
                        <div class="fw-bold text-dark" style="font-size: 0.75rem;">Due: Day 26 Ethiopian (17:00)</div>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="col-md-2 col-6">
                    <div class="p-3 rounded-3 h-100" style="background: rgba(2,132,199,0.04); border: 1px solid rgba(2,132,199,0.12);">
                        <div class="badge bg-info rounded-circle mb-2 text-white" style="width: 26px; height: 26px; line-height: 18px;">4</div>
                        <div class="fw-bold text-dark small mb-1">Weekly Execution</div>
                        <div class="text-muted" style="font-size: 0.72rem;">Schedule &amp; record DONE / NOT DONE</div>
                    </div>
                </div>

                <!-- Step 5 -->
                <div class="col-md-3 col-6">
                    <div class="p-3 rounded-3 h-100" style="background: rgba(16,185,129,0.04); border: 1px solid rgba(16,185,129,0.12);">
                        <div class="badge bg-success rounded-circle mb-2" style="width: 26px; height: 26px; line-height: 18px;">5</div>
                        <div class="fw-bold text-dark small mb-1">Monday Reporting</div>
                        <div class="text-muted" style="font-size: 0.72rem;">Due Mon 12:00 PM &amp; Official Vouchers</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Recent Audit Activity Strip ── -->
    @if(isset($recentActivities) && $recentActivities->count() > 0)
    <div class="card border-0 shadow-sm mb-4" style="background: var(--kaldis-card-bg); border-radius: 14px;">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0">
                <i class="fas fa-clock-rotate-left text-muted me-2"></i> Recent System Activity
            </h6>
            @if($user->isSuperAdmin())
                <a href="{{ route('admin.audit') }}" class="text-decoration-none small fw-semibold text-primary">View Full Trail &rarr;</a>
            @endif
        </div>
        <div class="card-body p-4">
            <div class="list-group list-group-flush">
                @foreach($recentActivities as $act)
                    <div class="list-group-item bg-transparent px-0 py-2.5 d-flex justify-content-between align-items-center border-light">
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.72rem;">
                                {{ $act->action }}
                            </span>
                            <span class="text-dark small">
                                <strong>{{ $act->user->full_name ?? $act->user->username ?? 'System Operator' }}</strong>
                                <span class="text-muted ms-1">({{ $act->module }})</span>
                            </span>
                        </div>
                        <span class="text-muted small" title="{{ $act->created_at }}">
                            {{ format_ethiopian_datetime($act->created_at) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

</div>
@endsection

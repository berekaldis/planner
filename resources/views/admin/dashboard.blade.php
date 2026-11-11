@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
            <i class="fa-solid fa-gauge-high text-warning"></i> Administration & Control Center
        </h4>
        <p class="text-muted small mb-0">
            Active Planning Cycle: <strong class="text-dark">{{ $currentMonth }} {{ $currentYear }}</strong> &bull; System Governance, Health Telemetry & Operations
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.users') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5">
            <i class="fa-solid fa-users"></i> Users
        </a>
        <a href="{{ route('admin.departments') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5">
            <i class="fa-solid fa-building"></i> Departments
        </a>
        <a href="{{ route('admin.settings') }}" class="btn btn-sm btn-kaldis d-inline-flex align-items-center gap-1.5">
            <i class="fa-solid fa-sliders"></i> Settings
        </a>
    </div>
</div>

<!-- Row 1: Primary Enterprise Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="card p-3 border-0 shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">User Accounts</span>
                    <h3 class="fw-bold my-1 text-dark">{{ $userCount }}</h3>
                    <div class="d-flex align-items-center gap-1.5">
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-1.5 py-0.5" style="font-size: 0.68rem;">
                            <i class="fa-solid fa-circle-check me-0.5"></i> {{ $activeUserCount }} Active
                        </span>
                        <a href="{{ route('admin.users') }}" class="small text-decoration-none text-muted" style="font-size: 0.75rem;">Manage &rarr;</a>
                    </div>
                </div>
                <div class="p-2.5 bg-primary-subtle rounded-3 text-primary">
                    <i class="fa-solid fa-users fs-5"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card p-3 border-0 shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Operational Units</span>
                    <h3 class="fw-bold my-1 text-dark">{{ $deptCount }}</h3>
                    <div class="d-flex align-items-center gap-1.5">
                        <span class="badge bg-info-subtle text-info border border-info-subtle px-1.5 py-0.5" style="font-size: 0.68rem;">
                            {{ $deptsWithHead }} Assigned Heads
                        </span>
                        <a href="{{ route('admin.departments') }}" class="small text-decoration-none text-muted" style="font-size: 0.75rem;">Configure &rarr;</a>
                    </div>
                </div>
                <div class="p-2.5 bg-warning-subtle rounded-3 text-warning">
                    <i class="fa-solid fa-building fs-5"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card p-3 border-0 shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Master Annual Goals</span>
                    <h3 class="fw-bold my-1 text-dark">{{ $goalCount }}</h3>
                    <div class="d-flex align-items-center gap-1.5">
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-1.5 py-0.5" style="font-size: 0.68rem;">
                            {{ $activatedGoalsCount }} Active ({{ $currentMonth }})
                        </span>
                        <a href="{{ route('strategy.activation') }}" class="small text-decoration-none text-muted" style="font-size: 0.75rem;">Activation &rarr;</a>
                    </div>
                </div>
                <div class="p-2.5 bg-success-subtle rounded-3 text-success">
                    <i class="fa-solid fa-bullseye fs-5"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card p-3 border-0 shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Immutable Audit Events</span>
                    <h3 class="fw-bold my-1 text-dark">{{ number_format($auditCount) }}</h3>
                    <div class="d-flex align-items-center gap-1.5">
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-1.5 py-0.5" style="font-size: 0.68rem;">
                            Live Logging
                        </span>
                        <a href="{{ route('admin.audit') }}" class="small text-decoration-none text-muted" style="font-size: 0.75rem;">Audit Trail &rarr;</a>
                    </div>
                </div>
                <div class="p-2.5 bg-info-subtle rounded-3 text-info">
                    <i class="fa-solid fa-clock-rotate-left fs-5"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 2: Operational Health Pulse for Current Planning Cycle -->
<div class="row g-3 mb-4">
    <!-- Monthly Submissions Pulse -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small fw-semibold text-muted text-uppercase" style="font-size: 0.72rem;">Day 26 Monthly Submission</span>
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle font-monospace">{{ $currentMonth }}</span>
            </div>
            <div class="d-flex align-items-baseline gap-2 mb-2">
                <h4 class="fw-bold text-dark mb-0">{{ $monthlySubmittedCount }} / {{ $activeDeptCount }}</h4>
                <small class="text-muted">Departments Submitted</small>
            </div>
            @php
                $monthlyPct = $activeDeptCount > 0 ? round(($monthlySubmittedCount / $activeDeptCount) * 100) : 0;
            @endphp
            <div class="progress mb-2" style="height: 6px;">
                <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $monthlyPct }}%" aria-valuenow="{{ $monthlyPct }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="d-flex justify-content-between small text-muted">
                <span>{{ $monthlyPlansCount }} Total Plans Formulated</span>
                <span class="fw-semibold text-dark">{{ $monthlyPct }}% Submitted</span>
            </div>
        </div>
    </div>

    <!-- Weekly Execution Velocity -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small fw-semibold text-muted text-uppercase" style="font-size: 0.72rem;">Weekly Execution Rate</span>
                <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace">Done / Scheduled</span>
            </div>
            <div class="d-flex align-items-baseline gap-2 mb-2">
                <h4 class="fw-bold text-dark mb-0">{{ $executionRate }}%</h4>
                <small class="text-muted">({{ $weeklyDoneCount }} of {{ $weeklyTasksCount }} tasks completed)</small>
            </div>
            <div class="progress mb-2" style="height: 6px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $executionRate }}%" aria-valuenow="{{ $executionRate }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="d-flex justify-content-between small text-muted">
                <span>Monday 12:30 Reporting Rule</span>
                <a href="{{ route('weekly.performance') }}" class="text-decoration-none fw-semibold">View Execution &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Telegram Notifications Telemetry -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small fw-semibold text-muted text-uppercase" style="font-size: 0.72rem;">Telegram Automation</span>
                @if($telegramEnabled)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                        <i class="fa-solid fa-circle text-success me-1" style="font-size: 0.45rem;"></i> Active Bot
                    </span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Disabled</span>
                @endif
            </div>
            <div class="d-flex align-items-baseline gap-2 mb-2">
                <h4 class="fw-bold text-dark mb-0">{{ $telegramStats['subscribed_users'] }}</h4>
                <small class="text-muted">Users Linked via Chat ID</small>
            </div>
            <div class="d-flex align-items-center gap-3 small text-muted mb-2">
                <span><i class="fa-solid fa-paper-plane text-success me-1"></i> {{ $telegramStats['sent'] }} Dispatched</span>
                <span><i class="fa-solid fa-triangle-exclamation text-danger me-1"></i> {{ $telegramStats['failed'] }} Failed</span>
            </div>
            <div class="d-flex justify-content-between small text-muted">
                <span>Scheduled Cron Check</span>
                <a href="{{ route('telegram.status') }}" class="text-decoration-none fw-semibold">Telegram Hub &rarr;</a>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: Main Administrative Hub & Quick Launcher -->
<div class="row g-4 mb-4">
    <!-- Left: Departments Directory & Recent Audits -->
    <div class="col-lg-8">
        <!-- 1. Operational Units Leadership Coverage -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom border-light-subtle py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-warning-subtle text-warning rounded-2">
                        <i class="fa-solid fa-sitemap"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Department Leadership & Strategic Ownership</h6>
                        <small class="text-muted" style="font-size: 0.75rem;">Appointed department heads and active goals count</small>
                    </div>
                </div>
                <a href="{{ route('admin.departments') }}" class="btn btn-sm btn-outline-secondary">
                    Manage All ({{ $departments->count() }})
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3" style="width: 70px;">Code</th>
                            <th>Department Name</th>
                            <th>Appointed Department Head</th>
                            <th class="text-center" style="width: 110px;">Goals Linked</th>
                            <th class="text-end pe-3" style="width: 80px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $dept)
                            <tr>
                                <td class="ps-3 font-monospace fw-bold text-secondary">{{ $dept->department_code }}</td>
                                <td class="fw-semibold text-dark">{{ $dept->department_name }}</td>
                                <td>
                                    @if($dept->head)
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-warning bg-opacity-20 text-warning d-flex align-items-center justify-content-center fw-bold" style="width: 26px; height: 26px; font-size: 0.7rem;">
                                                {{ strtoupper(substr($dept->head->full_name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="fw-semibold text-dark" style="font-size: 0.8125rem;">{{ $dept->head->full_name }}</div>
                                                <small class="text-muted font-monospace" style="font-size: 0.7rem;">{{ $dept->head->email }}</small>
                                            </div>
                                        </div>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                            <i class="fa-solid fa-circle-exclamation me-1"></i> Vacant
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                                        {{ $dept->annualGoals->count() }} Goals
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    @if($dept->active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-0.5">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No departments configured.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2. Recent Audit Trail -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom border-light-subtle py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-primary-subtle text-primary rounded-2">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Recent System Activity Audit</h6>
                        <small class="text-muted" style="font-size: 0.75rem;">Immutable logging of administrative, authentication, and planning events</small>
                    </div>
                </div>
                <a href="{{ route('admin.audit') }}" class="btn btn-sm btn-outline-secondary">
                    View Full Trail &rarr;
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.8125rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3" style="width: 140px;">Timestamp</th>
                            <th style="width: 140px;">User</th>
                            <th style="width: 110px;">Module</th>
                            <th>Action & Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentAudits as $log)
                            <tr>
                                <td class="ps-3 text-muted font-monospace" style="font-size: 0.72rem;">
                                    {{ $log->created_at ? $log->created_at->format('M d, H:i:s') : '--' }}
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $log->user ? $log->user->full_name : ($log->username ?? 'System') }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.68rem;">
                                        {{ strtoupper($log->module ?? 'general') }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark" style="font-size: 0.775rem;">{{ $log->action }}</div>
                                    <div class="text-muted text-truncate" style="max-width: 320px; font-size: 0.72rem;" title="{{ $log->description }}">
                                        {{ $log->description }}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No audit events recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right: Quick Commands & System Environment -->
    <div class="col-lg-4">
        <!-- Quick Administrative Commands -->
        <div class="card border-0 shadow-sm p-3 mb-4 bg-white">
            <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                <i class="fa-solid fa-bolt-lightning text-warning"></i> Quick Actions
            </h6>
            <div class="d-flex flex-column gap-2">
                <a href="{{ route('admin.users') }}" class="btn btn-outline-secondary text-start p-2.5 d-flex align-items-center gap-3">
                    <div class="p-2 bg-primary-subtle text-primary rounded-2">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="fw-bold text-dark" style="font-size: 0.825rem;">Manage User Accounts</div>
                        <div class="text-muted small" style="font-size: 0.72rem;">Create, edit, reset passwords (Default: kaldis123)</div>
                    </div>
                </a>

                <a href="{{ route('admin.departments') }}" class="btn btn-outline-secondary text-start p-2.5 d-flex align-items-center gap-3">
                    <div class="p-2 bg-warning-subtle text-warning rounded-2">
                        <i class="fa-solid fa-building"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="fw-bold text-dark" style="font-size: 0.825rem;">Department Management</div>
                        <div class="text-muted small" style="font-size: 0.72rem;">Assign heads, edit codes, or remove departments</div>
                    </div>
                </a>

                <a href="{{ route('strategy.activation') }}" class="btn btn-outline-secondary text-start p-2.5 d-flex align-items-center gap-3">
                    <div class="p-2 bg-success-subtle text-success rounded-2">
                        <i class="fa-solid fa-toggle-on"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="fw-bold text-dark" style="font-size: 0.825rem;">GM Monthly Strategy Activation</div>
                        <div class="text-muted small" style="font-size: 0.72rem;">Authorize strategic plans for {{ $currentMonth }}</div>
                    </div>
                </a>

                <a href="{{ route('admin.settings') }}" class="btn btn-outline-secondary text-start p-2.5 d-flex align-items-center gap-3">
                    <div class="p-2 bg-secondary-subtle text-secondary rounded-2">
                        <i class="fa-solid fa-sliders"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="fw-bold text-dark" style="font-size: 0.825rem;">System Configuration & Matrix</div>
                        <div class="text-muted small" style="font-size: 0.72rem;">Planning cycle parameters & role permissions</div>
                    </div>
                </a>

                <a href="{{ route('telegram.status') }}" class="btn btn-outline-secondary text-start p-2.5 d-flex align-items-center gap-3">
                    <div class="p-2 bg-info-subtle text-info rounded-2">
                        <i class="fa-brands fa-telegram"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="fw-bold text-dark" style="font-size: 0.825rem;">Telegram Notification Diagnostics</div>
                        <div class="text-muted small" style="font-size: 0.72rem;">Test bot connection & trigger automated checks</div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Environment & System Parameters -->
        <div class="card border-0 shadow-sm p-3 bg-white">
            <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                <i class="fa-solid fa-server text-secondary"></i> System Telemetry
            </h6>
            <div class="d-flex flex-column gap-2.5 small">
                <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light-subtle">
                    <span class="text-muted">Corporate Email Domain</span>
                    <span class="badge bg-light text-dark border font-monospace">@kaldisbunna.et</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light-subtle">
                    <span class="text-muted">Default New User Password</span>
                    <span class="badge bg-light text-dark border font-monospace">kaldis123</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light-subtle">
                    <span class="text-muted">Active Planning Year</span>
                    <span class="fw-bold text-dark">{{ $currentYear }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light-subtle">
                    <span class="text-muted">Active Planning Month</span>
                    <span class="fw-bold text-dark">{{ $currentMonth }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light-subtle">
                    <span class="text-muted">Monthly Cutoff Deadline</span>
                    <span class="fw-bold text-dark">Day 26 (17:00 EAT)</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-1">
                    <span class="text-muted">Weekly Reporting Deadline</span>
                    <span class="fw-bold text-dark">Every Monday (12:30 PM)</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

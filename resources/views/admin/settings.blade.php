@extends('layouts.app')

@section('title', 'System Settings & Governance')

@section('content')
<div class="container-fluid py-2" style="max-width: 1200px; margin: 0 auto;">

    <!-- ── Header ── -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-sliders text-warning me-2"></i> System Settings &amp; Governance
            </h4>
            <p class="text-muted small mb-0">
                Configure corporate deadlines, planning cycles, Telegram reminder schedules, and role permissions.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border py-2 px-3 shadow-xs">
                <i class="far fa-calendar-alt text-warning me-1"></i> {{ format_et_calendar() }}
            </span>
            <span class="badge bg-light text-dark border py-2 px-3 shadow-xs">
                <i class="far fa-clock text-warning me-1"></i> {{ format_ethiopian_time() }}
            </span>
        </div>
    </div>

    <!-- ── Modern Navigation Tabs ── -->
    <ul class="nav nav-pills mb-4 gap-2 border-bottom pb-3" id="settingsTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-semibold" id="tab-policies-btn" data-bs-toggle="pill" data-bs-target="#tab-policies" type="button" role="tab">
                <i class="fas fa-calendar-check me-1.5 text-warning"></i> Deadlines &amp; Planning Policies
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="tab-reminders-btn" data-bs-toggle="pill" data-bs-target="#tab-reminders" type="button" role="tab">
                <i class="fab fa-telegram me-1.5 text-info"></i> Telegram Reminders Schedule
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="tab-permissions-btn" data-bs-toggle="pill" data-bs-target="#tab-permissions" type="button" role="tab">
                <i class="fas fa-user-shield me-1.5 text-success"></i> Role Permissions Matrix
            </button>
        </li>
        <li class="nav-item ms-auto" role="presentation">
            <a href="{{ route('telegram.status') }}" class="btn btn-sm btn-outline-info">
                <i class="fab fa-telegram me-1"></i> Bot Connection &amp; Tester
            </a>
        </li>
    </ul>

    <div class="tab-content" id="settingsTabContent">

        <!-- ══════════════════════════════════════════════════════════════
             TAB 1: DEADLINES & PLANNING POLICIES
        ══════════════════════════════════════════════════════════════ -->
        <div class="tab-pane fade show active" id="tab-policies" role="tabpanel">
            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                
                <div class="row g-4">
                    <!-- Column 1: Submission Deadlines & Planning Cycles -->
                    <div class="col-lg-6">
                        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px;">
                            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                                <h6 class="fw-bold text-dark mb-1">
                                    <i class="fas fa-clock text-warning me-2"></i> Ethiopian Submission Deadlines
                                </h6>
                                <p class="text-muted small mb-0">Define on-time cutoffs for Monthly Plans and Weekly Deliverables.</p>
                            </div>
                            <div class="card-body p-4 d-flex flex-column gap-3.5">

                                <!-- Monthly Plan Deadline -->
                                @php
                                    $savedMonthlyDay = (int)($settings['monthly_plan_deadline_day']->setting_value ?? $settings['monthly_deadline_day']->setting_value ?? 26);
                                    if ($savedMonthlyDay < 20 || $savedMonthlyDay > 30) { $savedMonthlyDay = 26; }
                                    $savedMonthlyTime = $settings['monthly_plan_deadline_time']->setting_value ?? '17:00';
                                @endphp
                                <div class="p-3.5 rounded-3 border border-warning-subtle bg-warning bg-opacity-10">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <label class="fw-bold text-dark small text-uppercase mb-0">
                                            <i class="fas fa-calendar-alt text-warning me-1.5"></i>
                                            Monthly Plan Submission Deadline
                                        </label>
                                        <span class="badge bg-warning text-dark fw-bold" id="monthlyTimeBadge">
                                            {{ format_time_with_ethiopian_lt($savedMonthlyTime) }}
                                        </span>
                                    </div>
                                    <p class="text-muted small mb-3">
                                        <strong>Governance Rule:</strong> The monthly plan package is due on <strong>Day 26 of the preceding Ethiopian month</strong> (e.g. <em>Hidar plan must be submitted by Tikimt 26</em>).
                                    </p>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold text-secondary">Preceding Month Cutoff Day</label>
                                            <select name="monthly_plan_deadline_day" class="form-select form-select-sm">
                                                @for($d = 20; $d <= 30; $d++)
                                                    <option value="{{ $d }}" {{ $savedMonthlyDay === $d ? 'selected' : '' }}>
                                                        Day {{ $d }} of Month {{ $d === 26 ? '(Standard: Day 26)' : '' }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold text-secondary">Cutoff Time (EAT / Western)</label>
                                            <input type="time" name="monthly_plan_deadline_time" id="monthlyTimeInput" class="form-control form-control-sm" 
                                                   value="{{ $savedMonthlyTime }}" required onchange="updateTimeBadge('monthlyTimeInput', 'monthlyTimeBadge')">
                                        </div>
                                    </div>
                                </div>

                                <!-- Weekly Plan & Performance Deadline -->
                                @php
                                    $savedWeeklyDay = $settings['weekly_deadline_day']->setting_value ?? 'Monday';
                                    $savedWeeklyTime = $settings['weekly_deadline_time']->setting_value ?? '12:30';
                                @endphp
                                <div class="p-3.5 rounded-3 border border-primary-subtle bg-primary bg-opacity-10">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <label class="fw-bold text-dark small text-uppercase mb-0">
                                            <i class="fas fa-tasks text-primary me-1.5"></i>
                                            Weekly Plan &amp; Performance Deadline
                                        </label>
                                        <span class="badge bg-primary text-white fw-bold" id="weeklyTimeBadge">
                                            {{ format_time_with_ethiopian_lt($savedWeeklyTime) }}
                                        </span>
                                    </div>
                                    <p class="text-muted small mb-3">
                                        <strong>Governance Rule:</strong> Weekly performance and next week's plan are always submitted together every <strong>{{ $savedWeeklyDay }} at 6:30 Ethiopian Local Time</strong> (12:30 PM Western / EAT) (e.g. <em>Week 1 Performance and Week 2 Plan submit on Week 2 Monday at 6:30 LT</em>).
                                    </p>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold text-secondary">Submission Day</label>
                                            <select name="weekly_deadline_day" class="form-select form-select-sm">
                                                @foreach(['Sunday', 'Monday', 'Tuesday'] as $day)
                                                    <option value="{{ $day }}" {{ $savedWeeklyDay === $day ? 'selected' : '' }}>{{ $day }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold text-secondary">Cutoff Time (EAT / Western)</label>
                                            <input type="time" name="weekly_deadline_time" id="weeklyTimeInput" class="form-control form-control-sm" 
                                                   value="{{ $savedWeeklyTime }}" required onchange="updateTimeBadge('weeklyTimeInput', 'weeklyTimeBadge')">
                                        </div>
                                    </div>
                                </div>

                                <!-- Active Ethiopian Planning Period -->
                                @php
                                    $savedYear = $settings['current_planning_year']->setting_value ?? 'AUTO';
                                    $savedMonth = $settings['current_planning_month']->setting_value ?? 'AUTO';
                                    $ethPeriod = get_ethiopian_period();
                                    $years = get_planning_years();
                                    $months = get_ethiopian_months();
                                @endphp
                                <div class="p-3.5 rounded-3 border border-secondary-subtle bg-light">
                                    <label class="fw-bold text-dark small text-uppercase mb-2">
                                        <i class="far fa-calendar text-secondary me-1.5"></i>
                                        Active Planning Period (Auto or Manual Override)
                                    </label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold text-secondary">Planning Year</label>
                                            <select name="current_planning_year" class="form-select form-select-sm">
                                                <option value="AUTO" {{ ($savedYear === 'AUTO' || empty($savedYear)) ? 'selected' : '' }}>
                                                    Auto: Ethiopian Calendar ({{ $ethPeriod['year'] }})
                                                </option>
                                                @foreach($years as $y)
                                                    <option value="{{ $y }}" {{ ($savedYear === $y) ? 'selected' : '' }}>{{ $y }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold text-secondary">Planning Month</label>
                                            <select name="current_planning_month" class="form-select form-select-sm">
                                                <option value="AUTO" {{ ($savedMonth === 'AUTO' || empty($savedMonth)) ? 'selected' : '' }}>
                                                    Auto: Ethiopian Calendar ({{ $ethPeriod['month'] }})
                                                </option>
                                                @foreach($months as $m)
                                                    <option value="{{ $m }}" {{ ($savedMonth === $m) ? 'selected' : '' }}>
                                                        {{ $m }}{{ $m === $ethPeriod['month'] ? ' (Active)' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Column 2: Governance & Operational Policies -->
                    <div class="col-lg-6">
                        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px;">
                            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                                <h6 class="fw-bold text-dark mb-1">
                                    <i class="fas fa-shield-halved text-success me-2"></i> Planning &amp; Operational Policies
                                </h6>
                                <p class="text-muted small mb-0">Control plan creation rules, voucher accessibility, and password policies.</p>
                            </div>
                            <div class="card-body p-4 d-flex flex-column gap-3.5">

                                <!-- Policy 1: Strategy Goal Requirement -->
                                @php
                                    $isStrategyMandatory = kaldis_setting('strategy_field_mandatory', 'true') === 'true';
                                @endphp
                                <div class="p-3 rounded-3 border {{ $isStrategyMandatory ? 'border-primary bg-primary bg-opacity-10' : 'border-secondary bg-light' }}">
                                    <div class="d-flex align-items-center justify-content-between mb-1.5">
                                        <label class="fw-bold text-dark small text-uppercase mb-0">
                                            <i class="fas fa-bullseye me-1.5 {{ $isStrategyMandatory ? 'text-primary' : 'text-secondary' }}"></i>
                                            Strategy Goal Field
                                        </label>
                                        <span class="badge {{ $isStrategyMandatory ? 'bg-primary text-white' : 'bg-secondary text-white' }} fw-bold">
                                            {{ $isStrategyMandatory ? 'Mandatory (True)' : 'Optional (False)' }}
                                        </span>
                                    </div>
                                    <p class="text-muted small mb-2.5">
                                        Determines whether linking an Annual Strategic Goal is required when department heads add Monthly and Weekly plans.
                                    </p>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="strategy_field_mandatory" id="stratMandatoryTrue" value="true" {{ $isStrategyMandatory ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-semibold" for="stratMandatoryTrue">Mandatory</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="strategy_field_mandatory" id="stratMandatoryFalse" value="false" {{ !$isStrategyMandatory ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-semibold" for="stratMandatoryFalse">Optional</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Policy 2: Prior Week Performance Requirement -->
                                @php
                                    $isLastWeekPerfMandatory = kaldis_setting('last_week_performance_mandatory', 'true') === 'true';
                                @endphp
                                <div class="p-3 rounded-3 border {{ $isLastWeekPerfMandatory ? 'border-primary bg-primary bg-opacity-10' : 'border-secondary bg-light' }}">
                                    <div class="d-flex align-items-center justify-content-between mb-1.5">
                                        <label class="fw-bold text-dark small text-uppercase mb-0">
                                            <i class="fas fa-chart-line me-1.5 {{ $isLastWeekPerfMandatory ? 'text-primary' : 'text-secondary' }}"></i>
                                            Prior Week Performance Check
                                        </label>
                                        <span class="badge {{ $isLastWeekPerfMandatory ? 'bg-primary text-white' : 'bg-secondary text-white' }} fw-bold">
                                            {{ $isLastWeekPerfMandatory ? 'Mandatory (True)' : 'Optional (False)' }}
                                        </span>
                                    </div>
                                    <p class="text-muted small mb-2.5">
                                        When enabled, evaluating the prior week's tasks is required before submitting the new weekly task plan.
                                    </p>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="last_week_performance_mandatory" id="lastWeekMandatoryTrue" value="true" {{ $isLastWeekPerfMandatory ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-semibold" for="lastWeekMandatoryTrue">Mandatory</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="last_week_performance_mandatory" id="lastWeekMandatoryFalse" value="false" {{ !$isLastWeekPerfMandatory ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-semibold" for="lastWeekMandatoryFalse">Optional</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Policy 3: Official Printable Vouchers -->
                                @php
                                    $isVoucherEnabled = kaldis_setting('voucher_enabled', 'true') === 'true';
                                @endphp
                                <div class="p-3 rounded-3 border {{ $isVoucherEnabled ? 'border-success bg-success bg-opacity-10' : 'border-secondary bg-light' }}">
                                    <div class="d-flex align-items-center justify-content-between mb-1.5">
                                        <label class="fw-bold text-dark small text-uppercase mb-0">
                                            <i class="fas fa-file-invoice me-1.5 {{ $isVoucherEnabled ? 'text-success' : 'text-secondary' }}"></i>
                                            Official Printable Vouchers
                                        </label>
                                        <span class="badge {{ $isVoucherEnabled ? 'bg-success text-white' : 'bg-secondary text-white' }} fw-bold">
                                            {{ $isVoucherEnabled ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </div>
                                    <p class="text-muted small mb-2.5">
                                        Controls whether official printable vouchers with security QR verification codes are accessible to users.
                                    </p>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="voucher_enabled" id="voucherEnabledTrue" value="true" {{ $isVoucherEnabled ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-semibold" for="voucherEnabledTrue">Enabled</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="voucher_enabled" id="voucherEnabledFalse" value="false" {{ !$isVoucherEnabled ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-semibold" for="voucherEnabledFalse">Disabled</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Policy 4: Force Password Change on First Login -->
                                @php
                                    $isForcePasswordChange = kaldis_setting('force_first_login_password_change', 'true') === 'true';
                                @endphp
                                <div class="p-3 rounded-3 border {{ $isForcePasswordChange ? 'border-warning bg-warning bg-opacity-10' : 'border-secondary bg-light' }}">
                                    <div class="d-flex align-items-center justify-content-between mb-1.5">
                                        <label class="fw-bold text-dark small text-uppercase mb-0">
                                            <i class="fas fa-key me-1.5 {{ $isForcePasswordChange ? 'text-warning' : 'text-secondary' }}"></i>
                                            First Login Password Change
                                        </label>
                                        <span class="badge {{ $isForcePasswordChange ? 'bg-warning text-dark' : 'bg-secondary text-white' }} fw-bold">
                                            {{ $isForcePasswordChange ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </div>
                                    <p class="text-muted small mb-2.5">
                                        Forces users logging in for the first time or with temporary default passwords to choose a personal password.
                                    </p>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="force_first_login_password_change" id="forcePwdTrue" value="true" {{ $isForcePasswordChange ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-semibold" for="forcePwdTrue">Enabled</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="force_first_login_password_change" id="forcePwdFalse" value="false" {{ !$isForcePasswordChange ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-semibold" for="forcePwdFalse">Disabled</label>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Unified Save Button -->
                <div class="card border-0 shadow-sm mt-4 p-3 bg-white" style="border-radius: 12px;">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        <div class="text-muted small">
                            <i class="fas fa-info-circle text-primary me-1"></i>
                            Changes take effect immediately across all planning cycles, vouchers, and compliance reports.
                        </div>
                        <button type="submit" class="btn btn-kaldis px-4 py-2 fw-bold shadow-sm">
                            <i class="fas fa-save me-1.5"></i> Save Settings &amp; Deadlines
                        </button>
                    </div>
                </div>

            </form>
        </div>

        <!-- ══════════════════════════════════════════════════════════════
             TAB 2: TELEGRAM REMINDERS SCHEDULE
        ══════════════════════════════════════════════════════════════ -->
        <div class="tab-pane fade" id="tab-reminders" role="tabpanel">
            <form method="POST" action="{{ route('admin.settings.update') }}#reminders">
                @csrf

                <div class="row g-4">
                    <!-- Monthly Reminders Card -->
                    <div class="col-lg-6">
                        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px;">
                            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">
                                        <i class="far fa-calendar-check text-warning me-2"></i> Monthly Plan Reminders
                                    </h6>
                                    <p class="text-muted small mb-0">Multi-stage reminders for Ethiopian monthly plan submission.</p>
                                </div>
                                @php
                                    $monthlyRemindersActive = kaldis_setting('enable_monthly_reminders', 'true') === 'true';
                                @endphp
                                <div class="form-check form-switch m-0">
                                    <input type="hidden" name="enable_monthly_reminders" value="false">
                                    <input class="form-check-input" type="checkbox" name="enable_monthly_reminders" value="true" id="enableMonthlyReminders" {{ $monthlyRemindersActive ? 'checked' : '' }} style="width: 2.2rem; height: 1.2rem;">
                                </div>
                            </div>
                            <div class="card-body p-4 d-flex flex-column gap-3">
                                
                                <div class="p-3 bg-light rounded-3">
                                    <label class="form-label small fw-bold text-secondary text-uppercase mb-1">Reminder Start Day</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white">Start From</span>
                                        <select name="monthly_reminder_start_day" class="form-select">
                                            @for($d = 20; $d <= 25; $d++)
                                                <option value="{{ $d }}" {{ (int)kaldis_setting('monthly_reminder_start_day', 25) === $d ? 'selected' : '' }}>
                                                    Day {{ $d }} of Month {{ $d === 25 ? '(Recommended: Day 25)' : '' }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="small text-muted mt-1">Bot begins alerting department heads with pending monthly plans.</div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold text-secondary">Day 25 Morning (1st)</label>
                                        <input type="time" name="monthly_reminder_day25_morning_time" class="form-control form-control-sm" 
                                               value="{{ kaldis_setting('monthly_reminder_day25_morning_time', '09:30') }}">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold text-secondary">Day 25 Afternoon (2nd)</label>
                                        <input type="time" name="monthly_reminder_day25_afternoon_time" class="form-control form-control-sm" 
                                               value="{{ kaldis_setting('monthly_reminder_day25_afternoon_time', '16:00') }}">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold text-secondary">Day 26 Morning (3rd Final)</label>
                                        <input type="time" name="monthly_reminder_day26_morning_time" class="form-control form-control-sm" 
                                               value="{{ kaldis_setting('monthly_reminder_day26_morning_time', '09:00') }}">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold text-secondary">Day 26 Afternoon (3-Hr Warning)</label>
                                        <input type="time" name="monthly_reminder_day26_afternoon_time" class="form-control form-control-sm" 
                                               value="{{ kaldis_setting('monthly_reminder_day26_afternoon_time', '14:00') }}">
                                    </div>
                                </div>

                                <div class="alert alert-light border small text-muted mb-0 mt-auto">
                                    <i class="fas fa-info-circle text-primary me-1"></i> Notifications are dispatched strictly to Department Heads with unsubmitted plans.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Weekly Reminders Card -->
                    <div class="col-lg-6">
                        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px;">
                            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">
                                        <i class="fas fa-clock text-primary me-2"></i> Weekly Plan &amp; Performance Reminders
                                    </h6>
                                    <p class="text-muted small mb-0">Multi-stage reminders for Monday weekly deliverables.</p>
                                </div>
                                @php
                                    $weeklyRemindersActive = kaldis_setting('enable_weekly_reminders', 'true') === 'true';
                                @endphp
                                <div class="form-check form-switch m-0">
                                    <input type="hidden" name="enable_weekly_reminders" value="false">
                                    <input class="form-check-input" type="checkbox" name="enable_weekly_reminders" value="true" id="enableWeeklyReminders" {{ $weeklyRemindersActive ? 'checked' : '' }} style="width: 2.2rem; height: 1.2rem;">
                                </div>
                            </div>
                            <div class="card-body p-4 d-flex flex-column gap-3">
                                
                                <div class="row g-2">
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold text-secondary">
                                            <i class="fas fa-sun text-warning me-1"></i> Saturday 1st Advance Reminder
                                        </label>
                                        <input type="time" name="weekly_reminder_saturday_time" class="form-control form-control-sm" 
                                               value="{{ kaldis_setting('weekly_reminder_saturday_time', '10:00') }}">
                                        <div class="small text-muted mt-0.5">Alerts heads to prepare past week evaluations and next week task schedule.</div>
                                    </div>

                                    <div class="col-12 mt-2">
                                        <label class="form-label small fw-semibold text-secondary">
                                            <i class="fas fa-moon text-secondary me-1"></i> Sunday 2nd Advance Reminder
                                        </label>
                                        <input type="time" name="weekly_reminder_sunday_time" class="form-control form-control-sm" 
                                               value="{{ kaldis_setting('weekly_reminder_sunday_time', '16:30') }}">
                                        <div class="small text-muted mt-0.5">Evening reminder before the Monday business cutoff.</div>
                                    </div>

                                    <div class="col-12 mt-2">
                                        <label class="form-label small fw-semibold text-secondary">
                                            <i class="fas fa-bell text-danger me-1"></i> Monday Morning Final Reminder (before 12:30 PM cutoff)
                                        </label>
                                        <input type="time" name="weekly_reminder_monday_time" class="form-control form-control-sm" 
                                               value="{{ kaldis_setting('weekly_reminder_monday_time', '08:30') }}">
                                        <div class="small text-muted mt-0.5">Final urgent alert dispatched prior to the 12:30 PM (6:30 LT) audit cutoff.</div>
                                    </div>
                                </div>

                                <div class="mt-auto">
                                    <button type="submit" class="btn btn-kaldis w-100 fw-bold py-2 shadow-sm">
                                        <i class="fas fa-save me-1"></i> Save Reminder Schedule
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </form>
        </div>

        <!-- ══════════════════════════════════════════════════════════════
             TAB 3: ROLE PERMISSIONS MATRIX
        ══════════════════════════════════════════════════════════════ -->
        <div class="tab-pane fade" id="tab-permissions" role="tabpanel">
            <form method="POST" action="{{ route('admin.settings.update') }}#permissions">
                @csrf
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-3 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                        <div>
                            <h5 class="fw-bold text-dark mb-1">
                                <i class="fas fa-user-shield text-success me-2"></i> Role Capabilities &amp; Permissions Matrix
                            </h5>
                            <p class="text-muted small mb-0">Toggle system access and feature actions per organizational role.</p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="submit" name="save_permissions" value="1" class="btn btn-sm btn-success fw-bold px-3 py-1.5 shadow-sm">
                                <i class="fas fa-check me-1"></i> Save Permissions
                            </button>
                            <button type="submit" name="reset_permissions" value="1" class="btn btn-sm btn-outline-danger fw-bold px-3 py-1.5" onclick="return confirm('Restore all role permissions to defaults?');">
                                <i class="fas fa-rotate-left me-1"></i> Reset Defaults
                            </button>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                                <thead class="table-light text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.04em;">
                                    <tr>
                                        <th style="width: 40%; padding-left: 1.5rem;">Permission Scope</th>
                                        <th class="text-center" style="width: 15%;">Super Admin</th>
                                        <th class="text-center" style="width: 15%;">General Manager</th>
                                        <th class="text-center" style="width: 15%;">Department Head</th>
                                        <th class="text-center" style="width: 15%; padding-right: 1.5rem;">IT Admin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($permissionMatrix as $category => $permissions)
                                        <tr style="background: rgba(0,0,0,0.025);">
                                            <td colspan="5" class="fw-bold text-dark py-2.5" style="padding-left: 1.5rem; font-size: 0.78rem;">
                                                <i class="fas fa-folder text-primary me-1.5"></i> {{ strtoupper($category) }}
                                            </td>
                                        </tr>
                                        @foreach($permissions as $permName => $data)
                                            @php
                                                $pSlug = \Illuminate\Support\Str::slug($permName, '_');
                                            @endphp
                                            <tr>
                                                <td style="padding-left: 1.5rem;">
                                                    <div class="fw-bold text-dark">{{ $permName }}</div>
                                                    <div class="text-muted small" style="font-size: 0.75rem;">{{ $data['desc'] }}</div>
                                                </td>
                                                @foreach(['super_admin', 'gm_management', 'dept_head', 'it_admin'] as $roleKey)
                                                    <td class="text-center {{ $loop->last ? 'pe-4' : '' }}">
                                                        <div class="d-flex justify-content-center">
                                                            <input type="hidden" name="permissions[{{ $roleKey }}][{{ $pSlug }}]" value="0">
                                                            <div class="form-check form-switch m-0">
                                                                <input class="form-check-input" 
                                                                       type="checkbox" 
                                                                       name="permissions[{{ $roleKey }}][{{ $pSlug }}]" 
                                                                       value="1" 
                                                                       {{ !empty($data[$roleKey]) ? 'checked' : '' }}
                                                                       style="cursor: pointer; width: 2.1rem; height: 1.15rem;">
                                                            </div>
                                                        </div>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </form>
        </div>

    </div>

</div>

<!-- ── Tab Navigation & Live Ethiopian Time Script ── -->
<script>
    function updateTimeBadge(inputId, badgeId) {
        const val = document.getElementById(inputId)?.value;
        if (!val) return;
        const [hStr, mStr] = val.split(':');
        const h = parseInt(hStr, 10);
        const m = parseInt(mStr, 10);
        let ethHour = (h >= 6) ? (h - 6) : (h + 6);
        if (ethHour === 0) ethHour = 12;
        const padM = String(m).padStart(2, '0');
        
        // 12-hour Western format
        const period = (h >= 12) ? 'PM' : 'AM';
        let westHour = h % 12;
        if (westHour === 0) westHour = 12;
        const westStr = `${westHour}:${padM} ${period}`;
        
        const badge = document.getElementById(badgeId);
        if (badge) {
            badge.textContent = `${ethHour}:${padM} LT (${westStr})`;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const hash = (window.location.hash || '').replace('#', '');
        if (hash === 'reminders') {
            const btn = document.getElementById('tab-reminders-btn');
            if (btn) new bootstrap.Tab(btn).show();
        } else if (hash === 'permissions') {
            const btn = document.getElementById('tab-permissions-btn');
            if (btn) new bootstrap.Tab(btn).show();
        }
    });
</script>
@endsection

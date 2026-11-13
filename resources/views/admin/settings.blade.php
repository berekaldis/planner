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
                Configure corporate planning policies, Telegram reminder schedules, deadlines, and role permissions.
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
                <i class="fas fa-shield-halved me-1.5 text-warning"></i> General &amp; Policies
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
             TAB 1: GENERAL POLICIES & PLANNING CYCLES
        ══════════════════════════════════════════════════════════════ -->
        <div class="tab-pane fade show active" id="tab-policies" role="tabpanel">
            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                
                <div class="row g-4">
                    <!-- Column 1: Core Governance Policies -->
                    <div class="col-lg-6">
                        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px;">
                            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                                <h6 class="fw-bold text-dark mb-1">
                                    <i class="fas fa-toggle-on text-primary me-2"></i> Planning &amp; Voucher Policies
                                </h6>
                                <p class="text-muted small mb-0">Control plan creation rules and voucher accessibility.</p>
                            </div>
                            <div class="card-body p-4 d-flex flex-column gap-4">

                                <!-- Policy 1: Strategy Goal Requirement (Mandatory vs Optional) -->
                                @php
                                    $isStrategyMandatory = kaldis_setting('strategy_field_mandatory', 'true') === 'true';
                                @endphp
                                <div class="p-3.5 rounded-3 border {{ $isStrategyMandatory ? 'border-primary bg-primary bg-opacity-10' : 'border-secondary bg-light' }}">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <label class="fw-bold text-dark small text-uppercase mb-0">
                                            <i class="fas fa-bullseye me-1.5 {{ $isStrategyMandatory ? 'text-primary' : 'text-secondary' }}"></i>
                                            Strategy Goal Field
                                        </label>
                                        <span class="badge {{ $isStrategyMandatory ? 'bg-primary text-white' : 'bg-secondary text-white' }} fw-bold">
                                            {{ $isStrategyMandatory ? 'Mandatory (True)' : 'Optional (False)' }}
                                        </span>
                                    </div>
                                    <p class="text-muted small mb-3">
                                        Determines whether linking an Annual Strategic Goal is required when department heads add Monthly and Weekly plans.
                                    </p>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="strategy_field_mandatory" id="stratMandatoryTrue" value="true" {{ $isStrategyMandatory ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-semibold" for="stratMandatoryTrue">
                                                <strong>Mandatory (True)</strong> &mdash; Strict alignment required
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="strategy_field_mandatory" id="stratMandatoryFalse" value="false" {{ !$isStrategyMandatory ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-semibold" for="stratMandatoryFalse">
                                                <strong>Optional (False)</strong> &mdash; Free-form plans allowed
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Policy 2: Official Voucher Enabled/Disabled -->
                                @php
                                    $isVoucherEnabled = kaldis_setting('voucher_enabled', 'true') === 'true';
                                @endphp
                                <div class="p-3.5 rounded-3 border {{ $isVoucherEnabled ? 'border-success bg-success bg-opacity-10' : 'border-danger bg-danger bg-opacity-10' }}">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <label class="fw-bold text-dark small text-uppercase mb-0">
                                            <i class="fas fa-file-invoice me-1.5 {{ $isVoucherEnabled ? 'text-success' : 'text-danger' }}"></i>
                                            Official Printable Vouchers
                                        </label>
                                        <span class="badge {{ $isVoucherEnabled ? 'bg-success text-white' : 'bg-danger text-white' }} fw-bold">
                                            {{ $isVoucherEnabled ? 'Enabled (True)' : 'Disabled (False)' }}
                                        </span>
                                    </div>
                                    <p class="text-muted small mb-3">
                                        Controls whether official printable vouchers with security QR codes are visible and accessible to users.
                                    </p>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="voucher_enabled" id="voucherEnabledTrue" value="true" {{ $isVoucherEnabled ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-semibold" for="voucherEnabledTrue">
                                                <strong>Enabled (True)</strong> &mdash; Vouchers visible &amp; printable
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="voucher_enabled" id="voucherEnabledFalse" value="false" {{ !$isVoucherEnabled ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-semibold" for="voucherEnabledFalse">
                                                <strong>Disabled (False)</strong> &mdash; Hide voucher links &amp; buttons
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Policy 3: Force Password Change on First Login -->
                                @php
                                    $isForcePasswordChange = kaldis_setting('force_first_login_password_change', 'true') === 'true';
                                @endphp
                                <div class="p-3.5 rounded-3 border {{ $isForcePasswordChange ? 'border-warning bg-warning bg-opacity-10' : 'border-secondary bg-light' }}">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <label class="fw-bold text-dark small text-uppercase mb-0">
                                            <i class="fas fa-key me-1.5 {{ $isForcePasswordChange ? 'text-warning' : 'text-secondary' }}"></i>
                                            First Login Password Change
                                        </label>
                                        <span class="badge {{ $isForcePasswordChange ? 'bg-warning text-dark' : 'bg-secondary text-white' }} fw-bold">
                                            {{ $isForcePasswordChange ? 'Enabled (True)' : 'Disabled (False)' }}
                                        </span>
                                    </div>
                                    <p class="text-muted small mb-3">
                                        Forces users logging in for the first time or with temporary default passwords (<code class="fw-bold">kaldis123</code>) to set a new personal password before accessing the system.
                                    </p>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="force_first_login_password_change" id="forcePwdTrue" value="true" {{ $isForcePasswordChange ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-semibold" for="forcePwdTrue">
                                                <strong>Enabled (True)</strong> &mdash; Mandatory password change
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="force_first_login_password_change" id="forcePwdFalse" value="false" {{ !$isForcePasswordChange ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-semibold" for="forcePwdFalse">
                                                <strong>Disabled (False)</strong> &mdash; Allow direct dashboard access
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-auto">
                                    <button type="submit" class="btn btn-kaldis w-100 fw-bold py-2 shadow-sm">
                                        <i class="fas fa-save me-1"></i> Save Policies
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Column 2: Planning Cycles & Submission Deadlines -->
                    <div class="col-lg-6">
                        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px;">
                            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                                <h6 class="fw-bold text-dark mb-1">
                                    <i class="fas fa-calendar-alt text-warning me-2"></i> Ethiopian Planning Cycles &amp; Deadlines
                                </h6>
                                <p class="text-muted small mb-0">Active corporate operating period and cutoff times.</p>
                            </div>
                            <div class="card-body p-4 d-flex flex-column gap-3">
                                @php
                                    $savedYear = $settings['current_planning_year']->setting_value ?? 'AUTO';
                                    $savedMonth = $settings['current_planning_month']->setting_value ?? 'AUTO';
                                    $ethPeriod = get_ethiopian_period();
                                    $years = get_planning_years();
                                    $months = get_ethiopian_months();
                                @endphp
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-secondary">ACTIVE PLANNING YEAR</label>
                                        <select name="current_planning_year" class="form-select">
                                            <option value="AUTO" {{ ($savedYear === 'AUTO' || empty($savedYear)) ? 'selected' : '' }}>
                                                Auto: Ethiopian Calendar ({{ $ethPeriod['year'] }})
                                            </option>
                                            @foreach($years as $y)
                                                <option value="{{ $y }}" {{ ($savedYear === $y) ? 'selected' : '' }}>{{ $y }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-secondary">ACTIVE PLANNING MONTH</label>
                                        <select name="current_planning_month" class="form-select">
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

                                <hr class="my-2 border-light-subtle">

                                <!-- Monthly Plan Deadline -->
                                <div>
                                    <label class="form-label small fw-bold text-secondary text-uppercase">Monthly Plan Submission Deadline</label>
                                    @php
                                        $savedMonthlyDay = (int)($settings['monthly_plan_deadline_day']->setting_value ?? $settings['monthly_deadline_day']->setting_value ?? 26);
                                        if ($savedMonthlyDay < 20 || $savedMonthlyDay > 30) { $savedMonthlyDay = 26; }
                                    @endphp
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <select name="monthly_plan_deadline_day" class="form-select form-select-sm">
                                                @for($d = 20; $d <= 30; $d++)
                                                    <option value="{{ $d }}" {{ $savedMonthlyDay === $d ? 'selected' : '' }}>
                                                        Day {{ $d }} of Month {{ $d === 26 ? '(Standard)' : '' }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <input type="time" name="monthly_plan_deadline_time" class="form-control form-control-sm" 
                                                   value="{{ $settings['monthly_plan_deadline_time']->setting_value ?? '17:00' }}" required>
                                        </div>
                                    </div>
                                    <div class="small text-muted mt-1">Default: Day 26 at 17:00 (11:00 Ethiopian Local Time).</div>
                                </div>

                                <!-- Weekly Report Deadline -->
                                <div>
                                    <label class="form-label small fw-bold text-secondary text-uppercase">Weekly Performance Report Deadline</label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <select name="weekly_deadline_day" class="form-select form-select-sm">
                                                @foreach(['Sunday', 'Monday', 'Tuesday'] as $day)
                                                    <option value="{{ $day }}" {{ ($settings['weekly_deadline_day']->setting_value ?? 'Monday') === $day ? 'selected' : '' }}>{{ $day }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <input type="time" name="weekly_deadline_time" class="form-control form-control-sm" 
                                                   value="{{ $settings['weekly_deadline_time']->setting_value ?? '12:30' }}" required>
                                        </div>
                                    </div>
                                    <div class="small text-muted mt-1">Default: Monday at 12:30 PM (6:30 Ethiopian Midday Local Time).</div>
                                </div>

                            </div>
                        </div>
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
                                        <div class="small text-muted mt-0.5">Final urgent alert dispatched 4 hours prior to the 12:30 PM (6:30 LT) audit cutoff.</div>
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

<!-- ── Tab Navigation Script ── -->
<script>
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

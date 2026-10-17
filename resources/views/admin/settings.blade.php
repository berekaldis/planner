@extends('layouts.app')

@section('title', 'System Settings & Role Permissions')

@section('content')
<div class="container-fluid py-2" style="max-width: 1320px; margin: 0 auto;">

    <!-- ── React-Style Pill Navigation Switcher ── -->
    <div class="react-nav-pills mb-4 d-flex flex-wrap gap-2">
        <button class="nav-link active" id="btn-tab-parameters" onclick="switchSettingsTab('parameters', this)">
            <i class="fas fa-sliders-h text-warning me-1"></i> System Parameters &amp; Deadlines
        </button>
        <button class="nav-link" id="btn-tab-permissions" onclick="switchSettingsTab('permissions', this)">
            <i class="fas fa-shield-halved text-success me-1"></i> Role Permissions Matrix
        </button>
        <button class="nav-link" id="btn-tab-automation" onclick="switchSettingsTab('automation', this)">
            <i class="fas fa-clock-rotate-left text-primary me-1"></i> Scheduled Deadlines &amp; Automation
        </button>
        <a href="{{ route('telegram.status') }}" class="nav-link">
            <i class="fab fa-telegram text-info me-1"></i> Telegram Bot &amp; Webhook
        </a>
    </div>

    <!-- ── Page Header ── -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-sliders text-warning me-2"></i> System Settings &amp; Permissions
            </h4>
            <p class="text-muted small mb-0">
                Corporate reporting deadlines, Ethiopian calendar cycles, role capabilities, and automated Telegram governance.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge period-badge py-2 px-3 shadow-xs">
                <i class="far fa-calendar-alt text-warning me-1"></i> {{ format_et_calendar() }}
            </span>
            <span class="badge period-badge py-2 px-3 shadow-xs" title="Ethiopian Time (East Africa Time)">
                <i class="far fa-clock text-warning me-1"></i> {{ format_ethiopian_time() }}
            </span>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         TAB 1: SYSTEM PARAMETERS & DEADLINES
    ══════════════════════════════════════════════════════════════ -->
    <div id="tab-parameters" class="settings-tab-pane">
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            
            <div class="row g-4 mb-4">
                <!-- Left Column: Core Deadline Parameters -->
                <div class="col-lg-8">
                    <!-- Planning Period Card -->
                    <div class="card border-0 shadow-sm mb-4" style="background: var(--kaldis-card-bg); border-radius: 14px;">
                        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                            <span class="fs-6 fw-bold text-dark">
                                <i class="fas fa-calendar-days text-warning me-2"></i> Ethiopian Planning Cycles
                            </span>
                            <p class="text-muted small mb-0">Configure the active corporate planning year and monthly operating period.</p>
                        </div>
                        <div class="card-body p-4">
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
                                            Auto: Ethiopian Calendar (Currently {{ $ethPeriod['year'] }})
                                        </option>
                                        @foreach($years as $y)
                                            <option value="{{ $y }}" {{ ($savedYear === $y) ? 'selected' : '' }}>{{ $y }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text text-muted small">
                                        Active strategic planning year in Ethiopian Calendar (e.g. 2019 E.C.).
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">ACTIVE PLANNING MONTH</label>
                                    <select name="current_planning_month" class="form-select">
                                        <option value="AUTO" {{ ($savedMonth === 'AUTO' || empty($savedMonth)) ? 'selected' : '' }}>
                                            Auto: Ethiopian Calendar (Currently {{ $ethPeriod['month'] }})
                                        </option>
                                        @foreach($months as $m)
                                            <option value="{{ $m }}" {{ ($savedMonth === $m) ? 'selected' : '' }}>
                                                {{ $m }}{{ $m === $ethPeriod['month'] ? ' (Active Month)' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text text-muted small">
                                        Rolls over automatically when Ethiopian month elapses (e.g. to Hidar).
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submission Deadlines Card -->
                    <div class="card border-0 shadow-sm mb-4" style="background: var(--kaldis-card-bg); border-radius: 14px;">
                        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                            <span class="fs-6 fw-bold text-dark">
                                <i class="fas fa-hourglass-half text-primary me-2"></i> Operational Submission Deadlines
                            </span>
                            <p class="text-muted small mb-0">Enforce strict cutoff boundaries for monthly packages and weekly performance reports.</p>
                        </div>
                        <div class="card-body p-4">
                            <!-- Monthly Plan Deadline (Day 26 Ethiopian Calendar) -->
                            <div class="p-3.5 rounded-3 mb-4" style="background: rgba(217,119,6,0.05); border: 1.5px solid rgba(217,119,6,0.25);">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="fw-bold text-warning small text-uppercase">
                                        <i class="far fa-calendar-check me-1"></i> Monthly Plan Submission Due Day (Ethiopian Calendar)
                                    </div>
                                    <span class="badge bg-warning text-dark fw-bold">Official Corporate Rule</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-secondary">MONTHLY CUTOFF DAY</label>
                                        @php
                                            $savedMonthlyDay = (int)($settings['monthly_plan_deadline_day']->setting_value ?? $settings['monthly_deadline_day']->setting_value ?? 26);
                                        @endphp
                                        <select name="monthly_plan_deadline_day" class="form-select">
                                            @for($d = 1; $d <= 30; $d++)
                                                <option value="{{ $d }}" {{ $savedMonthlyDay === $d ? 'selected' : '' }}>
                                                    Day {{ $d }} of Ethiopian Month {{ $d === 26 ? '★ (Official Due Date: Day 26)' : '' }}
                                                </option>
                                            @endfor
                                        </select>
                                        <div class="form-text text-muted small">
                                            Default standard: <strong>Day 26 in Ethiopian calendar</strong>.
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-secondary">MONTHLY CUTOFF TIME (24H)</label>
                                        <input type="time" name="monthly_plan_deadline_time" class="form-control" 
                                               value="{{ $settings['monthly_plan_deadline_time']->setting_value ?? '17:00' }}" required>
                                        <div class="form-text text-muted small">
                                            Cutoff time: <strong>17:00 (11:00 Ethiopian Afternoon)</strong>.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Weekly Report Deadline -->
                            <div class="p-3.5 rounded-3" style="background: rgba(37,99,235,0.05); border: 1.5px solid rgba(37,99,235,0.25);">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="fw-bold text-primary small text-uppercase">
                                        <i class="fas fa-clock me-1"></i> Weekly Performance Report Deadline
                                    </div>
                                    <span class="badge bg-primary text-white fw-bold">Executive Audit</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-secondary">WEEKLY REPORTING DAY</label>
                                        <select name="weekly_deadline_day" class="form-select">
                                            @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day)
                                                <option value="{{ $day }}" {{ ($settings['weekly_deadline_day']->setting_value ?? 'Monday') === $day ? 'selected' : '' }}>{{ $day }}</option>
                                            @endforeach
                                        </select>
                                        <div class="form-text text-muted small">
                                            Weekly submission due day (default: <strong>Monday</strong>).
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-secondary">WEEKLY CUTOFF TIME (24H)</label>
                                        <input type="time" name="weekly_deadline_time" class="form-control" 
                                               value="{{ $settings['weekly_deadline_time']->setting_value ?? '12:00' }}" required>
                                        <div class="form-text text-muted small">
                                            Weekly audit cutoff (default: <strong>12:00 PM</strong>).
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Escalation Rules Card -->
                    <div class="card border-0 shadow-sm mb-4" style="background: var(--kaldis-card-bg); border-radius: 14px;">
                        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                            <span class="fs-6 fw-bold text-dark">
                                <i class="fas fa-triangle-exclamation text-danger me-2"></i> Non-Submission Escalation Rules
                            </span>
                            <p class="text-muted small mb-0">Multi-tier alerting system when weekly deadlines are breached.</p>
                        </div>
                        <div class="card-body p-4">
                            <div class="alert alert-light border small mb-3">
                                <div class="fw-bold mb-1"><i class="fas fa-info-circle text-primary me-1"></i> Three-Tier Escalation Protocol:</div>
                                <ul class="mb-0 text-muted ps-3">
                                    <li><strong>Level 1 (1 missed week):</strong> Automated warning delivered to Department Head via Telegram.</li>
                                    <li><strong>Level 2 (2 missed weeks):</strong> Formal non-compliance notification to Human Resources.</li>
                                    <li><strong>Level 3 (3+ missed weeks):</strong> High-priority executive escalation to General Management (GM).</li>
                                </ul>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary">HR ESCALATION RECIPIENT(S)</label>
                                    <input type="text" name="escalation_recipients_hr" class="form-control" 
                                           value="{{ $settings['escalation_recipients_hr']->setting_value ?? 'hr@kaldiscoffee.com' }}">
                                    <div class="form-text text-muted small">Email or Telegram chat IDs for Level 2 alerts.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary">GM ESCALATION RECIPIENT(S)</label>
                                    <input type="text" name="escalation_recipients_gm" class="form-control" 
                                           value="{{ $settings['escalation_recipients_gm']->setting_value ?? 'gm@kaldiscoffee.com' }}">
                                    <div class="form-text text-muted small">Executive recipients for Level 3 alerts.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Telegram & Actions -->
                <div class="col-lg-4">
                    <!-- Telegram Credentials -->
                    <div class="card border-0 shadow-sm mb-4" style="background: var(--kaldis-card-bg); border-radius: 14px;">
                        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                            <span class="fs-6 fw-bold text-dark">
                                <i class="fab fa-telegram text-info me-2"></i> Telegram Bot API
                            </span>
                            <p class="text-muted small mb-0">Bot authentication credentials.</p>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">TELEGRAM BOT TOKEN</label>
                                <input type="password" name="telegram_bot_token" class="form-control font-monospace" 
                                       value="{{ $settings['telegram_bot_token']->setting_value ?? '' }}" 
                                       placeholder="e.g. 123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ">
                                <div class="form-text text-muted small">Issued by <code>@BotFather</code> on Telegram.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">BOT USERNAME</label>
                                <input type="text" name="telegram_bot_username" class="form-control font-monospace" 
                                       value="{{ $settings['telegram_bot_username']->setting_value ?? 'KaldisPlannerBot' }}" 
                                       placeholder="KaldisPlannerBot">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">WEBHOOK URL</label>
                                <input type="url" name="telegram_webhook_url" class="form-control font-monospace" 
                                       value="{{ $settings['telegram_webhook_url']->setting_value ?? url('/telegram/webhook') }}" 
                                       placeholder="https://domain.com/telegram/webhook">
                                <div class="form-text text-muted small">Public HTTPS endpoint for Telegram webhooks.</div>
                            </div>

                            <a href="{{ route('telegram.status') }}" class="btn btn-outline-info btn-sm w-100 fw-semibold">
                                <i class="fas fa-stethoscope me-1"></i> Open Telegram Diagnostics
                            </a>
                        </div>
                    </div>

                    <!-- Save Action Card -->
                    <div class="card border-0 shadow-sm mb-4 sticky-top" style="top: 80px; background: var(--kaldis-card-bg); border-radius: 14px;">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-dark mb-2">Apply Configuration</h6>
                            <p class="text-muted small mb-3">Changes take effect immediately across all planning and voucher generation systems.</p>
                            <button type="submit" class="btn btn-kaldis w-100 py-2.5 fw-bold shadow-sm">
                                <i class="fas fa-save me-1"></i> Save System Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         TAB 2: ROLE-BASED ACCESS CONTROL & ALL PERMISSIONS MATRIX
    ══════════════════════════════════════════════════════════════ -->
    <div id="tab-permissions" class="settings-tab-pane d-none">
        
        <!-- Role Identity Cards Strip -->
        <div class="row g-3 mb-4">
            @foreach($roles as $r)
                @php
                    $roleBadges = [
                        'super_admin' => ['color' => '#dc2626', 'bg' => 'rgba(220,38,38,0.1)', 'icon' => 'fa-crown', 'scope' => 'Enterprise (Company-Wide)'],
                        'gm_management' => ['color' => '#d97706', 'bg' => 'rgba(217,119,6,0.1)', 'icon' => 'fa-user-tie', 'scope' => 'Enterprise (Executive)'],
                        'hr' => ['color' => '#2563eb', 'bg' => 'rgba(37,99,235,0.1)', 'icon' => 'fa-users', 'scope' => 'Enterprise (Compliance)'],
                        'dept_head' => ['color' => '#10b981', 'bg' => 'rgba(16,185,129,0.1)', 'icon' => 'fa-building-user', 'scope' => 'Assigned Department Only'],
                        'it_admin' => ['color' => '#7c3aed', 'bg' => 'rgba(124,58,237,0.1)', 'icon' => 'fa-screwdriver-wrench', 'scope' => 'System Administration'],
                    ];
                    $meta = $roleBadges[strtolower($r->name)] ?? ['color' => '#64748b', 'bg' => 'rgba(100,116,139,0.1)', 'icon' => 'fa-shield-halved', 'scope' => 'Custom Scope'];
                @endphp
                <div class="col-md-6 col-lg">
                    <div class="card border-0 shadow-sm h-100" style="background: var(--kaldis-card-bg); border-radius: 12px; border-top: 3px solid {{ $meta['color'] }} !important;">
                        <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                         style="width: 36px; height: 36px; background: {{ $meta['bg'] }}; color: {{ $meta['color'] }}; font-size: 1rem;">
                                        <i class="fas {{ $meta['icon'] }}"></i>
                                    </div>
                                    <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.7rem;">
                                        {{ $r->users_count }} Users
                                    </span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1" style="font-size: 0.95rem;">{{ $r->display_name }}</h6>
                                <div class="badge px-2 py-0.5 mb-2" style="background: {{ $meta['bg'] }}; color: {{ $meta['color'] }}; font-size: 0.68rem; font-weight: 600;">
                                    {{ $meta['scope'] }}
                                </div>
                                <p class="text-muted small mb-0" style="font-size: 0.78rem; line-height: 1.4;">
                                    {{ $r->description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Master Permissions Matrix Table -->
        <div class="card border-0 shadow-sm mb-4" style="background: var(--kaldis-card-bg); border-radius: 14px; overflow: hidden;">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                <div>
                    <h5 class="fw-bold text-dark mb-1">
                        <i class="fas fa-list-check text-success me-2"></i> Comprehensive Permissions Matrix
                    </h5>
                    <p class="text-muted small mb-0">Full catalogue of 25 system capabilities mapped across all user roles.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success-subtle text-success py-1.5 px-2.5">
                        <i class="fas fa-check me-1"></i> Authorized
                    </span>
                    <span class="badge bg-light text-muted border py-1.5 px-2.5">
                        <i class="fas fa-minus me-1"></i> Restricted
                    </span>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.86rem;">
                        <thead class="table-light text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.04em;">
                            <tr>
                                <th style="width: 32%; padding-left: 1.5rem;">Permission &amp; Responsibility Scope</th>
                                <th class="text-center" style="width: 13.6%;">Super Admin</th>
                                <th class="text-center" style="width: 13.6%;">General Manager</th>
                                <th class="text-center" style="width: 13.6%;">HR Officer</th>
                                <th class="text-center" style="width: 13.6%;">Dept Head</th>
                                <th class="text-center" style="width: 13.6%; padding-right: 1.5rem;">IT Admin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($permissionMatrix as $category => $permissions)
                                <!-- Category Section Divider -->
                                <tr style="background: rgba(0,0,0,0.025);">
                                    <td colspan="6" class="fw-bold text-dark py-2.5" style="padding-left: 1.5rem; font-size: 0.8rem; letter-spacing: 0.02em;">
                                        <i class="fas fa-folder-open text-primary me-2"></i> {{ strtoupper($category) }}
                                    </td>
                                </tr>

                                @foreach($permissions as $permName => $data)
                                    <tr>
                                        <td style="padding-left: 1.5rem;">
                                            <div class="fw-bold text-dark">{{ $permName }}</div>
                                            <div class="text-muted small" style="font-size: 0.76rem; line-height: 1.3;">{{ $data['desc'] }}</div>
                                        </td>
                                        <!-- Super Admin -->
                                        <td class="text-center">
                                            @if($data['super_admin'])
                                                <span class="badge bg-success-subtle text-success py-1 px-2"><i class="fas fa-check"></i></span>
                                            @else
                                                <span class="text-muted opacity-25">&mdash;</span>
                                            @endif
                                        </td>
                                        <!-- GM -->
                                        <td class="text-center">
                                            @if($data['gm_management'])
                                                <span class="badge bg-success-subtle text-success py-1 px-2"><i class="fas fa-check"></i></span>
                                            @else
                                                <span class="text-muted opacity-25">&mdash;</span>
                                            @endif
                                        </td>
                                        <!-- HR -->
                                        <td class="text-center">
                                            @if($data['hr'])
                                                <span class="badge bg-success-subtle text-success py-1 px-2"><i class="fas fa-check"></i></span>
                                            @else
                                                <span class="text-muted opacity-25">&mdash;</span>
                                            @endif
                                        </td>
                                        <!-- Dept Head -->
                                        <td class="text-center">
                                            @if($data['dept_head'])
                                                <span class="badge bg-success-subtle text-success py-1 px-2"><i class="fas fa-check"></i></span>
                                            @else
                                                <span class="text-muted opacity-25">&mdash;</span>
                                            @endif
                                        </td>
                                        <!-- IT Admin -->
                                        <td class="text-center" style="padding-right: 1.5rem;">
                                            @if($data['it_admin'])
                                                <span class="badge bg-success-subtle text-success py-1 px-2"><i class="fas fa-check"></i></span>
                                            @else
                                                <span class="text-muted opacity-25">&mdash;</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card-footer bg-light p-3 small text-muted d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span>
                    <i class="fas fa-lock text-secondary me-1"></i>
                    All permissions are strictly enforced server-side via Laravel middleware guards and cryptographic token validation.
                </span>
                <span class="badge bg-secondary-subtle text-secondary font-monospace">Security Standard RBAC v2.4</span>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         TAB 3: SCHEDULED DEADLINES & AUTOMATION
    ══════════════════════════════════════════════════════════════ -->
    <div id="tab-automation" class="settings-tab-pane d-none">
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <!-- Automation Schedule Card -->
                <div class="card border-0 shadow-sm mb-4" style="background: var(--kaldis-card-bg); border-radius: 14px;">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                        <span class="fs-6 fw-bold text-dark">
                            <i class="fas fa-clock-rotate-left text-primary me-2"></i> Automated Cron Schedules &amp; Reminders
                        </span>
                        <p class="text-muted small mb-0">System daemon tasks executed via Laravel Scheduler &amp; cron.</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                                <thead class="table-light small text-uppercase">
                                    <tr>
                                        <th>Schedule / Trigger</th>
                                        <th>Target Scope</th>
                                        <th>Action Taken</th>
                                        <th>Channel</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-warning">Days 20 to 25 (09:00 AM)</div>
                                            <small class="text-muted">Daily Ethiopian cycle</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning-subtle text-dark">Pending Depts</span>
                                        </td>
                                        <td>
                                            <strong>Advance Monthly Plan Reminder</strong>
                                            <div class="text-muted small">Notifies department heads to submit plan package before Day 26.</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info"><i class="fab fa-telegram me-1"></i> Telegram</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-danger">Day 26 (17:05 Cutoff)</div>
                                            <small class="text-muted">Monthly Deadline</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger-subtle text-danger">Missing Depts &amp; GM</span>
                                        </td>
                                        <td>
                                            <strong>Monthly Deadline Cutoff Evaluation</strong>
                                            <div class="text-muted small">Flags non-submitted units and dispatches executive summary to GM.</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info"><i class="fab fa-telegram me-1"></i> Telegram</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-primary">Every Monday (12:00 PM)</div>
                                            <small class="text-muted">Weekly Deadline</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary">13 Departments</span>
                                        </td>
                                        <td>
                                            <strong>Weekly Deadline Check &amp; Escalation</strong>
                                            <div class="text-muted small">Audits execution, triggers Level 1/2/3 escalations, and updates GM scorecards.</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info"><i class="fab fa-telegram me-1"></i> Telegram</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-secondary">Hourly Poll (:00)</div>
                                            <small class="text-muted">Recurring Health Check</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary">System</span>
                                        </td>
                                        <td>
                                            <strong>Telegram Connectivity Heartbeat</strong>
                                            <div class="text-muted small">Verifies webhook responsiveness and bot token validity.</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">Daemon</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Manual Diagnostic Trigger -->
                <div class="card border-0 shadow-sm mb-4" style="background: var(--kaldis-card-bg); border-radius: 14px;">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                        <span class="fs-6 fw-bold text-dark">
                            <i class="fas fa-play text-success me-2"></i> On-Demand Test Runner
                        </span>
                        <p class="text-muted small mb-0">Manually trigger automated evaluation routines.</p>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('management.run_telegram_check') }}" class="mb-3">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100 py-2 fw-semibold text-start d-flex align-items-center justify-content-between">
                                <span><i class="fas fa-bolt text-primary me-2"></i> Run Weekly Deadline Check</span>
                                <i class="fas fa-chevron-right small text-muted"></i>
                            </button>
                        </form>

                        <div class="alert alert-light border small text-muted mb-0">
                            <i class="fas fa-terminal me-1 text-secondary"></i>
                            CLI commands:
                            <div class="font-monospace small mt-1 text-dark">php artisan kaldis:monthly-reminder</div>
                            <div class="font-monospace small text-dark">php artisan kaldis:deadline-check</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ── Tab Switcher Script ── -->
<script>
    function switchSettingsTab(tabName, btn) {
        document.querySelectorAll('.settings-tab-pane').forEach(el => el.classList.add('d-none'));
        document.querySelectorAll('.react-nav-pills .nav-link').forEach(el => el.classList.remove('active'));
        
        const target = document.getElementById('tab-' + tabName);
        if (target) {
            target.classList.remove('d-none');
        }
        if (btn) {
            btn.classList.add('active');
        }
    }
</script>
@endsection

@extends('layouts.app')

@section('title', 'System Settings')

@section('content')
<!-- React-Style Pill Navigation Switcher -->
<div class="react-nav-pills mb-4">
    <a href="{{ route('admin.settings') }}" class="nav-link active">
        <i class="fas fa-sliders-h text-warning"></i> System Parameters
    </a>
    <a href="{{ route('telegram.status') }}" class="nav-link">
        <i class="fab fa-telegram text-info"></i> Telegram Bot &amp; Webhook
    </a>
</div>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">System Settings</h4>
        <p class="text-muted small mb-0">Reporting deadlines, planning cycles, and global system parameters</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <span><i class="fas fa-sliders-h me-1 text-secondary"></i> Global Configuration Parameters</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Current Planning Year</label>
                            @php
                                $savedYear = $settings['current_planning_year']->setting_value ?? 'AUTO';
                                $ethPeriod = get_ethiopian_period();
                                $years = get_planning_years();
                                $months = get_ethiopian_months();
                            @endphp
                            <select name="current_planning_year" class="form-select">
                                <option value="AUTO" {{ ($savedYear === 'AUTO' || empty($savedYear)) ? 'selected' : '' }}>
                                    Auto: Ethiopian Calendar (Currently {{ $ethPeriod['year'] }})
                                </option>
                                @foreach($years as $y)
                                    <option value="{{ $y }}" {{ ($savedYear === $y) ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                            <div class="form-text text-muted small">
                                Active planning year in Ethiopian Calendar.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Current Planning Month</label>
                            @php
                                $savedMonth = $settings['current_planning_month']->setting_value ?? 'AUTO';
                            @endphp
                            <select name="current_planning_month" class="form-select">
                                <option value="AUTO" {{ ($savedMonth === 'AUTO' || empty($savedMonth)) ? 'selected' : '' }}>
                                    Auto: Ethiopian Calendar (Currently {{ $ethPeriod['month'] }})
                                </option>
                                @foreach($months as $m)
                                    <option value="{{ $m }}" {{ ($savedMonth === $m) ? 'selected' : '' }}>
                                        {{ $m }}{{ $m === $ethPeriod['month'] ? ' (Current Month)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text text-muted small">
                                Defaults to current Ethiopian calendar month (<strong>{{ $ethPeriod['month'] }}</strong>) and rolls over automatically (e.g. to Hidar).
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Weekly Reporting Deadline Day</label>
                            <select name="weekly_deadline_day" class="form-select">
                                @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day)
                                    <option value="{{ $day }}" {{ ($settings['weekly_deadline_day']->setting_value ?? 'Monday') === $day ? 'selected' : '' }}>{{ $day }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Weekly Reporting Deadline Cutoff Time</label>
                            <input type="time" name="weekly_deadline_time" class="form-control" value="{{ $settings['weekly_deadline_time']->setting_value ?? '12:00' }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Telegram Bot Token</label>
                        <input type="text" name="telegram_bot_token" class="form-control" value="{{ $settings['telegram_bot_token']->setting_value ?? '' }}" placeholder="Optional Bot Father API Token">
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-kaldis">
                            <i class="fas fa-save me-1"></i> Save System Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

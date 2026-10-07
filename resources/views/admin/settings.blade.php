@extends('layouts.app')

@section('title', 'System Settings')

@section('content')
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
                            <input type="text" name="current_planning_year" class="form-control" value="{{ $settings['current_planning_year']->setting_value ?? '2019 E.C.' }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Current Planning Month</label>
                            <input type="text" name="current_planning_month" class="form-control" value="{{ $settings['current_planning_month']->setting_value ?? 'Nehase' }}" required>
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

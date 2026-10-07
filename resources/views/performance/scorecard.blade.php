@extends('layouts.app')

@section('title', 'Weekly Performance Scorecard')

@section('content')
<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Weekly Performance Scorecard</h4>
        <p class="text-muted small mb-0">Cross-Department Performance & Submission Compliance Matrix</p>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar">
    <form method="GET" action="{{ route('performance.weekly') }}" class="row g-2 align-items-center">
        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Week Number</label>
            <select name="week" class="form-select form-select-sm" onchange="this.form.submit()">
                @for($w = 1; $w <= 5; $w++)
                    <option value="{{ $w }}" {{ $selectedWeek === $w ? 'selected' : '' }}>Week {{ $w }}</option>
                @endfor
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Planning Month</label>
            <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach(config('kaldis.ethiopian_months') as $m)
                    <option value="{{ $m }}" {{ $selectedMonth === $m ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Planning Year</label>
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach(config('kaldis.planning_years') as $y)
                    <option value="{{ $y }}" {{ $selectedYear === $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Filter Departments</label>
            <input type="text" id="scorecardSearchInput" class="form-control form-control-sm" placeholder="Live filter...">
        </div>
    </form>
</div>

<!-- Scorecard Table -->
<div class="card mb-4">
    <div class="card-header">
        <span><i class="fas fa-table me-1 text-secondary"></i> Scorecard & Compliance Matrix (Week {{ $selectedWeek }}, {{ $selectedMonth }} {{ $selectedYear }})</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="scorecardTable">
            <thead>
                <tr>
                    <th>Department</th>
                    <th class="text-center">Planned</th>
                    <th class="text-center">Done</th>
                    <th class="text-center">Not Done</th>
                    <th>Completion Rate</th>
                    <th style="width: 140px;" class="text-center">Submission</th>
                </tr>
            </thead>
            <tbody>
                @foreach($matrix as $m)
                    <tr>
                        <td>
                            <strong class="text-dark">{{ $m['department']->department_name }}</strong>
                            <span class="badge bg-light text-secondary ms-1">{{ $m['department']->department_code }}</span>
                        </td>
                        <td class="text-center">{{ $m['total'] }}</td>
                        <td class="text-center text-success fw-bold">{{ $m['done'] }}</td>
                        <td class="text-center text-danger fw-bold">{{ $m['not_done'] }}</td>
                        <td style="width: 200px;">
                            @if($m['total'] > 0)
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar bg-success" style="width: {{ $m['rate'] }}%;"></div>
                                    </div>
                                    <span class="small fw-semibold">{{ $m['rate'] }}%</span>
                                </div>
                            @else
                                <span class="text-muted small">&mdash;</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($m['submission'])
                                <span class="badge {{ $m['submission']->compliance_status === 'ON_TIME' ? 'bg-success' : ($m['submission']->compliance_status === 'LATE' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                    {{ $m['submission']->compliance_status }}
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border">WAITING</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    setupLiveSearch('scorecardSearchInput', 'scorecardTable');
</script>
@endsection

@extends('layouts.app')

@section('title', 'Monthly Performance Rollup')

@section('content')
<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Monthly Performance Aggregation</h4>
        <p class="text-muted small mb-0">Total Performance Rollup for {{ $selectedMonth }} {{ $selectedYear }}</p>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar">
    <form method="GET" action="{{ route('performance.monthly') }}" class="row g-2 align-items-center">
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

        <div class="col-md-6">
            <label class="form-label small text-muted mb-1">Search Department</label>
            <input type="text" id="monthlyScorecardSearch" class="form-control form-control-sm" placeholder="Live filter departments...">
        </div>
    </form>
</div>

<!-- Monthly Scorecard Table -->
<div class="card mb-4">
    <div class="card-header">
        <span><i class="fas fa-calendar-alt me-1 text-secondary"></i> Monthly Performance Aggregation ({{ $selectedMonth }} {{ $selectedYear }})</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="monthlyScorecardTable">
            <thead>
                <tr>
                    <th>Department</th>
                    <th class="text-center">Total Planned</th>
                    <th class="text-center">Done</th>
                    <th class="text-center">Not Done</th>
                    <th>Monthly Completion Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyData as $row)
                    <tr>
                        <td>
                            <strong class="text-dark">{{ $row['department']->department_name }}</strong>
                            <span class="badge bg-light text-secondary ms-1">{{ $row['department']->department_code }}</span>
                        </td>
                        <td class="text-center">{{ $row['total'] }}</td>
                        <td class="text-center text-success fw-bold">{{ $row['done'] }}</td>
                        <td class="text-center text-danger fw-bold">{{ $row['not_done'] }}</td>
                        <td style="width: 250px;">
                            @if($row['total'] > 0)
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar bg-success" style="width: {{ $row['rate'] }}%;"></div>
                                    </div>
                                    <span class="small fw-semibold">{{ $row['rate'] }}%</span>
                                </div>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">&mdash;</span>
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
    setupLiveSearch('monthlyScorecardSearch', 'monthlyScorecardTable');
</script>
@endsection

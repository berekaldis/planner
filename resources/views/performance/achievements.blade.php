@extends('layouts.app')

@section('title', 'Company Achievements Feed')

@section('content')
<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Company Achievements Feed</h4>
        <p class="text-muted small mb-0">Recorded accomplishments and successes across Kaldis Coffee departments</p>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar">
    <form method="GET" action="{{ route('performance.achievements') }}" class="row g-2 align-items-center">
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
            <label class="form-label small text-muted mb-1">Search Achievements</label>
            <input type="text" id="achSearch" class="form-control form-control-sm" placeholder="Live search achievements...">
        </div>
    </form>
</div>

<!-- Achievements List -->
<div class="card mb-4">
    <div class="card-header">
        <span><i class="fas fa-trophy me-1 text-warning"></i> Logged Achievements ({{ $achievements->count() }})</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="achievementsTable">
            <thead>
                <tr>
                    <th style="width: 140px;">Department</th>
                    <th>Achievement Title & Details</th>
                    <th style="width: 110px;">Week</th>
                    <th>Impact / Quantified Result</th>
                </tr>
            </thead>
            <tbody>
                @forelse($achievements as $ach)
                    <tr>
                        <td>
                            <strong class="text-dark">{{ $ach->department->department_name ?? 'Dept' }}</strong>
                            <span class="badge bg-light text-secondary ms-1 small">{{ $ach->department->department_code ?? '' }}</span>
                        </td>
                        <td>
                            <strong class="text-dark">{{ $ach->achievement_title }}</strong>
                            <div class="small text-secondary mt-1">{{ $ach->description }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-secondary border">Week {{ $ach->week_number }}</span>
                        </td>
                        <td>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                {{ $ach->quantified_result ?: 'Accomplished' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-state py-5">
                            <i class="fas fa-award d-block opacity-25"></i>
                            <h6>No achievements recorded for the selected period</h6>
                            <p class="small text-muted mb-0">Department heads log independent achievements in their weekly execution sheet.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    setupLiveSearch('achSearch', 'achievementsTable');
</script>
@endsection

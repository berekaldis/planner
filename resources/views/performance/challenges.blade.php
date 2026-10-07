@extends('layouts.app')

@section('title', 'Challenges & Blockers Tracker')

@section('content')
<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Challenges & Operational Blockers</h4>
        <p class="text-muted small mb-0">Cross-department challenge monitoring, severities, and required escalations</p>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar">
    <form method="GET" action="{{ route('performance.challenges') }}" class="row g-2 align-items-center">
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
            <label class="form-label small text-muted mb-1">Search Challenges</label>
            <input type="text" id="chSearch" class="form-control form-control-sm" placeholder="Live search challenges...">
        </div>
    </form>
</div>

<!-- Challenges Table -->
<div class="card mb-4">
    <div class="card-header">
        <span><i class="fas fa-flag me-1 text-danger"></i> Logged Operational Challenges ({{ $challenges->count() }})</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="challengesTable">
            <thead>
                <tr>
                    <th style="width: 140px;">Department</th>
                    <th>Challenge Title & Description</th>
                    <th style="width: 100px;">Severity</th>
                    <th style="width: 100px;">Week</th>
                    <th>Recommendation & Support Needed</th>
                </tr>
            </thead>
            <tbody>
                @forelse($challenges as $ch)
                    <tr>
                        <td>
                            <strong class="text-dark">{{ $ch->department->department_name ?? 'Dept' }}</strong>
                            <span class="badge bg-light text-secondary ms-1 small">{{ $ch->department->department_code ?? '' }}</span>
                        </td>
                        <td>
                            <strong class="text-dark">{{ $ch->challenge_title }}</strong>
                            <div class="small text-secondary mt-1">{{ $ch->description }}</div>
                        </td>
                        <td>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle small">
                                {{ $ch->severity }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-light text-secondary border">Week {{ $ch->week_number }}</span>
                        </td>
                        <td>
                            <div class="small text-dark">{{ $ch->recommended_solution ?: 'No recommendation' }}</div>
                            @if($ch->support_needed_from)
                                <small class="text-danger fw-semibold">Support: {{ $ch->support_needed_from }}</small>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-state py-5">
                            <i class="fas fa-shield-alt d-block opacity-25"></i>
                            <h6>No challenges recorded under the selected filter criteria</h6>
                            <p class="small text-muted mb-0">Operational blockers are logged directly by department heads.</p>
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
    setupLiveSearch('chSearch', 'challengesTable');
</script>
@endsection

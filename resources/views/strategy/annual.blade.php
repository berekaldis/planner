@extends('layouts.app')

@section('title', 'Annual Strategy Plan')

@section('content')
<!-- Header -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Annual Strategy Plan</h4>
        <p class="text-muted small mb-0">Master Corporate Strategic Objectives &bull; Status for {{ $selectedMonth }} {{ $selectedYear }}</p>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar mb-4">
    <form method="GET" action="{{ route('strategy.annual') }}" class="row g-2 align-items-center">
        <div class="col-sm-6 col-md-2">
            <label class="form-label small text-muted mb-1">Planning Year</label>
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach(config('kaldis.planning_years') as $y)
                    <option value="{{ $y }}" {{ $selectedYear === $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-sm-6 col-md-2">
            <label class="form-label small text-muted mb-1">Activation Month</label>
            <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach(config('kaldis.ethiopian_months') as $m)
                    <option value="{{ $m }}" {{ ($selectedMonth ?? '') === $m ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-sm-6 col-md-3">
            <label class="form-label small text-muted mb-1">Perspective</label>
            <select name="perspective" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Perspectives</option>
                @foreach($perspectives as $p)
                    <option value="{{ $p }}" {{ $perspective === $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-sm-6 col-md-3">
            <label class="form-label small text-muted mb-1">Owner Department</label>
            <select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Departments</option>
                @foreach($departments as $d)
                    <option value="{{ $d->id }}" {{ $departmentId == $d->id ? 'selected' : '' }}>{{ $d->department_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-12 col-md-2">
            <label class="form-label small text-muted mb-1">Search Strategy</label>
            <input type="text" id="strategySearchInput" class="form-control form-control-sm" placeholder="Filter goals live...">
        </div>
    </form>
</div>

<!-- Goals Table -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="fas fa-chess-knight me-1 text-secondary"></i> Strategic Objectives ({{ $goals->count() }} Plans)</span>
        <span class="badge bg-light text-secondary border">GM Status: {{ $selectedMonth }} {{ $selectedYear }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="annualGoalsTable">
            <thead>
                <tr>
                    <th style="width: 80px;">Code</th>
                    <th style="width: 140px;">Perspective</th>
                    <th>Goal Title & Target Description</th>
                    <th>Definition of Done (DoD)</th>
                    <th style="width: 90px;" class="text-center">Weight</th>
                    <th>Owner Departments</th>
                    <th style="width: 100px;" class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($goals as $goal)
                    <tr>
                        <td>
                            <span class="badge bg-dark font-monospace">{{ $goal->goal_code }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-secondary border small">{{ $goal->perspective }}</span>
                        </td>
                        <td>
                            <strong class="text-dark">{{ $goal->goal_title }}</strong>
                            @if($goal->target_description)
                                <div class="small text-muted mt-1">{{ $goal->target_description }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="small text-secondary">{{ $goal->definition_of_done }}</div>
                        </td>
                        <td class="text-center">
                            <span class="fw-bold">{{ $goal->weight }}%</span>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($goal->departments as $dept)
                                    <span class="badge bg-secondary-subtle text-secondary small">
                                        {{ $dept->department_code }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="text-center">
                            @php
                                $isActivated = in_array($goal->id, $activeGoalIds ?? []);
                            @endphp
                            @if($isActivated)
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" title="Activated by GM for {{ $selectedMonth }}">
                                    <i class="fas fa-check-circle me-1"></i>Active
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1" title="Inactive in GM Activation for {{ $selectedMonth }}">
                                    <i class="fas fa-circle-xmark me-1"></i>Inactive
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state py-5">
                            <i class="fas fa-folder-open d-block opacity-25"></i>
                            <h6>No strategic goals found for this criteria</h6>
                            <p class="small text-muted">Adjust your filter options above.</p>
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
    setupLiveSearch('strategySearchInput', 'annualGoalsTable');
</script>
@endsection

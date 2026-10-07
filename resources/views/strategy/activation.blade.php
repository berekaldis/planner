@extends('layouts.app')

@section('title', 'GM Monthly Strategy Activation')

@section('content')
<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">GM Monthly Strategy Activation</h4>
        <p class="text-muted small mb-0">Select and activate annual strategic objectives for {{ $selectedMonth }} {{ $selectedYear }}</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-primary-subtle text-primary border border-primary px-3 py-2 fs-6">
            <span id="activeCountBadge">{{ $activeCount }}</span> / {{ $totalCount }} Active This Month
        </span>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar">
    <form method="GET" action="{{ route('strategy.activation') }}" class="row g-2 align-items-center">
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
            <label class="form-label small text-muted mb-1">Instant Filter</label>
            <input type="text" id="activationSearchInput" class="form-control form-control-sm" placeholder="Search strategy code, title, or owner departments...">
        </div>
    </form>
</div>

<!-- Strategy Activation Table -->
<div class="card mb-4">
    <div class="card-header">
        <span><i class="fas fa-toggle-on me-1 text-secondary"></i> All Strategic Plans (By Default: FALSE / Inactive)</span>
        <span class="text-muted small">Activating a strategy activates it for all assigned co-owner departments</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="activationTable">
            <thead>
                <tr>
                    <th style="width: 80px;">Code</th>
                    <th>Goal Title & Target Description</th>
                    <th>Owner Departments</th>
                    <th style="width: 150px;" class="text-center">Status</th>
                    <th style="width: 140px;" class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($goals as $goal)
                    @php
                        $isActive = in_array($goal->id, $activeGoalIds);
                    @endphp
                    <tr id="row-{{ $goal->id }}">
                        <td>
                            <span class="badge bg-dark font-monospace">{{ $goal->goal_code }}</span>
                        </td>
                        <td>
                            <strong class="text-dark">{{ $goal->goal_title }}</strong>
                            <div class="small text-muted mt-0.5">{{ $goal->target_description }}</div>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($goal->departments as $dept)
                                    <span class="badge bg-light text-secondary border small">
                                        {{ $dept->department_code }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="text-center">
                            <span id="badge-{{ $goal->id }}" class="badge {{ $isActive ? 'bg-success' : 'bg-secondary' }}">
                                {{ $isActive ? 'TRUE (Active)' : 'FALSE (Inactive)' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <button type="button" 
                                    class="btn btn-sm {{ $isActive ? 'btn-outline-danger' : 'btn-kaldis' }} toggle-btn"
                                    id="btn-{{ $goal->id }}"
                                    onclick="togglePlan({{ $goal->id }}, {{ $isActive ? 0 : 1 }})">
                                <i class="fas {{ $isActive ? 'fa-times' : 'fa-check' }} me-1"></i>
                                <span>{{ $isActive ? 'Deactivate' : 'Activate' }}</span>
                            </button>
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
    setupLiveSearch('activationSearchInput', 'activationTable');

    function togglePlan(goalId, newStatus) {
        const btn = document.getElementById('btn-' + goalId);
        const badge = document.getElementById('badge-' + goalId);
        btn.disabled = true;

        fetch("{{ route('strategy.activation.toggle') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                annual_goal_id: goalId,
                year: "{{ $selectedYear }}",
                month: "{{ $selectedMonth }}",
                is_active: newStatus
            })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (data.success) {
                // Update badge
                if (data.is_active) {
                    badge.className = 'badge bg-success';
                    badge.textContent = 'TRUE (Active)';
                    btn.className = 'btn btn-sm btn-outline-danger toggle-btn';
                    btn.innerHTML = '<i class="fas fa-times me-1"></i><span>Deactivate</span>';
                    btn.setAttribute('onclick', `togglePlan(${goalId}, 0)`);
                } else {
                    badge.className = 'badge bg-secondary';
                    badge.textContent = 'FALSE (Inactive)';
                    btn.className = 'btn btn-sm btn-kaldis toggle-btn';
                    btn.innerHTML = '<i class="fas fa-check me-1"></i><span>Activate</span>';
                    btn.setAttribute('onclick', `togglePlan(${goalId}, 1)`);
                }

                // Update counter
                document.getElementById('activeCountBadge').textContent = data.active_count;
            }
        })
        .catch(err => {
            btn.disabled = false;
            alert('Failed to update strategy status. Please try again.');
        });
    }
</script>
@endsection

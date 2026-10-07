@extends('layouts.app')

@section('title', 'Monthly Plans - ' . $department->department_name)

@section('content')
<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Monthly Plans</h4>
        <p class="text-muted small mb-0">{{ $department->department_name }} &bull; {{ $selectedMonth }} {{ $selectedYear }}</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-kaldis btn-sm" data-bs-toggle="modal" data-bs-target="#createMonthlyPlanModal">
            <i class="fas fa-plus-circle me-1"></i> Add Monthly Plan
        </button>
        <a href="{{ route('weekly.plans', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth]) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-right me-1"></i> Go to Weekly Plans
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar">
    <form method="GET" action="{{ route('monthly.plans') }}" class="row g-2 align-items-center">
        @if(auth()->user()->canAccessAllDepartments())
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Department</label>
                <select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($allDepartments as $d)
                        <option value="{{ $d->id }}" {{ $department->id == $d->id ? 'selected' : '' }}>
                            {{ $d->department_name }} ({{ $d->department_code }})
                        </option>
                    @endforeach
                </select>
            </div>
        @else
            <input type="hidden" name="department_id" value="{{ $department->id }}">
        @endif

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
            <label class="form-label small text-muted mb-1">Search Plans</label>
            <input type="text" id="monthlyPlanSearchInput" class="form-control form-control-sm" placeholder="Filter plans live...">
        </div>
    </form>
</div>

<!-- Plans Table -->
<div class="card mb-4">
    <div class="card-header">
        <span><i class="fas fa-calendar-alt me-1 text-secondary"></i> Department Monthly Plans ({{ $monthlyPlans->count() }})</span>
        <div class="d-flex gap-2">
            <a href="{{ route('monthly.plans', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth]) }}" class="btn btn-xs btn-outline-secondary {{ empty($planTypeFilter) ? 'active' : '' }}">All</a>
            <a href="{{ route('monthly.plans', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'plan_type' => 'STRATEGY']) }}" class="btn btn-xs btn-outline-secondary {{ $planTypeFilter === 'STRATEGY' ? 'active' : '' }}">Strategy</a>
            <a href="{{ route('monthly.plans', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'plan_type' => 'OPERATIONAL']) }}" class="btn btn-xs btn-outline-secondary {{ $planTypeFilter === 'OPERATIONAL' ? 'active' : '' }}">Operational</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="monthlyPlansTable">
            <thead>
                <tr>
                    <th style="width: 110px;">Type</th>
                    <th style="width: 80px;">Goal</th>
                    <th>Plan Title & Target</th>
                    <th>Definition of Done</th>
                    <th style="width: 100px;">Target %</th>
                    <th style="width: 90px;">Priority</th>
                    <th>Responsible</th>
                    <th style="width: 60px;" class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($monthlyPlans as $plan)
                    <tr>
                        <td>
                            @if($plan->plan_type === 'STRATEGY')
                                <span class="badge-strategy">Strategy</span>
                            @else
                                <span class="badge-operational">Operational</span>
                            @endif
                        </td>
                        <td>
                            @if($plan->annualGoal)
                                <span class="badge bg-dark font-monospace">{{ $plan->annualGoal->goal_code }}</span>
                            @else
                                <span class="text-muted">&mdash;</span>
                            @endif
                        </td>
                        <td>
                            <strong class="text-dark">{{ $plan->title }}</strong>
                            <div class="small text-muted"><strong>Target:</strong> {{ $plan->monthly_target }}</div>
                        </td>
                        <td>
                            <div class="small text-secondary">{{ $plan->definition_of_done }}</div>
                        </td>
                        <td>
                            <div class="progress" style="height: 16px;">
                                <div class="progress-bar bg-warning text-dark fw-bold small" style="width: {{ (int)$plan->target_percentage }}%;">
                                    {{ (int)$plan->target_percentage }}%
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-secondary border">{{ $plan->priority }}</span>
                        </td>
                        <td>
                            <small class="text-muted">{{ $plan->responsible_person ?: 'Department' }}</small>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('monthly.plans.destroy', $plan->id) }}" onsubmit="return confirm('Delete this monthly plan? Associated weekly tasks will also be removed.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Delete">
                                    <i class="fas fa-trash-alt small"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-state py-5">
                            <i class="fas fa-clipboard-list d-block opacity-25"></i>
                            <h6>No monthly plans found for this period</h6>
                            <p class="small text-muted mb-3">Click <strong>Add Monthly Plan</strong> above to create Strategy or Operational plans.</p>
                            <button type="button" class="btn btn-sm btn-kaldis" data-bs-toggle="modal" data-bs-target="#createMonthlyPlanModal">
                                <i class="fas fa-plus-circle me-1"></i> Add Monthly Plan Now
                            </button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Create Monthly Plan -->
<div class="modal fade" id="createMonthlyPlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('monthly.plans.store') }}">
                @csrf
                <input type="hidden" name="department_id" value="{{ $department->id }}">
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                <input type="hidden" name="month" value="{{ $selectedMonth }}">

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark fs-6"><i class="fas fa-plus-circle me-2 text-warning"></i> Add Monthly Plan ({{ $selectedMonth }} {{ $selectedYear }})</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Step 1: Select Plan Type -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Plan Type <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="form-check p-2.5 border rounded-3 bg-light">
                                    <input class="form-check-input" type="radio" name="plan_type" id="typeStrategy" value="STRATEGY" checked onchange="togglePlanType()">
                                    <label class="form-check-label fw-semibold text-primary" for="typeStrategy">
                                        <i class="fas fa-chess-knight me-1"></i> STRATEGY PLAN
                                    </label>
                                    <div class="small text-muted" style="font-size: 0.75rem;">Linked to GM-activated Annual Strategy Goal</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check p-2.5 border rounded-3 bg-light">
                                    <input class="form-check-input" type="radio" name="plan_type" id="typeOperational" value="OPERATIONAL" onchange="togglePlanType()">
                                    <label class="form-check-label fw-semibold text-info" for="typeOperational">
                                        <i class="fas fa-cogs me-1"></i> OPERATIONAL PLAN
                                    </label>
                                    <div class="small text-muted" style="font-size: 0.75rem;">Department routine & maintenance tasks</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: GM Activated Strategy Selector -->
                    <div id="strategyGoalSection" class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">SELECT GM-ACTIVATED ANNUAL STRATEGY <span class="text-danger">*</span></label>
                        @if($activatedGoals->isEmpty())
                            <div class="alert alert-warning py-2 px-3 small border-0 rounded-3">
                                <i class="fas fa-exclamation-triangle me-1"></i> No GM-activated strategic goals found for <strong>{{ $department->department_name }}</strong> in <strong>{{ $selectedMonth }} {{ $selectedYear }}</strong>.
                                @if(auth()->user()->isGM() || auth()->user()->isSuperAdmin())
                                    <a href="{{ route('strategy.activation', ['year' => $selectedYear, 'month' => $selectedMonth]) }}" class="alert-link ms-1">Activate goals here &rarr;</a>
                                @else
                                    <span class="text-muted">Please contact the General Manager to activate strategies for this month.</span>
                                @endif
                            </div>
                        @else
                            <select name="annual_goal_id" id="annualGoalSelect" class="form-select" onchange="autoFillStrategyGoal()">
                                <option value="">-- Choose Activated Annual Goal --</option>
                                @foreach($activatedGoals as $ag)
                                    <option value="{{ $ag->id }}" 
                                            data-title="{{ e($ag->goal_title) }}" 
                                            data-dod="{{ e($ag->definition_of_done) }}" 
                                            data-target="{{ e($ag->target_description) }}">
                                        [{{ $ag->goal_code }}] {{ $ag->goal_title }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <!-- Step 3: Plan Details -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Plan Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="planTitleInput" class="form-control" placeholder="e.g. Implement Network Monitoring System" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Monthly Target <span class="text-danger">*</span></label>
                            <input type="text" name="monthly_target" id="monthlyTargetInput" class="form-control" placeholder="e.g. Deploy to 5 Branches" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Target %</label>
                            <input type="number" name="target_percentage" class="form-control" value="100" min="0" max="100" step="1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Priority <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select">
                                <option value="HIGH">HIGH</option>
                                <option value="MEDIUM" selected>MEDIUM</option>
                                <option value="LOW">LOW</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Definition of Done (DoD) <span class="text-danger">*</span></label>
                        <textarea name="definition_of_done" id="dodInput" class="form-control" rows="2" placeholder="Clear criteria for when this plan is considered complete..." required></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Responsible Person</label>
                            <input type="text" name="responsible_person" class="form-control" placeholder="e.g. Senior Network Engineer">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Description (Optional)</label>
                            <input type="text" name="description" class="form-control" placeholder="Additional operational notes">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-kaldis">Save Monthly Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    setupLiveSearch('monthlyPlanSearchInput', 'monthlyPlansTable');

    function togglePlanType() {
        const isStrategy = document.getElementById('typeStrategy').checked;
        const section = document.getElementById('strategyGoalSection');
        const goalSelect = document.getElementById('annualGoalSelect');
        
        if (isStrategy) {
            section.style.display = 'block';
            if (goalSelect) goalSelect.required = true;
        } else {
            section.style.display = 'none';
            if (goalSelect) {
                goalSelect.required = false;
                goalSelect.value = '';
            }
        }
    }

    function autoFillStrategyGoal() {
        const select = document.getElementById('annualGoalSelect');
        if (!select || !select.value) return;

        const opt = select.options[select.selectedIndex];
        const title = opt.getAttribute('data-title');
        const dod = opt.getAttribute('data-dod');
        const target = opt.getAttribute('data-target');

        const titleInput = document.getElementById('planTitleInput');
        const dodInput = document.getElementById('dodInput');
        const targetInput = document.getElementById('monthlyTargetInput');

        if (titleInput && (!titleInput.value || titleInput.value.length < 5)) {
            titleInput.value = title;
        }
        if (dodInput && (!dodInput.value || dodInput.value.length < 5)) {
            dodInput.value = dod;
        }
        if (targetInput && (!targetInput.value || targetInput.value.length < 5)) {
            targetInput.value = target;
        }
    }
</script>
@endsection

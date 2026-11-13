@extends('layouts.app')

@section('title', 'Monthly Plans - ' . $department->department_name)

@section('content')
<!-- Header -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Monthly Plans</h4>
        <p class="text-muted small mb-0">{{ $department->department_name }} &bull; {{ $selectedMonth }} {{ $selectedYear }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if(kaldis_setting('voucher_enabled', 'true') === 'true')
            <a href="{{ route('monthly.voucher', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth]) }}" class="btn btn-outline-dark btn-sm" target="_blank" title="Open Official Monthly Plan Voucher">
                <i class="fas fa-file-invoice me-1"></i> View Official Voucher
            </a>
        @endif
        <button type="button" class="btn {{ $submission ? 'btn-outline-success' : 'btn-success' }} btn-sm" data-bs-toggle="modal" data-bs-target="#submitPackageModal">
            <i class="fas {{ $submission ? 'fa-redo' : 'fa-paper-plane' }} me-1"></i> {{ $submission ? 'Re-Submit to GM' : 'Submit to GM' }}
        </button>
        <button type="button" class="btn btn-kaldis btn-sm" data-bs-toggle="modal" data-bs-target="#createMonthlyPlanModal">
            <i class="fas fa-plus-circle me-1"></i> Add Task
        </button>
        <a href="{{ route('weekly.plans', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth]) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-right me-1"></i> Go to Weekly Plans
        </a>
    </div>
</div>

<!-- Monthly Submission Status Banner -->
@if($submission && ($submission->isDelayApproved() || $submission->status === 'DELAY_APPROVED'))
    <div class="alert alert-info border-0 shadow-sm d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="fas fa-shield-alt text-primary fs-5"></i>
                <strong class="text-dark">Delay Approved by GM</strong>
                {!! format_submission_status_badge('DELAY_APPROVED', 'APPROVED', $submission->delay_reason) !!}
                @if(!empty($submission->approval_status))
                    <span class="badge {{ $submission->approval_status === 'APPROVED' ? 'bg-success' : 'bg-secondary' }}">
                        GM: {{ $submission->approval_status }}
                    </span>
                @endif
            </div>
            <div class="small text-muted">
                Delay reason approved. This package is excused.
            </div>
            @if(!empty($submission->delay_reason))
                <div class="small text-dark mt-1"><strong>Reason:</strong> {{ $submission->delay_reason }}</div>
            @endif
            @if(!empty($submission->delay_gm_notes))
                <div class="small text-primary mt-0.5"><strong>GM Notes:</strong> {{ $submission->delay_gm_notes }}</div>
            @endif
        </div>
        <div class="small text-muted text-nowrap">
            Approved: {{ $submission->delay_approved_at ? format_ethiopian_datetime($submission->delay_approved_at) : '' }}
        </div>
    </div>
@elseif($submission && $submission->isDelayPending())
    <div class="alert alert-warning border-0 shadow-sm d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="fas fa-hourglass-half text-warning fs-5"></i>
                <strong class="text-dark">Delay Reason Pending GM Review</strong>
                {!! format_submission_status_badge($submission->status, 'PENDING', $submission->delay_reason) !!}
            </div>
            <div class="small text-muted">
                Your delay reason is waiting for GM review.
            </div>
            <div class="small text-dark mt-1"><strong>Reason:</strong> {{ $submission->delay_reason }}</div>
        </div>
        <div class="small text-muted text-nowrap">
            Updated: {{ $submission->updated_at ? format_ethiopian_datetime($submission->updated_at) : '' }}
        </div>
    </div>
@elseif($submission && in_array($submission->status, ['ON_TIME', 'LATE', 'SUBMITTED']))
    <div class="alert alert-success border-0 shadow-sm d-flex justify-content-between align-items-center mb-3">
        <div>
            <i class="fas fa-check-circle me-2 text-success"></i>
            <strong>Plans Submitted:</strong> Monthly plans for {{ $selectedMonth }} {{ $selectedYear }} are submitted to GM.
            {!! format_submission_status_badge($submission->status, $submission->delay_status, $submission->delay_reason) !!}
            @if(!empty($submission->approval_status))
                <span class="badge {{ $submission->approval_status === 'APPROVED' ? 'bg-success' : ($submission->approval_status === 'REVISION_REQUESTED' ? 'bg-danger' : 'bg-secondary') }} ms-1">
                    GM: {{ $submission->approval_status }}
                </span>
            @endif
        </div>
        <div class="small text-muted">
            Submitted: {{ $submission->submitted_at ? format_ethiopian_datetime($submission->submitted_at) : '' }}
        </div>
    </div>
@else
    <div class="alert alert-warning border-0 shadow-sm d-flex justify-content-between align-items-center mb-3">
        <div>
            <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
            <strong>Plans Not Submitted Yet:</strong> Click <strong>Submit to GM</strong> when ready.
        </div>
        <div class="small text-muted">
            Deadline: <strong>{{ $deadlineInfo['deadline_formatted'] ?? 'Day 26' }}</strong>
        </div>
    </div>
@endif

@if($submission && !empty($submission->gm_feedback))
    <div class="card border-primary mb-4 shadow-sm">
        <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
            <span class="fw-bold small"><i class="fas fa-comment-dots me-1"></i> Executive Review Comments from General Manager</span>
            <span class="small opacity-75">{{ $submission->gm_feedback_at ? format_ethiopian_datetime($submission->gm_feedback_at) : '' }}</span>
        </div>
        <div class="card-body bg-light py-2 px-3 small">
            <p class="mb-0 text-dark">{{ $submission->gm_feedback }}</p>
        </div>
    </div>
@endif

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

@php
    $isReviewer = auth()->user()->isGM() || auth()->user()->isSuperAdmin();
    $pendingPlansCount = $monthlyPlans->where('approval_status', '!=', 'APPROVED')->count();
@endphp

<!-- Plans Table -->
<div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2">
            <span><i class="fas fa-calendar-alt me-1 text-secondary"></i> Department Monthly Plans ({{ $monthlyPlans->count() }})</span>
            @if($isReviewer && $pendingPlansCount > 0)
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle small">
                    {{ $pendingPlansCount }} Pending
                </span>
            @endif
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @if($isReviewer && $pendingPlansCount > 0)
                <!-- Bulk Approve All Pending Form -->
                <form method="POST" action="{{ route('monthly.plans.bulk_approve') }}" class="d-inline" onsubmit="return confirm('Approve ALL {{ $pendingPlansCount }} pending monthly plans for {{ $department->department_name }}?');">
                    @csrf
                    <input type="hidden" name="department_id" value="{{ $department->id }}">
                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <button type="submit" class="btn btn-xs btn-success fw-semibold shadow-xs" title="Approve all pending plans for this month">
                        <i class="fas fa-check-double me-1"></i> Approve All Pending ({{ $pendingPlansCount }})
                    </button>
                </form>
                <!-- Bulk Approve Selected Button -->
                <button type="button" id="btnApproveSelectedPlans" class="btn btn-xs btn-outline-success fw-semibold d-none" onclick="submitSelectedPlans()">
                    <i class="fas fa-check-circle me-1"></i> Approve Selected (<span id="selectedPlansCount">0</span>)
                </button>
            @endif
            <div class="btn-group btn-group-sm">
                <a href="{{ route('monthly.plans', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth]) }}" class="btn btn-xs btn-outline-secondary {{ empty($planTypeFilter) ? 'active' : '' }}">All</a>
                <a href="{{ route('monthly.plans', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'plan_type' => 'STRATEGY']) }}" class="btn btn-xs btn-outline-secondary {{ $planTypeFilter === 'STRATEGY' ? 'active' : '' }}">Strategy</a>
                <a href="{{ route('monthly.plans', ['department_id' => $department->id, 'year' => $selectedYear, 'month' => $selectedMonth, 'plan_type' => 'OPERATIONAL']) }}" class="btn btn-xs btn-outline-secondary {{ $planTypeFilter === 'OPERATIONAL' ? 'active' : '' }}">Operational</a>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="monthlyPlansTable">
            <thead>
                <tr>
                    @if($isReviewer)
                        <th style="width: 36px;" class="text-center">
                            <input type="checkbox" id="selectAllPlans" class="form-check-input" title="Select all pending plans">
                        </th>
                    @endif
                    <th style="width: 100px;">Type</th>
                    <th style="width: 90px;">Goal</th>
                    <th>Plan Title &amp; Target</th>
                    <th>Definition of Done (DOD)</th>
                    <th style="width: 140px;" class="text-center">Approval Status</th>
                    <th style="width: 110px;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($monthlyPlans as $plan)
                    <tr>
                        @if($isReviewer)
                            <td class="text-center">
                                @if($plan->approval_status !== 'APPROVED')
                                    <input type="checkbox" class="form-check-input plan-chk" value="{{ $plan->id }}" title="Select plan #{{ $plan->id }}">
                                @endif
                            </td>
                        @endif
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
                            <div class="small text-muted">Target: {{ $plan->monthly_target }}</div>
                            @if($plan->gm_comment)
                                <div class="mt-1.5 p-2 rounded-2 border bg-warning-subtle text-dark" style="font-size: 0.78rem;">
                                    <div class="d-flex align-items-center gap-1.5 fw-semibold text-warning-emphasis mb-0.5">
                                        <i class="fas fa-comment-dots text-warning"></i>
                                        <span>GM Comment</span>
                                        @if($plan->gm_comment_at)
                                            <span class="text-muted fw-normal" style="font-size: 0.7rem;">({{ $plan->gm_comment_at->diffForHumans() }})</span>
                                        @endif
                                    </div>
                                    <div class="text-secondary" style="white-space: pre-wrap;">{{ $plan->gm_comment }}</div>
                                </div>
                            @endif
                        </td>
                        <td>
                            <div class="small text-secondary">{{ $plan->definition_of_done }}</div>
                        </td>
                        <td class="text-center">
                            @if($plan->approval_status === 'APPROVED')
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="fas fa-check-circle me-1"></i>Approved
                                </span>
                            @elseif($plan->approval_status === 'DECLINED')
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="{{ $plan->decline_reason }}">
                                    <i class="fas fa-circle-xmark me-1"></i>Declined
                                </span>
                                @if($plan->decline_reason)
                                    <div class="text-danger small mt-0.5" style="font-size: 0.72rem;">{{ $plan->decline_reason }}</div>
                                @endif
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border">Pending</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex align-items-center gap-1">
                                @if(auth()->user()->isGM() || auth()->user()->isSuperAdmin())
                                    <button type="button" class="btn btn-xs btn-outline-warning py-0.5 px-1.5" 
                                            onclick="openGmCommentModal({{ $plan->id }}, '{{ addslashes($plan->title) }}', '{{ addslashes($plan->gm_comment ?? '') }}')" 
                                            title="Add / Edit GM Comment">
                                        <i class="fas fa-comment-dots"></i>
                                    </button>
                                    @if($plan->approval_status !== 'APPROVED')
                                        <form method="POST" action="{{ route('monthly.plans.approve', $plan->id) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-xs btn-outline-success py-0.5 px-1.5" title="Approve Plan">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if($plan->approval_status !== 'DECLINED')
                                        <button type="button" class="btn btn-xs btn-outline-danger py-0.5 px-1.5" onclick="openDeclineMonthlyModal({{ $plan->id }}, '{{ addslashes($plan->title) }}')" title="Decline Plan">
                                            <i class="fas fa-xmark"></i>
                                        </button>
                                    @endif
                                @endif
                                <form method="POST" action="{{ route('monthly.plans.destroy', $plan->id) }}" onsubmit="return confirm('Delete this monthly plan?');" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-secondary py-0.5 px-1.5" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isReviewer ? 7 : 6 }}" class="empty-state py-5 text-center">
                            <i class="fas fa-clipboard-list d-block opacity-25 fs-2 mb-2"></i>
                            <h6 class="fw-semibold text-dark">No monthly plans yet</h6>
                            <p class="small text-muted mb-3">Add monthly plans for this period.</p>
                            <button type="button" class="btn btn-sm btn-kaldis" data-bs-toggle="modal" data-bs-target="#createMonthlyPlanModal">
                                <i class="fas fa-plus-circle me-1"></i> Add Task
                            </button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($isReviewer)
    <!-- Hidden Form for Bulk Approving Selected Plans -->
    <form id="bulkApprovePlansForm" method="POST" action="{{ route('monthly.plans.bulk_approve') }}" class="d-none">
        @csrf
        <div id="bulkApprovePlansInputs"></div>
    </form>
@endif

<!-- Modal: Create Monthly Plan -->
<div class="modal fade" id="createMonthlyPlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('monthly.plans.store') }}">
                @csrf
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                <input type="hidden" name="month" value="{{ $selectedMonth }}">

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark fs-6"><i class="fas fa-plus-circle me-2 text-warning"></i> Add Task / Plan ({{ $selectedMonth }} {{ $selectedYear }})</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    @if(auth()->user()->canAccessAllDepartments() && isset($allDepartments) && $allDepartments->count() > 1)
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Target Department <span class="text-danger">*</span></label>
                            <select name="department_id" class="form-select" required>
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

                    @php
                        $isStratMandatory = kaldis_setting('strategy_field_mandatory', 'true') === 'true';
                    @endphp
                    <!-- Step 2: GM Activated Strategy Selector -->
                    <div id="strategyGoalSection" class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">
                            SELECT GM-ACTIVATED ANNUAL STRATEGY 
                            @if($isStratMandatory)
                                <span class="badge bg-danger-subtle text-danger ms-1">Mandatory</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary ms-1">Optional</span>
                            @endif
                        </label>
                        @if($activatedGoals->isEmpty())
                            <div class="alert alert-warning py-2 px-3 small border-0 rounded-3">
                                <i class="fas fa-exclamation-triangle me-1"></i> No GM-activated strategic goals found for <strong>{{ $department->department_name }}</strong> in <strong>{{ $selectedMonth }} {{ $selectedYear }}</strong>.
                                @if($isStratMandatory)
                                    @if(auth()->user()->isGM() || auth()->user()->isSuperAdmin())
                                        <a href="{{ route('strategy.activation', ['year' => $selectedYear, 'month' => $selectedMonth]) }}" class="alert-link ms-1">Activate goals here &rarr;</a>
                                    @else
                                        <span class="text-muted">Please contact the General Manager to activate strategies for this month.</span>
                                    @endif
                                @else
                                    <span class="text-muted">Strategy field is currently optional. You may proceed without selecting an annual goal.</span>
                                @endif
                            </div>
                        @else
                            <select name="annual_goal_id" id="annualGoalSelect" class="form-select" data-no-search="true" onchange="autoFillStrategyGoal()">
                                <option value="">-- Choose Activated Annual Goal {{ $isStratMandatory ? '' : '(Optional)' }} --</option>
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

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Monthly Target <span class="text-danger">*</span></label>
                        <input type="text" name="monthly_target" id="monthlyTargetInput" class="form-control" placeholder="e.g. Deploy to 5 Branches" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Definition of Done (DoD) <span class="text-danger">*</span></label>
                        <textarea name="definition_of_done" id="dodInput" class="form-control" rows="3" placeholder="Clear criteria for when this plan is considered complete..." required></textarea>
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

<!-- Modal: Decline Monthly Plan (GM Action) -->
<div class="modal fade" id="declineMonthlyPlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="declineMonthlyPlanForm" action="">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark fs-6">Decline Monthly Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-secondary small mb-3">Please specify the reason for declining this plan:</p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Why are you declining this plan? <span class="text-danger">*</span></label>
                        <textarea name="decline_reason" id="monthlyDeclineReasonInput" class="form-control" rows="3" placeholder="Provide reason or required changes..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger fw-semibold">Decline Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Submit Monthly Plan Package -->
<div class="modal fade" id="submitPackageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('monthly.plans.submit_package') }}">
                @csrf
                <input type="hidden" name="department_id" value="{{ $department->id }}">
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                <input type="hidden" name="month" value="{{ $selectedMonth }}">

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark fs-6">
                        <i class="fas fa-paper-plane text-success me-2"></i> Submit Monthly Plan Package to GM
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 small mb-3 border-0">
                        <i class="fas fa-info-circle me-1"></i>
                        Submitting for <strong>{{ $department->department_name }}</strong> &bull; <strong>{{ $selectedMonth }} {{ $selectedYear }}</strong>.
                        This will notify the General Manager and record your formal submission timing.
                    </div>

                    <div class="bg-light p-3 rounded-3 mb-3 border">
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Total Monthly Plans:</span>
                            <span class="fw-bold text-dark">{{ $monthlyPlans->count() }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Strategy Plans:</span>
                            <span class="badge bg-primary">{{ $monthlyPlans->where('plan_type', 'STRATEGY')->count() }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Operational Plans:</span>
                            <span class="badge bg-info text-dark">{{ $monthlyPlans->where('plan_type', 'OPERATIONAL')->count() }}</span>
                        </div>
                        <div class="d-flex justify-content-between small pt-1 border-top mt-1">
                            <span class="text-muted">Submission Deadline:</span>
                            <span class="text-secondary fw-semibold">{{ $deadlineInfo['deadline_formatted'] ?? 'Day 26 of Preceding Month (17:00)' }}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">
                            <i class="fas fa-clock text-warning me-1"></i> Delay Reason / Justification (If submitting late or requesting extension)
                        </label>
                        <textarea name="delay_reason" class="form-control" rows="2" placeholder="If submitting after Day 26 (17:00), explain the operational justification. If approved by GM, this plan will NOT be marked as Not Submitted.">{{ $submission->delay_reason ?? '' }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Department Remarks / Notes to GM (Optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Add executive summary notes, key focus areas, or remarks for the General Manager..."></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success" {{ $monthlyPlans->isEmpty() ? 'disabled' : '' }}>
                        <i class="fas fa-check-circle me-1"></i> Transmit Package to GM
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Decline Monthly Plan (GM Action) -->
<div class="modal fade" id="declineMonthlyPlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="declineMonthlyPlanForm" action="">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark fs-6">Decline Monthly Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-secondary small mb-3">Please specify the reason for declining this plan:</p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Why are you declining this plan? <span class="text-danger">*</span></label>
                        <textarea name="decline_reason" id="monthlyDeclineReasonInput" class="form-control" rows="3" placeholder="Provide reason or required changes..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger fw-semibold">Decline Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Submit Monthly Plan Delay Reason -->
<div class="modal fade" id="monthlyDelayReasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('monthly.delay_reason') }}">
                @csrf
                <input type="hidden" name="department_id" value="{{ $department->id }}">
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                <input type="hidden" name="month" value="{{ $selectedMonth }}">

                <div class="modal-header bg-warning bg-opacity-10 border-bottom border-warning border-opacity-25">
                    <h5 class="modal-title fw-bold text-dark fs-6">
                        <i class="fas fa-clock text-warning me-2"></i> Submit Delay Reason to General Management
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 small mb-3 border-0">
                        <i class="fas fa-shield-alt text-primary me-1"></i>
                        <strong>Executive Governance Rule:</strong> If your department has a justifiable delay reason and the <strong>General Manager Approves</strong>, your monthly plan will <strong>NOT</strong> be recorded as "Not Submitted", "Missing", or penalized in compliance audits.
                    </div>

                    <div class="bg-light p-3 rounded-3 mb-3 border small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Department:</span>
                            <span class="fw-bold text-dark">{{ $department->department_name }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Target Month:</span>
                            <span class="fw-semibold text-dark">{{ $selectedMonth }} {{ $selectedYear }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Current Delay Status:</span>
                            <span>{!! format_submission_status_badge($submission->status ?? null, $submission->delay_status ?? null, $submission->delay_reason ?? null) !!}</span>
                        </div>
                        @if($submission && !empty($submission->delay_gm_notes))
                            <div class="pt-2 border-top mt-2 text-primary">
                                <strong>Last GM Remarks:</strong> {{ $submission->delay_gm_notes }}
                            </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">
                            Detailed Reason for Delay / Extension Request <span class="text-danger">*</span>
                        </label>
                        <textarea name="delay_reason" class="form-control" rows="4" required placeholder="State why the monthly plan package cannot be submitted by Day 26 (17:00), estimated date of completion, and operational constraints...">{{ $submission->delay_reason ?? '' }}</textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-sm btn-warning fw-semibold">
                        <i class="fas fa-paper-plane me-1"></i> Submit Justification to GM
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Decline Monthly Plan (GM Action) -->
<div class="modal fade" id="declineMonthlyPlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="declineMonthlyPlanForm" action="">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark fs-6">Decline Monthly Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-secondary small mb-3">Please specify the reason for declining this plan:</p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Why are you declining this plan? <span class="text-danger">*</span></label>
                        <textarea name="decline_reason" id="monthlyDeclineReasonInput" class="form-control" rows="3" placeholder="Provide reason or required changes..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger fw-semibold">Decline Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: GM Item Comment (GM Granular Inline Feedback) -->
<div class="modal fade" id="gmCommentMonthlyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="gmCommentMonthlyForm" action="">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark fs-6">
                        <i class="fas fa-comment-dots text-warning me-2"></i> GM Feedback &amp; Comment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-secondary small mb-2">Monthly Plan: <strong id="gmCommentPlanTitle" class="text-dark"></strong></p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">General Manager Comment / Instructions</label>
                        <textarea name="gm_comment" id="gmCommentInput" class="form-control" rows="4" placeholder="Enter constructive feedback, adjustments, or guidance for this plan..."></textarea>
                        <div class="small text-muted mt-1">Leave empty and save to remove the comment.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-warning text-dark fw-bold">
                        <i class="fas fa-save me-1"></i> Save GM Comment
                    </button>
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

    function openDeclineMonthlyModal(planId, title) {
        const form = document.getElementById('declineMonthlyPlanForm');
        form.action = "{{ url('/monthly/plans') }}/" + planId + "/decline";
        document.getElementById('monthlyDeclineReasonInput').value = '';
        const modal = new bootstrap.Modal(document.getElementById('declineMonthlyPlanModal'));
        modal.show();
    }

    function openGmCommentModal(planId, title, comment) {
        const form = document.getElementById('gmCommentMonthlyForm');
        form.action = "{{ url('/monthly/plans') }}/" + planId + "/gm-comment";
        document.getElementById('gmCommentPlanTitle').innerText = title;
        document.getElementById('gmCommentInput').value = comment || '';
        const modal = new bootstrap.Modal(document.getElementById('gmCommentMonthlyModal'));
        modal.show();
    }

    function initPlanBulkSelection() {
        const selectAll = document.getElementById('selectAllPlans');
        const checkboxes = document.querySelectorAll('.plan-chk');
        const btnSelected = document.getElementById('btnApproveSelectedPlans');
        const countSpan = document.getElementById('selectedPlansCount');

        if (!checkboxes.length) return;

        function updateState() {
            const checked = Array.from(checkboxes).filter(c => c.checked);
            if (countSpan) countSpan.textContent = checked.length;
            if (btnSelected) {
                if (checked.length > 0) {
                    btnSelected.classList.remove('d-none');
                } else {
                    btnSelected.classList.add('d-none');
                }
            }
            if (selectAll) {
                selectAll.checked = checked.length > 0 && checked.length === checkboxes.length;
                selectAll.indeterminate = checked.length > 0 && checked.length < checkboxes.length;
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checkboxes.forEach(c => c.checked = selectAll.checked);
                updateState();
            });
        }

        checkboxes.forEach(c => c.addEventListener('change', updateState));
    }

    function submitSelectedPlans() {
        const checked = Array.from(document.querySelectorAll('.plan-chk:checked'));
        if (!checked.length) {
            alert('Please select at least one pending monthly plan to approve.');
            return;
        }
        if (!confirm('Approve ' + checked.length + ' selected monthly plan(s)?')) return;

        const form = document.getElementById('bulkApprovePlansForm');
        const container = document.getElementById('bulkApprovePlansInputs');
        if (!form || !container) return;

        container.innerHTML = '';
        checked.forEach(c => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'plan_ids[]';
            input.value = c.value;
            container.appendChild(input);
        });
        form.submit();
    }

    document.addEventListener('DOMContentLoaded', function() {
        togglePlanType();
        initPlanBulkSelection();
        const createModal = document.getElementById('createMonthlyPlanModal');
        if (createModal) {
            createModal.addEventListener('shown.bs.modal', function () {
                togglePlanType();
            });
            createModal.addEventListener('hidden.bs.modal', function () {
                const form = createModal.querySelector('form');
                if (form) {
                    form.reset();
                    togglePlanType();
                }
            });
        }
    });
</script>
@endsection

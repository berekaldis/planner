@extends('layouts.app')

@section('title', 'Bulk Quarterly Performance Submission - ' . $department->department_name)

@section('content')
<style>
.quarter-pill {
    padding: 0.6rem 1.25rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.88rem;
    transition: all 0.2s ease;
    border: 1px solid #E5E7EB;
    background: #ffffff;
    color: #4B5563;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.quarter-pill:hover {
    background: #F3F4F6;
    color: #111827;
}
.quarter-pill.active {
    background: #0A1128;
    color: #ffffff;
    border-color: #0A1128;
    box-shadow: 0 4px 12px rgba(10, 17, 40, 0.15);
}
.quarter-pill.active .badge {
    background: #D97706 !important;
    color: #ffffff !important;
}
.month-section-header {
    background: #F8FAFC;
    border-left: 4px solid #3B82F6;
    padding: 0.85rem 1.25rem;
    border-radius: 6px;
    margin-bottom: 1rem;
}
.plan-eval-row {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    padding: 1rem 1.25rem;
    margin-bottom: 0.85rem;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.plan-eval-row:hover {
    border-color: #CBD5E1;
    box-shadow: 0 2px 6px rgba(0,0,0,0.03);
}
.plan-eval-row.status-done {
    border-left: 4px solid #10B981;
}
.plan-eval-row.status-partial {
    border-left: 4px solid #F59E0B;
}
.plan-eval-row.status-not_done {
    border-left: 4px solid #EF4444;
}
.plan-eval-row.status-pending {
    border-left: 4px solid #94A3B8;
}
</style>

<!-- Page Header & Filter Controls -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                <i class="fas fa-layer-group me-1"></i> Quarterly Performance Module
            </span>
            <span class="text-muted small">&bull;</span>
            <span class="text-muted small fw-semibold">Last 3 Months Bulk Submission</span>
        </div>
        <h4 class="fw-bold text-dark mb-0">
            {{ $department->department_name }} ({{ $department->department_code }})
        </h4>
    </div>

    <!-- Period & Dept Selectors -->
    <div class="d-flex flex-wrap align-items-center gap-2">
        <form method="GET" action="{{ route('performance.quarterly') }}" id="filterForm" class="d-flex gap-2">
            @if($allDepartments->count() > 1)
                <select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($allDepartments as $d)
                        <option value="{{ $d->id }}" {{ $d->id == $department->id ? 'selected' : '' }}>
                            {{ $d->department_name }}
                        </option>
                    @endforeach
                </select>
            @endif

            <input type="hidden" name="quarter" value="{{ $selectedQuarter }}">

            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach(get_planning_years() as $yr)
                    <option value="{{ $yr }}" {{ $yr == $selectedYear ? 'selected' : '' }}>{{ $yr }}</option>
                @endforeach
            </select>
        </form>

        <a href="{{ route('quarterly.voucher', ['department_id' => $department->id, 'year' => $selectedYear, 'quarter' => $selectedQuarter]) }}" class="btn btn-sm btn-outline-primary" target="_blank">
            <i class="fas fa-receipt me-1"></i> Official Voucher
        </a>

        <a href="{{ route('performance.quarterly.export', ['department_id' => $department->id, 'year' => $selectedYear, 'quarter' => $selectedQuarter]) }}" class="btn btn-sm btn-outline-success">
            <i class="fas fa-file-excel me-1"></i> Export to Excel
        </a>
    </div>
</div>

<!-- Quarter Navigation Pills -->
<div class="d-flex flex-wrap gap-2 mb-4">
    @foreach($quarters as $qCode => $qData)
        <a href="{{ route('performance.quarterly', ['department_id' => $department->id, 'year' => $selectedYear, 'quarter' => $qCode]) }}" 
           class="quarter-pill {{ $selectedQuarter === $qCode ? 'active' : '' }}">
            <span>{{ $qData['label'] }}</span>
            <span class="badge bg-secondary-subtle text-secondary small">{{ $qData['months'][0] }} - {{ $qData['months'][2] }}</span>
        </a>
    @endforeach
</div>

<!-- Executive Summary Status Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold text-uppercase">Quarterly Plans</span>
                <span class="badge bg-primary-subtle text-primary"><i class="fas fa-list-check"></i> 3 Months</span>
            </div>
            <div class="fs-3 fw-bold text-dark">{{ $totalPlans }}</div>
            <div class="text-muted small">Meskerem - Hidar (or cycle)</div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-success small fw-bold text-uppercase">Fully Completed</span>
                <span class="badge bg-success-subtle text-success"><i class="fas fa-check-double"></i> 100% Target</span>
            </div>
            <div class="fs-3 fw-bold text-success">{{ $donePlans }}</div>
            <div class="text-muted small">{{ $totalPlans > 0 ? round(($donePlans / $totalPlans) * 100, 1) : 0 }}% of total quarterly plans</div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-primary small fw-bold text-uppercase">Completion Rate</span>
                <span class="badge bg-primary-subtle text-primary"><i class="fas fa-chart-line"></i> Score</span>
            </div>
            <div class="fs-3 fw-bold text-primary">{{ $submission ? $submission->completion_percentage : $avgCompletionRate }}%</div>
            <div class="progress mt-1" style="height: 6px;">
                @php $comp = $submission ? $submission->completion_percentage : $avgCompletionRate; @endphp
                <div class="progress-bar {{ $comp >= 80 ? 'bg-success' : ($comp >= 50 ? 'bg-warning' : 'bg-danger') }}" 
                     role="progressbar" style="width: {{ $comp }}%"></div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold text-uppercase">Submission Status</span>
                @if($submission && $submission->status === 'SUBMITTED')
                    <span class="badge bg-success-subtle text-success"><i class="fas fa-check-circle me-1"></i> Submitted</span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary"><i class="fas fa-file-pen me-1"></i> In Draft</span>
                @endif
            </div>
            <div class="fw-bold fs-6 text-dark mt-1">
                @if($submission && $submission->approval_status === 'APPROVED')
                    <span class="text-success"><i class="fas fa-circle-check me-1"></i> GM Approved</span>
                @elseif($submission && $submission->approval_status === 'REVISION_REQUESTED')
                    <span class="text-danger"><i class="fas fa-triangle-exclamation me-1"></i> Revision Requested</span>
                @elseif($submission && $submission->status === 'SUBMITTED')
                    <span class="text-warning"><i class="fas fa-hourglass-half me-1"></i> Pending GM Review</span>
                @else
                    <span class="text-muted"><i class="fas fa-clock me-1"></i> Pending Submission</span>
                @endif
            </div>
            <div class="text-muted small">
                @if($submission && $submission->submitted_at)
                    Submitted: {{ $submission->submitted_at->format('M d, H:i') }}
                @else
                    Due end of quarter cycle
                @endif
            </div>
        </div>
    </div>
</div>

<!-- GM Review & Directives Banner (If feedback exists) -->
@if($submission && !empty($submission->gm_feedback))
    <div class="card border-0 shadow-sm mb-4 border-start border-4 border-warning bg-white">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="fas fa-comment-dots text-warning me-2"></i> General Management Executive Review
                </h6>
                <span class="badge {{ $submission->approval_status === 'APPROVED' ? 'bg-success' : 'bg-danger' }}">
                    {{ $submission->approval_status }}
                </span>
            </div>
            <p class="text-dark small mb-1" style="white-space: pre-line;">{{ $submission->gm_feedback }}</p>
            <div class="text-muted small fst-italic">
                Reviewed on {{ $submission->gm_feedback_at ? $submission->gm_feedback_at->format('M d, Y H:i') : 'N/A' }} 
                by {{ $submission->reviewer->full_name ?? 'General Management' }}
            </div>
        </div>
    </div>
@endif

<!-- GM / SuperAdmin Review Panel -->
@if((auth()->user()->isGM() || auth()->user()->isSuperAdmin()) && $submission && $submission->status === 'SUBMITTED')
    <div class="card border-0 shadow-sm mb-4 bg-light-subtle border-start border-4 border-primary">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="fw-bold text-dark mb-0">
                <i class="fas fa-stamp text-primary me-2"></i> Executive Decision &amp; Feedback (General Manager)
            </h6>
        </div>
        <div class="card-body p-3">
            <form method="POST" action="{{ route('performance.quarterly.feedback') }}">
                @csrf
                <input type="hidden" name="submission_id" value="{{ $submission->id }}">

                <div class="mb-3">
                    <label class="form-label small fw-bold">GM Directives / Feedback</label>
                    <textarea name="gm_feedback" rows="3" class="form-control form-control-sm" placeholder="Provide executive comments, approval remarks, or required corrections..." required>{{ $submission->gm_feedback ?? '' }}</textarea>
                </div>

                <div class="row g-2 align-items-center">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Approval Decision</label>
                        <select name="approval_status" class="form-select form-select-sm">
                            <option value="APPROVED" {{ $submission->approval_status === 'APPROVED' ? 'selected' : '' }}>APPROVE QUARTERLY PERFORMANCE</option>
                            <option value="REVISION_REQUESTED" {{ $submission->approval_status === 'REVISION_REQUESTED' ? 'selected' : '' }}>REQUEST REVISION</option>
                        </select>
                    </div>
                    <div class="col-md-6 text-end pt-3">
                        <button type="submit" class="btn btn-sm btn-primary px-3">
                            <i class="fas fa-paper-plane me-1"></i> Submit Executive Review
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif

<!-- Bulk Submission Form -->
<form method="POST" action="{{ route('performance.quarterly.submit') }}" id="quarterlyForm">
    @csrf
    <input type="hidden" name="department_id" value="{{ $department->id }}">
    <input type="hidden" name="year" value="{{ $selectedYear }}">
    <input type="hidden" name="quarter" value="{{ $selectedQuarter }}">

    <!-- Month-by-Month Plan Evaluations -->
    <div class="card border-0 shadow-sm mb-4 bg-white">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold text-dark mb-0">
                    <i class="fas fa-list-check text-primary me-2"></i> Monthly Plan Execution Across {{ $quarterInfo['name'] }}
                </h6>
                <small class="text-muted">Evaluate and rate each monthly plan for {{ implode(', ', $quarterMonths) }}</small>
            </div>
            <div>
                <span class="badge bg-light text-dark border">{{ $totalPlans }} Plans Total</span>
            </div>
        </div>

        <div class="card-body p-3 p-md-4">
            @forelse($quarterMonths as $monthName)
                @php
                    $mPlans = $plansByMonth[$monthName] ?? collect();
                @endphp
                <div class="mb-4">
                    <div class="month-section-header d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold text-dark fs-6">
                                <i class="fas fa-calendar-day text-primary me-2"></i> Month: {{ $monthName }}
                            </span>
                            <span class="badge bg-white text-dark border ms-2">{{ $mPlans->count() }} Plans</span>
                        </div>
                    </div>

                    @forelse($mPlans as $plan)
                        @php
                            $pStatus = $plan->performance_status ?: 'PENDING';
                            $rowClass = match($pStatus) {
                                'DONE' => 'status-done',
                                'PARTIAL' => 'status-partial',
                                'NOT_DONE' => 'status-not_done',
                                default => 'status-pending',
                            };
                        @endphp
                        <div class="plan-eval-row {{ $rowClass }}" id="plan-card-{{ $plan->id }}">
                            <div class="row g-3">
                                <!-- Plan Metadata & Title -->
                                <div class="col-lg-5">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge bg-light text-secondary border">#MP-{{ str_pad($plan->id, 4, '0', STR_PAD_LEFT) }}</span>
                                        <span class="badge {{ $plan->plan_type === 'STRATEGY' ? 'bg-purple-subtle text-purple border' : 'bg-blue-subtle text-blue border' }}" style="font-size: 0.72rem;">
                                            {{ $plan->plan_type }}
                                        </span>
                                        @if($plan->annualGoal)
                                            <span class="text-primary small fw-semibold text-truncate" style="max-width: 200px;" title="{{ $plan->annualGoal->code }}: {{ $plan->annualGoal->title }}">
                                                <i class="fas fa-bullseye me-1"></i> {{ $plan->annualGoal->code }}
                                            </span>
                                        @endif
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">{{ $plan->title }}</h6>
                                    @if(!empty($plan->monthly_target))
                                        <div class="text-muted small mb-1">
                                            <strong>Target:</strong> {{ $plan->monthly_target }}
                                        </div>
                                    @endif
                                    @if(!empty($plan->definition_of_done))
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            <strong>DoD:</strong> {{ $plan->definition_of_done }}
                                        </div>
                                    @endif
                                    <div class="text-muted small mt-1">
                                        <i class="fas fa-user-circle me-1"></i> {{ $plan->responsible_person ?: 'Dept Head' }}
                                    </div>
                                </div>

                                <!-- Evaluation Controls -->
                                <div class="col-lg-7">
                                    <div class="row g-2">
                                        <div class="col-md-5">
                                            <label class="form-label small fw-bold text-muted mb-1">Performance Status</label>
                                            <select name="evaluations[{{ $plan->id }}][performance_status]" 
                                                    class="form-select form-select-sm perf-status-select" 
                                                    data-plan-id="{{ $plan->id }}"
                                                    onchange="updatePlanCardStatus({{ $plan->id }}, this.value)">
                                                <option value="DONE" {{ $pStatus === 'DONE' ? 'selected' : '' }}>DONE (100% Completed)</option>
                                                <option value="PARTIAL" {{ $pStatus === 'PARTIAL' ? 'selected' : '' }}>PARTIAL (Under Progress)</option>
                                                <option value="NOT_DONE" {{ $pStatus === 'NOT_DONE' ? 'selected' : '' }}>NOT DONE (0% Achieved)</option>
                                                <option value="PENDING" {{ $pStatus === 'PENDING' ? 'selected' : '' }}>PENDING EVALUATION</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-bold text-muted mb-1">Achieved %</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" 
                                                       name="evaluations[{{ $plan->id }}][achievement_percentage]" 
                                                       id="achieve-pct-{{ $plan->id }}"
                                                       class="form-control" 
                                                       min="0" max="100" 
                                                       value="{{ $plan->achievement_percentage ?? ($pStatus === 'DONE' ? 100 : 0) }}">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold text-muted mb-1">Actual Output / Units</label>
                                            <input type="text" 
                                                   name="evaluations[{{ $plan->id }}][actual_result]" 
                                                   class="form-control form-control-sm" 
                                                   placeholder="e.g. 5,000 bags / 100%" 
                                                   value="{{ $plan->actual_result ?? '' }}">
                                        </div>
                                        <div class="col-12 mt-2">
                                            <label class="form-label small fw-bold text-muted mb-1">Execution Remarks &amp; Notes</label>
                                            <input type="text" 
                                                   name="evaluations[{{ $plan->id }}][completion_notes]" 
                                                   class="form-control form-control-sm" 
                                                   placeholder="Explanation, dependencies met, or notes..." 
                                                   value="{{ $plan->completion_notes ?? '' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-3 text-center text-muted bg-light rounded border border-dashed mb-3">
                            <i class="fas fa-calendar-xmark me-1"></i> No monthly plans created for {{ $monthName }}.
                        </div>
                    @endforelse
                </div>
            @empty
                <div class="text-center py-5 text-muted">
                    No quarterly months configured.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Narrative & Submission Card -->
    <div class="card border-0 shadow-sm mb-4 bg-white">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="fw-bold text-dark mb-0">
                <i class="fas fa-file-signature text-primary me-2"></i> Quarterly Executive Narrative &amp; Rollup
            </h6>
        </div>
        <div class="card-body p-3 p-md-4">
            <div class="mb-3">
                <label class="form-label small fw-bold text-dark">
                    Executive Summary for {{ $quarterInfo['name'] }} <span class="text-danger">*</span>
                </label>
                <textarea name="executive_summary" rows="4" class="form-control" placeholder="Provide a high-level executive summary of your department's accomplishments, overall performance against targets, and key deliverables across the 3 months..." required>{{ $submission->executive_summary ?? old('executive_summary') }}</textarea>
                <div class="form-text">This narrative is surfaced to the General Manager and executive committee.</div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-danger">
                        <i class="fas fa-triangle-exclamation me-1"></i> Key Challenges &amp; Bottlenecks
                    </label>
                    <textarea name="challenges" rows="3" class="form-control" placeholder="Detail any operational, supply chain, system, or inter-departmental hurdles encountered...">{{ $submission->challenges ?? old('challenges') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-primary">
                        <i class="fas fa-bullseye me-1"></i> Priorities for Next Quarter
                    </label>
                    <textarea name="next_quarter_priorities" rows="3" class="form-control" placeholder="Outline focus areas, strategic adjustments, and upcoming targets for the next cycle...">{{ $submission->next_quarter_priorities ?? old('next_quarter_priorities') }}</textarea>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pt-3 border-top">
                <div>
                    <button type="submit" 
                            formaction="{{ route('performance.quarterly.save_evaluation') }}" 
                            class="btn btn-outline-secondary">
                        <i class="fas fa-floppy-disk me-1"></i> Save Draft (Do Not Submit Yet)
                    </button>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" 
                            class="btn btn-success px-4 fw-bold"
                            onclick="return confirm('Are you sure you want to officially submit your full 3-month performance for {{ $quarterInfo['name'] }} to General Management?');">
                        <i class="fas fa-paper-plane me-1"></i> Submit Full Quarter Performance to GM
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function updatePlanCardStatus(planId, status) {
    const card = document.getElementById('plan-card-' + planId);
    const pctInput = document.getElementById('achieve-pct-' + planId);
    if (!card) return;

    card.classList.remove('status-done', 'status-partial', 'status-not_done', 'status-pending');
    
    if (status === 'DONE') {
        card.classList.add('status-done');
        if (pctInput && (pctInput.value === '0' || !pctInput.value)) {
            pctInput.value = 100;
        }
    } else if (status === 'PARTIAL') {
        card.classList.add('status-partial');
        if (pctInput && (pctInput.value === '100' || pctInput.value === '0' || !pctInput.value)) {
            pctInput.value = 50;
        }
    } else if (status === 'NOT_DONE') {
        card.classList.add('status-not_done');
        if (pctInput) {
            pctInput.value = 0;
        }
    } else {
        card.classList.add('status-pending');
    }
}
</script>
@endsection

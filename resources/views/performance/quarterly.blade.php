@extends('layouts.app')

@section('title', 'Quarterly Performance - ' . $department->department_name)

@section('content')
<style>
.quarter-pill {
    padding: 0.5rem 1.15rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.2s ease;
    border: 1px solid #E5E7EB;
    background: #ffffff;
    color: #4B5563;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
}
.quarter-pill:hover {
    background: #F3F4F6;
    color: #111827;
}
.quarter-pill.active {
    background: #0A1128;
    color: #ffffff;
    border-color: #0A1128;
    box-shadow: 0 3px 8px rgba(10, 17, 40, 0.12);
}
.quarter-pill.active .badge {
    background: #D97706 !important;
    color: #ffffff !important;
}
.month-tab-btn {
    border-radius: 6px;
    font-size: 0.82rem;
    font-weight: 600;
    padding: 0.35rem 0.85rem;
    transition: all 0.15s ease;
}
.month-tab-btn.active {
    background: #0A1128 !important;
    color: #ffffff !important;
    border-color: #0A1128 !important;
}
.perf-row-item {
    transition: background-color 0.15s ease, border-left-color 0.15s ease;
}
.perf-row-item.status-done {
    border-left: 4px solid #10B981 !important;
    background-color: rgba(16, 185, 129, 0.02);
}
.perf-row-item.status-partial {
    border-left: 4px solid #F59E0B !important;
    background-color: rgba(245, 158, 11, 0.02);
}
.perf-row-item.status-not_done {
    border-left: 4px solid #EF4444 !important;
    background-color: rgba(239, 68, 68, 0.02);
}
.perf-row-item.status-pending {
    border-left: 4px solid #94A3B8 !important;
}
.table-compact th, .table-compact td {
    padding: 0.65rem 0.75rem;
    vertical-align: middle;
}
</style>

<!-- Page Header & Filter Controls -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5 small">
                <i class="fas fa-layer-group me-1"></i> Quarterly Performance
            </span>
            <span class="text-muted small">&bull;</span>
            <span class="text-dark small fw-semibold">{{ $quarterInfo['name'] }} ({{ implode(' - ', [$quarterMonths[0], $quarterMonths[2]]) }})</span>
        </div>
        <h4 class="fw-bold text-dark mb-0">
            {{ $department->department_name }} <span class="text-muted fw-normal fs-6">({{ $department->department_code }})</span>
        </h4>
    </div>

    <!-- Period & Dept Selectors -->
    <div class="d-flex flex-wrap align-items-center gap-2">
        <form method="GET" action="{{ route('performance.quarterly') }}" id="filterForm" class="d-flex align-items-center gap-2">
            @if($allDepartments->count() > 1)
                <select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()" style="max-width: 200px;">
                    @foreach($allDepartments as $d)
                        <option value="{{ $d->id }}" {{ $d->id == $department->id ? 'selected' : '' }}>
                            {{ $d->department_name }}
                        </option>
                    @endforeach
                </select>
            @endif

            <input type="hidden" name="quarter" value="{{ $selectedQuarter }}">

            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 120px;">
                @foreach(get_planning_years() as $yr)
                    <option value="{{ $yr }}" {{ $yr == $selectedYear ? 'selected' : '' }}>{{ $yr }}</option>
                @endforeach
            </select>
        </form>

        <a href="{{ route('quarterly.voucher', ['department_id' => $department->id, 'year' => $selectedYear, 'quarter' => $selectedQuarter]) }}" 
           class="btn btn-sm btn-outline-primary" target="_blank" title="Print/View Cryptographically Verified Voucher">
            <i class="fas fa-receipt me-1"></i> Voucher
        </a>

        <a href="{{ route('performance.quarterly.export', ['department_id' => $department->id, 'year' => $selectedYear, 'quarter' => $selectedQuarter]) }}" 
           class="btn btn-sm btn-outline-success" title="Download Excel Sheet">
            <i class="fas fa-file-excel me-1"></i> Excel
        </a>
    </div>
</div>

<!-- Quarter Navigation Pills -->
<div class="d-flex flex-wrap gap-2 mb-3">
    @foreach($quarters as $qCode => $qData)
        <a href="{{ route('performance.quarterly', ['department_id' => $department->id, 'year' => $selectedYear, 'quarter' => $qCode]) }}" 
           class="quarter-pill {{ $selectedQuarter === $qCode ? 'active' : '' }}">
            <span>{{ $qData['label'] }} &bull; {{ $qData['name'] }}</span>
            <span class="badge bg-secondary-subtle text-secondary small">{{ $qData['months'][0] }}–{{ $qData['months'][2] }}</span>
        </a>
    @endforeach
</div>

<!-- Executive Summary Metric Tiles -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white" style="border-radius: 10px;">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted small fw-bold text-uppercase">Quarterly Plans</span>
                <span class="badge bg-light text-secondary border"><i class="fas fa-list-check me-1"></i>3 Months</span>
            </div>
            <div class="fs-4 fw-bold text-dark">{{ $totalPlans }} <span class="fs-6 text-muted fw-normal">plans</span></div>
            <div class="text-muted small" style="font-size: 0.78rem;">Across {{ $quarterMonths[0] }} to {{ $quarterMonths[2] }}</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white" style="border-radius: 10px;">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-success small fw-bold text-uppercase">Completed</span>
                <span class="badge bg-success-subtle text-success"><i class="fas fa-check-circle me-1"></i>100% DONE</span>
            </div>
            <div class="fs-4 fw-bold text-success">{{ $donePlans }} <span class="fs-6 text-muted fw-normal">/ {{ $totalPlans }}</span></div>
            <div class="text-muted small" style="font-size: 0.78rem;">{{ $totalPlans > 0 ? round(($donePlans / $totalPlans) * 100, 1) : 0 }}% of quarterly targets achieved</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white" style="border-radius: 10px;">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-primary small fw-bold text-uppercase">Avg Score</span>
                <span class="badge bg-primary-subtle text-primary fw-bold">{{ $submission ? $submission->completion_percentage : $avgCompletionRate }}%</span>
            </div>
            @php $comp = $submission ? $submission->completion_percentage : $avgCompletionRate; @endphp
            <div class="fs-4 fw-bold text-primary">{{ $comp }}%</div>
            <div class="progress mt-1" style="height: 5px;">
                <div class="progress-bar {{ $comp >= 80 ? 'bg-success' : ($comp >= 50 ? 'bg-warning' : 'bg-danger') }}" 
                     role="progressbar" style="width: {{ $comp }}%"></div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white" style="border-radius: 10px;">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted small fw-bold text-uppercase">GM Status</span>
                @if($submission && $submission->status === 'SUBMITTED')
                    <span class="badge bg-success-subtle text-success"><i class="fas fa-check me-1"></i>Submitted</span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary">In Draft</span>
                @endif
            </div>
            <div class="fw-bold small text-dark mt-1">
                @if($submission && $submission->approval_status === 'APPROVED')
                    <span class="text-success"><i class="fas fa-circle-check me-1"></i>GM Approved</span>
                @elseif($submission && $submission->approval_status === 'REVISION_REQUESTED')
                    <span class="text-danger"><i class="fas fa-triangle-exclamation me-1"></i>Revision Requested</span>
                @elseif($submission && $submission->status === 'SUBMITTED')
                    <span class="text-warning"><i class="fas fa-hourglass-half me-1"></i>Pending GM Review</span>
                @else
                    <span class="text-muted"><i class="fas fa-clock me-1"></i>Ready to Submit</span>
                @endif
            </div>
            <div class="text-muted small" style="font-size: 0.76rem;">
                {{ $submission && $submission->submitted_at ? 'Submitted ' . $submission->submitted_at->format('M d, H:i') : 'Draft in progress' }}
            </div>
        </div>
    </div>
</div>

<!-- GM Review Feedback Banner (If exists) -->
@if($submission && !empty($submission->gm_feedback))
    <div class="alert alert-warning border-0 shadow-sm mb-4 bg-white border-start border-4 border-warning p-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <h6 class="fw-bold text-dark mb-0">
                <i class="fas fa-comment-dots text-warning me-1.5"></i> General Management Executive Review
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
@endif

<!-- GM / SuperAdmin Review Panel -->
@if((auth()->user()->isGM() || auth()->user()->isSuperAdmin()) && $submission && $submission->status === 'SUBMITTED')
    <div class="card border-0 shadow-sm mb-4 bg-light border-start border-4 border-primary">
        <div class="card-header bg-transparent py-2.5 px-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0">
                <i class="fas fa-stamp text-primary me-2"></i> Executive Decision &amp; Feedback (General Manager)
            </h6>
            <span class="badge bg-primary">Executive Action</span>
        </div>
        <div class="card-body p-3">
            <form method="POST" action="{{ route('performance.quarterly.feedback') }}">
                @csrf
                <input type="hidden" name="submission_id" value="{{ $submission->id }}">

                <div class="mb-2">
                    <label class="form-label small fw-bold text-secondary mb-1">GM Directives / Feedback</label>
                    <textarea name="gm_feedback" rows="2" class="form-control form-control-sm" placeholder="Provide executive comments, approval remarks, or required corrections..." required>{{ $submission->gm_feedback ?? '' }}</textarea>
                </div>

                <div class="row g-2 align-items-center">
                    <div class="col-md-7">
                        <select name="approval_status" class="form-select form-select-sm">
                            <option value="APPROVED" {{ $submission->approval_status === 'APPROVED' ? 'selected' : '' }}>APPROVE QUARTERLY PERFORMANCE</option>
                            <option value="REVISION_REQUESTED" {{ $submission->approval_status === 'REVISION_REQUESTED' ? 'selected' : '' }}>REQUEST REVISION</option>
                        </select>
                    </div>
                    <div class="col-md-5 text-end">
                        <button type="submit" class="btn btn-sm btn-primary px-3 fw-bold">
                            <i class="fas fa-paper-plane me-1"></i> Submit Decision
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif

<!-- Evaluation & Submission Form -->
<form method="POST" action="{{ route('performance.quarterly.submit') }}" id="quarterlyForm">
    @csrf
    <input type="hidden" name="department_id" value="{{ $department->id }}">
    <input type="hidden" name="year" value="{{ $selectedYear }}">
    <input type="hidden" name="quarter" value="{{ $selectedQuarter }}">

    <!-- Month Filter Tabs & Global Actions -->
    <div class="card border-0 shadow-sm mb-4 bg-white" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div>
                <h6 class="fw-bold text-dark mb-0">
                    <i class="fas fa-tasks text-primary me-2"></i> Deliverables Evaluation &amp; Performance
                </h6>
                <p class="text-muted small mb-0">Evaluate each monthly plan deliverable as DONE (100%), PARTIAL (50%), or NOT DONE (0%).</p>
            </div>

            <!-- Month Quick Filter Tabs -->
            <div class="d-flex flex-wrap align-items-center gap-1.5">
                <button type="button" class="btn btn-sm month-tab-btn active" onclick="filterMonthTab('all', this)">
                    All Months <span class="badge bg-secondary ms-1">{{ $totalPlans }}</span>
                </button>
                @foreach($quarterMonths as $mName)
                    @php
                        $mCount = ($plansByMonth[$mName] ?? collect())->count();
                        $mSlug = \Illuminate\Support\Str::slug($mName);
                    @endphp
                    <button type="button" class="btn btn-sm btn-light border month-tab-btn" onclick="filterMonthTab('{{ $mSlug }}', this)">
                        {{ $mName }} <span class="badge bg-light text-dark border ms-1">{{ $mCount }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="card-body p-0">
            @foreach($quarterMonths as $monthName)
                @php
                    $mPlans = $plansByMonth[$monthName] ?? collect();
                    $mDone = $mPlans->where('performance_status', 'DONE')->count();
                    $mSlug = \Illuminate\Support\Str::slug($monthName);
                @endphp
                <div class="month-quarter-section border-bottom" id="month-section-{{ $mSlug }}" data-month-slug="{{ $mSlug }}">
                    <!-- Month Section Header -->
                    <div class="py-2.5 px-3 px-md-4 bg-light d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold text-dark fs-6">
                                <i class="far fa-calendar text-primary me-1.5"></i> {{ $monthName }}
                            </span>
                            <span class="badge bg-white text-dark border small">{{ $mPlans->count() }} Deliverables</span>
                            @if($mPlans->count() > 0)
                                <span class="badge {{ $mDone === $mPlans->count() ? 'bg-success text-white' : 'bg-success-subtle text-success' }} small">
                                    <i class="fas fa-check-circle me-1"></i>{{ $mDone }}/{{ $mPlans->count() }} Done
                                </span>
                            @endif
                        </div>

                        <!-- Month Level Quick Actions -->
                        <div class="d-flex align-items-center gap-2">
                            @if($mPlans->count() > 0)
                                <button type="button" class="btn btn-xs btn-outline-success fw-semibold px-2.5 py-1" onclick="quickMarkMonthDone('{{ $mSlug }}')" title="Quickly set all plans in {{ $monthName }} to DONE with 100% achievement">
                                    <i class="fas fa-check-double me-1"></i> Mark All Done
                                </button>
                            @endif
                            <button type="button" class="btn btn-xs btn-primary fw-semibold px-2.5 py-1 shadow-xs" onclick="openAddPlanModal('{{ $monthName }}')">
                                <i class="fas fa-plus me-1"></i> Add {{ $monthName }} Plan
                            </button>
                        </div>
                    </div>

                    <!-- Plans Table for this Month -->
                    @if($mPlans->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover table-compact align-middle mb-0" style="font-size: 0.85rem;">
                                <thead class="table-light text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.03em;">
                                    <tr>
                                        <th style="min-width: 280px; padding-left: 1.25rem;">Deliverable &amp; Target</th>
                                        <th style="width: 140px;">Type / Goal</th>
                                        <th style="width: 180px;">Performance Status</th>
                                        <th style="width: 95px;">Achieved %</th>
                                        <th style="min-width: 160px;">Output / Units</th>
                                        <th style="min-width: 180px;">Remarks</th>
                                        <th style="width: 45px; padding-right: 1.25rem;" class="text-center"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($mPlans as $plan)
                                        @php
                                            $pStatus = $plan->performance_status ?: 'PENDING';
                                            $rowStatusClass = match($pStatus) {
                                                'DONE' => 'status-done',
                                                'PARTIAL' => 'status-partial',
                                                'NOT_DONE' => 'status-not_done',
                                                default => 'status-pending',
                                            };
                                        @endphp
                                        <tr class="perf-row-item {{ $rowStatusClass }}" id="plan-row-{{ $plan->id }}">
                                            <!-- Col 1: Deliverable Title & Target -->
                                            <td style="padding-left: 1.25rem;">
                                                <div class="d-flex align-items-center gap-1.5 mb-0.5">
                                                    <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;">#MP-{{ str_pad($plan->id, 4, '0', STR_PAD_LEFT) }}</span>
                                                    <span class="fw-bold text-dark">{{ $plan->title }}</span>
                                                </div>
                                                @if(!empty($plan->monthly_target))
                                                    <div class="text-muted small" style="font-size: 0.77rem; line-height: 1.3;">
                                                        <strong class="text-secondary">Target:</strong> {{ $plan->monthly_target }}
                                                    </div>
                                                @endif
                                                @if(!empty($plan->definition_of_done) && $plan->definition_of_done !== $plan->monthly_target)
                                                    <div class="text-muted" style="font-size: 0.73rem; line-height: 1.3;">
                                                        <strong class="text-secondary">DoD:</strong> {{ $plan->definition_of_done }}
                                                    </div>
                                                @endif
                                                <div class="text-muted" style="font-size: 0.72rem;">
                                                    <i class="far fa-user text-secondary me-1"></i> {{ $plan->responsible_person ?: 'Dept Head' }}
                                                </div>
                                            </td>

                                            <!-- Col 2: Type & Goal -->
                                            <td>
                                                <div>
                                                    <span class="badge {{ $plan->plan_type === 'STRATEGY' ? 'bg-purple-subtle text-purple border' : 'bg-primary-subtle text-primary border' }}" style="font-size: 0.68rem;">
                                                        {{ $plan->plan_type }}
                                                    </span>
                                                </div>
                                                @if($plan->annualGoal)
                                                    <div class="text-primary small fw-semibold text-truncate mt-0.5" style="max-width: 130px;" title="{{ $plan->annualGoal->code }}: {{ $plan->annualGoal->title }}">
                                                        <i class="fas fa-bullseye me-1"></i>{{ $plan->annualGoal->code }}
                                                    </div>
                                                @endif
                                            </td>

                                            <!-- Col 3: Status Dropdown -->
                                            <td>
                                                <select name="evaluations[{{ $plan->id }}][performance_status]" 
                                                        class="form-select form-select-sm perf-status-select fw-semibold" 
                                                        data-plan-id="{{ $plan->id }}"
                                                        onchange="updateRowStatus({{ $plan->id }}, this.value)">
                                                    <option value="DONE" {{ $pStatus === 'DONE' ? 'selected' : '' }}>🟢 DONE (100%)</option>
                                                    <option value="PARTIAL" {{ $pStatus === 'PARTIAL' ? 'selected' : '' }}>🟡 PARTIAL (50%)</option>
                                                    <option value="NOT_DONE" {{ $pStatus === 'NOT_DONE' ? 'selected' : '' }}>🔴 NOT DONE (0%)</option>
                                                    <option value="PENDING" {{ $pStatus === 'PENDING' ? 'selected' : '' }}>⚪ PENDING</option>
                                                </select>
                                            </td>

                                            <!-- Col 4: Achievement % -->
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" 
                                                           name="evaluations[{{ $plan->id }}][achievement_percentage]" 
                                                           id="achieve-pct-{{ $plan->id }}"
                                                           class="form-control form-control-sm text-center fw-bold" 
                                                           min="0" max="100" 
                                                           value="{{ $plan->achievement_percentage ?? ($pStatus === 'DONE' ? 100 : 0) }}">
                                                    <span class="input-group-text px-1 text-muted" style="font-size: 0.75rem;">%</span>
                                                </div>
                                            </td>

                                            <!-- Col 5: Actual Output / Units -->
                                            <td>
                                                <input type="text" 
                                                       name="evaluations[{{ $plan->id }}][actual_result]" 
                                                       class="form-control form-control-sm" 
                                                       placeholder="Output units / metrics..." 
                                                       value="{{ $plan->actual_result ?? '' }}">
                                            </td>

                                            <!-- Col 6: Remarks -->
                                            <td>
                                                <input type="text" 
                                                       name="evaluations[{{ $plan->id }}][completion_notes]" 
                                                       class="form-control form-control-sm" 
                                                       placeholder="Operational remarks..." 
                                                       value="{{ $plan->completion_notes ?? '' }}">
                                            </td>

                                            <!-- Col 7: Delete Action -->
                                            <td style="padding-right: 1.25rem;" class="text-center">
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="confirmDeletePlan({{ $plan->id }}, '{{ addslashes($plan->title) }}')" title="Delete deliverable">
                                                    <i class="far fa-trash-can"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <!-- Compact Empty State for Month -->
                        <div class="py-4 px-3 text-center bg-white">
                            <div class="text-muted small mb-2">No plans recorded for <strong>{{ $monthName }}</strong>.</div>
                            <button type="button" class="btn btn-xs btn-outline-primary fw-semibold" onclick="openAddPlanModal('{{ $monthName }}')">
                                <i class="fas fa-plus me-1"></i> Add {{ $monthName }} Plan
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Quarterly Executive Narrative & Submission Card -->
    <div class="card border-0 shadow-sm mb-4 bg-white" style="border-radius: 12px;">
        <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom">
            <h6 class="fw-bold text-dark mb-0">
                <i class="fas fa-file-signature text-primary me-2"></i> Executive Summary &amp; Final Rollup
            </h6>
            <p class="text-muted small mb-0">High-level departmental narrative for the General Manager and executive committee.</p>
        </div>
        <div class="card-body p-3 p-md-4">
            <div class="mb-3">
                <label class="form-label small fw-bold text-dark mb-1">
                    Executive Summary for {{ $quarterInfo['name'] }} <span class="text-danger">*</span>
                </label>
                <textarea name="executive_summary" rows="3" class="form-control" placeholder="Provide a concise executive summary of your department's accomplishments, milestones, and performance against targets across {{ implode(', ', $quarterMonths) }}..." required>{{ $submission->executive_summary ?? old('executive_summary') }}</textarea>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-danger mb-1">
                        <i class="fas fa-triangle-exclamation me-1"></i> Key Challenges &amp; Bottlenecks
                    </label>
                    <textarea name="challenges" rows="2" class="form-control form-control-sm" placeholder="Operational, supply chain, system, or cross-department hurdles...">{{ $submission->challenges ?? old('challenges') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-primary mb-1">
                        <i class="fas fa-bullseye me-1"></i> Priorities for Next Quarter
                    </label>
                    <textarea name="next_quarter_priorities" rows="2" class="form-control form-control-sm" placeholder="Key focus areas and strategic priorities for upcoming cycle...">{{ $submission->next_quarter_priorities ?? old('next_quarter_priorities') }}</textarea>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-3 border-top">
                <button type="submit" 
                        formaction="{{ route('performance.quarterly.save_evaluation') }}" 
                        class="btn btn-outline-secondary btn-sm px-3 fw-semibold">
                    <i class="fas fa-floppy-disk me-1"></i> Save Draft (Do Not Submit Yet)
                </button>

                <div class="d-flex align-items-center gap-2">
                    <button type="submit" 
                            class="btn btn-success btn-sm px-4 py-2 fw-bold shadow-sm"
                            onclick="return confirm('Are you sure you want to officially submit your full 3-month performance for {{ $quarterInfo['name'] }} to General Management?');">
                        <i class="fas fa-paper-plane me-1.5"></i> Submit Full Quarter Performance to GM
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Hidden form for deleting a plan -->
<form id="deletePlanForm" method="POST" action="" style="display:none;">
    @csrf
    @method('DELETE')
</form>

<!-- Modal: Add Monthly Plan & Performance -->
<div class="modal fade" id="addPlanModal" tabindex="-1" aria-labelledby="addPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <form method="POST" action="{{ route('performance.quarterly.add_plan') }}" id="modalAddPlanForm">
                @csrf
                <input type="hidden" name="department_id" value="{{ $department->id }}">
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                
                <div class="modal-header bg-dark text-white py-3 px-4">
                    <h6 class="modal-title fw-bold mb-0" id="addPlanModalLabel">
                        <i class="fas fa-plus-circle text-warning me-2"></i> Add Monthly Plan &amp; Performance Evaluation
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Ethiopian Month <span class="text-danger">*</span></label>
                            <select name="month" id="modalPlanMonth" class="form-select form-select-sm" required>
                                @foreach($quarterMonths as $m)
                                    <option value="{{ $m }}">{{ $m }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Plan Category / Type <span class="text-danger">*</span></label>
                            <select name="plan_type" id="modalPlanType" class="form-select form-select-sm" onchange="toggleModalGoalSelect(this.value)" required>
                                <option value="OPERATIONAL">OPERATIONAL (Core Departmental Deliverable)</option>
                                <option value="STRATEGY">STRATEGY (Aligned to 58 Annual Goals)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Strategy Goal Link (Dynamic) -->
                    <div class="mb-3" id="modalGoalContainer" style="display: none;">
                        <label class="form-label small fw-bold text-primary">
                            <i class="fas fa-bullseye me-1"></i> Link to Annual Strategic Goal <span class="text-danger">*</span>
                        </label>
                        <select name="annual_goal_id" id="modalAnnualGoalId" class="form-select form-select-sm">
                            <option value="">-- Select Annual Strategic Goal --</option>
                            @foreach($annualGoals as $g)
                                <option value="{{ $g->id }}">{{ $g->code }} - {{ $g->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Monthly Plan Title / Deliverable <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="modalPlanTitle" class="form-control form-control-sm" placeholder="e.g. Conduct branch equipment maintenance & calibration" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Planned Monthly Target <span class="text-danger">*</span></label>
                            <input type="text" name="monthly_target" id="modalPlanTarget" class="form-control form-control-sm" placeholder="e.g. 25 retail branches serviced" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Definition of Done (DoD)</label>
                            <input type="text" name="definition_of_done" id="modalPlanDoD" class="form-control form-control-sm" placeholder="e.g. Signed maintenance checklist for each branch">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Responsible Person</label>
                            <input type="text" name="responsible_person" id="modalPlanResp" class="form-control form-control-sm" value="{{ auth()->user()->full_name ?: auth()->user()->username }}" placeholder="Lead name or title">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Priority</label>
                            <select name="priority" class="form-select form-select-sm">
                                <option value="HIGH" selected>HIGH</option>
                                <option value="MEDIUM">MEDIUM</option>
                                <option value="LOW">LOW</option>
                            </select>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded-3 border mb-2">
                        <div class="fw-bold small text-dark mb-2">
                            <i class="fas fa-chart-line text-success me-1"></i> Performance Evaluation
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-secondary mb-1">Status</label>
                                <select name="performance_status" id="modalPerfStatus" class="form-select form-select-sm" onchange="syncModalPct(this.value)" required>
                                    <option value="DONE" selected>DONE (100% Completed)</option>
                                    <option value="PARTIAL">PARTIAL (Under Progress)</option>
                                    <option value="NOT_DONE">NOT DONE (0%)</option>
                                    <option value="PENDING">PENDING</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-secondary mb-1">Achieved %</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="achievement_percentage" id="modalAchievePct" class="form-control text-center fw-bold" min="0" max="100" value="100">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-bold text-secondary mb-1">Actual Output / Result</label>
                                <input type="text" name="actual_result" id="modalActualResult" class="form-control form-control-sm" placeholder="e.g. 25/25 branches completed">
                            </div>
                            <div class="col-12 mt-2">
                                <label class="form-label small fw-bold text-secondary mb-1">Remarks &amp; Notes</label>
                                <input type="text" name="completion_notes" id="modalNotes" class="form-control form-control-sm" placeholder="Notes, explanations, or milestones reached...">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2.5 px-4">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success fw-bold px-3">
                        <i class="fas fa-check-circle me-1"></i> Save Plan &amp; Performance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddPlanModal(month) {
    if (month) {
        const monthSelect = document.getElementById('modalPlanMonth');
        if (monthSelect) {
            monthSelect.value = month;
        }
    }
    document.getElementById('modalPlanTitle').value = '';
    document.getElementById('modalPlanTarget').value = '';
    document.getElementById('modalPlanDoD').value = '';
    document.getElementById('modalActualResult').value = '';
    document.getElementById('modalNotes').value = '';
    document.getElementById('modalPerfStatus').value = 'DONE';
    document.getElementById('modalAchievePct').value = 100;

    const typeSelect = document.getElementById('modalPlanType');
    typeSelect.value = 'OPERATIONAL';
    toggleModalGoalSelect('OPERATIONAL');

    const modalEl = document.getElementById('addPlanModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function toggleModalGoalSelect(val) {
    const container = document.getElementById('modalGoalContainer');
    const goalSelect = document.getElementById('modalAnnualGoalId');
    if (val === 'STRATEGY') {
        container.style.display = 'block';
        goalSelect.setAttribute('required', 'required');
    } else {
        container.style.display = 'none';
        goalSelect.removeAttribute('required');
        goalSelect.value = '';
    }
}

function syncModalPct(status) {
    const pctInput = document.getElementById('modalAchievePct');
    if (status === 'DONE') {
        pctInput.value = 100;
    } else if (status === 'PARTIAL') {
        pctInput.value = 50;
    } else if (status === 'NOT_DONE') {
        pctInput.value = 0;
    }
}

function confirmDeletePlan(planId, title) {
    if (confirm('Are you sure you want to delete this plan?\n\n"' + title + '"')) {
        const form = document.getElementById('deletePlanForm');
        form.action = "{{ url('performance/quarterly/plan') }}/" + planId;
        form.submit();
    }
}

function updateRowStatus(planId, status) {
    const row = document.getElementById('plan-row-' + planId);
    const pctInput = document.getElementById('achieve-pct-' + planId);
    if (!row) return;

    row.classList.remove('status-done', 'status-partial', 'status-not_done', 'status-pending');
    
    if (status === 'DONE') {
        row.classList.add('status-done');
        if (pctInput && (pctInput.value === '0' || !pctInput.value)) {
            pctInput.value = 100;
        }
    } else if (status === 'PARTIAL') {
        row.classList.add('status-partial');
        if (pctInput && (pctInput.value === '100' || pctInput.value === '0' || !pctInput.value)) {
            pctInput.value = 50;
        }
    } else if (status === 'NOT_DONE') {
        row.classList.add('status-not_done');
        if (pctInput) {
            pctInput.value = 0;
        }
    } else {
        row.classList.add('status-pending');
    }
}

function quickMarkMonthDone(monthSlug) {
    const section = document.getElementById('month-section-' + monthSlug);
    if (!section) return;

    const selects = section.querySelectorAll('.perf-status-select');
    selects.forEach(select => {
        select.value = 'DONE';
        const planId = select.dataset.planId;
        const pctInput = document.getElementById('achieve-pct-' + planId);
        if (pctInput) {
            pctInput.value = 100;
        }
        updateRowStatus(planId, 'DONE');
    });
}

function filterMonthTab(tabKey, btn) {
    document.querySelectorAll('.month-tab-btn').forEach(b => {
        b.classList.remove('active', 'btn-dark');
        b.classList.add('btn-light');
    });
    btn.classList.remove('btn-light');
    btn.classList.add('active', 'btn-dark');

    const sections = document.querySelectorAll('.month-quarter-section');
    sections.forEach(sec => {
        if (tabKey === 'all' || sec.dataset.monthSlug === tabKey) {
            sec.style.display = 'block';
        } else {
            sec.style.display = 'none';
        }
    });
}
</script>
@endsection

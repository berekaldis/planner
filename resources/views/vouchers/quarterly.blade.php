@extends('layouts.app')

@section('title', 'Quarterly Performance Voucher - ' . $department->department_name)

@section('content')
<style>
/* ══════════════════════════════════════════════════════════
   OFFICIAL VOUCHER STYLING (KALDIS COFFEE PLC)
   ══════════════════════════════════════════════════════════ */
.voucher-wrapper {
    max-width: 1120px;
    margin: 0 auto 3rem auto;
}

.voucher-card {
    background: #ffffff;
    border: 2px solid #D1D5DB;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
    position: relative;
    overflow: hidden;
}

.voucher-header {
    background: linear-gradient(135deg, #0A1128 0%, #1C2541 100%);
    color: #ffffff;
    padding: 2rem 2.5rem;
    border-bottom: 4px solid #D97706;
}

.voucher-brand-logo {
    width: 68px;
    height: 68px;
    object-fit: contain;
    border-radius: 50%;
    border: 3px solid #F59E0B;
    background: #ffffff;
    padding: 4px;
}

.voucher-ref-badge {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.25);
    padding: 0.35rem 0.85rem;
    border-radius: 6px;
    font-family: monospace;
    font-size: 0.9rem;
    letter-spacing: 0.05em;
}

.voucher-status-stamp {
    display: inline-block;
    padding: 0.5rem 1.25rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    border-radius: 8px;
    font-size: 0.85rem;
    border: 2px solid currentColor;
}

.stamp-approved {
    color: #10B981;
    border-color: #10B981;
    background: rgba(16, 185, 129, 0.08);
}

.stamp-submitted {
    color: #3B82F6;
    border-color: #3B82F6;
    background: rgba(59, 130, 246, 0.08);
}

.stamp-pending {
    color: #F59E0B;
    border-color: #F59E0B;
    background: rgba(245, 158, 11, 0.08);
}

.stamp-revision {
    color: #EF4444;
    border-color: #EF4444;
    background: rgba(239, 68, 68, 0.08);
}

.voucher-meta-box {
    background: #F9FAFB;
    border-bottom: 1px solid #E5E7EB;
    padding: 1.25rem 2.5rem;
}

.voucher-body {
    padding: 2.5rem;
}

.voucher-table {
    border: 1px solid #E5E7EB;
}

.voucher-table thead th {
    background: #F3F4F6;
    color: #374151;
    font-weight: 700;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 2px solid #D1D5DB;
    padding: 0.75rem 0.65rem;
}

.voucher-table td {
    font-size: 0.85rem;
    vertical-align: middle;
    padding: 0.75rem 0.65rem;
}

.badge-strat {
    background: #EDE9FE;
    color: #5B21B6;
    border: 1px solid #DDD6FE;
    font-size: 0.72rem;
    font-weight: 700;
}

.badge-ops {
    background: #E0E7FF;
    color: #3730A3;
    border: 1px solid #C7D2FE;
    font-size: 0.72rem;
    font-weight: 700;
}

.voucher-signatures {
    background: #F9FAFB;
    border-top: 2px dashed #D1D5DB;
    padding: 2rem 2.5rem;
}

.sig-box {
    border: 1px solid #D1D5DB;
    background: #ffffff;
    border-radius: 8px;
    padding: 1.25rem;
    min-height: 140px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.sig-line {
    border-top: 1px solid #9CA3AF;
    margin-top: 2.5rem;
    padding-top: 0.35rem;
}

@media print {
    body {
        background: #ffffff !important;
    }
    .no-print {
        display: none !important;
    }
    .voucher-wrapper {
        margin: 0 !important;
        max-width: 100% !important;
    }
    .voucher-card {
        border: none !important;
        box-shadow: none !important;
    }
    .voucher-header {
        background: #0A1128 !important;
        color: #ffffff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>

<div class="voucher-wrapper">
    <!-- Action Controls (Hidden on Print) -->
    <div class="card p-3 mb-4 shadow-sm border-0 bg-white no-print">
        <div class="row g-2 align-items-center">
            <div class="col-md-5">
                <form method="GET" action="{{ route('quarterly.voucher') }}" class="d-flex gap-2">
                    @if($allDepartments->count() > 1)
                        <select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach($allDepartments as $d)
                                <option value="{{ $d->id }}" {{ $d->id == $department->id ? 'selected' : '' }}>
                                    {{ $d->department_name }} ({{ $d->department_code }})
                                </option>
                            @endforeach
                        </select>
                    @endif
                    <select name="quarter" class="form-select form-select-sm" onchange="this.form.submit()" style="max-width: 180px;">
                        @foreach(get_ethiopian_quarters() as $code => $q)
                            <option value="{{ $code }}" {{ $code === $selectedQuarter ? 'selected' : '' }}>
                                {{ $code }} - {{ $q['months'][0] }} to {{ $q['months'][2] }}
                            </option>
                        @endforeach
                    </select>
                    <select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="max-width: 130px;">
                        @foreach(get_planning_years() as $yr)
                            <option value="{{ $yr }}" {{ $yr == $selectedYear ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="col-md-7 text-end d-flex justify-content-end gap-2">
                <a href="{{ route('performance.quarterly', ['department_id' => $department->id, 'year' => $selectedYear, 'quarter' => $selectedQuarter]) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Back to Quarterly Module
                </a>
                <a href="{{ route('performance.quarterly.export', ['department_id' => $department->id, 'year' => $selectedYear, 'quarter' => $selectedQuarter]) }}" class="btn btn-outline-success btn-sm">
                    <i class="fas fa-file-excel me-1"></i> Export to Excel
                </a>
                <button type="button" onclick="window.print()" class="btn btn-primary btn-sm px-3">
                    <i class="fas fa-print me-1"></i> Print Official Voucher
                </button>
            </div>
        </div>
    </div>

    <!-- Official Voucher Document -->
    <div class="voucher-card">
        <!-- Header -->
        <div class="voucher-header">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <img src="{{ asset('images/logo.png') }}" alt="Kaldis" class="voucher-brand-logo" onerror="this.src='{{ asset('images/kaldis-logo.svg') }}'">
                    <div>
                        <div class="text-uppercase fw-bold tracking-wider" style="color: #F59E0B; font-size: 0.78rem; letter-spacing: 0.12em;">
                            Kaldis Coffee PLC &bull; Strategic Planning &amp; Performance System
                        </div>
                        <h3 class="fw-black mb-0 text-white" style="letter-spacing: -0.02em;">
                            OFFICIAL QUARTERLY PERFORMANCE VOUCHER
                        </h3>
                        <div class="text-white-50 small mt-0.5">
                            Comprehensive 3-Month Plan Execution &amp; Strategic Output Report
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <div class="voucher-ref-badge mb-2">
                        REF: KBP-QTR-{{ str_replace([' ', '.'], '', $selectedYear) }}-{{ $department->department_code }}-{{ $selectedQuarter }}
                    </div>
                    <div>
                        @php
                            $appr = $submission->approval_status ?? 'PENDING';
                            $stampClass = match($appr) {
                                'APPROVED' => 'stamp-approved',
                                'REVISION_REQUESTED' => 'stamp-revision',
                                default => ($submission && $submission->status === 'SUBMITTED' ? 'stamp-submitted' : 'stamp-pending')
                            };
                            $stampText = match($appr) {
                                'APPROVED' => '✓ GM APPROVED',
                                'REVISION_REQUESTED' => '⚠ REVISION REQUESTED',
                                default => ($submission && $submission->status === 'SUBMITTED' ? '★ SUBMITTED FOR REVIEW' : 'DRAFT REVIEW')
                            };
                        @endphp
                        <span class="voucher-status-stamp {{ $stampClass }}">
                            {{ $stampText }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Meta Strip -->
        <div class="voucher-meta-box">
            <div class="row g-3 align-items-center">
                <div class="col-md-3">
                    <small class="text-muted text-uppercase fw-semibold d-block">Department</small>
                    <strong class="text-dark fs-6">{{ $department->department_name }}</strong>
                    <span class="badge bg-secondary-subtle text-secondary border ms-1">{{ $department->department_code }}</span>
                </div>
                <div class="col-md-3">
                    <small class="text-muted text-uppercase fw-semibold d-block">Quarter &amp; Cycle</small>
                    <strong class="text-dark fs-6">{{ $quarterInfo['name'] }}</strong>
                    <div class="text-muted small">Months: {{ implode(', ', $quarterMonths) }}</div>
                </div>
                <div class="col-md-3">
                    <small class="text-muted text-uppercase fw-semibold d-block">Planning Year</small>
                    <strong class="text-dark fs-6">{{ $selectedYear }}</strong>
                    <div class="text-muted small">Gregorian: {{ date('Y') }}</div>
                </div>
                <div class="col-md-3">
                    <small class="text-muted text-uppercase fw-semibold d-block">Submission Status</small>
                    @if($submission && $submission->submitted_at)
                        <strong class="text-success fs-6"><i class="fas fa-check-circle me-1"></i> Submitted</strong>
                        <div class="text-muted small">{{ $submission->submitted_at->format('M d, Y H:i') }}</div>
                    @else
                        <strong class="text-secondary fs-6"><i class="fas fa-file-pen me-1"></i> Draft</strong>
                        <div class="text-muted small">Not submitted yet</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="voucher-body">
            <!-- Executive Metrics Rollup -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="p-3 border rounded bg-light text-center">
                        <small class="text-muted text-uppercase fw-semibold d-block">Total Monthly Plans</small>
                        <strong class="fs-4 text-dark">{{ $totalPlans }}</strong>
                        <span class="text-muted small d-block">Across 3 Months</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 border rounded bg-success-subtle text-center">
                        <small class="text-success text-uppercase fw-semibold d-block">Fully Completed (Done)</small>
                        <strong class="fs-4 text-success">{{ $donePlans }}</strong>
                        <span class="text-muted small d-block">{{ $totalPlans > 0 ? round(($donePlans / $totalPlans) * 100, 1) : 0 }}% of Plans</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 border rounded bg-warning-subtle text-center">
                        <small class="text-warning text-uppercase fw-semibold d-block">Partial &amp; In-Progress</small>
                        <strong class="fs-4 text-warning">{{ $partialPlans }}</strong>
                        <span class="text-muted small d-block">Under execution</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 border rounded bg-primary-subtle text-center">
                        <small class="text-primary text-uppercase fw-semibold d-block">Quarterly Completion Rate</small>
                        <strong class="fs-4 text-primary">{{ $submission ? $submission->completion_percentage : 0 }}%</strong>
                        <span class="text-muted small d-block">Cumulative Score</span>
                    </div>
                </div>
            </div>

            <!-- Executive Summary & Strategic Narrative -->
            @if($submission && (!empty($submission->executive_summary) || !empty($submission->challenges) || !empty($submission->next_quarter_priorities)))
                <div class="card border border-light-subtle bg-light mb-4">
                    <div class="card-header bg-white border-bottom py-2">
                        <span class="fw-bold text-dark small"><i class="fas fa-file-lines text-primary me-2"></i> Department Executive Performance Narrative</span>
                    </div>
                    <div class="card-body p-3">
                        @if(!empty($submission->executive_summary))
                            <div class="mb-3">
                                <label class="small text-muted fw-bold text-uppercase d-block mb-1">Executive Summary for {{ $selectedQuarter }}</label>
                                <p class="mb-0 text-dark small" style="white-space: pre-line;">{{ $submission->executive_summary }}</p>
                            </div>
                        @endif

                        <div class="row g-3">
                            @if(!empty($submission->challenges))
                                <div class="col-md-6">
                                    <label class="small text-danger fw-bold text-uppercase d-block mb-1"><i class="fas fa-triangle-exclamation me-1"></i> Challenges Encountered</label>
                                    <p class="mb-0 text-dark small" style="white-space: pre-line;">{{ $submission->challenges }}</p>
                                </div>
                            @endif

                            @if(!empty($submission->next_quarter_priorities))
                                <div class="col-md-6">
                                    <label class="small text-primary fw-bold text-uppercase d-block mb-1"><i class="fas fa-bullseye me-1"></i> Next Quarter Priorities</label>
                                    <p class="mb-0 text-dark small" style="white-space: pre-line;">{{ $submission->next_quarter_priorities }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Detailed Monthly Plans Table -->
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="fas fa-list-check text-dark me-2"></i>
                    Department Monthly Plans &amp; Performance Execution Matrix
                </h6>
                <span class="text-muted small">{{ $totalPlans }} Total Plans &bull; {{ implode(', ', $quarterMonths) }}</span>
            </div>

            <div class="table-responsive mb-4">
                <table class="table voucher-table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th style="width: 45px;" class="text-center">#</th>
                            <th style="width: 90px;">Month</th>
                            <th style="width: 80px;">Type</th>
                            <th>Strategic Goal &amp; Monthly Plan Title</th>
                            <th style="width: 130px;">Planned Target</th>
                            <th style="width: 110px;" class="text-center">Status</th>
                            <th style="width: 80px;" class="text-center">Score</th>
                            <th>Actual Output &amp; Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plans as $idx => $p)
                            <tr>
                                <td class="text-center text-muted fw-bold">{{ $idx + 1 }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $p->month }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $p->plan_type === 'STRATEGY' ? 'badge-strat' : 'badge-ops' }}">
                                        {{ $p->plan_type }}
                                    </span>
                                </td>
                                <td>
                                    @if($p->annualGoal)
                                        <div class="text-primary fw-semibold small mb-0.5">
                                            <i class="fas fa-bullseye me-1"></i> {{ $p->annualGoal->code }}: {{ $p->annualGoal->title }}
                                        </div>
                                    @endif
                                    <div class="fw-bold text-dark">{{ $p->title }}</div>
                                    @if(!empty($p->definition_of_done))
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            <strong>DoD:</strong> {{ $p->definition_of_done }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="small fw-semibold text-dark">{{ $p->monthly_target ?: 'N/A' }}</span>
                                </td>
                                <td class="text-center">
                                    @php
                                        $perf = $p->performance_status ?? 'PENDING';
                                        $badgeBg = match($perf) {
                                            'DONE' => 'bg-success text-white',
                                            'PARTIAL' => 'bg-warning text-dark',
                                            'NOT_DONE' => 'bg-danger text-white',
                                            default => 'bg-secondary text-white'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeBg }} px-2 py-1">
                                        {{ $perf }}
                                    </span>
                                </td>
                                <td class="text-center fw-bold">
                                    <span class="{{ $p->achievement_percentage >= 80 ? 'text-success' : ($p->achievement_percentage >= 50 ? 'text-warning' : 'text-danger') }}">
                                        {{ $p->achievement_percentage ?? 0 }}%
                                    </span>
                                </td>
                                <td>
                                    @if(!empty($p->actual_result))
                                        <div class="small text-dark fw-semibold">{{ $p->actual_result }}</div>
                                    @else
                                        <span class="text-muted small fst-italic">Pending evaluation</span>
                                    @endif
                                    @if(!empty($p->completion_notes))
                                        <div class="text-muted" style="font-size: 0.72rem;">{{ $p->completion_notes }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-folder-open fs-3 d-block mb-2 text-secondary"></i>
                                    No monthly plans registered for {{ $quarterInfo['name'] }} ({{ implode(', ', $quarterMonths) }}).
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Executive GM Review & Feedback Section -->
            <div class="card border border-warning-subtle bg-light mb-4">
                <div class="card-header bg-warning-subtle d-flex justify-content-between align-items-center py-2">
                    <span class="fw-bold text-dark small"><i class="fas fa-comment-dots text-warning me-2"></i> General Manager Executive Review &amp; Approval Feedback</span>
                    @if($submission && $submission->gm_feedback_at)
                        <small class="text-muted">Reviewed on: {{ $submission->gm_feedback_at->format('M d, Y H:i') }}</small>
                    @endif
                </div>
                <div class="card-body p-3">
                    @if($submission && !empty($submission->gm_feedback))
                        <div class="p-3 border rounded bg-white mb-2">
                            <p class="mb-0 text-dark" style="white-space: pre-line;">{{ $submission->gm_feedback }}</p>
                        </div>
                    @else
                        <p class="text-muted small mb-2 fst-italic">No executive feedback recorded yet.</p>
                    @endif

                    @if((auth()->user()->isGM() || auth()->user()->isSuperAdmin()) && $submission)
                        <div class="no-print mt-2">
                            <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="collapse" data-bs-target="#gmFeedbackCollapse">
                                <i class="fas fa-pen-to-square me-1"></i> Edit GM Feedback &amp; Decision
                            </button>
                            <div class="collapse mt-2" id="gmFeedbackCollapse">
                                <form method="POST" action="{{ route('performance.quarterly.feedback') }}" class="p-3 border rounded bg-white shadow-sm">
                                    @csrf
                                    <input type="hidden" name="submission_id" value="{{ $submission->id }}">

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Executive Directives / Feedback</label>
                                        <textarea name="gm_feedback" rows="3" class="form-control form-control-sm" placeholder="Provide quarterly observations, strategic guidance, or commendations...">{{ $submission->gm_feedback ?? '' }}</textarea>
                                    </div>

                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Official Approval Decision</label>
                                            <select name="approval_status" class="form-select form-select-sm">
                                                <option value="APPROVED" {{ ($submission->approval_status ?? '') === 'APPROVED' ? 'selected' : '' }}>APPROVED</option>
                                                <option value="REVISION_REQUESTED" {{ ($submission->approval_status ?? '') === 'REVISION_REQUESTED' ? 'selected' : '' }}>REVISION REQUESTED</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 text-end pt-3">
                                            <button type="submit" class="btn btn-success btn-sm px-3">
                                                <i class="fas fa-check-circle me-1"></i> Save Decision &amp; Feedback
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Official Sign-off Block -->
        <div class="voucher-signatures">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="sig-box">
                        <div>
                            <span class="small fw-bold text-muted text-uppercase d-block">1. Prepared &amp; Submitted By</span>
                            <strong class="text-dark small">{{ $submission && $submission->submitter ? $submission->submitter->full_name : ($department->head->full_name ?? 'Head of Department') }}</strong>
                            <small class="text-muted d-block">{{ $department->department_name }}</small>
                        </div>
                        <div class="sig-line text-muted small text-center">
                            Signature &amp; Date
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="sig-box">
                        <div>
                            <span class="small fw-bold text-muted text-uppercase d-block">2. Verified By</span>
                            <strong class="text-dark small">Corporate Planning Directorate</strong>
                            <small class="text-muted d-block">Monitoring &amp; Evaluation Directorate</small>
                        </div>
                        <div class="sig-line text-muted small text-center">
                            Signature &amp; Date
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="sig-box">
                        <div>
                            <span class="small fw-bold text-muted text-uppercase d-block">3. Authorized &amp; Approved By</span>
                            <strong class="text-dark small">{{ $submission && $submission->reviewer ? $submission->reviewer->full_name : 'Office of General Management' }}</strong>
                            <small class="text-muted d-block">General Management Executive</small>
                        </div>
                        <div class="sig-line text-muted small text-center">
                            Signature &amp; Date
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-3 text-muted" style="font-size: 0.72rem;">
                Official Corporate Performance Voucher &bull; Kaldis Coffee PLC &bull; Generated: {{ format_ethiopian_datetime() }}
            </div>
        </div>
    </div>
</div>
@endsection

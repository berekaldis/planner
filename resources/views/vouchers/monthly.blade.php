@extends('layouts.app')

@section('title', 'Monthly Plan Voucher - ' . $department->department_name)

@section('content')
<style>
/* ══════════════════════════════════════════════════════════
   OFFICIAL VOUCHER STYLING (KALDIS COFFEE PLC)
   ══════════════════════════════════════════════════════════ */
.voucher-wrapper {
    max-width: 1080px;
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
.stamp-ontime {
    color: #059669;
    background: #ECFDF5;
    border-color: #10B981;
}
.stamp-late {
    color: #D97706;
    background: #FFFBEB;
    border-color: #F59E0B;
}
.stamp-notsubmitted {
    color: #DC2626;
    background: #FEF2F2;
    border-color: #EF4444;
}

.voucher-meta-box {
    background: #F9FAFB;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    padding: 1.25rem 1.5rem;
    margin: 1.75rem 2rem 1rem 2rem;
}

.voucher-table {
    border: 1px solid #E5E7EB;
    width: 100%;
    margin-bottom: 1.5rem;
}
.voucher-table th {
    background: #F3F4F6;
    color: #374151;
    font-weight: 700;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 0.65rem 0.85rem;
    border: 1px solid #E5E7EB;
}
.voucher-table td {
    padding: 0.75rem 0.85rem;
    border: 1px solid #E5E7EB;
    font-size: 0.84rem;
    vertical-align: top;
}

.voucher-signatures {
    margin: 2.5rem 2rem 1.5rem 2rem;
    padding-top: 1.5rem;
    border-top: 2px dashed #E5E7EB;
}
.sig-box {
    background: #FAFAFA;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    padding: 1rem;
    min-height: 130px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.sig-line {
    border-top: 1px solid #9CA3AF;
    margin-top: 2rem;
    padding-top: 0.35rem;
}

@media print {
    body * {
        visibility: hidden;
    }
    .voucher-card, .voucher-card * {
        visibility: visible;
    }
    .voucher-card {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
        border: none;
        box-shadow: none;
    }
    .no-print, .kaldis-sidebar, .kaldis-navbar, .filter-bar, footer {
        display: none !important;
    }
}
</style>

<div class="voucher-wrapper">
    <!-- Top Action Toolbar -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ url()->previous() ?: route('dashboard.gm') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
            <span class="text-muted small">|</span>
            <span class="badge bg-dark">Official Plan Document</span>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-kaldis btn-sm shadow-sm">
                <i class="fas fa-print me-1"></i> Print / Export Voucher (A4)
            </button>
        </div>
    </div>

    <!-- Voucher Card -->
    <div class="voucher-card">
        <!-- Official Letterhead Header -->
        <div class="voucher-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="Kaldis Logo" class="voucher-brand-logo" onerror="this.src='/Planner/assets/images/logo.png'">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h4 class="mb-0 fw-bold tracking-wide text-warning" style="letter-spacing: 0.06em;">KALDIS COFFEE PLC</h4>
                        </div>
                        <h6 class="mb-0 text-white-50 text-uppercase small" style="letter-spacing: 0.08em;">Strategic Planning &amp; Operational Management System</h6>
                        <div class="fs-5 fw-bold text-white mt-1">MONTHLY STRATEGIC &amp; OPERATIONAL PLAN VOUCHER</div>
                    </div>
                </div>

                <div class="text-md-end">
                    <div class="voucher-ref-badge mb-2">
                        VCH-MPL-{{ substr($selectedYear, 0, 4) }}-{{ strtoupper(substr($selectedMonth, 0, 3)) }}-{{ $department->department_code }}
                    </div>
                    <div>
                        @if($timingStatus === 'ON_TIME')
                            <span class="voucher-status-stamp stamp-ontime">
                                <i class="fas fa-circle-check me-1"></i> SUBMITTED ON-TIME
                            </span>
                        @elseif($timingStatus === 'LATE')
                            <span class="voucher-status-stamp stamp-late">
                                <i class="fas fa-clock me-1"></i> LATE SUBMISSION
                            </span>
                        @else
                            <span class="voucher-status-stamp stamp-notsubmitted">
                                <i class="fas fa-circle-xmark me-1"></i> NOT SUBMITTED
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Voucher Metadata Grid -->
        <div class="voucher-meta-box">
            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <span class="text-muted small fw-semibold d-block text-uppercase">Department</span>
                    <strong class="text-dark fs-6">{{ $department->department_name }}</strong>
                    <span class="badge bg-light text-secondary border ms-1">{{ $department->department_code }}</span>
                </div>
                <div class="col-md-3 col-6">
                    <span class="text-muted small fw-semibold d-block text-uppercase">Planning Period</span>
                    <strong class="text-dark fs-6">{{ $selectedMonth }} {{ $selectedYear }}</strong>
                    <small class="text-muted d-block">Ethiopian Calendar</small>
                </div>
                <div class="col-md-3 col-6">
                    <span class="text-muted small fw-semibold d-block text-uppercase">Department Head</span>
                    <strong class="text-dark fs-6">{{ $department->head->full_name ?? 'Head of ' . $department->department_code }}</strong>
                    <small class="text-muted d-block">{{ $department->head->email ?? 'N/A' }}</small>
                </div>
                <div class="col-md-3 col-6">
                    <span class="text-muted small fw-semibold d-block text-uppercase">Submission Date</span>
                    <strong class="text-dark fs-6">
                        {{ $submission && $submission->submitted_at ? format_ethiopian_datetime($submission->submitted_at) : 'Pending Submission' }}
                    </strong>
                    <small class="text-muted d-block">Channel: {{ $submission->submission_channel ?? 'WEB PORTAL' }}</small>
                </div>
            </div>
        </div>

        <!-- Body: 1. Strategic Goals & Plans -->
        <div class="px-4 pt-2">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="fas fa-bullseye text-danger me-2"></i> Section 1: Annual Strategy Plans (Linked to Master Goals)
                </h6>
                <span class="badge bg-primary-subtle text-primary border border-primary small">
                    {{ $strategyPlans->count() }} Strategy Plan(s)
                </span>
            </div>

            @if($strategyPlans->isEmpty())
                <div class="p-3 mb-3 bg-light text-center text-muted border rounded small">
                    No strategic plans configured for this period.
                </div>
            @else
                <div class="table-responsive">
                    <table class="voucher-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;" class="text-center">#</th>
                                <th style="width: 140px;">Strategic Goal</th>
                                <th>Monthly Plan Title</th>
                                <th>Target Output</th>
                                <th>Definition of Done (DOD)</th>
                                <th style="width: 80px;" class="text-center">Priority</th>
                                <th style="width: 120px;">Responsible</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($strategyPlans as $idx => $sp)
                                <tr>
                                    <td class="text-center fw-bold text-muted">{{ $idx + 1 }}</td>
                                    <td>
                                        @if($sp->annualGoal)
                                            <span class="badge bg-dark font-monospace mb-1">{{ $sp->annualGoal->goal_code }}</span>
                                            <small class="d-block text-muted line-clamp-2" style="font-size: 0.75rem;">{{ $sp->annualGoal->goal_title }}</small>
                                        @else
                                            <span class="text-muted small">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold text-dark">{{ $sp->title }}</td>
                                    <td class="text-primary fw-semibold">{{ $sp->monthly_target }}</td>
                                    <td class="text-muted small">{{ $sp->definition_of_done }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $sp->priority === 'HIGH' ? 'bg-danger' : ($sp->priority === 'LOW' ? 'bg-secondary' : 'bg-warning text-dark') }} small">
                                            {{ $sp->priority }}
                                        </span>
                                    </td>
                                    <td class="small">{{ $sp->responsible_person ?: 'Department Team' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- Body: 2. Operational Routine Plans -->
            <div class="d-flex justify-content-between align-items-center mb-2 mt-3">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="fas fa-cogs text-info me-2"></i> Section 2: Department Operational Routine Plans
                </h6>
                <span class="badge bg-info-subtle text-info border border-info small">
                    {{ $operationalPlans->count() }} Operational Plan(s)
                </span>
            </div>

            @if($operationalPlans->isEmpty())
                <div class="p-3 mb-3 bg-light text-center text-muted border rounded small">
                    No operational plans recorded for this period.
                </div>
            @else
                <div class="table-responsive">
                    <table class="voucher-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;" class="text-center">#</th>
                                <th>Operational Routine Title</th>
                                <th>Monthly Target / Frequency</th>
                                <th>Definition of Done (DOD)</th>
                                <th style="width: 80px;" class="text-center">Target %</th>
                                <th style="width: 80px;" class="text-center">Priority</th>
                                <th style="width: 120px;">Responsible</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($operationalPlans as $idx => $op)
                                <tr>
                                    <td class="text-center fw-bold text-muted">{{ $idx + 1 }}</td>
                                    <td class="fw-bold text-dark">{{ $op->title }}</td>
                                    <td class="text-info fw-semibold">{{ $op->monthly_target }}</td>
                                    <td class="text-muted small">{{ $op->definition_of_done }}</td>
                                    <td class="text-center font-monospace">{{ $op->target_percentage }}%</td>
                                    <td class="text-center">
                                        <span class="badge {{ $op->priority === 'HIGH' ? 'bg-danger' : ($op->priority === 'LOW' ? 'bg-secondary' : 'bg-warning text-dark') }} small">
                                            {{ $op->priority }}
                                        </span>
                                    </td>
                                    <td class="small">{{ $op->responsible_person ?: 'Operational Staff' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- Summary Totals Box -->
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <div class="p-2.5 border rounded bg-light text-center">
                        <small class="text-muted text-uppercase fw-semibold d-block">Total Plans</small>
                        <strong class="fs-5 text-dark">{{ $plans->count() }} Plans</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-2.5 border rounded bg-light text-center">
                        <small class="text-muted text-uppercase fw-semibold d-block">Strategic vs Operational</small>
                        <strong class="fs-5 text-dark">{{ $strategyPlans->count() }} Strat &bull; {{ $operationalPlans->count() }} Ops</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-2.5 border rounded bg-light text-center">
                        <small class="text-muted text-uppercase fw-semibold d-block">Approval Status</small>
                        @php $appr = $submission->approval_status ?? 'PENDING'; @endphp
                        <span class="badge {{ $appr === 'APPROVED' ? 'bg-success' : ($appr === 'REVISION_REQUESTED' ? 'bg-danger' : 'bg-warning text-dark') }} fs-6 px-3 py-1">
                            {{ $appr }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Executive GM Review & Feedback Section (Requirement 5) -->
            <div class="card border border-warning-subtle bg-light mb-3">
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

                    @if(auth()->user()->isGM() || auth()->user()->isSuperAdmin())
                        <div class="no-print mt-2">
                            <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="collapse" data-bs-target="#gmFeedbackCollapse">
                                <i class="fas fa-pen-to-square me-1"></i> Edit GM Feedback &amp; Approval Status
                            </button>
                            <div class="collapse mt-2" id="gmFeedbackCollapse">
                                <form method="POST" action="{{ route('monthly.feedback') }}" class="p-3 border rounded bg-white shadow-sm">
                                    @csrf
                                    <input type="hidden" name="department_id" value="{{ $department->id }}">
                                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                                    <input type="hidden" name="month" value="{{ $selectedMonth }}">

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Executive Comments / Directives</label>
                                        <textarea name="gm_feedback" rows="3" class="form-control form-control-sm" placeholder="Provide strategic observations, required revisions, or commendations...">{{ $submission->gm_feedback ?? '' }}</textarea>
                                    </div>

                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Official Approval Decision</label>
                                            <select name="approval_status" class="form-select form-select-sm">
                                                <option value="APPROVED" {{ ($submission->approval_status ?? '') === 'APPROVED' ? 'selected' : '' }}>APPROVED</option>
                                                <option value="PENDING" {{ ($submission->approval_status ?? '') === 'PENDING' ? 'selected' : '' }}>PENDING REVIEW</option>
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
                            <span class="small fw-bold text-muted text-uppercase d-block">1. Prepared By</span>
                            <strong class="text-dark small">{{ $department->head->full_name ?? 'Head of Department' }}</strong>
                            <small class="text-muted d-block">Department Head</small>
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
                            <strong class="text-dark small">Planning &amp; Performance Directorate</strong>
                            <small class="text-muted d-block">Corporate Monitoring</small>
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
                            <strong class="text-dark small">Office of the General Manager</strong>
                            <small class="text-muted d-block">General Management Executive</small>
                        </div>
                        <div class="sig-line text-muted small text-center">
                            Signature &amp; Date
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-3 text-muted" style="font-size: 0.72rem;">
                Official Corporate Planning Voucher &bull; Kaldis Coffee PLC &bull; Generated: {{ format_ethiopian_datetime() }}
            </div>
        </div>
    </div>
</div>
@endsection

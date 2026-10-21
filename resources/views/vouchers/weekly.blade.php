@extends('layouts.app')

@section('title', 'Weekly Performance Voucher - ' . $department->department_name . ' (Week ' . $selectedWeek . ')')

@section('content')
<style>
/* ══════════════════════════════════════════════════════════
   OFFICIAL WEEKLY PERFORMANCE VOUCHER (KALDIS COFFEE PLC)
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
    background: linear-gradient(135deg, #0A1128 0%, #162038 100%);
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
            <span class="badge bg-dark">{{ ($voucherType ?? 'performance') === 'plan' ? 'Official Weekly Plan Voucher' : 'Official Performance Voucher' }}</span>
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
                        <h6 class="mb-0 text-white-50 text-uppercase small" style="letter-spacing: 0.08em;">Corporate Strategy &amp; Performance Evaluation System</h6>
                        <div class="fs-5 fw-bold text-white mt-1">{{ ($voucherType ?? 'performance') === 'plan' ? 'WEEKLY OPERATIONAL TASK PLAN VOUCHER' : 'WEEKLY EXECUTION & PERFORMANCE REPORT VOUCHER' }}</div>
                    </div>
                </div>

                <div class="text-md-end">
                    <div class="voucher-ref-badge mb-2">
                        VCH-{{ ($voucherType ?? 'performance') === 'plan' ? 'WPL' : 'WPR' }}-{{ substr($selectedYear, 0, 4) }}-{{ strtoupper(substr($selectedMonth, 0, 3)) }}-W0{{ $selectedWeek }}-{{ $department->department_code }}
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
                    <span class="text-muted small fw-semibold d-block text-uppercase">Planning Cycle</span>
                    <strong class="text-dark fs-6">Week {{ $selectedWeek }} ({{ $selectedMonth }} {{ $selectedYear }})</strong>
                    <small class="text-muted d-block">Ethiopian Calendar</small>
                </div>
                <div class="col-md-3 col-6">
                    <span class="text-muted small fw-semibold d-block text-uppercase">Department Head</span>
                    <strong class="text-dark fs-6">{{ $department->head->full_name ?? 'Head of ' . $department->department_code }}</strong>
                    <small class="text-muted d-block">{{ $department->head->email ?? 'N/A' }}</small>
                </div>
                <div class="col-md-3 col-6">
                    <span class="text-muted small fw-semibold d-block text-uppercase">Report Submission</span>
                    @php $subDate = $submission->submitted_at ?? ($report->submitted_at ?? null); @endphp
                    <strong class="text-dark fs-6">
                        {{ $subDate ? format_ethiopian_datetime($subDate) : 'Pending Submission' }}
                    </strong>
                    <small class="text-muted d-block">Submitter: {{ $submission->submitter->full_name ?? ($report->submitter->full_name ?? 'Dept Head') }}</small>
                </div>
            </div>
        </div>

        <!-- Executive Performance Scorecard Strip -->
        <div class="px-4 mb-3">
            <div class="row g-2 text-center">
                <div class="col-md-3 col-6">
                    <div class="p-2.5 border rounded bg-light">
                        <small class="text-muted text-uppercase fw-semibold d-block">Total Tasks</small>
                        <strong class="fs-4 text-dark">{{ $totalTasks }}</strong>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-2.5 border rounded bg-success-subtle border-success">
                        <small class="text-success text-uppercase fw-semibold d-block">DONE Tasks</small>
                        <strong class="fs-4 text-success">{{ $doneTasks }}</strong>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-2.5 border rounded bg-danger-subtle border-danger">
                        <small class="text-danger text-uppercase fw-semibold d-block">NOT DONE Tasks</small>
                        <strong class="fs-4 text-danger">{{ $notDoneTasks }}</strong>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-2.5 border rounded bg-warning-subtle border-warning">
                        <small class="text-warning-emphasis text-uppercase fw-semibold d-block">Completion Rate</small>
                        <strong class="fs-4 text-dark">{{ $completionPct }}%</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Body: Planned Tasks Execution Matrix -->
        <div class="px-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="fas fa-list-check text-warning me-2"></i> Weekly Task Execution Matrix
                </h6>
                <span class="badge bg-secondary small">
                    {{ $tasks->count() }} Planned Task(s)
                </span>
            </div>

            @if($tasks->isEmpty())
                <div class="p-3 mb-3 bg-light text-center text-muted border rounded small">
                    No weekly tasks recorded for this week.
                </div>
            @else
                <div class="table-responsive">
                    <table class="voucher-table">
                        <thead>
                            <tr>
                                <th style="width: 35px;" class="text-center">#</th>
                                <th style="width: 90px;">Type</th>
                                <th>Task Title</th>
                                <th>Definition of Done (DOD)</th>
                                <th style="width: 95px;" class="text-center">Result</th>
                                <th>Root Cause / Explanation (If Not Done)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tasks as $idx => $task)
                                @php
                                    $res = $task->result ? $task->result->result : 'PENDING';
                                    $reasonName = $task->result && $task->result->reasonCategory ? $task->result->reasonCategory->name : '';
                                    $explanation = $task->result ? $task->result->not_done_explanation : '';
                                @endphp
                                <tr>
                                    <td class="text-center fw-bold text-muted">{{ $idx + 1 }}</td>
                                    <td>
                                        <span class="badge {{ $task->plan_type === 'STRATEGY' ? 'bg-primary' : 'bg-info text-dark' }} small">
                                            {{ $task->plan_type }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $task->task_title }}</div>
                                        @if($task->monthlyPlan)
                                            <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                Linked Plan: {{ $task->monthlyPlan->title }}
                                            </small>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $task->definition_of_done }}</td>
                                    <td class="text-center">
                                        @if($res === 'DONE')
                                            <span class="badge bg-success px-2 py-1"><i class="fas fa-check me-1"></i>DONE</span>
                                        @elseif($res === 'NOT_DONE')
                                            <span class="badge bg-danger px-2 py-1"><i class="fas fa-times me-1"></i>NOT DONE</span>
                                        @else
                                            <span class="badge bg-secondary px-2 py-1">PENDING</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($res === 'NOT_DONE')
                                            @if($reasonName)
                                                <div class="badge bg-danger-subtle text-danger border border-danger small mb-1">
                                                    {{ $reasonName }}
                                                </div>
                                            @endif
                                            <div class="text-dark small" style="white-space: pre-line;">{{ $explanation ?: 'No root cause entered' }}</div>
                                            @if($task->result && !empty($task->result->next_action))
                                                <small class="text-muted d-block mt-1">
                                                    <strong>Next Action:</strong> {{ $task->result->next_action }}
                                                    @if($task->result->expected_completion_date)
                                                        ({{ $task->result->expected_completion_date->format('M d') }})
                                                    @endif
                                                </small>
                                            @endif
                                        @else
                                            <span class="text-muted small">&mdash;</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- Qualitative Highlights: Achievements & Challenges -->
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="p-3 border rounded bg-white h-100">
                        <h6 class="fw-bold text-dark small mb-2">
                            <i class="fas fa-star text-warning me-1"></i> Notable Weekly Achievements
                        </h6>
                        @forelse($achievements as $ach)
                            <div class="p-2 mb-1 bg-light rounded small border-start border-3 border-warning">
                                <div class="fw-semibold text-dark">{{ $ach->achievement_text }}</div>
                            </div>
                        @empty
                            <p class="text-muted small mb-0 fst-italic">No specific achievements logged for this week.</p>
                        @endforelse
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="p-3 border rounded bg-white h-100">
                        <h6 class="fw-bold text-dark small mb-2">
                            <i class="fas fa-triangle-exclamation text-danger me-1"></i> Operational Challenges &amp; Blockers
                        </h6>
                        @forelse($challenges as $ch)
                            <div class="p-2 mb-1 bg-light rounded small border-start border-3 border-danger">
                                <div class="fw-semibold text-danger">{{ $ch->challenge_text }}</div>
                            </div>
                        @empty
                            <p class="text-muted small mb-0 fst-italic">No operational challenges logged for this week.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Department Head Submission Remarks -->
            @php $remarks = $submission->remarks ?? ($report->notes ?? ''); @endphp
            @if(!empty($remarks))
                <div class="p-3 border rounded bg-light mb-3">
                    <h6 class="fw-bold text-dark small mb-1"><i class="fas fa-comment-alt text-secondary me-1"></i> Department Head Submission Notes:</h6>
                    <p class="mb-0 text-dark small" style="white-space: pre-line;">{{ $remarks }}</p>
                </div>
            @endif

            <!-- Executive GM Review & Feedback Section (Requirement 5) -->
            <div class="card border border-warning-subtle bg-light mb-3">
                <div class="card-header bg-warning-subtle d-flex justify-content-between align-items-center py-2">
                    <span class="fw-bold text-dark small"><i class="fas fa-comment-dots text-warning me-2"></i> General Manager Executive Evaluation &amp; Approval Feedback</span>
                    @php $gmDate = $report->gm_feedback_at ?? ($submission->gm_feedback_at ?? null); @endphp
                    @if($gmDate)
                        <small class="text-muted">Reviewed on: {{ $gmDate->format('M d, Y H:i') }}</small>
                    @endif
                </div>
                <div class="card-body p-3">
                    @php 
                        $feedback = $report->gm_feedback ?? ($submission->gm_feedback ?? '');
                        $apprStatus = $report->approval_status ?? ($submission->approval_status ?? 'PENDING');
                    @endphp

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Official Executive Decision:</span>
                        <span class="badge {{ $apprStatus === 'APPROVED' ? 'bg-success' : ($apprStatus === 'REVISION_REQUESTED' ? 'bg-danger' : 'bg-warning text-dark') }} fs-6 px-3 py-1">
                            {{ $apprStatus }}
                        </span>
                    </div>

                    @if(!empty($feedback))
                        <div class="p-3 border rounded bg-white mb-2">
                            <p class="mb-0 text-dark" style="white-space: pre-line;">{{ $feedback }}</p>
                        </div>
                    @else
                        <p class="text-muted small mb-2 fst-italic">No executive feedback recorded yet.</p>
                    @endif

                    @if(auth()->user()->isGM() || auth()->user()->isSuperAdmin())
                        <div class="no-print mt-2">
                            <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="collapse" data-bs-target="#gmWeeklyFeedbackCollapse">
                                <i class="fas fa-pen-to-square me-1"></i> Edit GM Evaluation &amp; Approval Status
                            </button>
                            <div class="collapse mt-2" id="gmWeeklyFeedbackCollapse">
                                <form method="POST" action="{{ route('weekly.feedback') }}" class="p-3 border rounded bg-white shadow-sm">
                                    @csrf
                                    <input type="hidden" name="department_id" value="{{ $department->id }}">
                                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                    <input type="hidden" name="week_number" value="{{ $selectedWeek }}">

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Executive Evaluation / Directives</label>
                                        <textarea name="gm_feedback" rows="3" class="form-control form-control-sm" placeholder="Provide strategic observations, operational directives, or commendations...">{{ $feedback }}</textarea>
                                    </div>

                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Official Approval Decision</label>
                                            <select name="approval_status" class="form-select form-select-sm">
                                                <option value="APPROVED" {{ $apprStatus === 'APPROVED' ? 'selected' : '' }}>APPROVED</option>
                                                <option value="PENDING" {{ $apprStatus === 'PENDING' ? 'selected' : '' }}>PENDING REVIEW</option>
                                                <option value="REVISION_REQUESTED" {{ $apprStatus === 'REVISION_REQUESTED' ? 'selected' : '' }}>REVISION REQUESTED</option>
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
                            <strong class="text-dark small">{{ $department->head->full_name ?? 'Department Head' }}</strong>
                            <small class="text-muted d-block">Head of {{ $department->department_code }}</small>
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
                            <small class="text-muted d-block">Operations Monitoring</small>
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
                            <small class="text-muted d-block">Executive Operations</small>
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

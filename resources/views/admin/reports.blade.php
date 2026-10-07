@extends('layouts.app')

@section('title', 'Reports & Export Hub')

@section('content')
<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Reports & Export Hub</h4>
        <p class="text-muted small mb-0">Official performance reporting sheets, printable summaries, and data exports</p>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card h-100 p-3">
            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-file-alt text-primary me-2"></i> Weekly Department Scorecard</h6>
            <p class="small text-muted mb-3">Complete cross-department performance compliance matrix for the selected period.</p>
            <a href="{{ route('performance.weekly') }}" class="btn btn-sm btn-outline-secondary mt-auto">
                Open Scorecard &rarr;
            </a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 p-3">
            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-calendar-alt text-success me-2"></i> Monthly Aggregation Report</h6>
            <p class="small text-muted mb-3">Departmental monthly execution progress and task completion rates.</p>
            <a href="{{ route('performance.monthly') }}" class="btn btn-sm btn-outline-secondary mt-auto">
                Open Monthly Rollup &rarr;
            </a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 p-3">
            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-exclamation-triangle text-danger me-2"></i> Root Cause (Not Done) Log</h6>
            <p class="small text-muted mb-3">Categorized breakdown of missed targets, explanations, and corrective actions.</p>
            <a href="{{ route('performance.not_done') }}" class="btn btn-sm btn-outline-secondary mt-auto">
                Open Analysis &rarr;
            </a>
        </div>
    </div>
</div>
@endsection

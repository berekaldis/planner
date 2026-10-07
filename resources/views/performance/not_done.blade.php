@extends('layouts.app')

@section('title', 'Not Done Root Cause Analysis')

@section('content')
<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Root Cause Analysis (NOT DONE)</h4>
        <p class="text-muted small mb-0">Analysis of uncompleted tasks, root cause categories, and corrective next actions</p>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar">
    <form method="GET" action="{{ route('performance.not_done') }}" class="row g-2 align-items-center">
        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Reason Category</label>
            <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Reason Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>
                @endforeach
            </select>
        </div>

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
            <label class="form-label small text-muted mb-1">Search Root Causes</label>
            <input type="text" id="notDoneSearch" class="form-control form-control-sm" placeholder="Live filter...">
        </div>
    </form>
</div>

<!-- Not Done Table -->
<div class="card mb-4">
    <div class="card-header">
        <span><i class="fas fa-exclamation-triangle me-1 text-danger"></i> Uncompleted Tasks ({{ $notDoneList->count() }})</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="notDoneTable">
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Task Title & Week</th>
                    <th>Reason Category</th>
                    <th>Explanation (Why Not Done)</th>
                    <th>Corrective Next Action</th>
                    <th>Target Date & Owner</th>
                </tr>
            </thead>
            <tbody>
                @forelse($notDoneList as $row)
                    <tr>
                        <td>
                            <strong class="text-dark">{{ $row->department_name }}</strong>
                            <span class="badge bg-light text-secondary ms-1">{{ $row->department_code }}</span>
                        </td>
                        <td>
                            <strong class="text-dark">{{ $row->task_title }}</strong>
                            <div class="small text-muted">Week {{ $row->week_number }} ({{ $row->plan_type }})</div>
                        </td>
                        <td>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                {{ $row->category_name ?? 'Uncategorized' }}
                            </span>
                        </td>
                        <td>
                            <div class="small text-secondary">{{ $row->reason_explanation }}</div>
                        </td>
                        <td>
                            <div class="small text-primary fw-semibold">{{ $row->next_action }}</div>
                        </td>
                        <td>
                            <div class="small text-dark">{{ $row->next_action_deadline ?: 'N/A' }}</div>
                            <small class="text-muted">{{ $row->responsible_person ?: 'Unassigned' }}</small>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state py-5">
                            <i class="fas fa-check-circle text-success d-block opacity-50"></i>
                            <h6 class="text-success">Clean Performance Record!</h6>
                            <p class="small text-muted mb-0">No tasks marked as <strong>NOT DONE</strong> for {{ $selectedMonth }} {{ $selectedYear }}.</p>
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
    setupLiveSearch('notDoneSearch', 'notDoneTable');
</script>
@endsection

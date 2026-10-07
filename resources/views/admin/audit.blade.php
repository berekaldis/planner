@extends('layouts.app')

@section('title', 'Audit Trail')

@section('content')
<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">System Audit Trail</h4>
        <p class="text-muted small mb-0">Immutable compliance logging of user actions, planning edits, and evaluations</p>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar">
    <form method="GET" action="{{ route('admin.audit') }}" class="row g-2 align-items-center">
        <div class="col-md-4">
            <label class="form-label small text-muted mb-1">Module</label>
            <input type="text" name="module" class="form-control form-control-sm" placeholder="e.g. strategy, performance, auth" value="{{ request('module') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label small text-muted mb-1">Action</label>
            <input type="text" name="action" class="form-control form-control-sm" placeholder="e.g. LOGIN, EVALUATE_TASK" value="{{ request('action') }}">
        </div>
        <div class="col-md-4 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-sm btn-kaldis w-100">
                <i class="fas fa-filter me-1"></i> Filter Logs
            </button>
            <a href="{{ route('admin.audit') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
        </div>
    </form>
</div>

<!-- Audit Log Table -->
<div class="card mb-4">
    <div class="card-header">
        <span><i class="fas fa-history me-1 text-secondary"></i> Recorded Events ({{ $logs->total() }})</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 150px;">Timestamp</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Details</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="small text-muted">{{ $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '' }}</td>
                        <td><strong class="text-dark">{{ $log->user_name ?? 'System' }}</strong></td>
                        <td><span class="badge bg-secondary-subtle text-secondary">{{ $log->action }}</span></td>
                        <td><code>{{ $log->module }}</code></td>
                        <td><small class="text-secondary">{{ $log->details }}</small></td>
                        <td class="small text-muted">{{ $log->ip_address }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state py-5">
                            <i class="fas fa-fingerprint d-block opacity-25"></i>
                            <h6>No audit events recorded yet</h6>
                            <p class="small text-muted mb-0">System events will appear here automatically.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
        <div class="card-footer bg-light">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection

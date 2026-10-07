@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Administration & Operations</h4>
        <p class="text-muted small mb-0">System Health, User Governance, Audit Trail & Global Settings</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.settings') }}" class="btn btn-kaldis btn-sm">
            <i class="fas fa-sliders-h me-1"></i> System Settings
        </a>
        <a href="{{ route('admin.users') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-user-plus me-1"></i> Add User
        </a>
    </div>
</div>

<!-- Overview Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="card p-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">System Users</span>
                    <h3 class="fw-bold my-1 text-dark">{{ $userCount }}</h3>
                    <a href="{{ route('admin.users') }}" class="small text-decoration-none text-muted">Manage Users &rarr;</a>
                </div>
                <div class="p-2.5 bg-primary-subtle rounded-3 text-primary">
                    <i class="fas fa-users fs-5"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card p-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Departments</span>
                    <h3 class="fw-bold my-1 text-dark">{{ $deptCount }}</h3>
                    <a href="{{ route('admin.departments') }}" class="small text-decoration-none text-muted">Configure &rarr;</a>
                </div>
                <div class="p-2.5 bg-warning-subtle rounded-3 text-warning">
                    <i class="fas fa-building fs-5"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card p-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Annual Goals</span>
                    <h3 class="fw-bold my-1 text-dark">{{ $goalCount }}</h3>
                    <a href="{{ route('strategy.annual') }}" class="small text-decoration-none text-muted">View Master &rarr;</a>
                </div>
                <div class="p-2.5 bg-success-subtle rounded-3 text-success">
                    <i class="fas fa-chess-knight fs-5"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card p-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Audit Events</span>
                    <h3 class="fw-bold my-1 text-dark">{{ $auditCount }}</h3>
                    <a href="{{ route('admin.audit') }}" class="small text-decoration-none text-muted">Audit Log &rarr;</a>
                </div>
                <div class="p-2.5 bg-info-subtle rounded-3 text-info">
                    <i class="fas fa-history fs-5"></i>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

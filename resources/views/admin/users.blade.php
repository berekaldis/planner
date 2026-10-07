@extends('layouts.app')

@section('title', 'Users Management')

@section('content')
<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">User Accounts & Roles</h4>
        <p class="text-muted small mb-0">System access control, role appointments, and department associations</p>
    </div>
    <button type="button" class="btn btn-kaldis btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="fas fa-user-plus me-1"></i> Add New User
    </button>
</div>

<!-- Users Table -->
<div class="card mb-4">
    <div class="card-header">
        <span><i class="fas fa-users me-1 text-secondary"></i> Registered Accounts ({{ $users->count() }})</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Full Name & Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th style="width: 100px;">Status</th>
                    <th>Last Login</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                    <tr>
                        <td>
                            <strong class="text-dark">{{ $u->full_name }}</strong>
                            <div class="small text-muted font-monospace">{{ $u->username }}</div>
                        </td>
                        <td><small>{{ $u->email }}</small></td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary border">
                                {{ $u->role->role_name ?? 'N/A' }}
                            </span>
                        </td>
                        <td>
                            @if($u->department)
                                <span class="small fw-semibold text-dark">{{ $u->department->department_name }}</span>
                            @else
                                <span class="text-muted small">All Departments</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $u->status === 'ACTIVE' ? 'bg-success' : 'bg-danger' }}">
                                {{ $u->status }}
                            </span>
                        </td>
                        <td class="small text-muted">
                            {{ $u->last_login_at ? $u->last_login_at->diffForHumans() : 'Never' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add User -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark fs-6"><i class="fas fa-user-plus text-warning me-2"></i> Create User Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" placeholder="e.g. John Doe" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" placeholder="username" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="user@kaldiscoffeegroup.com" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Role <span class="text-danger">*</span></label>
                            <select name="role_id" class="form-select" required>
                                @foreach($roles as $r)
                                    <option value="{{ $r->id }}">{{ $r->role_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">None (Global / Admin)</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}">{{ $d->department_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select">
                            <option value="ACTIVE" selected>ACTIVE</option>
                            <option value="INACTIVE">INACTIVE</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-kaldis">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

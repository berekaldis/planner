@extends('layouts.app')

@section('title', 'Departments Management')

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Action Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-warning bg-opacity-10 text-warning px-2.5 py-1 rounded-pill small fw-semibold">
                    <i class="fas fa-sitemap me-1"></i> Organizational Governance
                </span>
                <span class="badge bg-secondary-subtle text-secondary px-2.5 py-1 rounded-pill small font-monospace">
                    {{ $departments->count() }} Corporate Units
                </span>
            </div>
            <h4 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                Company Departments Directory
            </h4>
            <p class="text-muted small mb-0">Strategic planning units, department codes, appointed leadership, and annual goal ownership</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-warning text-dark fw-bold shadow-sm d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill" data-bs-toggle="modal" data-bs-target="#createDepartmentModal">
                <i class="fas fa-plus-circle"></i> Add Department
            </button>
        </div>
    </div>

    <!-- Quick Metrics Row -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm bg-white mb-0 rounded-4 overflow-hidden h-100">
                <div class="card-body p-3.5 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                        <i class="fas fa-building fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.05em;">Total Departments</div>
                        <div class="fw-bold text-dark fs-4 mb-0">{{ $departments->count() }}</div>
                        <div class="text-muted small" style="font-size: 0.72rem;">Official Corporate Units</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm bg-white mb-0 rounded-4 overflow-hidden h-100">
                <div class="card-body p-3.5 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                        <i class="fas fa-circle-check fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.05em;">Active Units</div>
                        <div class="fw-bold text-dark fs-4 mb-0">{{ $departments->where('active', 1)->count() }}</div>
                        <div class="text-success small fw-medium" style="font-size: 0.72rem;">
                            {{ $departments->count() > 0 ? round(($departments->where('active', 1)->count() / $departments->count()) * 100) : 0 }}% operational
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm bg-white mb-0 rounded-4 overflow-hidden h-100">
                <div class="card-body p-3.5 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                        <i class="fas fa-user-tie fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.05em;">Heads Appointed</div>
                        <div class="fw-bold text-dark fs-4 mb-0">{{ $departments->whereNotNull('head_user_id')->count() }}</div>
                        <div class="text-muted small" style="font-size: 0.72rem;">
                            @php $vacantCount = $departments->whereNull('head_user_id')->count(); @endphp
                            {{ $vacantCount > 0 ? "{$vacantCount} vacant unit(s)" : "Fully appointed" }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm bg-white mb-0 rounded-4 overflow-hidden h-100">
                <div class="card-body p-3.5 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                        <i class="fas fa-bullseye fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.05em;">Strategic Linkages</div>
                        <div class="fw-bold text-dark fs-4 mb-0">{{ $departments->sum(fn($d) => $d->annualGoals->count()) }}</div>
                        <div class="text-muted small" style="font-size: 0.72rem;">Annual Goals Assigned</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Strategic Visual Analytics Row -->
    <div class="row g-3 mb-4">
        <!-- Chart 1: Strategic Goals Distribution per Department -->
        <div class="col-lg-8">
            <div class="card border border-light-subtle shadow-sm bg-white rounded-4 overflow-hidden h-100">
                <div class="card-header bg-white py-3 border-bottom border-light-subtle d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-3 bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="fas fa-chart-column"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Strategic Goal Ownership Distribution</h6>
                            <span class="text-muted" style="font-size: 0.72rem;">Annual strategic targets assigned per organizational unit</span>
                        </div>
                    </div>
                    <span class="badge bg-light text-secondary border font-monospace small">Chart.js</span>
                </div>
                <div class="card-body p-3">
                    <div style="height: 220px; position: relative;">
                        <canvas id="deptGoalsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart 2: Governance & Appointment Breakdown -->
        <div class="col-lg-4">
            <div class="card border border-light-subtle shadow-sm bg-white rounded-4 overflow-hidden h-100">
                <div class="card-header bg-white py-3 border-bottom border-light-subtle d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Leadership Status</h6>
                            <span class="text-muted" style="font-size: 0.72rem;">Head appointment ratio</span>
                        </div>
                    </div>
                    <span class="badge bg-light text-secondary border font-monospace small">Ratio</span>
                </div>
                <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center">
                    <div style="height: 190px; width: 100%; position: relative;">
                        <canvas id="deptGovernanceChart"></canvas>
                    </div>
                    <div class="d-flex justify-content-center gap-3 mt-2" style="font-size: 0.75rem;">
                        <span class="d-flex align-items-center gap-1.5 text-muted">
                            <span class="d-inline-block rounded-circle" style="width: 10px; height: 10px; background: #059669;"></span> Appointed ({{ $departments->whereNotNull('head_user_id')->count() }})
                        </span>
                        <span class="d-flex align-items-center gap-1.5 text-muted">
                            <span class="d-inline-block rounded-circle" style="width: 10px; height: 10px; background: #F59E0B;"></span> Vacant ({{ $departments->whereNull('head_user_id')->count() }})
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Departments DataTables Card -->
    <div class="card border border-light-subtle shadow-sm bg-white rounded-4 overflow-hidden mb-4">
        <!-- Card Header with Filter Pills -->
        <div class="card-header bg-white py-3 border-bottom border-light-subtle">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <h6 class="fw-bold mb-0 text-dark">Operational Units Directory</h6>
                    <span class="badge bg-dark font-monospace">{{ $departments->count() }} Registered</span>
                </div>
                
                <!-- Quick Filter Pills -->
                <div class="react-nav-pills flex-wrap" id="deptFilterPills">
                    <button type="button" class="nav-link active" data-filter="all">
                        <i class="fas fa-layer-group"></i> All ({{ $departments->count() }})
                    </button>
                    <button type="button" class="nav-link" data-filter="active">
                        <i class="fas fa-circle-check text-success"></i> Active ({{ $departments->where('active', 1)->count() }})
                    </button>
                    <button type="button" class="nav-link" data-filter="inactive">
                        <i class="fas fa-power-off text-muted"></i> Inactive ({{ $departments->where('active', 0)->count() }})
                    </button>
                    <button type="button" class="nav-link" data-filter="appointed">
                        <i class="fas fa-user-check text-primary"></i> Appointed ({{ $departments->whereNotNull('head_user_id')->count() }})
                    </button>
                    <button type="button" class="nav-link" data-filter="vacant">
                        <i class="fas fa-user-slash text-warning"></i> Vacant ({{ $departments->whereNull('head_user_id')->count() }})
                    </button>
                </div>
            </div>
        </div>

        <div class="p-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100" id="departmentsDataTable" style="font-size: 0.88rem;">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 100px;">Code</th>
                            <th>Department Name</th>
                            <th>Appointed Head</th>
                            <th>Strategy Goals Owned</th>
                            <th style="width: 110px;">Status</th>
                            <th style="width: 140px;" class="text-end text-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $dept)
                            @php
                                $goalIds = $dept->annualGoals->pluck('id')->toArray();
                                $goalsCount = $dept->annualGoals->count();
                            @endphp
                            <tr data-dept-id="{{ $dept->id }}" data-active="{{ $dept->active ? '1' : '0' }}" data-has-head="{{ $dept->head_user_id ? '1' : '0' }}">
                                <td>
                                    <span class="badge bg-dark font-monospace px-2.5 py-1.5 shadow-2xs">{{ $dept->department_code }}</span>
                                </td>
                                <td>
                                    <strong class="text-dark fs-6 d-block">{{ $dept->department_name }}</strong>
                                    <span class="text-muted small" style="font-size: 0.75rem;">Created {{ $dept->created_at ? $dept->created_at->format('M Y') : 'System Initialized' }}</span>
                                </td>
                                <td>
                                    @if($dept->head)
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-warning bg-opacity-20 text-dark d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 30px; height: 30px; font-size: 0.78rem;">
                                                {{ strtoupper(substr($dept->head->full_name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="fw-semibold text-dark">{{ $dept->head->full_name }}</div>
                                                <span class="text-muted small" style="font-size: 0.72rem;">@ {{ $dept->head->username }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning-subtle px-2 py-1">
                                            <i class="fas fa-circle-exclamation me-1"></i> Vacant / Not Appointed
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($goalsCount > 0)
                                        <div class="d-flex flex-wrap align-items-center gap-1">
                                            @foreach($dept->annualGoals->take(4) as $goal)
                                                <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.72rem;" title="{{ $goal->goal_description }}">
                                                    {{ $goal->goal_code }}
                                                </span>
                                            @endforeach
                                            @if($goalsCount > 4)
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle font-monospace" style="font-size: 0.72rem;">+{{ $goalsCount - 4 }} more</span>
                                            @endif
                                            <span class="badge bg-secondary-subtle text-secondary small ms-1 font-monospace">{{ $goalsCount }} {{ Str::plural('Goal', $goalsCount) }}</span>
                                        </div>
                                    @else
                                        <span class="text-muted small fst-italic">
                                            <i class="fas fa-minus-circle text-muted me-1"></i> No strategic goals
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($dept->active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill">
                                            <i class="fas fa-circle text-success me-1" style="font-size: 0.5rem;"></i> Active
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1 rounded-pill">
                                            <i class="fas fa-circle text-secondary me-1" style="font-size: 0.5rem;"></i> Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary edit-dept-btn px-2.5" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editDepartmentModal"
                                                data-id="{{ $dept->id }}"
                                                data-code="{{ $dept->department_code }}"
                                                data-name="{{ $dept->department_name }}"
                                                data-head="{{ $dept->head_user_id }}"
                                                data-active="{{ $dept->active }}"
                                                data-goals="{{ json_encode($goalIds) }}"
                                                title="Edit Department">
                                            <i class="fas fa-pen-to-square text-primary me-1"></i> Edit
                                        </button>
                                        <button type="button" class="btn btn-outline-danger delete-dept-btn px-2.5"
                                                data-id="{{ $dept->id }}"
                                                data-code="{{ $dept->department_code }}"
                                                data-name="{{ $dept->department_name }}"
                                                data-goals-count="{{ $goalsCount }}"
                                                title="Deactivate or Remove Department">
                                            <i class="fas fa-trash-can me-1"></i> Remove
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-building fa-2x mb-2 opacity-25 d-block"></i>
                                    No departments found. Click "Add Department" above to create one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     CREATE DEPARTMENT MODAL
     ══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="createDepartmentModal" tabindex="-1" aria-labelledby="createDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-warning bg-opacity-10 py-3 border-bottom border-light-subtle">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="rounded-3 bg-warning text-dark d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="fas fa-plus-circle fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="createDepartmentModalLabel">
                            Create New Department
                        </h5>
                        <small class="text-muted">Register a strategic organizational unit and allocate strategy ownership</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.departments.store') }}" id="createDepartmentForm">
                @csrf
                <div class="modal-body p-4">
                    <!-- Live Preview Pill Card -->
                    <div class="p-3 mb-4 rounded-3 border border-light-subtle bg-light d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted small fw-semibold">Live Preview:</span>
                            <span class="badge bg-dark font-monospace px-2.5 py-1" id="previewCodeBadge">NEW</span>
                            <span class="fw-bold text-dark" id="previewNameLabel">New Department Name</span>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 small" id="previewStatusBadge">Active</span>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">
                                Department Code <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-barcode"></i></span>
                                <input type="text" name="department_code" id="createDeptCodeInput" class="form-control font-monospace text-uppercase border-start-0" placeholder="e.g. BD" maxlength="20" required value="{{ old('department_code') }}">
                            </div>
                            <div class="form-text small">Short unique code (e.g. IT, OPS, MKT, SCM)</div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-secondary">
                                Department Name <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-building"></i></span>
                                <input type="text" name="department_name" id="createDeptNameInput" class="form-control border-start-0" placeholder="e.g. Business Development & Partnerships" maxlength="150" required value="{{ old('department_name') }}">
                            </div>
                            <div class="form-text small">Full official corporate title</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-secondary">
                                Appointed Department Head
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-user-tie"></i></span>
                                <select name="head_user_id" id="createDeptHeadSelect" class="form-select border-start-0">
                                    <option value="">-- No Head Appointed Yet --</option>
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}" {{ old('head_user_id') == $u->id ? 'selected' : '' }}>
                                            {{ $u->full_name }} ({{ $u->username }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-text small">Appointed leader responsible for quarterly & monthly plans</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">
                                Status
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-toggle-on"></i></span>
                                <select name="active" id="createDeptActiveSelect" class="form-select border-start-0">
                                    <option value="1" {{ old('active', '1') == '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ old('active') == '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">
                            Assigned Annual Strategy Goals (Ownership)
                        </label>
                        <select name="annual_goal_ids[]" id="createDeptGoalsSelect" class="form-select" multiple placeholder="Search & select strategic goals owned by this department...">
                            @foreach($annualGoals as $g)
                                <option value="{{ $g->id }}" {{ in_array($g->id, old('annual_goal_ids', [])) ? 'selected' : '' }}>
                                    [{{ $g->goal_code }}] {{ Str::limit($g->goal_description, 80) }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text small">Link strategic annual goals that this department owns and reports on.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2.5 px-4 border-top border-light-subtle d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-warning text-dark fw-bold px-4 rounded-pill shadow-xs" id="createDeptSubmitBtn">
                        <i class="fas fa-plus-circle me-1"></i> Save Department
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     EDIT DEPARTMENT MODAL
     ══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1" aria-labelledby="editDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-light py-3 border-bottom border-light-subtle">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="rounded-3 bg-secondary bg-opacity-10 text-dark d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="fas fa-pen-to-square fs-5 text-warning"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="editDepartmentModalLabel">
                            Edit Department Configuration
                        </h5>
                        <small class="text-muted">Update department code, title, appointed head, and goal linkages</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="editDepartmentForm" action="">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">
                                Department Code <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-barcode"></i></span>
                                <input type="text" name="department_code" id="editDeptCode" class="form-control font-monospace text-uppercase border-start-0" maxlength="20" required>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-secondary">
                                Department Name <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-building"></i></span>
                                <input type="text" name="department_name" id="editDeptName" class="form-control border-start-0" maxlength="150" required>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-secondary">
                                Appointed Department Head
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-user-tie"></i></span>
                                <select name="head_user_id" id="editDeptHead" class="form-select border-start-0">
                                    <option value="">-- No Head Appointed Yet --</option>
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}">{{ $u->full_name }} ({{ $u->username }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Status</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-toggle-on"></i></span>
                                <select name="active" id="editDeptActive" class="form-select border-start-0">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">
                            Assigned Annual Strategy Goals
                        </label>
                        <select name="annual_goal_ids[]" id="editDeptGoals" class="form-select" multiple placeholder="Select strategic goals owned by this department...">
                            @foreach($annualGoals as $g)
                                <option value="{{ $g->id }}">
                                    [{{ $g->goal_code }}] {{ Str::limit($g->goal_description, 80) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2.5 px-4 border-top border-light-subtle d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-warning text-dark fw-bold px-4 rounded-pill shadow-xs" id="editDeptSubmitBtn">
                        <i class="fas fa-check-circle me-1"></i> Update Department
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     DELETE / DEACTIVATE CONFIRMATION MODAL
     ══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="deleteDepartmentModal" tabindex="-1" aria-labelledby="deleteDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-body p-4 text-center">
                <div class="mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center bg-danger-subtle text-danger" style="width: 60px; height: 60px; font-size: 1.6rem;">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1" id="deleteDepartmentModalLabel">
                    Remove or Deactivate Department?
                </h5>
                <p class="text-muted small mb-3">You are about to remove the following organizational unit:</p>

                <!-- Department Focus Card -->
                <div class="p-3 mb-3 rounded-3 border border-danger-subtle bg-danger-subtle bg-opacity-25 text-start">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-dark font-monospace px-2.5 py-1" id="deleteDeptCodeBadge">CODE</span>
                        <strong class="text-dark fs-6" id="deleteDeptNameLabel">Department Name</strong>
                    </div>
                    <div class="small text-danger" id="deleteDeptGoalsNotice">
                        <i class="fas fa-info-circle me-1"></i> Linked to strategic goals and historical activity.
                    </div>
                </div>

                <div class="alert alert-warning text-start small mb-4 py-2.5 px-3 border border-warning-subtle" style="font-size: 0.8rem;">
                    <i class="fas fa-shield-halved text-warning me-1.5"></i>
                    <strong>Organizational Audit Trail Protection:</strong> If this department has linked historical plans, weekly tasks, or registered user accounts, it will be safely marked <strong>INACTIVE</strong> to preserve reporting records. If completely unused, it will be permanently deleted.
                </div>

                <form method="POST" id="deleteDepartmentForm" action="">
                    @csrf
                    @method('DELETE')
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-light border px-4 rounded-pill fw-semibold" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger px-4 rounded-pill fw-semibold shadow-xs" id="deleteDeptSubmitBtn">
                            <i class="fas fa-trash-can me-1"></i> Confirm Deactivation / Removal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // ══════════════════════════════════════════════════════════
        // 1. Chart.js Visualizations
        // ══════════════════════════════════════════════════════════
        @php
            $chartLabels = $departments->pluck('department_code')->toArray();
            $chartGoals = $departments->map(fn($d) => $d->annualGoals->count())->toArray();
            $headsAppointedCount = $departments->whereNotNull('head_user_id')->count();
            $headsVacantCount = $departments->whereNull('head_user_id')->count();
        @endphp

        const chartLabels = @json($chartLabels);
        const chartGoals = @json($chartGoals);
        const headsAppointed = {{ $headsAppointedCount }};
        const headsVacant = {{ $headsVacantCount }};

        // Chart 1: Goals per Department
        const goalsCanvas = document.getElementById('deptGoalsChart');
        if (goalsCanvas && typeof Chart !== 'undefined') {
            const ctx = goalsCanvas.getContext('2d');
            const goldGradient = ctx.createLinearGradient(0, 0, 0, 220);
            goldGradient.addColorStop(0, 'rgba(245, 158, 11, 0.9)');
            goldGradient.addColorStop(1, 'rgba(217, 119, 6, 0.45)');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Strategic Goals Owned',
                        data: chartGoals,
                        backgroundColor: goldGradient,
                        borderColor: '#D97706',
                        borderWidth: 1.5,
                        borderRadius: 6,
                        maxBarThickness: 28,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' Strategic Annual Goals Assigned';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, font: { family: 'JetBrains Mono', size: 11 } },
                            grid: { color: 'rgba(0, 0, 0, 0.05)' }
                        },
                        x: {
                            ticks: { font: { family: 'JetBrains Mono', weight: 'bold', size: 10 } },
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // Chart 2: Governance Ratio Donut
        const govCanvas = document.getElementById('deptGovernanceChart');
        if (govCanvas && typeof Chart !== 'undefined') {
            const ctx2 = govCanvas.getContext('2d');
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['Heads Appointed', 'Vacant / Needs Head'],
                    datasets: [{
                        data: [headsAppointed, headsVacant],
                        backgroundColor: ['#059669', '#F59E0B'],
                        borderColor: ['#FFFFFF', '#FFFFFF'],
                        borderWidth: 2,
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return ' ' + context.label + ': ' + context.parsed + ' units';
                                }
                            }
                        }
                    }
                }
            });
        }

        // ══════════════════════════════════════════════════════════
        // 2. DataTables 2.0 Integration & Live Filters
        // ══════════════════════════════════════════════════════════
        let dataTableInstance = null;
        if (typeof $ !== 'undefined' && $.fn.DataTable) {
            dataTableInstance = $('#departmentsDataTable').DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                order: [[0, 'asc']], // Order by Department Code
                columnDefs: [
                    { targets: 5, orderable: false, searchable: false } // Actions column not sortable
                ],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search code, name, head, goals...",
                    lengthMenu: "Show _MENU_ units",
                    info: "Showing _START_ to _END_ of _TOTAL_ departments",
                    infoEmpty: "No departments found",
                    infoFiltered: "(filtered from _MAX_ total)",
                    paginate: {
                        first: '<i class="fas fa-angles-left"></i>',
                        previous: '<i class="fas fa-angle-left"></i>',
                        next: '<i class="fas fa-angle-right"></i>',
                        last: '<i class="fas fa-angles-right"></i>'
                    }
                }
            });
        }

        // Quick Filter Pills Handler
        const filterPills = document.querySelectorAll('#deptFilterPills .nav-link');
        filterPills.forEach(pill => {
            pill.addEventListener('click', function () {
                filterPills.forEach(p => p.classList.remove('active'));
                this.classList.add('active');

                const filterType = this.dataset.filter;
                if (!dataTableInstance) return;

                if (filterType === 'all') {
                    dataTableInstance.column(4).search('').column(2).search('').draw();
                } else if (filterType === 'active') {
                    dataTableInstance.column(4).search('Active').column(2).search('').draw();
                } else if (filterType === 'inactive') {
                    dataTableInstance.column(4).search('Inactive').column(2).search('').draw();
                } else if (filterType === 'vacant') {
                    dataTableInstance.column(4).search('').column(2).search('Vacant').draw();
                } else if (filterType === 'appointed') {
                    // Filter out rows containing Vacant in Head column
                    dataTableInstance.column(4).search('').column(2).search('^(?!.*Vacant).*$', true, false).draw();
                }
            });
        });

        // ══════════════════════════════════════════════════════════
        // 3. Create Department Live Preview & Auto-Uppercase
        // ══════════════════════════════════════════════════════════
        const createCodeInput = document.getElementById('createDeptCodeInput');
        const createNameInput = document.getElementById('createDeptNameInput');
        const previewCodeBadge = document.getElementById('previewCodeBadge');
        const previewNameLabel = document.getElementById('previewNameLabel');
        const previewStatusBadge = document.getElementById('previewStatusBadge');
        const createActiveSelect = document.getElementById('createDeptActiveSelect');

        if (createCodeInput) {
            createCodeInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9_\-]/g, '');
                previewCodeBadge.textContent = this.value || 'NEW';
            });
        }
        if (createNameInput) {
            createNameInput.addEventListener('input', function() {
                previewNameLabel.textContent = this.value || 'New Department Name';
            });
        }
        if (createActiveSelect) {
            createActiveSelect.addEventListener('change', function() {
                if (this.value === '1') {
                    previewStatusBadge.className = 'badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 small';
                    previewStatusBadge.textContent = 'Active';
                } else {
                    previewStatusBadge.className = 'badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-0.5 small';
                    previewStatusBadge.textContent = 'Inactive';
                }
            });
        }

        // Form Submit Loading State & Toast
        const createForm = document.getElementById('createDepartmentForm');
        if (createForm) {
            createForm.addEventListener('submit', function() {
                const submitBtn = document.getElementById('createDeptSubmitBtn');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creating Department...';
                }
            });
        }

        // ══════════════════════════════════════════════════════════
        // 4. Edit Department Modal Wiring
        // ══════════════════════════════════════════════════════════
        const editForm = document.getElementById('editDepartmentForm');
        const editCodeInput = document.getElementById('editDeptCode');
        const editNameInput = document.getElementById('editDeptName');
        const editHeadSelect = document.getElementById('editDeptHead');
        const editActiveSelect = document.getElementById('editDeptActive');
        const editGoalsSelect = document.getElementById('editDeptGoals');

        document.querySelectorAll('.edit-dept-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                const code = this.dataset.code || '';
                const name = this.dataset.name || '';
                const head = this.dataset.head || '';
                const active = this.dataset.active;
                let goals = [];
                try {
                    goals = JSON.parse(this.dataset.goals || '[]');
                } catch (e) {
                    goals = [];
                }

                // Update Form Action
                editForm.action = "{{ url('admin/departments') }}/" + id;

                // Populate Fields
                editCodeInput.value = code;
                editNameInput.value = name;
                
                // Head Select
                if (editHeadSelect.tomselect) {
                    editHeadSelect.tomselect.setValue(head);
                } else {
                    editHeadSelect.value = head;
                }

                // Active Select
                if (editActiveSelect.tomselect) {
                    editActiveSelect.tomselect.setValue(active == '1' ? '1' : '0');
                } else {
                    editActiveSelect.value = active == '1' ? '1' : '0';
                }

                // Multi Goals Select
                if (editGoalsSelect.tomselect) {
                    editGoalsSelect.tomselect.setValue(goals);
                } else {
                    Array.from(editGoalsSelect.options).forEach(opt => {
                        opt.selected = goals.includes(parseInt(opt.value));
                    });
                }
            });
        });

        if (editForm) {
            editForm.addEventListener('submit', function() {
                const submitBtn = document.getElementById('editDeptSubmitBtn');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving Changes...';
                }
            });
        }

        // ══════════════════════════════════════════════════════════
        // 5. Delete / Deactivate Confirmation Modal Wiring
        // ══════════════════════════════════════════════════════════
        const deleteForm = document.getElementById('deleteDepartmentForm');
        const deleteCodeBadge = document.getElementById('deleteDeptCodeBadge');
        const deleteNameLabel = document.getElementById('deleteDeptNameLabel');
        const deleteGoalsNotice = document.getElementById('deleteDeptGoalsNotice');
        const deleteModalEl = document.getElementById('deleteDepartmentModal');
        const bsDeleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;

        document.querySelectorAll('.delete-dept-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                const code = this.dataset.code || '';
                const name = this.dataset.name || '';
                const goalsCount = parseInt(this.dataset.goalsCount || '0', 10);

                if (deleteForm) {
                    deleteForm.action = "{{ url('admin/departments') }}/" + id;
                }
                if (deleteCodeBadge) deleteCodeBadge.textContent = code;
                if (deleteNameLabel) deleteNameLabel.textContent = name;
                if (deleteGoalsNotice) {
                    if (goalsCount > 0) {
                        deleteGoalsNotice.innerHTML = `<i class="fas fa-bullseye me-1 text-warning"></i> Department currently owns <strong>${goalsCount}</strong> strategic annual ${goalsCount === 1 ? 'goal' : 'goals'}.`;
                    } else {
                        deleteGoalsNotice.innerHTML = `<i class="fas fa-check-circle me-1 text-success"></i> No active annual strategic goals linked.`;
                    }
                }

                const submitBtn = document.getElementById('deleteDeptSubmitBtn');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-trash-can me-1"></i> Confirm Deactivation / Removal';
                }

                if (bsDeleteModal) {
                    bsDeleteModal.show();
                }
            });
        });

        if (deleteForm) {
            deleteForm.addEventListener('submit', function() {
                const submitBtn = document.getElementById('deleteDeptSubmitBtn');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';
                }
            });
        }

        // ══════════════════════════════════════════════════════════
        // 6. Auto Re-open Create Modal on Validation Error
        // ══════════════════════════════════════════════════════════
        @if(isset($errors) && $errors->any() && (old('department_code') || old('department_name')))
            const createModal = new bootstrap.Modal(document.getElementById('createDepartmentModal'));
            createModal.show();
        @endif
    });
</script>
@endsection

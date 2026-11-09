@extends('layouts.app')

@section('title', 'Departments Management')

@section('styles')
<style>
    /* DataTables Kaldis Custom Styling */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0.25rem 0.65rem !important;
        border-radius: 6px !important;
        margin: 0 2px !important;
        font-size: 0.825rem !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: var(--k-gold, #D97706) !important;
        color: #ffffff !important;
        border-color: var(--k-gold, #D97706) !important;
    }
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_length {
        font-size: 0.825rem !important;
        color: var(--bs-secondary-color, #64748B) !important;
    }
    .dataTables_wrapper .dataTables_length select {
        padding: 0.25rem 1.75rem 0.25rem 0.65rem !important;
        border-radius: 6px !important;
        font-size: 0.825rem !important;
        border-color: var(--bs-border-color, #E2E8F0) !important;
    }
    .dept-filter-btn.active {
        background-color: var(--k-gold, #D97706) !important;
        border-color: var(--k-gold, #D97706) !important;
        color: #ffffff !important;
        box-shadow: 0 2px 4px rgba(217, 119, 6, 0.25);
    }
    .metric-card-hover {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .metric-card-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -4px rgba(0, 0, 0, 0.08) !important;
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Action Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="fa-solid fa-sitemap text-warning"></i> Company Departments
            </h4>
            <p class="text-muted small mb-0">Organizational units, department codes, appointed heads, and annual strategy ownership</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 shadow-xs" id="toggleAnalyticsBtn" onclick="toggleDeptAnalytics()">
                <i class="fa-solid fa-chart-column text-warning"></i>
                <span id="analyticsBtnText">Show Analytics</span>
            </button>
            <button type="button" class="btn btn-warning text-dark fw-bold shadow-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createDepartmentModal">
                <i class="fa-solid fa-plus"></i> Add Department
            </button>
        </div>
    </div>

    <!-- Quick Metrics Row -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm bg-white mb-0 metric-card-hover rounded-3">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                        <i class="fa-solid fa-building fs-5"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.04em;">Total Departments</div>
                        <div class="fw-bold text-dark fs-5">{{ $departments->count() }} Units</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm bg-white mb-0 metric-card-hover rounded-3">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                        <i class="fa-solid fa-circle-check fs-5"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.04em;">Active Units</div>
                        <div class="fw-bold text-dark fs-5">{{ $departments->where('active', 1)->count() }} Active</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm bg-white mb-0 metric-card-hover rounded-3">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                        <i class="fa-solid fa-user-tie fs-5"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.04em;">Heads Appointed</div>
                        <div class="fw-bold text-dark fs-5">{{ $departments->whereNotNull('head_user_id')->count() }} / {{ $departments->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm bg-white mb-0 metric-card-hover rounded-3">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                        <i class="fa-solid fa-bullseye fs-5"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.04em;">Strategic Linkages</div>
                        <div class="fw-bold text-dark fs-5">{{ $departments->sum(fn($d) => $d->annualGoals->count()) }} Goals</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Governance & Strategy Analytics (Collapsible) -->
    <div id="deptAnalyticsSection" class="card border border-light-subtle shadow-sm bg-white rounded-3 mb-4 d-none">
        <div class="card-header bg-white py-3 border-bottom border-light-subtle d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-chart-pie text-warning"></i>
                <h6 class="fw-bold mb-0 text-dark">Strategic Governance & Alignment Insights</h6>
            </div>
            <button type="button" class="btn btn-sm btn-link text-muted p-0 text-decoration-none" onclick="toggleDeptAnalytics()">
                <i class="fa-solid fa-xmark"></i> Close Insights
            </button>
        </div>
        <div class="card-body p-4">
            <div class="row g-4 align-items-center">
                <!-- Bar Chart: Annual Goals Allocation by Department -->
                <div class="col-lg-8 border-end-lg">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="small fw-bold text-uppercase text-secondary mb-0" style="letter-spacing: 0.05em;">
                            <i class="fa-solid fa-bars-progress text-warning me-1"></i> Strategy Goals Ownership by Department
                        </h6>
                        <span class="badge bg-light text-muted border font-monospace small">{{ $annualGoals->count() }} Corporate Goals</span>
                    </div>
                    <div style="height: 250px; position: relative;">
                        <canvas id="deptGoalsChart"></canvas>
                    </div>
                </div>
                <!-- Doughnut Chart: Department Governance & Operational Readiness -->
                <div class="col-lg-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="small fw-bold text-uppercase text-secondary mb-0" style="letter-spacing: 0.05em;">
                            <i class="fa-solid fa-shield-halved text-success me-1"></i> Governance Readiness
                        </h6>
                    </div>
                    <div style="height: 250px; position: relative;">
                        <canvas id="deptGovernanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Departments Directory Table Card with DataTables -->
    <div class="card border border-light-subtle shadow-sm bg-white rounded-3">
        <div class="card-header bg-white py-3 border-bottom border-light-subtle">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2 me-2">
                        <h6 class="fw-bold mb-0 text-dark">Directory of Operational Units</h6>
                        <span class="badge bg-secondary-subtle text-secondary font-monospace">{{ $departments->count() }} Registered</span>
                    </div>
                    <!-- Quick Filter Buttons -->
                    <div class="btn-group btn-group-sm" role="group" aria-label="Department Filter">
                        <button type="button" class="btn btn-outline-secondary active dept-filter-btn" data-filter="all">All ({{ $departments->count() }})</button>
                        <button type="button" class="btn btn-outline-secondary dept-filter-btn" data-filter="active">Active ({{ $departments->where('active', 1)->count() }})</button>
                        <button type="button" class="btn btn-outline-secondary dept-filter-btn" data-filter="inactive">Inactive ({{ $departments->where('active', 0)->count() }})</button>
                        <button type="button" class="btn btn-outline-secondary dept-filter-btn" data-filter="head">With Head ({{ $departments->whereNotNull('head_user_id')->count() }})</button>
                    </div>
                </div>
                <div class="w-100 w-lg-auto" style="min-width: 260px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-light-subtle text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" id="deptSearchInput" class="form-control bg-light border-light-subtle" placeholder="Instant filter departments...">
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive p-3">
            <table class="table table-hover align-middle mb-0 w-100" id="departmentsTable" style="font-size: 0.88rem;">
                <thead class="table-light">
                    <tr>
                        <th style="width: 90px;" class="text-center">Code</th>
                        <th>Department Name</th>
                        <th>Appointed Head</th>
                        <th>Strategy Goals Owned</th>
                        <th style="width: 110px;" class="text-center">Status</th>
                        <th style="width: 140px;" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $dept)
                        @php
                            $goalIds = $dept->annualGoals->pluck('id')->toArray();
                            $hasHead = !empty($dept->head_user_id);
                        @endphp
                        <tr data-status="{{ $dept->active ? 'active' : 'inactive' }}" data-has-head="{{ $hasHead ? 'yes' : 'no' }}">
                            <td class="text-center">
                                <span class="badge bg-dark font-monospace px-2 py-1 shadow-xs">{{ $dept->department_code }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-2 bg-light text-secondary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                        <i class="fa-solid fa-building-user text-muted" style="font-size: 0.85rem;"></i>
                                    </div>
                                    <div>
                                        <strong class="text-dark d-block">{{ $dept->department_name }}</strong>
                                        <span class="text-muted small font-monospace" style="font-size: 0.72rem;">ID: #{{ $dept->id }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($dept->head)
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-warning bg-opacity-20 text-dark d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                            {{ strtoupper(substr($dept->head->full_name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="fw-semibold text-dark d-block">{{ $dept->head->full_name }}</span>
                                            <span class="text-muted small" style="font-size: 0.72rem;">{{ $dept->head->username }}</span>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted small fst-italic d-inline-flex align-items-center gap-1">
                                        <i class="fa-solid fa-circle-question text-muted opacity-50"></i> Not Appointed
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($dept->annualGoals->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($dept->annualGoals->take(4) as $goal)
                                            <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.7rem;" title="{{ $goal->goal_description }}">
                                                {{ $goal->goal_code }}
                                            </span>
                                        @endforeach
                                        @if($dept->annualGoals->count() > 4)
                                            <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.7rem;">+{{ $dept->annualGoals->count() - 4 }} more</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted small fst-italic">No goals assigned</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($dept->active)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="fa-solid fa-circle text-success me-1" style="font-size: 0.5rem;"></i> Active
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                        <i class="fa-solid fa-circle text-secondary me-1" style="font-size: 0.5rem;"></i> Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <!-- Edit Department -->
                                    <button type="button" class="btn btn-outline-secondary edit-dept-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editDepartmentModal"
                                            data-id="{{ $dept->id }}"
                                            data-code="{{ $dept->department_code }}"
                                            data-name="{{ $dept->department_name }}"
                                            data-head="{{ $dept->head_user_id }}"
                                            data-active="{{ $dept->active }}"
                                            data-goals="{{ json_encode($goalIds) }}"
                                            title="Edit Department">
                                        <i class="fa-solid fa-pencil"></i>
                                    </button>

                                    @if($dept->active)
                                        <!-- Deactivate / Delete Action -->
                                        <button type="button" class="btn btn-outline-danger delete-dept-btn"
                                                data-id="{{ $dept->id }}"
                                                data-name="{{ $dept->department_name }}"
                                                data-code="{{ $dept->department_code }}"
                                                data-active="1"
                                                data-goals="{{ $dept->annualGoals->count() }}"
                                                data-head="{{ $dept->head ? $dept->head->full_name : 'No Head' }}"
                                                title="Deactivate or Remove Department">
                                            <i class="fa-solid fa-power-off"></i>
                                        </button>
                                    @else
                                        <!-- Reactivate Button -->
                                        <button type="button" class="btn btn-outline-success reactivate-dept-btn"
                                                data-id="{{ $dept->id }}"
                                                data-name="{{ $dept->department_name }}"
                                                data-code="{{ $dept->department_code }}"
                                                title="Reactivate Department">
                                            <i class="fa-solid fa-rotate-left"></i>
                                        </button>
                                        <!-- Permanent Delete Action for Inactive -->
                                        <button type="button" class="btn btn-outline-danger delete-dept-btn"
                                                data-id="{{ $dept->id }}"
                                                data-name="{{ $dept->department_name }}"
                                                data-code="{{ $dept->department_code }}"
                                                data-active="0"
                                                data-goals="{{ $dept->annualGoals->count() }}"
                                                data-head="{{ $dept->head ? $dept->head->full_name : 'No Head' }}"
                                                title="Permanently Remove Inactive Department">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="fa-solid fa-building fa-2x mb-2 opacity-25 d-block"></i>
                                No departments found. Click "Add Department" above to create one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="createDepartmentModalLabel">
                    <i class="fa-solid fa-plus-circle text-warning"></i> Create New Department
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.departments.store') }}" id="createDepartmentForm">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Department Code <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-light-subtle text-muted"><i class="fa-solid fa-id-badge"></i></span>
                                <input type="text" name="department_code" class="form-control font-monospace text-uppercase" placeholder="e.g. MKT" maxlength="20" required oninput="this.value = this.value.toUpperCase();">
                            </div>
                            <div class="form-text small">Short identifier (e.g. OPS, FIN, HR)</div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-secondary">Department Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-light-subtle text-muted"><i class="fa-solid fa-building"></i></span>
                                <input type="text" name="department_name" class="form-control" placeholder="e.g. Marketing & Corporate Communications" maxlength="150" required>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-secondary">Appointed Department Head</label>
                            <select name="head_user_id" class="form-select">
                                <option value="">-- No Head Appointed Yet --</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->full_name }} ({{ $u->username }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Status</label>
                            <select name="active" class="form-select">
                                <option value="1" selected>Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Assigned Annual Strategy Goals (Ownership)</label>
                        <select name="annual_goal_ids[]" class="form-select" multiple placeholder="Select strategic goals owned by this department...">
                            @foreach($annualGoals as $g)
                                <option value="{{ $g->id }}">
                                    [{{ $g->goal_code }}] {{ Str::limit($g->goal_description, 80) }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text small">Link goals that this department is responsible for achieving.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 border-top border-light-subtle">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="createDeptSubmitBtn" class="btn btn-sm btn-warning text-dark fw-bold px-4 d-inline-flex align-items-center gap-2">
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        <i class="fa-solid fa-check"></i>
                        <span>Create Department</span>
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
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="editDepartmentModalLabel">
                    <i class="fa-solid fa-pencil text-warning"></i> Edit Department
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="editDepartmentForm" action="">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Department Code <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-light-subtle text-muted"><i class="fa-solid fa-id-badge"></i></span>
                                <input type="text" name="department_code" id="editDeptCode" class="form-control font-monospace text-uppercase" maxlength="20" required oninput="this.value = this.value.toUpperCase();">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-secondary">Department Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-light-subtle text-muted"><i class="fa-solid fa-building"></i></span>
                                <input type="text" name="department_name" id="editDeptName" class="form-control" maxlength="150" required>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-secondary">Appointed Department Head</label>
                            <select name="head_user_id" id="editDeptHead" class="form-select">
                                <option value="">-- No Head Appointed Yet --</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->full_name }} ({{ $u->username }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Status</label>
                            <select name="active" id="editDeptActive" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Assigned Annual Strategy Goals</label>
                        <select name="annual_goal_ids[]" id="editDeptGoals" class="form-select" multiple placeholder="Select strategic goals owned by this department...">
                            @foreach($annualGoals as $g)
                                <option value="{{ $g->id }}">
                                    [{{ $g->goal_code }}] {{ Str::limit($g->goal_description, 80) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 border-top border-light-subtle">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="editDeptSubmitBtn" class="btn btn-sm btn-warning text-dark fw-bold px-4 d-inline-flex align-items-center gap-2">
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Update Department</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     DELETE / DEACTIVATE CONFIRMATION MODAL (ENHANCED UX)
     ══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="deleteDepartmentModal" tabindex="-1" aria-labelledby="deleteDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-start">
                <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                    <i class="fa-solid fa-triangle-exclamation fs-4"></i>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pt-3 pb-2">
                <h5 class="fw-bold text-dark mb-1" id="deleteDepartmentModalLabel">Remove / Deactivate Department</h5>
                <p class="text-muted small mb-3">Please review the organizational impact before proceeding.</p>

                <!-- Department Details Card -->
                <div class="card border border-light-subtle rounded-3 bg-light bg-opacity-50 p-3 mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge bg-dark font-monospace px-2 py-1" id="delModalCode">--</span>
                        <span id="delModalStatusBadge" class="badge">--</span>
                    </div>
                    <h6 class="fw-bold text-dark mb-1" id="delModalName">--</h6>
                    <div class="small text-muted d-flex align-items-center gap-3 mt-2">
                        <span><i class="fa-solid fa-bullseye text-warning me-1"></i> <span id="delModalGoalsCount">0</span> Goals Linked</span>
                        <span><i class="fa-solid fa-user-tie text-primary me-1"></i> <span id="delModalHeadStatus">No Head</span></span>
                    </div>
                </div>

                <!-- Audit Protection Notice -->
                <div class="alert alert-warning border-0 bg-warning bg-opacity-10 py-2.5 px-3 small d-flex align-items-start gap-2 mb-0">
                    <i class="fa-solid fa-shield-halved text-warning mt-1 flex-shrink-0"></i>
                    <div class="text-muted" style="line-height: 1.45;">
                        <strong class="text-dark">Audit Protection Safeguard:</strong>
                        If this department has associated operational records (monthly plans, weekly performance tasks, or active user accounts), the system will safely mark it <strong>INACTIVE</strong> instead of hard-deleting it to preserve corporate audit trails.
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-2 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteDepartmentForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" id="confirmDeleteSubmitBtn" class="btn btn-danger fw-bold d-inline-flex align-items-center gap-2">
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        <i class="fa-solid fa-power-off"></i>
                        <span id="confirmDeleteBtnText">Confirm Action</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     REACTIVATE CONFIRMATION MODAL
     ══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="reactivateDepartmentModal" tabindex="-1" aria-labelledby="reactivateDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-start">
                <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                    <i class="fa-solid fa-rotate-left fs-4"></i>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pt-3 pb-2">
                <h5 class="fw-bold text-dark mb-1" id="reactivateDepartmentModalLabel">Reactivate Department</h5>
                <p class="text-muted small mb-3">Re-enable this department for organizational planning and reporting.</p>

                <div class="card border border-light-subtle rounded-3 bg-light bg-opacity-50 p-3 mb-2">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge bg-dark font-monospace px-2 py-1" id="reactModalCode">--</span>
                        <span class="badge bg-secondary-subtle text-secondary">Currently Inactive</span>
                    </div>
                    <h6 class="fw-bold text-dark mb-0" id="reactModalName">--</h6>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-2 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="reactivateDepartmentForm" method="POST" action="">
                    @csrf
                    @method('PATCH')
                    <button type="submit" id="confirmReactivateSubmitBtn" class="btn btn-success fw-bold d-inline-flex align-items-center gap-2">
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Reactivate Unit</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Toggle Analytics Section
    function toggleDeptAnalytics() {
        const sec = document.getElementById('deptAnalyticsSection');
        const btnText = document.getElementById('analyticsBtnText');
        if (!sec) return;
        const isHidden = sec.classList.contains('d-none');
        if (isHidden) {
            sec.classList.remove('d-none');
            btnText.textContent = 'Hide Analytics';
            initDeptCharts();
        } else {
            sec.classList.add('d-none');
            btnText.textContent = 'Show Analytics';
        }
    }

    let chartsInitialized = false;
    function initDeptCharts() {
        if (chartsInitialized) return;
        chartsInitialized = true;

        // 1. Strategic Goals Ownership Chart
        const deptLabels = {!! json_encode($departments->pluck('department_code')) !!};
        const deptNames = {!! json_encode($departments->pluck('department_name')) !!};
        const deptGoalCounts = {!! json_encode($departments->map(fn($d) => $d->annualGoals->count())) !!};

        const goalsCanvas = document.getElementById('deptGoalsChart');
        if (goalsCanvas) {
            new Chart(goalsCanvas, {
                type: 'bar',
                data: {
                    labels: deptLabels,
                    datasets: [{
                        label: 'Annual Strategy Goals',
                        data: deptGoalCounts,
                        backgroundColor: 'rgba(217, 119, 6, 0.75)',
                        borderColor: '#D97706',
                        borderWidth: 1.5,
                        borderRadius: 6,
                        hoverBackgroundColor: '#B45309'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: function(items) {
                                    const idx = items[0].dataIndex;
                                    return deptNames[idx] + ' (' + deptLabels[idx] + ')';
                                },
                                label: function(item) {
                                    return ' ' + item.raw + ' Strategic Goals Assigned';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, stepSize: 1 },
                            grid: { color: 'rgba(0, 0, 0, 0.05)' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { family: 'JetBrains Mono', size: 10 } }
                        }
                    }
                }
            });
        }

        // 2. Governance Readiness Doughnut Chart
        const govCanvas = document.getElementById('deptGovernanceChart');
        if (govCanvas) {
            const activeWithHead = {{ $departments->where('active', 1)->whereNotNull('head_user_id')->count() }};
            const activeWithoutHead = {{ $departments->where('active', 1)->whereNull('head_user_id')->count() }};
            const inactive = {{ $departments->where('active', 0)->count() }};

            new Chart(govCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Appointed Head', 'Vacant Head', 'Inactive Units'],
                    datasets: [{
                        data: [activeWithHead, activeWithoutHead, inactive],
                        backgroundColor: [
                            '#10B981',
                            '#F59E0B',
                            '#94A3B8'
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, padding: 12, font: { size: 11 } }
                        }
                    },
                    cutout: '68%'
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        // ══════════════════════════════════════════════════════════
        // 1. DATATABLES INITIALIZATION
        // ══════════════════════════════════════════════════════════
        let dt = null;
        if (typeof $.fn.DataTable !== 'undefined') {
            dt = $('#departmentsTable').DataTable({
                responsive: true,
                pageLength: 16,
                lengthMenu: [[10, 16, 25, 50, -1], ['10 rows', '16 rows', '25 rows', '50 rows', 'All']],
                order: [[1, 'asc']], // Order by Department Name
                dom: "<'row g-2 align-items-center mb-3'<'col-sm-6'l><'col-sm-6 text-sm-end'>>" +
                     "<'table-responsive'tr>" +
                     "<'row g-2 align-items-center mt-3'<'col-sm-6'i><'col-sm-6 text-sm-end'p>>",
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search departments...",
                    lengthMenu: "Show _MENU_",
                    info: "Showing _START_ to _END_ of _TOTAL_ departments",
                    infoEmpty: "Showing 0 to 0 of 0 departments",
                    infoFiltered: "(filtered from _MAX_ total)",
                    zeroRecords: "No matching departments found",
                    paginate: {
                        previous: '<i class="fa-solid fa-chevron-left"></i>',
                        next: '<i class="fa-solid fa-chevron-right"></i>'
                    }
                },
                columnDefs: [
                    { targets: 0, className: 'text-center' },
                    { targets: 4, className: 'text-center' },
                    { targets: 5, orderable: false, searchable: false, className: 'text-end' }
                ]
            });

            // Wire custom search input
            $('#deptSearchInput').on('keyup', function() {
                dt.search(this.value).draw();
            });

            // Wire filter buttons
            $('.dept-filter-btn').on('click', function() {
                $('.dept-filter-btn').removeClass('active');
                $(this).addClass('active');

                const filter = $(this).data('filter');
                if (filter === 'all') {
                    dt.column(4).search('').column(2).search('').draw();
                } else if (filter === 'active') {
                    dt.column(4).search('Active').column(2).search('').draw();
                } else if (filter === 'inactive') {
                    dt.column(4).search('Inactive').column(2).search('').draw();
                } else if (filter === 'head') {
                    dt.column(4).search('').column(2).search('^(?!.*Not Appointed).*$', true, false).draw();
                }
            });
        } else {
            // Fallback live search if DataTables is unavailable
            setupLiveSearch('deptSearchInput', 'departmentsTable');
        }

        // ══════════════════════════════════════════════════════════
        // 2. WIRE EDIT DEPARTMENT MODAL
        // ══════════════════════════════════════════════════════════
        const editForm = document.getElementById('editDepartmentForm');
        const codeInput = document.getElementById('editDeptCode');
        const nameInput = document.getElementById('editDeptName');
        const headSelect = document.getElementById('editDeptHead');
        const activeSelect = document.getElementById('editDeptActive');
        const goalsSelect = document.getElementById('editDeptGoals');

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

                editForm.action = "{{ url('admin/departments') }}/" + id;

                codeInput.value = code;
                nameInput.value = name;
                
                if (headSelect.tomselect) {
                    headSelect.tomselect.setValue(head);
                } else {
                    headSelect.value = head;
                }

                if (activeSelect.tomselect) {
                    activeSelect.tomselect.setValue(active == '1' ? '1' : '0');
                } else {
                    activeSelect.value = active == '1' ? '1' : '0';
                }

                if (goalsSelect.tomselect) {
                    goalsSelect.tomselect.setValue(goals);
                } else {
                    Array.from(goalsSelect.options).forEach(opt => {
                        opt.selected = goals.includes(parseInt(opt.value));
                    });
                }
            });
        });

        // ══════════════════════════════════════════════════════════
        // 3. WIRE DELETE / DEACTIVATE MODAL (NO BROWSER CONFIRM)
        // ══════════════════════════════════════════════════════════
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteDepartmentModal'));
        const deleteForm = document.getElementById('deleteDepartmentForm');
        const delCode = document.getElementById('delModalCode');
        const delName = document.getElementById('delModalName');
        const delStatusBadge = document.getElementById('delModalStatusBadge');
        const delGoalsCount = document.getElementById('delModalGoalsCount');
        const delHeadStatus = document.getElementById('delModalHeadStatus');
        const delBtnText = document.getElementById('confirmDeleteBtnText');

        $(document).on('click', '.delete-dept-btn', function () {
            const id = $(this).data('id');
            const code = $(this).data('code') || '';
            const name = $(this).data('name') || '';
            const active = $(this).data('active') == '1';
            const goals = $(this).data('goals') || 0;
            const head = $(this).data('head') || 'No Head';

            deleteForm.action = "{{ url('admin/departments') }}/" + id;

            delCode.textContent = code;
            delName.textContent = name;
            delGoalsCount.textContent = goals;
            delHeadStatus.textContent = head;

            if (active) {
                delStatusBadge.className = 'badge bg-success-subtle text-success border border-success-subtle';
                delStatusBadge.textContent = 'Active';
                delBtnText.textContent = 'Confirm Deactivate / Remove';
            } else {
                delStatusBadge.className = 'badge bg-secondary-subtle text-secondary border border-secondary-subtle';
                delStatusBadge.textContent = 'Inactive';
                delBtnText.textContent = 'Confirm Permanent Deletion';
            }

            deleteModal.show();
        });

        // ══════════════════════════════════════════════════════════
        // 4. WIRE REACTIVATE MODAL
        // ══════════════════════════════════════════════════════════
        const reactivateModal = new bootstrap.Modal(document.getElementById('reactivateDepartmentModal'));
        const reactivateForm = document.getElementById('reactivateDepartmentForm');
        const reactCode = document.getElementById('reactModalCode');
        const reactName = document.getElementById('reactModalName');

        $(document).on('click', '.reactivate-dept-btn', function () {
            const id = $(this).data('id');
            const code = $(this).data('code') || '';
            const name = $(this).data('name') || '';

            reactivateForm.action = "{{ url('admin/departments') }}/" + id + "/reactivate";
            reactCode.textContent = code;
            reactName.textContent = name;

            reactivateModal.show();
        });

        // ══════════════════════════════════════════════════════════
        // 5. BUTTON LOADING SPINNERS ON FORM SUBMISSIONS
        // ══════════════════════════════════════════════════════════
        ['createDepartmentForm', 'editDepartmentForm', 'deleteDepartmentForm', 'reactivateDepartmentForm'].forEach(formId => {
            const f = document.getElementById(formId);
            if (f) {
                f.addEventListener('submit', function () {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        const spinner = submitBtn.querySelector('.spinner-border');
                        if (spinner) spinner.classList.remove('d-none');
                        submitBtn.disabled = true;
                    }
                });
            }
        });
    });
</script>
@endsection

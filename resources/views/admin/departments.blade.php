@extends('layouts.app')

@section('title', 'Departments Management')

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Action Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="fas fa-sitemap text-warning"></i> Company Departments
            </h4>
            <p class="text-muted small mb-0">Organizational units, department codes, appointed heads, and annual strategy ownership</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-warning text-dark fw-bold shadow-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createDepartmentModal">
                <i class="fas fa-plus"></i> Add Department
            </button>
        </div>
    </div>

    <!-- Quick Metrics Row -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm bg-white mb-0">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                        <i class="fas fa-building fs-5"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Total Departments</div>
                        <div class="fw-bold text-dark fs-5">{{ $departments->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm bg-white mb-0">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                        <i class="fas fa-circle-check fs-5"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Active Units</div>
                        <div class="fw-bold text-dark fs-5">{{ $departments->where('active', 1)->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm bg-white mb-0">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                        <i class="fas fa-user-tie fs-5"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Heads Appointed</div>
                        <div class="fw-bold text-dark fs-5">{{ $departments->whereNotNull('head_user_id')->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm bg-white mb-0">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                        <i class="fas fa-bullseye fs-5"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Strategic Linkages</div>
                        <div class="fw-bold text-dark fs-5">{{ $departments->sum(fn($d) => $d->annualGoals->count()) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Departments Table Card -->
    <div class="card border border-light-subtle shadow-sm bg-white">
        <div class="card-header bg-white py-3 border-bottom border-light-subtle d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <h6 class="fw-bold mb-0 text-dark">Directory of Operational Units</h6>
                <span class="badge bg-secondary-subtle text-secondary font-monospace">{{ $departments->count() }} Registered</span>
            </div>
            <div class="w-100 w-sm-auto" style="min-width: 260px;">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-light-subtle text-muted"><i class="fas fa-search"></i></span>
                    <input type="text" id="deptSearchInput" class="form-control bg-light border-light-subtle" placeholder="Filter departments...">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="departmentsTable" style="font-size: 0.88rem;">
                <thead class="table-light">
                    <tr>
                        <th style="width: 100px;">Code</th>
                        <th>Department Name</th>
                        <th>Appointed Head</th>
                        <th>Strategy Goals Owned</th>
                        <th style="width: 110px;">Status</th>
                        <th style="width: 130px;" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $dept)
                        @php
                            $goalIds = $dept->annualGoals->pluck('id')->toArray();
                        @endphp
                        <tr>
                            <td>
                                <span class="badge bg-dark font-monospace px-2 py-1">{{ $dept->department_code }}</span>
                            </td>
                            <td>
                                <strong class="text-dark fs-6">{{ $dept->department_name }}</strong>
                            </td>
                            <td>
                                @if($dept->head)
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-warning bg-opacity-20 text-dark d-flex align-items-center justify-content-center fw-bold" style="width: 26px; height: 26px; font-size: 0.75rem;">
                                            {{ strtoupper(substr($dept->head->full_name, 0, 1)) }}
                                        </div>
                                        <span class="fw-semibold text-dark">{{ $dept->head->full_name }}</span>
                                    </div>
                                @else
                                    <span class="text-muted small fst-italic"><i class="fas fa-circle-notch text-muted me-1"></i> Not Appointed</span>
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
                                            <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">+{{ $dept->annualGoals->count() - 4 }} more</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted small fst-italic">No goals assigned</span>
                                @endif
                            </td>
                            <td>
                                @if($dept->active)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="fas fa-circle text-success me-1" style="font-size: 0.5rem;"></i> Active
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                        <i class="fas fa-circle text-secondary me-1" style="font-size: 0.5rem;"></i> Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
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
                                        <i class="fas fa-pencil"></i>
                                    </button>
                                    @if($dept->active)
                                        <form method="POST" action="{{ route('admin.departments.destroy', $dept->id) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to deactivate or remove this department?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Deactivate Department">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
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

<!-- ══════════════════════════════════════════════════════════
     CREATE DEPARTMENT MODAL
     ══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="createDepartmentModal" tabindex="-1" aria-labelledby="createDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-warning bg-opacity-10 py-3 border-bottom border-light-subtle">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="createDepartmentModalLabel">
                    <i class="fas fa-plus-circle text-warning"></i> Create New Department
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.departments.store') }}">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Department Code <span class="text-danger">*</span></label>
                            <input type="text" name="department_code" class="form-control font-monospace text-uppercase" placeholder="e.g. MKT" maxlength="20" required>
                            <div class="form-text small">Short identifier (e.g. OPS, FIN, HR)</div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-secondary">Department Name <span class="text-danger">*</span></label>
                            <input type="text" name="department_name" class="form-control" placeholder="e.g. Marketing & Corporate Communications" maxlength="150" required>
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
                    <button type="submit" class="btn btn-sm btn-warning text-dark fw-bold px-4">
                        <i class="fas fa-check me-1"></i> Create Department
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
                    <i class="fas fa-pencil text-warning"></i> Edit Department
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
                            <input type="text" name="department_code" id="editDeptCode" class="form-control font-monospace text-uppercase" maxlength="20" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-secondary">Department Name <span class="text-danger">*</span></label>
                            <input type="text" name="department_name" id="editDeptName" class="form-control" maxlength="150" required>
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
                    <button type="submit" class="btn btn-sm btn-warning text-dark fw-bold px-4">
                        <i class="fas fa-save me-1"></i> Update Department
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Instant Table Live Search
        setupLiveSearch('deptSearchInput', 'departmentsTable');

        // 2. Wire Edit Department Modal
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

                // Update Form Action
                editForm.action = "{{ url('admin/departments') }}/" + id;

                // Populate Fields
                codeInput.value = code;
                nameInput.value = name;
                
                // Head Select
                if (headSelect.tomselect) {
                    headSelect.tomselect.setValue(head);
                } else {
                    headSelect.value = head;
                }

                // Active Select
                if (activeSelect.tomselect) {
                    activeSelect.tomselect.setValue(active == '1' ? '1' : '0');
                } else {
                    activeSelect.value = active == '1' ? '1' : '0';
                }

                // Multi Goals Select
                if (goalsSelect.tomselect) {
                    goalsSelect.tomselect.setValue(goals);
                } else {
                    Array.from(goalsSelect.options).forEach(opt => {
                        opt.selected = goals.includes(parseInt(opt.value));
                    });
                }
            });
        });
    });
</script>
@endsection

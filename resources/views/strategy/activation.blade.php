@extends('layouts.app')

@section('title', 'GM Monthly Strategy Activation')

@section('content')
<!-- Header with Executive Metric Badges -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="fas fa-toggle-on text-warning me-2"></i> GM Monthly Strategy Activation
        </h4>
        <p class="text-muted small mb-0">General Manager authorization portal for activating Annual Strategies for <strong>{{ $selectedMonth }} {{ $selectedYear }}</strong>.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('strategy.annual', ['year' => $selectedYear]) }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-bullseye me-1"></i> Annual Goals Master
        </a>
        <a href="{{ route('monthly.plans', ['year' => $selectedYear, 'month' => $selectedMonth]) }}" class="btn btn-sm btn-kaldis">
            <i class="fas fa-calendar-check me-1"></i> View Monthly Plans
        </a>
    </div>
</div>

<!-- Rule Highlight Alert -->
<div class="alert alert-warning border-warning d-flex align-items-center mb-4 shadow-sm bg-white py-2 px-3 rounded-3">
    <div class="p-2 bg-warning-subtle text-warning rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
        <i class="fas fa-shield-halved"></i>
    </div>
    <div class="small">
        <strong>Mandatory Workflow Rule (Rule 2):</strong> Strategies are <strong>FALSE (Inactive)</strong> by default. When the General Manager turns a strategic plan to <strong>TRUE (Active)</strong>, it automatically activates for <strong>all assigned owner and co-owner departments</strong>. Only activated strategies can be selected for Monthly Strategy Plans.
    </div>
</div>

<!-- Executive Metric Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm bg-white h-100">
            <div class="card-body d-flex align-items-center py-3">
                <div class="p-3 bg-light text-primary rounded-circle me-3 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="fas fa-list-check fa-lg"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Total Strategic Plans</div>
                    <div class="fs-4 fw-bold text-dark" id="statTotalCount">{{ $totalCount }}</div>
                    <small class="text-muted" style="font-size: 0.7rem;">Master annual goals for {{ $selectedYear }}</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm bg-white border-start border-4 border-success h-100">
            <div class="card-body d-flex align-items-center py-3">
                <div class="p-3 bg-success-subtle text-success rounded-circle me-3 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="fas fa-check-circle fa-lg"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Active This Month</div>
                    <div class="fs-4 fw-bold text-success d-flex align-items-center gap-1">
                        <span id="statActiveCount">{{ $activeCount }}</span>
                        <span class="badge bg-success text-white fs-7 align-middle">TRUE</span>
                    </div>
                    <small class="text-muted" style="font-size: 0.7rem;">Activated by General Manager</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm bg-white border-start border-4 border-secondary h-100">
            <div class="card-body d-flex align-items-center py-3">
                <div class="p-3 bg-secondary-subtle text-secondary rounded-circle me-3 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="fas fa-times-circle fa-lg"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Inactive This Month</div>
                    <div class="fs-4 fw-bold text-secondary d-flex align-items-center gap-1">
                        <span id="statInactiveCount">{{ $inactiveCount }}</span>
                        <span class="badge bg-secondary text-white fs-7 align-middle">FALSE</span>
                    </div>
                    <small class="text-muted" style="font-size: 0.7rem;">Default inactive status</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm bg-white border-start border-4 border-warning h-100">
            <div class="card-body d-flex align-items-center py-3">
                <div class="p-3 bg-warning-subtle text-warning rounded-circle me-3 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="fas fa-sitemap fa-lg"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Departments</div>
                    <div class="fs-4 fw-bold text-dark">{{ $departments->count() }}</div>
                    <small class="text-muted" style="font-size: 0.7rem;">Multi-dept alignment enabled</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters Bar with Fast Auto-Submit & Search -->
<div class="filter-bar mb-4 bg-white p-3 rounded-3 shadow-sm border">
    <form method="GET" action="{{ route('strategy.activation') }}" class="row g-2 align-items-end" id="activationFilterForm">
        <div class="col-6 col-md-2">
            <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Planning Year</label>
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach(config('kaldis.planning_years', ['2019 E.C.']) as $y)
                    <option value="{{ $y }}" {{ $selectedYear === $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-6 col-md-2">
            <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Planning Month</label>
            <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach(config('kaldis.ethiopian_months', ['Meskerem', 'Tikimt', 'Hidar', 'Tahsas', 'Tir', 'Yakatit', 'Magabit', 'Miyazya', 'Ginbot', 'Sene', 'Hamle', 'Nehase', 'Pagume']) as $m)
                    <option value="{{ $m }}" {{ $selectedMonth === $m ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-6 col-md-2">
            <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Status Filter</label>
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="ALL" {{ $selectedStatus === 'ALL' ? 'selected' : '' }}>All Statuses</option>
                <option value="ACTIVE" {{ in_array($selectedStatus, ['ACTIVE', 'TRUE']) ? 'selected' : '' }}>TRUE (Active Only)</option>
                <option value="INACTIVE" {{ in_array($selectedStatus, ['INACTIVE', 'FALSE']) ? 'selected' : '' }}>FALSE (Inactive Only)</option>
            </select>
        </div>

        <div class="col-6 col-md-2">
            <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Owner Dept</label>
            <select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="0" {{ empty($selectedDept) ? 'selected' : '' }}>All Departments</option>
                @foreach($departments as $d)
                    <option value="{{ $d->id }}" {{ (int)$selectedDept === (int)$d->id ? 'selected' : '' }}>
                        {{ $d->department_code }} - {{ $d->department_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-8 col-md-3">
            <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Search Strategy</label>
            <div class="input-group input-group-sm">
                <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Search code, title or dept...">
                @if(!empty($search))
                    <a href="{{ route('strategy.activation', ['year' => $selectedYear, 'month' => $selectedMonth, 'status' => $selectedStatus, 'department_id' => $selectedDept, 'per_page' => $perPage]) }}" class="btn btn-outline-secondary" title="Clear Search">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
                <button type="submit" class="btn btn-kaldis">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>

        <div class="col-4 col-md-1">
            <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Per Page</label>
            <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="10" {{ (int)$perPage === 10 ? 'selected' : '' }}>10</option>
                <option value="15" {{ (int)$perPage === 15 ? 'selected' : '' }}>15</option>
                <option value="25" {{ (int)$perPage === 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ (int)$perPage === 50 ? 'selected' : '' }}>50</option>
            </select>
        </div>
    </form>
</div>

<!-- Strategy Master Activation Card -->
<div class="card shadow-sm border-0 mb-4 bg-white rounded-3">
    <!-- Card Header with Bulk Action Buttons -->
    <div class="card-header kaldis-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-3 px-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="fs-6 fw-bold text-white d-flex align-items-center">
                <i class="fas fa-list-check me-2 text-warning"></i> Strategic Plans for {{ $selectedMonth }} {{ $selectedYear }}
            </span>
            <span class="badge bg-warning text-dark font-monospace">
                Showing {{ $goals->firstItem() ?? 0 }}-{{ $goals->lastItem() ?? 0 }} of {{ $goals->total() }}
            </span>
            <span id="selectedCountBadge" class="badge bg-white text-dark d-none">
                0 selected
            </span>
        </div>

        <!-- Prominent Bulk Action Buttons -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="button" class="btn btn-sm btn-success px-3 fw-bold shadow-sm d-inline-flex align-items-center" onclick="bulkSetActivation(true)" title="Turn all selected plans to TRUE (Active)">
                <i class="fas fa-check-circle me-1.5"></i> Bulk Active
            </button>
            <button type="button" class="btn btn-sm btn-danger px-3 fw-bold shadow-sm d-inline-flex align-items-center" onclick="bulkSetActivation(false)" title="Turn all selected plans to FALSE (Inactive)">
                <i class="fas fa-times-circle me-1.5"></i> Bulk Inactive
            </button>

            <!-- Batch Dropdown for Extra Bulk Actions -->
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-light dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bolt me-1"></i> More Actions
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li>
                        <button type="button" class="dropdown-item text-success fw-semibold" onclick="bulkSetActivationPage(true)">
                            <i class="fas fa-check-double me-2"></i> Activate All on This Page ({{ $goals->count() }})
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item text-danger fw-semibold" onclick="bulkSetActivationPage(false)">
                            <i class="fas fa-times me-2"></i> Deactivate All on This Page
                        </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <button type="button" class="dropdown-item text-success fw-bold" onclick="bulkSetActivationAll(true)">
                            <i class="fas fa-globe me-2"></i> Activate ALL Strategic Plans (Company-Wide)
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item text-secondary" onclick="bulkSetActivationAll(false)">
                            <i class="fas fa-ban me-2"></i> Deactivate ALL Strategic Plans (Company-Wide)
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Quick Selection Helper Sub-Bar -->
    <div class="bg-light px-3 py-2 border-bottom d-flex justify-content-between align-items-center text-muted small flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
                <label class="form-check-label fw-semibold text-dark user-select-none" for="selectAllCheckbox">
                    Select All on Page
                </label>
            </div>
            <button type="button" class="btn btn-link btn-xs text-muted text-decoration-none p-0" onclick="selectAllAcrossPages()" title="Select all strategic plans">
                <i class="fas fa-check-double me-1"></i> Select All Across Entire List
            </button>
            <button type="button" class="btn btn-link btn-xs text-secondary text-decoration-none p-0 d-none" id="clearSelectionBtn" onclick="clearSelection()">
                <i class="fas fa-undo me-1"></i> Deselect All
            </button>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted" style="font-size: 0.78rem;">
                Page <strong>{{ $goals->currentPage() }}</strong> of <strong>{{ $goals->lastPage() }}</strong>
            </span>
            @if($goals->hasMorePages())
                <a href="{{ $goals->nextPageUrl() }}" class="btn btn-xs btn-outline-warning text-dark py-1 px-2.5 rounded-pill shadow-xs d-inline-flex align-items-center gap-1" title="Go to next page">
                    <span>Next ..</span> <i class="fas fa-arrow-right"></i>
                </a>
            @endif
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle" id="activationTable">
            <thead class="table-light text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                <tr>
                    <th style="width: 45px;" class="text-center no-print">
                        <span class="visually-hidden">Select</span>
                    </th>
                    <th style="width: 90px;">Goal ID</th>
                    <th>Annual Strategic Goal &amp; Definition of Done</th>
                    <th style="width: 200px;">Owner Departments</th>
                    <th style="width: 140px;">Annual Target</th>
                    <th style="width: 95px;">Priority</th>
                    <th style="width: 150px;" class="text-center">Status ({{ $selectedMonth }})</th>
                    <th style="width: 140px;" class="text-center no-print">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($goals as $goal)
                    @php
                        $isActive = in_array($goal->id, $activeGoalIds);
                        $depts = $goal->departments;
                    @endphp
                    <tr id="row-{{ $goal->id }}" class="{{ $isActive ? 'table-success-subtle' : '' }}">
                        <!-- Row Checkbox -->
                        <td class="text-center no-print">
                            <input type="checkbox" class="form-check-input plan-checkbox" value="{{ $goal->id }}" id="cb-{{ $goal->id }}" onchange="updateSelectedCount()">
                        </td>

                        <!-- Goal ID -->
                        <td>
                            <span class="badge bg-dark font-monospace fs-7 px-2 py-1">{{ $goal->goal_code }}</span>
                        </td>

                        <!-- Title & DoD -->
                        <td>
                            <div class="fw-bold text-dark mb-1">{{ $goal->goal_title }}</div>
                            @if(!empty($goal->definition_of_done))
                                <div class="small text-muted">
                                    <span class="fw-semibold text-secondary">DoD:</span> {{ $goal->definition_of_done }}
                                </div>
                            @elseif(!empty($goal->target_description))
                                <div class="small text-muted">
                                    <span class="fw-semibold text-secondary">Target:</span> {{ $goal->target_description }}
                                </div>
                            @endif
                        </td>

                        <!-- Owner Departments -->
                        <td>
                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                @forelse($depts as $dept)
                                    @if(!empty($dept->pivot->is_primary))
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-1.5 py-0.5" title="Lead Owner: {{ $dept->department_name }}">
                                            <i class="fas fa-star text-warning me-0.5" style="font-size: 0.65rem;"></i>{{ $dept->department_code }}
                                        </span>
                                    @else
                                        <span class="badge bg-light text-secondary border px-1.5 py-0.5" title="Co-Owner: {{ $dept->department_name }}">
                                            {{ $dept->department_code }}
                                        </span>
                                    @endif
                                @empty
                                    <span class="badge bg-light text-muted border">Company-Wide</span>
                                @endforelse
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">
                                {{ $depts->count() > 1 ? $depts->count() . ' departments' : ($depts->count() === 1 ? '1 department' : 'Enterprise') }}
                            </small>
                        </td>

                        <!-- Annual Target -->
                        <td>
                            <small class="fw-semibold text-dark">{{ $goal->annual_target ?: '—' }}</small>
                        </td>

                        <!-- Priority -->
                        <td>
                            @php
                                $prio = strtoupper($goal->priority ?? 'MEDIUM');
                                $prioColor = match($prio) {
                                    'HIGH' => 'danger',
                                    'MEDIUM' => 'warning',
                                    'LOW' => 'info',
                                    default => 'secondary'
                                };
                            @endphp
                            <span class="badge bg-{{ $prioColor }}-subtle text-{{ $prioColor }} border border-{{ $prioColor }}-subtle fs-8 px-2 py-0.5">
                                {{ $prio }}
                            </span>
                        </td>

                        <!-- Status Badge -->
                        <td class="text-center status-col">
                            <span id="badge-{{ $goal->id }}" class="badge {{ $isActive ? 'bg-success text-white' : 'bg-secondary-subtle text-secondary border' }} px-2 py-1 fs-7 shadow-xs">
                                <i class="fas {{ $isActive ? 'fa-check-circle' : 'fa-times-circle' }} me-1"></i>
                                <span>{{ $isActive ? 'TRUE (Active)' : 'FALSE (Inactive)' }}</span>
                            </span>
                        </td>

                        <!-- 1-Click Action Toggle Button -->
                        <td class="text-center no-print action-col">
                            <button type="button" 
                                    class="btn btn-sm {{ $isActive ? 'btn-outline-danger' : 'btn-kaldis' }} toggle-btn px-2.5 py-1 shadow-xs"
                                    id="btn-{{ $goal->id }}"
                                    data-active="{{ $isActive ? 1 : 0 }}"
                                    onclick="togglePlan({{ $goal->id }})">
                                <i class="fas {{ $isActive ? 'fa-times' : 'fa-check' }} me-1"></i>
                                <span>{{ $isActive ? 'Set FALSE' : 'Turn TRUE' }}</span>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <div class="p-3 bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                                <i class="fas fa-folder-open fa-2x text-muted opacity-50"></i>
                            </div>
                            <div class="fw-bold text-dark mb-1">No Strategic Plans Found</div>
                            <div class="small text-muted mb-3">No plans match the selected year, month, or filters.</div>
                            <a href="{{ route('strategy.activation', ['year' => $selectedYear, 'month' => $selectedMonth]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-undo me-1"></i> Reset Filters
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Card Footer with Circle Pagination Controls and Next .. Button -->
    <div class="card-footer bg-white border-top py-3 px-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
        <div class="text-muted small">
            Showing <strong>{{ $goals->firstItem() ?? 0 }}</strong> to <strong>{{ $goals->lastItem() ?? 0 }}</strong> of <strong>{{ $goals->total() }}</strong> strategic plans
            @if($goals->lastPage() > 1)
                &bull; Page <strong>{{ $goals->currentPage() }}</strong> of <strong>{{ $goals->lastPage() }}</strong>
            @endif
        </div>

        @if($goals->hasPages())
            <nav aria-label="Strategy Activation Page Navigation">
                <ul class="pagination pagination-circle mb-0 justify-content-center flex-wrap align-items-center gap-1">
                    {{-- Previous Page Link --}}
                    @if ($goals->onFirstPage())
                        <li class="page-item disabled" aria-disabled="true">
                            <span class="page-link circle-nav-btn" aria-hidden="true" title="Previous Page">
                                <i class="fas fa-chevron-left"></i>
                            </span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link circle-nav-btn" href="{{ $goals->previousPageUrl() }}" rel="prev" title="Previous Page">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    @endif

                    {{-- Pagination Elements with Circle Styling --}}
                    @foreach ($goals->getUrlRange(1, $goals->lastPage()) as $page => $url)
                        @if ($page == $goals->currentPage())
                            <li class="page-item active" aria-current="page">
                                <span class="page-link circle-page-btn">{{ $page }}</span>
                            </li>
                        @elseif ($page == 1 || $page == $goals->lastPage() || abs($page - $goals->currentPage()) <= 2)
                            <li class="page-item">
                                <a class="page-link circle-page-btn" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @elseif (abs($page - $goals->currentPage()) == 3)
                            <li class="page-item disabled"><span class="page-link circle-page-btn border-0">..</span></li>
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($goals->hasMorePages())
                        <li class="page-item">
                            <a class="page-link circle-nav-btn" href="{{ $goals->nextPageUrl() }}" rel="next" title="Next Page">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                        <li class="page-item ms-2">
                            <a class="btn btn-kaldis btn-sm rounded-pill px-3 shadow-xs d-inline-flex align-items-center gap-1.5" href="{{ $goals->nextPageUrl() }}">
                                <span>Next ..</span> <i class="fas fa-arrow-right"></i>
                            </a>
                        </li>
                    @else
                        <li class="page-item disabled" aria-disabled="true">
                            <span class="page-link circle-nav-btn" aria-hidden="true" title="Next Page">
                                <i class="fas fa-chevron-right"></i>
                            </span>
                        </li>
                    @endif
                </ul>
            </nav>
        @endif
    </div>
</div>

<!-- Floating Quick Action Bar for Instant Bulk Management -->
<div id="floatingBulkBar" class="floating-bulk-bar shadow-lg border rounded-pill bg-white px-4 py-2.5 d-none align-items-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-warning text-dark rounded-pill px-2.5 py-1 fs-7 fw-bold" id="floatingSelectedCount">0</span>
        <span class="fw-semibold text-dark small">Plans Selected</span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-success rounded-pill px-3 fw-bold" onclick="bulkSetActivation(true)">
            <i class="fas fa-check-circle me-1"></i> Turn Active (TRUE)
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold" onclick="bulkSetActivation(false)">
            <i class="fas fa-times-circle me-1"></i> Turn Inactive (FALSE)
        </button>
        <button type="button" class="btn btn-sm btn-link text-muted p-1 text-decoration-none" onclick="clearSelection()" title="Cancel Selection">
            <i class="fas fa-xmark fs-5"></i>
        </button>
    </div>
</div>

<style>
/* ══════════════════════════════════════════════════════════
   CIRCLE PAGINATION & MODERN GM ACTIVATION STYLING
   ══════════════════════════════════════════════════════════ */
.pagination-circle {
    display: inline-flex;
    list-style: none;
    padding: 0;
    margin: 0;
}

.pagination-circle .page-item {
    display: inline-flex;
    align-items: center;
}

.pagination-circle .circle-page-btn,
.pagination-circle .circle-nav-btn {
    width: 38px;
    height: 38px;
    border-radius: 50% !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: 0 3px;
    font-weight: 700;
    font-size: 0.85rem;
    border: 1.5px solid var(--kaldis-border, #E2E8F0);
    color: var(--kaldis-text, #0F172A);
    background: #FFFFFF;
    transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    text-decoration: none;
}

.pagination-circle .circle-page-btn:hover,
.pagination-circle .circle-nav-btn:hover {
    background: var(--kaldis-gold-soft, #FFFBEB);
    border-color: var(--kaldis-gold, #D97706);
    color: var(--kaldis-gold, #D97706);
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(217, 119, 6, 0.18);
}

.pagination-circle .page-item.active .circle-page-btn {
    background: linear-gradient(135deg, #FBBF24 0%, #D97706 60%, #B45309 100%) !important;
    border-color: #B45309 !important;
    color: #FFFFFF !important;
    box-shadow: 0 4px 14px rgba(217, 119, 6, 0.38) !important;
    transform: scale(1.08);
}

.pagination-circle .page-item.disabled .circle-nav-btn,
.pagination-circle .page-item.disabled .circle-page-btn {
    opacity: 0.4;
    cursor: not-allowed;
    background: #F8FAFC;
    border-color: #E2E8F0;
    color: #94A3B8;
    transform: none;
    box-shadow: none;
}

/* Floating Bulk Action Bar */
.floating-bulk-bar {
    position: fixed;
    bottom: 28px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 1060;
    min-width: 360px;
    border-color: var(--kaldis-gold, #D97706) !important;
    animation: slideUpFloating 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes slideUpFloating {
    from { opacity: 0; transform: translate(-50%, 20px); }
    to { opacity: 1; transform: translate(-50%, 0); }
}

[data-bs-theme=dark] .pagination-circle .circle-page-btn,
[data-bs-theme=dark] .pagination-circle .circle-nav-btn {
    background: #1E293B;
    border-color: #334155;
    color: #F1F5F9;
}

[data-bs-theme=dark] .floating-bulk-bar {
    background: #0F172A !important;
    border-color: rgba(251, 191, 36, 0.4) !important;
}
</style>
@endsection

@section('scripts')
<script>
const PLANNING_YEAR = '{{ $selectedYear }}';
const PLANNING_MONTH = '{{ $selectedMonth }}';
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
let selectedAllAcross = false;

/**
 * Handle 1-Click Toggle for a single strategy
 */
function togglePlan(goalId) {
    const btn = document.getElementById('btn-' + goalId);
    if (!btn) return;

    const currentActive = parseInt(btn.getAttribute('data-active')) === 1;
    const newActive = !currentActive;
    btn.disabled = true;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    fetch("{{ route('strategy.activation.toggle') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            annual_goal_id: goalId,
            year: PLANNING_YEAR,
            month: PLANNING_MONTH,
            is_active: newActive ? 1 : 0
        })
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        if (data.success) {
            updateRowUI(goalId, newActive);
            updateCounters(data.active_count, data.inactive_count, data.total_count);
            if (window.Toast) {
                window.Toast.success(data.message);
            }
        } else {
            btn.innerHTML = originalHtml;
            if (window.Toast) {
                window.Toast.error(data.message || 'Error updating strategy status');
            } else {
                alert(data.message || 'Error updating strategy status');
            }
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        console.error(err);
        if (window.Toast) {
            window.Toast.error('Network or server error updating strategy status');
        }
    });
}

/**
 * Update Row UI elements to reflect new status
 */
function updateRowUI(goalId, isActive) {
    const row = document.getElementById('row-' + goalId);
    const badge = document.getElementById('badge-' + goalId);
    const btn = document.getElementById('btn-' + goalId);

    if (row) {
        if (isActive) {
            row.classList.add('table-success-subtle');
        } else {
            row.classList.remove('table-success-subtle');
        }
    }

    if (badge) {
        if (isActive) {
            badge.className = 'badge bg-success text-white px-2 py-1 fs-7 shadow-xs';
            badge.innerHTML = '<i class="fas fa-check-circle me-1"></i><span>TRUE (Active)</span>';
        } else {
            badge.className = 'badge bg-secondary-subtle text-secondary border px-2 py-1 fs-7 shadow-xs';
            badge.innerHTML = '<i class="fas fa-times-circle me-1"></i><span>FALSE (Inactive)</span>';
        }
    }

    if (btn) {
        btn.setAttribute('data-active', isActive ? 1 : 0);
        if (isActive) {
            btn.className = 'btn btn-sm btn-outline-danger toggle-btn px-2.5 py-1 shadow-xs';
            btn.innerHTML = '<i class="fas fa-times me-1"></i><span>Set FALSE</span>';
        } else {
            btn.className = 'btn btn-sm btn-kaldis toggle-btn px-2.5 py-1 shadow-xs';
            btn.innerHTML = '<i class="fas fa-check me-1"></i><span>Turn TRUE</span>';
        }
    }
}

/**
 * Update counters on screen
 */
function updateCounters(activeCount, inactiveCount, totalCount) {
    const activeEl = document.getElementById('statActiveCount');
    const inactiveEl = document.getElementById('statInactiveCount');
    const totalEl = document.getElementById('statTotalCount');

    if (activeEl && activeCount !== undefined) activeEl.textContent = activeCount;
    if (inactiveEl && inactiveCount !== undefined) inactiveEl.textContent = inactiveCount;
    if (totalEl && totalCount !== undefined) totalEl.textContent = totalCount;
}

/**
 * Bulk Activate or Deactivate Selected Plans
 */
function bulkSetActivation(isActive) {
    const selectedCheckboxes = document.querySelectorAll('.plan-checkbox:checked');
    if (selectedCheckboxes.length === 0 && !selectedAllAcross) {
        if (window.Toast) {
            window.Toast.warning('Please select at least one strategy plan using the checkboxes.');
        } else {
            alert('Please select at least one strategy plan using the checkboxes.');
        }
        return;
    }

    const goalIds = Array.from(selectedCheckboxes).map(cb => parseInt(cb.value));
    executeBulkRequest({
        year: PLANNING_YEAR,
        month: PLANNING_MONTH,
        is_active: isActive ? 1 : 0,
        annual_goal_ids: goalIds,
        all: selectedAllAcross ? 1 : 0
    }, isActive, goalIds);
}

/**
 * Bulk Activate/Deactivate All on Current Page
 */
function bulkSetActivationPage(isActive) {
    const checkboxes = document.querySelectorAll('.plan-checkbox');
    if (checkboxes.length === 0) return;
    const goalIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

    executeBulkRequest({
        year: PLANNING_YEAR,
        month: PLANNING_MONTH,
        is_active: isActive ? 1 : 0,
        annual_goal_ids: goalIds
    }, isActive, goalIds);
}

/**
 * Bulk Activate/Deactivate All Plans Company-Wide
 */
function bulkSetActivationAll(isActive) {
    const actionName = isActive ? 'ACTIVATE ALL' : 'DEACTIVATE ALL';
    if (!confirm(`Are you sure you want to ${actionName} strategic plans for ${PLANNING_MONTH} ${PLANNING_YEAR}?`)) {
        return;
    }

    executeBulkRequest({
        year: PLANNING_YEAR,
        month: PLANNING_MONTH,
        is_active: isActive ? 1 : 0,
        all: 1
    }, isActive, null);
}

/**
 * Common Bulk Request Executor
 */
function executeBulkRequest(payload, isActive, goalIds) {
    const csrfToken = CSRF_TOKEN;

    fetch("{{ route('strategy.activation.bulk') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (data.goal_ids && Array.isArray(data.goal_ids)) {
                data.goal_ids.forEach(id => updateRowUI(id, isActive));
            } else if (goalIds) {
                goalIds.forEach(id => updateRowUI(id, isActive));
            } else {
                // All company-wide updated; update all rows visible on current page
                document.querySelectorAll('.plan-checkbox').forEach(cb => {
                    updateRowUI(parseInt(cb.value), isActive);
                });
            }

            updateCounters(data.active_count, data.inactive_count, data.total_count);
            clearSelection();

            if (window.Toast) {
                window.Toast.success(data.message || 'Bulk status update completed!');
            }
        } else {
            if (window.Toast) {
                window.Toast.error(data.message || 'Error executing batch action.');
            }
        }
    })
    .catch(err => {
        console.error(err);
        if (window.Toast) {
            window.Toast.error('Network error executing bulk action.');
        }
    });
}

/**
 * Select All Checkboxes on Current Page
 */
function toggleSelectAll(masterCb) {
    selectedAllAcross = false;
    const checkboxes = document.querySelectorAll('.plan-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = masterCb.checked;
    });
    updateSelectedCount();
}

/**
 * Select All Across Pages
 */
function selectAllAcrossPages() {
    selectedAllAcross = true;
    const checkboxes = document.querySelectorAll('.plan-checkbox');
    checkboxes.forEach(cb => { cb.checked = true; });
    const master = document.getElementById('selectAllCheckbox');
    if (master) master.checked = true;

    const badge = document.getElementById('selectedCountBadge');
    if (badge) {
        badge.textContent = 'All {{ $totalCount }} plans selected';
        badge.classList.remove('d-none');
    }

    const floatingBar = document.getElementById('floatingBulkBar');
    const floatingCount = document.getElementById('floatingSelectedCount');
    if (floatingBar && floatingCount) {
        floatingCount.textContent = '{{ $totalCount }} (All)';
        floatingBar.classList.remove('d-none');
        floatingBar.classList.add('d-flex');
    }

    const clearBtn = document.getElementById('clearSelectionBtn');
    if (clearBtn) clearBtn.classList.remove('d-none');
}

/**
 * Clear All Selections
 */
function clearSelection() {
    selectedAllAcross = false;
    const checkboxes = document.querySelectorAll('.plan-checkbox');
    checkboxes.forEach(cb => { cb.checked = false; });
    const master = document.getElementById('selectAllCheckbox');
    if (master) master.checked = false;
    updateSelectedCount();
}

/**
 * Update Selected Count Badge & Floating Bar
 */
function updateSelectedCount() {
    if (selectedAllAcross) {
        return;
    }
    const selected = document.querySelectorAll('.plan-checkbox:checked');
    const count = selected.length;
    const badge = document.getElementById('selectedCountBadge');
    const floatingBar = document.getElementById('floatingBulkBar');
    const floatingCount = document.getElementById('floatingSelectedCount');
    const clearBtn = document.getElementById('clearSelectionBtn');
    const master = document.getElementById('selectAllCheckbox');

    if (count > 0) {
        if (badge) {
            badge.textContent = `${count} selected`;
            badge.classList.remove('d-none');
        }
        if (floatingBar && floatingCount) {
            floatingCount.textContent = count;
            floatingBar.classList.remove('d-none');
            floatingBar.classList.add('d-flex');
        }
        if (clearBtn) clearBtn.classList.remove('d-none');
    } else {
        if (badge) badge.classList.add('d-none');
        if (floatingBar) {
            floatingBar.classList.add('d-none');
            floatingBar.classList.remove('d-flex');
        }
        if (clearBtn) clearBtn.classList.add('d-none');
        if (master) master.checked = false;
    }
}
</script>
@endsection

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | {{ config('kaldis.app_name') }}</title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 & FontAwesome 6 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <!-- Simple Light UI Stylesheet -->
    <style>
        :root {
            --kaldis-primary: #5B1425;
            --kaldis-gold: #D97706;
            --kaldis-gold-light: #FEF3C7;
            --kaldis-bg: #F8FAFC;
            --kaldis-card-bg: #FFFFFF;
            --kaldis-text: #0F172A;
            --kaldis-muted: #64748B;
            --kaldis-border: #E2E8F0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--kaldis-bg);
            color: var(--kaldis-text);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Navbar */
        .navbar-kaldis {
            background-color: #FFFFFF;
            border-bottom: 1px solid var(--kaldis-border);
            padding: 0.65rem 1.5rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--kaldis-primary) !important;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }

        .navbar-brand img {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--kaldis-gold);
        }

        /* Layout Container */
        .app-wrapper {
            display: flex;
            flex: 1;
        }

        /* Simple Sidebar */
        .sidebar {
            width: 250px;
            background-color: #FFFFFF;
            border-right: 1px solid var(--kaldis-border);
            padding: 1.25rem 0.75rem;
            flex-shrink: 0;
        }

        .sidebar-heading {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--kaldis-muted);
            padding: 0.5rem 0.85rem;
            margin-top: 0.5rem;
        }

        .nav-link-kaldis {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.55rem 0.85rem;
            border-radius: 8px;
            color: #334155;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
            margin-bottom: 0.15rem;
        }

        .nav-link-kaldis i {
            width: 18px;
            text-align: center;
            color: #94A3B8;
            font-size: 0.95rem;
        }

        .nav-link-kaldis:hover {
            background-color: #F1F5F9;
            color: var(--kaldis-primary);
        }

        .nav-link-kaldis:hover i {
            color: var(--kaldis-gold);
        }

        .nav-link-kaldis.active {
            background-color: var(--kaldis-gold-light);
            color: #92400E;
            font-weight: 600;
        }

        .nav-link-kaldis.active i {
            color: var(--kaldis-gold);
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            padding: 1.75rem 2rem;
            overflow-y: auto;
        }

        /* Cards */
        .card {
            background: #FFFFFF;
            border: 1px solid var(--kaldis-border);
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background-color: #FFFFFF;
            border-bottom: 1px solid var(--kaldis-border);
            padding: 0.9rem 1.25rem;
            font-weight: 600;
            font-size: 0.95rem;
            color: #0F172A;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Buttons */
        .btn-kaldis {
            background-color: var(--kaldis-gold);
            color: #FFFFFF;
            border: none;
            font-weight: 500;
            border-radius: 6px;
        }

        .btn-kaldis:hover {
            background-color: #B45309;
            color: #FFFFFF;
        }

        .btn-kaldis-primary {
            background-color: var(--kaldis-primary);
            color: #FFFFFF;
            border: none;
            font-weight: 500;
            border-radius: 6px;
        }

        .btn-kaldis-primary:hover {
            background-color: #430D1B;
            color: #FFFFFF;
        }

        /* Badges */
        .badge-done {
            background-color: #DEF7EC;
            color: #03543F;
            font-weight: 600;
            border: 1px solid #BCF0DA;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
        }

        .badge-not-done {
            background-color: #FDE8E8;
            color: #9B1C1C;
            font-weight: 600;
            border: 1px solid #FBD5D5;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
        }

        .badge-pending {
            background-color: #FEF08A;
            color: #854D0E;
            font-weight: 600;
            border: 1px solid #FDE047;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
        }

        .badge-strategy {
            background-color: #E0E7FF;
            color: #3730A3;
            font-weight: 600;
            border: 1px solid #C7D2FE;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
        }

        .badge-operational {
            background-color: #E0F2FE;
            color: #0369A1;
            font-weight: 600;
            border: 1px solid #BAE6FD;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
        }

        /* Filter Box */
        .filter-bar {
            background-color: #FFFFFF;
            border: 1px solid var(--kaldis-border);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        /* Tables */
        .table {
            color: #334155;
            vertical-align: middle;
        }

        .table th {
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748B;
            background-color: #F8FAFC;
            border-bottom: 1px solid var(--kaldis-border);
            padding: 0.75rem 1rem;
        }

        .table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #F1F5F9;
            font-size: 0.875rem;
        }

        /* Empty state */
        .empty-state {
            padding: 3rem 1.5rem;
            text-align: center;
            color: #94A3B8;
        }

        .empty-state i {
            font-size: 2.75rem;
            margin-bottom: 1rem;
            color: #CBD5E1;
        }

        .empty-state h6 {
            color: #475569;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
    </style>
    @yield('styles')
</head>
<body>

    <!-- Simple Light Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-kaldis">
        <div class="container-fluid px-0">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <img src="{{ asset('images/logo.png') }}" alt="Kaldis Coffee Logo" onerror="this.src='{{ asset('assets/images/logo.png') }}'">
                <span>Kaldis Coffee</span>
                <span class="badge bg-light text-secondary border fw-normal small ms-1" style="font-size: 0.75rem;">Planning & Performance</span>
            </a>

            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2.5 py-1.5 rounded-pill small">
                    <i class="far fa-calendar-alt me-1"></i> {{ format_et_calendar() }}
                </span>

                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2 border-0 bg-light" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle text-secondary"></i>
                        <span class="fw-semibold text-dark">{{ auth()->user()->full_name ?? auth()->user()->username }}</span>
                        <span class="badge bg-secondary-subtle text-secondary small">{{ auth()->user()->role->role_name ?? '' }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border">
                        <li>
                            <div class="dropdown-item-text small text-muted">
                                <div>Signed in as:</div>
                                <strong>{{ auth()->user()->username }}</strong>
                                @if(auth()->user()->department)
                                    <div>Dept: {{ auth()->user()->department->department_name }}</div>
                                @endif
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger small">
                                    <i class="fas fa-sign-out-alt me-2"></i> Log Out
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Layout Body -->
    <div class="app-wrapper">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-heading">Dashboards</div>
            @if(auth()->user()->isGM() || auth()->user()->isSuperAdmin())
                <a href="{{ route('dashboard.gm') }}" class="nav-link-kaldis {{ request()->routeIs('dashboard.gm') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i> GM Dashboard
                </a>
            @endif
            @if(auth()->user()->department_id || auth()->user()->canAccessAllDepartments())
                <a href="{{ route('dashboard.department') }}" class="nav-link-kaldis {{ request()->routeIs('dashboard.department') ? 'active' : '' }}">
                    <i class="fas fa-briefcase"></i> Department Workspace
                </a>
            @endif

            <div class="sidebar-heading">Strategy & Planning</div>
            <a href="{{ route('strategy.annual') }}" class="nav-link-kaldis {{ request()->routeIs('strategy.annual') ? 'active' : '' }}">
                <i class="fas fa-bullseye"></i> Annual Strategy Plan
            </a>
            @if(auth()->user()->isGM() || auth()->user()->isSuperAdmin())
                <a href="{{ route('strategy.activation') }}" class="nav-link-kaldis {{ request()->routeIs('strategy.activation') ? 'active' : '' }}">
                    <i class="fas fa-toggle-on"></i> GM Monthly Activation
                </a>
            @endif
            <a href="{{ route('monthly.plans') }}" class="nav-link-kaldis {{ request()->routeIs('monthly.plans*') ? 'active' : '' }}">
                <i class="fas fa-calendar-check"></i> Monthly Plans
            </a>
            <a href="{{ route('weekly.plans') }}" class="nav-link-kaldis {{ request()->routeIs('weekly.plans*') ? 'active' : '' }}">
                <i class="fas fa-tasks"></i> Weekly Plans
            </a>

            <div class="sidebar-heading">Execution & Reports</div>
            <a href="{{ route('weekly.performance') }}" class="nav-link-kaldis {{ request()->routeIs('weekly.performance*') ? 'active' : '' }}">
                <i class="fas fa-clipboard-check"></i> Weekly Performance
            </a>
            <a href="{{ route('performance.weekly') }}" class="nav-link-kaldis {{ request()->routeIs('performance.weekly') ? 'active' : '' }}">
                <i class="fas fa-table"></i> Weekly Scorecard
            </a>
            <a href="{{ route('performance.monthly') }}" class="nav-link-kaldis {{ request()->routeIs('performance.monthly') ? 'active' : '' }}">
                <i class="fas fa-calendar-alt"></i> Monthly Rollup
            </a>
            <a href="{{ route('performance.not_done') }}" class="nav-link-kaldis {{ request()->routeIs('performance.not_done') ? 'active' : '' }}">
                <i class="fas fa-exclamation-triangle"></i> Not Done Analysis
            </a>
            <a href="{{ route('performance.achievements') }}" class="nav-link-kaldis {{ request()->routeIs('performance.achievements') ? 'active' : '' }}">
                <i class="fas fa-trophy"></i> Achievements Feed
            </a>
            <a href="{{ route('performance.challenges') }}" class="nav-link-kaldis {{ request()->routeIs('performance.challenges') ? 'active' : '' }}">
                <i class="fas fa-flag"></i> Challenges Tracker
            </a>

            @if(auth()->user()->isSuperAdmin())
                <div class="sidebar-heading">Administration</div>
                <a href="{{ route('admin.dashboard') }}" class="nav-link-kaldis {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-cogs"></i> Admin Overview
                </a>
                <a href="{{ route('admin.users') }}" class="nav-link-kaldis {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i> Users & Roles
                </a>
                <a href="{{ route('admin.departments') }}" class="nav-link-kaldis {{ request()->routeIs('admin.departments*') ? 'active' : '' }}">
                    <i class="fas fa-building"></i> Departments
                </a>
                <a href="{{ route('admin.settings') }}" class="nav-link-kaldis {{ request()->routeIs('admin.settings*') ? 'active' : '' }}">
                    <i class="fas fa-sliders-h"></i> System Settings
                </a>
                <a href="{{ route('admin.audit') }}" class="nav-link-kaldis {{ request()->routeIs('admin.audit*') ? 'active' : '' }}">
                    <i class="fas fa-history"></i> Audit Trail
                </a>
            @endif
        </aside>

        <!-- Main Content Body -->
        <main class="main-content">
            <!-- Flash Notifications -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i> <strong>Please check the following errors:</strong>
                    <ul class="mb-0 mt-1 small">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Bootstrap 5 Bundle & Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    
    <!-- Instant Live Search Helper -->
    <script>
        function setupLiveSearch(inputId, tableId) {
            const input = document.getElementById(inputId);
            const table = document.getElementById(tableId);
            if (!input || !table) return;

            input.addEventListener('keyup', function () {
                const filter = input.value.toLowerCase();
                const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

                for (let i = 0; i < rows.length; i++) {
                    const rowText = rows[i].textContent || rows[i].innerText;
                    if (rowText.toLowerCase().indexOf(filter) > -1) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            });
        }
    </script>
    @yield('scripts')
</body>
</html>

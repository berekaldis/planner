<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | {{ config('kaldis.app_name', 'Kaldis Planning Portal') }}</title>
    
    <!-- Inline Theme & Sidebar State Initializer (Eliminates Flash) -->
    <script>
        window.APP_BASE_URL = '{{ url('') }}';
        (function() {
            const savedTheme = localStorage.getItem('kaldis_theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
            if (localStorage.getItem('sidebar_collapsed') === 'true' && window.innerWidth >= 992) {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        })();
    </script>

    <!-- Google Fonts: Outfit & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 & FontAwesome 6 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">

    <!-- TomSelect 2.4 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">

    <!-- Kaldis Design System Core Stylesheet with Cache Busting -->
    <link href="{{ asset('assets/css/style.css') }}?v={{ time() }}" rel="stylesheet">

    <style>
        /* ══════════════════════════════════════════════════════════
           EXACT MATCH FOR USER SCREENSHOT (media_1791907933701.png)
           SHADCN / RADIX STYLE SEARCHABLE SELECT POP-OVER
           ══════════════════════════════════════════════════════════ */
        .ts-wrapper.form-control, .ts-wrapper.form-select {
            padding: 0;
            border: none;
            box-shadow: none;
            background: transparent;
        }

        .ts-wrapper {
            position: relative !important;
            width: 100% !important;
        }

        .ts-wrapper.dropdown-active {
            z-index: 1070 !important;
        }

        /* Stacking Context & Overflow Elevation */
        .filter-bar {
            position: relative !important;
            z-index: 30;
            overflow: visible !important;
        }

        .filter-bar:has(.dropdown-active),
        .filter-bar:has(.ts-wrapper.focus),
        .filter-bar:focus-within {
            z-index: 1065 !important;
        }

        .card:has(.dropdown-active),
        .card:has(.ts-wrapper.dropdown-active) {
            position: relative !important;
            z-index: 1055 !important;
            overflow: visible !important;
        }

        .card-body:has(.dropdown-active),
        .card-header:has(.dropdown-active) {
            overflow: visible !important;
        }

        .modal-content:has(.dropdown-active),
        .modal-body:has(.dropdown-active) {
            overflow: visible !important;
        }

        .table-responsive:has(.dropdown-active),
        .table-responsive:has(.ts-wrapper.dropdown-active) {
            overflow: visible !important;
        }

        /* Closed / Active Trigger Control */
        .ts-control {
            background-color: #F9FAFB !important;
            border: 1px solid #E5E7EB !important;
            border-radius: 8px !important;
            padding: 0.45rem 2.25rem 0.45rem 0.85rem !important;
            font-size: 0.875rem !important;
            font-weight: 500 !important;
            color: #111827 !important;
            min-height: 38px !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.04) !important;
            position: relative !important;
            cursor: pointer !important;
            display: flex !important;
            align-items: center !important;
            transition: all 0.15s ease !important;
        }

        /* Small variation for filter bars and compact views */
        .ts-wrapper.form-select-sm .ts-control,
        .form-select-sm + .ts-wrapper .ts-control {
            min-height: 31px !important;
            padding: 0.25rem 2rem 0.25rem 0.65rem !important;
            font-size: 0.8125rem !important;
            border-radius: 6px !important;
        }

        /* Chevrons Up/Down Icon (↕) exactly matching screenshot */
        .ts-wrapper .ts-control::after {
            content: '' !important;
            position: absolute !important;
            top: 50% !important;
            right: 11px !important;
            transform: translateY(-50%) !important;
            width: 14px !important;
            height: 14px !important;
            background-repeat: no-repeat !important;
            background-position: center !important;
            background-size: contain !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%236B7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m7 15 5 5 5-5'/%3E%3Cpath d='m7 9 5-5 5 5'/%3E%3C/svg%3E") !important;
            border: none !important;
            pointer-events: none !important;
            transition: none !important;
        }

        /* Focus state for trigger */
        .ts-wrapper.focus .ts-control,
        .ts-wrapper.dropdown-active .ts-control {
            border-color: #D1D5DB !important;
            background-color: #FFFFFF !important;
            box-shadow: 0 0 0 2px rgba(217, 119, 6, 0.12) !important;
        }

        /* Clean item text in trigger for single select - ABSOLUTELY NO BADGE APPEARANCE */
        .ts-wrapper:not(.multi) .ts-control .item,
        .ts-wrapper.single .ts-control .item {
            background: transparent !important;
            background-color: transparent !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
            color: #111827 !important;
            font-weight: 500 !important;
            font-size: 0.875rem !important;
            border-radius: 0 !important;
            display: inline-block !important;
            vertical-align: middle !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            max-width: calc(100% - 24px) !important;
            pointer-events: none !important;
        }

        /* Keep any input inside trigger visually unobtrusive without breaking browser focus */
        .ts-wrapper.has-items .ts-control .items-placeholder {
            display: none !important;
        }
        .ts-wrapper.single.has-items .ts-control > input:not(.items-placeholder) {
            opacity: 0 !important;
            width: 1px !important;
            position: absolute !important;
            pointer-events: none !important;
        }

        .ts-control .items-placeholder {
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            background: transparent !important;
            color: #6B7280 !important;
            font-size: 0.875rem !important;
            font-weight: 500 !important;
            padding: 0 !important;
            margin: 0 !important;
            cursor: pointer !important;
            pointer-events: none !important;
        }

        /* Floating Popover Container - Strictly Anchored Under Input */
        .ts-dropdown,
        .ts-wrapper > .ts-dropdown {
            position: absolute !important;
            top: 100% !important;
            left: 0 !important;
            width: 100% !important;
            min-width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
            border: 1px solid #E5E7EB !important;
            border-radius: 8px !important;
            box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.14), 0 4px 10px -2px rgba(0, 0, 0, 0.08) !important;
            background: #FFFFFF !important;
            padding: 0 !important;
            margin-top: 4px !important;
            overflow: hidden !important;
            z-index: 1080 !important;
            animation: reactPopFade 0.15s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes reactPopFade {
            from { opacity: 0; transform: translateY(-4px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Embedded Search Input Header (Top of Popover) */
        .ts-dropdown .dropdown-input-wrap {
            position: relative !important;
            padding: 8px 12px 8px 34px !important;
            border-bottom: 1px solid #E5E7EB !important;
            background: #FFFFFF !important;
            margin: 0 !important;
        }

        /* Magnifying Glass Icon (🔍) inside Search Header */
        .ts-dropdown .dropdown-input-wrap::before {
            content: '' !important;
            position: absolute !important;
            top: 50% !important;
            left: 11px !important;
            transform: translateY(-50%) !important;
            width: 14px !important;
            height: 14px !important;
            background-repeat: no-repeat !important;
            background-position: center !important;
            background-size: contain !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236B7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'%3E%3C/circle%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'%3E%3C/line%3E%3C/svg%3E") !important;
            pointer-events: none !important;
        }

        /* Search input element */
        .ts-dropdown .dropdown-input,
        .plugin-dropdown_input.focus .ts-dropdown .dropdown-input {
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            background: transparent !important;
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
            font-size: 0.875rem !important;
            font-family: inherit !important;
            color: #111827 !important;
        }
        .ts-dropdown .dropdown-input::placeholder {
            color: #6B7280 !important;
            font-size: 0.875rem !important;
            opacity: 0.85;
        }

        /* Options list container */
        .ts-dropdown .ts-dropdown-content {
            padding: 4px !important;
            max-height: 220px !important;
            overflow-y: auto !important;
        }
        .ts-dropdown .ts-dropdown-content::-webkit-scrollbar {
            width: 5px;
        }
        .ts-dropdown .ts-dropdown-content::-webkit-scrollbar-track {
            background: transparent;
        }
        .ts-dropdown .ts-dropdown-content::-webkit-scrollbar-thumb {
            background: #E2E8F0;
            border-radius: 4px;
        }
        .ts-dropdown .ts-dropdown-content::-webkit-scrollbar-thumb:hover {
            background: #CBD5E1;
        }

        /* Individual Option Items */
        .ts-dropdown .option {
            padding: 7px 12px !important;
            font-size: 0.875rem !important;
            color: #1F2937 !important;
            border-radius: 6px !important;
            margin-bottom: 1px !important;
            cursor: pointer !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            white-space: normal !important;
            word-break: break-word !important;
            line-height: 1.35 !important;
            transition: background-color 0.1s ease !important;
        }
        .ts-dropdown .option:hover,
        .ts-dropdown .option.active {
            background-color: #F3F4F6 !important;
            color: #111827 !important;
        }
        .ts-dropdown .option.selected {
            background-color: #F3F4F6 !important;
            color: #111827 !important;
            font-weight: 500 !important;
        }
        .ts-dropdown .option.selected::after {
            content: '✓';
            color: #059669;
            font-weight: 700;
            font-size: 0.85rem;
            margin-left: 8px !important;
            flex-shrink: 0 !important;
        }

        /* Multi-select Pills / Tags */
        .ts-wrapper.multi .ts-control .item {
            background: #F3F4F6 !important;
            border: 1px solid #E5E7EB !important;
            color: #1F2937 !important;
            border-radius: 6px !important;
            font-weight: 500 !important;
            font-size: 0.8rem !important;
            padding: 2px 8px !important;
            margin: 2px 4px 2px 0 !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 4px !important;
        }
        .ts-wrapper.multi .ts-control .item .remove {
            color: #9CA3AF !important;
            margin-left: 4px !important;
            border-left: none !important;
            padding: 0 2px !important;
            cursor: pointer !important;
            text-decoration: none !important;
            font-weight: bold !important;
            line-height: 1 !important;
            font-size: 0.95rem !important;
        }
        .ts-wrapper.multi .ts-control .item .remove:hover {
            color: #EF4444 !important;
        }

        /* Dark Theme Support */
        [data-bs-theme="dark"] .ts-control {
            background-color: #1F2937 !important;
            border-color: #374151 !important;
            color: #F9FAFB !important;
        }
        [data-bs-theme="dark"] .ts-control .items-placeholder {
            color: #9CA3AF !important;
        }
        [data-bs-theme="dark"] .ts-wrapper:not(.multi) .ts-control .item,
        [data-bs-theme="dark"] .ts-wrapper.single .ts-control .item {
            color: #F9FAFB !important;
        }
        [data-bs-theme="dark"] .ts-dropdown,
        [data-bs-theme="dark"] .ts-wrapper > .ts-dropdown {
            background-color: #111827 !important;
            border-color: #374151 !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.4) !important;
        }
        [data-bs-theme="dark"] .ts-dropdown .dropdown-input-wrap {
            background-color: #111827 !important;
            border-bottom-color: #374151 !important;
        }
        [data-bs-theme="dark"] .ts-dropdown .dropdown-input {
            color: #F9FAFB !important;
        }
        [data-bs-theme="dark"] .ts-dropdown .option {
            color: #D1D5DB !important;
        }
        [data-bs-theme="dark"] .ts-dropdown .option:hover,
        [data-bs-theme="dark"] .ts-dropdown .option.active {
            background-color: #1F2937 !important;
            color: #F9FAFB !important;
        }
        [data-bs-theme="dark"] .ts-dropdown .option.selected {
            background-color: #1F2937 !important;
            color: #F9FAFB !important;
        }
        [data-bs-theme="dark"] .ts-wrapper.multi .ts-control .item {
            background: #374151 !important;
            border-color: #4B5563 !important;
            color: #F9FAFB !important;
        }

        /* ══════════════════════════════════════════════════════════
           REACT-STYLE TOAST NOTIFICATION STACK
           ══════════════════════════════════════════════════════════ */
        .react-toast-container {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 1090;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
            max-width: 420px;
            width: calc(100% - 48px);
        }
        .react-toast {
            pointer-events: auto;
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.12), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
            padding: 14px 16px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            position: relative;
            overflow: hidden;
            animation: toastSlideIn 0.24s cubic-bezier(0.16, 1, 0.3, 1);
            transition: all 0.2s ease;
        }
        .react-toast.toast-hiding {
            opacity: 0;
            transform: translateX(100%) scale(0.95);
        }
        @keyframes toastSlideIn {
            from { opacity: 0; transform: translateY(-16px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .react-toast-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: var(--k-gold, #D97706);
            width: 100%;
            animation: toastProgress linear forwards;
        }
        @keyframes toastProgress {
            from { width: 100%; }
            to { width: 0%; }
        }
        [data-bs-theme="dark"] .react-toast {
            background: #0F172A;
            border-color: #334155;
            color: #F8FAFC;
        }

        /* ══════════════════════════════════════════════════════════
           REACT-STYLE PILL TABS
           ══════════════════════════════════════════════════════════ */
        .react-nav-pills {
            background: #F1F5F9;
            padding: 4px;
            border-radius: 12px;
            display: inline-flex;
            gap: 4px;
            border: 1px solid #E2E8F0;
        }
        .react-nav-pills .nav-link {
            border-radius: 9px;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 6px 14px;
            color: #64748B;
            border: none;
            transition: all 0.15s cubic-bezier(0.16, 1, 0.3, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .react-nav-pills .nav-link.active,
        .react-nav-pills .nav-link:hover {
            background: #FFFFFF;
            color: #0F172A;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.08);
        }
        .react-nav-pills .nav-link.active {
            color: #B45309;
        }
        [data-bs-theme="dark"] .react-nav-pills {
            background: #0F172A;
            border-color: #1E293B;
        }
        [data-bs-theme="dark"] .react-nav-pills .nav-link {
            color: #94A3B8;
        }
        [data-bs-theme="dark"] .react-nav-pills .nav-link.active,
        [data-bs-theme="dark"] .react-nav-pills .nav-link:hover {
            background: #1E293B;
            color: #FBBF24;
        }

        /* React-Style Modal Scale Transition */
        .modal.fade .modal-dialog {
            transform: scale(0.96);
            transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .modal.show .modal-dialog {
            transform: scale(1);
        }
        .modal-backdrop.show {
            backdrop-filter: blur(6px);
        }

        /* Page Entrance Animation */
        @keyframes fadeInContent {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>

    @yield('styles')
</head>
<body>

    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand kaldis-navbar sticky-top">
        <div class="container-fluid px-3 d-flex align-items-center justify-content-between">
            
            <!-- Left: Brand Logo & Title -->
            <div class="d-flex align-items-center">
                <button id="sidebarToggle" class="btn btn-outline-secondary btn-sm rounded-circle me-2 d-flex align-items-center justify-content-center shadow-xs sidebar-toggle-btn" 
                        style="width: 36px; height: 36px;" type="button" aria-label="Toggle Sidebar" title="Toggle Sidebar (Alt+S)">
                    <i class="fas fa-bars text-secondary"></i>
                </button>
                <a class="navbar-brand d-flex align-items-center m-0 text-decoration-none" href="{{ route('dashboard') }}">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="Kaldis Coffee" class="kaldis-brand-img me-2" onerror="this.src='{{ asset('images/logo.png') }}'">
                    <div class="lh-sm">
                        <div class="brand-title">KALDIS COFFEE PLC</div>
                        <div class="brand-subtitle">PLANNING &amp; PERFORMANCE</div>
                    </div>
                </a>
            </div>

            <!-- Right: Period Badges, Theme Toggle, User Profile -->
            <div class="d-flex align-items-center gap-2 gap-md-3 ms-auto">
                <span class="badge period-badge py-2 px-3 shadow-sm d-none d-sm-inline-flex">
                    <i class="far fa-calendar-alt text-warning me-1"></i> {{ format_et_calendar() }}
                </span>

                <!-- Theme Toggle Button (Light Mode / Dark Mode) -->
                <button id="themeToggle" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center shadow-sm" 
                        style="width: 36px; height: 36px;" type="button" title="Toggle Light / Dark Mode (Alt+D)" aria-label="Toggle Dark Mode">
                    <i id="themeToggleIcon" class="fas fa-moon text-secondary"></i>
                </button>

                <!-- User Profile Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle d-flex align-items-center gap-2 user-profile-btn shadow-sm" type="button" data-bs-toggle="dropdown">
                        <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center fw-bold" style="width: 26px; height: 26px; font-size: 0.8rem;">
                            {{ strtoupper(substr(auth()->user()->full_name ?? auth()->user()->username ?? 'U', 0, 1)) }}
                        </div>
                        <span class="d-none d-sm-inline fw-semibold small user-profile-name">
                            {{ auth()->user()->full_name ?? auth()->user()->username }}
                        </span>
                        <span class="badge bg-warning text-dark text-uppercase d-none d-md-inline" style="font-size: 0.65rem;">
                            {{ auth()->user()->role->display_name ?? auth()->user()->role->name ?? '' }}
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li>
                            <div class="dropdown-item-text">
                                <small class="text-muted d-block">Signed in as</small>
                                <strong>{{ auth()->user()->username }}</strong>
                                @if(auth()->user()->department)
                                    <div class="small text-muted">{{ auth()->user()->department->department_name }}</div>
                                @endif
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        @if(auth()->user()->isSuperAdmin())
                            <li><a class="dropdown-item" href="{{ route('admin.settings') }}"><i class="fas fa-sliders me-2 text-muted"></i> System Settings</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.audit') }}"><i class="fas fa-history me-2 text-muted"></i> Audit Trail</a></li>
                            <li><hr class="dropdown-divider"></li>
                        @endif
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

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="sidebarBackdrop" class="sidebar-backdrop"></div>

    <!-- App Wrapper (Sidebar + Main Content) -->
    <div class="app-wrapper">
        <!-- Sidebar Navigation -->
        <aside class="kaldis-sidebar" id="kaldisSidebar">
            <!-- Floating Edge Toggle (Desktop) — Outside inner-wrapper so overflow:hidden cannot clip it -->
            <button id="sidebarEdgeToggle" class="sidebar-edge-toggle d-none d-lg-flex" type="button" 
                    title="Toggle Sidebar (Alt+S)" aria-label="Toggle Sidebar">
                <i class="fas fa-chevron-left edge-chevron"></i>
            </button>

            <div class="sidebar-inner-wrapper">
                <!-- Mobile Drawer Close Header (Only shown on mobile screen when drawer is open) -->
                <div class="sidebar-mobile-header d-flex align-items-center justify-content-between px-3 py-3 border-bottom d-lg-none" style="border-color: rgba(255,255,255,0.08) !important;">
                    <div class="d-flex align-items-center gap-2">
                        <img src="{{ asset('assets/images/logo.png') }}" alt="Kaldis Coffee" style="width: 28px; height: 28px; border-radius: 50%; border: 1.5px solid rgba(251,191,36,0.5);" onerror="this.src='{{ asset('images/logo.png') }}'">
                        <span class="fw-bold small" style="color: rgba(255,255,255,0.80); letter-spacing: 0.06em; font-size: 0.78rem;">KALDIS PLANNER</span>
                    </div>
                    <button class="btn btn-sm p-1 border-0" id="sidebarMobileClose" type="button" aria-label="Close Sidebar" style="color: rgba(255,255,255,0.40); background: rgba(255,255,255,0.06); border-radius: 6px;">
                        <i class="fas fa-xmark fs-5"></i>
                    </button>
                </div>

                <!-- Modern Search Menu Box with Shortcut Badge -->
                <div class="sidebar-search-box">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="sidebarMenuSearch" class="form-control" placeholder="Search menu..." autocomplete="off">
                        <span class="input-group-text pe-1 d-none d-lg-flex">
                            <kbd class="bg-white border text-muted px-1.5 py-0.5 rounded shadow-xs" style="font-size: 0.65rem;">Alt+S</kbd>
                        </span>
                    </div>
                </div>

                <!-- Navigation List -->
                <div class="sidebar-nav-scroll flex-grow-1">
                    <!-- Dashboards -->
                    <div class="sidebar-heading">Dashboards</div>
                    <ul class="nav flex-column mb-3">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" 
                               data-bs-toggle="tooltip" data-bs-placement="right" title="Overview">
                                <span class="nav-icon-wrap icon-dashboard"><i class="fas fa-house"></i></span>
                                <span class="nav-text">Overview</span>
                                <i class="fas fa-chevron-right sidebar-chevron"></i>
                            </a>
                        </li>
                        @if(auth()->user()->isGM() || auth()->user()->isSuperAdmin())
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('dashboard.gm') ? 'active' : '' }}" href="{{ route('dashboard.gm') }}" 
                                   data-bs-toggle="tooltip" data-bs-placement="right" title="GM Executive Dashboard">
                                    <span class="nav-icon-wrap icon-dashboard"><i class="fas fa-chart-line"></i></span>
                                    <span class="nav-text">GM Dashboard</span>
                                    <i class="fas fa-chevron-right sidebar-chevron"></i>
                                </a>
                            </li>
                        @endif
                        @if(auth()->user()->department_id || auth()->user()->canAccessAllDepartments())
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('dashboard.department') ? 'active' : '' }}" href="{{ route('dashboard.department') }}" 
                                   data-bs-toggle="tooltip" data-bs-placement="right" title="Department Workspace">
                                    <span class="nav-icon-wrap icon-dashboard"><i class="fas fa-building-user"></i></span>
                                    <span class="nav-text">Department Workspace</span>
                                    <i class="fas fa-chevron-right sidebar-chevron"></i>
                                </a>
                            </li>
                        @endif
                    </ul>

                    <!-- Strategic Alignment -->
                    <div class="sidebar-heading">Strategic Alignment</div>
                    <ul class="nav flex-column mb-3">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('strategy.annual') ? 'active' : '' }}" href="{{ route('strategy.annual') }}" 
                               data-bs-toggle="tooltip" data-bs-placement="right" title="Annual Strategy Plan">
                                <span class="nav-icon-wrap icon-strategy"><i class="fas fa-bullseye"></i></span>
                                <span class="nav-text">Annual Strategy Plan</span>
                                <i class="fas fa-chevron-right sidebar-chevron"></i>
                            </a>
                        </li>
                        @if(auth()->user()->isGM() || auth()->user()->isSuperAdmin())
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('strategy.activation') ? 'active' : '' }}" href="{{ route('strategy.activation') }}" 
                                   data-bs-toggle="tooltip" data-bs-placement="right" title="GM Monthly Activation">
                                    <span class="nav-icon-wrap icon-strategy"><i class="fas fa-toggle-on"></i></span>
                                    <span class="nav-text">GM Monthly Activation</span>
                                    <i class="fas fa-chevron-right sidebar-chevron"></i>
                                </a>
                            </li>
                        @endif
                    </ul>

                    <!-- Operational Planning -->
                    <div class="sidebar-heading">Operational Planning</div>
                    <ul class="nav flex-column mb-3">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('monthly.plans*') ? 'active' : '' }}" href="{{ route('monthly.plans') }}" 
                               data-bs-toggle="tooltip" data-bs-placement="right" title="Monthly Plans">
                                <span class="nav-icon-wrap icon-planning"><i class="fas fa-calendar-days"></i></span>
                                <span class="nav-text">Monthly Plans</span>
                                <i class="fas fa-chevron-right sidebar-chevron"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('weekly.plans*') ? 'active' : '' }}" href="{{ route('weekly.plans') }}" 
                               data-bs-toggle="tooltip" data-bs-placement="right" title="Weekly Plans">
                                <span class="nav-icon-wrap icon-planning"><i class="fas fa-list-check"></i></span>
                                <span class="nav-text">Weekly Plans</span>
                                <i class="fas fa-chevron-right sidebar-chevron"></i>
                            </a>
                        </li>
                    </ul>

                    <!-- Execution & Performance -->
                    <div class="sidebar-heading">Execution &amp; Performance</div>
                    <ul class="nav flex-column mb-3">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('weekly.performance*') ? 'active' : '' }}" href="{{ route('weekly.performance') }}" 
                               data-bs-toggle="tooltip" data-bs-placement="right" title="Weekly Performance">
                                <span class="nav-icon-wrap icon-performance"><i class="fas fa-clipboard-check"></i></span>
                                <span class="nav-text">Weekly Performance</span>
                                <i class="fas fa-chevron-right sidebar-chevron"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('performance.weekly*') ? 'active' : '' }}" href="{{ route('performance.weekly') }}" 
                               data-bs-toggle="tooltip" data-bs-placement="right" title="Weekly Scorecard">
                                <span class="nav-icon-wrap icon-performance"><i class="fas fa-table"></i></span>
                                <span class="nav-text">Weekly Scorecard</span>
                                <i class="fas fa-chevron-right sidebar-chevron"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('performance.monthly*') ? 'active' : '' }}" href="{{ route('performance.monthly') }}" 
                               data-bs-toggle="tooltip" data-bs-placement="right" title="Monthly Rollup">
                                <span class="nav-icon-wrap icon-performance"><i class="fas fa-calendar-check"></i></span>
                                <span class="nav-text">Monthly Rollup</span>
                                <i class="fas fa-chevron-right sidebar-chevron"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('performance.not_done*') ? 'active' : '' }}" href="{{ route('performance.not_done') }}" 
                               data-bs-toggle="tooltip" data-bs-placement="right" title="Why Not Done Analysis">
                                <span class="nav-icon-wrap icon-performance"><i class="fas fa-circle-exclamation"></i></span>
                                <span class="nav-text">Why Not Done Analysis</span>
                                <i class="fas fa-chevron-right sidebar-chevron"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('performance.achievements*') ? 'active' : '' }}" href="{{ route('performance.achievements') }}" 
                               data-bs-toggle="tooltip" data-bs-placement="right" title="Achievements Feed">
                                <span class="nav-icon-wrap icon-performance"><i class="fas fa-award"></i></span>
                                <span class="nav-text">Achievements Feed</span>
                                <i class="fas fa-chevron-right sidebar-chevron"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('performance.challenges*') ? 'active' : '' }}" href="{{ route('performance.challenges') }}" 
                               data-bs-toggle="tooltip" data-bs-placement="right" title="Challenges Tracker">
                                <span class="nav-icon-wrap icon-performance"><i class="fas fa-triangle-exclamation"></i></span>
                                <span class="nav-text">Challenges Tracker</span>
                                <i class="fas fa-chevron-right sidebar-chevron"></i>
                            </a>
                        </li>
                    </ul>

                    @if(auth()->user()->isSuperAdmin() || auth()->user()->isGM())
                        <!-- Administration -->
                        <div class="sidebar-heading">System Administration</div>
                        <ul class="nav flex-column mb-3">
                            @if(auth()->user()->isSuperAdmin())
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}" 
                                       data-bs-toggle="tooltip" data-bs-placement="right" title="Admin Overview">
                                        <span class="nav-icon-wrap icon-admin"><i class="fas fa-gauge-high"></i></span>
                                        <span class="nav-text">Admin Overview</span>
                                        <i class="fas fa-chevron-right sidebar-chevron"></i>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users') }}" 
                                       data-bs-toggle="tooltip" data-bs-placement="right" title="User Accounts">
                                        <span class="nav-icon-wrap icon-admin"><i class="fas fa-users-gear"></i></span>
                                        <span class="nav-text">User Accounts</span>
                                        <i class="fas fa-chevron-right sidebar-chevron"></i>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('admin.departments*') ? 'active' : '' }}" href="{{ route('admin.departments') }}" 
                                       data-bs-toggle="tooltip" data-bs-placement="right" title="Departments">
                                        <span class="nav-icon-wrap icon-admin"><i class="fas fa-sitemap"></i></span>
                                        <span class="nav-text">Departments</span>
                                        <i class="fas fa-chevron-right sidebar-chevron"></i>
                                    </a>
                                </li>
                            @endif
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.settings*') ? 'active' : '' }}" href="{{ route('admin.settings') }}" 
                                   data-bs-toggle="tooltip" data-bs-placement="right" title="System Settings">
                                    <span class="nav-icon-wrap icon-admin"><i class="fas fa-sliders"></i></span>
                                    <span class="nav-text">System Settings</span>
                                    <i class="fas fa-chevron-right sidebar-chevron"></i>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('telegram.*') ? 'active' : '' }}" href="{{ route('telegram.status') }}" 
                                   data-bs-toggle="tooltip" data-bs-placement="right" title="Telegram Bot &amp; Webhook">
                                    <span class="nav-icon-wrap icon-admin"><i class="fab fa-telegram"></i></span>
                                    <span class="nav-text">Telegram &amp; Bot</span>
                                    <i class="fas fa-chevron-right sidebar-chevron"></i>
                                </a>
                            </li>
                            @if(auth()->user()->isSuperAdmin())
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('admin.audit*') ? 'active' : '' }}" href="{{ route('admin.audit') }}" 
                                       data-bs-toggle="tooltip" data-bs-placement="right" title="Audit Trail">
                                        <span class="nav-icon-wrap icon-admin"><i class="fas fa-fingerprint"></i></span>
                                        <span class="nav-text">Audit Trail</span>
                                        <i class="fas fa-chevron-right sidebar-chevron"></i>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('admin.reports*') ? 'active' : '' }}" href="{{ route('admin.reports') }}" 
                                       data-bs-toggle="tooltip" data-bs-placement="right" title="Reports & Tracking">
                                        <span class="nav-icon-wrap icon-admin"><i class="fas fa-file-invoice"></i></span>
                                        <span class="nav-text">Reports &amp; Tracking</span>
                                        <i class="fas fa-chevron-right sidebar-chevron"></i>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    @endif
                </div>

                <!-- Integrated User Profile Card & Collapse Footer -->
                <div class="sidebar-user-section">
                    <div class="sidebar-user-card">
                        <div class="sidebar-avatar-wrap">
                            {{ strtoupper(substr(auth()->user()->full_name ?? auth()->user()->username ?? 'U', 0, 1)) }}
                            <span class="sidebar-online-dot" title="Active"></span>
                        </div>
                        <div class="sidebar-user-info overflow-hidden">
                            <div class="fw-bold small text-truncate text-white">{{ auth()->user()->full_name ?? auth()->user()->username }}</div>
                            <div class="text-white-50" style="font-size: 0.7rem;">{{ auth()->user()->role->display_name ?? auth()->user()->role->name ?? 'User' }}</div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="ms-auto mb-0">
                            @csrf
                            <button type="submit" class="btn btn-sm p-1 border-0" title="Log Out" style="color: rgba(255,255,255,0.30); background: transparent; transition: color 0.15s ease;" onmouseover="this.style.color='#FB7185'" onmouseout="this.style.color='rgba(255,255,255,0.30)'">
                                <i class="fas fa-arrow-right-from-bracket"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Content Wrapper (Main Content Area + Footer) -->
        <div class="content-wrapper" style="flex:1; display:flex; flex-direction:column; min-width:0;">
            <!-- Main Content Area -->
            <main class="main-content" style="animation: fadeInContent 0.35s cubic-bezier(0.34,1.56,0.64,1) both;">
                <!-- Flash Notifications -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
                        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
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

            <!-- Footer Banner -->
            <footer class="kaldis-footer">
                <div class="container-fluid px-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                    <div>
                        &copy; {{ date('Y') }} Kaldis Coffee PLC. All rights reserved.
                    </div>
                    <div>
                        Developed by <strong>IT Department</strong>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- React-Style Toast Container -->
    <div id="reactToastContainer" class="react-toast-container"></div>

    <!-- Bootstrap 5 Bundle & Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    
    <!-- TomSelect 2.4 JS -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/js/tom-select.complete.min.js"></script>

    <!-- Kaldis Core JS with Cache Busting -->
    <script src="{{ asset('assets/js/app.js') }}?v={{ time() }}"></script>

    <!-- React-Style Toast Notification System -->
    <script>
        window.Toast = {
            show(type, title, message, duration = 4500) {
                const container = document.getElementById('reactToastContainer');
                if (!container) return;

                const toast = document.createElement('div');
                toast.className = 'react-toast shadow-lg';
                
                const iconMap = {
                    success: 'fa-circle-check text-success',
                    error: 'fa-circle-exclamation text-danger',
                    warning: 'fa-triangle-exclamation text-warning',
                    info: 'fa-circle-info text-info'
                };
                const iconClass = iconMap[type] || 'fa-bell text-warning';

                toast.innerHTML = `
                    <i class="fas ${iconClass} fs-5 mt-1 flex-shrink-0"></i>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold small text-dark mb-0">${title}</div>
                        <div class="small text-muted text-break">${message}</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-link text-muted p-0 border-0 ms-2" style="font-size: 0.9rem;" onclick="this.closest('.react-toast').classList.add('toast-hiding'); setTimeout(() => this.closest('.react-toast')?.remove(), 200);">
                        <i class="fas fa-xmark"></i>
                    </button>
                    <div class="react-toast-progress" style="animation-duration: ${duration}ms;"></div>
                `;

                container.appendChild(toast);

                setTimeout(() => {
                    if (toast.parentElement) {
                        toast.classList.add('toast-hiding');
                        setTimeout(() => toast.remove(), 250);
                    }
                }, duration);
            },
            success(message, title = 'Success') { this.show('success', title, message); },
            error(message, title = 'Error') { this.show('error', title, message); },
            info(message, title = 'Information') { this.show('info', title, message); },
            warning(message, title = 'Attention') { this.show('warning', title, message); }
        };

        // Automatically trigger toast for flash notifications
        @if(session('success'))
            document.addEventListener('DOMContentLoaded', () => {
                window.Toast.success("{{ addslashes(session('success')) }}");
            });
        @endif
        @if(session('error'))
            document.addEventListener('DOMContentLoaded', () => {
                window.Toast.error("{{ addslashes(session('error')) }}");
            });
        @endif
    </script>
    
    <!-- Global Searchable Selects (TomSelect) Delegator -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof window.initSearchableSelects === 'function') {
                window.initSearchableSelects();
            }
            document.addEventListener('shown.bs.modal', function(e) {
                if (typeof window.initSearchableSelects === 'function') {
                    window.initSearchableSelects(e.target);
                }
            });
        });
    </script>
    
    <!-- Instant Live Search Helper -->
    <script>
        function setupLiveSearch(inputId, tableId) {
            const input = document.getElementById(inputId);
            const table = document.getElementById(tableId);
            if (!input || !table) return;

            input.addEventListener('keyup', function () {
                const filter = input.value.toLowerCase();
                const tbody = table.getElementsByTagName('tbody')[0];
                if (!tbody) return;
                const rows = tbody.getElementsByTagName('tr');

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

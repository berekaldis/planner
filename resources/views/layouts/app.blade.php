<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | {{ config('kaldis.app_name', 'Kaldis Planning Portal') }}</title>
    
    <!-- Inline State Initializer (Eliminates Flash) -->
    <script>
        window.APP_BASE_URL = '{{ url('') }}';
        (function() {
            localStorage.removeItem('kaldis_theme');
            document.documentElement.setAttribute('data-bs-theme', 'light');
            if (localStorage.getItem('sidebar_collapsed') === 'true' && window.innerWidth >= 992) {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        })();
    </script>

    <!-- Google Fonts: Instrument Sans (Matching CompanySystem Shadcn/UI) & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 & FontAwesome 6 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <!-- DataTables Bootstrap 5 CSS & Responsive Extension -->
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=3">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=3">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v=3">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v=3">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v=3">

    <!-- TomSelect 2.4 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">

    <!-- Kaldis Design System Core Stylesheet with Cache Busting -->
    <link href="{{ asset('assets/css/style.css') }}?v=4.0.0" rel="stylesheet">

    <style>
        /* ══════════════════════════════════════════════════════════
           COMPANYSYSTEM-ALIGNED INSET APPSHELL & SHADCN SURFACES
           ══════════════════════════════════════════════════════════ */
        :root {
            --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --background: #FFFFFF;
            --foreground: #18181B;
            --card: #FFFFFF;
            --card-foreground: #18181B;
            --popover: #FFFFFF;
            --popover-foreground: #18181B;
            --primary: #18181B;
            --primary-foreground: #FAFAFA;
            --secondary: #F4F4F5;
            --secondary-foreground: #18181B;
            --muted: #F4F4F5;
            --muted-foreground: #71717A;
            --accent: #F4F4F5;
            --accent-foreground: #18181B;
            --destructive: #EF4444;
            --destructive-foreground: #FAFAFA;
            --border: #E4E4E7;
            --input: #E4E4E7;
            --ring: #D4D4D8;
            --radius: 0.625rem;

            --sidebar: #FAFAFA;
            --sidebar-foreground: #18181B;
            --sidebar-primary: #18181B;
            --sidebar-primary-foreground: #FAFAFA;
            --sidebar-accent: #F4F4F5;
            --sidebar-accent-foreground: #18181B;
            --sidebar-border: #E4E4E7;
            --sidebar-ring: #D4D4D8;

            --kaldis-gold: #D97706;
            --kaldis-gold-hover: #B45309;
            --kaldis-sidebar-width: 16.5rem;
            --kaldis-sidebar-collapsed-width: 3.25rem;
            --kaldis-topbar-height: 3.75rem;
            --kaldis-footer-height: 3.125rem;
            --app-vh: calc(100vh / 0.8);
            --app-dvh: calc(100dvh / 0.8);
        }

        /* Reset & Root Canvas (Scaled to 80% default view) */
        html {
            min-height: 100%;
            scroll-behavior: smooth;
            zoom: 0.8;
        }

        body {
            min-height: var(--app-vh, 100vh);
            margin: 0;
            padding: 0;
            font-family: var(--font-sans);
            background-color: var(--sidebar);
            color: var(--foreground);
            overflow-x: hidden;
            overflow-y: auto;
            -webkit-font-smoothing: antialiased;
        }

        /* Inset AppShell Container */
        .app-shell {
            display: flex;
            width: 100%;
            min-height: var(--app-vh, 100vh);
            background-color: var(--sidebar);
            position: relative;
        }

        /* ══════════════════════════════════════════════════════════
           SIDEBAR COMPONENT (MATCHING COMPANYSYSTEM APPSIDEBAR)
           ══════════════════════════════════════════════════════════ */
        .kaldis-sidebar {
            width: var(--kaldis-sidebar-width) !important;
            min-width: var(--kaldis-sidebar-width) !important;
            max-width: var(--kaldis-sidebar-width) !important;
            height: var(--app-vh, 100vh) !important;
            height: var(--app-dvh, 100dvh) !important;
            min-height: var(--app-vh, 100vh) !important;
            max-height: var(--app-vh, 100vh) !important;
            max-height: var(--app-dvh, 100dvh) !important;
            position: sticky !important;
            top: 0 !important;
            bottom: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            background-color: var(--sidebar) !important;
            background-image: none !important;
            border-right: 1px solid var(--sidebar-border) !important;
            box-shadow: none !important;
            transition: width 0.2s cubic-bezier(0.4, 0, 0.2, 1), min-width 0.2s cubic-bezier(0.4, 0, 0.2, 1), transform 0.2s ease !important;
            z-index: 1040;
            overflow: hidden;
            user-select: none;
        }

        .sidebar-inner-wrapper {
            display: flex !important;
            flex-direction: column !important;
            height: 100% !important;
            min-height: 100% !important;
            max-height: 100% !important;
            width: 100%;
            overflow: hidden;
        }

        /* Sidebar Brand Header */
        .sidebar-brand-header {
            height: 3.75rem;
            min-height: 3.75rem;
            display: flex;
            align-items: center;
            padding: 0 1rem;
            border-bottom: 1px solid var(--sidebar-border);
            gap: 0.75rem;
        }

        .sidebar-brand-logo-badge {
            width: 38px;
            height: 38px;
            border-radius: 0.5rem;
            background-color: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }

        .sidebar-brand-logo-img {
            width: 36px;
            height: 36px;
            object-fit: contain;
        }

        .sidebar-brand-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--sidebar-foreground);
            line-height: 1.2;
            letter-spacing: -0.01em;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-brand-subtitle {
            font-size: 0.7rem;
            color: var(--muted-foreground);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Sidebar Search Box */
        .sidebar-search-box {
            padding: 0.75rem 0.875rem 0.25rem 0.875rem;
        }

        .sidebar-search-box .input-group {
            background-color: var(--background) !important;
            border: 1px solid var(--sidebar-border) !important;
            border-radius: calc(var(--radius) - 2px) !important;
            padding: 2px 6px;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
            transition: all 0.15s ease;
        }

        .sidebar-search-box .input-group:focus-within {
            border-color: var(--sidebar-ring) !important;
            box-shadow: 0 0 0 1px var(--sidebar-ring) !important;
        }

        .sidebar-search-box .input-group-text {
            background: transparent !important;
            border: none !important;
            color: var(--muted-foreground) !important;
            font-size: 0.75rem;
            padding: 0 4px;
        }

        .sidebar-search-box input {
            background: transparent !important;
            border: none !important;
            font-size: 0.8125rem;
            color: var(--sidebar-foreground) !important;
            box-shadow: none !important;
            padding: 0.3rem 0.4rem;
        }

        .sidebar-search-box input::placeholder {
            color: var(--muted-foreground) !important;
            font-size: 0.8125rem;
            opacity: 0.85;
        }

        /* Sidebar Nav Scroll */
        .sidebar-nav-scroll {
            flex: 1 1 auto;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 0.5rem 0.65rem 0.5rem 0.65rem;
            min-height: 0;
        }

        /* Accordion Section Group (Matching CompanySystem NavMain) */
        .nav-group-section {
            margin-bottom: 0.25rem;
        }

        .nav-group-trigger {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 0.65rem;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--sidebar-foreground);
            background: transparent;
            border: none;
            border-radius: calc(var(--radius) - 2px);
            cursor: pointer;
            transition: background-color 0.15s ease, color 0.15s ease;
            text-align: left;
        }

        .nav-group-trigger:hover {
            background-color: var(--sidebar-accent);
            color: var(--sidebar-accent-foreground);
        }

        .nav-group-trigger .group-left {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            min-width: 0;
        }

        .nav-group-trigger .group-left i {
            width: 16px;
            font-size: 0.85rem;
            color: var(--muted-foreground);
            transition: color 0.15s ease;
        }

        .nav-group-trigger:hover .group-left i {
            color: var(--sidebar-foreground);
        }

        .nav-group-trigger .group-chevron {
            font-size: 0.68rem;
            color: var(--muted-foreground);
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .nav-group-trigger[aria-expanded="true"] .group-chevron {
            transform: rotate(90deg);
        }

        /* Submenu Items under Section */
        .nav-group-items {
            display: flex;
            flex-direction: column;
            gap: 1px;
            padding-left: 1.65rem;
            margin-top: 2px;
            border-left: 1px solid var(--sidebar-border);
            margin-left: 1.15rem;
        }

        .nav-sub-item {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.42rem 0.65rem;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--muted-foreground);
            border-radius: calc(var(--radius) - 3px);
            text-decoration: none;
            transition: background-color 0.12s ease, color 0.12s ease;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-sub-item i {
            font-size: 0.8rem;
            width: 14px;
            text-align: center;
            opacity: 0.8;
            flex-shrink: 0;
        }

        .nav-sub-item:hover {
            background-color: var(--sidebar-accent);
            color: var(--sidebar-foreground);
            text-decoration: none;
        }

        .nav-sub-item.active {
            background-color: var(--sidebar-accent);
            color: var(--sidebar-foreground);
            font-weight: 600;
        }

        .nav-sub-item.active i {
            color: var(--kaldis-gold);
            opacity: 1;
        }

        /* Sidebar Footer (Same size & height as App Footer: 3.125rem / 50px, aligned at bottom) */
        .sidebar-footer {
            height: var(--kaldis-footer-height, 3.125rem);
            min-height: var(--kaldis-footer-height, 3.125rem);
            max-height: var(--kaldis-footer-height, 3.125rem);
            padding: 0 0.5rem;
            border-top: 1px solid var(--sidebar-border);
            background-color: var(--sidebar);
            margin-top: auto;
            position: relative;
            display: flex;
            align-items: center;
            box-sizing: border-box;
        }

        .sidebar-footer .dropup {
            width: 100%;
            display: flex;
            align-items: center;
        }

        .nav-user-btn {
            width: 100%;
            height: 2.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.2rem 0.45rem;
            border-radius: calc(var(--radius) - 3px);
            border: 1px solid transparent;
            background: transparent;
            cursor: pointer;
            transition: background-color 0.15s ease, border-color 0.15s ease;
            text-align: left;
            text-decoration: none;
        }

        .nav-user-btn:hover, .nav-user-btn[aria-expanded="true"] {
            background-color: var(--sidebar-accent);
            border-color: var(--sidebar-border);
        }

        .nav-user-avatar {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background-color: rgba(217, 119, 6, 0.15);
            color: #D97706;
            border: 1px solid rgba(217, 119, 6, 0.25);
            font-weight: 600;
            font-size: 0.68rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .nav-user-info {
            display: flex;
            flex-direction: column;
            min-width: 0;
            flex-grow: 1;
            line-height: 1.15;
        }

        .nav-user-name {
            font-size: 0.775rem;
            font-weight: 600;
            color: var(--sidebar-foreground);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.15;
        }

        .nav-user-role {
            font-size: 0.65rem;
            color: var(--muted-foreground);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.1;
        }

        .sidebar-footer .dropdown-menu {
            min-width: 240px;
            margin-bottom: 0.4rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.08) !important;
            border: 1px solid var(--border) !important;
            border-radius: var(--radius) !important;
            padding: 0.35rem;
            background-color: var(--card) !important;
        }

        .sidebar-footer .dropdown-item {
            border-radius: calc(var(--radius) - 3px);
            font-size: 0.8125rem;
            padding: 0.45rem 0.6rem;
            transition: background-color 0.12s ease, color 0.12s ease;
            color: var(--foreground);
        }

        .sidebar-footer .dropdown-item:hover {
            background-color: var(--secondary);
            color: var(--foreground);
        }

        .sidebar-footer .dropdown-item.text-danger:hover {
            background-color: rgba(239, 68, 68, 0.1);
            color: #DC2626 !important;
        }

        /* ══════════════════════════════════════════════════════════
           COLLAPSED SIDEBAR (DESKTOP)
           ══════════════════════════════════════════════════════════ */
        @media (min-width: 992px) {
            html.sidebar-collapsed .kaldis-sidebar,
            body.sidebar-collapsed .kaldis-sidebar {
                width: var(--kaldis-sidebar-collapsed-width) !important;
                min-width: var(--kaldis-sidebar-collapsed-width) !important;
                max-width: var(--kaldis-sidebar-collapsed-width) !important;
            }

            html.sidebar-collapsed .sidebar-brand-title,
            html.sidebar-collapsed .sidebar-brand-subtitle,
            html.sidebar-collapsed .sidebar-search-box,
            html.sidebar-collapsed .nav-group-trigger .nav-label,
            html.sidebar-collapsed .nav-group-trigger .group-chevron,
            html.sidebar-collapsed .nav-group-items,
            html.sidebar-collapsed .nav-user-info,
            html.sidebar-collapsed .nav-user-btn i.fa-up-down {
                display: none !important;
            }

            html.sidebar-collapsed .sidebar-brand-header {
                justify-content: center;
                padding: 0;
            }

            html.sidebar-collapsed .nav-group-trigger {
                justify-content: center;
                padding: 0.65rem 0;
            }

            html.sidebar-collapsed .nav-group-trigger .group-left {
                justify-content: center;
            }

            html.sidebar-collapsed .nav-group-trigger .group-left i {
                margin: 0;
                font-size: 1rem;
            }

            html.sidebar-collapsed .sidebar-footer {
                height: var(--kaldis-footer-height, 3.125rem);
                min-height: var(--kaldis-footer-height, 3.125rem);
                max-height: var(--kaldis-footer-height, 3.125rem);
                padding: 0;
                justify-content: center;
            }

            html.sidebar-collapsed .nav-user-btn {
                width: 2.25rem;
                height: 2.25rem;
                justify-content: center;
                padding: 0;
            }
        }

        /* Mobile Off-Canvas Sidebar */
        @media (max-width: 991.98px) {
            .kaldis-sidebar {
                position: fixed !important;
                top: 0 !important;
                left: -100% !important;
                bottom: 0 !important;
                height: 100% !important;
                height: var(--app-vh, 100vh) !important;
                width: 17.5rem !important;
                min-width: 17.5rem !important;
                z-index: 1060 !important;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25) !important;
            }

            .mobile-sidebar-open .kaldis-sidebar {
                left: 0 !important;
            }

            .sidebar-backdrop {
                display: none;
                position: fixed;
                inset: 0;
                background-color: rgba(0, 0, 0, 0.45);
                backdrop-filter: blur(3px);
                z-index: 1050;
            }

            .mobile-sidebar-open .sidebar-backdrop {
                display: block;
            }

            .sidebar-footer {
                height: var(--kaldis-footer-height, 3.125rem);
                min-height: var(--kaldis-footer-height, 3.125rem);
                max-height: var(--kaldis-footer-height, 3.125rem);
                padding: 0 0.5rem;
            }
        }

        @if(auth()->check() && !empty(auth()->user()->must_change_password))
        /* Lock down sidebar navigation during required first-login password change */
        .app-sidebar .sidebar-nav {
            pointer-events: none;
            opacity: 0.35;
            filter: grayscale(0.5);
            user-select: none;
        }
        @endif

        /* ══════════════════════════════════════════════════════════
           INSET CONTAINER & APP CONTENT (MATCHING COMPANYSYSTEM)
           ══════════════════════════════════════════════════════════ */
        .sidebar-inset-container {
            flex: 1 1 auto;
            min-width: 0;
            display: flex;
            flex-direction: column;
            background-color: var(--background);
            transition: all 0.2s ease;
        }

        @media (min-width: 992px) {
            .sidebar-inset-container {
                margin: 0.5rem 0.5rem 0 0;
                border-radius: var(--radius) var(--radius) 0 0;
                border: 1px solid var(--border);
                border-bottom: none;
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
                min-height: calc(var(--app-vh, 100vh) - 0.5rem);
            }
        }

        /* App Sidebar Header (Top Inset Header) */
        .app-sidebar-header {
            height: 3.75rem;
            min-height: 3.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.25rem;
            border-bottom: 1px solid var(--border);
            background-color: var(--background);
            position: sticky;
            top: 0;
            z-index: 1030;
            border-top-left-radius: var(--radius);
            border-top-right-radius: var(--radius);
        }

        /* Sidebar Trigger Button (Shadcn Ghost Button) */
        .sidebar-trigger-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: calc(var(--radius) - 3px);
            background: transparent;
            border: 1px solid transparent;
            color: var(--foreground);
            font-size: 0.95rem;
            cursor: pointer;
            transition: background-color 0.15s ease, border-color 0.15s ease;
            padding: 0;
        }

        .sidebar-trigger-btn:hover {
            background-color: var(--secondary);
            border-color: var(--border);
        }

        /* Breadcrumbs */
        .breadcrumbs-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8125rem;
            color: var(--muted-foreground);
        }

        .breadcrumb-sep {
            color: var(--muted-foreground);
            opacity: 0.5;
            font-size: 0.7rem;
        }

        .breadcrumb-link {
            color: var(--muted-foreground);
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .breadcrumb-link:hover {
            color: var(--foreground);
        }

        .breadcrumb-current {
            color: var(--foreground);
            font-weight: 500;
        }

        /* Inset Header Utilities (Ethiopian Date/Time & Theme Toggle) */
        .header-util-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--muted-foreground);
            background-color: var(--secondary);
            border: 1px solid var(--border);
            border-radius: 9999px;
            padding: 0.3rem 0.75rem;
        }

        /* App Content Body */
        .app-content-body {
            flex: 1 0 auto;
            padding: 1.5rem;
            background-color: var(--background);
            min-width: 0;
        }

        @media (max-width: 768px) {
            .app-content-body {
                padding: 1rem;
            }
        }

        /* App Inset Footer (Matches Sidebar Footer Height & Alignments: 3.125rem / 50px) */
        .app-footer {
            height: var(--kaldis-footer-height, 3.125rem);
            min-height: var(--kaldis-footer-height, 3.125rem);
            max-height: var(--kaldis-footer-height, 3.125rem);
            padding: 0 1.25rem;
            border-top: 1px solid var(--border);
            background-color: var(--background);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8125rem;
            color: var(--muted-foreground);
            box-sizing: border-box;
        }

        @media (max-width: 768px) {
            .app-footer {
                flex-direction: column;
                height: auto;
                min-height: auto;
                max-height: none;
                padding: 0.75rem 1rem;
                gap: 0.35rem;
                align-items: flex-start;
            }
        }

        /* ══════════════════════════════════════════════════════════
           CARDS, TABLES, BUTTONS & FORM STYLING
           ══════════════════════════════════════════════════════════ */
        .card {
            background-color: var(--card) !important;
            color: var(--card-foreground) !important;
            border: 1px solid var(--border) !important;
            border-radius: var(--radius) !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04) !important;
            margin-bottom: 1.25rem;
        }

        .card-header {
            background-color: transparent !important;
            border-bottom: 1px solid var(--border) !important;
            padding: 1rem 1.25rem !important;
        }

        .card-title {
            font-size: 0.95rem !important;
            font-weight: 600 !important;
            color: var(--foreground) !important;
            letter-spacing: -0.01em !important;
            margin: 0 !important;
        }

        .card-body {
            padding: 1.25rem !important;
        }

        /* Shadcn Button Variants */
        .btn-primary, .btn-kaldis {
            background-color: var(--primary) !important;
            color: var(--primary-foreground) !important;
            border: 1px solid transparent !important;
            border-radius: calc(var(--radius) - 2px) !important;
            font-weight: 500 !important;
            font-size: 0.875rem !important;
            padding: 0.45rem 0.95rem !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
            transition: opacity 0.15s ease, background-color 0.15s ease !important;
        }

        .btn-primary:hover, .btn-kaldis:hover {
            opacity: 0.92 !important;
            color: var(--primary-foreground) !important;
        }

        .btn-secondary {
            background-color: var(--secondary) !important;
            color: var(--secondary-foreground) !important;
            border: 1px solid var(--border) !important;
            border-radius: calc(var(--radius) - 2px) !important;
            font-weight: 500 !important;
            font-size: 0.875rem !important;
            padding: 0.45rem 0.95rem !important;
        }

        .btn-secondary:hover {
            background-color: var(--muted) !important;
            color: var(--secondary-foreground) !important;
        }

        .btn-outline-secondary {
            background-color: var(--background) !important;
            border-color: var(--border) !important;
            color: var(--foreground) !important;
            border-radius: calc(var(--radius) - 2px) !important;
            font-weight: 500 !important;
            font-size: 0.875rem !important;
        }

        .btn-outline-secondary:hover {
            background-color: var(--secondary) !important;
            border-color: var(--border) !important;
            color: var(--foreground) !important;
        }

        /* Form Controls */
        .form-control, .form-select {
            background-color: var(--background) !important;
            border: 1px solid var(--input) !important;
            color: var(--foreground) !important;
            border-radius: calc(var(--radius) - 2px) !important;
            font-size: 0.875rem !important;
            padding: 0.5rem 0.75rem !important;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--foreground) !important;
            box-shadow: 0 0 0 1px var(--foreground) !important;
            color: var(--foreground) !important;
            background-color: var(--background) !important;
        }

        /* Clean Table Dividers */
        .table {
            --bs-table-bg: transparent !important;
            --bs-table-color: var(--foreground) !important;
            --bs-table-border-color: var(--border) !important;
            color: var(--foreground) !important;
            font-size: 0.875rem !important;
        }

        .table thead th {
            font-size: 0.75rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            color: var(--muted-foreground) !important;
            font-weight: 600 !important;
            border-bottom: 1px solid var(--border) !important;
            padding: 0.75rem 1rem !important;
            background-color: var(--muted) !important;
        }

        .table tbody td {
            padding: 0.8rem 1rem !important;
            border-bottom: 1px solid var(--border) !important;
            vertical-align: middle !important;
        }

        .table tbody tr:hover td {
            background-color: var(--accent) !important;
        }

        /* Badges */
        .badge {
            border-radius: 6px !important;
            font-weight: 500 !important;
            padding: 0.3em 0.65em !important;
            font-size: 0.75rem !important;
        }

        /* ══════════════════════════════════════════════════════════
           TOAST NOTIFICATIONS (SONNER STYLE)
           ══════════════════════════════════════════════════════════ */
        .react-toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 380px;
            width: calc(100vw - 40px);
            pointer-events: none;
        }

        .react-toast {
            pointer-events: auto;
            background-color: var(--card);
            color: var(--card-foreground);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            padding: 0.85rem 1rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            position: relative;
            overflow: hidden;
            animation: toastSlideIn 0.22s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes toastSlideIn {
            from { opacity: 0; transform: translateY(-10px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .react-toast.toast-hiding {
            opacity: 0;
            transform: translateY(-8px) scale(0.96);
            transition: all 0.2s ease;
        }

        .react-toast-icon-wrap {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1rem;
            margin-top: 1px;
        }

        .toast-success .react-toast-icon-wrap { color: #10B981; }
        .toast-warning .react-toast-icon-wrap { color: #F59E0B; }
        .toast-error .react-toast-icon-wrap, .toast-danger .react-toast-icon-wrap { color: #EF4444; }
        .toast-info .react-toast-icon-wrap { color: #3B82F6; }

        .react-toast-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 2px;
            background: var(--kaldis-gold);
            width: 100%;
            animation: toastProgress linear forwards;
        }

        .toast-success .react-toast-progress { background: #10B981; }
        .toast-warning .react-toast-progress { background: #F59E0B; }
        .toast-error .react-toast-progress { background: #EF4444; }
        .toast-info .react-toast-progress { background: #3B82F6; }

        @keyframes toastProgress {
            from { width: 100%; }
            to { width: 0%; }
        }

        /* ══════════════════════════════════════════════════════════
           TOMSELECT SHADCN POPOVER STYLE
           ══════════════════════════════════════════════════════════ */
        .ts-wrapper.form-control, .ts-wrapper.form-select {
            padding: 0;
            border: none;
            box-shadow: none;
            background: transparent;
        }
        .ts-wrapper { position: relative !important; width: 100% !important; }
        .ts-control {
            border: 1px solid var(--input) !important;
            border-radius: calc(var(--radius) - 2px) !important;
            padding: 0.5rem 0.75rem !important;
            font-size: 0.875rem !important;
            background-color: var(--background) !important;
            color: var(--foreground) !important;
            box-shadow: none !important;
        }
        .ts-control:focus, .ts-wrapper.focus .ts-control {
            border-color: var(--foreground) !important;
            box-shadow: 0 0 0 1px var(--foreground) !important;
        }
        .ts-dropdown {
            border: 1px solid var(--border) !important;
            border-radius: var(--radius) !important;
            background-color: var(--popover) !important;
            color: var(--popover-foreground) !important;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1) !important;
            padding: 4px !important;
        }
        .ts-dropdown .option {
            padding: 6px 10px !important;
            font-size: 0.875rem !important;
            border-radius: calc(var(--radius) - 4px) !important;
            color: var(--popover-foreground) !important;
        }
        .ts-dropdown .option:hover, .ts-dropdown .option.active {
            background-color: var(--accent) !important;
            color: var(--accent-foreground) !important;
        }

        /* ══════════════════════════════════════════════════════════
           FULL CROSS-DEVICE RESPONSIVE ENHANCEMENTS
           ══════════════════════════════════════════════════════════ */
        @media (max-width: 991.98px) {
            .app-sidebar-header {
                padding: 0 0.85rem;
                height: 3.5rem;
                min-height: 3.5rem;
            }
            .sidebar-inset-container {
                margin: 0 !important;
                border-radius: 0 !important;
                border: none !important;
                min-height: var(--app-vh, 100vh) !important;
                width: 100% !important;
                box-shadow: none !important;
            }
            .app-content-body {
                padding: 1rem 0.85rem !important;
            }
        }

        @media (max-width: 767.98px) {
            .app-sidebar-header {
                padding: 0 0.75rem;
            }
            .app-content-body {
                padding: 0.75rem 0.5rem !important;
            }
            .card-header {
                padding: 0.75rem 1rem !important;
            }
            .card-body {
                padding: 1rem 0.85rem !important;
            }
            .table th, .table td {
                padding: 0.65rem 0.75rem !important;
                font-size: 0.8125rem !important;
            }
            .filter-bar {
                padding: 0.85rem !important;
            }
            .modal-dialog {
                margin: 0.5rem auto !important;
                max-width: 96vw !important;
            }
            .modal-body {
                padding: 1rem !important;
            }
            .modal-header, .modal-footer {
                padding: 0.75rem 1rem !important;
            }
            .btn {
                font-size: 0.8125rem !important;
            }
        }

        @media (max-width: 575.98px) {
            .app-content-body {
                padding: 0.5rem 0.35rem !important;
            }
            .card {
                border-radius: calc(var(--radius) - 2px) !important;
                margin-bottom: 0.85rem !important;
            }
            .filter-bar .row > div {
                margin-bottom: 0.4rem;
            }
        }

        /* ══════════════════════════════════════════════════════════
           TOP LOADING PROGRESS BAR & PULSE SPINNER (ASYNC & NAV)
           ══════════════════════════════════════════════════════════ */
        #topLoadingLine {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: linear-gradient(90deg, #D97706 0%, #F59E0B 50%, #FEF3C7 100%);
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.8), 0 0 5px rgba(217, 119, 6, 0.6);
            z-index: 9999999;
            transition: width 0.22s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.28s ease;
            opacity: 0;
            pointer-events: none;
        }
    </style>

    @yield('styles')
</head>
<body>

    <!-- Top Loading Progress Bar for Async Operations -->
    <div id="topLoadingLine" class="top-loading-line" role="progressbar" aria-valuemin="0" aria-valuemax="100"></div>

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="sidebarBackdrop" class="sidebar-backdrop"></div>

    <!-- Master Inset AppShell Container -->
    <div class="app-shell" id="appShell">
        
        <!-- ══════════════════════════════════════════════════════════
             FULL-HEIGHT LEFT SIDEBAR (MATCHING COMPANYSYSTEM)
             ══════════════════════════════════════════════════════════ -->
        <aside class="kaldis-sidebar" id="kaldisSidebar">
            <div class="sidebar-inner-wrapper">
                
                <!-- Sidebar Header / Brand Logo -->
                <div class="sidebar-brand-header">
                    <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2.5 text-decoration-none w-100 overflow-hidden">
                        <div class="sidebar-brand-logo-badge">
                            <img src="{{ asset('assets/images/logo.png') }}" alt="Kaldis" class="sidebar-brand-logo-img" onerror="this.src='{{ asset('images/logo.png') }}'">
                        </div>
                        <div class="d-flex flex-column min-w-0">
                            <h1 class="sidebar-brand-title">Kaldis Coffee</h1>
                            <span class="sidebar-brand-subtitle">Planning Portal</span>
                        </div>
                    </a>
                    <!-- Mobile Close Button -->
                    <button class="btn btn-sm btn-link text-muted p-1 border-0 d-lg-none ms-auto" id="sidebarMobileClose" type="button" aria-label="Close Sidebar">
                        <i class="fas fa-xmark fs-5"></i>
                    </button>
                </div>

                <!-- Sidebar Navigation (Collapsible Accordion Groups) -->
                <div class="sidebar-nav-scroll custom-scrollbar" id="sidebarNavScroll">
                    
                    <!-- 1. Dashboards Group -->
                    <div class="nav-group-section">
                        @php
                            $isDashActive = true; // Expanded by default
                        @endphp
                        <button class="nav-group-trigger" type="button" data-group="dashboards" aria-expanded="{{ $isDashActive ? 'true' : 'false' }}">
                            <span class="group-left">
                                <i class="fas fa-layer-group"></i>
                                <span class="nav-label">Dashboards</span>
                            </span>
                            <i class="fas fa-chevron-right group-chevron"></i>
                        </button>
                        <div class="nav-group-items {{ $isDashActive ? '' : 'd-none' }}" id="nav-group-dashboards">
                            <a href="{{ route('overview') }}" class="nav-sub-item {{ (request()->routeIs('dashboard') || request()->routeIs('overview')) ? 'active' : '' }}" title="Overview">
                                <i class="fas fa-house"></i>
                                <span>Overview</span>
                            </a>
                            @if(auth()->user()->isGM() || auth()->user()->isSuperAdmin())
                                <a href="{{ route('dashboard.gm') }}" class="nav-sub-item {{ request()->routeIs('dashboard.gm') ? 'active' : '' }}" title="GM Executive Dashboard">
                                    <i class="fas fa-chart-line"></i>
                                    <span>GM Dashboard</span>
                                </a>
                            @endif
                            @if(auth()->user()->department_id || auth()->user()->canAccessAllDepartments())
                                <a href="{{ route('dashboard.department') }}" class="nav-sub-item {{ request()->routeIs('dashboard.department') ? 'active' : '' }}" title="Department Workspace">
                                    <i class="fas fa-building-user"></i>
                                    <span>Department Workspace</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- 2. Strategic Alignment Group -->
                    <div class="nav-group-section">
                        @php
                            $isStratActive = false;
                        @endphp
                        <button class="nav-group-trigger" type="button" data-group="strategy" aria-expanded="{{ $isStratActive ? 'true' : 'false' }}">
                            <span class="group-left">
                                <i class="fas fa-bullseye"></i>
                                <span class="nav-label">Strategic Alignment</span>
                            </span>
                            <i class="fas fa-chevron-right group-chevron"></i>
                        </button>
                        <div class="nav-group-items {{ $isStratActive ? '' : 'd-none' }}" id="nav-group-strategy">
                            <a href="{{ route('strategy.annual') }}" class="nav-sub-item {{ request()->routeIs('strategy.annual') ? 'active' : '' }}" title="Annual Strategy Plan">
                                <i class="fas fa-crosshairs"></i>
                                <span>Annual Strategy Plan</span>
                            </a>
                            @if(auth()->user()->isGM() || auth()->user()->isSuperAdmin())
                                <a href="{{ route('strategy.activation') }}" class="nav-sub-item {{ request()->routeIs('strategy.activation') ? 'active' : '' }}" title="GM Monthly Activation">
                                    <i class="fas fa-toggle-on"></i>
                                    <span>GM Monthly Activation</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- 3. Operational Planning Group -->
                    <div class="nav-group-section">
                        @php
                            $isPlanActive = false;
                        @endphp
                        <button class="nav-group-trigger" type="button" data-group="planning" aria-expanded="{{ $isPlanActive ? 'true' : 'false' }}">
                            <span class="group-left">
                                <i class="fas fa-calendar-days"></i>
                                <span class="nav-label">Operational Planning</span>
                            </span>
                            <i class="fas fa-chevron-right group-chevron"></i>
                        </button>
                        <div class="nav-group-items {{ $isPlanActive ? '' : 'd-none' }}" id="nav-group-planning">
                            <a href="{{ route('monthly.plans') }}" class="nav-sub-item {{ request()->routeIs('monthly.plans*') ? 'active' : '' }}" title="Monthly Plans">
                                <i class="fas fa-calendar"></i>
                                <span>Monthly Plans</span>
                            </a>
                            <a href="{{ route('weekly.plans') }}" class="nav-sub-item {{ request()->routeIs('weekly.plans*') ? 'active' : '' }}" title="Weekly Plans">
                                <i class="fas fa-list-check"></i>
                                <span>Weekly Plans</span>
                            </a>
                        </div>
                    </div>

                    <!-- 4. Execution & Performance Group -->
                    <div class="nav-group-section">
                        @php
                            $isPerfActive = false;
                        @endphp
                        <button class="nav-group-trigger" type="button" data-group="performance" aria-expanded="{{ $isPerfActive ? 'true' : 'false' }}">
                            <span class="group-left">
                                <i class="fas fa-chart-simple"></i>
                                <span class="nav-label">Execution &amp; Performance</span>
                            </span>
                            <i class="fas fa-chevron-right group-chevron"></i>
                        </button>
                        <div class="nav-group-items {{ $isPerfActive ? '' : 'd-none' }}" id="nav-group-performance">
                            <a href="{{ route('weekly.performance') }}" class="nav-sub-item {{ request()->routeIs('weekly.performance*') ? 'active' : '' }}" title="Weekly Performance">
                                <i class="fas fa-clipboard-check"></i>
                                <span>Weekly Performance</span>
                            </a>
                            <a href="{{ route('performance.weekly') }}" class="nav-sub-item {{ request()->routeIs('performance.weekly*') ? 'active' : '' }}" title="Weekly Scorecard">
                                <i class="fas fa-table"></i>
                                <span>Weekly Scorecard</span>
                            </a>
                            <a href="{{ route('performance.monthly') }}" class="nav-sub-item {{ request()->routeIs('performance.monthly*') ? 'active' : '' }}" title="Monthly Rollup">
                                <i class="fas fa-calendar-check"></i>
                                <span>Monthly Rollup</span>
                            </a>
                            <a href="{{ route('performance.not_done') }}" class="nav-sub-item {{ request()->routeIs('performance.not_done*') ? 'active' : '' }}" title="Why Not Done Analysis">
                                <i class="fas fa-circle-exclamation"></i>
                                <span>Why Not Done</span>
                            </a>
                            <a href="{{ route('performance.achievements') }}" class="nav-sub-item {{ request()->routeIs('performance.achievements*') ? 'active' : '' }}" title="Achievements Feed">
                                <i class="fas fa-award"></i>
                                <span>Achievements Feed</span>
                            </a>
                            <a href="{{ route('performance.challenges') }}" class="nav-sub-item {{ request()->routeIs('performance.challenges*') ? 'active' : '' }}" title="Challenges Tracker">
                                <i class="fas fa-triangle-exclamation"></i>
                                <span>Challenges Tracker</span>
                            </a>
                            <a href="{{ route('performance.quarterly') }}" class="nav-sub-item {{ request()->routeIs('performance.quarterly*') ? 'active' : '' }}" title="Quarterly Performance">
                                <i class="fas fa-chart-pie"></i>
                                <span>Quarterly Performance</span>
                            </a>
                        </div>
                    </div>

                    <!-- 5. Reports & Governance Group -->
                    @if(auth()->user()->isSuperAdmin() || auth()->user()->isGM() || auth()->user()->isHROfficer())
                        <div class="nav-group-section">
                            @php
                                $isReportsActive = false;
                            @endphp
                            <button class="nav-group-trigger" type="button" data-group="reports" aria-expanded="{{ $isReportsActive ? 'true' : 'false' }}">
                                <span class="group-left">
                                    <i class="fas fa-file-lines"></i>
                                    <span class="nav-label">Governance &amp; Reports</span>
                                </span>
                                <i class="fas fa-chevron-right group-chevron"></i>
                            </button>
                            <div class="nav-group-items {{ $isReportsActive ? '' : 'd-none' }}" id="nav-group-reports">
                                <a href="{{ route('admin.reports') }}" class="nav-sub-item {{ request()->routeIs('admin.reports*') ? 'active' : '' }}" title="Reports &amp; Tracking">
                                    <i class="fas fa-chart-column"></i>
                                    <span>Reports &amp; Tracking</span>
                                </a>
                            </div>
                        </div>
                    @endif

                    <!-- 6. System Administration Group (Super Admin) -->
                    @if(auth()->user()->isSuperAdmin())
                        <div class="nav-group-section">
                            @php
                                $isAdminActive = false;
                            @endphp
                            <button class="nav-group-trigger" type="button" data-group="admin" aria-expanded="{{ $isAdminActive ? 'true' : 'false' }}">
                                <span class="group-left">
                                    <i class="fas fa-gear"></i>
                                    <span class="nav-label">System Administration</span>
                                </span>
                                <i class="fas fa-chevron-right group-chevron"></i>
                            </button>
                            <div class="nav-group-items {{ $isAdminActive ? '' : 'd-none' }}" id="nav-group-admin">
                                <a href="{{ route('admin.dashboard') }}" class="nav-sub-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" title="Admin Overview">
                                    <i class="fas fa-gauge-high"></i>
                                    <span>Admin Overview</span>
                                </a>
                                <a href="{{ route('admin.users') }}" class="nav-sub-item {{ request()->routeIs('admin.users*') ? 'active' : '' }}" title="User Accounts">
                                    <i class="fas fa-users-gear"></i>
                                    <span>User Accounts</span>
                                </a>
                                <a href="{{ route('admin.departments') }}" class="nav-sub-item {{ request()->routeIs('admin.departments*') ? 'active' : '' }}" title="Departments">
                                    <i class="fas fa-sitemap"></i>
                                    <span>Departments</span>
                                </a>
                                <a href="{{ route('admin.settings') }}" class="nav-sub-item {{ request()->routeIs('admin.settings*') ? 'active' : '' }}" title="System Settings">
                                    <i class="fas fa-sliders"></i>
                                    <span>System Settings</span>
                                </a>
                                <a href="{{ route('telegram.status') }}" class="nav-sub-item {{ request()->routeIs('telegram.*') ? 'active' : '' }}" title="Telegram Bot &amp; Webhook">
                                    <i class="fab fa-telegram"></i>
                                    <span>Telegram &amp; Bot</span>
                                </a>
                                <a href="{{ route('admin.audit') }}" class="nav-sub-item {{ request()->routeIs('admin.audit*') ? 'active' : '' }}" title="Audit Trail">
                                    <i class="fas fa-fingerprint"></i>
                                    <span>Audit Trail</span>
                                </a>
                            </div>
                        </div>
                    @endif

                </div>

                <!-- Sidebar Footer (Same size and height as .app-footer: 3.125rem / 50px) -->
                <div class="sidebar-footer">
                    <div class="dropup w-100">
                        <button class="nav-user-btn" id="sidebarAccountDropdownBtn" type="button" data-bs-toggle="dropdown" data-bs-auto-close="true" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Account Menu">
                            <div class="nav-user-avatar">
                                {{ strtoupper(substr(auth()->user()->full_name ?? auth()->user()->username ?? 'U', 0, 2)) }}
                            </div>
                            <div class="nav-user-info">
                                <span class="nav-user-name">{{ auth()->user()->full_name ?? auth()->user()->username }}</span>
                                <span class="nav-user-role">{{ auth()->user()->role->display_name ?? auth()->user()->role->name ?? 'User' }}</span>
                            </div>
                            <i class="fas fa-up-down text-muted ms-auto fa-up-down" style="font-size: 0.72rem;"></i>
                        </button>
                        <ul class="dropdown-menu shadow-lg border border-light-subtle py-1 mb-2" style="border-radius: var(--radius); min-width: 240px; z-index: 1060;">
                            <li class="px-3 py-2.5 border-bottom border-light-subtle bg-light bg-opacity-50">
                                <div class="fw-bold text-dark text-truncate" style="font-size: 0.85rem;">{{ auth()->user()->full_name ?? auth()->user()->username }}</div>
                                <div class="small text-muted text-truncate font-monospace" style="font-size: 0.72rem;">{{ auth()->user()->email ?? auth()->user()->username }}</div>
                                @if(auth()->user()->department)
                                    <div class="badge bg-secondary-subtle text-secondary border mt-1.5" style="font-size: 0.68rem;">
                                        {{ auth()->user()->department->department_name }}
                                    </div>
                                @endif
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 small py-2" href="{{ route('password.change') }}" data-bs-toggle="modal" data-bs-target="#quickChangePasswordModal">
                                    <i class="fas fa-key text-muted" style="width: 16px;"></i>
                                    <span>Change Password</span>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1 border-light-subtle"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item d-flex align-items-center gap-2 small py-2 text-danger">
                                        <i class="fas fa-arrow-right-from-bracket" style="width: 16px;"></i>
                                        <span>Log Out</span>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </aside>

        <!-- ══════════════════════════════════════════════════════════
             RIGHT INSET CONTENT CONTAINER (MATCHING COMPANYSYSTEM)
             ══════════════════════════════════════════════════════════ -->
        <main class="sidebar-inset-container" id="sidebarInsetContainer">
            
            <!-- Inset Header (Sidebar Trigger + Breadcrumbs + Utilities) -->
            <header class="app-sidebar-header">
                <!-- Left: Sidebar Trigger & Breadcrumbs -->
                <div class="d-flex align-items-center gap-3">
                    <button class="sidebar-trigger-btn sidebar-toggle-btn" id="sidebarToggle" type="button" title="Toggle Sidebar (Alt+S)" aria-label="Toggle Sidebar">
                        <i class="fas fa-bars-staggered"></i>
                    </button>

                    <!-- Dynamic Breadcrumbs Hierarchy matching CompanySystem -->
                    @php
                        $routeName = request()->route() ? request()->route()->getName() : '';
                        $sectionTitle = 'Dashboard';
                        $pageTitle = trim($__env->yieldContent('title'));

                        if (str_starts_with($routeName, 'strategy.')) {
                            $sectionTitle = 'Strategic Alignment';
                        } elseif (str_starts_with($routeName, 'monthly.') || str_starts_with($routeName, 'weekly.plans')) {
                            $sectionTitle = 'Operational Planning';
                        } elseif (str_starts_with($routeName, 'weekly.performance') || str_starts_with($routeName, 'performance.')) {
                            $sectionTitle = 'Execution & Performance';
                        } elseif (str_starts_with($routeName, 'admin.reports')) {
                            $sectionTitle = 'Governance & Reports';
                        } elseif (str_starts_with($routeName, 'admin.') || str_starts_with($routeName, 'telegram.')) {
                            $sectionTitle = 'System Administration';
                        } elseif (str_starts_with($routeName, 'management.')) {
                            $sectionTitle = 'Management';
                        } elseif (str_starts_with($routeName, 'department.')) {
                            $sectionTitle = 'Department Workspace';
                        }
                    @endphp

                    <div class="breadcrumbs-container d-none d-sm-flex align-items-center">
                        <a href="{{ route('dashboard') }}" class="breadcrumb-link">Home</a>
                        <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
                        <span class="breadcrumb-link">{{ $sectionTitle }}</span>
                        @if($pageTitle && $pageTitle !== 'Dashboard' && $pageTitle !== $sectionTitle)
                            <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
                            <span class="breadcrumb-current">{{ Str::limit($pageTitle, 32) }}</span>
                        @endif
                    </div>
                </div>

                <!-- Right: Ethiopian Calendar & Time Utilities -->
                <div class="d-flex align-items-center gap-2 gap-md-2.5 ms-auto">
                    <span class="header-util-badge d-inline-flex" title="Ethiopian Date">
                        <i class="far fa-calendar-alt text-warning"></i>
                        <span>{{ format_et_calendar() }}</span>
                    </span>
                    <span class="header-util-badge d-none d-lg-inline-flex" title="East Africa Time">
                        <i class="far fa-clock text-warning"></i>
                        <span id="liveEthiopianTime">{{ format_ethiopian_time() }}</span>
                    </span>
                </div>
            </header>

            <!-- Main Content Viewport -->
            <div class="app-content-body flex-grow-1">
                <!-- Flash Alerts -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show shadow-xs mb-4 border-0 d-flex align-items-center gap-2" role="alert" style="background-color: rgba(16, 185, 129, 0.1); color: #047857; border-radius: 8px;">
                        <i class="fas fa-circle-check flex-shrink-0"></i>
                        <div class="flex-grow-1">{{ session('success') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="font-size: 0.75rem;"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show shadow-xs mb-4 border-0 d-flex align-items-center gap-2" role="alert" style="background-color: rgba(239, 68, 68, 0.1); color: #DC2626; border-radius: 8px;">
                        <i class="fas fa-circle-exclamation flex-shrink-0"></i>
                        <div class="flex-grow-1">{{ session('error') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="font-size: 0.75rem;"></button>
                    </div>
                @endif

                @if(isset($errors) && $errors->any())
                    <div class="alert alert-danger alert-dismissible fade show shadow-xs mb-4 border-0" role="alert" style="background-color: rgba(239, 68, 68, 0.1); color: #DC2626; border-radius: 8px;">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="fas fa-triangle-exclamation flex-shrink-0"></i>
                            <strong>Please check the following errors:</strong>
                        </div>
                        <ul class="mb-0 mt-1 small ps-4">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="font-size: 0.75rem;"></button>
                    </div>
                @endif

                @yield('content')
            </div>

            <!-- Inset App Footer (Same size & height as sidebar-footer: 3.125rem / 50px) -->
            <footer class="app-footer">
                <div>
                    &copy; {{ date('Y') }} Kaldis Coffee PLC. All rights reserved.
                </div>
                <div class="text-muted small">
                    Enterprise Strategy &amp; Performance &bull; Developed by <strong>IT Department</strong>
                </div>
            </footer>

        </main>
    </div>

    <!-- Sonner-Style Toast Container -->
    <div id="reactToastContainer" class="react-toast-container"></div>

    <!-- Quick Change Password Modal -->
    <div class="modal fade" id="quickChangePasswordModal" tabindex="-1" aria-labelledby="quickChangePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
            <div class="modal-content border border-border shadow-lg" style="border-radius: var(--radius); background-color: var(--card); color: var(--card-foreground);">
                <div class="modal-header border-bottom border-border px-4 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="p-2 bg-secondary text-foreground rounded-circle d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                            <i class="fas fa-key fs-6"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-semibold text-foreground fs-6 mb-0" id="quickChangePasswordModalLabel">Change Password</h5>
                            <small class="text-muted">User: <strong>{{ auth()->user()->username ?? '' }}</strong></small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('password.update') }}" id="modalChangePasswordForm">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="modal_current_password" class="form-label fw-medium small">
                                Current Password <span class="text-danger">*</span>
                            </label>
                            <div class="position-relative">
                                <input type="password" id="modal_current_password" name="current_password" class="form-control pe-5" required placeholder="Enter current password" autocomplete="current-password">
                                <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-muted text-decoration-none pe-3 py-0 border-0 toggle-modal-pwd" data-target="modal_current_password" tabindex="-1">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="modal_new_password" class="form-label fw-medium small">
                                New Password <span class="text-danger">*</span>
                            </label>
                            <div class="position-relative">
                                <input type="password" id="modal_new_password" name="new_password" class="form-control pe-5" minlength="6" required placeholder="Enter new password (min. 6 chars)" autocomplete="new-password">
                                <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-muted text-decoration-none pe-3 py-0 border-0 toggle-modal-pwd" data-target="modal_new_password" tabindex="-1">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text small" style="font-size: 0.75rem;">Minimum 6 characters; different from current.</div>
                        </div>

                        <div class="mb-2">
                            <label for="modal_new_password_confirmation" class="form-label fw-medium small">
                                Confirm New Password <span class="text-danger">*</span>
                            </label>
                            <div class="position-relative">
                                <input type="password" id="modal_new_password_confirmation" name="new_password_confirmation" class="form-control pe-5" required placeholder="Re-enter new password" autocomplete="new-password">
                                <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-muted text-decoration-none pe-3 py-0 border-0 toggle-modal-pwd" data-target="modal_new_password_confirmation" tabindex="-1">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                            <div id="modalPasswordMatchHint" class="form-text small d-none" style="font-size: 0.75rem;"></div>
                        </div>
                    </div>
                    <div class="modal-footer border-top border-border px-4 py-3 d-flex justify-content-between align-items-center">
                        <a href="{{ route('password.change') }}" class="small text-decoration-none text-muted">
                            <i class="fas fa-arrow-up-right-from-square me-1"></i> Full page view
                        </a>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm px-3 fw-medium">
                                <i class="fas fa-floppy-disk me-1"></i> Update Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts: jQuery, DataTables, Bootstrap, Chart.js, TomSelect -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/js/tom-select.complete.min.js"></script>

    <!-- Core App JS -->
    <script src="{{ asset('assets/js/app.js') }}?v=3.0.0"></script>

    <!-- Enhanced Toast Notification System (Sonner Style) -->
    <script>
        window.Toast = {
            show(type, title, message, duration = 4500) {
                const container = document.getElementById('reactToastContainer');
                if (!container) return;

                const activeToasts = container.querySelectorAll('.react-toast:not(.toast-hiding)');
                if (activeToasts.length >= 4) {
                    activeToasts[0].classList.add('toast-hiding');
                    setTimeout(() => activeToasts[0]?.remove(), 220);
                }

                const normalizedType = ['success', 'warning', 'error', 'danger', 'info'].includes(type) ? (type === 'danger' ? 'error' : type) : 'info';
                const iconMap = {
                    success: 'fa-circle-check',
                    warning: 'fa-triangle-exclamation',
                    error: 'fa-circle-xmark',
                    info: 'fa-circle-info'
                };

                const toast = document.createElement('div');
                toast.className = `react-toast toast-${normalizedType}`;
                toast.setAttribute('role', 'alert');

                toast.innerHTML = `
                    <div class="react-toast-icon-wrap">
                        <i class="fa-solid ${iconMap[normalizedType]}"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-0.5">
                            <strong class="small fw-semibold mb-0" style="color: var(--foreground); font-size: 0.825rem;">${title}</strong>
                            <small class="text-muted" style="font-size: 0.65rem;">Now</small>
                        </div>
                        <div class="small text-muted text-break" style="line-height: 1.4; font-size: 0.775rem;">${message}</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-link text-muted p-0 border-0 ms-1" style="font-size: 0.85rem; opacity: 0.6;" title="Dismiss" onclick="this.closest('.react-toast').classList.add('toast-hiding'); setTimeout(() => this.closest('.react-toast')?.remove(), 220);">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    <div class="react-toast-progress" style="animation-duration: ${duration}ms;"></div>
                `;

                container.appendChild(toast);

                setTimeout(() => {
                    if (toast.parentElement) {
                        toast.classList.add('toast-hiding');
                        setTimeout(() => toast.remove(), 220);
                    }
                }, duration);
            },
            success(message, title = 'Success', duration = 4500) { this.show('success', title, message, duration); },
            error(message, title = 'Error', duration = 6000) { this.show('error', title, message, duration); },
            warning(message, title = 'Attention', duration = 5000) { this.show('warning', title, message, duration); },
            info(message, title = 'Notice', duration = 4500) { this.show('info', title, message, duration); }
        };

        window.showToast = function(type, message, title = '', duration = 4500) {
            const mappedTitle = title || (type.charAt(0).toUpperCase() + type.slice(1));
            window.Toast.show(type, mappedTitle, message, duration);
        };

        document.addEventListener('DOMContentLoaded', () => {
            @if(session('success'))
                window.Toast.success("{!! addslashes(session('success')) !!}");
            @endif
            @if(session('warning'))
                window.Toast.warning("{!! addslashes(session('warning')) !!}");
            @endif
            @if(session('error'))
                window.Toast.error("{!! addslashes(session('error')) !!}");
            @endif
            @if(session('info'))
                window.Toast.info("{!! addslashes(session('info')) !!}");
            @endif
        });
    </script>

    <!-- Sidebar Accordion Persistence & Interaction Controller -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Section Accordion Toggles (By Default Collapse All Navigations Except Dashboard)
            document.querySelectorAll('.nav-group-trigger').forEach(function(trigger) {
                const groupKey = trigger.getAttribute('data-group');
                const itemsContainer = document.getElementById('nav-group-' + groupKey);
                if (!itemsContainer) return;

                if (groupKey === 'dashboards') {
                    // Dashboards is expanded by default
                    const savedDashState = localStorage.getItem('sidebar-group-dashboards');
                    if (savedDashState === 'collapsed') {
                        itemsContainer.classList.add('d-none');
                        trigger.setAttribute('aria-expanded', 'false');
                    } else {
                        itemsContainer.classList.remove('d-none');
                        trigger.setAttribute('aria-expanded', 'true');
                    }
                } else {
                    // All other groups are collapsed by default on page load
                    itemsContainer.classList.add('d-none');
                    trigger.setAttribute('aria-expanded', 'false');
                    try { localStorage.removeItem('sidebar-group-' + groupKey); } catch (e) {}
                }

                trigger.addEventListener('click', function(e) {
                    e.preventDefault();
                    const isExpanded = trigger.getAttribute('aria-expanded') === 'true';
                    if (isExpanded) {
                        itemsContainer.classList.add('d-none');
                        trigger.setAttribute('aria-expanded', 'false');
                        if (groupKey === 'dashboards') {
                            localStorage.setItem('sidebar-group-dashboards', 'collapsed');
                        }
                    } else {
                        itemsContainer.classList.remove('d-none');
                        trigger.setAttribute('aria-expanded', 'true');
                        if (groupKey === 'dashboards') {
                            localStorage.setItem('sidebar-group-dashboards', 'expanded');
                        }
                    }
                });
            });

            // Password visibility toggles in modal
            document.querySelectorAll('.toggle-modal-pwd').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const targetId = this.getAttribute('data-target');
                    const input = document.getElementById(targetId);
                    const icon = this.querySelector('i');
                    if (input && icon) {
                        if (input.type === 'password') {
                            input.type = 'text';
                            icon.classList.remove('fa-eye');
                            icon.classList.add('fa-eye-slash');
                        } else {
                            input.type = 'password';
                            icon.classList.remove('fa-eye-slash');
                            icon.classList.add('fa-eye');
                        }
                    }
                });
            });

            // Password match live verification in modal
            const mNew = document.getElementById('modal_new_password');
            const mConf = document.getElementById('modal_new_password_confirmation');
            const mHint = document.getElementById('modalPasswordMatchHint');
            if (mNew && mConf && mHint) {
                function checkModalMatch() {
                    if (!mConf.value) {
                        mHint.classList.add('d-none');
                        return;
                    }
                    mHint.classList.remove('d-none');
                    if (mNew.value === mConf.value) {
                        mHint.innerHTML = '<span class="text-success small"><i class="fas fa-check-circle me-1"></i> Passwords match</span>';
                    } else {
                        mHint.innerHTML = '<span class="text-danger small"><i class="fas fa-triangle-exclamation me-1"></i> Passwords do not match</span>';
                    }
                }
                mNew.addEventListener('input', checkModalMatch);
                mConf.addEventListener('input', checkModalMatch);
            }

            // Initialize Sidebar Account Dropdown with fixed Popper positioning
            const sidebarAccountBtn = document.getElementById('sidebarAccountDropdownBtn');
            if (sidebarAccountBtn && window.bootstrap && window.bootstrap.Dropdown) {
                bootstrap.Dropdown.getOrCreateInstance(sidebarAccountBtn, {
                    popperConfig: function(defaultBsPopperConfig) {
                        return {
                            ...defaultBsPopperConfig,
                            strategy: 'fixed'
                        };
                    }
                });
            }
        });
    </script>

    @yield('scripts')

    <!-- Live East Africa / Ethiopian Time Clock Controller -->
    <script>
        (function() {
            function updateLiveClock() {
                const el = document.getElementById('liveEthiopianTime');
                if (!el) return;
                try {
                    const now = new Date();
                    const options = {
                        timeZone: 'Africa/Addis_Ababa',
                        hour: 'numeric',
                        minute: 'numeric',
                        second: 'numeric',
                        hour12: false
                    };
                    const parts = new Intl.DateTimeFormat('en-US', options).formatToParts(now);
                    let h24 = 0, m = 0, s = 0;
                    for (const p of parts) {
                        if (p.type === 'hour') h24 = parseInt(p.value, 10);
                        if (p.type === 'minute') m = parseInt(p.value, 10);
                        if (p.type === 'second') s = parseInt(p.value, 10);
                    }
                    let ethHour = h24 - 6;
                    if (ethHour < 0) ethHour += 12;
                    else if (ethHour >= 12) ethHour -= 12;
                    ethHour = (ethHour % 12) || 12;

                    let periodAm = 'ሌሊት';
                    if (h24 >= 6 && h24 <= 11) periodAm = 'ጠዋት';
                    else if (h24 >= 12 && h24 <= 17) periodAm = 'ከሰዓት';
                    else if (h24 >= 18 && h24 <= 23) periodAm = 'ምሽት';

                    const pad = (n) => String(n).padStart(2, '0');
                    el.textContent = `${pad(ethHour)}:${pad(m)}:${pad(s)} ${periodAm}`;
                } catch (e) {
                    // Fallback to local time if timezone not supported
                }
            }
            document.addEventListener('DOMContentLoaded', function() {
                updateLiveClock();
                setInterval(updateLiveClock, 1000);
            });
        })();
    </script>

</body>
</html>
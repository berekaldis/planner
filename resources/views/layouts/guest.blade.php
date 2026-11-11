<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sign In') | {{ config('kaldis.app_name', 'Kaldis Planning Portal') }}</title>
    
    <!-- Google Fonts: Instrument Sans (Matching CompanySystem) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 & FontAwesome 6 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=3">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=3">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v=3">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v=3">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v=3">

    <style>
        :root {
            --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --background: #FFFFFF;
            --foreground: #18181B;
            --card: #FFFFFF;
            --card-foreground: #18181B;
            --muted: #F4F4F5;
            --muted-foreground: #71717A;
            --primary: #18181B;
            --primary-foreground: #FAFAFA;
            --border: #E4E4E7;
            --input: #E4E4E7;
            --ring: #D4D4D8;
            --radius: 0.625rem;
            --app-vh: calc(100vh / 0.8);
        }

        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; width: 100%; min-height: var(--app-vh, 100vh); }
        html { scroll-behavior: smooth; zoom: 0.8; }

        body {
            font-family: var(--font-sans);
            min-height: var(--app-vh, 100vh);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem;
            background-color: var(--muted);
            color: var(--foreground);
            -webkit-font-smoothing: antialiased;
        }

        .auth-container {
            width: 100%;
            max-width: 420px;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .auth-logo-badge {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            background: #FFFFFF;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            transition: transform 0.2s ease;
        }
        .auth-logo-badge:hover {
            transform: scale(1.03);
        }

        .auth-logo-img {
            width: 78px;
            height: 78px;
            object-fit: contain;
        }

        .auth-card {
            background-color: var(--card);
            color: var(--card-foreground);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .auth-card-header {
            padding: 2rem 2rem 0.5rem 2rem;
            text-align: center;
        }

        .auth-card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--foreground);
            letter-spacing: -0.015em;
            margin-bottom: 0.35rem;
        }

        .auth-card-description {
            font-size: 0.875rem;
            color: var(--muted-foreground);
            margin-bottom: 0;
        }

        .auth-card-body {
            padding: 1.5rem 2rem 2rem 2rem;
        }

        .form-label {
            color: var(--foreground);
            font-weight: 500;
            font-size: 0.875rem;
            margin-bottom: 0.35rem;
        }

        .form-control {
            background-color: var(--background);
            border: 1px solid var(--input);
            color: var(--foreground);
            border-radius: calc(var(--radius) - 2px);
            padding: 0.55rem 0.85rem;
            font-size: 0.875rem;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .form-control:focus {
            background-color: var(--background);
            border-color: var(--foreground);
            color: var(--foreground);
            box-shadow: 0 0 0 1px var(--foreground);
        }

        .btn-auth-submit {
            background-color: var(--primary);
            color: var(--primary-foreground);
            font-family: var(--font-sans);
            font-weight: 500;
            font-size: 0.875rem;
            border: 1px solid transparent;
            padding: 0.6rem 1.25rem;
            border-radius: calc(var(--radius) - 2px);
            transition: opacity 0.15s ease, background-color 0.15s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .btn-auth-submit:hover {
            opacity: 0.92;
            color: var(--primary-foreground);
        }

        .form-check-input {
            border-color: var(--border);
            border-radius: 4px;
        }
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .auth-footer {
            text-align: center;
            font-size: 0.75rem;
            color: var(--muted-foreground);
        }
    </style>
</head>
<body>
    @yield('content')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sign In') | {{ config('kaldis.app_name', 'Kaldis Planning Portal') }}</title>
    
    <!-- Google Fonts: Outfit & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 & FontAwesome 6 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">

    <style>
        :root {
            --gold: #D97706;
            --gold-hover: #B45309;
            --gold-light: #F59E0B;
            --gold-soft: rgba(217, 119, 6, 0.08);
            --bg-canvas: #F8FAFC;
            --card-bg: #FFFFFF;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --border-color: #E2E8F0;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; width: 100%; min-height: 100vh; }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background-color: var(--bg-canvas);
            color: var(--text-main);
            position: relative;
            background-image: 
                radial-gradient(ellipse 60% 50% at 50% -10%, rgba(217, 119, 6, 0.08) 0%, transparent 60%),
                radial-gradient(circle at 100% 100%, rgba(245, 158, 11, 0.05) 0%, transparent 40%);
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2.5rem 2.25rem;
            box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.06), 0 4px 6px -2px rgba(15, 23, 42, 0.03);
            animation: cardFadeIn 0.4s ease-out;
        }

        @keyframes cardFadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-logo {
            width: 82px;
            height: 82px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--gold);
            padding: 2px;
            background: #FFFFFF;
            box-shadow: 0 6px 16px rgba(217, 119, 6, 0.2);
            margin-bottom: 1rem;
        }

        .brand-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.02em;
            font-size: 1.5rem;
        }

        .input-group-text {
            background-color: #F8FAFC !important;
            border: 1.5px solid #CBD5E1 !important;
            border-right: none !important;
            color: var(--text-muted) !important;
            border-radius: 10px 0 0 10px !important;
        }

        .form-control {
            background-color: #FFFFFF !important;
            border: 1.5px solid #CBD5E1 !important;
            color: var(--text-main) !important;
            border-radius: 0 10px 10px 0 !important;
            padding: 0.65rem 0.9rem;
            font-size: 0.92rem;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--gold) !important;
            box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.15) !important;
        }

        .input-group:focus-within .input-group-text {
            border-color: var(--gold) !important;
            color: var(--gold) !important;
            background-color: var(--gold-soft) !important;
        }

        .form-label {
            color: #334155;
            font-weight: 600;
            font-size: 0.8rem;
            margin-bottom: 0.4rem;
        }

        .btn-kaldis-login {
            background: var(--gold);
            color: #FFFFFF;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 0.96rem;
            border: none;
            padding: 0.75rem 1.25rem;
            border-radius: 10px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(217, 119, 6, 0.25);
        }

        .btn-kaldis-login:hover {
            background: var(--gold-hover);
            color: #FFFFFF;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(217, 119, 6, 0.35);
        }

        .demo-pill {
            cursor: pointer;
            transition: all 0.15s ease;
            border: 1px solid #E2E8F0;
            background: #F8FAFC;
            color: #475569;
            font-size: 0.72rem;
            padding: 4px 10px;
            border-radius: 9999px;
            font-weight: 600;
        }

        .demo-pill:hover {
            background: var(--gold-soft);
            border-color: var(--gold);
            color: var(--gold-hover);
        }
    </style>
</head>
<body>
    @yield('content')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>

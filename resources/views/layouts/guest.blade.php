<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sign In') | {{ config('kaldis.app_name') }}</title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 & FontAwesome 6 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --kaldis-primary: #5B1425;
            --kaldis-gold: #D97706;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F8FAFC 0%, #F1F5F9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .login-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -2px rgba(0,0,0,0.05);
            max-width: 440px;
            width: 100%;
            padding: 2.25rem 2rem;
        }

        .login-logo {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--kaldis-gold);
            margin-bottom: 1.25rem;
        }

        .btn-kaldis-login {
            background-color: var(--kaldis-gold);
            color: #FFFFFF;
            font-weight: 600;
            border: none;
            padding: 0.65rem 1rem;
            border-radius: 8px;
            transition: all 0.15s ease;
        }

        .btn-kaldis-login:hover {
            background-color: #B45309;
            color: #FFFFFF;
        }
    </style>
</head>
<body>
    @yield('content')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

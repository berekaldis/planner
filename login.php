<?php
/**
 * Login Controller & View
 * Kaldis Coffee PLC
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

if (Auth::check()) {
    redirect('/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        if (Auth::attempt($username, $password)) {
            $returnUrl = $_SESSION['return_url'] ?? '/overview.php';
            unset($_SESSION['return_url']);
            redirect($returnUrl);
        } else {
            $error = 'Invalid username or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In &mdash; Kaldis Coffee PLC Planning System</title>
    <meta name="description" content="Kaldis Coffee Strategic Planning & Performance Management Portal Login">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= url('/assets/images/logo.png') ?>">
    <link rel="shortcut icon" type="image/png" href="<?= url('/assets/images/logo.png') ?>">
    <style>
        :root {
            --gold:   #D97706;
            --gold-l: #F59E0B;
            --gold-b: #FBBF24;
            --gold-s: rgba(245,158,11,.12);
        }

        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin:0; padding:0; width:100%; min-height:100vh; overflow-x:hidden; }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            background:
                radial-gradient(ellipse 80% 60% at 20% 10%, rgba(146,64,14,.35) 0%, transparent 55%),
                radial-gradient(ellipse 60% 50% at 85% 85%, rgba(30,15,5,.7) 0%, transparent 60%),
                linear-gradient(145deg, #080D18 0%, #0F1520 40%, #1A0C05 80%, #080D18 100%);
        }

        /* Grid overlay */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(245,158,11,.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(245,158,11,.04) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
            z-index: 0;
        }

        /* Glow orb top-right */
        body::after {
            content: '';
            position: fixed;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(217,119,6,.18) 0%, transparent 70%);
            top: -180px; right: -120px;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            animation: orbPulse 8s ease-in-out infinite alternate;
        }
        @keyframes orbPulse {
            from { transform: scale(1); opacity:.7; }
            to   { transform: scale(1.2) translate(25px,15px); opacity:1; }
        }

        .orb-2 {
            position: fixed;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(59,130,246,.1) 0%, transparent 70%);
            bottom: -100px; left: -80px;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            animation: orbPulse 10s ease-in-out 2s infinite alternate-reverse;
        }

        /* ── Login Card ── */
        .login-wrap {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 458px;
            animation: cardEntry .6s cubic-bezier(.34,1.56,.64,1) both;
        }
        @keyframes cardEntry {
            from { opacity:0; transform: translateY(28px) scale(.95); }
            to   { opacity:1; transform: none; }
        }

        .login-card {
            background: rgba(13, 19, 34, 0.84);
            border: 1px solid rgba(245,158,11,.18);
            border-radius: 24px;
            box-shadow:
                0 32px 64px -12px rgba(0,0,0,.7),
                0 0 0 1px rgba(255,255,255,.04) inset,
                0 0 60px rgba(217,119,6,.1);
            backdrop-filter: blur(30px) saturate(1.6);
            -webkit-backdrop-filter: blur(30px) saturate(1.6);
            overflow: hidden;
        }

        /* ── Header ── */
        .login-header {
            padding: 40px 36px 28px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .login-header::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(217,119,6,.1) 0%, transparent 60%);
            pointer-events: none;
        }
        .login-header::after {
            content: '';
            position: absolute;
            bottom: 0; left: 50%;
            transform: translateX(-50%);
            width: 60%; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(245,158,11,.45), transparent);
        }

        .login-logo {
            width: 92px; height: 92px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid var(--gold);
            box-shadow:
                0 0 0 6px rgba(245,158,11,.12),
                0 14px 36px rgba(217,119,6,.4),
                0 0 45px rgba(245,158,11,.22);
            margin-bottom: 16px;
            transition: transform .4s cubic-bezier(.34,1.56,.64,1);
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        .login-logo:hover { transform: scale(1.08) rotate(8deg); }

        .login-brand-name {
            font-family: 'Outfit', sans-serif;
            font-size: 1.3rem;
            font-weight: 900;
            color: #F1F5F9;
            letter-spacing: .7px;
            margin: 0 0 4px;
        }
        .login-brand-tagline {
            font-size: .65rem;
            font-weight: 700;
            color: var(--gold);
            letter-spacing: 2px;
            text-transform: uppercase;
            opacity: .88;
        }

        /* ── Body ── */
        .login-body {
            padding: 28px 36px 32px;
        }

        .login-heading {
            font-family: 'Outfit', sans-serif;
            font-size: 1.45rem;
            font-weight: 800;
            color: #F1F5F9;
            margin-bottom: 2px;
        }
        .login-sub {
            font-size: .82rem;
            color: rgba(148,163,184,.8);
            margin-bottom: 22px;
        }

        /* ── Inputs ── */
        .form-label-custom {
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: 1.3px;
            text-transform: uppercase;
            color: rgba(148,163,184,.75);
            margin-bottom: 5px;
            display: block;
        }
        .input-group-text {
            background: rgba(255,255,255,.05) !important;
            border: 1.5px solid rgba(255,255,255,.1) !important;
            border-right: none !important;
            color: rgba(148,163,184,.7) !important;
            border-radius: 12px 0 0 12px !important;
            padding: .65rem .9rem !important;
        }
        .form-control-custom {
            flex: 1;
            background: rgba(255,255,255,.06) !important;
            border: 1.5px solid rgba(255,255,255,.1) !important;
            border-left: none !important;
            color: #F1F5F9 !important;
            border-radius: 0 12px 12px 0 !important;
            padding: .68rem 1rem !important;
            font-size: .9rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            outline: none;
            transition: all .22s ease;
            width: 100%;
        }
        .form-control-custom::placeholder { color: rgba(100,116,139,.65) !important; }
        .form-control-custom:focus {
            background: rgba(245,158,11,.07) !important;
            border-color: var(--gold) !important;
            box-shadow: 0 0 0 4px rgba(245,158,11,.15), 0 0 18px rgba(245,158,11,.1) !important;
        }
        .input-group-wrap:focus-within .input-group-text {
            border-color: var(--gold) !important;
            color: var(--gold) !important;
            background: rgba(245,158,11,.07) !important;
        }

        /* ── Error Alert ── */
        .login-alert {
            background: rgba(244,63,94,.1);
            border: 1px solid rgba(244,63,94,.28);
            border-left: 3px solid #F43F5E;
            color: #FCA5A5;
            border-radius: 10px;
            padding: .7rem 1rem;
            font-size: .865rem;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        /* ── Sign In Button ── */
        .btn-signin {
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
            color: #FFFFFF;
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: .4px;
            border: none;
            border-radius: 14px;
            padding: .82rem 1.5rem;
            width: 100%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 6px 22px rgba(245,158,11,.4), 0 0 0 1px rgba(251,191,36,.25) inset;
            transition: all .3s cubic-bezier(.34,1.56,.64,1);
            position: relative;
            overflow: hidden;
        }
        .btn-signin::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,.14) 0%, transparent 50%);
        }
        .btn-signin:hover {
            background: linear-gradient(135deg, #FBBF24 0%, #D97706 100%);
            transform: translateY(-3px) scale(1.01);
            box-shadow: 0 14px 35px rgba(245,158,11,.55), 0 0 0 1px rgba(251,191,36,.35) inset;
        }
        .btn-signin:active {
            transform: translateY(-1px) scale(.99);
            box-shadow: 0 5px 14px rgba(245,158,11,.35);
        }
        .btn-signin:disabled { opacity: .7; cursor: not-allowed; transform: none; }

        .login-footer-note {
            text-align: center;
            font-size: .72rem;
            color: rgba(100,116,139,.55);
            margin-top: 16px;
        }

        /* Particles */
        .particles { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(245,158,11,.3);
            animation: floatP linear infinite;
        }
        @keyframes floatP {
            0%   { transform: translateY(0) scale(1); opacity:.25; }
            50%  { transform: translateY(-50vh) translateX(12px) scale(.7); opacity:.55; }
            100% { transform: translateY(-110vh) scale(.3); opacity:0; }
        }
        @keyframes themeIconSpin {
            from { transform: rotate(-180deg) scale(.5); }
            to   { transform: rotate(0) scale(1); }
        }
    </style>
</head>
<body>

<div class="orb-2" aria-hidden="true"></div>
<div class="particles" id="particles" aria-hidden="true"></div>

<div class="login-wrap">
    <div class="login-card">

        <!-- Header -->
        <div class="login-header">
            <img src="<?= url('/assets/images/logo.png') ?>" alt="Kaldis Coffee Logo" class="login-logo">
            <h1 class="login-brand-name">KALDIS COFFEE PLC</h1>
            <p class="login-brand-tagline">Strategic Planning &bull; Execution &bull; Performance</p>
        </div>

        <!-- Body -->
        <div class="login-body">
            <h2 class="login-heading">Welcome back</h2>
            <p class="login-sub">Sign in to access your planning dashboard</p>

            <?php if (!empty($error)): ?>
                <div class="login-alert" role="alert">
                    <i class="fas fa-exclamation-circle flex-shrink-0"></i>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" id="loginForm" novalidate>
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="username" class="form-label-custom">Username or Email</label>
                    <div class="d-flex input-group-wrap">
                        <span class="input-group-text"><i class="fas fa-user-circle"></i></span>
                        <input type="text" class="form-control-custom" id="username" name="username"
                               required autofocus autocomplete="username"
                               placeholder="e.g. admin or your email">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label-custom">Password</label>
                    <div class="d-flex input-group-wrap">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control-custom" id="password" name="password"
                               required autocomplete="current-password"
                               placeholder="Enter your password">
                    </div>
                </div>

                <button type="submit" class="btn-signin" id="signinBtn">
                    <i class="fas fa-arrow-right-to-bracket"></i>
                    Sign In to Portal
                </button>
            </form>

            <p class="login-footer-note">&copy; <?= date('Y') ?> Kaldis Coffee PLC &mdash; IT Department</p>
        </div>

    </div>
</div>

<script>
document.getElementById('loginForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('signinBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In\u2026';
    btn.disabled = true;
});

// Floating particles
(function() {
    const c = document.getElementById('particles');
    if (!c) return;
    for (let i = 0; i < 20; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        const sz  = Math.random() * 4 + 1.5;
        const dur = Math.random() * 14 + 10;
        const del = Math.random() * 12;
        p.style.cssText = `
            width:${sz}px;height:${sz}px;
            left:${Math.random()*100}%;
            bottom:-10px;
            opacity:${Math.random()*.45+.15};
            animation-duration:${dur}s;
            animation-delay:${del}s;
        `;
        c.appendChild(p);
    }
})();
</script>
</body>
</html>


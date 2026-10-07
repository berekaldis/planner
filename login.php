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
            $returnUrl = $_SESSION['return_url'] ?? '/index.php';
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
    <title>Login &mdash; Kaldis Coffee PLC Planning System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= url('/assets/css/style.css') ?>" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0F172A 0%, #1E1B18 50%, #292524 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            position: relative;
            overflow-x: hidden;
        }
        body::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(217, 119, 6, 0.15) 0%, rgba(0, 0, 0, 0) 70%);
            top: -100px;
            right: -100px;
            border-radius: 50%;
            pointer-events: none;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4), 0 0 30px rgba(217, 119, 6, 0.15);
            max-width: 460px;
            width: 100%;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(16px);
            transition: transform 0.3s ease;
        }
        [data-bs-theme="dark"] .login-card {
            background: rgba(21, 29, 42, 0.98);
            border-color: #1E293B;
        }
        .login-header {
            background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
            padding: 38px 28px 28px;
            text-align: center;
            border-bottom: 2px solid #FDE68A;
        }
        [data-bs-theme="dark"] .login-header {
            background: linear-gradient(135deg, #1E293B 0%, #0F172A 100%);
            border-bottom: 2px solid #D97706;
        }
        .login-brand-title {
            color: #1C130D;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        [data-bs-theme="dark"] .login-brand-title {
            color: #F8FAFC;
        }
        .login-brand-subtitle {
            color: #D97706;
            font-weight: 700;
            font-size: 0.72rem;
            letter-spacing: 1.2px;
        }
        .login-body {
            padding: 32px 30px 28px;
        }
        .demo-pill {
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.75rem;
            background: var(--kaldis-card-bg) !important;
            border: 1px solid var(--kaldis-border) !important;
            color: var(--kaldis-text) !important;
            border-radius: 20px !important;
            padding: 0.4rem 0.75rem !important;
        }
        .demo-pill:hover {
            background: var(--kaldis-gold-soft) !important;
            border-color: var(--kaldis-gold) !important;
            color: var(--kaldis-gold) !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <img src="<?= url('/assets/images/logo.png') ?>" alt="Kaldis Coffee" height="85" class="mb-3 rounded-circle shadow-sm p-1 bg-white border border-2 border-warning">
        <h5 class="login-brand-title mb-1">KALDIS COFFEE PLC</h5>
        <p class="login-brand-subtitle mb-0 font-monospace">STRATEGIC PLANNING &bull; EXECUTION &bull; PERFORMANCE</p>
    </div>

    <div class="login-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 small d-flex align-items-center">
                <i class="fas fa-exclamation-circle me-2"></i> <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="username" class="form-label small fw-bold text-muted">USERNAME OR EMAIL</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control border-start-0" id="username" name="username" required autofocus placeholder="e.g. admin or gm">
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label small fw-bold text-muted">PASSWORD</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control border-start-0" id="password" name="password" required placeholder="Enter your password">
                </div>
            </div>

            <button type="submit" class="btn btn-kaldis w-100 py-2 fw-bold text-uppercase">
                <i class="fas fa-sign-in-alt me-2"></i> Sign In to Portal
            </button>
        </form>

        <!-- Demo Accounts Quick Filler for Easy Testing -->
        <div class="mt-4 pt-3 border-top">
            <div class="text-center small text-muted mb-2 fw-semibold">Quick Test Accounts (Password: <code>admin123</code>):</div>
            <div class="d-flex flex-wrap gap-1 justify-content-center">
                <span class="badge bg-light text-dark border p-2 demo-pill" onclick="fillLogin('admin')">
                    <strong>Admin</strong> (Super Admin)
                </span>
                <span class="badge bg-light text-dark border p-2 demo-pill" onclick="fillLogin('gm')">
                    <strong>GM</strong> (Management)
                </span>
                <span class="badge bg-light text-dark border p-2 demo-pill" onclick="fillLogin('it_head')">
                    <strong>IT Head</strong> (Dept Head)
                </span>
                <span class="badge bg-light text-dark border p-2 demo-pill" onclick="fillLogin('ops_head')">
                    <strong>Ops Head</strong> (Dept Head)
                </span>
                <span class="badge bg-light text-dark border p-2 demo-pill" onclick="fillLogin('hr_officer')">
                    <strong>HR Officer</strong>
                </span>
            </div>
        </div>
    </div>
</div>

<script>
function fillLogin(username) {
    document.getElementById('username').value = username;
    document.getElementById('password').value = 'admin123';
}
</script>
</body>
</html>

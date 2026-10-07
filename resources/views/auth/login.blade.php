@extends('layouts.guest')

@section('title', 'Sign In')

@section('content')
<div class="login-card">
    <div class="text-center">
        <img src="{{ asset('images/logo.png') }}" alt="Kaldis Coffee" class="login-logo" onerror="this.src='{{ asset('assets/images/logo.png') }}'">
        <h4 class="fw-bold text-dark mb-1">Kaldis Coffee PLC</h4>
        <p class="text-muted small mb-4">Strategic Planning & Performance Management</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger py-2 px-3 small border-0 mb-3 rounded-3">
            <i class="fas fa-exclamation-circle me-1"></i> {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Username</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-user small"></i></span>
                <input type="text" name="username" id="usernameInput" class="form-control border-start-0" placeholder="Enter username" value="{{ old('username') }}" required autofocus>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-lock small"></i></span>
                <input type="password" name="password" id="passwordInput" class="form-control border-start-0" placeholder="••••••••" required>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input type="checkbox" name="remember" class="form-check-input" id="rememberMe">
                <label class="form-check-label small text-muted" for="rememberMe">Keep me logged in</label>
            </div>
        </div>

        <button type="submit" class="btn btn-kaldis-login w-100 mb-4">
            Sign In to System <i class="fas fa-arrow-right ms-1"></i>
        </button>
    </form>

    <!-- 1-Click Quick Fill Credentials -->
    <div class="p-3 bg-light rounded-3 border">
        <div class="small fw-bold text-secondary mb-2 text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">
            <i class="fas fa-key me-1 text-warning"></i> Quick Demo Access (Default: admin123)
        </div>
        <div class="d-flex flex-wrap gap-1">
            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 small" onclick="fillCreds('gm', 'admin123')">
                <i class="fas fa-user-tie me-1"></i> General Manager
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 small" onclick="fillCreds('it_head', 'admin123')">
                <i class="fas fa-laptop-code me-1"></i> IT Dept Head
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 small" onclick="fillCreds('ops_head', 'admin123')">
                <i class="fas fa-store me-1"></i> Ops Head
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 small" onclick="fillCreds('admin', 'admin123')">
                <i class="fas fa-shield-alt me-1"></i> Admin
            </button>
        </div>
    </div>
</div>

<script>
    function fillCreds(u, p) {
        document.getElementById('usernameInput').value = u;
        document.getElementById('passwordInput').value = p;
    }
</script>
@endsection

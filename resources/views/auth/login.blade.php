@extends('layouts.guest')

@section('title', 'Log in to your account')

@section('content')
<div class="auth-container">
    <!-- Centered Circular Logo Badge matching CompanySystem -->
    <div class="auth-logo-badge">
        <img src="{{ asset('assets/images/logo.png') }}" alt="Kaldis Coffee" class="auth-logo-img" onerror="this.src='{{ asset('images/logo.png') }}'">
    </div>

    <!-- Auth Card matching CompanySystem's AuthCardLayout -->
    <div class="auth-card">
        <div class="auth-card-header">
            <h1 class="auth-card-title">Log in to your account</h1>
            <p class="auth-card-description">Enter your corporate email address to log in</p>
        </div>

        <div class="auth-card-body">
            @if($errors->any())
                <div class="alert alert-danger py-2 px-3 small mb-3 rounded-md d-flex align-items-center gap-2 border-0" style="background-color: rgba(239, 68, 68, 0.1); color: #DC2626; border-radius: 8px;">
                    <i class="fas fa-circle-exclamation flex-shrink-0"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf
                
                <div class="mb-3">
                    <label for="emailInput" class="form-label">Email Address</label>
                    <input type="email" name="email" id="emailInput" class="form-control" placeholder="name@kaldisbunna.et" value="{{ old('email') }}" required autofocus autocomplete="email">
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="passwordInput" class="form-label mb-0">Password</label>
                    </div>
                    <div class="position-relative">
                        <input type="password" name="password" id="passwordInput" class="form-control pe-5" placeholder="••••••••" required autocomplete="current-password">
                        <button type="button" id="togglePasswordBtn" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-muted text-decoration-none pe-3 py-0 border-0" tabindex="-1" style="font-size: 0.875rem;">
                            <i class="far fa-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input type="checkbox" name="remember" class="form-check-input" id="rememberMe">
                        <label class="form-check-label small text-muted" for="rememberMe" style="cursor: pointer;">Remember me</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-auth-submit w-100">
                    Log in
                </button>
            </form>
        </div>
    </div>

    <div class="auth-footer">
        &copy; {{ date('Y') }} Kaldis Coffee PLC. All rights reserved. &bull; IT Department
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const passwordInput = document.getElementById('passwordInput');
        const toggleIcon = document.getElementById('togglePasswordIcon');
        
        if (toggleBtn && passwordInput && toggleIcon) {
            toggleBtn.addEventListener('click', function () {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    toggleIcon.classList.remove('fa-eye');
                    toggleIcon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    toggleIcon.classList.remove('fa-eye-slash');
                    toggleIcon.classList.add('fa-eye');
                }
            });
        }
    });
</script>
@endsection

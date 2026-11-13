@extends('layouts.app')

@section('title', 'Change Password - Kaldis Coffee PLC')

@section('content')
@php
    $isFirstLogin = !empty($user->must_change_password) || (method_exists($user, 'requiresPasswordChange') && $user->requiresPasswordChange());
@endphp

<div class="d-flex align-items-center justify-content-center py-4 py-md-5">
    <div class="card border-0 shadow-sm" style="max-width: 440px; width: 100%; border-radius: 12px; background: var(--kaldis-card-bg, #ffffff);">
        <div class="card-body p-4 p-sm-5">

            <!-- Clean Header -->
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10 text-warning mb-3" style="width: 52px; height: 52px; font-size: 1.4rem;">
                    <i class="fas fa-key"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">
                    {{ $isFirstLogin ? 'Set New Password' : 'Change Password' }}
                </h4>
                <p class="text-muted small mb-0">
                    {{ $isFirstLogin ? 'Please set a personal password to continue.' : 'Enter your current password and choose a new one.' }}
                </p>
                <div class="mt-2">
                    <span class="badge bg-light text-dark border font-monospace">{{ $user->username }}</span>
                </div>
            </div>

            <!-- Simple Form -->
            <form method="POST" action="{{ route('password.update') }}">
                @csrf

                <!-- Current Password -->
                <div class="mb-3">
                    <label for="current_password" class="form-label small fw-semibold text-secondary">
                        Current Password <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input type="password" 
                               id="current_password" 
                               name="current_password" 
                               class="form-control @error('current_password') is-invalid @enderror" 
                               placeholder="Current password" 
                               required 
                               autocomplete="current-password">
                        <button type="button" class="btn btn-outline-secondary toggle-pwd" data-target="current_password" tabindex="-1">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                    @error('current_password')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                    @if($isFirstLogin)
                        <div class="text-muted small mt-1" style="font-size: 0.78rem;">
                            Default password: <code class="fw-bold">kaldis123</code>
                        </div>
                    @endif
                </div>

                <!-- New Password -->
                <div class="mb-3">
                    <label for="new_password" class="form-label small fw-semibold text-secondary">
                        New Password <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input type="password" 
                               id="new_password" 
                               name="new_password" 
                               class="form-control @error('new_password') is-invalid @enderror" 
                               placeholder="At least 6 characters" 
                               required 
                               minlength="6" 
                               autocomplete="new-password">
                        <button type="button" class="btn btn-outline-secondary toggle-pwd" data-target="new_password" tabindex="-1">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                    @error('new_password')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Confirm New Password -->
                <div class="mb-4">
                    <label for="new_password_confirmation" class="form-label small fw-semibold text-secondary">
                        Confirm New Password <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input type="password" 
                               id="new_password_confirmation" 
                               name="new_password_confirmation" 
                               class="form-control" 
                               placeholder="Re-enter new password" 
                               required 
                               autocomplete="new-password">
                        <button type="button" class="btn btn-outline-secondary toggle-pwd" data-target="new_password_confirmation" tabindex="-1">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-kaldis w-100 py-2 fw-semibold shadow-sm mb-2">
                    <i class="fas fa-check-circle me-1.5"></i> Save Password
                </button>

                @if(!$isFirstLogin)
                    <div class="text-center mt-3">
                        <a href="{{ route('overview') }}" class="text-muted small text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i> Cancel
                        </a>
                    </div>
                @else
                    <div class="text-center mt-3">
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-link text-muted small p-0 text-decoration-none">
                                <i class="fas fa-arrow-right-from-bracket me-1"></i> Log out
                            </button>
                        </form>
                    </div>
                @endif

            </form>

        </div>
    </div>
</div>

<script>
document.querySelectorAll('.toggle-pwd').forEach(btn => {
    btn.addEventListener('click', function() {
        const input = document.getElementById(this.dataset.target);
        if (input) {
            const isPwd = input.type === 'password';
            input.type = isPwd ? 'text' : 'password';
            this.querySelector('i').className = isPwd ? 'far fa-eye-slash' : 'far fa-eye';
        }
    });
});
</script>
@endsection

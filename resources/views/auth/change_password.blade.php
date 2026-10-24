@extends('layouts.app')

@section('title', 'Change Account Password - Kaldis Coffee PLC')

@section('content')
<div class="container-fluid py-3" style="max-width: 900px; margin: 0 auto;">

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0 py-1" style="font-size: 0.85rem;">
            <li class="breadcrumb-item"><a href="{{ route('overview') }}" class="text-decoration-none text-muted"><i class="fas fa-house me-1"></i> Overview</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Change Password</li>
        </ol>
    </nav>

    <!-- Header Card -->
    <div class="card border-0 shadow-sm mb-4" style="background: var(--kaldis-card-bg); border-radius: 16px;">
        <div class="card-body p-4 p-md-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center fw-bold shadow-xs" 
                         style="width: 52px; height: 52px; font-size: 1.4rem;">
                        {{ strtoupper(substr($user->full_name ?? $user->username ?? 'U', 0, 1)) }}
                    </div>
                    <div>
                        <h4 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                            <span>Account Security</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2.5 rounded-pill" style="font-size: 0.72rem;">
                                <i class="fas fa-shield-halved me-1"></i> Active
                            </span>
                        </h4>
                        <div class="text-muted small">
                            <strong>{{ $user->full_name ?? $user->username }}</strong>
                            <span class="mx-1">&bull;</span>
                            <span class="badge bg-light text-dark border">{{ $user->username }}</span>
                            @if($user->department)
                                <span class="mx-1">&bull;</span>
                                <span>{{ $user->department->department_name }}</span>
                            @endif
                            <span class="mx-1">&bull;</span>
                            <span class="badge bg-warning text-dark">{{ $user->role->display_name ?? $user->role->name ?? 'User' }}</span>
                        </div>
                    </div>
                </div>
                <a href="{{ route('overview') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="fas fa-arrow-left me-1"></i> Back to Overview
                </a>
            </div>
        </div>
    </div>

    <!-- Main Form Grid -->
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm" style="background: var(--kaldis-card-bg); border-radius: 16px;">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
                    <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="fas fa-key text-warning"></i>
                        <span>Change Password</span>
                    </h5>
                    <p class="text-muted small mb-0">Update your account credentials to keep your profile secure.</p>
                </div>
                <div class="card-body p-4 pt-2">

                    <form method="POST" action="{{ route('password.update') }}" id="changePasswordForm">
                        @csrf

                        <!-- Current Password -->
                        <div class="mb-3">
                            <label for="current_password" class="form-label fw-semibold text-dark small">
                                Current Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" 
                                       id="current_password" 
                                       name="current_password" 
                                       class="form-control border-start-0 border-end-0 @error('current_password') is-invalid @enderror" 
                                       placeholder="Enter your current password" 
                                       required 
                                       autocomplete="current-password">
                                <button type="button" class="btn btn-outline-secondary border-start-0 toggle-password-btn" data-target="current_password" tabindex="-1">
                                    <i class="far fa-eye"></i>
                                </button>
                                @error('current_password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- New Password -->
                        <div class="mb-3">
                            <label for="new_password" class="form-label fw-semibold text-dark small">
                                New Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted">
                                    <i class="fas fa-shield-alt"></i>
                                </span>
                                <input type="password" 
                                       id="new_password" 
                                       name="new_password" 
                                       class="form-control border-start-0 border-end-0 @error('new_password') is-invalid @enderror" 
                                       placeholder="Enter new password (min. 6 characters)" 
                                       required 
                                       minlength="6"
                                       autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary border-start-0 toggle-password-btn" data-target="new_password" tabindex="-1">
                                    <i class="far fa-eye"></i>
                                </button>
                                @error('new_password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-text small" style="font-size: 0.78rem;">
                                <i class="fas fa-info-circle text-primary me-1"></i> Must be at least 6 characters long and different from current password.
                            </div>
                        </div>

                        <!-- Confirm New Password -->
                        <div class="mb-4">
                            <label for="new_password_confirmation" class="form-label fw-semibold text-dark small">
                                Confirm New Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted">
                                    <i class="fas fa-check-double"></i>
                                </span>
                                <input type="password" 
                                       id="new_password_confirmation" 
                                       name="new_password_confirmation" 
                                       class="form-control border-start-0 border-end-0" 
                                       placeholder="Re-enter your new password" 
                                       required 
                                       autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary border-start-0 toggle-password-btn" data-target="new_password_confirmation" tabindex="-1">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                            <div id="passwordMatchHint" class="form-text small d-none" style="font-size: 0.78rem;"></div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex align-items-center gap-2 pt-2">
                            <button type="submit" class="btn btn-success fw-semibold px-4 py-2 shadow-sm rounded-pill d-inline-flex align-items-center gap-2">
                                <i class="fas fa-floppy-disk"></i>
                                <span>Save New Password</span>
                            </button>
                            <a href="{{ route('overview') }}" class="btn btn-light border fw-semibold px-3 py-2 rounded-pill">
                                Cancel
                            </a>
                        </div>

                    </form>

                </div>
            </div>
        </div>

        <!-- Sidebar / Guidance Column -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-4" style="background: var(--kaldis-card-bg); border-radius: 16px;">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <i class="fas fa-shield-heart text-success"></i>
                        <span>Security Best Practices</span>
                    </h6>
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2 text-muted small" style="font-size: 0.82rem; line-height: 1.5;">
                        <li class="d-flex align-items-start gap-2">
                            <i class="fas fa-circle-check text-success mt-1"></i>
                            <span>Use a unique password that you do not use on other platforms.</span>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="fas fa-circle-check text-success mt-1"></i>
                            <span>Combine letters, numbers, and symbols to make your password harder to guess.</span>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="fas fa-circle-check text-success mt-1"></i>
                            <span>Never share your Planner credentials or password with anyone else.</span>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="fas fa-circle-check text-success mt-1"></i>
                            <span>All password updates are permanently recorded in the system audit trail for security.</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="background: rgba(217, 119, 6, 0.04); border: 1.5px dashed rgba(217, 119, 6, 0.3) !important; border-radius: 16px;">
                <div class="card-body p-3.5">
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="p-2 bg-warning-subtle text-warning rounded-circle">
                            <i class="fas fa-headset fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark small">Need Help or Account Recovery?</div>
                            <div class="text-muted" style="font-size: 0.76rem;">Contact the IT Department or System Administrator if you forget your password.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Password visibility toggle
    document.querySelectorAll('.toggle-password-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            if (input) {
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

    // Real-time password confirmation check
    const newPwd = document.getElementById('new_password');
    const confPwd = document.getElementById('new_password_confirmation');
    const matchHint = document.getElementById('passwordMatchHint');

    function checkMatch() {
        if (!confPwd.value) {
            matchHint.classList.add('d-none');
            return;
        }
        matchHint.classList.remove('d-none');
        if (newPwd.value === confPwd.value) {
            matchHint.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i> Passwords match</span>';
        } else {
            matchHint.innerHTML = '<span class="text-danger"><i class="fas fa-triangle-exclamation me-1"></i> Passwords do not match</span>';
        }
    }

    if (newPwd && confPwd && matchHint) {
        newPwd.addEventListener('input', checkMatch);
        confPwd.addEventListener('input', checkMatch);
    }
});
</script>
@endsection

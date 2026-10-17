@extends('layouts.guest')

@section('title', 'Sign In')

@section('content')
<div class="login-card">
    <div class="text-center mb-4">
        <img src="{{ asset('assets/images/logo.png') }}" alt="Kaldis Coffee" class="login-logo" onerror="this.src='{{ asset('images/logo.png') }}'">
        <h3 class="brand-title mb-1">Kaldis Coffee PLC</h3>
        <p class="text-muted small mb-0 fw-semibold">
            Strategic Planning &bull; Execution &bull; Performance
        </p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger py-2 px-3 small mb-3 rounded-3 d-flex align-items-center gap-2">
            <i class="fas fa-exclamation-circle text-danger flex-shrink-0"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-user text-muted"></i></span>
                <input type="text" name="username" id="usernameInput" class="form-control" placeholder="Enter username (e.g. gm or admin)" value="{{ old('username') }}" required autofocus>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Enter password" required>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input type="checkbox" name="remember" class="form-check-input" id="rememberMe">
                <label class="form-check-label small text-muted" for="rememberMe">Remember me</label>
            </div>
        </div>

        <button type="submit" class="btn btn-kaldis-login w-100">
            Sign In to Portal <i class="fas fa-arrow-right ms-1"></i>
        </button>
    </form>

    <div class="text-center mt-3 pt-2">
        <small class="text-muted" style="font-size: 0.72rem;">&copy; {{ date('Y') }} Kaldis Coffee PLC &bull; IT Department</small>
    </div>
</div>
@endsection

@section('scripts')
@endsection

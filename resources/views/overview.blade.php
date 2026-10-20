@extends('layouts.app')

@section('title', 'Corporate Overview & Planning Launchpad')

@section('content')
<style>
    /* ── Floating & Transition Animation for Overview Logo ── */
    @keyframes kaldisLogoFloat {
        0% {
            transform: translateY(0px) rotate(0deg);
            box-shadow: 0 10px 25px -4px rgba(217, 119, 6, 0.28), 0 6px 12px -4px rgba(0, 0, 0, 0.08);
        }
        50% {
            transform: translateY(-12px) rotate(0.8deg);
            box-shadow: 0 24px 38px -6px rgba(217, 119, 6, 0.38), 0 12px 18px -4px rgba(0, 0, 0, 0.12);
        }
        100% {
            transform: translateY(0px) rotate(0deg);
            box-shadow: 0 10px 25px -4px rgba(217, 119, 6, 0.28), 0 6px 12px -4px rgba(0, 0, 0, 0.08);
        }
    }

    .kaldis-floating-logo-wrap {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10px;
        border-radius: 50%;
        background: transparent !important;
        border: 3.5px solid rgba(217, 119, 6, 0.35);
        animation: kaldisLogoFloat 4.2s ease-in-out infinite;
        transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), border-color 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
    }

    .kaldis-floating-logo-wrap:hover {
        transform: scale(1.1) translateY(-14px);
        border-color: #d97706;
        box-shadow: 0 28px 45px -8px rgba(217, 119, 6, 0.45);
    }

    .kaldis-overview-logo {
        width: 110px;
        height: 110px;
        object-fit: contain;
        transition: transform 0.35s ease;
    }

    .kaldis-floating-logo-wrap:hover .kaldis-overview-logo {
        transform: rotate(-3deg) scale(1.04);
    }

    .overview-action-card {
        background: var(--kaldis-card-bg);
        border: 1px solid rgba(0, 0, 0, 0.06);
        border-radius: 16px;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .overview-action-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px -6px rgba(0, 0, 0, 0.09) !important;
        border-color: rgba(217, 119, 6, 0.35);
    }
</style>

<div class="container-fluid py-2" style="max-width: 1320px; margin: 0 auto;">

    <!-- ══════════════════════════════════════════════════════════════
         1. SMART HERO WELCOME SECTION (WITH FLOATING LOGO)
    ══════════════════════════════════════════════════════════════ -->
    <div class="card border-0 shadow-sm mb-4 overflow-hidden" style="background: var(--kaldis-card-bg); border-radius: 18px;">
        <div class="card-body p-4 p-md-5 text-center position-relative">
            <!-- Decorative Subtle Accent Glows -->
            <div style="position: absolute; top: -60px; right: -60px; width: 200px; height: 200px; background: radial-gradient(circle, rgba(217,119,6,0.14) 0%, transparent 70%); border-radius: 50%; pointer-events: none;"></div>
            <div style="position: absolute; bottom: -60px; left: -60px; width: 200px; height: 200px; background: radial-gradient(circle, rgba(37,99,235,0.09) 0%, transparent 70%); border-radius: 50%; pointer-events: none;"></div>

            <!-- Live Calendar & Ethiopian Time Window Pills -->
            <div class="d-inline-flex align-items-center gap-2 flex-wrap justify-content-center mb-3">
                <span class="status-pill status-done shadow-xs py-1.5 px-3">
                    <span class="status-dot dot-done status-dot-pulse"></span>
                    <span class="fw-semibold">Active Operating Period</span>
                </span>
                <span class="badge period-badge shadow-xs py-2 px-3">
                    <i class="far fa-calendar-alt text-warning me-1"></i>
                    {{ $period['month'] }} {{ $period['year'] }} &bull; Week {{ $selectedWeek }}
                </span>
                <span class="badge period-badge shadow-xs py-2 px-3" title="Ethiopian Time (East Africa Time)">
                    <i class="far fa-clock text-warning me-1"></i>
                    {{ format_ethiopian_time() }}
                </span>
            </div>

            <!-- Enlarged Brand Identity Logo with Smooth Floating Transition -->
            <div class="d-block mb-3">
                <div class="kaldis-floating-logo-wrap" title="Kaldis Coffee PLC">
                    <img src="{{ asset('assets/images/logo.png') }}" 
                         alt="Kaldis Coffee PLC" 
                         class="kaldis-overview-logo"
                         onerror="this.src='{{ asset('images/logo.png') }}'">
                </div>
            </div>

            <!-- Personalized Executive Greeting & Subtitle -->
            <h1 class="fw-extrabold text-dark mb-2" style="font-size: clamp(1.8rem, 3.5vw, 2.3rem); letter-spacing: -0.025em;">
                {{ get_greeting() }}, {{ explode(' ', $user->full_name ?? $user->username)[0] }}!
            </h1>
            <p class="text-muted mx-auto mb-3" style="max-width: 680px; font-size: 1.02rem; line-height: 1.6;">
                Welcome to the <strong>Kaldis Coffee PLC</strong> Enterprise Planning &amp; Operational Governance Portal. 
                Execute annual strategies, align monthly milestones, and monitor weekly team performance.
            </p>

            <!-- User Role & Department Capsules -->
            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                <span class="badge py-2 px-3 shadow-xs d-inline-flex align-items-center gap-1.5" 
                      style="background: var(--k-gold-soft); color: var(--k-gold); border: 1px solid rgba(245,158,11,.3); font-size: 0.85rem; border-radius: 9999px;">
                    <i class="fas fa-shield-halved"></i>
                    <span>{{ $user->role->display_name ?? 'Corporate Staff' }}</span>
                </span>
                <span class="badge py-2 px-3 shadow-xs d-inline-flex align-items-center gap-1.5" 
                      style="background: var(--kaldis-card-bg); color: var(--kaldis-text-secondary); border: 1px solid var(--kaldis-border); font-size: 0.85rem; border-radius: 9999px;">
                    <i class="fas fa-building text-secondary"></i>
                    <span>{{ $user->department->department_name ?? 'Corporate Executive Office' }}</span>
                </span>
                @if(!empty($user->telegram_chat_id))
                    <span class="badge py-2 px-3 shadow-xs d-inline-flex align-items-center gap-1.5" 
                          style="background: rgba(14,165,233,0.1); color: #0284c7; border: 1px solid rgba(14,165,233,0.3); font-size: 0.85rem; border-radius: 9999px;">
                        <i class="fab fa-telegram"></i>
                        <span>Telegram Linked</span>
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         2. CORE STRATEGIC KPI METRICS ROW
    ══════════════════════════════════════════════════════════════ -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm p-3.5 overview-action-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center text-warning" style="width: 48px; height: 48px; background: rgba(217, 119, 6, 0.12); font-size: 1.35rem;">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Annual Goals</div>
                        <h4 class="fw-bold text-dark mb-0">{{ $totalGoals }} <span class="fs-6 text-muted fw-normal">Active</span></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm p-3.5 overview-action-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center text-primary" style="width: 48px; height: 48px; background: rgba(37, 99, 235, 0.12); font-size: 1.35rem;">
                        <i class="fas fa-building"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Operating Units</div>
                        <h4 class="fw-bold text-dark mb-0">{{ $totalDepartments }} <span class="fs-6 text-muted fw-normal">Depts</span></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm p-3.5 overview-action-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center text-success" style="width: 48px; height: 48px; background: rgba(22, 163, 74, 0.12); font-size: 1.35rem;">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Monthly Plans</div>
                        <h4 class="fw-bold text-dark mb-0">{{ $allMonthlyPlansCount }} <span class="fs-6 text-muted fw-normal">Plans</span></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm p-3.5 overview-action-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center text-info" style="width: 48px; height: 48px; background: rgba(14, 165, 233, 0.12); font-size: 1.35rem;">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Weekly Submissions</div>
                        <h4 class="fw-bold text-dark mb-0">{{ $submittedWeeklyReportsCount }} <span class="fs-6 text-muted fw-normal">Reports</span></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         3. STRATEGIC WORKFLOW QUICK LAUNCHPAD
    ══════════════════════════════════════════════════════════════ -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Annual Strategic Goals -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 overview-action-card shadow-sm p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-inline-flex p-2.5 rounded-3 mb-3" style="background: rgba(217, 119, 6, 0.1); color: #d97706; font-size: 1.25rem;">
                        <i class="fas fa-compass"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Annual Strategy</h5>
                    <p class="text-muted small mb-3" style="line-height: 1.5;">
                        Master strategic priorities, perspective weights, definitions of done, and department goal mapping.
                    </p>
                </div>
                <a href="{{ route('strategy.annual') }}" class="btn btn-outline-warning btn-sm w-100 fw-bold">
                    View Strategic Goals <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>

        <!-- Card 2: Monthly Planning -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 overview-action-card shadow-sm p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-inline-flex p-2.5 rounded-3 mb-3" style="background: rgba(37, 99, 235, 0.1); color: #2563eb; font-size: 1.25rem;">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Monthly Planning</h5>
                    <p class="text-muted small mb-3" style="line-height: 1.5;">
                        Monthly milestone targets, strategic activations, and submission packages for corporate review.
                    </p>
                </div>
                <a href="{{ route('monthly.plans') }}" class="btn btn-outline-primary btn-sm w-100 fw-bold">
                    Monthly Workspace <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>

        <!-- Card 3: Weekly Execution -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 overview-action-card shadow-sm p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-inline-flex p-2.5 rounded-3 mb-3" style="background: rgba(22, 163, 74, 0.1); color: #16a34a; font-size: 1.25rem;">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Weekly Tasks</h5>
                    <p class="text-muted small mb-3" style="line-height: 1.5;">
                        Track weekly deliverables, verify execution status (DONE/NOT DONE), and submit team performance reports.
                    </p>
                </div>
                <a href="{{ route('weekly.plans') }}" class="btn btn-outline-success btn-sm w-100 fw-bold">
                    Weekly Execution <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>

        <!-- Card 4: Management & Analytics -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 overview-action-card shadow-sm p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-inline-flex p-2.5 rounded-3 mb-3" style="background: rgba(124, 58, 237, 0.1); color: #7c3aed; font-size: 1.25rem;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Executive Oversight</h5>
                    <p class="text-muted small mb-3" style="line-height: 1.5;">
                        Company-wide scorecard, department submission compliance, official planning vouchers, and GM reviews.
                    </p>
                </div>
                <a href="{{ auth()->user()->isGM() || auth()->user()->isSuperAdmin() ? route('dashboard.gm') : route('dashboard.department') }}" class="btn btn-outline-secondary btn-sm w-100 fw-bold">
                    Open Dashboard <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         4. RECENT SYSTEM ACTIVITIES & AUDIT STREAM
    ══════════════════════════════════════════════════════════════ -->
    @if(!empty($recentActivities) && $recentActivities->isNotEmpty())
        <div class="card border-0 shadow-sm p-4 mb-4" style="background: var(--kaldis-card-bg); border-radius: 16px;">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="fas fa-clock-rotate-left text-warning"></i> Recent System Activity
                </h6>
                <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.74rem;">Real-time Governance Audit</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                    <tbody>
                        @foreach($recentActivities as $activity)
                            <tr>
                                <td style="width: 40px;">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-muted bg-light border" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                        <i class="fas fa-bolt text-warning"></i>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $activity->user->full_name ?? ($activity->user->username ?? 'System Operator') }}</div>
                                    <small class="text-muted">{{ json_decode($activity->new_values, true)['description'] ?? $activity->action }}</small>
                                </td>
                                <td class="text-end text-muted small font-monospace">
                                    {{ $activity->created_at ? $activity->created_at->diffForHumans() : 'Recently' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection

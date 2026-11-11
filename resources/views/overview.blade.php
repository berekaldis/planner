@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $hour = (int) date('H');
    $greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');
    $user = auth()->user();
    $userName = $user ? (explode(' ', $user->full_name ?? $user->name ?? $user->username)[0] ?? 'User') : 'User';
@endphp

<style>
    /* ══════════════════════════════════════════════════════════════════════
       COMPANY SYSTEM WELCOME HERO & COFFEE STEAM STYLES (PURE LIGHT MODE)
       ══════════════════════════════════════════════════════════════════════ */
    .overview-hero-wrapper {
        position: relative;
        width: 100%;
        overflow: hidden;
        border-radius: 1rem;
        background: radial-gradient(circle at 50% 30%, #FFFBEB 0%, #FFFFFF 70%);
        border: none;
        box-shadow: none;
        margin-bottom: 2rem;
    }

    .hero-greeting-title {
        font-size: clamp(2rem, 4vw, 3.5rem);
        font-weight: 700;
        color: #78350F;
        letter-spacing: -0.025em;
        line-height: 1.15;
        margin-bottom: 0.75rem;
    }

    .hero-welcome-sub {
        font-size: clamp(1.15rem, 2vw, 1.5rem);
        color: #92400E;
        font-weight: 500;
        margin-bottom: 0.35rem;
    }

    .hero-tagline {
        font-size: clamp(0.95rem, 1.5vw, 1.125rem);
        color: #B45309;
        font-weight: 400;
    }

    .steam-container {
        pointer-events: none;
        position: absolute;
        top: -1.75rem;
        left: 50%;
        height: 10rem;
        width: 100%;
        max-width: 420px;
        transform: translateX(-50%);
        z-index: 5;
    }

    .kaldis-cup-img {
        height: 16rem;
        width: 16rem;
        object-fit: contain;
        filter: drop-shadow(0 20px 25px rgba(120, 53, 15, 0.18));
        transition: transform 0.3s ease;
    }

    @media (min-width: 768px) {
        .kaldis-cup-img {
            height: 20rem;
            width: 20rem;
        }
    }

    .kaldis-cup-img:hover {
        transform: translateY(-4px) scale(1.02);
    }

    /* Ambient decorative circles */
    .hero-decor-circle-1 {
        position: absolute;
        top: 2rem;
        left: 2rem;
        width: 4rem;
        height: 4rem;
        transform: rotate(12deg);
        border-radius: 9999px;
        background-color: #92400E;
        opacity: 0.08;
        pointer-events: none;
    }

    .hero-decor-circle-2 {
        position: absolute;
        right: 2rem;
        bottom: 2rem;
        width: 5rem;
        height: 5rem;
        transform: rotate(-12deg);
        border-radius: 9999px;
        background-color: #92400E;
        opacity: 0.08;
        pointer-events: none;
    }

    .hero-decor-circle-3 {
        position: absolute;
        top: 50%;
        right: 3rem;
        width: 6rem;
        height: 6rem;
        border-radius: 9999px;
        background-color: #78350F;
        opacity: 0.05;
        pointer-events: none;
    }

    /* Launchpad cards matching CompanySystem UI */
    .cs-card {
        background-color: #FFFFFF;
        border: 1px solid #E4E4E7;
        border-radius: 0.75rem;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .cs-card:hover {
        border-color: #D4D4D8;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
        transform: translateY(-2px);
        color: inherit;
    }

    .cs-card-header {
        padding: 1.25rem 1.25rem 0.5rem 1.25rem;
    }

    .cs-card-body {
        padding: 0.5rem 1.25rem 1.25rem 1.25rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .cs-icon-box {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
    }

    .cs-badge-pill {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.75rem;
        padding: 0.25rem 0.65rem;
        border-radius: 9999px;
        font-weight: 500;
    }

    .radar-card-item {
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 0.625rem;
        transition: border-color 0.15s ease;
    }

    .radar-card-item:hover {
        border-color: #CBD5E1;
    }
</style>

<div class="container-fluid px-0" style="max-width: 1320px; margin: 0 auto;">

    <!-- ══════════════════════════════════════════════════════════════════════
         COMPANY SYSTEM WELCOME SECTION WITH COFFEE CUP & MULTI-LAYER STEAM
         ══════════════════════════════════════════════════════════════════════ -->
    <div class="overview-hero-wrapper">
        <!-- Ambient decorative coffee bean circles -->
        <div class="hero-decor-circle-1"></div>
        <div class="hero-decor-circle-2"></div>
        <div class="hero-decor-circle-3"></div>

        <div class="position-relative py-5 px-3 px-md-5 text-center" style="z-index: 10;">
            <!-- Greeting & Subtitles -->
            <div class="mb-4">
                <h1 class="hero-greeting-title">
                    {{ $greeting }}, {{ $userName }}!
                </h1>
                <p class="hero-welcome-sub">Welcome to Kaldi's Coffee Planning Management System</p>
                <p class="hero-tagline mb-0">Sip a Coffee, Make a Memory.</p>
            </div>

            <!-- Kaldi's Coffee Cup with Realistic Animated Steam Effects -->
            <div class="position-relative d-inline-block mt-3 mb-2">
                <!-- Realistic Animated Steam Effects - Multiple Layers -->
                <div class="steam-container">
                    <svg viewBox="0 0 400 200" style="width: 100%; height: 100%; filter: blur(1px);">
                        <defs>
                            <!-- Gradients for realistic steam fade -->
                            <linearGradient id="steamGradient1" x1="0%" y1="100%" x2="0%" y2="0%">
                                <stop offset="0%" stop-color="#78350F" stop-opacity="0.18" />
                                <stop offset="20%" stop-color="#92400E" stop-opacity="0.12" />
                                <stop offset="50%" stop-color="#B45309" stop-opacity="0.05" />
                                <stop offset="100%" stop-color="#B45309" stop-opacity="0.0" />
                            </linearGradient>
                            <linearGradient id="steamGradient2" x1="0%" y1="100%" x2="0%" y2="0%">
                                <stop offset="0%" stop-color="#78350F" stop-opacity="0.25" />
                                <stop offset="50%" stop-color="#92400E" stop-opacity="0.10" />
                                <stop offset="100%" stop-color="#92400E" stop-opacity="0.02" />
                            </linearGradient>
                        </defs>

                        <!-- Layer 1 - Background steam (thin, slow) -->
                        <g style="color: #9CA3AF;">
                            <path d="M120 160 Q115 130 120 100 Q125 70 120 40 Q115 10 120 -10"
                                  fill="none" stroke="url(#steamGradient2)" stroke-width="2" stroke-linecap="round">
                                <animate attributeName="d"
                                         values="M120 160 Q115 130 120 100 Q125 70 120 40 Q115 10 120 -10;
                                                 M120 160 Q125 130 120 100 Q115 70 120 40 Q125 10 120 -10;
                                                 M120 160 Q115 130 120 100 Q125 70 120 40 Q115 10 120 -10"
                                         dur="4s" repeatCount="indefinite" />
                            </path>
                            <path d="M280 160 Q285 130 280 100 Q275 70 280 40 Q285 10 280 -10"
                                  fill="none" stroke="url(#steamGradient2)" stroke-width="2" stroke-linecap="round">
                                <animate attributeName="d"
                                         values="M280 160 Q285 130 280 100 Q275 70 280 40 Q285 10 280 -10;
                                                 M280 160 Q275 130 280 100 Q285 70 280 40 Q275 10 280 -10;
                                                 M280 160 Q285 130 280 100 Q275 70 280 40 Q285 10 280 -10"
                                         dur="4.5s" repeatCount="indefinite" />
                            </path>
                        </g>

                        <!-- Layer 2 - Main steam (medium thickness) -->
                        <g style="color: #6B7280;">
                            <path d="M150 160 Q145 125 152 90 Q158 55 150 20 Q145 -5 152 -20"
                                  fill="none" stroke="url(#steamGradient1)" stroke-width="3.5" stroke-linecap="round">
                                <animate attributeName="d"
                                         values="M150 160 Q145 125 152 90 Q158 55 150 20 Q145 -5 152 -20;
                                                 M150 160 Q155 125 148 90 Q142 55 150 20 Q155 -5 148 -20;
                                                 M150 160 Q145 125 152 90 Q158 55 150 20 Q145 -5 152 -20"
                                         dur="3.2s" repeatCount="indefinite" />
                            </path>
                            <path d="M250 160 Q255 125 248 90 Q242 55 250 20 Q255 -5 248 -20"
                                  fill="none" stroke="url(#steamGradient1)" stroke-width="3.5" stroke-linecap="round">
                                <animate attributeName="d"
                                         values="M250 160 Q255 125 248 90 Q242 55 250 20 Q255 -5 248 -20;
                                                 M250 160 Q245 125 252 90 Q258 55 250 20 Q245 -5 252 -20;
                                                 M250 160 Q255 125 248 90 Q242 55 250 20 Q255 -5 248 -20"
                                         dur="3.7s" repeatCount="indefinite" />
                            </path>
                        </g>

                        <!-- Layer 3 - Center steam (thickest, most visible) -->
                        <g style="color: #4B5563;">
                            <path d="M200 160 Q195 120 202 85 Q208 50 200 18 Q195 -10 202 -25"
                                  fill="none" stroke="url(#steamGradient1)" stroke-width="5" stroke-linecap="round">
                                <animate attributeName="d"
                                         values="M200 160 Q195 120 202 85 Q208 50 200 18 Q195 -10 202 -25;
                                                 M200 160 Q205 120 198 85 Q192 50 200 18 Q205 -10 198 -25;
                                                 M200 160 Q195 120 202 85 Q208 50 200 18 Q195 -10 202 -25"
                                         dur="2.8s" repeatCount="indefinite" />
                            </path>
                        </g>

                        <!-- Layer 4 - Wispy side steam (thin, fast) -->
                        <g style="color: #6B7280;">
                            <path d="M180 160 Q175 135 182 105 Q188 75 180 45 Q175 20 182 0"
                                  fill="none" stroke="url(#steamGradient2)" stroke-width="2.5" stroke-linecap="round">
                                <animate attributeName="d"
                                         values="M180 160 Q175 135 182 105 Q188 75 180 45 Q175 20 182 0;
                                                 M180 160 Q185 135 178 105 Q172 75 180 45 Q185 20 178 0;
                                                 M180 160 Q175 135 182 105 Q188 75 180 45 Q175 20 182 0"
                                         dur="2.3s" repeatCount="indefinite" />
                            </path>
                            <path d="M220 160 Q225 135 218 105 Q212 75 220 45 Q225 20 218 0"
                                  fill="none" stroke="url(#steamGradient2)" stroke-width="2.5" stroke-linecap="round">
                                <animate attributeName="d"
                                         values="M220 160 Q225 135 218 105 Q212 75 220 45 Q225 20 218 0;
                                                 M220 160 Q215 135 222 105 Q228 75 220 45 Q215 20 222 0;
                                                 M220 160 Q225 135 218 105 Q212 75 220 45 Q225 20 218 0"
                                         dur="2.6s" repeatCount="indefinite" />
                            </path>
                        </g>
                    </svg>
                </div>

                <!-- Coffee Cup Image -->
                <div class="position-relative" style="z-index: 10;">
                    <img src="{{ asset('images/kaldis cup.png') }}"
                         onerror="this.onerror=null; this.src='{{ asset('assets/images/kaldis cup.png') }}';"
                         alt="Kaldi's Coffee Cup"
                         class="kaldis-cup-img">
                </div>
            </div>

            </div>
        </div>
</div>
@endsection

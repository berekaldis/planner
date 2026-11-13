<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Handle an incoming request.
     * Intercepts users who must change their password on first login
     * and forces them to complete password change before accessing any internal pages.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && (!empty($user->must_change_password) || $user->requiresPasswordChange())) {
            // Allow password change routes, logout, and AJAX requests
            if ($request->routeIs('password.change') ||
                $request->routeIs('password.update') ||
                $request->routeIs('logout') ||
                $request->is('password/*') ||
                $request->is('logout') ||
                $request->ajax()) {
                return $next($request);
            }

            if (!$request->session()->has('first_login_prompt_shown')) {
                $request->session()->flash('warning', 'Welcome to Kaldis Coffee Planning System! For your account security, you must set a new personal password on your first login before continuing.');
                $request->session()->put('first_login_prompt_shown', true);
            }

            return redirect()->route('password.change');
        }

        return $next($request);
    }
}

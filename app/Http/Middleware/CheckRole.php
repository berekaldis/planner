<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Super Admin has unrestricted access everywhere
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (empty($roles)) {
            return $next($request);
        }

        $userRoleName = $user->role ? strtolower(str_replace(' ', '_', $user->role->name)) : '';
        $normalizedRoles = array_map(fn($r) => strtolower(str_replace(' ', '_', $r)), $roles);

        if (in_array($userRoleName, $normalizedRoles, true)) {
            return $next($request);
        }

        abort(403, 'Unauthorized access for your role.');
    }
}

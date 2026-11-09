<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\CompanySystemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $identifier = trim($credentials['username']);
        $password = $credentials['password'];
        $remember = $request->boolean('remember');

        // 1. Primary Authentication: Central Company System Database (kaldisbp_company_system)
        try {
            $user = CompanySystemService::authenticate($identifier, $password);
            if ($user) {
                Auth::login($user, $remember);

                AuditLog::log('LOGIN', 'auth', "User {$user->username} ({$user->full_name}) logged in successfully via Company System", $user->id);

                $request->session()->regenerate();

                return redirect()->intended(route('dashboard'));
            }
        } catch (\Throwable $e) {
            Log::error("CompanySystemService authentication error: " . $e->getMessage());
        }

        // 2. Fallback Authentication: Local Planner Database
        $localUser = User::where(function ($query) use ($identifier) {
                $query->where('username', $identifier)
                      ->orWhere('email', $identifier);
            })
            ->where('is_active', 1)
            ->first();

        if ($localUser) {
            $passwordValid = false;
            // Check standard bcrypt or legacy hash
            if (Hash::check($password, $localUser->password_hash)) {
                $passwordValid = true;
            } elseif (password_verify($password, $localUser->password_hash)) {
                $passwordValid = true;
                // Rehash with current settings
                $localUser->password_hash = Hash::make($password);
                $localUser->save();
            }

            if ($passwordValid) {
                Auth::login($localUser, $remember);

                AuditLog::log('LOGIN', 'auth', "User {$localUser->username} logged in successfully via local credentials", $localUser->id);

                $request->session()->regenerate();

                return redirect()->intended(route('dashboard'));
            }
        }

        AuditLog::log('LOGIN_FAILED', 'auth', "Failed login attempt for identifier: {$identifier}");

        return back()->withErrors([
            'username' => 'Invalid username or password provided.',
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            AuditLog::log('LOGOUT', 'auth', "User {$user->username} logged out", $user->id);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been successfully logged out.');
    }

    /**
     * Show the change password view.
     */
    public function showChangePassword()
    {
        return view('auth.change_password', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Handle updating the authenticated user's password.
     */
    public function updatePassword(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'current_password.required' => 'Please provide your current password.',
            'new_password.required' => 'Please enter a new password.',
            'new_password.min' => 'The new password must be at least 6 characters long.',
            'new_password.confirmed' => 'The new password confirmation does not match.',
        ]);

        // Verify current password against bcrypt or legacy hash
        $currentValid = false;
        if (Hash::check($validated['current_password'], $user->password_hash)) {
            $currentValid = true;
        } elseif (password_verify($validated['current_password'], $user->password_hash)) {
            $currentValid = true;
        }

        if (!$currentValid) {
            return back()->withErrors([
                'current_password' => 'The provided current password is incorrect.',
            ])->withInput();
        }

        // Prevent setting the exact same password
        if ($validated['current_password'] === $validated['new_password']) {
            return back()->withErrors([
                'new_password' => 'Your new password cannot be the same as your current password.',
            ])->withInput();
        }

        // Update password hash
        $newHash = Hash::make($validated['new_password']);
        $user->password_hash = $newHash;
        $user->save();

        // Sync to Company System if connected
        try {
            if ($user->company_user_id && CompanySystemService::isAvailable()) {
                $pdo = CompanySystemService::getConnection();
                if ($pdo) {
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$newHash, $user->company_user_id]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Could not sync updated password to company system: " . $e->getMessage());
        }

        AuditLog::log('CHANGE_PASSWORD', 'auth', "User {$user->username} successfully changed their account password", $user->id);

        return back()->with('success', 'Your password has been changed successfully.');
    }
}

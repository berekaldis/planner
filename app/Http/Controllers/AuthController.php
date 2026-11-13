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
            'email' => 'nullable|string',
            'username' => 'nullable|string',
            'password' => 'required|string',
        ]);

        $identifier = trim($request->input('email') ?: $request->input('username', ''));
        if (empty($identifier)) {
            return back()->withErrors(['email' => 'Please enter your email address.'])->withInput();
        }

        $password = $credentials['password'];
        $remember = $request->boolean('remember');

        // Local Authentication by Email (or username)
        $localUser = User::where(function ($query) use ($identifier) {
                $query->where('email', $identifier)
                      ->orWhere('username', $identifier);
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

                AuditLog::log('LOGIN', 'auth', "User {$localUser->username} ({$localUser->email}) logged in successfully", $localUser->id);

                if ($request->hasSession()) {
                    $request->session()->regenerate();
                }

                // If user is required to change password on first login
                if (!empty($localUser->must_change_password)) {
                    AuditLog::log('FIRST_LOGIN_FORCE_PASSWORD', 'auth', "User {$localUser->username} prompted to change temporary password on first login", $localUser->id);
                    $request->session()->put('first_login_prompt_shown', true);
                    return redirect()->route('password.change')->with('warning', 'Welcome to Kaldis Coffee Planning System! For your account security, you must set a new personal password on your first login.');
                }

                return redirect()->intended(route('dashboard'));
            }
        }

        // Check if user exists but is deactivated (Inactive)
        $inactiveUser = User::where(function ($query) use ($identifier) {
                $query->where('email', $identifier)
                      ->orWhere('username', $identifier);
            })
            ->where('is_active', 0)
            ->first();

        if ($inactiveUser && (Hash::check($password, $inactiveUser->password_hash) || password_verify($password, $inactiveUser->password_hash))) {
            AuditLog::log('LOGIN_BLOCKED', 'auth', "Blocked login attempt for deactivated user {$inactiveUser->username}", $inactiveUser->id);
            return back()->withErrors([
                'email' => 'Your account is currently Inactive / Disabled. Please contact your system administrator to re-enable access.',
            ])->onlyInput('email', 'username');
        }

        AuditLog::log('LOGIN_FAILED', 'auth', "Failed login attempt for email/username: {$identifier}");

        return back()->withErrors([
            'email' => 'Invalid email or password provided.',
        ])->onlyInput('email', 'username');
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

        $wasFirstLogin = !empty($user->must_change_password);

        // Update password hash and clear first login flag
        $newHash = Hash::make($validated['new_password']);
        $user->password_hash = $newHash;
        $user->must_change_password = 0;
        $user->password_changed_at = now();
        $user->save();

        if ($request->hasSession()) {
            $request->session()->forget('first_login_prompt_shown');
        }

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

        AuditLog::log('CHANGE_PASSWORD', 'auth', "User {$user->username} successfully changed their account password" . ($wasFirstLogin ? ' (First Login Setup Completed)' : ''), $user->id);

        if ($wasFirstLogin) {
            return redirect()->route('dashboard')->with('success', 'Your personal password has been set successfully! Welcome to Kaldis Coffee Planning System.');
        }

        return back()->with('success', 'Your password has been changed successfully.');
    }
}

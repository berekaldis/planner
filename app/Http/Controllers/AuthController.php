<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

        $user = User::where('username', $credentials['username'])
            ->where('is_active', 1)
            ->first();

        if ($user) {
            $passwordValid = false;
            // Check standard bcrypt or legacy hash
            if (Hash::check($credentials['password'], $user->password_hash)) {
                $passwordValid = true;
            } elseif (password_verify($credentials['password'], $user->password_hash)) {
                $passwordValid = true;
                // Rehash with current settings
                $user->password_hash = Hash::make($credentials['password']);
                $user->save();
            }

            if ($passwordValid) {
                Auth::login($user, $request->boolean('remember'));

                AuditLog::log('LOGIN', 'auth', "User {$user->username} logged in successfully", $user->id);

                $request->session()->regenerate();

                return redirect()->intended(route('dashboard'));
            }
        }

        AuditLog::log('LOGIN_FAILED', 'auth', "Failed login attempt for username: {$credentials['username']}");

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
}

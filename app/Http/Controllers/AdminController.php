<?php

namespace App\Http\Controllers;

use App\Models\AnnualGoal;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function dashboard()
    {
        $userCount = User::count();
        $deptCount = Department::count();
        $goalCount = AnnualGoal::count();
        $auditCount = AuditLog::count();

        $recentAudits = AuditLog::orderByDesc('id')->take(10)->get();

        return view('admin.dashboard', compact('userCount', 'deptCount', 'goalCount', 'auditCount', 'recentAudits'));
    }

    public function users()
    {
        $users = User::with(['role', 'department'])->orderBy('id')->get();
        $roles = Role::orderBy('id')->get();
        $departments = Department::where('active', 1)->orderBy('department_name')->get();

        return view('admin.users', compact('users', 'roles', 'departments'));
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|unique:users,username',
            'full_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'nullable|exists:departments,id',
            'telegram_chat_id' => 'nullable|string',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        $validated['password_hash'] = Hash::make($validated['password']);
        $validated['is_active'] = ($validated['status'] === 'ACTIVE') ? 1 : 0;
        unset($validated['password'], $validated['status']);

        $user = User::create($validated);

        AuditLog::log('CREATE_USER', 'admin', "Created user account {$user->username}", $user->id);

        return back()->with('success', "User account {$user->username} created successfully.");
    }

    public function departments()
    {
        $departments = Department::with('head')->orderBy('department_name')->get();
        $users = User::where('is_active', 1)->orderBy('full_name')->get();

        return view('admin.departments', compact('departments', 'users'));
    }

    public function settings()
    {
        $settings = SystemSetting::all()->keyBy('setting_key');
        return view('admin.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $inputs = $request->except(['_token']);

        foreach ($inputs as $key => $val) {
            SystemSetting::set($key, (string)$val);
        }

        AuditLog::log('UPDATE_SETTINGS', 'admin', "Updated system configuration settings");

        return back()->with('success', 'System settings saved successfully.');
    }

    public function auditLogs(Request $request)
    {
        $query = AuditLog::orderByDesc('id');

        if ($request->has('module') && $request->module) {
            $query->where('module', $request->module);
        }
        if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
        }

        $logs = $query->paginate(30);

        return view('admin.audit', compact('logs'));
    }

    public function reports(Request $request)
    {
        $selectedYear = $request->query('year', kaldis_setting('current_planning_year', config('kaldis.current_planning_year')));
        $selectedMonth = $request->query('month', kaldis_setting('current_planning_month', config('kaldis.current_planning_month')));
        $departments = Department::where('active', 1)->orderBy('department_name')->get();

        return view('admin.reports', compact('departments', 'selectedYear', 'selectedMonth'));
    }
}

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

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'username' => 'required|string|max:50|unique:users,username,' . $id,
            'full_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'nullable|exists:departments,id',
            'telegram_chat_id' => 'nullable|string',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        $updateData = [
            'username' => $validated['username'],
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
            'department_id' => $validated['department_id'],
            'telegram_chat_id' => $validated['telegram_chat_id'] ?? null,
            'is_active' => ($validated['status'] === 'ACTIVE') ? 1 : 0,
        ];

        if (!empty($validated['password'])) {
            $updateData['password_hash'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        AuditLog::log('UPDATE_USER', 'admin', "Updated user account {$user->username}", $user->id);

        return back()->with('success', "User account {$user->username} updated successfully.");
    }

    public function destroyUser($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting oneself
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $username = $user->username;
        $user->delete();

        AuditLog::log('DELETE_USER', 'admin', "Deleted user account {$username}", $id);

        return back()->with('success', "User account {$username} deleted successfully.");
    }

    public function departments()
    {
        $departments = Department::with(['head', 'annualGoals'])->orderBy('department_name')->get();
        $users = User::where('is_active', 1)->orderBy('full_name')->get();
        $annualGoals = \App\Models\AnnualGoal::orderBy('goal_code')->get();

        return view('admin.departments', compact('departments', 'users', 'annualGoals'));
    }

    public function storeDepartment(Request $request)
    {
        $validated = $request->validate([
            'department_code' => 'required|string|max:20|unique:departments,department_code',
            'department_name' => 'required|string|max:150|unique:departments,department_name',
            'head_user_id' => 'nullable|exists:users,id',
            'active' => 'nullable',
            'annual_goal_ids' => 'nullable|array',
            'annual_goal_ids.*' => 'exists:annual_strategy_goals,id',
        ]);

        $active = in_array($request->input('active', '1'), ['1', 1, 'ACTIVE', true], true) ? 1 : 0;

        $dept = Department::create([
            'department_code' => strtoupper(trim($validated['department_code'])),
            'department_name' => trim($validated['department_name']),
            'head_user_id' => $validated['head_user_id'] ?: null,
            'active' => $active,
        ]);

        if (!empty($validated['annual_goal_ids'])) {
            $dept->annualGoals()->sync($validated['annual_goal_ids']);
        }

        AuditLog::log('CREATE_DEPARTMENT', 'department', "Created department {$dept->department_name} ({$dept->department_code})", null, null, $dept->toArray());

        return back()->with('success', "Department '{$dept->department_name}' created successfully.");
    }

    public function updateDepartment(Request $request, $id)
    {
        $dept = Department::findOrFail($id);

        $validated = $request->validate([
            'department_code' => 'required|string|max:20|unique:departments,department_code,' . $dept->id,
            'department_name' => 'required|string|max:150|unique:departments,department_name,' . $dept->id,
            'head_user_id' => 'nullable|exists:users,id',
            'active' => 'nullable',
            'annual_goal_ids' => 'nullable|array',
            'annual_goal_ids.*' => 'exists:annual_strategy_goals,id',
        ]);

        $active = in_array($request->input('active', '1'), ['1', 1, 'ACTIVE', true], true) ? 1 : 0;

        $dept->update([
            'department_code' => strtoupper(trim($validated['department_code'])),
            'department_name' => trim($validated['department_name']),
            'head_user_id' => $validated['head_user_id'] ?: null,
            'active' => $active,
        ]);

        if ($request->has('annual_goal_ids')) {
            $dept->annualGoals()->sync($validated['annual_goal_ids'] ?? []);
        }

        AuditLog::log('UPDATE_DEPARTMENT', 'department', "Updated department {$dept->department_name} ({$dept->department_code})", null, null, $dept->toArray());

        return back()->with('success', "Department '{$dept->department_name}' updated successfully.");
    }

    public function destroyDepartment($id)
    {
        $dept = Department::findOrFail($id);

        $hasUsers = $dept->users()->count() > 0;
        $hasPlans = $dept->monthlyPlans()->count() > 0;

        if ($hasUsers || $hasPlans) {
            $dept->update(['active' => 0]);
            AuditLog::log('DEACTIVATE_DEPARTMENT', 'department', "Deactivated department {$dept->department_name} due to existing records");
            return back()->with('warning', "Department '{$dept->department_name}' has linked users or plans and was set to INACTIVE instead of deleted.");
        }

        $dept->annualGoals()->detach();
        $dept->delete();

        AuditLog::log('DELETE_DEPARTMENT', 'department', "Deleted department {$dept->department_name}");

        return back()->with('success', "Department deleted successfully.");
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

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
        $roles = Role::withCount('users')->get();

        $permissionMatrix = [
            'Strategy & Executive Governance' => [
                'View Corporate Annual Goals' => [
                    'desc' => 'View high-level corporate strategic objectives and department assignments',
                    'super_admin' => true, 'gm_management' => true, 'hr' => true, 'dept_head' => true, 'it_admin' => true,
                ],
                'Create & Edit Annual Goals' => [
                    'desc' => 'Define annual strategic goals, targets and departmental weightings',
                    'super_admin' => true, 'gm_management' => true, 'hr' => false, 'dept_head' => false, 'it_admin' => false,
                ],
                'Monthly Strategic Activation' => [
                    'desc' => 'Activate and prioritize specific annual goals for current Ethiopian month',
                    'super_admin' => true, 'gm_management' => true, 'hr' => false, 'dept_head' => false, 'it_admin' => false,
                ],
                'Access GM Executive Dashboard' => [
                    'desc' => 'Executive KPI tracking, cross-department submission heatmaps & scorecards',
                    'super_admin' => true, 'gm_management' => true, 'hr' => true, 'dept_head' => false, 'it_admin' => false,
                ],
                'Send Executive Telegram Reminders' => [
                    'desc' => 'Dispatch direct executive alerts and nudges to missing department heads',
                    'super_admin' => true, 'gm_management' => true, 'hr' => false, 'dept_head' => false, 'it_admin' => true,
                ],
            ],
            'Department Operations & Planning' => [
                'Formulate Monthly Plans' => [
                    'desc' => 'Create and draft departmental operational initiatives linked to active goals',
                    'super_admin' => true, 'gm_management' => false, 'hr' => false, 'dept_head' => true, 'it_admin' => false,
                ],
                'Submit Monthly Package (Day 26)' => [
                    'desc' => 'Transmit final monthly plan package before Day 26 (17:00) cutoff in Ethiopian Calendar',
                    'super_admin' => true, 'gm_management' => false, 'hr' => false, 'dept_head' => true, 'it_admin' => false,
                ],
                'Schedule Weekly Tasks' => [
                    'desc' => 'Breakdown monthly initiatives into actionable weekly deliverables for staff',
                    'super_admin' => true, 'gm_management' => false, 'hr' => false, 'dept_head' => true, 'it_admin' => false,
                ],
                'Evaluate Weekly Execution' => [
                    'desc' => 'Grade task results (DONE / NOT DONE) and assign root cause failure categories',
                    'super_admin' => true, 'gm_management' => false, 'hr' => false, 'dept_head' => true, 'it_admin' => false,
                ],
            ],
            'Reporting, Compliance & Official Vouchers' => [
                'Submit Weekly Performance Report' => [
                    'desc' => 'Transmit weekly execution scorecard before Monday 6:30 LT (12:30 PM) deadline',
                    'super_admin' => true, 'gm_management' => false, 'hr' => false, 'dept_head' => true, 'it_admin' => false,
                ],
                'Generate Official Monthly Voucher' => [
                    'desc' => 'Access and print cryptographically-verified official monthly plan voucher with security token',
                    'super_admin' => true, 'gm_management' => true, 'hr' => true, 'dept_head' => true, 'it_admin' => true,
                ],
                'Generate Official Weekly Voucher' => [
                    'desc' => 'Access and print signed official weekly execution confirmation voucher',
                    'super_admin' => true, 'gm_management' => true, 'hr' => true, 'dept_head' => true, 'it_admin' => true,
                ],
                'Organization Scorecard Analytics' => [
                    'desc' => 'View 13-department compliance, timely submission rates and historical trends',
                    'super_admin' => true, 'gm_management' => true, 'hr' => true, 'dept_head' => false, 'it_admin' => false,
                ],
                'Why Not Done Root Cause Analysis' => [
                    'desc' => 'Access organizational failure categorization and recovery action plans',
                    'super_admin' => true, 'gm_management' => true, 'hr' => true, 'dept_head' => true, 'it_admin' => false,
                ],
                'Deadline Escalation Tracking' => [
                    'desc' => 'Review multi-tier escalation triggers for consecutive missed submissions',
                    'super_admin' => true, 'gm_management' => true, 'hr' => true, 'dept_head' => false, 'it_admin' => false,
                ],
            ],
            'System Administration & Security' => [
                'Master Administration Access' => [
                    'desc' => 'Access core administrative panels, configuration and database parameters',
                    'super_admin' => true, 'gm_management' => false, 'hr' => false, 'dept_head' => false, 'it_admin' => true,
                ],
                'Manage User Accounts & Roles' => [
                    'desc' => 'Create, edit, reset passwords, and assign system roles and departments',
                    'super_admin' => true, 'gm_management' => false, 'hr' => false, 'dept_head' => false, 'it_admin' => false,
                ],
                'Manage Departments' => [
                    'desc' => 'Create, edit, and configure corporate operational departments',
                    'super_admin' => true, 'gm_management' => false, 'hr' => false, 'dept_head' => false, 'it_admin' => false,
                ],
                'Configure Deadlines & Parameters' => [
                    'desc' => 'Update Ethiopian calendar cycles, Day 26 cutoff, and Monday reporting rules',
                    'super_admin' => true, 'gm_management' => false, 'hr' => false, 'dept_head' => false, 'it_admin' => true,
                ],
                'Telegram Bot & Webhook Setup' => [
                    'desc' => 'Configure Bot Father token, webhook URL, diagnostic tester, and polling',
                    'super_admin' => true, 'gm_management' => false, 'hr' => false, 'dept_head' => false, 'it_admin' => true,
                ],
                'Immutable Audit Trail' => [
                    'desc' => 'Inspect comprehensive security audit logs and system trail',
                    'super_admin' => true, 'gm_management' => false, 'hr' => false, 'dept_head' => false, 'it_admin' => true,
                ],
            ],
            'Data Scoping & Boundary Rules' => [
                'Enterprise-Wide Data Scope' => [
                    'desc' => 'Unrestricted access to data across all 13 company operational units',
                    'super_admin' => true, 'gm_management' => true, 'hr' => true, 'dept_head' => false, 'it_admin' => true,
                ],
                'Department-Scoped Isolation' => [
                    'desc' => 'Access is strictly limited to user assigned department only',
                    'super_admin' => false, 'gm_management' => false, 'hr' => false, 'dept_head' => true, 'it_admin' => false,
                ],
            ],
        ];

        // Overlay custom permissions configured by Admin if saved
        $customPermsRaw = $settings['custom_role_permissions']->setting_value ?? null;
        $customPerms = !empty($customPermsRaw) ? json_decode($customPermsRaw, true) : null;

        if (is_array($customPerms)) {
            foreach ($permissionMatrix as $cat => &$perms) {
                foreach ($perms as $pName => &$pData) {
                    $pKey = \Illuminate\Support\Str::slug($pName, '_');
                    foreach (['super_admin', 'gm_management', 'hr', 'dept_head', 'it_admin'] as $rName) {
                        if (isset($customPerms[$rName]) && array_key_exists($pKey, $customPerms[$rName])) {
                            $pData[$rName] = !empty($customPerms[$rName][$pKey]);
                        }
                    }
                }
            }
            unset($perms, $pData);
        }

        return view('admin.settings', compact('settings', 'roles', 'permissionMatrix'));
    }

    public function updateSettings(Request $request)
    {
        $inputs = $request->except(['_token']);

        if ($request->has('save_permissions')) {
            $submittedPerms = $request->input('permissions', []);
            SystemSetting::set('custom_role_permissions', json_encode($submittedPerms));
            AuditLog::log('UPDATE_PERMISSIONS', 'admin', "Updated dynamic role permissions matrix");
            return back()->with('success', 'Role permissions matrix updated successfully.');
        }

        if ($request->has('reset_permissions')) {
            SystemSetting::set('custom_role_permissions', '');
            AuditLog::log('RESET_PERMISSIONS', 'admin', "Restored role permissions matrix to default");
            return back()->with('success', 'Role permissions restored to system defaults.');
        }

        if (isset($inputs['monthly_plan_deadline_day'])) {
            $inputs['monthly_deadline_day'] = $inputs['monthly_plan_deadline_day'];
        }

        foreach ($inputs as $key => $val) {
            if ($key !== 'permissions') {
                SystemSetting::set($key, (string)$val);
            }
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

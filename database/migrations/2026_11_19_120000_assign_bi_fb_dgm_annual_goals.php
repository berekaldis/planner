<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\AnnualGoal;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure monthly_strategy_activations.department_id is NULLABLE
        if (Schema::hasTable('monthly_strategy_activations')) {
            try {
                DB::statement("ALTER TABLE `monthly_strategy_activations` MODIFY COLUMN `department_id` INT NULL");
            } catch (\Throwable $e) {}
        }

        if (!Schema::hasTable('departments') || !Schema::hasTable('annual_goals')) {
            return;
        }

        // 2. Ensure BI, F&B, and DGM departments exist
        $deptsToEnsure = [
            'BI' => [
                'name' => 'Business Intelligence & Analytics',
                'code' => 'BI',
                'alt_codes' => ['BI'],
            ],
            'FB' => [
                'name' => 'Food & Beverage',
                'code' => 'F&B',
                'alt_codes' => ['FB', 'F&B', 'F_B'],
            ],
            'DGM' => [
                'name' => 'Deputy General Manager',
                'code' => 'DGM',
                'alt_codes' => ['DGM', 'BD'],
            ],
        ];

        $deptMap = [];
        foreach ($deptsToEnsure as $key => $info) {
            $altCodes = $info['alt_codes'];
            $dept = Department::whereIn('department_code', $altCodes)
                ->orWhere('department_name', $info['name'])
                ->orWhere('department_name', 'LIKE', '%' . $info['name'] . '%')
                ->first();

            if (!$dept) {
                $dept = Department::create([
                    'department_name' => $info['name'],
                    'department_code' => $info['code'],
                    'active' => 1,
                ]);
            } else {
                if (!$dept->active) {
                    $dept->update(['active' => 1]);
                }
            }
            $deptMap[$key] = $dept;
        }

        // 3. Define Strategy Goal assignments
        $assignments = [
            'BI' => [
                'primary' => ['G51', 'G52', 'G53', 'G54', 'G55', 'G56', 'G57', 'G58'],
                'secondary' => ['G22', 'G27', 'G29', 'G36'],
            ],
            'FB' => [
                'primary' => ['G19', 'G20', 'G21', 'G33', 'G34'],
                'secondary' => ['G09', 'G10', 'G17', 'G32'],
            ],
            'DGM' => [
                'primary' => ['G08'],
                'secondary' => ['G02', 'G03', 'G43', 'G44'],
            ],
        ];

        $planningYear = function_exists('kaldis_setting') ? kaldis_setting('current_planning_year', '2019 E.C.') : '2019 E.C.';
        $months = function_exists('get_ethiopian_months') ? get_ethiopian_months() : [
            'Meskerem', 'Tikimt', 'Hidar', 'Tahsas', 'Tir', 'Yakatit',
            'Megabit', 'Miazia', 'Ginbot', 'Sene', 'Hamle', 'Nehase', 'Pagume'
        ];
        $now = now();
        $adminUser = DB::table('users')->where('username', 'admin')->first();
        $adminId = $adminUser ? $adminUser->id : 1;

        foreach ($assignments as $deptKey => $goalConfig) {
            $dept = $deptMap[$deptKey] ?? null;
            if (!$dept) continue;

            $primaryCodes = $goalConfig['primary'];
            $secondaryCodes = $goalConfig['secondary'];
            $allCodes = array_unique(array_merge($primaryCodes, $secondaryCodes));

            // Set primary ownership on annual_goals
            AnnualGoal::whereIn('goal_code', $primaryCodes)
                ->update(['responsible_department_id' => $dept->id]);

            // Link in annual_goal_departments pivot table
            $allGoals = AnnualGoal::whereIn('goal_code', $allCodes)->get();
            $syncData = [];
            foreach ($allGoals as $g) {
                $isPrimary = in_array($g->goal_code, $primaryCodes) ? 1 : 0;
                $syncData[$g->id] = ['is_primary' => $isPrimary];
            }
            $dept->annualGoals()->syncWithoutDetaching($syncData);

            // Populate monthly strategy activations for all months
            if (Schema::hasTable('monthly_strategy_activations')) {
                $activations = [];
                foreach ($allGoals as $g) {
                    foreach ($months as $m) {
                        $activations[] = [
                            'annual_goal_id' => $g->id,
                            'department_id' => $dept->id,
                            'year' => $planningYear,
                            'month' => $m,
                            'active' => 'NO',
                            'activated_by' => $adminId,
                            'activated_at' => $now,
                            'updated_by' => $adminId,
                            'updated_at' => $now,
                        ];
                    }
                }
                if (!empty($activations)) {
                    DB::table('monthly_strategy_activations')->insertOrIgnore($activations);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe no-op to preserve operational data
    }
};

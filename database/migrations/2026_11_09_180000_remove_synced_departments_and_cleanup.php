<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $officialCodes = ['IT', 'OPS', 'MKT', 'SCM', 'HR', 'FIN', 'QAR', 'PM', 'TND', 'RND', 'SAFE', 'LOG', 'AUD', 'BI', 'FB', 'BD'];

        if (Schema::hasTable('departments')) {
            $syncedDepts = DB::table('departments')
                ->whereNotIn('department_code', $officialCodes)
                ->pluck('id')
                ->toArray();

            if (!empty($syncedDepts)) {
                DB::transaction(function () use ($syncedDepts) {
                    if (Schema::hasTable('users')) {
                        DB::table('users')->whereIn('department_id', $syncedDepts)->update(['department_id' => null]);
                    }
                    if (Schema::hasTable('annual_goal_departments')) {
                        DB::table('annual_goal_departments')->whereIn('department_id', $syncedDepts)->delete();
                    }
                    if (Schema::hasTable('monthly_strategy_activations')) {
                        DB::table('monthly_strategy_activations')->whereIn('department_id', $syncedDepts)->delete();
                    }
                    if (Schema::hasTable('escalations')) {
                        DB::table('escalations')->whereIn('department_id', $syncedDepts)->delete();
                    }
                    if (Schema::hasTable('notifications')) {
                        DB::table('notifications')->whereIn('department_id', $syncedDepts)->delete();
                    }
                    if (Schema::hasTable('annual_goals')) {
                        DB::table('annual_goals')->whereIn('responsible_department_id', $syncedDepts)->update(['responsible_department_id' => null]);
                    }

                    DB::table('departments')->whereIn('id', $syncedDepts)->delete();
                });
            }

            // Drop company_department_id column if present
            if (Schema::hasColumn('departments', 'company_department_id')) {
                Schema::table('departments', function (Blueprint $table) {
                    $table->dropColumn('company_department_id');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: removing non-system synced departments should not be reverted
    }
};

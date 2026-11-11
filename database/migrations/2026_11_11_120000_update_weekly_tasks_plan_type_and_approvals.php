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
        if (Schema::hasTable('weekly_tasks')) {
            // Modify plan_type column to VARCHAR(50) so it supports 'MONTH_PLAN' and 'NEW_TASK'
            DB::statement("ALTER TABLE weekly_tasks MODIFY COLUMN plan_type VARCHAR(50) NOT NULL DEFAULT 'NEW_TASK'");
            
            // Map any existing records: if has monthly_plan_id then MONTH_PLAN, else NEW_TASK
            DB::statement("UPDATE weekly_tasks SET plan_type = 'MONTH_PLAN' WHERE monthly_plan_id IS NOT NULL");
            DB::statement("UPDATE weekly_tasks SET plan_type = 'NEW_TASK' WHERE monthly_plan_id IS NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('weekly_tasks')) {
            DB::statement("UPDATE weekly_tasks SET plan_type = 'OPERATIONAL' WHERE plan_type NOT IN ('STRATEGY', 'OPERATIONAL')");
            DB::statement("ALTER TABLE weekly_tasks MODIFY COLUMN plan_type ENUM('STRATEGY','OPERATIONAL') NOT NULL DEFAULT 'OPERATIONAL'");
        }
    }
};

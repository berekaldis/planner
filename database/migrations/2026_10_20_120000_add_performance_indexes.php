<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to speed up system database queries.
     */
    public function up(): void
    {
        Schema::table('monthly_strategy_activations', function (Blueprint $table) {
            $table->index(['year', 'month', 'active', 'annual_goal_id'], 'idx_msa_year_month_active');
            $table->index(['annual_goal_id', 'year', 'month', 'active'], 'idx_msa_goal_year_month');
        });

        Schema::table('annual_goals', function (Blueprint $table) {
            $table->index(['year', 'status'], 'idx_ag_year_status');
        });

        Schema::table('monthly_plans', function (Blueprint $table) {
            $table->index(['department_id', 'year', 'month'], 'idx_mp_dept_period');
            $table->index(['year', 'month'], 'idx_mp_period');
        });

        Schema::table('weekly_tasks', function (Blueprint $table) {
            $table->index(['department_id', 'year', 'month', 'week_number'], 'idx_wt_dept_period_week');
            $table->index(['year', 'month'], 'idx_wt_period');
        });

        Schema::table('weekly_task_results', function (Blueprint $table) {
            $table->index(['result'], 'idx_wtr_result');
            $table->index(['department_id', 'year', 'month', 'week_number'], 'idx_wtr_dept_period');
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->index(['year', 'month', 'week_number'], 'idx_sub_period_week');
            $table->index(['department_id', 'year', 'month', 'week_number'], 'idx_sub_dept_period_week');
            $table->index(['status'], 'idx_sub_status');
        });

        Schema::table('monthly_submissions', function (Blueprint $table) {
            $table->index(['department_id', 'year', 'month'], 'idx_msub_dept_period');
            $table->index(['year', 'month'], 'idx_msub_period');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['created_at'], 'idx_audit_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_strategy_activations', function (Blueprint $table) {
            $table->dropIndex('idx_msa_year_month_active');
            $table->dropIndex('idx_msa_goal_year_month');
        });

        Schema::table('annual_goals', function (Blueprint $table) {
            $table->dropIndex('idx_ag_year_status');
        });

        Schema::table('monthly_plans', function (Blueprint $table) {
            $table->dropIndex('idx_mp_dept_period');
            $table->dropIndex('idx_mp_period');
        });

        Schema::table('weekly_tasks', function (Blueprint $table) {
            $table->dropIndex('idx_wt_dept_period_week');
            $table->dropIndex('idx_wt_period');
        });

        Schema::table('weekly_task_results', function (Blueprint $table) {
            $table->dropIndex('idx_wtr_result');
            $table->dropIndex('idx_wtr_dept_period');
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex('idx_sub_period_week');
            $table->dropIndex('idx_sub_dept_period_week');
            $table->dropIndex('idx_sub_status');
        });

        Schema::table('monthly_submissions', function (Blueprint $table) {
            $table->dropIndex('idx_msub_dept_period');
            $table->dropIndex('idx_msub_period');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_created');
        });
    }
};

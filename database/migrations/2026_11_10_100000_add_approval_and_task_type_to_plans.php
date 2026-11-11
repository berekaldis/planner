<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add approval columns to monthly_plans
        if (Schema::hasTable('monthly_plans')) {
            Schema::table('monthly_plans', function (Blueprint $table) {
                if (!Schema::hasColumn('monthly_plans', 'approval_status')) {
                    $table->string('approval_status', 50)->default('PENDING')->after('completion_notes')->index();
                }
                if (!Schema::hasColumn('monthly_plans', 'decline_reason')) {
                    $table->text('decline_reason')->nullable()->after('approval_status');
                }
                if (!Schema::hasColumn('monthly_plans', 'approved_by')) {
                    $table->unsignedBigInteger('approved_by')->nullable()->after('decline_reason')->index();
                }
                if (!Schema::hasColumn('monthly_plans', 'approved_at')) {
                    $table->dateTime('approved_at')->nullable()->after('approved_by');
                }
            });
        }

        // 2. Add task_type and approval columns to weekly_tasks
        if (Schema::hasTable('weekly_tasks')) {
            Schema::table('weekly_tasks', function (Blueprint $table) {
                if (!Schema::hasColumn('weekly_tasks', 'task_type')) {
                    $table->string('task_type', 50)->default('PLANNED')->after('plan_type')->index();
                }
                if (!Schema::hasColumn('weekly_tasks', 'approval_status')) {
                    $table->string('approval_status', 50)->default('PENDING')->after('notes')->index();
                }
                if (!Schema::hasColumn('weekly_tasks', 'decline_reason')) {
                    $table->text('decline_reason')->nullable()->after('approval_status');
                }
                if (!Schema::hasColumn('weekly_tasks', 'approved_by')) {
                    $table->unsignedBigInteger('approved_by')->nullable()->after('decline_reason')->index();
                }
                if (!Schema::hasColumn('weekly_tasks', 'approved_at')) {
                    $table->dateTime('approved_at')->nullable()->after('approved_by');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('monthly_plans')) {
            Schema::table('monthly_plans', function (Blueprint $table) {
                $columns = ['approval_status', 'decline_reason', 'approved_by', 'approved_at'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('monthly_plans', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('weekly_tasks')) {
            Schema::table('weekly_tasks', function (Blueprint $table) {
                $columns = ['task_type', 'approval_status', 'decline_reason', 'approved_by', 'approved_at'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('weekly_tasks', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

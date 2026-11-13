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
        // 1. Monthly Plans GM Comments
        Schema::table('monthly_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('monthly_plans', 'gm_comment')) {
                $table->text('gm_comment')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('monthly_plans', 'gm_comment_at')) {
                $table->dateTime('gm_comment_at')->nullable()->after('gm_comment');
            }
            if (!Schema::hasColumn('monthly_plans', 'gm_comment_by')) {
                $table->unsignedBigInteger('gm_comment_by')->nullable()->after('gm_comment_at');
            }
        });

        // 2. Weekly Tasks GM Comments
        Schema::table('weekly_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('weekly_tasks', 'gm_comment')) {
                $table->text('gm_comment')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('weekly_tasks', 'gm_comment_at')) {
                $table->dateTime('gm_comment_at')->nullable()->after('gm_comment');
            }
            if (!Schema::hasColumn('weekly_tasks', 'gm_comment_by')) {
                $table->unsignedBigInteger('gm_comment_by')->nullable()->after('gm_comment_at');
            }
        });

        // 3. Weekly Task Results GM Comments
        Schema::table('weekly_task_results', function (Blueprint $table) {
            if (!Schema::hasColumn('weekly_task_results', 'gm_comment')) {
                $table->text('gm_comment')->nullable()->after('attachment');
            }
            if (!Schema::hasColumn('weekly_task_results', 'gm_comment_at')) {
                $table->dateTime('gm_comment_at')->nullable()->after('gm_comment');
            }
            if (!Schema::hasColumn('weekly_task_results', 'gm_comment_by')) {
                $table->unsignedBigInteger('gm_comment_by')->nullable()->after('gm_comment_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_plans', function (Blueprint $table) {
            $table->dropColumn(['gm_comment', 'gm_comment_at', 'gm_comment_by']);
        });

        Schema::table('weekly_tasks', function (Blueprint $table) {
            $table->dropColumn(['gm_comment', 'gm_comment_at', 'gm_comment_by']);
        });

        Schema::table('weekly_task_results', function (Blueprint $table) {
            $table->dropColumn(['gm_comment', 'gm_comment_at', 'gm_comment_by']);
        });
    }
};

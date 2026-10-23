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
        // 1. Create quarterly_submissions table
        if (!Schema::hasTable('quarterly_submissions')) {
            Schema::create('quarterly_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
                $table->string('year', 50);
                $table->string('quarter', 10); // 'Q1', 'Q2', 'Q3', 'Q4'
                $table->string('months', 255); // 'Meskerem, Tikimt, Hidar'
                $table->integer('total_plans')->default(0);
                $table->integer('done_plans')->default(0);
                $table->integer('not_done_plans')->default(0);
                $table->decimal('completion_percentage', 5, 2)->default(0.00);
                $table->string('status', 50)->default('DRAFT'); // 'DRAFT', 'SUBMITTED', 'LATE'
                $table->string('approval_status', 50)->default('PENDING'); // 'PENDING', 'APPROVED', 'REVISION_REQUESTED'
                $table->string('submission_channel', 50)->default('WEB');
                $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('submitted_at')->nullable();
                $table->text('executive_summary')->nullable();
                $table->text('challenges')->nullable();
                $table->text('next_quarter_priorities')->nullable();
                $table->text('gm_feedback')->nullable();
                $table->dateTime('gm_feedback_at')->nullable();
                $table->foreignId('gm_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['department_id', 'year', 'quarter'], 'uk_dept_quarterly_period');
            });
        }

        // 2. Add performance columns to monthly_plans if missing
        if (Schema::hasTable('monthly_plans')) {
            Schema::table('monthly_plans', function (Blueprint $table) {
                if (!Schema::hasColumn('monthly_plans', 'performance_status')) {
                    $table->string('performance_status', 50)->default('PENDING')->after('notes');
                }
                if (!Schema::hasColumn('monthly_plans', 'actual_result')) {
                    $table->text('actual_result')->nullable()->after('performance_status');
                }
                if (!Schema::hasColumn('monthly_plans', 'achievement_percentage')) {
                    $table->integer('achievement_percentage')->default(0)->after('actual_result');
                }
                if (!Schema::hasColumn('monthly_plans', 'completion_notes')) {
                    $table->text('completion_notes')->nullable()->after('achievement_percentage');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('quarterly_submissions')) {
            Schema::dropIfExists('quarterly_submissions');
        }

        if (Schema::hasTable('monthly_plans')) {
            Schema::table('monthly_plans', function (Blueprint $table) {
                $columns = ['performance_status', 'actual_result', 'achievement_percentage', 'completion_notes'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('monthly_plans', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

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
        // 1. annual_goals: perspective and weight
        if (Schema::hasTable('annual_goals')) {
            Schema::table('annual_goals', function (Blueprint $table) {
                if (!Schema::hasColumn('annual_goals', 'perspective')) {
                    $table->string('perspective', 100)->nullable()->after('year');
                }
                if (!Schema::hasColumn('annual_goals', 'weight')) {
                    $table->decimal('weight', 5, 2)->nullable()->default(0.00)->after('perspective');
                }
            });
        }

        // 2. submissions: approval_status
        if (Schema::hasTable('submissions')) {
            Schema::table('submissions', function (Blueprint $table) {
                if (!Schema::hasColumn('submissions', 'approval_status')) {
                    $table->enum('approval_status', ['PENDING', 'APPROVED', 'REVISION_REQUESTED'])
                        ->default('PENDING')
                        ->after('consecutive_missed_count');
                }
            });
        }

        // 3. weekly_reports: gm_feedback, gm_feedback_at, approval_status
        if (Schema::hasTable('weekly_reports')) {
            Schema::table('weekly_reports', function (Blueprint $table) {
                if (!Schema::hasColumn('weekly_reports', 'gm_feedback')) {
                    $table->text('gm_feedback')->nullable()->after('notes');
                }
                if (!Schema::hasColumn('weekly_reports', 'gm_feedback_at')) {
                    $table->dateTime('gm_feedback_at')->nullable()->after('gm_feedback');
                }
                if (!Schema::hasColumn('weekly_reports', 'approval_status')) {
                    $table->enum('approval_status', ['PENDING', 'APPROVED', 'REVISION_REQUESTED'])
                        ->default('PENDING')
                        ->after('gm_feedback_at');
                }
            });
        }

        // 4. users: remember_token
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'remember_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->rememberToken()->after('password_hash');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('annual_goals')) {
            Schema::table('annual_goals', function (Blueprint $table) {
                if (Schema::hasColumn('annual_goals', 'weight')) {
                    $table->dropColumn('weight');
                }
                if (Schema::hasColumn('annual_goals', 'perspective')) {
                    $table->dropColumn('perspective');
                }
            });
        }

        if (Schema::hasTable('submissions')) {
            Schema::table('submissions', function (Blueprint $table) {
                if (Schema::hasColumn('submissions', 'approval_status')) {
                    $table->dropColumn('approval_status');
                }
            });
        }

        if (Schema::hasTable('weekly_reports')) {
            Schema::table('weekly_reports', function (Blueprint $table) {
                if (Schema::hasColumn('weekly_reports', 'approval_status')) {
                    $table->dropColumn('approval_status');
                }
                if (Schema::hasColumn('weekly_reports', 'gm_feedback_at')) {
                    $table->dropColumn('gm_feedback_at');
                }
                if (Schema::hasColumn('weekly_reports', 'gm_feedback')) {
                    $table->dropColumn('gm_feedback');
                }
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'remember_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropRememberToken();
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add Delay Reason and GM Delay Approval columns to monthly submissions,
     * weekly plans, weekly reports, and weekly submission records.
     */
    public function up(): void
    {
        // 1. monthly_submissions
        if (Schema::hasTable('monthly_submissions')) {
            Schema::table('monthly_submissions', function (Blueprint $table) {
                if (!Schema::hasColumn('monthly_submissions', 'delay_reason')) {
                    $table->text('delay_reason')->nullable()->after('status');
                }
                if (!Schema::hasColumn('monthly_submissions', 'delay_status')) {
                    $table->string('delay_status', 50)->nullable()->after('delay_reason');
                }
                if (!Schema::hasColumn('monthly_submissions', 'delay_approved_by')) {
                    $table->integer('delay_approved_by')->nullable()->after('delay_status');
                }
                if (!Schema::hasColumn('monthly_submissions', 'delay_approved_at')) {
                    $table->dateTime('delay_approved_at')->nullable()->after('delay_approved_by');
                }
                if (!Schema::hasColumn('monthly_submissions', 'delay_gm_notes')) {
                    $table->text('delay_gm_notes')->nullable()->after('delay_approved_at');
                }
            });
        }

        // 2. weekly_plans
        if (Schema::hasTable('weekly_plans')) {
            Schema::table('weekly_plans', function (Blueprint $table) {
                if (!Schema::hasColumn('weekly_plans', 'submitted_at')) {
                    $table->dateTime('submitted_at')->nullable()->after('status');
                }
                if (!Schema::hasColumn('weekly_plans', 'submission_status')) {
                    $table->string('submission_status', 50)->default('DRAFT')->after('submitted_at');
                }
                if (!Schema::hasColumn('weekly_plans', 'delay_reason')) {
                    $table->text('delay_reason')->nullable()->after('submission_status');
                }
                if (!Schema::hasColumn('weekly_plans', 'delay_status')) {
                    $table->string('delay_status', 50)->nullable()->after('delay_reason');
                }
                if (!Schema::hasColumn('weekly_plans', 'delay_approved_by')) {
                    $table->integer('delay_approved_by')->nullable()->after('delay_status');
                }
                if (!Schema::hasColumn('weekly_plans', 'delay_approved_at')) {
                    $table->dateTime('delay_approved_at')->nullable()->after('delay_approved_by');
                }
                if (!Schema::hasColumn('weekly_plans', 'delay_gm_notes')) {
                    $table->text('delay_gm_notes')->nullable()->after('delay_approved_at');
                }
            });
        }

        // 3. weekly_reports
        if (Schema::hasTable('weekly_reports')) {
            Schema::table('weekly_reports', function (Blueprint $table) {
                if (!Schema::hasColumn('weekly_reports', 'delay_reason')) {
                    $table->text('delay_reason')->nullable()->after('submission_status');
                }
                if (!Schema::hasColumn('weekly_reports', 'delay_status')) {
                    $table->string('delay_status', 50)->nullable()->after('delay_reason');
                }
                if (!Schema::hasColumn('weekly_reports', 'delay_approved_by')) {
                    $table->integer('delay_approved_by')->nullable()->after('delay_status');
                }
                if (!Schema::hasColumn('weekly_reports', 'delay_approved_at')) {
                    $table->dateTime('delay_approved_at')->nullable()->after('delay_approved_by');
                }
                if (!Schema::hasColumn('weekly_reports', 'delay_gm_notes')) {
                    $table->text('delay_gm_notes')->nullable()->after('delay_approved_at');
                }
            });
        }

        // 4. submissions (weekly performance tracking)
        if (Schema::hasTable('submissions')) {
            Schema::table('submissions', function (Blueprint $table) {
                if (!Schema::hasColumn('submissions', 'delay_reason')) {
                    $table->text('delay_reason')->nullable()->after('status');
                }
                if (!Schema::hasColumn('submissions', 'delay_status')) {
                    $table->string('delay_status', 50)->nullable()->after('delay_reason');
                }
                if (!Schema::hasColumn('submissions', 'delay_approved_by')) {
                    $table->integer('delay_approved_by')->nullable()->after('delay_status');
                }
                if (!Schema::hasColumn('submissions', 'delay_approved_at')) {
                    $table->dateTime('delay_approved_at')->nullable()->after('delay_approved_by');
                }
                if (!Schema::hasColumn('submissions', 'delay_gm_notes')) {
                    $table->text('delay_gm_notes')->nullable()->after('delay_approved_at');
                }
            });
        }

        // 5. quarterly_submissions
        if (Schema::hasTable('quarterly_submissions')) {
            Schema::table('quarterly_submissions', function (Blueprint $table) {
                if (!Schema::hasColumn('quarterly_submissions', 'delay_reason')) {
                    $table->text('delay_reason')->nullable()->after('status');
                }
                if (!Schema::hasColumn('quarterly_submissions', 'delay_status')) {
                    $table->string('delay_status', 50)->nullable()->after('delay_reason');
                }
                if (!Schema::hasColumn('quarterly_submissions', 'delay_approved_by')) {
                    $table->integer('delay_approved_by')->nullable()->after('delay_status');
                }
                if (!Schema::hasColumn('quarterly_submissions', 'delay_approved_at')) {
                    $table->dateTime('delay_approved_at')->nullable()->after('delay_approved_by');
                }
                if (!Schema::hasColumn('quarterly_submissions', 'delay_gm_notes')) {
                    $table->text('delay_gm_notes')->nullable()->after('delay_approved_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['monthly_submissions', 'weekly_plans', 'weekly_reports', 'submissions', 'quarterly_submissions'];
        $cols = ['delay_reason', 'delay_status', 'delay_approved_by', 'delay_approved_at', 'delay_gm_notes'];

        foreach ($tables as $t) {
            if (Schema::hasTable($t)) {
                Schema::table($t, function (Blueprint $table) use ($t, $cols) {
                    foreach ($cols as $c) {
                        if (Schema::hasColumn($t, $c)) {
                            $table->dropColumn($c);
                        }
                    }
                });
            }
        }

        if (Schema::hasTable('weekly_plans')) {
            Schema::table('weekly_plans', function (Blueprint $table) {
                if (Schema::hasColumn('weekly_plans', 'submission_status')) {
                    $table->dropColumn('submission_status');
                }
                if (Schema::hasColumn('weekly_plans', 'submitted_at')) {
                    $table->dropColumn('submitted_at');
                }
            });
        }
    }
};

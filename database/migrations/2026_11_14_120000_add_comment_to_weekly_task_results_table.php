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
        Schema::table('weekly_task_results', function (Blueprint $table) {
            if (!Schema::hasColumn('weekly_task_results', 'comment')) {
                $table->text('comment')->nullable()->after('completed_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weekly_task_results', function (Blueprint $table) {
            if (Schema::hasColumn('weekly_task_results', 'comment')) {
                $table->dropColumn('comment');
            }
        });
    }
};

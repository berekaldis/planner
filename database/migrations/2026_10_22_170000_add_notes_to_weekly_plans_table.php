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
        if (Schema::hasTable('weekly_plans') && !Schema::hasColumn('weekly_plans', 'notes')) {
            Schema::table('weekly_plans', function (Blueprint $table) {
                $table->text('notes')->nullable()->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('weekly_plans') && Schema::hasColumn('weekly_plans', 'notes')) {
            Schema::table('weekly_plans', function (Blueprint $table) {
                $table->dropColumn('notes');
            });
        }
    }
};

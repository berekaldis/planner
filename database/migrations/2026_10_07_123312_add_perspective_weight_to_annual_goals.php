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
    }
};

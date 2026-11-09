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
        if (Schema::hasTable('departments')) {
            Schema::table('departments', function (Blueprint $table) {
                if (!Schema::hasColumn('departments', 'company_department_id')) {
                    $table->unsignedBigInteger('company_department_id')->nullable()->after('id')->index();
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'company_user_id')) {
                    $table->unsignedBigInteger('company_user_id')->nullable()->after('id')->index();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('departments') && Schema::hasColumn('departments', 'company_department_id')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->dropColumn('company_department_id');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'company_user_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('company_user_id');
            });
        }
    }
};

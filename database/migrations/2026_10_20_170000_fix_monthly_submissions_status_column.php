<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Widens status and approval_status columns on monthly_submissions to VARCHAR(50)
     * to eliminate MySQL Error 1265 "Data truncated for column 'status'".
     */
    public function up(): void
    {
        if (Schema::hasTable('monthly_submissions')) {
            DB::statement("ALTER TABLE `monthly_submissions` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'DRAFT'");
            DB::statement("ALTER TABLE `monthly_submissions` MODIFY COLUMN `approval_status` VARCHAR(50) NOT NULL DEFAULT 'PENDING'");
            DB::statement("ALTER TABLE `monthly_submissions` MODIFY COLUMN `submission_channel` VARCHAR(50) NOT NULL DEFAULT 'WEB'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('monthly_submissions')) {
            DB::statement("ALTER TABLE `monthly_submissions` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'DRAFT'");
            DB::statement("ALTER TABLE `monthly_submissions` MODIFY COLUMN `approval_status` VARCHAR(50) NOT NULL DEFAULT 'PENDING'");
        }
    }
};

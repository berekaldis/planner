<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Monthly Plans: widen targets, DoD, and descriptions
        if (Schema::hasTable('monthly_plans')) {
            try { DB::statement("ALTER TABLE `monthly_plans` MODIFY COLUMN `monthly_target` TEXT NULL"); } catch (\Throwable $e) {}
            try { DB::statement("ALTER TABLE `monthly_plans` MODIFY COLUMN `definition_of_done` TEXT NULL"); } catch (\Throwable $e) {}
            try { DB::statement("ALTER TABLE `monthly_plans` MODIFY COLUMN `description` TEXT NULL"); } catch (\Throwable $e) {}
            try { DB::statement("ALTER TABLE `monthly_plans` MODIFY COLUMN `notes` TEXT NULL"); } catch (\Throwable $e) {}
            try { DB::statement("ALTER TABLE `monthly_plans` MODIFY COLUMN `dependency` TEXT NULL"); } catch (\Throwable $e) {}
            try { DB::statement("ALTER TABLE `monthly_plans` MODIFY COLUMN `decline_reason` TEXT NULL"); } catch (\Throwable $e) {}
        }

        // Weekly Tasks: widen task descriptions, expected results, and notes
        if (Schema::hasTable('weekly_tasks')) {
            try { DB::statement("ALTER TABLE `weekly_tasks` MODIFY COLUMN `expected_result` TEXT NULL"); } catch (\Throwable $e) {}
            try { DB::statement("ALTER TABLE `weekly_tasks` MODIFY COLUMN `task_description` TEXT NULL"); } catch (\Throwable $e) {}
            try { DB::statement("ALTER TABLE `weekly_tasks` MODIFY COLUMN `notes` TEXT NULL"); } catch (\Throwable $e) {}
            try { DB::statement("ALTER TABLE `weekly_tasks` MODIFY COLUMN `dependency` TEXT NULL"); } catch (\Throwable $e) {}
            try { DB::statement("ALTER TABLE `weekly_tasks` MODIFY COLUMN `decline_reason` TEXT NULL"); } catch (\Throwable $e) {}
        }

        // Weekly Task Results: widen explanations, next actions, and comments
        if (Schema::hasTable('weekly_task_results')) {
            try { DB::statement("ALTER TABLE `weekly_task_results` MODIFY COLUMN `not_done_explanation` TEXT NULL"); } catch (\Throwable $e) {}
            try { DB::statement("ALTER TABLE `weekly_task_results` MODIFY COLUMN `next_action` TEXT NULL"); } catch (\Throwable $e) {}
            try { DB::statement("ALTER TABLE `weekly_task_results` MODIFY COLUMN `comment` TEXT NULL"); } catch (\Throwable $e) {}
        }

        // Annual Goals: widen targets and DoD
        if (Schema::hasTable('annual_goals')) {
            try { DB::statement("ALTER TABLE `annual_goals` MODIFY COLUMN `annual_target` TEXT NULL"); } catch (\Throwable $e) {}
            try { DB::statement("ALTER TABLE `annual_goals` MODIFY COLUMN `definition_of_done` TEXT NULL"); } catch (\Throwable $e) {}
            try { DB::statement("ALTER TABLE `annual_goals` MODIFY COLUMN `notes` TEXT NULL"); } catch (\Throwable $e) {}
        }

        // Weekly Achievements & Challenges
        if (Schema::hasTable('weekly_achievements')) {
            try { DB::statement("ALTER TABLE `weekly_achievements` MODIFY COLUMN `achievement_text` TEXT NULL"); } catch (\Throwable $e) {}
        }
        if (Schema::hasTable('weekly_challenges')) {
            try { DB::statement("ALTER TABLE `weekly_challenges` MODIFY COLUMN `challenge_text` TEXT NULL"); } catch (\Throwable $e) {}
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe no-op: retaining TEXT columns avoids truncation of long data
    }
};

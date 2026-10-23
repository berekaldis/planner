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
        $this->updateForeignKey('escalations', 'department_id', 'departments', 'id', 'CASCADE');
        $this->updateForeignKey('submissions', 'department_id', 'departments', 'id', 'CASCADE');
        $this->updateForeignKey('weekly_reports', 'department_id', 'departments', 'id', 'CASCADE');
        $this->updateForeignKey('weekly_plans', 'department_id', 'departments', 'id', 'CASCADE');
        $this->updateForeignKey('weekly_tasks', 'department_id', 'departments', 'id', 'CASCADE');
        $this->updateForeignKey('weekly_task_results', 'department_id', 'departments', 'id', 'CASCADE');
        $this->updateForeignKey('weekly_achievements', 'department_id', 'departments', 'id', 'CASCADE');
        $this->updateForeignKey('weekly_challenges', 'department_id', 'departments', 'id', 'CASCADE');
        $this->updateForeignKey('monthly_plans', 'department_id', 'departments', 'id', 'CASCADE');
        $this->updateForeignKey('annual_goals', 'responsible_department_id', 'departments', 'id', 'SET NULL');
    }

    /**
     * Helper to safely replace any existing foreign key constraint with the new ON DELETE action
     */
    private function updateForeignKey(string $table, string $column, string $refTable, string $refColumn, string $onDelete): void
    {
        if (!Schema::hasTable($table) || !Schema::hasTable($refTable)) {
            return;
        }

        try {
            $dbName = DB::connection()->getDatabaseName();

            // Find all existing foreign keys for this column
            $existingConstraints = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = ? 
                  AND TABLE_NAME = ? 
                  AND COLUMN_NAME = ? 
                  AND REFERENCED_TABLE_NAME = ?
            ", [$dbName, $table, $column, $refTable]);

            foreach ($existingConstraints as $row) {
                try {
                    DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$row->CONSTRAINT_NAME}`");
                } catch (\Throwable $e) {}
            }

            // Also make sure the column is nullable if ON DELETE SET NULL
            if ($onDelete === 'SET NULL') {
                try {
                    DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` INT NULL");
                } catch (\Throwable $e) {
                    try {
                        DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` BIGINT UNSIGNED NULL");
                    } catch (\Throwable $e2) {}
                }
            }

            // Add the updated constraint with ON DELETE action
            $newConstraintName = "fk_{$table}_{$column}_cascade";
            DB::statement("
                ALTER TABLE `{$table}` 
                ADD CONSTRAINT `{$newConstraintName}` 
                FOREIGN KEY (`{$column}`) 
                REFERENCES `{$refTable}` (`{$refColumn}`) 
                ON DELETE {$onDelete}
            ");
        } catch (\Throwable $e) {
            // Graceful fallback if driver/constraint already exists
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep constraints intact
    }
};

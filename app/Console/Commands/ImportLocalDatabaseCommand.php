<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportLocalDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:import-local 
                            {--file= : Custom SQL dump file path (defaults to database/database_export_full.sql)}
                            {--force : Force the operation to run without confirmation in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import full local database dump (database/database_export_full.sql) into the current database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->option('file') 
            ? base_path($this->option('file')) 
            : database_path('database_export_full.sql');

        if (!File::exists($filePath)) {
            $this->error("SQL dump file not found at: {$filePath}");
            return Command::FAILURE;
        }

        $connection = config('database.default');
        $dbName = config("database.connections.{$connection}.database");

        $this->info("Target database: {$dbName} (Connection: {$connection})");
        $this->info("Importing file:  {$filePath} (" . round(File::size($filePath) / 1024, 2) . " KB)");

        if (app()->isProduction() && !$this->option('force')) {
            if (!$this->confirm('Application is in PRODUCTION mode. This will overwrite tables in the database. Do you wish to continue?')) {
                $this->warn('Operation cancelled by user.');
                return Command::SUCCESS;
            }
        }

        $this->output->write('Executing SQL import with foreign key checks temporarily disabled... ');
        $start = microtime(true);

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            $sql = File::get($filePath);
            DB::unprepared($sql);

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $duration = round(microtime(true) - $start, 2);
            $this->info("DONE in {$duration}s!");

            // Run optimize:clear to clear view, config, route caches
            $this->line('Clearing application caches...');
            Artisan::call('optimize:clear');
            $this->line(Artisan::output());

            // Summary of imported data
            $goalsCount = DB::table('annual_goals')->count();
            $deptCount = DB::table('departments')->count();
            $userCount = DB::table('users')->count();
            $tablesCount = count(DB::select('SHOW TABLES'));

            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Tables Created', $tablesCount],
                    ['Departments', $deptCount],
                    ['Users / Roles', $userCount],
                    ['Annual Strategy Goals', $goalsCount],
                ]
            );

            $this->info('Database import completed successfully!');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->error("Error during SQL execution: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

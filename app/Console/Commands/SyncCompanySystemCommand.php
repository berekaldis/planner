<?php

namespace App\Console\Commands;

use App\Services\CompanySystemService;
use Illuminate\Console\Command;

class SyncCompanySystemCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kaldis:sync-company-system';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize departments and designated department heads from the central Company System database (kaldisbp_company_system)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("Connecting to Company System database...");

        if (!CompanySystemService::isAvailable()) {
            $this->error("FAILED: Could not establish connection to Company System database.");
            $this->warn("Please check your COMPANY_DB_* settings in .env or database configuration.");
            return self::FAILURE;
        }

        $this->info("Connection established. Synchronizing departments and department heads...");

        try {
            $result = CompanySystemService::syncDepartments();

            $this->newLine();
            $this->line("=================================================");
            $this->info("  COMPANY SYSTEM SYNCHRONIZATION REPORT");
            $this->line("=================================================");
            $this->line("Total Departments in Company System : {$result['total_company_departments']}");
            $this->line("Synced / Updated in Planner        : {$result['synced_departments']}");
            $this->line("Department Heads Linked            : {$result['heads_synced']}");
            $this->line("=================================================");
            $this->newLine();

            $this->info("Synchronization completed successfully!");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Error during synchronization: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}

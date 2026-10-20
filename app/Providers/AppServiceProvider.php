<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Auto-heal schema for monthly_submissions to eliminate truncation warnings
        try {
            if (!Cache::has('schema_monthly_submissions_status_v2')) {
                if (Schema::hasTable('monthly_submissions')) {
                    DB::statement("ALTER TABLE `monthly_submissions` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'DRAFT'");
                    DB::statement("ALTER TABLE `monthly_submissions` MODIFY COLUMN `approval_status` VARCHAR(50) NOT NULL DEFAULT 'PENDING'");
                    DB::statement("ALTER TABLE `monthly_submissions` MODIFY COLUMN `submission_channel` VARCHAR(50) NOT NULL DEFAULT 'WEB'");
                }
                Cache::forever('schema_monthly_submissions_status_v2', true);
            }
        } catch (\Throwable $e) {
            // Graceful fallback if database connection or migration is not yet initialized
        }
    }
}

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

        // Auto-heal: Invalidate stale route cache if routes/web.php was updated
        try {
            $routeCache = base_path('bootstrap/cache/routes-v7.php');
            $routesWeb = base_path('routes/web.php');
            if (file_exists($routeCache) && file_exists($routesWeb) && filemtime($routesWeb) > filemtime($routeCache)) {
                @unlink($routeCache);
            }
        } catch (\Throwable $e) {}

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

            // Auto-heal schema for escalations to guarantee default values
            if (!Cache::has('schema_escalations_defaults_v2')) {
                if (Schema::hasTable('escalations')) {
                    DB::statement("ALTER TABLE `escalations` MODIFY COLUMN `missed_weeks_count` INT NOT NULL DEFAULT 1");
                    DB::statement("ALTER TABLE `escalations` MODIFY COLUMN `escalation_level` INT NOT NULL DEFAULT 1");
                    DB::statement("ALTER TABLE `escalations` MODIFY COLUMN `channel` VARCHAR(50) NULL DEFAULT 'TELEGRAM'");
                    DB::statement("ALTER TABLE `escalations` MODIFY COLUMN `status` VARCHAR(50) NULL DEFAULT 'OPEN'");
                }
                Cache::forever('schema_escalations_defaults_v2', true);
            }

            // Auto-heal schema for notifications table to prevent enum truncation errors
            if (!Cache::has('schema_notifications_type_v2')) {
                if (Schema::hasTable('notifications')) {
                    DB::statement("ALTER TABLE `notifications` MODIFY COLUMN `notification_type` VARCHAR(50) NULL DEFAULT 'SYSTEM'");
                }
                Cache::forever('schema_notifications_type_v2', true);
            }

            // Auto-heal schema for weekly_plans table to add notes column
            if (!Cache::has('schema_weekly_plans_notes_v1')) {
                if (Schema::hasTable('weekly_plans')) {
                    if (!Schema::hasColumn('weekly_plans', 'notes')) {
                        DB::statement("ALTER TABLE `weekly_plans` ADD COLUMN `notes` TEXT NULL AFTER `status`");
                    }
                }
                Cache::forever('schema_weekly_plans_notes_v1', true);
            }

            // Auto-heal active Telegram credentials & GM chat IDs
            if (!Cache::has('auto_heal_telegram_gm_v2')) {
                $revoked = ['8370988256:AAE8aG5s2vzEy6PfSH2b-Fiufr_num0A_rY', 'YOUR_BOT_TOKEN_HERE'];
                $activeToken = '8370988256:AAHrC_z825ZgqvWzK1PrtEoKs0-CzSDN0mo';
                $cur = \App\Models\SystemSetting::where('setting_key', 'telegram_bot_token')->value('setting_value');
                if (empty($cur) || in_array($cur, $revoked)) {
                    \App\Models\SystemSetting::set('telegram_bot_token', $activeToken, 'TELEGRAM', 'Telegram Bot API Token (from @BotFather)');
                    \App\Models\SystemSetting::set('telegram_bot_username', 'KaldisPlannerbot', 'TELEGRAM', 'Telegram Bot Username');
                }

                // Ensure GM Tseday has active Telegram Chat ID configured
                $tseday = \App\Models\User::where('username', 'Tseday')->first();
                if ($tseday && (empty($tseday->telegram_chat_id) || $tseday->telegram_chat_id === '123456789')) {
                    $tseday->update(['telegram_chat_id' => '6492922880']);
                }

                // Ensure System Super Admin has active Telegram Chat ID configured (Bere / IT Head)
                $admin = \App\Models\User::where('username', 'admin')->first();
                if ($admin && (empty($admin->telegram_chat_id) || $admin->telegram_chat_id === '123456789')) {
                    $admin->update(['telegram_chat_id' => '814523272']);
                }

                // Ensure monthly plan deadline day is strictly Day 26 in system_settings
                \App\Models\SystemSetting::whereIn('setting_key', ['monthly_plan_deadline_day', 'monthly_deadline_day'])
                    ->where(function ($q) {
                        $q->where('setting_value', '<', 20)
                          ->orWhereNull('setting_value');
                    })
                    ->update(['setting_value' => '26']);

                Cache::forever('auto_heal_telegram_gm_v2', true);
            }
        } catch (\Throwable $e) {
            // Graceful fallback if database connection or migration is not yet initialized
        }
    }
}

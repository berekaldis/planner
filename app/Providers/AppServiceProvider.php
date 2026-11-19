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
        // Bind resilient MySQL connector with automatic fallback (TCP 127.0.0.1, alternate ports, socket autodetection)
        $this->app->bind('db.connector.mysql', function () {
            return new \App\Database\Connectors\ResilientMySqlConnector;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        } elseif (app()->environment('production') || str_contains(config('app.url', ''), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

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

            // Auto-heal weekly deadline time to 12:30 (6:30 Ethiopian LT)
            if (!Cache::has('schema_weekly_deadline_time_1230_v1')) {
                if (Schema::hasTable('system_settings')) {
                    \App\Models\SystemSetting::where('setting_key', 'weekly_deadline_time')
                        ->whereIn('setting_value', ['12:00', '12:00:00'])
                        ->update(['setting_value' => '12:30']);
                }
                Cache::forever('schema_weekly_deadline_time_1230_v1', true);
            }

            // Auto-heal schema for quarterly_submissions and monthly_plans performance columns
            if (!Cache::has('schema_quarterly_submissions_v1')) {
                if (!Schema::hasTable('quarterly_submissions')) {
                    DB::statement("CREATE TABLE IF NOT EXISTS `quarterly_submissions` (
                        `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        `department_id` BIGINT UNSIGNED NOT NULL,
                        `year` VARCHAR(50) NOT NULL,
                        `quarter` VARCHAR(10) NOT NULL,
                        `months` VARCHAR(255) NOT NULL,
                        `total_plans` INT NOT NULL DEFAULT 0,
                        `done_plans` INT NOT NULL DEFAULT 0,
                        `not_done_plans` INT NOT NULL DEFAULT 0,
                        `completion_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                        `status` VARCHAR(50) NOT NULL DEFAULT 'DRAFT',
                        `approval_status` VARCHAR(50) NOT NULL DEFAULT 'PENDING',
                        `submission_channel` VARCHAR(50) NOT NULL DEFAULT 'WEB',
                        `submitted_by` BIGINT UNSIGNED NULL,
                        `submitted_at` DATETIME NULL,
                        `executive_summary` TEXT NULL,
                        `challenges` TEXT NULL,
                        `next_quarter_priorities` TEXT NULL,
                        `gm_feedback` TEXT NULL,
                        `gm_feedback_at` DATETIME NULL,
                        `gm_reviewed_by` BIGINT UNSIGNED NULL,
                        `created_at` TIMESTAMP NULL,
                        `updated_at` TIMESTAMP NULL,
                        UNIQUE KEY `uk_dept_quarterly_period` (`department_id`, `year`, `quarter`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                }
                if (Schema::hasTable('monthly_plans')) {
                    if (!Schema::hasColumn('monthly_plans', 'performance_status')) {
                        DB::statement("ALTER TABLE `monthly_plans` ADD COLUMN `performance_status` VARCHAR(50) NOT NULL DEFAULT 'PENDING' AFTER `notes`");
                    }
                    if (!Schema::hasColumn('monthly_plans', 'actual_result')) {
                        DB::statement("ALTER TABLE `monthly_plans` ADD COLUMN `actual_result` TEXT NULL AFTER `performance_status`");
                    }
                    if (!Schema::hasColumn('monthly_plans', 'achievement_percentage')) {
                        DB::statement("ALTER TABLE `monthly_plans` ADD COLUMN `achievement_percentage` INT NOT NULL DEFAULT 0 AFTER `actual_result`");
                    }
                    if (!Schema::hasColumn('monthly_plans', 'completion_notes')) {
                        DB::statement("ALTER TABLE `monthly_plans` ADD COLUMN `completion_notes` TEXT NULL AFTER `achievement_percentage`");
                    }
                }
                Cache::forever('schema_quarterly_submissions_v1', true);
            }

            // Auto-heal foreign keys on departments to cascade delete transient records
            if (!Cache::has('auto_heal_dept_cascade_fks_v2')) {
                $dbName = DB::connection()->getDatabaseName();
                $tablesToCascade = [
                    'escalations' => 'department_id',
                    'submissions' => 'department_id',
                    'weekly_reports' => 'department_id',
                    'weekly_plans' => 'department_id',
                    'weekly_tasks' => 'department_id',
                    'weekly_task_results' => 'department_id',
                    'weekly_achievements' => 'department_id',
                    'weekly_challenges' => 'department_id',
                    'monthly_plans' => 'department_id',
                ];

                foreach ($tablesToCascade as $t => $col) {
                    if (Schema::hasTable($t)) {
                        $fks = DB::select("
                            SELECT CONSTRAINT_NAME 
                            FROM information_schema.KEY_COLUMN_USAGE 
                            WHERE TABLE_SCHEMA = ? 
                              AND TABLE_NAME = ? 
                              AND COLUMN_NAME = ? 
                              AND REFERENCED_TABLE_NAME = 'departments'
                        ", [$dbName, $t, $col]);

                        foreach ($fks as $fkRow) {
                            try {
                                DB::statement("ALTER TABLE `{$t}` DROP FOREIGN KEY `{$fkRow->CONSTRAINT_NAME}`");
                            } catch (\Throwable $e) {}
                        }

                        try {
                            $cName = "fk_{$t}_{$col}_cascade";
                            DB::statement("ALTER TABLE `{$t}` ADD CONSTRAINT `{$cName}` FOREIGN KEY (`{$col}`) REFERENCES `departments` (`id`) ON DELETE CASCADE");
                        } catch (\Throwable $e) {}
                    }
                }

                // annual_goals responsible_department_id should be SET NULL
                if (Schema::hasTable('annual_goals')) {
                    $fks = DB::select("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = ? 
                          AND TABLE_NAME = 'annual_goals' 
                          AND COLUMN_NAME = 'responsible_department_id' 
                          AND REFERENCED_TABLE_NAME = 'departments'
                    ", [$dbName]);

                    foreach ($fks as $fkRow) {
                        try {
                            DB::statement("ALTER TABLE `annual_goals` DROP FOREIGN KEY `{$fkRow->CONSTRAINT_NAME}`");
                        } catch (\Throwable $e) {}
                    }

                    try {
                        DB::statement("ALTER TABLE `annual_goals` MODIFY COLUMN `responsible_department_id` INT NULL");
                        DB::statement("ALTER TABLE `annual_goals` ADD CONSTRAINT `fk_annual_goals_resp_dept_setnull` FOREIGN KEY (`responsible_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL");
                    } catch (\Throwable $e) {}
                }

                Cache::forever('auto_heal_dept_cascade_fks_v2', true);
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

            // Ensure IT Department Head is properly linked so IT receives its own department notifications
            if (!Cache::has('auto_heal_it_dept_head_v1')) {
                if (Schema::hasTable('departments') && Schema::hasTable('users')) {
                    $itDept = \App\Models\Department::where(function ($q) {
                        $q->where('department_code', 'IT')
                          ->orWhere('department_name', 'LIKE', '%Information Technology%')
                          ->orWhere('department_name', 'LIKE', '%IT%');
                    })->first();
                    $admin = \App\Models\User::where('username', 'admin')->first();
                    if ($itDept && $admin) {
                        if (empty($admin->department_id)) {
                            $admin->update(['department_id' => $itDept->id]);
                        }
                        if (empty($itDept->head_user_id)) {
                            $itDept->update(['head_user_id' => $admin->id]);
                        }
                    }
                }
                Cache::forever('auto_heal_it_dept_head_v1', true);
            }
            // Auto-heal schema for company user link columns
            if (!Cache::has('schema_company_system_links_v1')) {
                if (Schema::hasTable('users')) {
                    if (!Schema::hasColumn('users', 'company_user_id')) {
                        DB::statement("ALTER TABLE `users` ADD COLUMN `company_user_id` BIGINT UNSIGNED NULL AFTER `id`, ADD INDEX (`company_user_id`)");
                    }
                    DB::statement("ALTER TABLE `users` MODIFY COLUMN `username` VARCHAR(100) NOT NULL");
                }
                Cache::forever('schema_company_system_links_v1', true);
            }

            // Auto-heal schema for users first-login force password change
            if (Schema::hasTable('users')) {
                if (!Schema::hasColumn('users', 'must_change_password')) {
                    DB::statement("ALTER TABLE `users` ADD COLUMN `must_change_password` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_active`");
                }
                if (!Schema::hasColumn('users', 'password_changed_at')) {
                    DB::statement("ALTER TABLE `users` ADD COLUMN `password_changed_at` DATETIME NULL AFTER `must_change_password`");
                }

                // Ensure all non-superadmin users with no recorded password change have must_change_password = 1
                if (!Cache::has('auto_flag_first_login_password_v4')) {
                    DB::table('users')
                        ->whereNotIn('role_id', [1, 5])
                        ->whereNull('password_changed_at')
                        ->update(['must_change_password' => 1]);
                    Cache::forever('auto_flag_first_login_password_v4', true);
                }
            }

            // Auto-heal schema for GM item-by-item comments
            if (!Cache::has('schema_gm_comments_v1')) {
                if (Schema::hasTable('monthly_plans')) {
                    if (!Schema::hasColumn('monthly_plans', 'gm_comment')) {
                        DB::statement("ALTER TABLE `monthly_plans` ADD COLUMN `gm_comment` TEXT NULL, ADD COLUMN `gm_comment_at` DATETIME NULL, ADD COLUMN `gm_comment_by` BIGINT UNSIGNED NULL");
                    }
                }
                if (Schema::hasTable('weekly_tasks')) {
                    if (!Schema::hasColumn('weekly_tasks', 'gm_comment')) {
                        DB::statement("ALTER TABLE `weekly_tasks` ADD COLUMN `gm_comment` TEXT NULL, ADD COLUMN `gm_comment_at` DATETIME NULL, ADD COLUMN `gm_comment_by` BIGINT UNSIGNED NULL");
                    }
                }
                if (Schema::hasTable('weekly_task_results')) {
                    if (!Schema::hasColumn('weekly_task_results', 'gm_comment')) {
                        DB::statement("ALTER TABLE `weekly_task_results` ADD COLUMN `gm_comment` TEXT NULL, ADD COLUMN `gm_comment_at` DATETIME NULL, ADD COLUMN `gm_comment_by` BIGINT UNSIGNED NULL");
                    }
                }
                Cache::forever('schema_gm_comments_v1', true);
            }

            // Auto-heal schema: widen plan targets, definitions of done, and descriptions to TEXT
            // Prevents SQLSTATE[22001] "Data too long for column 'monthly_target'" and similar truncation errors
            if (!Cache::has('schema_plan_text_columns_v2')) {
                if (Schema::hasTable('monthly_plans')) {
                    try { DB::statement("ALTER TABLE `monthly_plans` MODIFY COLUMN `title` TEXT NULL"); } catch (\Throwable $e) {}
                    try { DB::statement("ALTER TABLE `monthly_plans` MODIFY COLUMN `monthly_target` TEXT NULL"); } catch (\Throwable $e) {}
                    try { DB::statement("ALTER TABLE `monthly_plans` MODIFY COLUMN `definition_of_done` TEXT NULL"); } catch (\Throwable $e) {}
                    try { DB::statement("ALTER TABLE `monthly_plans` MODIFY COLUMN `description` TEXT NULL"); } catch (\Throwable $e) {}
                    try { DB::statement("ALTER TABLE `monthly_plans` MODIFY COLUMN `notes` TEXT NULL"); } catch (\Throwable $e) {}
                    try { DB::statement("ALTER TABLE `monthly_plans` MODIFY COLUMN `dependency` TEXT NULL"); } catch (\Throwable $e) {}
                    try { DB::statement("ALTER TABLE `monthly_plans` MODIFY COLUMN `decline_reason` TEXT NULL"); } catch (\Throwable $e) {}
                }
                if (Schema::hasTable('weekly_tasks')) {
                    try { DB::statement("ALTER TABLE `weekly_tasks` MODIFY COLUMN `task_title` TEXT NULL"); } catch (\Throwable $e) {}
                    try { DB::statement("ALTER TABLE `weekly_tasks` MODIFY COLUMN `expected_result` TEXT NULL"); } catch (\Throwable $e) {}
                    try { DB::statement("ALTER TABLE `weekly_tasks` MODIFY COLUMN `task_description` TEXT NULL"); } catch (\Throwable $e) {}
                    try { DB::statement("ALTER TABLE `weekly_tasks` MODIFY COLUMN `notes` TEXT NULL"); } catch (\Throwable $e) {}
                    try { DB::statement("ALTER TABLE `weekly_tasks` MODIFY COLUMN `dependency` TEXT NULL"); } catch (\Throwable $e) {}
                    try { DB::statement("ALTER TABLE `weekly_tasks` MODIFY COLUMN `decline_reason` TEXT NULL"); } catch (\Throwable $e) {}
                }
                if (Schema::hasTable('weekly_task_results')) {
                    try { DB::statement("ALTER TABLE `weekly_task_results` MODIFY COLUMN `not_done_explanation` TEXT NULL"); } catch (\Throwable $e) {}
                    try { DB::statement("ALTER TABLE `weekly_task_results` MODIFY COLUMN `next_action` TEXT NULL"); } catch (\Throwable $e) {}
                    try { DB::statement("ALTER TABLE `weekly_task_results` MODIFY COLUMN `comment` TEXT NULL"); } catch (\Throwable $e) {}
                }
                if (Schema::hasTable('annual_goals')) {
                    try { DB::statement("ALTER TABLE `annual_goals` MODIFY COLUMN `annual_target` TEXT NULL"); } catch (\Throwable $e) {}
                    try { DB::statement("ALTER TABLE `annual_goals` MODIFY COLUMN `definition_of_done` TEXT NULL"); } catch (\Throwable $e) {}
                    try { DB::statement("ALTER TABLE `annual_goals` MODIFY COLUMN `notes` TEXT NULL"); } catch (\Throwable $e) {}
                }
                if (Schema::hasTable('weekly_achievements')) {
                    try { DB::statement("ALTER TABLE `weekly_achievements` MODIFY COLUMN `achievement_text` TEXT NULL"); } catch (\Throwable $e) {}
                }
                if (Schema::hasTable('weekly_challenges')) {
                    try { DB::statement("ALTER TABLE `weekly_challenges` MODIFY COLUMN `challenge_text` TEXT NULL"); } catch (\Throwable $e) {}
                }
                Cache::forever('schema_plan_text_columns_v2', true);
            }

            // Auto-heal schema & department goal assignments for BI, F&B, and DGM
            if (!Cache::has('auto_heal_bi_fb_dgm_goals_v3')) {
                if (Schema::hasTable('monthly_strategy_activations')) {
                    try {
                        DB::statement("ALTER TABLE `monthly_strategy_activations` MODIFY COLUMN `department_id` INT NULL");
                    } catch (\Throwable $e) {}
                }

                if (Schema::hasTable('departments') && Schema::hasTable('annual_goals')) {
                    $deptsToEnsure = [
                        'BI' => [
                            'name' => 'Business Intelligence & Analytics',
                            'code' => 'BI',
                            'alt_codes' => ['BI'],
                        ],
                        'FB' => [
                            'name' => 'Food & Beverage',
                            'code' => 'F&B',
                            'alt_codes' => ['FB', 'F&B', 'F_B'],
                        ],
                        'DGM' => [
                            'name' => 'Deputy General Manager',
                            'code' => 'DGM',
                            'alt_codes' => ['DGM', 'BD'],
                        ],
                    ];

                    $deptMap = [];
                    foreach ($deptsToEnsure as $key => $info) {
                        $altCodes = $info['alt_codes'];
                        $dept = \App\Models\Department::whereIn('department_code', $altCodes)
                            ->orWhere('department_name', $info['name'])
                            ->orWhere('department_name', 'LIKE', '%' . $info['name'] . '%')
                            ->first();

                        if (!$dept) {
                            $dept = \App\Models\Department::create([
                                'department_name' => $info['name'],
                                'department_code' => $info['code'],
                                'active' => 1,
                            ]);
                        } else {
                            if (!$dept->active) {
                                $dept->update(['active' => 1]);
                            }
                        }
                        $deptMap[$key] = $dept;
                    }

                    $assignments = [
                        'BI' => [
                            'primary' => ['G51', 'G52', 'G53', 'G54', 'G55', 'G56', 'G57', 'G58'],
                            'secondary' => ['G22', 'G27', 'G29', 'G36'],
                        ],
                        'FB' => [
                            'primary' => ['G19', 'G20', 'G21', 'G33', 'G34'],
                            'secondary' => ['G09', 'G10', 'G17', 'G32'],
                        ],
                        'DGM' => [
                            'primary' => ['G08'],
                            'secondary' => ['G02', 'G03', 'G43', 'G44'],
                        ],
                    ];

                    $planningYear = function_exists('kaldis_setting') ? kaldis_setting('current_planning_year', '2019 E.C.') : '2019 E.C.';
                    $months = function_exists('get_ethiopian_months') ? get_ethiopian_months() : [
                        'Meskerem', 'Tikimt', 'Hidar', 'Tahsas', 'Tir', 'Yakatit',
                        'Megabit', 'Miazia', 'Ginbot', 'Sene', 'Hamle', 'Nehase', 'Pagume'
                    ];
                    $now = now();
                    $adminUser = DB::table('users')->where('username', 'admin')->first();
                    $adminId = $adminUser ? $adminUser->id : 1;

                    foreach ($assignments as $deptKey => $goalConfig) {
                        $dept = $deptMap[$deptKey] ?? null;
                        if (!$dept) continue;

                        $primaryCodes = $goalConfig['primary'];
                        $secondaryCodes = $goalConfig['secondary'];
                        $allCodes = array_unique(array_merge($primaryCodes, $secondaryCodes));

                        \App\Models\AnnualGoal::whereIn('goal_code', $primaryCodes)
                            ->update(['responsible_department_id' => $dept->id]);

                        $allGoals = \App\Models\AnnualGoal::whereIn('goal_code', $allCodes)->get();
                        $syncData = [];
                        foreach ($allGoals as $g) {
                            $isPrimary = in_array($g->goal_code, $primaryCodes) ? 1 : 0;
                            $syncData[$g->id] = ['is_primary' => $isPrimary];
                        }
                        $dept->annualGoals()->syncWithoutDetaching($syncData);

                        if (Schema::hasTable('monthly_strategy_activations')) {
                            $activations = [];
                            foreach ($allGoals as $g) {
                                foreach ($months as $m) {
                                    $activations[] = [
                                        'annual_goal_id' => $g->id,
                                        'department_id' => $dept->id,
                                        'year' => $planningYear,
                                        'month' => $m,
                                        'active' => 'NO',
                                        'activated_by' => $adminId,
                                        'activated_at' => $now,
                                        'updated_by' => $adminId,
                                        'updated_at' => $now,
                                    ];
                                }
                            }
                            if (!empty($activations)) {
                                DB::table('monthly_strategy_activations')->insertOrIgnore($activations);
                            }
                        }
                    }
                }
                Cache::forever('auto_heal_bi_fb_dgm_goals_v3', true);
            }
        } catch (\Throwable $e) {
            // Graceful fallback if database connection or migration is not yet initialized
        }
    }
}

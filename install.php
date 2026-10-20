<?php
/**
 * 1-Click Interactive Web & CLI Installer
 * Kaldis Coffee PLC - Strategic Planning & Reporting System
 */

$lockFile = __DIR__ . '/install.lock';
if (file_exists($lockFile)) {
    die('System is already installed. To re-install, delete install.lock first.');
}

// 0. Auto-load .env file if present
if (file_exists(__DIR__ . '/.env')) {
    $envLines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v, " \t\n\r\0\x0B\"'");
        $_ENV[$k] = $v;
        putenv("{$k}={$v}");
    }
}

$config = require __DIR__ . '/config/config.php';

// Override config from .env
if (!empty($_ENV['DB_HOST'])) $config['db_host'] = $_ENV['DB_HOST'];
if (!empty($_ENV['DB_PORT'])) $config['db_port'] = (int)$_ENV['DB_PORT'];
if (!empty($_ENV['DB_DATABASE'])) $config['db_name'] = $_ENV['DB_DATABASE'];
if (!empty($_ENV['DB_USERNAME'])) $config['db_user'] = $_ENV['DB_USERNAME'];
if (isset($_ENV['DB_PASSWORD'])) $config['db_pass'] = $_ENV['DB_PASSWORD'];

// Allow POST override from web form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['db_host'])) $config['db_host'] = trim($_POST['db_host']);
    if (!empty($_POST['db_port'])) $config['db_port'] = (int)$_POST['db_port'];
    if (!empty($_POST['db_name'])) $config['db_name'] = trim($_POST['db_name']);
    if (!empty($_POST['db_user'])) $config['db_user'] = trim($_POST['db_user']);
    if (isset($_POST['db_pass'])) $config['db_pass'] = trim($_POST['db_pass']);
}

$errors = [];
$successMessages = [];
$canInstall = true;

// 1. Check PHP Version
if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    $errors[] = "PHP 8.2 or higher is required. Current version: " . PHP_VERSION;
    $canInstall = false;
} else {
    $successMessages[] = "PHP Version: " . PHP_VERSION . " (Compatible)";
}

// 2. Check Extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'curl', 'json', 'mbstring', 'session'];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $errors[] = "Missing required PHP extension: {$ext}";
        $canInstall = false;
    } else {
        $successMessages[] = "PHP Extension loaded: {$ext}";
    }
}

// Check writable directories
$writableDirs = [__DIR__ . '/uploads', __DIR__ . '/reports', __DIR__ . '/logs'];
foreach ($writableDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (!is_writable($dir)) {
        $errors[] = "Directory is not writable: " . basename($dir);
    }
}

// Execute installation if requested or via CLI
$action = $_POST['action'] ?? ($argc ?? 0 > 1 ? 'install' : '');

if ($action === 'install' && $canInstall) {
    try {
        $dbName = $config['db_name'];
        $pdo = null;

        // 1. Try connecting directly to existing database
        try {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $config['db_host'], $config['db_port'], $dbName, $config['db_charset']);
            $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        } catch (PDOException $directEx) {
            // 2. Fallback: connect without database and attempt CREATE DATABASE
            $dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $config['db_host'], $config['db_port'], $config['db_charset']);
            $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");
        }

        // Schema Definitions
        $tablesSql = "
        -- 1. Roles
        CREATE TABLE IF NOT EXISTS `roles` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(50) NOT NULL UNIQUE,
            `display_name` VARCHAR(100) NOT NULL,
            `description` TEXT NULL
        ) ENGINE=InnoDB;

        -- 2. Departments
        CREATE TABLE IF NOT EXISTS `departments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `department_name` VARCHAR(150) NOT NULL,
            `department_code` VARCHAR(20) NOT NULL UNIQUE,
            `head_user_id` INT NULL,
            `active` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;

        -- 3. Users
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `email` VARCHAR(100) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `remember_token` VARCHAR(100) NULL,
            `full_name` VARCHAR(150) NOT NULL,
            `role_id` INT NOT NULL,
            `department_id` INT NULL,
            `telegram_chat_id` VARCHAR(50) NULL,
            `telegram_username` VARCHAR(50) NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`),
            FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB;

        -- 4. Annual Goals (Master Strategy)
        CREATE TABLE IF NOT EXISTS `annual_goals` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `goal_code` VARCHAR(20) NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `definition_of_done` TEXT NOT NULL,
            `responsible_department_id` INT NOT NULL,
            `annual_target` VARCHAR(255) NOT NULL,
            `priority` ENUM('HIGH', 'MEDIUM', 'LOW') DEFAULT 'MEDIUM',
            `notes` TEXT NULL,
            `status` ENUM('ACTIVE', 'INACTIVE', 'COMPLETED', 'ARCHIVED') DEFAULT 'ACTIVE',
            `year` VARCHAR(50) NOT NULL,
            `perspective` VARCHAR(100) NULL,
            `weight` DECIMAL(5,2) DEFAULT 0.00,
            `created_by` INT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`responsible_department_id`) REFERENCES `departments`(`id`),
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            UNIQUE KEY `uk_goal_year` (`goal_code`, `year`)
        ) ENGINE=InnoDB;

        -- 4b. Annual Goal Multi-Department Ownership
        CREATE TABLE IF NOT EXISTS `annual_goal_departments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `annual_goal_id` INT NOT NULL,
            `department_id` INT NOT NULL,
            `is_primary` TINYINT(1) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`annual_goal_id`) REFERENCES `annual_goals`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
            UNIQUE KEY `uq_goal_dept` (`annual_goal_id`, `department_id`),
            INDEX `idx_agd_goal` (`annual_goal_id`),
            INDEX `idx_agd_dept` (`department_id`)
        ) ENGINE=InnoDB;

        -- 5. GM Monthly Strategy Activations
        CREATE TABLE IF NOT EXISTS `monthly_strategy_activations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `annual_goal_id` INT NOT NULL,
            `department_id` INT NOT NULL,
            `year` VARCHAR(50) NOT NULL,
            `month` VARCHAR(50) NOT NULL,
            `active` ENUM('YES', 'NO') DEFAULT 'NO',
            `activated_by` INT NULL,
            `activated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_by` INT NULL,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`annual_goal_id`) REFERENCES `annual_goals`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`activated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            UNIQUE KEY `uk_activation` (`annual_goal_id`, `department_id`, `year`, `month`)
        ) ENGINE=InnoDB;

        -- 6. Monthly Plans (Strategy & Operational)
        CREATE TABLE IF NOT EXISTS `monthly_plans` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `year` VARCHAR(50) NOT NULL,
            `month` VARCHAR(50) NOT NULL,
            `department_id` INT NOT NULL,
            `plan_type` ENUM('STRATEGY', 'OPERATIONAL') NOT NULL,
            `annual_goal_id` INT NULL,
            `title` VARCHAR(255) NOT NULL,
            `description` TEXT NULL,
            `monthly_target` VARCHAR(255) NOT NULL,
            `definition_of_done` TEXT NOT NULL,
            `target_percentage` INT DEFAULT 100,
            `priority` ENUM('HIGH', 'MEDIUM', 'LOW') DEFAULT 'MEDIUM',
            `dependency` VARCHAR(255) NULL,
            `responsible_person` VARCHAR(150) NULL,
            `notes` TEXT NULL,
            `active` TINYINT(1) DEFAULT 1,
            `created_by` INT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`),
            FOREIGN KEY (`annual_goal_id`) REFERENCES `annual_goals`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB;

        -- 7. Weekly Plans
        CREATE TABLE IF NOT EXISTS `weekly_plans` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `department_id` INT NOT NULL,
            `year` VARCHAR(50) NOT NULL,
            `month` VARCHAR(50) NOT NULL,
            `week_number` TINYINT NOT NULL,
            `status` ENUM('DRAFT', 'SUBMITTED', 'LOCKED') DEFAULT 'DRAFT',
            `created_by` INT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`),
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            UNIQUE KEY `uk_dept_week` (`department_id`, `year`, `month`, `week_number`)
        ) ENGINE=InnoDB;

        -- 8. Weekly Tasks
        CREATE TABLE IF NOT EXISTS `weekly_tasks` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `weekly_plan_id` INT NOT NULL,
            `monthly_plan_id` INT NULL,
            `department_id` INT NOT NULL,
            `year` VARCHAR(50) NOT NULL,
            `month` VARCHAR(50) NOT NULL,
            `week_number` TINYINT NOT NULL,
            `plan_type` ENUM('STRATEGY', 'OPERATIONAL') NOT NULL,
            `annual_goal_id` INT NULL,
            `task_title` VARCHAR(255) NOT NULL,
            `task_description` TEXT NULL,
            `expected_result` TEXT NOT NULL,
            `responsible_person` VARCHAR(150) NOT NULL,
            `priority` ENUM('HIGH', 'MEDIUM', 'LOW') DEFAULT 'MEDIUM',
            `due_date` DATE NULL,
            `dependency` VARCHAR(255) NULL,
            `notes` TEXT NULL,
            `created_by` INT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`weekly_plan_id`) REFERENCES `weekly_plans`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`monthly_plan_id`) REFERENCES `monthly_plans`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`),
            FOREIGN KEY (`annual_goal_id`) REFERENCES `annual_goals`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB;

        -- 9. Reason Categories for NOT DONE
        CREATE TABLE IF NOT EXISTS `reason_categories` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(150) NOT NULL UNIQUE,
            `is_active` TINYINT(1) DEFAULT 1,
            `display_order` INT DEFAULT 0
        ) ENGINE=InnoDB;

        -- 10. Weekly Task Results (Strictly DONE / NOT DONE)
        CREATE TABLE IF NOT EXISTS `weekly_task_results` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `weekly_task_id` INT NOT NULL,
            `department_id` INT NOT NULL,
            `year` VARCHAR(50) NOT NULL,
            `month` VARCHAR(50) NOT NULL,
            `week_number` TINYINT NOT NULL,
            `result` ENUM('DONE', 'NOT_DONE') NOT NULL,
            `completed_at` DATETIME NULL,
            `completed_by` INT NULL,
            `not_done_reason_id` INT NULL,
            `not_done_explanation` TEXT NULL,
            `next_action` TEXT NULL,
            `expected_completion_date` DATE NULL,
            `attachment` VARCHAR(255) NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`weekly_task_id`) REFERENCES `weekly_tasks`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`),
            FOREIGN KEY (`completed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`not_done_reason_id`) REFERENCES `reason_categories`(`id`) ON DELETE SET NULL,
            UNIQUE KEY `uk_task_result` (`weekly_task_id`)
        ) ENGINE=InnoDB;

        -- 11. Weekly Achievements (Separate entity)
        CREATE TABLE IF NOT EXISTS `weekly_achievements` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `department_id` INT NOT NULL,
            `year` VARCHAR(50) NOT NULL,
            `month` VARCHAR(50) NOT NULL,
            `week_number` TINYINT NOT NULL,
            `achievement_text` TEXT NOT NULL,
            `attachment` VARCHAR(255) NULL,
            `created_by` INT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`),
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB;

        -- 12. Weekly Challenges (Separate entity)
        CREATE TABLE IF NOT EXISTS `weekly_challenges` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `department_id` INT NOT NULL,
            `year` VARCHAR(50) NOT NULL,
            `month` VARCHAR(50) NOT NULL,
            `week_number` TINYINT NOT NULL,
            `challenge_text` TEXT NOT NULL,
            `related_weekly_task_id` INT NULL,
            `attachment` VARCHAR(255) NULL,
            `created_by` INT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`),
            FOREIGN KEY (`related_weekly_task_id`) REFERENCES `weekly_tasks`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB;

        -- 13. Weekly Reports Submission Master
        CREATE TABLE IF NOT EXISTS `weekly_reports` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `department_id` INT NOT NULL,
            `year` VARCHAR(50) NOT NULL,
            `month` VARCHAR(50) NOT NULL,
            `week_number` TINYINT NOT NULL,
            `status` ENUM('DRAFT', 'SUBMITTED') DEFAULT 'DRAFT',
            `total_tasks` INT DEFAULT 0,
            `done_tasks` INT DEFAULT 0,
            `not_done_tasks` INT DEFAULT 0,
            `completion_percentage` DECIMAL(5,2) DEFAULT 0.00,
            `submitted_by` INT NULL,
            `submitted_at` DATETIME NULL,
            `submission_status` ENUM('WAITING', 'ON_TIME', 'LATE', 'MISSING') DEFAULT 'WAITING',
            `notes` TEXT NULL,
            `gm_feedback` TEXT NULL,
            `gm_feedback_at` DATETIME NULL,
            `approval_status` ENUM('PENDING', 'APPROVED', 'REVISION_REQUESTED') DEFAULT 'PENDING',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`),
            FOREIGN KEY (`submitted_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            UNIQUE KEY `uk_dept_report_period` (`department_id`, `year`, `month`, `week_number`)
        ) ENGINE=InnoDB;

        -- 14. Submissions Tracker
        CREATE TABLE IF NOT EXISTS `submissions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `department_id` INT NOT NULL,
            `year` VARCHAR(50) NOT NULL,
            `month` VARCHAR(50) NOT NULL,
            `week_number` TINYINT NOT NULL,
            `deadline_at` DATETIME NOT NULL,
            `submitted_at` DATETIME NULL,
            `status` ENUM('WAITING', 'ON_TIME', 'LATE', 'MISSING') DEFAULT 'WAITING',
            `submission_channel` ENUM('WEB', 'TELEGRAM') DEFAULT 'WEB',
            `submitted_by` INT NULL,
            `consecutive_missed_count` INT DEFAULT 0,
            `approval_status` ENUM('PENDING', 'APPROVED', 'REVISION_REQUESTED') DEFAULT 'PENDING',
            `remarks` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`),
            FOREIGN KEY (`submitted_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            UNIQUE KEY `uk_submission_period` (`department_id`, `year`, `month`, `week_number`)
        ) ENGINE=InnoDB;

        -- 14b. Monthly Submissions Tracker
        CREATE TABLE IF NOT EXISTS `monthly_submissions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `department_id` INT NOT NULL,
            `year` VARCHAR(50) NOT NULL,
            `month` VARCHAR(50) NOT NULL,
            `deadline_at` DATETIME NULL,
            `submitted_at` DATETIME NULL,
            `status` ENUM('DRAFT', 'SUBMITTED', 'LATE') DEFAULT 'DRAFT',
            `submission_channel` ENUM('WEB', 'TELEGRAM') DEFAULT 'WEB',
            `submitted_by` INT NULL,
            `approval_status` ENUM('PENDING', 'APPROVED', 'NEEDS_REVISION') DEFAULT 'PENDING',
            `gm_feedback` TEXT NULL,
            `gm_feedback_at` DATETIME NULL,
            `remarks` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`submitted_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            UNIQUE KEY `uk_dept_monthly_period` (`department_id`, `year`, `month`)
        ) ENGINE=InnoDB;

        -- 15. Submission Status Change Logs
        CREATE TABLE IF NOT EXISTS `submission_status_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `submission_id` INT NOT NULL,
            `department_id` INT NOT NULL,
            `year` VARCHAR(50) NOT NULL,
            `month` VARCHAR(50) NOT NULL,
            `week_number` TINYINT NOT NULL,
            `old_status` VARCHAR(50) NULL,
            `new_status` VARCHAR(50) NOT NULL,
            `changed_by` INT NULL,
            `reason` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`submission_id`) REFERENCES `submissions`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB;

        -- 16. Telegram Users
        CREATE TABLE IF NOT EXISTS `telegram_users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `telegram_user_id` VARCHAR(100) NOT NULL UNIQUE,
            `username` VARCHAR(100) NULL,
            `first_name` VARCHAR(100) NULL,
            `last_name` VARCHAR(100) NULL,
            `user_id` INT NULL,
            `department_id` INT NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB;

        -- 17. Telegram Messages
        CREATE TABLE IF NOT EXISTS `telegram_messages` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `message_id` VARCHAR(100) NOT NULL,
            `telegram_user_id` VARCHAR(100) NOT NULL,
            `department_id` INT NULL,
            `year` VARCHAR(50) NULL,
            `month` VARCHAR(50) NULL,
            `week_number` TINYINT NULL,
            `message_text` TEXT NULL,
            `file_id` VARCHAR(255) NULL,
            `file_name` VARCHAR(255) NULL,
            `file_path` VARCHAR(255) NULL,
            `direction` ENUM('INCOMING', 'OUTGOING') DEFAULT 'INCOMING',
            `status` VARCHAR(50) DEFAULT 'PROCESSED',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB;

        -- 18. Telegram Files
        CREATE TABLE IF NOT EXISTS `telegram_files` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `file_id` VARCHAR(255) NOT NULL UNIQUE,
            `file_unique_id` VARCHAR(255) NULL,
            `file_name` VARCHAR(255) NOT NULL,
            `mime_type` VARCHAR(100) NULL,
            `file_size` INT NULL,
            `local_path` VARCHAR(255) NOT NULL,
            `uploaded_by_telegram_user_id` VARCHAR(100) NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;

        -- 19. In-App Notifications
        CREATE TABLE IF NOT EXISTS `notifications` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NULL,
            `department_id` INT NULL,
            `title` VARCHAR(255) NOT NULL,
            `message` TEXT NOT NULL,
            `notification_type` ENUM('REMINDER', 'ESCALATION', 'SYSTEM', 'REPORT_SUBMISSION') DEFAULT 'SYSTEM',
            `is_read` TINYINT(1) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB;

        -- 20. Escalations
        CREATE TABLE IF NOT EXISTS `escalations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `department_id` INT NOT NULL,
            `year` VARCHAR(50) NOT NULL,
            `month` VARCHAR(50) NOT NULL,
            `week_number` TINYINT NOT NULL,
            `missed_weeks_count` INT NOT NULL,
            `escalation_level` INT NOT NULL,
            `triggered_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `recipients_snapshot` TEXT NULL,
            `channel` VARCHAR(50) DEFAULT 'ALL',
            `status` ENUM('OPEN', 'RESOLVED', 'DISMISSED') DEFAULT 'OPEN',
            `resolved_at` DATETIME NULL,
            `resolved_by` INT NULL,
            `resolution_notes` TEXT NULL,
            FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`),
            FOREIGN KEY (`resolved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB;

        -- 21. System Settings
        CREATE TABLE IF NOT EXISTS `system_settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `setting_key` VARCHAR(100) NOT NULL UNIQUE,
            `setting_value` TEXT NULL,
            `setting_group` VARCHAR(50) DEFAULT 'GENERAL',
            `description` VARCHAR(255) NULL,
            `updated_by` INT NULL,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;

        -- 22. Audit Logs
        CREATE TABLE IF NOT EXISTS `audit_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NULL,
            `action` VARCHAR(100) NOT NULL,
            `module` VARCHAR(100) NOT NULL,
            `record_id` VARCHAR(50) NULL,
            `old_values` LONGTEXT NULL,
            `new_values` LONGTEXT NULL,
            `ip_address` VARCHAR(50) NULL,
            `user_agent` VARCHAR(255) NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_module_action` (`module`, `action`),
            INDEX `idx_user_time` (`user_id`, `created_at`)
        ) ENGINE=InnoDB;

        -- 23. Sessions
        CREATE TABLE IF NOT EXISTS `sessions` (
            `id` VARCHAR(128) PRIMARY KEY,
            `user_id` INT NULL,
            `ip_address` VARCHAR(50) NULL,
            `user_agent` VARCHAR(255) NULL,
            `payload` LONGTEXT NOT NULL,
            `last_activity` INT NOT NULL
        ) ENGINE=InnoDB;
        ";

        // Execute table creation statements
        $statements = array_filter(array_map('trim', explode(';', $tablesSql)));
        foreach ($statements as $stmt) {
            if (!empty($stmt)) {
                try {
                    $pdo->exec($stmt);
                } catch (PDOException $ex) {
                    $msg = $ex->getMessage();
                    // Ignore duplicate constraint, duplicate table, or errno 121 if re-running installer
                    if (!str_contains($msg, 'already exists') && 
                        !str_contains($msg, 'Duplicate foreign key') &&
                        !str_contains($msg, 'errno: 121') &&
                        !str_contains($msg, 'Duplicate key on write or update')) {
                        throw $ex;
                    }
                }
            }
        }

        // Add foreign key for departments.head_user_id gracefully
        try {
            $pdo->exec("ALTER TABLE `departments` ADD CONSTRAINT `fk_dept_head` FOREIGN KEY (`head_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL");
        } catch (PDOException $ex) {
            // Already added or not supported, safe to ignore
        }

        // Auto-patch missing governance columns on existing databases
        $columnPatches = [
            "ALTER TABLE `annual_goals` ADD COLUMN `perspective` VARCHAR(100) NULL AFTER `year`",
            "ALTER TABLE `annual_goals` ADD COLUMN `weight` DECIMAL(5,2) DEFAULT 0.00 AFTER `perspective`",
            "ALTER TABLE `submissions` ADD COLUMN `approval_status` ENUM('PENDING','APPROVED','REVISION_REQUESTED') DEFAULT 'PENDING' AFTER `consecutive_missed_count`",
            "ALTER TABLE `weekly_reports` ADD COLUMN `gm_feedback` TEXT NULL AFTER `notes`",
            "ALTER TABLE `weekly_reports` ADD COLUMN `gm_feedback_at` DATETIME NULL AFTER `gm_feedback`",
            "ALTER TABLE `weekly_reports` ADD COLUMN `approval_status` ENUM('PENDING','APPROVED','REVISION_REQUESTED') DEFAULT 'PENDING' AFTER `gm_feedback_at`",
            "ALTER TABLE `users` ADD COLUMN `remember_token` VARCHAR(100) NULL AFTER `password_hash`",
            "ALTER TABLE `monthly_submissions` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'DRAFT'",
            "ALTER TABLE `monthly_submissions` MODIFY COLUMN `approval_status` VARCHAR(50) NOT NULL DEFAULT 'PENDING'",
            "ALTER TABLE `monthly_submissions` MODIFY COLUMN `submission_channel` VARCHAR(50) NOT NULL DEFAULT 'WEB'"
        ];
        foreach ($columnPatches as $patch) {
            try {
                $pdo->exec($patch);
            } catch (PDOException $ex) {
                // Column already exists or duplicate, safe to ignore
            }
        }

        // 3. Seed Roles
        $roles = [
            ['name' => 'super_admin', 'display_name' => 'Super Administrator', 'description' => 'Unrestricted company-wide management & configuration.'],
            ['name' => 'gm_management', 'display_name' => 'General Manager / Management', 'description' => 'Strategy activation, executive oversight, performance reviews.'],
            ['name' => 'hr', 'display_name' => 'Human Resources', 'description' => 'Submission compliance, escalation review, department analytics.'],
            ['name' => 'dept_head', 'display_name' => 'Department Head', 'description' => 'Own department monthly planning, weekly tasks, and performance submission.'],
            ['name' => 'it_admin', 'display_name' => 'IT Administrator', 'description' => 'System configuration, Telegram bot, database operations.']
        ];
        $insertRoleStmt = $pdo->prepare("INSERT IGNORE INTO `roles` (`name`, `display_name`, `description`) VALUES (?, ?, ?)");
        foreach ($roles as $r) {
            $insertRoleStmt->execute([$r['name'], $r['display_name'], $r['description']]);
        }

        // 4. Seed Departments
        $departments = [
            ['name' => 'Information Technology', 'code' => 'IT'],
            ['name' => 'Retail Operations', 'code' => 'OPS'],
            ['name' => 'Marketing & Brand Strategy', 'code' => 'MKT'],
            ['name' => 'Supply Chain & Procurement', 'code' => 'SCM'],
            ['name' => 'Human Resources', 'code' => 'HR'],
            ['name' => 'Finance & Accounting', 'code' => 'FIN'],
            ['name' => 'Quality Assurance & Roastery', 'code' => 'QAR'],
            ['name' => 'Project Management', 'code' => 'PM'],
            ['name' => 'Training & Development', 'code' => 'TND'],
            ['name' => 'Product R&D & Innovation', 'code' => 'RND'],
            ['name' => 'OSH & Environmental Safety', 'code' => 'SAFE'],
            ['name' => 'Logistics & Maintenance', 'code' => 'LOG'],
            ['name' => 'Internal Audit & Compliance', 'code' => 'AUD'],
            ['name' => 'Business Intelligence & Analytics', 'code' => 'BI'],
            ['name' => 'Food & Beverage', 'code' => 'FB'],
            ['name' => 'Business Development', 'code' => 'BD']
        ];
        $insertDeptStmt = $pdo->prepare("INSERT IGNORE INTO `departments` (`department_name`, `department_code`) VALUES (?, ?)");
        foreach ($departments as $d) {
            $insertDeptStmt->execute([$d['name'], $d['code']]);
        }

        // 5. Seed Reason Categories
        $reasons = [
            'Waiting for Approval',
            'Waiting for Another Department',
            'Waiting for Management Decision',
            'Technical Issue',
            'Supplier/Vendor Delay',
            'Budget/Procurement Issue',
            'Resource Issue',
            'Requirement Not Received',
            'External Dependency',
            'Other'
        ];
        $insertReasonStmt = $pdo->prepare("INSERT IGNORE INTO `reason_categories` (`name`, `display_order`) VALUES (?, ?)");
        foreach ($reasons as $order => $reason) {
            $insertReasonStmt->execute([$reason, $order + 1]);
        }

        // Get Role IDs
        $roleMap = $pdo->query("SELECT name, id FROM roles")->fetchAll(PDO::FETCH_KEY_PAIR);
        $deptMap = $pdo->query("SELECT department_code, id FROM departments")->fetchAll(PDO::FETCH_KEY_PAIR);

        // 6. Seed Default Users (Password: admin123)
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $users = [
            ['username' => 'admin', 'email' => 'admin@kaldiscoffee.com', 'full_name' => 'System Super Admin', 'role_id' => $roleMap['super_admin'], 'dept_id' => null],
            ['username' => 'gm', 'email' => 'gm@kaldiscoffee.com', 'full_name' => 'General Manager', 'role_id' => $roleMap['gm_management'], 'dept_id' => null],
            ['username' => 'it_head', 'email' => 'it.head@kaldiscoffee.com', 'full_name' => 'Dawit Tadesse (IT Head)', 'role_id' => $roleMap['dept_head'], 'dept_id' => $deptMap['IT']],
            ['username' => 'ops_head', 'email' => 'ops.head@kaldiscoffee.com', 'full_name' => 'Almaz Bekele (Ops Head)', 'role_id' => $roleMap['dept_head'], 'dept_id' => $deptMap['OPS']],
            ['username' => 'hr_officer', 'email' => 'hr@kaldiscoffee.com', 'full_name' => 'Hanna Solomon (HR Officer)', 'role_id' => $roleMap['hr'], 'dept_id' => $deptMap['HR']],
        ];

        $insertUserStmt = $pdo->prepare("
            INSERT IGNORE INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role_id`, `department_id`)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($users as $u) {
            $insertUserStmt->execute([$u['username'], $u['email'], $passwordHash, $u['full_name'], $u['role_id'], $u['dept_id']]);
        }

        // Assign IT Head and Ops Head in departments table
        $userMap = $pdo->query("SELECT username, id FROM users")->fetchAll(PDO::FETCH_KEY_PAIR);
        if (!empty($userMap['it_head']) && !empty($deptMap['IT'])) {
            $pdo->exec("UPDATE departments SET head_user_id = {$userMap['it_head']} WHERE id = {$deptMap['IT']}");
        }
        if (!empty($userMap['ops_head']) && !empty($deptMap['OPS'])) {
            $pdo->exec("UPDATE departments SET head_user_id = {$userMap['ops_head']} WHERE id = {$deptMap['OPS']}");
        }

                // 7. Seed Official 58 Annual Strategic Goals (2019 E.C.)
        $goals = [
[
                'code' => 'G01',
                'title' => "Renovate 2 branches per month to new Kaldi's/EFDA standards; visible logo added to each",
                'dod' => '24 branches renovated by year-end; logo visible at every renovated branch',
                'target' => '24 branches renovated by year-end',
                'priority' => 'HIGH',
                'perspective' => 'Customer',
                'weight' => 1.73,
                'primary_dept' => 'PM',
                'all_depts' => ['PM'],
                'notes' => 'Project Manager',
            ],
            [
                'code' => 'G02',
                'title' => 'Open 5 new stores (excluding replacements for closed branches)',
                'dod' => '5 new stores trading by year-end',
                'target' => '5 new stores trading by year-end',
                'priority' => 'HIGH',
                'perspective' => 'Customer',
                'weight' => 1.73,
                'primary_dept' => 'PM',
                'all_depts' => ['PM', 'OPS', 'MKT'],
                'notes' => 'Project Manager / Operations / Marketing',
            ],
            [
                'code' => 'G03',
                'title' => 'Rebrand 12 branches with new branding and interior design',
                'dod' => '12 branches fully rebranded by year-end',
                'target' => '12 branches fully rebranded by year-end',
                'priority' => 'HIGH',
                'perspective' => 'Customer',
                'weight' => 1.73,
                'primary_dept' => 'PM',
                'all_depts' => ['PM', 'MKT'],
                'notes' => 'Project Manager / Marketing',
            ],
            [
                'code' => 'G04',
                'title' => 'All staff trained: new manuals, PowerPoints, pre/post assessments, follow-up trainings per QA guideline',
                'dod' => '100% of staff assessed pre and post; follow-up training calendar running',
                'target' => '100% of staff assessed pre and post',
                'priority' => 'HIGH',
                'perspective' => 'Learning & Growth',
                'weight' => 1.73,
                'primary_dept' => 'TND',
                'all_depts' => ['TND'],
                'notes' => 'Training & Development',
            ],
            [
                'code' => 'G05',
                'title' => 'All frontline staff inspected 2x per month; reward/probation protocol applied',
                'dod' => 'Every frontline staff member has 2 inspection records per month; actions logged',
                'target' => '2 inspection records per frontline staff per month',
                'priority' => 'HIGH',
                'perspective' => 'Internal Process',
                'weight' => 1.73,
                'primary_dept' => 'QAR',
                'all_depts' => ['QAR'],
                'notes' => 'Quality Assurance',
            ],
            [
                'code' => 'G06',
                'title' => 'Branch managers and shift leaders complete AM + PM checklists daily',
                'dod' => 'Checklist completion rate visible daily; misses escalated',
                'target' => 'Daily AM + PM checklist completion tracked across all branches',
                'priority' => 'HIGH',
                'perspective' => 'Internal Process',
                'weight' => 1.73,
                'primary_dept' => 'OPS',
                'all_depts' => ['OPS'],
                'notes' => 'Operations',
            ],
            [
                'code' => 'G07',
                'title' => 'Roasted & packed coffee sold at branches and supermarkets',
                'dod' => 'Product live at all branches + supermarket accounts opened',
                'target' => 'Live at all branches + supermarket retail distribution',
                'priority' => 'HIGH',
                'perspective' => 'Customer',
                'weight' => 1.73,
                'primary_dept' => 'MKT',
                'all_depts' => ['MKT', 'SCM', 'LOG'],
                'notes' => 'Marketing / Import-Export',
            ],
            [
                'code' => 'G08',
                'title' => '1 franchise store launched',
                'dod' => 'First franchise location open and trading',
                'target' => 'First franchise location open and trading',
                'priority' => 'MEDIUM',
                'perspective' => 'Customer',
                'weight' => 1.73,
                'primary_dept' => 'BD',
                'all_depts' => ['BD', 'PM', 'OPS'],
                'notes' => 'DGM / Business Development',
            ],
            [
                'code' => 'G09',
                'title' => 'Kitchens removed from branches; fast-heating ovens installed; prep moved to production',
                'dod' => 'All branch kitchens converted; central production supplying',
                'target' => '100% branch kitchens converted to ovens; central production supply',
                'priority' => 'HIGH',
                'perspective' => 'Internal Process',
                'weight' => 1.73,
                'primary_dept' => 'OPS',
                'all_depts' => ['OPS', 'SCM', 'QAR'],
                'notes' => 'Operations / Import / Production',
            ],
            [
                'code' => 'G10',
                'title' => 'Soda discontinued; replaced with fizzy drinks',
                'dod' => 'Soda phased out at all branches; fizzy drink line live',
                'target' => 'Soda phased out; fizzy drinks live in 100% of branches',
                'priority' => 'MEDIUM',
                'perspective' => 'Customer',
                'weight' => 1.73,
                'primary_dept' => 'MKT',
                'all_depts' => ['MKT', 'SCM', 'OPS'],
                'notes' => 'Marketing / Import / Operations',
            ],
            [
                'code' => 'G11',
                'title' => '30+ gym partnerships with member discount card',
                'dod' => '30 gyms signed (~3 per month run-rate)',
                'target' => '30 gyms signed (~3 per month run-rate)',
                'priority' => 'MEDIUM',
                'perspective' => 'Customer',
                'weight' => 1.73,
                'primary_dept' => 'MKT',
                'all_depts' => ['MKT'],
                'notes' => 'Marketing',
            ],
            [
                'code' => 'G12',
                'title' => 'Coffee subscription program implemented',
                'dod' => 'Subscription live with enrollment tracked monthly',
                'target' => 'Subscription program live and tracked monthly',
                'priority' => 'MEDIUM',
                'perspective' => 'Customer',
                'weight' => 1.73,
                'primary_dept' => 'MKT',
                'all_depts' => ['MKT'],
                'notes' => 'Marketing',
            ],
            [
                'code' => 'G13',
                'title' => 'Live on 3 delivery apps',
                'dod' => 'Orders flowing from 3 apps',
                'target' => 'Orders actively flowing from 3 delivery apps',
                'priority' => 'HIGH',
                'perspective' => 'Customer',
                'weight' => 1.73,
                'primary_dept' => 'MKT',
                'all_depts' => ['MKT', 'IT', 'OPS'],
                'notes' => 'Marketing / IT / Operations',
            ],
            [
                'code' => 'G14',
                'title' => '40+ event partnerships (catering, booths)',
                'dod' => '40 events done (~4 per month run-rate)',
                'target' => '40 events delivered (~4 per month run-rate)',
                'priority' => 'MEDIUM',
                'perspective' => 'Customer',
                'weight' => 1.73,
                'primary_dept' => 'MKT',
                'all_depts' => ['MKT', 'OPS'],
                'notes' => 'Marketing / Operations',
            ],
            [
                'code' => 'G15',
                'title' => 'Bundle deals available at cafes (coffee + pastry)',
                'dod' => 'Bundles on menu at all branches',
                'target' => 'Bundles available on menu at all branches',
                'priority' => 'MEDIUM',
                'perspective' => 'Customer',
                'weight' => 1.73,
                'primary_dept' => 'MKT',
                'all_depts' => ['MKT'],
                'notes' => 'Marketing',
            ],
            [
                'code' => 'G16',
                'title' => 'Loyalty program, customer database, birthday gift system solidified',
                'dod' => 'System live; birthday gifts sending automatically',
                'target' => 'Loyalty database live; automated birthday gifts active',
                'priority' => 'HIGH',
                'perspective' => 'Customer',
                'weight' => 1.73,
                'primary_dept' => 'MKT',
                'all_depts' => ['MKT', 'IT'],
                'notes' => 'Marketing / IT',
            ],
            [
                'code' => 'G17',
                'title' => 'Seasonal menus: rainy season, spring/new year, mango season',
                'dod' => 'Each seasonal menu launched on time',
                'target' => '3 seasonal menu launches completed on time',
                'priority' => 'MEDIUM',
                'perspective' => 'Customer',
                'weight' => 1.73,
                'primary_dept' => 'MKT',
                'all_depts' => ['MKT', 'FB', 'RND'],
                'notes' => 'Marketing',
            ],
            [
                'code' => 'G18',
                'title' => '4 strategic partnerships with trendy companies',
                'dod' => '4 partnerships signed (1 per quarter)',
                'target' => '4 partnerships signed (1 per quarter)',
                'priority' => 'MEDIUM',
                'perspective' => 'Customer',
                'weight' => 1.73,
                'primary_dept' => 'MKT',
                'all_depts' => ['MKT'],
                'notes' => 'Marketing',
            ],
            [
                'code' => 'G19',
                'title' => 'Recipe on file for every menu item, approved and uploaded to CNET without error',
                'dod' => '100% menu coverage; zero recipe errors on CNET',
                'target' => '100% menu coverage; zero recipe errors on CNET',
                'priority' => 'HIGH',
                'perspective' => 'Internal Process',
                'weight' => 1.73,
                'primary_dept' => 'FB',
                'all_depts' => ['FB', 'RND', 'QAR', 'IT'],
                'notes' => 'F&B (Tseday) / Production',
            ],
            [
                'code' => 'G20',
                'title' => 'Cost % goal set per department (e.g., hot beverage 21-24%)',
                'dod' => 'Targets published; tracked monthly on dashboard',
                'target' => 'Targets published; tracked monthly on dashboard',
                'priority' => 'HIGH',
                'perspective' => 'Financial',
                'weight' => 1.73,
                'primary_dept' => 'FB',
                'all_depts' => ['FB', 'RND', 'FIN'],
                'notes' => 'F&B (Tseday)',
            ],
            [
                'code' => 'G21',
                'title' => 'Price adjustments per standard every 3 months',
                'dod' => '4 pricing reviews completed on schedule',
                'target' => '4 pricing reviews completed on schedule',
                'priority' => 'HIGH',
                'perspective' => 'Financial',
                'weight' => 1.73,
                'primary_dept' => 'FB',
                'all_depts' => ['FB', 'RND', 'FIN'],
                'notes' => 'F&B (Tseday)',
            ],
            [
                'code' => 'G22',
                'title' => 'COGS dashboard set on SIV (not recipe)',
                'dod' => 'Dashboard live; monthly SIV vs recipe gap reviewed',
                'target' => 'Dashboard live; monthly SIV vs recipe gap reviewed',
                'priority' => 'HIGH',
                'perspective' => 'Financial',
                'weight' => 1.73,
                'primary_dept' => 'SCM',
                'all_depts' => ['SCM', 'FIN', 'BI', 'IT'],
                'notes' => 'Supply Chain / Finance / BI',
            ],
            [
                'code' => 'G23',
                'title' => 'Financial analyst team hired: balance sheet, cash flow, investment planning, cost analysis',
                'dod' => 'Team in seat with JDs/KPIs; monthly analysis pack delivered',
                'target' => 'Financial analyst team in seat; monthly analysis pack delivered',
                'priority' => 'HIGH',
                'perspective' => 'Financial',
                'weight' => 1.73,
                'primary_dept' => 'FIN',
                'all_depts' => ['FIN', 'HR'],
                'notes' => 'Finance',
            ],
            [
                'code' => 'G24',
                'title' => 'Standard set for SIV cost percentage',
                'dod' => 'Standard published; branches measured against it',
                'target' => 'Standard published; branches measured against it',
                'priority' => 'HIGH',
                'perspective' => 'Financial',
                'weight' => 1.73,
                'primary_dept' => 'SCM',
                'all_depts' => ['SCM', 'FIN'],
                'notes' => 'Supply Chain / Finance',
            ],
            [
                'code' => 'G25',
                'title' => 'Contract renewals every 3 months with price research',
                'dod' => 'All supplier contracts on 3-month cycle; renewal log kept',
                'target' => 'All supplier contracts on 3-month cycle; renewal log kept',
                'priority' => 'HIGH',
                'perspective' => 'Internal Process',
                'weight' => 1.72,
                'primary_dept' => 'SCM',
                'all_depts' => ['SCM'],
                'notes' => 'Supply Chain',
            ],
            [
                'code' => 'G26',
                'title' => 'Minimum 3 proformas collected and stored for every purchase',
                'dod' => 'Zero purchases without 3 proformas on file',
                'target' => 'Zero purchases without 3 proformas on file',
                'priority' => 'HIGH',
                'perspective' => 'Internal Process',
                'weight' => 1.72,
                'primary_dept' => 'SCM',
                'all_depts' => ['SCM', 'AUD'],
                'notes' => 'Supply Chain',
            ],
            [
                'code' => 'G27',
                'title' => 'Purchase/supplier database created for all items',
                'dod' => 'Database live covering every item Kaldi\'s buys',
                'target' => 'Database live covering every item Kaldi\'s buys',
                'priority' => 'HIGH',
                'perspective' => 'Internal Process',
                'weight' => 1.72,
                'primary_dept' => 'SCM',
                'all_depts' => ['SCM', 'BI', 'IT'],
                'notes' => 'Supply Chain / BI',
            ],
            [
                'code' => 'G28',
                'title' => 'Raw material reception centralized at production (except milk, bottled bevs) with QA control',
                'dod' => 'Centralized reception running; quality + quantity signed at intake',
                'target' => 'Centralized reception running; quality + quantity signed at intake',
                'priority' => 'HIGH',
                'perspective' => 'Internal Process',
                'weight' => 1.72,
                'primary_dept' => 'QAR',
                'all_depts' => ['QAR', 'OPS', 'SCM'],
                'notes' => 'Production / QA / Operations',
            ],
            [
                'code' => 'G29',
                'title' => 'AI cameras at major areas + significant number of branches: reception, recipes, processes',
                'dod' => 'Cameras live; weekly exception report per branch feeding QA protocol',
                'target' => 'Cameras live; weekly exception report per branch feeding QA protocol',
                'priority' => 'HIGH',
                'perspective' => 'Internal Process',
                'weight' => 1.72,
                'primary_dept' => 'IT',
                'all_depts' => ['IT', 'BI', 'QAR', 'OPS'],
                'notes' => 'IT / BI',
            ],
            [
                'code' => 'G30',
                'title' => 'Improve reception at main store + production: standard quality, correct quantity, signed',
                'dod' => 'Reception protocol documented; discrepancies logged and falling',
                'target' => 'Reception protocol documented; discrepancies logged and falling',
                'priority' => 'HIGH',
                'perspective' => 'Internal Process',
                'weight' => 1.72,
                'primary_dept' => 'SCM',
                'all_depts' => ['SCM', 'QAR'],
                'notes' => 'Supply Chain',
            ],
            [
                'code' => 'G31',
                'title' => "CNET revised: essential steps cannot be skipped or edited (e.g., 'received' vouchers)",
                'dod' => 'Voucher acknowledgements locked system-wide',
                'target' => 'Voucher acknowledgements locked system-wide',
                'priority' => 'HIGH',
                'perspective' => 'Internal Process',
                'weight' => 1.72,
                'primary_dept' => 'IT',
                'all_depts' => ['IT'],
                'notes' => 'IT',
            ],
            [
                'code' => 'G32',
                'title' => 'Solve daily branch inventory counting issues (errors/dishonesty)',
                'dod' => 'Count accuracy verified by spot audit; cost and on-hand inventory trusted',
                'target' => 'Count accuracy verified by spot audit; inventory trusted',
                'priority' => 'HIGH',
                'perspective' => 'Internal Process',
                'weight' => 1.72,
                'primary_dept' => 'OPS',
                'all_depts' => ['OPS', 'FB', 'RND', 'AUD'],
                'notes' => 'Operations / F&B',
            ],
            [
                'code' => 'G33',
                'title' => 'Juice & hot beverage recipe training: consistent execution at branch',
                'dod' => 'QA inspections confirm recipe adherence at branch level',
                'target' => 'QA inspections confirm recipe adherence at branch level',
                'priority' => 'HIGH',
                'perspective' => 'Learning & Growth',
                'weight' => 1.72,
                'primary_dept' => 'FB',
                'all_depts' => ['FB', 'RND', 'TND', 'QAR'],
                'notes' => 'F&B / Training & Development',
            ],
            [
                'code' => 'G34',
                'title' => 'Theft control mechanism defined and running',
                'dod' => 'Named metric + protocol live; incidents tracked',
                'target' => 'Named metric + protocol live; incidents tracked',
                'priority' => 'HIGH',
                'perspective' => 'Internal Process',
                'weight' => 1.72,
                'primary_dept' => 'FB',
                'all_depts' => ['FB', 'RND', 'QAR', 'IT', 'AUD'],
                'notes' => 'F&B / QA / IT',
            ],
            [
                'code' => 'G35',
                'title' => "'Too Good To Go' discount program for items nearing damage",
                'dod' => 'Program live; wastage value recovered tracked monthly',
                'target' => 'Program live; wastage value recovered tracked monthly',
                'priority' => 'MEDIUM',
                'perspective' => 'Customer',
                'weight' => 1.72,
                'primary_dept' => 'MKT',
                'all_depts' => ['MKT', 'OPS'],
                'notes' => 'Marketing',
            ],
            [
                'code' => 'G36',
                'title' => 'Quotas controlled strictly by AI + regional managers, automated to limit wastage',
                'dod' => 'Quota adjustments automated at multiple daily points; wastage trending down',
                'target' => 'Quota adjustments automated at multiple daily points; wastage trending down',
                'priority' => 'HIGH',
                'perspective' => 'Internal Process',
                'weight' => 1.72,
                'primary_dept' => 'OPS',
                'all_depts' => ['OPS', 'BI', 'IT'],
                'notes' => 'Operations / BI',
            ],
            [
                'code' => 'G37',
                'title' => 'Loan money injected as working capital into 2 accounts: Expense Account + Cost Account',
                'dod' => 'Accounts open; opening balances funded from loan',
                'target' => 'Accounts open; opening balances funded from loan',
                'priority' => 'HIGH',
                'perspective' => 'Financial',
                'weight' => 1.72,
                'primary_dept' => 'FIN',
                'all_depts' => ['FIN'],
                'notes' => 'Finance',
            ],
            [
                'code' => 'G38',
                'title' => 'Separate Sales Account receives all revenue; only owner has access',
                'dod' => 'Sales account live; access restricted to owner',
                'target' => 'Sales account live; access restricted to owner',
                'priority' => 'HIGH',
                'perspective' => 'Financial',
                'weight' => 1.72,
                'primary_dept' => 'FIN',
                'all_depts' => ['FIN'],
                'notes' => 'Finance / Owner',
            ],
            [
                'code' => 'G39',
                'title' => 'Month-end: Expense + Cost accounts replenished from Sales minus profit',
                'dod' => 'Monthly cycle running; profit withdrawn first, budgets funded after',
                'target' => 'Monthly cycle running; profit withdrawn first, budgets funded after',
                'priority' => 'HIGH',
                'perspective' => 'Financial',
                'weight' => 1.72,
                'primary_dept' => 'FIN',
                'all_depts' => ['FIN'],
                'notes' => 'Finance',
            ],
            [
                'code' => 'G40',
                'title' => 'Finance owns accurate planning — replenishment from real monthly budget',
                'dod' => 'Planning accuracy tracked as a Finance KPI',
                'target' => 'Planning accuracy tracked as a Finance KPI',
                'priority' => 'HIGH',
                'perspective' => 'Financial',
                'weight' => 1.72,
                'primary_dept' => 'FIN',
                'all_depts' => ['FIN'],
                'notes' => 'Finance',
            ],
            [
                'code' => 'G41',
                'title' => 'Safeguards: 5% tolerance built into replenishment; 2-4 week cost float; profit draw method defined (fixed vs % of sales); CNET mirrors account separation; monthly cash calendar with fixed dates',
                'dod' => 'All 5 safeguards agreed in writing before month 1',
                'target' => 'All 5 safeguards agreed in writing before month 1',
                'priority' => 'HIGH',
                'perspective' => 'Financial',
                'weight' => 1.72,
                'primary_dept' => 'FIN',
                'all_depts' => ['FIN', 'IT'],
                'notes' => 'Finance / IT / Owner',
            ],
            [
                'code' => 'G42',
                'title' => 'Budget vs expense variance held at 5% or less; departments budget monthly; finance controls except emergencies',
                'dod' => 'Variance report monthly; any dept over 5% explains in person',
                'target' => 'Variance report monthly; any dept over 5% explains in person',
                'priority' => 'HIGH',
                'perspective' => 'Financial',
                'weight' => 1.72,
                'primary_dept' => 'FIN',
                'all_depts' => array_keys($deptMap),
                'notes' => 'Finance / All Depts',
            ],
            [
                'code' => 'G43',
                'title' => 'JDs + trackable KPIs for all staff; monthly evaluations with action (train or terminate)',
                'dod' => 'Every termination/promotion cites a KPI score; monthly evaluation pack delivered',
                'target' => 'Every termination/promotion cites a KPI score; monthly evaluation pack delivered',
                'priority' => 'HIGH',
                'perspective' => 'Learning & Growth',
                'weight' => 1.72,
                'primary_dept' => 'HR',
                'all_depts' => ['HR', 'OPS'],
                'notes' => 'HR / DGM',
            ],
            [
                'code' => 'G44',
                'title' => 'Updated organizational structure on CNET',
                'dod' => 'Structure live and current on CNET',
                'target' => 'Structure live and current on CNET',
                'priority' => 'MEDIUM',
                'perspective' => 'Internal Process',
                'weight' => 1.72,
                'primary_dept' => 'HR',
                'all_depts' => ['HR', 'IT'],
                'notes' => 'HR',
            ],
            [
                'code' => 'G45',
                'title' => 'Annual leave: all departments ensure staff take days off; no payout without justification',
                'dod' => 'Leave balance report monthly; payouts require written justification',
                'target' => 'Leave balance report monthly; payouts require written justification',
                'priority' => 'MEDIUM',
                'perspective' => 'Internal Process',
                'weight' => 1.72,
                'primary_dept' => 'HR',
                'all_depts' => array_keys($deptMap),
                'notes' => 'HR / All Depts',
            ],
            [
                'code' => 'G46',
                'title' => 'Fixed assets registered; maintenance per item tracked; replace when expense too high',
                'dod' => 'Asset register complete; replace-vs-repair threshold alerts firing',
                'target' => 'Asset register complete; replace-vs-repair threshold alerts firing',
                'priority' => 'HIGH',
                'perspective' => 'Financial',
                'weight' => 1.72,
                'primary_dept' => 'FIN',
                'all_depts' => ['FIN', 'LOG'],
                'notes' => 'Finance',
            ],
            [
                'code' => 'G47',
                'title' => 'Lease contract management: all agreements updated, renewed on time',
                'dod' => 'Zero expired leases; renewal calendar maintained',
                'target' => 'Zero expired leases; renewal calendar maintained',
                'priority' => 'HIGH',
                'perspective' => 'Financial',
                'weight' => 1.72,
                'primary_dept' => 'FIN',
                'all_depts' => ['FIN'],
                'notes' => 'Finance',
            ],
            [
                'code' => 'G48',
                'title' => 'Cross-training at branches: display and barista interchangeable, esp. small branches',
                'dod' => 'Small branches staffed with cross-trained teams; labor cost per branch falling',
                'target' => 'Small branches staffed with cross-trained teams; labor cost per branch falling',
                'priority' => 'HIGH',
                'perspective' => 'Learning & Growth',
                'weight' => 1.72,
                'primary_dept' => 'OPS',
                'all_depts' => ['OPS', 'TND'],
                'notes' => 'Operations / Training & Dev',
            ],
            [
                'code' => 'G49',
                'title' => 'Maintenance department moved under Logistics',
                'dod' => 'Reporting line changed; handover complete',
                'target' => 'Reporting line changed; handover complete',
                'priority' => 'HIGH',
                'perspective' => 'Internal Process',
                'weight' => 1.72,
                'primary_dept' => 'LOG',
                'all_depts' => ['LOG'],
                'notes' => 'Logistics',
            ],
            [
                'code' => 'G50',
                'title' => 'Maintenance providers audited; 3-month contracts with current prices and strict KPIs',
                'dod' => 'All providers on 3-month contracts; quarterly audit log kept',
                'target' => 'All providers on 3-month contracts; quarterly audit log kept',
                'priority' => 'HIGH',
                'perspective' => 'Internal Process',
                'weight' => 1.72,
                'primary_dept' => 'LOG',
                'all_depts' => ['LOG'],
                'notes' => 'Logistics',
            ],
            [
                'code' => 'G51',
                'title' => 'COGS dashboard (SIV-based) with cost % vs targets per department/branch',
                'dod' => 'Live dashboard; drift flagged automatically',
                'target' => 'Live dashboard; drift flagged automatically',
                'priority' => 'HIGH',
                'perspective' => 'Financial',
                'weight' => 1.72,
                'primary_dept' => 'BI',
                'all_depts' => ['BI', 'SCM', 'FIN', 'IT'],
                'notes' => 'BI / Supply Chain / Finance',
            ],
            [
                'code' => 'G52',
                'title' => 'Budget vs expense variance dashboard per department (5% rule)',
                'dod' => 'Monthly pack feeds account replenishment decision',
                'target' => 'Monthly pack feeds account replenishment decision',
                'priority' => 'HIGH',
                'perspective' => 'Financial',
                'weight' => 1.72,
                'primary_dept' => 'BI',
                'all_depts' => ['BI', 'FIN', 'IT'],
                'notes' => 'BI / Finance',
            ],
            [
                'code' => 'G53',
                'title' => 'Staff KPI & evaluation tracking dashboard',
                'dod' => 'Monthly scores visible; improvement/termination triggers automated',
                'target' => 'Monthly scores visible; improvement/termination triggers automated',
                'priority' => 'HIGH',
                'perspective' => 'Learning & Growth',
                'weight' => 1.72,
                'primary_dept' => 'BI',
                'all_depts' => ['BI', 'HR', 'IT'],
                'notes' => 'BI / HR',
            ],
            [
                'code' => 'G54',
                'title' => 'Fixed asset & maintenance cost tracker with replace-vs-repair alerts',
                'dod' => 'Cumulative spend per asset visible; alerts firing',
                'target' => 'Cumulative spend per asset visible; alerts firing',
                'priority' => 'HIGH',
                'perspective' => 'Financial',
                'weight' => 1.72,
                'primary_dept' => 'BI',
                'all_depts' => ['BI', 'FIN', 'LOG', 'IT'],
                'notes' => 'BI / Finance',
            ],
            [
                'code' => 'G55',
                'title' => 'AI camera analytics: reception, recipe adherence, process, theft, quota signals',
                'dod' => 'Weekly exception report per branch feeding reward/probation protocol',
                'target' => 'Weekly exception report per branch feeding reward/probation protocol',
                'priority' => 'HIGH',
                'perspective' => 'Internal Process',
                'weight' => 1.72,
                'primary_dept' => 'BI',
                'all_depts' => ['BI', 'IT', 'QAR', 'OPS'],
                'notes' => 'BI / IT / QA',
            ],
            [
                'code' => 'G56',
                'title' => 'Branch inventory data quality: validation rules, camera cross-checks, spot audits',
                'dod' => 'Count data trusted; error rate published monthly',
                'target' => 'Count data trusted; error rate published monthly',
                'priority' => 'HIGH',
                'perspective' => 'Internal Process',
                'weight' => 1.72,
                'primary_dept' => 'BI',
                'all_depts' => ['BI', 'OPS', 'AUD', 'IT'],
                'notes' => 'BI / Operations',
            ],
            [
                'code' => 'G57',
                'title' => 'Loyalty/customer database structured for analysis (with subscription + birthday data)',
                'dod' => 'Customer behavior reports produced, not just stored data',
                'target' => 'Customer behavior reports produced, not just stored data',
                'priority' => 'HIGH',
                'perspective' => 'Customer',
                'weight' => 1.72,
                'primary_dept' => 'BI',
                'all_depts' => ['BI', 'MKT', 'IT'],
                'notes' => 'BI / Marketing / IT',
            ],
            [
                'code' => 'G58',
                'title' => 'BI operating rhythm: daily alerts, weekly branch reports, monthly packs, quarterly benchmarks',
                'dod' => 'Rhythm documented and delivering on schedule',
                'target' => 'Rhythm documented and delivering on schedule',
                'priority' => 'HIGH',
                'perspective' => 'Internal Process',
                'weight' => 1.72,
                'primary_dept' => 'BI',
                'all_depts' => ['BI', 'IT'],
                'notes' => 'BI',
            ],
        ];

        $ethiopianMonths = [
            'Meskerem', 'Tikimt', 'Hidar', 'Tahsas', 'Tir', 'Yakatit',
            'Megabit', 'Miazia', 'Genbot', 'Sene', 'Hamle', 'Nehase', 'Pagume'
        ];

        $insertGoalStmt = $pdo->prepare("
            INSERT INTO `annual_goals` (
                `goal_code`, `title`, `definition_of_done`, `responsible_department_id`,
                `annual_target`, `priority`, `notes`, `status`, `year`, `perspective`, `weight`, `created_by`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'ACTIVE', '2019 E.C.', ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                `title` = VALUES(`title`),
                `definition_of_done` = VALUES(`definition_of_done`),
                `responsible_department_id` = VALUES(`responsible_department_id`),
                `annual_target` = VALUES(`annual_target`),
                `priority` = VALUES(`priority`),
                `notes` = VALUES(`notes`),
                `perspective` = VALUES(`perspective`),
                `weight` = VALUES(`weight`)
        ");

        $insertPivotStmt = $pdo->prepare("
            INSERT IGNORE INTO `annual_goal_departments` (`annual_goal_id`, `department_id`, `is_primary`)
            VALUES (?, ?, ?)
        ");

        $insertActStmt = $pdo->prepare("
            INSERT IGNORE INTO `monthly_strategy_activations` (
                `annual_goal_id`, `department_id`, `year`, `month`, `active`, `activated_by`, `activated_at`, `updated_by`
            ) VALUES (?, ?, '2019 E.C.', ?, 'YES', ?, NOW(), ?)
        ");

        foreach ($goals as $g) {
            $primaryDeptCode = $g['primary_dept'];
            $primaryDeptId = $deptMap[$primaryDeptCode] ?? ($deptMap['OPS'] ?? 1);

            $insertGoalStmt->execute([
                $g['code'], $g['title'], $g['dod'], $primaryDeptId,
                $g['target'], $g['priority'], $g['notes'],
                $g['perspective'], $g['weight'], $userMap['admin'] ?? 1
            ]);
            
            // Get goal id
            $goalId = $pdo->query("SELECT id FROM annual_goals WHERE goal_code = '{$g['code']}' AND year = '2019 E.C.'")->fetchColumn();
            if (!$goalId) continue;

            $linkedDeptIds = [];
            foreach ($g['all_depts'] as $dCode) {
                if (isset($deptMap[$dCode])) {
                    $linkedDeptIds[] = $deptMap[$dCode];
                }
            }
            $linkedDeptIds = array_unique($linkedDeptIds);

            foreach ($linkedDeptIds as $dId) {
                $isPrimary = ($dId === $primaryDeptId) ? 1 : 0;
                $insertPivotStmt->execute([$goalId, $dId, $isPrimary]);

                foreach ($ethiopianMonths as $m) {
                    $insertActStmt->execute([$goalId, $dId, $m, $userMap['admin'] ?? 1, $userMap['admin'] ?? 1]);
                }
            }
        }

        // 8. Fetch Seeded Goal IDs for Sample Plans
        $goalIds = $pdo->query("SELECT goal_code, id FROM annual_goals WHERE year = '2019 E.C.'")->fetchAll(PDO::FETCH_KEY_PAIR);

        // 9. Seed Sample Monthly Plans for IT Department (Nehase 2019 E.C.)
        // Strategy Plan:
        $insertMonthlyStmt = $pdo->prepare("
            INSERT IGNORE INTO `monthly_plans` (
                `year`, `month`, `department_id`, `plan_type`, `annual_goal_id`, `title`, `monthly_target`,
                `definition_of_done`, `target_percentage`, `priority`, `responsible_person`, `created_by`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        // Plan 1: Strategy (G02)
        $insertMonthlyStmt->execute([
            '2019 E.C.', 'Nehase', $deptMap['IT'], 'STRATEGY', $goalIds['G02'],
            'Develop customer registration & loyalty backend API', '30% Milestone',
            'REST API endpoints tested and integrated with sandbox POS', 30, 'HIGH',
            'Yonas Alemayehu', $userMap['it_head'] ?? 1
        ]);
        $monthlyPlanId1 = $pdo->lastInsertId();

        // Plan 2: Operational (No annual goal)
        $insertMonthlyStmt->execute([
            '2019 E.C.', 'Nehase', $deptMap['IT'], 'OPERATIONAL', null,
            'Branch Hardware & Network Preventive Maintenance (PM)', 'Audit 15 selected branches',
            'All POS terminals, thermal printers, and network switches cleaned and diagnosed', 100, 'MEDIUM',
            'Henok Girma', $userMap['it_head'] ?? 1
        ]);
        $monthlyPlanId2 = $pdo->lastInsertId();

        // 10. Seed System Settings
        $settings = [
            ['weekly_deadline_day', 'Monday', 'DEADLINE', 'Day of the week when weekly reports are due'],
            ['weekly_deadline_time', '12:00', 'DEADLINE', 'Cutoff time for weekly reports (24h format)'],
            ['escalation_level_1_rule', '1 missed week -> Department Head Warning Reminder', 'ESCALATION', 'Level 1 escalation trigger'],
            ['escalation_level_2_rule', '2 consecutive missed weeks -> HR & Operations Alert', 'ESCALATION', 'Level 2 escalation trigger'],
            ['escalation_level_3_rule', '3+ consecutive missed weeks -> General Manager Escalation', 'ESCALATION', 'Level 3 escalation trigger'],
            ['escalation_recipients_hr', 'hr@kaldiscoffee.com', 'ESCALATION', 'HR recipients for level 2 escalations'],
            ['escalation_recipients_gm', 'gm@kaldiscoffee.com', 'ESCALATION', 'Management recipients for level 3 escalations'],
            ['telegram_bot_token', '', 'TELEGRAM', 'Telegram Bot API Token (from @BotFather)'],
            ['telegram_bot_username', 'KaldisPlannerBot', 'TELEGRAM', 'Telegram Bot Username'],
            ['current_planning_year', '2019 E.C.', 'PLANNING', 'Active organizational planning year'],
            ['current_planning_month', 'Nehase', 'PLANNING', 'Active planning month']
        ];
        $insertSettingStmt = $pdo->prepare("INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `setting_group`, `description`) VALUES (?, ?, ?, ?)");
        foreach ($settings as $s) {
            $insertSettingStmt->execute([$s[0], $s[1], $s[2], $s[3]]);
        }

        // Auto-update .env if it exists
        $envPath = __DIR__ . '/.env';
        if (file_exists($envPath)) {
            $currentEnv = file_get_contents($envPath);
            $replacements = [
                'DB_HOST' => $config['db_host'],
                'DB_PORT' => $config['db_port'],
                'DB_DATABASE' => $config['db_name'],
                'DB_USERNAME' => $config['db_user'],
                'DB_PASSWORD' => '"' . addcslashes($config['db_pass'], '"\\$') . '"',
            ];
            foreach ($replacements as $rk => $rv) {
                if (preg_match("/^{$rk}=.*/m", $currentEnv)) {
                    $currentEnv = preg_replace("/^{$rk}=.*/m", "{$rk}={$rv}", $currentEnv);
                } else {
                    $currentEnv .= "\n{$rk}={$rv}";
                }
            }
            file_put_contents($envPath, $currentEnv);
        }

        // Create installation lock file
        file_put_contents($lockFile, "Installed on " . date('Y-m-d H:i:s') . "\n");

        if (php_sapi_name() === 'cli') {
            echo "Installation completed successfully!\n";
            echo "Admin user: admin / Password: admin123\n";
            exit(0);
        }

        $installedSuccessfully = true;
    } catch (Exception $e) {
        if (php_sapi_name() === 'cli') {
            echo "Installation failed: " . $e->getMessage() . "\n";
            exit(1);
        }
        $errors[] = "Installation failed: " . $e->getMessage();
    }
}

// If CLI without install action, trigger it
if (php_sapi_name() === 'cli' && empty($installedSuccessfully) && empty($errors)) {
    // Already handled above or run directly
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Kaldis Coffee PLC Planning System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; color: #1e293b; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; min-height: 100vh; }
        .install-card { background: #ffffff; color: #1e293b; border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); max-width: 650px; margin: 40px auto; overflow: hidden; border: 1px solid #e2e8f0; }
        .install-header { background: #faf7f2; color: #21130d; padding: 30px 25px; border-bottom: 3px solid #c88e3b; text-align: center; }
        .install-body { padding: 30px; }
        .btn-install { background: #c88e3b; color: #ffffff; font-weight: bold; border: none; padding: 12px; }
        .btn-install:hover { background: #b1792a; color: #ffffff; }
    </style>
</head>
<body>
<div class="container">
    <div class="install-card">
        <div class="install-header">
            <img src="assets/images/logo.png" alt="Kaldis Coffee" height="75" class="rounded-circle shadow-sm mb-2 bg-white p-1 border border-2 border-warning">
            <h3 class="mb-1 fw-bold text-dark">KALDIS COFFEE PLC</h3>
            <p class="mb-0 text-muted small fw-semibold">Strategic Planning & Performance Management System Setup</p>
        </div>
        <div class="install-body">
            <?php if (!empty($installedSuccessfully)): ?>
                <div class="alert alert-success">
                    <h4><i class="fas fa-check-circle me-2"></i> System Installed Successfully!</h4>
                    <p class="mb-2">The database tables, default roles, reason categories, departments, and seed data have been initialized.</p>
                    <hr>
                    <p class="mb-1"><strong>Default Super Admin Account:</strong></p>
                    <ul>
                        <li>Username: <code>admin</code></li>
                        <li>Password: <code>admin123</code></li>
                    </ul>
                    <p class="mb-3"><strong>General Manager Account:</strong></p>
                    <ul>
                        <li>Username: <code>gm</code></li>
                        <li>Password: <code>admin123</code></li>
                    </ul>
                    <a href="login.php" class="btn btn-install w-100"><i class="fas fa-right-to-bracket me-2"></i> Proceed to Login</a>
                </div>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-triangle-exclamation me-2"></i> Errors detected:</h6>
                        <ul class="mb-0">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <h5>System Environment Verification</h5>
                <ul class="list-group mb-4">
                    <?php foreach ($successMessages as $msg): ?>
                        <li class="list-group-item list-group-item-success d-flex align-items-center">
                            <i class="fas fa-check text-success me-2"></i> <?= htmlspecialchars($msg) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($canInstall): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="install">

                        <div class="card mb-4 bg-light border-0 shadow-sm" style="border-radius: 10px;">
                            <div class="card-body p-3">
                                <h6 class="card-title fw-bold text-dark mb-3"><i class="fas fa-database text-warning me-2"></i> Database Connection Settings</h6>
                                <div class="row g-2">
                                    <div class="col-8">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Host</label>
                                        <input type="text" name="db_host" class="form-control form-control-sm" value="<?= htmlspecialchars($config['db_host']) ?>" required>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Port</label>
                                        <input type="number" name="db_port" class="form-control form-control-sm" value="<?= htmlspecialchars((string)$config['db_port']) ?>" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Database Name</label>
                                        <input type="text" name="db_name" class="form-control form-control-sm" value="<?= htmlspecialchars($config['db_name']) ?>" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Database User</label>
                                        <input type="text" name="db_user" class="form-control form-control-sm" value="<?= htmlspecialchars($config['db_user']) ?>" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Database Password</label>
                                        <input type="password" name="db_pass" class="form-control form-control-sm" value="<?= htmlspecialchars($config['db_pass']) ?>" placeholder="Enter database password" required>
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-2">Values auto-loaded from <code>.env</code>. You can adjust them directly above before installing.</small>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-install w-100 py-3 fs-5">
                            <i class="fas fa-play-circle me-2"></i> Run Automated Installation & Seeder
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning">
                        Please correct the environment requirements above before proceeding.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>

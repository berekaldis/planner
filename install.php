<?php
/**
 * 1-Click Interactive Web & CLI Installer
 * Kaldis Coffee PLC - Strategic Planning & Reporting System
 */

$lockFile = __DIR__ . '/install.lock';
if (file_exists($lockFile)) {
    die('System is already installed. To re-install, delete install.lock first.');
}

$config = require __DIR__ . '/config/config.php';
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
        // Connect to server (without DB)
        $dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $config['db_host'], $config['db_port'], $config['db_charset']);
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Create Database if not exists
        $dbName = $config['db_name'];
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbName}`");

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

        -- Update departments foreign key for head_user_id
        ALTER TABLE `departments` ADD CONSTRAINT `fk_dept_head` 
            FOREIGN KEY (`head_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;

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
            `monthly_plan_id` INT NOT NULL,
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
            FOREIGN KEY (`monthly_plan_id`) REFERENCES `monthly_plans`(`id`) ON DELETE CASCADE,
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
            `remarks` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`),
            FOREIGN KEY (`submitted_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            UNIQUE KEY `uk_submission_period` (`department_id`, `year`, `month`, `week_number`)
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
                    // Ignore duplicate constraint if re-run
                    if (!str_contains($ex->getMessage(), 'already exists') && !str_contains($ex->getMessage(), 'Duplicate foreign key')) {
                        throw $ex;
                    }
                }
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
            ['name' => 'Internal Audit & Compliance', 'code' => 'AUD']
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

        // 7. Seed Sample Annual Strategy Goals (2019 E.C.)
        $annualGoals = [
            [
                'code' => 'G01',
                'title' => 'Open 5 new flagship retail stores',
                'dod' => '5 new stores fully furnished, staffed, and commercially operational.',
                'dept_id' => $deptMap['OPS'],
                'target' => '5 Stores',
                'priority' => 'HIGH',
                'year' => '2019 E.C.'
            ],
            [
                'code' => 'G02',
                'title' => 'Launch Digital Customer Loyalty & Rewards Platform',
                'dod' => 'Mobile loyalty app integrated with POS across all Addis Ababa cafes.',
                'dept_id' => $deptMap['IT'],
                'target' => '100% Addis Ababa Branches Live',
                'priority' => 'HIGH',
                'year' => '2019 E.C.'
            ],
            [
                'code' => 'G03',
                'title' => 'Expand Delivery Platform Partnerships',
                'dod' => 'Active delivery integration with 3 leading aggregator apps.',
                'dept_id' => $deptMap['MKT'],
                'target' => '3 Aggregators Live',
                'priority' => 'MEDIUM',
                'year' => '2019 E.C.'
            ],
            [
                'code' => 'G04',
                'title' => 'Supply Chain ERP & Inventory Automation',
                'dod' => 'Automated requisition and stock tracking from central warehouse to cafes.',
                'dept_id' => $deptMap['SCM'],
                'target' => 'Central Warehouse & 25 Hubs',
                'priority' => 'HIGH',
                'year' => '2019 E.C.'
            ],
            [
                'code' => 'G05',
                'title' => 'Core IT Infrastructure & High Availability Upgrade',
                'dod' => 'Redundant cloud failover and branch firewall security deployment.',
                'dept_id' => $deptMap['IT'],
                'target' => '99.9% Uptime Across Network',
                'priority' => 'HIGH',
                'year' => '2019 E.C.'
            ],
        ];

        $insertGoalStmt = $pdo->prepare("
            INSERT IGNORE INTO `annual_goals` (`goal_code`, `title`, `definition_of_done`, `responsible_department_id`, `annual_target`, `priority`, `year`, `created_by`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($annualGoals as $g) {
            $insertGoalStmt->execute([
                $g['code'], $g['title'], $g['dod'], $g['dept_id'], $g['target'], $g['priority'], $g['year'], $userMap['admin'] ?? 1
            ]);
        }

        // 8. Seed Sample Monthly Strategy Activations (for Nehase 2019 E.C.)
        $goalIds = $pdo->query("SELECT goal_code, id FROM annual_goals WHERE year = '2019 E.C.'")->fetchAll(PDO::FETCH_KEY_PAIR);
        $activations = [
            ['goal_id' => $goalIds['G01'], 'dept_id' => $deptMap['OPS'], 'active' => 'YES'],
            ['goal_id' => $goalIds['G02'], 'dept_id' => $deptMap['IT'], 'active' => 'YES'],
            ['goal_id' => $goalIds['G03'], 'dept_id' => $deptMap['MKT'], 'active' => 'NO'],
            ['goal_id' => $goalIds['G04'], 'dept_id' => $deptMap['SCM'], 'active' => 'YES'],
            ['goal_id' => $goalIds['G05'], 'dept_id' => $deptMap['IT'], 'active' => 'NO'],
        ];

        $insertActStmt = $pdo->prepare("
            INSERT IGNORE INTO `monthly_strategy_activations` (`annual_goal_id`, `department_id`, `year`, `month`, `active`, `activated_by`)
            VALUES (?, ?, '2019 E.C.', 'Nehase', ?, ?)
        ");
        foreach ($activations as $act) {
            $insertActStmt->execute([$act['goal_id'], $act['dept_id'], $act['active'], $userMap['gm'] ?? 1]);
        }

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

        // Create installation lock file
        file_put_contents($lockFile, "Installed on " . date('Y-m-d H:i:s') . "\n");

        if (php_sapi_name() === 'cli') {
            echo "Installation completed successfully!\n";
            echo "Admin user: admin / Password: admin123\n";
            exit(0);
        }

        $installedSuccessfully = true;
    } catch (Exception $e) {
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

                <div class="card mb-4 bg-light">
                    <div class="card-body">
                        <h6 class="card-title fw-bold"><i class="fas fa-database me-2"></i> Target Database Configuration</h6>
                        <div class="small">
                            <div><strong>Host:</strong> <?= htmlspecialchars($config['db_host']) ?>:<?= htmlspecialchars((string)$config['db_port']) ?></div>
                            <div><strong>Database:</strong> <?= htmlspecialchars($config['db_name']) ?></div>
                            <div><strong>User:</strong> <?= htmlspecialchars($config['db_user']) ?></div>
                        </div>
                    </div>
                </div>

                <?php if ($canInstall): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="install">
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

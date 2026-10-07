<?php
/**
 * Cron: Background Notifications & Maintenance
 * Kaldis Coffee PLC
 */

require_once __DIR__ . '/../config/database.php';

$db = Database::getConnection();

// Clean up sessions older than 7 days
$db->query("DELETE FROM sessions WHERE last_activity < (UNIX_TIMESTAMP() - 604800)");

// Clean up read notifications older than 30 days
$db->query("DELETE FROM notifications WHERE is_read = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");

echo "Maintenance and notification processing completed.\n";

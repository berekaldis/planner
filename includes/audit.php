<?php
/**
 * Audit Logging Helper
 * Kaldis Coffee PLC
 */

require_once __DIR__ . '/../config/database.php';

function audit_log(
    ?int $userId,
    string $action,
    string $module,
    ?string $recordId = null,
    mixed $oldValue = null,
    mixed $newValue = null
): void {
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO audit_logs (
                user_id, action, module, record_id, old_values, new_values, ip_address, user_agent, created_at
            ) VALUES (
                :user_id, :action, :module, :record_id, :old_values, :new_values, :ip_address, :user_agent, NOW()
            )
        ");

        $oldJson = $oldValue !== null ? (is_string($oldValue) ? $oldValue : json_encode($oldValue, JSON_UNESCAPED_UNICODE)) : null;
        $newJson = $newValue !== null ? (is_string($newValue) ? $newValue : json_encode($newValue, JSON_UNESCAPED_UNICODE)) : null;

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'CLI/Cron', 0, 255);

        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':module' => $module,
            ':record_id' => $recordId,
            ':old_values' => $oldJson,
            ':new_values' => $newJson,
            ':ip_address' => $ipAddress,
            ':user_agent' => $userAgent,
        ]);
    } catch (Exception $e) {
        // Fail quietly so audit logging failure does not block the primary user action
        error_log("Audit log failed: " . $e->getMessage());
    }
}

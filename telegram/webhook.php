<?php
/**
 * Telegram Bot Webhook Endpoint
 * Kaldis Coffee PLC
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/telegram.php';
require_once __DIR__ . '/commands.php';

$content = file_get_contents('php://input');
if (empty($content)) {
    echo json_encode(['ok' => false, 'error' => 'Empty request body']);
    exit;
}

$update = json_decode($content, true);
if (!is_array($update)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

try {
    $db = Database::getConnection();
    $notifier = new TelegramNotifier();
    $handler = new TelegramCommandHandler($db, $notifier);

    if (isset($update['message'])) {
        $msg = $update['message'];
        $text = $msg['text'] ?? ($msg['caption'] ?? '[non-text update]');
        $fromId = (string)($msg['from']['id'] ?? '');

        // Log to telegram_messages table safely
        try {
            $db->prepare("
                INSERT INTO telegram_messages (message_id, telegram_user_id, message_text, direction, status, created_at)
                VALUES (?, ?, ?, 'INCOMING', 'RECEIVED', NOW())
            ")->execute([
                $msg['message_id'] ?? 0,
                $fromId,
                $text
            ]);
        } catch (\Throwable $te) {
            // Ignore log duplicate errors if any
        }

        $handler->handle($msg);
    }

    echo json_encode(['ok' => true]);
} catch (\Throwable $e) {
    error_log("Telegram webhook error: " . $e->getMessage());
    echo json_encode(['ok' => true, 'warning' => $e->getMessage()]);
}

<?php
/**
 * Telegram Bot Webhook Endpoint
 * Kaldis Coffee PLC
 */

// Must be set before any output — functions.php (loaded via database.php) calls
// ob_start() and session_start() with guards, which are harmless here.
header('Content-Type: application/json');

// commands.php already requires database.php and telegram.php — load once.
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
        $msg    = $update['message'];
        $text   = $msg['text'] ?? ($msg['caption'] ?? '[non-text update]');
        $fromId = (string)($msg['from']['id'] ?? '');

        // Log incoming message — wrapped so errors never block the bot response
        try {
            $db->prepare("
                INSERT INTO telegram_messages
                    (message_id, telegram_user_id, message_text, direction, status, created_at)
                VALUES (?, ?, ?, 'INCOMING', 'RECEIVED', NOW())
            ")->execute([
                $msg['message_id'] ?? 0,
                $fromId,
                $text,
            ]);
        } catch (\Throwable $te) {
            error_log("telegram_messages log error: " . $te->getMessage());
        }

        $handler->handle($msg);
    }

    echo json_encode(['ok' => true]);
} catch (\Throwable $e) {
    error_log(
        "Telegram webhook error: " . $e->getMessage() .
        " in " . $e->getFile() . ":" . $e->getLine()
    );
    // Always return 200 OK to Telegram so it does not retry endlessly
    echo json_encode(['ok' => true, 'warning' => $e->getMessage()]);
}

<?php
/**
 * Telegram Bot CLI Long-Polling Runner
 * Kaldis Coffee PLC
 *
 * Usage: php telegram/bot.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/telegram.php';
require_once __DIR__ . '/commands.php';

$db = Database::getConnection();
$notifier = new TelegramNotifier();

if (!$notifier->isConfigured()) {
    echo "Error: Telegram Bot Token is not configured in config/config.php\n";
    exit(1);
}

echo "====================================================\n";
echo "Kaldis Coffee Telegram Bot Runner (CLI Polling Mode)\n";
echo "====================================================\n";
echo "Listening for updates from Telegram... (Press Ctrl+C to stop)\n";

$handler = new TelegramCommandHandler($db, $notifier);
$offset = 0;

// Delete any active webhook first so getUpdates works
$notifier->deleteWebhook();

while (true) {
    $res = $notifier->getUpdates($offset);

    if (!empty($res['ok']) && !empty($res['result'])) {
        foreach ($res['result'] as $update) {
            $offset = $update['update_id'] + 1;

            if (isset($update['message'])) {
                $msg = $update['message'];
                $chatId = $msg['chat']['id'] ?? '';
                $text = $msg['text'] ?? '';
                $user = $msg['from']['first_name'] ?? 'User';

                echo "[" . date('H:i:s') . "] Message from {$user} ({$chatId}): {$text}\n";

                $handler->handle($msg);
            }
        }
    }

    sleep(2);
}

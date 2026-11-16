<?php
/**
 * Telegram Bot Webhook Endpoint
 * Kaldis Coffee PLC
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$content = file_get_contents('php://input');
if (empty($content)) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode([
            'ok' => true,
            'message' => 'Kaldis Coffee Telegram Webhook endpoint is active and listening.',
            'timestamp' => date('c'),
        ]);
        exit;
    }
    echo json_encode(['ok' => false, 'error' => 'Empty request body']);
    exit;
}

$update = json_decode($content, true);
if (!is_array($update)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

try {
    $service = new \App\Services\TelegramService();
    $service->handleUpdate($update);
    echo json_encode(['ok' => true]);
} catch (\Throwable $e) {
    \Illuminate\Support\Facades\Log::error("telegram/webhook.php error: " . $e->getMessage(), [
        'exception' => $e,
    ]);
    // Always return 200 OK to Telegram so it does not retry endlessly
    echo json_encode(['ok' => true, 'warning' => $e->getMessage()]);
}

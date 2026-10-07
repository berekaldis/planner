<?php
/**
 * Telegram Bot API Client
 * Kaldis Coffee PLC - Strategic Planning & Reporting System
 */

class TelegramNotifier {
    private string $botToken;
    private string $apiUrl;

    public function __construct(?string $token = null) {
        if (!empty($token)) {
            $this->botToken = $token;
        } else {
            $tokenFromDb = '';
            if (class_exists('Database')) {
                try {
                    $db = Database::getConnection();
                    $stmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'telegram_bot_token'");
                    $tokenFromDb = trim((string)$stmt->fetchColumn());
                } catch (\Throwable $e) {}
            }

            if (!empty($tokenFromDb)) {
                $this->botToken = $tokenFromDb;
            } else {
                $config = file_exists(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];
                $this->botToken = $config['telegram_bot_token'] ?? getenv('TELEGRAM_BOT_TOKEN') ?: '';
            }
        }
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}/";
    }

    public function isConfigured(): bool {
        return !empty($this->botToken);
    }

    /**
     * Send a text message to a specific chat ID
     */
    public function sendMessage(string|int $chatId, string $text, ?array $replyMarkup = null): array {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'description' => 'Telegram Bot Token not configured'];
        }

        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        if ($replyMarkup !== null) {
            $params['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->request('sendMessage', $params);
    }

    /**
     * Send document / file
     */
    public function sendDocument(string|int $chatId, string $filePath, string $caption = ''): array {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'description' => 'Telegram Bot Token not configured'];
        }

        if (!file_exists($filePath)) {
            return ['ok' => false, 'description' => 'File not found'];
        }

        $params = [
            'chat_id' => $chatId,
            'caption' => $caption,
            'document' => new CURLFile($filePath),
            'parse_mode' => 'HTML',
        ];

        return $this->request('sendDocument', $params, true);
    }

    /**
     * Set Webhook URL
     */
    public function setWebhook(string $url): array {
        return $this->request('setWebhook', ['url' => $url]);
    }

    /**
     * Delete Webhook
     */
    public function deleteWebhook(): array {
        return $this->request('deleteWebhook', []);
    }

    /**
     * Get Webhook Info
     */
    public function getWebhookInfo(): array {
        return $this->request('getWebhookInfo', []);
    }

    /**
     * Get updates manually (Long Polling)
     */
    public function getUpdates(int $offset = 0, int $limit = 100): array {
        return $this->request('getUpdates', [
            'offset' => $offset,
            'limit' => $limit,
            'timeout' => 5
        ]);
    }

    /**
     * Internal cURL executor
     */
    private function request(string $method, array $params = [], bool $isMultipart = false): array {
        $ch = curl_init($this->apiUrl . $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($isMultipart) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['ok' => false, 'description' => 'cURL Error: ' . $error];
        }

        $result = json_decode($response, true);
        return is_array($result) ? $result : ['ok' => false, 'description' => 'Invalid JSON from Telegram API'];
    }
}
